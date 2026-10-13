<?php
/**
 * lib/propuestas.php — fuentes propuestas por los usuarios (medios y substacks/newsletters).
 *
 * Quien tenga aura >= source_propose_aura (6 por defecto; moderadores y admin, siempre) puede proponer una fuente con RSS
 * en /proponer-fuente. No entra en el catalogo: queda en source_proposals con status 'pending' y la revisa un moderador en
 * /moderacion#fuentes, que ve el resultado de la prueba automatica del RSS (numero de noticias, idioma, ultimos titulos o el
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

/** Propuestas pendientes (para el contador de /moderacion). */
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
    $r = ['feed' => $feed, 'items' => 0, 'lang' => '', 'last' => null, 'titles' => [], 'error' => '', 'title' => '', 'descr' => ''];
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
            // nombre del feed: su primer <title> (el del canal; los de las noticias van despues)
            if (preg_match('#<title[^>]*>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*</title>#si', $body, $tt)) $r['title'] = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($tt[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            // descripcion del canal (13/10/2026): el primer <description> (RSS) o <subtitle> (Atom) antes del primer item/entry
            $cab = preg_split('#<(item|entry)\b#i', $body, 2)[0];
            if (preg_match('#<(?:description|subtitle)[^>]*>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*</(?:description|subtitle)>#si', $cab, $dd)) $r['descr'] = mb_substr(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($dd[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 0, 200);
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

/** Rellena el alta de una fuente a partir de su web (13/10/2026, admin y moderacion): busca el RSS (fprop_probar), y de el saca nombre
 *  (<title> del canal), descripcion, idioma; el tipo se adivina por la direccion (substack → newsletter, youtube → canal). Lo que ya
 *  venga relleno en $f se respeta. Devuelve $f completado y 'error' si no se pudo leer nada. */
function fprop_rellenar(array $f): array {
    $home = trim((string)($f['home'] ?? ''));
    if ($home !== '' && !preg_match('#^https?://#i', $home)) $home = 'https://' . $home;
    $f['home'] = $home; $f['error'] = '';
    if (!preg_match('#^https?://[^\s]+$#i', $home)) { $f['error'] = 'Pon la web (https://…).'; return $f; }
    $host = strtolower((string)parse_url($home, PHP_URL_HOST));
    if (empty($f['kind']) || $f['kind'] === 'medio') {
        if (str_contains($host, 'substack.com')) $f['kind'] = 'newsletter';
        elseif (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) $f['kind'] = 'youtube';
    }
    $feed = trim((string)($f['feed'] ?? ''));
    if (($f['kind'] ?? '') === 'youtube' && $feed === '') { $e = null; $feed = (string)(youtube_feed_de($home, $e) ?? ''); }
    $r = fprop_probar($feed, $home);
    if (!$r['items']) { $f['error'] = 'No se ha podido leer ningún RSS de esa web: ' . (string)$r['error']; return $f; }
    $f['feed'] = (string)$r['feed'];
    if (trim((string)($f['name'] ?? '')) === '' && $r['title'] !== '') $f['name'] = mb_substr(preg_replace('/\s*[|\-–—:]\s*(RSS|Feed|Noticias|News|Últimas noticias|Portada).*$/iu', '', $r['title']), 0, 48);
    if (trim((string)($f['descr'] ?? '')) === '' && $r['descr'] !== '') $f['descr'] = $r['descr'];
    if (trim((string)($f['lang'] ?? '')) === '' && preg_match('/^[a-z]{2}$/', (string)$r['lang'])) $f['lang'] = (string)$r['lang'];
    $f['items'] = (int)$r['items']; $f['titles'] = $r['titles'];
    return $f;
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

/* ---- añadirse una fuente pegando su direccion (migra-108) -------------
 * Desde «Mis medios / Mis substacks / Mis canales» (+ Añadir), quien tenga aura >= source_add_aura pega la direccion de la web, del RSS
 * o del canal de YouTube. Si ya esta en Deltha, la sigue. Si no, se prueba: si el feed se lee, la fuente se crea al momento con
 * listed = 0 (fuera del catalogo publico), el la sigue y queda una propuesta pendiente con source_id para que un moderador la publique
 * en el catalogo (o la retire); si no se lee, queda la propuesta pendiente sin fuente y un moderador la añade a mano. */
function fadd_puede(?array $u): bool {
    if (!$u || !fuentes_on()) return false;
    if (in_array($u['level'] ?? '', ['mod', 'admin'], true)) return true;
    return (float)$u['aura'] >= (float)cfg('source_add_aura', 5);
}
/** Fuente que ya existe con ese RSS o esa web (este o no en el catalogo publico), o null. */
function fadd_buscar(PDO $pdo, string $feed, string $home): ?array {
    $f = fprop_norm($feed); $hm = fprop_norm($home);
    foreach ($pdo->query("SELECT * FROM sources") as $s) {
        if (($f !== '' && fprop_norm((string)$s['feed_portada']) === $f) || ($hm !== '' && fprop_norm((string)$s['home']) === $hm)) return $s;
    }
    return null;
}
/** Añade (o sigue) la fuente de $url para el usuario $u desde la pestaña $k. Devuelve [codigo, nombre]:
 *  ok (creada y seguida) | ya (existia: seguida) | manual (no se pudo leer: propuesta para moderacion) | pend (ya esta pendiente)
 *  | url | bloq (nombre = regla) | noyt | tope | off | aura. */
function fadd_anadir(PDO $pdo, array $u, string $k, string $url): array {
    if (!fadd_puede($u)) return ['aura', ''];
    $uid = (int)$u['user_id'];
    $url = trim($url);
    if ($url !== '' && !preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    if (mb_strlen($url) > 200 || !fprop_url_ok($url)) return ['url', ''];
    if ($b = source_blocked($pdo, $url)) return ['bloq', (string)$b];
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    $esYt = (bool)preg_match('/(^|\.)(youtube\.com|youtu\.be)$/', $host);
    if ($esYt) { if (!youtube_on()) return ['url', '']; $kind = 'youtube'; }
    elseif ($k === 'youtube') return ['noyt', ''];
    else $kind = (str_ends_with($host, '.substack.com') || $k === 'newsletter') ? 'newsletter' : 'medio';
    $dia = $pdo->prepare("SELECT COUNT(*) FROM source_proposals WHERE user_id = ? AND created > NOW() - INTERVAL 1 DAY"); $dia->execute([$uid]);
    if ((int)$dia->fetchColumn() >= max(1, (int)cfg('source_add_per_day', 10)) && !in_array($u['level'] ?? '', ['mod', 'admin'], true)) return ['tope', ''];

    $home = $url;
    if ($ya = fadd_buscar($pdo, $url, $url)) return fadd_seguir($pdo, $uid, $ya);   // tal cual la han pegado, sin salir a internet
    if ($kind === 'youtube') {
        $e = null; $feed = youtube_feed_de($url, $e);
        $r = $feed ? fprop_probar($feed, '') : ['feed' => $url, 'items' => 0, 'lang' => '', 'last' => null, 'titles' => [], 'title' => '', 'error' => (string)($e ?: 'no se ha podido sacar el feed del canal')];
    } else {
        $r = fprop_probar($url, '');
        if ($r['items'] && fprop_norm($r['feed']) === fprop_norm($url)) $home = (parse_url($url, PHP_URL_SCHEME) ?: 'https') . '://' . $host;   // pegaron el RSS: la web es su dominio
    }
    if ($r['items'] && ($ya = fadd_buscar($pdo, (string)$r['feed'], $home))) return fadd_seguir($pdo, $uid, $ya);
    if (fprop_existe($pdo, (string)$r['feed'], $home) !== null) return ['pend', ''];   // ya propuesta y pendiente de que la añadan a mano
    $name = mb_substr(trim((string)($r['title'] ?? '')) ?: preg_replace('/^www\./', '', $host), 0, 48);
    $feed = mb_substr($r['feed'] !== '' ? (string)$r['feed'] : $url, 0, 300);
    $sid = null;
    if ($r['items']) {
        try {
            $pdo->prepare("INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured, listed, added_by) VALUES (?,?,?,?,NULL,?,'',NULL,?,'',?,'ok',0,0,?)")
                ->execute([fprop_slug($pdo, $name), $kind, $name, mb_substr($home, 0, 200), (string)$r['lang'], $feed, $kind === 'youtube' ? 180 : ($kind === 'newsletter' ? 1440 : 60), $uid]);
            $sid = (int)$pdo->lastInsertId();
            fuente_fav_set($pdo, $uid, $sid, true);
        } catch (Throwable $e) { $sid = null; $r['items'] = 0; $r['error'] = 'no se pudo crear la fuente: ' . $e->getMessage(); }
    }
    $pdo->prepare("INSERT INTO source_proposals (user_id, kind, name, home, feed, note, lang, test_items, test_info, source_id) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([$uid, $kind, $name, mb_substr($home, 0, 200), $feed, $sid ? 'Añadida por su cuenta desde «Mis medios»: ya la sigue, fuera del catálogo público.' : 'Intentó añadírsela desde «Mis medios» y no se pudo leer: hay que darla de alta a mano.', (string)$r['lang'], (int)$r['items'], fprop_info($r), $sid]);
    return [$sid ? 'ok' : 'manual', $name, $kind];
}
/** La fuente ya existia: si esta activa y su tipo encendido, el usuario pasa a seguirla. */
function fadd_seguir(PDO $pdo, int $uid, array $src): array {
    if (($src['status'] ?? '') !== 'ok' || !fuente_kind_on((string)$src['kind'])) return ['off', (string)$src['name']];
    fuente_fav_set($pdo, $uid, (int)$src['source_id'], true);
    return ['ya', (string)$src['name'], (string)$src['kind']];
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
