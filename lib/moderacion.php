<?php
/**
 * Moderacion con IA local (posmoderacion) — sql/migra-062-moderacion-ia-local.sql
 *
 * Todo se publica al momento. Si config.mod_enabled = 1, cada comentario, mensaje del chat y envio
 * nuevo entra en mod_queue; el worker que corre en el PC de la redaccion (local/moderador/) los pide
 * a /api/moderacion.php, los pasa por el modelo y devuelve el veredicto. Lo reportado pasa delante.
 *
 * Estados de mod_queue:
 *   queued -> processing (reservado por el worker hasta leased_until) -> ok | flag | hidden | needs_human
 *   flag / hidden / needs_human -> human_ok | human_removed   (lo cierra un moderador en /moderacion#ia)
 *
 * La IA nunca borra: como mucho oculta provisionalmente (mod_auto_hide) y siempre decide una persona.
 */

/** Categorias que devuelve el modelo: las mismas claves que report_reasons(). */
const MOD_CATS = ['insulto', 'odio', 'acoso', 'datos', 'ilegal', 'spam', 'otro'];
const MOD_OPEN = ['flag', 'hidden', 'needs_human'];   // esperan a un moderador

function mod_on(): bool { return cfg('mod_enabled') === '1'; }

/** Encola un elemento para la IA. $prio = 1 para lo reportado (pasa delante). No duplica: si ya esta en cola, como mucho sube la prioridad. */
function mod_enqueue(PDO $pdo, string $type, int $id, int $prio = 0): void {
    if (!mod_on() || !in_array($type, ['story', 'comment', 'chat'], true) || $id <= 0) return;
    try {
        $q = $pdo->prepare("SELECT item_id, status, priority FROM mod_queue WHERE item_type = ? AND ref_id = ? ORDER BY item_id DESC LIMIT 1");
        $q->execute([$type, $id]);
        if ($row = $q->fetch()) {
            if ($prio > (int)$row['priority'] && in_array($row['status'], ['queued', 'processing'], true))
                $pdo->prepare("UPDATE mod_queue SET priority = ? WHERE item_id = ?")->execute([$prio, $row['item_id']]);
            return;
        }
        $pdo->prepare("INSERT INTO mod_queue (item_type, ref_id, priority) VALUES (?,?,?)")->execute([$type, $id, $prio]);
    } catch (Throwable $e) { /* sin la migracion 062 no se encola nada: la web sigue funcionando */ }
}

/** Vuelve a pasar por la IA algo ya revisado (p. ej. un comentario que su autor ha editado). */
function mod_requeue(PDO $pdo, string $type, int $id): void {
    if (!mod_on()) return;
    try {
        $st = $pdo->prepare("UPDATE mod_queue SET status = 'queued', attempts = 0, leased_until = NULL WHERE item_type = ? AND ref_id = ? AND status = 'ok'");
        $st->execute([$type, $id]);
        if (!$st->rowCount()) mod_enqueue($pdo, $type, $id);
    } catch (Throwable $e) {}
}

/** ¿Lleva algun enlace? (http/https, www. o dominio.tld de los habituales). Con mod_hide_links_only = 1 la IA solo oculta lo que lo lleve:
 *  el spam que importa es el que mete un enlace; un insulto o una tonteria sin enlace se señala y lo decide una persona (27/10/2026). */
function mod_has_link(string $t): bool {
    return (bool)preg_match('~https?://|www\.|\b[a-z0-9-]+\.(com|net|org|es|eu|info|io|co|me|app|xyz|win|site|online|shop|store|top|club|biz|ru|cn|uk|de|fr|it|pt|mx|ar|cl|br|link|click|live|vip|pro|cc|tv)\b~i', $t);
}

