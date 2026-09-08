#!/usr/bin/php
<?php
/**
 * Portero (premoderacion legal). Solo actua si config.mod_enabled = 1.
 * Servicio: php scripts/moderar.php --loop   |  cron: * * * * * php scripts/moderar.php
 * FAIL-CLOSED: nada se publica sin veredicto 'ok'.
 * El prompt es publico por diseño.
 */
declare(strict_types=1);
$_SERVER['REQUEST_METHOD'] = 'CLI';
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/aura.php';

const SYSTEM_PROMPT = <<<'TXT'
Eres un clasificador de moderacion legal para un sitio de conversacion en español.
Tu UNICO criterio es la ley. NO moderas tono, insultos, opiniones, ideologia, sarcasmo,
criticas duras ni contenido ofensivo: todo eso es LEGAL y debe aprobarse.
Rechaza UNICAMENTE si el texto encaja claramente en una de estas categorias:
1. amenaza: amenaza creible de violencia contra persona o grupo concreto.
2. odio_penal: incitacion directa a la violencia o al odio contra un grupo por etnia,
   religion, orientacion sexual, etc. (no basta el insulto: debe haber incitacion).
3. csam: cualquier contenido sexual que involucre a menores.
4. doxxing: datos personales privados de un tercero (direccion, telefono, DNI, datos medicos).
5. delito_facilitado: instrucciones concretas para un delito grave (armas, explosivos, malware).
6. terrorismo: apologia o captacion terrorista en terminos penales.
En caso de DUDA razonable, aprueba. Responde EXCLUSIVAMENTE con JSON valido, sin texto extra:
{"verdict":"ok"}  o  {"verdict":"reject","category":"<una de las 6>","quote":"<fragmento minimo>"}
TXT;

function llamar_modelo(string $texto): ?array {
    $payload = json_encode(['model' => cfg('mod_model'), 'stream' => false, 'format' => 'json',
        'options' => ['temperature' => 0],
        'messages' => [['role' => 'system', 'content' => SYSTEM_PROMPT], ['role' => 'user', 'content' => $texto]]],
        JSON_UNESCAPED_UNICODE);
    $ch = curl_init(cfg('mod_ollama_url'));
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => (int)cfg('mod_timeout_sec', 30)]);
    $raw = curl_exec($ch); curl_close($ch);
    if ($raw === false) return null;
    $v = json_decode(json_decode($raw, true)['message']['content'] ?? '', true);
    if (!is_array($v) || !in_array($v['verdict'] ?? '', ['ok', 'reject'], true)) return null;
    if ($v['verdict'] === 'reject' && empty($v['category'])) return null;
    return $v;
}

function procesar(PDO $pdo, array $item): void {
    if ($item['item_type'] === 'story') {
        $q = $pdo->prepare("SELECT CONCAT('TITULO: ', title, '\nURL: ', COALESCE(url,''), '\nTEXTO: ', COALESCE(content,'')) FROM stories WHERE story_id = ?");
    } else {
        $q = $pdo->prepare("SELECT content FROM comments WHERE comment_id = ?");
    }
    $q->execute([$item['ref_id']]);
    $texto = $q->fetchColumn();
    $v = $texto ? llamar_modelo((string)$texto) : null;

    if ($v === null) {
        $at = (int)$item['attempts'] + 1;
        $ns = $at >= (int)cfg('mod_max_retries', 3) ? 'needs_human' : 'queued';
        $pdo->prepare("UPDATE mod_queue SET status=?, attempts=? WHERE item_id=?")->execute([$ns, $at, $item['item_id']]);
        echo date('c') . "  item {$item['item_id']} sin veredicto (intento $at) -> $ns\n";
        return;
    }
    $ok = $v['verdict'] === 'ok';
    $pdo->beginTransaction();
    if ($item['item_type'] === 'story') {
        $pdo->prepare("UPDATE stories SET status=? WHERE story_id=? AND status='pending_mod'")->execute([$ok ? 'nuevas' : 'rejected', $item['ref_id']]);
        if ($ok) { $uq = $pdo->prepare("SELECT user_id FROM stories WHERE story_id=?"); $uq->execute([$item['ref_id']]); if ($uid = $uq->fetchColumn()) aura_add($pdo, (int)$uid, (float)cfg('aura_envio', 0.1), 'envio', (int)$item['ref_id']); }
    } else {
        $pdo->prepare("UPDATE comments SET status=? WHERE comment_id=? AND status='pending_mod'")->execute([$ok ? 'ok' : 'rejected', $item['ref_id']]);
        if ($ok) $pdo->prepare("UPDATE stories s JOIN comments c ON c.story_id=s.story_id SET s.comments=s.comments+1 WHERE c.comment_id=?")->execute([$item['ref_id']]);
    }
    $pdo->prepare("UPDATE mod_queue SET status=?, verdict=?, reason=?, processed=NOW() WHERE item_id=?")
        ->execute([$ok ? 'ok' : 'reject', json_encode($v, JSON_UNESCAPED_UNICODE), $ok ? null : substr($v['category'], 0, 64), $item['item_id']]);
    $pdo->commit();
    echo date('c') . "  {$item['item_type']} {$item['ref_id']} " . ($ok ? 'OK' : "RECHAZADO ({$v['category']})") . "\n";
}

if (cfg('mod_enabled') !== '1') { echo date('c') . "  mod_enabled=0, nada que hacer\n"; exit(0); }
$lock = fopen('/tmp/deltha-mod.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) exit(0);
$loop = in_array('--loop', $argv ?? [], true);
do {
    $pdo->exec("UPDATE mod_queue SET status='processing' WHERE status='queued' ORDER BY created LIMIT 20");
    $items = $pdo->query("SELECT item_id, item_type, ref_id, attempts FROM mod_queue WHERE status='processing' ORDER BY created LIMIT 20")->fetchAll();
    foreach ($items as $it) procesar($pdo, $it);
    if ($loop && !$items) sleep(2);
} while ($loop);
flock($lock, LOCK_UN);
