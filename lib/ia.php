<?php
/**
 * lib/ia.php — capa comun de IA (migra-173). Una sola forma de usar el modelo desde cualquier parte del PHP.
 *
 * El modelo NO esta en el droplet: corre en el PC de la redaccion y es el PC quien pide trabajo a /api/ia.php (worker
 * generico local/ia/ia.py). Por eso aqui no hay «llamar al modelo y esperar»: hay una cola (ia_jobs) y, para cada tarea,
 * un handler PHP que se ejecuta cuando llega el resultado. Si el PC esta apagado, la cola espera y la web sigue igual.
 *
 *   ia_pedir($pdo, 'relacionadas', ['user' => $texto, ...datos], 'story', $sid)   encola (o devuelve el trabajo ya en cola)
 *   ia_handler('relacionadas', function (PDO $pdo, array $job, array $res): void { ... })   que hacer con la respuesta
 *   ia_resultado($pdo, 'relacionadas', 'story', $sid)                              ultimo resultado terminado, o null
 *
 * Cada tarea vive en ia_tasks (admin → IA): prompt de sistema, esquema JSON de la respuesta (el worker lo pasa como
 * response_format json_schema; si esta vacio el modelo responde texto libre y el resultado llega como ['text' => ...]),
 * temperatura, tokens, prioridad, reserva, reintentos, tope diario e interruptor. El PHP solo compone el mensaje de
 * usuario ($input['user']); el prompt es cosa del panel. Lo demas que vaya en $input se guarda para el handler.
 *
 * Estados de ia_jobs: queued -> processing (reservado hasta leased_until) -> done | failed (handler roto) | needs_human
 * (el modelo fallo max_retries veces). La moderacion no usa ia_jobs: sigue en mod_queue con su flujo humano, pero el
 * worker generico la sirve como tarea «moderacion» (ver /api/ia.php) y su prompt se edita tambien en admin → IA.
 */

/** Tareas que trae de serie la migracion y que el codigo da por existentes. */
const IA_TAREAS_BASE = ['moderacion', 'relacionadas'];

function ia_on(): bool { return cfg('ia_enabled', '1') === '1'; }

/** Ficha de una tarea (fila de ia_tasks) o null si no existe. En cache dentro de la peticion. */
function ia_tarea(PDO $pdo, string $key): ?array {
    static $C = [];
    if (array_key_exists($key, $C)) return $C[$key];
    try { $q = $pdo->prepare("SELECT * FROM ia_tasks WHERE task_key = ?"); $q->execute([$key]); $C[$key] = $q->fetch() ?: null; }
    catch (Throwable $e) { $C[$key] = null; }   // sin la migracion 173: nada funciona, nada rompe
    return $C[$key];
}

/** Todas las tareas, para admin y para el worker. */
function ia_tareas(PDO $pdo): array {
    try { return $pdo->query("SELECT * FROM ia_tasks ORDER BY priority DESC, task_key")->fetchAll(); } catch (Throwable $e) { return []; }
}

/** ¿Se puede encolar esta tarea ahora? (capa encendida, tarea existente y activa, tope diario no agotado) */
function ia_tarea_activa(PDO $pdo, string $key): bool {
    if (!ia_on()) return false;
    $t = ia_tarea($pdo, $key);
    if (!$t || !(int)$t['enabled']) return false;
    if ((int)$t['per_day'] > 0) {
        $q = $pdo->prepare("SELECT COUNT(*) FROM ia_jobs WHERE task_key = ? AND created >= CURDATE()"); $q->execute([$key]);
        if ((int)$q->fetchColumn() >= (int)$t['per_day']) return false;
    }
    return true;
}

/**
 * Encola un trabajo. $input['user'] es lo que vera el modelo como mensaje de usuario (obligatorio); el resto se guarda
 * tal cual para el handler. Con $refType/$refId no se duplica: si ya hay uno en cola o en proceso para esa referencia,
 * se devuelve ese (subiendo la prioridad si la nueva es mayor). Devuelve el job_id o null si no se encola.
 */
