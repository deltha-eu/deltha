<?php
/** /cerrar-aviso?k=<clave> — la × del aviso a la comunidad (aviso_comunidad(), lib/app.php). Guarda en la cookie ddd_av la huella del
 *  texto en vigor (un año) y vuelve a la pagina de origen; sin JS. Si la clave no es la del aviso actual, no guarda nada (un enlace viejo
 *  no puede cerrar un aviso nuevo). 17/10/2026, pedido por Kosako. */
require __DIR__ . '/../lib/app.php';
$txt = trim((string)cfg('aviso_txt', ''));
if ($txt !== '' && (string)($_GET['k'] ?? '') === aviso_clave($txt)) pref_cookie(DDD_AVISO_COOKIE, aviso_clave($txt));
// ?a=clave: la × de un aviso de ayuda (migra-181); con ?js=1 (app/scroll.js lo quita en el sitio) responde 204 y no redirige
if (($a = (string)($_GET['a'] ?? '')) !== '') { ayuda_cerrar($a); if (isset($_GET['js'])) { http_response_code(204); exit; } }
$back = (string)($_SERVER['HTTP_REFERER'] ?? '');
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($back === '' || !$host || parse_url($back, PHP_URL_HOST) !== $host || str_contains($back, 'cerrar-aviso')) $back = '/';
header('Cache-Control: no-store');
redirect($back);
