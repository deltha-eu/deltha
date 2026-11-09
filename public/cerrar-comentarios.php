<?php
/** POST id, act=cerrar|abrir — cierra o vuelve a abrir los comentarios de un hilo (stories.comments_closed, migra-183).
 *  Solo moderadores y admin. El hilo sigue viéndose, votándose y en su sitio (radar, portada, foro); solo deja de admitir
 *  comentarios nuevos. Vuelve al hilo. */
require __DIR__ . '/../lib/app.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_mod()) { http_response_code(403); exit('Solo moderadores'); }
mod_log_post($pdo, 'cerrar-comentarios');   // registro (migra-134)
$id = (int)post('id');
$cerrar = post('act') === 'cerrar' ? 1 : 0;
$pdo->prepare("UPDATE stories SET comments_closed = ? WHERE story_id = ?")->execute([$cerrar, $id]);
redirect("/hilo/$id?comm=ok#comentarios");
