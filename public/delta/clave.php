<?php
/** /delta/clave.php — puerta de acceso mientras Deltha es privada (config.access_key) */
define('DDD_PUBLIC', true);
require __DIR__ . '/../../lib/app.php';
if (gate_open()) redirect('/');

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals(trim((string)cfg('access_key')), post('clave'))) {
        setcookie(DDD_KEY_COOKIE, gate_token(), [
            'expires'  => time() + 30 * 86400,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        redirect('/');
    }
    $err = 'Esa clave no es correcta.';
    usleep(300000);   // freno suave a la fuerza bruta
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="robots" content="noindex">
<link rel="icon" href="/img/favicon.png">
<title>Clave de acceso · <?= h(cfg('site_name')) ?></title>
<link rel="stylesheet" href="/css/ddd.css?v=<?= DDD_CSS_VER ?>">
<style>
html, body { height: 100%; }
.gate { min-height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center;
  padding: 32px 24px calc(32px + env(safe-area-inset-bottom)); text-align: center; }
.gate .logo { height: 72px; margin-bottom: 28px; }
.gate .logo.l { display: var(--logo-l); }
.gate .logo.d { display: var(--logo-d); }
.gate h1 { font-family: var(--serif); font-size: 30px; line-height: 1.2; margin: 0 0 12px; padding: 0; max-width: 520px; }
.gate p { font-size: 17px; line-height: 1.5; max-width: 520px; color: var(--muted); }
.gate form.plain { width: 100%; max-width: 360px; align-items: center; }
.gate form.plain input { width: 100%; text-align: center; letter-spacing: .08em; }
.gate form.plain button { align-self: center; padding: 12px 28px; font-size: 17px; }
.gate .error { max-width: 360px; width: 100%; }
.gate .back { font-size: 14px; margin-top: 8px; }
</style></head><body>
<div class="gate">
  <img class="logo l" src="/img/logo.png" alt=""><img class="logo d" src="/img/logo-dark.png" alt="">
  <h1>Deltha aún es privada</h1>
  <p>Si te han dado una clave de acceso, escríbela aquí. Se guarda en una única cookie técnica mientras dure el acceso privado; cuando abramos Deltha dejará de existir.</p>
  <?php if ($err) echo "<p class=\"error\">" . h($err) . "</p>"; ?>
  <form class="plain" method="post">
    <input type="password" name="clave" required autocomplete="off" autofocus placeholder="clave">
    <button>Entrar</button>
  </form>
  <p class="back"><a href="/delta/">Volver a la presentación</a> · <a href="/login.php">Ya tengo cuenta</a> · <a href="/privacidad.php">Privacidad</a></p>
</div>
</body></html>
