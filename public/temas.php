<?php
/** /temas.php — todos los temas, con lo enviado esta semana y en total; cada uno lleva a /tema.php?t=ID. Enlazado desde la franja movil. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$rows = $pdo->query("SELECT t.topic_id, t.name, t.color, t.bonus,
                            (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id AND s.status IN ('nuevas','destacadas','archived') AND s.sent_date >= NOW() - INTERVAL 7 DAY) n,
                            (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id AND s.status IN ('nuevas','destacadas','archived')) total,
                            (SELECT MAX(s.sent_date) FROM stories s WHERE s.topic_id = t.topic_id AND s.status IN ('nuevas','destacadas','archived')) last
                       FROM topics t ORDER BY t.name")->fetchAll();

if (user()) header('Cache-Control: private, no-cache'); else { header('Cache-Control: public, max-age=60'); header('Vary: Cookie'); }
page_header(t('Temas'), 'temas');
echo "<p class=\"notice\">" . t('Los <b>temas</b> los pone %s; todo envío lleva uno. Aquí van por orden alfabético; cada tema muestra todo lo enviado en él, esté en la fase que esté.', h(cfg('site_name'))) . "</p>";
if (!$rows) echo "<p class=\"end\">" . t('Todavía no hay temas.') . "</p>";
foreach ($rows as $r) {
    $bonus = (float)$r['bonus'];
    echo "<a class=\"fz comm\" href=\"/tema.php?t=" . (int)$r['topic_id'] . "\"><span class=\"dot\" style=\"background:" . h($r['color']) . "\"></span><div class=\"fzt\"><b>" . h(ucfirst($r['name'])) . "</b>";
    echo "<span class=\"meta\"> · " . t('%d esta semana', (int)$r['n']) . " · " . t('%d en total', (int)$r['total']) . ($r['last'] ? " · " . h(hace($r['last'])) : '') . ($bonus > 0 ? " · " . h(t('bonus %s en el δ', '+' . num_pg(lang(), $bonus))) : '') . "</span></div></a>";
}
page_footer();
