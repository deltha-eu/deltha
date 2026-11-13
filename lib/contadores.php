<?php
/**
 * lib/contadores.php — lecturas y clics de cada envio (migra-120).
 *   stories.views  = entradas al hilo (/hilo/N)
 *   stories.clicks = salidas al medio (/ir?id=…, que suma y redirige)
 * Una vez por persona, envio y dia: la misma huella de la analitica (HMAC de ip+navegador+dia, truncado; nunca la IP),
 * apuntada en Redis 24 h (lib/cache.php). Sin Redis se cuenta igual, sin deduplicar. No cuentan bots, precargas,
 * recargas automaticas ni el propio autor. Nunca rompe la pagina.
 */
declare(strict_types=1);

/** Suma una lectura ('views') o un clic ('clicks') al envio $s (fila de stories). */
function story_cuenta(PDO $pdo, array $s, string $col): void {
    global $DDD_SECRET;
    if (!in_array($col, ['views', 'clicks'], true) || PHP_SAPI === 'cli') return;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return;
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('~bot|crawl|spider|slurp|fetch|monitor|curl|wget|python|headless|lighthouse|preview~i', $ua)) return;
    if (($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? '') !== '') return;   // prefetch/prerender: no es una lectura
    if (($_GET['auto'] ?? '') === '1') return;                                             // recarga automatica
    $u = user();
    if ($u && (int)$u['user_id'] === (int)($s['user_id'] ?? 0)) return;                    // el autor no se cuenta a si mismo
    $id = (int)$s['story_id'];
    $fp = substr(hash_hmac('sha256', client_ip() . '|' . $ua . '|' . date('Y-m-d'), $DDD_SECRET), 0, 16);
    $r = redis_conn();
    if ($r) {
        try { if (!$r->set("cnt:$col:$id:$fp", 1, ['nx', 'ex' => 86400])) return; }       // ya contado hoy
        catch (Throwable $e) { /* Redis caido a medias: se cuenta sin deduplicar */ }
    }
    try { $pdo->prepare("UPDATE stories SET $col = $col + 1 WHERE story_id = ?")->execute([$id]); }
    catch (Throwable $e) { /* columna aun sin crear (migra-120 pendiente) */ }
}

/** Enlace de salida al medio que pasa por el contador de clics. */
function ir_url(array $s): string { return '/ir?id=' . (int)$s['story_id']; }

/** Cifras de lecturas y clics para la fila de acciones de la tarjeta y la barra del hilo. */
function story_contadores(array $s): string {
    $o = "<span class=\"cnt\" title=\"" . h(t('lecturas')) . "\">" . icon('eye') . "<span>" . (int)($s['views'] ?? 0) . "</span></span>";
    if (!empty($s['url'])) $o .= "<span class=\"cnt\" title=\"" . h(t('clics al medio')) . "\">" . icon('out') . "<span>" . (int)($s['clicks'] ?? 0) . "</span></span>";
    return $o;
}

/* ---- uso de las «Relacionadas» del hilo (migra-200, 13/11/2026) ------------------------------------------------------------- */

/** ¿Se usan las relacionadas? Apunta en related_daily cuántas veces se ha mostrado la caja ('shown': una por persona, hilo y día) y
 *  cuántas se ha pulsado uno de sus enlaces ('clicks': una por persona, origen, destino y día). Misma huella y mismos filtros que
 *  story_cuenta(): sin bots, precargas ni recargas automáticas; sin Redis se cuenta sin deduplicar. Nunca rompe la página. */
function rel_cuenta(PDO $pdo, string $col, int $de, int $a = 0): void {
    global $DDD_SECRET;
    if (!in_array($col, ['shown', 'clicks'], true) || PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return;
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('~bot|crawl|spider|slurp|fetch|monitor|curl|wget|python|headless|lighthouse|preview~i', $ua)) return;
    if (($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? '') !== '') return;
    if (($_GET['auto'] ?? '') === '1') return;
    $fp = substr(hash_hmac('sha256', client_ip() . '|' . $ua . '|' . date('Y-m-d'), $DDD_SECRET), 0, 16);
    $r = redis_conn();
    if ($r) {
        try { if (!$r->set("cnt:rel:$col:$de:$a:$fp", 1, ['nx', 'ex' => 86400])) return; }
        catch (Throwable $e) { /* Redis caido a medias: se cuenta sin deduplicar */ }
    }
    try { $pdo->exec("INSERT INTO related_daily (day, $col) VALUES (CURDATE(), 1) ON DUPLICATE KEY UPDATE $col = $col + 1"); }
    catch (Throwable $e) { /* tabla aun sin crear (migra-200 pendiente) */ }
}
/** Sumas de related_daily entre dos fechas (incluidas): ['shown' => n, 'clicks' => n]. */
function rel_sum(PDO $pdo, string $from, string $to): array {
    try { $st = $pdo->prepare("SELECT COALESCE(SUM(shown),0) shown, COALESCE(SUM(clicks),0) clicks FROM related_daily WHERE day BETWEEN ? AND ?"); $st->execute([$from, $to]); return array_map('intval', $st->fetch()); }
    catch (Throwable $e) { return ['shown' => 0, 'clicks' => 0]; }
}
