<?php
/** /medios.php — catalogo de medios (fuentes RSS de tipo «medio») y newsletters («newsletter») que se pueden ver con el aspecto de Deltha
 *  y elegir en el perfil. Con ?k=medio o ?k=newsletter se muestra solo ese grupo (es a donde llevan los «todos» de la columna derecha);
 *  sin k, los dos. Dentro de cada grupo, por orden de aparicion: la fuente con la noticia mas reciente primero. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

$u = user();
$k = (string)($_GET['k'] ?? '');
if (!isset(DDD_MEDIA_KINDS[$k])) $k = '';
$kinds = $k ? [$k] : array_keys(DDD_MEDIA_KINDS);
$agg = (!$k && fuentes_on()) ? fuentes_activas($pdo, 'agregador') : [];
$titulo = $k === 'newsletter' ? t('Substacks') : t('Medios');

page_header($titulo);
echo "<h1>" . h($titulo) . "</h1>";
echo "<p class=\"notice\">" . t('Medios con RSS que puedes leer con el aspecto de Deltha. Cada noticia lleva un botón para proponerla en Nuevas. Elige hasta %d en <a href="/perfil.php#medios">tu perfil</a> y los tendrás en el menú lateral y en el pie.', medios_max('medio')) . ' ' . t('Las newsletters (Substack) van en su propio grupo, «Mis substacks», hasta %d.', medios_max('newsletter')) . ($u ? '' : ' ' . t('<a href="/login.php">Entra</a> para elegir tus medios.')) . "</p>";
foreach ($kinds as $kind) {
    $lista = fuentes_on() ? fuentes_activas($pdo, $kind) : [];
    $mine = array_column(mis_medios($pdo, $u, $kind), 'source_id');
    if (!$k) echo "<h3 class=\"sec\">" . t($kind === 'medio' ? 'Medios' : 'Substacks') . "</h3>";
    if (!$lista) echo "<p class=\"end\">" . t('Todavía no hay medios en el catálogo.') . "</p>";
    // numero de noticias y fecha de la ultima, y orden por esa fecha (la mas reciente primero; sin noticias, al final)
    $n = $pdo->prepare("SELECT COUNT(*), MAX(pub_date) FROM source_items WHERE source_id = ?");
    foreach ($lista as &$m) { $n->execute([$m['source_id']]); [$m['cnt'], $m['last']] = $n->fetch(PDO::FETCH_NUM); }
    unset($m);
    usort($lista, fn($a, $b) => strcmp((string)$b['last'], (string)$a['last']));
    foreach ($lista as $m) {
        echo "<a class=\"fz medio\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . (in_array($m['source_id'], $mine, false) ? " <small class=\"mine\">" . t('mío') . "</small>" : "") . "</span><br><span class=\"meta\">" . h((string)$m['descr'] ?: source_of($m['home'])) . " · " . t('%d noticias', (int)$m['cnt']) . ($m['last'] ? " · " . h(hace($m['last'])) : '') . "</span></span>" . icon('news') . "</a>";
    }
}
if ($agg) {
    echo "<h3 class=\"sec\">" . t('Agregadores') . "</h3>";
    foreach ($agg as $m) echo "<a class=\"fz medio\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . t('portada, candidatas y nuevas, solo lectura') . "</span></span>" . icon('proc') . "</a>";
}
// abajo, los dos grupos (y «todos» cuando se esta viendo uno solo)
echo "<nav class=\"sub\">";
foreach (['newsletter' => t('Substacks'), 'medio' => t('Medios')] as $kk => $label) echo "<a href=\"/medios.php?k=$kk\"" . ($k === $kk ? ' class="on"' : '') . ">" . h($label) . "</a>";
if ($k) echo "<a href=\"/medios.php\">" . t('todos') . "</a>";
echo "</nav>";
page_footer();
