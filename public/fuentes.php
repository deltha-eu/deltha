<?php
/** /fuentes — «Todas las fuentes» (08/10/2026; antes /medios, que redirige aqui): el catalogo de medios, substacks, canales de YouTube y
 *  agregadores que se pueden ver con el aspecto de Deltha (los tres primeros se siguen con el boton «Seguir», sin limite). Selector de
 *  tipo arriba (Medios · Substacks · Canales · Agregadores · todas), que se abre por el ultimo tipo usado (cookie ddd_fk, la misma que
 *  /mis-fuentes); ?todas=1 enseña los cuatro grupos seguidos. ?il=<idioma> filtra por idioma (fila de idiomas bajo la casilla).
 *  Dentro de cada grupo, la fuente con la noticia mas reciente primero. ?q=texto (09/10/2026): buscador para la gente, por nombre,
 *  descripcion o dominio, sin distinguir mayusculas ni tildes; con busqueda se miran todos los grupos a la vez (el selector no filtra). */
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
$q = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 64));
$kinds = $k && $q === '' ? [$k] : array_keys($GRUPOS);   // buscando, todos los grupos
// comparar sin mayusculas ni tildes («Pagina» encuentra «Página»; «eldiario» encuentra eldiario.es)
$llano = fn(string $t): string => strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u', 'ã' => 'a', 'õ' => 'o', 'ç' => 'c', 'ä' => 'a', 'ö' => 'o', 'ß' => 'ss']);
$qn = $llano($q);
$coincide = fn(array $m): bool => $qn === '' || str_contains($llano($m['name'] . ' ' . (string)$m['descr'] . ' ' . (string)$m['home']), $qn);
page_header(t('Todas las fuentes'), 'fuentes');
echo fuentes_subnav($pdo, '/fuentes', $k, false, "<a class=\"todas" . ($k === '' ? ' on' : '') . "\" href=\"/fuentes?todas=1\">" . t('Todas') . "</a> <a class=\"mias\" href=\"/mis-fuentes" . ($k ? "?k=$k" : '') . "\">" . t('Mis fuentes') . "</a>");
// sin <h1>: page_header() ya pinta el titulo en la cabecera (salia repetido)
echo "<p class=\"notice\">" . t('Medios, substacks y canales con RSS que puedes leer con el aspecto de Deltha. Cada noticia lleva un botón para proponerla en el radar. Pulsa «Seguir» en los que quieras, sin límite: los tendrás en «Mis fuentes».') . ($u ? '' : ' ' . t('<a href="/login">Entra</a> para elegir tus medios.'))
   . ($k !== 'agregador' ? ' ' . (fprop_puede($u) ? t('Con aura %s o más puedes proponer medios y substacks nuevos; la tuya es %s.', num_pg(lang(), cfg('source_propose_aura', 6)), num_pg(lang(), round((float)$u['aura'], 1)))
                                                  : t('Con aura %s o más puedes proponer medios y substacks nuevos.', num_pg(lang(), cfg('source_propose_aura', 6)))) : '') . "</p>";
echo fuentes_casilla_idioma();   // el mismo control de idiomas que en el perfil: cambiarlo aqui lo cambia alli
// buscador (09/10/2026): nombre, descripcion o dominio; busca en todos los tipos. Conserva el idioma elegido
$il0 = (string)($_GET['il'] ?? '');
echo "<form class=\"buscar-u\" method=\"get\" action=\"/fuentes\">" . ($il0 !== '' ? "<input type=\"hidden\" name=\"il\" value=\"" . h($il0) . "\">" : '')
   . "<input type=\"search\" name=\"q\" value=\"" . h($q) . "\" maxlength=\"64\" placeholder=\"" . h(t('buscar una fuente por nombre o dirección')) . "\"><button>" . t('Buscar') . "</button>"
   . ($q !== '' ? " <a href=\"/fuentes?" . ($k ? "k=$k" : 'todas=1') . ($il0 !== '' ? "&amp;il=" . h($il0) : '') . "\">" . t('todos') . "</a>" : '') . "</form>";
// filtro por idioma para mirar el catalogo (?il=pt): solo esta vista, no se guarda. Se ofrecen los idiomas que tienen las fuentes
// visibles de los grupos mostrados; si todas son del mismo idioma (o la casilla de arriba ya deja solo el tuyo), no sale.
$il = (string)($_GET['il'] ?? '');
$porIdioma = [];
if (fuentes_on()) foreach ($kinds as $kind) foreach (fuentes_visibles($pdo, $kind) as $m) if (($m['lang'] ?? '') !== '') $porIdioma[$m['lang']] = ($porIdioma[$m['lang']] ?? 0) + 1;
if (!isset($porIdioma[$il])) $il = '';
$qk = $k ? "k=$k&amp;" : '';
if (count($porIdioma) > 1) {
    $ord = array_merge(array_intersect(array_keys(DDD_LANGS), array_keys($porIdioma)), array_diff(array_keys($porIdioma), array_keys(DDD_LANGS)));
    $qq = $q !== '' ? '&amp;q=' . rawurlencode($q) : '';
    echo "<nav class=\"sub langs\" aria-label=\"" . h(t('Idioma')) . "\"><a href=\"/fuentes" . ($k ? "?k=$k" : '?todas=1') . $qq . "\"" . ($il === '' ? ' class="on"' : '') . ">" . t('todos') . "</a>";
    foreach ($ord as $l) echo "<a href=\"/fuentes?" . ($k ? "k=$k&amp;" : "todas=1&amp;") . "il=" . h($l) . $qq . "\"" . ($il === $l ? ' class="on"' : '') . " lang=\"" . h($l) . "\">" . h(DDD_LANGS[$l] ?? strtoupper($l)) . " <small>" . (int)$porIdioma[$l] . "</small></a>";
    echo "</nav>";
}
if (fprop_puede($u)) echo "<p class=\"fprop-cta\">" . t('¿Falta alguno? <a href="%s">Propón un medio o un substack</a>.', '/proponer-fuente' . ($k ? "?k=$k" : '')) . "</p>";   // aura >= source_propose_aura
$hallados = 0; $solo = count($kinds) === 1;
foreach ($kinds as $kind) {
    $lista = fuentes_on() ? fuentes_visibles($pdo, $kind) : [];
    if ($il !== '') $lista = array_values(array_filter($lista, fn($m) => ($m['lang'] ?? '') === $il));
    if ($q !== '') { $lista = array_values(array_filter($lista, $coincide)); $hallados += count($lista); }
    if (($il !== '' || $q !== '') && !$lista && !$solo) continue;   // viendo varios grupos: el que no tiene nada en ese idioma / busqueda no se pinta
    if (!$solo) echo "<h3 class=\"sec\">" . h($GRUPOS[$kind]) . "</h3>";
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
if ($q !== '') echo "<p class=\"end\">" . ($hallados ? t('%d coinciden con «%s».', $hallados, h($q)) : t('Ninguna fuente coincide con «%s».', h($q))) . "</p>";
page_footer();
