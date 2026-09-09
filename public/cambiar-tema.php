<?php
/** POST id, topic — cambia el tema de un envío. Solo moderadores y admin. Vuelve al hilo. */
require __DIR__ . '/../lib/app.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_mod()) { http_response_code(403); exit('Solo moderadores'); }
$id = (int)post('id'); $t = (int)post('topic');
$ok = $pdo->prepare("SELECT 1 FROM topics WHERE topic_id = ?"); $ok->execute([$t]);
if ($ok->fetchColumn()) $pdo->prepare("UPDATE stories SET topic_id = ? WHERE story_id = ?")->execute([$t, $id]);
$c = (int)post('community');
if ($c === 0) $pdo->prepare("UPDATE stories SET community_id = NULL WHERE story_id = ?")->execute([$id]);
else { $okc = $pdo->prepare("SELECT 1 FROM communities WHERE community_id = ?"); $okc->execute([$c]); if ($okc->fetchColumn()) $pdo->prepare("UPDATE stories SET community_id = ? WHERE story_id = ?")->execute([$c, $id]); }
redirect("/hilo.php?id=$id");
