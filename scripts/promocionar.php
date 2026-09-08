#!/usr/bin/php
<?php
/** cron: cada 5 min -> /usr/bin/php /var/www/deltha/scripts/promocionar.php >> /var/log/deltha-promo.log 2>&1 */
declare(strict_types=1);
$_SERVER['REQUEST_METHOD'] = 'CLI';
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/ciclo.php';
$lock = fopen('/tmp/deltha-promo.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) exit(0);
foreach (run_cycle($pdo) as $l) echo date('c') . "  $l\n";
flock($lock, LOCK_UN);
