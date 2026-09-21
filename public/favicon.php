<?php
/** /favicon.ico -> este fichero (AliasMatch en apache.conf): sirve el icono del sitio en vigor (el subido en admin -> Logo o el de
 *  serie) como PNG de 64 px. Windows, los lectores de feeds y algunos clientes piden /favicon.ico a pelo, sin mirar el <link rel="icon">,
 *  y si no, se quedan con el logo viejo que tuvieran en cache. Cache corta para que un cambio de icono se note pronto. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$f = __DIR__ . icon_src(64);
if (!is_file($f)) $f = __DIR__ . '/img/favicon.png';
header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
header('Content-Length: ' . filesize($f));
readfile($f);
