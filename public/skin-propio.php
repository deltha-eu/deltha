<?php
/** /skin-propio — conmuta «usar siempre mi plantilla» (users.skin_propio, migra-143) desde la cabecera de una comunidad o fuente. POST v=0|1. Vuelve al origen. */
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
try { $pdo->prepare("UPDATE users SET skin_propio = ? WHERE user_id = ?")->execute([post('v') === '1' ? 1 : 0, (int)$u['user_id']]); } catch (Throwable $e) {}
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = '/perfil';
header('Cache-Control: no-store');
redirect($back);
