<?php
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login.php');
$id = (int)post('id'); $v = (int)post('v');
if (!in_array($v, [1, -1], true)) redirect('/');

$pdo->beginTransaction();
try {
    $st = $pdo->prepare("SELECT status, user_id FROM stories WHERE story_id = ? FOR UPDATE");
    $st->execute([$id]);
    $s = $st->fetch();
    if ($s && in_array($s['status'], ['nuevas','candidatas','destacadas'], true) && (int)$s['user_id'] !== (int)$u['user_id']) {
        $prev = $pdo->prepare("SELECT value FROM votes WHERE story_id=? AND user_id=?");
        $prev->execute([$id, $u['user_id']]);
        $old = $prev->fetchColumn();
        if ($old === false) {
            $pdo->prepare("INSERT INTO votes (story_id, user_id, value) VALUES (?,?,?)")->execute([$id, $u['user_id'], $v]);
            $col = $v === 1 ? 'votes_pos' : 'votes_neg';
            $pdo->prepare("UPDATE stories SET $col = $col + 1 WHERE story_id = ?")->execute([$id]);
        } elseif ((int)$old !== $v) {
            // cambio de sentido
            $pdo->prepare("UPDATE votes SET value=? WHERE story_id=? AND user_id=?")->execute([$v, $id, $u['user_id']]);
            $pdo->prepare($v === 1
                ? "UPDATE stories SET votes_pos = votes_pos + 1, votes_neg = votes_neg - 1 WHERE story_id = ?"
                : "UPDATE stories SET votes_neg = votes_neg + 1, votes_pos = votes_pos - 1 WHERE story_id = ?")->execute([$id]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) { $pdo->rollBack(); }
// volver a la misma pagina, al post votado (ancla s{id}) en vez de arriba del todo
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = "/hilo.php?id=$id";
redirect($back . "#s$id");
