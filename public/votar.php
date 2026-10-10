<?php
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
$id = (int)post('id'); $v = (int)post('v');
if (!in_array($v, [1, -1], true)) redirect('/');

$pdo->beginTransaction();
try {
    $st = $pdo->prepare("SELECT status, user_id FROM stories WHERE story_id = ? FOR UPDATE");
    $st->execute([$id]);
    $s = $st->fetch();
    if ($s && in_array($s['status'], ['nuevas','destacadas'], true) && (int)$s['user_id'] !== (int)$u['user_id']) {
        $prev = $pdo->prepare("SELECT value, created FROM votes WHERE story_id=? AND user_id=?");
        $prev->execute([$id, $u['user_id']]);
        $pv = $prev->fetch();
        $old = $pv ? $pv['value'] : false;
        $undo = max(0, (int)cfg('vote_undo_minutes', 5));   // anular el voto: la misma flecha otra vez, durante vote_undo_minutes (migra-121)
        if ($old === false) {
            $pdo->prepare("INSERT INTO votes (story_id, user_id, value, fp) VALUES (?,?,?,?)")->execute([$id, $u['user_id'], $v, vote_fp($id)]);   // huella: un voto por conexion y envio en el δ
            alias_user($pdo, $id, (int)$u['user_id']);   // alias Ghost con el que se vera el voto en /procesos
            $col = $v === 1 ? 'votes_pos' : 'votes_neg';
            $pdo->prepare("UPDATE stories SET $col = $col + 1 WHERE story_id = ?")->execute([$id]);
        } elseif ((int)$old === $v) {
            if ($undo > 0 && time() - strtotime((string)$pv['created']) < $undo * 60) {   // misma flecha dentro del plazo: se retira el voto
                $pdo->prepare("DELETE FROM votes WHERE story_id=? AND user_id=?")->execute([$id, $u['user_id']]);
                $col = $v === 1 ? 'votes_pos' : 'votes_neg';
                $pdo->prepare("UPDATE stories SET $col = GREATEST(0, $col - 1) WHERE story_id = ?")->execute([$id]);
            }
        } else {
            // cambio de sentido
            $pdo->prepare("UPDATE votes SET value=? WHERE story_id=? AND user_id=?")->execute([$v, $id, $u['user_id']]);
            $pdo->prepare($v === 1
                ? "UPDATE stories SET votes_pos = votes_pos + 1, votes_neg = votes_neg - 1 WHERE story_id = ?"
                : "UPDATE stories SET votes_neg = votes_neg + 1, votes_pos = votes_pos - 1 WHERE story_id = ?")->execute([$id]);
        }
        if ($s['status'] === 'nuevas') eff_votes_update($pdo, $id);   // el δ en vivo refleja el voto al momento (si cuenta)
    }
    $pdo->commit();
} catch (Throwable $e) { $pdo->rollBack(); }
// con fetch (app/scroll.js, ajax=1; 10/10/2026): se devuelve solo el widget de votos ya actualizado y el script lo cambia en su sitio, sin recargar
if (isset($_POST['ajax'])) {
    require_once __DIR__ . '/../lib/render.php';
    $st = $pdo->prepare(story_query_base() . " WHERE s.story_id = ?"); $st->execute([$id]);
    header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store');
    if ($s = $st->fetch()) vote_widget($s);
    exit;
}
// volver a la misma pagina, al post votado (ancla s{id}) en vez de arriba del todo
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = "/hilo/$id";
redirect($back . (isset($_POST['js']) ? '' : "#s$id"));   // con JS (app/scroll.js) sin ancla: el script restaura el scroll
