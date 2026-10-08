<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

// Aterrizaje: si el usuario eligio un tema o comunidad como entrada, se le lleva alli cuando llega
// desde fuera (sin referer del propio sitio). Navegando por dentro, las portadas son las portadas.
if (user() && (user()['landing'] ?? '') !== '' && empty($_GET) && !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', (string)($_SERVER['HTTP_REFERER'] ?? ''))) {
    $to = landing_url($pdo, user());
    if ($to !== '/') redirect($to);
}
// Portadas: Medios, Substacks y Canales (YouTube): /?portada=medios | substacks | canales. Sin ?portada= se abre la ultima que se eligio (cookie ddd_portada), o Substacks.
$p = portada_actual();
$page = page_num();
$ml = stories_mylang_sql();   // «solo en mi idioma»: fuera los envios en otro idioma (migra-092)
// destacadas (migra-111): las primeras en la pagina 1, con halo; mientras lo esten no salen tambien en su sitio por fecha (al quitarlas vuelven a el)
$pins = pins_stories($pdo, $p);
$np = $pins ? " AND s.story_id NOT IN (" . implode(',', array_map(fn($x) => (int)$x['story_id'], $pins)) . ")" : '';
$cnt = $pdo->prepare("SELECT COUNT(*) FROM stories s WHERE s.status='destacadas' AND s.portada = ?$ml$np");
$cnt->execute([$p]);
$total = (int)$cnt->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.status='destacadas' AND s.portada = ?$ml$np ORDER BY s.published_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, $p);
$st->bindValue(2, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(3, page_offset($page), PDO::PARAM_INT);
$st->execute();

// autodescubrimiento del RSS de cada portada (/rss?portada=…), la actual primero: los lectores de feeds lo cogen de aqui
$rss = '';
foreach (array_unique(array_merge([$p], array_keys(portadas()))) as $k) $rss .= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"" . h(cfg('site_name', 'Deltha') . ' · ' . t(DDD_PORTADAS[$k])) . "\" href=\"/rss?portada=$k\">";
page_header(t(DDD_PORTADAS[$p]), $p, $rss);
if (isset($_GET['baja'])) echo "<p class=\"notice\">" . t('Tu cuenta se ha borrado. Gracias por haber estado aquí.') . "</p>";   // viene de /baja
if (isset($_GET['bienvenida']) && user()) {
    echo "<p class=\"notice\">" . t('Bienvenido, <b>') . "" . h(user()['login']) . "</b>: " . t('tu cuenta está creada y ya has entrado. Publicas en modo Ghost (alias durante %d min) y puedes votar. En <a href="/perfil">tu perfil</a> puedes poner foto, una frase y elegir tema claro u oscuro.', (int)cfg('ghost_minutes', 30)) . "</p>";
}
$n = 0;
pin_halo_primera($page === 1 && !$pins);   // sin destacadas: la primera tarjeta lleva el halo igualmente
if ($page === 1) foreach ($pins as $s) { story_post($pdo, $s, "full"); $n++; }   // destacadas, las primeras
foreach ($st as $s) { story_post($pdo, $s, "full"); $n++; }
pin_halo_primera(false);
if ($n === 0) echo "<p class=\"end\">" . ($p === 'canales' ? t('Nada en Canales todavía. Aquí se publica el vídeo del radar con mayor δ cuando llega al mínimo.') : ($p === 'substacks'
    ? t('Nada en Substacks todavía. Cada dos ciclos se publica aquí el Substack del radar con mayor δ, si llega al mínimo.')
    : t('Nada en Medios todavía. Cada dos ciclos se publica aquí la noticia del radar con mayor δ, si llega al mínimo.'))) . "</p>";
paginate('/?portada=' . $p, $page, $total);
page_footer();
