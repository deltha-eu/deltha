<?php
/** /editar-usuario.php?id=N — edición del perfil de otro usuario por moderadores y admin:
 *  nick, frase y foto (mods y admin); correo, estado de activación, nueva contraseña, nivel (normal/mod/admin) y aura (solo admin).
 *  El formulario (lib/usuarios.php, modedit_form) es el mismo que sale en el perfil público (/u.php#modedit); con back=u se vuelve allí. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/imagen.php';
require __DIR__ . '/../lib/usuarios.php';
if (!user()) redirect('/login.php');
if (!is_mod()) { http_response_code(403); page_header('Editar usuario'); echo "<p class=\"end\">Solo moderadores.</p>"; page_footer(); exit; }

$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT user_id, login, email, level, aura, avatar, bio, verified, created FROM users WHERE user_id = ?");
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); page_header('Editar usuario'); echo "<p class=\"end\">Ese usuario no existe.</p>"; page_footer(); exit; }
// un moderador no toca a admins ni a otros moderadores (tampoco a quien modera por aura, ni a si mismo: eso va en /perfil.php); el admin puede con todos
if (!modedit_puede($p)) { http_response_code(403); page_header('Editar usuario'); echo "<p class=\"end\">Solo el admin puede editar a moderadores y admins.</p>"; page_footer(); exit; }

$err = null; $ok = ($_GET['ok'] ?? '') === '2' ? 'Perfil guardado y contraseña cambiada.' : (isset($_GET['ok']) ? 'Perfil guardado.' : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = post('login') ?: $p['login'];
    $bio   = mb_substr(post('bio'), 0, 160);
    if ($login !== $p['login']) {
        if (!preg_match('/^[a-z0-9_\-]{3,32}$/i', $login)) $err = 'Usuario: 3–32 caracteres, letras, números, guion o guion bajo.';
        else {
            $q = $pdo->prepare("SELECT 1 FROM users WHERE login = ? AND user_id <> ?"); $q->execute([$login, $id]);
            if ($q->fetchColumn()) $err = 'Ese nombre de usuario ya está cogido.';
        }
    }
    $avatar = $p['avatar'];
    if (!empty($_FILES['photo']['name'])) {
        $new = avatar_desde_subida($_FILES['photo']);
        if (!$new) $err = 'No se pudo procesar la foto (mínimo 64×64, máximo 6 MB).';
        else { if ($avatar) @unlink(DDD_MEDIA_DIR . '/avatars/' . $avatar); $avatar = $new; }
    }
    if (post('quitar') === '1' && $avatar) { @unlink(DDD_MEDIA_DIR . '/avatars/' . $avatar); $avatar = null; }

    $email = $p['email']; $verified = (int)$p['verified'];
    if (is_admin()) {
        $email = mb_substr(post('email') ?: $p['email'], 0, 128);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'El correo no es válido.';
        elseif ($email !== $p['email']) {
            $q = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND user_id <> ?"); $q->execute([$email, $id]);
            if ($q->fetchColumn()) $err = 'Ese correo ya lo usa otra cuenta.';
        }
        $verified = post('verified') === '1' ? 1 : 0;
        $newpass = (string)($_POST['pass'] ?? '');
        if ($newpass !== '' && strlen($newpass) < 8) $err = 'La contraseña nueva debe tener al menos 8 caracteres.';
        elseif ($newpass !== '' && $newpass !== (string)($_POST['pass2'] ?? '')) $err = 'Las dos contraseñas no coinciden.';
        $aura = (float)$p['aura'];
        if (isset($_POST['aura']) && trim((string)$_POST['aura']) !== '') {
            $a = str_replace(',', '.', trim((string)$_POST['aura']));
            if (!is_numeric($a) || (float)$a < 0 || (float)$a > (float)cfg('aura_max', 50)) $err = 'El aura debe ser un número entre 0 y ' . num_es(cfg('aura_max', 50)) . '.';
            else $aura = round((float)$a, 3);
        }
        // nivel: normal | mod | admin, cualquiera a cualquiera salvo el propio (para no quedarse sin admin por un descuido)
        $level = (int)user()['user_id'] === $id ? $p['level'] : (in_array(post('level'), ['normal', 'mod', 'admin'], true) ? post('level') : $p['level']);
    }
    if (!$err) {
        $pdo->prepare("UPDATE users SET login = ?, bio = ?, avatar = ?, email = ?, verified = ? WHERE user_id = ?")
            ->execute([$login, $bio ?: null, $avatar, $email, $verified, $id]);
        if (is_admin()) {
            if (abs($aura - (float)$p['aura']) >= 0.0005) {   // aura a mano: al historial, marcado como del admin (no cuenta para el tope diario)
                $pdo->prepare("UPDATE users SET aura = ? WHERE user_id = ?")->execute([$aura, $id]);
                $motivo = mb_substr(trim(post('aura_motivo')), 0, 50);
                $pdo->prepare("INSERT INTO aura_log (user_id, amount, reason, ref_id) VALUES (?,?,?,?)")
                    ->execute([$id, round($aura - (float)$p['aura'], 3), mb_substr('admin' . ($motivo !== '' ? ": $motivo" : ''), 0, 64), (int)user()['user_id']]);
            }
            if ($level !== $p['level']) $pdo->prepare("UPDATE users SET level = ? WHERE user_id = ? AND user_id <> ?")->execute([$level, $id, (int)user()['user_id']]);
        }
        if (is_admin() && agents_on()) {   // seccion «Agente» de la ficha (lib/agente.php): se guarda si la casilla esta marcada o si hay personalidad
            // escrita o algun cupo puesto (para no perder lo escrito por olvidar la casilla); solo se quita si estaba y se desmarca a proposito
            $ag = agent_from_post();
            $quiere = post('ag_is') === '1' || trim($ag['persona']) !== '' || $ag['enabled'] || $ag['submits_per_day'] > 0;
            if ($quiere) agent_save($pdo, $id, $ag);
            elseif (post('ag_was') === '1') agent_delete($pdo, $id);
        }
        if (is_admin() && $newpass !== '') {
            $pdo->prepare("UPDATE users SET pass = ?, token = NULL, token_sent = NULL WHERE user_id = ?")->execute([password_hash($newpass, PASSWORD_DEFAULT), $id]);
            if (post('back') === 'u') redirect('/u.php?u=' . rawurlencode($login) . '&ok=2#modedit');
            redirect("/editar-usuario.php?id=$id&ok=2");
        }
        if (post('back') === 'u') redirect('/u.php?u=' . rawurlencode($login) . '&ok=1#modedit');
        redirect("/editar-usuario.php?id=$id&ok=1");
    }
    if ($err && post('back') === 'u') redirect('/u.php?u=' . rawurlencode($p['login']) . '&err=' . rawurlencode($err) . '#modedit');
    $p = array_merge($p, ['login' => $login, 'bio' => $bio, 'avatar' => $avatar, 'email' => $email, 'verified' => $verified] + (is_admin() ? ['aura' => $aura ?? $p['aura'], 'level' => $level ?? $p['level']] : []));
}

page_header('Editar usuario');
echo "<div class=\"profile\">" . avatar((float)$p['aura'], $p['login'], $p['avatar']) . "<div><h1>" . h($p['login']) . "</h1>";
echo "<p class=\"meta\">#{$p['user_id']} · " . h($p['level']) . " · aura " . number_format((float)$p['aura'], 1, ',', '') . " · desde " . date('M Y', strtotime($p['created'])) . " · <a href=\"/u.php?u=" . rawurlencode($p['login']) . "\">perfil público</a>" . (is_admin() ? " · <a href=\"/admin.php\">admin</a>" : "") . "</p></div></div>";
echo "<p class=\"notice\">Edición de moderación del perfil de otro usuario. Los cambios se ven al momento; el usuario no recibe aviso.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($ok)  echo "<p class=\"notice\">" . h($ok) . "</p>";
echo modedit_form($pdo, $p);
page_footer();
