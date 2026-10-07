<?php
/** /chat — pestaña Chat: un solo flujo de mensajes cortos, el mas reciente arriba, sin anidar (tipo Nótame).
 *  Cuadro arriba; «responder» bajo cada mensaje abre el cuadro con «#ID » puesto; cada #N del texto enlaza al
 *  mensaje N y este enseña sus respuestas. @nick enlaza al perfil. Sin avatares: solo el nombre.
 *  Registrados con su nick; anonimos con alias estable por IP si chat_anon = 1. Borrado propio durante
 *  chat_delete_minutes; reportar → /reportar?chat=N; borrado de moderacion desde /moderacion; los administradores
 *  ademas pueden editar el texto (/editar-chat, queda «editado»).
 *  ?m=N lleva a la pagina donde esta el mensaje N (ancla #mN). Se refresca sola cada chat_refresh_sec (60; 0 = nunca)
 *  salvo cuando se esta escribiendo (?r=N) o se acaba de publicar. Con JS lo lleva /app/escribir.js, que cancela el
 *  refresco en cuanto hay algo escrito (antes se perdia el mensaje a medias); sin JS, el <noscript><meta refresh> de siempre.
 *  Dos vistas (subpestañas «Por hilos» / «Por tiempo»): por hilos, la de por defecto, agrupa cada mensaje con sus respuestas
 *  (reply_to: el primer #N citado), el hilo con actividad mas reciente arriba y, dentro, del mas antiguo al mas nuevo con las respuestas
 *  sangradas segun a quien responden (sin rayas entre mensajes); ?v=tiempo es el flujo cronologico de antes, el mas reciente arriba. La vista viaja en la URL (no hay cookie): responder, publicar, borrar, paginar y el refresco la conservan. */
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
$hilos = (string)($_GET['v'] ?? '') !== 'tiempo';   // por hilos es la vista por defecto; ?v=tiempo, el flujo cronologico (?v=hilos de enlaces antiguos sigue valiendo)
$base = '/chat' . ($hilos ? '' : '?v=tiempo');   // la vista elegida va en todos los enlaces y redirecciones
$amp = $hilos ? '?' : '&';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('act') === 'borrar') {
        $id = (int)post('id');
        $st = $pdo->prepare("SELECT * FROM chat WHERE msg_id = ?"); $st->execute([$id]);
        if (($m = $st->fetch()) && chat_can_delete_own($m, $u, $u ? null : $anonKey)) $pdo->prepare("UPDATE chat SET status = 'gone' WHERE msg_id = ?")->execute([$id]);
        redirect($base);
    }
    if (post('act') === 'anuncio') {   // admin/mod: marcar o quitar «mensaje a la comunidad» (migra-121)
        $id = (int)post('id');
        if (is_mod()) $pdo->prepare("UPDATE chat SET anuncio = 1 - anuncio WHERE msg_id = ? AND status = 'ok' AND deleted = 0")->execute([$id]);
        redirect($base . $amp . "m=$id" . (isset($_POST['js']) ? '' : "#m$id"));
    }
    if (!$canWrite) redirect('/login');
    $content = trim(preg_replace("/\r\n?/", "\n", post('content')));
    $content = mb_substr($content, 0, $maxLen);
    if (mb_strlen($content) < 1) redirect($base);
    $limit = (int)cfg($u ? 'chat_per_hour' : 'chat_anon_per_hour', $u ? 30 : 10);
    if (chat_recent_count($pdo, $u, $u ? null : $anonKey) >= $limit) $err = t('Demasiados mensajes seguidos: espera un rato.');
    else {
        $ref = chat_first_ref($content);
        if ($ref) { $q = $pdo->prepare("SELECT 1 FROM chat WHERE msg_id = ? AND status = 'ok'"); $q->execute([$ref]); if (!$q->fetchColumn()) $ref = null; }
        $anuncio = is_mod() && post('anuncio') === '1' ? 1 : 0;   // mensaje a la comunidad: solo admin/mod (migra-121)
        $pdo->prepare("INSERT INTO chat (user_id, alias, anon_key, reply_to, content, anuncio) VALUES (?,?,?,?,?,?)")
            ->execute([$u ? $u['user_id'] : null, $u ? '' : $anonAlias, $u ? null : $anonKey, $ref, $content, $anuncio]);
        $mid = (int)$pdo->lastInsertId();
        mod_enqueue($pdo, 'chat', $mid);   // posmoderacion: la IA local lo revisa despues
        avisos_chat($pdo, $mid, $u ? (int)$u['user_id'] : null, $u ? (string)$u['login'] : $anonAlias, $content, $ref);   // @mencionados y autor del #N citado
        redirect($base . $amp . 'ok=1' . (isset($_POST['js']) ? '' : '#m' . $mid));   // con JS (app/scroll.js) sin ancla: el script restaura el scroll
    }
}

