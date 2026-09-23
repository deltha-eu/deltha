<?php
/** lib/imagen.php — foto del envio: subida del usuario o og:image; recorte a WxH y JPEG limpio (sin EXIF).
 *  Formatos de entrada: los de GD (JPEG, PNG, GIF, WebP, BMP) y AVIF (ver imagen_decodificar()). */
declare(strict_types=1);

/** ¿Son bytes de un AVIF? Contenedor ISOBMFF: caja `ftyp` en el byte 4 y marca avif/avis entre las marcas (mayor o compatibles). */
function imagen_es_avif(string $bytes): bool {
    return strlen($bytes) > 16 && substr($bytes, 4, 4) === 'ftyp' && preg_match('/avi[fs]/', substr($bytes, 8, 40)) === 1;
}

/** Primer ejecutable existente de la lista (o de la clave de config), como curl_impersonate_bin(). */
function imagen_bin(string $cfgKey, array $cands): ?string {
    static $cache = [];
    if (!array_key_exists($cfgKey, $cache)) {
        $cache[$cfgKey] = null;
        foreach (array_merge([cfg($cfgKey, '')], $cands) as $c) {
            if ($c && is_executable($c)) { $cache[$cfgKey] = $c; break; }
        }
    }
    return $cache[$cfgKey];
}

/** Decodifica bytes de imagen a un recurso GD, o null. Lo que entiende imagecreatefromstring (JPEG, PNG, GIF, WebP, BMP…)
 *  y ademas AVIF (cada vez mas habitual en los RSS de los medios): 1) GD compilado con libavif (imagecreatefromavif, PHP 8.1+);
 *  2) si no, `avifdec` o ImageMagick (`magick`/`convert`) si estan instalados, pasando por un PNG temporal.
 *  $como, si se pasa, recibe el metodo que funciono (para el diagnostico de admin). */
function imagen_decodificar(string $bytes, ?string &$como = null): ?GdImage {
    $como = 'gd';
    $img = @imagecreatefromstring($bytes);
    if ($img) return $img;
    if (!imagen_es_avif($bytes)) { $como = null; return null; }
    $tmp = tempnam(sys_get_temp_dir(), 'ddd-avif-');
    if ($tmp === false || @file_put_contents($tmp, $bytes) === false) { $como = null; return null; }
    $png = $tmp . '.png';
    try {
        if (function_exists('imagecreatefromavif')) {
            $como = 'gd/libavif';
            if ($img = @imagecreatefromavif($tmp)) return $img;
        }
        $cmds = [];
        if ($b = imagen_bin('avifdec', ['/usr/bin/avifdec', '/usr/local/bin/avifdec'])) $cmds['avifdec'] = escapeshellarg($b) . ' ' . escapeshellarg($tmp) . ' ' . escapeshellarg($png);
        if ($b = imagen_bin('imagemagick', ['/usr/bin/magick', '/usr/local/bin/magick', '/usr/bin/convert', '/usr/local/bin/convert'])) $cmds['imagemagick'] = escapeshellarg($b) . ' ' . escapeshellarg('avif:' . $tmp) . ' ' . escapeshellarg('png:' . $png);
        foreach ($cmds as $como => $cmd) {
            @shell_exec($cmd . ' >/dev/null 2>&1');
            if (is_file($png) && filesize($png) > 0 && ($img = @imagecreatefrompng($png))) return $img;
        }
        $como = null;
        return null;
    } finally {
        @unlink($tmp); @unlink($png);
    }
}

/** Devuelve nombre de fichero guardado en /media o null. */
function imagen_desde_bytes(string $bytes): ?string {
    if (strlen($bytes) < 100 || strlen($bytes) > (int)cfg('img_max_bytes', 6000000)) return null;
    $src = imagen_decodificar($bytes);
    if (!$src) return null;
    $W = (int)cfg('img_w', 1200); $H = (int)cfg('img_h', 900);
    $sw = imagesx($src); $sh = imagesy($src);
    if ($sw < 240 || $sh < 180) { imagedestroy($src); return null; }   // demasiado pequena (icono, logo)

    // recorte centrado a proporcion W:H
    $ratio = $W / $H;
    if ($sw / $sh > $ratio) { $cw = (int)round($sh * $ratio); $ch = $sh; $cx = intdiv($sw - $cw, 2); $cy = 0; }
    else                    { $cw = $sw; $ch = (int)round($sw / $ratio); $cx = 0; $cy = intdiv($sh - $ch, 2); }

    $dst = imagecreatetruecolor($W, $H);
    imagecopyresampled($dst, $src, 0, 0, $cx, $cy, $W, $H, $cw, $ch);
    imagedestroy($src);

    $name = bin2hex(random_bytes(12)) . '.jpg';
    if (!is_dir(DDD_MEDIA_DIR)) mkdir(DDD_MEDIA_DIR, 0775, true);
    $ok = imagejpeg($dst, DDD_MEDIA_DIR . '/' . $name, 82);   // GD no escribe EXIF: la foto sale limpia
    imagedestroy($dst);
    return $ok ? $name : null;
}

