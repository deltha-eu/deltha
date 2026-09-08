<?php
/** /editar.php?id=N — edición de un envío por moderadores y admin: titular, texto, enlace, tema y foto. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/imagen.php';
if (!user()) redirect('/login.php');
if (!is_mod()) { http_response_code(403); page_header('Editar'); echo "<p class=\"end\">Solo moderadores.</p>"; page_footer(); exit; }

$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT * FROM stories WHERE story_id = ?");
$st->execute([$id]);
$s = $st->fetch();
if (!$s) { http_response_code(404); page_header('Editar'); echo "<p class=\"end\">Ese envío no existe.</p>"; page_footer(); exit; }

$topics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY name")->fetchAll();
$isArt = $s['content_type'] === 'article';
$maxLen = $isArt ? 12000 : 2000;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'eliminar') {
    if (post('seguro') !== '1') $err = 'Marca la casilla de confirmación para eliminar.';
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
    $url     = $isArt ? null : post('url');
    $image   = $s['image'];
    if (post('quitar') === '1') $image = null;
    if (post('extraer') === '1' && $url) {                 // foto del enlace (og:image / twitter:image)
        if ($og = imagen_desde_og($url)) $image = $og;
        else $err = 'La página del enlace no ofrece ninguna foto usable.';
    }
    if ($up = imagen_desde_subida($_FILES['photo'] ?? [])) $image = $up;
    elseif (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) $err = 'No se ha podido procesar la foto (¿demasiado pequeña o formato no válido?).';

    if (!$err && mb_strlen($title) < 8) $err = 'El titular es demasiado corto.';
    elseif (!$err && !in_array($topic, array_column($topics, 'topic_id'), false)) $err = 'Elige un tema.';
    elseif (!$err && !$isArt && $url !== '' && !preg_match('#^https?://[^\s]+$#i', $url)) $err = 'La URL no es válida.';
    elseif (!$err && $isArt && mb_strlen(trim($content)) < 20) $err = 'El texto del artículo es demasiado corto.';

    if (!$err) {
        if ($isArt) {
            $pdo->prepare("UPDATE stories SET title=?, content=?, topic_id=?, image=? WHERE story_id=?")
                ->execute([$title, $content, $topic, $image, $id]);
        } else {
            $pdo->prepare("UPDATE stories SET title=?, content=?, topic_id=?, image=?, url=?, source=? WHERE story_id=?")
                ->execute([$title, $content ?: null, $topic, $image, $url ?: null, $url ? source_of($url) : null, $id]);
        }
        redirect("/hilo.php?id=$id");
    }
    $s = array_merge($s, ['title' => $title, 'content' => $content, 'topic_id' => $topic, 'url' => $url, 'image' => $image]);
}

page_header('Editar envío');
echo "<h1>Editar " . ($isArt ? 'artículo' : 'envío') . " #$id</h1>";
echo "<p class=\"notice\">Edición de moderación. Los cambios se ven al momento; el autor, los votos y el delta no cambian. <a href=\"/hilo.php?id=$id\">Volver al hilo</a>.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "<label>Titular</label><input type=\"text\" name=\"title\" maxlength=\"200\" required value=\"" . h($s['title']) . "\">";
if (!$isArt) echo "<label>Enlace (vacío = sin enlace)</label><input type=\"url\" name=\"url\" value=\"" . h((string)$s['url']) . "\" placeholder=\"https://…\">";
echo "<label>" . ($isArt ? 'Texto del artículo' : 'Entradilla') . "</label><textarea name=\"content\" rows=\"" . ($isArt ? 16 : 5) . "\" maxlength=\"$maxLen\">" . h((string)$s['content']) . "</textarea>";
echo "<label>Tema</label><select name=\"topic\">";
foreach ($topics as $t) echo "<option value=\"{$t['topic_id']}\"" . ((int)$s['topic_id'] === (int)$t['topic_id'] ? ' selected' : '') . ">" . h($t['name']) . "</option>";
echo "</select>";
if ($s['image']) {
    echo "<label>Foto actual</label><div class=\"media sm\" style=\"background-image:url('/media/" . h($s['image']) . "')\"></div>";
    echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"> quitar la foto</label>";
    echo "<label>O sustituirla por otra</label>";
} else {
    echo "<label>Foto</label>";
}
echo "<input type=\"file\" name=\"photo\" accept=\"image/*\">";
if (!$isArt) echo "<label><input type=\"checkbox\" name=\"extraer\" value=\"1\"> extraer la foto de la página del enlace al guardar (og:image)</label>";
echo "<button>Guardar cambios</button></form>";
echo "<h3 style=\"padding:16px 16px 0\">Eliminar</h3>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"eliminar\">";
echo "<label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> sí, eliminar este " . ($isArt ? 'artículo' : 'envío') . " y sus comentarios de todas las listas</label>";
echo "<button class=\"danger\">Eliminar</button></form>";
page_footer();
