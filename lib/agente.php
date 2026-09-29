<?php
/**
 * lib/agente.php — agente comentarista (bot con IA local). sql/migra-089-agente-comentarista.sql
 *
 *  El worker del PC de la redaccion (local/comentarista/comentarista.py) pide hilos candidatos a /api/agente.php,
 *  baja el articulo, lo pasa por el modelo local con la personalidad de local/comentarista/personalidad.md y devuelve
 *  un comentario (o «paso»). Aqui: que hilos se le ofrecen, el tope diario y la publicacion del comentario a nombre
 *  de la cuenta bot (config.agent_user) por el mismo camino que /comentar.php: alias Ghost del hilo, contador del
 *  hilo, cola de moderacion. Sin aura (un bot no debe ganar aura: con mod_aura llegaria a moderar).
 *  agent_seen recuerda que hilos ha visto ya y que hizo con cada uno.
 */
declare(strict_types=1);

function agent_on(): bool { return (string)cfg('agent_enabled', '0') === '1' && trim((string)cfg('agent_user', '')) !== ''; }

/** La cuenta bot (users) o null si no existe o esta sin activar. */
function agent_user(PDO $pdo): ?array {
    $login = trim((string)cfg('agent_user', ''));
    if ($login === '') return null;
    $st = $pdo->prepare("SELECT user_id, login, aura, bio FROM users WHERE login = ? AND verified = 1");
    $st->execute([$login]);
    return $st->fetch() ?: null;
}

/** Comentarios del bot hoy (hora del servidor). */
function agent_today(PDO $pdo, int $uid): int {
    $st = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ? AND created >= CURDATE()");
    $st->execute([$uid]);
    return (int)$st->fetchColumn();
}
function agent_quota_left(PDO $pdo, int $uid): int {
    return max(0, (int)cfg('agent_per_day', 4) - agent_today($pdo, $uid));
}

/** Lista de ids/nicks de una clave de config separada por comas. */
function agent_cfg_list(string $k): array {
    return array_values(array_filter(array_map('trim', explode(',', (string)cfg($k, ''))), fn($x) => $x !== ''));
}

/** Hilos candidatos: abiertos, con la edad y los comentarios que marca la config, no del bot ni de los nicks excluidos,
 *  del tema permitido y no vistos ya (o con la reserva caducada). Los reserva (agent_seen.leased) y devuelve lo que
 *  necesita el worker: titular, entradilla, enlace, fuente, tema y los comentarios que ya hay (el idioma lo ve el modelo en el texto). */
