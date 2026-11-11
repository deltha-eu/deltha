<?php
/** lib/iplog.php — registro de IP con caducidad (migra-193, 11/11/2026).
 *  Para dos cosas y ninguna más: detectar multicuentas y poder responder a un requerimiento formal de un juzgado o de la policía.
 *  Se apunta la IP (client_ip(), la de verdad: ver ip_de_proxy en lib/app.php) al registrarse, al abrir sesión y al publicar
 *  (envío, artículo, comentario, mensaje del Chat), con cuenta o sin ella. Los votos no se apuntan: siguen con su huella.
 *  Cada fila caduca a los config.ip_log_days días (365; 0 = no se guarda nada y se borra lo que hubiera): lo purga el ciclo.
 *  Solo lo consulta el administrador (/ips) y cada consulta queda en el registro de moderación (mod_log, acción ips:consulta).
 *  Las huellas HMAC de votos, anónimos, topes y analítica no cambian: ahí la IP sigue sin guardarse. */
declare(strict_types=1);

const IP_LOG_KINDS = ['registro' => 'registro', 'login' => 'inicio de sesión', 'story' => 'envío', 'comment' => 'comentario', 'chat' => 'chat'];

/** Días que se conserva cada fila (0 = el registro está apagado). */
function ip_log_days(): int { return max(0, (int)cfg('ip_log_days', 365)); }

/** Apunta la IP de la petición en curso. Nunca lanza: si la tabla aún no existe, el registro, el login o la publicación siguen igual. */
function ip_log_add(PDO $pdo, string $kind, ?int $uid, int $ref = 0): void {
    if (!isset(IP_LOG_KINDS[$kind]) || ip_log_days() <= 0 || PHP_SAPI === 'cli') return;
    $ip = client_ip();
    if (!filter_var($ip, FILTER_VALIDATE_IP) || $ip === '0.0.0.0') return;
    try {
        $pdo->prepare("INSERT INTO ip_log (user_id, kind, ref_id, ip) VALUES (?,?,?,?)")
            ->execute([$uid && $uid > 0 ? $uid : null, $kind, max(0, $ref), $ip]);
    } catch (Throwable $e) { error_log('ip_log: ' . $e->getMessage()); }
}

/** Borra lo caducado (lo llama el ciclo). Con ip_log_days = 0 lo borra todo. Devuelve las filas borradas. */
function ip_log_purge(PDO $pdo): int {
    try {
        $d = ip_log_days();
        if ($d <= 0) return (int)$pdo->exec("DELETE FROM ip_log");
        $st = $pdo->prepare("DELETE FROM ip_log WHERE created < NOW() - INTERVAL ? DAY");
        $st->execute([$d]);
        return $st->rowCount();
    } catch (Throwable $e) { return 0; }   // migra-193 sin aplicar
}

/** La IP recortada, para dejar constancia de una consulta sin enseñar la dirección a quien lea el registro: 81.33.x.x, 2a02:9130:… */
function ip_log_mask(string $ip): string {
    if (str_contains($ip, ':')) return implode(':', array_slice(explode(':', $ip), 0, 2)) . ':…';
    $p = explode('.', $ip);
    return ($p[0] ?? '?') . '.' . ($p[1] ?? '?') . '.x.x';
}

/** Deja constancia en el registro de moderación de que el administrador ha consultado el registro de IP. */
function ip_log_audit(PDO $pdo, string $detalle): void {
    $u = user();
    if (!$u) return;
    try {
        $pdo->prepare("INSERT INTO mod_log (user_id, action, detail, ip) VALUES (?,?,?,?)")
            ->execute([(int)$u['user_id'], 'ips:consulta', mb_substr($detalle, 0, 700), client_ip()]);
    } catch (Throwable $e) { error_log('ip_log audit: ' . $e->getMessage()); }
}

/** IP desde las que ha actuado una cuenta: [ip, n, first, last], la más reciente primero. */
function ip_log_de_usuario(PDO $pdo, int $uid): array {
    $st = $pdo->prepare("SELECT ip, COUNT(*) AS n, MIN(created) AS first, MAX(created) AS last FROM ip_log WHERE user_id = ? GROUP BY ip ORDER BY last DESC LIMIT 300");
    $st->execute([$uid]);
    return $st->fetchAll();
}

/** Otras cuentas que han actuado desde alguna de las IP de esta: [user_id, login, level, created, ips (cuántas comparten), n, last]. */
function ip_log_cuentas_comunes(PDO $pdo, int $uid): array {
    $st = $pdo->prepare("SELECT u.user_id, u.login, u.level, u.created, COUNT(DISTINCT b.ip) AS ips, COUNT(*) AS n, MAX(b.created) AS last
                           FROM ip_log b JOIN users u ON u.user_id = b.user_id
                          WHERE b.user_id <> ? AND b.ip IN (SELECT ip FROM (SELECT DISTINCT ip FROM ip_log WHERE user_id = ?) x)
                          GROUP BY u.user_id, u.login, u.level, u.created ORDER BY ips DESC, last DESC LIMIT 200");
    $st->execute([$uid, $uid]);
    return $st->fetchAll();
}

/** Filas del registro, las más recientes primero. $by: 'user' (id), 'ip' (dirección) o 'item' ([kind, ref_id]). */
function ip_log_filas(PDO $pdo, string $by, $val, int $limit = 300): array {
    $limit = max(1, min(1000, $limit));
    if ($by === 'user')     { $w = 'l.user_id = ?'; $a = [(int)$val]; }
    elseif ($by === 'ip')   { $w = 'l.ip = ?'; $a = [(string)$val]; }
    else                    { $w = 'l.kind = ? AND l.ref_id = ?'; $a = [(string)$val[0], (int)$val[1]]; }
    $st = $pdo->prepare("SELECT l.*, u.login FROM ip_log l LEFT JOIN users u ON u.user_id = l.user_id WHERE $w ORDER BY l.log_id DESC LIMIT $limit");
    $st->execute($a);
    return $st->fetchAll();
}

/** Adónde lleva una fila (el envío, el comentario, el mensaje) o '' si no apunta a nada. Los comentarios necesitan su hilo. */
function ip_log_href(PDO $pdo, array $r): string {
    $ref = (int)$r['ref_id'];
    if ($ref <= 0) return '';
    if ($r['kind'] === 'story') return "/hilo/$ref";
    if ($r['kind'] === 'chat')  return "/chat?m=$ref#m$ref";
    if ($r['kind'] === 'comment') {
        try { $q = $pdo->prepare("SELECT story_id FROM comments WHERE comment_id = ?"); $q->execute([$ref]); $sid = (int)$q->fetchColumn(); }
        catch (Throwable $e) { $sid = 0; }
        return $sid ? "/hilo/$sid#c$ref" : '';
    }
    return '';
}
