<?php
/** php scripts/comprobar-fk.php — comprueba que ningún nombre de CONSTRAINT se usa en dos tablas distintas (24/11/2026).
 *  En MySQL 8 el nombre de una clave foránea es único en toda la base de datos, no por tabla: repetirlo da «ERROR 1826 Duplicate
 *  foreign key constraint name» y la migración (o schema.sql en una instalación nueva) se queda a medias. MariaDB lo tolera, así
 *  que probar solo en MariaDB no lo detecta: pasó con fk_uc_user (user_communities, migra-142, y usenet_comments, migra-212).
 *  Mira sql/schema.sql y todas las sql/migra-*.sql. Sale con 1 si hay algún nombre repetido; lo pasa también la acción de GitHub
 *  .github/workflows/sql.yml en cada PR. Regla: llamar a la clave fk_<tabla entera>_<columna>, sin abreviar la tabla. */
$dir = __DIR__ . '/../sql';
$usos = [];   // nombre => [tabla => [ficheros]]
foreach (array_merge([$dir . '/schema.sql'], glob($dir . '/migra-*.sql')) as $f) {
    $sql = (string)file_get_contents($f);
    $sql = preg_replace('~^\s*--.*$~m', '', $sql);   // fuera los comentarios de línea
    // se recorre el fichero en orden: cada CONSTRAINT pertenece a la última tabla nombrada por un CREATE TABLE / ALTER TABLE
    // (vale también para los ALTER que van dentro de una cadena para PREPARE)
    preg_match_all('~\b(?:CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|ALTER\s+TABLE)\s+`?(\w+)`?|\b(DROP)\s+(?:FOREIGN\s+KEY|CONSTRAINT)\b|\bCONSTRAINT\s+`?(\w+)`?\s+(?:FOREIGN\s+KEY|UNIQUE|CHECK|PRIMARY)~i', $sql, $m, PREG_SET_ORDER);
    $tabla = '';
    foreach ($m as $x) {
        if (($x[1] ?? '') !== '') { $tabla = strtolower($x[1]); continue; }
        if (($x[2] ?? '') !== '') continue;   // quitar una clave no es definirla
        if ($tabla !== '' && ($x[3] ?? '') !== '') $usos[strtolower($x[3])][$tabla][basename($f)] = true;
    }
}
$mal = 0;
foreach ($usos as $n => $tablas) {
    if (count($tablas) < 2) continue;
    $mal++;
    echo "REPETIDO  $n\n";
    foreach ($tablas as $t => $fs) echo "    en la tabla $t  (" . implode(', ', array_keys($fs)) . ")\n";
}
echo $mal ? "\n$mal nombre(s) de CONSTRAINT en más de una tabla: MySQL 8 no lo admite (ERROR 1826). Renombra con fk_<tabla>_<columna>.\n"
          : "OK: " . count($usos) . " nombres de CONSTRAINT, ninguno repetido entre tablas.\n";
exit($mal ? 1 : 0);
