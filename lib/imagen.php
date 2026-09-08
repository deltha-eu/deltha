<?php
/** lib/imagen.php — foto del envio: subida del usuario o og:image; recorte a WxH y JPEG limpio (sin EXIF). */
declare(strict_types=1);

/** Devuelve nombre de fichero guardado en /media o null. */
function imagen_desde_bytes(string $bytes): ?string {
    if (strlen($bytes) < 100 || strlen($bytes) > (int)cfg('img_max_bytes', 6000000)) return null;
    $src = @imagecreatefromstring($bytes);
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
    $src = @imagecreatefromstring($bytes);
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

/** Peticion HTTP. 1) curl-impersonate como Chrome si existe; 2) curl de PHP con cascada navegador -> Twitterbot -> facebookexternalhit. */
function http_get(string $url, int $timeout, int $max = 3000000): ?string {
    if (!preg_match('#^https?://[^\s\'"]+$#i', $url)) return null;

    if ($bin = curl_impersonate_bin()) {
        $cmd = escapeshellarg($bin) . ' -s -L --max-redirs 4 --max-time ' . ((int)$timeout + 3) . ' --max-filesize ' . (int)$max
             . ' -H ' . escapeshellarg('Accept-Language: es-ES,es;q=0.9')
             . ' -w ' . escapeshellarg('\n%{http_code}') . ' ' . escapeshellarg($url) . ' 2>/dev/null';
        $out = shell_exec($cmd);
        if (is_string($out) && ($pos = strrpos($out, "\n")) !== false) {
            $code = (int)substr($out, $pos + 1);
            $body = substr($out, 0, $pos);
            if ($code > 0 && $code < 400 && $body !== '') return $body;
        }
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
        curl_close($ch);
        if ($data !== false && $code > 0 && $code < 400 && strlen($data) > 0) return $data;
        if ($code !== 403 && $code !== 429 && $code !== 503 && $code !== 0) return null; // 404 etc: no reintentar
    }
    return null;
}

/** Busca og:image / twitter:image en la pagina y la procesa. */
function imagen_desde_og(string $url): ?string {
    $t = (int)cfg('og_timeout_sec', 3);
    $html = http_get($url, $t, 400000);
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
        $p = parse_url($url); $img = $p['scheme'] . '://' . $p['host'] . $img;
    }
    $bytes = http_get($img, $t);
    return $bytes ? imagen_desde_bytes($bytes) : null;
}

/** Lee la pagina y devuelve ['title','description','image'] (image = fichero ya procesado en /media o null). */
function extraer_meta(string $url): array {
    $out = ['title' => '', 'description' => '', 'image' => null];
    $html = http_get($url, (int)cfg('og_timeout_sec', 3), 400000);
    if (!$html) return $out;
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
    $out['image'] = imagen_desde_og($url);
    return $out;
}
