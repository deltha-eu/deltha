<?php
/** /editar.php?id=N — edición de un envío. Moderadores y admin: todo (titular, texto, enlace, tema,
 *  comunidad, foto, eliminar). El autor, en plazo (aura > story_edit_min_aura, story_edit_minutes
 *  desde el envío y mientras no llegue a portada): titular, entradilla/texto, tema y foto — el
 *  enlace no se toca — y retirar el envío. Toda edición que cambie algo marca stories.edited_at. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/imagen.php';
if (!user()) redirect('/login.php');

$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT * FROM stories WHERE story_id = ?");
$st->execute([$id]);
$s = $st->fetch();
if (!$s) { http_response_code(404); page_header(t('Editar')); echo "<p class=\"end\">" . t('Ese envío no existe.') . "</p>"; page_footer(); exit; }
$own = !is_mod() && can_edit_own_story($s);   // el autor: aura > story_edit_min_aura, en plazo y aun no en portada
if (!is_mod() && !$own) {
    http_response_code(403); page_header(t('Editar'));
    $mine = (int)($s['user_id'] ?? 0) === (int)user()['user_id'];
    echo "<p class=\"end\">" . ($mine
        ? t('Ya no se puede editar: el plazo es de %d minutos desde el envío, hace falta aura mayor que %s y que el envío no haya llegado a portada.', (int)cfg('story_edit_minutes', 15), h(num_es(cfg('story_edit_min_aura', 10))))
        : t('Solo el autor (en los primeros minutos) o los moderadores.')) . " <a href=\"/hilo.php?id=$id\">" . t('Volver al hilo') . "</a></p>";
    page_footer(); exit;
}

$topics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY name")->fetchAll();
$comms  = $pdo->query("SELECT community_id, name FROM communities ORDER BY name")->fetchAll();
$isArt = $s['content_type'] === 'article';
$maxLen = $isArt ? 12000 : 2000;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'eliminar') {
    if (post('seguro') !== '1') $err = $own ? t('Marca la casilla de confirmación para retirarlo.') : 'Marca la casilla de confirmación para eliminar.';
    else {
        // no se borra fisicamente: pasa a "discard" y deja de verse en todas partes (el admin aun puede abrir el hilo)
        $pdo->prepare("UPDATE stories SET status = 'discard' WHERE story_id = ?")->execute([$id]);
        redirect('/');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') !== 'eliminar') {
    $title   = mb_substr(post('title'), 0, 200);
    $content = mb_substr(post('content'), 0, $maxLen);
    $topic   = (int)post('topic');
    if (!$own) {   // la comunidad principal solo la cambia moderacion; las adoptadas se gestionan desde el hilo
        $comm = (int)post('community');
        if ($comm && !in_array($comm, array_column($comms, 'community_id'), false)) $comm = 0;
        story_set_main_community($pdo, $id, $comm, (int)user()['user_id']);
    }
    $url     = $isArt ? null : ($own ? (string)$s['url'] : post('url'));   // el autor no toca el enlace
    $image   = $s['image'];
    if (post('quitar') === '1') $image = null;
    if (post('extraer') === '1' && $url) {                 // foto del enlace (og:image / twitter:image)
        if ($og = imagen_desde_og($url)) $image = $og;
        else $err = t('La página del enlace no ofrece ninguna foto usable.');
    }
    if ($up = imagen_desde_subida($_FILES['photo'] ?? [])) $image = $up;
    elseif (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) $err = t('No se ha podido procesar la foto (¿demasiado pequeña o formato no válido?).');

    if (!$err && mb_strlen($title) < 8) $err = t('El titular es demasiado corto.');
    elseif (!$err && !in_array($topic, array_column($topics, 'topic_id'), false)) $err = t('Elige un tema.');
    elseif (!$err && !$isArt && !$own && $url !== '' && !preg_match('#^https?://[^\s]+$#i', $url)) $err = 'La URL no es válida.';
    elseif (!$err && !$isArt && !$own && $url !== '' && ($bloq = source_blocked($pdo, $url))) $err = "Ese medio está bloqueado ($bloq): no se admiten sus enlaces. Se gestiona en moderación → Medios bloqueados.";
    elseif (!$err && $isArt && mb_strlen(trim($content)) < 20) $err = t('El texto del artículo es demasiado corto.');

    if (!$err) {
        $changed = $title !== $s['title'] || $content !== (string)($s['content'] ?? '') || $topic !== (int)$s['topic_id']
                || (string)$image !== (string)($s['image'] ?? '') || (!$isArt && ($url ?: null) !== $s['url']);
        $mark = $changed ? ", edited_at = NOW()" : "";
        if ($isArt) {
            $pdo->prepare("UPDATE stories SET title=?, content=?, topic_id=?, image=?$mark WHERE story_id=?")
                ->execute([$title, $content, $topic, $image, $id]);
        } else {
            $pdo->prepare("UPDATE stories SET title=?, content=?, topic_id=?, image=?, url=?, source=?$mark WHERE story_id=?")
                ->execute([$title, $content ?: null, $topic, $image, $url ?: null, $url ? source_of($url) : null, $id]);
        }
        redirect("/hilo.php?id=$id");
    }
    $s = array_merge($s, ['title' => $title, 'content' => $content, 'topic_id' => $topic, 'url' => $url, 'image' => $image]);
}

page_header(t('Editar envío'));
echo "<h1>" . ($isArt ? t('Editar artículo') : t('Editar envío')) . " #$id</h1>";
if ($own) {
    $q = (int)cfg('story_edit_minutes', 15) * 60 - (time() - strtotime($s['sent_date']));
    echo "<p class=\"notice\">" . t('Puedes corregir tu envío durante %d minutos tras enviarlo, mientras no llegue a portada (te quedan %d min). Quedará marcado como editado.', (int)cfg('story_edit_minutes', 15), max(1, intdiv($q, 60))) . " <a href=\"/hilo.php?id=$id\">" . t('Volver al hilo') . "</a>.</p>";
} else {
    echo "<p class=\"notice\">Edición de moderación. Los cambios se ven al momento; el autor, los votos y el delta no cambian. <a href=\"/hilo.php?id=$id\">Volver al hilo</a>.</p>";
}
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "<label>" . t('Titular') . "</label><input type=\"text\" name=\"title\" maxlength=\"200\" required value=\"" . h($s['title']) . "\">";
if (!$isArt && !$own) echo "<label>Enlace (vacío = sin enlace)</label><input type=\"url\" name=\"url\" value=\"" . h((string)$s['url']) . "\" placeholder=\"https://…\">";
if (!$isArt && $own && $s['url']) echo "<label>" . t('Enlace') . "</label><p class=\"meta\" style=\"padding:0\"><a href=\"" . h($s['url']) . "\" rel=\"noopener nofollow\">" . h((string)$s['source']) . "</a> · " . t('el enlace no se puede cambiar') . "</p>";
echo "<label>" . ($isArt ? t('Texto del artículo') : t('Entradilla')) . "</label><textarea name=\"content\" rows=\"" . ($isArt ? 16 : 5) . "\" maxlength=\"$maxLen\">" . h((string)$s['content']) . "</textarea>";
echo "<label>" . t('Tema') . "</label><select name=\"topic\">";
foreach ($topics as $t) echo "<option value=\"{$t['topic_id']}\"" . ((int)$s['topic_id'] === (int)$t['topic_id'] ? ' selected' : '') . ">" . h($t['name']) . "</option>";
echo "</select>";
if (!$own) {
    echo "<label>Comunidad principal</label><select name=\"community\"><option value=\"0\">— ninguna —</option>";
    foreach ($comms as $co) echo "<option value=\"{$co['community_id']}\"" . ((int)$s['community_id'] === (int)$co['community_id'] ? ' selected' : '') . ">" . h($co['name']) . "</option>";
    echo "</select>";
}
if ($s['image']) {
    echo "<label>" . t('Foto actual') . "</label><div class=\"media sm\" style=\"background-image:url('/media/" . h($s['image']) . "')\"></div>";
    echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"> " . t('quitar la foto') . "</label>";
    echo "<label>" . t('O sustituirla por otra') . "</label>";
} else {
    echo "<label>" . t('Foto') . "</label>";
}
echo "<input type=\"file\" name=\"photo\" accept=\"image/*\">";
if (!$isArt && $s['url']) echo "<label><input type=\"checkbox\" name=\"extraer\" value=\"1\"> " . t('extraer la foto de la página del enlace al guardar (og:image)') . "</label>";
echo "<button>" . t('Guardar cambios') . "</button></form>";
echo "<h3 style=\"padding:16px 16px 0\">" . ($own ? t('Retirar') : 'Eliminar') . "</h3>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"eliminar\">";
if ($own) echo "<label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> " . t('sí, retirar este %s y sus comentarios de todas las listas (no se puede deshacer)', $isArt ? t('artículo') : t('envío')) . "</label>";
else      echo "<label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> sí, eliminar este " . ($isArt ? 'artículo' : 'envío') . " y sus comentarios de todas las listas</label>";
echo "<button class=\"danger\">" . ($own ? t('Retirar') : 'Eliminar') . "</button></form>";
page_footer();
