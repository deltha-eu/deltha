<?php
/** lib/avisos.php — avisos (la campanita): una sola tabla, `notifications`, y una sola burbuja con el numero de no leidos
 *  delante del avatar (cabecera en movil, rail en escritorio). Tipos:
 *    mention_comment  alguien escribe @tunick en un comentario      → /hilo.php?id=S#cN
 *    reply_comment    alguien responde a un comentario tuyo         → /hilo.php?id=S#cN
 *    mention_chat     alguien escribe @tunick en el Chat            → /chat.php?m=N#mN
 *    reply_chat       alguien cita (#N) un mensaje tuyo del Chat    → /chat.php?m=N#mN
 *    dm               alguien te manda un privado                   → /mensajes.php?u=nick
 *  Se enganchan donde se guarda cada cosa (comentar.php, editar-comentario.php, chat.php, mensajes.php). En la fila se guarda
 *  el tipo y el id del origen (no la URL, que se construye al pintar) y un trozo del texto de entonces. La lista (/avisos.php)
 *  es el historico: nada se borra, solo cambia read_at. Un aviso pasa a leido al pulsarlo (o con «marcar todo como leido»);
 *  los privados, ademas, al abrir la conversacion. Los privados seguidos de la misma persona se agrupan en un aviso
 *  mientras este sin leer; leido, el siguiente crea aviso nuevo.
 *  En los comentarios el que avisa firma con su alias del hilo (Ghost): el aviso guarda ese alias y solo enseña el nick
 *  cuando ya han pasado los ghost_minutes del comentario, igual que el hilo. sql/migra-068-avisos.sql */
declare(strict_types=1);

const DDD_NICK_RE = '/(?<![\w@])@([A-Za-z0-9_\-]{3,32})\b/';

/** Avisos activos (config.notif_enabled y tabla creada). */
function avisos_on(): bool {
    static $on = null; global $pdo;
    if ($on !== null) return $on;
    if ((string)cfg('notif_enabled', '1') !== '1') return $on = false;
    try { $pdo->query("SELECT 1 FROM notifications LIMIT 1"); return $on = true; } catch (Throwable $e) { return $on = false; }
}

/** Nicks mencionados con @ en un texto, en minusculas y sin repetir (misma regla que el Chat y que los nicks al registrarse). */
function avisos_nicks(string $text): array {
    preg_match_all(DDD_NICK_RE, $text, $m);
    return array_values(array_unique(array_map('strtolower', $m[1])));
}
/** De una lista de nicks (minusculas) a los usuarios que existen: [nick_min => ['user_id' => n, 'login' => 'Nick']]. */
function avisos_users_by_nick(PDO $pdo, array $nicks): array {
    if (!$nicks) return [];
    $q = $pdo->prepare("SELECT user_id, login FROM users WHERE LOWER(login) IN (" . implode(',', array_fill(0, count($nicks), '?')) . ")");
    $q->execute(array_values($nicks));
    $out = [];
    foreach ($q->fetchAll() as $r) $out[strtolower($r['login'])] = ['user_id' => (int)$r['user_id'], 'login' => $r['login']];
    return $out;
}
function aviso_snippet(string $text): string { return mb_strimwidth(trim(preg_replace('/\s+/u', ' ', $text)), 0, 80, '…'); }

/** Inserta un aviso. Nunca lanza: si algo falla, la accion original (comentar, escribir) no se resiente. */
function aviso_insert(PDO $pdo, int $to, ?int $from, string $fromName, string $kind, int $ref, ?int $story, string $text): void {
    if (!avisos_on() || $to <= 0 || ($from !== null && $from === $to)) return;
    try {
        $pdo->prepare("INSERT INTO notifications (user_id, from_id, from_name, kind, ref_id, story_id, snippet) VALUES (?,?,?,?,?,?,?)")
            ->execute([$to, $from, mb_substr($fromName, 0, 64), $kind, $ref, $story, aviso_snippet($text)]);
    } catch (Throwable $e) { error_log('aviso: ' . $e->getMessage()); }
}

