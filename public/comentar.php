<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/aura.php';
require __DIR__ . '/../lib/moderacion.php';
require __DIR__ . '/../lib/imagen.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/');
$id = (int)post('id');
$content = mb_substr(post('content'), 0, 4000);
$u = user();
if (!$u && cfg('anon_can_comment') !== '1') redirect('/login');
if (mb_strlen($content) < 2) redirect("/hilo/$id");

$st = $pdo->prepare("SELECT status, comments_closed FROM stories WHERE story_id = ?");
$st->execute([$id]);
$story = $st->fetch();
$status = $story['status'] ?? '';
if (!in_array($status, ['nuevas','candidatas','destacadas'], true)) redirect("/hilo/$id");
if (!empty($story['comments_closed'])) redirect("/hilo/$id#comentarios");   // comentarios cerrados por moderacion (migra-183)

if ($u) { $alias = alias_user($pdo, $id, (int)$u['user_id']); $key = null; $aura = (float)$u['aura']; $uid = $u['user_id']; }
else    { [$alias, $key] = alias_anon($id); $aura = (float)cfg('aura_anon', 5); $uid = null; }

$parent = (int)post('parent'); $root = null;
if ($parent > 0) {
    $q = $pdo->prepare("SELECT comment_id, root_id, user_id FROM comments WHERE comment_id = ? AND story_id = ? AND status = 'ok'");
    $q->execute([$parent, $id]);
    if ($pr = $q->fetch()) $root = $pr['root_id'] ?: (int)$pr['comment_id'];
    else { $parent = 0; $pr = null; }
}
// imagen adjunta (migra-166): solo con cuenta y aura; si falla, el comentario se publica igual sin ella (el motivo se muestra en el hilo)
$image = null;
if ($u && comment_image_can() && !empty($_FILES['photo'])) {
    $image = imagen_comentario_desde_subida($_FILES['photo'], $ierr);
    if ($ierr) $_SESSION['flash_err'] = $ierr;
}
// posmoderacion: se publica ya y la IA local lo revisa despues (lib/moderacion.php). Anonimos con premod_anon = 1 (migra-183):
// entra como pending_mod, no cuenta en el hilo ni avisa a nadie, y espera en /moderacion a que un moderador lo apruebe (mod_unhide
// lo publica y suma el contador) o lo retire.
$premod = !$u && premod_anon();
$cstatus = $premod ? 'pending_mod' : 'ok';
$pdo->beginTransaction();
$pdo->prepare("INSERT INTO comments (story_id, parent_id, root_id, user_id, alias, anon_key, aura, content, image, status) VALUES (?,?,?,?,?,?,?,?,?,?)")
    ->execute([$id, $parent ?: null, $root, $uid, $alias, $key, $aura, $content, $image, $cstatus]);
$cid = (int)$pdo->lastInsertId();
if ($premod) mod_premod($pdo, 'comment', $cid, 'ok');
else {
    $pdo->prepare("UPDATE stories SET comments = comments + 1 WHERE story_id = ?")->execute([$id]);
    story_touch($pdo, $id);   // last_activity (migra-170)
    mod_enqueue($pdo, 'comment', $cid);
}
if ($u) aura_add($pdo, (int)$u['user_id'], (float)cfg('aura_comentario', 0.02), 'comentario', $cid);
$pdo->commit();
$v = in_array(post('v'), ['tiempo', 'votos'], true) ? '?v=' . post('v') : '';
if ($premod) redirect("/hilo/$id" . ($v ? "$v&" : '?') . "premod=1#comentarios");   // el aviso de que espera aprobacion
// avisos: @mencionados y autor del comentario respondido (lib/avisos.php)
avisos_comment($pdo, ['comment_id' => $cid, 'story_id' => $id, 'user_id' => $uid, 'alias' => $alias, 'content' => $content], $parent ? $pr : null);
redirect("/hilo/$id$v" . (isset($_POST['js']) ? '' : "#c$cid"));   // con JS (app/scroll.js) sin ancla: el script restaura el scroll
