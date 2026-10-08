<?php
/** /plantilla — pestaña «Plantilla»: elegir la plantilla visual (y el aspecto claro/oscuro) con una lista de casillas,
 *  tambien sin cuenta. Con cuenta se guarda en users.skin / users.theme; sin cuenta, en las cookies ddd_skin / ddd_theme,
 *  que solo existen porque la persona las pide (save_skin_pref / save_theme_pref en lib/app.php). Es la misma logica que
 *  el desplegable del rail de escritorio (/cambiar-skin); esta pagina es la via en movil, donde el rail no existe. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
$u = user();
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    save_skin_pref(post('skin'));
    save_theme_pref(post('theme', 'auto'));
    header('Cache-Control: no-store');
    redirect('/');   // a la portada, para ver la skin puesta (antes volvia a esta pantalla con «Skin guardada.»)
}
if (isset($_GET['ok'])) $ok = t('Skin guardada.');

$saved = $u ? (string)($u['skin'] ?? '') : (string)($_COOKIE[DDD_SKIN_COOKIE] ?? '');   // la elegida ('' = la del sitio)
if (!isset(DDD_SKINS[$saved])) $saved = '';
$th = $u ? (string)($u['theme'] ?? 'auto') : (string)($_COOKIE['ddd_theme'] ?? 'auto');
if (!in_array($th, ['light', 'dark'], true)) $th = 'auto';
$descs = skin_descs();

page_header(t('Skin'), 'plantilla');
echo "<p class=\"notice\">" . t('Misma portada, mismos números, distinta piel. Elige una y pulsa «Aplicar»; el enlace «ver» la enseña en la portada antes de decidir.') . ($u ? "" : " " . t('Sin cuenta se recuerda con una cookie en este navegador, solo porque tú la pides.')) . "</p>";
if ($ok) echo "<p class=\"notice\">" . h($ok) . "</p>";
echo "<form class=\"plain skinform\" method=\"post\">";
echo "<div class=\"skinlist\">";
foreach ($descs as $k => $d) {
    if ($k !== '' && !isset(DDD_SKINS[$k])) continue;
    [$name, $desc] = $k === '' ? [t('La del sitio'), t('Ahora, %s; cambia si el sitio cambia', DDD_SKINS[cfg('skin_default', 'editorial')])] : [DDD_SKINS[$k], array_pad(explode(' — ', $d, 2), 2, '')[1]];
    echo "<div class=\"sk\"><label><input type=\"radio\" name=\"skin\" value=\"$k\"" . ($saved === $k ? ' checked' : '') . ">";
    echo "<i class=\"sw sw-" . ($k === '' ? cfg('skin_default', 'editorial') : $k) . "\"></i>";
    echo "<span><b>" . h($name) . "</b><small>" . h($desc) . "</small></span></label>";
    echo "<a class=\"ver\" href=\"/?skin=" . ($k === '' ? cfg('skin_default', 'editorial') : $k) . "\">" . t('ver') . "</a></div>";
}
echo "</div>";
echo "<label>" . t('Aspecto') . "</label><div class=\"skinlist seg\">";
foreach (['auto' => t('Como el sistema'), 'light' => t('Claro'), 'dark' => t('Oscuro')] as $k => $v) {
    echo "<div class=\"sk\"><label><input type=\"radio\" name=\"theme\" value=\"$k\"" . ($th === $k ? ' checked' : '') . "><span><b>" . h($v) . "</b></span></label></div>";
}
echo "</div><small class=\"hint\">" . t('El aspecto claro u oscuro es independiente de la skin: se aplica a todas.') . "</small>";
echo "<button>" . t('Aplicar') . "</button></form>";
page_footer();