function ia_pedir(PDO $pdo, string $task, array $input, string $refType = '', int $refId = 0, ?int $prio = null): ?int {
    if (trim((string)($input['user'] ?? '')) === '' || !ia_tarea_activa($pdo, $task)) return null;
    $t = ia_tarea($pdo, $task);
    $prio = $prio ?? (int)$t['priority'];
    try {
        if ($refType !== '') {
            $q = $pdo->prepare("SELECT job_id, priority FROM ia_jobs WHERE task_key = ? AND ref_type = ? AND ref_id = ? AND status IN ('queued','processing') ORDER BY job_id DESC LIMIT 1");
            $q->execute([$task, $refType, $refId]);
            if ($row = $q->fetch()) {
                if ($prio > (int)$row['priority']) $pdo->prepare("UPDATE ia_jobs SET priority = ? WHERE job_id = ?")->execute([$prio, $row['job_id']]);
                return (int)$row['job_id'];
            }
        }
        $pdo->prepare("INSERT INTO ia_jobs (task_key, ref_type, ref_id, priority, input) VALUES (?,?,?,?,?)")
            ->execute([$task, $refType, $refId, $prio, json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable $e) { return null; }
}

/** Ultimo resultado terminado para una referencia (array decodificado) o null. */
function ia_resultado(PDO $pdo, string $task, string $refType, int $refId): ?array {
    try {
        $q = $pdo->prepare("SELECT result FROM ia_jobs WHERE task_key = ? AND ref_type = ? AND ref_id = ? AND status = 'done' ORDER BY job_id DESC LIMIT 1");
        $q->execute([$task, $refType, $refId]);
        $r = $q->fetchColumn();
        return $r ? (json_decode((string)$r, true) ?: null) : null;
    } catch (Throwable $e) { return null; }
}

/* ---- handlers ---------------------------------------------------------------------------------------------------- */
$IA_HANDLERS = [];
/** Registra que hacer cuando llega el resultado de una tarea: function (PDO $pdo, array $job, array $res): void. */
function ia_handler(string $task, callable $fn): void { global $IA_HANDLERS; $IA_HANDLERS[$task] = $fn; }

/**
 * Reserva hasta $n trabajos para el worker (status processing, leased_until). Devuelve lo que el worker necesita:
 * id, task, user, y la ficha de la tarea la pide aparte (?act=tareas). Trabajos de tareas apagadas no se sirven.
 */
function ia_lease(PDO $pdo, int $n): array {
    $out = [];
    $pdo->beginTransaction();
    $q = $pdo->prepare("SELECT j.job_id, j.task_key, j.input, t.lease_sec FROM ia_jobs j JOIN ia_tasks t ON t.task_key = j.task_key
                         WHERE t.enabled = 1 AND (j.status = 'queued' OR (j.status = 'processing' AND j.leased_until < NOW()))
                         ORDER BY j.priority DESC, j.job_id ASC LIMIT $n FOR UPDATE");
    $q->execute();
    foreach ($q->fetchAll() as $j) {
        $lease = max(60, (int)$j['lease_sec']);
        $pdo->prepare("UPDATE ia_jobs SET status = 'processing', leased_until = NOW() + INTERVAL $lease SECOND WHERE job_id = ?")->execute([$j['job_id']]);
        $in = json_decode((string)$j['input'], true) ?: [];
        $out[] = ['id' => (int)$j['job_id'], 'task' => $j['task_key'], 'user' => (string)($in['user'] ?? '')];
    }
    $pdo->commit();
    return $out;
}

/** El worker devuelve el resultado: se guarda y se aplica el handler de la tarea. Devuelve el estado final. */
function ia_aplicar(PDO $pdo, int $jobId, array $res, string $model = '', ?int $ms = null, ?int $tokens = null): ?string {
    global $IA_HANDLERS;
    $q = $pdo->prepare("SELECT * FROM ia_jobs WHERE job_id = ? AND status = 'processing'"); $q->execute([$jobId]);
    $job = $q->fetch();
    if (!$job) return null;   // caducado o ya resuelto
    $job['input'] = json_decode((string)$job['input'], true) ?: [];
    $status = 'done'; $err = null;
    if (isset($IA_HANDLERS[$job['task_key']])) {
        try { $pdo->beginTransaction(); ($IA_HANDLERS[$job['task_key']])($pdo, $job, $res); $pdo->commit(); }
        catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $status = 'failed'; $err = $e->getMessage(); }
    }
    if ($err !== null) $res['_error'] = mb_substr($err, 0, 400);
    $pdo->prepare("UPDATE ia_jobs SET status = ?, result = ?, model = ?, ms = ?, tokens = ?, leased_until = NULL, processed = NOW() WHERE job_id = ?")
        ->execute([$status, json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), mb_substr($model, 0, 80) ?: null, $ms, $tokens, $jobId]);
    return $status;
}

/** El worker no pudo con el trabajo: vuelve a la cola o, pasados los reintentos de la tarea, a needs_human. */
function ia_fallo(PDO $pdo, int $jobId, string $error): ?string {
    $q = $pdo->prepare("SELECT j.attempts, t.max_retries FROM ia_jobs j JOIN ia_tasks t ON t.task_key = j.task_key WHERE j.job_id = ? AND j.status = 'processing'");
    $q->execute([$jobId]);
    if (!($j = $q->fetch())) return null;
    $at = (int)$j['attempts'] + 1;
    $ns = $at >= max(1, (int)$j['max_retries']) ? 'needs_human' : 'queued';
    $pdo->prepare("UPDATE ia_jobs SET status = ?, attempts = ?, leased_until = NULL, result = ? WHERE job_id = ?")
        ->execute([$ns, $at, json_encode(['_error' => 'fallo del modelo: ' . mb_substr($error, 0, 300)], JSON_UNESCAPED_UNICODE), $jobId]);
    return $ns;
}

/** Limpieza (la llama el ciclo): trabajos terminados mas viejos que ia_keep_days. */
function ia_limpiar(PDO $pdo): void {
    try { $pdo->prepare("DELETE FROM ia_jobs WHERE status IN ('done','failed') AND processed < NOW() - INTERVAL ? DAY")->execute([max(1, (int)cfg('ia_keep_days', 30))]); } catch (Throwable $e) {}
}

/** Estado del worker generico, para admin → IA y /moderacion. */
function ia_worker_line(): string {
    $seen = (string)cfg('ia_worker_seen', '');
    if ($seen === '') return 'El worker de IA no se ha conectado nunca.';
    $ago = time() - strtotime($seen);
    return ($ago < 180 ? 'Worker de IA conectado' : 'Worker de IA desconectado') . ' (último contacto ' . hace($seen) . ').';
}

/** Resumen de la cola por tarea y estado (admin → IA). */
function ia_resumen(PDO $pdo): array {
    try { return $pdo->query("SELECT task_key, status, COUNT(*) n, MAX(created) ultimo FROM ia_jobs GROUP BY task_key, status")->fetchAll(); } catch (Throwable $e) { return []; }
}

/* ---- tarea «relacionadas» ----------------------------------------------------------------------------------------
 * Al publicarse un envio (enviar.php) se buscan candidatas por texto completo (ft_stories: titulo + entradilla) entre
 * las ultimas relacionadas_dias y, si hay alguna, se le pide al modelo que diga cuales tratan de lo mismo. El handler
 * guarda las que pasen de relacionadas_min en story_related, en los dos sentidos. En el hilo: story_relacionadas(). */

/** Candidatas por texto completo para una noticia: [story_id => fila]. */
function relacionadas_candidatas(PDO $pdo, array $s, int $max = 15): array {
    $texto = trim((string)$s['title'] . ' ' . mb_substr((string)($s['content'] ?? ''), 0, 300));
    if (mb_strlen($texto) < 12) return [];
    $dias = max(1, (int)cfg('relacionadas_dias', 90));
    try {
        // tope de 300 ms (MySQL 8; MariaDB lo ignora): si la busqueda se alarga, se corta y quien envia no ve la caja, pero no espera (30/10/2026)
        $q = $pdo->prepare("SELECT /*+ MAX_EXECUTION_TIME(300) */ story_id, title, source, content, sent_date, MATCH(title, content) AGAINST (? IN NATURAL LANGUAGE MODE) rel
                              FROM stories WHERE story_id <> ? AND status IN ('nuevas','destacadas','archived') AND sent_date >= NOW() - INTERVAL ? DAY
                               AND MATCH(title, content) AGAINST (? IN NATURAL LANGUAGE MODE)
                             ORDER BY rel DESC LIMIT " . max(1, $max));
        $q->execute([$texto, (int)$s['story_id'], $dias, $texto]);
        $out = [];
        foreach ($q as $r) $out[(int)$r['story_id']] = $r;
        return $out;
    } catch (Throwable $e) { return []; }
}

/** Encola la busqueda de relacionadas para un envio recien publicado (o corregido). Devuelve el job_id o null. */
function relacionadas_pedir(PDO $pdo, int $sid): ?int {
    if (!ia_tarea_activa($pdo, 'relacionadas')) return null;
    $q = $pdo->prepare("SELECT story_id, title, source, content FROM stories WHERE story_id = ?"); $q->execute([$sid]);
    if (!($s = $q->fetch())) return null;
    $cand = relacionadas_candidatas($pdo, $s);
    if (!$cand) return null;
    $txt = "<datos>\nNOTICIA NUEVA\nTitular: " . $s['title'] . "\n" . ($s['source'] ? "Medio: {$s['source']}\n" : '')
         . ($s['content'] ? "Entradilla: " . mb_substr(preg_replace('/\s+/u', ' ', (string)$s['content']), 0, 500) . "\n" : '') . "\nYA ENVIADAS\n";
    foreach ($cand as $id => $c)
        $txt .= "[$id] " . $c['title'] . ($c['source'] ? " — {$c['source']}" : '') . " — " . substr((string)$c['sent_date'], 0, 10)
              . ($c['content'] ? "\n    " . mb_substr(preg_replace('/\s+/u', ' ', (string)$c['content']), 0, 220) : '') . "\n";
    $txt .= "</datos>";
    return ia_pedir($pdo, 'relacionadas', ['user' => $txt, 'candidatas' => array_keys($cand)], 'story', $sid);
}

ia_handler('relacionadas', function (PDO $pdo, array $job, array $res): void {
    $sid = (int)$job['ref_id'];
    $cand = array_map('intval', (array)($job['input']['candidatas'] ?? []));
    $min = (float)cfg('relacionadas_min', 0.5);
    $pdo->prepare("DELETE FROM story_related WHERE story_id = ?")->execute([$sid]);   // se recalcula entero (p. ej. tras corregir el envio)
    $ins = $pdo->prepare("INSERT INTO story_related (story_id, related_id, score, note) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE score = GREATEST(score, VALUES(score)), note = VALUES(note)");
    foreach ((array)($res['relacionadas'] ?? []) as $r) {
        $id = (int)($r['id'] ?? 0); $sc = max(0.0, min(1.0, (float)($r['relacion'] ?? 0)));
        if ($id <= 0 || $id === $sid || !in_array($id, $cand, true) || $sc < $min) continue;   // solo lo que se le ofrecio: el modelo no inventa ids
        $nota = mb_substr(trim((string)($r['nota'] ?? '')), 0, 160) ?: null;
        $ins->execute([$sid, $id, $sc, $nota]);
        $ins->execute([$id, $sid, $sc, $nota]);
    }
});

/** Relacionadas de un hilo, para mostrarlas: filas con story_id, title, score, note. */
function story_relacionadas(PDO $pdo, int $sid): array {
    try {
        $q = $pdo->prepare("SELECT s.story_id, s.title, s.source, s.content_type, s.sent_date, r.score, r.note FROM story_related r JOIN stories s ON s.story_id = r.related_id
                             WHERE r.story_id = ? AND s.status IN ('nuevas','destacadas','archived') ORDER BY r.score DESC, s.sent_date DESC LIMIT " . max(1, (int)cfg('relacionadas_max', 5)));
        $q->execute([$sid]);
        return $q->fetchAll();
    } catch (Throwable $e) { return []; }
}
