<?php
/** /editar-comentario.php?id=N — moderadores y admin: editar el texto de un comentario o eliminarlo. */
require __DIR__ . '/../lib/app.php';
if (!user()) redirect('/login.php');
if (!is_mod()) { http_response_code(403); page_header('Editar comentario'); echo "<p class=\"end\">Solo moderadores.</p>"; page_footer(); exit; }

$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT * FROM comments WHERE comment_id = ?");
$st->execute([$id]);
$c = $st->fetch();
if (!$c) { http_response_code(404); page_header('Editar comentario'); echo "<p class=\"end\">Ese comentario no existe.</p>"; page_footer(); exit; }
$back = "/hilo.php?id={$c['story_id']}#c$id";
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('act') === 'eliminar') {
        if (post('seguro') !== '1') $err = 'Marca la casilla de confirmación para eliminar.';
        else {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE comment_id = ? AND status = 'ok'")->execute([$id]);
            $pdo->prepare("UPDATE stories SET comments = GREATEST(0, comments - 1) WHERE story_id = ?")->execute([$c['story_id']]);
            $pdo->commit();
            redirect("/hilo.php?id={$c['story_id']}#comentarios");
        }
    } else {
        $txt = mb_substr(trim(post('content')), 0, 4000);
        if (mb_strlen($txt) < 1) $err = 'El comentario no puede quedar vacío.';
        else { $pdo->prepare("UPDATE comments SET content = ? WHERE comment_id = ?")->execute([$txt, $id]); redirect($back); }
        $c['content'] = $txt;
    }
}

page_header('Editar comentario');
echo "<h1>Editar comentario #$id</h1>";
echo "<p class=\"notice\">Edición de moderación. <a href=\"$back\">Volver al hilo</a>.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "<label>Texto</label><textarea name=\"content\" rows=\"8\" maxlength=\"4000\" required>" . h((string)$c['content']) . "</textarea>";
echo "<button>Guardar</button></form>";
echo "<h3 style=\"padding:16px 16px 0\">Eliminar</h3>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"eliminar\">";
echo "<label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> sí, eliminar este comentario (las respuestas que cuelguen de él se mantienen)</label>";
echo "<button class=\"danger\">Eliminar</button></form>";
page_footer();
