<?php
/** lib/chat.php — el Chat: un solo flujo de mensajes cortos (tipo Nótame), sin anidar.
 *  Se responde citando #ID: cada #N del texto se convierte en enlace al mensaje N, y el mensaje N
 *  enseña «respuestas: #a #b». @nick enlaza al perfil si el usuario existe. Sin avatares: solo el nombre.
 *  Registrados con su nick; anonimos (si chat_anon = 1) con alias estable por IP (Apellido-Lugar).
 *  Borrado propio durante chat_delete_minutes (status gone); borrado de moderacion como en los comentarios
 *  (texto sustituido, original en content_orig). Tablas chat y chat_reports (sql/migra-027-chat.sql). */
declare(strict_types=1);

const DDD_CHAT_DELETED_TXT = 'Mensaje eliminado por incumplir las normas de uso';

/** Condicion SQL de «mensaje visible en el flujo»: status ok y, si lo retiro moderacion, solo durante chat_deleted_minutes
 *  (30 por defecto; 0 = el aviso se queda para siempre). Pasado ese plazo el aviso deja de pintarse; el original sigue en content_orig.
 *  $a = alias de la tabla chat en la consulta ('' si no hay). sql/migra-065-chat-aviso-borrado.sql */
function chat_visible_sql(string $a = ''): string {
    $p = $a !== '' ? "$a." : '';
    $min = (int)cfg('chat_deleted_minutes', 30);
    return "{$p}status = 'ok'" . ($min > 0 ? " AND ({$p}deleted = 0 OR {$p}deleted_at IS NULL OR {$p}deleted_at > NOW() - INTERVAL $min MINUTE)" : '');
}

/** Alias estable + huella del anonimo en el Chat. Va por IP **y navegador** (User-Agent): dos personas en la misma
 *  conexion (casa, oficina, movil y PC por wifi) no comparten alias; la misma persona en el mismo navegador lo conserva. */
function chat_anon(): array {
    global $DDD_SECRET;
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
    $key = substr(hash_hmac('sha256', client_ip() . '|' . $ua . '|chat', $DDD_SECRET), 0, 16);
    $n = hexdec(substr($key, 0, 8));
    return [DDD_NOMBRES[$n % count(DDD_NOMBRES)] . '-' . DDD_LUGARES[intdiv($n, 997) % count(DDD_LUGARES)], $key];
}
/** Quien firma un mensaje: nick del registrado o alias del anonimo. */
function chat_who(array $m): string {
    if (!empty($m['login']) && ghost_veiled($m['user_id'] ?? 0)) return ghost_alias((int)$m['user_id']);   // velado por ghosteo: alias de pareja (lib/ghost.php)
    return !empty($m['login']) ? (string)$m['login'] : (string)$m['alias'];
}

/** Mensajes por hora de esta persona (registrado por user_id; anonimo por huella). */
function chat_recent_count(PDO $pdo, ?array $u, ?string $key): int {
    if ($u) { $st = $pdo->prepare("SELECT COUNT(*) FROM chat WHERE user_id = ? AND created >= NOW() - INTERVAL 1 HOUR"); $st->execute([$u['user_id']]); }
    elseif ($key) { $st = $pdo->prepare("SELECT COUNT(*) FROM chat WHERE anon_key = ? AND created >= NOW() - INTERVAL 1 HOUR"); $st->execute([$key]); }
    else return 0;
    return (int)$st->fetchColumn();
}

/** El autor puede borrar su mensaje? (registrado por user_id, anonimo por huella) durante chat_delete_minutes. */
function chat_can_delete_own(array $m, ?array $u, ?string $key): bool {
    if (!empty($m['deleted']) || $m['status'] !== 'ok') return false;
    if (time() - strtotime($m['created']) >= (int)cfg('chat_delete_minutes', 5) * 60) return false;
    if ($u) return !empty($m['user_id']) && (int)$m['user_id'] === (int)$u['user_id'];
    return $key !== null && empty($m['user_id']) && (string)$m['anon_key'] === $key;
}
/** Borrado de moderacion: el mensaje sigue en el flujo con el texto sustituido; cierra sus reportes. */
function chat_delete_mod(PDO $pdo, int $id): bool {
    $st = $pdo->prepare("UPDATE chat SET content_orig = IF(deleted = 1, content_orig, content), deleted_at = IF(deleted = 1, deleted_at, NOW()), deleted = 1, content = ? WHERE msg_id = ? AND status = 'ok'");
    $st->execute([DDD_CHAT_DELETED_TXT, $id]);
    try { $pdo->prepare("UPDATE chat_reports SET status = 'deleted', decided_by = ?, decided = NOW() WHERE msg_id = ? AND status = 'open'")->execute([user()['user_id'] ?? null, $id]); } catch (Throwable $e) {}
    return $st->rowCount() > 0;
}
/** Reportes de Chat pendientes (para el enlace de moderacion). */
function chat_open_reports(PDO $pdo): int {
    try { return (int)$pdo->query("SELECT COUNT(DISTINCT msg_id) FROM chat_reports WHERE status = 'open'")->fetchColumn(); } catch (Throwable $e) { return 0; }
}

