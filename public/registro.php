<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/mail.php';
if (user()) redirect('/');
$err = null; $done = null; $mot = null;   // $mot: motivo del rechazo, para el historico (reg_log)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    users_purge_unverified($pdo);   // antes de comprobar si el usuario o el correo existen: las cuentas nunca activadas los liberan
    $login = post('login'); $email = mb_strtolower(post('email')); $pass = post('pass');
    // limite por IP (huella HMAC, sin guardar la IP)
    $fp = substr(hash_hmac('sha256', 'reg|' . client_ip(), $DDD_SECRET), 0, 16);
    $pdo->exec("DELETE FROM reg_attempts WHERE created < NOW() - INTERVAL 1 DAY");
    $n = $pdo->prepare("SELECT COUNT(*) FROM reg_attempts WHERE fp = ? AND created >= NOW() - INTERVAL 1 HOUR"); $n->execute([$fp]);

    if ((int)$n->fetchColumn() >= (int)cfg('reg_per_ip_hour', 3)) { $err = t('Demasiados registros desde esta conexión. Prueba más tarde.'); $mot = 'tope de registros por conexión'; }
    elseif (!preg_match('/^[a-z0-9_\-]{3,32}$/i', $login)) { $err = t('Usuario: 3–32 caracteres, letras, números, guion o guion bajo.'); $mot = 'nombre de usuario no válido'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $err = t('Correo no válido.'); $mot = 'correo no válido'; }
    elseif ($r = email_rejected($pdo, $email)) { $err = $r; $mot = 'dominio de correo rechazado'; $pdo->prepare("INSERT INTO reg_attempts (fp) VALUES (?)")->execute([$fp]); }   // cuenta para el tope por hora: cada intento hace peticiones a la web del dominio
    elseif (strlen($pass) < 8) { $err = t('Contraseña de al menos 8 caracteres.'); $mot = 'contraseña corta'; }
    elseif ($pass !== (string)($_POST['pass2'] ?? '')) { $err = t('Las dos contraseñas no coinciden.'); $mot = 'las contraseñas no coinciden'; }
    else {
        try {
            $pdo->prepare("INSERT INTO users (login, email, pass, verified, aura) VALUES (?,?,?,0,?)")
                ->execute([$login, $email, password_hash($pass, PASSWORD_DEFAULT), (float)cfg('aura_start', 5)]);
            $uid = (int)$pdo->lastInsertId();
            foros_seguir_por_defecto($pdo, $uid);   // el foro Deltha, seguido de salida (migra-170)
            $pdo->prepare("INSERT INTO reg_attempts (fp) VALUES (?)")->execute([$fp]);
            $enviado = send_activation($pdo, $uid, $email);
            reg_log_add($pdo, $login, $email, 'pendiente', $enviado ? '' : 'no se pudo enviar el correo de activación', $uid);
            if ($enviado) $done = $email;
            else $err = t('No se ha podido enviar el correo de activación. Inténtalo de nuevo en unos minutos o escríbenos.');
        } catch (PDOException $e) { $err = t('Ese usuario o correo ya existe.'); $mot = 'usuario o correo ya existe'; }
    }
    if ($mot !== null) reg_log_add($pdo, (string)$login, (string)$email, 'rechazado', $mot);   // historico permanente (admin -> Procesos -> Registros)
}

page_header(t('Crear cuenta'));
if ($done) {
    echo "" . t('<h1>Revisa tu correo</h1>') . "";
    echo "<p class=\"notice\">" . t('Hemos enviado un enlace de activación a <b>') . "" . h($done) . "" . t('</b>. Ábrelo para terminar: la cuenta no funciona hasta entonces. Si no lo ves, mira en la carpeta de spam.') . "</p>";
    echo "<p class=\"end\">" . t('¿No llega? <a href="/reenviar">Pedir otro enlace</a>.') . "</p>";
    page_footer(); exit;
}
echo "<p class=\"notice\">" . t('Con cuenta publicas en modo Ghost: cada envío o comentario sale con un alias y tu nombre aparece a los %d minutos. Puedes votar y tu aura crece con lo que aportas. Te enviaremos un correo para activar la cuenta.', (int)cfg('ghost_minutes', 30)) . "</p>";
if ($err) echo "<p class=\"error\">" . $err . "</p>";   // sin h(): todos los $err son textos fijos del sitio (el de email temporal lleva un enlace)
echo "<form class=\"plain\" method=\"post\">" . t('<label>Usuario</label>') . "<input type=\"text\" name=\"login\" required value=\"" . h(post('login')) . "\">";
echo "" . t('<label>Correo</label>') . "<input type=\"email\" name=\"email\" required value=\"" . h(post('email')) . "\">";
echo "" . t('<label>Contraseña (mínimo 8 caracteres)</label>') . "<input type=\"password\" name=\"pass\" data-pw required minlength=\"8\" autocomplete=\"new-password\">";
echo "" . t('<label>Repite la contraseña</label>') . "<input type=\"password\" name=\"pass2\" data-pw required minlength=\"8\" autocomplete=\"new-password\">";
echo pw_toggle() . "" . t('<button>Crear</button>') . "</form>";
page_footer();
