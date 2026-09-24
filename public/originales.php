<?php
/** /originales.php — textos propios de la comunidad (content_type = article), por fecha, lo mas reciente arriba.
 *  No pasan por el radar ni van a las portadas: se leen, se votan y se comentan aqui mientras su hilo este abierto. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$page = page_num();
$total = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE content_type='article' AND status IN ('nuevas','destacadas','archived')")->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.content_type='article' AND s.status IN ('nuevas','destacadas','archived')
                    ORDER BY s.sent_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header(t('Originales'), 'originales');
echo "<p class=\"notice\">" . t('Textos propios de la comunidad, hasta %d palabras, del más reciente al más antiguo. No van a las portadas: se leen, se votan y se comentan aquí. <a href="/articulo.php">Escribe el tuyo</a>.', (int)cfg('article_max_words', 500)) . "</p>";
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, 'full'); $n++; }
if ($n === 0) echo "<p class=\"end\">" . t('Todavía no hay originales. <a href="/articulo.php">Escribe el primero</a>.') . "</p>";
paginate('/originales.php', $page, $total);
page_footer();
