#!/usr/bin/php
<?php
/** cron: cada minuto -> /usr/bin/php /var/www/deltha/scripts/promocionar.php >> /var/log/deltha-promo.log 2>&1
 *  El intervalo real lo marca config.cycle_minutes (admin -> Ciclo): el script solo
 *  ejecuta el ciclo en los minutos multiplos de ese valor; el resto de veces sale en silencio.
 *  Con --force se ejecuta siempre (pruebas a mano).
 *  Ademas lee las fuentes externas (lib/fuentes.php) cuando vence su intervalo (sources.interval_min). */
declare(strict_types=1);
$_SERVER['REQUEST_METHOD'] = 'CLI';
if (function_exists('proc_nice')) @proc_nice(10);   // el cron cede la CPU a la web (php-fpm): leer feeds y convertir fotos puede esperar
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/ciclo.php';
require __DIR__ . '/../lib/fuentes.php';
$force = in_array('--force', $argv, true);
$cycle = max(1, (int)cfg('cycle_minutes', 5));
$lock = fopen('/tmp/deltha-promo.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) exit(0);
if ($force || (int)date('i') % $cycle === 0) {
    foreach (run_cycle($pdo) as $l) echo date('c') . "  $l\n";
}
try { foreach (fuentes_tick($pdo, $force) as $l) echo date('c') . "  $l\n"; }
catch (Throwable $e) { echo date('c') . "  fuentes: ERROR " . $e->getMessage() . "\n"; }
foreach (ana_tick($pdo) as $l) echo date('c') . "  $l\n";   // analitica: cierra los dias completos en ana_daily y poda hits
flock($lock, LOCK_UN);
