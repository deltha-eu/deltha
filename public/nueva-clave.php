<?php
/** /nueva-clave?u=ID&t=TOKEN — elegir contraseña nueva desde el enlace de /recuperar (migra-129). Comprueba el token (una vez, reset_hours),
 *  pide la contraseña dos veces, la guarda, borra el token y abre sesion. Visible sin clave. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$uid = (int)($_GET['u'] ?? post('u')); $t = (string)($_GET['t'] ?? post('t'));
$st = $pdo->prepare("SELECT user_id, login, reset_token, reset_sent FROM users WHERE user_id = ?");
$st->execute([$uid]);
$u = $st->fetch();
$hours = (int)cfg('reset_hours', 2);
header('Cache-Control: no-store');
page_header(t('Nueva contraseña'));
$mal = !$u || !$u['reset_token'] || !preg_match('/^[a-f0-9]{32}$/', $t) || !hash_equals((string)$u['reset_token'], $t);
if ($mal) { echo "<p class=\"error\">" . t('El enlace no es válido o ya se ha usado.') . " <a href=\"/recuperar\">" . t('Pide otro') . "</a>.</p>"; page_footer(); exit; }
if (strtotime((string)$u['reset_sent']) < time() - $hours * 3600) { echo "<p class=\"error\">" . t('El enlace ha caducado: valía %d horas.', $hours) . " <a href=\"/recuperar\">" . t('Pide otro') . "</a>.</p>"; page_footer(); exit; }
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = (string)($_POST['pass'] ?? '');
    if (strlen($pass) < 8) $err = t('Contraseña de al menos 8 caracteres.');
    elseif ($pass !== (string)($_POST['pass2'] ?? '')) $err = t('Las dos contraseñas no coinciden.');
    else {
        $pdo->prepare("UPDATE users SET pass = ?, reset_token = NULL, reset_sent = NULL WHERE user_id = ?")->execute([password_hash($pass, PASSWORD_DEFAULT), $uid]);
        session_arranca();
        session_regenerate_id(true);
        $_SESSION = ['uid' => $uid];
        redirect('/perfil?clave=1');
    }
}
if ($err) echo "<p class=\"error\" role=\"alert\">" . h($err) . "</p>";
echo "<p class=\"notice\">" . t('Elige una contraseña nueva para %s.', '<b>' . h($u['login']) . '</b>') . "</p>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"u\" value=\"$uid\"><input type=\"hidden\" name=\"t\" value=\"" . h($t) . "\">";
echo "" . t('<label>Contraseña (mínimo 8 caracteres)</label>') . "<input type=\"password\" name=\"pass\" required minlength=\"8\" autocomplete=\"new-password\" autofocus>";
echo "" . t('<label>Repite la contraseña</label>') . "<input type=\"password\" name=\"pass2\" required minlength=\"8\" autocomplete=\"new-password\">";
echo "<button>" . t('Guardar la contraseña') . "</button></form>";
page_footer();
