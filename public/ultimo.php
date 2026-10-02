<?php
/** /ultimo.php?k=newsletter|medio|agregador|youtube — lo publicado en los ultimos config.ultimo_days dias (7), paginado (si en ese plazo no
 *  hay nada, las diez ultimas). Antes: las diez ultimas noticias del grupo del rail («Mis substacks», «Mis medios», «Mis agregadores»),
 *  en formato normal (article.post, como en /fuente.php), la mas reciente primero. Las fuentes son todos los favoritos del usuario (sin limite) o,
 *  si no tiene, las que han publicado mas recientemente (agregadores: todos los activos, solo su portada). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

$k = (string)($_GET['k'] ?? 'newsletter');
if (!isset(DDD_ULTIMO_KINDS[$k]) || !fuente_kind_on($k)) $k = 'newsletter';   // agregadores (config.sources_agregadores) o YouTube (config.sources_youtube) apagados
$u = user();
$label = t(DDD_ULTIMO_KINDS[$k][0]);
$fuentes = fuentes_on() ? ultimo_fuentes($pdo, $u, $k) : [];
$porId = array_column($fuentes, null, 'source_id');
$rows = []; $total = 0;
$DIAS = max(1, (int)cfg('ultimo_days', 7)); $per = (int)cfg('per_page', 20); $page = page_num();
if ($porId) {
    $in = implode(',', array_fill(0, count($porId), '?'));
    $w = "i.source_id IN ($in)" . ($k === 'agregador' ? " AND i.phase = 'portada'" : "");
    $cn = $pdo->prepare("SELECT COUNT(*) FROM source_items i WHERE $w AND i.pub_date >= NOW() - INTERVAL $DIAS DAY");
    $cn->execute(array_keys($porId));
    $total = (int)$cn->fetchColumn();
    // con algo en la ultima semana: todo lo de esa semana, paginado; sin nada (fuentes que publican poco): las diez ultimas
    $st = $pdo->prepare("SELECT i.* FROM source_items i WHERE $w" . ($total ? " AND i.pub_date >= NOW() - INTERVAL $DIAS DAY" : '') . " ORDER BY i.pub_date DESC LIMIT " . ($total ? "$per OFFSET " . page_offset($page) : '10'));
    $st->execute(array_keys($porId));
    $rows = $st->fetchAll();
}
$sent = $k === 'agregador' ? [] : fuente_ya_enviadas($pdo, array_column($rows, 'url'));

header('Cache-Control: private, no-cache');   // depende de cookies (sesion, ddd_srclang): sin cache compartida ni de 60 s, o la casilla de idioma parece no hacer nada
page_header($label, "ultimo:$k");
echo "<nav class=\"sub grupos\">";   // .grupos: en movil no se pinta (ya esta la franja de iconos)
foreach (DDD_ULTIMO_KINDS as $kk => [$lbl]) if (fuente_kind_on($kk) && ($kk !== 'youtube' || $kk === $k || fuentes_activas($pdo, 'youtube'))) echo "<a href=\"/ultimo.php?k=$kk\"" . ($kk === $k ? ' class="on"' : '') . ">" . h(t($lbl)) . "</a>";
echo "</nav>";

$mias = (bool)mis_medios($pdo, $u, $k);
$nombres = implode(', ', array_map(fn($m) => "<a href=\"" . h(fuente_url($m)) . "\">" . h($m['name']) . "</a>", array_slice($fuentes, 0, 5))) . (count($fuentes) > 5 ? '…' : '');   // con muchos favoritos, solo los cinco mas recientes
$intro = $fuentes ? ($total ? t('Lo último de %s: lo publicado en los últimos %d días, lo más reciente primero.', $nombres, $DIAS) : t('Lo último de %s: las %d noticias más recientes, la última primero.', $nombres, 10)) : t('Todavía no hay medios en el catálogo.');
if ($k !== 'agregador' || $mias) $intro .= ' ' . ($u ? ($mias ? t('Son los que sigues: <a href="%s">verlos todos</a>.', "/mis-fuentes.php?k=$k") : t('Son los que han publicado más recientemente: pulsa «Seguir» en los que quieras tener aquí.')) : t('<a href="/login.php">Entra</a> para elegir tus medios.'));
$cat = t('catálogo');   // antes «todos», que al lado de «verlos todos» parecia un enlace repetido
$intro .= ' <a href="/medios.php?k=' . $k . '">' . h(mb_strtoupper(mb_substr($cat, 0, 1)) . mb_substr($cat, 1)) . '</a>.';
echo "<p class=\"notice\">$intro</p>";
echo fuentes_casilla_idioma();   // la misma casilla que en el perfil

foreach ($rows as $i) fuente_post($porId[$i['source_id']], $i, 'full', $sent);
if ($fuentes && !$rows) echo "<p class=\"end\">" . t('El lector aún no ha pasado por esta fuente.') . "</p>";
if ($total) paginate("/ultimo.php?k=$k", $page, $total);
page_footer();
