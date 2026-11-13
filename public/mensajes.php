<?php
/**
 * /mensajes — mensajes privados entre registrados.
 *
 *  /mensajes            bandeja: una linea por conversacion (ultimo mensaje, no leidos)
 *  /mensajes?u=nick     conversacion con ese usuario + cuadro para escribir
 *  POST to + content        envia (y vuelve a la conversacion)
 *
 * Con nick real: un privado no tiene sentido con alias. Solo registrados y verificados.
 *  /mensajes?u=nick&e=N  corregir un mensaje propio: el cuadro de escribir sale con su texto (migra-199)
 *  POST to + edit + content   guarda la corrección; solo el autor y durante msg_edit_minutes (config, 10; 0 = no se edita).
 *                             Queda marcado como «editado»; no cuenta para msg_per_hour ni vuelve a avisar al destinatario.
 * Limite msg_per_hour por remitente (config). Sin JS, sin refresco automatico (no se
 * pierde lo que se este escribiendo). Los moderadores no ven los privados de nadie.
 */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
$me = user();
if (!$me) redirect('/login');
if ((string)cfg('msg_enabled', '1') !== '1') { page_header(t('Mensajes'), 'mensajes'); echo "<p class=\"end\">" . t('Los mensajes privados están desactivados.') . "</p>"; page_footer(); exit; }
$myId = (int)$me['user_id'];
$MAX  = 2000;

function msg_user(PDO $pdo, string $login): ?array {
    $st = $pdo->prepare("SELECT user_id, login, avatar, aura FROM users WHERE login = ? AND verified = 1");
    $st->execute([$login]);
    $r = $st->fetch();
    return ($r && !ghost_veiled($r['user_id'])) ? $r : null;   // ghosteo (en cualquier sentido): sin privados entre ambos (lib/ghost.php)
}

/** ¿Puedo corregir todavía este mensaje? Solo los míos y durante msg_edit_minutes desde el envío (migra-199). */
function msg_editable(array $m, int $myId): bool {
    $min = (int)cfg('msg_edit_minutes', 10);
    return $min > 0 && (int)$m['from_id'] === $myId && time() - strtotime((string)$m['created']) < $min * 60;
}

/* ---- enviar ------------------------------------------------------- */
$err = '';
$editId = 0;   // mensaje propio que se está corrigiendo (migra-199)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toLogin = post('to');
    $content = trim((string)($_POST['content'] ?? ''));
    $to = msg_user($pdo, $toLogin);
    $editId = (int)post('edit');   // > 0: no es un mensaje nuevo, es la corrección de uno propio (migra-199); se conserva si hay error para volver al modo edición
    if (!$to)                                   $err = t('Ese usuario no existe.');
    elseif ((int)$to['user_id'] === $myId)      $err = t('No puedes escribirte a ti mismo.');
    elseif ($content === '')                    $err = t('El mensaje está vacío.');
    elseif (mb_strlen($content) > $MAX)         $err = t('Máximo %d caracteres.', $MAX);
    elseif ($editId > 0) {   // corrección de un mensaje propio, en plazo (migra-199)
        $q = $pdo->prepare("SELECT msg_id, from_id, content, created FROM messages WHERE msg_id = ? AND from_id = ? AND to_id = ?");
        $q->execute([$editId, $myId, (int)$to['user_id']]);
        $old = $q->fetch();
        if (!$old || !msg_editable($old, $myId)) { $err = t('Ese mensaje ya no se puede editar: el plazo es de %d minutos.', (int)cfg('msg_edit_minutes', 10)); $editId = 0; }
        else {
            if ($content !== (string)$old['content']) {
                $pdo->prepare("UPDATE messages SET content = ?, edited_at = NOW() WHERE msg_id = ?")->execute([$content, $editId]);
                try { $pdo->prepare("UPDATE notifications SET snippet = ? WHERE user_id = ? AND kind = 'dm' AND ref_id = ? AND read_at IS NULL")->execute([aviso_snippet($content), (int)$to['user_id'], $editId]); } catch (Throwable $e) {}   // el aviso sin leer enseña el texto corregido
            }
            redirect('/mensajes?u=' . rawurlencode($to['login']) . "#m$editId");
        }
    }
    else {
        $lim = max(1, (int)cfg('msg_per_hour', 30));
        $n = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE from_id = ? AND created >= NOW() - INTERVAL 1 HOUR");
        $n->execute([$myId]);
        if ((int)$n->fetchColumn() >= $lim) $err = t('Has enviado muchos mensajes en poco tiempo; espera un rato.');
        else {
            $pdo->prepare("INSERT INTO messages (from_id, to_id, content) VALUES (?,?,?)")->execute([$myId, $to['user_id'], $content]);
            aviso_dm($pdo, $myId, (string)$me['login'], (int)$to['user_id'], (int)$pdo->lastInsertId(), $content);   // campanita del destinatario
            redirect('/mensajes?u=' . rawurlencode($to['login']) . '#fin');
        }
    }
    $u = ($to && (int)$to['user_id'] !== $myId) ? $toLogin : '';   // con error: volver a la conversacion (o a la bandeja) mostrandolo
} else {
    $u = trim((string)($_GET['u'] ?? ''));
    $editId = (int)($_GET['e'] ?? 0);
}

