<?php
/** /mis-fuentes?k=medio|newsletter|youtube|agregador — «Mis fuentes» (08/10/2026): una sola pagina para las fuentes externas que sigue
 *  el usuario, con el selector de tipo arriba (Mis medios · Mis substacks · Mis canales · Mis agregadores; se abre el ultimo que uso,
 *  cookie ddd_fk) y, debajo, lo ultimo que han publicado (antes /ultimo). Entre medias, los nombres de las que sigue y un desplegable
 *  «Seguir y dejar de seguir» con la lista completa, el catalogo de las que aun no sigue y «añadir pegando la direccion» (migra-108).
 *  Sin favoritos en ese tipo (o sin cuenta): las destacadas en admin o, si no hay, las que han publicado mas recientemente, con la
 *  invitacion a elegir. Las noticias son lo publicado en los ultimos config.ultimo_days dias, paginado (si no hay nada, las diez ultimas).
 *  /ultimo?k= redirige aqui. El catalogo entero esta en /fuentes («Todas las fuentes»). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';
require __DIR__ . '/../lib/propuestas.php';

$u = user();
$k = fuentes_tipo_actual($pdo);
header('Cache-Control: private, no-cache');   // depende de cookies (sesion, ddd_srclang, ddd_fk)
if ($k === '') { page_header(t('Mis fuentes'), 'misfuentes'); echo "<p class=\"end\">" . t('Todavía no hay medios en el catálogo.') . "</p>"; page_footer(); exit; }
$label = t(DDD_ULTIMO_KINDS[$k][0]);
$add = !empty($_GET['add']);

$mias = $u ? mis_medios($pdo, $u, $k) : [];      // todas las que sigue de este tipo, tambien en otro idioma (aqui se gestionan)
$fuentes = ultimo_fuentes($pdo, $u, $k);          // de donde salen las noticias: las suyas (en su idioma) o las destacadas / recientes
$porId = array_column($fuentes, null, 'source_id');
$ult = fuentes_ultima_noticia($pdo);
$rows = []; $total = 0;
$DIAS = max(1, (int)cfg('ultimo_days', 7)); $per = (int)cfg('per_page', 20); $page = page_num();
if ($porId) {
    $in = implode(',', array_fill(0, count($porId), '?'));
    $w = "i.source_id IN ($in)" . ($k === 'agregador' ? " AND i.phase = 'portada'" : "");
    $cn = $pdo->prepare("SELECT COUNT(*) FROM source_items i WHERE $w AND i.pub_date >= NOW() - INTERVAL $DIAS DAY");
    $cn->execute(array_keys($porId));
    $total = (int)$cn->fetchColumn();
    // con algo en la ultima semana: todo lo de esa semana, paginado; sin nada (fuentes que publican poco): las diez ultimas
    $st = $pdo->prepare("SELECT i.* FROM source_items i WHERE $w" . ($total ? " AND i.pub_date >= NOW() - INTERVAL $DIAS DAY" : '') . " ORDER BY i.pub_date DESC LIMIT " . ($total ? "$per OFFSET " . page_offset($page) : '10'));
    $st->execute(array_keys($porId));
    $rows = $st->fetchAll();
}
$sent = $k === 'agregador' ? [] : fuente_ya_enviadas($pdo, array_column($rows, 'url'));

page_header(t('Mis fuentes'), 'misfuentes');
echo fuentes_subnav($pdo, '/mis-fuentes', $k, true, "<a class=\"todas\" href=\"/fuentes?k=$k\">" . t('Todas las fuentes') . "</a>");

// --- cabecera del tipo: a quien sigue (o que se le enseña) y el desplegable de gestion
$fila = function (array $m) use ($ult): string {
    $descr = h((string)$m['descr'] ?: source_of($m['home']));
    if (($m['lang'] ?? '') !== '' && $m['lang'] !== lang()) $descr = "<b>" . h(strtoupper($m['lang'])) . "</b> · " . $descr;
    return "<div class=\"fz medio\"><a class=\"fzl\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . $descr
         . (isset($ult[$m['source_id']]) ? " · " . h(hace($ult[$m['source_id']])) : '') . "</span></span></a>" . fuente_fav_form($m) . "</div>";
};
$nombres = implode(', ', array_map(fn($m) => "<a href=\"" . h(fuente_url($m)) . "\">" . h($m['name']) . "</a>", array_slice($fuentes, 0, 8))) . (count($fuentes) > 8 ? '…' : '');
if (!$fuentes) $intro = t('Todavía no hay medios en el catálogo.');
elseif ($mias) $intro = t('Sigues %d: %s.', count($mias), $nombres);
elseif ($u) $intro = t('Todavía no sigues ninguno aquí; te enseñamos %s. Pulsa «Seguir» en los que quieras tener aquí.', $nombres);
else $intro = t('Lo último de %s.', $nombres) . ' ' . t('<a href="/login">Entra</a> para elegir tus medios.');
echo "<p class=\"notice\">$intro</p>";
echo fuentes_casilla_idioma();   // la misma casilla que en el perfil y el catalogo

if ($u && fuentes_on()) {
    $txt = $k === 'newsletter' ? t('Añadir substack') : ($k === 'agregador' ? t('Añadir agregador') : ($k === 'youtube' ? t('Añadir canal') : t('Añadir medio')));
    echo "<details class=\"editbox gestion\" id=\"anadir\"" . ($add ? ' open' : '') . "><summary>" . t('Seguir y dejar de seguir') . "</summary>";
    if ($mias) {
        echo "<h3 class=\"sec\">" . t('Los que sigues') . " · " . count($mias) . "</h3>";
        $lst = $mias; usort($lst, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
        foreach ($lst as $m) echo $fila($m);
    }
    // los del catalogo que aun no sigue (respeta «solo en mi idioma»), el que ha publicado mas recientemente primero
    $ids = array_column($mias, 'source_id');
    $resto = array_values(array_filter(fuentes_visibles($pdo, $k), fn($m) => !in_array($m['source_id'], $ids)));
    usort($resto, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
    echo "<h3 class=\"sec\">" . h($txt) . "</h3>";
    // añadirse una fuente que no esta en el catalogo pegando su direccion (migra-108, /anadir-fuente); arriba, el resultado del ultimo intento
    if ($k !== 'agregador') {
        $rn = h((string)($_GET['n'] ?? ''));
        $R = ['ok'     => t('Añadido: ya sigues %s. Lo leeremos en unos minutos; al catálogo público entrará cuando lo revise un moderador.', "<b>$rn</b>"),
              'ya'     => t('Ya estaba en Deltha: ahora sigues %s.', "<b>$rn</b>"),
              'manual' => t('Ha habido un problema al incorporar esa fuente. Queda apuntada y un moderador la añadirá a mano; te avisará por mensaje.'),
              'pend'   => t('Esa fuente ya está pendiente de que un moderador la añada.'),
              'url'    => t('Esa dirección no es válida.'),
              'bloq'   => t('Ese medio está bloqueado en este sitio (%s): no se admiten sus enlaces.', $rn),
              'noyt'   => t('Eso no es un canal de YouTube.'),
              'tope'   => t('Has propuesto muchas fuentes hoy; vuelve mañana.'),
              'off'    => t('Esa fuente está desactivada en este sitio.')];
        $r = (string)($_GET['r'] ?? '');
        if (isset($R[$r])) echo "<p class=\"" . (in_array($r, ['ok', 'ya', 'manual', 'pend'], true) ? 'notice' : 'error') . "\">" . $R[$r] . "</p>";
        if (fadd_puede($u)) {
            echo "<form class=\"plain\" method=\"post\" action=\"/anadir-fuente\"><input type=\"hidden\" name=\"k\" value=\"$k\">"
               . "<label>" . ($k === 'youtube' ? t('¿No está en el catálogo? Pega la dirección del canal y lo añadimos a los tuyos.') : t('¿No está en el catálogo? Pega la dirección de su web o de su RSS y lo añadimos a los tuyos.')) . "</label>"
               . "<input type=\"url\" name=\"url\" required maxlength=\"200\" placeholder=\"https://…\"><button>" . h($txt) . "</button></form>";
        } else echo "<p class=\"notice\">" . t('Con aura %s o más podrás añadir aquí fuentes que no estén en el catálogo; la tuya es %s.', num_pg(lang(), cfg('source_add_aura', 5)), num_pg(lang(), round((float)$u['aura'], 1))) . "</p>";
    }
    if (!$resto) echo "<p class=\"end\">" . t('Ya sigues todos los del catálogo.') . "</p>";
    foreach ($resto as $m) echo $fila($m);
    if ($k !== 'agregador' && $k !== 'youtube' && fprop_puede($u)) echo "<p class=\"fprop-cta\">" . t('¿Falta alguno? <a href="%s">Propón un medio o un substack</a>.', "/proponer-fuente?k=$k") . "</p>";
    echo "</details>";
}

// --- lo ultimo de esas fuentes
foreach ($rows as $i) fuente_post($porId[$i['source_id']], $i, 'full', $sent);
if ($fuentes && !$rows) echo "<p class=\"end\">" . t('El lector aún no ha pasado por esta fuente.') . "</p>";
if ($total) paginate("/mis-fuentes?k=$k", $page, $total);
page_footer();
