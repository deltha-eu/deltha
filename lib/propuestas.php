<?php
/**
 * lib/propuestas.php — fuentes propuestas por los usuarios (medios y substacks/newsletters).
 *
 * Quien tenga aura >= source_propose_aura (6 por defecto; moderadores y admin, siempre) puede proponer una fuente con RSS
 * en /proponer-fuente.php. No entra en el catalogo: queda en source_proposals con status 'pending' y la revisa un moderador en
 * /moderacion.php#fuentes, que ve el resultado de la prueba automatica del RSS (numero de noticias, idioma, ultimos titulos o el
 * error), puede volver a probarla y la aprueba (se crea en sources, activa y sin destacar) o la rechaza con un motivo. En ambos
 * casos quien la propuso recibe un mensaje privado del moderador.
 * Topes: source_propose_max_pending pendientes a la vez por persona y source_propose_per_day propuestas en 24 h.
 * Tabla y config: sql/migra-057-fuentes-propuestas.sql.
 */
declare(strict_types=1);
require_once __DIR__ . '/fuentes.php';

/** ¿Puede este usuario proponer fuentes? */
function fprop_puede(?array $u): bool {
    if (!$u || !fuentes_on()) return false;
    if (in_array($u['level'] ?? '', ['mod', 'admin'], true)) return true;
    return (float)$u['aura'] >= (float)cfg('source_propose_aura', 6);
}

/** Propuestas pendientes (para el contador de /moderacion.php). */
function fprop_pendientes(PDO $pdo): int {
    static $n = null;
    if ($n !== null) return $n;
    try { $n = (int)$pdo->query("SELECT COUNT(*) FROM source_proposals WHERE status = 'pending'")->fetchColumn(); } catch (Throwable $e) { $n = 0; }
    return $n;
}

/** URL publica http(s) en los puertos normales y que no apunte a la red interna del servidor (el servidor la va a descargar). */
function fprop_url_ok(string $url): bool {
    if (!preg_match('#^https?://[^\s\'"<>]+$#i', $url)) return false;
    $p = parse_url($url);
    if (!$p || empty($p['host']) || isset($p['user']) || isset($p['pass'])) return false;
    if (isset($p['port']) && !in_array((int)$p['port'], [80, 443], true)) return false;
    $host = strtolower($p['host']);
    if ($host === 'localhost' || !preg_match('/\.[a-z]{2,}$/', $host)) return false;
    $ips = @gethostbynamel($host) ?: [];
    if (!$ips) return false;
    foreach ($ips as $ip) if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
    return true;
}