/** Avatar: cuadrado 256x256 en /media/avatars */
function avatar_desde_subida(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) return null;
    $bytes = file_get_contents($file['tmp_name']);
    if ($bytes === false || strlen($bytes) > (int)cfg('img_max_bytes', 6000000)) return null;
    $src = imagen_decodificar($bytes);
    if (!$src) return null;
    $sw = imagesx($src); $sh = imagesy($src); $side = min($sw, $sh);
    if ($side < 64) { imagedestroy($src); return null; }
    $dst = imagecreatetruecolor(256, 256);
    imagecopyresampled($dst, $src, 0, 0, intdiv($sw - $side, 2), intdiv($sh - $side, 2), 256, 256, $side, $side);
    imagedestroy($src);
    $dir = DDD_MEDIA_DIR . '/avatars';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(12)) . '.jpg';
    $ok = imagejpeg($dst, "$dir/$name", 85);
    imagedestroy($dst);
    return $ok ? $name : null;
}

function imagen_desde_subida(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($file['tmp_name'])) return null;
    $bytes = file_get_contents($file['tmp_name']);
    return $bytes === false ? null : imagen_desde_bytes($bytes);
}

/** Ruta al curl-impersonate si esta instalado (huella TLS de Chrome: pasa Cloudflare/Akamai). */
function curl_impersonate_bin(): ?string {
    static $bin = false;
    if ($bin === false) {
        $bin = null;
        foreach ([cfg('curl_impersonate', ''), '/opt/curl-impersonate/curl_chrome116', '/usr/local/bin/curl_chrome116'] as $c) {
            if ($c && is_executable($c)) { $bin = $c; break; }
        }
    }
    return $bin;
}

/** Rastro de la ultima http_get(): un texto por intento («curl-impersonate: HTTP 403 en 210 ms»), para el diagnostico de admin. */
function http_last(): array { return $GLOBALS['ddd_http_last'] ?? []; }

/** Peticion HTTP. 1) curl-impersonate como Chrome si existe; 2) curl de PHP con cascada navegador -> Twitterbot -> facebookexternalhit. */
function http_get(string $url, int $timeout, int $max = 3000000): ?string {
    $GLOBALS['ddd_http_last'] = [];
    if (!preg_match('#^https?://[^\s\'"]+$#i', $url)) { $GLOBALS['ddd_http_last'][] = 'URL no valida'; return null; }
    $t0 = microtime(true);
    $traza = function (string $via, int $code, int $bytes, string $err = '') use (&$t0) {
        $GLOBALS['ddd_http_last'][] = "$via: " . ($code > 0 ? "HTTP $code" : 'sin codigo') . ", $bytes bytes en " . (int)round((microtime(true) - $t0) * 1000) . " ms" . ($err !== '' ? " ($err)" : '');
        $t0 = microtime(true);
    };

    if ($bin = curl_impersonate_bin()) {
        $cmd = escapeshellarg($bin) . ' -s -L --max-redirs 4 --max-time ' . ((int)$timeout + 3) . ' --max-filesize ' . (int)$max
             . ' -H ' . escapeshellarg('Accept-Language: es-ES,es;q=0.9')
             . ' -H ' . escapeshellarg('Accept: text/html,application/xhtml+xml,image/avif,image/webp,image/*,*/*;q=0.8')
             . ' -w ' . escapeshellarg('\n%{http_code}') . ' ' . escapeshellarg($url) . ' 2>/dev/null';
        $out = shell_exec($cmd);
        if (is_string($out) && ($pos = strrpos($out, "\n")) !== false) {
            $code = (int)substr($out, $pos + 1);
            $body = substr($out, 0, $pos);
            $traza('curl-impersonate', $code, strlen($body));
            if ($code > 0 && $code < 400 && $body !== '') return $body;
        } else $traza('curl-impersonate', 0, 0, 'sin salida');
        // si falla, seguimos con el curl normal
    }

    $agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Twitterbot/1.0',
        'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    ];
    foreach ($agents as $ua) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout + 3,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml,image/avif,image/webp,image/*,*/*;q=0.8',
                                       'Accept-Language: es-ES,es;q=0.9', 'Cache-Control: no-cache'],
            CURLOPT_ENCODING       => '',
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS=> CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_NOPROGRESS     => false,
            CURLOPT_PROGRESSFUNCTION => function ($ch, $dlTotal, $dl) use ($max) { return $dl > $max ? 1 : 0; },
        ]);
        $data = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_errno($ch) ? curl_error($ch) : '';
        curl_close($ch);
        $traza('curl ' . explode('/', $ua)[0], $code, is_string($data) ? strlen($data) : 0, $err);
        if ($data !== false && $code > 0 && $code < 400 && strlen($data) > 0) return $data;
        if ($code !== 403 && $code !== 429 && $code !== 503 && $code !== 0) return null; // 404 etc: no reintentar
    }
    return null;
}

