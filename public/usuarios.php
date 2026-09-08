<?php
/** /usuarios.php — los ultimos 20 registrados, del mas reciente al mas antiguo (solo para registrados) */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
if (!user()) redirect('/login.php');

$rows = $pdo->query("SELECT login, aura, avatar, bio, created FROM users WHERE verified = 1 ORDER BY created DESC, user_id DESC LIMIT 20")->fetchAll();
$total = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE verified = 1")->fetchColumn();

page_header('Usuarios', 'usuarios');
echo "<h1>Últimos registros</h1><p class=\"notice\">Los 20 más recientes de $total. Pulsa en un nombre para ver su perfil público.</p>";
foreach ($rows as $r) {
    echo "<div class=\"ulist\">" . avatar((float)$r['aura'], $r['login'], $r['avatar'], '/u.php?u=' . rawurlencode($r['login']));
    echo "<div><b><a href=\"/u.php?u=" . rawurlencode($r['login']) . "\">" . h($r['login']) . "</a></b>";
    echo "<span class=\"meta\"> · aura " . number_format((float)$r['aura'], 1, ',', '') . " · " . h(hace($r['created'])) . "</span>";
    if ($r['bio']) echo "<p class=\"bio\">" . h($r['bio']) . "</p>";
    echo "</div></div>";
}
page_footer();
