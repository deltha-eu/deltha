<?php
/**
 * lib/agente.php — agentes comentaristas (bots con IA local), uno por cuenta. sql/migra-089-agente-comentarista.sql
 *
 *  Una cuenta se marca como agente en su ficha (lib/usuarios.php, solo admin): ahi van su personalidad —las instrucciones
 *  del modelo— y sus parametros, con la casilla «activo». Puede haber varios. El worker del PC de la redaccion
 *  (local/comentarista/comentarista.py) pide hilos candidatos a /api/agente.php para cada agente activo con cupo, baja el
 *  articulo, lo pasa por el modelo local con la personalidad y devuelve un comentario (o «paso»). Aqui: que hilos se le
 *  ofrecen a cada uno, el tope diario y la publicacion del comentario por el mismo camino que /comentar.php: alias Ghost del
 *  hilo, contador del hilo, cola de moderacion, avisos. Sin aura (un bot no debe ganar aura: con mod_aura llegaria a moderar).
 *  agent_seen recuerda que hilos ha visto cada agente y que hizo con cada uno.
 */
declare(strict_types=1);

const AGENT_DEFAULTS = ['enabled' => 0, 'persona' => '', 'per_day' => 4, 'min_age_min' => 20, 'max_age_h' => 24, 'max_comments' => 6, 'replies' => 0, 'skip_users' => '', 'topics' => ''];

/** Tabla creada (migra-089). */
function agents_on(): bool {
    static $on = null; global $pdo;
    if ($on !== null) return $on;
    try { $pdo->query("SELECT 1 FROM agents LIMIT 1"); return $on = true; } catch (Throwable $e) { return $on = false; }
}

/** Ficha de agente de una cuenta (o null si no es agente). */
function agent_get(PDO $pdo, int $uid): ?array {
    if (!agents_on()) return null;
    $st = $pdo->prepare("SELECT a.*, u.login, u.aura FROM agents a JOIN users u ON u.user_id = a.user_id WHERE a.user_id = ?");
    $st->execute([$uid]);
    return $st->fetch() ?: null;
}
/** ¿Es un agente? (para la etiqueta «bot» del perfil y las listas). */
function agent_is(PDO $pdo, int $uid): bool { return agent_get($pdo, $uid) !== null; }

/** Agentes activos con cuenta validada. */
function agents_active(PDO $pdo): array {
    if (!agents_on()) return [];
    return $pdo->query("SELECT a.*, u.login, u.aura FROM agents a JOIN users u ON u.user_id = a.user_id WHERE a.enabled = 1 AND u.verified = 1 ORDER BY u.login")->fetchAll();
}

/** Guarda la ficha de agente desde el formulario (solo admin). $f = campos ya saneados (ver agent_from_post). */
function agent_save(PDO $pdo, int $uid, array $f): void {
    $pdo->prepare("INSERT INTO agents (user_id, enabled, persona, per_day, min_age_min, max_age_h, max_comments, replies, skip_users, topics)
                   VALUES (?,?,?,?,?,?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), persona = VALUES(persona), per_day = VALUES(per_day), min_age_min = VALUES(min_age_min),
                   max_age_h = VALUES(max_age_h), max_comments = VALUES(max_comments), replies = VALUES(replies), skip_users = VALUES(skip_users), topics = VALUES(topics)")
        ->execute([$uid, $f['enabled'], $f['persona'], $f['per_day'], $f['min_age_min'], $f['max_age_h'], $f['max_comments'], $f['replies'], $f['skip_users'], $f['topics']]);
}
function agent_delete(PDO $pdo, int $uid): void {
    $pdo->prepare("DELETE FROM agents WHERE user_id = ?")->execute([$uid]);
}
/** Campos del formulario de la ficha (prefijo ag_) saneados. */
function agent_from_post(): array {
    $n = fn(string $k, int $d, int $min, int $max) => max($min, min($max, (int)(trim((string)($_POST[$k] ?? '')) === '' ? $d : (int)$_POST[$k])));
    $skip = implode(',', array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($_POST['ag_skip_users'] ?? ''))), fn($x) => preg_match('/^[a-z0-9_\-]{3,32}$/i', $x)))));
    $topics = implode(',', array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ag_topics'] ?? [])), fn($x) => $x > 0))));
    return ['enabled' => post('ag_enabled') === '1' ? 1 : 0, 'persona' => mb_substr(post('ag_persona'), 0, 20000),
            'per_day' => $n('ag_per_day', 4, 0, 200), 'min_age_min' => $n('ag_min_age_min', 20, 0, 10000), 'max_age_h' => $n('ag_max_age_h', 24, 1, 720),
            'max_comments' => $n('ag_max_comments', 6, 0, 250), 'replies' => post('ag_replies') === '1' ? 1 : 0, 'skip_users' => mb_substr($skip, 0, 300), 'topics' => mb_substr($topics, 0, 120)];
}

