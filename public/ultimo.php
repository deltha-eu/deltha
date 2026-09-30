<?php
/** /ultimo.php?k=newsletter|medio|agregador — las diez ultimas noticias del grupo del rail («Mis substacks», «Mis medios», «Mis agregadores»),
 *  en formato normal (article.post, como en /fuente.php), la mas reciente primero. Las fuentes son todos los favoritos del usuario (sin limite) o,
 *  si no tiene, las que han publicado mas recientemente (agregadores: todos los activos, solo su portada). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

$k = (string)($_GET['k'] ?? 'newsletter');
if (!isset(DDD_ULTIMO_KINDS[$k]) || ($k === 'agregador' && !agregadores_on())) $k = 'newsletter';   // agregadores apagados (config.sources_agregadores)
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

header('Cache-Control: private, no-cache');   // depende de cookies (sesion, ddd_srclang): sin cache compartida ni de 60 s, o la casilla de idioma parece no hacer nada
page_header($label, "ultimo:$k");
echo "<nav class=\"sub\">";
foreach (DDD_ULTIMO_KINDS as $kk => [$lbl]) if ($kk !== 'agregador' || agregadores_on()) echo "<a href=\"/ultimo.php?k=$kk\"" . ($kk === $k ? ' class="on"' : '') . ">" . h(t($lbl)) . "</a>";
echo "</nav>";

$mias = $k !== 'agregador' && mis_medios($pdo, $u, $k);
$nombres = implode(', ', array_map(fn($m) => "<a href=\"" . h(fuente_url($m)) . "\">" . h($m['name']) . "</a>", array_slice($fuentes, 0, 5))) . (count($fuentes) > 5 ? '…' : '');   // con muchos favoritos, solo los cinco mas recientes
$intro = $fuentes ? t('Lo último de %s: las %d noticias más recientes, la última primero.', $nombres, 10) : t('Todavía no hay medios en el catálogo.');
if ($k !== 'agregador') $intro .= ' ' . ($u ? ($mias ? t('Son los que sigues: <a href="%s">verlos todos</a>.', "/mis-fuentes.php?k=$k") : t('Son los que han publicado más recientemente: pulsa «Seguir» en los que quieras tener aquí.')) : t('<a href="/login.php">Entra</a> para elegir tus medios.'));
$intro .= ' <a href="/medios.php?k=' . $k . '">' . t('todos') . '</a>.';
echo "<p class=\"notice\">$intro</p>";
echo fuentes_casilla_idioma();   // la misma casilla que en el perfil

foreach ($rows as $i) fuente_post($porId[$i['source_id']], $i, 'full', $sent);
if ($fuentes && !$rows) echo "<p class=\"end\">" . t('El lector aún no ha pasado por esta fuente.') . "</p>";
page_footer();
