<?php
/** /fuentes — «Todas las fuentes» (08/10/2026; antes /medios, que redirige aqui): el catalogo de medios, substacks, canales de YouTube y
 *  agregadores que se pueden ver con el aspecto de Deltha (los tres primeros se siguen con el boton «Seguir», sin limite). Selector de
 *  tipo arriba (Medios · Substacks · Canales · Agregadores · todas), que se abre por el ultimo tipo usado (cookie ddd_fk, la misma que
 *  /mis-fuentes); ?todas=1 enseña los cuatro grupos seguidos. ?il=<idioma> filtra por idioma (fila de idiomas bajo la casilla).
 *  Dentro de cada grupo, la fuente con la noticia mas reciente primero. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';
require __DIR__ . '/../lib/propuestas.php';

$u = user();
$GRUPOS = ['medio' => t('Medios'), 'newsletter' => t('Substacks'), 'youtube' => 'YouTube', 'agregador' => t('Agregadores')];
foreach (array_keys($GRUPOS) as $gk) if (!fuente_kind_visible($gk)) unset($GRUPOS[$gk]);   // apagados en admin, o canales y agregadores para quien aun no llega a fuentes_nivel_aura
if (!fuentes_activas($pdo, 'youtube')) unset($GRUPOS['youtube']);   // sin canales en el catalogo, el grupo no se anuncia
$k = isset($_GET['todas']) ? '' : fuentes_tipo_actual($pdo);   // sin ?k=: el ultimo tipo que abrio; ?todas=1: los cuatro grupos
if (!isset($GRUPOS[$k])) $k = '';
$kinds = $k ? [$k] : array_keys($GRUPOS);
page_header(t('Todas las fuentes'), 'fuentes');
echo fuentes_subnav($pdo, '/fuentes', $k, false, "<a class=\"todas" . ($k === '' ? ' on' : '') . "\" href=\"/fuentes?todas=1\">" . t('todas') . "</a> <a class=\"mias\" href=\"/mis-fuentes" . ($k ? "?k=$k" : '') . "\">" . t('Mis fuentes') . "</a>");
// sin <h1>: page_header() ya pinta el titulo en la cabecera (salia repetido)
echo "<p class=\"notice\">" . t('Medios, substacks y canales con RSS que puedes leer con el aspecto de Deltha. Cada noticia lleva un botón para proponerla en el radar. Pulsa «Seguir» en los que quieras, sin límite: los tendrás en «Mis fuentes».') . ($u ? '' : ' ' . t('<a href="/login">Entra</a> para elegir tus medios.'))
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
    echo "<nav class=\"sub langs\" aria-label=\"" . h(t('Idioma')) . "\"><a href=\"/fuentes" . ($k ? "?k=$k" : '?todas=1') . "\"" . ($il === '' ? ' class="on"' : '') . ">" . t('todos') . "</a>";
    foreach ($ord as $l) echo "<a href=\"/fuentes?" . ($k ? "k=$k&amp;" : "todas=1&amp;") . "il=" . h($l) . "\"" . ($il === $l ? ' class="on"' : '') . " lang=\"" . h($l) . "\">" . h(DDD_LANGS[$l] ?? strtoupper($l)) . " <small>" . (int)$porIdioma[$l] . "</small></a>";
    echo "</nav>";
}
if (fprop_puede($u) && $k !== 'agregador' && $k !== 'youtube') echo "<p class=\"fprop-cta\">" . t('¿Falta alguno? <a href="%s">Propón un medio o un substack</a>.', '/proponer-fuente' . ($k ? "?k=$k" : '')) . "</p>";   // aura >= source_propose_aura
foreach ($kinds as $kind) {
    $lista = fuentes_on() ? fuentes_visibles($pdo, $kind) : [];
    if ($il !== '') $lista = array_values(array_filter($lista, fn($m) => ($m['lang'] ?? '') === $il));
    if ($il !== '' && !$lista && !$k) continue;   // viendo los tres grupos: el que no tiene nada en ese idioma no se pinta
    if (!$k) echo "<h3 class=\"sec\">" . h($GRUPOS[$kind]) . "</h3>";
    if (!$lista) echo "<p class=\"end\">" . t('Todavía no hay medios en el catálogo.') . "</p>";
    // numero de noticias y fecha de la ultima, y orden por esa fecha (la mas reciente primero; sin noticias, al final)
    $n = $pdo->prepare("SELECT COUNT(*), MAX(pub_date) FROM source_items WHERE source_id = ?");
    foreach ($lista as &$m) { $n->execute([$m['source_id']]); [$m['cnt'], $m['last']] = $n->fetch(PDO::FETCH_NUM); }
    unset($m);
    usort($lista, fn($a, $b) => strcmp((string)$b['last'], (string)$a['last']));
    foreach ($lista as $m) {
        $descr = $kind === 'agregador' ? t('portada, populares y nuevas, solo lectura') : h((string)$m['descr'] ?: source_of($m['home']));
        if (($m['lang'] ?? '') !== '' && $m['lang'] !== lang()) $descr = "<b>" . h(strtoupper($m['lang'])) . "</b> · " . $descr;   // otro idioma: se avisa
        $cuerpo = avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . $descr . " · " . t('%d noticias', (int)$m['cnt']) . ($m['last'] ? " · " . h(hace($m['last'])) : '') . "</span></span>";
        // con cuenta, medios y substacks llevan «+ Seguir» / «✓ Siguiendo» al final de la fila (la fila deja de ser un solo enlace: un form no cabe dentro de un <a>)
        if ($fav = fuente_fav_form($m)) echo "<div class=\"fz medio\"><a class=\"fzl\" href=\"" . h(fuente_url($m)) . "\">$cuerpo</a>$fav</div>";
        else echo "<a class=\"fz medio\" href=\"" . h(fuente_url($m)) . "\">$cuerpo" . icon($kind === 'agregador' ? 'agg' : ($kind === 'youtube' ? 'play' : 'news')) . "</a>";
    }
}
page_footer();
