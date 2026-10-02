<?php
/**
 * lib/fuentes.php — fuentes externas «pintadas» con Deltha (solo lectura).
 *
 * Una fuente (tabla sources) es otro sitio con RSS. Dos tipos (sources.kind):
 *  - agregador (Menéame): feed de portada y feed de cola, fases nuevas -> portada, votos y comentarios de la
 *    fuente; se ve en /fuente.php?f=slug con Portada / Populares / Nuevas.
 *  - medio (Jot Down, Revista Mercurio...): un solo feed, sin fases ni votos; cada noticia lleva un boton
 *    «enviar al radar» que abre /enviar.php prerrellenado. Cada usuario marca como favoritos los que quiera, sin
 *    limite (boton «Seguir» en el catalogo, en la fuente, en cada noticia y en el hilo; tabla user_media); en el rail salen
 *    los media_max_per_user que han publicado mas recientemente y en /mis-fuentes.php (pestañas del perfil), todos.
 *  - newsletter (Substack): igual que un medio, en su grupo «Mis substacks».
 *  - youtube (canal de YouTube): igual que un medio, en su grupo «Mis canales». El feed es el Atom publico del canal
 *    (youtube.com/feeds/videos.xml, sin clave de API); cada item es un video, con su miniatura descargada a /media como
 *    cualquier otra foto. No se incrusta el reproductor: el enlace lleva a YouTube. Se apagan todos con config.sources_youtube.
 *
 * El cron (promocionar.php, cada minuto) llama a fuentes_tick(): lee los feeds de cada fuente cuando vence su
 * interval_min y guarda los items en source_items, uno por id externo (meneame:link_id o guid), actualizando
 * fase, votos y comentarios. Nada de esto entra en stories, en el δ, en Procesos ni en las estadisticas.
 *
 * Imagenes (politica del sitio: sin recursos de terceros): la miniatura del agregador se descarga tal cual a
 * /media/fuentes (image = 'fuentes/x.webp'); en los medios se usa la imagen del feed o, si no hay, la og:image
 * de la noticia, procesada como en un envio (imagen_desde_bytes -> /media/x.jpg, image = 'x.jpg').
 */
declare(strict_types=1);
require_once __DIR__ . '/imagen.php';   // http_get(), imagen_desde_bytes(), imagen_desde_og()

const DDD_FUENTES_DIR = DDD_MEDIA_DIR . '/fuentes';

function fuentes_on(): bool { return (string)cfg('sources_enabled', '1') === '1'; }
/** Agregadores (Meneame, Mediatize, Renegados, Burbuja, Areopago) activos? config.sources_agregadores, apagados por defecto (migra-070):
 *  fuentes_activas() no los devuelve, asi que desaparecen del catalogo, el rail, la franja, la columna derecha, el pie, /ultimo.php y /fuente.php,
 *  y el cron no lee sus RSS. Siguen en admin > Fuentes. */
function agregadores_on(): bool { return (string)cfg('sources_agregadores', '0') === '1'; }
/** Canales de YouTube (fuentes de tipo «youtube») activos? config.sources_youtube (migra-103). Apagados, igual que los agregadores:
 *  fuera de todo salvo admin > Fuentes, y el cron no lee sus feeds. */
function youtube_on(): bool { return (string)cfg('sources_youtube', '1') === '1'; }
/** ¿Esta encendido este tipo de fuente? Medios y substacks, siempre; agregadores y YouTube tienen interruptor en Config. */
function fuente_kind_on(string $kind): bool { return $kind === 'agregador' ? agregadores_on() : ($kind === 'youtube' ? youtube_on() : true); }

/** Fuentes activas (cacheado por peticion). $kind: '' todas | agregador | medio */
function fuentes_activas(PDO $pdo, string $kind = ''): array {
    static $rows = null;
    if ($rows === null) {
        try { $rows = $pdo->query("SELECT * FROM sources WHERE status = 'ok' ORDER BY kind, name")->fetchAll(); }
        catch (Throwable $e) { $rows = []; }   // tabla aun sin crear
        $rows = array_values(array_filter($rows, fn($s) => fuente_kind_on((string)($s['kind'] ?? 'agregador'))));   // agregadores o YouTube apagados
    }
    if ($kind === '') return $rows;
    return array_values(array_filter($rows, fn($s) => ($s['kind'] ?? 'agregador') === $kind));
}
/* ---- idioma de las fuentes ------------------------------------------
 * sources.lang: codigo de dos letras (es, en, pt…); '' = sin saber. Se elige en admin -> Fuentes o, si se deja en «detectar», se toma del
 * propio RSS (<language>, dc:language o xml:lang del Atom) al darla de alta y en cada lectura mientras siga vacio.
 * users.src_mylang = 1: el usuario quiere ver solo fuentes en su idioma (el de la interfaz, lang()). Las de idioma desconocido se ven.
 * Filtra menus, listas y catalogo; la pagina de una fuente (/fuente.php) se sigue abriendo con su enlace. */
/** ¿Solo fuentes en mi idioma? Con cuenta, users.src_mylang (perfil o casilla de /medios.php y /ultimo.php, es la misma);
 *  sin cuenta, cookie ddd_srclang que solo existe si la persona marca la casilla. */
function fuentes_solo_mi_idioma(): bool { $u = user(); return $u ? !empty($u['src_mylang']) : ($_COOKIE['ddd_srclang'] ?? '') === '1'; }
/** Casilla «Solo fuentes en mi idioma» para las listas de fuentes: cambia la misma preferencia que el perfil (via /cambiar-skin.php?m=)
 *  y vuelve a la pagina. Se guarda con el boton «Guardar» de al lado (no se envia sola al marcarla). */
function fuentes_casilla_idioma(): string {
    return "<form class=\"srclang\" method=\"get\" action=\"/cambiar-skin.php\" autocomplete=\"off\"><input type=\"hidden\" name=\"m\" value=\"0\"><label><input type=\"checkbox\" name=\"m\" value=\"1\""
         . (fuentes_solo_mi_idioma() ? ' checked' : '') . "> " . t('Solo fuentes y envíos en mi idioma') . " <small>(" . h(DDD_LANGS[lang()] ?? lang()) . ")</small></label>"
         . " <button type=\"submit\">" . t('Guardar') . "</button></form>";
}
function fuente_en_mi_idioma(array $src): bool {
    if (!fuentes_solo_mi_idioma()) return true;
    $l = (string)($src['lang'] ?? '');
    return $l === '' || $l === lang();
}
/** fuentes_activas() menos las de otro idioma si el usuario lo ha pedido. */
function fuentes_visibles(PDO $pdo, string $kind = ''): array {
    return array_values(array_filter(fuentes_activas($pdo, $kind), 'fuente_en_mi_idioma'));
}
/** Idioma declarado por un feed (RSS <language>, dc:language, Atom xml:lang): «es-ES» -> «es». '' si no dice nada. */
function fuentes_feed_lang(string $xml): string {
    $prev = libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    if (!$doc) return '';
    $l = '';
    if ($doc->getName() === 'feed') $l = (string)($doc->attributes('http://www.w3.org/XML/1998/namespace')->lang ?? '');
    elseif (isset($doc->channel)) {
        $l = (string)$doc->channel->language;
        if ($l === '') $l = (string)(fuentes_ns($doc->channel, 'http://purl.org/dc/elements/1.1/')->language ?? '');
    }
    return preg_match('/^\s*([a-z]{2})\b/i', $l, $m) ? strtolower($m[1]) : '';
}
function fuente_by_slug(PDO $pdo, string $slug): ?array {
    foreach (fuentes_activas($pdo) as $s) if ($s['slug'] === $slug) return $s;
    return null;
}
function fuente_url(array $src, string $view = ''): string {
    return '/fuente.php?f=' . rawurlencode($src['slug']) . ($view && $view !== 'portada' ? '&v=' . $view : '');
}
function fuente_es_medio(array $src): bool { return in_array($src['kind'] ?? 'agregador', ['medio', 'newsletter', 'youtube'], true); }   // sin fases ni votos, con «enviar al radar»
/** Grupos con favoritos del usuario: tipo => [clave de config de cuantos salen en el menu, etiqueta del grupo] */
const DDD_MEDIA_KINDS = ['medio' => ['media_max_per_user', 'Mis medios'], 'newsletter' => ['news_max_per_user', 'Mis substacks'], 'youtube' => ['yt_max_per_user', 'Mis canales'], 'agregador' => ['agg_max_per_user', 'Mis agregadores']];
/** ¿Se puede seguir? Medios y substacks siempre; agregadores, mientras esten encendidos (config.sources_agregadores). */
function fuente_seguible(array $src): bool { return fuente_es_medio($src) || (($src['kind'] ?? '') === 'agregador' && agregadores_on()); }
/** Grupos del rail y de /ultimo.php, en su orden: tipo => [etiqueta, icono]. Los dos primeros se eligen con el boton «Seguir» (DDD_MEDIA_KINDS). */
const DDD_ULTIMO_KINDS = ['medio' => ['Mis medios', 'news'], 'newsletter' => ['Mis substacks', 'letter'], 'youtube' => ['Mis canales', 'play'], 'agregador' => ['Mis agregadores', 'agg']];   // mismo orden que las portadas: Medios, Substacks
/** Fuentes de las que /ultimo.php saca las noticias de un grupo: TODOS los favoritos del usuario (sin limite) o, si no tiene
 *  ninguno / no tiene cuenta, las del menu (medios_menu(): las que han publicado mas recientemente; agregadores: los destacados). */
