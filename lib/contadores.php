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
 *  en cuántas de esas veces se ha pulsado alguna de sus noticias ('clicks': una por persona, caja y día, se pulse el enlace que se
 *  pulse: dos noticias distintas de la misma caja son una sola pulsación). Así los clics nunca superan los mostrados y /analitica los
 *  enseña como porcentaje (antes se apuntaba una por destino y salían cosas como «127,7 %», 27/11/2026). Misma huella y mismos
 *  filtros que story_cuenta(): sin bots, precargas ni recargas automáticas; sin Redis se cuenta sin deduplicar. Nunca rompe la página. */
/** ¿Llega la petición desde una página del propio sitio? Lo dice el navegador en Sec-Fetch-Site (same-origin); los que no mandan esa
 *  cabecera, en Referer. Un robot que sigue enlaces sin navegador no manda ninguna de las dos, y un enlace pegado o abierto desde fuera
 *  llega como «none» o «cross-site». */
function rel_de_dentro(): bool {
    $sfs = strtolower(trim((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')));
    if ($sfs !== '') return $sfs === 'same-origin';
    $ref = strtolower((string)parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST));
    $yo  = strtolower((string)preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    return $ref !== '' && $ref === $yo;
}
function rel_cuenta(PDO $pdo, string $col, int $de): void {
    global $DDD_SECRET;
    if (!in_array($col, ['shown', 'clicks'], true) || PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return;
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('~bot|crawl|spider|slurp|fetch|monitor|curl|wget|python|headless|lighthouse|preview~i', $ua)) return;
    if (($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? '') !== '') return;
    if (($_GET['auto'] ?? '') === '1') return;
    // contra los robots (27/11/2026, migra-216): el 26/11 uno recorrió los hilos siguiendo los enlaces de la caja (8.032 mostradas y 10.253
    // clics en un día). El clic solo cuenta si se pulsa desde una página de Deltha, y cada visitante tiene un máximo diario (abajo).
    if ($col === 'clicks' && !rel_de_dentro()) return;
    $fp = substr(hash_hmac('sha256', client_ip() . '|' . $ua . '|' . date('Y-m-d'), $DDD_SECRET), 0, 16);
    $r = redis_conn();
    if ($r) {
        // la clave no lleva el destino (27/11/2026): pulsar dos noticias distintas de la misma caja cuenta una sola vez, y el «mostrado»
        // conserva su clave de antes (el hueco que ocupaba el destino va como 0) para no contar dos veces el día del cambio.
        try { if (!$r->set("cnt:rel:$col:$de:0:$fp", 1, ['nx', 'ex' => 86400])) return; }
        catch (Throwable $e) { /* Redis caido a medias: se cuenta sin deduplicar */ }
        // máximo por visitante y día: rel_max_clicks_dia (20) cajas pulsadas y rel_max_shown_dia (200) cajas vistas; lo que pase de ahí no
        // es una persona leyendo y no se apunta. 0 = sin máximo. Necesita Redis, como la deduplicación.
        try {
            $max = (int)cfg($col === 'clicks' ? 'rel_max_clicks_dia' : 'rel_max_shown_dia', $col === 'clicks' ? 20 : 200);
            if ($max > 0) {
                $n = (int)$r->incr("cnt:rel:n:$col:$fp");
                if ($n === 1) $r->expire("cnt:rel:n:$col:$fp", 86400);
                if ($n > $max) return;
            }
        } catch (Throwable $e) { /* sin el máximo */ }
    }
    try { $pdo->exec("INSERT INTO related_daily (day, $col) VALUES (CURDATE(), 1) ON DUPLICATE KEY UPDATE $col = $col + 1"); }
    catch (Throwable $e) { /* tabla aun sin crear (migra-200 pendiente) */ }
}
/** Sumas de related_daily entre dos fechas (incluidas): ['shown' => n, 'clicks' => n]. */
function rel_sum(PDO $pdo, string $from, string $to): array {
    try { $st = $pdo->prepare("SELECT COALESCE(SUM(shown),0) shown, COALESCE(SUM(clicks),0) clicks FROM related_daily WHERE day BETWEEN ? AND ?"); $st->execute([$from, $to]); return array_map('intval', $st->fetch()); }
    catch (Throwable $e) { return ['shown' => 0, 'clicks' => 0]; }
}
/** Desde este día 'clicks' son cajas pulsadas y no clics sueltos: lo anterior no se puede recalcular y no se suma (27/11/2026). */
const REL_CUENTA_DESDE = '2026-11-27';
