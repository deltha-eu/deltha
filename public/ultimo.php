<?php
/** /ultimo?k= → /mis-fuentes?k= (08/10/2026): lo ultimo de las fuentes que sigues esta ahora dentro de «Mis fuentes». */
require __DIR__ . '/../lib/app.php';
$q = [];
foreach (['k', 'p'] as $p) if (isset($_GET[$p]) && $_GET[$p] !== '') $q[] = $p . '=' . rawurlencode((string)$_GET[$p]);
header('Location: /mis-fuentes' . ($q ? '?' . implode('&', $q) : ''), true, 301);
exit;
