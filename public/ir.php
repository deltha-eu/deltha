<?php
/** /ir?id=N — salida al medio de un envio: suma un clic (stories.clicks, lib/contadores.php) y redirige a su URL.
 *  302 y fuera de buscadores (robots.txt + X-Robots-Tag): es un contador, no un enlace propio. Sin URL (originales), al hilo. */
require __DIR__ . '/../lib/app.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT story_id, user_id, url, source, status FROM stories WHERE story_id = ?");
$st->execute([$id]);
$s = $st->fetch();
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
if (!$s || (in_array($s['status'], ['pending_mod', 'rejected', 'discard'], true) && !is_admin())) { http_response_code(404); exit; }
$url = url_limpia((string)$s['url']);   // los envios antiguos pueden llevar utm: se sale limpio
if (!preg_match('~^https?://~i', $url)) redirect('/hilo/' . $id);
story_cuenta($pdo, $s, 'clicks');
salida_cuenta($pdo, (string)($s['source'] ?? source_of($url)));   // salidas por dia y por medio (migra-144/147): /estadisticas
$url = url_con_utm($url);   // utm_source=deltha (13/10/2026, lib/app.php)
header('Location: ' . str_replace(["\r", "\n"], '', $url), true, 302);
