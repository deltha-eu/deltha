<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$login = trim((string)($_GET['u'] ?? ''));
$st = $pdo->prepare("SELECT user_id, login, level, aura, avatar, bio, created FROM users WHERE login = ?");
$st->execute([$login]);
$p = $st->fetch();
if (!$p) { http_response_code(404); page_header('No existe'); echo "<p class=\"end\">No existe ese usuario.</p>"; page_footer(); exit; }
$own = user() && (int)user()['user_id'] === (int)$p['user_id'];

page_header($p['login']);
echo "<div class=\"profile\">" . avatar((float)$p['aura'], $p['login'], $p['avatar']) . "<div><h1>" . h($p['login']) . "</h1>";
if ($p['bio']) echo "<p class=\"bio\">" . h($p['bio']) . "</p>";
$nPub = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE user_id = {$p['user_id']} AND status NOT IN ('rejected','discard') AND (revealed = 1 OR sent_date <= NOW() - INTERVAL " . (int)cfg('ghost_minutes', 30) . " MINUTE)")->fetchColumn();
echo "<p class=\"meta\">aura " . number_format((float)$p['aura'], 1, ',', '') . " · desde " . date('M Y', strtotime($p['created'])) . " · $nPub envíos revelados";
if ($own) echo " · <a href=\"/perfil.php\">editar</a>";
if (!$own && user() && (string)cfg('msg_enabled', '1') === '1') echo " · <a href=\"/mensajes.php?u=" . rawurlencode($p['login']) . "\">enviar mensaje</a>";
$canMod = !$own && is_mod() && (is_admin() || $p['level'] === 'normal');
if ($canMod) echo " · <a href=\"#modedit\">editar perfil</a>";
echo "</p></div></div>";
// moderadores y admin: edicion del perfil aqui mismo, sin pasar por moderacion ni admin
if ($canMod) {
    echo "<details class=\"editbox\" id=\"modedit\"" . (isset($_GET['ok']) || isset($_GET['err']) ? ' open' : '') . "><summary>Editar este perfil (moderación)</summary>";
    if (isset($_GET['ok'])) echo "<p class=\"notice\">Perfil guardado.</p>";
    if (isset($_GET['err'])) echo "<p class=\"error\">" . h($_GET['err']) . "</p>";
    echo "<form class=\"plain\" method=\"post\" action=\"/editar-usuario.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"id\" value=\"{$p['user_id']}\"><input type=\"hidden\" name=\"back\" value=\"u\">";
    echo "<label>Nombre de usuario</label><input type=\"text\" name=\"login\" required pattern=\"[A-Za-z0-9_\\-]{3,32}\" value=\"" . h($p['login']) . "\" autocomplete=\"off\">";
    echo "<label>Frase</label><input type=\"text\" name=\"bio\" maxlength=\"160\" value=\"" . h($p['bio'] ?? '') . "\">";
    echo "<label>Foto</label><input type=\"file\" name=\"photo\" accept=\"image/*\">";
    if ($p['avatar']) echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"> quitar la foto actual</label>";
    if (is_admin()) {
        $em = $pdo->prepare("SELECT email, verified FROM users WHERE user_id = ?"); $em->execute([$p['user_id']]); $ex = $em->fetch();
        echo "<label>Correo</label><input type=\"email\" name=\"email\" required value=\"" . h($ex['email']) . "\" autocomplete=\"off\">";
        echo "<label><input type=\"checkbox\" name=\"verified\" value=\"1\"" . ((int)$ex['verified'] ? ' checked' : '') . "> correo validado</label>";
        echo "<label>Nueva contraseña (vacío = no cambiar)</label><input type=\"password\" name=\"pass\" minlength=\"8\" autocomplete=\"new-password\">";
        echo "<label>Repite la nueva contraseña</label><input type=\"password\" name=\"pass2\" minlength=\"8\" autocomplete=\"new-password\">";
    }
    echo "<button>Guardar</button></form>";
    if (is_admin()) echo "<p class=\"meta\" style=\"padding:0 16px 12px\">Hacer moderador, validar o borrar la cuenta: <a href=\"/admin.php?t=usuarios&q=" . rawurlencode($p['login']) . "\">en admin</a>.</p>";
    echo "</details>";
}

// Solo lo revelado es publico; el dueño ve ademas sus hilos vivos
$gm = (int)cfg('ghost_minutes', 30);
$where = $own ? "s.user_id = ? AND s.status NOT IN ('rejected','discard')" : "s.user_id = ? AND (s.revealed = 1 OR s.sent_date <= NOW() - INTERVAL $gm MINUTE) AND s.status NOT IN ('rejected','discard')";
$q = $pdo->prepare(story_query_base() . " WHERE $where ORDER BY s.sent_date DESC LIMIT 20");
$q->execute([$p['user_id']]);
$n = 0;
foreach ($q as $s) {
    if ($own && !(int)$s['revealed'] && !ghost_over($s['sent_date'])) echo "<p class=\"notice\" style=\"margin-bottom:0\">Vivo, solo lo ves tú (alias " . h($s['author_alias']) . "):</p>";
    story_post($pdo, $s, 'compact'); $n++;
}
if ($n === 0) echo "<p class=\"end\">" . ($own ? "Aún no has enviado nada." : "Nada revelado todavía: sus hilos siguen vivos o no ha enviado.") . "</p>";
page_footer();