/** Texto que ve el modelo: el contenido y, aparte, algo de contexto (titular del hilo, mensaje al que responde). Null si ya no existe o ya lo retiro un moderador. */
function mod_item_text(PDO $pdo, string $type, int $id): ?array {
    if ($type === 'story') {
        $q = $pdo->prepare("SELECT content_type, title, url, content, status FROM stories WHERE story_id = ?"); $q->execute([$id]);
        $s = $q->fetch();
        if (!$s || in_array($s['status'], ['rejected', 'discard'], true)) return null;
        $txt = "TITULAR: {$s['title']}\n" . ($s['url'] ? "ENLACE: {$s['url']}\n" : '') . ($s['content'] ? "TEXTO: " . mb_substr((string)$s['content'], 0, 6000) : '');
        return ['text' => $txt, 'context' => $s['content_type'] === 'article' ? 'Artículo escrito por un usuario.' : 'Enlace enviado por un usuario.'];
    }
    if ($type === 'comment') {
        $q = $pdo->prepare("SELECT c.content, c.deleted, c.status, s.title, p.content AS parent
                              FROM comments c JOIN stories s ON s.story_id = c.story_id LEFT JOIN comments p ON p.comment_id = c.parent_id
                             WHERE c.comment_id = ?"); $q->execute([$id]);
        $c = $q->fetch();
        if (!$c || (int)$c['deleted'] || $c['status'] === 'rejected') return null;
        $ctx = "Comentario en el hilo «" . mb_substr((string)$c['title'], 0, 200) . "».";
        if ($c['parent']) $ctx .= "\nResponde a este comentario: «" . mb_substr((string)$c['parent'], 0, 600) . "»";
        return ['text' => (string)$c['content'], 'context' => $ctx];
    }
    if ($type === 'chat') {
        $q = $pdo->prepare("SELECT c.content, c.deleted, c.status, r.content AS parent FROM chat c LEFT JOIN chat r ON r.msg_id = c.reply_to WHERE c.msg_id = ?"); $q->execute([$id]);
        $c = $q->fetch();
        if (!$c || (int)$c['deleted'] || $c['status'] === 'gone') return null;
        $ctx = 'Mensaje del chat general del sitio.';
        if ($c['parent']) $ctx .= "\nResponde a este mensaje: «" . mb_substr((string)$c['parent'], 0, 400) . "»";
        return ['text' => (string)$c['content'], 'context' => $ctx];
    }
    return null;
}

/** Reserva hasta $n elementos de mod_queue para un worker (status processing hasta mod_lease_sec) y devuelve lo que el modelo
 *  tiene que ver. Lo usan /api/moderacion.php (moderador.py) y /api/ia.php (worker generico, tarea «moderacion», migra-173). */
function mod_lease(PDO $pdo, int $n): array {
    $n = max(1, min(20, $n));
    $lease = max(60, (int)cfg('mod_lease_sec', 300));
    $items = [];
    $pdo->beginTransaction();
    $q = $pdo->prepare("SELECT item_id, item_type, ref_id, attempts FROM mod_queue
                         WHERE status = 'queued' OR (status = 'processing' AND leased_until < NOW())
                         ORDER BY priority DESC, item_id ASC LIMIT $n FOR UPDATE");
    $q->execute();
    foreach ($q->fetchAll() as $it) {
        $t = mod_item_text($pdo, $it['item_type'], (int)$it['ref_id']);
        if ($t === null) {   // ya no existe o ya lo retiro un moderador: nada que revisar
            $pdo->prepare("UPDATE mod_queue SET status = 'ok', verdict = '{\"motivo\":\"ya no existe o ya estaba retirado\"}', leased_until = NULL, processed = NOW() WHERE item_id = ?")->execute([$it['item_id']]);
            continue;
        }
        $pdo->prepare("UPDATE mod_queue SET status = 'processing', leased_until = NOW() + INTERVAL $lease SECOND WHERE item_id = ?")->execute([$it['item_id']]);
        $items[] = ['id' => (int)$it['item_id'], 'type' => $it['item_type'], 'ref' => (int)$it['ref_id'], 'text' => $t['text'], 'context' => $t['context']];
    }
    $pdo->commit();
    return $items;
}

/** El worker no pudo con un elemento reservado: vuelve a la cola o, tras mod_max_retries, a needs_human. Devuelve el estado o null si no estaba reservado. */
function mod_fail(PDO $pdo, int $itemId, string $error): ?string {
    $q = $pdo->prepare("SELECT item_id, attempts FROM mod_queue WHERE item_id = ? AND status = 'processing'"); $q->execute([$itemId]);
    if (!($it = $q->fetch())) return null;
    $at = (int)$it['attempts'] + 1;
    $ns = $at >= (int)cfg('mod_max_retries', 3) ? 'needs_human' : 'queued';
    $err = json_encode(['motivo' => 'fallo del modelo: ' . mb_substr($error, 0, 300)], JSON_UNESCAPED_UNICODE);
    $pdo->prepare("UPDATE mod_queue SET status = ?, attempts = ?, leased_until = NULL, verdict = ? WHERE item_id = ?")->execute([$ns, $at, $err, $itemId]);
    return $ns;
}

/** last_activity del hilo de un comentario (migra-170), al ocultarlo o reponerlo. */
function mod_touch_story(PDO $pdo, int $cid): void {
    if (!function_exists('story_touch')) return;
    $q = $pdo->prepare("SELECT story_id FROM comments WHERE comment_id = ?"); $q->execute([$cid]);
    if ($sid = (int)$q->fetchColumn()) story_touch($pdo, $sid);
}
/** Oculta provisionalmente (status pending_mod). Devuelve el estado previo (para restaurarlo) o null si no se pudo. */
function mod_hide(PDO $pdo, string $type, int $id): ?string {
    if ($type === 'comment') {
        $st = $pdo->prepare("UPDATE comments SET status = 'pending_mod' WHERE comment_id = ? AND status = 'ok' AND deleted = 0"); $st->execute([$id]);
        if (!$st->rowCount()) return null;
        $pdo->prepare("UPDATE stories s JOIN comments c ON c.story_id = s.story_id SET s.comments = GREATEST(s.comments - 1, 0) WHERE c.comment_id = ?")->execute([$id]);
        mod_touch_story($pdo, $id);
        return 'ok';
    }
    if ($type === 'chat') {
        $st = $pdo->prepare("UPDATE chat SET status = 'pending_mod' WHERE msg_id = ? AND status = 'ok' AND deleted = 0"); $st->execute([$id]);
        return $st->rowCount() ? 'ok' : null;
    }
    $q = $pdo->prepare("SELECT status FROM stories WHERE story_id = ?"); $q->execute([$id]);
    $prev = $q->fetchColumn();
    if (!in_array($prev, ['nuevas', 'destacadas'], true)) return null;   // archivado o ya retirado: solo se señala
    $pdo->prepare("UPDATE stories SET status = 'pending_mod' WHERE story_id = ? AND status = ?")->execute([$id, $prev]);
    return (string)$prev;
}

/** Deshace mod_hide(). */
function mod_unhide(PDO $pdo, string $type, int $id, ?string $prev): void {
    if ($type === 'comment') {
        $st = $pdo->prepare("UPDATE comments SET status = 'ok' WHERE comment_id = ? AND status = 'pending_mod'"); $st->execute([$id]);
        if ($st->rowCount()) { $pdo->prepare("UPDATE stories s JOIN comments c ON c.story_id = s.story_id SET s.comments = s.comments + 1 WHERE c.comment_id = ?")->execute([$id]); mod_touch_story($pdo, $id); }
    } elseif ($type === 'chat') {
        $pdo->prepare("UPDATE chat SET status = 'ok' WHERE msg_id = ? AND status = 'pending_mod'")->execute([$id]);
    } else {
        $to = in_array($prev, ['nuevas', 'destacadas'], true) ? $prev : 'nuevas';
        $pdo->prepare("UPDATE stories SET status = ? WHERE story_id = ? AND status = 'pending_mod'")->execute([$to, $id]);
    }
}

/**
 * Aplica el veredicto del modelo. $v = [categoria, confianza, motivo, cita, modelo].
 * La politica (umbrales, ocultar o no, solo con enlace) se decide aqui, no en el PC: se cambia desde admin → Config.
 * Devuelve el estado final del elemento en la cola.
 */
function mod_apply_verdict(PDO $pdo, array $item, array $v): string {
    $cat = in_array($v['categoria'] ?? '', MOD_CATS, true) ? $v['categoria'] : null;   // 'ninguna' o algo raro -> null
    $score = max(0.0, min(1.0, (float)($v['confianza'] ?? 0)));
    $flagAt = (float)cfg('mod_flag_score', 0.6);
    $hideAt = (float)cfg('mod_hide_score', 0.9);
    $status = 'ok'; $prev = null;
    if ($cat !== null && $score >= $flagAt) {
        $status = 'flag';
        // Que se oculta (28/10/2026): solo los tipos de mod_hide_types (comment) y las categorias de mod_hide_cats (spam), y con
        // enlace si mod_hide_links_only = 1. Todo lo demas (envios, chat, insultos...) se señala y se decide a posteriori.
        $tipos = array_filter(array_map('trim', explode(',', (string)cfg('mod_hide_types', 'comment'))));
        $cats  = array_filter(array_map('trim', explode(',', (string)cfg('mod_hide_cats', 'spam'))));
        $ocultable = ($tipos === [] || in_array($item['item_type'], $tipos, true)) && ($cats === [] || in_array($cat, $cats, true));
        $conEnlace = cfg('mod_hide_links_only', '1') !== '1'
            || mod_has_link((string)(mod_item_text($pdo, $item['item_type'], (int)$item['ref_id'])['text'] ?? ''));   // solo se oculta lo que lleva enlace (27/10/2026)
        if (cfg('mod_auto_hide') === '1' && $score >= $hideAt && $cat !== 'otro' && $ocultable && $conEnlace) {
            $prev = mod_hide($pdo, $item['item_type'], (int)$item['ref_id']);
            if ($prev !== null) $status = 'hidden';
        }
    }
    $verdict = json_encode(['motivo' => mb_substr((string)($v['motivo'] ?? ''), 0, 400), 'cita' => mb_substr((string)($v['cita'] ?? ''), 0, 300)], JSON_UNESCAPED_UNICODE);
    $pdo->prepare("UPDATE mod_queue SET status = ?, category = ?, score = ?, prev_status = ?, verdict = ?, reason = ?, model = ?, leased_until = NULL, processed = NOW() WHERE item_id = ?")
        ->execute([$status, $cat, $score, $prev, $verdict, $cat, mb_substr((string)($v['modelo'] ?? ''), 0, 80) ?: null, $item['item_id']]);
    return $status;
}

/** Retira sin rastro un comentario o mensaje de chat que estaba oculto provisionalmente (pending_mod): comentario → status
 *  'rejected' (como los rechazados desde admin), chat → 'gone'. En los dos, deleted = 1 y el original en content_orig; se cierran
 *  sus reportes y se borra la imagen adjunta si la hay. No pasa por mod_unhide(), asi que el contador del hilo no se toca. */
function mod_remove_hidden(PDO $pdo, string $type, int $id, int $by): void {
    if ($type === 'comment') {
        try { $qi = $pdo->prepare("SELECT image FROM comments WHERE comment_id = ?"); $qi->execute([$id]); if (($img = $qi->fetchColumn()) && function_exists('imagen_comentario_borrar')) { imagen_comentario_borrar((string)$img); $pdo->prepare("UPDATE comments SET image = NULL WHERE comment_id = ?")->execute([$id]); } } catch (Throwable $e) {}
        $pdo->prepare("UPDATE comments SET content_orig = IF(deleted = 1, content_orig, content), deleted = 1, status = 'rejected' WHERE comment_id = ? AND status = 'pending_mod'")->execute([$id]);
        try { $pdo->prepare("UPDATE comment_reports SET status = 'deleted', decided_by = ?, decided = NOW() WHERE comment_id = ? AND status = 'open'")->execute([$by, $id]); } catch (Throwable $e) {}
    } else {
        $pdo->prepare("UPDATE chat SET content_orig = IF(deleted = 1, content_orig, content), deleted_at = IF(deleted = 1, deleted_at, NOW()), deleted = 1, status = 'gone' WHERE msg_id = ? AND status = 'pending_mod'")->execute([$id]);
        try { $pdo->prepare("UPDATE chat_reports SET status = 'deleted', decided_by = ?, decided = NOW() WHERE msg_id = ? AND status = 'open'")->execute([$by, $id]); } catch (Throwable $e) {}
    }
}

/** Decision humana sobre un elemento señalado u oculto: 'keep' (se queda / vuelve a verse) o 'remove' (se retira como cualquier borrado de moderacion). */
function mod_human(PDO $pdo, int $itemId, string $dec, int $by): ?string {
    $q = $pdo->prepare("SELECT * FROM mod_queue WHERE item_id = ?"); $q->execute([$itemId]);
    $it = $q->fetch();
    if (!$it || !in_array($it['status'], array_merge(MOD_OPEN, ['queued', 'processing', 'ok']), true)) return null;
    $type = $it['item_type']; $id = (int)$it['ref_id'];
    $pdo->beginTransaction();
    if ($dec === 'remove' && $it['status'] === 'hidden' && in_array($type, ['comment', 'chat'], true)) {
        // Oculto provisionalmente y retirado: no vuelve a la vista ni con el aviso de «eliminado», desaparece sin dejar marca (27/10/2026).
        // El texto se conserva en content_orig por si hace falta ante un requerimiento; el contador del hilo ya lo bajo mod_hide().
        mod_remove_hidden($pdo, $type, $id, $by);
    } else {
        mod_unhide($pdo, $type, $id, $it['prev_status']);   // lo que estaba a la vista: retirar = texto sustituido a la vista, no desaparecer
        if ($dec === 'remove') {
            if ($type === 'comment') comment_delete($pdo, $id);
            elseif ($type === 'chat') chat_delete_mod($pdo, $id);
            else $pdo->prepare("UPDATE stories SET status = 'rejected' WHERE story_id = ?")->execute([$id]);
        }
    }
    $pdo->prepare("UPDATE mod_queue SET status = ?, decided_by = ?, processed = NOW() WHERE item_id = ?")->execute([$dec === 'remove' ? 'human_removed' : 'human_ok', $by, $itemId]);
    $pdo->commit();
    return $dec === 'remove' ? 'retirado' : 'se queda';
}

/** Lo que espera a un moderador (para el contador del pie). */
function mod_open_count(PDO $pdo): int {
    try { return (int)$pdo->query("SELECT COUNT(*) FROM mod_queue WHERE status IN ('flag','hidden','needs_human')")->fetchColumn(); } catch (Throwable $e) { return 0; }
}

/** Opinion de la IA sobre una lista de elementos (para los reportes): [ref_id => fila de mod_queue]. */
function mod_notes(PDO $pdo, string $type, array $ids): array {
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) return [];
    try {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $q = $pdo->prepare("SELECT * FROM mod_queue WHERE item_type = ? AND ref_id IN ($in) ORDER BY item_id");
        $q->execute(array_merge([$type], $ids));
        $out = [];
        foreach ($q as $r) $out[(int)$r['ref_id']] = $r;   // la ultima revision manda
        return $out;
    } catch (Throwable $e) { return []; }
}

/** Una linea legible con la opinion de la IA. */
function mod_note_line(?array $m): string {
    if (!$m) return mod_on() ? 'IA: sin revisar' : '';
    if (in_array($m['status'], ['queued', 'processing'], true)) return 'IA: en cola';
    if ($m['status'] === 'needs_human') return 'IA: no ha podido decidir';
    if ($m['category'] === null) return 'IA: sin problemas' . ($m['score'] !== null ? ' (' . num_es(round((float)$m['score'], 2)) . ')' : '');
    $RZ = report_reasons();
    $v = json_decode((string)$m['verdict'], true) ?: [];
    return 'IA: ' . ($RZ[$m['category']] ?? $m['category']) . ' · confianza ' . num_es(round((float)$m['score'], 2))
         . (!empty($v['motivo']) ? ' — ' . $v['motivo'] : '');
}

/** Estado del worker para la cabecera de moderacion. */
function mod_worker_line(): string {
    $seen = (string)cfg('mod_worker_seen', '');
    if ($seen === '') return 'La IA local no se ha conectado nunca.';
    $ago = time() - strtotime($seen);
    return ($ago < 180 ? 'IA local conectada' : 'IA local desconectada') . ' (último contacto ' . hace($seen) . ').';
}

/* ---- voto coordinado: cuentas que votan juntas (2026-09-26) ----------------------------------------
 * El δ no pondera por aura (democracia de un voto por persona), asi que una camarilla se combate haciendola visible, no
 * restandole votos: aqui se listan las parejas de cuentas que coinciden en los mismos envios, en el mismo sentido y con pocos
 * minutos de diferencia, y las cuentas que votan un mismo envio desde la misma conexion (votes.fp; en el δ ya cuentan como una).
 * Solo informa; decidir es cosa de los moderadores. */
function votos_coordinados(PDO $pdo, int $dias = 7, int $ventanaMin = 10, int $minCoinc = 4): array {
    $pares = $pdo->prepare("SELECT a.user_id ua, b.user_id ub, ua.login la, ub.login lb, COUNT(*) n,
                                   SUM(a.value = 1) pos, MIN(a.created) desde, MAX(a.created) hasta,
                                   (SELECT COUNT(*) FROM votes x WHERE x.user_id = a.user_id AND x.created >= NOW() - INTERVAL ? DAY) na,
                                   (SELECT COUNT(*) FROM votes y WHERE y.user_id = b.user_id AND y.created >= NOW() - INTERVAL ? DAY) nb
                              FROM votes a JOIN votes b ON b.story_id = a.story_id AND b.user_id > a.user_id AND b.value = a.value
                                   AND ABS(TIMESTAMPDIFF(MINUTE, a.created, b.created)) <= ?
                              JOIN users ua ON ua.user_id = a.user_id JOIN users ub ON ub.user_id = b.user_id
                             WHERE a.created >= NOW() - INTERVAL ? DAY
                             GROUP BY a.user_id, b.user_id HAVING n >= ? ORDER BY n DESC, hasta DESC LIMIT 40");
    $pares->execute([$dias, $dias, $ventanaMin, $dias, $minCoinc]);
    $mismaConexion = $pdo->prepare("SELECT v.story_id, s.title, COUNT(DISTINCT v.user_id) cuentas, GROUP_CONCAT(DISTINCT u.login ORDER BY u.login SEPARATOR ', ') logins, MAX(v.created) ultimo
                                      FROM votes v JOIN users u ON u.user_id = v.user_id JOIN stories s ON s.story_id = v.story_id
                                     WHERE v.fp IS NOT NULL AND v.created >= NOW() - INTERVAL ? DAY
                                     GROUP BY v.story_id, v.fp HAVING cuentas > 1 ORDER BY cuentas DESC, ultimo DESC LIMIT 40");
    $mismaConexion->execute([$dias]);
    return ['pares' => $pares->fetchAll(), 'conexion' => $mismaConexion->fetchAll()];
}
