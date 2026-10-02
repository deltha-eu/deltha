<?php
/** /mis-fuentes.php?k=medio|newsletter — pestañas «Mis medios» y «Mis substacks» del perfil: todos los favoritos del usuario en ese grupo
 *  (sin limite), el que ha publicado mas recientemente primero, cada uno con su boton «Siguiendo» para dejar de seguirlo. Privado, solo el dueño.
 *  En el menu lateral salen los tres primeros de esta lista (medios_menu()).
 *  Boton «+ Añadir medio / substack» (?add=1): despliega aqui mismo los del catalogo que aun no sigue, cada uno con su «Seguir»; al pulsarlo
 *  se vuelve a esta pagina con la lista aun abierta (fav-fuente.php vuelve al Referer), asi se añaden varios seguidos. Sin JS. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';
require __DIR__ . '/../lib/propuestas.php';
$u = user();
if (!$u) redirect('/login.php');
$k = (string)($_GET['k'] ?? 'medio');
if (!isset(DDD_MEDIA_KINDS[$k]) || ($k === 'agregador' && !agregadores_on())) $k = 'medio';   // agregadores apagados: no hay pestaña
$label = t(DDD_MEDIA_KINDS[$k][1]);

header('Cache-Control: private, no-cache');
page_header($label, "fuentes:$k");
echo "<div class=\"profile\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<div><h1>" . h($u['login']) . "</h1>";
if ($u['bio']) echo "<p class=\"bio\">" . h($u['bio']) . "</p>";
echo "<p class=\"meta\">" . t('aura') . " " . number_format((float)$u['aura'], 1, ',', '') . " · <a href=\"/u.php?u=" . rawurlencode($u['login']) . "\">" . t('ver perfil público') . "</a> · <a href=\"/salir.php\">" . t('salir') . "</a></p></div></div>";
profile_tabs("fuentes:$k");

$lista = fuentes_on() ? mis_medios($pdo, $u, $k) : [];   // todos, tambien los de otro idioma: aqui se gestionan
$ult = fuentes_ultima_noticia($pdo);
usort($lista, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
$cat = "/medios.php?k=$k";
if ($lista) {
    echo "<p class=\"notice\">" . t('Los que sigues en este grupo, sin límite. En el menú salen los %d que han publicado más recientemente; sigue o deja de seguir aquí, en el <a href="%s">catálogo</a> o en cada noticia.', medios_max($k), $cat)
       . " <a href=\"/ultimo.php?k=$k\">" . h(t('lo último en %s', $label)) . "</a>.</p>";
    foreach ($lista as $m) {
        $descr = h((string)$m['descr'] ?: source_of($m['home']));
        if (($m['lang'] ?? '') !== '' && $m['lang'] !== lang()) $descr = "<b>" . h(strtoupper($m['lang'])) . "</b> · " . $descr;
        echo "<div class=\"fz medio\"><a class=\"fzl\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . $descr
           . (isset($ult[$m['source_id']]) ? " · " . h(hace($ult[$m['source_id']])) : '') . "</span></span></a>" . fuente_fav_form($m) . "</div>";
    }
} else {
    echo "<p class=\"end\">" . t('Todavía no sigues ninguno aquí. Pulsa «Seguir» en el <a href="%s">catálogo</a> o en cada noticia.', $cat) . "</p>";
}
// «+ Añadir medio / substack»: los del catalogo que aun no sigue (respeta «solo en mi idioma»), el que ha publicado mas recientemente primero
if (fuentes_on()) {
    $add = !empty($_GET['add']);
    $txt = $k === 'newsletter' ? t('Añadir substack') : ($k === 'agregador' ? t('Añadir agregador') : t('Añadir medio'));
    echo "<p class=\"fz-add" . ($add ? ' on' : '') . "\" id=\"anadir\"><a href=\"/mis-fuentes.php?k=$k" . ($add ? '' : '&amp;add=1#anadir') . "\">" . h($add ? t('Cerrar') : $txt) . "</a></p>";
    if ($add) {
        $mias = array_column($lista, 'source_id');
        $resto = array_values(array_filter(fuentes_visibles($pdo, $k), fn($m) => !in_array($m['source_id'], $mias)));
        usort($resto, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
        echo "<h3 class=\"sec\">" . h($txt) . "</h3>";
        if (!$resto) echo "<p class=\"end\">" . t('Ya sigues todos los del catálogo.') . "</p>";
        foreach ($resto as $m) {
            $descr = h((string)$m['descr'] ?: source_of($m['home']));
            if (($m['lang'] ?? '') !== '' && $m['lang'] !== lang()) $descr = "<b>" . h(strtoupper($m['lang'])) . "</b> · " . $descr;
            echo "<div class=\"fz medio\"><a class=\"fzl\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . $descr
               . (isset($ult[$m['source_id']]) ? " · " . h(hace($ult[$m['source_id']])) : '') . "</span></span></a>" . fuente_fav_form($m) . "</div>";
        }
        if ($k !== 'agregador' && fprop_puede($u)) echo "<p class=\"fprop-cta\">" . t('¿Falta alguno? <a href="%s">Propón un medio o un substack</a>.', "/proponer-fuente.php?k=$k") . "</p>";
    }
}
page_footer();
