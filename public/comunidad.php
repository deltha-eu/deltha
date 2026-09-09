<?php
/** /comunidad.php?c=slug — todo lo enviado a una comunidad, de más reciente a más antiguo. El creador (y los mods) la editan aquí mismo. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$slug = trim((string)($_GET['c'] ?? post('c')));
$c = community_by_slug($pdo, $slug);
if (!$c || ($c['status'] !== 'ok' && !is_mod())) { http_response_code(404); page_header('Comunidad'); echo "<p class=\"end\">Esa comunidad no existe. <a href=\"/comunidades.php\">Ver todas</a>.</p>"; page_footer(); exit; }
$u = user();
$canEdit = $u && ((int)$u['user_id'] === (int)$c['owner_id'] || is_mod());
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (post('act') === 'ocultar' && is_mod()) {
        $pdo->prepare("UPDATE communities SET status = IF(status = 'ok', 'hidden', 'ok') WHERE community_id = ?")->execute([$c['community_id']]);
        redirect('/comunidad.php?c=' . rawurlencode($slug));
    }
    $name = trim(preg_replace('/\s+/', ' ', post('name'))); $descr = mb_substr(trim(post('descr')), 0, 200); $color = post('color') ?: $c['color'];
    if (mb_strlen($name) < 3 || mb_strlen($name) > 48) $err = 'El nombre debe tener entre 3 y 48 caracteres.';
    elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $err = 'Color no válido.';
    else {
        try { $pdo->prepare("UPDATE communities SET name = ?, descr = ?, color = ? WHERE community_id = ?")->execute([$name, $descr ?: null, $color, $c['community_id']]); redirect('/comunidad.php?c=' . rawurlencode($slug) . '&ok=1'); }
        catch (PDOException $e) { $err = 'Ya existe otra comunidad con ese nombre.'; }
    }
}

$page = page_num();
$cnt = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE community_id = ? AND status IN ('nuevas','candidatas','destacadas','archived')");
$cnt->execute([$c['community_id']]);
$total = (int)$cnt->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.community_id = ? AND s.status IN ('nuevas','candidatas','destacadas','archived') ORDER BY s.sent_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)$c['community_id'], PDO::PARAM_INT);
$st->bindValue(2, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(3, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header($c['name']);
echo "<div class=\"commhead\" style=\"--cc:" . h($c['color']) . "\"><span class=\"dot\"></span><div><h1>" . h($c['name']) . "</h1>";
if ($c['descr']) echo "<p class=\"bio\">" . h($c['descr']) . "</p>";
echo "<p class=\"meta\">comunidad creada por <a href=\"/u.php?u=" . rawurlencode($c['owner']) . "\">" . h($c['owner']) . "</a> · $total " . ($total == 1 ? 'envío' : 'envíos') . " · <a href=\"/comunidades.php\">todas las comunidades</a>";
if ($c['status'] !== 'ok') echo " · <b>oculta</b>";
if ($u && $u['landing'] !== 'c:' . $c['community_id']) echo " · <a href=\"/perfil.php#aterrizar\">entrar aquí al abrir Deltha</a>";
echo "</p></div></div>";
if (isset($_GET['nueva'])) echo "<p class=\"notice\">Comunidad creada. Ya se puede elegir al enviar una noticia; abajo puedes ajustar el nombre, la descripción y el color.</p>";
if (isset($_GET['ok'])) echo "<p class=\"notice\">Guardado.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($canEdit) {
    echo "<details class=\"editbox\"><summary>Editar la comunidad</summary><form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"c\" value=\"" . h($slug) . "\">";
    echo "<label>Nombre</label><input type=\"text\" name=\"name\" maxlength=\"48\" required value=\"" . h($c['name']) . "\">";
    echo "<label>De qué va</label><input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h((string)$c['descr']) . "\">";
    echo "<label>Color</label><input type=\"color\" name=\"color\" value=\"" . h($c['color']) . "\">";
    echo "<button>Guardar</button></form>";
    if (is_mod()) echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"c\" value=\"" . h($slug) . "\"><input type=\"hidden\" name=\"act\" value=\"ocultar\"><button class=\"danger\">" . ($c['status'] === 'ok' ? 'Ocultar comunidad (moderación)' : 'Volver a mostrar') . "</button></form>";
    echo "</details>";
}
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, "compact"); $n++; }
if ($n === 0) echo "<p class=\"end\">Todavía no hay envíos en esta comunidad. <a href=\"/enviar.php\">Envía el primero</a> y elígela en el formulario.</p>";
paginate('/comunidad.php?c=' . rawurlencode($slug), $page, $total);
page_footer();
