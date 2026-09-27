<?php
/** /chat.php — pestaña Chat: un solo flujo de mensajes cortos, el mas reciente arriba, sin anidar (tipo Nótame).
 *  Cuadro arriba; «responder» bajo cada mensaje abre el cuadro con «#ID » puesto; cada #N del texto enlaza al
 *  mensaje N y este enseña sus respuestas. @nick enlaza al perfil. Sin avatares: solo el nombre.
 *  Registrados con su nick; anonimos con alias estable por IP si chat_anon = 1. Borrado propio durante
 *  chat_delete_minutes; reportar → /reportar.php?chat=N; borrado de moderacion desde /moderacion.php.
 *  ?m=N lleva a la pagina donde esta el mensaje N (ancla #mN). Se refresca sola cada chat_refresh_sec (60; 0 = nunca)
 *  salvo cuando se esta escribiendo (?r=N) o se acaba de publicar. Con JS lo lleva /app/escribir.js, que cancela el
 *  refresco en cuanto hay algo escrito (antes se perdia el mensaje a medias); sin JS, el <noscript><meta refresh> de siempre. */
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
        avisos_chat($pdo, $mid, $u ? (int)$u['user_id'] : null, $u ? (string)$u['login'] : $anonAlias, $content, $ref);   // @mencionados y autor del #N citado
        redirect('/chat.php?ok=1' . (isset($_POST['js']) ? '' : '#m' . $mid));   // con JS (app/scroll.js) sin ancla: el script restaura el scroll
    }
}

$per = max(10, (int)cfg('chat_per_page', 50));
$page = page_num();
if (($goto = (int)($_GET['m'] ?? 0)) > 0) $page = chat_page_of($pdo, $goto);
$total = (int)$pdo->query("SELECT COUNT(*) FROM chat WHERE " . chat_visible_sql())->fetchColumn();   // los avisos de borrado caducan (chat_deleted_minutes)
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
// my_vote: sol (1) o lagrima (-1) que el usuario actual ya puso a cada mensaje (NULL si ninguno o anonimo); migra-074
$st = $pdo->prepare("SELECT c.*, u.login, cv.value AS my_vote FROM chat c LEFT JOIN users u ON u.user_id = c.user_id
                     LEFT JOIN chat_votes cv ON cv.msg_id = c.msg_id AND cv.user_id = ?
                     WHERE " . chat_visible_sql('c') . " ORDER BY c.msg_id DESC LIMIT ? OFFSET ?");
$st->bindValue(1, (int)($u['user_id'] ?? 0), PDO::PARAM_INT); $st->bindValue(2, $per, PDO::PARAM_INT); $st->bindValue(3, ($page - 1) * $per, PDO::PARAM_INT); $st->execute();
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
    $q = $pdo->prepare("SELECT user_id, login FROM users WHERE LOWER(login) IN (" . implode(',', array_fill(0, count($mentions), '?')) . ")");
    $q->execute(array_keys($mentions));
    foreach ($q->fetchAll() as $r) $logins[strtolower($r['login'])] = ghost_veiled($r['user_id']) ? ['alias' => ghost_alias((int)$r['user_id'])] : $r['login'];   // velados por ghosteo: alias, sin enlace
}
$replyTo = (int)($_GET['r'] ?? 0);
$writing = $replyTo > 0 || isset($_GET['ok']) || $err;

