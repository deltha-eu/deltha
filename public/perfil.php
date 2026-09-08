<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/imagen.php';
$u = user();
if (!$u) redirect('/login.php');
$err = null; $ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = mb_substr(post('bio'), 0, 160);
    $login = post('login') ?: $u['login'];
    if ($login !== $u['login']) {
        if (!preg_match('/^[a-z0-9_\-]{3,32}$/i', $login)) $err = 'Usuario: 3–32 caracteres, letras, números, guion o guion bajo.';
        else {
            $q = $pdo->prepare("SELECT 1 FROM users WHERE login = ? AND user_id <> ?"); $q->execute([$login, $u['user_id']]);
            if ($q->fetchColumn()) $err = 'Ese nombre de usuario ya está cogido.';
        }
    }
    $theme = in_array(post('theme'), ['auto', 'light', 'dark'], true) ? post('theme') : 'auto';
    $avatar = $u['avatar'];
    if (!empty($_FILES['photo']['name'])) {
        $new = avatar_desde_subida($_FILES['photo']);
        if (!$new) $err = 'No se pudo procesar la foto (mínimo 64×64, máximo 6 MB).';
        else { if ($avatar) @unlink(DDD_MEDIA_DIR . '/avatars/' . $avatar); $avatar = $new; }
    }
    if (post('quitar') === '1' && $avatar) { @unlink(DDD_MEDIA_DIR . '/avatars/' . $avatar); $avatar = null; }
    if (!$err) {
        $pdo->prepare("UPDATE users SET login = ?, bio = ?, avatar = ?, theme = ? WHERE user_id = ?")->execute([$login, $bio ?: null, $avatar, $theme, $u['user_id']]);
        redirect('/perfil.php?ok=1');
    }
}
if (isset($_GET['ok'])) $ok = 'Perfil guardado.';

page_header('Perfil');
echo "<div class=\"profile\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<div><h1>" . h($u['login']) . "</h1>";
if ($u['bio']) echo "<p class=\"bio\">" . h($u['bio']) . "</p>";
echo "<p class=\"meta\">aura " . number_format((float)$u['aura'], 1, ',', '') . " · <a href=\"/u.php?u=" . rawurlencode($u['login']) . "\">ver perfil público</a> · <a href=\"/usuarios.php\">usuarios</a>" . (is_mod() ? " · <a href=\"/moderacion.php\">moderación</a>" : "") . (is_admin() ? " · <a href=\"/admin.php\">admin</a>" : "") . " · <a href=\"/salir.php\">salir</a></p></div></div>";
echo "<p class=\"notice\">Tu foto y tu frase se ven en tu perfil y junto a tu nombre cuando se revela, a los " . (int)cfg('ghost_minutes', 30) . " minutos de cada aportación. Hasta entonces eres un alias con halo.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($ok)  echo "<p class=\"notice\">" . h($ok) . "</p>";
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\">";
echo "<label>Nombre de usuario</label><input type=\"text\" name=\"login\" required pattern=\"[A-Za-z0-9_\\-]{3,32}\" value=\"" . h($u['login']) . "\" autocomplete=\"username\">";
echo "<label>Frase (hasta 160 caracteres)</label><input type=\"text\" name=\"bio\" maxlength=\"160\" value=\"" . h($u['bio'] ?? '') . "\">";
echo "<label>Foto</label><input type=\"file\" name=\"photo\" accept=\"image/*\">";
if ($u['avatar']) echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"> quitar la foto actual</label>";
echo "<label>Tema</label><select name=\"theme\">";
foreach (['auto' => 'Automático (como el sistema)', 'light' => 'Claro', 'dark' => 'Oscuro'] as $k => $v) {
    echo "<option value=\"$k\"" . (($u['theme'] ?? 'auto') === $k ? ' selected' : '') . ">" . h($v) . "</option>";
}
echo "</select>";
echo "<button>Guardar</button></form>";
page_footer();