/** Enlace relativo de una pagina -> absoluto. */
function fprop_abs(string $href, string $base): string {
    $href = html_entity_decode(trim($href), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (preg_match('#^https?://#i', $href)) return $href;
    $p = parse_url($base); $sch = ($p['scheme'] ?? 'https'); $host = $p['host'] ?? '';
    if (str_starts_with($href, '//')) return "$sch:$href";
    if (str_starts_with($href, '/')) return "$sch://$host$href";
    $dir = preg_replace('#/[^/]*$#', '/', $p['path'] ?? '/');
    return "$sch://$host" . $dir . $href;
}

/** Normaliza una URL para comparar (sin esquema, sin www., sin barra final, minusculas). */
function fprop_norm(string $url): string {
    $u = strtolower(trim($url));
    $u = preg_replace('#^https?://(www\.)?#', '', $u);
    return rtrim($u, '/');
}

/**
 * Prueba el RSS de una propuesta. Si no se da RSS (o lo dado es una pagina web), lo busca en la pagina (<link rel="alternate"
 * type="application/rss+xml|atom+xml">) y, si no, prueba /feed. Devuelve:
 * ['feed' => URL del RSS que funciona (o la probada), 'items' => n, 'lang' => 'es'|'', 'last' => fecha de la mas reciente|null,
 *  'titles' => [hasta 5], 'error' => '' o el motivo].
 */
function fprop_probar(string $feed, string $home): array {
    $r = ['feed' => $feed, 'items' => 0, 'lang' => '', 'last' => null, 'titles' => [], 'error' => ''];
    $timeout = max(3, min(8, (int)cfg('sources_timeout_sec', 8)));
    $cands = [];
    if ($feed !== '') $cands[] = $feed;
    if ($home !== '' && $home !== $feed) $cands[] = $home;
    $vistas = []; $errores = [];
    for ($i = 0; $i < count($cands) && count($vistas) < 5; $i++) {
        $url = $cands[$i];
        if (isset($vistas[$url])) continue;
        $vistas[$url] = true;
        if (!fprop_url_ok($url)) { $errores[] = "$url: dirección no válida"; continue; }
        $body = http_get($url, $timeout, 4000000);
        if ($body === null) { $errores[] = "$url: sin respuesta" . (($tr = http_last()) ? ' (' . implode(' · ', $tr) . ')' : ''); continue; }
        $items = fuentes_parse_rss($body);
        if ($items) {
            usort($items, fn($a, $b) => strcmp($b['pub_date'], $a['pub_date']));
            $r['feed'] = $url; $r['items'] = count($items); $r['lang'] = fuentes_feed_lang($body);
            $r['last'] = $items[0]['pub_date'];
            $r['titles'] = array_map(fn($it) => mb_strimwidth((string)$it['title'], 0, 110, '…'), array_slice($items, 0, 5));
            $r['error'] = '';
            return $r;
        }
        // no es un feed: si es una pagina, buscar sus RSS anunciados; si no anuncia ninguno, probar /feed
        if (stripos($body, '<html') !== false || stripos($body, '<head') !== false) {
            $found = 0;
            if (preg_match_all('#<link\b[^>]*>#i', $body, $mm)) foreach ($mm[0] as $tag) {
                if (!preg_match('#type\s*=\s*["\']application/(rss|atom)\+xml["\']#i', $tag) || !preg_match('#href\s*=\s*["\']([^"\']+)["\']#i', $tag, $hh)) continue;
                if (preg_match('#/comments/feed/?$#i', $hh[1])) continue;   // WordPress: el de comentarios no
                $cands[] = fprop_abs($hh[1], $url); if (++$found >= 2) break;
            }
            if (!$found) $cands[] = rtrim($url, '/') . '/feed';
            $errores[] = "$url: es una página web, no un RSS" . ($found ? '; se prueba el RSS que anuncia' : '; no anuncia ningún RSS');
        } else $errores[] = "$url: no es un RSS ni Atom legible";
    }
    $r['error'] = mb_substr(implode(' · ', $errores) ?: 'no se ha podido probar', 0, 580);
    return $r;
}

/** Texto guardado de la prueba (source_proposals.test_info). */
function fprop_info(array $r): string {
    if ($r['items']) return mb_substr("idioma " . ($r['lang'] ?: '?') . " · última " . ($r['last'] ?? '?') . "\n" . implode("\n", $r['titles']), 0, 600);
    return mb_substr('ERROR: ' . $r['error'], 0, 600);
}

/** ¿Ya esta en el catalogo (mismo RSS o misma web) o propuesta y pendiente? Devuelve el nombre, o null. */
function fprop_existe(PDO $pdo, string $feed, string $home): ?string {
    $f = fprop_norm($feed); $hm = fprop_norm($home);
    foreach ($pdo->query("SELECT name, home, feed_portada FROM sources") as $s) {
        if (($f !== '' && fprop_norm((string)$s['feed_portada']) === $f) || ($hm !== '' && fprop_norm((string)$s['home']) === $hm)) return (string)$s['name'];
    }
    foreach ($pdo->query("SELECT name, home, feed FROM source_proposals WHERE status = 'pending'") as $s) {
        if (($f !== '' && fprop_norm((string)$s['feed']) === $f) || ($hm !== '' && fprop_norm((string)$s['home']) === $hm)) return (string)$s['name'];
    }
    return null;
}

/** Slug libre a partir del nombre (a-z, 0-9 y guion, hasta 24). */
function fprop_slug(PDO $pdo, string $name): string {
    $s = strtolower((string)(@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name));
    $s = trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    $s = substr($s, 0, 24) ?: 'fuente';
    $s = rtrim($s, '-');
    if (strlen($s) < 2) $s = 'fuente';
    $st = $pdo->prepare("SELECT 1 FROM sources WHERE slug = ?");
    $base = $s; $n = 2;
    while (true) { $st->execute([$s]); if (!$st->fetchColumn()) return $s; $suf = '-' . $n++; $s = substr($base, 0, 24 - strlen($suf)) . $suf; }
}

/** Mensaje privado del moderador a quien propuso (si no es la misma persona). Sin mensajes activos o si falla, no pasa nada. */
function fprop_avisar(PDO $pdo, int $from, int $to, string $txt): void {
    if ($from === $to || $to <= 0) return;
    try { $pdo->prepare("INSERT INTO messages (from_id, to_id, content) VALUES (?,?,?)")->execute([$from, $to, $txt]); } catch (Throwable $e) {}
}
