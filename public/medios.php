<?php
/** /medios.php — catalogo de medios (fuentes RSS de tipo «medio») que se pueden ver con el aspecto de Deltha y elegir en el perfil. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

$u = user();
$agg = fuentes_on() ? fuentes_activas($pdo, 'agregador') : [];

page_header(t('Medios'));
echo "<h1>" . t('Medios') . "</h1>";
echo "<p class=\"notice\">" . t('Medios con RSS que puedes leer con el aspecto de Deltha. Cada noticia lleva un botón para proponerla en Nuevas. Elige hasta %d en <a href="/perfil.php#medios">tu perfil</a> y los tendrás en el menú lateral y en el pie.', medios_max('medio')) . ' ' . t('Las newsletters (Substack) van en su propio grupo, «Mis substacks», hasta %d.', medios_max('newsletter')) . ($u ? '' : ' ' . t('<a href="/login.php">Entra</a> para elegir tus medios.')) . "</p>";
foreach (DDD_MEDIA_KINDS as $kind => [, $label]) {
    $lista = fuentes_on() ? fuentes_activas($pdo, $kind) : [];
    $mine = array_column(mis_medios($pdo, $u, $kind), 'source_id');
    echo "<h3 class=\"sec\">" . t($kind === 'medio' ? 'Medios' : 'Substacks') . "</h3>";
    if (!$lista) echo "<p class=\"end\">" . t('Todavía no hay medios en el catálogo.') . "</p>";
    foreach ($lista as $m) {
        $n = $pdo->prepare("SELECT COUNT(*), MAX(pub_date) FROM source_items WHERE source_id = ?"); $n->execute([$m['source_id']]); [$cnt, $last] = $n->fetch(PDO::FETCH_NUM);
        echo "<a class=\"fz medio\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . (in_array($m['source_id'], $mine, false) ? " <small class=\"mine\">" . t('mío') . "</small>" : "") . "</span><br><span class=\"meta\">" . h((string)$m['descr'] ?: source_of($m['home'])) . " · " . t('%d noticias', (int)$cnt) . ($last ? " · " . h(hace($last)) : '') . "</span></span>" . icon('news') . "</a>";
    }
}
if ($agg) {
    echo "<h3 class=\"sec\">" . t('Agregadores') . "</h3>";
    foreach ($agg as $m) echo "<a class=\"fz medio\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . t('portada, candidatas y nuevas, solo lectura') . "</span></span>" . icon('proc') . "</a>";
}
page_footer();
