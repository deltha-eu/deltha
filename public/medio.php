<?php
/** /medio.php?m=dominio — todo lo enviado de un medio (por el dominio de la fuente), en cualquier fase, de más reciente a más antiguo.
 *  Enlazado desde el nombre del medio en cada post, igual que el tema. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$m = strtolower(trim((string)($_GET['m'] ?? '')));
if ($m === '' || !preg_match('~^[a-z0-9.-]{3,100}$~', $m)) { http_response_code(404); page_header(t('Medio')); echo "<p class=\"end\">" . t('Ese medio no existe.') . "</p>"; page_footer(); exit; }

$page = page_num();
$cnt = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE source = ? AND status IN ('nuevas','candidatas','destacadas','archived')");
$cnt->execute([$m]);
$total = (int)$cnt->fetchColumn();
if ($total === 0) { http_response_code(404); page_header(t('Medio')); echo "<p class=\"end\">" . t('No hay nada enviado de <b>%s</b>.', h($m)) . "</p>"; page_footer(); exit; }
$st = $pdo->prepare(story_query_base() . " WHERE s.source = ? AND s.status IN ('nuevas','candidatas','destacadas','archived')
                                            ORDER BY s.sent_date DESC LIMIT ? OFFSET ?");
$st->bindValue(1, $m);
$st->bindValue(2, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(3, page_offset($page), PDO::PARAM_INT);
$st->execute();

page_header($m);
echo "<p class=\"notice\">" . t('Todo lo enviado de %s, esté donde esté: en portada, candidatas, nuevas o ya archivado. %d en total.', "<a href=\"https://" . h($m) . "/\" rel=\"noopener nofollow\"><b>" . h($m) . "</b></a>", $total) . "</p>";
foreach ($st as $s) story_post($pdo, $s, "compact");
paginate('/medio.php?m=' . rawurlencode($m), $page, $total);
page_footer();
