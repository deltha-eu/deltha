<?php
/** /comunidad/slug — todo lo que está en una comunidad (enviado a ella o adoptado por su dueño desde el hilo), lo último
 *  añadido primero. El creador (y los mods) la editan aquí mismo. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/imagen.php';   // logo y portada de la comunidad (migra-126)

$slug = trim((string)($_GET['c'] ?? post('c')));
$c = community_by_slug($pdo, $slug);
if (!$c || ($c['status'] !== 'ok' && !is_mod())) { http_response_code(404); page_header(t('Comunidad')); echo "<p class=\"end\">" . t('Esa comunidad no existe. <a href="/comunidades">Ver todas</a>.') . "</p>"; page_footer(); exit; }
$u = user();
$canEdit = $u && ((int)$u['user_id'] === (int)$c['owner_id'] || is_mod());
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (is_mod() && (int)$u['user_id'] !== (int)$c['owner_id']) mod_log_post($pdo, 'comunidad', $err, 'comunidad ' . (string)$c['slug']);   // registro (migra-134): un mod toca una comunidad ajena
    if (post('act') === 'ocultar' && is_mod()) {
        $pdo->prepare("UPDATE communities SET status = IF(status = 'ok', 'hidden', 'ok') WHERE community_id = ?")->execute([$c['community_id']]);
        redirect('/comunidad/' . rawurlencode($slug));
    }
    $name = trim(preg_replace('/\s+/', ' ', post('name'))); $descr = mb_substr(trim(post('descr')), 0, 200); $color = post('color') ?: $c['color'];
    $skin = isset(DDD_SKINS[post('skin')]) ? post('skin') : '';
    // logo (cuadrado, sustituye al punto de color) y portada (fondo de la cabecera), migra-126: /media/comunidades
    $logo = $c['logo'] ?? null; $cover = $c['cover'] ?? null; $imgErr = '';
    foreach (['logo' => [256, 256, &$logo], 'cover' => [1600, 400, &$cover]] as $campo => [$w, $h, &$cur]) {
        if (post("quitar_$campo") === '1' && $cur) { @unlink(DDD_MEDIA_DIR . '/comunidades/' . $cur); $cur = null; }
        if (!empty($_FILES[$campo]['name'])) {
            $new = comunidad_imagen_desde_subida($_FILES[$campo], $w, $h);
            if (!$new) $imgErr = t('No se ha podido leer la imagen (JPG, PNG, WebP o AVIF, hasta %d MB).', (int)round((int)cfg('img_max_bytes', 6000000) / 1000000));
            else { if ($cur) @unlink(DDD_MEDIA_DIR . '/comunidades/' . $cur); $cur = $new; }
        }
    }
    unset($cur);
    // cambio de dueño (08/10/2026): el dueño o un mod escriben el nick de quien se la queda; el nuevo dueño edita y adopta envios
    $owner = (int)$c['owner_id']; $newOwner = trim(post('owner_login'));
    if ($newOwner !== '' && strcasecmp($newOwner, (string)$c['owner']) !== 0) {
        $q = $pdo->prepare("SELECT user_id FROM users WHERE login = ?"); $q->execute([$newOwner]);
        if (!($owner = (int)$q->fetchColumn())) $imgErr = t('Ese usuario no existe.');
    }
    if (mb_strlen($name) < 3 || mb_strlen($name) > 48) $err = t('El nombre debe tener entre 3 y 48 caracteres.');
    elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $err = t('Color no válido.');
    elseif ($imgErr !== '') $err = $imgErr;
    else {
        try { $pdo->prepare("UPDATE communities SET name = ?, descr = ?, color = ?, skin = ?, logo = ?, cover = ?, owner_id = ? WHERE community_id = ?")->execute([$name, $descr ?: null, $color, $skin, $logo, $cover, $owner, $c['community_id']]); redirect('/comunidad/' . rawurlencode($slug) . '?ok=1'); }
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
$style = "--cc:" . h($c['color']) . (!empty($c['cover']) ? ";background-image:url('/media/comunidades/" . h($c['cover']) . "')" : '');
echo "<div class=\"commhead" . (!empty($c['cover']) ? ' withcover' : '') . "\" style=\"$style\">" . (!empty($c['logo']) ? "<img class=\"logo\" src=\"/media/comunidades/" . h($c['logo']) . "\" alt=\"\">" : "<span class=\"dot\"></span>") . "<div><h1>" . h($c['name']) . "</h1>";
if ($c['descr']) echo "<p class=\"bio\">" . h($c['descr']) . "</p>";
echo "<p class=\"meta\">" . t('comunidad creada por') . " <a href=\"/u/" . rawurlencode($c['owner']) . "\">" . h($c['owner']) . "</a> · " . t('%d envíos', $total) . " · <a href=\"/comunidades\">" . t('todas las comunidades') . "</a>";
if ($c['status'] !== 'ok') echo " · <b>" . t('oculta') . "</b>";
if ($u && $u['landing'] !== 'c:' . $c['community_id']) echo " · <a href=\"/perfil#aterrizar\">" . t('entrar aquí al abrir Deltha') . "</a>";
echo "</p></div></div>";
if (isset($_GET['nueva'])) echo "<p class=\"notice\">" . t('Comunidad creada. Ya se puede elegir al enviar una noticia; abajo puedes ajustar el nombre, la descripción, el color y la skin.') . "</p>";
if (isset($_GET['ok'])) echo "<p class=\"notice\">" . t('Guardado.') . "</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($canEdit) {
    echo "<details class=\"editbox\">" . t('<summary>Editar la comunidad</summary>') . "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"c\" value=\"" . h($slug) . "\">";
    echo "" . t('<label>Nombre</label>') . "<input type=\"text\" name=\"name\" maxlength=\"48\" required value=\"" . h($c['name']) . "\">";
    echo "" . t('<label>De qué va</label>') . "<input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h((string)$c['descr']) . "\">";
    echo "" . t('<label>Color</label>') . "<input type=\"color\" name=\"color\" value=\"" . h($c['color']) . "\">";
    echo skin_select((string)($c['skin'] ?? ''));
    echo "<label>" . t('Logo') . " <small>" . t('cuadrado; sustituye al punto de color') . "</small></label><input type=\"file\" name=\"logo\" accept=\"image/*\">";
    if (!empty($c['logo'])) echo "<label><input type=\"checkbox\" name=\"quitar_logo\" value=\"1\"> " . t('quitar el logo actual') . "</label>";
    echo "<label>" . t('Imagen de fondo') . " <small>" . t('apaisada, se recorta a 4:1; va detrás de la cabecera') . "</small></label><input type=\"file\" name=\"cover\" accept=\"image/*\">";
    if (!empty($c['cover'])) echo "<label><input type=\"checkbox\" name=\"quitar_cover\" value=\"1\"> " . t('quitar la imagen de fondo actual') . "</label>";
    echo "<label>" . t('Dueño') . " <small>" . t('nick de quien pasa a ser el dueño; si no eres mod, dejarás de poder editarla') . "</small></label><input type=\"text\" name=\"owner_login\" maxlength=\"32\" value=\"" . h($c['owner']) . "\" autocomplete=\"off\">";
    echo "" . t('<button>Guardar</button>') . "</form>";
    if (is_mod()) echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"c\" value=\"" . h($slug) . "\"><input type=\"hidden\" name=\"act\" value=\"ocultar\"><button class=\"danger\">" . ($c['status'] === 'ok' ? t('Ocultar comunidad (moderación)') : t('Volver a mostrar')) . "</button></form>";
    echo "</details>";
}
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, "compact"); $n++; }
if ($n === 0) echo "<p class=\"end\">" . t('Todavía no hay envíos en esta comunidad. <a href="/enviar">Envía el primero</a> y elígela en el formulario.') . ($canEdit ? " " . t('También puedes añadir a esta comunidad cualquier envío ya publicado desde su hilo.') : '') . "</p>";
paginate('/comunidad/' . rawurlencode($slug), $page, $total);
page_footer();
