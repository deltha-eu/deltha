<?php
/** lib/lateral.php — las dos columnas de escritorio (>= 1100 px): a la izquierda la navegacion en vertical
 *  (las dos barras de pestañas, «Publicar», el perfil y el pie con los enlaces del sitio); a la derecha, lo que esta pasando, igual para todo el mundo
 *  y sin nada «para ti»: el proximo ciclo con el radar, las comunidades con actividad y el pie.
 *  En movil las dos van ocultas por CSS (display: none) y todo sigue como estaba. Sin JS. */
declare(strict_types=1);
require_once __DIR__ . '/cola.php';
require_once __DIR__ . '/render.php';   // story_query_base(), avatar()

/** Columna izquierda. */
function rail(string $tab): void {
    $u = user();
    echo "<aside class=\"rail\">";
    echo "<a class=\"logo\" href=\"/\" aria-label=\"" . h(cfg('site_name')) . "\"><img src=\"" . logo_src('light') . "\" alt=\"\" class=\"l\"><img src=\"" . logo_src('dark') . "\" alt=\"\" class=\"d\"></a>";
    nav_tabs($tab, 'rail-top');
    nav_tabs($tab, 'rail-bottom');
    rail_skins();
    rail_medios();
    echo "<a class=\"pub\" href=\"/enviar.php\">" . icon('plus') . "<span>" . t('Publicar') . "</span></a>";
    if ($u) echo avisos_bell('rail-bell') . "<a class=\"me\" href=\"/perfil.php\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<span><b>" . h($u['login']) . "</b><small>" . t('aura') . " " . number_format((float)$u['aura'], 1, ',', '') . "</small></span></a>";
    else    echo "<a class=\"me\" href=\"/login.php\">" . icon('user') . "<span><b>" . t('entrar') . "</b><small>" . t('o crear una cuenta') . "</small></span></a>";
    // pie (¿Que es Deltha?, manifiesto, privacidad, normas...) al final del rail: asi las dos columnas laterales terminan igual
    echo "<footer class=\"sidefoot\"><a class=\"ctr\" href=\"https://contralgoritmia.com/\" rel=\"noopener\" title=\"" . t('Manifiesto contralgorítmico') . "\"><img src=\"/img/contralgoritmia.png\" alt=\"" . t('Sello contralgorítmico') . "\"></a><p>" . footer_links() . "</p></footer>";
    echo "</aside>";
}

/** Tercer grupo del rail: plantilla, aspecto (claro/oscuro) e idioma, cada uno en un desplegable <details> (sin JS), tambien sin cuenta. */
function rail_dd(string $icon, string $label, string $current, array $items, string $cls = ''): void {
    echo "<details class=\"rail-dd $cls\"><summary>" . icon($icon) . "<span>" . h($label) . "<small>" . h($current) . "</small></span></summary><div class=\"list\">";
    foreach ($items as $it) { [$href, $txt, $on, $sw] = $it; $extra = $it[4] ?? '';
        echo "<a href=\"" . h($href) . "\"" . (($on || $extra) ? ' class="' . trim(($on ? 'on ' : '') . $extra) . '"' : '') . ">" . ($sw ? "<i class=\"sw sw-$sw\"></i>" : '') . h($txt) . "</a>"; }
    echo "</div></details>";
}
function rail_skins(): void {
    $u = user();
    // plantilla
    $cur = skin();
    $items = [];
    foreach (DDD_SKINS as $k => $v) $items[] = ["/cambiar-skin.php?s=$k", $v, $k === $cur, $k];
    $saved = $u ? (string)($u['skin'] ?? '') : (string)($_COOKIE[DDD_SKIN_COOKIE] ?? '');
    if ($saved !== '') $items[] = ["/cambiar-skin.php?s=", t('la del sitio'), false, null, 'reset'];
    rail_dd('skin', t('Skin'), DDD_SKINS[$cur] ?? $cur, $items, 'skins');
    // aspecto: claro / oscuro / como el sistema
    $th = theme() ?? 'auto';
    $opts = ['auto' => t('Como el sistema'), 'light' => t('Claro'), 'dark' => t('Oscuro')];
    $items = [];
    foreach ($opts as $k => $v) $items[] = ["/cambiar-skin.php?t=$k", $v, $k === $th, null];
    rail_dd('theme', t('Aspecto'), $opts[$th], $items);
    // idioma
    $cl = lang();
    $items = [];
    foreach (DDD_LANGS as $k => $v) $items[] = ["/cambiar-skin.php?l=$k", $v, $k === $cl, null];
    rail_dd('lang', t('Idioma'), DDD_LANGS[$cl] ?? $cl, $items);
}

