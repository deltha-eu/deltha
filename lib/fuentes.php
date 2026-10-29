<?php
/**
 * lib/fuentes.php — fuentes externas «pintadas» con Deltha (solo lectura).
 *
 * Una fuente (tabla sources) es otro sitio con RSS. Dos tipos (sources.kind):
 *  - agregador (Menéame): feed de portada y feed de cola, fases nuevas -> portada, votos y comentarios de la
 *    fuente; se ve en /fuente/slug con Portada / Populares / Nuevas.
 *  - medio (Jot Down, Revista Mercurio...): un solo feed, sin fases ni votos; cada noticia lleva un boton
 *    «enviar al radar» que abre /enviar prerrellenado. Cada usuario marca como favoritos los que quiera, sin
 *    limite (boton «Seguir» en el catalogo, en la fuente, en cada noticia y en el hilo; tabla user_media); en el rail salen
 *    los media_max_per_user que han publicado mas recientemente y en /mis-fuentes (pestañas del perfil), todos.
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
require_once __DIR__ . '/destacar.php';  // destacar noticias de medios y substacks (source_pins, migra-111)

const DDD_FUENTES_DIR = DDD_MEDIA_DIR . '/fuentes';

function fuentes_on(): bool { return (string)cfg('sources_enabled', '1') === '1'; }
/** Agregadores (Meneame, Mediatize, Renegados, Burbuja, Areopago) activos? config.sources_agregadores, apagados por defecto (migra-070):
 *  fuentes_activas() no los devuelve, asi que desaparecen del catalogo, el rail, la franja, la columna derecha, el pie, /ultimo y /fuente,
 *  y el cron no lee sus RSS. Siguen en admin > Fuentes. */
function agregadores_on(): bool { return (string)cfg('sources_agregadores', '0') === '1'; }
/** Canales de YouTube (fuentes de tipo «youtube») activos? config.sources_youtube (migra-103). Apagados, igual que los agregadores:
 *  fuera de todo salvo admin > Fuentes, y el cron no lee sus feeds. */
function youtube_on(): bool { return (string)cfg('sources_youtube', '1') === '1'; }
/** ¿Esta encendido este tipo de fuente? Medios y substacks, siempre; agregadores y YouTube tienen interruptor en Config. */
function fuente_kind_on(string $kind): bool { return $kind === 'agregador' ? agregadores_on() : ($kind === 'youtube' ? youtube_on() : true); }
/** Canales y agregadores (08/10/2026): aunque esten encendidos, solo los ve quien ha llegado a config.fuentes_nivel_aura (6) de aura,
 *  y siempre los moderadores. Asi a quien acaba de llegar le salen solo Mis medios y Mis substacks. Sin cuenta, no. */
function fuente_kind_visible(string $kind): bool {
    if (!fuente_kind_on($kind)) return false;
    if ($kind !== 'agregador' && $kind !== 'youtube') return true;
    $u = user();
    return $u && (is_mod() || (float)$u['aura'] >= (float)cfg('fuentes_nivel_aura', 6));
}

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
 * users.src_mylang = 1: el usuario quiere filtrar por idioma; users.src_langs guarda la lista elegida («es,en»). Lista vacia = el
 * idioma de la interfaz (lang()), como la casilla antigua. Las fuentes de idioma desconocido se ven siempre.
 * Filtra menus, listas y catalogo; la pagina de una fuente (/fuente) se sigue abriendo con su enlace. */
/** Lista «es,en» -> codigos de DDD_LANGS, sin repetir, en el orden de la interfaz. */
function fuentes_langs_parse(string $s): array {
    $out = [];
    foreach (explode(',', $s) as $x) { $x = strtolower(trim((string)$x)); if (isset(DDD_LANGS[$x])) $out[$x] = true; }
    return array_values(array_intersect(array_keys(DDD_LANGS), array_keys($out)));
}
/** Idiomas de un campo multiple del formulario (src_langs[] en el perfil, ml[] en el control rapido). */
function fuentes_langs_field(string $name, array $src): array {
    $v = $src[$name] ?? [];
    if (!is_array($v)) $v = [$v];
    return fuentes_langs_parse(implode(',', array_map('strval', $v)));
}
function fuentes_langs_post(string $name = 'src_langs'): array { return fuentes_langs_field($name, $_POST); }
/** Idiomas activos: [] = sin filtro. Con cuenta, src_mylang + src_langs (vacio = el de la interfaz); sin cuenta, la cookie
 *  ddd_srclang (el «1» antiguo tambien es el idioma de la interfaz; la lista nueva va como «es,en»). */
function fuentes_idiomas(): array {
    static $ls = null;
    if ($ls !== null) return $ls;
    $u = user();
    if ($u) return $ls = empty($u['src_mylang']) ? [] : (fuentes_langs_parse((string)($u['src_langs'] ?? '')) ?: [lang()]);
    $c = (string)($_COOKIE['ddd_srclang'] ?? '');
    return $ls = $c === '1' ? [lang()] : fuentes_langs_parse($c);
}
/** ¿Hay filtro de idioma? Nombre nuevo de fuentes_solo_mi_idioma(). */
function fuentes_filtra_idioma(): bool { return fuentes_idiomas() !== []; }
/** Compatibilidad con el nombre antiguo (lo usan app.php y lateral.php). */
function fuentes_solo_mi_idioma(): bool { return fuentes_filtra_idioma(); }
/** Guarda la preferencia (perfil, control rapido y login con cookie). $on null = derivado de la lista; con $on y lista vacia
 *  se guarda el idioma de la interfaz, como la casilla antigua. Con cuenta se escribe en users y se borra la cookie. */
function fuentes_guardar_idiomas(PDO $pdo, ?int $uid, array $langs, ?bool $on = null): void {
    $langs = fuentes_langs_parse(implode(',', $langs));
    $on ??= $langs !== [];
    if ($uid) {
        $pdo->prepare("UPDATE users SET src_mylang = ?, src_langs = ? WHERE user_id = ?")->execute([$on ? 1 : 0, implode(',', $langs), $uid]);
        pref_cookie('ddd_srclang', '');
    } else {
        pref_cookie('ddd_srclang', $on ? (implode(',', $langs) ?: '1') : '');
    }
}
/** Control rapido de idiomas para las listas de fuentes: misma preferencia que el perfil (via /cambiar-skin?lset=1&ml[]=…);
 *  se guarda con «Guardar» (no se envia solo al marcar). Sin cuenta, cookie ddd_srclang. */
function fuentes_casilla_idioma(): string {
    $ls = fuentes_idiomas();
    $nombres = array_map(fn($l) => h(DDD_LANGS[$l] ?? strtoupper($l)), $ls);
    $h = "<form class=\"srclang\" method=\"get\" action=\"/cambiar-skin\" autocomplete=\"off\"><input type=\"hidden\" name=\"lset\" value=\"1\"><details><summary>"
       . t('Idiomas') . ": <b>" . ($nombres ? implode(', ', $nombres) : t('Todos los idiomas')) . "</b> <small>(" . t('cambiar') . ")</small></summary><div class=\"langpick\">";
    foreach (DDD_LANGS as $k => $name)
        $h .= "<label><input type=\"checkbox\" name=\"ml[]\" value=\"$k\"" . (in_array($k, $ls, true) ? ' checked' : '') . "> " . h($name) . "</label>";
    return $h . "</div><button type=\"submit\">" . t('Guardar') . "</button></details></form>";
}
function fuente_en_mi_idioma(array $src): bool {
    $ls = fuentes_idiomas();
    if (!$ls) return true;
    $l = (string)($src['lang'] ?? '');
    return $l === '' || in_array($l, $ls, true);
}
/** ¿Esta en el catalogo publico? Las que se añade un usuario pegando su direccion (migra-108) entran con listed = 0: se leen y las ve
 *  quien las sigue, pero no salen en menus, catalogo, lateral ni buscador hasta que un moderador las aprueba. */
