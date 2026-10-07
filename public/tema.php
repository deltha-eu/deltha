<?php
/** /tema/ID[?v=deltha|medios] — la pagina de un tema, con dos subpestañas:
 *   · «En Deltha»: todo lo enviado en el tema (enlaces y originales, en cualquier fase), de mas reciente a mas antiguo;
 *   · «En los medios»: las ultimas noticias de los medios y substacks del catalogo que tienen este tema asignado (sources.topic_id),
 *     sin enviar todavia, cada una con su boton «enviar al radar». Es el «feed por tema» sin añadir nada al menu;
 *   · «En medios en mi idioma» (&v=idioma): lo mismo, solo con las fuentes en el idioma de la interfaz (las de idioma desconocido
 *     cuentan). Solo sale si el tema tiene fuentes en otros idiomas y el lector no tiene ya marcado «solo en mi idioma».
 *  Sin ?v=, abre «En Deltha» salvo que el tema tenga menos de TEMA_POCO envios y si tenga fuentes: entonces «En los medios»
 *  (en la beta, que ningun tema se vea vacio). Si el tema no tiene fuentes, no hay subpestañas. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';
const TEMA_POCO = 5;

$tid = (int)($_GET['t'] ?? 0);
$tq = $pdo->prepare("SELECT topic_id, name, color FROM topics WHERE topic_id = ?");
$tq->execute([$tid]);
$topic = $tq->fetch();
if (!$topic) { http_response_code(404); page_header(t('Tema')); echo "<p class=\"end\">" . t('Ese tema no existe.') . "</p>"; page_footer(); exit; }

$page = page_num();
$per = (int)cfg('per_page', 20);
$ml = stories_mylang_sql();
$cnt = $pdo->prepare("SELECT COUNT(*) FROM stories s WHERE s.topic_id = ? AND s.status IN ('nuevas','destacadas','archived')$ml");
$cnt->execute([$tid]);
$total = (int)$cnt->fetchColumn();

// medios y substacks del catalogo con este tema (los de otro idioma, fuera si el lector lo ha pedido)
$fuentes = fuentes_on() ? array_values(array_filter(fuentes_visibles($pdo), fn($f) => fuente_es_medio($f) && (int)($f['topic_id'] ?? 0) === $tid)) : [];
$mias = array_values(array_filter($fuentes, fn($f) => in_array((string)($f['lang'] ?? ''), ['', lang()], true)));   // en mi idioma (o sin idioma conocido)
$hayIdioma = $mias && count($mias) < count($fuentes);   // la tercera pestaña solo si quita algo
$v = (string)($_GET['v'] ?? '');
if (!in_array($v, ['deltha', 'medios', 'idioma'], true)) $v = ($fuentes && $total < TEMA_POCO) ? 'medios' : 'deltha';
if (!$fuentes) $v = 'deltha';
if ($v === 'idioma' && !$hayIdioma) $v = 'medios';
$base = "/tema/$tid";

header('Cache-Control: private, no-cache');   // depende de la sesion y de «solo en mi idioma»
page_header(ucfirst($topic['name']));
if ($fuentes) {
    echo "<nav class=\"sub\"><a href=\"$base?v=deltha\"" . ($v === 'deltha' ? ' class="on"' : '') . ">" . t('En Deltha') . " <small>$total</small></a>"
       . "<a href=\"$base?v=medios\"" . ($v === 'medios' ? ' class="on"' : '') . ">" . t('En los medios') . " <small>" . count($fuentes) . "</small></a>"
       . ($hayIdioma ? "<a href=\"$base?v=idioma\"" . ($v === 'idioma' ? ' class="on"' : '') . ">" . t('En medios en mi idioma') . " <small>" . count($mias) . "</small></a>" : '') . "</nav>";
}

if ($v === 'medios' || $v === 'idioma') {
    if ($v === 'idioma') $fuentes = $mias;
    $porId = array_column($fuentes, null, 'source_id');
    $in = implode(',', array_map('intval', array_keys($porId)));
    $ult = fuentes_ultima_noticia($pdo);
    usort($fuentes, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
    $totalM = (int)$pdo->query("SELECT COUNT(*) FROM source_items WHERE source_id IN ($in)")->fetchColumn();
    $q = $pdo->prepare("SELECT i.* FROM source_items i WHERE i.source_id IN ($in) ORDER BY i.pub_date DESC LIMIT ? OFFSET ?");
    $q->bindValue(1, $per, PDO::PARAM_INT); $q->bindValue(2, page_offset($page), PDO::PARAM_INT); $q->execute();
    $rows = $q->fetchAll();
    $sent = fuente_ya_enviadas($pdo, array_column($rows, 'url'));
    $nombres = implode(', ', array_map(fn($m) => "<a href=\"" . h(fuente_url($m)) . "\">" . h($m['name']) . "</a>", array_slice($fuentes, 0, 6))) . (count($fuentes) > 6 ? '…' : '');
    echo "<p class=\"notice\">" . t('Lo último de los medios y substacks de este tema, todavía sin enviar: %s. Cada noticia lleva un botón para proponerla en el radar.', $nombres) . "</p>";
    foreach ($rows as $i) fuente_post($porId[$i['source_id']], $i, 'full', $sent);
    if (!$rows) echo "<p class=\"end\">" . t('El lector aún no ha pasado por esta fuente.') . "</p>";
    paginate("$base?v=$v", $page, $totalM, $per);
} else {
    $st = $pdo->prepare(story_query_base() . " WHERE s.topic_id = ? AND s.status IN ('nuevas','destacadas','archived')$ml
                                                ORDER BY s.sent_date DESC LIMIT ? OFFSET ?");
    $st->bindValue(1, $tid, PDO::PARAM_INT);
    $st->bindValue(2, $per, PDO::PARAM_INT);
    $st->bindValue(3, page_offset($page), PDO::PARAM_INT);
    $st->execute();
    echo "<p class=\"notice\">" . t('Todo lo enviado en este tema, noticias y originales, estén donde estén: en una portada, en el radar o ya archivado. %d en total.', $total) . "</p>";
    $n = 0;
    foreach ($st as $s) { story_post($pdo, $s, "compact"); $n++; }
    if ($n === 0) echo "<p class=\"end\">" . t('Todavía no hay nada en este tema.') . "</p>";
    paginate($fuentes ? "$base?v=deltha" : $base, $page, $total, $per);
}
page_footer();