/** Comentario nuevo: avisa a los @mencionados y, si responde a un comentario de un registrado, a su autor (una vez, aunque tambien lo mencione).
 *  $c = fila del comentario (comment_id, story_id, user_id, alias, content, parent_id); $parent = fila del comentario padre o null;
 *  $skip = nicks (minusculas) ya avisados en una version anterior del texto (edicion). */
function avisos_comment(PDO $pdo, array $c, ?array $parent = null, array $skip = []): void {
    if (!avisos_on()) return;
    $from = !empty($c['user_id']) ? (int)$c['user_id'] : null;
    $name = (string)$c['alias'];   // el alias del hilo: el nick solo se enseña cuando el hilo tambien lo revela
    $done = [];
    foreach (avisos_users_by_nick($pdo, array_diff(avisos_nicks((string)$c['content']), $skip)) as $u) {
        aviso_insert($pdo, $u['user_id'], $from, $name, 'mention_comment', (int)$c['comment_id'], (int)$c['story_id'], (string)$c['content']);
        $done[$u['user_id']] = true;
    }
    if ($parent && !empty($parent['user_id']) && !isset($done[(int)$parent['user_id']])) {
        aviso_insert($pdo, (int)$parent['user_id'], $from, $name, 'reply_comment', (int)$c['comment_id'], (int)$c['story_id'], (string)$c['content']);
    }
}
/** Mensaje nuevo del Chat: @mencionados y, si cita #N de un registrado, su autor. $who = nick o alias anonimo de quien escribe. */
function avisos_chat(PDO $pdo, int $mid, ?int $uid, string $who, string $content, ?int $replyTo): void {
    if (!avisos_on()) return;
    $done = [];
    foreach (avisos_users_by_nick($pdo, avisos_nicks($content)) as $u) {
        aviso_insert($pdo, $u['user_id'], $uid, $who, 'mention_chat', $mid, null, $content);
        $done[$u['user_id']] = true;
    }
    if ($replyTo) {
        $q = $pdo->prepare("SELECT user_id FROM chat WHERE msg_id = ?"); $q->execute([$replyTo]);
        $to = (int)$q->fetchColumn();
        if ($to && !isset($done[$to])) aviso_insert($pdo, $to, $uid, $who, 'reply_chat', $mid, null, $content);
    }
}
/** Privado nuevo: un aviso por persona mientras no se lea (se actualiza con el ultimo mensaje); leido, el siguiente crea otro. */
function aviso_dm(PDO $pdo, int $from, string $fromLogin, int $to, int $msgId, string $content): void {
    if (!avisos_on()) return;
    try {
        $st = $pdo->prepare("UPDATE notifications SET ref_id = ?, snippet = ?, created = NOW() WHERE user_id = ? AND from_id = ? AND kind = 'dm' AND read_at IS NULL");
        $st->execute([$msgId, aviso_snippet($content), $to, $from]);
        if ($st->rowCount() === 0) aviso_insert($pdo, $to, $from, $fromLogin, 'dm', $msgId, null, $content);
    } catch (Throwable $e) { error_log('aviso dm: ' . $e->getMessage()); }
}
/** Al abrir la conversacion con alguien, sus avisos de privado quedan leidos. */
function avisos_dm_read(PDO $pdo, int $me, int $other): void {
    if (!avisos_on()) return;
    try { $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND from_id = ? AND kind = 'dm' AND read_at IS NULL")->execute([$me, $other]); } catch (Throwable $e) {}
}

/** Avisos sin leer del usuario actual (una consulta por pagina, cacheada). */
function avisos_unread(): int {
    static $n = null; global $pdo;
    if ($n !== null) return $n;
    $n = 0;
    if (user() && avisos_on()) {
        try { $st = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL"); $st->execute([user()['user_id']]); $n = (int)$st->fetchColumn(); }
        catch (Throwable $e) {}
    }
    return $n;
}
/** La campanita: enlace a /avisos.php con la burbuja de no leidos (vacia si no hay). Solo para registrados. */
function avisos_bell(string $cls = ''): string {
    if (!user() || !avisos_on()) return '';
    $n = avisos_unread();
    return "<a class=\"bell" . ($cls !== '' ? " $cls" : '') . ($n ? ' has' : '') . "\" href=\"/avisos.php\" title=\"" . t('Avisos') . "\" data-bell>" . icon('bell')
        . "<i class=\"badge\"" . ($n ? '' : ' hidden') . ">" . ($n > 99 ? '99+' : $n) . "</i><span>" . t('Avisos') . "</span></a>";
}

/** Adonde lleva un aviso. */
function aviso_href(array $n): string {
    switch ($n['kind']) {
        case 'mention_comment': case 'reply_comment': return "/hilo.php?id=" . (int)$n['story_id'] . "#c" . (int)$n['ref_id'];
        case 'mention_chat':    case 'reply_chat':    return "/chat.php?m=" . (int)$n['ref_id'] . "#m" . (int)$n['ref_id'];
        case 'dm': return !empty($n['login']) ? "/mensajes.php?u=" . rawurlencode($n['login']) : "/mensajes.php";
    }
    return '/avisos.php';
}
/** Como se llama quien avisa, ahora: en comentarios el alias hasta que el hilo revela el nick; en el Chat y privados, el nick (o el alias anonimo). */
function aviso_who(array $n): string {
    if (in_array($n['kind'], ['mention_comment', 'reply_comment'], true)) return (!empty($n['login']) && ghost_over($n['created'])) ? $n['login'] : ($n['from_name'] ?: t('anónimo'));
    return $n['login'] ?: ($n['from_name'] ?: t('anónimo'));
}
/** Texto corto de la linea («Fulano te ha mencionado en un comentario»). $who ya escapado. */
function aviso_text(array $n, string $who): string {
    return match ($n['kind']) {
        'mention_comment' => t('<b>%s</b> te ha mencionado en un comentario', $who),
        'reply_comment'   => t('<b>%s</b> ha respondido a tu comentario', $who),
        'mention_chat'    => t('<b>%s</b> te ha mencionado en el Chat', $who),
        'reply_chat'      => t('<b>%s</b> ha citado tu mensaje del Chat', $who),
        'dm'              => t('<b>%s</b> te ha enviado un mensaje', $who),
        default           => $who,
    };
}
/** Icono por tipo (el mismo que la pestaña de origen). */
function aviso_icon(array $n): string {
    return icon(match ($n['kind']) { 'mention_chat', 'reply_chat' => 'chat', 'dm' => 'msg', default => 'reply' });
}

/* ---- @nick en los comentarios: enlace al perfil si el usuario existe ----------------------------------------------
 * Los nicks se buscan de una vez por pagina (avisos_preload_nicks con todos los textos) y comment_block() los enlaza. */
$DDD_NICKS = [];
function avisos_preload_nicks(PDO $pdo, array $texts): void {
    global $DDD_NICKS;
    $all = [];
    foreach ($texts as $t) foreach (avisos_nicks((string)$t) as $n) $all[$n] = true;
    foreach (avisos_users_by_nick($pdo, array_keys($all)) as $k => $u) $DDD_NICKS[$k] = $u['login'];
}
/** HTML (ya escapado) → cada @nick existente enlazado a /u.php. */
function nicks_format(string $html): string {
    global $DDD_NICKS;
    if (!$DDD_NICKS || !str_contains($html, '@')) return $html;
    return preg_replace_callback(DDD_NICK_RE, function ($m) use ($DDD_NICKS) {
        $k = strtolower($m[1]);
        return isset($DDD_NICKS[$k]) ? "<a class=\"at\" href=\"/u.php?u=" . rawurlencode($DDD_NICKS[$k]) . "\">@" . h($DDD_NICKS[$k]) . "</a>" : $m[0];
    }, $html);
}
