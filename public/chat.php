<?php
/** /chat.php — pestaña Chat: un solo flujo de mensajes cortos, el mas reciente arriba, sin anidar (tipo Nótame).
 *  Cuadro arriba; «responder» bajo cada mensaje abre el cuadro con «#ID » puesto; cada #N del texto enlaza al
 *  mensaje N y este enseña sus respuestas. @nick enlaza al perfil. Sin avatares: solo el nombre.
 *  Registrados con su nick; anonimos con alias estable por IP si chat_anon = 1. Borrado propio durante
 *  chat_delete_minutes; reportar → /reportar.php?chat=N; borrado de moderacion desde /moderacion.php.
 *  ?m=N lleva a la pagina donde esta el mensaje N (ancla #mN). Se refresca sola cada 60 s (meta refresh, sin JS)
 *  salvo cuando se esta escribiendo (?r=N) o se acaba de publicar. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/chat.php';
require __DIR__ . '/../lib/moderacion.php';
if ((string)cfg('chat_enabled', '1') !== '1') redirect('/');

$u = user();
[$anonAlias, $anonKey] = chat_anon();
$canWrite = $u || (string)cfg('chat_anon', '1') === '1';
$maxLen = min(1000, max(50, (int)cfg('chat_max_len', 500)));
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('act') === 'borrar') {
        $id = (int)post('id');
        $st = $pdo->prepare("SELECT * FROM chat WHERE msg_id = ?"); $st->execute([$id]);
        if (($m = $st->fetch()) && chat_can_delete_own($m, $u, $u ? null : $anonKey)) $pdo->prepare("UPDATE chat SET status = 'gone' WHERE msg_id = ?")->execute([$id]);
        redirect('/chat.php');
    }
    if (!$canWrite) redirect('/login.php');
    $content = trim(preg_replace("/\r\n?/", "\n", post('content')));
    $content = mb_substr($content, 0, $maxLen);
    if (mb_strlen($content) < 1) redirect('/chat.php');
    $limit = (int)cfg($u ? 'chat_per_hour' : 'chat_anon_per_hour', $u ? 30 : 10);
    if (chat_recent_count($pdo, $u, $u ? null : $anonKey) >= $limit) $err = t('Demasiados mensajes seguidos: espera un rato.');
    else {
        $ref = chat_first_ref($content);
        if ($ref) { $q = $pdo->prepare("SELECT 1 FROM chat WHERE msg_id = ? AND status = 'ok'"); $q->execute([$ref]); if (!$q->fetchColumn()) $ref = null; }
        $pdo->prepare("INSERT INTO chat (user_id, alias, anon_key, reply_to, content) VALUES (?,?,?,?,?)")
            ->execute([$u ? $u['user_id'] : null, $u ? '' : $anonAlias, $u ? null : $anonKey, $ref, $content]);
        $mid = (int)$pdo->lastInsertId();
        mod_enqueue($pdo, 'chat', $mid);   // posmoderacion: la IA local lo revisa despues
        redirect('/chat.php?ok=1#m' . $mid);
    }
}

$per = max(10, (int)cfg('chat_per_page', 50));
$page = page_num();
if (($goto = (int)($_GET['m'] ?? 0)) > 0) $page = chat_page_of($pdo, $goto);
$total = (int)$pdo->query("SELECT COUNT(*) FROM chat WHERE " . chat_visible_sql())->fetchColumn();   // los avisos de borrado caducan (chat_deleted_minutes)
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$st = $pdo->prepare("SELECT c.*, u.login FROM chat c LEFT JOIN users u ON u.user_id = c.user_id WHERE " . chat_visible_sql('c') . " ORDER BY c.msg_id DESC LIMIT ? OFFSET ?");
$st->bindValue(1, $per, PDO::PARAM_INT); $st->bindValue(2, ($page - 1) * $per, PDO::PARAM_INT); $st->execute();
$rows = $st->fetchAll();
$ids = []; $mentions = [];
foreach ($rows as $m) { $ids[(int)$m['msg_id']] = true; foreach (chat_mentions($m['content']) as $n) $mentions[$n] = true; }
// respuestas a cada mensaje de la pagina («respuestas: #a #b»)
$replies = [];
if ($ids) {
    $in = implode(',', array_map('intval', array_keys($ids)));
    foreach ($pdo->query("SELECT msg_id, reply_to FROM chat WHERE status = 'ok' AND deleted = 0 AND reply_to IN ($in) ORDER BY msg_id") as $r) $replies[(int)$r['reply_to']][] = (int)$r['msg_id'];
}
$logins = [];
if ($mentions) {
    $q = $pdo->prepare("SELECT login FROM users WHERE LOWER(login) IN (" . implode(',', array_fill(0, count($mentions), '?')) . ")");
    $q->execute(array_keys($mentions));
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $l) $logins[strtolower($l)] = $l;
}
$replyTo = (int)($_GET['r'] ?? 0);
$writing = $replyTo > 0 || isset($_GET['ok']) || $err;

page_header(t('Chat'), 'chat', $writing ? '' : '<meta http-equiv="refresh" content="60">');
echo "<div class=\"chat\">";
if ($canWrite) {
    echo "<form class=\"chatbox\" method=\"post\" id=\"escribir\">";
    $ph = $u ? t('Escribe como %s', $u['login']) : t('Escribes como %s (anónimo). Con cuenta, con tu nombre.', $anonAlias);
    echo "<textarea name=\"content\" maxlength=\"$maxLen\" rows=\"2\" placeholder=\"" . h($ph) . "\" required" . ($replyTo ? " autofocus" : "") . ">" . ($replyTo ? "#$replyTo " : "") . "</textarea>";
    echo "<div class=\"row\"><small class=\"meta\">" . t('#N cita un mensaje · @nick menciona · hasta %d caracteres', $maxLen) . ($replyTo ? " · <a href=\"/chat.php\">" . t('cancelar') . "</a>" : "") . "</small><button>" . t('Enviar') . "</button></div></form>";
} else {
    echo "<p class=\"notice\">" . t('Para escribir en el Chat hace falta una cuenta.') . " <a href=\"/login.php\">" . t('entrar') . "</a></p>";
}
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if (isset($_GET['reportado'])) echo "<p class=\"notice\">" . t('Gracias: el mensaje queda avisado a los moderadores.') . "</p>";
if (!$rows) echo "<p class=\"end\">" . t('Nadie ha dicho nada todavía. Empieza tú.') . "</p>";
foreach ($rows as $m) {
    $id = (int)$m['msg_id'];
    $who = chat_who($m);
    $del = (int)$m['deleted'] === 1;
    echo "<article class=\"cm" . ($del ? ' deleted' : '') . ($u && !empty($m['user_id']) && (int)$m['user_id'] === (int)$u['user_id'] ? ' mine' : '') . ($id === $goto ? ' hl' : '') . "\" id=\"m$id\">";
    echo "<div class=\"ch\"><a class=\"num\" href=\"#m$id\">#$id</a> ";
    echo !empty($m['login']) ? "<a class=\"who\" href=\"/u.php?u=" . rawurlencode($m['login']) . "\">" . h($who) . "</a>" : "<b class=\"who anon\">" . h($who) . "</b>";
    echo "<span class=\"meta\"> · " . h(hace($m['created'])) . "</span></div>";
    if ($del) echo "<p class=\"delmsg\"><i>" . h(t(DDD_CHAT_DELETED_TXT)) . "</i> · <a href=\"/normas.php\">" . t('normas') . "</a></p>";
    else      echo "<p>" . chat_format($m['content'], $logins, $ids) . "</p>";
    $acts = [];
    if (!$del && $canWrite) $acts[] = "<a href=\"/chat.php?r=$id#escribir\">" . icon('reply') . "<span>" . t('responder') . "</span></a>";
    if (!empty($replies[$id])) $acts[] = "<span class=\"reps\">" . t('respuestas:') . " " . implode(' ', array_map(fn($r) => "<a class=\"ref\" href=\"" . (isset($ids[$r]) ? "#m$r" : "/chat.php?m=$r#m$r") . "\">#$r</a>", $replies[$id])) . "</span>";
    if (!$del && chat_can_delete_own($m, $u, $u ? null : $anonKey)) $acts[] = "<form method=\"post\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"id\" value=\"$id\"><button class=\"link\" title=\"" . h(t('solo durante %d min', (int)cfg('chat_delete_minutes', 5))) . "\">" . t('borrar') . "</button></form>";
    if (!$del) $acts[] = "<a class=\"replink\" href=\"/reportar.php?chat=$id\">" . t('reportar') . "</a>";
    if (is_mod() && !$del) $acts[] = "<form method=\"post\" action=\"/moderacion.php\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"chat_borrar\"><input type=\"hidden\" name=\"mid\" value=\"$id\"><input type=\"hidden\" name=\"back\" value=\"1\"><button class=\"link modlink\">" . t('eliminar') . "</button></form>";
    if ($acts) echo "<div class=\"actions\">" . implode('', $acts) . "</div>";
    echo "</article>";
}
echo "</div>";
paginate('/chat.php', $page, $total, $per);
page_footer();
