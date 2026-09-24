<?php
/**
 * /api/moderacion.php — lo usa SOLO el worker de IA local (local/moderador/moderador.py), que corre en el PC
 * de la redaccion. El servidor nunca llama al PC: es el PC quien pide trabajo y devuelve veredictos.
 * Autenticacion: cabecera X-Deltha-Token = [mod] token de /etc/deltha/config.ini (si esta vacio, la API esta cerrada).
 *
 *   GET  ?act=lease&n=4                       -> {"enabled":bool, "items":[{"id","type","ref","text","context"}]}
 *   POST ?act=verdict  {"id","categoria","confianza","motivo","cita","modelo"}   -> {"ok":true,"status":"ok|flag|hidden"}
 *   POST ?act=fail     {"id","error"}                                               -> {"ok":true,"status":"queued|needs_human"}
 *   GET  ?act=ping                            -> {"ok":true}
 */
define('DDD_PUBLIC', 1);   // sin la puerta de la clave de acceso: la protege el token
require __DIR__ . '/../../lib/app.php';
require __DIR__ . '/../../lib/moderacion.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function api_out(array $a, int $code = 200): never {
    http_response_code($code);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}

$token = trim((string)($ini['mod']['token'] ?? ''));
$given = (string)($_SERVER['HTTP_X_DELTHA_TOKEN'] ?? '');
if (strlen($token) < 24 || !hash_equals($token, $given)) api_out(['error' => 'no autorizado'], 403);

// ultimo contacto del worker (se ve en /moderacion.php)
$pdo->prepare("INSERT INTO config (ckey, cvalue, descr) VALUES ('mod_worker_seen', ?, 'Ultima conexion del worker de IA local') ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue)")
    ->execute([date('Y-m-d H:i:s')]);

$act = (string)($_GET['act'] ?? '');
$body = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) api_out(['error' => 'JSON no valido'], 400);
}

if ($act === 'ping') api_out(['ok' => true, 'enabled' => mod_on()]);

if ($act === 'lease') {
    if (!mod_on()) api_out(['enabled' => false, 'items' => []]);
    $n = max(1, min(20, (int)($_GET['n'] ?? 4)));
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
    api_out(['enabled' => true, 'items' => $items]);
}

if ($act === 'verdict' || $act === 'fail') {
    $q = $pdo->prepare("SELECT * FROM mod_queue WHERE item_id = ? AND status = 'processing'");
    $q->execute([(int)($body['id'] ?? 0)]);
    $it = $q->fetch();
    if (!$it) api_out(['error' => 'ese elemento no esta reservado (caducado o ya resuelto)'], 409);

    if ($act === 'fail') {
        $at = (int)$it['attempts'] + 1;
        $ns = $at >= (int)cfg('mod_max_retries', 3) ? 'needs_human' : 'queued';
        $err = json_encode(['motivo' => 'fallo del modelo: ' . mb_substr((string)($body['error'] ?? ''), 0, 300)], JSON_UNESCAPED_UNICODE);
        $pdo->prepare("UPDATE mod_queue SET status = ?, attempts = ?, leased_until = NULL, verdict = ? WHERE item_id = ?")->execute([$ns, $at, $err, $it['item_id']]);
        api_out(['ok' => true, 'status' => $ns]);
    }

    $cat = (string)($body['categoria'] ?? '');
    if ($cat !== 'ninguna' && !in_array($cat, MOD_CATS, true)) api_out(['error' => 'categoria desconocida: ' . mb_substr($cat, 0, 40)], 400);
    if (!is_numeric($body['confianza'] ?? null)) api_out(['error' => 'falta confianza'], 400);
    $pdo->beginTransaction();
    $st = mod_apply_verdict($pdo, $it, $body);
    $pdo->commit();
    api_out(['ok' => true, 'status' => $st]);
}

api_out(['error' => 'accion desconocida'], 400);
