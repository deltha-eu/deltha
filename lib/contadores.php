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
