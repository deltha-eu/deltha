<?php
/** /editar-comentario.php?id=N — moderadores y admin: editar el texto de un comentario o eliminarlo. */
require __DIR__ . '/../lib/app.php';
if (!user()) redirect('/login.php');
$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT * FROM comments WHERE comment_id = ?");
$st->execute([$id]);
$c = $st->fetch();
if (!$c) { http_response_code(404); page_header('Editar comentario'); echo "<p class=\"end\">Ese comentario no existe.</p>"; page_footer(); exit; }
$own = !is_mod() && can_edit_own_comment($c);   // el autor: aura > comment_edit_min_aura y dentro del plazo
if (!is_mod() && !$own) {
    http_response_code(403); page_header('Editar comentario');
    $mine = (int)($c['user_id'] ?? 0) === (int)user()['user_id'];
    echo "<p class=\"end\">" . ($mine ? "Ya no se puede editar: el plazo es de " . (int)cfg('comment_edit_minutes', 10) . " minutos y hace falta aura mayor que " . h(num_es(cfg('comment_edit_min_aura', 10))) . "." : "Solo el autor (en los primeros minutos) o los moderadores.") . " <a href=\"/hilo.php?id={$c['story_id']}#c$id\">Volver al hilo</a></p>";
    page_footer(); exit;
}
$back = "/hilo.php?id={$c['story_id']}#c$id";
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('act') === 'eliminar' && is_mod()) {
        if (post('seguro') !== '1') $err = 'Marca la casilla de confirmación para eliminar.';
        else {
            comment_delete($pdo, $id);   // el comentario se queda, con el texto sustituido por el aviso
            redirect($back);
        }
    } else {
        $txt = mb_substr(trim(post('content')), 0, 4000);
        if (mb_strlen($txt) < 1) $err = 'El comentario no puede quedar vacío.';
        else { $pdo->prepare("UPDATE comments SET content = ?, edited_at = NOW() WHERE comment_id = ? AND content <> ?")->execute([$txt, $id, $txt]); redirect($back); }
        $c['content'] = $txt;
    }
}

page_header('Editar comentario');
echo "<h1>Editar comentario #$id</h1>";
if ($own) { $q = (int)cfg('comment_edit_minutes', 10) * 60 - (time() - strtotime($c['created'])); echo "<p class=\"notice\">Puedes corregir tu comentario durante " . (int)cfg('comment_edit_minutes', 10) . " minutos tras publicarlo (te quedan " . max(1, intdiv($q, 60)) . " min). Quedará marcado como editado. <a href=\"$back\">Volver al hilo</a>.</p>"; }
else echo "<p class=\"notice\">Edición de moderación. <a href=\"$back\">Volver al hilo</a>.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "<label>Texto</label><textarea name=\"content\" rows=\"8\" maxlength=\"4000\" required>" . h((string)$c['content']) . "</textarea>";
echo "<button>Guardar</button></form>";
if (is_mod()) {
echo "<h3 style=\"padding:16px 16px 0\">Eliminar</h3>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"eliminar\">";
echo "<label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> sí, eliminar este comentario: su texto se sustituye por «" . h(DDD_DELETED_TXT) . "» (las respuestas se mantienen)</label>";
echo "<button class=\"danger\">Eliminar</button></form>";
}
page_footer();
