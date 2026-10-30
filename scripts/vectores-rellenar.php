#!/usr/bin/php
<?php
/**
 * Vectoriza de una vez los envios que aun no tienen vector de titular (lib/vectores.php, migra-175), a tandas de 200:
 *   php /var/www/deltha/scripts/vectores-rellenar.php
 * El ciclo hace lo mismo a 40 por pasada; esto es para ponerse al dia tras instalar el servicio (scripts/vectores/instalar.sh).
 * Se para si el servicio no responde.
 */
define('DDD_PUBLIC', 1);
require __DIR__ . '/../lib/app.php';
if (!vec_on()) exit("vectores apagados (config vectores_on)\n");
$e = vec_estado();
if (!$e['ok']) exit("el servicio de vectores no responde: {$e['error']}\n");
$total = 0;
while (($n = vectores_rellenar($pdo, 200)) > 0) { $total += $n; echo date('H:i:s'), "  $n vectorizados (total $total)\n"; }
echo "hecho: $total envios vectorizados\n";
