<?php
/** /medios.php — catalogo de medios (fuentes RSS de tipo «medio»), newsletters («newsletter») y agregadores que se pueden ver con el aspecto
 *  de Deltha (los dos primeros, elegibles en el perfil). ?il=<idioma> filtra por idioma (fila de idiomas bajo la casilla). Con ?k=newsletter|medio|agregador se muestra solo ese grupo (es a donde llevan los
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
// sin <h1>: page_header() ya pinta el titulo en la cabecera (salia repetido)
echo "<p class=\"notice\">" . t('Medios con RSS que puedes leer con el aspecto de Deltha. Cada noticia lleva un botón para proponerla en Nuevas. Elige hasta %d en <a href="/perfil.php#medios">tu perfil</a> y los tendrás en el menú lateral y en el pie.', medios_max('medio')) . ' ' . t('Las newsletters (Substack) van en su propio grupo, «Mis substacks», hasta %d.', medios_max('newsletter')) . ($u ? '' : ' ' . t('<a href="/login.php">Entra</a> para elegir tus medios.'))
   . ($k !== 'agregador' ? ' ' . (fprop_puede($u) ? t('Con aura %s o más puedes proponer medios y substacks nuevos; la tuya es %s.', num_pg(lang(), cfg('source_propose_aura', 6)), num_pg(lang(), round((float)$u['aura'], 1)))
                                                  : t('Con aura %s o más puedes proponer medios y substacks nuevos.', num_pg(lang(), cfg('source_propose_aura', 6)))) : '') . "</p>";
echo fuentes_casilla_idioma();   // la misma casilla que en el perfil: marcarla aqui la marca alli
// filtro por idioma para mirar el catalogo (?il=pt): solo esta vista, no se guarda. Se ofrecen los idiomas que tienen las fuentes
// visibles de los grupos mostrados; si todas son del mismo idioma (o la casilla de arriba ya deja solo el tuyo), no sale.
$il = (string)($_GET['il'] ?? '');
$porIdioma = [];
if (fuentes_on()) foreach ($kinds as $kind) foreach (fuentes_visibles($pdo, $kind) as $m) if (($m['lang'] ?? '') !== '') $porIdioma[$m['lang']] = ($porIdioma[$m['lang']] ?? 0) + 1;
if (!isset($porIdioma[$il])) $il = '';
$qk = $k ? "k=$k&amp;" : '';
if (count($porIdioma) > 1) {
    $ord = array_merge(array_intersect(array_keys(DDD_LANGS), array_keys($porIdioma)), array_diff(array_keys($porIdioma), array_keys(DDD_LANGS)));
    echo "<nav class=\"sub langs\" aria-label=\"" . h(t('Idioma')) . "\"><a href=\"/medios.php" . ($k ? "?k=$k" : '') . "\"" . ($il === '' ? ' class="on"' : '') . ">" . t('todos') . "</a>";
    foreach ($ord as $l) echo "<a href=\"/medios.php?{$qk}il=" . h($l) . "\"" . ($il === $l ? ' class="on"' : '') . " lang=\"" . h($l) . "\">" . h(DDD_LANGS[$l] ?? strtoupper($l)) . " <small>" . (int)$porIdioma[$l] . "</small></a>";
    echo "</nav>";
}
if (fprop_puede($u) && $k !== 'agregador') echo "<p class=\"fprop-cta\">" . t('¿Falta alguno? <a href="%s">Propón un medio o un substack</a>.', '/proponer-fuente.php' . ($k ? "?k=$k" : '')) . "</p>";   // aura >= source_propose_aura
foreach ($kinds as $kind) {
    $lista = fuentes_on() ? fuentes_visibles($pdo, $kind) : [];
    if ($il !== '') $lista = array_values(array_filter($lista, fn($m) => ($m['lang'] ?? '') === $il));
    if ($il !== '' && !$lista && !$k) continue;   // viendo los tres grupos: el que no tiene nada en ese idioma no se pinta
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
$qil = $il !== '' ? "&amp;il=$il" : '';   // el idioma elegido se mantiene al cambiar de grupo
foreach ($GRUPOS as $kk => $label) echo "<a href=\"/medios.php?k=$kk$qil\"" . ($k === $kk ? ' class="on"' : '') . ">" . h($label) . "</a>";
if ($k) echo "<a href=\"/medios.php" . ($il !== '' ? "?il=$il" : '') . "\">" . t('todos') . "</a>";
echo "</nav>";
page_footer();
