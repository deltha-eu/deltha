<?php
/** /ir-fuente?i=N — salida a un medio o substack desde su ficha (/fuente/slug, lib/fuentes.php fuente_post): suma una salida en
 *  clicks_daily (migra-144, «Salidas a los medios» de /estadisticas) y redirige a la noticia con utm_source=deltha (url_con_utm).
 *  Hermano de /ir (envios). 302 y fuera de buscadores: es un contador, no un enlace propio. 13/10/2026. */
require __DIR__ . '/../lib/app.php';

$id = (int)($_GET['i'] ?? 0);
$st = $pdo->prepare("SELECT i.url FROM source_items i WHERE i.item_id = ?");
$st->execute([$id]);
$url = (string)$st->fetchColumn();
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
if (!preg_match('~^https?://~i', $url)) { http_response_code(404); exit; }
salida_cuenta($pdo, source_of($url));   // salidas por dia y por medio (migra-144/147)
header('Location: ' . str_replace(["\r", "\n"], '', url_con_utm($url)), true, 302);
