<?php
/** /cerrar-aviso?k=<clave> — la × del aviso a la comunidad (aviso_comunidad(), lib/app.php). Guarda en la cookie ddd_av la huella del
 *  texto en vigor (un año) y vuelve a la pagina de origen; sin JS. Si la clave no es la del aviso actual, no guarda nada (un enlace viejo
 *  no puede cerrar un aviso nuevo). 17/10/2026, pedido por Kosako. */
require __DIR__ . '/../lib/app.php';
$txt = trim((string)cfg('aviso_txt', ''));
if ($txt !== '' && (string)($_GET['k'] ?? '') === aviso_clave($txt)) pref_cookie(DDD_AVISO_COOKIE, aviso_clave($txt));
$back = (string)($_SERVER['HTTP_REFERER'] ?? '');
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($back === '' || !$host || parse_url($back, PHP_URL_HOST) !== $host || str_contains($back, 'cerrar-aviso')) $back = '/';
header('Cache-Control: no-store');
redirect($back);