function ultimo_fuentes(PDO $pdo, ?array $u, string $kind): array {
    if ($mias = mis_medios_recientes($pdo, $u, $kind)) return $mias;
    return medios_menu($pdo, $u, 0, $kind);
}
/** Cuantas fuentes de un grupo salen en el menu (no cuantas se pueden guardar: eso no tiene limite). */
function medios_max(string $kind): int { return max(1, (int)cfg(DDD_MEDIA_KINDS[$kind][0] ?? 'media_max_per_user', 3)); }

/** Fuentes de un tipo que el usuario tiene como favoritas (en el orden en que las guardo). Vacio si no tiene. */
function mis_medios(PDO $pdo, ?array $u, string $kind = 'medio'): array {
    static $cache = [];
    if (!$u || !fuente_kind_on($kind)) return [];
    $k = $u['user_id'] . '|' . $kind;
    if (isset($cache[$k])) return $cache[$k];
    try {
        $st = $pdo->prepare("SELECT s.* FROM user_media um JOIN sources s ON s.source_id = um.source_id WHERE um.user_id = ? AND s.status = 'ok' AND s.kind = ? ORDER BY um.pos");
        $st->execute([(int)$u['user_id'], $kind]);
        return $cache[$k] = $st->fetchAll();
    } catch (Throwable $e) { return $cache[$k] = []; }
}
/** Añade o quita una fuente de los favoritos del usuario (sin limite). */
function fuente_fav_set(PDO $pdo, int $uid, int $sid, bool $on): void {
    if ($on) $pdo->prepare("INSERT IGNORE INTO user_media (user_id, source_id, pos) SELECT ?, ?, LEAST(COALESCE(MAX(pos), 0) + 1, 65535) FROM user_media WHERE user_id = ?")->execute([$uid, $sid, $uid]);
    else     $pdo->prepare("DELETE FROM user_media WHERE user_id = ? AND source_id = ?")->execute([$uid, $sid]);
}
/** ¿Es favorita del usuario actual? */
function fuente_es_fav(array $src): bool {
    global $pdo;
    foreach (mis_medios($pdo, user(), (string)($src['kind'] ?? 'medio')) as $m) if ((int)$m['source_id'] === (int)$src['source_id']) return true;
    return false;
}
/** Favoritos de un tipo, la que ha publicado mas recientemente primero (las que aun no tienen noticias, al final); con «solo en mi
 *  idioma» se quitan las de otro idioma. */
function mis_medios_recientes(PDO $pdo, ?array $u, string $kind): array {
    $list = array_values(array_filter(mis_medios($pdo, $u, $kind), 'fuente_en_mi_idioma'));
    $ult = fuentes_ultima_noticia($pdo);
    usort($list, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
    return $list;
}
/** Fuente del catalogo (medio o substack) a la que pertenece un dominio («jotdown.es»), para ofrecer «Seguir» desde un envio. */
function fuente_por_dominio(PDO $pdo, string $dominio): ?array {
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (fuentes_activas($pdo) as $s) if (fuente_es_medio($s) && ($s['kind'] ?? '') !== 'youtube' && ($d = (string)source_of((string)$s['home'])) !== '') $map[$d] ??= $s;
    }
    return $map[strtolower($dominio)] ?? null;
}
/** Boton «+ Seguir» / «✓ Siguiendo» de un medio o substack (el + y el ✓ los pone el CSS), solo para registrados; sin JS: un POST a
 *  /fav-fuente.php que vuelve a la misma pagina. Pulsarlo cuando ya se sigue, deja de seguir. $nombre: «Seguir Jot Down» (pagina de la
 *  fuente, hilo: ahi no se ve a que medio se refiere); sin el, «Seguir» a secas (filas del catalogo y tarjetas, que ya llevan el nombre). */
function fuente_fav_form(array $src, bool $nombre = false): string {
    if (!user() || !fuente_seguible($src)) return '';
    $on = fuente_es_fav($src);
    $title = h($on ? t('dejar de seguir %s', $src['name']) : t('Seguir %s', $src['name']));
    return "<form class=\"seguir" . ($on ? ' on' : '') . "\" method=\"post\" action=\"/fav-fuente.php\"><input type=\"hidden\" name=\"f\" value=\"" . (int)$src['source_id'] . "\"><input type=\"hidden\" name=\"act\" value=\"" . ($on ? 'quitar' : 'guardar') . "\">"
         . "<button title=\"$title\">" . h($on ? t('Siguiendo') : ($nombre ? t('Seguir %s', $src['name']) : t('Seguir'))) . "</button></form>";
}
/** Fuentes de un tipo que se ofrecen en el menu: de los favoritos del usuario, los que han publicado mas recientemente (una noticia
 *  por fuente: «los tres ultimos sin repetir fuente») o, si no tiene favoritos (o no tiene cuenta),
 *  en medios y substacks las que han publicado mas recientemente (siempre hay algo fresco, y van rotando solas); en agregadores las
 *  destacadas en admin (sources.featured). Si no sale ninguna, las primeras del catalogo. Nunca mas del maximo del tipo. */
/** Fecha de la noticia mas reciente de cada fuente: source_id => pub_date (una consulta por peticion). */
function fuentes_ultima_noticia(PDO $pdo): array {
    static $r = null;
    if ($r === null) {
        try { $r = $pdo->query("SELECT source_id, MAX(pub_date) FROM source_items GROUP BY source_id")->fetchAll(PDO::FETCH_KEY_PAIR); }
        catch (Throwable $e) { $r = []; }
    }
    return $r;
}
function medios_menu(PDO $pdo, ?array $u, int $max = 0, string $kind = 'medio'): array {
    $max = $max ?: medios_max($kind);
    $list = mis_medios_recientes($pdo, $u, $kind);   // «solo en mi idioma» tambien quita las favoritas en otro
    if (!$list && $kind !== 'agregador') {
        $ult = fuentes_ultima_noticia($pdo);
        $list = array_values(array_filter(fuentes_visibles($pdo, $kind), fn($m) => isset($ult[$m['source_id']])));
        usort($list, fn($a, $b) => strcmp($ult[$b['source_id']], $ult[$a['source_id']]));
    }
    if (!$list) $list = array_values(array_filter(fuentes_visibles($pdo, $kind), fn($m) => !empty($m['featured'])));
    if (!$list) $list = fuentes_visibles($pdo, $kind);
    return array_slice($list, 0, $max);
}

