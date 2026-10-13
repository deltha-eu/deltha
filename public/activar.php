<?php
/** /activar?u=ID&t=TOKEN — activa la cuenta desde el enlace del correo y abre sesion. Visible sin clave. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$uid = (int)($_GET['u'] ?? 0); $t = (string)($_GET['t'] ?? '');
$st = $pdo->prepare("SELECT user_id, verified, token, token_sent FROM users WHERE user_id = ?");
$st->execute([$uid]);
$u = $st->fetch();
$hours = (int)cfg('token_hours', 48);

// primero se decide, luego se pinta (13/10/2026): si se abria la cabecera antes, el exito no podia abrir sesion ni redirigir («headers already sent»)
$err = '';
if (!$u) $err = t('<h1>Enlace no válido</h1><p class="end">Esa cuenta no existe.</p>');
elseif ((int)$u['verified'] === 1) $err = t('<h1>Cuenta ya activa</h1><p class="end">Ya puedes <a href="/login">entrar</a>.</p>');
elseif (!$u['token'] || !preg_match('/^[a-f0-9]{32}$/', $t) || !hash_equals($u['token'], $t)) $err = t('<h1>Enlace no válido</h1><p class="end">El enlace está incompleto o ya se ha usado. <a href="/reenviar">Pide otro</a>.</p>');
elseif (strtotime($u['token_sent']) < time() - $hours * 3600) $err = "<h1>" . t('Enlace caducado') . "</h1><p class=\"end\">" . t('Tenía %d horas de validez. <a href="/reenviar">Pide otro</a>.', $hours) . "</p>";
if ($err !== '') { page_header(t('Activar cuenta')); echo $err; page_footer(); exit; }
$pdo->prepare("UPDATE users SET verified = 1, token = NULL, token_sent = NULL WHERE user_id = ?")->execute([$uid]);
try { $pdo->prepare("UPDATE reg_log SET estado = 'activada', activated = NOW() WHERE user_id = ? AND estado = 'pendiente'")->execute([$uid]); } catch (Throwable $e) { /* sin migra-102 */ }
session_arranca();   // misma duracion que en login.php (session_days), no hasta cerrar el navegador
session_regenerate_id(true);
$_SESSION['uid'] = $uid;
redirect('/?bienvenida=1');
