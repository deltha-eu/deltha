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

/* ---- dominios de correo: lista negra, lista blanca y comprobacion de la web del dominio -------------------------
 * Al registrarse (email_rejected):
 *   1. banned_domains (lista negra: moderacion, desechables conocidos y los que no pasan la comprobacion) -> no se crea la cuenta.
 *   2. allowed_domains (lista blanca: proveedores reconocidos y revisiones manuales de moderacion) -> se admite sin mas.
 *   3. Sin registros MX ni A -> «ese dominio de correo no existe».
 *   4. email_web_check=1: la web del dominio (https://dominio/, https://www.dominio/, http://dominio/) tiene que responder 2xx tras
 *      seguir redirecciones. Si no, el dominio entra solo en la lista negra (motivo «auto: …») y no se crea la cuenta.
 * Las dos listas cubren tambien los subdominios (alumnos.us.es cae con us.es). Se gestionan en /moderacion.php#dominios.
 * Tablas y config: sql/migra-058-dominios-correo.sql. */

/** Mensaje al rechazar un dominio (lista negra o web que no responde). */
function email_temp_msg(): string {
    return t('No se ha registrado la cuenta por el uso de un email temporal. Recuerda que no necesitas cuenta para enviar o comentar. Si crees que es un error, puedes <a href="/privacidad.php">escribirnos</a> para que te activemos la cuenta manualmente.');
}

/** Normaliza lo que pegue un moderador (dominio, correo entero o URL) a un dominio; null si no vale. */
function email_domain_normalize(string $raw): ?string {
    $d = mb_strtolower(trim($raw));
    $d = preg_replace('~^.*@~', '', $d);                                         // correo entero
    if (preg_match('~^[a-z][a-z0-9+.-]*://~', $d)) $d = (string)parse_url($d, PHP_URL_HOST);
    $d = preg_replace('~[/?#].*$~', '', $d);
    $d = preg_replace('/^www\./', '', trim((string)$d, " \t\n\r."));
    return ($d !== '' && strlen($d) <= 120 && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $d)) ? $d : null;
}

/** ¿Esta el dominio (o uno superior) en esta lista? Devuelve el dominio de la lista que lo cubre, o null. $tabla: banned_domains | allowed_domains */
function email_domain_listed(PDO $pdo, string $tabla, string $d): ?string {
    if (!in_array($tabla, ['banned_domains', 'allowed_domains'], true) || $d === '') return null;
    $cands = []; $p = explode('.', $d);
    for ($i = 0; $i < count($p) - 1; $i++) $cands[] = implode('.', array_slice($p, $i));   // a.b.us.es, b.us.es, us.es
    try {
        $st = $pdo->prepare("SELECT domain FROM $tabla WHERE domain IN (" . implode(',', array_fill(0, count($cands), '?')) . ") ORDER BY LENGTH(domain) DESC LIMIT 1");
        $st->execute($cands);
        $r = $st->fetchColumn();
        return $r === false ? null : (string)$r;
    } catch (Throwable $e) { return null; }   // sin la tabla (migracion pendiente)
}

/** ¿Responde la web del dominio? [bool, descripcion]. Solo hosts con IP publica; sigue hasta 5 redirecciones; no descarga mas de 64 KB. */
function email_web_check(string $d): array {
    if (!function_exists('curl_init')) return [true, 'sin curl: no se comprueba'];
    $timeout = max(2, min(15, (int)cfg('email_web_timeout', 6)));
    $trazas = [];
    foreach (["https://$d/", "https://www.$d/", "http://$d/"] as $url) {
        $host = (string)parse_url($url, PHP_URL_HOST);
        $ips = @gethostbynamel($host) ?: [];
        if (!$ips) { $trazas[] = "$host: no resuelve"; continue; }
        $priv = false;
        foreach ($ips as $ip) if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) $priv = true;
        if ($priv) { $trazas[] = "$host: IP privada"; continue; }
        $n = 0;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_CONNECTTIMEOUT => min(4, $timeout), CURLOPT_TIMEOUT => $timeout,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml,*/*;q=0.8', 'Accept-Language: es-ES,es;q=0.9,en;q=0.8'],
            CURLOPT_WRITEFUNCTION => function ($c, $chunk) use (&$n) { $n += strlen($chunk); return $n > 65536 ? -1 : strlen($chunk); },
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_errno($ch) && curl_errno($ch) !== CURLE_WRITE_ERROR ? curl_error($ch) : '';
        curl_close($ch);
        if ($code >= 200 && $code < 300) return [true, "$url: HTTP $code"];
        $trazas[] = "$host: " . ($code ? "HTTP $code" : ($err !== '' ? $err : 'sin respuesta'));
    }
    return [false, implode(' · ', $trazas)];
}

/** ¿Se rechaza este correo al registrarse? Devuelve el motivo (HTML seguro, texto fijo) o null si se admite. */
function email_rejected(PDO $pdo, string $email): ?string {
    $d = email_domain($email);
    if ($d === '') return t('Correo no válido.');
    if (email_domain_listed($pdo, 'banned_domains', $d)) return email_temp_msg();
    if (email_domain_listed($pdo, 'allowed_domains', $d)) return null;
    if (function_exists('checkdnsrr') && !checkdnsrr($d . '.', 'MX') && !checkdnsrr($d . '.', 'A')) return t('Ese dominio de correo no existe.');
    if ((string)cfg('email_web_check', '1') === '1') {
        [$ok, $info] = email_web_check($d);
        if (!$ok) {
            try { $pdo->prepare("INSERT IGNORE INTO banned_domains (domain, reason, added_by) VALUES (?,?,NULL)")->execute([$d, mb_substr('auto: la web no responde (' . $info . ')', 0, 120)]); } catch (Throwable $e) {}
            return email_temp_msg();
        }
    }
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