/* ---- lectura de feeds (cron) ---------------------------------------- */

/** Llamada cada minuto desde promocionar.php: lee las fuentes cuyo intervalo haya vencido. Devuelve lineas de log. */
function fuentes_tick(PDO $pdo, bool $force = false, ?int $maxN = null, ?int $maxSec = null): array {
    if (!fuentes_on()) return [];
    $log = [];
    // Tope por pasada: con muchas fuentes vencidas a la vez (p. ej. 50 substacks recien dados de alta, cada una con 20 fotos que
    // descargar y convertir) una sola pasada tardaba minutos, saturaba la CPU y la web daba 524 en Cloudflare. Ahora se leen como
    // mucho sources_per_tick fuentes y, pasados sources_tick_seconds, no se empieza ninguna mas; las demas, en el minuto siguiente,
    // primero las que llevan mas tiempo sin leerse (las nuevas, sin last_fetch, antes que nadie).
    $maxN   = $maxN   ?? max(1, (int)cfg('sources_per_tick', 3));
    $maxSec = $maxSec ?? max(5, (int)cfg('sources_tick_seconds', 40));
    $t0 = time(); $n = 0;
    $due = array_values(array_filter(fuentes_activas($pdo), fn($src) => empty($src['relay']) && ($force || !$src['last_fetch'] || time() - strtotime($src['last_fetch']) >= max(1, (int)$src['interval_min']) * 60 - 5)));   // relay = 1: la lee el lector local (TIBERIO), no el cron
    usort($due, fn($a, $b) => strcmp((string)$a['last_fetch'], (string)$b['last_fetch']));   // NULL ('') primero, luego la mas antigua
    foreach ($due as $i => $src) {
        if ($n >= $maxN || time() - $t0 >= $maxSec) { $log[] = "fuentes: " . (count($due) - $i) . " pendientes para la proxima pasada"; break; }
        $n++;
        try { $log = array_merge($log, fuente_actualizar($pdo, $src)); }
        catch (Throwable $e) {
            $log[] = "fuente {$src['slug']}: ERROR " . $e->getMessage();
            $pdo->prepare("UPDATE sources SET last_fetch = NOW(), last_error = ? WHERE source_id = ?")->execute([mb_substr($e->getMessage(), 0, 255), $src['source_id']]);
        }
    }
    return $log;
}

/** Lee los feeds de una fuente y sincroniza source_items.
 *  $xmls (lector local, /api/lector.php): los feeds ya descargados desde fuera, ['portada' => xml, 'nuevas' => xml]; entonces el
 *  servidor no pide nada a la fuente (ni feeds ni fotos: las fotos pendientes se apuntan en $GLOBALS['ddd_relay_pend'] para que
 *  las traiga el lector). Una fuente con relay = 1 no se lee nunca desde el servidor: su web rechaza la IP del droplet. */
function fuente_actualizar(PDO $pdo, array $src, ?array $xmls = null): array {
    $log = []; $err = [];
    if (!empty($src['relay']) && $xmls === null) return ["fuente {$src['slug']}: la lee el lector local (relay), no el servidor"];
    $GLOBALS['DDD_RELAY'] = $xmls !== null; $GLOBALS['ddd_relay_pend'] = [];
    $timeout = max(3, (int)cfg('sources_timeout_sec', 8));
    $feeds = ['portada' => $src['feed_portada'], 'nuevas' => $src['feed_nuevas']];
    fuente_imagen($src, [], true);   // contadores de imagenes a cero para esta fuente
    // primero la cola y luego la portada: si un item esta en las dos, gana 'portada'
    foreach (['nuevas', 'portada'] as $phase) {
        $url = trim((string)$feeds[$phase]);
        if ($url === '') continue;
        $xml = $xmls !== null ? (isset($xmls[$phase]) && $xmls[$phase] !== '' ? (string)$xmls[$phase] : null) : http_get($url, $timeout, 4000000);
        if ($xml === null && $xmls !== null) { $err[] = "$phase: el lector local no pudo descargarlo" . (!empty($xmls["error_$phase"]) ? ' (' . mb_substr((string)$xmls["error_$phase"], 0, 120) . ')' : ''); continue; }
        if ($xml === null) { $err[] = "$phase: sin respuesta" . (($tr = http_last()) ? ' (' . implode(' · ', $tr) . ')' : ''); continue; }   // con el rastro HTTP: 403 = veto del CDN a la IP
        $items = fuentes_parse_rss($xml);
        if (!$items) { $err[] = "$phase: feed vacio o ilegible"; continue; }
        if ((string)($src['lang'] ?? '') === '' && ($fl = fuentes_feed_lang($xml)) !== '') {   // idioma aun sin fijar: el que declare el feed
            $pdo->prepare("UPDATE sources SET lang = ? WHERE source_id = ? AND lang = ''")->execute([$fl, $src['source_id']]);
            $src['lang'] = $fl; $log[] = "fuente {$src['slug']}: idioma del feed «{$fl}»";
        }
        // antiguedad maxima: algunos feeds (El Pais / Babelia) cuelan articulos de hace anos entre los nuevos; fuera lo que pase de
        // sources_max_age_days (0 = sin limite). Lo ya guardado y que deje de verse caduca solo a los sources_keep_days.
        if (($maxAge = (int)cfg('sources_max_age_days', 45)) > 0) {
            $lim = date('Y-m-d H:i:s', time() - $maxAge * 86400); $n0 = count($items);
            $items = array_values(array_filter($items, fn($it) => $it['pub_date'] >= $lim));
            if ($n0 - count($items)) $log[] = "fuente {$src['slug']} $phase: " . ($n0 - count($items)) . " items de hace mas de $maxAge dias, descartados";
            if (!$items) { $err[] = "$phase: todo lo del feed tiene mas de $maxAge dias"; continue; }
        }
        if (($src['kind'] ?? '') === 'youtube' && (string)cfg('youtube_titulares', '1') === '1') {   // titulares de YouTube: sin GRITOS ni emojis
            foreach ($items as &$it) { $it['title'] = titular_normal($it['title']); $it['content'] = sin_emojis($it['content']); }
            unset($it);
        }
        if (!empty($items[0]['smf'])) { $k = fuente_smf_completar($pdo, $src, $items, $timeout); if ($k) $log[] = "fuente {$src['slug']}: $k mensajes leidos del foro"; }
        if (str_contains($url, 'renegados.es')) { $k = fuente_renegados_completar($items, $timeout); if ($k) $log[] = "fuente {$src['slug']} $phase: votos y comentarios de la API para $k items"; }
        [$new, $upd] = fuente_guardar($pdo, $src, $phase, $items);
        $log[] = "fuente {$src['slug']} $phase: " . count($items) . " items, $new nuevos, $upd actualizados";
    }
    // caducidad: lo que lleva sources_keep_days dias sin aparecer en ningun feed
    $keep = max(1, (int)cfg('sources_keep_days', 7));
    $st = $pdo->prepare("SELECT item_id, image FROM source_items WHERE source_id = ? AND last_seen < NOW() - INTERVAL ? DAY");
    $st->execute([$src['source_id'], $keep]);
    $old = $st->fetchAll();
    if ($old) {
        foreach ($old as $o) if ($o['image'] && is_file(DDD_MEDIA_DIR . '/' . $o['image'])) @unlink(DDD_MEDIA_DIR . '/' . $o['image']);
        $pdo->prepare("DELETE FROM source_items WHERE source_id = ? AND last_seen < NOW() - INTERVAL ? DAY")->execute([$src['source_id'], $keep]);
        $log[] = "fuente {$src['slug']}: " . count($old) . " caducados";
    }
    $pdo->prepare("UPDATE sources SET last_fetch = NOW(), last_error = ? WHERE source_id = ?")
        ->execute([$err ? mb_substr(implode(' · ', $err), 0, 255) : null, $src['source_id']]);
    if ($err) $log[] = "fuente {$src['slug']}: " . implode(' · ', $err);
    return $log;
}

