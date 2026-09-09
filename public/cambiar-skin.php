<?php
/** /cambiar-skin.php — cambio rapido desde el menu lateral: ?s=plantilla | ?t=auto|light|dark | ?l=es|en|fr
 *  Con cuenta se guarda en users (skin / theme / lang), como en el perfil. Sin cuenta, cookie de un año (ddd_skin /
 *  ddd_theme / ddd_lang) que solo existe porque la persona la ha pedido; el valor vacio o "auto" la borra.
 *  Vuelve a la pagina de origen. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$u = user();
$ck = function (string $name, string $v) use ($secure): void {
    if ($v === '') setcookie($name, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $secure, 'samesite' => 'Lax']);
    else setcookie($name, $v, ['expires' => time() + 365 * 86400, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
};
if (isset($_GET['s'])) {
    $s = (string)$_GET['s']; if (!isset(DDD_SKINS[$s])) $s = '';
    if ($u) { $pdo->prepare("UPDATE users SET skin = ? WHERE user_id = ?")->execute([$s, $u['user_id']]); $ck(DDD_SKIN_COOKIE, ''); }
    else $ck(DDD_SKIN_COOKIE, $s);
}
if (isset($_GET['t'])) {
    $t = in_array($_GET['t'], ['light', 'dark'], true) ? (string)$_GET['t'] : 'auto';
    if ($u) { $pdo->prepare("UPDATE users SET theme = ? WHERE user_id = ?")->execute([$t, $u['user_id']]); $ck('ddd_theme', ''); }
    else $ck('ddd_theme', $t === 'auto' ? '' : $t);
}
if (isset($_GET['l'])) {
    $l = (string)$_GET['l']; if (!isset(DDD_LANGS[$l])) $l = '';
    if ($u) { $pdo->prepare("UPDATE users SET lang = ? WHERE user_id = ?")->execute([$l, $u['user_id']]); $ck('ddd_lang', ''); }
    else $ck('ddd_lang', $l);
}
$back = (string)($_SERVER['HTTP_REFERER'] ?? '');
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($back === '' || !$host || parse_url($back, PHP_URL_HOST) !== $host || str_contains($back, 'cambiar-skin.php')) $back = '/';
$back = preg_replace('/([?&])(skin|lang)=[^&]*&?/', '$1', $back); $back = rtrim($back, '?&');   // fuera los ?skin= / ?lang= de prueba
redirect($back);
