<?php
/** /reportar.php?id=N — reportar un comentario a los moderadores. Cualquiera puede (registrado o anonimo);
 *  un reporte por persona y comentario (registrados por user_id; anonimos por huella HMAC ip+comentario, no reversible). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT c.comment_id, c.story_id, c.content, c.deleted, c.alias, c.created, u.login FROM comments c LEFT JOIN users u ON u.user_id = c.user_id WHERE c.comment_id = ? AND c.status = 'ok'");
$st->execute([$id]);
$c = $st->fetch();
if (!$c) { http_response_code(404); page_header('Reportar'); echo "<p class=\"end\">Ese comentario no existe.</p>"; page_footer(); exit; }
$back = "/hilo.php?id={$c['story_id']}#c$id";
if ((int)$c['deleted']) redirect($back);

$REASONS = report_reasons();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = isset($REASONS[post('reason')]) ? post('reason') : 'otro';
    $u = user();
    global $DDD_SECRET;
    $fp = $u ? null : substr(hash_hmac('sha256', client_ip() . '|rep|' . $id, $DDD_SECRET), 0, 16);
    try {
        $pdo->prepare("INSERT INTO comment_reports (comment_id, user_id, fp, reason) VALUES (?,?,?,?)")->execute([$id, $u ? $u['user_id'] : null, $fp, $reason]);
    } catch (PDOException $e) { /* ya lo habia reportado: silencio, mismo resultado */ }
    redirect($back . '&reportado=1');
}

page_header('Reportar comentario');
$n = display_name($c, ghost_over($c['created']));
echo "<h1>Reportar un comentario</h1>";
echo "<p class=\"notice\">Lo revisará una persona. Si incumple las <a href=\"/normas.php\">normas de uso</a>, se retirará. <a href=\"$back\">Volver al hilo</a>.</p>";
echo "<blockquote class=\"quote\"><b>" . h($n) . "</b> " . h(mb_strimwidth($c['content'], 0, 400, '…')) . "</blockquote>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "<label>¿Qué pasa con este comentario?</label>";
foreach ($REASONS as $k => $v) echo "<label class=\"radio\"><input type=\"radio\" name=\"reason\" value=\"$k\"" . ($k === 'insulto' ? ' checked' : '') . "> " . h($v) . "</label>";
echo "<button>Enviar el reporte</button></form>";
page_footer();
