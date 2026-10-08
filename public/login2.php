<?php
/** /login2 — segundo paso de la entrada: el codigo de seis cifras enviado por correo (lib/twofa.php, migra-095).
 *  Solo se llega aqui desde /login con la contraseña ya acertada (la sesion lleva pending_uid, todavia no uid).
 *  ?otro=1 envia otro codigo (como mucho uno por minuto y TWOFA_SENDS por hora). Visible sin clave de acceso privado. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/twofa.php';
if (user()) redirect('/');
$uid = (int)($_SESSION['pending_uid'] ?? 0);
if ($uid <= 0 || time() - (int)($_SESSION['pending_at'] ?? 0) > 3600 || !($row = twofa_row($pdo, $uid))) redirect('/login');
$err = null; $info = null;
$min = max(2, (int)cfg('twofa_minutes', 10));
$gone = t('El código ha caducado o se han agotado los intentos: <a href="/login2?otro=1">pide otro</a>.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = twofa_check($pdo, $uid, post('code'));
    if ($r === 'ok') login_finish($pdo, $uid);
    $err = $r === 'bad' ? t('Código incorrecto.') : $gone;
} elseif (isset($_GET['otro'])) {
    $r = twofa_send($pdo, $uid, (string)$row['email']);
    if ($r === 'ok') $info = t('Te hemos enviado otro código.');
    elseif ($r === 'wait') $info = t('Acabamos de enviarte uno: espera un minuto antes de pedir otro.');
    else $err = t('No hemos podido enviarte el código. Inténtalo de nuevo más tarde.');
}
header('Cache-Control: no-store');
page_header(t('Código de acceso'));
echo "<p class=\"notice\">" . t('Te hemos enviado un código de seis cifras a %s. Caduca en %d minutos; mira también en la carpeta de correo no deseado.', '<b>' . h(twofa_mask((string)$row['email'])) . '</b>', $min) . "</p>";
if ($info) echo "<p class=\"notice\">" . h($info) . "</p>";
if ($err) echo "<p class=\"error\">$err</p>";
echo "<form class=\"plain\" method=\"post\">" . t('<label>Código</label>') . "<input type=\"text\" name=\"code\" required inputmode=\"numeric\" pattern=\"[0-9 ]{6,8}\" maxlength=\"8\" autocomplete=\"one-time-code\" autofocus>" . t('<button>Entrar</button>') . "</form>";
echo "<p><a href=\"/login2?otro=1\">" . t('Enviar otro código') . "</a> · <a href=\"/salir\">" . t('cancelar') . "</a></p>";
page_footer();