/* ---- conversacion ------------------------------------------------- */
if ($u !== '') {
    $other = msg_user($pdo, $u);
    if (!$other) { http_response_code(404); page_header(t('Mensajes'), 'mensajes'); echo "<p class=\"end\">" . t('Ese usuario no existe. <a href="/mensajes">Volver a los mensajes</a>') . "</p>"; page_footer(); exit; }
    $oid = (int)$other['user_id'];
    if ($oid === $myId) redirect('/mensajes');

    // lo que me ha escrito queda leido al abrir la conversacion
    $pdo->prepare("UPDATE messages SET read_at = NOW() WHERE to_id = ? AND from_id = ? AND read_at IS NULL")->execute([$myId, $oid]);
    avisos_dm_read($pdo, $myId, $oid);

    $st = $pdo->prepare("SELECT * FROM (SELECT msg_id, from_id, content, created, edited_at FROM messages
                          WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?)
                          ORDER BY msg_id DESC LIMIT 200) x ORDER BY msg_id ASC");
    $st->execute([$myId, $oid, $oid, $myId]);
    $msgs = $st->fetchAll();

    page_header($other['login'], 'mensajes');
    echo "<div class=\"convhead\"><a href=\"/mensajes\"" . t('>← mensajes</a>') . "";
    echo avatar((float)$other['aura'], $other['login'], $other['avatar'], '/u/' . rawurlencode($other['login']));
    echo "<b><a href=\"/u/" . rawurlencode($other['login']) . "\">" . h($other['login']) . "</a></b></div>";
    if (!$msgs) echo "<p class=\"end\">" . t('Todavía no os habéis escrito. Lo que escribas abajo solo lo verá <b>') . "" . h($other['login']) . "</b>.</p>";
    $lastDay = '';
    $editing = null;   // el mensaje que se corrige, si es mío y sigue en plazo
    if ($editId) { foreach ($msgs as $m) if ((int)$m['msg_id'] === $editId && msg_editable($m, $myId)) $editing = $m; if (!$editing && !$err) $err = t('Ese mensaje ya no se puede editar: el plazo es de %d minutos.', (int)cfg('msg_edit_minutes', 10)); }
    $conv = '/mensajes?u=' . rawurlencode($other['login']);
    foreach ($msgs as $m) {
        $day = date('Y-m-d', strtotime($m['created']));
        if ($day !== $lastDay) { echo "<div class=\"msgday\">" . h(msg_day($day)) . "</div>"; $lastDay = $day; }
        $mine = (int)$m['from_id'] === $myId;
        echo "<div class=\"msg " . ($mine ? 'me' : 'them') . "\" id=\"m{$m['msg_id']}\"><div class=\"bubble\">" . md_comentario($m['content']) . "</div><span class=\"meta\">" . date('H:i', strtotime($m['created']))
           . (!empty($m['edited_at']) ? ' · ' . t('editado') : '')
           . ($mine && msg_editable($m, $myId) ? " · <a href=\"$conv&amp;e={$m['msg_id']}#fin\">" . t('editar') . "</a>" : '') . "</span></div>";
    }
    echo "<span id=\"fin\"></span>";
    if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
    if ($editing) echo "<p class=\"notice\">" . t('Estás corrigiendo un mensaje ya enviado; quedará marcado como editado.') . " <a href=\"$conv#m{$editing['msg_id']}\">" . t('cancelar') . "</a></p>";
    echo "<form class=\"composer\" method=\"post\" action=\"/mensajes\"><input type=\"hidden\" name=\"to\" value=\"" . h($other['login']) . "\">" . ($editing ? "<input type=\"hidden\" name=\"edit\" value=\"" . (int)$editing['msg_id'] . "\">" : '');
    echo avatar((float)$me['aura'], $me['login'], $me['avatar']);
    echo "<div class=\"cbox\"><textarea name=\"content\" maxlength=\"$MAX\" placeholder=\"" . t('Mensaje para %s', h($other['login'])) . "\" required autofocus>" . h($err && isset($content) ? $content : ($editing ? (string)$editing['content'] : '')) . "</textarea>" . md_ayuda() . ($editing ? t('<button>Guardar</button>') : t('<button>Enviar</button>')) . "</div></form>";
    page_footer();
    exit;
}

