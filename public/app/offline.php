<?php
/** /app/offline.php — la pagina «Sin conexión» de la PWA, generada al vuelo para que lleve el logo y el icono en vigor (Admin > Logo),
 *  no los de serie de /img. El service worker (sw.js) la guarda en cache al instalarse, junto con lo que lista /app/precache.php,
 *  y la sirve cuando una navegacion falla por falta de red. Antes era /app/offline.html, estatica, con /img/logo.png fijo (09/11/2026). */
define('DDD_PUBLIC', true);
require __DIR__ . '/../../lib/app.php';
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
$name = h((string)cfg('site_name', 'Deltha'));
?><!doctype html>
<html lang="<?= h(lang()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<title><?= h(t('Sin conexión')) ?> · <?= $name ?></title>
<link rel="icon" href="<?= h(icon_src(64)) ?>">
<link rel="stylesheet" href="/css/ddd.css?v=<?= DDD_CSS_VER ?>">
<style>
  .off { min-height: 70vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 24px; gap: 12px; }
  .off img { height: 40px; }
  .off .l { display: var(--logo-l); } .off .d { display: var(--logo-d); }
  .off h1 { padding: 0; }
  .off p { color: var(--muted); margin: 0; max-width: 32ch; }
  .off a { border: 1px solid var(--line2); border-radius: 999px; padding: 8px 16px; margin-top: 8px; }
</style>
</head>
<body>
<div class="shell">
  <main class="off">
    <img src="<?= h(logo_src('light')) ?>" alt="" class="l"><img src="<?= h(logo_src('dark')) ?>" alt="" class="d">
    <h1><?= h(t('Sin conexión')) ?></h1>
    <p><?= h(t('%s no guarda nada en tu móvil: las noticias, los votos y los comentarios viven en el servidor. Cuando vuelva la red, sigue por donde ibas.', (string)cfg('site_name', 'Deltha'))) ?></p>
    <a href="/"><?= h(t('Reintentar')) ?></a>
  </main>
</div>
</body>
</html>
