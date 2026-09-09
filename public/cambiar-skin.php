<?php
/** /cambiar-skin.php?s=nombre — cambio rapido de plantilla desde el menu lateral.
 *  Con cuenta: se guarda en users.skin (lo mismo que el perfil). Sin cuenta: cookie ddd_skin de un año, que solo existe
 *  porque la persona la ha pedido; s= vacio la borra (vuelve a la del sitio). Vuelve a la pagina de origen. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$s = (string)($_GET['s'] ?? '');
if ($s !== '' && !isset(DDD_SKINS[$s])) $s = '';
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if ($u = user()) {
    $pdo->prepare("UPDATE users SET skin = ? WHERE user_id = ?")->execute([$s, $u['user_id']]);
    setcookie(DDD_SKIN_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $secure, 'samesite' => 'Lax']);
} elseif ($s === '') {
    setcookie(DDD_SKIN_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $secure, 'samesite' => 'Lax']);
} else {
    setcookie(DDD_SKIN_COOKIE, $s, ['expires' => time() + 365 * 86400, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
}
$back = (string)($_SERVER['HTTP_REFERER'] ?? '');
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($back === '' || !$host || parse_url($back, PHP_URL_HOST) !== $host || str_contains($back, 'cambiar-skin.php')) $back = '/';
$back = preg_replace('/([?&])skin=[^&]*&?/', '$1', $back); $back = rtrim($back, '?&');   // fuera el ?skin= de prueba, si venia
redirect($back);