function fuente_listada(array $src): bool { return !isset($src['listed']) || (int)$src['listed'] === 1; }
/** El catalogo publico: fuentes_activas() sin las no listadas y, si el usuario lo ha pedido, sin las de otro idioma. */
function fuentes_visibles(PDO $pdo, string $kind = ''): array {
    return array_values(array_filter(fuentes_activas($pdo, $kind), fn($s) => fuente_listada($s) && fuente_en_mi_idioma($s)));
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
    return '/fuente/' . rawurlencode($src['slug']) . ($view && $view !== 'portada' ? '?v=' . $view : '');
}
function fuente_es_medio(array $src): bool { return in_array($src['kind'] ?? 'agregador', ['medio', 'newsletter', 'youtube'], true); }   // sin fases ni votos, con «enviar al radar»
/** Grupos con favoritos del usuario: tipo => [clave de config de cuantos salen en el menu, etiqueta del grupo] */
const DDD_MEDIA_KINDS = ['medio' => ['media_max_per_user', 'Mis medios'], 'newsletter' => ['news_max_per_user', 'Mis substacks'], 'youtube' => ['yt_max_per_user', 'Mis canales'], 'agregador' => ['agg_max_per_user', 'Mis agregadores']];
/** ¿Se puede seguir? Medios y substacks siempre; agregadores, mientras esten encendidos (config.sources_agregadores). */
function fuente_seguible(array $src): bool { return fuente_es_medio($src) || (($src['kind'] ?? '') === 'agregador' && agregadores_on()); }
/** Grupos del rail y de /ultimo, en su orden: tipo => [etiqueta, icono]. Los dos primeros se eligen con el boton «Seguir» (DDD_MEDIA_KINDS). */
const DDD_ULTIMO_KINDS = ['medio' => ['Mis medios', 'news'], 'newsletter' => ['Mis substacks', 'letter'], 'youtube' => ['Mis canales', 'play'], 'agregador' => ['Mis agregadores', 'agg']];   // mismo orden que las portadas: Medios, Substacks
/** Fuentes de las que /ultimo saca las noticias de un grupo: TODOS los favoritos del usuario (sin limite) o, si no tiene
 *  ninguno / no tiene cuenta, TODAS las destacadas en admin del tipo (sources.featured) o, si no hay ninguna, las del menu
 *  (medios_menu(): las que han publicado mas recientemente). */
function ultimo_fuentes(PDO $pdo, ?array $u, string $kind): array {
    if ($mias = mis_medios_recientes($pdo, $u, $kind)) return $mias;
    return fuentes_destacadas($pdo, $kind) ?: medios_menu($pdo, $u, 0, $kind);
}
/** Fuentes de un tipo destacadas en admin (sources.featured), la que ha publicado mas recientemente primero. Son las que ve en
 *  «Mis medios» / «Mis substacks» / «Mis canales» quien no ha elegido ninguna (o no tiene cuenta); con filtro de idiomas, las
 *  de otro idioma se quitan. */