/** Foros SMF (Areopago): el RSS no trae ni el autor ni el numero del mensaje dentro del hilo (#11515). Se leen de la pagina del
 *  propio mensaje, solo para los items que aun no estan guardados y con tope sources_smf_per_run por pasada (una peticion por mensaje).
 *  El numero va a «comments» (es lo que se muestra como respuestas) y el autor a «author». */
function fuente_smf_completar(PDO $pdo, array $src, array &$items, int $timeout): int {
    $sel = $pdo->prepare("SELECT 1 FROM source_items WHERE source_id = ? AND ext_id = ?");
    $max = max(0, (int)cfg('sources_smf_per_run', 12)); $n = 0;
    foreach ($items as &$it) {
        if (empty($it['smf'])) continue;
        $sel->execute([$src['source_id'], $it['ext_id']]);
        if ($sel->fetchColumn() || $n >= $max) continue;
        $n++;
        $html = http_get($it['url'], $timeout, 3000000);
        if ($html === null) continue;
        $id = (int)$it['smf']['msg'];
        if (preg_match('/msg=' . $id . '["&][^>]*class="smalltext">#(\d+)</', $html, $m)) $it['comments'] = (int)$m[1];
        // autor: el bloque del mensaje (id="msgN") y, dentro, el nombre en div.poster > h4
        if (preg_match('/id="msg' . $id . '".{0,4000}?<div class="poster">.*?<h4>\s*(?:<a[^>]*>)?\s*([^<]{1,64}?)\s*</su', $html, $m)) $it['author'] = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: null;
    }
    unset($it);
    return $n;
}
/** Renegados: el RSS no trae votos ni comentarios, pero su API publica (api.renegados.es/api/v1/posts, 25 por pagina, ?page=N)
 *  los da con el slug del post, junto con la URL del articulo original. Se leen sources_renegados_pages paginas (2) por pasada,
 *  cacheadas un minuto en fichero para no repetir entre «nuevas» y «portada», y se cruzan por el slug del <link> (/posts/slug).
 *  Ademas la url pasa a ser el articulo original (como en Meneame) y thread_url, el post en Renegados. Devuelve items completados. */
function fuente_renegados_completar(array &$items, int $timeout): int {
    static $map = null;
    if ($map === null) {
        $map = [];
        $cache = sys_get_temp_dir() . '/deltha-renegados.json';
        if (is_file($cache) && time() - filemtime($cache) < 60) $map = json_decode((string)file_get_contents($cache), true) ?: [];
        else {
            $pages = max(1, min(10, (int)cfg('sources_renegados_pages', 2)));
            for ($p = 1; $p <= $pages; $p++) {
                $json = http_get('https://api.renegados.es/api/v1/posts?page=' . $p, $timeout, 2000000);
                if ($json === null) break;
                $d = json_decode($json, true);
                if (!is_array($d['data'] ?? null) || !$d['data']) break;
                foreach ($d['data'] as $row) if (!empty($row['slug'])) $map[$row['slug']] = ['v' => (int)($row['vote_count'] ?? 0), 'c' => (int)($row['comment_count'] ?? 0), 'u' => (string)($row['url'] ?? '')];
            }
            if ($map) @file_put_contents($cache, json_encode($map));
        }
    }
    $n = 0;
    foreach ($items as &$it) {
        if (!preg_match('#/posts/([^/?\#]+)#', $it['thread_url'] ?: $it['url'], $m) || !isset($map[$m[1]])) continue;
        $r = $map[$m[1]];
        $it['votes'] = $r['v']; $it['comments'] = max((int)$it['comments'], $r['c']);
        if ($r['u'] !== '' && preg_match('#^https?://#i', $r['u'])) { $it['thread_url'] = $it['url']; $it['url'] = mb_substr($r['u'], 0, 600); $it['source'] = ($so = source_of($r['u'])) ? mb_substr($so, 0, 100) : null; }
        $n++;
    }
    unset($it);
    return $n;
}
/** Fuentes sin votos (foros SMF): no se pintan las flechas ni «↑ n» en la columna. */
/** Fuentes cuyo feed no trae votos (se muestra el numero de comentarios / del mensaje en vez de los votos): foros SMF (action=.xml)
 *  y XenForo (index.rss: Burbuja). Renegados tampoco los trae en el RSS, pero es un agregador con votos y Angel quiere ver la
 *  «burbuja» de votos en su pagina (skin Phantom), asi que se pinta el widget normal, a 0 hasta que su feed los incluya. */
const DDD_FEEDS_SIN_VOTOS = ['action=.xml', 'index.rss'];
function fuente_sin_votos(array $src): bool {
    foreach (DDD_FEEDS_SIN_VOTOS as $p) if (str_contains((string)$src['feed_portada'], $p)) return true;
    return false;
}