/** Cuarto grupo del rail: «Mis substacks», «Mis medios» y «Mis agregadores» como cabeceras (mismo aspecto que Plantilla / Aspecto / Idioma,
 *  sin flecha ni plegado), cada una pulsable → /ultimo.php?k=… (las diez ultimas noticias del grupo en formato normal), y debajo, siempre
 *  visibles, solo los nombres: los elegidos en el perfil o, sin eleccion / sin cuenta, los que han publicado mas recientemente (agregadores: los destacados en admin). */
function rail_medios(): void {
    global $pdo; require_once __DIR__ . '/fuentes.php';
    if (!fuentes_on()) return;
    $u = user();
    foreach (DDD_ULTIMO_KINDS as $kind => [$label, $ic]) {
        if (!($list = ultimo_fuentes($pdo, $u, $kind))) continue;
        $mine = $kind !== 'agregador' && mis_medios($pdo, $u, $kind);
        $on = current_tab() === "ultimo:$kind" ? ' class="on"' : '';
        echo "<details class=\"rail-dd medios\" open><summary><a href=\"/ultimo.php?k=$kind\"$on title=\"" . h(t('lo último en %s', t($label))) . "\">" . icon($ic) . "<span>" . t($label) . "<small>" . ($kind === 'agregador' ? t('%d fuentes', count($list)) : ($mine ? t('%d elegidos', count($list)) : t('recientes'))) . "</small></span></a></summary><div class=\"list\">";
        foreach ($list as $m) echo "<a href=\"" . h(fuente_url($m)) . "\"" . (current_tab() === 'fuente:' . $m['slug'] ? ' class="on"' : '') . ">" . h($m['name']) . "</a>";
        echo "</div></details>";
    }
}

/** Columna derecha. */
/** Modulos de la columna derecha: clave => [nombre para admin, funcion]. El orden y cuales se ven los fija
 *  config.side_order (claves separadas por comas; lo que no este, no se pinta) desde admin -> Lateral. */
function side_modules(): array {
    return ['ciclo'       => [t('Próximo ciclo'), 'side_ciclo'],
            'temas'       => [t('Temas'), 'side_temas'],
            'comunidades' => [t('En comunidades'), 'side_comunidades'],
            'votadas'     => [t('Las más votadas'), 'side_votadas'],
            'agregadores' => [t('Último en agregadores'), 'side_agregadores'],
            'medios'      => [t('Último en medios'), 'side_medios'],
            'substacks'   => [t('Último en substacks'), 'side_substacks']];
}
const DDD_SIDE_DEFAULT = 'ciclo,temas,comunidades,votadas,agregadores,medios,substacks';
/** Orden en vigor (solo claves existentes). */
function side_order(): array {
    $mods = side_modules(); $out = [];
    foreach (explode(',', (string)cfg('side_order', DDD_SIDE_DEFAULT)) as $k) { $k = trim($k); if (isset($mods[$k]) && !in_array($k, $out, true)) $out[] = $k; }
    return $out;
}
function side(): void {
    global $pdo;
    $mods = side_modules();
    echo "<aside class=\"side\">";
    foreach (side_order() as $k) { try { ($mods[$k][1])($pdo); } catch (Throwable $e) {} }
    echo "</aside>";
}

