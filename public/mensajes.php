<?php
/**
 * /mensajes.php — mensajes privados entre registrados.
 *
 *  /mensajes.php            bandeja: una linea por conversacion (ultimo mensaje, no leidos)
 *  /mensajes.php?u=nick     conversacion con ese usuario + cuadro para escribir
 *  POST to + content        envia (y vuelve a la conversacion)
 *
 * Con nick real: un privado no tiene sentido con alias. Solo registrados y verificados.
 * Limite msg_per_hour por remitente (config). Sin JS, sin refresco automatico (no se
 * pierde lo que se este escribiendo). Los moderadores no ven los privados de nadie.
 */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
$me = user();
if (!$me) redirect('/login.php');
if ((string)cfg('msg_enabled', '1') !== '1') { page_header(t('Mensajes'), 'mensajes'); echo "<p class=\"end\">" . t('Los mensajes privados están desactivados.') . "</p>"; page_footer(); exit; }
$myId = (int)$me['user_id'];
$MAX  = 2000;

function msg_user(PDO $pdo, string $login): ?array {
    $st = $pdo->prepare("SELECT user_id, login, avatar, aura FROM users WHERE login = ? AND verified = 1");
    $st->execute([$login]);
    return $st->fetch() ?: null;
}

/* ---- enviar ------------------------------------------------------- */
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toLogin = post('to');
    $content = trim((string)($_POST['content'] ?? ''));
    $to = msg_user($pdo, $toLogin);
    if (!$to)                                   $err = t('Ese usuario no existe.');
    elseif ((int)$to['user_id'] === $myId)      $err = t('No puedes escribirte a ti mismo.');
    elseif ($content === '')                    $err = t('El mensaje está vacío.');
    elseif (mb_strlen($content) > $MAX)         $err = t('Máximo %d caracteres.', $MAX);
    else {
        $lim = max(1, (int)cfg('msg_per_hour', 30));
        $n = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE from_id = ? AND created >= NOW() - INTERVAL 1 HOUR");
        $n->execute([$myId]);
        if ((int)$n->fetchColumn() >= $lim) $err = t('Has enviado muchos mensajes en poco tiempo; espera un rato.');
        else {
            $pdo->prepare("INSERT INTO messages (from_id, to_id, content) VALUES (?,?,?)")->execute([$myId, $to['user_id'], $content]);
            aviso_dm($pdo, $myId, (string)$me['login'], (int)$to['user_id'], (int)$pdo->lastInsertId(), $content);   // campanita del destinatario
            redirect('/mensajes.php?u=' . rawurlencode($to['login']) . '#fin');
        }
    }
    $u = ($to && (int)$to['user_id'] !== $myId) ? $toLogin : '';   // con error: volver a la conversacion (o a la bandeja) mostrandolo
} else {
    $u = trim((string)($_GET['u'] ?? ''));
}

