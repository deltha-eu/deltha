<?php
define('DDD_PUBLIC', true);   // los registrados entran sin clave
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/twofa.php';
if (user()) redirect('/');
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare("SELECT user_id, pass, verified FROM users WHERE login = ?");
    $st->execute([post('login')]);
    $row = $st->fetch();
    if ($row && password_verify(post('pass'), $row['pass']) && (int)$row['verified'] === 0) {
        $err = t('Esa cuenta aún no está activada: abre el enlace del correo que te enviamos o <a href="/reenviar">pide otro</a>.');
    } elseif ($row && password_verify(post('pass'), $row['pass'])) {
        $uid = (int)$row['user_id'];
        $r2 = twofa_row($pdo, $uid);
        if (!twofa_needed($r2)) login_finish($pdo, $uid);
        // verificacion en dos pasos: la contraseña es buena, pero no entra hasta escribir el codigo que va a su correo (/login2)
        $env = twofa_send($pdo, $uid, (string)$r2['email']);
        if ($env === 'ok' || $env === 'wait') {
            session_arranca();
            session_regenerate_id(true);
            $_SESSION['pending_uid'] = $uid; $_SESSION['pending_at'] = time();
            redirect('/login2');
        }
        $err = t('No hemos podido enviarte el código. Inténtalo de nuevo más tarde.');
    }
    if (!$err) $err = t('Usuario o contraseña incorrectos.');
}
page_header(t('Entrar'));
echo "" . t('<p class="notice">Al entrar se crea una única cookie técnica de sesión. Sin cuenta, ninguna.</p>') . "";   // sin <h1>: el titulo ya va en la cabecera
if ($err) echo "<p class=\"error\" role=\"alert\">$err</p>";
echo "<form class=\"plain\" method=\"post\">" . t('<label>Usuario</label>') . "<input type=\"text\" name=\"login\" required autocomplete=\"username\"" . ($err ? " autofocus" : "") . ">";
echo "" . t('<label>Contraseña</label>') . "<input type=\"password\" name=\"pass\" required autocomplete=\"current-password\">" . t('<button>Entrar</button>') . "</form>";
echo "<p><a href=\"/registro\">" . t('Crear cuenta') . "</a></p>";
page_footer();
