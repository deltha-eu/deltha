<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$page = page_num();
$total = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE content_type='article' AND status IN ('nuevas','candidatas','destacadas','archived')")->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.content_type='article' AND s.status IN ('nuevas','candidatas','destacadas','archived')
                    ORDER BY FIELD(s.status,'destacadas','candidatas','nuevas','archived'), COALESCE(s.published_date, s.sent_date) DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header('Artículos', 'articulos');
echo "<p class=\"notice\">Textos propios de la comunidad, hasta " . (int)cfg('article_max_words', 500) . " palabras. <a href=\"/articulo.php\">Escribe el tuyo</a>.</p>";
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, 'full'); $n++; }
if ($n === 0) echo "<p class=\"end\">Todavía no hay artículos. <a href=\"/articulo.php\">Escribe el primero</a>.</p>";
paginate('/articulos.php', $page, $total);
page_footer();
