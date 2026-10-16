<?php
/**
 * /api/agente.php — lo usa SOLO el worker del agente comentarista (local/comentarista/comentarista.py), que corre en el PC
 * de la redaccion. El servidor nunca llama al PC. Autenticacion: cabecera X-Deltha-Token = [agent] token de
 * /etc/deltha/config.ini (si no existe, vale [mod] token; vacio = API cerrada). lib/agente.php
 *
 *   GET  ?act=ping                 -> {"ok":true,"agents":[{"user":"nick","enabled":bool,"left":n}]}
 *   GET  ?act=lease&n=3            -> {"agents":[{user,persona,left,awake,recent:[{id,story_id,reply,when,title,text}]}],
 *                                      "items":[{agent,story_id,kind,title,url,source,lead,topic,author,sent,status,comments:[{id,parent,mine,who,text}],reply_to?,own?}]}
 *                                     (n hilos como mucho por agente activo, con cupo y despierto —franja horaria y pausa, migra-157—; cada item
 *                                      lleva el nick del agente al que va; recent = sus ultimos comentarios; los items con reply_to son respuestas
 *                                      de otros a un comentario suyo (own) que puede contestar)
 *   POST ?act=comment {"agent","story_id","content","parent"?,"reply_to"?,"model","note"}  -> {"ok":true,"comment_id":N,"left":n}
 *   POST ?act=skip    {"agent","story_id","note","model"?,"fail"?:true,"reply_to"?}         -> {"ok":true}
 *
 *  Envios evaluados por el modelo (migra-117; agentes con «la IA local evalua lo que envia»):
 *   GET  ?act=submit_lease[&dry=1] -> {"jobs":[{agent,brief,min,langs,items:[{id,title,lead,source,kind,topic,published}]}]}
 *                                     (un trabajo por agente al que le toca enviar; dry=1: todos, sin reservar: para probar)
 *   POST ?act=submit_pick {"agent","scores":[{"id","score","note"}],"pick":item_id|0,"model","fail"?:true} -> {"ok":true,"story_id":N,"msg":"…"}
 */
define('DDD_PUBLIC', 1);   // sin la puerta de la clave de acceso: la protege el token
require __DIR__ . '/../../lib/app.php';
require __DIR__ . '/../../lib/render.php';
require __DIR__ . '/../../lib/agente.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function api_out(array $a, int $code = 200): never {
    http_response_code($code);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}

$token = trim((string)($ini['agent']['token'] ?? $ini['mod']['token'] ?? ''));
$given = (string)($_SERVER['HTTP_X_DELTHA_TOKEN'] ?? '');
if (strlen($token) < 24 || !hash_equals($token, $given)) api_out(['error' => 'no autorizado'], 403);

try { $pdo->prepare("UPDATE config SET cvalue = ? WHERE ckey = 'agent_worker_seen'")->execute([date('Y-m-d H:i:s')]); } catch (Throwable $e) {}

$act = (string)($_GET['act'] ?? '');
$body = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) api_out(['error' => 'JSON no valido'], 400);
}
$agents = agents_on() ? agents_active($pdo) : [];
$byLogin = [];
foreach ($agents as $a) $byLogin[strtolower($a['login'])] = $a;
$resumen = array_map(fn($a) => ['user' => $a['login'], 'enabled' => true, 'left' => agent_quota_left($pdo, $a)], $agents);

if ($act === 'ping') api_out(['ok' => true, 'agents' => $resumen, 'why' => $agents ? '' : (agents_on() ? 'ningun agente activo' : 'falta la migracion 089')]);

if ($act === 'lease') {
    $n = max(1, min(10, (int)($_GET['n'] ?? 3)));
    $items = []; $out = [];
    foreach ($agents as $a) {
        $left = agent_quota_left($pdo, $a); $awake = agent_awake($a);
        $out[] = ['user' => $a['login'], 'persona' => (string)$a['persona'], 'left' => $left, 'awake' => $awake, 'recent' => agent_recent($pdo, $a)];
        if ($left > 0 && $awake) {
            $items = array_merge($items, agent_reply_lease($pdo, $a, min(2, $n, $left)));   // primero lo que le han contestado
            $items = array_merge($items, agent_lease($pdo, $a, min($n, $left)));
        }
    }
    api_out(['agents' => $out, 'items' => $items, 'why' => $agents ? '' : (agents_on() ? 'ningun agente activo' : 'falta la migracion 089')]);
}

if ($act === 'comment' || $act === 'skip') {
    $a = $byLogin[strtolower((string)($body['agent'] ?? ''))] ?? null;
    if (!$a) api_out(['error' => 'agente desconocido o apagado: ' . mb_substr((string)($body['agent'] ?? ''), 0, 32)], 409);
    $sid = (int)($body['story_id'] ?? 0);
    if ($sid <= 0) api_out(['error' => 'falta story_id'], 400);
    $replyTo = (int)($body['reply_to'] ?? 0);
    if ($act === 'skip') {
        if ($replyTo > 0) agent_reply_skip($pdo, (int)$a['user_id'], $replyTo, !empty($body['fail']) ? 'fail' : 'skip', (string)($body['note'] ?? ''), (string)($body['model'] ?? ''));
        else agent_skip($pdo, (int)$a['user_id'], $sid, !empty($body['fail']) ? 'fail' : 'skip', (string)($body['note'] ?? ''), (string)($body['model'] ?? ''));
        api_out(['ok' => true]);
    }
    [$cid, $err] = agent_comment($pdo, $a, $sid, (string)($body['content'] ?? ''), (int)($body['parent'] ?? 0) ?: null, (string)($body['model'] ?? ''), (string)($body['note'] ?? ''), $replyTo);
    if ($err) api_out(['error' => $err], 409);
    api_out(['ok' => true, 'comment_id' => $cid, 'left' => agent_quota_left($pdo, $a)]);
}

if ($act === 'submit_lease') {
    api_out(['jobs' => agent_review_lease($pdo, $agents, !empty($_GET['dry'])), 'why' => agent_review_on() ? '' : 'falta la migracion 117']);
}

if ($act === 'submit_pick') {
    $a = $byLogin[strtolower((string)($body['agent'] ?? ''))] ?? null;
    if (!$a) api_out(['error' => 'agente desconocido o apagado: ' . mb_substr((string)($body['agent'] ?? ''), 0, 32)], 409);
    if (!agent_review_on()) api_out(['error' => 'falta la migracion 117'], 409);
    [$sid, $msg] = agent_review_pick($pdo, $a, is_array($body['scores'] ?? null) ? $body['scores'] : [], (int)($body['pick'] ?? 0), (string)($body['model'] ?? ''), !empty($body['fail']));
    api_out(['ok' => true, 'story_id' => $sid, 'msg' => $msg]);
}

api_out(['error' => 'accion desconocida'], 400);
