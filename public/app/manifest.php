<?php
/** /app/manifest.php — manifiesto de la PWA generado al vuelo para que lleve los iconos en vigor (Admin > Logo). */
define('DDD_PUBLIC', true);
require __DIR__ . '/../../lib/app.php';
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache');   // el vhost lo fija igual; sin esto el bloque de /app lo cachea 7 dias
$name = (string)cfg('site_name', 'Deltha');
$i192 = icon_src(192);
echo json_encode([
    'id' => '/', 'name' => $name, 'short_name' => $name,
    'description' => 'Delta de Dirac — agregador sin algoritmo oculto',
    'lang' => lang(), 'start_url' => '/?src=app', 'scope' => '/', 'display' => 'standalone', 'orientation' => 'portrait',
    'background_color' => '#ffffff', 'theme_color' => '#ffffff',
    'icons' => [
        ['src' => $i192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => icon_src(512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => icon_src('512m'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
    'shortcuts' => [
        ['name' => t('Substacks'),  'url' => '/?portada=substacks&src=app', 'icons' => [['src' => $i192, 'sizes' => '192x192']]],
        ['name' => t('Radar'),      'url' => '/radar.php?src=app',      'icons' => [['src' => $i192, 'sizes' => '192x192']]],
        ['name' => t('Enviar'),     'url' => '/enviar.php?src=app',     'icons' => [['src' => $i192, 'sizes' => '192x192']]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
