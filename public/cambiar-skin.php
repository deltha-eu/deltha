<?php
/** /cambiar-skin — cambio rapido desde el menu lateral: ?s=plantilla | ?t=auto|light|dark | ?l=es|en|fr | ?lset=1&ml[]=es|en (idiomas de fuentes y envios) | ?m=0|1 (casilla antigua: solo el idioma de la interfaz) | ?z=1|0|vacio (sin distracciones: siempre | nunca | solo en Originales)
 *  Con cuenta se guarda en users (skin / theme / lang), como en el perfil. Sin cuenta, cookie de un año (ddd_skin /
 *  ddd_theme / ddd_lang) que solo existe porque la persona la ha pedido; el valor vacio o "auto" la borra
 *  (save_skin_pref / save_theme_pref / pref_cookie en lib/app.php; /plantilla usa las mismas).
 *  Vuelve a la pagina de origen. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$u = user();
if (isset($_GET['s'])) save_skin_pref((string)$_GET['s']);
if (isset($_GET['t'])) save_theme_pref((string)$_GET['t']);
if (isset($_GET['lset'])) {   // idiomas de fuentes y envios: la misma preferencia que el perfil (vacio = todos)
    require_once __DIR__ . '/../lib/fuentes.php';
    fuentes_guardar_idiomas($pdo, $u ? (int)$u['user_id'] : null, fuentes_langs_field('ml', $_GET));
}
if (isset($_GET['m'])) {   // compatibilidad: la casilla antigua (1 = solo el idioma de la interfaz)
    require_once __DIR__ . '/../lib/fuentes.php';
    fuentes_guardar_idiomas($pdo, $u ? (int)$u['user_id'] : null, [], (string)$_GET['m'] === '1');
}
if (isset($_GET['k'])) pref_cookie('ddd_hacker', (string)$_GET['k'] === '1' ? '1' : '');   // modo hacker (20/10/2026): 1 = activo; otra cosa = fuera
if (isset($_GET['z'])) pref_cookie('ddd_foco', in_array((string)$_GET['z'], ['0', '1'], true) ? (string)$_GET['z'] : '');   // modo «sin distracciones» del escritorio (1 = siempre, 0 = nunca, vacio = solo en Originales): del navegador, con o sin cuenta (foco() en lib/app.php)
if (isset($_GET['l'])) {
    $l = (string)$_GET['l']; if (!isset(DDD_LANGS[$l])) $l = '';
    if ($u) { $pdo->prepare("UPDATE users SET lang = ? WHERE user_id = ?")->execute([$l, $u['user_id']]); pref_cookie('ddd_lang', ''); }
    else pref_cookie('ddd_lang', $l);
}
$back = (string)($_SERVER['HTTP_REFERER'] ?? '');
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($back === '' || !$host || parse_url($back, PHP_URL_HOST) !== $host || str_contains($back, 'cambiar-skin.php')) $back = '/';
$back = preg_replace('/([?&])(skin|lang)=[^&]*&?/', '$1', $back); $back = rtrim($back, '?&');   // fuera los ?skin= / ?lang= de prueba
header('Cache-Control: no-store');   // ni el navegador ni Cloudflare deben guardar esta redireccion
redirect($back);