$per = max(10, (int)cfg('chat_per_page', 50));
$page = page_num();
$goto = (int)($_GET['m'] ?? 0);
$sel = "SELECT c.*, u.login, cv.value AS my_vote FROM chat c LEFT JOIN users u ON u.user_id = c.user_id
        LEFT JOIN chat_votes cv ON cv.msg_id = c.msg_id AND cv.user_id = ?";   // my_vote: sol (1) o lagrima (-1) del usuario actual (NULL si ninguno o anonimo); migra-074
$uid = (int)($u['user_id'] ?? 0);
$sangria = [];   // vista por hilos: msg_id => nivel de sangria de una respuesta (1 = responde al que abre el hilo, 2 = a una respuesta... tope 6); $cabeza: msg_id => true si abre hilo
$cabeza = [];
if ($hilos) {
    // Hilos de los ultimos mensajes visibles (tope: 3000, de sobra para el Chat): la raiz de cada mensaje se saca subiendo por reply_to
    // mientras el citado siga visible; los hilos se ordenan por su mensaje mas reciente y se paginan por hilos (20 por pagina).
    $padre = $pdo->query("SELECT msg_id, reply_to FROM chat WHERE " . chat_visible_sql() . " ORDER BY msg_id DESC LIMIT 3000")->fetchAll(PDO::FETCH_KEY_PAIR);
    $raiz = function (int $id) use ($padre): int {
        for ($n = 0; $n < 200 && !empty($padre[$id]) && array_key_exists((int)$padre[$id], $padre) && (int)$padre[$id] < $id; $n++) $id = (int)$padre[$id];
        return $id;
    };
    $nivel = function (int $id) use ($padre): int {   // pasos hasta la raiz: cuanto se sangra
        for ($n = 0; $n < 200 && !empty($padre[$id]) && array_key_exists((int)$padre[$id], $padre) && (int)$padre[$id] < $id; $n++) $id = (int)$padre[$id];
        return $n;
    };
    $grupos = [];   // raiz => [msg_id...]; las claves quedan ya por actividad mas reciente (la consulta viene del mas nuevo al mas viejo)
    foreach ($padre as $id => $p) $grupos[$raiz((int)$id)][] = (int)$id;
    $porPag = 20;
    $total = count($grupos);
    $pages = max(1, (int)ceil($total / $porPag));
    if ($goto > 0 && array_key_exists($goto, $padre)) $page = intdiv((int)array_search($raiz($goto), array_keys($grupos), true), $porPag) + 1;
    $page = min($page, $pages);
    $trozo = array_slice($grupos, ($page - 1) * $porPag, $porPag, true);
    $rows = [];
    if ($trozo) {
        $todos = array_merge(...array_values($trozo));
        $st = $pdo->prepare("$sel WHERE c.msg_id IN (" . implode(',', array_map('intval', $todos)) . ")");
        $st->execute([$uid]);
        $porId = array_column($st->fetchAll(), null, 'msg_id');
        foreach ($trozo as $r => $lista) {
            // dentro del hilo, en arbol: cada respuesta justo debajo del mensaje al que responde (y entre hermanas, la mas antigua primero)
            sort($lista);
            $hijos = [];
            foreach ($lista as $id) if ($id !== (int)$r) $hijos[(int)$padre[$id]][] = $id;
            $orden = []; $pila = [(int)$r];
            while ($pila) { $id = array_pop($pila); $orden[] = $id; foreach (array_reverse($hijos[$id] ?? []) as $h) $pila[] = $h; }
            foreach ($orden as $id) if (isset($porId[$id])) { $rows[] = $porId[$id]; if ($id === (int)$r) $cabeza[$id] = true; else $sangria[$id] = min(6, max(1, $nivel($id))); }
        }
    }
} else {
    if ($goto > 0) $page = chat_page_of($pdo, $goto);
    $total = (int)$pdo->query("SELECT COUNT(*) FROM chat WHERE " . chat_visible_sql())->fetchColumn();   // los avisos de borrado caducan (chat_deleted_minutes)
    $pages = max(1, (int)ceil($total / $per));
    $page = min($page, $pages);
    $st = $pdo->prepare("$sel WHERE " . chat_visible_sql('c') . " ORDER BY c.msg_id DESC LIMIT ? OFFSET ?");
    $st->bindValue(1, $uid, PDO::PARAM_INT); $st->bindValue(2, $per, PDO::PARAM_INT); $st->bindValue(3, ($page - 1) * $per, PDO::PARAM_INT); $st->execute();
    $rows = $st->fetchAll();
}
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
echo "<nav class=\"sub\"><a href=\"/chat\"" . ($hilos ? ' class="on"' : '') . ">" . t('Por hilos') . "</a><a href=\"/chat?v=tiempo\"" . ($hilos ? '' : ' class="on"') . ">" . t('Por tiempo') . "</a></nav>";
echo "<div class=\"chat" . ($hilos ? ' hilos' : '') . "\"" . ($refresco ? " data-refresh=\"$refresco\" data-refresh-url=\"" . h(refresh_url()) . "\"" : '') . ">";
// el cuadro de escribir: arriba; al responder (?r=N) sale justo debajo del mensaje N si esta en esta pagina (asi no hay que subir)
$box = function (bool $reply) use ($u, $anonAlias, $maxLen, $replyTo, $base) {
    echo "<form class=\"chatbox" . ($reply ? ' reply' : '') . "\" method=\"post\" id=\"escribir\">";
    $ph = $u ? t('Escribe como %s', $u['login']) : t('Escribes como %s (anónimo). Con cuenta, con tu nombre.', $anonAlias);
    echo "<textarea name=\"content\" maxlength=\"$maxLen\" rows=\"2\" placeholder=\"" . h($ph) . "\" required" . ($reply ? " autofocus" : "") . ">" . ($reply ? "#$replyTo " : "") . "</textarea>";
    echo "<div class=\"row\"><small class=\"meta\">" . t('#N cita un mensaje · @nick menciona · hasta %d caracteres', $maxLen) . ($reply ? " · <a href=\"" . h($base) . "\">" . t('cancelar') . "</a>" : "") . "</small>";
    if (is_mod()) echo "<label class=\"anuncio\" title=\"" . h(t('marcar como mensaje a la comunidad')) . "\"><input type=\"checkbox\" name=\"anuncio\" value=\"1\">" . icon('mega') . "<span>" . t('Mensaje a la comunidad') . "</span></label>";   // admin/mod (migra-121)
    echo "<button>" . t('Enviar') . "</button></div></form>";
};
$replyHere = $replyTo > 0 && isset($ids[$replyTo]);   // el mensaje respondido esta en esta pagina: el cuadro va debajo de el
if ($canWrite) {
    if (!$replyHere) $box($replyTo > 0);
} else {
    echo "<p class=\"notice\">" . t('Para escribir en el Chat hace falta una cuenta.') . " <a href=\"/login\">" . t('entrar') . "</a></p>";
}
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($page === 1 && $goto === 0) {   // mensajes a la comunidad (admin/mod, migra-121): los tres ultimos, arriba del flujo, enlazados a su sitio
    $an = $pdo->query("SELECT c.msg_id, c.content, c.user_id, c.alias, u.login FROM chat c LEFT JOIN users u ON u.user_id = c.user_id WHERE c.anuncio = 1 AND c.deleted = 0 AND " . chat_visible_sql('c') . " ORDER BY c.msg_id DESC LIMIT 3")->fetchAll();
    $an = array_filter($an, fn($a) => !isset($ids[(int)$a['msg_id']]));   // los que ya se ven en esta pagina no se repiten: la franja es para los que han quedado atras
    if ($an) {
        echo "<div class=\"anuncios\"><div class=\"ch\"><span class=\"anun\">" . icon('mega') . t('Mensajes a la comunidad') . "</span></div>";
        foreach ($an as $a) echo "<p><a class=\"num\" href=\"/chat?m={$a['msg_id']}#m{$a['msg_id']}\">#{$a['msg_id']}</a> <b>" . h(chat_who($a)) . "</b> <span>" . h(mb_strimwidth(preg_replace('/\s+/', ' ', (string)$a['content']), 0, 160, '…')) . "</span></p>";
        echo "</div>";
    }
}
if (isset($_GET['reportado'])) echo "<p class=\"notice\">" . t('Gracias: el mensaje queda avisado a los moderadores.') . "</p>";
if (!$rows) echo "<p class=\"end\">" . t('Nadie ha dicho nada todavía. Empieza tú.') . "</p>";
foreach ($rows as $m) {
    $id = (int)$m['msg_id'];
    $who = chat_who($m);
    $del = (int)$m['deleted'] === 1;
    $anun = !$del && (int)($m['anuncio'] ?? 0) === 1;   // mensaje a la comunidad (migra-121): resaltado y con etiqueta
    echo "<article class=\"cm" . ($anun ? ' anuncio' : '') . (isset($sangria[$id]) ? ' rep' : '') . (isset($cabeza[$id]) ? ' top' : '') . ($del ? ' deleted' : '') . ($u && !empty($m['user_id']) && (int)$m['user_id'] === (int)$u['user_id'] ? ' mine' : '') . ($id === $goto ? ' hl' : '') . "\" id=\"m$id\"" . (isset($sangria[$id]) ? " style=\"--lv:{$sangria[$id]}\"" : '') . ">";
    echo "<div class=\"ch\"><a class=\"num\" href=\"#m$id\">#$id</a> ";
    echo (!empty($m['login']) && !ghost_veiled($m['user_id'])) ? "<a class=\"who\" href=\"/u/" . rawurlencode($m['login']) . "\">" . h($who) . "</a>" : "<b class=\"who anon\">" . h($who) . "</b>";   // velado por ghosteo: como un anonimo mas
    echo "<span class=\"meta\"> · " . h(hace($m['created'])) . (!empty($m['edited_at']) && !$del ? " · " . t('editado') : "") . "</span>" . ($anun ? " <span class=\"anun\">" . icon('mega') . t('Mensaje a la comunidad') . "</span>" : "") . "</div>";
    if ($del) echo "<p class=\"delmsg\"><i>" . h(t(DDD_CHAT_DELETED_TXT)) . "</i> · <a href=\"/normas\">" . t('normas') . "</a></p>";
    else      echo "<p>" . chat_format($m['content'], $logins, $ids) . "</p>";
    $acts = [];
    if (!$del && $canWrite) $acts[] = "<a class=\"replylink\" href=\"" . h($base . $amp) . "r=$id#m$id\">" . icon('reply') . "<span>" . t('responder') . "</span></a>";
    if (!$hilos && !empty($replies[$id])) $acts[] = "<span class=\"reps\">" . t('respuestas:') . " " . implode(' ', array_map(fn($r) => "<a class=\"ref\" href=\"" . (isset($ids[$r]) ? "#m$r" : "/chat?m=$r#m$r") . "\">#$r</a>", $replies[$id])) . "</span>";
    if (!$del && chat_can_delete_own($m, $u, $u ? null : $anonKey)) $acts[] = "<form method=\"post\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"id\" value=\"$id\"><button class=\"link\" title=\"" . h(t('solo durante %d min', (int)cfg('chat_delete_minutes', 5))) . "\">" . t('borrar') . "</button></form>";
    if (!$del) $acts[] = "<a class=\"replink\" href=\"/reportar?chat=$id\">" . t('reportar') . "</a>";
    if (is_admin() && !$del) $acts[] = "<a class=\"modlink\" href=\"/editar-chat?id=$id\">" . t('editar') . "</a>";   // solo administradores (migra-094); los moderadores, solo eliminar
    if (is_mod() && !$del) $acts[] = "<form method=\"post\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"anuncio\"><input type=\"hidden\" name=\"id\" value=\"$id\"><button class=\"link anunbtn" . ($anun ? ' on' : '') . "\" title=\"" . h($anun ? t('quitar la marca de mensaje a la comunidad') : t('marcar como mensaje a la comunidad')) . "\" aria-label=\"" . h(t('Mensaje a la comunidad')) . "\">" . icon('mega') . "</button></form>";   // megafono: mensaje a la comunidad (migra-121)
    if (is_mod() && !$del) $acts[] = "<form method=\"post\" action=\"/moderacion\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"chat_borrar\"><input type=\"hidden\" name=\"mid\" value=\"$id\"><input type=\"hidden\" name=\"back\" value=\"1\"><button class=\"link modlink\">" . t('eliminar') . "</button></form>";
    if ($acts || !$del) {
        echo "<div class=\"actions\">" . implode('', $acts);
        // sol / lagrima: registrados sobre mensajes ajenos; el resto (y el autor) ve solo los contadores
        if (!$del) comment_vote_widget($m, $u && (int)$m['user_id'] !== (int)$u['user_id'], '/votar-chat', 'msg_id');
        echo "</div>";
    }
    echo "</article>";
    if ($canWrite && $replyHere && $id === $replyTo) $box(true);
}
echo "</div>";
paginate($base, $page, $total, $hilos ? 20 : $per);
page_footer();
