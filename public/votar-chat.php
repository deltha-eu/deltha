<?php
/**
 * Sol / lagrima en un mensaje del Chat. POST id (msg_id) y v (1 | -1). Solo registrados, sobre mensajes ajenos,
 * visibles y no borrados. Repetir el mismo voto lo retira; el contrario lo cambia de sentido.
 * Contadores en chat.likes / chat.dislikes; el detalle en chat_votes (migra-074). Gemelo de votar-comentario.php.
 */
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
$id = (int)post('id'); $v = (int)post('v');
if (!in_array($v, [1, -1], true)) redirect('/chat');

$pdo->beginTransaction();
try {
    $st = $pdo->prepare("SELECT msg_id, user_id, deleted, status FROM chat WHERE msg_id = ? FOR UPDATE");
    $st->execute([$id]);
    $m = $st->fetch();
    if ($m && $m['status'] === 'ok' && !$m['deleted'] && (int)$m['user_id'] !== (int)$u['user_id']) {
        $prev = $pdo->prepare("SELECT value FROM chat_votes WHERE msg_id = ? AND user_id = ?");
        $prev->execute([$id, $u['user_id']]);
        $old = $prev->fetchColumn();
        if ($old === false) {
            $pdo->prepare("INSERT INTO chat_votes (msg_id, user_id, value) VALUES (?,?,?)")->execute([$id, $u['user_id'], $v]);
            $col = $v === 1 ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE chat SET $col = $col + 1 WHERE msg_id = ?")->execute([$id]);
        } elseif ((int)$old === $v) {
            // mismo boton: se retira la reaccion
            $pdo->prepare("DELETE FROM chat_votes WHERE msg_id = ? AND user_id = ?")->execute([$id, $u['user_id']]);
            $col = $v === 1 ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE chat SET $col = GREATEST(0, $col - 1) WHERE msg_id = ?")->execute([$id]);
        } else {
            // cambio de sentido
            $pdo->prepare("UPDATE chat_votes SET value = ? WHERE msg_id = ? AND user_id = ?")->execute([$v, $id, $u['user_id']]);
            $pdo->prepare($v === 1
                ? "UPDATE chat SET likes = likes + 1, dislikes = GREATEST(0, dislikes - 1) WHERE msg_id = ?"
                : "UPDATE chat SET dislikes = dislikes + 1, likes = GREATEST(0, likes - 1) WHERE msg_id = ?")->execute([$id]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) { $pdo->rollBack(); }
if (isset($_POST['ajax'])) {   // con fetch (app/scroll.js): solo el widget actualizado, sin recargar (10/10/2026)
    require_once __DIR__ . '/../lib/render.php';
    $st = $pdo->prepare("SELECT c.*, cv.value AS my_vote FROM chat c LEFT JOIN chat_votes cv ON cv.msg_id = c.msg_id AND cv.user_id = ? WHERE c.msg_id = ?"); $st->execute([$u['user_id'], $id]);
    header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store');
    if ($m = $st->fetch()) comment_vote_widget($m, true, '/votar-chat', 'msg_id');
    exit;
}
// volver a la misma pagina del Chat, al mensaje votado (ancla m{id})
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/chat(\.php)?~i', $back)) $back = "/chat?m=$id";
redirect($back . (isset($_POST['js']) ? '' : "#m$id"));   // con JS (app/scroll.js) sin ancla: el script restaura el scroll
