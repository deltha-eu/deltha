<?php
/** /comunidad/slug — todo lo que está en una comunidad (enviado a ella o adoptado por su dueño desde el hilo), lo último
 *  añadido primero. El creador (y los mods) la editan aquí mismo. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$slug = trim((string)($_GET['c'] ?? post('c')));
$c = community_by_slug($pdo, $slug);
if (!$c || ($c['status'] !== 'ok' && !is_mod())) { http_response_code(404); page_header(t('Comunidad')); echo "<p class=\"end\">" . t('Esa comunidad no existe. <a href="/comunidades">Ver todas</a>.') . "</p>"; page_footer(); exit; }
$u = user();
$canEdit = $u && ((int)$u['user_id'] === (int)$c['owner_id'] || is_mod());
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (post('act') === 'ocultar' && is_mod()) {
        $pdo->prepare("UPDATE communities SET status = IF(status = 'ok', 'hidden', 'ok') WHERE community_id = ?")->execute([$c['community_id']]);
        redirect('/comunidad/' . rawurlencode($slug));
    }
    $name = trim(preg_replace('/\s+/', ' ', post('name'))); $descr = mb_substr(trim(post('descr')), 0, 200); $color = post('color') ?: $c['color'];
    $skin = isset(DDD_SKINS[post('skin')]) ? post('skin') : '';
    if (mb_strlen($name) < 3 || mb_strlen($name) > 48) $err = t('El nombre debe tener entre 3 y 48 caracteres.');
    elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $err = t('Color no válido.');
    else {
        try { $pdo->prepare("UPDATE communities SET name = ?, descr = ?, color = ?, skin = ? WHERE community_id = ?")->execute([$name, $descr ?: null, $color, $skin, $c['community_id']]); redirect('/comunidad/' . rawurlencode($slug) . '&ok=1'); }
        catch (PDOException $e) { $err = t('Ya existe otra comunidad con ese nombre.'); }
    }
}

$page = page_num();
$ml = stories_mylang_sql();
$cnt = $pdo->prepare("SELECT COUNT(*) FROM story_communities sc JOIN stories s ON s.story_id = sc.story_id WHERE sc.community_id = ? AND s.status IN " . DDD_COMM_PUBLIC . $ml);
$cnt->execute([$c['community_id']]);
$total = (int)$cnt->fetchColumn();
$st = $pdo->prepare(story_query_base("FROM story_communities sc JOIN stories s ON s.story_id = sc.story_id") . " WHERE sc.community_id = ? AND s.status IN " . DDD_COMM_PUBLIC . $ml . " ORDER BY sc.created DESC, s.sent_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)$c['community_id'], PDO::PARAM_INT);
$st->bindValue(2, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(3, page_offset($page), PDO::PARAM_INT);
$st->execute();

if (!empty($c['skin'])) community_skin($c['skin']);   // la comunidad se ve con su plantilla
page_header($c['name']);
echo "<div class=\"commhead\" style=\"--cc:" . h($c['color']) . "\"><span class=\"dot\"></span><div><h1>" . h($c['name']) . "</h1>";
if ($c['descr']) echo "<p class=\"bio\">" . h($c['descr']) . "</p>";
echo "<p class=\"meta\">" . t('comunidad creada por') . " <a href=\"/u/" . rawurlencode($c['owner']) . "\">" . h($c['owner']) . "</a> · " . t('%d envíos', $total) . " · <a href=\"/comunidades\">" . t('todas las comunidades') . "</a>";
if ($c['status'] !== 'ok') echo " · <b>" . t('oculta') . "</b>";
if ($u && $u['landing'] !== 'c:' . $c['community_id']) echo " · <a href=\"/perfil#aterrizar\">" . t('entrar aquí al abrir Deltha') . "</a>";
echo "</p></div></div>";
if (isset($_GET['nueva'])) echo "<p class=\"notice\">" . t('Comunidad creada. Ya se puede elegir al enviar una noticia; abajo puedes ajustar el nombre, la descripción, el color y la skin.') . "</p>";
if (isset($_GET['ok'])) echo "<p class=\"notice\">" . t('Guardado.') . "</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($canEdit) {
    echo "<details class=\"editbox\">" . t('<summary>Editar la comunidad</summary>') . "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"c\" value=\"" . h($slug) . "\">";
    echo "" . t('<label>Nombre</label>') . "<input type=\"text\" name=\"name\" maxlength=\"48\" required value=\"" . h($c['name']) . "\">";
    echo "" . t('<label>De qué va</label>') . "<input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h((string)$c['descr']) . "\">";
    echo "" . t('<label>Color</label>') . "<input type=\"color\" name=\"color\" value=\"" . h($c['color']) . "\">";
    echo skin_select((string)($c['skin'] ?? ''));
    echo "" . t('<button>Guardar</button>') . "</form>";
    if (is_mod()) echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"c\" value=\"" . h($slug) . "\"><input type=\"hidden\" name=\"act\" value=\"ocultar\"><button class=\"danger\">" . ($c['status'] === 'ok' ? t('Ocultar comunidad (moderación)') : t('Volver a mostrar')) . "</button></form>";
    echo "</details>";
}
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, "compact"); $n++; }
if ($n === 0) echo "<p class=\"end\">" . t('Todavía no hay envíos en esta comunidad. <a href="/enviar">Envía el primero</a> y elígela en el formulario.') . ($canEdit ? " " . t('También puedes añadir a esta comunidad cualquier envío ya publicado desde su hilo.') : '') . "</p>";
paginate('/comunidad/' . rawurlencode($slug), $page, $total);
page_footer();
