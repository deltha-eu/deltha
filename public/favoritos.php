<?php
/** /favoritos — pestaña Favoritos del perfil: los envios y articulos que el usuario guardo desde el hilo (privado, solo el dueño). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
$u = user();
if (!$u) redirect('/login');

page_header(t('Favoritos'));
echo "<div class=\"profile\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<div><h1>" . h($u['login']) . "</h1>";
if ($u['bio']) echo "<p class=\"bio\">" . h($u['bio']) . "</p>";
echo "<p class=\"meta\">" . t('aura') . " " . number_format((float)$u['aura'], 1, ',', '') . " · <a href=\"/u/" . rawurlencode($u['login']) . "\">" . t('ver perfil público') . "</a> · <a href=\"/salir\">" . t('salir') . "</a></p></div></div>";
profile_tabs('favoritos');

$total = 0;
try {
    $c = $pdo->prepare("SELECT COUNT(*) FROM favorites f JOIN stories s ON s.story_id = f.story_id WHERE f.user_id = ? AND s.status NOT IN ('rejected','discard')");
    $c->execute([$u['user_id']]);
    $total = (int)$c->fetchColumn();
} catch (Throwable $e) { /* tabla aun sin crear */ }
$page = page_num(); $per = (int)cfg('per_page', 20);
if ($total > 0) {
    $q = $pdo->prepare(story_query_base('FROM favorites f JOIN stories s ON s.story_id = f.story_id') . " WHERE f.user_id = ? AND s.status NOT IN ('rejected','discard') ORDER BY f.created DESC LIMIT $per OFFSET " . page_offset($page));
    $q->execute([$u['user_id']]);
    foreach ($q as $s) story_post($pdo, $s, 'compact');
    paginate('/favoritos', $page, $total, $per);
} else {
    echo "<p class=\"notice\">" . t('Aquí verás lo que guardes con «guardar», junto a los votos, dentro de cualquier envío o artículo. Solo lo ves tú.') . "</p>";
    echo "<p class=\"end\">" . t('Todavía no has guardado nada.') . "</p>";
}
page_footer();
