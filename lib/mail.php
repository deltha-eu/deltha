<?php
/** lib/mail.php — envio de correo del sistema. Texto plano, remitente fijo.
 *  Sin [mail] en config.ini usa mail() del servidor; con smtp_host usa SMTP (STARTTLS o SSL, AUTH LOGIN). */
declare(strict_types=1);

function send_mail(string $to, string $subject, string $body): bool {
    global $ini;
    $from = (string)cfg('mail_from', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $name = (string)cfg('site_name', 'Deltha');
    $m = $ini['mail'] ?? [];
    if (!empty($m['smtp_host'])) return smtp_send($m, $from, $name, $to, $subject, $body);
    $headers = "From: =?UTF-8?B?" . base64_encode($name) . "?= <$from>\r\nReply-To: $from\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
    return @mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, $headers, "-f$from");
}

/** Cliente SMTP minimo. config.ini [mail]: smtp_host, smtp_port (587 STARTTLS | 465 SSL), smtp_user, smtp_pass */
function smtp_send(array $m, string $from, string $name, string $to, string $subject, string $body): bool {
    $host = $m['smtp_host']; $port = (int)($m['smtp_port'] ?? 587);
    $ssl = $port === 465;
    $fp = @stream_socket_client(($ssl ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $errstr, 10);
    if (!$fp) { error_log("smtp: $errstr"); return false; }
    stream_set_timeout($fp, 10);
    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 515)) !== false) { $out .= $line; if (!isset($line[3]) || $line[3] !== '-') break; }
        return $out;
    };
    $cmd = function (string $c, string $ok) use ($fp, $read): bool {
        fwrite($fp, $c . "\r\n"); $r = $read();
        if (!str_starts_with($r, $ok)) { error_log("smtp: $c -> " . trim($r)); return false; }
        return true;
    };
    $me = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!str_starts_with($read(), '220')) return false;
    if (!$cmd("EHLO $me", '250')) return false;
    if (!$ssl) {
        if (!$cmd('STARTTLS', '220')) return false;
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return false;
        if (!$cmd("EHLO $me", '250')) return false;
    }
    if (!empty($m['smtp_user'])) {
        if (!$cmd('AUTH LOGIN', '334') || !$cmd(base64_encode($m['smtp_user']), '334') || !$cmd(base64_encode($m['smtp_pass'] ?? ''), '235')) return false;
    }
    if (!$cmd("MAIL FROM:<$from>", '250') || !$cmd("RCPT TO:<$to>", '250') || !$cmd('DATA', '354')) return false;
    $hdr = "From: =?UTF-8?B?" . base64_encode($name) . "?= <$from>\r\nTo: <$to>\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
         . "Date: " . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(8)) . "@$me>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
    $data = $hdr . "\r\n" . preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $body));
    $ok = $cmd($data . "\r\n.", '250');
    $cmd('QUIT', '221');
    fclose($fp);
    return $ok;
}

/* ---- activacion de cuenta ---------------------------------------- */
function email_domain(string $email): string { return mb_strtolower(substr(strrchr($email, '@') ?: '', 1)); }

/** Dominio vetado por moderacion (o sin registros MX)? Devuelve el motivo o null. */
function email_rejected(PDO $pdo, string $email): ?string {
    $d = email_domain($email);
    if ($d === '') return 'Correo no válido.';
    $st = $pdo->prepare("SELECT 1 FROM banned_domains WHERE domain = ?"); $st->execute([$d]);
    if ($st->fetchColumn()) return 'No aceptamos cuentas de ese dominio de correo.';
    if (function_exists('checkdnsrr') && !checkdnsrr($d . '.', 'MX') && !checkdnsrr($d . '.', 'A')) return 'Ese dominio de correo no existe.';
    return null;
}

/** Genera token, lo guarda y envia el correo de activacion. Texto fijo: nada del usuario viaja en el correo. */
function send_activation(PDO $pdo, int $uid, string $email): bool {
    $token = bin2hex(random_bytes(16));
    $pdo->prepare("UPDATE users SET token = ?, token_sent = NOW() WHERE user_id = ?")->execute([$token, $uid]);
    $site = (string)cfg('site_name', 'Deltha');
    $url  = rtrim((string)cfg('site_url', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/') . "/activar.php?u=$uid&t=$token";
    $body = "Hola.\n\nAlguien (probablemente tú) ha creado una cuenta en $site con esta dirección de correo.\n\n"
          . "Para activarla, abre este enlace en las próximas " . (int)cfg('token_hours', 48) . " horas:\n\n$url\n\n"
          . "Si no has sido tú, no hagas nada: la cuenta no se activará y este correo no se repetirá.\n\n— $site\n";
    return send_mail($email, "Activa tu cuenta en $site", $body);
}
