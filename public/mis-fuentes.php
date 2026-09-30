<?php
/** /mis-fuentes.php?k=medio|newsletter — pestañas «Mis medios» y «Mis substacks» del perfil: todos los favoritos del usuario en ese grupo
 *  (sin limite), el que ha publicado mas recientemente primero, cada uno con su estrella para quitarlo. Privado, solo el dueño.
 *  En el menu lateral salen los tres primeros de esta lista (medios_menu()). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';
$u = user();
if (!$u) redirect('/login.php');
$k = (string)($_GET['k'] ?? 'medio');
if (!isset(DDD_MEDIA_KINDS[$k])) $k = 'medio';
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
    echo "<p class=\"notice\">" . t('Tus favoritos de este grupo, sin límite. En el menú salen los %d que han publicado más recientemente; añade o quita con la estrella, aquí, en el <a href="%s">catálogo</a> o en cada noticia.', medios_max($k), $cat)
       . " <a href=\"/ultimo.php?k=$k\">" . h(t('lo último en %s', $label)) . "</a>.</p>";
    foreach ($lista as $m) {
        $descr = h((string)$m['descr'] ?: source_of($m['home']));
        if (($m['lang'] ?? '') !== '' && $m['lang'] !== lang()) $descr = "<b>" . h(strtoupper($m['lang'])) . "</b> · " . $descr;
        echo "<div class=\"fz medio\"><a class=\"fzl\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . $descr
           . (isset($ult[$m['source_id']]) ? " · " . h(hace($ult[$m['source_id']])) : '') . "</span></span></a>" . fuente_fav_form($m) . "</div>";
    }
} else {
    echo "<p class=\"end\">" . t('Todavía no tienes favoritos aquí. Márcalos con la estrella en el <a href="%s">catálogo</a> o en cada noticia.', $cat) . "</p>";
}
page_footer();