/* ---- conversacion ------------------------------------------------- */
if ($u !== '') {
    $other = msg_user($pdo, $u);
    if (!$other) { http_response_code(404); page_header(t('Mensajes'), 'mensajes'); echo "<p class=\"end\">" . t('Ese usuario no existe. <a href="/mensajes.php">Volver a los mensajes</a>') . "</p>"; page_footer(); exit; }
    $oid = (int)$other['user_id'];
    if ($oid === $myId) redirect('/mensajes.php');

    // lo que me ha escrito queda leido al abrir la conversacion
    $pdo->prepare("UPDATE messages SET read_at = NOW() WHERE to_id = ? AND from_id = ? AND read_at IS NULL")->execute([$myId, $oid]);
    avisos_dm_read($pdo, $myId, $oid);

    $st = $pdo->prepare("SELECT * FROM (SELECT msg_id, from_id, content, created FROM messages
                          WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?)
                          ORDER BY msg_id DESC LIMIT 200) x ORDER BY msg_id ASC");
    $st->execute([$myId, $oid, $oid, $myId]);
    $msgs = $st->fetchAll();

    page_header($other['login'], 'mensajes');
    echo "<div class=\"convhead\"><a href=\"/mensajes.php\"" . t('>← mensajes</a>') . "";
    echo avatar((float)$other['aura'], $other['login'], $other['avatar'], '/u.php?u=' . rawurlencode($other['login']));
    echo "<b><a href=\"/u.php?u=" . rawurlencode($other['login']) . "\">" . h($other['login']) . "</a></b></div>";
    if (!$msgs) echo "<p class=\"end\">" . t('Todavía no os habéis escrito. Lo que escribas abajo solo lo verá <b>') . "" . h($other['login']) . "</b>.</p>";
    $lastDay = '';
    foreach ($msgs as $m) {
        $day = date('Y-m-d', strtotime($m['created']));
        if ($day !== $lastDay) { echo "<div class=\"msgday\">" . h(msg_day($day)) . "</div>"; $lastDay = $day; }
        $mine = (int)$m['from_id'] === $myId;
        echo "<div class=\"msg " . ($mine ? 'me' : 'them') . "\" id=\"m{$m['msg_id']}\"><div class=\"bubble\">" . h($m['content']) . "</div><span class=\"meta\">" . date('H:i', strtotime($m['created'])) . "</span></div>";
    }
    echo "<span id=\"fin\"></span>";
    if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
    echo "<form class=\"composer\" method=\"post\" action=\"/mensajes.php\"><input type=\"hidden\" name=\"to\" value=\"" . h($other['login']) . "\">";
    echo avatar((float)$me['aura'], $me['login'], $me['avatar']);
    echo "<div class=\"cbox\"><textarea name=\"content\" maxlength=\"$MAX\" placeholder=\"" . t('Mensaje para %s', h($other['login'])) . "\" required autofocus>" . ($err ? h($content ?? '') : '') . "</textarea>" . t('<button>Enviar</button>') . "</div></form>";
    page_footer();
    exit;
}

/* ---- bandeja -------------------------------------------------------- */
$st = $pdo->prepare("SELECT IF(from_id = ?, to_id, from_id) AS other, MAX(msg_id) AS last_id,
                            SUM(to_id = ? AND read_at IS NULL) AS unread
                       FROM messages WHERE from_id = ? OR to_id = ?
                      GROUP BY other ORDER BY last_id DESC LIMIT 100");
$st->execute([$myId, $myId, $myId, $myId]);
$convs = $st->fetchAll();

page_header(t('Mensajes'), 'mensajes');
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"newmsg\" method=\"get\" action=\"/mensajes.php\"><input type=\"text\" name=\"u\" placeholder=\"" . t('Escribir a… (nick)') . "\" maxlength=\"32\" required autocomplete=\"off\">" . t('<button>Abrir</button>') . "</form>";
if (!$convs) echo "<p class=\"end\">" . t('No tienes conversaciones. Escribe a alguien por su nick, o desde su perfil con «enviar mensaje».') . "</p>";
if ($convs) {
    $ids = implode(',', array_map(fn($c) => (int)$c['other'], $convs));
    $lastIds = implode(',', array_map(fn($c) => (int)$c['last_id'], $convs));
    $U = []; foreach ($pdo->query("SELECT user_id, login, avatar, aura FROM users WHERE user_id IN ($ids)") as $r) $U[(int)$r['user_id']] = $r;
    $L = []; foreach ($pdo->query("SELECT msg_id, from_id, content, created FROM messages WHERE msg_id IN ($lastIds)") as $r) $L[(int)$r['msg_id']] = $r;
    foreach ($convs as $c) {
        $o = $U[(int)$c['other']] ?? null; $l = $L[(int)$c['last_id']] ?? null;
        if (!$o || !$l) continue;   // cuenta borrada
        $href = '/mensajes.php?u=' . rawurlencode($o['login']);
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
