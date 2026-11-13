<?php
/** /editar-comentario?id=N — moderadores y admin: editar el texto de un comentario o eliminarlo. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/moderacion.php';
if (!user()) redirect('/login');
$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT * FROM comments WHERE comment_id = ?");
$st->execute([$id]);
$c = $st->fetch();
if (!$c) { http_response_code(404); page_header(t('Editar comentario')); echo "<p class=\"end\">" . t('Ese comentario no existe.') . "</p>"; page_footer(); exit; }
$own = !is_mod() && can_edit_own_comment($c);   // el autor: aura > comment_edit_min_aura y dentro del plazo
if (!is_mod() && !$own) {
    http_response_code(403); page_header(t('Editar comentario'));
    $mine = (int)($c['user_id'] ?? 0) === (int)user()['user_id'];
    echo "<p class=\"end\">" . ($mine ? t('Ya no se puede editar: el plazo es de %d minutos y hace falta aura mayor que %s.', (int)cfg('comment_edit_minutes', 10), h(num_es(cfg('comment_edit_min_aura', 10)))) : t('Solo el autor (en los primeros minutos) o los moderadores.')) . " <a href=\"/hilo/{$c['story_id']}#c$id\">" . t('Volver al hilo') . "</a></p>";
    page_footer(); exit;
}
$back = "/hilo/{$c['story_id']}#c$id";
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_mod() && (int)$c['user_id'] !== (int)user()['user_id']) {
    $GLOBALS['DDD_LOG_ITEM'] = ['comment', (int)$c['comment_id']];   // el registro enseña la conversación de ese comentario (migra-191)
    mod_undo_comment($pdo, (int)$c['comment_id']);   // texto, estado, marca de editado y reportes, para poder deshacerlo (migra-176)
    mod_log_post($pdo, 'editar-comentario', $err, "comentario #" . (int)$c['comment_id']);   // registro (migra-134)
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('act') === 'eliminar' && is_mod()) {
        if (post('seguro') !== '1') $err = 'Marca la casilla de confirmación para eliminar.';
        elseif (post('modo') === 'todo' && is_admin()) {   // admin: borrado total, sin dejar rastro (pruebas y limpieza, 20/10/2026)
            require_once __DIR__ . '/../lib/imagen.php';
            if (comment_purge($pdo, $id)) redirect($back);
            $err = 'Tiene respuestas: solo cabe el borrado normal (deja el aviso y las respuestas).';
        } else {
            comment_delete($pdo, $id);   // el comentario se queda, con el texto sustituido por el aviso
            redirect($back);
        }
    } else {
        $txt = mb_substr(trim(post('content')), 0, 4000);
        $avisar = is_mod() && !empty($c['user_id']) && (int)$c['user_id'] !== (int)user()['user_id'];   // moderación edita un comentario ajeno: motivo obligatorio si cambia el texto, y se le envía al autor (13/11/2026)
        $motivo = $avisar ? mod_motivo_post() : '';
        if (mb_strlen($txt) < 1) $err = t('El comentario no puede quedar vacío.');
        elseif ($avisar && $txt !== (string)$c['content'] && mb_strlen($motivo) < 3) $err = 'Indica el motivo de la edición: queda en el registro y se le envía al autor.';
        else {
            $st = $pdo->prepare("UPDATE comments SET content = ?, edited_at = NOW() WHERE comment_id = ? AND content <> ?"); $st->execute([$txt, $id, $txt]);
            if (post('quitar_img') === '1' && !empty($c['image'])) { require_once __DIR__ . '/../lib/imagen.php'; imagen_comentario_borrar((string)$c['image']); $pdo->prepare("UPDATE comments SET image = NULL WHERE comment_id = ?")->execute([$id]); }   // migra-166
            if ($own && $st->rowCount()) {
                mod_requeue($pdo, 'comment', $id);   // lo edita su autor: la IA lo vuelve a mirar
                avisos_comment($pdo, array_merge($c, ['content' => $txt]), null, avisos_nicks((string)$c['content']), false);   // solo los @nick nuevos; editar no avisa a los seguidores del hilo
            }
            if ($avisar && mb_strlen($motivo) >= 3 && ($st->rowCount() || (post('quitar_img') === '1' && !empty($c['image'])))) {
                $qt = $pdo->prepare("SELECT title FROM stories WHERE story_id = ?"); $qt->execute([(int)$c['story_id']]);
                mod_aviso_edicion($pdo, (int)$c['user_id'], "/hilo/{$c['story_id']}#c$id", 'Hola. Un moderador ha editado tu comentario en «%s». Motivo: %s. Si crees que es un error, responde a este mensaje.', mb_strimwidth((string)$qt->fetchColumn(), 0, 120, '…'), $motivo);
            }
            redirect($back);
        }
        $c['content'] = $txt;
    }
}

page_header(t('Editar comentario') . " #$id");   // sin <h1>: el titulo ya va en la cabecera
if ($own) { $q = (int)cfg('comment_edit_minutes', 10) * 60 - (time() - strtotime($c['created'])); echo "<p class=\"notice\">" . t('Puedes corregir tu comentario durante %d minutos tras publicarlo (te quedan %d min). Quedará marcado como editado.', (int)cfg('comment_edit_minutes', 10), max(1, intdiv($q, 60))) . " <a href=\"$back\">" . t('Volver al hilo') . "</a>.</p>"; }
else echo "<p class=\"notice\">Edición de moderación. Si cambias el texto, el motivo se le envía al autor por mensaje. <a href=\"$back\">Volver al hilo</a>.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "" . t('<label>Texto</label>') . "<textarea name=\"content\" rows=\"8\" maxlength=\"4000\" required>" . h((string)$c['content']) . "</textarea>" . md_ayuda();
if (!empty($c['image'])) echo "<a class=\"cimg\" href=\"/media/" . h($c['image']) . "\" target=\"_blank\" rel=\"noopener\"><img src=\"/media/" . h($c['image']) . "\" alt=\"\"></a><label><input type=\"checkbox\" name=\"quitar_img\" value=\"1\"> " . t('quitar la imagen') . "</label>";   // migra-166
if (is_mod() && !empty($c['user_id']) && (int)$c['user_id'] !== (int)user()['user_id']) echo mod_motivo_campo();   // motivo para el autor (13/11/2026)
echo "" . t('<button>Guardar</button>') . "</form>";
if (is_mod()) {
echo "<h3 style=\"padding:16px 16px 0\">Eliminar</h3>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"eliminar\">";
echo "<label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> sí, eliminar este comentario: su texto se sustituye por «" . h(DDD_DELETED_TXT) . "» (las respuestas se mantienen)</label>";
if (is_admin()) {   // borrado total (20/10/2026): sin aviso, sin fila, sin votos ni imagen; solo si no tiene respuestas
    $nr = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE parent_id = ?"); $nr->execute([$id]); $nr = (int)$nr->fetchColumn();
    echo "<label><input type=\"radio\" name=\"modo\" value=\"aviso\" checked> dejar el aviso de comentario eliminado (moderación)</label>";
    echo "<label><input type=\"radio\" name=\"modo\" value=\"todo\"" . ($nr ? ' disabled' : '') . "> borrar del todo, sin rastro: fila, votos, reportes, avisos e imagen; el contador del hilo baja" . ($nr ? " <small>(no: tiene $nr respuesta" . ($nr > 1 ? 's' : '') . ")</small>" : " <small>(admin; para pruebas y limpieza)</small>") . "</label>";
}
echo "<button class=\"danger\">Eliminar</button></form>";
}
page_footer();