/** Primer #N citado en un texto (para reply_to), o null. */
function chat_first_ref(string $text): ?int {
    return preg_match('/(?<![\w\/])#(\d{1,9})\b/', $text, $m) ? (int)$m[1] : null;
}
/** Nicks mencionados con @ en un texto. */
function chat_mentions(string $text): array {
    preg_match_all('/(?<![\w@])@([A-Za-z0-9_\-]{3,32})\b/', $text, $m);
    return array_values(array_unique(array_map('strtolower', $m[1])));
}
/** Una linea de mensaje, en linea y sin parrafos: escapado, #N → enlace al mensaje, @nick → perfil (solo si existe:
 *  $logins = nicks existentes en minusculas → nick real, o ['alias' => …] si esta velado por ghosteo), **negrita**,
 *  *cursiva*, [texto](url) y URLs sueltas → enlace (nofollow). $ids = ids de mensajes de la pagina actual (ancla local);
 *  el resto va por ?m=N. Sirve para el texto del mensaje y para la vista previa del mensaje citado. */
function chat_inline(string $t, array $logins, array $ids): string {
    $h = h($t);
    $h = preg_replace_callback('~(?<![\w\/])#(\d{1,9})\b~', function ($m) use ($ids) {
        $n = (int)$m[1];
        $href = isset($ids[$n]) ? "#m$n" : "/chat?m=$n#m$n";
        return "<a class=\"ref\" href=\"$href\">#$n</a>";
    }, $h);
    $h = preg_replace_callback('~(?<![\w@])@([A-Za-z0-9_\-]{3,32})\b~', function ($m) use ($logins) {
        $k = strtolower($m[1]);
        if (!isset($logins[$k])) return $m[0];
        if (is_array($logins[$k])) return "<span class=\"at\">@" . h($logins[$k]['alias']) . "</span>";   // velado por ghosteo: alias y sin enlace
        return "<a class=\"at\" href=\"/u/" . rawurlencode($logins[$k]) . "\">@" . h($logins[$k]) . "</a>";
    }, $h);
    return saltos(enlaces(md_inline($h)));
}

/** Vista previa de un mensaje citado (#N): quien lo escribio, cuando y su texto ya formateado (enlaces incluidos).
 *  Va JUSTO DETRAS del enlace (hermano, no dentro: dentro de un <a> no cabe otro <a>) y la muestra el CSS al pasar
 *  el raton por el enlace o al enfocarlo con el teclado; sin JS y sin peticiones.
 *  aria-hidden: el lector de pantalla ya tiene el enlace; no debe leer dos veces el mensaje. */
function chat_preview(array $m, array $logins, array $ids): string {
    if (!function_exists('md_inline')) require_once __DIR__ . '/render.php';
    $del = (int)($m['deleted'] ?? 0) === 1;
    $txt = $del ? h(t(DDD_CHAT_DELETED_TXT)) : chat_inline((string)$m['content'], $logins, $ids);
    return '<span class="refpop" aria-hidden="true"><span class="rp-h"><b>' . h(chat_who($m)) . '</b> · ' . h(hace((string)$m['created'])) . '</span>'
        . '<span class="rp-t">' . $txt . '</span></span>';
}

/** Texto del mensaje a HTML: parrafos y citas, con lo de chat_inline() dentro (ver arriba).
 *  $previews = id => HTML de chat_preview() de los mensajes de esta pagina: el #N que lo tiene enseña el mensaje
 *  citado al pasar el raton. La caja se añade cuando el texto ya esta formateado, para que ni los enlaces ni las
 *  marcas de dentro de la vista previa se vuelvan a interpretar. */
function chat_format(string $text, array $logins, array $ids, array $previews = []): string {
    if (!function_exists('md_bloques')) require_once __DIR__ . '/render.php';   // mismas marcas que en los comentarios
    $pops = [];
    $out = md_bloques($text, function (string $t) use ($logins, $ids, $previews, &$pops): string {
        $h = chat_inline($t, $logins, $ids);
        if (!$previews) return $h;
        // el enlace va dentro de un <span> que lo envuelve con la caja: asi la caja puede llevar enlaces (hermana,
        // nunca dentro del <a>) y se coloca respecto del enlace (.refwrap, ver ddd.css)
        return preg_replace_callback('~<a class="ref" href="[^"]*">#(\d+)</a>~', function ($m) use (&$pops, $previews) {
            if (!isset($previews[(int)$m[1]])) return $m[0];
            $t = "\x02" . count($pops) . "\x03";   // se cambia por la caja al final, fuera de las marcas
            $pops[$t] = $previews[(int)$m[1]];
            return '<span class="refwrap">' . $m[0] . $t . '</span>';
        }, $h);
    });
    return strtr($out, $pops);
}
/** Pagina (1..n) en la que esta el mensaje N, con los mensajes ordenados del mas nuevo al mas viejo. */
function chat_page_of(PDO $pdo, int $id): int {
    $per = max(10, (int)cfg('chat_per_page', 50));
    $st = $pdo->prepare("SELECT COUNT(*) FROM chat WHERE " . chat_visible_sql() . " AND msg_id > ?"); $st->execute([$id]);
    return intdiv((int)$st->fetchColumn(), $per) + 1;
}
