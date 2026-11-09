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
    // (21/10/2026, pedido por Angel) perfil y avisos arriba, bajo el logo: una fila con la cuenta (avatar, nombre, aura) y la campana; antes iban al final
    echo "<div class=\"who\">" . ($u ? "<a class=\"me\" href=\"/perfil\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<span><b>" . h($u['login']) . "</b><small>" . t('aura') . " " . number_format((float)$u['aura'], 1, ',', '') . "</small></span></a>" . avisos_bell('rail-bell') : "<a class=\"me\" href=\"/login\">" . icon('user') . "<span><b>" . t('entrar') . "</b><small>" . t('o crear una cuenta') . "</small></span></a>") . "</div>";
    if (buscar_on()) echo "<form class=\"rail-search\" action=\"/buscar\" method=\"get\" role=\"search\"><input type=\"search\" name=\"q\" placeholder=\"" . t('Buscar') . "\" aria-label=\"" . t('Buscar') . "\" maxlength=\"80\"><button type=\"submit\" aria-label=\"" . t('Buscar') . "\">" . icon('search') . "</button></form>";   // buscador (migra-085): solo escritorio; en movil, la lupa de la cabecera
    nav_tabs($tab, 'rail-top');
    echo "<a class=\"pub\" href=\"/enviar\">" . icon('plus') . "<span>" . t('Publicar') . "</span></a>";   // (20/10/2026) bajo las pestañas de contenido, a la vista sin bajar; antes iba al final, tras Mis fuentes
    nav_tabs($tab, 'rail-bottom');
    rail_skins();
    rail_medios();
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
    foreach (DDD_SKINS as $k => $v) $items[] = ["/cambiar-skin?s=$k", $v, $k === $cur, $k];
    $saved = $u ? (string)($u['skin'] ?? '') : (string)($_COOKIE[DDD_SKIN_COOKIE] ?? '');
    if ($saved !== '') $items[] = ["/cambiar-skin?s=", t('la del sitio'), false, null, 'reset'];
    rail_dd('skin', t('Skin'), DDD_SKINS[$cur] ?? $cur, $items, 'skins');
    // aspecto: claro / oscuro / como el sistema
    $th = theme() ?? 'auto';
    $opts = ['auto' => t('Como el sistema'), 'light' => t('Claro'), 'dark' => t('Oscuro')];
    $items = [];
    foreach ($opts as $k => $v) $items[] = ["/cambiar-skin?t=$k", $v, $k === $th, null];
    rail_dd('theme', t('Aspecto'), $opts[$th], $items);
    // sin distracciones (escritorio): sin columna derecha, columna central ancha y fotos a la derecha del titular
    $fz = foco_pref();   // '' = solo en Foros (por defecto; antes «Solo en Originales») | '1' = siempre | '0' = nunca
    $fo = ['' => t('Solo en Foros'), '1' => t('Siempre'), '0' => t('Nunca')];
    $items = [];
    foreach ($fo as $k => $v) $items[] = ["/cambiar-skin?z=$k", $v, $k === $fz, null];
    rail_dd('focus', t('Sin distracciones'), $fo[$fz], $items, 'foco');
    // modo hacker (20/10/2026): solo se ve en pantallas de 2400 px o mas (CSS); cinco columnas, todo a la vista
    $hk = hacker();
    rail_dd('hacker', t('Modo hacker'), $hk ? t('Activo') : t('Apagado'), [["/cambiar-skin?k=1", t('Activo'), $hk, null], ["/cambiar-skin?k=0", t('Apagado'), !$hk, null]], 'hacker');
    // idioma
    $cl = lang();
    $items = [];
    foreach (DDD_LANGS as $k => $v) $items[] = ["/cambiar-skin?l=$k", $v, $k === $cl, null];
    rail_dd('lang', t('Idioma'), DDD_LANGS[$cl] ?? $cl, $items);
}

