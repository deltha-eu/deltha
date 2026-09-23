<?php
/** /medios.php — catalogo de medios (fuentes RSS de tipo «medio»), newsletters («newsletter») y agregadores que se pueden ver con el aspecto
 *  de Deltha (los dos primeros, elegibles en el perfil). Con ?k=newsletter|medio|agregador se muestra solo ese grupo (es a donde llevan los
 *  «todos» de la columna derecha); sin k, los tres. Dentro de cada grupo, por orden de aparicion: la fuente con la noticia mas reciente primero. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';
require __DIR__ . '/../lib/propuestas.php';

$u = user();
$GRUPOS = ['newsletter' => t('Substacks'), 'medio' => t('Medios'), 'agregador' => t('Agregadores')];
$k = (string)($_GET['k'] ?? '');
if (!isset($GRUPOS[$k])) $k = '';
$kinds = $k ? [$k] : array_keys($GRUPOS);
$titulo = $k ? $GRUPOS[$k] : t('Medios');

page_header($titulo);
echo "<h1>" . h($titulo) . "</h1>";
echo "<p class=\"notice\">" . t('Medios con RSS que puedes leer con el aspecto de Deltha. Cada noticia lleva un botón para proponerla en Nuevas. Elige hasta %d en <a href="/perfil.php#medios">tu perfil</a> y los tendrás en el menú lateral y en el pie.', medios_max('medio')) . ' ' . t('Las newsletters (Substack) van en su propio grupo, «Mis substacks», hasta %d.', medios_max('newsletter')) . ($u ? '' : ' ' . t('<a href="/login.php">Entra</a> para elegir tus medios.')) . "</p>";
echo fuentes_casilla_idioma();   // la misma casilla que en el perfil: marcarla aqui la marca alli
if (fprop_puede($u) && $k !== 'agregador') echo "<p class=\"fprop-link\">" . t('¿Falta alguno? <a href="%s">Propón un medio o un substack</a>.', '/proponer-fuente.php' . ($k ? "?k=$k" : '')) . "</p>";   // aura >= source_propose_aura
foreach ($kinds as $kind) {
    $lista = fuentes_on() ? fuentes_visibles($pdo, $kind) : [];
    $mine = $kind === 'agregador' ? [] : array_column(mis_medios($pdo, $u, $kind), 'source_id');
    if (!$k) echo "<h3 class=\"sec\">" . h($GRUPOS[$kind]) . "</h3>";
    if (!$lista) echo "<p class=\"end\">" . t('Todavía no hay medios en el catálogo.') . "</p>";
    // numero de noticias y fecha de la ultima, y orden por esa fecha (la mas reciente primero; sin noticias, al final)
    $n = $pdo->prepare("SELECT COUNT(*), MAX(pub_date) FROM source_items WHERE source_id = ?");
    foreach ($lista as &$m) { $n->execute([$m['source_id']]); [$m['cnt'], $m['last']] = $n->fetch(PDO::FETCH_NUM); }
    unset($m);
    usort($lista, fn($a, $b) => strcmp((string)$b['last'], (string)$a['last']));
    foreach ($lista as $m) {
        $descr = $kind === 'agregador' ? t('portada, candidatas y nuevas, solo lectura') : h((string)$m['descr'] ?: source_of($m['home']));
        if (($m['lang'] ?? '') !== '' && $m['lang'] !== lang()) $descr = "<b>" . h(strtoupper($m['lang'])) . "</b> · " . $descr;   // otro idioma: se avisa
        echo "<a class=\"fz medio\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . (in_array($m['source_id'], $mine, false) ? " <small class=\"mine\">" . t('mío') . "</small>" : "") . "</span><br><span class=\"meta\">" . $descr . " · " . t('%d noticias', (int)$m['cnt']) . ($m['last'] ? " · " . h(hace($m['last'])) : '') . "</span></span>" . icon($kind === 'agregador' ? 'agg' : 'news') . "</a>";
    }
}
// abajo, los tres grupos (y «todos» cuando se esta viendo uno solo)
echo "<nav class=\"sub\">";
foreach ($GRUPOS as $kk => $label) echo "<a href=\"/medios.php?k=$kk\"" . ($k === $kk ? ' class="on"' : '') . ">" . h($label) . "</a>";
if ($k) echo "<a href=\"/medios.php\">" . t('todos') . "</a>";
echo "</nav>";
page_footer();
