<?php
/** lib/emo.php — iconos propios en comentarios y chat (26/09/2026).
 *
 *  Se escriben con un codigo entre dos puntos, :troll: :grin: :delta:, y se pintan como SVG en linea (currentColor: siguen el
 *  color del texto y de cada plantilla). Cada icono es un fichero public/img/emo/<codigo>.svg (viewBox 0 0 24 24, trazo
 *  currentColor): para añadir uno basta con dejar ahi el SVG, sin tocar codigo ni BD. Solo se sustituyen los codigos que
 *  existen; «12:30:45» o «:foo:» se quedan como estan. La paleta (emo_picker) sale junto al cuadro de escribir: con JS inserta
 *  el codigo donde esta el cursor; sin JS enseña los codigos para escribirlos a mano. Sin terceros, sin cookies. */
declare(strict_types=1);

const DDD_EMO_DIR = DDD_ROOT . '/public/img/emo';
const DDD_EMO_RE  = '~(?<![\w/:]):([a-z0-9_]{2,24}):(?![\w:])~';

/** codigo => ruta del fichero, ordenados por nombre (la paleta sigue este orden). */
function emo_list(): array {
    static $l = null;
    if ($l !== null) return $l;
    $l = [];
    foreach (glob(DDD_EMO_DIR . '/*.svg') ?: [] as $f) {
        $k = basename($f, '.svg');
        if (preg_match('/^[a-z0-9_]{2,24}$/', $k)) $l[$k] = $f;
    }
    ksort($l);
    return $l;
}
/** SVG en linea de un icono (con class, role y titulo); '' si no existe. Se lee una vez por peticion. */
function emo_svg(string $code, string $cls = 'emo'): string {
    static $cache = [];
    $l = emo_list();
    if (!isset($l[$code])) return '';
    if (!isset($cache[$code])) {
        $svg = (string)@file_get_contents($l[$code]);
        $svg = preg_replace('~<\?xml[^>]*>|<!--.*?-->~s', '', $svg);
        $cache[$code] = trim($svg);
    }
    if ($cache[$code] === '') return '';
    return preg_replace('~^<svg\b~', "<svg class=\"$cls\" role=\"img\" aria-label=\":$code:\"", $cache[$code], 1) ?? '';
}
/** Sustituye :codigo: por su icono en HTML ya escapado (antes de nl2br). */
function emo_format(string $html): string {
    if (!str_contains($html, ':') || !emo_list()) return $html;
    return preg_replace_callback(DDD_EMO_RE, function ($m) {
        $svg = emo_svg($m[1]);
        return $svg !== '' ? "<span class=\"emoi\" title=\":{$m[1]}:\">$svg</span>" : $m[0];
    }, $html) ?? $html;
}
/** Paleta: un <details> con todos los iconos; cada boton lleva data-emo con el codigo (lo inserta app/escribir.js). */
function emo_picker(): string {
    $l = emo_list();
    if (!$l) return '';
    $h = "<details class=\"emo-pick\"><summary title=\"" . h(t('Iconos')) . "\" aria-label=\"" . h(t('Iconos')) . "\">" . emo_svg('grin', 'emo') . "</summary><div class=\"emo-grid\">";
    foreach ($l as $k => $f) $h .= "<button type=\"button\" data-emo=\":$k:\" title=\":$k:\">" . emo_svg($k) . "<small>:$k:</small></button>";
    return $h . "</div></details>";
}