/** Cuarto grupo del rail (08/10/2026): dos cabeceras con el mismo aspecto que Plantilla / Aspecto / Idioma.
 *   - «Mis fuentes» → /mis-fuentes: debajo, los nombres de las que han publicado mas recientemente entre todas las que sigue (de
 *     cualquier tipo, hasta media_max_per_user) o, si no sigue ninguna / no tiene cuenta, las destacadas en admin; y «todas · N».
 *   - «Todas las fuentes» → /fuentes: debajo, un enlace por tipo con cuantas hay en el catalogo (Medios · Substacks · Canales · Agregadores). */
function rail_medios(): void {
    global $pdo; require_once __DIR__ . '/fuentes.php';
    if (!fuentes_on() || !($tipos = fuentes_tipos($pdo))) return;
    $u = user();
    $ult = fuentes_ultima_noticia($pdo);
    $mias = []; $nm = 0;
    foreach ($tipos as $k => $v) { $l = mis_medios_recientes($pdo, $u, $k); $nm += count($l); $mias = array_merge($mias, $l); }
    if (!$mias) foreach ($tipos as $k => $v) $mias = array_merge($mias, fuentes_destacadas($pdo, $k));
    $dest = !$nm && $mias;
    if (!$mias) foreach ($tipos as $k => $v) if ($k !== 'agregador') $mias = array_merge($mias, medios_menu($pdo, $u, 0, $k));
    usort($mias, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
    $max = medios_max('medio');
    $list = array_slice($mias, 0, $max);
    $tab = current_tab();
    $sub = $nm ? t('%d elegidos', $nm) : ($dest ? t('destacados') : t('recientes'));
    echo "<details class=\"rail-dd medios\" open><summary><a href=\"/mis-fuentes\"" . ($tab === 'misfuentes' ? ' class="on"' : '') . " title=\"" . h(t('lo último de las fuentes que sigues')) . "\">" . icon('news') . "<span>" . t('Mis fuentes') . "<small>" . h($sub) . "</small></span></a></summary><div class=\"list\">";
    foreach ($list as $m) echo "<a href=\"" . h(fuente_url($m)) . "\"" . ($tab === 'fuente:' . $m['slug'] ? ' class="on"' : '') . ">" . h($m['name']) . "</a>";
    if ($nm > count($list)) echo "<a class=\"more\" href=\"/mis-fuentes\">" . t('todos') . " · $nm</a>";
    echo "</div></details>";
    $nt = 0; foreach ($tipos as $k => $v) $nt += count(fuentes_visibles($pdo, $k));
    echo "<details class=\"rail-dd medios todas\" open><summary><a href=\"/fuentes\"" . ($tab === 'fuentes' ? ' class="on"' : '') . " title=\"" . h(t('el catálogo entero, para seguir las que quieras')) . "\">" . icon('agg') . "<span>" . t('Todas las fuentes') . "<small>" . h(t('%d fuentes', $nt)) . "</small></span></a></summary><div class=\"list\">";
    foreach ($tipos as $k => $v) echo "<a href=\"/fuentes?k=$k\">" . h($k === 'youtube' ? 'YouTube' : t(['medio' => 'Medios', 'newsletter' => 'Substacks', 'agregador' => 'Agregadores'][$k])) . " <small>" . count(fuentes_visibles($pdo, $k)) . "</small></a>";
    echo "</div></details>";
}

/** Columna derecha. */
/** Modulos de la columna derecha: clave => [nombre para admin y para /mi-columna, funcion]. El orden y cuales se ven los fija
 *  config.side_order (claves separadas por comas; lo que no este, no se pinta) desde admin -> Lateral; y, desde migra-179, cada
 *  usuario con aura suficiente (side_user_aura) guarda los suyos en users.side_order (/mi-columna). */
function side_modules(): array {
    return ['ciclo'       => [t('Próximo ciclo'), 'side_ciclo'],
            'temas'       => [t('Temas'), 'side_temas'],
            'comunidades' => [t('En comunidades'), 'side_comunidades'],
            'votadas'     => [t('Las más votadas'), 'side_votadas'],
            'usuarios'    => [t('Últimas de usuarios'), 'side_usuarios'],
            'radar'       => [t('Último en el radar'), 'side_radar'],
            'medios_deltha' => [t('Medios en Deltha'), 'side_medios_deltha'],
            'comentarios_foros'    => [t('Últimos comentarios en Foros'), 'side_comentarios_foros'],
            'comentarios_noticias' => [t('Últimos comentarios en noticias'), 'side_comentarios_noticias'],
            'seguidos'    => [t('Medios más seguidos'), 'side_seguidos'],
            'agregadores' => [t('Último en agregadores'), 'side_agregadores'],
            'medios'      => [t('Último en medios'), 'side_medios'],
            'substacks'   => [t('Último en substacks'), 'side_substacks'],
            'youtube'     => [t('Último en YouTube'), 'side_youtube']];
}
/** Que explica cada caja en /mi-columna (clave => frase). */
function side_modules_descr(): array {
    return ['ciclo'       => t('Cuándo es el próximo ciclo y qué envíos del radar llegan al δ mínimo.'),
            'temas'       => t('Los temas de Deltha, con los envíos de esta semana.'),
            'comunidades' => t('La última noticia de cada comunidad con actividad.'),
            'votadas'     => t('Las más votadas de las últimas horas.'),
            'usuarios'    => t('Los últimos envíos hechos por personas (sin agentes), en cualquier fase.'),
            'radar'       => t('Lo último que ha entrado en el radar.'),
            'medios_deltha' => t('Las últimas noticias ya en Deltha cuya fuente es un medio o substack del catálogo.'),
            'comentarios_foros'    => t('Los últimos comentarios en los hilos de los foros.'),
            'comentarios_noticias' => t('Los últimos comentarios en los hilos de noticias.'),
            'seguidos'    => t('Los medios, substacks y canales del catálogo con más gente siguiéndolos.'),
            'agregadores' => t('Lo último publicado en los agregadores del catálogo, todavía fuera de Deltha.'),
            'medios'      => t('Lo último publicado por los medios del catálogo, todavía fuera de Deltha: puedes enviarlo tú.'),
            'substacks'   => t('Lo último publicado en los substacks del catálogo, todavía fuera de Deltha: puedes enviarlo tú.'),
            'youtube'     => t('Los últimos vídeos de los canales del catálogo.')];
}
const DDD_SIDE_DEFAULT = 'ciclo,temas,comunidades,votadas,agregadores,medios,substacks,youtube';
/** Lista de claves validas (sin repetir) a partir de «a,b,c». */
function side_parse(string $csv): array {
    $mods = side_modules(); $out = [];
    foreach (explode(',', $csv) as $k) { $k = trim($k); if (isset($mods[$k]) && !in_array($k, $out, true)) $out[] = $k; }
    return $out;
}
/** Orden de admin (config.side_order; solo claves existentes). */
function side_order(): array { return side_parse((string)cfg('side_order', DDD_SIDE_DEFAULT)); }
/** ¿Puede esta cuenta elegir sus cajas? Aura >= side_user_aura (10); moderadores y admin, siempre; 0 = nadie salvo ellos. */
function side_user_can(?array $u = null): bool {
    $u = $u ?? user();
    if (!$u) return false;
    if (user_is_mod($u)) return true;
    $min = (float)cfg('side_user_aura', 10);
    return $min > 0 && (float)($u['aura'] ?? 0) >= $min;
}
/** Lo elegido por el usuario actual (users.side_order) o null si no ha elegido, no puede o falta la migra-179. */
function side_user_order(): ?array {
    static $c = false; global $pdo;
    if ($c !== false) return $c;
    $c = null; $u = user();
    if ($u && side_user_can($u)) {
        try {
            $st = $pdo->prepare("SELECT side_order FROM users WHERE user_id = ?"); $st->execute([(int)$u['user_id']]);
            $v = $st->fetchColumn();
            if ($v !== null && $v !== false) $c = side_parse((string)$v);   // '' guardado = «ninguna caja», tambien vale
        } catch (Throwable $e) { /* migra-179 sin aplicar */ }
    }
    return $c;
}
/** Tope de cajas pintadas (config.side_max, migra-180; 0 = sin tope). */
function side_max(): int { return max(0, (int)cfg('side_max', 10)); }
/** Orden en vigor para quien mira: el suyo si lo tiene; si no, el de admin. Recortado al tope. */
function side_order_actual(): array {
    $o = side_user_order() ?? side_order();
    return side_max() > 0 ? array_slice($o, 0, side_max()) : $o;
}
function side(): void {
    global $pdo;
    $mods = side_modules();
    // .mods envuelve las cajas (06/11/2026): en modo hacker el multicolumna va en ese div, no en el aside, que sigue siendo el que se
    // pega arriba y hace scroll; si el multicolumna tenia el tope de alto, creaba columnas hacia la derecha y se perdian cajas
    echo "<aside class=\"side\"><div class=\"mods\">";
    foreach (side_order_actual() as $k) { try { ($mods[$k][1])($pdo); } catch (Throwable $e) {} }
    echo "</div></aside>";
}

/** Proximo ciclo (y a que portada le toca) + las primeras del radar con estimacion (mini fisgona). */
function side_ciclo(PDO $pdo): void {
    $prox = proximo_ciclo();
    $st = $pdo->query(str_replace('SELECT s.*,', 'SELECT s.*, ' . delta_sql() . ' AS delta_live,', story_query_base()) . " WHERE s.status='nuevas' AND s.content_type='link' ORDER BY delta_live DESC, s.sent_date ASC LIMIT 60");
    $rows = $st->fetchAll();
    $total = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE status='nuevas' AND content_type='link'")->fetchColumn();
    $eta = cola_eta($rows);
    echo "<section class=\"mod ciclo\"><h3>" . t('Próximo ciclo') . "<span>" . h(t(DDD_PORTADAS[portada_turno($prox)])) . " · " . h(proximo_ciclo_txt()) . "</span></h3>";
    echo "<p class=\"meta\">" . t('%d en el radar · cada ciclo, una portada, a suertes según el δ', $total) . "</p>";
    $n = 0;
    foreach ($rows as $s) {
        if (!isset($eta[(int)$s['story_id']])) continue;   // solo las que llegan al δ minimo
        $sid = (int)$s['story_id'];
        echo "<a class=\"row\" href=\"/hilo/$sid\"><b>δ " . number_format((float)$s['delta_live'], 1, ',', '') . "</b><span class=\"t\">" . h(mb_strimwidth($s['title'], 0, 70, '…')) . "</span><small>" . h(eta_txt($eta[$sid], portada_de($pdo, $s))) . "</small></a>";
        if (++$n >= 3) break;
    }
    if ($n === 0) echo "<p class=\"meta\">" . t('Nada llega aún al δ mínimo.') . "</p>";
    echo "<p class=\"more\"><a href=\"/procesos\">" . t('ver el radar entero') . "</a></p></section>";
}

/** Temas (las categorias que pone Deltha): pastillas con el color del tema y los envios de la ultima semana; el bonus, si lo hay. */
function side_temas(PDO $pdo): void {
    $rows = $pdo->query("SELECT t.topic_id, t.name, t.color, t.bonus,
                                (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id AND s.status IN ('nuevas','destacadas','archived') AND s.sent_date >= NOW() - INTERVAL 7 DAY) n
                           FROM topics t ORDER BY t.name")->fetchAll();
    if (!$rows) return;
    echo "<section class=\"mod temas\"><h3>" . t('Temas') . "<span>" . t('esta semana') . "</span></h3><div class=\"chips\">";
    foreach ($rows as $r) {
        $bonus = (float)$r['bonus'];
        echo "<a class=\"chip\" href=\"/tema/" . (int)$r['topic_id'] . "\" style=\"--cc:" . h($r['color']) . "\" title=\"" . ($bonus > 0 ? h(t('bonus %s en el δ', num_es($bonus))) : '') . "\">" . h($r['name']) . "<b>" . (int)$r['n'] . "</b>" . ($bonus > 0 ? "<small>+" . num_es($bonus) . "</small>" : '') . "</a>";
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
        echo "<a class=\"row\" href=\"/hilo/" . (int)$r['story_id'] . "\"><b><i>$i</i>" . icon('up') . (int)$r['votes_pos'] . "</b><span class=\"t\">" . h(mb_strimwidth($r['title'], 0, 80, '…')) . "</span></a>";
    }
    echo "</section>";
}

/** Comunidades con actividad: la ultima noticia de cada una, mas reciente primero. Dos enlaces por fila: el nombre de la
 *  comunidad lleva a la comunidad (todas sus noticias) y el titular, al hilo de esa noticia. El circulo muestra cuantos
 *  envios tiene la comunidad (no los comentarios del hilo). */
function side_comunidades(PDO $pdo): void {
    $rows = $pdo->query("SELECT c.name, c.slug, c.color, s.story_id, s.title, s.sent_date,
                                (SELECT COUNT(*) FROM story_communities sc2 JOIN stories y ON y.story_id = sc2.story_id
                                  WHERE sc2.community_id = c.community_id AND y.status IN ('nuevas','destacadas','archived')) AS posts
                           FROM communities c
                           JOIN stories s ON s.story_id = (SELECT sc.story_id FROM story_communities sc JOIN stories x ON x.story_id = sc.story_id WHERE sc.community_id = c.community_id AND x.status IN ('nuevas','destacadas','archived') ORDER BY sc.created DESC LIMIT 1)
                          WHERE c.status = 'ok' ORDER BY s.sent_date DESC LIMIT 8")->fetchAll();
    if (!$rows) return;
    // «todas» en la cabecera, como en los modulos de substacks y medios
    echo "<section class=\"mod comm\"><h3>" . t('En comunidades') . "<span><a href=\"/comunidades\">" . t('todas') . "</a></span></h3>";
    foreach ($rows as $r) {
        echo "<div class=\"row\" style=\"--cc:" . h($r['color']) . "\"><i class=\"n\" title=\"" . t('envíos') . "\">" . (int)$r['posts'] . "</i><span class=\"t\"><a class=\"cm\" href=\"/comunidad/" . rawurlencode((string)$r['slug']) . "\"><b>" . h($r['name']) . "</b></a> <a href=\"/hilo/" . (int)$r['story_id'] . "\">" . h(mb_strimwidth($r['title'], 0, 80, '…')) . "</a></span></div>";
    }
    echo "</section>";
}

/* ---- Cajas de lo que ya esta dentro de Deltha (migra-179, 06/11/2026): envios de personas, radar, medios ya enviados y ultimos
 *      comentarios. Todas con lo mismo para todo el mundo (solo el filtro de idioma del perfil); lo personal es cual se ve y donde. ---- */
/** Filas «titular + dato» de envios: un enlace por fila al hilo, con la fecha o la fuente en small. */
function side_rows_envios(string $cls, string $title, array $rows, string $more = '', string $moreTxt = ''): void {
    if (!$rows) return;
    echo "<section class=\"mod lista $cls\"><h3>" . h($title) . ($more !== '' ? "<span><a href=\"" . h($more) . "\">" . h($moreTxt) . "</a></span>" : '') . "</h3>";
    foreach ($rows as $r) {
        echo "<a class=\"row\" href=\"/hilo/" . (int)$r['story_id'] . "\"><span class=\"t\">" . (!empty($r['pre']) ? "<b>" . h($r['pre']) . "</b> " : '') . h(mb_strimwidth((string)$r['title'], 0, 80, '…')) . "</span><small>" . h($r['sub']) . "</small></a>";
    }
    echo "</section>";
}
/** Ultimos envios de personas: cuentas registradas que no son agentes (tabla agents), enlaces y articulos, en cualquier fase visible. */
function side_usuarios(PDO $pdo): void {
    require_once __DIR__ . '/agente.php';
    $noAg = agents_on() ? " AND s.user_id NOT IN (SELECT a.user_id FROM agents a)" : '';
    $rows = $pdo->query("SELECT s.story_id, s.title, s.sent_date, s.status, s.content_type, s.comments, s.user_id, s.author_alias, s.revealed, u.login
                           FROM stories s LEFT JOIN users u ON u.user_id = s.user_id
                          WHERE s.user_id IS NOT NULL AND s.status IN ('nuevas','destacadas','archived')$noAg" . stories_mylang_sql() . foros_cerrados_sql() . ghost_sql_not_in('s.user_id') . "
                          ORDER BY s.sent_date DESC LIMIT 6")->fetchAll();
    foreach ($rows as &$r) {
        // quien lo envio, con la misma regla que las tarjetas (story_post): nombre real pasado el velo Ghost, alias mientras tanto
        $rev = (int)$r['revealed'] === 1 || $r['content_type'] === 'article' || ghost_over($r['sent_date']);
        $r['pre'] = display_name(['login' => $r['login'], 'alias' => $r['author_alias'], 'user_id' => $r['user_id']], $rev);
        $r['sub'] = hace($r['sent_date']) . ((int)$r['comments'] > 0 ? " · " . t('%d comentarios', (int)$r['comments']) : '');
    }
    unset($r);
    side_rows_envios('usuarios', t('Últimas de usuarios'), $rows, '/nuevas', t('todas'));
}
/** Lo ultimo que ha entrado en el radar (status nuevas, enlaces): la cola de la que salen las portadas. */
function side_radar(PDO $pdo): void {
    $rows = $pdo->query("SELECT s.story_id, s.title, s.sent_date, s.source FROM stories s
                          WHERE s.status = 'nuevas' AND s.content_type = 'link'" . stories_mylang_sql() . " ORDER BY s.sent_date DESC LIMIT 6")->fetchAll();
    foreach ($rows as &$r) $r['sub'] = hace($r['sent_date']) . ($r['source'] ? " · " . $r['source'] : '');
    unset($r);
    side_rows_envios('radar', t('Último en el radar'), $rows, '/procesos', t('ver el radar entero'));
}
/** Noticias ya enviadas a Deltha cuya fuente es un medio o substack del catalogo (lo contrario de «Ultimo en medios», que son las
 *  que faltan por enviar). stories.source es el dominio; se casa con el de sources.home (fuente_por_dominio). Una por fuente, hasta 6. */
function side_medios_deltha(PDO $pdo): void {
    require_once __DIR__ . '/fuentes.php';
    if (!fuentes_on()) return;
    $dom = [];
    foreach (['medio', 'newsletter'] as $k) foreach (fuentes_visibles($pdo, $k) as $f) if (($d = (string)source_of((string)$f['home'])) !== '') $dom[$d] = $f['name'];
    if (!$dom) return;
    $st = $pdo->prepare("SELECT s.story_id, s.title, s.sent_date, s.source FROM stories s
                          WHERE s.status IN ('nuevas','destacadas','archived') AND s.content_type = 'link' AND s.source IN (" . implode(',', array_fill(0, count($dom), '?')) . ")"
                          . stories_mylang_sql() . " ORDER BY s.sent_date DESC LIMIT 40");
    $st->execute(array_keys($dom));
    $rows = []; $vistas = [];
    foreach ($st->fetchAll() as $r) {
        if (isset($vistas[$r['source']])) continue;
        $vistas[$r['source']] = true;
        $rows[] = $r + ['pre' => $dom[$r['source']], 'sub' => hace($r['sent_date'])];
        if (count($rows) >= 6) break;
    }
    side_rows_envios('medios-deltha', t('Medios en Deltha'), $rows, '/fuentes', t('todos'));
}
/** Ultimos comentarios (los N ultimos a secas, aunque repitan hilo: decision de Ángel 06/11/2026), revelados (ghost_minutes),
 *  en hilos visibles. $foros = true: hilos de foro (articulos con forum_id); false: hilos de noticias (enlaces). */
function side_comentarios(PDO $pdo, bool $foros, string $cls, string $title): void {
    $gm = (int)cfg('ghost_minutes', 30);
    $w = $foros ? "s.content_type = 'article' AND s.forum_id IS NOT NULL" . foros_cerrados_sql() : "s.content_type = 'link'";
    $rows = $pdo->query("SELECT c.comment_id, c.story_id, c.content, c.created, c.alias, c.user_id, u.login, s.title
                           FROM comments c JOIN stories s ON s.story_id = c.story_id LEFT JOIN users u ON u.user_id = c.user_id
                          WHERE c.status = 'ok' AND c.created <= NOW() - INTERVAL $gm MINUTE AND (c.deleted IS NULL OR c.deleted = 0)
                            AND s.status IN ('nuevas','destacadas','archived') AND $w" . ghost_sql_not_in('c.user_id') . "
                          ORDER BY c.created DESC LIMIT 6")->fetchAll();
    if (!$rows) return;
    echo "<section class=\"mod lista $cls\"><h3>" . h($title) . "</h3>";
    foreach ($rows as $r) {
        $quien = display_name($r, true);
        $txt = trim(preg_replace(['/\s+/u', '/\*\*?([^*]+)\*\*?/u', '/\[([^\]]+)\]\([^)]*\)/u', '/(^|\s)>\s*/u'], [' ', '$1', '$1', '$1'], strip_tags((string)$r['content'])));   // sin las marcas del Markdown minimo
        echo "<a class=\"row\" href=\"/hilo/" . (int)$r['story_id'] . "#c" . (int)$r['comment_id'] . "\"><span class=\"t\"><b>" . h($quien) . "</b> " . h(mb_strimwidth($txt, 0, 90, '…')) . "</span><small>" . h(mb_strimwidth((string)$r['title'], 0, 60, '…')) . " · " . h(hace($r['created'])) . "</small></a>";
    }
    echo "</section>";
}
function side_comentarios_foros(PDO $pdo): void { side_comentarios($pdo, true, 'comentarios foros', t('Últimos comentarios en Foros')); }
function side_comentarios_noticias(PDO $pdo): void { side_comentarios($pdo, false, 'comentarios noticias', t('Últimos comentarios en noticias')); }

/** Medios, substacks y canales con mas seguidores (user_media), hasta 8; solo los visibles (listados y en el idioma del que mira).
 *  Pedido por Ángel el 09/11/2026. */
function side_seguidos(PDO $pdo): void {
    require_once __DIR__ . '/fuentes.php';
    if (!fuentes_on()) return;
    $vis = [];
    foreach (['medio', 'newsletter', 'youtube'] as $k) foreach (fuentes_visibles($pdo, $k) as $f) $vis[(int)$f['source_id']] = $f;
    if (!$vis) return;
    $st = $pdo->prepare("SELECT source_id, COUNT(*) n FROM user_media WHERE source_id IN (" . implode(',', array_fill(0, count($vis), '?')) . ") GROUP BY source_id ORDER BY n DESC, source_id LIMIT 8");
    $st->execute(array_keys($vis));
    $rows = $st->fetchAll();
    if (!$rows) return;
    $kinds = ['medio' => t('medio'), 'newsletter' => t('substack'), 'youtube' => t('canal')];
    echo "<section class=\"mod seguidos\"><h3>" . t('Medios más seguidos') . "<span><a href=\"/fuentes\">" . t('todos') . "</a></span></h3>";
    $i = 0;
    foreach ($rows as $r) {
        $f = $vis[(int)$r['source_id']]; $i++;
        echo "<a class=\"row\" href=\"" . h(fuente_url($f)) . "\"><b><i>$i</i>" . (int)$r['n'] . "</b><span class=\"t\">" . h($f['name']) . "</span><small>" . h($kinds[$f['kind']] ?? $f['kind']) . " · " . h(t('%d siguen', (int)$r['n'])) . "</small></a>";
    }
    echo "</section>";
}

/** Fuentes externas en la columna derecha: un modulo por grupo («Ultimo en agregadores», «Ultimo en medios», «Ultimo en substacks»)
 *  con las cinco ultimas noticias de portada de TODO el catalogo del grupo (no solo las elegidas por el usuario), sin repetir
 *  fuente (la mas reciente de cada una), cada una con su fuente delante; el enlace lleva a la pagina de la fuente en Deltha y «todos», al catalogo del grupo. */
function side_agregadores(PDO $pdo): void { side_ultimo($pdo, 'agregador', t('Último en agregadores')); }
function side_medios(PDO $pdo): void { side_ultimo($pdo, 'medio', t('Último en medios')); }
function side_substacks(PDO $pdo): void { side_ultimo($pdo, 'newsletter', t('Último en substacks')); }
function side_youtube(PDO $pdo): void { side_ultimo($pdo, 'youtube', t('Último en YouTube')); }
function side_ultimo(PDO $pdo, string $kind, string $title): void {
    require_once __DIR__ . '/fuentes.php';
    if (!fuentes_on() || !fuente_kind_visible($kind)) return;   // canales y agregadores: solo desde fuentes_nivel_aura
    foreach ([$kind => $title] as $kind => $title) {
        $idi = fuentes_idiomas();   // idiomas elegidos: fuera las fuentes de otro idioma (las de idioma desconocido se ven)
        // cinco noticias de cinco fuentes distintas: las cinco fuentes que han publicado mas recientemente y la ultima noticia de cada una.
        // (Antes se cogian las 80 noticias mas recientes y se quitaban repetidas: si dos fuentes acababan de entrar con 40 items cada
        // una, el modulo se quedaba en dos filas. 14/10/2026.)
        $st = $pdo->prepare("SELECT s.source_id, s.name, s.slug, MAX(i.pub_date) AS ult FROM source_items i JOIN sources s ON s.source_id = i.source_id
                              WHERE s.kind = ? AND s.status = 'ok' AND s.listed = 1 AND i.phase = 'portada'"
                              . ($idi ? " AND s.lang IN ('', " . implode(',', array_fill(0, count($idi), '?')) . ")" : "") . "
                              GROUP BY s.source_id, s.name, s.slug ORDER BY ult DESC LIMIT 5");
        $st->execute($idi ? array_merge([$kind], $idi) : [$kind]);
        $rows = [];
        $it = $pdo->prepare("SELECT title, url FROM source_items WHERE source_id = ? AND phase = 'portada' ORDER BY pub_date DESC, item_id DESC LIMIT 1");
        foreach ($st->fetchAll() as $r) {
            $it->execute([(int)$r['source_id']]);
            if ($n = $it->fetch()) $rows[] = $r + $n;
        }
        if (!$rows) continue;
        // «todos» lleva al catalogo filtrado por este grupo (/fuentes?k=medio|newsletter)
        echo "<section class=\"mod fuente medio\"><h3>" . h($title) . "<span><a href=\"/fuentes?k=" . h($kind) . "\">" . t('todos') . "</a></span></h3>";
        // el enlace lleva a la pagina del medio en Deltha (/fuente/slug), no a la noticia
        foreach ($rows as $r) echo "<a class=\"row\" href=\"" . h(fuente_url(['slug' => $r['slug']])) . "\"><span class=\"t\"><b>" . h($r['name']) . "</b> " . h(mb_strimwidth(sin_emojis($r['title']) ?: $r['title'], 0, 70, '…')) . "</span></a>";
        echo "</section>";
    }
}
