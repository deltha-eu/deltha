<?php
/** /recuperar — pide un enlace para cambiar la contraseña (migra-129). Usuario o correo; la respuesta es la misma exista o no la
 *  cuenta, para no revelar correos. Una vez cada 10 min por cuenta. Solo cuentas activadas (una pendiente de activar tiene /reenviar).
 *  El enlace lleva a /nueva-clave y vale reset_hours (2). Visible sin clave. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/mail.php';
if (user()) redirect('/perfil');
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $who = mb_strtolower(post('who'));
    $st = $pdo->prepare("SELECT user_id, email, verified, reset_sent FROM users WHERE login = ? OR email = ?");
    $st->execute([$who, $who]);
    $u = $st->fetch();
    $msg = t('Si esa cuenta existe, acabamos de enviarle un correo con un enlace para cambiar la contraseña. Mira también en la carpeta de spam.');
    if ($u && (int)$u['verified'] === 1 && (!$u['reset_sent'] || strtotime($u['reset_sent']) < time() - 600)) {
        try { send_reset($pdo, (int)$u['user_id'], $u['email']); } catch (Throwable $e) { error_log('recuperar: ' . $e->getMessage()); }
    }
}
header('Cache-Control: no-store');
page_header(t('Recuperar contraseña'));
if ($msg) echo "<p class=\"notice\">" . h($msg) . "</p>";
else echo "<p class=\"notice\">" . t('Dinos tu usuario o tu correo y te enviaremos un enlace para elegir una contraseña nueva.') . "</p>";
echo "<form class=\"plain\" method=\"post\">" . t('<label>Usuario o correo</label>') . "<input type=\"text\" name=\"who\" required autocomplete=\"username\"><button>" . t('Enviar el enlace') . "</button></form>";
echo "<p class=\"end\">" . t('El enlace vale %d horas y se puede pedir otro cada 10 minutos.', (int)cfg('reset_hours', 2)) . " <a href=\"/login\">" . t('Volver a entrar') . "</a></p>";
page_footer();
