<?php
/**
 * lib/bloqueos.php — medios bloqueados: dominios cuyos enlaces no se admiten en Deltha
 * (p. ej. RT y Sputnik, prohibidos en la UE). Los gestionan moderadores y admin en
 * /moderacion.php#medios; se comprueba al enviar (/enviar.php) y al editar el enlace (/editar.php).
 *
 * Funciona como los dominios de correo vetados: se guarda el dominio sin «www.» y en minúsculas,
 * y bloquea también sus subdominios (actualidad.rt.com cae con rt.com).
 * Tabla banned_sources (domain, reason, added_by, created), sql/migra-036-medios-bloqueados.sql.
 */
declare(strict_types=1);

/** Normaliza lo que pegue el moderador (URL entera o dominio suelto) a un dominio; null si no vale. */
function source_domain_normalize(string $raw): ?string {
    $d = mb_strtolower(trim($raw));
    if ($d === '') return null;
    if (preg_match('~^[a-z][a-z0-9+.-]*://~', $d)) $d = (string)parse_url($d, PHP_URL_HOST);   // URL entera
    else $d = preg_replace('~[/?#].*$~', '', $d);                                                // dominio con ruta pegada
    $d = preg_replace('/^www\./', '', trim((string)$d, " \t\n\r.\\/"));
    if ($d === '' || strlen($d) > 120 || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $d)) return null;
    return $d;
}

/** Dominios bloqueados (cacheados en la petición). */
function source_blocked_list(PDO $pdo): array {
    static $list = null;
    if ($list !== null) return $list;
    try { $list = $pdo->query("SELECT domain FROM banned_sources")->fetchAll(PDO::FETCH_COLUMN); }
    catch (Throwable $e) { $list = []; }   // sin la tabla (migración pendiente) no se bloquea nada
    return $list;
}

/** Devuelve el dominio bloqueado que cubre esta URL (el propio o uno superior), o null si se admite. */
function source_blocked(PDO $pdo, ?string $url): ?string {
    if (!$url) return null;
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    $host = preg_replace('/^www\./', '', $host);
    if ($host === '') return null;
    foreach (source_blocked_list($pdo) as $d) {
        if ($host === $d || substr($host, -strlen($d) - 1) === '.' . $d) return $d;
    }
    return null;
}

/** Alta desde moderación. Devuelve el dominio guardado o null si la entrada no vale. */
function source_block_add(PDO $pdo, string $raw, ?string $reason, ?int $by): ?string {
    $d = source_domain_normalize($raw);
    if ($d === null) return null;
    $pdo->prepare("INSERT IGNORE INTO banned_sources (domain, reason, added_by) VALUES (?,?,?)")
        ->execute([$d, $reason !== null && $reason !== '' ? mb_substr($reason, 0, 120) : null, $by]);
    return $d;
}

function source_block_remove(PDO $pdo, string $domain): void {
    $pdo->prepare("DELETE FROM banned_sources WHERE domain = ?")->execute([$domain]);
}
