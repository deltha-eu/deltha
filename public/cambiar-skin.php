<?php
/** /cambiar-skin.php — cambio rapido desde el menu lateral: ?s=plantilla | ?t=auto|light|dark | ?l=es|en|fr
 *  Con cuenta se guarda en users (skin / theme / lang), como en el perfil. Sin cuenta, cookie de un año (ddd_skin /
 *  ddd_theme / ddd_lang) que solo existe porque la persona la ha pedido; el valor vacio o "auto" la borra
 *  (save_skin_pref / save_theme_pref / pref_cookie en lib/app.php; /plantilla.php usa las mismas).
 *  Vuelve a la pagina de origen. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$u = user();
if (isset($_GET['s'])) save_skin_pref((string)$_GET['s']);
if (isset($_GET['t'])) save_theme_pref((string)$_GET['t']);
if (isset($_GET['l'])) {
    $l = (string)$_GET['l']; if (!isset(DDD_LANGS[$l])) $l = '';
    if ($u) { $pdo->prepare("UPDATE users SET lang = ? WHERE user_id = ?")->execute([$l, $u['user_id']]); pref_cookie('ddd_lang', ''); }
    else pref_cookie('ddd_lang', $l);
}
$back = (string)($_SERVER['HTTP_REFERER'] ?? '');
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($back === '' || !$host || parse_url($back, PHP_URL_HOST) !== $host || str_contains($back, 'cambiar-skin.php')) $back = '/';
$back = preg_replace('/([?&])(skin|lang)=[^&]*&?/', '$1', $back); $back = rtrim($back, '?&');   // fuera los ?skin= / ?lang= de prueba
redirect($back);
