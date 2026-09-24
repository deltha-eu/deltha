<?php
/**
 * lib/i18n.php — idioma de la interfaz: es (fuente), ca, eu, gl, en, fr, de, it, pt.
 *
 * Las cadenas del codigo estan en español y son la clave: t('Portada') devuelve la
 * traduccion del idioma activo o la propia clave si no la hay. Con argumentos usa
 * vsprintf: t('hace %d min', $n). Los diccionarios estan en lib/lang/<idioma>.php.
 *
 * Orden de preferencia del idioma: ?lang= en la URL (probar sin guardar) >
 * users.lang > cookie ddd_lang (solo anonimos que eligieron idioma en el menu) > cabecera Accept-Language del navegador > es.
 * Se traduce la interfaz publica; admin y moderacion siguen en español. Las paginas editables
 * (que-es, privacidad, normas, algoritmo) tienen una fila por idioma en pages (slug «normas.en»…,
 * migra-050) y page_get() cae en la española si falta la traduccion.
 */
declare(strict_types=1);

const DDD_LANGS = ['es' => 'Español', 'ca' => 'Català', 'eu' => 'Euskara', 'gl' => 'Galego', 'en' => 'English', 'fr' => 'Français', 'de' => 'Deutsch', 'it' => 'Italiano', 'pt' => 'Português'];
$I18N = [];

function lang(): string {
    static $l = null;
    if ($l !== null) return $l;
    $c = [(string)($_GET['lang'] ?? ''), (string)(user()['lang'] ?? ''), user() ? '' : (string)($_COOKIE['ddd_lang'] ?? '')];
    foreach (explode(',', (string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')) as $part) $c[] = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
    foreach ($c as $x) if (isset(DDD_LANGS[$x])) return $l = $x;
    return $l = 'es';
}
function i18n_load(): void {
    global $I18N;
    $l = lang();
    $f = __DIR__ . "/lang/$l.php";
    $I18N = ($l !== 'es' && is_file($f)) ? (require $f) : [];
}
function t(string $k, ...$a): string {
    global $I18N;
    $s = $I18N[$k] ?? $k;
    return $a ? vsprintf($s, $a) : $s;
}
