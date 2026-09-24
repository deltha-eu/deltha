<?php
/** /tema.php?t=ID — todo lo enviado en un tema (enlaces y artículos, en cualquier fase), de más reciente a más antiguo */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$tid = (int)($_GET['t'] ?? 0);
$tq = $pdo->prepare("SELECT topic_id, name, color FROM topics WHERE topic_id = ?");
$tq->execute([$tid]);
$topic = $tq->fetch();
if (!$topic) { http_response_code(404); page_header(t('Tema')); echo "<p class=\"end\">" . t('Ese tema no existe.') . "</p>"; page_footer(); exit; }

$page = page_num();
$cnt = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE topic_id = ? AND status IN ('nuevas','destacadas','archived')");
$cnt->execute([$tid]);
$total = (int)$cnt->fetchColumn();
$st = $pdo->prepare(story_query_base() . " WHERE s.topic_id = ? AND s.status IN ('nuevas','destacadas','archived')
                                            ORDER BY s.sent_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, $tid, PDO::PARAM_INT);
$st->bindValue(2, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(3, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header(ucfirst($topic['name']));
echo "<p class=\"notice\">" . t('Todo lo enviado en este tema, noticias y originales, estén donde estén: en una portada, en el radar o ya archivado. %d en total.', $total) . "</p>";
$n = 0;
foreach ($st as $s) { story_post($pdo, $s, "compact"); $n++; }
if ($n === 0) echo "<p class=\"end\">" . t('Todavía no hay nada en este tema.') . "</p>";
paginate('/tema.php?t=' . $tid, $page, $total);
page_footer();