$refresco = $writing ? 0 : max(0, (int)cfg('chat_refresh_sec', 60));   // 0 = no se recarga sola
page_header(t('Chat'), 'chat', $refresco ? '<noscript>' . refresh_meta($refresco) . '</noscript>' : '');
echo "<div class=\"chat\"" . ($refresco ? " data-refresh=\"$refresco\" data-refresh-url=\"" . h(refresh_url()) . "\"" : '') . ">";
// el cuadro de escribir: arriba; al responder (?r=N) sale justo debajo del mensaje N si esta en esta pagina (asi no hay que subir)
$box = function (bool $reply) use ($u, $anonAlias, $maxLen, $replyTo) {
    echo "<form class=\"chatbox" . ($reply ? ' reply' : '') . "\" method=\"post\" id=\"escribir\">";
    $ph = $u ? t('Escribe como %s', $u['login']) : t('Escribes como %s (anónimo). Con cuenta, con tu nombre.', $anonAlias);
    echo "<textarea name=\"content\" maxlength=\"$maxLen\" rows=\"2\" placeholder=\"" . h($ph) . "\" required" . ($reply ? " autofocus" : "") . ">" . ($reply ? "#$replyTo " : "") . "</textarea>";
    echo "<div class=\"row\"><small class=\"meta\">" . t('#N cita un mensaje · @nick menciona · hasta %d caracteres', $maxLen) . ($reply ? " · <a href=\"/chat.php\">" . t('cancelar') . "</a>" : "") . "</small><button>" . t('Enviar') . "</button></div></form>";
};
$replyHere = $replyTo > 0 && isset($ids[$replyTo]);   // el mensaje respondido esta en esta pagina: el cuadro va debajo de el
if ($canWrite) {
    if (!$replyHere) $box($replyTo > 0);
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
    echo (!empty($m['login']) && !ghost_veiled($m['user_id'])) ? "<a class=\"who\" href=\"/u.php?u=" . rawurlencode($m['login']) . "\">" . h($who) . "</a>" : "<b class=\"who anon\">" . h($who) . "</b>";   // velado por ghosteo: como un anonimo mas
    echo "<span class=\"meta\"> · " . h(hace($m['created'])) . "</span></div>";
    if ($del) echo "<p class=\"delmsg\"><i>" . h(t(DDD_CHAT_DELETED_TXT)) . "</i> · <a href=\"/normas.php\">" . t('normas') . "</a></p>";
    else      echo "<p>" . chat_format($m['content'], $logins, $ids) . "</p>";
    $acts = [];
    if (!$del && $canWrite) $acts[] = "<a class=\"replylink\" href=\"/chat.php?r=$id#m$id\">" . icon('reply') . "<span>" . t('responder') . "</span></a>";
    if (!empty($replies[$id])) $acts[] = "<span class=\"reps\">" . t('respuestas:') . " " . implode(' ', array_map(fn($r) => "<a class=\"ref\" href=\"" . (isset($ids[$r]) ? "#m$r" : "/chat.php?m=$r#m$r") . "\">#$r</a>", $replies[$id])) . "</span>";
    if (!$del && chat_can_delete_own($m, $u, $u ? null : $anonKey)) $acts[] = "<form method=\"post\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"id\" value=\"$id\"><button class=\"link\" title=\"" . h(t('solo durante %d min', (int)cfg('chat_delete_minutes', 5))) . "\">" . t('borrar') . "</button></form>";
    if (!$del) $acts[] = "<a class=\"replink\" href=\"/reportar.php?chat=$id\">" . t('reportar') . "</a>";
    if (is_mod() && !$del) $acts[] = "<form method=\"post\" action=\"/moderacion.php\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"chat_borrar\"><input type=\"hidden\" name=\"mid\" value=\"$id\"><input type=\"hidden\" name=\"back\" value=\"1\"><button class=\"link modlink\">" . t('eliminar') . "</button></form>";
    if ($acts || !$del) {
        echo "<div class=\"actions\">" . implode('', $acts);
        // sol / lagrima: registrados sobre mensajes ajenos; el resto (y el autor) ve solo los contadores
        if (!$del) comment_vote_widget($m, $u && (int)$m['user_id'] !== (int)$u['user_id'], '/votar-chat.php', 'msg_id');
        echo "</div>";
    }
    echo "</article>";
    if ($canWrite && $replyHere && $id === $replyTo) $box(true);
}
echo "</div>";
paginate('/chat.php', $page, $total, $per);
page_footer();
