<?php
/** /ir.php?id=N — salida al medio de un envio: suma un clic (stories.clicks, lib/contadores.php) y redirige a su URL.
 *  302 y fuera de buscadores (robots.txt + X-Robots-Tag): es un contador, no un enlace propio. Sin URL (originales), al hilo. */
require __DIR__ . '/../lib/app.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT story_id, user_id, url, status FROM stories WHERE story_id = ?");
$st->execute([$id]);
$s = $st->fetch();
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
if (!$s || (in_array($s['status'], ['pending_mod', 'rejected', 'discard'], true) && !is_admin())) { http_response_code(404); exit; }
$url = (string)$s['url'];
if (!preg_match('~^https?://~i', $url)) redirect('/hilo.php?id=' . $id);
story_cuenta($pdo, $s, 'clicks');
header('Location: ' . str_replace(["\r", "\n"], '', $url), true, 302);