/* ---- bandeja -------------------------------------------------------- */
$st = $pdo->prepare("SELECT IF(from_id = ?, to_id, from_id) AS other, MAX(msg_id) AS last_id,
                            SUM(to_id = ? AND read_at IS NULL) AS unread
                       FROM messages WHERE from_id = ? OR to_id = ?
                      GROUP BY other ORDER BY last_id DESC LIMIT 100");
$st->execute([$myId, $myId, $myId, $myId]);
$convs = array_values(array_filter($st->fetchAll(), fn($c) => !ghost_veiled($c['other'])));   // las conversaciones con velados por ghosteo no salen en la bandeja

page_header(t('Mensajes'), 'mensajes');
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"newmsg\" method=\"get\" action=\"/mensajes\"><input type=\"text\" name=\"u\" placeholder=\"" . t('Escribir a… (nick)') . "\" maxlength=\"32\" required autocomplete=\"off\">" . t('<button>Abrir</button>') . "</form>";
if (!$convs) echo "<p class=\"end\">" . t('No tienes conversaciones. Escribe a alguien por su nick, o desde su perfil con «enviar mensaje».') . "</p>";
if ($convs) {
    $ids = implode(',', array_map(fn($c) => (int)$c['other'], $convs));
    $lastIds = implode(',', array_map(fn($c) => (int)$c['last_id'], $convs));
    $U = []; foreach ($pdo->query("SELECT user_id, login, avatar, aura FROM users WHERE user_id IN ($ids)") as $r) $U[(int)$r['user_id']] = $r;
    $L = []; foreach ($pdo->query("SELECT msg_id, from_id, content, created FROM messages WHERE msg_id IN ($lastIds)") as $r) $L[(int)$r['msg_id']] = $r;
    foreach ($convs as $c) {
        $o = $U[(int)$c['other']] ?? null; $l = $L[(int)$c['last_id']] ?? null;
        if (!$o || !$l) continue;   // cuenta borrada
        $href = '/mensajes?u=' . rawurlencode($o['login']);
        $prev = ((int)$l['from_id'] === $myId ? t('Tú') . ': ' : '') . mb_strimwidth(preg_replace('/\s+/', ' ', $l['content']), 0, 90, '…');
        echo "<a class=\"fz conv" . ($c['unread'] ? ' unread' : '') . "\" href=\"$href\">" . avatar((float)$o['aura'], $o['login'], $o['avatar']);
        echo "<div class=\"fzt\"><b>" . h($o['login']) . "</b><span class=\"meta\"> · " . h(hace($l['created'])) . "</span><div class=\"prev\">" . h($prev) . "</div></div>";
        if ($c['unread']) echo "<span class=\"badge\">" . (int)$c['unread'] . "</span>";
        echo "</a>";
    }
}
page_footer();

function msg_day(string $ymd): string {
    if ($ymd === date('Y-m-d')) return t('Hoy');
    if ($ymd === date('Y-m-d', strtotime('-1 day'))) return t('Ayer');
    $meses = explode(',', t('enero,febrero,marzo,abril,mayo,junio,julio,agosto,septiembre,octubre,noviembre,diciembre'));
    [$y, $m, $d] = explode('-', $ymd);
    return t('%d de %s', (int)$d, $meses[(int)$m - 1]) . ($y !== date('Y') ? t(' de %d', (int)$y) : '');
}