/** Seccion «Agente» de la ficha de moderacion (solo admin). $a = agent_get() o null. */
function agent_form(PDO $pdo, int $uid, ?array $a): string {
    if (!is_admin() || !agents_on()) return '';
    $v = $a ? $a : AGENT_DEFAULTS;
    $h = "<details class=\"agentbox\"" . ($a ? ' open' : '') . "><summary>Agente (bot con IA local)" . ($a ? ($a['enabled'] ? ' · <b>activo</b>' : ' · apagado') : '') . "</summary>";
    $h .= "<label><input type=\"checkbox\" name=\"ag_is\" value=\"1\"" . ($a ? ' checked' : '') . "> esta cuenta es un agente: comenta hilos con el modelo del PC de la redacción</label>";
    $h .= "<label><input type=\"checkbox\" name=\"ag_enabled\" value=\"1\"" . ((int)$v['enabled'] ? ' checked' : '') . "> <b>activo</b> (sin marcar, el worker no le pide trabajo; la ficha se conserva)</label>";
    $h .= "<label>Personalidad: las instrucciones del modelo (quién es, de qué sabe, tono, qué hace en un comentario, cuándo callar)</label>";
    $h .= "<textarea name=\"ag_persona\" rows=\"14\" placeholder=\"Eres …, una cuenta de Deltha que comenta noticias. Todo el mundo sabe que eres un bot …\">" . h((string)$v['persona']) . "</textarea>";
    $h .= "<small class=\"hint\">Se manda tal cual como mensaje de sistema, seguido del artículo y de los comentarios que ya hay. En segunda persona. Si se deja vacío, el worker usa <code>local/comentarista/personalidad.md</code>. Hay un ejemplo en ese fichero.</small>";
    $h .= "<div class=\"agrow\"><label>Comentarios al día <input type=\"number\" name=\"ag_per_day\" min=\"0\" max=\"200\" value=\"" . (int)$v['per_day'] . "\"></label>";
    $h .= "<label>Hilo de al menos (min) <input type=\"number\" name=\"ag_min_age_min\" min=\"0\" max=\"10000\" value=\"" . (int)$v['min_age_min'] . "\"></label>";
    $h .= "<label>y como mucho (horas) <input type=\"number\" name=\"ag_max_age_h\" min=\"1\" max=\"720\" value=\"" . (int)$v['max_age_h'] . "\"></label>";
    $h .= "<label>Solo hilos con ≤ comentarios <input type=\"number\" name=\"ag_max_comments\" min=\"0\" max=\"250\" value=\"" . (int)$v['max_comments'] . "\"></label></div>";
    $h .= "<label><input type=\"checkbox\" name=\"ag_replies\" value=\"1\"" . ((int)$v['replies'] ? ' checked' : '') . "> puede responder a un comentario del hilo (sin marcar, solo comenta el artículo)</label>";
    $h .= "<label>No comenta los hilos enviados por (nicks separados por comas: los otros bots, por ejemplo)</label><input type=\"text\" name=\"ag_skip_users\" maxlength=\"300\" value=\"" . h((string)$v['skip_users']) . "\">";
    $sel = array_map('intval', array_filter(explode(',', (string)$v['topics'])));
    $h .= "<label>Temas en los que comenta (ninguno marcado = todos)</label><div class=\"agtopics\">";
    foreach ($pdo->query("SELECT topic_id, name FROM topics ORDER BY name") as $t)
        $h .= "<label><input type=\"checkbox\" name=\"ag_topics[]\" value=\"{$t['topic_id']}\"" . (in_array((int)$t['topic_id'], $sel, true) ? ' checked' : '') . "> " . h($t['name']) . "</label>";
    $h .= "</div>";
    if ($a) {
        $st = $pdo->prepare("SELECT result, COUNT(*) n FROM agent_seen WHERE user_id = ? GROUP BY result"); $st->execute([$uid]);
        $c = array_column($st->fetchAll(), 'n', 'result');
        $hoy = agent_today($pdo, $uid);
        $h .= "<p class=\"meta\" style=\"padding:0\">Hoy " . $hoy . " de " . (int)$v['per_day'] . " · vistos: " . (int)($c['comment'] ?? 0) . " comentados, " . (int)($c['skip'] ?? 0) . " pasados, " . (int)($c['fail'] ?? 0) . " fallos"
            . " · worker visto " . h((string)cfg('agent_worker_seen', '') ?: 'nunca') . " · <a href=\"/admin.php?t=agentes&amp;a=$uid\">registro</a></p>";
    }
    $h .= "</details>";
    return $h;
}

