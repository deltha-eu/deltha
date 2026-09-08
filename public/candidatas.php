<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$page = page_num();
$total = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE status='candidatas'")->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.status='candidatas' ORDER BY " . delta_sql() . " DESC, s.sent_date ASC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header('Candidatas', 'candidatas');
echo "<p class=\"notice\">Cada ciclo sube aquí la nueva de mayor delta (entre las de más de " . (int)cfg('delta_min_age_min') . " min). Ordenadas por delta calculado al cargar la página; la mayor se publica en portada en cada ciclo si supera " . h(cfg('delta_min_publish')) . ". La fórmula se muestra bajo cada titular.</p>";
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, "compact"); $n++; }
if ($n === 0) echo "<p class=\"end\">No hay candidatas ahora mismo.</p>";
paginate('/candidatas.php', $page, $total);
page_footer();