function fuentes_destacadas(PDO $pdo, string $kind): array {
    $list = array_values(array_filter(fuentes_visibles($pdo, $kind), fn($m) => !empty($m['featured']) && fuente_en_mi_idioma($m)));
    $ult = fuentes_ultima_noticia($pdo);
    usort($list, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
    return $list;
}
/** Tipos de fuente que se ofrecen ahora mismo (encendidos en admin y con alguna fuente visible), en el orden de DDD_ULTIMO_KINDS.
 *  Es la sub-navegacion de /mis-fuentes y /fuentes (08/10/2026: «Mis fuentes» y «Todas las fuentes» sustituyen a las cuatro entradas). */
function fuentes_tipos(PDO $pdo): array {
    static $t = null;
    if ($t !== null) return $t;
    $t = [];
    if (fuentes_on()) foreach (DDD_ULTIMO_KINDS as $k => $v) if (fuente_kind_visible($k) && fuentes_visibles($pdo, $k)) $t[$k] = $v;
    return $t;
}
const DDD_FKIND_COOKIE = 'ddd_fk';   // ultimo tipo de fuente abierto en /mis-fuentes o /fuentes (un año, sin JS)
/** Tipo de fuente en uso: ?k= si vale (y se recuerda en cookie), si no el ultimo que se abrio, si no el primero. '' si no hay ninguno. */
function fuentes_tipo_actual(PDO $pdo, ?string $k = null): string {
    $tipos = fuentes_tipos($pdo);
    if (!$tipos) return '';
    $k = $k ?? (string)($_GET['k'] ?? '');
    if (isset($tipos[$k])) {
        if (($_COOKIE[DDD_FKIND_COOKIE] ?? '') !== $k && !headers_sent()) pref_cookie(DDD_FKIND_COOKIE, $k);
        return $k;
    }
    $c = (string)($_COOKIE[DDD_FKIND_COOKIE] ?? '');
    return isset($tipos[$c]) ? $c : array_key_first($tipos);
}
/** Sub-navegacion por tipo: en /mis-fuentes «Mis medios · Mis substacks · …» con cuantas sigue de cada uno; en /fuentes «Medios ·
 *  Substacks · …» con cuantas hay en el catalogo (y «todas»). $extra: enlaces adicionales al final (p. ej. «todas»). */
function fuentes_subnav(PDO $pdo, string $base, string $k, bool $mias, string $extra = ''): string {
    $u = user(); $h = "<nav class=\"sub tipos\">";
    foreach (fuentes_tipos($pdo) as $kk => [$lbl]) {
        $n = $mias ? count(mis_medios($pdo, $u, $kk)) : count(fuentes_visibles($pdo, $kk));
        $txt = $mias ? t($lbl) : ($kk === 'youtube' ? 'YouTube' : t(['medio' => 'Medios', 'newsletter' => 'Substacks', 'agregador' => 'Agregadores'][$kk]));
        $h .= "<a href=\"$base?k=$kk\"" . ($kk === $k ? ' class="on"' : '') . ">" . h($txt) . ($n ? " <small>$n</small>" : '') . "</a>";
    }
    return $h . $extra . "</nav>";
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
/** ¿Enseña este usuario en su ficha los medios y substacks que sigue? (users.show_follows, migra-116; por defecto si). Consulta aparte,
 *  en try, para que nada se rompa entre subir el codigo y aplicar la migracion. */
function show_follows(PDO $pdo, int $uid): bool {
    static $c = [];
    if (isset($c[$uid])) return $c[$uid];
    try { $st = $pdo->prepare("SELECT show_follows FROM users WHERE user_id = ?"); $st->execute([$uid]); return $c[$uid] = (int)$st->fetchColumn() === 1; }
    catch (Throwable $e) { return $c[$uid] = true; }
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
        foreach (fuentes_activas($pdo) as $s) if (fuente_es_medio($s) && fuente_listada($s) && ($s['kind'] ?? '') !== 'youtube' && ($d = (string)source_of((string)$s['home'])) !== '') $map[$d] ??= $s;
    }
    return $map[strtolower($dominio)] ?? null;
}
/** Boton «+ Seguir» / «✓ Siguiendo» de un medio o substack (el + y el ✓ los pone el CSS), solo para registrados; sin JS: un POST a
 *  /fav-fuente que vuelve a la misma pagina. Pulsarlo cuando ya se sigue, deja de seguir. $nombre: «Seguir Jot Down» (pagina de la
 *  fuente, hilo: ahi no se ve a que medio se refiere); sin el, «Seguir» a secas (filas del catalogo y tarjetas, que ya llevan el nombre). */
function fuente_fav_form(array $src, bool $nombre = false): string {
    if (!user() || !fuente_seguible($src)) return '';
    $on = fuente_es_fav($src);
    $title = h($on ? t('dejar de seguir %s', $src['name']) : t('Seguir %s', $src['name']));
    return "<form class=\"seguir" . ($on ? ' on' : '') . "\" method=\"post\" action=\"/fav-fuente\"><input type=\"hidden\" name=\"f\" value=\"" . (int)$src['source_id'] . "\"><input type=\"hidden\" name=\"act\" value=\"" . ($on ? 'quitar' : 'guardar') . "\">"
         . "<button title=\"$title\">" . h($on ? t('Siguiendo') : ($nombre ? t('Seguir %s', $src['name']) : t('Seguir'))) . "</button></form>";
}
/** Fila «La siguen N personas» de un medio o substack (/fuente/slug y /medio/dominio, 12/10/2026): avatares de quienes muestran sus
 *  fuentes en el perfil (users.show_follows), por aura; el resto solo cuenta. A quien tengo silenciado (o me silencio) no lo veo
 *  (ghost_veiled). Enlazan a su perfil. Vacio si nadie la sigue. */
function fuente_quien_sigue(PDO $pdo, array $src): string {
    $qs = $pdo->prepare("SELECT u.user_id, u.login, u.avatar, u.aura, u.show_follows FROM user_media um JOIN users u ON u.user_id = um.user_id WHERE um.source_id = ? ORDER BY u.aura DESC, u.login LIMIT 400");
    $qs->execute([(int)$src['source_id']]);
    $seg = $qs->fetchAll(); $nSeg = count($seg);
    if (!$nSeg) return '';
    $vis = array_values(array_filter($seg, fn($r) => (int)$r['show_follows'] === 1 && !ghost_veiled((int)$r['user_id'])));
    $html = "<div class=\"srcquien\"><span class=\"n\">" . ($nSeg === 1 ? t('La sigue %d persona', 1) : t('La siguen %d personas', $nSeg)) . "</span>";
    foreach (array_slice($vis, 0, 24) as $r) $html .= "<a href=\"/u/" . rawurlencode($r['login']) . "\" title=\"" . h($r['login']) . "\">" . avatar((float)$r['aura'], $r['login'], $r['avatar']) . "</a>";
    if (count($vis) > 24) $html .= "<span class=\"mas\">+" . (count($vis) - 24) . "</span>";
    return $html . "</div>";
}
/** Fuentes de un tipo que se ofrecen en el menu: de los favoritos del usuario, los que han publicado mas recientemente (una noticia
 *  por fuente: «los tres ultimos sin repetir fuente») o, si no tiene favoritos (o no tiene cuenta), las destacadas en admin
 *  (sources.featured, fuentes_destacadas()); si no hay ninguna destacada, en medios, substacks y canales las que han publicado mas
 *  recientemente (siempre hay algo fresco, y van rotando solas). Si no sale ninguna, las primeras del catalogo. Nunca mas del
 *  maximo del tipo. */
/** Fecha de la noticia mas reciente de cada fuente: source_id => pub_date (una consulta por peticion). */
function fuentes_ultima_noticia(PDO $pdo): array {
    static $r = null;
    if ($r === null) {
        try { $r = cache_remember('fuentes:ultima', 60, fn() => $pdo->query("SELECT source_id, MAX(pub_date) FROM source_items GROUP BY source_id")->fetchAll(PDO::FETCH_KEY_PAIR)); }   // Redis, 60 s (lib/cache.php)
        catch (Throwable $e) { $r = []; }
    }
    return $r;
}
function medios_menu(PDO $pdo, ?array $u, int $max = 0, string $kind = 'medio'): array {
    $max = $max ?: medios_max($kind);
    $list = mis_medios_recientes($pdo, $u, $kind);   // el filtro de idiomas tambien quita las favoritas en otro
    if (!$list) $list = fuentes_destacadas($pdo, $kind);
    if (!$list && $kind !== 'agregador') {
        $ult = fuentes_ultima_noticia($pdo);
        $list = array_values(array_filter(fuentes_visibles($pdo, $kind), fn($m) => isset($ult[$m['source_id']])));
        usort($list, fn($a, $b) => strcmp($ult[$b['source_id']], $ult[$a['source_id']]));
    }
    if (!$list) $list = array_values(array_filter(fuentes_visibles($pdo, $kind), fn($m) => !empty($m['featured'])));   // destacadas aunque sean de otro idioma
    if (!$list) $list = fuentes_visibles($pdo, $kind);
    return array_slice($list, 0, $max);
}

/* ---- lectura de feeds (cron) ---------------------------------------- */

/** Llamada cada minuto desde promocionar.php: lee las fuentes cuyo intervalo haya vencido. Devuelve lineas de log. */
function fuentes_tick(PDO $pdo, bool $force = false, ?int $maxN = null, ?int $maxSec = null, string $kind = ''): array {   // $kind: solo ese tipo (boton de admin, 15/10/2026)
    if (!fuentes_on()) return [];
    $log = [];
    // Tope por pasada: con muchas fuentes vencidas a la vez (p. ej. 50 substacks recien dados de alta, cada una con 20 fotos que
    // descargar y convertir) una sola pasada tardaba minutos, saturaba la CPU y la web daba 524 en Cloudflare. Ahora se leen como
    // mucho sources_per_tick fuentes y, pasados sources_tick_seconds, no se empieza ninguna mas; las demas, en el minuto siguiente,
    // primero las que llevan mas tiempo sin leerse (las nuevas, sin last_fetch, antes que nadie).
    $maxN   = $maxN   ?? max(1, (int)cfg('sources_per_tick', 3));
    $maxSec = $maxSec ?? max(5, (int)cfg('sources_tick_seconds', 40));
    $t0 = time(); $n = 0;
    $due = array_values(array_filter(fuentes_activas($pdo, $kind), fn($src) => empty($src['relay']) && ($force || !$src['last_fetch'] || time() - strtotime($src['last_fetch']) >= max(1, (int)$src['interval_min']) * 60 - 5)));   // relay = 1: la lee el lector local (TIBERIO), no el cron
    usort($due, fn($a, $b) => strcmp((string)$a['last_fetch'], (string)$b['last_fetch']));   // NULL ('') primero, luego la mas antigua
    foreach ($due as $i => $src) {
        if ($n >= $maxN || time() - $t0 >= $maxSec) { $log[] = "fuentes: " . (count($due) - $i) . " pendientes para la proxima pasada"; break; }
        $n++;
        try { $log = array_merge($log, fuente_actualizar($pdo, $src)); }
        catch (Throwable $e) {
            $log[] = "fuente {$src['slug']}: ERROR " . $e->getMessage();
            $pdo->prepare("UPDATE sources SET last_fetch = NOW(), last_error = ? WHERE source_id = ?")->execute([mb_substr($e->getMessage(), 0, 255), $src['source_id']]);
            fuente_error_log($pdo, (int)$src['source_id'], $e->getMessage());
        }
    }
    return $log;
}

/** Lee los feeds de una fuente y sincroniza source_items.
 *  $xmls (lector local, /api/lector.php): los feeds ya descargados desde fuera, ['portada' => xml, 'nuevas' => xml]; entonces el
 *  servidor no pide nada a la fuente (ni feeds ni fotos: las fotos pendientes se apuntan en $GLOBALS['ddd_relay_pend'] para que
 *  las traiga el lector). Una fuente con relay = 1 no se lee nunca desde el servidor: su web rechaza la IP del droplet.
 *  $manual (29/10/2026, /moderacion -> Errores -> «pegar el feed»): $xmls lo pego un admin a mano; el servidor si pide las fotos. */
function fuente_actualizar(PDO $pdo, array $src, ?array $xmls = null, bool $manual = false): array {
    $log = []; $err = [];
    if (!empty($src['relay']) && $xmls === null) return ["fuente {$src['slug']}: la lee el lector local (relay), no el servidor"];
    $GLOBALS['DDD_RELAY'] = $xmls !== null && !$manual; $GLOBALS['ddd_relay_pend'] = [];
    $timeout = max(3, (int)cfg('sources_timeout_sec', 8));
    $feeds = ['portada' => $src['feed_portada'], 'nuevas' => $src['feed_nuevas']];
    fuente_imagen($src, [], true);   // contadores de imagenes a cero para esta fuente
    // primero la cola y luego la portada: si un item esta en las dos, gana 'portada'
    foreach (['nuevas', 'portada'] as $phase) {
        $url = trim((string)$feeds[$phase]);
        if ($url === '' && !($manual && isset($xmls[$phase]))) continue;
        $xml = $xmls !== null ? (isset($xmls[$phase]) && $xmls[$phase] !== '' ? (string)$xmls[$phase] : null) : http_get($url, $timeout, 4000000);
        if ($xml === null && $xmls !== null && $manual) continue;   // pegado a mano: solo la fase que se pego
        if ($xml === null && $xmls !== null) { $err[] = "$phase: el lector local no pudo descargarlo" . (!empty($xmls["error_$phase"]) ? ' (' . mb_substr((string)$xmls["error_$phase"], 0, 120) . ')' : ''); continue; }
        if ($xml === null) { $err[] = "$phase: sin respuesta" . (($tr = http_last()) ? ' (' . implode(' · ', $tr) . ')' : ''); continue; }   // con el rastro HTTP: 403 = veto del CDN a la IP
        $items = fuentes_parse_rss($xml);
        if (!$items) {
            $d = fuente_desafio($xml); $mot = $d === '' ? fuente_ilegible_motivo($xml) : '';
            if ($d === '' && fuente_sin_novedades_normal($src) && (str_starts_with($mot, 'RSS sin ningun <item>') || str_starts_with($mot, 'Atom sin <entry>'))) { $log[] = "fuente {$src['slug']} $phase: feed sin entradas (normal en substacks y canales)"; continue; }   // no es un error
            $err[] = "$phase: " . ($d !== '' ? "$d en vez del feed: veto a la IP del servidor; probar con «lector local»" : "feed vacio o ilegible ($mot)"); continue;
        }
        if (fuente_feed_actividad($src, $items)) {   // foro XenForo 1 (ForoMTB): el feed va por actividad pero pubDate es la creacion del hilo
            $t0 = time();
            foreach ($items as $k => &$it) { $it['pub_date'] = date('Y-m-d H:i:s', $t0 - $k); $it['actividad'] = true; }   // la fecha de lectura, en el orden del feed
            unset($it);
            $log[] = "fuente {$src['slug']} $phase: feed por actividad con fechas de creacion; se usa la de lectura";
        }
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
            if (!$items) {
                if (fuente_sin_novedades_normal($src)) { $log[] = "fuente {$src['slug']} $phase: nada en los ultimos $maxAge dias (normal en substacks y canales)"; continue; }   // no es un error
                $err[] = "$phase: todo lo del feed tiene mas de $maxAge dias"; continue;
            }
        }
        if (($src['kind'] ?? '') === 'youtube' && (string)cfg('youtube_titulares', '1') === '1') {   // titulares de YouTube: sin GRITOS ni emojis
            foreach ($items as &$it) { $it['title'] = titular_normal($it['title']); $it['content'] = sin_emojis($it['content']); }
            unset($it);
        } else {   // el resto (agregadores, foros, medios): sin emojis en el titular (08/10/2026: en agregadores y canales salian a montones)
            foreach ($items as &$it) $it['title'] = sin_emojis($it['title']) ?: $it['title'];
            unset($it);
        }
        if (!empty($items[0]['smf'])) { $k = fuente_smf_completar($pdo, $src, $items, $timeout); if ($k) $log[] = "fuente {$src['slug']}: $k mensajes leidos del foro"; }
        if (str_contains($url, 'renegados.es')) { $k = fuente_renegados_completar($items, $timeout); if ($k) $log[] = "fuente {$src['slug']} $phase: votos y comentarios de la API para $k items"; }
        if (str_contains($url, 'gatopardo.com')) { $k = fuente_gatopardo_completar($pdo, $src, $items, $timeout); if ($k) $log[] = "fuente {$src['slug']} $phase: titular, entradilla y foto leidos de $k articulos"; }
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
    if ($err) { $log[] = "fuente {$src['slug']}: " . implode(' · ', $err); fuente_error_log($pdo, (int)$src['source_id'], implode(' · ', $err)); }
    return $log;
}

/** Substacks (kind newsletter o alojadas en substack.com) y canales de YouTube: el feed siempre funciona, y que venga sin entradas o
 *  sin nada reciente solo quiere decir que el autor lleva tiempo sin publicar. No es un error y no sale en /moderacion -> Errores (29/10/2026). */
function fuente_sin_novedades_normal(array $src): bool {
    return in_array($src['kind'] ?? '', ['newsletter', 'youtube'], true) || str_contains((string)($src['home'] ?? ''), 'substack.com') || str_contains((string)($src['feed_portada'] ?? ''), 'substack.com')
        || str_contains((string)($src['feed_portada'] ?? ''), 'youtube.com/feeds/');
}

/** Un 200 cuyo cuerpo no es el feed sino un desafio anti-bots (29/10/2026: Semrush devolvia un reCAPTCHA al droplet; Cloudflare, un
 *  «Just a moment…»). Devuelve el nombre del desafio o '' si el cuerpo no lo parece. Solo se mira cuando el parser no saca nada. */
function fuente_desafio(string $body): string { return http_es_desafio($body); }   // la deteccion vive en imagen.php (http_get la usa en la cascada)

/** Por que no se ha sacado ningun item de un cuerpo (29/10/2026): si es HTML, lo dice; si es XML, el primer error de libxml o que no
 *  tiene <item>/<entry> (p. ej. un feed vacio de verdad o sin <link> en los items); y siempre con que empieza el texto. */
function fuente_ilegible_motivo(string $body): string {
    $ini = trim(preg_replace('/\s+/u', ' ', mb_substr(ltrim($body), 0, 70)));
    if ($ini === '') return 'respuesta vacia';
    if (preg_match('/^\s*<(!doctype html|html)/i', $body)) return 'es HTML, no un feed; empieza: ' . $ini;
    $prev = libxml_use_internal_errors(true);
    $raw = simplexml_load_string(fuentes_envolver_fragmento($body), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    $le = libxml_get_errors(); libxml_clear_errors(); libxml_use_internal_errors($prev);
    $doc = fuentes_xml($body);   // con las reparaciones (& sueltos, RECOVER)
    if (!$doc) return 'XML mal formado: ' . ($le ? trim($le[0]->message) . ' (linea ' . $le[0]->line . ')' : 'sin detalle') . '; empieza: ' . $ini;
    $rep = !$raw && $le ? ' (XML reparado: ' . trim($le[0]->message) . ', linea ' . $le[0]->line . ')' : '';
    $root = $doc->getName();
    if ($root === 'feed') return 'Atom sin <entry> con enlace' . $rep;
    if ($root !== 'rss' && $root !== 'RDF') return "raiz <$root>, no <rss> ni <feed>; empieza: $ini" . $rep;
    if (!isset($doc->channel->item)) return 'RSS sin ningun <item>' . $rep;
    return 'items sin <link>' . $rep;
}

/** Historial de errores de lectura (source_errors, migra-137; se ve en /moderacion -> Errores). Una fila por fuente y texto de error:
 *  la primera vez se crea; las siguientes solo suman veces y actualizan last_seen, asi una fuente caida no llena la tabla cada media hora. */
function fuente_error_log(PDO $pdo, int $sid, string $msg): void {
    $msg = mb_substr(trim($msg), 0, 255);
    if ($msg === '') return;
    try {
        $pdo->prepare("INSERT INTO source_errors (source_id, errhash, error, first_seen, last_seen, times) VALUES (?,?,?,NOW(),NOW(),1)
                       ON DUPLICATE KEY UPDATE last_seen = NOW(), times = times + 1")->execute([$sid, md5($msg), $msg]);
    } catch (Throwable $e) { /* sin la migra 137 */ }
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
        // numero del mensaje dentro del hilo: SMF 2.0 lo ponia en el enlace de la fecha (class="smalltext">#748); SMF 2.1 (Areopago desde
        // octubre de 2026) en <span class="page_number floatright">#748</span> dentro del bloque id="msgN", y en title="Respuesta nº748"
        if (preg_match('/id="msg' . $id . '".{0,6000}?class="page_number[^"]*">#(\d+)</su', $html, $m)) $it['comments'] = (int)$m[1];
        elseif (preg_match('/msg=' . $id . '["&][^>]*title="Respuesta n[ºo°]\s*(\d+)/u', $html, $m)) $it['comments'] = (int)$m[1];
        elseif (preg_match('/msg=' . $id . '["&][^>]*class="smalltext">#(\d+)</', $html, $m)) $it['comments'] = (int)$m[1];
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
const DDD_FEEDS_SIN_VOTOS = ['action=.xml', 'index.rss', 'external.php', 'mediavida.com/foro', 'press.asimov.com/articles', 'pagint.onrender.com', 'www.publico.es/', 'www.gatopardo.com/'];   // SMF · XenForo (Burbuja, ForoMTB, ForoBeta) · vBulletin (HTCMania) · Mediavida (sin feed: se lee la tabla de /foro/spy)
/** Foros XenForo y vBulletin: el feed trae el primer mensaje con sus fotos (emoticonos, capturas, anuncios de venta); no se descargan:
 *  el listado de un foro no lleva miniaturas. */
function fuente_sin_fotos(array $src): bool {
    foreach (['index.rss', 'external.php', 'mediavida.com/foro'] as $p) if (str_contains((string)$src['feed_portada'], $p)) return true;
    return false;
}
/** ¿Es un feed de foro ordenado por actividad cuyo pubDate es la fecha de CREACION del hilo (XenForo 1: ForoMTB)? Se nota porque los
 *  items no vienen en orden de fecha descendente. En XenForo 2 (ForoBeta, Burbuja) pubDate es el ultimo mensaje y el feed si va ordenado.
 *  Solo se mira en feeds index.rss; para estos, el lector pone como fecha la de la lectura (el feed ya es «lo ultimo con actividad»). */
function fuente_feed_actividad(array $src, array $items): bool {
    if (!str_contains((string)$src['feed_portada'], 'index.rss') || count($items) < 3) return false;
    for ($k = 1; $k < count($items); $k++) if ($items[$k]['pub_date'] > $items[$k - 1]['pub_date']) return true;
    return false;
}
function fuente_sin_votos(array $src): bool {
    foreach (DDD_FEEDS_SIN_VOTOS as $p) if (str_contains((string)$src['feed_portada'], $p)) return true;
    return false;
}

/** Upsert de los items de un feed. Devuelve [nuevos, actualizados]. */
function fuente_guardar(PDO $pdo, array $src, string $phase, array $items): array {
    $sel = $pdo->prepare("SELECT item_id, phase, image, comments FROM source_items WHERE source_id = ? AND ext_id = ?");
    $ins = $pdo->prepare("INSERT INTO source_items (source_id, ext_id, phase, title, url, thread_url, source, topic, author, content, image, votes, negatives, comments, pub_date)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    // (la fecha nueva se decide en PHP: MySQL evalua los SET de izquierda a derecha y phase ya tendria el valor nuevo)
    $upd = $pdo->prepare("UPDATE source_items SET phase = ?, title = ?, url = ?, thread_url = ?, source = COALESCE(?, source), topic = COALESCE(?, topic), content = IF(? = '', content, ?),
                          image = COALESCE(image, ?), votes = ?, negatives = ?, comments = GREATEST(comments, ?), pub_date = COALESCE(?, pub_date), last_seen = NOW() WHERE item_id = ?");   // url/thread_url se refrescan: Renegados los completa desde su API
    $new = $updated = 0;
    foreach ($items as $it) {
        $it['url'] = url_limpia((string)$it['url']);   // los feeds traen utm_source=rss y similares: fuera
        $sel->execute([$src['source_id'], $it['ext_id']]);
        $row = $sel->fetch();
        if ($row && $row['phase'] === 'portada' && $phase === 'nuevas') {   // ya esta en portada: la cola no la degrada
            $pdo->prepare("UPDATE source_items SET last_seen = NOW() WHERE item_id = ?")->execute([$row['item_id']]);
            continue;
        }
        $img = ($row && $row['image']) ? null : fuente_imagen($src, $it);
        if ($row) {
            $newDate = $row['phase'] === $phase ? null : $it['pub_date'];   // cambio de fase (nuevas -> portada): fecha de publicacion
            if (!empty($it['actividad']) && (int)$it['comments'] > (int)$row['comments']) $newDate = $it['pub_date'];   // feed por actividad: el hilo con respuestas nuevas sube
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
        if (empty($it['thumb']) || fuente_sin_fotos($src)) return null;
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
        elseif ($n = imagen_de_pagina($bytes, (string)$it['url'], (string)$it['thumb'])) return $n;   // sin la generica del medio (logo, relleno)
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
/** Un trozo de feed pegado a mano que empieza por <item> o <entry> (sin la cabecera <rss>/<feed> con los xmlns) se envuelve en un canal
 *  con los espacios de nombres habituales, para que libxml no lo rechace por «Namespace prefix media not defined» (29/10/2026). */
function fuentes_envolver_fragmento(string $xml): string {
    $t = ltrim($xml);
    if (preg_match('/^<item[\s>]/i', $t)) return '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:dc="http://purl.org/dc/elements/1.1/"'
        . ' xmlns:dcterms="http://purl.org/dc/terms" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:slash="http://purl.org/rss/1.0/modules/slash/"'
        . ' xmlns:meneame="http://meneame.net/faq-es.php" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"'
        . ' xmlns:georss="http://www.georss.org/georss" xmlns:yt="http://www.youtube.com/xml/schemas/2015"><channel><title>fragmento</title>' . $t . '</channel></rss>';
    if (preg_match('/^<entry[\s>]/i', $t)) return '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns:dc="http://purl.org/dc/elements/1.1/">' . $t . '</feed>';
    return $xml;
}
/** Carga un feed con simplexml tolerando lo que libxml rechaza y es frecuente en feeds reales (29/10/2026, Irish Mirror / Reach plc):
 *  1) tal cual; 2) escapando los «&» sueltos (xmlParseEntityRef: no name); 3) en modo RECOVER. Devuelve null si ni asi. */
function fuentes_xml(string $xml): ?SimpleXMLElement {
    $xml = fuentes_envolver_fragmento($xml);
    $prev = libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    if (!$doc) {
        $fix = preg_replace('/&(?!(?:[A-Za-z][A-Za-z0-9]*|#[0-9]+|#x[0-9A-Fa-f]+);)/', '&amp;', $xml);
        if ($fix !== $xml) { libxml_clear_errors(); $doc = simplexml_load_string($fix, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET); }
        if (!$doc) { libxml_clear_errors(); $doc = simplexml_load_string($fix ?? $xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET | 1 /* XML_PARSE_RECOVER: PHP no define la constante */ | LIBXML_NOERROR | LIBXML_NOWARNING); }
    }
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    if (!($doc instanceof SimpleXMLElement)) return null;
    try { $doc->getName(); } catch (Throwable $e) { return null; }   // en modo RECOVER con texto que no es XML, simplexml devuelve un objeto sin inicializar
    return $doc;
}
function fuentes_parse_rss(string $xml): array {
    if (str_contains($xml, 'id="tablatemas"')) return fuentes_parse_mediavida($xml);   // Mediavida: no hay feed, se lee la tabla de hilos
    if (str_contains($xml, 'class="ap-card"')) return fuentes_parse_asimov($xml);        // Asimov Press: sin feed, se lee /articles (migra-130)
    if (preg_match('/^\s*\[\s*\{/', $xml) && str_contains($xml, '"slugSeccion"')) return fuentes_parse_pagint($xml);   // Página Internacional: su API JSON (migra-130)
    if (str_contains($xml, 'm-preview-') && str_contains($xml, 'publico.es')) return fuentes_parse_publico($xml);   // Público: sin RSS desde 2024, se lee la portada (migra-136)
    if (str_contains($xml, 'gatopardo.com') && str_contains($xml, '/articulos/') && str_contains($xml, 'website-files.com')) return fuentes_parse_gatopardo($xml);   // Gatopardo (Webflow, sin RSS): se lee la portada (migra-161)
    if (preg_match('/^\s*\[\s*\{/', $xml) && str_contains($xml, '"rendered"') && str_contains($xml, '"link"')) return fuentes_parse_wpjson($xml);   // WordPress sin RSS: su API REST /wp-json/wp/v2/posts (migra-150, Rebelión)
    $doc = fuentes_xml($xml);
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
        $link = preg_replace('/&goto=newpost$/', '', $link);   // vBulletin (HTCMania): el enlace salta al ultimo mensaje; sin eso, al principio del hilo
        $desc = (string)$i->description;
        $full = isset($ce->encoded) ? (string)$ce->encoded : '';
        // XenForo (ForoMTB, ForoBeta): el extracto del primer mensaje acaba con un enlace al hilo («Leer más» o el titulo repetido)
        if ($full !== '') $full = preg_replace('~(?:\s|<br\s*/?>)*<a [^>]*class="(?:internalLink|link link--internal)"[^>]*>[^<]*</a>\s*(?:</div>)?\s*$~u', '', $full);
        $desc = preg_replace('~^Image: \S+\s*~u', '', $desc);   // vBulletin: la descripcion en texto plano empieza por la URL de la primera foto
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
            'title'      => mb_substr(texto_plano((string)$i->title), 0, 200),   // sin etiquetas ni entidades (texto_plano, 08/10/2026)
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

/** Mediavida (sin RSS): la tabla #tablatemas de /foro/spy (hilos con actividad reciente; /foro/new son los hilos nuevos). Por fila:
 *  <tr id="tNNN">, el subforo en td.autor-avatar a[original-title], el titulo en a.hb, las respuestas en span.num.reply[title="Este tema
 *  tiene N respuestas…"], el ultimo autor en td.col-av a[href=/id/usuario] y la hora del ultimo mensaje en span.m_date[data-time] (epoch).
 *  OJO: en el codigo fuente (no en el DOM del navegador) el enlace del hilo va con comillas simples (<a id='aNNN' href='…'>) y el
 *  subforo en title= (original-title lo pone su JS): los atributos se leen con cualquiera de las dos comillas.
 *  No trae extracto: content vacio. url = el hilo desde el principio; thread_url = el ultimo mensaje. Mismos campos que el RSS. */
function fuentes_parse_mediavida(string $html): array {
    $base = 'https://www.mediavida.com';
    $q = '(["\'])';   // la comilla que abra el atributo; \\N cierra con la misma
    if (!preg_match('~<tbody[^>]*>(.*?)</tbody>~su', $html, $tb)) $tb = [1 => $html];
    if (!preg_match_all('~<tr\s+id=' . $q . 't(\d+)\1[^>]*>(.*?)</tr>~su', $tb[1], $trs, PREG_SET_ORDER)) return [];
    $out = []; $now = time();
    $dec = fn(string $v): string => trim(html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    foreach ($trs as $tr) {
        [$row, $id] = [$tr[3], (int)$tr[2]];
        if (!preg_match('~<a\s+id=' . $q . 'a\d+\1[^>]*?\shref=' . $q . '([^"\']+)\2[^>]*>(.*?)</a>~su', $row, $m)) continue;
        $href = $dec($m[3]);
        if (!str_starts_with($href, 'http')) $href = $base . $href;
        $title = $dec(strip_tags($m[4]));
        if ($title === '') continue;
        $topic   = preg_match('~class=' . $q . 'autor-avatar\1.*?\s(?:original-)?title=' . $q . '([^"\']*)\2~su', $row, $t) ? $dec($t[3]) : null;
        $replies = preg_match('~class=' . $q . 'num reply\1[^>]*title=' . $q . 'Este tema tiene (\d+)~u', $row, $r) ? (int)$r[3] : 0;
        $author  = preg_match('~class=' . $q . 'col-av[^"\']*\1.*?href=' . $q . '/id/([^"\']+)\2~su', $row, $a) ? $dec(rawurldecode($a[3])) : null;   // /id/usuario (sin avatar hay una letra, no <img>)
        $ts      = preg_match('~class=' . $q . 'm_date[^"\']*\1[^>]*data-time=' . $q . '(\d+)\2~u', $row, $d) ? (int)$d[3] : (preg_match('~data-time=' . $q . '(\d+)\1~u', $row, $d) ? (int)$d[2] : $now);
        $last    = preg_match('~class=' . $q . 'last-av[^"\']*\1.*?<a\s+href=' . $q . '([^"\']+)\2~su', $row, $l) ? $dec($l[3]) : $href;
        if (!str_starts_with($last, 'http')) $last = $base . $last;
        $out[] = [
            'ext_id'     => 'mv:' . $id,
            'title'      => mb_substr($title, 0, 200),
            'url'        => mb_substr($href, 0, 600),
            'thread_url' => mb_substr($last, 0, 600),
            'source'     => null,
            'topic'      => $topic ? mb_substr($topic, 0, 48) : null,
            'author'     => $author ? mb_substr($author, 0, 64) : null,
            'content'    => '',
            'thumb'      => '',
            'votes'      => 0,
            'negatives'  => 0,
            'comments'   => $replies,
            'pub_date'   => date('Y-m-d H:i:s', $ts),
            'smf'        => null,
        ];
    }
    return $out;
}

/** Asimov Press (sin RSS, migra-130): la lista de https://press.asimov.com/articles. Cada articulo es <a href="/articles/slug" class="ap-card">
 *  con la categoria y la fecha («September 28, 2026») en .ap-card__kicker, el titulo en h2.ap-card__title, el extracto en p.ap-card__excerpt,
 *  el autor en .ap-card__author strong y la foto en .ap-card__media img. Sin votos ni comentarios. */
function fuentes_parse_asimov(string $html): array {
    $base = 'https://press.asimov.com';
    if (!preg_match_all('~<a\s+href="([^"]+)"\s+class="ap-card"[^>]*>(.*?)</a>~su', $html, $cards, PREG_SET_ORDER)) return [];
    $out = [];
    $dec = fn(string $v): string => trim(html_entity_decode(strip_tags($v), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    foreach ($cards as $c) {
        [$href, $card] = [$c[1], $c[2]];
        if (!str_starts_with($href, 'http')) $href = $base . $href;
        if (!preg_match('~<h2[^>]*class="ap-card__title"[^>]*>(.*?)</h2>~su', $card, $t)) continue;
        $title = $dec($t[1]);
        if ($title === '') continue;
        $excerpt = preg_match('~<p[^>]*class="ap-card__excerpt"[^>]*>(.*?)</p>~su', $card, $e) ? $dec($e[1]) : '';
        $author  = preg_match('~class="ap-card__author"[^>]*>.*?<strong[^>]*>(.*?)</strong>~su', $card, $a) ? $dec($a[1]) : null;
        $img     = preg_match('~<img[^>]+src="([^"]+)"~u', $card, $i) ? html_entity_decode($i[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
        if ($img !== '' && !str_starts_with($img, 'http')) $img = $base . $img;
        $topic = null; $ts = time();
        if (preg_match('~class="ap-card__kicker"[^>]*>(.*?)</div>~su', $card, $k)) {
            $partes = array_values(array_filter(array_map($dec, preg_split('~<span[^>]*class="ap-card__dot"[^>]*>.*?</span>~su', $k[1]) ?: [])));
            if (isset($partes[0]) && $partes[0] !== '') $topic = $partes[0];
            foreach ($partes as $px) if (($t2 = strtotime($px)) !== false && $t2 > 946684800) $ts = $t2;
        }
        $out[] = ['ext_id' => strlen($href) <= 32 ? $href : substr(sha1($href), 0, 32), 'title' => mb_substr($title, 0, 200), 'url' => mb_substr($href, 0, 600), 'thread_url' => '', 'source' => null,   // ext_id es VARCHAR(32): los slugs largos fallaban (11/10/2026); los cortos se dejan como estaban para no duplicar
                  'topic' => $topic ? mb_substr($topic, 0, 48) : null, 'author' => $author ? mb_substr($author, 0, 64) : null, 'content' => mb_substr($excerpt, 0, 2000),
                  'thumb' => $img, 'votes' => 0, 'negatives' => 0, 'comments' => 0, 'pub_date' => date('Y-m-d H:i:s', $ts), 'smf' => null];
    }
    return $out;
}

/** Público (publico.es; cerro sus RSS en diciembre de 2024; migra-136, 11/10/2026): se lee la portada. Cada pieza es un <article class="… m-preview-…">
 *  con el titular en h2.title > a (href relativo, /seccion/slug.html), la foto opcional en a.image picture img (src = version movil; se toma la
 *  mayor de los <source srcset>), el autor en p.author > a y a veces un subtitulo en p.epigraph. No hay fecha: la primera vez que se ve una
 *  pieza se le pone la hora de lectura menos su posicion en portada (en minutos), para que conserve el orden del periodico; en lecturas
 *  posteriores la fecha no se toca. Solo enlaces propios (los de sus cabeceras hermanas y promociones se dejan); el tema sale de la seccion. */
function fuentes_parse_publico(string $html): array {
    $base = 'https://www.publico.es';
    if (!preg_match_all('~<article\b[^>]*class="[^"]*m-preview-[^"]*"[^>]*>(.*?)</article>~su', $html, $arts)) return [];
    $dec = fn(string $v): string => trim(html_entity_decode(strip_tags($v), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $TEMAS = ['politica' => 'política', 'internacional' => 'internacional', 'sociedad' => 'sociedad', 'economia' => 'economía', 'culturas' => 'cultura', 'ciencias' => 'ciencia',
              'deportes' => 'deportes', 'opinion' => 'opinión', 'espacio-publico' => 'opinión', 'mujer' => 'mujer', 'tremending' => 'tremending', 'mira' => 'vídeo'];
    $FUERA = ['comunidad', 'public', 'cp', 'uwu', 'manana-en-publico', 'suscripcion', 'suscribete'];   // promociones, suscripcion, hemeroteca
    $out = []; $vistos = []; $pos = 0; $ahora = time();
    foreach ($arts[1] as $a) {
        if (!preg_match('~<h2[^>]*class="title[^"]*"[^>]*>\s*<a[^>]*href="([^"]+)"[^>]*>(.*?)</a>~su', $a, $t)) continue;
        $href = html_entity_decode($t[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!str_starts_with($href, '/') || isset($vistos[$href])) continue;   // solo lo propio, una vez
        $sec = explode('/', trim($href, '/'))[0] ?? '';
        if ($sec === '' || in_array($sec, $FUERA, true) || !str_ends_with($href, '.html')) continue;
        $title = $dec($t[2]);
        if (mb_strlen($title) < 8) continue;
        $vistos[$href] = true; $pos++;
        $img = '';
        if (preg_match_all('~<source[^>]+srcset="([^"\s]+)~u', $a, $ss) && $ss[1]) $img = $ss[1][1] ?? $ss[1][0];   // la segunda fuente (990 px) si la hay
        elseif (preg_match('~<img[^>]+src="([^"]+)"~u', $a, $i)) $img = $i[1];
        $img = html_entity_decode($img, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($img !== '' && !str_starts_with($img, 'http')) $img = $base . $img;
        if (str_contains($img, '/files/avatar/')) $img = '';   // columnas de opinion: la foto es el avatar del autor; mejor la og:image del articulo al ingerir
        $author  = preg_match('~<p[^>]*class="author[^"]*"[^>]*>.*?<a[^>]*>(.*?)</a>~su', $a, $au) ? $dec($au[1]) : null;
        $excerpt = preg_match('~<p[^>]*class="epigraph[^"]*"[^>]*>(.*?)</p>~su', $a, $e) ? $dec($e[1]) : '';
        $topic = $TEMAS[$sec] ?? ucfirst(str_replace('-', ' ', $sec));
        $url = $base . $href;
        $out[] = ['ext_id' => substr(sha1($url), 0, 32), 'title' => mb_substr($title, 0, 200), 'url' => mb_substr($url, 0, 600), 'thread_url' => '', 'source' => null,   // ext_id es VARCHAR(32): hash de la URL
                  'topic' => mb_substr($topic, 0, 48), 'author' => $author !== null && $author !== '' ? mb_substr($author, 0, 64) : null, 'content' => mb_substr($excerpt, 0, 2000),
                  'thumb' => $img, 'votes' => 0, 'negatives' => 0, 'comments' => 0, 'pub_date' => date('Y-m-d H:i:s', $ahora - $pos * 60), 'smf' => null];
    }
    return $out;
}

/** Gatopardo (gatopardo.com, revista mexicana de cronica y reportaje; migra-161). Es un sitio Webflow sin RSS: se lee la portada
 *  (feed_portada = https://www.gatopardo.com/) y se sacan, en el orden en que aparecen, los enlaces a /articulos/slug con su foto
 *  (cdn.prod.website-files.com). La portada no trae un titular fiable (bloques con texto de relleno de Webflow), asi que aqui se pone
 *  lo mejor que haya y fuente_gatopardo_completar() lee despues la pagina de cada articulo nuevo (og:title, og:description, og:image
 *  y fecha de publicacion), unos pocos por pasada. Sin votos ni comentarios. */
function fuentes_parse_gatopardo(string $html): array {
    $base = 'https://www.gatopardo.com';
    if (!preg_match_all('~<a\b[^>]*href="(?:https?://(?:www\.)?gatopardo\.com)?/articulos/([a-z0-9][a-z0-9_-]*)/?(?:[?#][^"]*)?"[^>]*>(.*?)</a>~su', $html, $m, PREG_SET_ORDER)) return [];
    $dec = fn(string $v): string => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($v), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $RELLENO = ['this is some text inside of a div block.', 'leer más', 'leer mas', 'ver más', 'ver mas', 'read more'];
    $GENEROS = ['reportaje', 'crónica', 'cronica', 'nota', 'ensayo', 'entrevista', 'perfil', 'opinión', 'opinion', 'fotogalería', 'fotogaleria', 'columna', 'podcast', 'video', 'vídeo'];
    $cards = []; $orden = [];
    foreach ($m as $a) {
        $slug = $a[1]; $blk = $a[2];
        if (!isset($cards[$slug])) { $cards[$slug] = ['title' => '', 'img' => '', 'topic' => '', 'excerpt' => '']; $orden[] = $slug; }
        $c = &$cards[$slug];
        if ($c['img'] === '' && preg_match('~<img[^>]+src="(https://cdn\.prod\.website-files\.com/[^"\s]+)"~u', $blk, $i)) $c['img'] = html_entity_decode($i[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($c['title'] === '' && preg_match('~<h[1-6][^>]*>(.*?)</h[1-6]>~su', $blk, $h) && mb_strlen($t = $dec($h[1])) >= 8) $c['title'] = $t;
        // sin cabecera: el trozo de texto mas largo del bloque que no sea relleno, genero, año o region
        if ($c['title'] === '') {
            $mejor = '';
            foreach (preg_split('~<[^>]+>~', $blk) as $tx) {
                $t = $dec($tx);
                if (mb_strlen($t) < 8 || in_array(mb_strtolower($t), $RELLENO, true) || in_array(mb_strtolower($t), $GENEROS, true) || preg_match('/^\d{4}$/', $t)) continue;
                if (mb_strlen($t) > mb_strlen($mejor)) $mejor = $t;
            }
            $c['title'] = $mejor;
        }
        if ($c['topic'] === '') foreach (preg_split('~<[^>]+>~', $blk) as $tx) { $t = mb_strtolower($dec($tx)); if (in_array($t, $GENEROS, true)) { $c['topic'] = $t; break; } }
        unset($c);
    }
    $out = []; $pos = 0; $ahora = time();
    foreach ($orden as $slug) {
        $c = $cards[$slug]; $pos++;
        $url = "$base/articulos/$slug";
        $title = $c['title'] !== '' ? $c['title'] : ucfirst(str_replace('-', ' ', $slug));   // provisional: lo corrige fuente_gatopardo_completar()
        $out[] = ['ext_id' => substr(sha1($url), 0, 32), 'title' => mb_substr($title, 0, 200), 'url' => mb_substr($url, 0, 600), 'thread_url' => '', 'source' => null,
                  'topic' => $c['topic'] !== '' ? mb_substr(ucfirst($c['topic']), 0, 48) : null, 'author' => null, 'content' => '',
                  'thumb' => $c['img'], 'votes' => 0, 'negatives' => 0, 'comments' => 0, 'pub_date' => date('Y-m-d H:i:s', $ahora - $pos * 60), 'smf' => null, 'gatopardo' => true];
        if (count($out) >= 40) break;
    }
    return $out;
}
/** Lee la pagina de cada articulo de Gatopardo que aun no esta guardado (o se guardo sin entradilla) y completa titular (og:title),
 *  entradilla (og:description), foto (og:image), autor y fecha de publicacion (article:published_time o datePublished del JSON-LD).
 *  Como mucho sources_gatopardo_per_run (6) paginas por pasada: el resto se completa en las siguientes. Devuelve cuantos completo. */
function fuente_gatopardo_completar(PDO $pdo, array $src, array &$items, int $timeout): int {
    if (!empty($GLOBALS['DDD_RELAY'])) return 0;   // por el lector local no se piden paginas desde el servidor
    $max = max(0, (int)cfg('sources_gatopardo_per_run', 6));
    if ($max === 0) return 0;
    $hechos = [];   // ext_id => titulo ya guardado de los articulos completados (content no vacio)
    try {
        $st = $pdo->prepare("SELECT ext_id, title FROM source_items WHERE source_id = ? AND content IS NOT NULL AND content <> ''");
        $st->execute([(int)$src['source_id']]);
        $hechos = $st->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Throwable $e) {}
    $dec = fn(string $v): string => trim(preg_replace('/\s+/u', ' ', html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $meta = function (string $html, array $names) use ($dec): string {
        foreach ($names as $n) {
            $q = preg_quote($n, '/');
            if (preg_match('/<meta[^>]+(?:property|name)=["\']' . $q . '["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $x)
             || preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+(?:property|name)=["\']' . $q . '["\']/i', $html, $x)) { $v = $dec($x[1]); if ($v !== '') return $v; }
        }
        return '';
    };
    $n = 0;
    foreach ($items as &$it) {
        if (empty($it['gatopardo'])) continue;
        if (isset($hechos[$it['ext_id']])) { $it['title'] = (string)$hechos[$it['ext_id']]; continue; }   // ya completado: que el UPDATE no pise el titular bueno con el provisional de la portada
        if ($n >= $max) break;
        $html = http_get((string)$it['url'], $timeout, 1500000);
        if ($html === null || $html === '') continue;
        $n++;
        if (($t = $meta($html, ['og:title', 'twitter:title'])) !== '') $it['title'] = mb_substr(preg_replace('/\s+[\-|–—·]\s+Gatopardo$/u', '', $t), 0, 200);
        elseif (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $x) && ($t = $dec(strip_tags($x[1]))) !== '') $it['title'] = mb_substr(preg_replace('/\s+[\-|–—·]\s+Gatopardo$/u', '', $t), 0, 200);
        if (($d = $meta($html, ['og:description', 'twitter:description', 'description'])) !== '') $it['content'] = mb_substr($d, 0, 2000);
        if (($i = $meta($html, ['og:image', 'twitter:image'])) !== '' && preg_match('#^https?://#i', $i)) $it['thumb'] = $i;
        if (($au = $meta($html, ['author', 'article:author'])) !== '' && !preg_match('#^https?://#i', $au)) $it['author'] = mb_substr($au, 0, 64);
        $fecha = $meta($html, ['article:published_time', 'datePublished']);
        if ($fecha === '' && preg_match('/"datePublished"\s*:\s*"([^"]+)"/', $html, $x)) $fecha = $x[1];
        if ($fecha !== '' && ($ts = strtotime($fecha)) !== false && $ts > 0 && $ts <= time() + 86400) $it['pub_date'] = date('Y-m-d H:i:s', $ts);
        if ($it['content'] === '') $it['content'] = "\u{00A0}";   // marca de «ya leido» aunque la pagina no traiga descripcion (un espacio normal contaria como vacio en MySQL)
    }
    unset($it);
    return $n;
}

/** WordPress que ha quitado el RSS pero deja abierta su API REST (migra-150, Rebelión: /feed/ da 410). Como «RSS» de la fuente se pone
 *  https://sitio/wp-json/wp/v2/posts?per_page=20&_embed=1 y el lector lo trata como un feed mas: titulo, enlace, fecha (date_gmt),
 *  resumen (excerpt), y con _embed la foto destacada, el autor y la primera categoria. Sirve para cualquier otro WordPress igual. */
function fuentes_parse_wpjson(string $json): array {
    $data = json_decode($json, true);
    if (!is_array($data)) return [];
    $dec = fn($v): string => trim(html_entity_decode(strip_tags((string)$v), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $out = [];
    foreach ($data as $p) {
        if (!is_array($p) || empty($p['link']) || empty($p['title']['rendered'])) continue;
        $url = (string)$p['link']; $title = $dec($p['title']['rendered']);
        if (mb_strlen($title) < 4) continue;
        $gmt = (string)($p['date_gmt'] ?? ''); $ts = $gmt !== '' ? strtotime($gmt . ' UTC') : strtotime((string)($p['date'] ?? 'now'));
        $emb = is_array($p['_embedded'] ?? null) ? $p['_embedded'] : [];
        $img = (string)($emb['wp:featuredmedia'][0]['source_url'] ?? ($emb['wp:featuredmedia'][0]['media_details']['sizes']['large']['source_url'] ?? ($p['jetpack_featured_media_url'] ?? '')));
        $author = $dec($emb['author'][0]['name'] ?? '');
        $topic = $dec($emb['wp:term'][0][0]['name'] ?? '');
        $out[] = ['ext_id' => substr(sha1($url), 0, 32), 'title' => mb_substr($title, 0, 200), 'url' => mb_substr($url, 0, 600), 'thread_url' => '', 'source' => null,
                  'topic' => $topic !== '' ? mb_substr($topic, 0, 48) : null, 'author' => $author !== '' ? mb_substr($author, 0, 64) : null,
                  'content' => mb_substr($dec($p['excerpt']['rendered'] ?? ''), 0, 2000), 'thumb' => $img, 'votes' => 0, 'negatives' => 0, 'comments' => 0,
                  'pub_date' => date('Y-m-d H:i:s', $ts ?: time()), 'smf' => null];
    }
    return $out;
}

/** Página Internacional (sin RSS, migra-130): la web es una aplicacion Flutter sin HTML util, pero su API publica (Strapi en
 *  pagint.onrender.com) devuelve la portada en JSON: id, titulo, subtitulo, autor, fecha, imagen, medio (el periodico original: NYT,
 *  Economist…), slugSeccion / tituloSeccion. El articulo esta en https://paginainternacional.com/articulo/ID/slug. Sin votos ni comentarios. */
function fuentes_parse_pagint(string $json): array {
    $data = json_decode($json, true);
    if (!is_array($data)) return [];
    $out = [];
    foreach ($data as $a) {
        if (!is_array($a) || empty($a['titulo']) || empty($a['id'])) continue;
        $url = 'https://paginainternacional.com/articulo/' . (int)$a['id'] . '/' . rawurlencode((string)($a['slug'] ?? ''));
        $ts = !empty($a['fecha']) ? strtotime((string)$a['fecha']) : false;
        $sub = trim((string)($a['subtitulo'] ?? ''));
        $medio = trim((string)($a['medio'] ?? ''));
        $out[] = ['ext_id' => 'pi:' . (int)$a['id'], 'title' => mb_substr(trim((string)$a['titulo']), 0, 200), 'url' => mb_substr($url, 0, 600), 'thread_url' => '',
                  'source' => null,   // el periodico original ($a['medio']: NYT, Economist…) se añade al extracto, que es donde se lee
                  'topic' => !empty($a['tituloSeccion']) ? mb_substr((string)$a['tituloSeccion'], 0, 48) : null,
                  'author' => !empty($a['autor']) ? mb_substr(trim((string)$a['autor']), 0, 64) : null,
                  'content' => mb_substr(($medio !== '' && $medio !== 'Página Internacional' ? $medio . ' · ' : '') . $sub, 0, 2000), 'thumb' => (string)($a['imagenMedium'] ?? $a['imagen'] ?? ''),
                  'votes' => 0, 'negatives' => 0, 'comments' => 0, 'pub_date' => date('Y-m-d H:i:s', $ts ?: time()), 'smf' => null];
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
            'title'      => mb_substr(texto_plano((string)$e->title), 0, 200),
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
    return '/enviar?' . http_build_query($q);
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
    $out = ($medio && !$yt) ? '/ir-fuente?i=' . (int)$i['item_id'] : $i['url'];   // medios y substacks salen por /ir-fuente: cuenta la salida y añade utm_source=deltha (13/10/2026); $i['url'] sigue siendo la clave de $sent y de enviar
    global $pdo;
    // destacar (migra-111) solo existe para los envios: una noticia RSS se envia al radar y se destaca alli (decidido el 04/10/2026)
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
    if ($isThumb) echo "<a class=\"thumb\" href=\"" . h($out) . "\" $rel style=\"background-image:url('/media/" . h($i['image']) . "')\"></a>";
    $foto = ($i['image'] && !$isThumb) ? "<div class=\"media" . ($mode === 'full' ? '' : ' sm') . ($yt ? ' yt' : '') . "\"><a class=\"pic\" href=\"" . h($out) . "\" $rel" . ($yt ? youtube_attr($i['url']) : '') . " style=\"background-image:url('/media/" . h($i['image']) . "')\"></a></div>" : '';
    if (foco()) echo $foto;   // sin distracciones: la foto, antes del titular, flota a su derecha
    echo "<h2><a href=\"" . h($out) . "\" $rel>" . h(sin_emojis($i['title']) ?: $i['title']) . "</a></h2>";   // tambien lo ya guardado con emojis
    if ($i['content']) echo "<p class=\"lead\">" . h(mb_strimwidth($i['content'], 0, $mode === 'full' ? 240 : 160, '…')) . "</p>";
    if (!foco()) echo $foto;
    echo "<div class=\"actions\">";
    if ($medio) {
        if (isset($sent[$i['url']])) echo "<a class=\"sent\" href=\"/hilo/" . (int)$sent[$i['url']] . "\" title=\"" . h(t('ya está en Deltha: ver el hilo')) . "\">" . icon('reply') . "<span>" . t('en Deltha') . "</span></a>";
        elseif (user() || cfg('anon_can_submit') === '1') echo "<a class=\"send\" href=\"" . h(fuente_enviar_url($src, $i)) . "\" title=\"" . h(t('enviar esta noticia al radar')) . "\">" . icon('plus') . "<span>" . t('enviar al radar') . "</span></a>";
        echo "<a class=\"share ext\" href=\"" . h($out) . "\" $rel>" . icon('link') . "<span>" . h($yt ? t('ver en %s', 'YouTube') : t('leer en %s', $src['name'])) . "</span></a>";
        echo fuente_fav_form($src);   // «+ Seguir»: el medio o substack de esta noticia, a «Mis medios» / «Mis substacks»
    } else {
        echo "<a href=\"" . h($i['thread_url']) . "\" rel=\"noopener\" title=\"" . h(t('comentarios en %s', $src['name'])) . "\">" . icon('reply') . "<span>" . (int)$i['comments'] . "</span></a>";
        if (!fuente_sin_votos($src)) echo "<span class=\"vote\"><span>" . icon('up') . "<span>" . (int)$i['votes'] . "</span></span><span>" . icon('down') . "<span>" . (int)$i['negatives'] . "</span></span></span>";
        echo "<a class=\"share ext\" href=\"" . h($i['thread_url']) . "\" rel=\"noopener\">" . icon('link') . "<span>" . h(t('ver en %s', $src['name'])) . "</span></a>";
    }
    echo "</div></div></article>";
}