/** Proximo ciclo (y a que portada le toca) + las primeras del radar con estimacion (mini fisgona). */
function side_ciclo(PDO $pdo): void {
    $prox = proximo_ciclo();
    $next = max(1, (int)ceil(($prox - time()) / 60));
    $st = $pdo->query(str_replace('SELECT s.*,', 'SELECT s.*, ' . delta_sql() . ' AS delta_live,', story_query_base()) . " WHERE s.status='nuevas' AND s.content_type='link' ORDER BY delta_live DESC, s.sent_date ASC LIMIT 60");
    $rows = $st->fetchAll();
    $total = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE status='nuevas' AND content_type='link'")->fetchColumn();
    $eta = cola_eta($rows);
    echo "<section class=\"mod ciclo\"><h3>" . t('Próximo ciclo') . "<span>" . h(t(DDD_PORTADAS[portada_turno($prox)])) . " · " . t('en %d min', $next) . "</span></h3>";
    echo "<p class=\"meta\">" . t('%d en el radar · cada ciclo, una portada', $total) . "</p>";
    $n = 0;
    foreach ($rows as $s) {
        if (!isset($eta[(int)$s['story_id']])) continue;   // solo las que llegan al δ minimo
        $sid = (int)$s['story_id'];
        echo "<a class=\"row\" href=\"/hilo.php?id=$sid\"><b>δ " . number_format((float)$s['delta_live'], 1, ',', '') . "</b><span class=\"t\">" . h(mb_strimwidth($s['title'], 0, 70, '…')) . "</span><small>" . h(eta_txt($eta[$sid], portada_de($pdo, $s))) . "</small></a>";
        if (++$n >= 3) break;
    }
    if ($n === 0) echo "<p class=\"meta\">" . t('Nada llega aún al δ mínimo.') . "</p>";
    echo "<p class=\"more\"><a href=\"/procesos.php\">" . t('ver el radar entero') . "</a></p></section>";
}

