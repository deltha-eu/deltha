<?php
/** /medios.php — catalogo de medios (fuentes RSS de tipo «medio») que se pueden ver con el aspecto de Deltha y elegir en el perfil. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

$u = user();
$medios = fuentes_on() ? fuentes_activas($pdo, 'medio') : [];
$mine = array_column(mis_medios($pdo, $u), 'source_id');
$agg = fuentes_on() ? fuentes_activas($pdo, 'agregador') : [];

page_header(t('Medios'));
echo "<h1>" . t('Medios') . "</h1>";
echo "<p class=\"notice\">" . t('Medios con RSS que puedes leer con el aspecto de Deltha. Cada noticia lleva un botón para proponerla en Nuevas. Elige hasta %d en <a href="/perfil.php#medios">tu perfil</a> y los tendrás en el menú lateral y en el pie.', max(1, (int)cfg('media_max_per_user', 3))) . ($u ? '' : ' ' . t('<a href="/login.php">Entra</a> para elegir tus medios.')) . "</p>";
if (!$medios) echo "<p class=\"end\">" . t('Todavía no hay medios en el catálogo.') . "</p>";
foreach ($medios as $m) {
    $n = $pdo->prepare("SELECT COUNT(*), MAX(pub_date) FROM source_items WHERE source_id = ?"); $n->execute([$m['source_id']]); [$cnt, $last] = $n->fetch(PDO::FETCH_NUM);
    echo "<a class=\"fz medio\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . (in_array($m['source_id'], $mine, false) ? " <small class=\"mine\">" . t('mío') . "</small>" : "") . "</span><br><span class=\"meta\">" . h((string)$m['descr'] ?: source_of($m['home'])) . " · " . t('%d noticias', (int)$cnt) . ($last ? " · " . h(hace($last)) : '') . "</span></span>" . icon('news') . "</a>";
}
if ($agg) {
    echo "<h3 class=\"sec\">" . t('Agregadores') . "</h3>";
    foreach ($agg as $m) echo "<a class=\"fz medio\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . t('portada, candidatas y nuevas, solo lectura') . "</span></span>" . icon('proc') . "</a>";
}
page_footer();
