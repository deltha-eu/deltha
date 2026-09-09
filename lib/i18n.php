<?php
/**
 * lib/i18n.php — idioma de la interfaz: es (fuente), en, fr.
 *
 * Las cadenas del codigo estan en español y son la clave: t('Portada') devuelve la
 * traduccion del idioma activo o la propia clave si no la hay. Con argumentos usa
 * vsprintf: t('hace %d min', $n). Los diccionarios estan en lib/lang/<idioma>.php.
 *
 * Orden de preferencia del idioma: ?lang= en la URL (probar sin guardar) >
 * users.lang > cookie ddd_lang (solo anonimos que eligieron idioma en el menu) > cabecera Accept-Language del navegador > es.
 * Solo se traduce la interfaz publica; admin, moderacion y las paginas editables
 * (privacidad, normas, algoritmo) siguen en español.
 */
declare(strict_types=1);

const DDD_LANGS = ['es' => 'Español', 'en' => 'English', 'fr' => 'Français'];
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
