<?php
/**
 * /api/lector.php — lo usa SOLO el lector local de feeds (local/lector/lector.py), que corre en el PC de la redaccion y
 * descarga lo que la web de una fuente no le deja descargar al servidor (sources.relay = 1, migra-098). El servidor nunca
 * llama al PC. Autenticacion: cabecera X-Deltha-Token = token de [lector], [mod] o [agent] de /etc/deltha/config.ini.
 *
 *   GET  ?act=lease            -> {"sources":[{"slug","name","portada":url,"nuevas":url,"due":bool}]}   (las de relay = 1 activas;
 *                                 due = ya toca segun su intervalo)
 *   POST ?act=feed   {"slug","portada":xml,"nuevas":xml,"error_portada":str?,"error_nuevas":str?}
 *                              -> {"ok":true,"log":[...],"images":[{"item_id":N,"thumb":url,"url":url}]}   (fotos que faltan)
 *   POST ?act=image&item=N     (cuerpo: los bytes de la imagen)  -> {"ok":true|false}
 */
define('DDD_PUBLIC', 1);   // sin la puerta de la clave de acceso: la protege el token
require __DIR__ . '/../../lib/app.php';
require_once __DIR__ . '/../../lib/imagen.php';
require_once __DIR__ . '/../../lib/fuentes.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function api_out(array $a, int $code = 200): never {
    http_response_code($code);
    echo json_encode($a, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$given = (string)($_SERVER['HTTP_X_DELTHA_TOKEN'] ?? '');
$ok = false;
foreach (['lector', 'mod', 'agent'] as $sec) { $tk = trim((string)($ini[$sec]['token'] ?? '')); if (strlen($tk) >= 24 && hash_equals($tk, $given)) $ok = true; }
if (!$ok) api_out(['error' => 'no autorizado'], 403);

try { $pdo->prepare("UPDATE config SET cvalue = ? WHERE ckey = 'relay_worker_seen'")->execute([date('Y-m-d H:i:s')]); } catch (Throwable $e) {}

$act = (string)($_GET['act'] ?? '');
$relay = array_values(array_filter(fuentes_on() ? fuentes_activas($pdo) : [], fn($s) => !empty($s['relay'])));

if ($act === 'lease') {
    $out = [];
    foreach ($relay as $s) $out[] = ['slug' => $s['slug'], 'name' => $s['name'], 'portada' => (string)$s['feed_portada'], 'nuevas' => (string)$s['feed_nuevas'],
        'due' => !$s['last_fetch'] || time() - strtotime($s['last_fetch']) >= max(1, (int)$s['interval_min']) * 60 - 5];
    api_out(['sources' => $out]);
}

if ($act === 'feed' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) api_out(['error' => 'JSON no valido'], 400);
    $src = null;
    foreach ($relay as $s) if ($s['slug'] === (string)($body['slug'] ?? '')) $src = $s;
    if (!$src) api_out(['error' => 'esa fuente no es de lector local'], 404);
    $xmls = ['portada' => (string)($body['portada'] ?? ''), 'nuevas' => (string)($body['nuevas'] ?? ''),
             'error_portada' => (string)($body['error_portada'] ?? ''), 'error_nuevas' => (string)($body['error_nuevas'] ?? '')];
    if (strlen($xmls['portada']) > 4000000 || strlen($xmls['nuevas']) > 4000000) api_out(['error' => 'feed demasiado grande'], 413);
    try { $log = fuente_actualizar($pdo, $src, $xmls); }
    catch (Throwable $e) { api_out(['error' => $e->getMessage()], 500); }
    // fotos que faltan: las noticias sin imagen de esta pasada, la mas reciente primero, como mucho sources_og_per_run
    $images = [];
    $pend = $GLOBALS['ddd_relay_pend'] ?? [];
    if ($pend) {
        $st = $pdo->prepare("SELECT item_id, ext_id FROM source_items WHERE source_id = ? AND image IS NULL ORDER BY pub_date DESC LIMIT 60");
        $st->execute([$src['source_id']]);
        $max = max(1, (int)cfg('sources_og_per_run', 10));
        foreach ($st->fetchAll() as $r) {
            if (!isset($pend[(string)$r['ext_id']])) continue;
            $images[] = ['item_id' => (int)$r['item_id']] + $pend[(string)$r['ext_id']];
            if (count($images) >= $max) break;
        }
    }
    api_out(['ok' => true, 'log' => $log, 'images' => $images]);
}

if ($act === 'image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_GET['item'] ?? 0);
    $st = $pdo->prepare("SELECT i.item_id, i.image, i.url FROM source_items i JOIN sources s ON s.source_id = i.source_id WHERE i.item_id = ? AND s.relay = 1");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) api_out(['error' => 'esa noticia no es de una fuente de lector local'], 404);
    if ($row['image']) api_out(['ok' => true, 'ya' => true]);
    $bytes = (string)file_get_contents('php://input', false, null, 0, (int)cfg('img_max_bytes', 6000000) + 1);
    $name = imagen_de_pagina($bytes, (string)$row['url']);   // misma receta que un envio: recorte y JPEG limpio en /media; sin la generica del medio
    if (!$name) api_out(['ok' => false, 'error' => 'imagen no valida, demasiado pequeña o generica del medio']);
    $pdo->prepare("UPDATE source_items SET image = ? WHERE item_id = ? AND image IS NULL")->execute([$name, $id]);
    api_out(['ok' => true, 'image' => $name]);
}

api_out(['error' => 'accion desconocida'], 400);
