#!/usr/bin/php
<?php
/** Lee ahora mismo los feeds de las fuentes externas (sin esperar al cron ni al intervalo):
 *    php scripts/fuentes.php            -> todas las fuentes activas
 *    php scripts/fuentes.php meneame    -> solo esa
 *  El cron normal (promocionar.php) ya las lee cada sources.interval_min minutos; esto es para probar. */
declare(strict_types=1);
$_SERVER['REQUEST_METHOD'] = 'CLI';
if (function_exists('proc_nice')) @proc_nice(10);   // el cron cede la CPU a la web (php-fpm): leer feeds y convertir fotos puede esperar
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/fuentes.php';
$solo = $argv[1] ?? '';
foreach (fuentes_activas($pdo) as $src) {
    if ($solo !== '' && $src['slug'] !== $solo) continue;
    try { foreach (fuente_actualizar($pdo, $src) as $l) echo date('c') . "  $l\n"; }
    catch (Throwable $e) { echo date('c') . "  fuente {$src['slug']}: ERROR " . $e->getMessage() . "\n"; }
}
