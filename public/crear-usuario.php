<?php
/** /crear-usuario — alta de una cuenta por el admin (POST desde el formulario de /usuarios o de admin > Usuarios; lib/usuarios.php).
 *  Bien: lleva al perfil nuevo (/u, con su ficha abierta). Mal: vuelve al formulario con el error y lo escrito (menos la contraseña). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/usuarios.php';
if (!is_admin()) { http_response_code(403); exit('Solo admin'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/usuarios#nuevo-usuario');

$login = post('login'); $email = post('email');
$aura = trim(post('aura')) !== '' && is_numeric(str_replace(',', '.', post('aura'))) ? (float)str_replace(',', '.', post('aura')) : null;
$verified = post('verified') === '1';
[$uid, $err] = user_create($pdo, $login, $email, (string)($_POST['pass'] ?? ''), post('level'), $aura, $verified);
$back = post('back') === 'admin' ? '/admin?t=usuarios' : '/usuarios';
if ($err) redirect($back . (str_contains($back, '?') ? '&' : '?') . 'nuevo_err=' . rawurlencode($err) . '&nl=' . rawurlencode($login) . '&ne=' . rawurlencode($email) . '#nuevo-usuario');
if (!$verified) { require __DIR__ . '/../lib/mail.php'; send_activation($pdo, $uid, mb_strtolower(trim($email))); }
redirect('/u/' . rawurlencode(trim($login)) . '?ok=creado#modedit');
