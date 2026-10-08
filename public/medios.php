<?php
/** /medios → /fuentes (08/10/2026): el catalogo se llama ahora «Todas las fuentes». Se conservan ?k= e ?il=. */
require __DIR__ . '/../lib/app.php';
$q = [];
foreach (['k', 'il'] as $p) if (isset($_GET[$p]) && $_GET[$p] !== '') $q[] = $p . '=' . rawurlencode((string)$_GET[$p]);
if (!isset($_GET['k'])) $q[] = 'todas=1';   // /medios a secas enseñaba los grupos seguidos
header('Location: /fuentes' . ($q ? '?' . implode('&', $q) : ''), true, 301);
exit;
