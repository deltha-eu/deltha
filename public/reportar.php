<?php
/** /reportar?id=N — reportar un comentario a los moderadores. Cualquiera puede (registrado o anonimo);
 *  un reporte por persona y comentario (registrados por user_id; anonimos por huella HMAC ip+comentario, no reversible). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/moderacion.php';

// un mensaje del Chat (?chat=N) o un comentario de un hilo (?id=N)
$chat = (int)($_GET['chat'] ?? post('chat'));
if ($chat > 0) {
    require __DIR__ . '/../lib/chat.php';
    $id = $chat;
    $st = $pdo->prepare("SELECT c.msg_id, c.content, c.deleted, c.alias, c.created, u.login FROM chat c LEFT JOIN users u ON u.user_id = c.user_id WHERE c.msg_id = ? AND c.status = 'ok'");
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) { http_response_code(404); page_header(t('Reportar')); echo "<p class=\"end\">" . t('Ese mensaje no existe.') . "</p>"; page_footer(); exit; }
    $back = "/chat?m=$id#m$id";
    $table = 'chat_reports'; $col = 'msg_id'; $backTxt = t('Volver al chat'); $n = chat_who($c);
} else {
    $id = (int)($_GET['id'] ?? post('id'));
    $st = $pdo->prepare("SELECT c.comment_id, c.story_id, c.content, c.deleted, c.alias, c.created, u.login FROM comments c LEFT JOIN users u ON u.user_id = c.user_id WHERE c.comment_id = ? AND c.status = 'ok'");
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) { http_response_code(404); page_header(t('Reportar')); echo "<p class=\"end\">" . t('Ese comentario no existe.') . "</p>"; page_footer(); exit; }
    $back = "/hilo/{$c['story_id']}#c$id";
    $table = 'comment_reports'; $col = 'comment_id'; $backTxt = t('Volver al hilo'); $n = display_name($c, ghost_over_comment($c['created']));
}
if ((int)$c['deleted']) redirect($back);

$REASONS = report_reasons();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = isset($REASONS[post('reason')]) ? post('reason') : 'otro';
    $u = user();
    global $DDD_SECRET;
    $fp = $u ? null : substr(hash_hmac('sha256', client_ip() . '|rep|' . ($chat ? 'chat' : '') . $id, $DDD_SECRET), 0, 16);
    try {
        $pdo->prepare("INSERT INTO $table ($col, user_id, fp, reason) VALUES (?,?,?,?)")->execute([$id, $u ? $u['user_id'] : null, $fp, $reason]);
    } catch (PDOException $e) { /* ya lo habia reportado: silencio, mismo resultado */ }
    mod_enqueue($pdo, $chat ? 'chat' : 'comment', $id, 1);   // si la IA aun no lo ha visto, pasa delante (su opinion sale en moderacion)
    redirect($back . (str_contains($back, '?') ? '&' : '?') . 'reportado=1');
}

page_header($chat ? t('Reportar mensaje') : t('Reportar comentario'));
echo "<p class=\"notice\">" . t('Lo revisará una persona. Si incumple las <a href="/normas">normas de uso</a>, se retirará. ') . "<a href=\"$back\">" . h($backTxt) . "</a>.</p>";
echo "<blockquote class=\"quote\"><b>" . h($n) . "</b> " . h(mb_strimwidth($c['content'], 0, 400, '…')) . "</blockquote>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"" . ($chat ? 'chat' : 'id') . "\" value=\"$id\">";
echo "" . t('<label>¿Qué pasa con este comentario?</label>') . "";
foreach ($REASONS as $k => $v) echo "<label class=\"radio\"><input type=\"radio\" name=\"reason\" value=\"$k\"" . ($k === 'insulto' ? ' checked' : '') . "> " . h($v) . "</label>";
echo "" . t('<button>Enviar el reporte</button>') . "</form>";
page_footer();
