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
elseif (is_mod() && (is_admin() || $p['level'] === 'normal')) echo " · <a href=\"/editar-usuario.php?id={$p['user_id']}\">editar perfil</a>";
echo "</p></div></div>";

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
