<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

// Aterrizaje: si el usuario eligio un tema o comunidad como entrada, se le lleva alli cuando llega
// desde fuera (sin referer del propio sitio). Navegando por dentro, Portada es Portada.
if (user() && (user()['landing'] ?? '') !== '' && empty($_GET) && !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', (string)($_SERVER['HTTP_REFERER'] ?? ''))) {
    $to = landing_url($pdo, user());
    if ($to !== '/') redirect($to);
}
$page = page_num();
$total = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE status='destacadas'")->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.status='destacadas' ORDER BY s.published_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header('Portada', 'portada');
if (isset($_GET['bienvenida']) && user()) {
    echo "<p class=\"notice\">Bienvenido, <b>" . h(user()['login']) . "</b>: tu cuenta está creada y ya has entrado. Publicas en modo Ghost (alias durante " . (int)cfg('ghost_minutes', 30) . " min) y puedes votar. En <a href=\"/perfil.php\">tu perfil</a> puedes poner foto, una frase y elegir tema claro u oscuro.</p>";
}
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, "full"); $n++; }
if ($n === 0) echo "<p class=\"end\">Nada en portada todavía. Las candidatas con mayor delta se publican cada ciclo.</p>";
paginate('/', $page, $total);
page_footer();
