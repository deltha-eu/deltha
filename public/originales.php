<?php
/** /originales — textos propios de la comunidad (content_type = article), por fecha, lo mas reciente arriba.
 *  No pasan por el radar ni van a las portadas: se leen, se votan y se comentan aqui mientras su hilo este abierto. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
foco_por_defecto();   // Originales se lee en modo sin distracciones salvo que el lector lo haya quitado (lib/app.php)

$page = page_num();
$ml = stories_mylang_sql();
// destacadas (migra-125): las primeras en la pagina 1, con halo, como en las portadas; aqui no hay halo automatico en la primera
$pins = pins_stories($pdo, 'originales');
$np = $pins ? " AND s.story_id NOT IN (" . implode(',', array_map(fn($x) => (int)$x['story_id'], $pins)) . ")" : '';
$total = (int)$pdo->query("SELECT COUNT(*) FROM stories s WHERE s.content_type='article' AND s.status IN ('nuevas','destacadas','archived')$ml$np")->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.content_type='article' AND s.status IN ('nuevas','destacadas','archived')$ml$np
                    ORDER BY s.sent_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header(t('Originales'), 'originales');
echo "<p class=\"notice\">" . t('Textos propios de la comunidad, hasta %d palabras, del más reciente al más antiguo. No van a las portadas: se leen, se votan y se comentan aquí. <a href="/articulo">Escribe el tuyo</a>.', (int)cfg('article_max_words', 1000)) . "</p>";
$n = 0;
if ($page === 1) foreach ($pins as $s) { story_post($pdo, $s, 'full'); $n++; }   // destacadas, las primeras
foreach ($st as $s) { story_post($pdo, $s, 'full'); $n++; }
if ($n === 0) echo "<p class=\"end\">" . t('Todavía no hay originales. <a href="/articulo">Escribe el primero</a>.') . "</p>";
paginate('/originales', $page, $total);
page_footer();
