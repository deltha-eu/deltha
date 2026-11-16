<?php
/* Votar en una encuesta o retirar el voto para cambiarlo (migra-204). Solo POST y solo con cuenta; vuelve a `back` (la pagina de
 * la encuesta) con ?pollerr= si algo no entra. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/encuesta.php';

$u = user();
$back = post('back');
if ($back === '' || $back[0] !== '/' || str_starts_with($back, '//')) $back = '/';   // solo rutas propias
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);
if (!$u) redirect('/login');
$pid = (int)post('poll');
$err = match (post('act')) {
    'votar'   => encuesta_votar($pdo, $pid, (int)$u['user_id'], (array)($_POST['opt'] ?? [])),
    'cambiar' => encuesta_cambiar($pdo, $pid, (int)$u['user_id']),
    default   => t('Acción desconocida.'),
};
if ($err) $_SESSION['flash_err'] = $err;
redirect($back);
