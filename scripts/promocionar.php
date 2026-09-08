#!/usr/bin/php
<?php
/** cron: cada minuto -> /usr/bin/php /var/www/deltha/scripts/promocionar.php >> /var/log/deltha-promo.log 2>&1
 *  El intervalo real lo marca config.cycle_minutes (admin -> Ciclo): el script solo
 *  ejecuta el ciclo en los minutos multiplos de ese valor; el resto de veces sale en silencio.
 *  Con --force se ejecuta siempre (pruebas a mano). */
declare(strict_types=1);
$_SERVER['REQUEST_METHOD'] = 'CLI';
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/ciclo.php';
$cycle = max(1, (int)cfg('cycle_minutes', 5));
if (!in_array('--force', $argv, true) && (int)date('i') % $cycle !== 0) exit(0);
$lock = fopen('/tmp/deltha-promo.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) exit(0);
foreach (run_cycle($pdo) as $l) echo date('c') . "  $l\n";
flock($lock, LOCK_UN);
