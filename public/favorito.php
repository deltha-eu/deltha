<?php
/** /favorito — guardar o quitar un envio de los favoritos del usuario (POST id, act=guardar|quitar). Sin JS: vuelve a la pagina de origen. */
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
$id = (int)post('id');
$act = post('act') === 'quitar' ? 'quitar' : 'guardar';

try {
    $st = $pdo->prepare("SELECT status FROM stories WHERE story_id = ?");
    $st->execute([$id]);
    $status = $st->fetchColumn();
    if ($status !== false && (!in_array($status, ['pending_mod', 'rejected', 'discard'], true) || is_admin())) {
        if ($act === 'guardar') $pdo->prepare("INSERT IGNORE INTO favorites (user_id, story_id) VALUES (?, ?)")->execute([$u['user_id'], $id]);
        else                    $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND story_id = ?")->execute([$u['user_id'], $id]);
    }
} catch (Throwable $e) { /* tabla aun sin crear: no romper la navegacion */ }

// volver a la misma pagina, al post (ancla s{id}); desde /favoritos, sin ancla (el post quitado ya no esta)
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = "/hilo/$id";
redirect($back . (str_contains($back, '/favoritos') || isset($_POST['js']) ? '' : "#s$id"));   // con JS (app/scroll.js) sin ancla
