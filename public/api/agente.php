<?php
/**
 * /api/agente.php — lo usa SOLO el worker del agente comentarista (local/comentarista/comentarista.py), que corre en el PC
 * de la redaccion. El servidor nunca llama al PC. Autenticacion: cabecera X-Deltha-Token = [agent] token de
 * /etc/deltha/config.ini (si no existe, vale [mod] token; vacio = API cerrada). lib/agente.php
 *
 *   GET  ?act=ping                 -> {"ok":true,"enabled":bool,"user":"nick","left":n}
 *   GET  ?act=lease&n=3            -> {"enabled":bool,"left":n,"items":[{story_id,kind,title,url,source,lead,topic,author,sent,status,comments:[{id,parent,who,text}]}]}
 *   POST ?act=comment {"story_id","content","parent"?,"model","note"}  -> {"ok":true,"comment_id":N,"left":n}
 *   POST ?act=skip    {"story_id","note","model"?,"fail"?:true}         -> {"ok":true}
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
$bot = agent_on() ? agent_user($pdo) : null;
$left = $bot ? agent_quota_left($pdo, (int)$bot['user_id']) : 0;

if ($act === 'ping') api_out(['ok' => true, 'enabled' => (bool)$bot, 'user' => $bot['login'] ?? '', 'left' => $left]);

if ($act === 'lease') {
    if (!$bot) api_out(['enabled' => false, 'left' => 0, 'items' => [], 'why' => agent_on() ? 'la cuenta agent_user no existe' : 'agent_enabled = 0 o agent_user vacio']);
    if ($left <= 0) api_out(['enabled' => true, 'left' => 0, 'items' => []]);
    $n = max(1, min(10, (int)($_GET['n'] ?? 3)));
    api_out(['enabled' => true, 'left' => $left, 'items' => agent_lease($pdo, $bot, min($n, $left))]);
}

if ($act === 'comment') {
    if (!$bot) api_out(['error' => 'agente apagado'], 409);
    [$cid, $err] = agent_comment($pdo, $bot, (int)($body['story_id'] ?? 0), (string)($body['content'] ?? ''), (int)($body['parent'] ?? 0) ?: null,
                                 (string)($body['model'] ?? ''), (string)($body['note'] ?? ''));
    if ($err) api_out(['error' => $err], 409);
    api_out(['ok' => true, 'comment_id' => $cid, 'left' => agent_quota_left($pdo, (int)$bot['user_id'])]);
}

if ($act === 'skip') {
    $sid = (int)($body['story_id'] ?? 0);
    if ($sid <= 0) api_out(['error' => 'falta story_id'], 400);
    agent_skip($pdo, $sid, !empty($body['fail']) ? 'fail' : 'skip', (string)($body['note'] ?? ''), (string)($body['model'] ?? ''));
    api_out(['ok' => true]);
}

api_out(['error' => 'accion desconocida'], 400);
