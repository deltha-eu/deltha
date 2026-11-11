<?php
/** /seguir — marcar o dejar de seguir un hilo (POST id, act=seguir|dejar). Sin JS: vuelve a la página de origen.
 *  Los hilos en los que no se puede escribir (retirados, ocultos) no se siguen, salvo para un administrador.
 *  La marca vive en thread_follows (lib/seguir.php, migra-192) y el aviso de cada comentario nuevo lo crea lib/avisos.php. */
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
$id = (int)post('id');
$act = post('act') === 'dejar' ? 'dejar' : 'seguir';

try {
    $st = $pdo->prepare("SELECT status FROM stories WHERE story_id = ?");
    $st->execute([$id]);
    $status = $st->fetchColumn();
    $canFollow = in_array($status, ['nuevas', 'candidatas', 'destacadas'], true);
    if ($status !== false && ($act === 'dejar' || $canFollow)) {
        hilo_seguir($pdo, (int)$u['user_id'], $id, $act === 'seguir');
    }
} catch (Throwable $e) { /* tabla aún sin crear: no romper la navegación */ }

// volver a la misma página, al post (ancla s{id}); con JS (app/scroll.js) sin ancla, que el script restaura el scroll
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = "/hilo/$id";
redirect($back . (isset($_POST['js']) ? '' : "#s$id"));