function agent_lease(PDO $pdo, array $bot, int $n): array {
    $minAge = max(0, (int)cfg('agent_min_age_min', 20));
    $maxAge = max(1, (int)cfg('agent_max_age_h', 24));
    $maxCom = max(0, (int)cfg('agent_max_comments', 6));
    $lease  = max(60, (int)cfg('agent_lease_sec', 600));
    $skip   = agent_cfg_list('agent_skip_users');
    $topics = array_map('intval', agent_cfg_list('agent_topics'));

    $w = ["s.status IN ('nuevas','destacadas')", "s.sent_date <= NOW() - INTERVAL $minAge MINUTE", "s.sent_date >= NOW() - INTERVAL $maxAge HOUR",
          "s.comments <= $maxCom", "(s.user_id IS NULL OR s.user_id <> " . (int)$bot['user_id'] . ")",
          "(a.story_id IS NULL OR (a.result = 'leased' AND a.leased_until < NOW()))"];
    $p = [];
    if ($skip)   { $w[] = "(u.login IS NULL OR u.login NOT IN (" . implode(',', array_fill(0, count($skip), '?')) . "))"; $p = array_merge($p, $skip); }
    if ($topics) { $w[] = "s.topic_id IN (" . implode(',', $topics) . ")"; }
    $st = $pdo->prepare("SELECT s.story_id, s.content_type, s.title, s.url, s.source, s.content, s.sent_date, s.comments, s.status, t.name AS topic, u.login AS author
                           FROM stories s JOIN topics t ON t.topic_id = s.topic_id LEFT JOIN users u ON u.user_id = s.user_id
                           LEFT JOIN agent_seen a ON a.story_id = s.story_id
                          WHERE " . implode(' AND ', $w) . " ORDER BY s.sent_date DESC LIMIT " . max(1, min(10, $n)));
    $st->execute($p);
    $items = [];
    $cs = $pdo->prepare("SELECT c.comment_id, c.parent_id, c.alias, c.content, c.created, u.login FROM comments c LEFT JOIN users u ON u.user_id = c.user_id
                          WHERE c.story_id = ? AND c.status = 'ok' AND c.deleted = 0 ORDER BY c.created ASC LIMIT 12");
    foreach ($st->fetchAll() as $s) {
        $pdo->prepare("INSERT INTO agent_seen (story_id, result, leased_until) VALUES (?, 'leased', NOW() + INTERVAL $lease SECOND)
                       ON DUPLICATE KEY UPDATE result = 'leased', leased_until = VALUES(leased_until)")->execute([$s['story_id']]);
        $cs->execute([$s['story_id']]);
        $comments = [];
        foreach ($cs->fetchAll() as $c) $comments[] = ['id' => (int)$c['comment_id'], 'parent' => (int)$c['parent_id'] ?: null,
            'who' => display_name($c, ghost_over($c['created'])), 'text' => mb_substr((string)$c['content'], 0, 1200)];
        $items[] = ['story_id' => (int)$s['story_id'], 'kind' => $s['content_type'], 'title' => $s['title'], 'url' => $s['url'], 'source' => $s['source'],
                    'lead' => $s['content_type'] === 'article' ? md_plano((string)$s['content']) : (string)$s['content'],
                    'topic' => $s['topic'], 'author' => $s['author'] ?: '', 'sent' => $s['sent_date'],
                    'status' => $s['status'], 'comments' => $comments];
    }
    return $items;
}

/** Publica el comentario del bot. Devuelve [comment_id, null] o [0, 'error']. */
function agent_comment(PDO $pdo, array $bot, int $story_id, string $content, ?int $parent, string $model, string $note): array {
    $content = trim(preg_replace("/\r\n?/", "\n", $content));
    if (mb_strlen($content) < 2) return [0, 'comentario vacio'];
    $content = mb_substr($content, 0, 4000);
    if (agent_quota_left($pdo, (int)$bot['user_id']) <= 0) return [0, 'tope diario alcanzado (agent_per_day)'];
    $q = $pdo->prepare("SELECT status FROM stories WHERE story_id = ?"); $q->execute([$story_id]);
    if (!in_array($q->fetchColumn(), ['nuevas', 'candidatas', 'destacadas'], true)) return [0, 'el hilo ya no admite comentarios'];
    $q = $pdo->prepare("SELECT 1 FROM comments WHERE story_id = ? AND user_id = ? LIMIT 1"); $q->execute([$story_id, $bot['user_id']]);
    if ($q->fetchColumn()) return [0, 'el bot ya ha comentado en ese hilo'];
    $root = null; $pr = null;
    if ($parent) {
        if ((string)cfg('agent_replies', '0') !== '1') $parent = null;
        else {
            $q = $pdo->prepare("SELECT comment_id, root_id, user_id FROM comments WHERE comment_id = ? AND story_id = ? AND status = 'ok' AND deleted = 0");
            $q->execute([$parent, $story_id]);
            if ($pr = $q->fetch()) $root = $pr['root_id'] ?: (int)$pr['comment_id']; else { $parent = null; $pr = null; }
        }
    }
    require_once __DIR__ . '/moderacion.php';
    $alias = alias_user($pdo, $story_id, (int)$bot['user_id']);
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO comments (story_id, parent_id, root_id, user_id, alias, anon_key, aura, content, status) VALUES (?,?,?,?,?,NULL,?,?,'ok')")
        ->execute([$story_id, $parent ?: null, $root, $bot['user_id'], $alias, (float)$bot['aura'], $content]);
    $cid = (int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE stories SET comments = comments + 1 WHERE story_id = ?")->execute([$story_id]);
    mod_enqueue($pdo, 'comment', $cid);   // la IA moderadora lo revisa como a cualquiera
    $pdo->prepare("INSERT INTO agent_seen (story_id, result, comment_id, note, model, leased_until) VALUES (?, 'comment', ?, ?, ?, NULL)
                   ON DUPLICATE KEY UPDATE result = 'comment', comment_id = VALUES(comment_id), note = VALUES(note), model = VALUES(model), leased_until = NULL")
        ->execute([$story_id, $cid, mb_substr($note, 0, 300), mb_substr($model, 0, 80)]);
    $pdo->commit();
    // avisos como los de cualquier comentario (respuesta al autor del comentario padre, @menciones)
    avisos_comment($pdo, ['comment_id' => $cid, 'story_id' => $story_id, 'user_id' => $bot['user_id'], 'alias' => $alias, 'content' => $content], $pr);
    return [$cid, null];
}

/** El worker no comenta este hilo (o fallo): queda anotado para no volver a ofrecerlo. */
function agent_skip(PDO $pdo, int $story_id, string $result, string $note, string $model = ''): void {
    $result = $result === 'fail' ? 'fail' : 'skip';
    $pdo->prepare("INSERT INTO agent_seen (story_id, result, note, model, leased_until) VALUES (?, ?, ?, ?, NULL)
                   ON DUPLICATE KEY UPDATE result = VALUES(result), note = VALUES(note), model = VALUES(model), leased_until = NULL")
        ->execute([$story_id, $result, mb_substr($note, 0, 300), mb_substr($model, 0, 80) ?: null]);
}
