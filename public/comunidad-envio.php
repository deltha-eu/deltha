<?php
/** POST id, community, act=añadir|quitar — añade un envío a una comunidad o lo quita de ella.
 *  Pueden hacerlo el dueño de esa comunidad, los moderadores y el admin (los mods arbitran; el autor no interviene).
 *  Se adoptan envíos en cualquier fase pública: radar, portada o archivados. Tope comm_max_per_story. Vuelve al hilo. */
require __DIR__ . '/../lib/app.php';
if (!user()) redirect('/login');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/');
$u = user();
$id = (int)post('id'); $c = (int)post('community');
if (!$id || !$c || !community_can_manage($pdo, $u, $c)) { http_response_code(403); exit('Solo el dueño de la comunidad o los moderadores'); }
$err = null;
if (post('act') === 'quitar') community_remove_story($pdo, $id, $c);
else $err = community_add_story($pdo, $id, $c, (int)$u['user_id']);
redirect("/hilo/$id?comm=" . rawurlencode($err ?? 'ok'));
