<?php
/**
 * Sol / lagrima en un comentario. POST id (comment_id) y v (1 | -1). Solo registrados, en hilos abiertos,
 * sobre comentarios de otros y no borrados. Repetir el mismo voto lo retira; el contrario lo cambia de sentido.
 * Contadores en comments.likes / comments.dislikes; el detalle en comment_votes. No toca aura ni delta.
 */
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
$id = (int)post('id'); $v = (int)post('v');
if (!in_array($v, [1, -1], true)) redirect('/');

$story = 0;
$pdo->beginTransaction();
try {
    $st = $pdo->prepare("SELECT c.comment_id, c.story_id, c.user_id, c.deleted, c.status, s.status AS sstatus
                         FROM comments c JOIN stories s ON s.story_id = c.story_id WHERE c.comment_id = ? FOR UPDATE");
    $st->execute([$id]);
    $c = $st->fetch();
    if ($c) $story = (int)$c['story_id'];
    if ($c && $c['status'] === 'ok' && !$c['deleted'] && in_array($c['sstatus'], ['nuevas', 'destacadas'], true)
        && (int)$c['user_id'] !== (int)$u['user_id']) {
        $prev = $pdo->prepare("SELECT value FROM comment_votes WHERE comment_id = ? AND user_id = ?");
        $prev->execute([$id, $u['user_id']]);
        $old = $prev->fetchColumn();
        if ($old === false) {
            $pdo->prepare("INSERT INTO comment_votes (comment_id, user_id, value) VALUES (?,?,?)")->execute([$id, $u['user_id'], $v]);
            $col = $v === 1 ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE comments SET $col = $col + 1 WHERE comment_id = ?")->execute([$id]);
        } elseif ((int)$old === $v) {
            // mismo boton: se retira la reaccion
            $pdo->prepare("DELETE FROM comment_votes WHERE comment_id = ? AND user_id = ?")->execute([$id, $u['user_id']]);
            $col = $v === 1 ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE comments SET $col = GREATEST(0, $col - 1) WHERE comment_id = ?")->execute([$id]);
        } else {
            // cambio de sentido
            $pdo->prepare("UPDATE comment_votes SET value = ? WHERE comment_id = ? AND user_id = ?")->execute([$v, $id, $u['user_id']]);
            $pdo->prepare($v === 1
                ? "UPDATE comments SET likes = likes + 1, dislikes = GREATEST(0, dislikes - 1) WHERE comment_id = ?"
                : "UPDATE comments SET dislikes = dislikes + 1, likes = GREATEST(0, likes - 1) WHERE comment_id = ?")->execute([$id]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) { $pdo->rollBack(); }
// volver al hilo, al comentario votado (ancla c{id})
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = $story ? "/hilo/$story" : '/';
redirect($back . (isset($_POST['js']) ? '' : "#c$id"));   // con JS (app/scroll.js) sin ancla: el script restaura el scroll
