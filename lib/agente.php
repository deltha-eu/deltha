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

const AGENT_DEFAULTS = ['enabled' => 0, 'persona' => '', 'per_day' => 4, 'min_age_min' => 20, 'max_age_h' => 24, 'max_comments' => 6, 'replies' => 0, 'skip_users' => '', 'topics' => '',
                        'submits_per_day' => 0, 'submit_kinds' => 'ambos', 'submit_topics' => ''];

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
    $pdo->prepare("INSERT INTO agents (user_id, enabled, persona, per_day, min_age_min, max_age_h, max_comments, replies, skip_users, topics, submits_per_day, submit_kinds, submit_topics)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), persona = VALUES(persona), per_day = VALUES(per_day), min_age_min = VALUES(min_age_min),
                   max_age_h = VALUES(max_age_h), max_comments = VALUES(max_comments), replies = VALUES(replies), skip_users = VALUES(skip_users), topics = VALUES(topics),
                   submits_per_day = VALUES(submits_per_day), submit_kinds = VALUES(submit_kinds), submit_topics = VALUES(submit_topics)")
        ->execute([$uid, $f['enabled'], $f['persona'], $f['per_day'], $f['min_age_min'], $f['max_age_h'], $f['max_comments'], $f['replies'], $f['skip_users'], $f['topics'],
                   $f['submits_per_day'], $f['submit_kinds'], $f['submit_topics']]);
}
function agent_delete(PDO $pdo, int $uid): void {
    $pdo->prepare("DELETE FROM agents WHERE user_id = ?")->execute([$uid]);
}
/** Campos del formulario de la ficha (prefijo ag_) saneados. */
function agent_from_post(): array {
    $n = fn(string $k, int $d, int $min, int $max) => max($min, min($max, (int)(trim((string)($_POST[$k] ?? '')) === '' ? $d : (int)$_POST[$k])));
    $skip = implode(',', array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($_POST['ag_skip_users'] ?? ''))), fn($x) => preg_match('/^[a-z0-9_\-]{3,32}$/i', $x)))));
    $topics = implode(',', array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ag_topics'] ?? [])), fn($x) => $x > 0))));
    $stopics = implode(',', array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ag_submit_topics'] ?? [])), fn($x) => $x > 0))));
    return ['enabled' => post('ag_enabled') === '1' ? 1 : 0, 'persona' => mb_substr(post('ag_persona'), 0, 20000),
            'per_day' => $n('ag_per_day', 4, 0, 200), 'min_age_min' => $n('ag_min_age_min', 20, 0, 10000), 'max_age_h' => $n('ag_max_age_h', 24, 1, 720),
            'max_comments' => $n('ag_max_comments', 6, 0, 250), 'replies' => post('ag_replies') === '1' ? 1 : 0, 'skip_users' => mb_substr($skip, 0, 300), 'topics' => mb_substr($topics, 0, 120),
            'submits_per_day' => $n('ag_submits_per_day', 0, 0, 48), 'submit_kinds' => in_array(post('ag_submit_kinds'), ['medio', 'newsletter', 'ambos'], true) ? post('ag_submit_kinds') : 'ambos',
            'submit_topics' => mb_substr($stopics, 0, 120)];
}

/** Seccion «Agente» de la ficha de moderacion (solo admin). $a = agent_get() o null. */
function agent_form(PDO $pdo, int $uid, ?array $a): string {
    if (!is_admin() || !agents_on()) return '';
    $v = $a ? $a : AGENT_DEFAULTS;
    $h = "<details class=\"agentbox\"" . ($a ? ' open' : '') . "><summary>Agente (bot con IA local)" . ($a ? ($a['enabled'] ? ' · <b>activo</b>' : ' · apagado') : '') . "</summary>";
    $h .= "<input type=\"hidden\" name=\"ag_was\" value=\"" . ($a ? '1' : '0') . "\">";
    $h .= "<label><input type=\"checkbox\" name=\"ag_is\" value=\"1\"" . ($a ? ' checked' : '') . "> esta cuenta es un agente" . ($a ? " <small>(desmárcala y guarda para dejar de serlo: se borra esta sección)</small>" : " <small>(se marca sola al guardar si escribes una personalidad o pones algún cupo)</small>") . "</label>";
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
    // envios al radar: noticias de las fuentes tal cual (sin modelo; las hace el cron del servidor, agentes_tick)
    $h .= "<label style=\"margin-top:10px\"><b>Envíos al radar</b>: noticias de los medios y substacks, tal cual llegan por RSS (titular, entradilla y foto de la fuente)</label>";
    $h .= "<div class=\"agrow\"><label>Envíos al día <input type=\"number\" name=\"ag_submits_per_day\" min=\"0\" max=\"48\" value=\"" . (int)$v['submits_per_day'] . "\"></label>";
    $h .= "<label>De <select name=\"ag_submit_kinds\">";
    foreach (['ambos' => 'medios y substacks', 'medio' => 'solo medios', 'newsletter' => 'solo substacks'] as $k => $lbl) $h .= "<option value=\"$k\"" . ((string)$v['submit_kinds'] === $k ? ' selected' : '') . ">$lbl</option>";
    $h .= "</select></label></div>";
    $ssel = array_map('intval', array_filter(explode(',', (string)$v['submit_topics'])));
    $h .= "<label>Temas de las fuentes de las que envía (el tema lo tiene cada fuente; ninguno marcado = todas)</label><div class=\"agtopics\">";
    foreach ($pdo->query("SELECT topic_id, name FROM topics ORDER BY name") as $t)
        $h .= "<label><input type=\"checkbox\" name=\"ag_submit_topics[]\" value=\"{$t['topic_id']}\"" . (in_array((int)$t['topic_id'], $ssel, true) ? ' checked' : '') . "> " . h($t['name']) . "</label>";
    $h .= "</div><small class=\"hint\">0 = no envía. Con N al día, uno cada 24/N horas más o menos (con azar), entre las noticias de las últimas " . (int)cfg('agent_submit_max_age_h', 24) . " h que nadie haya enviado. El agente no comenta lo que envía él.</small>";
    if ($a) {
        try { $q = $pdo->prepare("SELECT COUNT(*) FROM agent_sent WHERE user_id = ? AND created >= CURDATE()"); $q->execute([$uid]); $envHoy = (int)$q->fetchColumn(); } catch (Throwable $e) { $envHoy = 0; }
        $st = $pdo->prepare("SELECT result, COUNT(*) n FROM agent_seen WHERE user_id = ? GROUP BY result"); $st->execute([$uid]);
        $c = array_column($st->fetchAll(), 'n', 'result');
        $hoy = agent_today($pdo, $uid);
        $h .= "<p class=\"meta\" style=\"padding:0\">Hoy " . $hoy . " de " . (int)$v['per_day'] . " comentarios · " . $envHoy . " de " . (int)$v['submits_per_day'] . " envíos" . (!empty($a['next_submit']) && (int)$v['submits_per_day'] > 0 ? " (próximo hacia las " . date('H:i', strtotime((string)$a['next_submit'])) . ")" : "") . " · vistos: " . (int)($c['comment'] ?? 0) . " comentados, " . (int)($c['skip'] ?? 0) . " pasados, " . (int)($c['fail'] ?? 0) . " fallos"
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

/* ---- envios al radar (cron, sin modelo) ------------------------------------------------------------------------
 * Cada minuto desde scripts/promocionar.php. Para cada agente activo con submits_per_day > 0 al que le toque (next_submit
 * NULL o pasado), elige al azar una noticia reciente de sus fuentes (source_items de medios/substacks activos, del tipo y los
 * temas de su ficha, publicada en las ultimas agent_submit_max_age_h horas, no enviada ya por nadie ni en agent_sent) y la
 * envia tal cual como haria una persona desde /fuente.php: titular y entradilla del feed, foto del feed copiada (como en
 * enviar.php ?x=), tema el de la fuente (o el primero de su ficha), al radar, alias Ghost, cola de moderacion. Sin aura.
 * Luego fija next_submit = ahora + 24/N horas por un factor al azar entre 0,7 y 1,4, para que no sea un reloj. */
function agentes_tick(PDO $pdo): array {
    $log = [];
    if (!agents_on()) return $log;
    try { $ags = $pdo->query("SELECT a.*, u.login, u.aura FROM agents a JOIN users u ON u.user_id = a.user_id WHERE a.enabled = 1 AND u.verified = 1 AND a.submits_per_day > 0 AND (a.next_submit IS NULL OR a.next_submit <= NOW())")->fetchAll(); }
    catch (Throwable $e) { return ["agentes: " . $e->getMessage()]; }   // sin migra-091
    foreach ($ags as $a) {
        try { $log[] = agent_submit_one($pdo, $a); }
        catch (Throwable $e) { $log[] = "agente {$a['login']}: ERROR " . $e->getMessage(); }
    }
    return $log;
}

/** Envia una noticia por el agente (o no, si no hay candidatas) y programa la siguiente. Devuelve la linea de log. */
function agent_submit_one(PDO $pdo, array $a): string {
    $uid = (int)$a['user_id'];
    $gapMin = max(30, (int)round(1440 / max(1, (int)$a['submits_per_day'])));
    $next = (int)round($gapMin * (0.7 + random_int(0, 700) / 1000));   // 0,7–1,4 veces el intervalo
    $pdo->prepare("UPDATE agents SET next_submit = NOW() + INTERVAL ? MINUTE WHERE user_id = ?")->execute([$next, $uid]);
    $maxAge = max(1, (int)cfg('agent_submit_max_age_h', 24));
    $kinds = $a['submit_kinds'] === 'ambos' ? ['medio', 'newsletter'] : [$a['submit_kinds']];
    $topics = array_filter(array_map('intval', agent_list((string)$a['submit_topics'])));
    $w = ["s.status = 'ok'", "s.kind IN (" . implode(',', array_fill(0, count($kinds), '?')) . ")", "i.pub_date >= NOW() - INTERVAL $maxAge HOUR",
          "i.url LIKE 'http%'", "NOT EXISTS (SELECT 1 FROM agent_sent x WHERE x.item_id = i.item_id)",
          "NOT EXISTS (SELECT 1 FROM stories st WHERE st.url = i.url AND st.status NOT IN ('rejected','discard') AND st.sent_date > NOW() - INTERVAL 30 DAY)"];
    $p = $kinds;
    if ($topics) $w[] = "s.topic_id IN (" . implode(',', $topics) . ")";
    $st = $pdo->prepare("SELECT i.item_id, i.title, i.url, i.content, i.image, i.pub_date, s.name AS src_name, s.topic_id
                           FROM source_items i JOIN sources s ON s.source_id = i.source_id
                          WHERE " . implode(' AND ', $w) . " ORDER BY i.pub_date DESC LIMIT 12");
    $st->execute($p);
    $cands = $st->fetchAll();
    // sin medios bloqueados (lib/bloqueos.php) y con titular
    $cands = array_values(array_filter($cands, fn($i) => mb_strlen((string)$i['title']) >= 8 && !(function_exists('source_blocked') && source_blocked($pdo, (string)$i['url']))));
    if (!$cands) {   // nada que enviar ahora: volver a mirar en media hora, no dentro de todo el intervalo
        $pdo->prepare("UPDATE agents SET next_submit = NOW() + INTERVAL 30 MINUTE WHERE user_id = ?")->execute([$uid]);
        return "agente {$a['login']}: sin noticias nuevas que enviar (vuelve a mirar en 30 min)";
    }
    $i = $cands[random_int(0, count($cands) - 1)];
    $topic = (int)($i['topic_id'] ?: ($topics[0] ?? 0));
    if ($topic <= 0) $topic = (int)$pdo->query("SELECT topic_id FROM topics ORDER BY topic_id LIMIT 1")->fetchColumn();
    // foto: copia de la del feed (la del item se borra cuando caduca), como hace enviar.php con ?x=
    $image = null;
    if (!empty($i['image']) && preg_match('/^[a-f0-9]{24}\.jpg$/', (string)$i['image']) && is_file(DDD_MEDIA_DIR . '/' . $i['image'])) {
        $copia = bin2hex(random_bytes(12)) . '.jpg';
        if (@copy(DDD_MEDIA_DIR . '/' . $i['image'], DDD_MEDIA_DIR . '/' . $copia)) $image = $copia;
    }
    require_once __DIR__ . '/moderacion.php';
    $title = mb_substr(trim((string)$i['title']), 0, 200);
    $lead = mb_substr(trim((string)$i['content']), 0, 2000) ?: null;
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO stories (content_type, user_id, topic_id, community_id, title, url, source, content, image, status) VALUES ('link', ?, ?, NULL, ?, ?, ?, ?, ?, 'nuevas')")
        ->execute([$uid, $topic, $title, $i['url'], source_of((string)$i['url']), $lead, $image]);
    $sid = (int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE stories SET portada = ?, author_alias = ? WHERE story_id = ?")
        ->execute([portada_de($pdo, ['url' => $i['url'], 'source' => source_of((string)$i['url'])]), alias_user($pdo, $sid, $uid), $sid]);
    mod_enqueue($pdo, 'story', $sid);
    $pdo->prepare("INSERT INTO agent_sent (item_id, user_id, story_id) VALUES (?, ?, ?)")->execute([(int)$i['item_id'], $uid, $sid]);
    $pdo->commit();
    return "agente {$a['login']}: enviada #$sid «" . mb_strimwidth($title, 0, 60, '…') . "» ({$i['src_name']}; próximo en $next min)";
}
