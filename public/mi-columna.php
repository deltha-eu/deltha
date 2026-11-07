<?php
/** /mi-columna — pestaña «Mi barra lateral» del perfil (migra-179, 06/11/2026): cada usuario con aura >= side_user_aura elige qué cajas
 *  de la columna derecha (escritorio) ve y en qué orden. Se guarda en users.side_order (claves separadas por comas; NULL = el orden
 *  de admin -> Lateral). Sin JS: casillas, botones ↑/↓ (cada uno guarda) y «Guardar»; con /app/columna.js, además arrastrar y soltar. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/lateral.php';
$u = user();
if (!$u) redirect('/login');
$puede = side_user_can($u);
$mods = side_modules(); $descr = side_modules_descr();
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puede) {
    $act = post('act');
    if ($act === 'restablecer') {
        try { $pdo->prepare("UPDATE users SET side_order = NULL WHERE user_id = ?")->execute([(int)$u['user_id']]); } catch (Throwable $e) {}
        redirect('/mi-columna?ok=2');
    }
    // orden que llega del formulario (w[] en el orden del DOM) + lo que falte, por si el HTML venía incompleto
    $orden = [];
    foreach ((array)($_POST['w'] ?? []) as $k) { $k = (string)$k; if (isset($mods[$k]) && !in_array($k, $orden, true)) $orden[] = $k; }
    foreach ($mods as $k => $m) if (!in_array($k, $orden, true)) $orden[] = $k;
    $on = array_values(array_filter($orden, fn($k) => !empty($_POST['on'][$k])));
    // ↑ / ↓ sin JS: mueve una posición y guarda
    if (preg_match('/^(up|down):([a-z_]+)$/', post('mv'), $m) && isset($mods[$m[2]])) {
        $i = array_search($m[2], $orden, true); $j = $m[1] === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && $j >= 0 && $j < count($orden)) { [$orden[$i], $orden[$j]] = [$orden[$j], $orden[$i]]; }
        $on = array_values(array_filter($orden, fn($k) => !empty($_POST['on'][$k])));
    }
    try { $pdo->prepare("UPDATE users SET side_order = ? WHERE user_id = ?")->execute([implode(',', $on), (int)$u['user_id']]); } catch (Throwable $e) { /* migra-179 sin aplicar */ }
    redirect('/mi-columna?ok=1');
}
if (isset($_GET['ok'])) $ok = $_GET['ok'] === '2' ? t('Vuelves a ver la columna como la tiene Deltha.') : t('Columna guardada.');

page_header(t('Mi barra lateral'));
echo "<div class=\"profile\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<div><h1>" . h($u['login']) . "</h1>";
if ($u['bio']) echo "<p class=\"bio\">" . h($u['bio']) . "</p>";
echo "<p class=\"meta\">" . t('aura') . " " . number_format((float)$u['aura'], 1, ',', '') . " · <a href=\"/u/" . rawurlencode($u['login']) . "\">" . t('ver perfil público') . "</a> · <a href=\"/salir\">" . t('salir') . "</a></p></div></div>";
profile_tabs('micolumna');

$propio = side_user_order();            // null = sin elegir
$admin  = side_order();
$min = (float)cfg('side_user_aura', 10);
echo "<p class=\"notice ayuda\" data-k=\"mi-columna\">" . t('Las cajas de la columna derecha (solo en pantallas anchas) enseñan lo mismo a todo el mundo; aquí eliges cuáles ves y en qué orden. Las que no marques, no salen.') . "</p>";
if (!$puede) {
    echo "<p class=\"notice\">" . h($min > 0 ? t('Podrás elegirlas cuando llegues a %s de aura. Mientras tanto se ven así:', num_es($min)) : t('Por ahora las cajas las elige la redacción. Se ven así:')) . "</p><ol class=\"colsel ro\">";
    foreach ($admin as $k) echo "<li><b>" . h($mods[$k][0]) . "</b><small>" . h($descr[$k] ?? '') . "</small></li>";
    echo "</ol>";
    page_footer(); exit;
}
if ($ok) echo "<p class=\"notice\">" . h($ok) . "</p>";

$sel = $propio ?? $admin;
$max = side_max();
if ($max > 0 && count($sel) > $max) echo "<p class=\"notice\">" . h(t('Has marcado %d cajas y solo se pintan las %d primeras: las demás no salen hasta que las subas o quites otras.', count($sel), $max)) . "</p>";               // lo marcado, en orden; debajo el resto sin marcar
$lista = $sel; foreach ($mods as $k => $m) if (!in_array($k, $lista, true)) $lista[] = $k;
echo "<form class=\"plain colform\" method=\"post\"><input type=\"hidden\" name=\"act\" value=\"guardar\">";
echo "<ol class=\"colsel\" id=\"colsel\">";
$n = count($lista); $i = 0;
foreach ($lista as $k) {
    $i++;
    echo "<li draggable=\"true\" data-k=\"$k\"><input type=\"hidden\" name=\"w[]\" value=\"$k\">"
       . "<span class=\"grip\" aria-hidden=\"true\" title=\"" . h(t('arrastra para ordenar')) . "\">⋮⋮</span>"
       . "<label><input type=\"checkbox\" name=\"on[$k]\" value=\"1\"" . (in_array($k, $sel, true) ? ' checked' : '') . "> <span><b>" . h($mods[$k][0]) . "</b><small>" . h($descr[$k] ?? '') . "</small></span></label>"
       . "<span class=\"mv\"><button name=\"mv\" value=\"up:$k\" title=\"" . h(t('subir')) . "\"" . ($i === 1 ? ' disabled' : '') . ">" . icon('up') . "</button><button name=\"mv\" value=\"down:$k\" title=\"" . h(t('bajar')) . "\"" . ($i === $n ? ' disabled' : '') . ">" . icon('down') . "</button></span></li>";
}
echo "</ol>";
echo "<small class=\"hint\">" . t('Marca las que quieras ver y ordénalas arrastrando (o con las flechas). La columna solo aparece a partir de 1100 px de ancho; en el móvil no cambia nada.') . ($max > 0 ? " " . h(t('Se pintan como mucho %d cajas, las primeras de la lista.', $max)) : '') . "</small>";
echo "<p class=\"colbtns\"><button>" . t('Guardar') . "</button>" . ($propio !== null ? " <button class=\"link\" name=\"act\" value=\"restablecer\">" . t('Volver al orden de Deltha') . "</button>" : '') . "</p></form>";
echo "<script src=\"/app/columna.js?v=" . DDD_CSS_VER . "\" defer></script>";
page_footer();
