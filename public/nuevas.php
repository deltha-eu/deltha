<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$page = page_num();
$total = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE status='nuevas'")->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.status='nuevas' ORDER BY s.sent_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header(t('Nuevas'), 'nuevas');
echo "<p class=\"notice\">" . t('Por orden de llegada. En <a href="/procesos.php">Procesos</a> están ordenadas por δ, con la estimación de cuándo asciende cada una.') . "</p>";
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, "compact"); $n++; }
if ($n === 0) echo "<p class=\"end\">" . t('No hay envíos nuevos. <a href="/enviar.php">Envía el primero</a>.') . "</p>";
paginate('/nuevas.php', $page, $total);
page_footer();
