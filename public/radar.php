<?php
/** /radar.php[?portada=medios|substacks] — todo lo enviado que aun no ha llegado a portada, por orden de llegada (enlaces; los originales
 *  van en su pestaña). Cada ciclo publica, alternando Medios y Substacks, uno de esa portada, a suertes segun el δ, entre los que llegan al minimo.
 *  En /procesos.php estan ordenados por δ, con la estimacion. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/cola.php';

$f = (string)($_GET['portada'] ?? '');
if (!isset(DDD_PORTADAS[$f])) $f = '';
$w = "s.status='nuevas' AND s.content_type='link'" . ($f ? " AND s.portada = " . $pdo->quote($f) : '') . stories_mylang_sql();   // «solo en mi idioma» (migra-092)
$page = page_num();
$total = (int)$pdo->query("SELECT COUNT(*) FROM stories s WHERE $w")->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE $w ORDER BY s.sent_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();
// estimaciones: sobre todo el radar (las dos portadas compiten por turnos, no entre si), para pintarlas en cada envio
$all = $pdo->query(str_replace('SELECT s.*,', 'SELECT s.*, ' . delta_sql() . ' AS delta_live,', story_query_base()) . " WHERE s.status='nuevas' AND s.content_type='link' ORDER BY delta_live DESC, s.sent_date ASC")->fetchAll();
$DDD_ETA = cola_eta($all);

page_header(t('Radar'), 'radar');
echo "<nav class=\"sub\"><a href=\"/radar.php\"" . ($f === '' ? ' class="on"' : '') . ">" . t('Todo') . "</a>";
foreach (DDD_PORTADAS as $k => $lbl) echo "<a href=\"/radar.php?portada=$k\"" . ($f === $k ? ' class="on"' : '') . ">" . h(mb_strtoupper(mb_substr($x = t('hacia %s', t($lbl)), 0, 1)) . mb_substr($x, 1)) . "</a>";
echo "</nav>";
echo "<p class=\"notice\">" . t('Lo enviado que aún no está en portada, por orden de llegada. Cada %d min un ciclo publica en Medios o en Substacks, por turnos, un envío de esa portada que lleve más de %d min en el radar y cuyo δ llegue a %s (Medios) o %s (Substacks): a suertes, con más probabilidad cuanto mayor es el δ. En <a href="/procesos.php">Procesos</a> está ordenado por δ, con la estimación de cada uno.', (int)cfg('cycle_minutes', 5), (int)cfg('delta_min_age_min', 30), h(num_es(delta_minpub('medios'))), h(num_es(delta_minpub('substacks')))) . "</p>";
$n = 0;
foreach ($rows as $s) { story_post($pdo, $s, "compact"); $n++; }
if ($n === 0) echo "<p class=\"end\">" . t('No hay nada en el radar. <a href="/enviar.php">Envía algo</a>.') . "</p>";
paginate('/radar.php' . ($f ? "?portada=$f" : ''), $page, $total);
page_footer();
