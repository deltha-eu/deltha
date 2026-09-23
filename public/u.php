<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/usuarios.php';

$login = trim((string)($_GET['u'] ?? ''));
$st = $pdo->prepare("SELECT user_id, login, level, aura, avatar, bio, created, last_seen FROM users WHERE login = ?");
$st->execute([$login]);
$p = $st->fetch();
if (!$p) { http_response_code(404); page_header(t('No existe')); echo "<p class=\"end\">" . t('No existe ese usuario.') . "</p>"; page_footer(); exit; }
$own = user() && (int)user()['user_id'] === (int)$p['user_id'];

page_header($p['login']);
echo "<div class=\"profile\">" . avatar((float)$p['aura'], $p['login'], $p['avatar']) . "<div><h1>" . h($p['login']) . "</h1>";
if ($p['bio']) echo "<p class=\"bio\">" . h($p['bio']) . "</p>";
$nPub = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE user_id = {$p['user_id']} AND status NOT IN ('rejected','discard') AND (revealed = 1 OR sent_date <= NOW() - INTERVAL " . (int)cfg('ghost_minutes', 30) . " MINUTE)")->fetchColumn();
echo "<p class=\"meta\">" . t('aura') . " " . number_format((float)$p['aura'], 1, ',', '') . " · " . t('desde') . " " . date('M Y', strtotime($p['created'])) . ($p['last_seen'] ? " · " . h(t('último acceso %s', hace($p['last_seen']))) : '') . " · " . t('%d envíos revelados', $nPub);
if ($own) echo " · " . t('<a href="/perfil.php">editar</a>') . "";
if (!$own && user() && (string)cfg('msg_enabled', '1') === '1') echo " · <a href=\"/mensajes.php?u=" . rawurlencode($p['login']) . "\">" . t('enviar mensaje') . "</a>";
$canMod = modedit_puede($p);   // moderadores: usuarios normales; admin: cualquiera (lib/usuarios.php)
if ($canMod) echo " · <a href=\"#modedit\" onclick=\"document.getElementById('modedit').open = true\">" . t('editar perfil') . "</a>";
echo "</p></div></div>";
// moderadores y admin: edicion del perfil aqui mismo, sin pasar por moderacion ni admin
if ($canMod) {
    echo "<details class=\"editbox\" id=\"modedit\"" . (isset($_GET['ok']) || isset($_GET['err']) ? ' open' : '') . "><summary>Editar este perfil (" . (is_admin() ? 'admin' : 'moderación') . ")</summary>";
    if (isset($_GET['ok'])) echo "<p class=\"notice\">" . (($_GET['ok'] ?? '') === '2' ? 'Perfil guardado y contraseña cambiada.' : 'Perfil guardado.') . "</p>";
    if (isset($_GET['err'])) echo "<p class=\"error\">" . h((string)$_GET['err']) . "</p>";
    $ex = $pdo->prepare("SELECT user_id, login, email, level, aura, avatar, bio, verified FROM users WHERE user_id = ?"); $ex->execute([$p['user_id']]);
    echo modedit_form($pdo, $ex->fetch(), 'u');
    echo "</details>";
}

// Solo lo revelado es publico; el dueño ve ademas sus hilos vivos
$gm = (int)cfg('ghost_minutes', 30);
$where = $own ? "s.user_id = ? AND s.status NOT IN ('rejected','discard')" : "s.user_id = ? AND (s.revealed = 1 OR s.sent_date <= NOW() - INTERVAL $gm MINUTE) AND s.status NOT IN ('rejected','discard')";
$q = $pdo->prepare(story_query_base() . " WHERE $where ORDER BY s.sent_date DESC LIMIT 20");
$q->execute([$p['user_id']]);
$n = 0;
foreach ($q as $s) {
    if ($own && !(int)$s['revealed'] && !ghost_over($s['sent_date'])) echo "<p class=\"notice\" style=\"margin-bottom:0\">" . t('Vivo, solo lo ves tú (alias ') . "" . h($s['author_alias']) . "):</p>";
    story_post($pdo, $s, 'compact'); $n++;
}
if ($n === 0) echo "<p class=\"end\">" . ($own ? t('Aún no has enviado nada.') : t('Nada revelado todavía: sus hilos siguen vivos o no ha enviado.')) . "</p>";
page_footer();
