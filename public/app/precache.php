<?php
/** /app/precache.php — lista (JSON) de lo que el service worker guarda al instalarse: la pagina sin conexion, el CSS y el logo e iconos
 *  en vigor (Admin > Logo), que viven en /media/logo y cambian de nombre al subir otros; por eso no pueden ir fijos en sw.js (09/11/2026). */
define('DDD_PUBLIC', true);
require __DIR__ . '/../../lib/app.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
echo json_encode(array_values(array_unique([
    '/app/offline.php', '/css/ddd.css?v=' . DDD_CSS_VER,
    logo_src('light'), logo_src('dark'),
    icon_src(64), icon_src(180), icon_src(192), icon_src(512),
])), JSON_UNESCAPED_SLASHES);
