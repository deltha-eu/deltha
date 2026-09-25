<?php
define('DDD_PUBLIC', true);   // los registrados entran sin clave
require __DIR__ . '/../lib/app.php';
if (user()) redirect('/');
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare("SELECT user_id, pass, verified FROM users WHERE login = ?");
    $st->execute([post('login')]);
    $row = $st->fetch();
    if ($row && password_verify(post('pass'), $row['pass']) && (int)$row['verified'] === 0) {
        $err = t('Esa cuenta aún no está activada: abre el enlace del correo que te enviamos o <a href="/reenviar.php">pide otro</a>.');
    } elseif ($row && password_verify(post('pass'), $row['pass'])) {
        session_arranca();   // dura session_days (lib/app.php), no hasta cerrar el navegador
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$row['user_id'];
        if (($_COOKIE['ddd_srclang'] ?? '') === '1') {   // marco «Solo fuentes en mi idioma» antes de entrar: pasa a su cuenta
            $pdo->prepare("UPDATE users SET src_mylang = 1 WHERE user_id = ?")->execute([(int)$row['user_id']]);
            pref_cookie('ddd_srclang', '');
        }
        $lu = $pdo->prepare("SELECT landing FROM users WHERE user_id = ?"); $lu->execute([(int)$row['user_id']]);
        redirect(landing_url($pdo, ['landing' => (string)$lu->fetchColumn()]));
    }
    if (!$err) $err = t('Usuario o contraseña incorrectos.');
}
page_header(t('Entrar'));
echo "" . t('<p class="notice">Al entrar se crea una única cookie técnica de sesión. Sin cuenta, ninguna.</p>') . "";   // sin <h1>: el titulo ya va en la cabecera
if ($err) echo "<p class=\"error\">$err</p>";
echo "<form class=\"plain\" method=\"post\">" . t('<label>Usuario</label>') . "<input type=\"text\" name=\"login\" required autocomplete=\"username\">";
echo "" . t('<label>Contraseña</label>') . "<input type=\"password\" name=\"pass\" required autocomplete=\"current-password\">" . t('<button>Entrar</button>') . "</form>";
echo "<p><a href=\"/registro.php\">" . t('Crear cuenta') . "</a></p>";
page_footer();
