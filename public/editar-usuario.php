<?php
/** /editar-usuario.php?id=N — edición del perfil de otro usuario por moderadores y admin:
 *  nick, frase y foto (mods y admin); correo y estado de activación (solo admin). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/imagen.php';
if (!user()) redirect('/login.php');
if (!is_mod()) { http_response_code(403); page_header('Editar usuario'); echo "<p class=\"end\">Solo moderadores.</p>"; page_footer(); exit; }

$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT user_id, login, email, level, aura, avatar, bio, verified, created FROM users WHERE user_id = ?");
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); page_header('Editar usuario'); echo "<p class=\"end\">Ese usuario no existe.</p>"; page_footer(); exit; }
// un moderador no toca a admins ni a otros moderadores; el admin puede con todos
if (!is_admin() && $p['level'] !== 'normal') { http_response_code(403); page_header('Editar usuario'); echo "<p class=\"end\">Solo el admin puede editar a moderadores y admins.</p>"; page_footer(); exit; }

$err = null; $ok = isset($_GET['ok']) ? 'Perfil guardado.' : null;

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
    }
    if (!$err) {
        $pdo->prepare("UPDATE users SET login = ?, bio = ?, avatar = ?, email = ?, verified = ? WHERE user_id = ?")
            ->execute([$login, $bio ?: null, $avatar, $email, $verified, $id]);
        redirect("/editar-usuario.php?id=$id&ok=1");
    }
    $p = array_merge($p, ['login' => $login, 'bio' => $bio, 'avatar' => $avatar, 'email' => $email, 'verified' => $verified]);
}

page_header('Editar usuario');
echo "<div class=\"profile\">" . avatar((float)$p['aura'], $p['login'], $p['avatar']) . "<div><h1>" . h($p['login']) . "</h1>";
echo "<p class=\"meta\">#{$p['user_id']} · " . h($p['level']) . " · aura " . number_format((float)$p['aura'], 1, ',', '') . " · desde " . date('M Y', strtotime($p['created'])) . " · <a href=\"/u.php?u=" . rawurlencode($p['login']) . "\">perfil público</a>" . (is_admin() ? " · <a href=\"/admin.php\">admin</a>" : "") . "</p></div></div>";
echo "<p class=\"notice\">Edición de moderación del perfil de otro usuario. Los cambios se ven al momento; el usuario no recibe aviso.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($ok)  echo "<p class=\"notice\">" . h($ok) . "</p>";
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "<label>Nombre de usuario</label><input type=\"text\" name=\"login\" required pattern=\"[A-Za-z0-9_\\-]{3,32}\" value=\"" . h($p['login']) . "\" autocomplete=\"off\">";
echo "<label>Frase (hasta 160 caracteres)</label><input type=\"text\" name=\"bio\" maxlength=\"160\" value=\"" . h($p['bio'] ?? '') . "\">";
echo "<label>Foto</label><input type=\"file\" name=\"photo\" accept=\"image/*\">";
if ($p['avatar']) echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"> quitar la foto actual</label>";
if (is_admin()) {
    echo "<label>Correo</label><input type=\"email\" name=\"email\" required value=\"" . h($p['email']) . "\" autocomplete=\"off\">";
    echo "<label><input type=\"checkbox\" name=\"verified\" value=\"1\"" . ((int)$p['verified'] ? ' checked' : '') . "> correo validado (sin marcar, no puede iniciar sesión)</label>";
} else {
    echo "<label>Correo</label><p class=\"meta\" style=\"padding:0\">" . h($p['email']) . " <small>(solo lo cambia el admin)</small></p>";
}
echo "<button>Guardar</button></form>";
page_footer();
