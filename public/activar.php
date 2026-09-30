<?php
/** /activar.php?u=ID&t=TOKEN — activa la cuenta desde el enlace del correo y abre sesion. Visible sin clave. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$uid = (int)($_GET['u'] ?? 0); $t = (string)($_GET['t'] ?? '');
$st = $pdo->prepare("SELECT user_id, verified, token, token_sent FROM users WHERE user_id = ?");
$st->execute([$uid]);
$u = $st->fetch();
$hours = (int)cfg('token_hours', 48);

page_header(t('Activar cuenta'));
if (!$u) { echo "" . t('<h1>Enlace no válido</h1><p class="end">Esa cuenta no existe.</p>') . ""; page_footer(); exit; }
if ((int)$u['verified'] === 1) { echo "" . t('<h1>Cuenta ya activa</h1><p class="end">Ya puedes <a href="/login.php">entrar</a>.</p>') . ""; page_footer(); exit; }
if (!$u['token'] || !preg_match('/^[a-f0-9]{32}$/', $t) || !hash_equals($u['token'], $t)) {
    echo "" . t('<h1>Enlace no válido</h1><p class="end">El enlace está incompleto o ya se ha usado. <a href="/reenviar.php">Pide otro</a>.</p>') . ""; page_footer(); exit;
}
if (strtotime($u['token_sent']) < time() - $hours * 3600) {
    echo "<h1>" . t('Enlace caducado') . "</h1><p class=\"end\">" . t('Tenía %d horas de validez. <a href="/reenviar.php">Pide otro</a>.', $hours) . "</p>"; page_footer(); exit;
}
$pdo->prepare("UPDATE users SET verified = 1, token = NULL, token_sent = NULL WHERE user_id = ?")->execute([$uid]);
session_arranca();   // misma duracion que en login.php (session_days), no hasta cerrar el navegador
session_regenerate_id(true);
$_SESSION['uid'] = $uid;
redirect('/?bienvenida=1');
