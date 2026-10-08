<?php
/**
 * lib/analitica.php — analitica propia, sin cookies, sin JS y sin terceros.
 *
 * Cada pagina que pasa por page_header() (GET, no admin, no bots) deja una fila en hits con la huella
 * del dia (la misma de presence: HMAC de ip+navegador+dia, truncado; nunca la IP). Con eso:
 *   visitantes = huellas distintas (por dia; el mes y el año suman los dias, porque la huella cambia a diario),
 *   sesiones   = paginas marcadas new_session (primera tras analytics_session_minutes sin actividad),
 *   paginas    = filas.
 * ana_tick() (cron) cierra cada dia en ana_daily y borra el detalle de mas de analytics_keep_days dias.
 * /analitica (registrados) muestra ayer / mes / año desde ana_daily; admin -> Analitica, el detalle en vivo desde hits.
 */
declare(strict_types=1);

function ana_on(): bool { return (string)cfg('analytics_enabled', '1') === '1'; }

/** Registrar la pagina actual. Se llama desde page_header(); nunca rompe la pagina. */
function ana_hit(): void {
    global $pdo, $DDD_SECRET;
    static $done = false;
    if ($done || PHP_SAPI === 'cli' || !ana_on()) return;
    $done = true;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return;
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('~bot|crawl|spider|slurp|fetch|monitor|curl|wget|python|headless|lighthouse|preview~i', $ua)) return;
    if (($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? '') !== '') return;   // prefetch/prerender: no es una lectura
    if (($_GET['auto'] ?? '') === '1') return;   // recarga automatica (meta refresh del chat y de procesos, refresh_meta()): no es una lectura nueva
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string)(parse_url($uri, PHP_URL_PATH) ?: '/');
    if (preg_match('~^/(admin|moderacion|api/|app/|css/|img/|media/)~', $path)) return;
    parse_str((string)(parse_url($uri, PHP_URL_QUERY) ?? ''), $q);
    $keep = [];
    foreach (['id', 'c', 't', 'portada', 'k', 'u', 'p'] as $k) if (isset($q[$k]) && is_scalar($q[$k]) && (string)$q[$k] !== '') $keep[$k] = (string)$q[$k];
    if ($keep) $path .= '?' . http_build_query($keep);
    $path = mb_substr($path, 0, 190);

    $fp = substr(hash_hmac('sha256', client_ip() . '|' . $ua . '|' . date('Y-m-d'), $DDD_SECRET), 0, 16);
    $ref = '';
    $rh = strtolower((string)parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST));
    $own = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($rh !== '' && $rh !== $own && $rh !== "www.$own" && "www.$rh" !== $own) $ref = mb_substr(preg_replace('/^www\./', '', $rh), 0, 120);
    $device = preg_match('~iPad|Tablet|Silk|Kindle|PlayBook~i', $ua) ? 'tablet' : (preg_match('~Mobi|Android|iPhone|IEMobile|Opera Mini~i', $ua) ? 'mobile' : 'desktop');
    $country = strtoupper(substr((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''), 0, 2));
    if (!preg_match('/^[A-Z]{2}$/', $country)) $country = '';
    $lang = strtolower(substr(trim(explode(',', (string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''))[0]), 0, 2));
    if (!preg_match('/^[a-z]{2}$/', $lang)) $lang = '';
    $gap = max(1, (int)cfg('analytics_session_minutes', 30));
    try {
        $st = $pdo->prepare("SELECT 1 FROM hits WHERE fp = ? AND ts >= NOW() - INTERVAL $gap MINUTE LIMIT 1");
        $st->execute([$fp]);
        $new = $st->fetchColumn() ? 0 : 1;
        $pdo->prepare("INSERT INTO hits (ts, fp, logged, new_session, path, ref_host, device, country, lang) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$fp, user() ? 1 : 0, $new, $path, $ref, $device, $country, $lang]);
    } catch (Throwable $e) { /* tabla aun sin crear: no romper la pagina */ }
}

/** Totales de un dia calculados desde hits */
function ana_day_from_hits(PDO $pdo, string $day): array {
    $st = $pdo->prepare("SELECT COUNT(DISTINCT fp) visitors, COALESCE(SUM(new_session),0) sessions, COUNT(*) pageviews, COALESCE(SUM(logged),0) logged
                         FROM hits WHERE ts >= ? AND ts < ? + INTERVAL 1 DAY");
    $st->execute([$day, $day]);
    return array_map('intval', $st->fetch());
}

/** Cron (cada minuto): cierra en ana_daily los dias completos que falten y poda el detalle viejo. Idempotente. */
function ana_tick(PDO $pdo): array {
    $out = [];
    try {
        $first = $pdo->query("SELECT DATE(MIN(ts)) FROM hits")->fetchColumn();
        if (!$first) return $out;
        $last = $pdo->query("SELECT MAX(day) FROM ana_daily")->fetchColumn();
        $day = $last ? date('Y-m-d', strtotime("$last +1 day")) : $first;
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $n = 0;
        while ($day <= $yesterday && $n < 400) {
            $d = ana_day_from_hits($pdo, $day);
            $pdo->prepare("INSERT INTO ana_daily (day, visitors, sessions, pageviews, logged) VALUES (?,?,?,?,?)
                           ON DUPLICATE KEY UPDATE visitors = VALUES(visitors), sessions = VALUES(sessions), pageviews = VALUES(pageviews), logged = VALUES(logged)")
                ->execute([$day, $d['visitors'], $d['sessions'], $d['pageviews'], $d['logged']]);
            $out[] = "analitica: $day cerrado ({$d['visitors']} visitantes, {$d['sessions']} sesiones, {$d['pageviews']} paginas)";
            $day = date('Y-m-d', strtotime("$day +1 day")); $n++;
        }
        if ((int)date('i') === 7) {   // una vez por hora: podar el detalle
            $keep = max(7, (int)cfg('analytics_keep_days', 90));
            $del = $pdo->exec("DELETE FROM hits WHERE ts < CURDATE() - INTERVAL $keep DAY LIMIT 20000");
            if ($del) $out[] = "analitica: $del paginas vistas de mas de $keep dias borradas";
        }
    } catch (Throwable $e) { $out[] = 'analitica: ERROR ' . $e->getMessage(); }
    return $out;
}

/** Sumas de ana_daily entre dos fechas (incluidas) */
function ana_sum(PDO $pdo, string $from, string $to): array {
    $st = $pdo->prepare("SELECT COALESCE(SUM(visitors),0) visitors, COALESCE(SUM(sessions),0) sessions, COALESCE(SUM(pageviews),0) pageviews, COALESCE(SUM(logged),0) logged, COUNT(*) days FROM ana_daily WHERE day BETWEEN ? AND ?");
    $st->execute([$from, $to]);
    return array_map('intval', $st->fetch());
}
