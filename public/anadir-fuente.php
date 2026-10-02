<?php
/** /anadir-fuente.php — POST k=medio|newsletter|youtube, url=…: el usuario se añade una fuente que no esta en el catalogo pegando su
 *  direccion (migra-108; la logica, en fadd_anadir() de lib/propuestas.php). Sin JS: vuelve a su pestaña de «Mis medios» con el
 *  resultado en ?r= (y el nombre en ?n=), que pinta /mis-fuentes.php. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/propuestas.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login.php');
$k = in_array(post('k'), ['medio', 'newsletter', 'youtube'], true) ? post('k') : 'medio';
try { $res = fuentes_on() ? fadd_anadir($pdo, $u, $k, (string)post('url')) : ['url', '']; }
catch (Throwable $e) { $res = ['manual', '']; }   // p. ej. sin migra-108
if (isset($res[2]) && in_array($res[2], ['medio', 'newsletter', 'youtube'], true)) $k = $res[2];   // la fuente es de otro grupo (un substack pegado en «Mis medios», un canal…): a su pestaña
header('Cache-Control: no-store');
redirect("/mis-fuentes.php?k=$k&add=1&r=" . $res[0] . ($res[1] !== '' ? '&n=' . rawurlencode($res[1]) : '') . '#anadir');