/** Upsert de los items de un feed. Devuelve [nuevos, actualizados]. */
function fuente_guardar(PDO $pdo, array $src, string $phase, array $items): array {
    $sel = $pdo->prepare("SELECT item_id, phase, image FROM source_items WHERE source_id = ? AND ext_id = ?");
    $ins = $pdo->prepare("INSERT INTO source_items (source_id, ext_id, phase, title, url, thread_url, source, topic, author, content, image, votes, negatives, comments, pub_date)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    // (la fecha nueva se decide en PHP: MySQL evalua los SET de izquierda a derecha y phase ya tendria el valor nuevo)
    $upd = $pdo->prepare("UPDATE source_items SET phase = ?, title = ?, url = ?, thread_url = ?, source = COALESCE(?, source), topic = COALESCE(?, topic), content = IF(? = '', content, ?),
                          image = COALESCE(image, ?), votes = ?, negatives = ?, comments = GREATEST(comments, ?), pub_date = COALESCE(?, pub_date), last_seen = NOW() WHERE item_id = ?");   // url/thread_url se refrescan: Renegados los completa desde su API
    $new = $updated = 0;
    foreach ($items as $it) {
        $sel->execute([$src['source_id'], $it['ext_id']]);
        $row = $sel->fetch();
        if ($row && $row['phase'] === 'portada' && $phase === 'nuevas') {   // ya esta en portada: la cola no la degrada
            $pdo->prepare("UPDATE source_items SET last_seen = NOW() WHERE item_id = ?")->execute([$row['item_id']]);
            continue;
        }
        $img = ($row && $row['image']) ? null : fuente_imagen($src, $it);
        if ($row) {
            $newDate = $row['phase'] === $phase ? null : $it['pub_date'];   // cambio de fase (nuevas -> portada): fecha de publicacion
            $upd->execute([$phase, $it['title'], $it['url'], $it['thread_url'], $it['source'], $it['topic'], $it['content'], $it['content'], $img, $it['votes'], $it['negatives'], $it['comments'], $newDate, $row['item_id']]);
            $updated++;
        } else {
            $ins->execute([$src['source_id'], $it['ext_id'], $phase, $it['title'], $it['url'], $it['thread_url'], $it['source'], $it['topic'], $it['author'], $it['content'], $img, $it['votes'], $it['negatives'], $it['comments'], $it['pub_date']]);
            $new++;
        }
    }
    return [$new, $updated];
}

/** Imagen de un item, como ruta relativa a /media, o null.
 *  Agregador: la miniatura del feed tal cual, en /media/fuentes. Medio: la imagen del feed o la og:image de la noticia,
 *  procesadas como un envio (recorte 4:3, JPEG limpio) en /media; con tope sources_og_per_run por pasada. */
function fuente_imagen(array $src, array $it, bool $reset = false): ?string {
    static $fails = 0, $og = 0;   // contadores POR FUENTE (se reinician al empezar cada una): si su CDN no responde, no insistir item a item;
    if ($reset) { $fails = 0; $og = 0; return null; }   // y no descargar mas de sources_og_per_run paginas de esa fuente por pasada
    if (!empty($GLOBALS['DDD_RELAY'])) {   // lector local: el servidor no puede pedir nada a esta fuente; la foto la traera el lector
        if (fuente_es_medio($src)) $GLOBALS['ddd_relay_pend'][(string)$it['ext_id']] = ['thumb' => (string)($it['thumb'] ?? ''), 'url' => (string)$it['url']];
        return null;
    }
    if ($fails >= 3) return null;
    if (!fuente_es_medio($src)) {
        if (empty($it['thumb'])) return null;
        $bytes = http_get($it['thumb'], 3, 600000);
        if ($bytes === null || strlen($bytes) < 100) { $fails++; return null; }
        $info = @getimagesizefromstring($bytes);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif'][$info['mime'] ?? ''] ?? null;
        if (!$ext && imagen_es_avif($bytes)) $ext = 'avif';   // getimagesize solo reconoce AVIF desde PHP 8.1: mirar la cabecera
        if (!$ext) return null;   // (la miniatura se guarda tal cual y la pinta el navegador: todos los actuales muestran AVIF)
        if (!is_dir(DDD_FUENTES_DIR)) @mkdir(DDD_FUENTES_DIR, 0775, true);
        $name = preg_replace('/[^a-z0-9]/', '', $src['slug']) . '-' . preg_replace('/[^A-Za-z0-9_-]/', '', $it['ext_id']) . '.' . $ext;
        return @file_put_contents(DDD_FUENTES_DIR . '/' . $name, $bytes) ? 'fuentes/' . $name : null;
    }
    if (!empty($it['thumb'])) {
        $bytes = http_get($it['thumb'], 3, (int)cfg('img_max_bytes', 6000000));
        if ($bytes === null) $fails++;
        elseif ($n = imagen_desde_bytes($bytes)) return $n;
    }
    if ((string)cfg('sources_og_image', '1') === '1' && $og < max(0, (int)cfg('sources_og_per_run', 10))) {
        $og++;
        return imagen_desde_og($it['url']);
    }
    return null;
}

/** Parser de RSS 2.0: el de Meneame (rss2.php, espacio de nombres meneame:) y el generico (WordPress y similares).
 *  Devuelve items normalizados: ext_id, title, url, thread_url, source, topic, author, content, thumb, votes, negatives, comments, pub_date. */
/** Hijos de un item en un espacio de nombres, aceptando la URI con http:// o https:// (Mediatize declara los modulos estandar con https). */
function fuentes_ns(SimpleXMLElement $i, string $uri): SimpleXMLElement {
    $c = $i->children($uri);
    if (!count($c)) $c = $i->children(str_starts_with($uri, 'https://') ? 'http://' . substr($uri, 8) : 'https://' . substr($uri, 7));
    return $c;
}
function fuentes_parse_rss(string $xml): array {
    $prev = libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    if (!$doc) return [];
    if ($doc->getName() === 'feed') return fuentes_parse_atom($doc);   // Atom (The Conversation, Blogger, GitHub...)
    if (!isset($doc->channel->item)) return [];
    $out = [];
    foreach ($doc->channel->item as $i) {
        // Meneame y sus derivados (Mediatize: xmlns:mediatize) llevan link_id, votes, comments... en un espacio de nombres propio
        $mn = $i->children('http://meneame.net/faq-es.php');
        if (!count($mn)) foreach ($i->getNamespaces(true) as $uri) { $c = $i->children($uri); if (isset($c->link_id)) { $mn = $c; break; } }
        $md = fuentes_ns($i, 'http://search.yahoo.com/mrss/');
        $dc = fuentes_ns($i, 'http://purl.org/dc/elements/1.1/');
        $dt = fuentes_ns($i, 'http://purl.org/dc/terms');   // algunos feeds (RBA: National Geographic) usan dc:terms: creator y abstract
        $ce = fuentes_ns($i, 'http://purl.org/rss/1.0/modules/content/');
        $sl = fuentes_ns($i, 'http://purl.org/rss/1.0/modules/slash/');   // slash:comments (XenForo: Burbuja)
        $link = trim((string)$i->link);
        if ($link === '') continue;
        $desc = (string)$i->description;
        $full = isset($ce->encoded) ? (string)$ce->encoded : '';
        $meneame = isset($mn->link_id) && trim((string)$mn->link_id) !== '';
        // foros SMF (Areopago): guid «tag:…:msg=N» y enlace «topic=T.msgN»; cada item es un mensaje
        $smf = preg_match('/[?&;]topic=(\d+)\.msg(\d+)/', $link, $sm) && str_starts_with(trim((string)$i->guid), 'tag:') ? ['topic' => (int)$sm[1], 'msg' => (int)$sm[2]] : null;
        $extId = $meneame ? trim((string)$mn->link_id) : ($smf ? 'smf:' . $smf['msg'] : substr(sha1(trim((string)$i->guid) ?: $link), 0, 32));

        // imagen: media:content / media:thumbnail / enclosure / primer <img> del contenido
        $thumb = '';
        if (isset($md->content)) foreach ($md->content as $mc) { $u = trim((string)$mc->attributes()->url); if ($u !== '' && (!isset($mc->attributes()->medium) || (string)$mc->attributes()->medium === 'image')) { $thumb = $u; break; } }
        if ($thumb === '' && isset($md->thumbnail)) $thumb = trim((string)$md->thumbnail->attributes()->url);
        if ($thumb === '' && isset($i->enclosure) && str_starts_with((string)$i->enclosure->attributes()->type, 'image/')) $thumb = trim((string)$i->enclosure->attributes()->url);
        if ($thumb === '' && preg_match("/<img[^>]+src=['\"]([^'\"]+)['\"]/i", $full . $desc, $m)) $thumb = html_entity_decode($m[1]);

        if ($meneame) {
            $source = null;
            if (preg_match('/noticia original<\/a>\s*\(([^)]+)\)/u', $desc, $m)) $source = strtolower(preg_replace('/^www\./', '', trim($m[1])));
            // meneame:url / mediatize:url = el articulo original; si no viene, el redirector go?id= del propio agregador
            $url = trim((string)($mn->url ?? '')) ?: (preg_replace('#^(https?://[^/]+).*$#', '$1', $link) . '/go?id=' . (int)$mn->link_id);
            $thread = $link;
            $txt = preg_replace('/<p><strong>etiquetas<\/strong>.*$/su', '', $desc);
            $txt = preg_replace('/<p>[^<]*<a href="[^"]*go\?id=\d+"[^>]*>noticia original<\/a>.*$/su', '', $txt);
            $topic = trim((string)($mn->sub ?? '')) ?: null;
        } elseif ($smf) {
            $url = $link; $thread = $link;                                 // el mensaje en su hilo (<comments> de SMF es el formulario de respuesta)
            $source = null; $txt = $desc;
            $topic = isset($i->category) ? trim((string)$i->category[0]) : null;   // el subforo
        } else {
            $url = $link;                                                  // el articulo es el propio enlace
            $thread = trim((string)$i->comments) ?: $link;
            $source = source_of($link);
            $txt = isset($dt->abstract) && trim((string)$dt->abstract) !== '' ? (string)$dt->abstract : ($desc !== '' ? $desc : $full);   // dc:abstract = entradilla limpia si la hay
            $topic = isset($i->category) ? trim((string)$i->category[0]) : null;
        }
        $txt = trim(html_entity_decode(strip_tags(preg_replace('/<img[^>]*>/i', '', $txt)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $txt = trim(preg_replace(['/\s+/u', '/\s*\[…\]\s*$/u', '/\s*\[\.\.\.\]\s*$/u'], [' ', '…', '…'], $txt));
        $ts = strtotime((string)$i->pubDate) ?: (strtotime((string)($dc->date ?? '')) ?: time());
        $out[] = [
            'ext_id'     => $extId,
            'title'      => mb_substr(trim(html_entity_decode((string)$i->title, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 200),
            'url'        => mb_substr($url, 0, 600),
            'thread_url' => mb_substr($thread, 0, 600),
            'source'     => $source ? mb_substr($source, 0, 100) : null,
            'topic'      => $topic ? mb_substr($topic, 0, 48) : null,
            'author'     => mb_substr(trim((string)($mn->user ?? $dc->creator ?? $dt->creator ?? $i->author ?? '')), 0, 64) ?: null,
            'content'    => mb_substr($txt, 0, 2000),
            'thumb'      => preg_match('#^https?://#i', $thumb) ? $thumb : '',
            'votes'      => (int)($mn->votes ?? 0),
            'negatives'  => (int)($mn->negatives ?? 0),
            'comments'   => (int)($mn->comments ?? ($sl->comments ?? 0)),
            'pub_date'   => date('Y-m-d H:i:s', $ts),
            'smf'        => $smf,
        ];
    }
    return $out;
}

/** Atom 1.0: <feed><entry>. Mismos campos normalizados que el RSS; sin votos ni comentarios. */
function fuentes_parse_atom(SimpleXMLElement $doc): array {
    $out = [];
    foreach ($doc->entry as $e) {
        $link = '';
        foreach ($e->link as $l) { $rel = (string)($l->attributes()->rel ?? 'alternate'); if ($rel === 'alternate' || $link === '') { $link = trim((string)$l->attributes()->href); if ($rel === 'alternate') break; } }
        if ($link === '') continue;
        $id = trim((string)$e->id) ?: $link;
        $content = (string)$e->content;
        $summary = (string)$e->summary;
        $md = $e->children('http://search.yahoo.com/mrss/');
        $thumb = '';
        if (isset($md->group)) {   // YouTube: miniatura y descripcion van dentro de <media:group> (su media:content es el video, no una foto)
            $md = $md->group->children('http://search.yahoo.com/mrss/');
            if ($summary === '' && $content === '') $summary = htmlspecialchars(youtube_descripcion((string)$md->description), ENT_QUOTES, 'UTF-8');   // texto plano (se limpia como HTML mas abajo)
            if (isset($md->thumbnail)) $thumb = trim((string)$md->thumbnail->attributes()->url);
        }
        if ($thumb === '' && isset($md->content)) foreach ($md->content as $mc) { $u = trim((string)$mc->attributes()->url); if ($u !== '') { $thumb = $u; break; } }
        if ($thumb === '' && isset($md->thumbnail)) $thumb = trim((string)$md->thumbnail->attributes()->url);
        if ($thumb === '') foreach ($e->link as $l) if ((string)$l->attributes()->rel === 'enclosure' && str_starts_with((string)$l->attributes()->type, 'image/')) { $thumb = trim((string)$l->attributes()->href); break; }
        if ($thumb === '' && preg_match("/<img[^>]+src=['\"]([^'\"]+)['\"]/i", $content . $summary, $m)) $thumb = html_entity_decode($m[1]);
        // entradilla: el resumen o, si no, el primer parrafo con texto del contenido (los pies de foto de la figura inicial fuera)
        $txt = $summary;
        if (trim(strip_tags($txt)) === '' && $content !== '') {
            $body = preg_replace('/<figure.*?<\/figure>/is', '', $content);
            $txt = preg_match('/<p[^>]*>(.*?)<\/p>/is', $body, $m) && trim(strip_tags($m[1])) !== '' ? $m[1] : $body;
        }
        $txt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('/<img[^>]*>/i', '', $txt)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $cat = null;
        if (isset($e->category)) { $a = $e->category[0]->attributes(); $cat = trim((string)($a->label ?? '')) ?: trim((string)($a->term ?? '')); }
        $ts = strtotime((string)($e->published ?: $e->updated)) ?: time();
        $out[] = [
            'ext_id'     => substr(sha1($id), 0, 32),
            'title'      => mb_substr(trim(html_entity_decode((string)$e->title, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 200),
            'url'        => mb_substr($link, 0, 600),
            'thread_url' => mb_substr($link, 0, 600),
            'source'     => mb_substr((string)source_of($link), 0, 100) ?: null,
            'topic'      => $cat ? mb_substr($cat, 0, 48) : null,
            'author'     => mb_substr(trim((string)($e->author->name ?? '')), 0, 64) ?: null,
            'content'    => mb_substr($txt, 0, 2000),
            'thumb'      => preg_match('#^https?://#i', $thumb) ? $thumb : '',
            'votes' => 0, 'negatives' => 0, 'comments' => 0,
            'pub_date'   => date('Y-m-d H:i:s', $ts),
            'smf'        => null,
        ];
    }
    return $out;
}

/* ---- YouTube --------------------------------------------------------- */

/** Quita emojis y pictogramas (👉 🔥 ✅ ▶ …) y deja un solo espacio donde estaban. */
function sin_emojis(string $s): string {
    $s = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{25A0}-\x{27BF}\x{2B00}-\x{2BFF}\x{2600}-\x{26FF}\x{FE00}-\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}]+/u', ' ', $s) ?? $s;
    return trim(preg_replace('/\s{2,}/u', ' ', $s) ?? $s);
}
/** Titular «5 MISTERIOS de la HUMANIDAD que siguen dando MIEDO» -> «5 misterios de la humanidad que siguen dando miedo».
 *  Sin emojis; las palabras escritas enteras en mayusculas pasan a minusculas, salvo las siglas: las de DDD_SIGLAS y, en un titular
 *  que no va todo en mayusculas, cualquier palabra de hasta 4 letras que no sea una palabra corriente (NASA, ADN, IA, N26 se quedan).
 *  Primera letra del titular, y la que sigue a un punto, interrogacion o exclamacion, en mayuscula. No sabe de nombres propios:
 *  «EINSTEIN» queda «einstein» (eso solo lo arregla una persona o un modelo de lenguaje). */
const DDD_SIGLAS = ['NASA', 'ESA', 'ADN', 'ARN', 'IA', 'AI', 'GPT', 'UE', 'EEUU', 'USA', 'URSS', 'ONU', 'OTAN', 'OMS', 'CERN', 'CSIC', 'MIT', 'DNA', 'RNA', 'TV', 'PC', 'CPU', 'GPU', 'LHC', 'JWST', 'VIH', 'SIDA', 'COVID', 'UNESCO', 'OVNI', 'OVNIS', 'CEO', 'RAE', 'DIY', 'FAQ', 'II', 'III', 'IV', 'VI', 'VII', 'VIII', 'IX', 'XI', 'XII', 'XIX', 'XX', 'XXI'];
const DDD_CORTAS = ['QUE', 'QUÉ', 'POR', 'PARA', 'CON', 'SIN', 'LOS', 'LAS', 'UNA', 'UNO', 'UNOS', 'DEL', 'MÁS', 'MAS', 'MUY', 'TODO', 'TODA', 'NADA', 'ESTO', 'ESTE', 'ESTA', 'ESO', 'ESA', 'ASÍ', 'MAL', 'BIEN', 'NO', 'SÍ', 'SI', 'HOY', 'YA', 'CASI', 'SOLO', 'TAN', 'GRAN', 'PEOR', 'ES', 'EL', 'LA', 'DE', 'EN', 'UN', 'SE', 'TE', 'TU', 'MI', 'ME', 'LO', 'LE', 'AL', 'HA', 'HE', 'VA', 'VS', 'COMO', 'CÓMO', 'AQUÍ', 'FIN', 'MIL', 'DOS', 'TRES', 'AÑO', 'AÑOS', 'DÍA', 'DÍAS', 'VIDA', 'VER', 'SER', 'HAY', 'SON', 'FUE', 'ERA', 'TÚ', 'YO', 'NI', 'NOS', 'SUS', 'SU', 'MIS', 'TUS', 'ESE', 'ESOS', 'ESAS', 'ELLA', 'ELLO', 'PERO', 'CADA', 'TRAS', 'ANTE', 'BAJO', 'CASO', 'COSA', 'ALGO', 'AHORA', 'NUNCA', 'FAKE', 'TOP', 'NEW', 'THE', 'AND', 'OF', 'IS', 'WHY', 'HOW', 'OTRA', 'OTRO', 'MEJOR', 'REAL', 'CERO', 'LUZ', 'SOL', 'MAR', 'FE', 'OJO', 'VOZ', 'PAZ', 'LEY', 'REY', 'DIOS', 'ARTE', 'CINE', 'ROCK', 'JAZZ', 'ÉPICO', 'MITO', 'MITOS', 'RARO', 'RARA', 'LOCO', 'LOCA', 'MIEDO', 'CAOS', 'ODIO', 'AMOR', 'OJOS', 'NADIE', 'TODOS'];
function titular_normal(string $t): string {
    $t = sin_emojis($t);
    if ($t === '') return $t;
    $letras = preg_replace('/[^\p{L}]/u', '', $t);
    $todo = $letras !== '' && mb_strtoupper($letras) === $letras;   // el titular entero va en mayusculas
    $t = preg_replace_callback('/[\p{L}\p{N}]+/u', function ($m) use ($todo) {
        $w = $m[0];
        if (mb_strlen($w) < 2 || mb_strtoupper($w) !== $w || !preg_match('/\p{Lu}/u', $w)) return $w;   // no va en mayusculas (o es una letra o un numero)
        if (in_array($w, DDD_SIGLAS, true) || preg_match('/\d/', $w)) return $w;                           // sigla conocida o con cifras (N26, 4K)
        if (!$todo && mb_strlen($w) <= 4 && !in_array($w, DDD_CORTAS, true)) return $w;                    // corta y desconocida: sera una sigla
        return mb_strtolower($w);
    }, $t) ?? $t;
    // mayuscula inicial, y tras . ? ! (y sus aperturas ¿ ¡ y comillas)
    return preg_replace_callback('/(^|[.?!]\s+)([¿¡«"“(]*)(\p{Ll})/u', fn($m) => $m[1] . $m[2] . mb_strtoupper($m[3]), $t) ?? $t;
}
/** Descripcion de un video sin la morralla: las descripciones de YouTube mezclan el resumen con patrocinios, codigos de descuento,
 *  enlaces de afiliado, redes sociales e indices de capitulos. Se quitan las lineas con una URL, las de capitulos (0:00 …), las de
 *  etiquetas (#…) y las que quedan tras un «Redes / Sígueme / Suscríbete…»; de lo que queda, las primeras lineas con texto. */
function youtube_descripcion(string $d): string {
    $out = [];
    foreach (preg_split('/\R/u', $d) as $l) {
        $l = trim($l);
        if ($l === '') continue;
        if (preg_match('/^(redes|s[ií]gueme|s[ií]guenos|suscr[ií]bete|mis redes|contacto|cr[eé]ditos|fuentes|referencias|bibliograf[ií]a|m[uú]sica|cap[ií]tulos|[ií]ndice)\b/iu', $l)) break;   // de aqui en adelante, pie del video
        if (preg_match('#https?://|www\.|\b[\w-]+\.(com|es|net|org|io|tv|me|ly)/#i', $l)) continue;      // enlaces: patrocinios, afiliados, redes
        if (preg_match('/^\(?\d{1,2}:\d{2}/', $l) || preg_match('/^(#[\p{L}\d_]+\s*)+$/u', $l)) continue;   // capitulos y etiquetas
        if (preg_match('/\b(c[oó]digo|cup[oó]n|descuento|patrocin|sponsor|afiliad)/iu', $l)) continue;     // promociones sin enlace
        $out[] = $l;
        if (count($out) >= 3) break;
    }
    return implode(' ', $out);
}
/** Feed de un canal a partir de lo que se pegue en admin: la URL del canal (/channel/UC…, /@nombre, /c/…, /user/…), su feed o el
 *  id UC… a secas. Con /@nombre hay que abrir la pagina del canal para leer el id (una peticion, solo al guardar). Con
 *  config.youtube_shorts = 0 (por defecto) se usa la lista «solo videos largos» del canal (UULF…), sin shorts ni directos;
 *  con 1, el feed completo. null si no se pudo: $err dice por que. */
function youtube_feed_de(string $url, ?string &$err = null): ?string {
    $url = trim($url); $err = null;
    if (preg_match('#^https?://(?:www\.)?youtube\.com/feeds/videos\.xml\?(?:channel_id|playlist_id|user)=[\w-]+$#', $url)) return $url;   // ya es un feed: tal cual
    $id = '';
    if (preg_match('#^(UC[\w-]{22})$#', $url, $m) || preg_match('#youtube\.com/channel/(UC[\w-]{22})#', $url, $m)) $id = $m[1];
    elseif (preg_match('#^https?://(?:www\.|m\.)?youtube\.com/(?:@|c/|user/)[^\s]+$#', $url)) {
        $html = http_get($url, max(3, (int)cfg('sources_timeout_sec', 8)), 4000000);
        if ($html === null) { $err = 'YouTube no respondió al abrir el canal (' . implode(' · ', http_last()) . ')'; return null; }
        if (preg_match('#feeds/videos\.xml\?channel_id=(UC[\w-]{22})#', $html, $m) || preg_match('#<link rel="canonical" href="https://www\.youtube\.com/channel/(UC[\w-]{22})"#', $html, $m)
         || preg_match('#"(?:externalId|browseId)":"(UC[\w-]{22})"#', $html, $m)) $id = $m[1];
        else { $err = 'no encuentro el id del canal en su página (¿pantalla de consentimiento de YouTube?)'; return null; }
    } else { $err = 'no parece la dirección de un canal de YouTube'; return null; }
    return 'https://www.youtube.com/feeds/videos.xml?' . ((string)cfg('youtube_shorts', '0') === '1' ? 'channel_id=' . $id : 'playlist_id=UULF' . substr($id, 2));
}

/** Diagnostico de la foto de una noticia (admin -> Fuentes -> «probar foto»): que paso falla al buscar la og:image. */
function fuente_diag_imagen(string $url): array {
    $log = [];
    $t = (int)cfg('og_timeout_sec', 3);
    $max = og_max_bytes();
    $html = http_get($url, $t, $max);
    $intentos = "intentos: " . implode(' · ', http_last()) . " · curl-impersonate " . (curl_impersonate_bin() ? 'si' : 'NO');
    if (!$html) return ["pagina: SIN RESPUESTA (bloqueo, timeout de {$t}s o mas de " . round($max / 1000) . " KB) -> $url", $intentos,
        "pista: HTTP 403/503 en todos los intentos = el CDN (Cloudflare, Akamai) no deja pasar al servidor; si tambien falla curl-impersonate es un veto a la IP del servidor: pedir al medio que la ponga en su lista blanca; "
      . "'timed out' = sube og_timeout_sec; 'Callback aborted' o 'exceeds' = pagina mayor que og_max_bytes (config, por defecto 2500000)"];
    $log[] = "pagina: " . strlen($html) . " bytes (" . implode(' · ', http_last()) . ")";
    $img = null;
    if (preg_match('/<meta[^>]+(?:property|name)=["\']og:image(?::secure_url|:url)?["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)
     || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']og:image["\']/i', $html, $m)
     || preg_match('/<meta[^>]+(?:name|property)=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) $img = html_entity_decode($m[1]);
    if (!$img) return array_merge($log, ["og:image: NO ENCONTRADA en el HTML"]);
    $log[] = "og:image: $img";
    $bytes = http_get($img, $t);
    if (!$bytes) return array_merge($log, ["imagen: SIN RESPUESTA al descargarla (" . implode(' · ', http_last()) . ")"]);
    $info = @getimagesizefromstring($bytes);
    $log[] = "imagen: " . strlen($bytes) . " bytes, " . ($info ? "{$info[0]}x{$info[1]} {$info['mime']}" : (imagen_es_avif($bytes) ? "AVIF (por la cabecera)" : "formato no reconocido por GD"));
    $gd = gd_info();
    $log[] = "GD: webp " . (!empty($gd['WebP Support']) ? 'si' : 'NO') . " · avif " . (!empty($gd['AVIF Support']) ? 'si' : 'no') . " · jpeg " . (!empty($gd['JPEG Support']) ? 'si' : 'NO')
           . " · avifdec " . (imagen_bin('avifdec', ['/usr/bin/avifdec', '/usr/local/bin/avifdec']) ? 'si' : 'no')
           . " · imagemagick " . (imagen_bin('imagemagick', ['/usr/bin/magick', '/usr/local/bin/magick', '/usr/bin/convert', '/usr/local/bin/convert']) ? 'si' : 'no');
    $src = imagen_decodificar($bytes, $como);
    if (!$src) return array_merge($log, [imagen_es_avif($bytes)
        ? "AVIF: no se puede decodificar en este servidor (instala php-gd con libavif, el paquete libavif-bin [avifdec] o ImageMagick)"
        : "GD: no puede decodificar esta imagen (falta soporte del formato)"]);
    $log[] = "decodificada con $como: " . imagesx($src) . "x" . imagesy($src) . (imagesx($src) < 240 || imagesy($src) < 180 ? " -> DEMASIADO PEQUEÑA (minimo 240x180)" : " -> valida");
    return $log;
}

/* ---- vista ------------------------------------------------------------ */

/** Consulta de una vista: portada | populares | nuevas. Devuelve [sql_where_order, params] sobre source_items i. */
function fuente_vista_sql(string $view): array {
    if ($view === 'populares') {
        return ["i.phase = 'nuevas' AND i.pub_date > NOW() - INTERVAL ? HOUR AND i.votes >= ? ORDER BY i.votes DESC, i.pub_date DESC",
                [max(1, (int)cfg('sources_popular_hours', 24)), max(0, (int)cfg('sources_popular_min_votes', 5))]];
    }
    return [$view === 'nuevas' ? "i.phase = 'nuevas' ORDER BY i.pub_date DESC" : "i.phase = 'portada' ORDER BY i.pub_date DESC", []];
}

/** URL del formulario de envio prerrellenado con una noticia de un medio. */
function fuente_enviar_url(array $src, array $i): string {
    $q = ['url' => $i['url'], 'title' => $i['title'], 'content' => mb_substr((string)$i['content'], 0, 600)];
    if (!empty($src['topic_id'])) $q['topic'] = (int)$src['topic_id'];
    if (!empty($i['image'])) $q['x'] = (int)$i['item_id'];   // enviar.php reutiliza la foto que ya se saco del RSS
    return '/enviar.php?' . http_build_query($q);
}
/** story_id de los envios ya hechos con estas URLs (para marcar «ya enviada» y enlazar al hilo). */
function fuente_ya_enviadas(PDO $pdo, array $urls): array {
    $urls = array_values(array_unique(array_filter($urls)));
    if (!$urls) return [];
    $st = $pdo->prepare("SELECT url, story_id FROM stories WHERE url IN (" . implode(',', array_fill(0, count($urls), '?')) . ") AND status NOT IN ('rejected','discard') AND sent_date > NOW() - INTERVAL 30 DAY");
    $st->execute($urls);
    return $st->fetchAll(PDO::FETCH_KEY_PAIR);
}

/** Post de un item externo: mismo aspecto que story_post(), sin votar, sin comentar, sin δ.
 *  $sent: [url => story_id] de los ya enviados a Deltha (medios). */
function fuente_post(array $src, array $i, string $mode = 'full', array $sent = []): void {
    $medio = fuente_es_medio($src);
    $yt = ($src['kind'] ?? '') === 'youtube';   // canal de YouTube: la foto lleva el triangulo de «reproducir» y el enlace abre el video en YouTube
    $author = $i['author'] ?: $src['name'];
    $rel = 'rel="noopener nofollow"';
    echo "<article class=\"post $mode ext" . ($medio ? ' medio' : '') . "\" id=\"x{$i['item_id']}\">";
    echo avatar((float)cfg('aura_anon', 5), $author);
    echo "<div class=\"pc\">";
    $meta = [];
    if ($medio) $meta[] = "<a href=\"" . h(fuente_url($src)) . "\">" . h($src['name']) . "</a>";
    if ($i['topic'])  $meta[] = h($i['topic']);
    if (!$medio && $i['source']) $meta[] = h($i['source']);
    $meta[] = h(hace($i['pub_date']));
    echo "<div class=\"l1\"><b>" . h($author) . "</b><span class=\"meta\"><span class=\"sep\"> · </span>" . implode(' · ', $meta) . "</span></div>";
    $isThumb = $i['image'] && str_starts_with($i['image'], 'fuentes/');
    if ($isThumb) echo "<a class=\"thumb\" href=\"" . h($i['url']) . "\" $rel style=\"background-image:url('/media/" . h($i['image']) . "')\"></a>";
    $foto = ($i['image'] && !$isThumb) ? "<div class=\"media" . ($mode === 'full' ? '' : ' sm') . ($yt ? ' yt' : '') . "\"><a class=\"pic\" href=\"" . h($i['url']) . "\" $rel style=\"background-image:url('/media/" . h($i['image']) . "')\"></a></div>" : '';
    if (foco()) echo $foto;   // sin distracciones: la foto, antes del titular, flota a su derecha
    echo "<h2><a href=\"" . h($i['url']) . "\" $rel>" . h($i['title']) . "</a></h2>";
    if ($i['content']) echo "<p class=\"lead\">" . h(mb_strimwidth($i['content'], 0, $mode === 'full' ? 240 : 160, '…')) . "</p>";
    if (!foco()) echo $foto;
    echo "<div class=\"actions\">";
    if ($medio) {
        if (isset($sent[$i['url']])) echo "<a class=\"sent\" href=\"/hilo.php?id=" . (int)$sent[$i['url']] . "\" title=\"" . h(t('ya está en Deltha: ver el hilo')) . "\">" . icon('reply') . "<span>" . t('en Deltha') . "</span></a>";
        elseif (user() || cfg('anon_can_submit') === '1') echo "<a class=\"send\" href=\"" . h(fuente_enviar_url($src, $i)) . "\" title=\"" . h(t('enviar esta noticia al radar')) . "\">" . icon('plus') . "<span>" . t('enviar al radar') . "</span></a>";
        echo "<a class=\"share ext\" href=\"" . h($i['url']) . "\" $rel>" . icon('link') . "<span>" . h($yt ? t('ver en %s', 'YouTube') : t('leer en %s', $src['name'])) . "</span></a>";
        echo fuente_fav_form($src);   // «+ Seguir»: el medio o substack de esta noticia, a «Mis medios» / «Mis substacks»
    } else {
        echo "<a href=\"" . h($i['thread_url']) . "\" rel=\"noopener\" title=\"" . h(t('comentarios en %s', $src['name'])) . "\">" . icon('reply') . "<span>" . (int)$i['comments'] . "</span></a>";
        if (!fuente_sin_votos($src)) echo "<span class=\"vote\"><span>" . icon('up') . "<span>" . (int)$i['votes'] . "</span></span><span>" . icon('down') . "<span>" . (int)$i['negatives'] . "</span></span></span>";
        echo "<a class=\"share ext\" href=\"" . h($i['thread_url']) . "\" rel=\"noopener\">" . icon('link') . "<span>" . h(t('ver en %s', $src['name'])) . "</span></a>";
    }
    echo "</div></div></article>";
}