/** Temas (las categorias que pone Deltha): pastillas con el color del tema y los envios de la ultima semana; el coeficiente, si no es 1. */
function side_temas(PDO $pdo): void {
    $rows = $pdo->query("SELECT t.topic_id, t.name, t.color, t.coef,
                                (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id AND s.status IN ('nuevas','destacadas','archived') AND s.sent_date >= NOW() - INTERVAL 7 DAY) n
                           FROM topics t ORDER BY n DESC, t.name")->fetchAll();
    if (!$rows) return;
    echo "<section class=\"mod temas\"><h3>" . t('Temas') . "<span>" . t('esta semana') . "</span></h3><div class=\"chips\">";
    foreach ($rows as $r) {
        $coef = (float)$r['coef'];
        echo "<a class=\"chip\" href=\"/tema.php?t=" . (int)$r['topic_id'] . "\" style=\"--cc:" . h($r['color']) . "\" title=\"" . ($coef != 1.0 ? h(t('coeficiente %s', number_format($coef, 2, ',', ''))) : '') . "\">" . h($r['name']) . "<b>" . (int)$r['n'] . "</b>" . ($coef != 1.0 ? "<small>×" . number_format($coef, 2, ',', '') . "</small>" : '') . "</a>";
    }
    echo "</div></section>";
}

/** Las mas votadas de las ultimas horas (votos publicos; los clics no se cuentan porque no hay rastreo). */
function side_votadas(PDO $pdo): void {
    $h = (int)cfg('thread_ttl_hours', 48);
    $st = $pdo->prepare("SELECT s.story_id, s.title, s.votes_pos, s.votes_neg, s.comments, s.status
                           FROM stories s WHERE s.status IN ('nuevas','destacadas','archived') AND s.sent_date >= NOW() - INTERVAL ? HOUR AND s.votes_pos > 0
                          ORDER BY s.votes_pos DESC, s.comments DESC, s.sent_date DESC LIMIT 6");
    $st->execute([$h]);
    $rows = $st->fetchAll();
    if (!$rows) return;
    echo "<section class=\"mod votadas\"><h3>" . t('Las más votadas') . "<span>" . t('%d h', $h) . "</span></h3>";
    $i = 0;
    foreach ($rows as $r) {
        $i++;
        echo "<a class=\"row\" href=\"/hilo.php?id=" . (int)$r['story_id'] . "\"><b><i>$i</i>" . icon('up') . (int)$r['votes_pos'] . "</b><span class=\"t\">" . h(mb_strimwidth($r['title'], 0, 80, '…')) . "</span></a>";
    }
    echo "</section>";
}

/** Comunidades con actividad: la ultima noticia de cada una, mas reciente primero. Dos enlaces por fila: el nombre de la
 *  comunidad lleva a la comunidad (todas sus noticias) y el titular, al hilo de esa noticia. */
function side_comunidades(PDO $pdo): void {
    $rows = $pdo->query("SELECT c.name, c.slug, c.color, s.story_id, s.title, s.comments, s.sent_date
                           FROM communities c
                           JOIN stories s ON s.story_id = (SELECT sc.story_id FROM story_communities sc JOIN stories x ON x.story_id = sc.story_id WHERE sc.community_id = c.community_id AND x.status IN ('nuevas','destacadas','archived') ORDER BY sc.created DESC LIMIT 1)
                          WHERE c.status = 'ok' ORDER BY s.sent_date DESC LIMIT 8")->fetchAll();
    if (!$rows) return;
    // «todas» en la cabecera, como en los modulos de substacks y medios
    echo "<section class=\"mod comm\"><h3>" . t('En comunidades') . "<span><a href=\"/comunidades.php\">" . t('todas') . "</a></span></h3>";
    foreach ($rows as $r) {
        echo "<div class=\"row\" style=\"--cc:" . h($r['color']) . "\"><i class=\"n\">" . (int)$r['comments'] . "</i><span class=\"t\"><a class=\"cm\" href=\"/comunidad.php?c=" . rawurlencode((string)$r['slug']) . "\"><b>" . h($r['name']) . "</b></a> <a href=\"/hilo.php?id=" . (int)$r['story_id'] . "\">" . h(mb_strimwidth($r['title'], 0, 80, '…')) . "</a></span></div>";
    }
    echo "</section>";
}

/** Fuentes externas en la columna derecha: un modulo por grupo («Ultimo en agregadores», «Ultimo en medios», «Ultimo en substacks»)
 *  con las cinco ultimas noticias de portada de TODO el catalogo del grupo (no solo las elegidas por el usuario), cada una con su
 *  fuente delante; el enlace lleva a la pagina de la fuente en Deltha y «todos», al catalogo del grupo. */
function side_agregadores(PDO $pdo): void { side_ultimo($pdo, 'agregador', t('Último en agregadores')); }
function side_medios(PDO $pdo): void { side_ultimo($pdo, 'medio', t('Último en medios')); }
function side_substacks(PDO $pdo): void { side_ultimo($pdo, 'newsletter', t('Último en substacks')); }
function side_ultimo(PDO $pdo, string $kind, string $title): void {
    require_once __DIR__ . '/fuentes.php';
    if (!fuentes_on()) return;
    foreach ([$kind => $title] as $kind => $title) {
        $mylang = fuentes_solo_mi_idioma();   // «solo fuentes en mi idioma»: fuera las de otro idioma (las de idioma desconocido se ven)
        $st = $pdo->prepare("SELECT i.title, i.url, s.name, s.slug FROM source_items i JOIN sources s ON s.source_id = i.source_id
                              WHERE s.kind = ? AND s.status = 'ok' AND i.phase = 'portada'" . ($mylang ? " AND s.lang IN ('', ?)" : "") . " ORDER BY i.pub_date DESC LIMIT 5");
        $st->execute($mylang ? [$kind, lang()] : [$kind]);
        if (!($rows = $st->fetchAll())) continue;
        // «todos» lleva al catalogo filtrado por este grupo (/medios.php?k=medio|newsletter)
        echo "<section class=\"mod fuente medio\"><h3>" . h($title) . "<span><a href=\"/medios.php?k=" . h($kind) . "\">" . t('todos') . "</a></span></h3>";
        // el enlace lleva a la pagina del medio en Deltha (/fuente.php?f=slug), no a la noticia
        foreach ($rows as $r) echo "<a class=\"row\" href=\"" . h(fuente_url(['slug' => $r['slug']])) . "\"><span class=\"t\"><b>" . h($r['name']) . "</b> " . h(mb_strimwidth($r['title'], 0, 70, '…')) . "</span></a>";
        echo "</section>";
    }
}
