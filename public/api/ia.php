<?php
/**
 * /api/ia.php — punto de acceso unico del worker generico de IA (local/ia/ia.py), que corre en el PC de la redaccion.
 * El servidor nunca llama al PC: es el PC quien pide trabajo y devuelve resultados. lib/ia.php, migra-173.
 * Autenticacion: cabecera X-Deltha-Token = [ia] token de /etc/deltha/config.ini (si no existe, vale [mod] token;
 * vacio = API cerrada).
 *
 *   GET  ?act=ping                  -> {"ok":true,"enabled":bool}
 *   GET  ?act=tareas                -> {"tareas":{key:{kind,model,system,schema,temperature,max_tokens}}, "hash":"..."}
 *                                      (solo las activas; el worker la pide en cada pasada y se queda con el hash para saber si cambio algo)
 *   GET  ?act=lease&n=4             -> {"enabled":bool,"jobs":[{"id","task","user"}]}
 *                                      id numerico = ia_jobs; "m123" = elemento 123 de mod_queue (tarea «moderacion»: mismo flujo
 *                                      que /api/moderacion.php, lib/moderacion.php decide y la cola humana no cambia)
 *   POST ?act=result {"id","result":{...},"model","ms","tokens"}   -> {"ok":true,"status":"done|failed|ok|flag|hidden"}
 *   POST ?act=fail   {"id","error"}                                 -> {"ok":true,"status":"queued|needs_human"}
 */
define('DDD_PUBLIC', 1);   // sin la puerta de la clave de acceso: la protege el token
require __DIR__ . '/../../lib/app.php';
require_once __DIR__ . '/../../lib/moderacion.php';   // lib/ia.php ya lo carga app.php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function api_out(array $a, int $code = 200): never {
    http_response_code($code);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}

$token = trim((string)($ini['ia']['token'] ?? $ini['mod']['token'] ?? ''));
$given = (string)($_SERVER['HTTP_X_DELTHA_TOKEN'] ?? '');
if (strlen($token) < 24 || !hash_equals($token, $given)) api_out(['error' => 'no autorizado'], 403);

// ultimo contacto del worker (admin → IA y /moderacion); si sirve la moderacion, tambien cuenta como worker de moderacion
$up = $pdo->prepare("INSERT INTO config (ckey, cvalue, descr) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue)");
$up->execute(['ia_worker_seen', date('Y-m-d H:i:s'), 'Ultima conexion del worker generico de IA (lo escribe /api/ia.php)']);
$moderaAqui = ($t = ia_tarea($pdo, 'moderacion')) && (int)$t['enabled'] && mod_on();
if ($moderaAqui) $up->execute(['mod_worker_seen', date('Y-m-d H:i:s'), 'Ultima conexion del worker de IA local']);

$act = (string)($_GET['act'] ?? '');
$body = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) api_out(['error' => 'JSON no valido'], 400);
}

if ($act === 'ping') api_out(['ok' => true, 'enabled' => ia_on()]);

if ($act === 'tareas') {
    $out = [];
    foreach (ia_tareas($pdo) as $t) {
        if (!(int)$t['enabled']) continue;
        $schema = trim((string)$t['schema_json']) !== '' ? json_decode((string)$t['schema_json'], true) : null;
        $out[$t['task_key']] = ['kind' => $t['kind'], 'model' => (string)$t['model'], 'system' => (string)$t['system_prompt'], 'schema' => $schema,
                                'temperature' => (float)$t['temperature'], 'max_tokens' => (int)$t['max_tokens']];
    }
    $json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    api_out(['tareas' => $out, 'hash' => substr(sha1((string)$json), 0, 12)]);
}

if ($act === 'lease') {
    $n = max(1, min(20, (int)($_GET['n'] ?? 4)));
    $jobs = [];
    // la moderacion pasa delante (prioridad 9 de serie): lo que aun quede de cupo se sirve de ia_jobs
    if ($moderaAqui) foreach (mod_lease($pdo, $n) as $it)
        $jobs[] = ['id' => 'm' . $it['id'], 'task' => 'moderacion', 'user' => ($it['context'] !== '' ? "Contexto: {$it['context']}\n\n" : '') . "<texto>\n{$it['text']}\n</texto>"];
    if (ia_on() && count($jobs) < $n) foreach (ia_lease($pdo, $n - count($jobs)) as $j) $jobs[] = $j;
    api_out(['enabled' => ia_on() || $moderaAqui, 'jobs' => $jobs]);
}

if ($act === 'result' || $act === 'fail') {
    $id = (string)($body['id'] ?? '');
    $esMod = str_starts_with($id, 'm');
    $num = (int)ltrim($id, 'm');
    if ($num <= 0) api_out(['error' => 'falta id'], 400);

    if ($act === 'fail') {
        $st = $esMod ? mod_fail($pdo, $num, (string)($body['error'] ?? '')) : ia_fallo($pdo, $num, (string)($body['error'] ?? ''));
        if ($st === null) api_out(['error' => 'ese trabajo no esta reservado (caducado o ya resuelto)'], 409);
        api_out(['ok' => true, 'status' => $st]);
    }

    $res = $body['result'] ?? null;
    if (!is_array($res)) api_out(['error' => 'falta result'], 400);
    $model = (string)($body['model'] ?? '');
    if ($esMod) {
        $q = $pdo->prepare("SELECT * FROM mod_queue WHERE item_id = ? AND status = 'processing'"); $q->execute([$num]);
        if (!($it = $q->fetch())) api_out(['error' => 'ese elemento no esta reservado (caducado o ya resuelto)'], 409);
        $cat = (string)($res['categoria'] ?? '');
        if ($cat !== 'ninguna' && !in_array($cat, MOD_CATS, true)) api_out(['error' => 'categoria desconocida: ' . mb_substr($cat, 0, 40)], 400);
        if (!is_numeric($res['confianza'] ?? null)) api_out(['error' => 'falta confianza'], 400);
        $pdo->beginTransaction();
        $st = mod_apply_verdict($pdo, $it, $res + ['modelo' => $model]);
        $pdo->commit();
        api_out(['ok' => true, 'status' => $st]);
    }
    $st = ia_aplicar($pdo, $num, $res, $model, isset($body['ms']) ? (int)$body['ms'] : null, isset($body['tokens']) ? (int)$body['tokens'] : null);
    if ($st === null) api_out(['error' => 'ese trabajo no esta reservado (caducado o ya resuelto)'], 409);
    api_out(['ok' => true, 'status' => $st]);
}

api_out(['error' => 'accion desconocida'], 400);
