<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/aura.php';
require __DIR__ . '/../lib/moderacion.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/');
$id = (int)post('id');
$content = mb_substr(post('content'), 0, 4000);
$u = user();
if (!$u && cfg('anon_can_comment') !== '1') redirect('/login');
if (mb_strlen($content) < 2) redirect("/hilo/$id");

$st = $pdo->prepare("SELECT status FROM stories WHERE story_id = ?");
$st->execute([$id]);
$status = $st->fetchColumn();
if (!in_array($status, ['nuevas','candidatas','destacadas'], true)) redirect("/hilo/$id");

if ($u) { $alias = alias_user($pdo, $id, (int)$u['user_id']); $key = null; $aura = (float)$u['aura']; $uid = $u['user_id']; }
else    { [$alias, $key] = alias_anon($id); $aura = (float)cfg('aura_anon', 5); $uid = null; }

$parent = (int)post('parent'); $root = null;
if ($parent > 0) {
    $q = $pdo->prepare("SELECT comment_id, root_id, user_id FROM comments WHERE comment_id = ? AND story_id = ? AND status = 'ok'");
    $q->execute([$parent, $id]);
    if ($pr = $q->fetch()) $root = $pr['root_id'] ?: (int)$pr['comment_id'];
    else { $parent = 0; $pr = null; }
}
$cstatus = 'ok';   // posmoderacion: se publica ya y la IA local lo revisa despues (lib/moderacion.php)
$pdo->beginTransaction();
$pdo->prepare("INSERT INTO comments (story_id, parent_id, root_id, user_id, alias, anon_key, aura, content, status) VALUES (?,?,?,?,?,?,?,?,?)")
    ->execute([$id, $parent ?: null, $root, $uid, $alias, $key, $aura, $content, $cstatus]);
$cid = (int)$pdo->lastInsertId();
$pdo->prepare("UPDATE stories SET comments = comments + 1 WHERE story_id = ?")->execute([$id]);
mod_enqueue($pdo, 'comment', $cid);
if ($u) aura_add($pdo, (int)$u['user_id'], (float)cfg('aura_comentario', 0.02), 'comentario', $cid);
$pdo->commit();
// avisos: @mencionados y autor del comentario respondido (lib/avisos.php)
avisos_comment($pdo, ['comment_id' => $cid, 'story_id' => $id, 'user_id' => $uid, 'alias' => $alias, 'content' => $content], $parent ? $pr : null);
redirect("/hilo/$id" . (in_array(post('v'), ['tiempo', 'votos'], true) ? '?v=' . post('v') : '') . (isset($_POST['js']) ? '' : "#c$cid"));   // con JS (app/scroll.js) sin ancla: el script restaura el scroll
