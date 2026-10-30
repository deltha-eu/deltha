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
    api_out(['enabled' => true, 'items' => mod_lease($pdo, (int)($_GET['n'] ?? 4))]);   // (la reserva vive en lib/moderacion.php desde migra-173: la comparte /api/ia.php)
}

if ($act === 'verdict' || $act === 'fail') {
    $q = $pdo->prepare("SELECT * FROM mod_queue WHERE item_id = ? AND status = 'processing'");
    $q->execute([(int)($body['id'] ?? 0)]);
    $it = $q->fetch();
    if (!$it) api_out(['error' => 'ese elemento no esta reservado (caducado o ya resuelto)'], 409);

    if ($act === 'fail') api_out(['ok' => true, 'status' => mod_fail($pdo, (int)$it['item_id'], (string)($body['error'] ?? ''))]);

    $cat = (string)($body['categoria'] ?? '');
    if ($cat !== 'ninguna' && !in_array($cat, MOD_CATS, true)) api_out(['error' => 'categoria desconocida: ' . mb_substr($cat, 0, 40)], 400);
    if (!is_numeric($body['confianza'] ?? null)) api_out(['error' => 'falta confianza'], 400);
    $pdo->beginTransaction();
    $st = mod_apply_verdict($pdo, $it, $body);
    $pdo->commit();
    api_out(['ok' => true, 'status' => $st]);
}

api_out(['error' => 'accion desconocida'], 400);