/** Comentarios del agente hoy (hora del servidor). */
function agent_today(PDO $pdo, int $uid): int {
    $st = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ? AND created >= CURDATE()");
    $st->execute([$uid]);
    return (int)$st->fetchColumn();
}
function agent_quota_left(PDO $pdo, array $a): int { return max(0, (int)$a['per_day'] - agent_today($pdo, (int)$a['user_id'])); }

function agent_list(string $s): array { return array_values(array_filter(array_map('trim', explode(',', $s)), fn($x) => $x !== '')); }

/** Hilos candidatos para un agente: abiertos, con la edad y los comentarios de su ficha, no suyos ni de los nicks excluidos,
 *  de sus temas y no vistos ya por el (o con la reserva caducada). Los reserva (agent_seen.leased) y devuelve lo que
 *  necesita el worker: titular, entradilla, enlace, fuente, tema y los comentarios que ya hay. */
function agent_lease(PDO $pdo, array $a, int $n): array {
    $uid = (int)$a['user_id'];
    $lease  = max(60, (int)cfg('agent_lease_sec', 600));
    $skip   = agent_list((string)$a['skip_users']);
    $topics = array_filter(array_map('intval', agent_list((string)$a['topics'])));
    $w = ["s.status IN ('nuevas','destacadas')", "s.sent_date <= NOW() - INTERVAL " . (int)$a['min_age_min'] . " MINUTE", "s.sent_date >= NOW() - INTERVAL " . (int)$a['max_age_h'] . " HOUR",
          "s.comments <= " . (int)$a['max_comments'], "(s.user_id IS NULL OR s.user_id <> $uid)",
          "(v.story_id IS NULL OR (v.result = 'leased' AND v.leased_until < NOW()))"];
    $p = [];
    if ($skip)   { $w[] = "(u.login IS NULL OR u.login NOT IN (" . implode(',', array_fill(0, count($skip), '?')) . "))"; $p = $skip; }
    if ($topics) { $w[] = "s.topic_id IN (" . implode(',', $topics) . ")"; }
    $st = $pdo->prepare("SELECT s.story_id, s.content_type, s.title, s.url, s.source, s.content, s.sent_date, s.comments, s.status, t.name AS topic, u.login AS author
                           FROM stories s JOIN topics t ON t.topic_id = s.topic_id LEFT JOIN users u ON u.user_id = s.user_id
                           LEFT JOIN agent_seen v ON v.story_id = s.story_id AND v.user_id = $uid
                          WHERE " . implode(' AND ', $w) . " ORDER BY s.sent_date DESC LIMIT " . max(1, min(10, $n)));
    $st->execute($p);
    $items = [];
    $cs = $pdo->prepare("SELECT c.comment_id, c.parent_id, c.user_id, c.alias, c.content, c.created, u.login FROM comments c LEFT JOIN users u ON u.user_id = c.user_id
                          WHERE c.story_id = ? AND c.status = 'ok' AND c.deleted = 0 ORDER BY c.created ASC LIMIT 12");
    foreach ($st->fetchAll() as $s) {
        $pdo->prepare("INSERT INTO agent_seen (user_id, story_id, result, leased_until) VALUES (?, ?, 'leased', NOW() + INTERVAL $lease SECOND)
                       ON DUPLICATE KEY UPDATE result = 'leased', leased_until = VALUES(leased_until)")->execute([$uid, $s['story_id']]);
        $cs->execute([$s['story_id']]);
        $comments = [];
        foreach ($cs->fetchAll() as $c) $comments[] = ['id' => (int)$c['comment_id'], 'parent' => (int)$c['parent_id'] ?: null,
            'who' => display_name($c, ghost_over($c['created'])), 'text' => mb_substr((string)$c['content'], 0, 1200)];
        $items[] = ['agent' => $a['login'], 'story_id' => (int)$s['story_id'], 'kind' => $s['content_type'], 'title' => $s['title'], 'url' => $s['url'], 'source' => $s['source'],
                    'lead' => $s['content_type'] === 'article' ? md_plano((string)$s['content']) : (string)$s['content'],
                    'topic' => $s['topic'], 'author' => $s['author'] ?: '', 'sent' => $s['sent_date'], 'status' => $s['status'], 'comments' => $comments];
    }
    return $items;
}

/** Publica el comentario del agente. Devuelve [comment_id, null] o [0, 'error']. */
function agent_comment(PDO $pdo, array $a, int $story_id, string $content, ?int $parent, string $model, string $note): array {
    $uid = (int)$a['user_id'];
    $content = trim(preg_replace("/\r\n?/", "\n", $content));
    if (mb_strlen($content) < 2) return [0, 'comentario vacio'];
    $content = mb_substr($content, 0, 4000);
    if (!(int)$a['enabled']) return [0, 'agente apagado'];
    if (agent_quota_left($pdo, $a) <= 0) return [0, 'tope diario alcanzado'];
    $q = $pdo->prepare("SELECT status FROM stories WHERE story_id = ?"); $q->execute([$story_id]);
    if (!in_array($q->fetchColumn(), ['nuevas', 'candidatas', 'destacadas'], true)) return [0, 'el hilo ya no admite comentarios'];
    $q = $pdo->prepare("SELECT 1 FROM comments WHERE story_id = ? AND user_id = ? LIMIT 1"); $q->execute([$story_id, $uid]);
    if ($q->fetchColumn()) return [0, 'el agente ya ha comentado en ese hilo'];
    $root = null; $pr = null;
    if ($parent) {
        if (!(int)$a['replies']) $parent = null;
        else {
            $q = $pdo->prepare("SELECT comment_id, root_id, user_id FROM comments WHERE comment_id = ? AND story_id = ? AND status = 'ok' AND deleted = 0");
            $q->execute([$parent, $story_id]);
            if ($pr = $q->fetch()) $root = $pr['root_id'] ?: (int)$pr['comment_id']; else { $parent = null; $pr = null; }
        }
    }
    require_once __DIR__ . '/moderacion.php';
    $alias = alias_user($pdo, $story_id, $uid);
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO comments (story_id, parent_id, root_id, user_id, alias, anon_key, aura, content, status) VALUES (?,?,?,?,?,NULL,?,?,'ok')")
        ->execute([$story_id, $parent ?: null, $root, $uid, $alias, (float)$a['aura'], $content]);
    $cid = (int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE stories SET comments = comments + 1 WHERE story_id = ?")->execute([$story_id]);
    mod_enqueue($pdo, 'comment', $cid);   // la IA moderadora lo revisa como a cualquiera
    $pdo->prepare("INSERT INTO agent_seen (user_id, story_id, result, comment_id, note, model, leased_until) VALUES (?, ?, 'comment', ?, ?, ?, NULL)
                   ON DUPLICATE KEY UPDATE result = 'comment', comment_id = VALUES(comment_id), note = VALUES(note), model = VALUES(model), leased_until = NULL")
        ->execute([$uid, $story_id, $cid, mb_substr($note, 0, 300), mb_substr($model, 0, 80)]);
    $pdo->commit();
    // avisos como los de cualquier comentario (respuesta al autor del comentario padre, @menciones)
    avisos_comment($pdo, ['comment_id' => $cid, 'story_id' => $story_id, 'user_id' => $uid, 'alias' => $alias, 'content' => $content], $pr);
    return [$cid, null];
}

/** El worker no comenta este hilo (o fallo): queda anotado para no volver a ofrecerselo a ese agente. */
function agent_skip(PDO $pdo, int $uid, int $story_id, string $result, string $note, string $model = ''): void {
    $result = $result === 'fail' ? 'fail' : 'skip';
    $pdo->prepare("INSERT INTO agent_seen (user_id, story_id, result, note, model, leased_until) VALUES (?, ?, ?, ?, ?, NULL)
                   ON DUPLICATE KEY UPDATE result = VALUES(result), note = VALUES(note), model = VALUES(model), leased_until = NULL")
        ->execute([$uid, $story_id, $result, mb_substr($note, 0, 300), mb_substr($model, 0, 80) ?: null]);
}