/** Tope de bytes al leer el HTML de una noticia. Antes 400 KB fijos: las paginas grandes (medios con mucho JSON
 *  incrustado) abortaban la descarga entera ('Callback aborted') y se perdia la og:image aunque este en el <head>. */
function og_max_bytes(): int { return max(200000, (int)cfg('og_max_bytes', 2500000)); }

/** Busca og:image / twitter:image en la pagina y la procesa. $html: la pagina ya leida (extraer_meta), para no pedirla dos veces. */
function imagen_desde_og(string $url, ?string $html = null): ?string {
    $t = (int)cfg('og_timeout_sec', 3);
    if ($html === null) $html = http_get($url, $t, og_max_bytes());
    if (!$html) return null;
    $img = null;
    if (preg_match('/<meta[^>]+(?:property|name)=["\']og:image(?::secure_url|:url)?["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)
     || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']og:image["\']/i', $html, $m)
     || preg_match('/<meta[^>]+(?:name|property)=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)
     || preg_match('/"image"\s*:\s*(?:\{[^}]*"url"\s*:\s*)?"(https?:[^"]+)"/i', $html, $m)) {   // JSON-LD
        $img = html_entity_decode($m[1]);
    }
    if (!$img) return null;
    if (str_starts_with($img, '//')) $img = 'https:' . $img;
    elseif (str_starts_with($img, '/')) {
        $p = parse_url($url); $img = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '') . $img;
    }
    $bytes = http_get($img, $t);
    return $bytes ? imagen_desde_bytes($bytes) : null;
}

/** Lee la pagina y devuelve ['title','description','image'] (image = fichero ya procesado en /media o null).
 *  $conImagen = false: no descarga la og:image (el llamante ya tiene foto, p. ej. la del RSS de un medio). */
function extraer_meta(string $url, bool $conImagen = true): array {
    $out = ['title' => '', 'description' => '', 'image' => null];
    $html = http_get($url, (int)cfg('og_timeout_sec', 3), og_max_bytes());
    if (!$html) return $out;
    $raw = $html;   // para la og:image, el HTML tal cual (sin la conversion de charset)
    // charset: si no es utf-8, convertir
    if (preg_match('/charset=["\']?([\w-]+)/i', $html, $m) && strcasecmp($m[1], 'utf-8') !== 0) {
        $conv = @iconv($m[1], 'UTF-8//IGNORE', $html);
        if ($conv) $html = $conv;
    }
    $meta = function (array $names) use ($html): ?string {
        foreach ($names as $n) {
            $n = preg_quote($n, '/');
            if (preg_match('/<meta[^>]+(?:property|name)=["\']' . $n . '["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)
             || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']' . $n . '["\']/i', $html, $m)) {
                $v = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($v !== '') return $v;
            }
        }
        return null;
    };
    $title = $meta(['og:title', 'twitter:title']);
    if (!$title && preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($title) {
        // quitar sufijo de medio: "Titular - El Pais" / "Titular | eldiario.es"
        $title = preg_replace('/\s+[\-|–—·]\s+[^\-|–—·]{2,40}$/u', '', $title);
        $out['title'] = mb_substr(preg_replace('/\s+/', ' ', $title), 0, 200);
    }
    $desc = $meta(['og:description', 'twitter:description', 'description']);
    if ($desc) $out['description'] = mb_substr(preg_replace('/\s+/', ' ', $desc), 0, 2000);
    if ($conImagen) $out['image'] = imagen_desde_og($url, $raw);
    return $out;
}
