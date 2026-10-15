<?php
/**
 * lib/topes.php — topes de ritmo al enviar noticias (/enviar), contra la autopromocion en serie (15/10/2026, migra-155).
 *
 * No se veta a nadie: se limita cuanto puede enviar cada cual en poco tiempo.
 *   - send_anon_per_hour  (3): envios por hora de un anonimo, por huella HMAC de ip+navegador (stories.anon_key; la IP no se guarda).
 *   - send_user_per_hour (10): envios por hora de una cuenta.
 *   - send_domain_per_day (5): envios en 24 h de un mismo publicador (stories.pub_key), los envie quien los envie.
 *     El publicador es el dominio sin www (killbait.com); en YouTube es el canal (yt:UC…, leido de la pagina del video);
 *     en las plataformas compartidas de send_domain_platforms (x.com, medium.com…) no hay tope por dominio, solo por persona.
 *     Las fuentes del catalogo (Mis medios / Mis substacks) estan exentas: ahi cinco al dia se queda corto y no son el problema.
 *   0 = sin tope. Todo en admin -> Config.
 */
declare(strict_types=1);

/** Huella del anonimo que envia: HMAC(ip|navegador|envio) truncado. Misma receta que el chat; no es reversible. */
function envio_anon_key(): string {
    global $DDD_SECRET;
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
    return substr(hash_hmac('sha256', client_ip() . '|' . $ua . '|envio', $DDD_SECRET), 0, 16);
}

/** Plataformas compartidas (dominio exacto, sin www): mucha gente distinta publica bajo el mismo dominio. */
function envio_plataformas(): array {
    static $p = null;
    if ($p === null) $p = array_values(array_filter(array_map(fn($d) => strtolower(trim($d)), explode(',', (string)cfg('send_domain_platforms', '')))));
    return $p;
}

/** Clave del publicador de una URL (para el tope diario) o null si no se aplica tope por dominio.
 *  $channel: id del canal de YouTube si la pagina lo dio (extraer_meta()['channel']). */
function envio_pub_key(?string $url, ?string $channel = null): ?string {
    $host = source_of($url);
    if (!$host) return null;
    $m = preg_replace('/^m\./', '', $host);
    if (preg_match('/(^|\.)(youtube\.com|youtu\.be)$/', $m)) return $channel ? 'yt:' . $channel : null;   // canal, no youtube.com entero
    if (in_array($host, envio_plataformas(), true)) return null;
    return mb_substr($host, 0, 120);
}

/** Valida una clave que vuelve del formulario (campo oculto entre el paso 1 y el 2). */
function envio_pub_key_valida(string $k): ?string {
    $k = trim($k);
    if ($k === '') return null;
    if (preg_match('/^yt:UC[\w-]{20,40}$/', $k) || preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $k)) return $k;
    return null;
}

/** Envios vivos de un publicador en las ultimas 24 h. */
function envio_pub_count(PDO $pdo, string $pubKey): int {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE pub_key = ? AND sent_date >= NOW() - INTERVAL 1 DAY AND status NOT IN ('rejected','discard')");
        $st->execute([$pubKey]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }   // sin la columna (migracion pendiente) no hay tope
}

/** ¿Este publicador esta exento del tope diario? (fuente del catalogo: medio o substack dado de alta) */
function envio_pub_exento(PDO $pdo, string $pubKey): bool {
    if (str_starts_with($pubKey, 'yt:')) return false;
    require_once __DIR__ . '/fuentes.php';
    return fuentes_on() && fuente_por_dominio($pdo, $pubKey) !== null;
}

/** Comprueba los topes por persona. Devuelve el texto del error (con HTML) o null si puede enviar. */
function envio_tope_persona(PDO $pdo, ?array $u, string $anonKey): ?string {
    try {
        if ($u) {
            $max = (int)cfg('send_user_per_hour', 10);
            if ($max <= 0) return null;
            $st = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE user_id = ? AND sent_date >= NOW() - INTERVAL 1 HOUR");
            $st->execute([(int)$u['user_id']]);
            if ((int)$st->fetchColumn() >= $max) return t('Has llegado al máximo de %d envíos por hora. Espera un rato antes de enviar otra noticia.', $max);
        } else {
            $max = (int)cfg('send_anon_per_hour', 3);
            if ($max <= 0) return null;
            $st = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE anon_key = ? AND sent_date >= NOW() - INTERVAL 1 HOUR");
            $st->execute([$anonKey]);
            if ((int)$st->fetchColumn() >= $max) return t('Sin cuenta se pueden enviar %d noticias por hora y ya las has enviado. <a href="/registro">Regístrate</a> para enviar sin ese límite.', $max);
        }
    } catch (Throwable $e) {}   // sin la columna (migracion pendiente) no hay tope
    return null;
}

/** Comprueba el tope diario del publicador. Devuelve el error o null. */
function envio_tope_publicador(PDO $pdo, ?string $pubKey): ?string {
    if ($pubKey === null) return null;
    $max = (int)cfg('send_domain_per_day', 5);
    if ($max <= 0 || envio_pub_exento($pdo, $pubKey)) return null;
    if (envio_pub_count($pdo, $pubKey) >= $max) return t('Hoy ya se han enviado %d noticias de %s, el máximo diario por medio. Prueba con una de otro medio o vuelve mañana.', $max, h(envio_pub_nombre($pubKey)));
    return null;
}

/** Aviso (no error) cuando el publicador ya lleva envios hoy: «De este medio ya van N de 5 hoy». Null si no hay nada que avisar. */
function envio_aviso_publicador(PDO $pdo, ?string $pubKey): ?string {
    if ($pubKey === null) return null;
    $max = (int)cfg('send_domain_per_day', 5);
    if ($max <= 0 || envio_pub_exento($pdo, $pubKey)) return null;
    $n = envio_pub_count($pdo, $pubKey);
    if ($n < 1) return null;
    return t('De %s ya se han enviado hoy %d noticias de un máximo de %d al día.', h(envio_pub_nombre($pubKey)), $n, $max);
}

/** Como se nombra el publicador en los avisos: el dominio, o «ese canal de YouTube». */
function envio_pub_nombre(string $pubKey): string {
    return str_starts_with($pubKey, 'yt:') ? t('ese canal de YouTube') : $pubKey;
}
