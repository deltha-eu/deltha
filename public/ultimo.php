<?php
/** /ultimo.php?k=newsletter|medio|agregador — las diez ultimas noticias del grupo del rail («Mis substacks», «Mis medios», «Mis agregadores»),
 *  en formato normal (article.post, como en /fuente.php), la mas reciente primero. Las fuentes son las que salen en el rail: las elegidas en el
 *  perfil o, si no, las destacadas (agregadores: todos los activos, solo su portada). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

$k = (string)($_GET['k'] ?? 'newsletter');
if (!isset(DDD_ULTIMO_KINDS[$k])) $k = 'newsletter';
$u = user();
$label = t(DDD_ULTIMO_KINDS[$k][0]);
$fuentes = fuentes_on() ? ultimo_fuentes($pdo, $u, $k) : [];
$porId = array_column($fuentes, null, 'source_id');
$rows = [];
if ($porId) {
    $in = implode(',', array_fill(0, count($porId), '?'));
    $st = $pdo->prepare("SELECT i.* FROM source_items i WHERE i.source_id IN ($in)" . ($k === 'agregador' ? " AND i.phase = 'portada'" : "") . " ORDER BY i.pub_date DESC LIMIT 10");
    $st->execute(array_keys($porId));
    $rows = $st->fetchAll();
}
$sent = $k === 'agregador' ? [] : fuente_ya_enviadas($pdo, array_column($rows, 'url'));

if ($u) header('Cache-Control: private, no-cache'); else { header('Cache-Control: public, max-age=60'); header('Vary: Cookie'); }
page_header($label, "ultimo:$k");
echo "<nav class=\"sub\">";
foreach (DDD_ULTIMO_KINDS as $kk => [$lbl]) echo "<a href=\"/ultimo.php?k=$kk\"" . ($kk === $k ? ' class="on"' : '') . ">" . h(t($lbl)) . "</a>";
echo "</nav>";

$nombres = implode(', ', array_map(fn($m) => "<a href=\"" . h(fuente_url($m)) . "\">" . h($m['name']) . "</a>", $fuentes));
$intro = $fuentes ? t('Lo último de %s: las %d noticias más recientes, la última primero.', $nombres, 10) : t('Todavía no hay medios en el catálogo.');
if ($k !== 'agregador') $intro .= ' ' . ($u ? (mis_medios($pdo, $u, $k) ? t('Puedes cambiarlos en <a href="/perfil.php#medios">tu perfil</a>.') : t('Son los que han publicado más recientemente: elige los tuyos en <a href="/perfil.php#medios">tu perfil</a>.')) : t('<a href="/login.php">Entra</a> para elegir tus medios.'));
$intro .= ' <a href="/medios.php?k=' . $k . '">' . t('todos') . '</a>.';
echo "<p class=\"notice\">$intro</p>";
echo fuentes_casilla_idioma();   // la misma casilla que en el perfil

foreach ($rows as $i) fuente_post($porId[$i['source_id']], $i, 'full', $sent);
if ($fuentes && !$rows) echo "<p class=\"end\">" . t('El lector aún no ha pasado por esta fuente.') . "</p>";
page_footer();
