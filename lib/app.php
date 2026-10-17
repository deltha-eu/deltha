<?php
/**
 * lib/app.php — bootstrap comun de todas las paginas.
 *
 * Reglas:
 *  - Ningun anonimo recibe cookie: solo se arranca sesion si ya existe
 *    la cookie (usuario logueado) o en el propio login.
 *  - Sin JS obligatorio, sin recursos de terceros.
 */
declare(strict_types=1);
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Madrid');

define('DDD_ROOT', dirname(__DIR__));
define('DDD_MEDIA_DIR', DDD_ROOT . '/public/media');
/** Motivos del cuestionario de baja (/perfil -> /baja, migra-127): clave guardada en bajas.motivo => texto (se traduce con t()). */
const DDD_BAJA_MOTIVOS = ['' => 'Prefiero no contestar', 'entiendo' => 'No entiendo la plataforma', 'gusta' => 'No me gusta la plataforma',
                          'usuarios' => 'He tenido una mala experiencia con otros usuarios', 'admin' => 'He tenido una mala experiencia con la administración', 'otra' => 'Otra (cuéntanos abajo)'];
define('DDD_SESSION', 'ddd_s');
define('DDD_CSS_VER', 212);   // subir al cambiar ddd.css

/* ---- credenciales fuera del repo ---------------------------------- */
$ini = @parse_ini_file('/etc/deltha/config.ini', true);
if ($ini === false) {
    $ini = @parse_ini_file(DDD_ROOT . '/config.ini', true);   // fallback dev
}
if ($ini === false || !isset($ini['db'])) {
    http_response_code(500);
    exit('Falta /etc/deltha/config.ini');
}
// Zona horaria unica para web y CLI (cron): si difieren, NOW() de MySQL
// se desplaza y el ciclo no ve cumplidos los 30 min de las nuevas.
date_default_timezone_set($ini['app']['timezone'] ?? 'Europe/Madrid');
$pdo = new PDO($ini['db']['dsn'], $ini['db']['user'], $ini['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec("SET time_zone = '" . date('P') . "'");   // NOW() de MySQL alineado con la hora de PHP
$DDD_SECRET = $ini['app']['secret'] ?? 'cambia-este-secreto';

/* ---- config de BD -------------------------------------------------- */
$CFG = [];
foreach ($pdo->query("SELECT ckey, cvalue FROM config") as $r) {
    $CFG[$r['ckey']] = $r['cvalue'];
}
function cfg(string $k, $default = null) { global $CFG; return $CFG[$k] ?? $default; }
/** Logo del sitio: el subido en Admin > Logo (/media/logo) o el de serie. El oscuro cae al claro personalizado si no hay uno propio. */
function logo_src(string $mode): string {
    $f = cfg($mode === 'dark' ? 'logo_dark' : 'logo_light');
    if (!$f && $mode === 'dark') $f = cfg('logo_light');
    return $f ? '/media/logo/' . rawurlencode($f) : ($mode === 'dark' ? '/img/logo-dark.png' : '/img/logo.png');
}
/** Icono del sitio (favicon, pantalla de inicio, PWA): el subido en Admin > Logo (config logo_icon = base del nombre en /media/logo,
 *  con variantes -64/-180/-192/-512/-512m) o los de serie. $size: 64 | 180 | 192 | 512 | '512m' (maskable). */
function icon_src($size): string {
    $b = (string)cfg('logo_icon');
    if ($b) return '/media/logo/' . rawurlencode($b) . "-$size.png";
    return ['64' => '/img/favicon.png', '180' => '/img/icon-180.png', '192' => '/app/icon-192.png', '512' => '/app/icon-512.png', '512m' => '/app/icon-512-maskable.png'][(string)$size] ?? '/img/favicon.png';
}

/* ---- sesion solo si ya hay cookie (nunca para anonimos) ------------
 * De serie PHP cierra la sesion sola: la cookie muere al cerrar el navegador (session.cookie_lifetime = 0) y el
 * servidor borra el fichero de sesion a los 24 min de inactividad (session.gc_maxlifetime = 1440). Por eso «se
 * cerraban las sesiones de vez en cuando». Aqui dura config.session_days (30) y el plazo cuenta desde la ultima
 * visita, no desde que se entro. En el droplet hace falta ademas un .ini en conf.d de php-fpm: el limpiador de
 * sesiones de Debian/Ubuntu lee gc_maxlifetime de php.ini + conf.d, no de aqui ni del pool (ver README, «Sesiones»). */
/** Dias que dura la sesion iniciada. */
function session_dias(): int { return max(1, (int)cfg('session_days', 30)); }
/** ¿La peticion llega por HTTPS? (con Cloudflare delante, Apache puede verla como HTTP: entonces la cabecera lo dice). */
function es_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}
/** Arranca la sesion con la duracion de session_days (la usan app.php y login.php: los mismos parametros en los dos sitios). */
function session_arranca(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $ttl = session_dias() * 86400;
    ini_set('session.gc_maxlifetime', (string)$ttl);
    session_start(['cookie_lifetime' => $ttl, 'cookie_httponly' => true, 'cookie_secure' => es_https(), 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
}
/** Vuelve a mandar la cookie en cada visita: asi el plazo corre desde la ultima vez y no desde que se entro. */
function session_refresca(): void {
    if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) return;
    setcookie(session_name(), session_id(), ['expires' => time() + session_dias() * 86400, 'path' => '/', 'httponly' => true, 'secure' => es_https(), 'samesite' => 'Lax']);
}
$USER = null;
session_name(DDD_SESSION);
if (isset($_COOKIE[DDD_SESSION])) {
    session_arranca();
    if (!empty($_SESSION['uid'])) {
        $st = $pdo->prepare("SELECT user_id, login, level, aura, avatar, bio, theme, skin, landing, lang, src_mylang, skin_propio FROM users WHERE user_id = ? AND verified = 1");
        $st->execute([$_SESSION['uid']]);
        $USER = $st->fetch() ?: null;
        if ($USER && PHP_SAPI !== 'cli') {   // ultima visita, como mucho una vez por minuto (para /estadisticas)
            session_refresca();   // ventana deslizante: mientras entre, no se le cierra la sesion
            $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ? AND (last_seen IS NULL OR last_seen < NOW() - INTERVAL 1 MINUTE)")
                ->execute([$USER['user_id']]);
        }
    }
}
require_once __DIR__ . '/contadores.php';   // lecturas y clics de cada envio (migra-120)
require_once __DIR__ . '/cache.php';     // cache Redis opcional: si Redis no esta, todo sigue contra MySQL
require_once __DIR__ . '/i18n.php';
i18n_load();
require_once __DIR__ . '/bloqueos.php';   // medios bloqueados (source_blocked)
require_once __DIR__ . '/avisos.php';     // campanita: menciones, respuestas y privados
require_once __DIR__ . '/ghost.php';      // «Ghostear» a un usuario: velo simetrico, comentarios plegados (user_ghosts)
require_once __DIR__ . '/destacar.php';   // «Destacar»: envios y noticias RSS los primeros de su lista, con halo (migra-111)
require_once __DIR__ . '/analitica.php';  // analitica propia sin cookies (hits, ana_daily)

/* ---- presencia anonima: huella no reversible (ip+navegador+dia, HMAC truncado)
 * guardada unos minutos, solo para contar visitantes en /estadisticas.
 * No se guarda la IP; el dia dentro de la huella impide seguir a nadie de un dia a otro. */
if ($USER === null && PHP_SAPI !== 'cli' && !preg_match('~bot|crawl|spider|slurp|fetch|monitor~i', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
    $fp = substr(hash_hmac('sha256', client_ip() . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . date('Y-m-d'), $DDD_SECRET), 0, 16);
    try {
        $pdo->prepare("INSERT INTO presence (fp, seen) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE seen = NOW()")->execute([$fp]);
        if (random_int(1, 20) === 1) $pdo->exec("DELETE FROM presence WHERE seen < NOW() - INTERVAL 10 MINUTE");
    } catch (Throwable $e) { /* tabla aun sin crear: no romper la pagina */ }
}
function user(): ?array { global $USER; return $USER; }
function is_admin(): bool { $u = user(); return $u && $u['level'] === 'admin'; }
/** ¿Modera esta cuenta? Por nivel (mod, admin: lo pone el admin) o por aura: desde config.mod_aura (20; 0 = solo por nivel)
 *  cualquier registrado modera mientras la mantenga (el aura baja cada dia, asi que se pierde solo si deja de participar). */
function user_is_mod(?array $u): bool {
    if (!$u) return false;
    if (in_array($u['level'] ?? 'normal', ['mod', 'admin'], true)) return true;
    $min = (float)cfg('mod_aura', 20);
    return $min > 0 && (float)($u['aura'] ?? 0) >= $min;
}
function is_mod(): bool { return user_is_mod(user()); }
/** De donde le viene la moderacion: 'admin' | 'mod' (nivel) | 'aura' | '' (no modera). Para las fichas y admin -> Usuarios. */
function mod_origen(?array $u): string {
    if (!$u) return '';
    if ($u['level'] === 'admin') return 'admin';
    if ($u['level'] === 'mod') return 'mod';
    return user_is_mod($u) ? 'aura' : '';
}

/* ---- acceso privado temporal (clave) --------------------------------
 * Mientras config.access_key no este vacia, la web es privada: quien no
 * tenga la clave va a la puerta (/delta/clave.php) en vez de a la portada.
 * Pasan: usuarios logueados y quien haya introducido la clave en
 * /delta/clave.php (cookie tecnica ddd_k con un HMAC de la clave; cambiar
 * la clave en admin invalida todas las cookies). Vaciar access_key abre
 * la web y la cookie deja de tener efecto.
 * Las paginas exentas definen DDD_PUBLIC antes de incluir este fichero.
 * ------------------------------------------------------------------ */
define('DDD_KEY_COOKIE', 'ddd_k');
function gate_active(): bool { return trim((string)cfg('access_key', '')) !== ''; }
function gate_token(): string { global $DDD_SECRET; return hash_hmac('sha256', 'clave|' . trim((string)cfg('access_key', '')), $DDD_SECRET); }
function gate_open(): bool {
    if (!gate_active() || user()) return true;
    return isset($_COOKIE[DDD_KEY_COOKIE]) && hash_equals(gate_token(), (string)$_COOKIE[DDD_KEY_COOKIE]);
}
// En CLI (cron: promocionar.php, moderar.php) no hay cookie ni sesion: sin esta
// excepcion, con la clave activa redirect() hacia exit y el ciclo moria en silencio
// (nada salia del radar a las portadas, ni revelado, ni archivo, ni decaimiento de aura).
if (PHP_SAPI !== 'cli' && !defined('DDD_PUBLIC') && !gate_open()) redirect('/delta/clave.php');   // /que-es, privacidad, normas y algoritmo son publicas (DDD_PUBLIC)

/* ---- helpers ------------------------------------------------------- */
/** Casilla «mostrar contraseña» para un formulario (la activa /app/pw.js; sin JS no aparece). */
function pw_toggle(): string {
    return "<span class=\"showpw\" hidden><label><input type=\"checkbox\"> mostrar contraseña</label></span><script src=\"/app/pw.js\" defer></script>";
}
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
/** Apunta un intento de registro en el historico permanente (tabla reg_log, migra-102; lo ve el admin en Procesos -> Registros).
 *  $estado: pendiente | activada | rechazado. Nunca rompe el registro: si la tabla no existe todavia, no hace nada. */
function reg_log_add(PDO $pdo, string $login, string $email, string $estado, string $motivo = '', int $uid = 0): void {
    try {
        $pdo->prepare("INSERT INTO reg_log (login, email, user_id, estado, motivo, activated) VALUES (?,?,?,?,?,?)")
            ->execute([mb_substr($login, 0, 64), mb_substr($email, 0, 128), $uid ?: null, $estado, mb_substr($motivo, 0, 160), $estado === 'activada' ? date('Y-m-d H:i:s') : null]);
    } catch (Throwable $e) { /* sin migra-102 */ }
}
/** Saltos de linea de un texto ya escapado, para pintarlo: como mucho una linea en blanco seguida (tres o mas saltos se quedan en dos) y
 *  cada salto pasa a ser un <br> SIN el salto literal detras. nl2br() dejaba «<br />\n» y, como los comentarios y el chat se pintan con
 *  white-space: pre-wrap, cada salto contaba dos veces (un parrafo aparte salia con tres lineas en blanco). */
function saltos(string $html): string {
    $html = preg_replace('/\n{3,}/', "\n\n", trim(str_replace(["\r\n", "\r"], "\n", $html)));
    return str_replace("\n", '<br>', $html);
}
/** Titular o entradilla en texto plano: fuera etiquetas HTML (incluidas las que llegan escapadas, como «&lt;span lang="gl"&gt;…»),
 *  entidades decodificadas y espacios normalizados. Para lo que viene de los feeds, de la og:title y de los formularios (08/10/2026). */
function texto_plano(?string $s): string {
    $t = html_entity_decode((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');   // primero: «&lt;span&gt;» pasa a ser una etiqueta de verdad
    $t = strip_tags(preg_replace('~<br\s*/?>|</p>~i', ' ', $t));
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');           // segundo: lo doblemente escapado (&amp;amp;)
    return trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $t));
}
/** URLs sueltas del texto (ya escapado con h()) → enlaces (nofollow, recortados a 60 caracteres). Lo usan los comentarios y el Chat.
 *  No toca lo que ya es un atributo o va dentro de una etiqueta (lookbehind de comillas y >); la puntuacion final se queda fuera del enlace. */
function enlaces(string $html): string {
    if (!str_contains($html, '://')) return $html;
    return preg_replace_callback('~(?<![">=/])\bhttps?://[^\s<]+~', function ($m) {
        $url = rtrim(preg_split('~&(?:quot|lt|gt|#0?39);~', $m[0])[0], '.,;:)');   // una comilla o < > escritos por el usuario (ya escapados) cierran la URL
        $tail = substr($m[0], strlen($url));
        return "<a href=\"$url\" rel=\"noopener nofollow\">" . h(mb_strimwidth(html_entity_decode($url, ENT_QUOTES), 0, 60, '…')) . "</a>$tail";
    }, $html);
}
function redirect(string $to): never { header("Location: $to"); exit; }
/** Registro de acciones de moderadores y admin (migra-134, 09/10/2026): tabla mod_log, se ve en admin -> Registro.
 *  Se llama al entrar en el POST de cada pagina de moderacion/administracion; apunta al terminar el script (tambien si hubo redirect),
 *  asi que recoge la accion (pagina:act), los campos enviados (sin contraseñas, recortados) y, si se le pasa $out, el resultado. */
function mod_log_post(PDO $pdo, string $page, &$out = null, string $extra = ''): void {   // $out: array de mensajes, texto de error o nada (por referencia: se lee al final)
    $u = user();
    if (!$u || !user_is_mod($u) || $_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $campos = [];
    foreach ($_POST as $k => $v) {
        if (in_array($k, ['act', 't', 'js'], true) || preg_match('/pass|clave|pw|token/i', (string)$k)) continue;
        if (is_array($v)) $v = implode(',', array_map('strval', $v));
        $v = trim(preg_replace('/\s+/', ' ', (string)$v));
        if ($v === '') continue;
        $campos[] = $k . '=' . mb_strimwidth($v, 0, 120, '…');
    }
    if (!empty($_FILES)) foreach ($_FILES as $k => $f) if (!empty($f['name'])) $campos[] = "$k=[archivo " . (string)$f["name"] . "]";
    $accion = $page . (($_POST['act'] ?? '') !== '' ? ':' . (string)$_POST['act'] : '');
    $detalle = mb_strimwidth(implode(' · ', $campos), 0, 700, '…');
    $uid = (int)$u['user_id']; $ip = client_ip();
    register_shutdown_function(function () use ($pdo, $uid, $accion, $detalle, $extra, &$out, $ip) {
        $res = $out ? mb_strimwidth(implode(' | ', array_map('strval', (array)$out)), 0, 500, '…') : null;
        if ($extra !== '') $res = trim($extra . ($res !== null ? " | $res" : ''));
        try { $pdo->prepare("INSERT INTO mod_log (user_id, action, detail, result, ip) VALUES (?,?,?,?,?)")->execute([$uid, mb_substr($accion, 0, 60), $detalle ?: null, $res, $ip]); } catch (Throwable $e) {}
    });
}
// Los navegadores envian los textarea con CRLF: se normaliza a LF, que es lo que esperan los parrafos (\n\n) al pintar.
function post(string $k, string $d = ''): string { return trim(str_replace(["\r\n", "\r"], "\n", (string)($_POST[$k] ?? $d))); }
function client_ip(): string { return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

function hace(string $dt): string {
    $s = time() - strtotime($dt);
    if ($s < 60)    return t('ahora');
    if ($s < 3600)  return t('hace %d min', intdiv($s, 60));
    if ($s < 86400) return t('hace %d h', intdiv($s, 3600));
    return t('hace %d d', intdiv($s, 86400));
}
function queda(string $published): string {
    $ttl = (int)cfg('thread_ttl_hours', 48) * 3600;
    $s = strtotime($published) + $ttl - time();
    if ($s <= 0) return t('archivado');
    return $s < 3600 ? t('se archiva en %d min', intdiv($s, 60)) : t('se archiva en %d h', intdiv($s, 3600));   // antes «muere en» (cambiado el 07/10/2026 a peticion de Angel)
}
function vida_pct(string $published): int {
    $ttl = (int)cfg('thread_ttl_hours', 48) * 3600;
    $used = time() - strtotime($published);
    return max(0, min(100, 100 - (int)round($used * 100 / $ttl)));
}
/** Quita de una URL los codigos de seguimiento (pedido por Angel, 08/10/2026): todos los utm_*, los identificadores de clic de las
 *  redes y plataformas de anuncios (fbclid, gclid, msclkid…), los de boletines (mc_cid, _hsenc, mkt_tok…), el «si» de YouTube y
 *  Spotify, y el «r» de quien comparte en Substack. Lo demas queda tal cual, en su orden y sin recodificar (hay medios cuyo
 *  enlace depende de ?id= o ?p=). Se aplica al enviar, al editar, al leer los feeds, en los envios de los agentes y en /ir.php. */
const DDD_URL_TRACK = ['fbclid', 'gclid', 'gclsrc', 'dclid', 'gbraid', 'wbraid', 'gad_source', 'gad_campaignid', 'msclkid', 'yclid', 'twclid', 'ttclid', 'li_fat_id', 'igshid', 'igsh',
    'mc_cid', 'mc_eid', '_hsenc', '_hsmi', '__hssc', '__hstc', '__hsfp', 'hsctatracking', 'mkt_tok', 'vero_id', 'vero_conv', 'oly_enc_id', 'oly_anon_id', 's_cid', '_ga', '_gl',
    'ref_src', 'ref_url', 'srsltid', 'xtor', 'smid', 'smtyp', 'cmpid', 'ocid', 'triedredirect', 'at_medium', 'at_campaign', 'at_custom1', 'at_custom2', 'at_custom3', 'at_custom4', 'pk_campaign', 'pk_kwd', 'pk_source', 'pk_medium',
    'mtm_campaign', 'mtm_source', 'mtm_medium', 'mtm_kwd', 'mtm_content', 'mtm_cid', 'mtm_group', 'mtm_placement'];
/** Apunta una salida a un medio (13/10/2026): total por dia (clicks_daily, migra-144) y por dia y dominio (clicks_source, migra-147),
 *  para «Salidas a los medios» de /estadisticas y su top de medios. La llaman /ir (envios) e /ir-fuente (fichas). Nunca rompe. */
function salida_cuenta(PDO $pdo, ?string $dominio): void {
    try { $pdo->exec("INSERT INTO clicks_daily (day, n) VALUES (CURDATE(), 1) ON DUPLICATE KEY UPDATE n = n + 1"); } catch (Throwable $e) {}
    $dominio = strtolower(trim((string)$dominio));
    if ($dominio === '') return;
    try { $pdo->prepare("INSERT INTO clicks_source (day, source, n) VALUES (CURDATE(), ?, 1) ON DUPLICATE KEY UPDATE n = n + 1")->execute([mb_substr($dominio, 0, 100)]); } catch (Throwable $e) {}
}
/** URL de salida a un medio con nuestro utm (13/10/2026): utm_source=deltha&utm_medium=referral, detras de la query y antes del
 *  #fragmento, para que el medio vea en su analitica de donde le llega el trafico aunque se pierda el referer (apps, lectores).
 *  Se parte de la URL limpia (url_limpia) para no acumular utm ajenos. La usan /ir (envios) y fuente_post() (medios y substacks). */
function url_con_utm(?string $url): string {
    $url = url_limpia($url);
    if (!preg_match('~^https?://~i', $url)) return $url;
    $frag = ''; if (($p = strpos($url, '#')) !== false) { $frag = substr($url, $p); $url = substr($url, 0, $p); }
    return $url . (str_contains($url, '?') ? '&' : '?') . 'utm_source=deltha&utm_medium=referral' . $frag;
}
function url_limpia(?string $url): string {
    $url = trim((string)$url);
    if ($url === '' || !preg_match('~^https?://~i', $url)) return $url;
    $frag = ''; $query = null;
    if (($p = strpos($url, '#')) !== false) { $frag = substr($url, $p + 1); $url = substr($url, 0, $p); }
    if (($p = strpos($url, '?')) !== false) { $query = substr($url, $p + 1); $url = substr($url, 0, $p); }
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    $path = (string)parse_url($url, PHP_URL_PATH);
    $esDe = fn(string ...$ds) => (bool)array_filter($ds, fn($d) => $host === $d || str_ends_with($host, '.' . $d));
    if ($query !== null) {
        $keep = [];
        foreach (explode('&', $query) as $par) {
            if ($par === '') continue;
            $k = strtolower(rawurldecode(explode('=', $par, 2)[0]));
            $v = explode('=', $par, 2)[1] ?? '';
            if (str_starts_with($k, 'utm_') || in_array($k, DDD_URL_TRACK, true)) continue;
            if ($k === 'si' && $esDe('youtube.com', 'youtu.be', 'spotify.com')) continue;                                             // quien comparte
            if ($k === 'r' && preg_match('~^[a-z0-9]{2,10}$~i', $v) && ($esDe('substack.com') || str_starts_with($path, '/p/'))) continue;   // Substack: quien comparte
            $keep[] = $par;
        }
        if ($keep) $url .= '?' . implode('&', $keep);
    }
    if ($frag !== '' && !preg_match('~^(utm_|xtor=|at_medium=|Echobox=)~i', $frag)) $url .= '#' . $frag;
    return $url;
}
function source_of(?string $url): ?string {
    if (!$url) return null;
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return null;
    return strtolower(preg_replace('/^www\./', '', $host));
}

/** El velo Ghost de una aportacion ha caducado? (fecha de la aportacion + ghost_minutes) */
function ghost_over(string $dt): bool {
    return time() - strtotime($dt) >= (int)cfg('ghost_minutes', 30) * 60;
}

/* ---- comunidades ----------------------------------------------------
 * Segunda clasificacion de los envios, ortogonal a los temas: cada envio tiene un
 * tema y, opcionalmente, una comunidad. Las crean los usuarios con aura > comm_min_aura,
 * hasta comm_max_per_user cada uno. El creador (y los mods) editan nombre, descripcion y color. */
function community_slug(string $name): string {
    $s = mb_strtolower(trim($name));
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(substr($s, 0, 40), '-');
}
function community_by_slug(PDO $pdo, string $slug): ?array {
    $st = $pdo->prepare("SELECT c.*, u.login AS owner FROM communities c JOIN users u ON u.user_id = c.owner_id WHERE c.slug = ?");
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}
/** Cuantas comunidades mas puede crear este usuario (0 = ninguna) */
function community_quota(PDO $pdo, ?array $u): int {
    if (!$u) return 0;
    $max = max(0, (int)cfg('comm_max_per_user', 3));
    if (!is_mod() && (float)$u['aura'] <= (float)cfg('comm_min_aura', 6)) return 0;   // mods y admin: sin requisito de aura
    $st = $pdo->prepare("SELECT COUNT(*) FROM communities WHERE owner_id = ?"); $st->execute([$u['user_id']]);
    return is_admin() ? 99 : max(0, $max - (int)$st->fetchColumn());
}
function community_link(array $s): string {
    return "<a href=\"/comunidad/" . rawurlencode((string)$s['comm_slug']) . "\">" . h($s['comm_name']) . "</a>";
}
/** Todas las comunidades del envio (story_communities), la principal primero: " · Cine clásico, Cortos". '' si no hay.
 *  Usa comm_list (GROUP_CONCAT de story_query_base) si viene; si no, la principal. */
function community_links(array $s): string {
    $out = [];
    if (!empty($s['comm_list'])) {
        foreach (explode("\n", (string)$s['comm_list']) as $row) {
            [$slug, $name] = array_pad(explode('|', $row, 2), 2, '');
            if ($slug !== '') $out[] = community_link(['comm_slug' => $slug, 'comm_name' => $name]);
        }
    } elseif (!empty($s['comm_slug'])) $out[] = community_link($s);
    return $out ? ' · ' . implode(', ', $out) : '';
}
const DDD_COMM_PUBLIC = "('nuevas','destacadas','archived')";   // fases en las que un envio se puede adoptar y se ve en la comunidad
/** Comunidades en las que esta un envio (principal primero), con quien la añadio. */
function story_communities(PDO $pdo, int $story_id): array {
    $st = $pdo->prepare("SELECT c.community_id, c.name, c.slug, c.color, c.owner_id, c.status, sc.added_by, sc.created, (c.community_id = s.community_id) AS main
                           FROM story_communities sc JOIN communities c ON c.community_id = sc.community_id JOIN stories s ON s.story_id = sc.story_id
                          WHERE sc.story_id = ? ORDER BY main DESC, c.name");
    $st->execute([$story_id]);
    return $st->fetchAll();
}
/** Comunidades que administra el usuario: las suyas; los mods y el admin, todas las visibles. */
/** Seguir comunidades (migra-142, 12/10/2026): user_communities. Las que sigue el usuario, por nombre; quien crea una la sigue. */
function comunidades_seguidas(PDO $pdo, ?array $u): array {
    if (!$u) return [];
    static $c = [];
    $k = (int)$u['user_id'];
    if (!isset($c[$k])) { try { $st = $pdo->prepare("SELECT c.* FROM user_communities uc JOIN communities c ON c.community_id = uc.community_id WHERE uc.user_id = ? AND c.status = 'ok' ORDER BY c.name"); $st->execute([$k]); $c[$k] = $st->fetchAll(); } catch (Throwable $e) { $c[$k] = []; } }
    return $c[$k];
}
function comunidad_seguida(PDO $pdo, ?array $u, int $cid): bool { foreach (comunidades_seguidas($pdo, $u) as $c) if ((int)$c['community_id'] === $cid) return true; return false; }
function comunidad_seguir_set(PDO $pdo, int $uid, int $cid, bool $on): void {
    if ($on) $pdo->prepare("INSERT IGNORE INTO user_communities (user_id, community_id) VALUES (?,?)")->execute([$uid, $cid]);
    else $pdo->prepare("DELETE FROM user_communities WHERE user_id = ? AND community_id = ?")->execute([$uid, $cid]);
}
/** Boton «Seguir» / «Siguiendo» de una comunidad (misma forma que el de las fuentes: form.seguir, app/scroll.js lo envia sin saltar). */
function comunidad_seguir_form(array $c, bool $nombre = false): string {
    $u = user(); if (!$u || ($c['status'] ?? 'ok') !== 'ok') return '';
    global $pdo; $on = comunidad_seguida($pdo, $u, (int)$c['community_id']);
    $title = h($on ? t('dejar de seguir %s', $c['name']) : t('Seguir %s', $c['name']));
    return "<form class=\"seguir" . ($on ? ' on' : '') . "\" method=\"post\" action=\"/seguir-comunidad\"><input type=\"hidden\" name=\"c\" value=\"" . (int)$c['community_id'] . "\"><input type=\"hidden\" name=\"act\" value=\"" . ($on ? 'quitar' : 'guardar') . "\">"
         . "<button title=\"$title\">" . h($on ? t('Siguiendo') : ($nombre ? t('Seguir %s', $c['name']) : t('Seguir'))) . "</button></form>";
}
/** Vista de /comunidades que se abre sin ?v=: la ultima usada (cookie ddd_cv); sin cookie, «mias» si sigue alguna, si no «todas». */
function comunidades_vista(PDO $pdo): string {
    $v = (string)($_GET['v'] ?? '');
    if (!in_array($v, ['mias', 'todas'], true)) { $v = (string)($_COOKIE['ddd_cv'] ?? ''); if (!in_array($v, ['mias', 'todas'], true)) $v = comunidades_seguidas($pdo, user()) ? 'mias' : 'todas'; }
    if (!user()) $v = 'todas';
    if (($_COOKIE['ddd_cv'] ?? '') !== $v && !headers_sent()) pref_cookie('ddd_cv', $v);
    return $v;
}

function my_communities(PDO $pdo, ?array $u): array {
    if (!$u) return [];
    if (is_mod()) return $pdo->query("SELECT community_id, name, slug FROM communities WHERE status = 'ok' ORDER BY name")->fetchAll();
    $st = $pdo->prepare("SELECT community_id, name, slug FROM communities WHERE owner_id = ? AND status = 'ok' ORDER BY name");
    $st->execute([$u['user_id']]);
    return $st->fetchAll();
}
/** ¿Puede este usuario añadir o quitar envios en esa comunidad? Su dueño, los mods y el admin. */
function community_can_manage(PDO $pdo, ?array $u, int $community_id): bool {
    if (!$u) return false;
    if (is_mod()) return true;
    $st = $pdo->prepare("SELECT 1 FROM communities WHERE community_id = ? AND owner_id = ?"); $st->execute([$community_id, $u['user_id']]);
    return (bool)$st->fetchColumn();
}
/** Añade un envio a una comunidad (adopcion). Devuelve null si va bien o el mensaje de error. */
function community_add_story(PDO $pdo, int $story_id, int $community_id, ?int $by): ?string {
    $st = $pdo->prepare("SELECT status FROM stories WHERE story_id = ?"); $st->execute([$story_id]);
    $status = $st->fetchColumn();
    if (!$status || !in_array($status, ['nuevas', 'destacadas', 'archived'], true)) return t('Ese envío no se puede añadir a una comunidad.');
    $st = $pdo->prepare("SELECT 1 FROM communities WHERE community_id = ? AND status = 'ok'"); $st->execute([$community_id]);
    if (!$st->fetchColumn()) return t('Esa comunidad no existe.');
    $st = $pdo->prepare("SELECT COUNT(*) FROM story_communities WHERE story_id = ?"); $st->execute([$story_id]);
    $max = max(1, (int)cfg('comm_max_per_story', 3));
    if ((int)$st->fetchColumn() >= $max) return t('Un envío puede estar como mucho en %d comunidades.', $max);
    $pdo->prepare("INSERT IGNORE INTO story_communities (story_id, community_id, added_by) VALUES (?,?,?)")->execute([$story_id, $community_id, $by]);
    return null;
}
/** Quita un envio de una comunidad. Si era la principal, pasa a serlo la mas antigua de las que queden (o ninguna). */
function community_remove_story(PDO $pdo, int $story_id, int $community_id): void {
    $pdo->prepare("DELETE FROM story_communities WHERE story_id = ? AND community_id = ?")->execute([$story_id, $community_id]);
    $st = $pdo->prepare("SELECT community_id FROM stories WHERE story_id = ?"); $st->execute([$story_id]);
    if ((int)$st->fetchColumn() === $community_id) {
        $st = $pdo->prepare("SELECT community_id FROM story_communities WHERE story_id = ? ORDER BY created LIMIT 1"); $st->execute([$story_id]);
        $pdo->prepare("UPDATE stories SET community_id = ? WHERE story_id = ?")->execute([$st->fetchColumn() ?: null, $story_id]);
    }
}
/** Cambia la comunidad principal (la que elige el autor o los mods): sustituye la antigua principal por la nueva
 *  en story_communities y deja las adoptadas como estan. 0 = sin principal (la antigua se quita). */
function story_set_main_community(PDO $pdo, int $story_id, int $community_id, ?int $by): void {
    $st = $pdo->prepare("SELECT community_id FROM stories WHERE story_id = ?"); $st->execute([$story_id]);
    $old = (int)$st->fetchColumn();
    if ($old === $community_id) return;
    if ($community_id) {
        $st = $pdo->prepare("SELECT 1 FROM communities WHERE community_id = ?"); $st->execute([$community_id]);
        if (!$st->fetchColumn()) return;
    }
    if ($old) $pdo->prepare("DELETE FROM story_communities WHERE story_id = ? AND community_id = ?")->execute([$story_id, $old]);
    if ($community_id) $pdo->prepare("INSERT IGNORE INTO story_communities (story_id, community_id, added_by) VALUES (?,?,?)")->execute([$story_id, $community_id, $by]);
    $pdo->prepare("UPDATE stories SET community_id = ? WHERE story_id = ?")->execute([$community_id ?: null, $story_id]);
}

/* ---- temas: renombrar y borrar (moderadores y admin) ---------------- */
/** Renombra un tema (minusculas, 2-32 letras/numeros/espacio/guion). Devuelve null o el error. */
function topic_rename(PDO $pdo, int $topic_id, string $name): ?string {
    $name = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    if (!preg_match('/^[\p{L}\p{N} \-]{2,32}$/u', $name)) return 'Nombre de tema no válido (2–32 letras, números, espacio o guion).';
    try { $pdo->prepare("UPDATE topics SET name = ? WHERE topic_id = ?")->execute([$name, $topic_id]); }
    catch (PDOException $e) { return 'Ya hay otro tema con ese nombre.'; }
    return null;
}
/** Borra un tema moviendo antes sus envios (y los medios que lo proponian, y quien aterrizaba en el) al tema $to. */
function topic_delete(PDO $pdo, int $topic_id, int $to): ?string {
    if ($topic_id === $to) return 'Elige otro tema al que mover los envíos.';
    $n = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
    if ((int)$n < 2) return 'Tiene que quedar al menos un tema.';
    $st = $pdo->prepare("SELECT name FROM topics WHERE topic_id = ?");
    $st->execute([$topic_id]); $name = $st->fetchColumn();
    $st->execute([$to]);       $toName = $st->fetchColumn();
    if (!$name || !$toName) return 'Ese tema no existe.';
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE stories SET topic_id = ? WHERE topic_id = ?")->execute([$to, $topic_id]);
        try { $pdo->prepare("UPDATE sources SET topic_id = ? WHERE topic_id = ?")->execute([$to, $topic_id]); } catch (PDOException $e) {}   // sin fuentes en esta instalacion
        $pdo->prepare("UPDATE users SET landing = ? WHERE landing = ?")->execute(["t:$to", "t:$topic_id"]);
        $pdo->prepare("DELETE FROM topics WHERE topic_id = ?")->execute([$topic_id]);
        $pdo->commit();
    } catch (PDOException $e) { $pdo->rollBack(); return 'No se pudo borrar el tema: ' . $e->getMessage(); }
    return null;
}
/** A donde aterriza el usuario al entrar: '' = portada | t:ID | c:ID | u:medio / u:sub / u:yt / u:agg (lo ultimo de Mis medios / Mis substacks / Mis canales / Mis agregadores, /ultimo) */
function landing_url(PDO $pdo, ?array $u): string {
    $l = (string)($u['landing'] ?? '');
    if ($l === 'u:medio' || $l === 'u:sub') return (string)cfg('sources_enabled', '1') === '1' ? '/mis-fuentes?k=' . ($l === 'u:sub' ? 'newsletter' : 'medio') : '/';
    if (preg_match('/^t:(\d+)$/', $l, $m)) {
        $st = $pdo->prepare("SELECT 1 FROM topics WHERE topic_id = ?"); $st->execute([(int)$m[1]]);
        if ($st->fetchColumn()) return '/tema/' . (int)$m[1];
    } elseif (preg_match('/^c:(\d+)$/', $l, $m)) {
        $st = $pdo->prepare("SELECT slug FROM communities WHERE community_id = ? AND status = 'ok'"); $st->execute([(int)$m[1]]);
        if ($slug = $st->fetchColumn()) return '/comunidad/' . rawurlencode($slug);
    }
    if ($l === 'u:agg' && (string)cfg('sources_enabled', '1') === '1' && (string)cfg('sources_agregadores', '0') === '1') return '/mis-fuentes?k=agregador';
    if ($l === 'u:yt' && (string)cfg('sources_enabled', '1') === '1' && (string)cfg('sources_youtube', '1') === '1') return '/mis-fuentes?k=youtube';
    return '/';
}

/* ---- reportes y borrado de comentarios ------------------------------ */
const DDD_DELETED_TXT = 'Comentario eliminado por incumplir las normas de uso';
function report_reasons(): array {
    return ['insulto' => t('Insulta o veja a alguien'), 'odio' => t('Incita al odio o la discriminación'), 'acoso' => t('Amenaza o acosa'),
            'datos' => t('Publica datos personales de otra persona (doxxing)'), 'ilegal' => t('Contenido ilegal'), 'spam' => t('Spam o publicidad encubierta'), 'otro' => t('Otra cosa')];
}
/** Borrado de moderacion: el comentario sigue en el hilo (y en el contador) pero con el texto sustituido;
 *  el original queda en content_orig por si hace falta ante un requerimiento. Cierra sus reportes. */
function comment_delete(PDO $pdo, int $id): bool {
    $st = $pdo->prepare("UPDATE comments SET content_orig = IF(deleted = 1, content_orig, content), deleted = 1, content = ? WHERE comment_id = ? AND status = 'ok'");
    $st->execute([DDD_DELETED_TXT, $id]);
    try { $pdo->prepare("UPDATE comment_reports SET status = 'deleted', decided_by = ?, decided = NOW() WHERE comment_id = ? AND status = 'open'")->execute([user()['user_id'] ?? null, $id]); } catch (Throwable $e) {}
    return $st->rowCount() > 0;
}
/** El usuario actual puede editar este comentario suyo? Aura > comment_edit_min_aura y menos de comment_edit_minutes desde que lo publico. */
function can_edit_own_comment(array $c): bool {
    $u = user();
    if (!$u || empty($c['user_id']) || (int)$c['user_id'] !== (int)$u['user_id'] || !empty($c['deleted'])) return false;
    if ((float)$u['aura'] <= (float)cfg('comment_edit_min_aura', 6)) return false;
    return time() - strtotime($c['created']) < (int)cfg('comment_edit_minutes', 10) * 60;
}
/** El usuario actual puede editar (o retirar) este envio suyo? Aura > story_edit_min_aura, menos de
 *  story_edit_minutes desde el envio y el envio aun sin llegar a portada (en el radar). */
function can_edit_own_story(array $s): bool {
    $u = user();
    if (!$u || empty($s['user_id']) || (int)$s['user_id'] !== (int)$u['user_id']) return false;
    if (!in_array($s['status'], ['pending_mod', 'nuevas'], true)) return false;   // en portada (o archivado) ya no
    if ((float)$u['aura'] <= (float)cfg('story_edit_min_aura', 6)) return false;
    return time() - strtotime($s['sent_date']) < (int)cfg('story_edit_minutes', 15) * 60;
}
/** Reportes de comentarios pendientes (para el enlace de moderacion) */
function open_reports(): int {
    static $n = null; global $pdo;
    if ($n !== null) return $n;
    try { $n = (int)$pdo->query("SELECT COUNT(DISTINCT comment_id) FROM comment_reports WHERE status = 'open'")->fetchColumn(); } catch (Throwable $e) { $n = 0; }
    try { $n += (int)$pdo->query("SELECT COUNT(DISTINCT msg_id) FROM chat_reports WHERE status = 'open'")->fetchColumn(); } catch (Throwable $e) {}
    try { $n += (int)$pdo->query("SELECT COUNT(*) FROM source_proposals WHERE status = 'pending'")->fetchColumn(); } catch (Throwable $e) {}   // fuentes propuestas (migra-057)
    try { $n += (int)$pdo->query("SELECT COUNT(*) FROM mod_queue WHERE status IN ('flag','hidden','needs_human')")->fetchColumn(); } catch (Throwable $e) {}   // señalado por la IA local (migra-062)
    return $n;
}

/* ---- paginas editables (tabla pages, admin) ------------------------- */
/** Pagina editable en el idioma pedido ($lang; por defecto el de la interfaz). Las traducciones son filas propias con slug
 *  «que-es.en», «normas.fr»…; si no hay traduccion se sirve la española. 'lang' dice en que idioma ha salido. */
function page_get(PDO $pdo, string $slug, ?string $lang = null): array {
    $lang ??= lang();
    $st = $pdo->prepare("SELECT slug, title, body, updated FROM pages WHERE slug = ?");
    if ($lang !== 'es' && isset(DDD_LANGS[$lang])) {
        $st->execute(["$slug.$lang"]);
        if ($pg = $st->fetch()) return ['slug' => $slug, 'lang' => $lang] + $pg;
    }
    $st->execute([$slug]);
    $pg = $st->fetch() ?: ['slug' => $slug, 'title' => ucfirst($slug), 'body' => '<p>Sin contenido todavía.</p>', 'updated' => null];
    return ['lang' => 'es'] + $pg;
}
/** Cuerpo HTML con {site}, {ttl}, {gm}… sustituidos por la config actual (lista en admin -> Paginas) */
/** Numero en formato español, sin ceros sobrantes (0.05 -> 0,05; 1.00 -> 1; 2.50 -> 2,5) */
function num_es($v): string { $s = rtrim(rtrim(number_format((float)$v, 3, ',', '.'), '0'), ','); return $s === '' ? '0' : $s; }
/** Numero de las paginas editables: coma decimal salvo en ingles (0,05 / 0.05) */
function num_pg(string $lang, $v): string { $s = num_es($v); return $lang === 'en' ? strtr($s, [',' => '.', '.' => ',']) : $s; }
/** Fecha larga de una Y-m-d en el idioma de la pagina (para {auradl}): «31 de diciembre de 2027», «31 December 2027»,
 *  «31. Dezember 2026», «2026ko abenduaren 31»… */
function fecha_larga(string $ymd, string $lang = 'es'): string {
    $t = strtotime($ymd) ?: time();
    $M = [
        'es' => ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'],
        'ca' => ['gener','febrer','març','abril','maig','juny','juliol','agost','setembre','octubre','novembre','desembre'],
        'gl' => ['xaneiro','febreiro','marzo','abril','maio','xuño','xullo','agosto','setembro','outubro','novembro','decembro'],
        'eu' => ['urtarrilaren','otsailaren','martxoaren','apirilaren','maiatzaren','ekainaren','uztailaren','abuztuaren','irailaren','urriaren','azaroaren','abenduaren'],
        'en' => ['January','February','March','April','May','June','July','August','September','October','November','December'],
        'fr' => ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'],
        'de' => ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
        'it' => ['gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'],
        'pt' => ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'],
        'tr' => ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'],
    ];
    $d = (int)date('j', $t); $y = date('Y', $t); $m = ($M[$lang] ?? $M['es'])[(int)date('n', $t) - 1];
    // catalan: «d'abril», «d'agost», «d'octubre» (apostrofo ante vocal)
    if ($lang === 'ca') return "$d " . (preg_match('/^[aeiou]/', $m) ? "d'$m" : "de $m") . " de $y";
    return match ($lang) {
        'gl', 'pt' => "$d de $m de $y",
        'eu'       => "{$y}ko $m $d",
        'de'       => "$d. $m $y",
        'en', 'fr', 'it', 'tr' => "$d $m $y",
        default    => "$d de $m de $y",
    };
}
/** Temas con bonus, para la pagina del algoritmo: «cultura, ciencia y relatos (+0,3)» o, con bonus distintos, «cultura (+0,3), ciencia (+0,2)». */
function bonus_temas_txt(string $L): string {
    global $pdo;
    try { $rows = $pdo->query("SELECT name, bonus FROM topics WHERE bonus > 0 ORDER BY bonus DESC, name")->fetchAll(); } catch (Throwable $e) { $rows = []; }
    if (!$rows) return '—';
    $and = ['es' => 'y', 'ca' => 'i', 'gl' => 'e', 'eu' => 'eta', 'en' => 'and', 'fr' => 'et', 'de' => 'und', 'it' => 'e', 'pt' => 'e', 'tr' => 've'][$L] ?? 'y';
    $vals = array_unique(array_map(fn($r) => (float)$r['bonus'], $rows));
    $names = array_map(fn($r) => h($r['name']), $rows);
    $join = fn(array $a) => count($a) > 1 ? implode(', ', array_slice($a, 0, -1)) . " $and " . end($a) : ($a[0] ?? '');
    if (count($vals) === 1) return $join($names) . ' (+' . num_pg($L, $rows[0]['bonus']) . ')';
    return $join(array_map(fn($r) => h($r['name']) . ' (+' . num_pg($L, $r['bonus']) . ')', $rows));
}
function page_body(array $pg): string {
    $L = $pg['lang'] ?? 'es';
    return strtr($pg['body'], [
        '{site}' => h(cfg('site_name', 'Deltha')),
        '{ttl}'  => (string)(int)cfg('thread_ttl_hours', 48),
        '{gm}'   => (string)(int)cfg('ghost_minutes', 30),
        // parametros del algoritmo (pagina «Como funciona el δ»)
        '{cycle}'    => (string)(int)cfg('cycle_minutes', 5),
        '{minage}'   => (string)(int)cfg('delta_min_age_min', 30),
        '{minpub}'   => num_pg($L, delta_minpub('medios')),
        '{minpubs}'  => num_pg($L, delta_minpub('substacks')),
        // umbrales en una frase (migra-140): «1 en las dos portadas» si coinciden; si no, «1 (Medios) o 2 (Substacks)» / «… y …» (linea de los numeros)
        '{minpubtxt}'  => abs(delta_minpub('medios') - delta_minpub('substacks')) < 0.0005 ? t_in($L, '%s en las dos portadas', num_pg($L, delta_minpub('medios'))) : t_in($L, '%s (Medios) o %s (Substacks)', num_pg($L, delta_minpub('medios')), num_pg($L, delta_minpub('substacks'))),
        '{minpubtxt2}' => abs(delta_minpub('medios') - delta_minpub('substacks')) < 0.0005 ? t_in($L, '%s en las dos portadas', num_pg($L, delta_minpub('medios'))) : t_in($L, '%s (Medios) y %s (Substacks)', num_pg($L, delta_minpub('medios')), num_pg($L, delta_minpub('substacks'))),
        '{nlangs}'   => (string)count(DDD_LANGS),   // idiomas de la interfaz (migra-140)
        '{minvotos}' => (string)delta_min_votes(),
        '{newttl}'   => (string)(int)cfg('nuevas_ttl_hours', 168),
        '{newttltxt}'=> dur_pg($L, (int)cfg('nuevas_ttl_hours', 168)),
        '{ttltxt}'   => dur_pg($L, (int)cfg('thread_ttl_hours', 48)),
        '{decay}'    => num_pg($L, delta_decay('medios')),
        '{decays}'   => num_pg($L, delta_decay('substacks')),
        '{quar}'     => (string)(int)cfg('vote_quarantine_hours', 24),
        '{quartxt}'  => dur_pg($L, (int)cfg('vote_quarantine_hours', 24)),
        '{bonustemas}' => bonus_temas_txt($L),
        '{auraport}' => num_pg($L, cfg('aura_portada', 0.5)),
        '{auraenv}'  => num_pg($L, cfg('aura_envio', 0.1)),
        '{auracom}'  => num_pg($L, cfg('aura_comentario', 0.02)),
        '{auracap}'  => num_pg($L, cfg('aura_daily_cap', 2)),
        '{auradec}'  => num_pg($L, cfg('aura_daily_decay', 0.5)),
        '{aurastart}'=> num_pg($L, cfg('aura_start', 5)),
        '{auramax}'  => num_pg($L, cfg('aura_max', 50)),
        '{commaura}' => num_pg($L, cfg('comm_min_aura', 6)),
        '{commmax}'  => (string)(int)cfg('comm_max_per_user', 3),
        '{editaura}' => num_pg($L, cfg('comment_edit_min_aura', 6)),
        '{editmin}'  => (string)(int)cfg('comment_edit_minutes', 10),
        '{seditaura}'=> num_pg($L, cfg('story_edit_min_aura', 6)),
        '{pincost}'  => num_pg($L, cfg('pin_cost', 0.5)),                    // destacar en portada, en aura (migra-132)
        '{pincostorig}' => num_pg($L, cfg('pin_cost_originales', 1)),          // destacar un original
        '{aurafloor}'=> num_pg($L, cfg('aura_gasto_minimo', cfg('aura_start', 5))),   // suelo del aura como moneda
        '{pinmin}'   => (string)(int)cfg('pin_user_minutes', 30),               // minutos que dura la destacada de usuario en portada
        '{pinhorig}' => num_pg($L, round((int)cfg('pin_user_minutes_originales', 240) / 60, 1)),   // horas en Originales
        '{seditmin}' => (string)(int)cfg('story_edit_minutes', 15),
        // fuentes RSS (cuantos medios y newsletters favoritos salen en el menu, aura para proponer)
        '{mediamax}' => (string)(int)cfg('media_max_per_user', 3),
        '{newsmax}'  => (string)(int)cfg('news_max_per_user', 3),
        '{propaura}' => num_pg($L, cfg('source_propose_aura', 6)),
        // pagina «¿Que es Deltha?»
        '{skins}'    => (string)count(DDD_SKINS),
        '{auradl}'   => fecha_larga(cfg('aura_deadline', '2027-12-31'), $L),
    ]);
}

/* ---- plantillas visuales ----------------------------------------------
 * editorial (la clasica, con mas aire) | comunidad (tarjetas, usuarios)
 * | delta (identidad grafica: espiga, δ visible, fotos en gris) | flujo (fotos laterales, denso).
 * Orden de preferencia: ?skin= en la URL (probar sin guardar) > communities.skin (en la comunidad y sus hilos) > users.skin > cookie ddd_skin (solo anonimos que eligieron) > config.skin_default. */
const DDD_SKINS = ['editorial' => 'Editorial', 'comunidad' => 'Comunidad', 'delta' => 'Delta', 'flujo' => 'Flujo', 'retro' => 'Retrocomputer', 'manga' => 'Manga', 'punk' => 'Punk', 'popart' => 'Pop Art', 'zen' => 'Zen', 'orange' => 'Orange', 'foro' => 'Foro',
    'hyperpop' => 'Hyperpop / Y2K', 'neobrutal' => 'Neo-Brutal', 'stickerbomb' => 'Stickerbomb', 'riso' => 'Riso / Zine', 'aero' => 'Frutiger Aero', 'hud' => 'Game HUD', 'acid' => 'Acid Web', 'pixel' => 'Pixel / 16-bit', 'dopamine' => 'Dopamine', 'www1998' => 'WWW 1998', 'solarpunk' => 'Solarpunk',
    'galactica' => 'Galáctica', 'impresionismo' => 'Impresionismo', 'grotesque' => 'Grotesque', 'phantom' => 'Phantom', 'bw' => 'B/W', 'surrealismo' => 'Surrealismo',
    'marino' => 'Marino', 'acero' => 'Acero', 'sendero' => 'Sendero', 'pildora' => 'Píldora', 'brasa' => 'Brasa'];
/** Familias de skins: una skin puede heredar toda la estructura de otra y cambiar solo colores, letra y detalles. Se emite en
 *  <html data-fam="…">; el CSS de la skin base se escribe contra [data-fam="base"] y el de la derivada, despues, contra [data-skin="…"].
 *  Las cuatro de foro (v156: Marino = Burbuja, Acero = HTCMania, Sendero = ForoMTB, Pildora = ForoBeta) heredan de Comunidad. */
const DDD_SKIN_FAM = ['marino' => 'comunidad', 'acero' => 'comunidad', 'sendero' => 'comunidad', 'pildora' => 'comunidad', 'brasa' => 'comunidad'];   // brasa = Mediavida (v158)
function skin_fam(): string { return DDD_SKIN_FAM[skin()] ?? skin(); }
/** Cookie de plantilla para quien no tiene cuenta: solo se crea si la persona elige una plantilla en el menu (/cambiar-skin) o en /plantilla. */
const DDD_SKIN_COOKIE = 'ddd_skin';
/** Cookie de preferencia (plantilla, tema, idioma) para quien no tiene cuenta: un año, y solo porque la persona la pidio; '' la borra. */
function pref_cookie(string $name, string $v): void {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($v === '') setcookie($name, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $secure, 'samesite' => 'Lax']);
    else setcookie($name, $v, ['expires' => time() + 365 * 86400, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
}
/** Guarda la plantilla elegida: con cuenta en users.skin (y borra la cookie); sin cuenta, cookie. '' = la del sitio. */
function save_skin_pref(string $s): void {
    global $pdo; $u = user();
    if (!isset(DDD_SKINS[$s])) $s = '';
    if ($u) { $pdo->prepare("UPDATE users SET skin = ? WHERE user_id = ?")->execute([$s, $u['user_id']]); pref_cookie(DDD_SKIN_COOKIE, ''); }
    else pref_cookie(DDD_SKIN_COOKIE, $s);
}
/** Guarda el tema (auto|light|dark) igual que la plantilla. */
function save_theme_pref(string $t): void {
    global $pdo; $u = user();
    $t = in_array($t, ['light', 'dark'], true) ? $t : 'auto';
    if ($u) { $pdo->prepare("UPDATE users SET theme = ? WHERE user_id = ?")->execute([$t, $u['user_id']]); pref_cookie('ddd_theme', ''); }
    else pref_cookie('ddd_theme', $t === 'auto' ? '' : $t);
}
/** Tema claro/oscuro: users.theme o, sin cuenta, la cookie ddd_theme (solo si se eligio en el menu). null = sigue al sistema. */
/** Modo «sin distracciones» (solo cambia el escritorio de tres columnas): sin columna derecha, la central se ensancha hasta ocupar su
 *  sitio y las fotos dejan de ir como tarjeta bajo el texto para ir a la derecha del titular, al ancho que tenia esa columna. Pensado para
 *  leer los hilos de los foros. Es una preferencia del navegador, no de la cuenta (depende de la pantalla): cookie ddd_foco, con o sin sesion;
 *  se cambia en el menu lateral (/cambiar-skin?z=…). Tres estados: sin cookie = solo en Foros (la lista /foros y el hilo
 *  de un texto propio: esas paginas llaman a foco_por_defecto() antes de pintar); '1' = siempre; '0' = nunca.
 *  page_header() lo marca en <html data-foco="1">. */
$DDD_FOCO_DEF = false;
function foco_por_defecto(bool $on = true): void { global $DDD_FOCO_DEF; $DDD_FOCO_DEF = $on; }
function foco_pref(): string { $c = (string)($_COOKIE['ddd_foco'] ?? ''); return in_array($c, ['0', '1'], true) ? $c : ''; }
function foco(): bool { global $DDD_FOCO_DEF; $p = foco_pref(); return $p === '1' || ($p === '' && $DDD_FOCO_DEF); }
function theme(): ?string {
    $u = user();
    $t = $u ? (string)($u['theme'] ?? 'auto') : (string)($_COOKIE['ddd_theme'] ?? 'auto');
    return in_array($t, ['light', 'dark'], true) ? $t : null;
}
/** Plantilla propia de la comunidad que se esta viendo (su pagina y sus hilos): la fija la pagina antes de page_header().
 *  Manda sobre la del usuario, pero no sobre ?skin= (probar). '' = la comunidad no tiene plantilla propia. */
$DDD_COMM_SKIN = '';
/** La comunidad o la fuente imponen su plantilla, salvo que el usuario haya pedido en su perfil «usar siempre la mía» (users.skin_propio, migra-143). */
function community_skin(?string $skin): void { global $DDD_COMM_SKIN; $DDD_COMM_SKIN = isset(DDD_SKINS[(string)$skin]) && empty(user()['skin_propio']) ? (string)$skin : ''; }
/** Enlace para conmutar esa preferencia desde la propia comunidad (cabecera): «ver con mi plantilla» / «ver con la suya». */
function skin_propio_link(): string {
    $u = user(); if (!$u) return '';
    $on = !empty($u['skin_propio']);
    // solo el boton, atado por form="" a un formulario que pinta page_footer(): un <form> dentro de un <p class="notice"> cerraria el parrafo (12/10/2026)
    $GLOBALS['DDD_SKINPROP_FORM'] = "<form id=\"skinprop\" method=\"post\" action=\"/skin-propio\" hidden><input type=\"hidden\" name=\"v\" value=\"" . ($on ? 0 : 1) . "\"></form>";
    return "<button class=\"link\" form=\"skinprop\">" . ($on ? t('ver con su plantilla') : t('ver con mi plantilla')) . "</button>";
}
function skin(): string {
    static $s = null;
    if ($s !== null) return $s;
    global $DDD_COMM_SKIN;
    $q = (string)($_GET['skin'] ?? '');
    $u = user();
    $ck = $u ? '' : (string)($_COOKIE[DDD_SKIN_COOKIE] ?? '');   // sin cuenta: la elegida en el menu (cookie), si la hay
    foreach ([$q, $DDD_COMM_SKIN, (string)($u['skin'] ?? ''), $ck, (string)cfg('skin_default', 'editorial')] as $c) {
        if (isset(DDD_SKINS[$c])) return $s = $c;
    }
    return $s = 'editorial';
}
/* ---- delta: la formula publica (2026-09-26, migra-080) -------------------
 *   δ = signo(n) × √|n| + bonus del tema (solo si n > 0) − decaimiento de la portada × max(0, medias horas − medias horas minimas)
 * n = votos netos QUE CUENTAN (stories.eff_pos − eff_neg): un voto por conexion y envio (votes.fp, HMAC de ip+envio, sin guardar la IP)
 * y solo de cuentas con mas de vote_quarantine_hours. La raiz comprime: cuatro votos valen dos, dieciseis valen cuatro, asi una
 * camarilla necesita el cuadrado de lo de antes. El bonus suma, no multiplica (un coeficiente amplificaba tambien los negativos y el
 * decaimiento). El decaimiento empieza a contar cuando el envio es elegible (delta_min_age_min), no al enviarse, y es distinto por
 * portada (Substacks mas lento: lo suyo no caduca con la actualidad). Misma formula en SQL (delta_sql) y en PHP (delta_live). */
function delta_decay(?string $portada): float {
    if ($portada === 'canales') return (float)cfg('decay_canales', 0.02);   // un video, como un Substack, no caduca con la actualidad
    return (float)cfg($portada === 'substacks' ? 'decay_substacks' : 'decay_medios', $portada === 'substacks' ? 0.02 : 0.05);
}
function delta_minpub(?string $portada): float {
    if ($portada === 'canales') return (float)cfg('delta_min_publish_canales', 1);
    return (float)cfg($portada === 'substacks' ? 'delta_min_publish_substacks' : 'delta_min_publish_medios', 1);
}
/** Votos netos que cuentan necesarios para ser candidato (migra-083). Con la raiz, un voto da δ = 1 y alcanzaba el umbral: la puerta
 *  se pone en votos, no en δ, y el bonus del tema no sustituye a ninguno. */
function delta_min_votes(): int { return max(0, (int)cfg('delta_min_votes', 2)); }
/** Medias horas minimas antes de que empiece a descontar la antigüedad (= delta_min_age_min / 30). */
function delta_hh_min(): int { return intdiv(max(0, (int)cfg('delta_min_age_min', 30)), 30); }
/** Medias horas que descuentan a un envio enviado en $sent (0 mientras no es elegible). */
function delta_hh(string $sent): int { return max(0, intdiv(max(0, time() - strtotime($sent)), 1800) - delta_hh_min()); }
/** Valor del delta en vivo de una noticia (misma formula que delta_sql). $s: eff_pos, eff_neg, bonus, portada, sent_date. */
function delta_live(array $s): float {
    $n = (int)($s['eff_pos'] ?? 0) - (int)($s['eff_neg'] ?? 0);
    $bonus = $n > 0 ? (float)($s['bonus'] ?? 0) : 0.0;
    return ($n <=> 0) * sqrt(abs($n)) + $bonus - delta_decay($s['portada'] ?? null) * delta_hh((string)$s['sent_date']);
}
/** Expresion SQL del delta en vivo (tablas: stories s, topics t). La usan el ciclo (para guardarlo) y las listas (para ordenar/mostrar). */
function delta_sql(): string {
    $dm = number_format(delta_decay('medios'), 4, '.', ''); $ds = number_format(delta_decay('substacks'), 4, '.', ''); $dc = number_format(delta_decay('canales'), 4, '.', '');
    $n  = "(s.eff_pos - s.eff_neg)";
    $hh = "GREATEST(0, FLOOR(TIMESTAMPDIFF(MINUTE, s.sent_date, NOW()) / 30) - " . delta_hh_min() . ")";
    return "(SIGN($n) * SQRT(ABS($n)) + IF($n > 0, t.bonus, 0) - (CASE s.portada WHEN 'substacks' THEN $ds WHEN 'canales' THEN $dc ELSE $dm END) * $hh)";
}
/** Huella de un voto: la misma para todas las cuentas que voten el mismo envio desde la misma conexion. HMAC truncado con el secreto
 *  del sitio y el id del envio dentro: no se guarda la IP y la huella de un envio no sirve para enlazar votos en otro. */
function vote_fp(int $story_id): string { global $DDD_SECRET; return substr(hash_hmac('sha256', "voto|$story_id|" . client_ip(), $DDD_SECRET), 0, 16); }
/** Recalcula los votos que cuentan (eff_pos / eff_neg) de un envio o de todo el radar: un voto por huella y sentido, solo de cuentas
 *  con mas de vote_quarantine_hours. Los votos de una cuenta recien creada se guardan y se ven, pero entran en el δ cuando madura. */
function eff_votes_update(PDO $pdo, ?int $story_id = null): void {
    $q = max(0, (int)cfg('vote_quarantine_hours', 24));
    $where = $story_id !== null ? "s.story_id = ?" : "s.status = 'nuevas'";
    $sql = "UPDATE stories s LEFT JOIN (
                SELECT v.story_id,
                       COUNT(DISTINCT CASE WHEN v.value = 1  THEN COALESCE(v.fp, CONCAT('u', v.user_id)) END) AS pos,
                       COUNT(DISTINCT CASE WHEN v.value = -1 THEN COALESCE(v.fp, CONCAT('u', v.user_id)) END) AS neg
                  FROM votes v JOIN users u ON u.user_id = v.user_id
                 WHERE u.created <= NOW() - INTERVAL $q HOUR" . ($story_id !== null ? " AND v.story_id = ?" : "") . "
                 GROUP BY v.story_id) e ON e.story_id = s.story_id
               SET s.eff_pos = COALESCE(e.pos, 0), s.eff_neg = COALESCE(e.neg, 0)
             WHERE $where";
    $pdo->prepare($sql)->execute($story_id !== null ? [$story_id, $story_id] : []);
}
/* ---- el ciclo: ventanas de cycle_minutes con un minuto de ejecucion imprevisible ------------------
 * El cron llama cada minuto. Antes el ciclo corria en los minutos multiplos de cycle_minutes, y como el radar muestra el δ de todos,
 * bastaba votar un minuto antes para adelantar a quien fuera (sniping). Ahora cada ventana de cycle_minutes tiene un minuto de
 * ejecucion derivado del secreto del sitio (ciclo_desfase): dentro de la ventana, nadie de fuera sabe cuando. La web dice
 * «antes de las hh:mm» (final de la ventana) en vez de la cuenta atras. cycle_jitter = 0 vuelve al minuto 0 de la ventana. */
function ciclo_ventana(int $ts): int { $c = max(1, (int)cfg('cycle_minutes', 5)); $m = intdiv($ts, 60); return ($m - $m % $c) * 60; }
function ciclo_desfase(int $ventana): int {
    global $DDD_SECRET;
    $c = max(1, (int)cfg('cycle_minutes', 5));
    if ($c < 2 || cfg('cycle_jitter', '1') !== '1') return 0;
    return (int)(hexdec(substr(hash_hmac('sha256', "ciclo|$ventana", $DDD_SECRET), 0, 8)) % $c);
}
/** ¿Toca ejecutar el ciclo en el minuto de $ts? */
function ciclo_toca(int $ts): bool { $v = ciclo_ventana($ts); return intdiv($ts - $v, 60) === ciclo_desfase($v); }
/** Inicio de la ventana cuyo ciclo aun no se ha ejecutado (si el minuto de esta ya paso, la siguiente). */
function ciclo_pendiente(?int $now = null): int {
    $now ??= time(); $c = max(1, (int)cfg('cycle_minutes', 5)) * 60;
    $v = ciclo_ventana($now);
    return intdiv($now, 60) > intdiv($v + ciclo_desfase($v) * 60, 60) ? $v + $c : $v;
}
/** Final de la ventana: «antes de las hh:mm». */
function ciclo_antes_de(int $ventana): int { return $ventana + max(1, (int)cfg('cycle_minutes', 5)) * 60; }

/* ---- las dos portadas: Medios y Substacks -------------------------------
 * Un enlace va a Substacks si su dominio es *.substack.com o el de una fuente del catalogo marcada como newsletter
 * (asi entran los Substacks con dominio propio); todo lo demas va a Medios. Los originales (content_type = article)
 * no van a ninguna portada: se quedan en su pestaña. El ciclo alterna: cada cycle_minutes publica en una portada,
 * y la otra espera al siguiente, asi que cada portada recibe como mucho una cada 2 × cycle_minutes.
 * Tercera portada, Canales (migra-106): los enlaces a YouTube. No entra en la alternancia: tiene TURNO CONDICIONADO. Un ciclo es
 * suyo solo si tiene algun video publicable y han pasado canales_gap_min desde su ultima publicacion; si no, el ciclo es de
 * Medios o Substacks como siempre (portada_turno no cambia). Se apaga con config.portada_canales = 0 (YouTube vuelve a Medios). */
const DDD_PORTADAS = ['medios' => 'Medios', 'substacks' => 'Substacks', 'canales' => 'Canales'];
const DDD_YOUTUBE_HOSTS = ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be'];
/** Id del video de una URL de YouTube (watch?v=, /shorts/, /live/, /embed/, youtu.be/); '' si no lo es. */
function youtube_video_id(?string $url): string {
    return preg_match('#^https?://(?:www\.|m\.|music\.)?(?:youtube\.com/(?:watch\?(?:[^\#]*&)?v=|shorts/|live/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})#', (string)$url, $m) ? $m[1] : '';
}
/** Reproducir los videos dentro de Deltha (config.youtube_embed, migra-107): la miniatura sigue siendo la copia local y nada de
 *  YouTube se carga hasta que se pulsa; entonces /app/video.js cambia la foto por el reproductor de youtube-nocookie.com.
 *  Hace falta «frame-src https://www.youtube-nocookie.com» en la CSP (apache.conf.example); sin ella o sin JS, el enlace abre YouTube. */
function youtube_embed_on(): bool { return (string)cfg('youtube_embed', '1') === '1'; }
/** Atributo data-yt="ID" para el enlace de la foto de un video (vacio si la URL no es de YouTube o la reproduccion esta apagada). */
function youtube_attr(?string $url): string {
    return youtube_embed_on() && ($id = youtube_video_id($url)) !== '' ? ' data-yt="' . $id . '" title="' . h(t('reproducir aquí')) . '"' : '';
}
function portada_canales_on(): bool { return (string)cfg('portada_canales', '1') === '1' && (string)cfg('sources_youtube', '1') === '1'; }   // con los canales apagados (admin > Config) tampoco hay portada Canales
/** Portadas en uso, en su orden: clave => etiqueta (sin Canales si esta apagada). */
function portadas(): array { $p = DDD_PORTADAS; if (!portada_canales_on()) unset($p['canales']); return $p; }
/* ---- Foros (migra-159, 16/10/2026): la seccion Foros (/foros) agrupa los textos propios (content_type = article) por foro. Tabla forums:
 *  slug (URL /foros?f=slug y grupo de destacados en pins), nombre, descr (aviso de cabecera), status on/off (apagado = solo lo ve el
 *  admin), position (orden de pestañas) y orden de los hilos ('fecha' = hilo mas reciente arriba; 'actividad' = ultimo mensaje arriba).
 *  Lo que era Originales es el foro «originales»; cada original es un hilo. ---- */
/** Foros por orden de pestaña; sin $todos, solo los activos (el admin ve tambien los apagados, marcados en la pestaña). Una consulta por pagina. */
function foros(bool $todos = false): array {
    static $all = null; global $pdo;
    if ($all === null) { try { $all = array_column($pdo->query("SELECT * FROM forums ORDER BY position, forum_id")->fetchAll(), null, 'slug'); } catch (Throwable $e) { $all = []; } }   // sin migra-159: sin foros
    return $todos ? $all : array_filter($all, fn($f) => $f['status'] === 'on');
}
/** El foro de un slug (incluidos los apagados: sus hilos y su URL siguen abriendose; en la lista solo lo ve el admin). */
function foro(string $slug): ?array { return foros(true)[$slug] ?? null; }
/** El foro de un envio (por stories.forum_id); null para los enlaces y los originales anteriores a la migracion. */
function foro_de(array $s): ?array { $id = (int)($s['forum_id'] ?? 0); if (!$id) return null; foreach (foros(true) as $f) if ((int)$f['forum_id'] === $id) return $f; return null; }
function foro_url(array $f): string { return '/foros?f=' . rawurlencode((string)$f['slug']); }
/** Minutos minimos entre dos publicaciones en Canales. */
function canales_gap(): int { return max(1, (int)cfg('canales_gap_min', 15)); }
/** Instante de la ultima publicacion en Canales (0 = nunca). */
function canales_ultima(PDO $pdo): int {
    static $t = null;
    if ($t !== null) return $t;
    try { $c = $pdo->query("SELECT MAX(created) FROM promo_log WHERE portada = 'canales'")->fetchColumn(); } catch (Throwable $e) { $c = null; }
    return $t = $c ? (int)strtotime((string)$c) : 0;
}
const DDD_PORTADA_COOKIE = 'ddd_portada';
/** Dominios (sin www.) de las fuentes del catalogo de tipo newsletter. */
function newsletter_hosts(PDO $pdo): array {
    static $h = null;
    if ($h !== null) return $h;
    $h = [];
    try { foreach ($pdo->query("SELECT home FROM sources WHERE kind = 'newsletter'") as $r) if ($d = source_of((string)$r['home'])) $h[$d] = true; }
    catch (Throwable $e) { /* tabla aun sin crear */ }
    return $h;
}
/** Portada a la que va un envio: 'medios' | 'substacks' | 'canales' (YouTube); null para los originales. */
function portada_de(PDO $pdo, array $s): ?string {
    if (($s['content_type'] ?? 'link') === 'article') return null;
    $d = strtolower((string)($s['source'] ?? '')) ?: (string)source_of((string)($s['url'] ?? ''));
    if ($d === '') return 'medios';
    if (in_array($d, DDD_YOUTUBE_HOSTS, true) && portada_canales_on()) return 'canales';
    return ($d === 'substack.com' || str_ends_with($d, '.substack.com') || isset(newsletter_hosts($pdo)[$d])) ? 'substacks' : 'medios';
}
/** Portada que publica en el ciclo que empieza en $ts (minuto multiplo de cycle_minutes): alternan medios, substacks, medios... */
function portada_turno(int $ts): string {
    $cycle = max(1, (int)cfg('cycle_minutes', 5));
    return intdiv(intdiv($ts, 60), $cycle) % 2 === 0 ? 'medios' : 'substacks';
}
/** Portada que se esta viendo: ?portada= en la URL (y entonces se recuerda en la cookie ddd_portada) o la ultima elegida; si no, Substacks.
 *  (No ?p=: ese es el numero de pagina de paginate().) */
function portada_actual(): string {
    static $p = null;
    if ($p !== null) return $p;
    $q = (string)($_GET['portada'] ?? '');
    if (isset(portadas()[$q])) {
        if (($_COOKIE[DDD_PORTADA_COOKIE] ?? '') !== $q && !headers_sent()) pref_cookie(DDD_PORTADA_COOKIE, $q);
        return $p = $q;
    }
    $c = (string)($_COOKIE[DDD_PORTADA_COOKIE] ?? '');
    return $p = isset(portadas()[$c]) ? $c : 'substacks';   // quien llega por primera vez ve Substacks, lo propio de Deltha
}
function delta_explica(array $s): string {
    $n = (int)($s['eff_pos'] ?? 0) - (int)($s['eff_neg'] ?? 0);
    $raiz = ($n <=> 0) * sqrt(abs($n));
    $bonus = $n > 0 ? (float)($s['bonus'] ?? 0) : 0.0;
    $dec = delta_decay($s['portada'] ?? null) * delta_hh((string)$s['sent_date']);
    $f = fn(float $x) => number_format($x, 2, ',', '');
    $txt = ($n < 0 ? '−' : '') . '√' . abs($n) . ' ' . t('votos') . ' = ' . number_format($raiz, 2, ',', '');
    if ($bonus > 0) $txt .= ' + ' . $f($bonus) . ' ' . h((string)($s['topic'] ?? ''));
    $txt .= ' − ' . $f($dec) . ' ' . t('por antigüedad');
    return $txt . ' = ' . number_format($raiz + $bonus - $dec, 1, ',', '');
}
/** Duracion en horas, en palabras y en el idioma de la pagina: 48 → «dos días», 168 → «una semana», 36 → «36 horas». */
function dur_pg(string $lang, int $hours): string {
    $W = [
        'es' => [['hora', 'horas'], ['día', 'días'], ['semana', 'semanas'], ['una', 'un', 'una']],
        'ca' => [['hora', 'hores'], ['dia', 'dies'], ['setmana', 'setmanes'], ['una', 'un', 'una']],
        'gl' => [['hora', 'horas'], ['día', 'días'], ['semana', 'semanas'], ['unha', 'un', 'unha']],
        'eu' => [['ordu', 'ordu'], ['egun', 'egun'], ['aste', 'aste'], ['bat', 'bat', 'bat']],
        'en' => [['hour', 'hours'], ['day', 'days'], ['week', 'weeks'], ['one', 'one', 'one']],
        'fr' => [['heure', 'heures'], ['jour', 'jours'], ['semaine', 'semaines'], ['une', 'un', 'une']],
        'de' => [['Stunde', 'Stunden'], ['Tag', 'Tage'], ['Woche', 'Wochen'], ['eine', 'ein', 'eine']],
        'it' => [['ora', 'ore'], ['giorno', 'giorni'], ['settimana', 'settimane'], ["un'", 'un', 'una']],
        'pt' => [['hora', 'horas'], ['dia', 'dias'], ['semana', 'semanas'], ['uma', 'um', 'uma']],
        'tr' => [['saat', 'saat'], ['gün', 'gün'], ['hafta', 'hafta'], ['bir', 'bir', 'bir']],   // en turco el nombre no lleva plural tras un numero
    ];
    [$h, $d, $w, $one] = $W[$lang] ?? $W['es'];
    $NUM = ['es' => ['dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete'], 'ca' => ['dues', 'tres', 'quatre', 'cinc', 'sis', 'set'],
            'gl' => ['dúas', 'tres', 'catro', 'cinco', 'seis', 'sete'], 'eu' => ['bi', 'hiru', 'lau', 'bost', 'sei', 'zazpi'],
            'en' => ['two', 'three', 'four', 'five', 'six', 'seven'], 'fr' => ['deux', 'trois', 'quatre', 'cinq', 'six', 'sept'],
            'de' => ['zwei', 'drei', 'vier', 'fünf', 'sechs', 'sieben'], 'it' => ['due', 'tre', 'quattro', 'cinque', 'sei', 'sette'],
            'pt' => ['duas', 'três', 'quatro', 'cinco', 'seis', 'sete'], 'tr' => ['iki', 'üç', 'dört', 'beş', 'altı', 'yedi']];
    $DOS_M = ['ca' => 'dos', 'gl' => 'dous', 'pt' => 'dois'];   // «dos» masculino (dias): el resto de unidades son femeninas en estas lenguas
    if ($hours > 0 && $hours % 168 === 0)     { $n = intdiv($hours, 168); $u = $w; $a = $one[2]; }
    elseif ($hours > 0 && $hours % 24 === 0)  { $n = intdiv($hours, 24);  $u = $d; $a = $one[1]; }
    else                                      { $n = $hours;              $u = $h; $a = $one[0]; }
    if ($n === 1) return $lang === 'eu' ? "$u[0] $a" : ($lang === 'it' && $u === $h ? "un'ora" : "$a $u[0]");
    $num = ($n >= 2 && $n <= 7) ? ($NUM[$lang] ?? $NUM['es'])[$n - 2] : (string)$n;
    if ($n === 2 && $u === $d && isset($DOS_M[$lang])) $num = $DOS_M[$lang];
    if ($lang === 'eu') return "$num $u[1]";
    if ($lang === 'de' && $u === $d && $n >= 2) return "$num Tage";
    return "$num $u[1]";
}

/* ---- alias Ghost ----------------------------------------------------
 * Apellido de una figura de la ciencia, la filosofia, el arte o las letras
 * (mujeres y hombres, ya fallecidos) + guion + un pueblo o ciudad de
 * cualquier parte del mundo: Mozart-Alburquerque, Curie-Kioto...
 * ------------------------------------------------------------------ */
const DDD_NOMBRES = [
    // ciencia
    'Curie','Einstein','Newton','Darwin','Galileo','Kepler','Copérnico','Hipatia','Lovelace','Noether',
    'Franklin','Meitner','Turing','Tesla','Faraday','Maxwell','Bohr','Planck','Heisenberg','Schrödinger',
    'Dirac','Fermi','Feynman','Hawking','Pasteur','Mendel','Linneo','Humboldt','Cajal','Ochoa',
    'Herschel','Somerville','Hodgkin','Fossey','Carson','Margulis','McClintock','Rubin','Leavitt','Bassi',
    'Euler','Gauss','Hilbert','Gödel','Ramanujan','Germain','Kovalévskaya','Lavoisier','Boyle','Volta',
    // filosofía
    'Platón','Aristóteles','Sócrates','Epicuro','Séneca','Averroes','Maimónides','Spinoza','Kant','Hegel',
    'Nietzsche','Kierkegaard','Schopenhauer','Wittgenstein','Arendt','Beauvoir','Weil','Zambrano','Ortega','Unamuno',
    'Descartes','Pascal','Montaigne','Voltaire','Rousseau','Diderot','Hume','Locke','Leibniz','Bergson',
    'Camus','Sartre','Foucault','Deleuze','Benjamin','Adorno','Marx','Gramsci','Murdoch','Anscombe',
    // arte y música
    'Mozart','Bach','Beethoven','Vivaldi','Chopin','Schubert','Debussy','Ravel','Mahler','Stravinski',
    'Falla','Albéniz','Granados','Piazzolla','Ellington','Armstrong','Holiday','Fitzgerald','Simone','Callas',
    'Kahlo','Goya','Velázquez','Picasso','Dalí','Miró','Sorolla','Zurbarán','Murillo','Greco',
    'Leonardo','Miguel Ángel','Rafael','Caravaggio','Rembrandt','Vermeer','Monet','Cézanne','Gauguin','Matisse',
    'Klimt','Schiele','Kandinski','Klee','Rothko','Pollock','Hopper','Basquiat','Warhol','Haring',
    'Gentileschi','Carrington','Varo','Modersohn','Münter','Lempicka','Bourgeois','Hepworth','Kollwitz','Cassatt',
    // letras
    'Cervantes','Quevedo','Góngora','Lope','Calderón','Bécquer','Galdós','Pardo Bazán','Machado','Lorca',
    'Cernuda','Alberti','Hernández','Delibes','Martín Gaite','Matute','Laforet','Chacel','Rodoreda','Pizarnik',
    'Borges','Cortázar','Rulfo','Onetti','Bolaño','Mistral','Storni','Castellanos','Garro','Ocampo',
    'Homero','Safo','Virgilio','Ovidio','Dante','Petrarca','Boccaccio','Shakespeare','Milton','Blake',
    'Austen','Brontë','Eliot','Woolf','Dickinson','Whitman','Poe','Melville','Twain','Hemingway',
    'Tolstói','Dostoievski','Chéjov','Gógol','Ajmátova','Tsvetáieva','Kafka','Mann','Rilke','Brecht',
    'Proust','Flaubert','Balzac','Zola','Colette','Duras','Yourcenar','Sand','Baudelaire','Rimbaud',
    'Joyce','Beckett','Wilde','Yeats','Orwell','Lessing','Murasaki','Tagore','Mahfuz','Achebe',
];
const DDD_LUGARES = [
    // España
    'Alburquerque','Cádiz','Ronda','Úbeda','Trujillo','Cuéllar','Sigüenza','Frómista','Laguardia','Cudillero',
    'Ribadeo','Betanzos','Combarro','Llanes','Potes','Sos','Aínsa','Albarracín','Morella','Peñíscola',
    'Altea','Cehegín','Moguer','Carmona','Osuna','Écija','Almagro','Zafra','Pedraza','Sepúlveda',
    // Europa
    'Lisboa','Oporto','Évora','Óbidos','Sintra','Burdeos','Aviñón','Carcasona','Colmar','Amberes',
    'Brujas','Gante','Delft','Leiden','Lübeck','Bamberg','Heidelberg','Ratisbona','Innsbruck','Salzburgo',
    'Trieste','Mantua','Ferrara','Siena','Lucca','Ravena','Bérgamo','Matera','Palermo','Siracusa',
    'Dubrovnik','Kotor','Ohrid','Corfú','Nauplia','Tesalónica','Cracovia','Toruń','Tallin','Riga',
    'Vilna','Tartu','Bergen','Tromsø','Uppsala','Visby','Odense','Aarhus','Reikiavik','Galway',
    'Kilkenny','Bath','York','Durham','Edimburgo','Inverness','Lviv','Odesa','Tiflis','Ereván',
    // África y Oriente Medio
    'Tánger','Fez','Esauira','Chauen','Tombuctú','Dakar','Saint-Louis','Zanzíbar','Lamu','Asmara',
    'Adís Abeba','Lalibela','Luxor','Asuán','Alejandría','Túnez','Kairuán','Trípoli','Alepo','Damasco',
    'Beirut','Jerusalén','Acre','Petra','Bagdad','Basora','Isfahán','Shiraz','Yazd','Tabriz',
    // Asia y Oceanía
    'Samarcanda','Bujará','Jiva','Kabul','Lahore','Jaipur','Udaipur','Benarés','Pondicherry','Cochín',
    'Katmandú','Timbu','Mandalay','Luang Prabang','Hoi An','Malaca','Yogyakarta','Kioto','Nara','Kanazawa',
    'Takayama','Hakodate','Gyeongju','Lijiang','Pingyao','Suzhou','Hangzhou','Kashgar','Lhasa','Ulán Bator',
    'Hobart','Fremantle','Dunedin','Nelson','Apia','Suva','Papeete','Nukualofa','Cebú','Vigan',
    // América
    'Valparaíso','Punta Arenas','Ushuaia','Bariloche','Salta','Colonia','Paraty','Ouro Preto','Olinda','Salvador',
    'Cuzco','Arequipa','Chachapoyas','Cartagena','Mompox','Popayán','Cuenca','Loja','Sucre','Potosí',
    'Antigua','Granada','Trinidad','Cienfuegos','Oaxaca','Guanajuato','Zacatecas','Morelia','Mérida','Campeche',
    'Querétaro','Santa Fe','Taos','Savannah','Charleston','Nueva Orleans','Natchez','Galena','Sitka','Quebec',
    'Tadoussac','Lunenburg','San Juan','Ponce','Quito','Cajamarca','Vicuña','Chiloé','Tilcara','Cafayate',
];

function alias_random(): string {
    return DDD_NOMBRES[random_int(0, count(DDD_NOMBRES) - 1)] . '-' . DDD_LUGARES[random_int(0, count(DDD_LUGARES) - 1)];
}
/** Alias estable para un anonimo dentro de un hilo, sin cookie: hash(ip+hilo+secreto). No se guarda la IP. */
function alias_anon(int $story_id): array {
    global $DDD_SECRET;
    if (cfg('anon_alias_mode') === 'random') return [alias_random(), null];
    $key = substr(hash_hmac('sha256', client_ip() . '|' . $story_id, $DDD_SECRET), 0, 16);
    $n = hexdec(substr($key, 0, 8));
    $alias = DDD_NOMBRES[$n % count(DDD_NOMBRES)] . '-' . DDD_LUGARES[intdiv($n, 997) % count(DDD_LUGARES)];
    return [$alias, $key];
}
/** Alias Ghost de un registrado en un hilo: se crea la primera vez y se reutiliza. */
function alias_user(PDO $pdo, int $story_id, int $user_id): string {
    $st = $pdo->prepare("SELECT alias FROM thread_aliases WHERE story_id=? AND user_id=?");
    $st->execute([$story_id, $user_id]);
    if ($a = $st->fetchColumn()) return $a;
    $alias = alias_random();
    $pdo->prepare("INSERT IGNORE INTO thread_aliases (story_id, user_id, alias) VALUES (?,?,?)")
        ->execute([$story_id, $user_id, $alias]);
    return $alias;
}

/* ---- idioma de un texto (envios) ------------------------------------
 * Deteccion barata por palabras frecuentes y distintivas de cada idioma de la interfaz: sirve para titular + entradilla.
 * Devuelve el codigo (es, en, pt, ca, gl, eu, fr, de, it, tr) o '' si no esta claro (texto corto o empate): '' = se ve para todos. */
function lang_detect(string $text): string {
    // [palabras casi exclusivas del idioma (pesan 3), palabras frecuentes que comparte con otros (pesan 1)]
    static $W = [
        'es' => [['y','el','los','las','del','es','para','por','como','pero','también','muy','sus','más','ya','entre','lo','su','este','esta','hay','con','una'], ['de','en','un','a','no','se','al','que','la']],
        'en' => [['the','and','of','to','is','that','for','with','are','this','by','from','was','it','be','as','at','have','not','an','has','their','which'], ['a','in','on']],
        'pt' => [['não','é','uma','um','você','também','já','são','pelo','pela','mais','ao','com','isso','muito','em','dos','das'], ['o','a','os','as','de','do','da','e','que','para','como','nas','nos']],
        'ca' => [['els','les','amb','és','per','aquest','aquesta','també','molt','dels','més','però','són','ho','hi','i','seva','seu'], ['el','la','de','del','que','en','un','una','al','no','ha']],
        'gl' => [['unha','non','máis','ao','cos','polo','pola','tamén','xa','moi','coa','cando','mentres','ata'], ['de','en','un','con','o','a','os','as','e','do','da','das','dos','que','para','nas','nos']],
        'eu' => [['eta','da','du','ez','dira','ere','bere','dute','izan','zen','bat','hau','hori','egin','dago','ditu','edo','baina','zer','oso','dela','zuen','izango','arte'], []],
        'fr' => [['les','des','est','pour','dans','qui','pas','sur','une','ce','et','du','au','plus','avec','par','sont','cette','à','elle','ne'], ['le','la','de','un','que','en','il','se','on']],
        'de' => [['der','die','das','und','ist','nicht','mit','für','ein','eine','den','dem','von','zu','sich','auf','auch','wird','werden','sind','im','als','bei','nach','über','hat','einer','einem'], ['es','an']],
        'it' => [['il','gli','della','nel','che','è','sono','anche','più','alla','dei','delle','essere','si','da','di','nella','sul'], ['la','le','un','una','con','del','per','in','al','lo','e','non']],
        'tr' => [['ve','bir','bu','için','ile','çok','daha','olarak','gibi','ama','var','yok','değil','olan','sonra','kadar','veya','her','göre','ise','nasıl','neden','oldu','yeni','ilk','büyük','karşı','üzerine','değil','şu','hem'], ['da','de','ne','mi','ki','o','en','ya']],
    ];
    $t = mb_strtolower($text);
    preg_match_all('/\p{L}+/u', $t, $m);
    $words = $m[0];
    if (count($words) < 6) return '';
    $cnt = array_count_values($words);
    $score = [];
    foreach ($W as $l => [$dist, $comun]) {
        $n = 0;
        foreach ($dist as $w) $n += 3 * ($cnt[$w] ?? 0);
        foreach ($comun as $w) $n += $cnt[$w] ?? 0;
        $score[$l] = $n;
    }
    arsort($score);
    $best = array_key_first($score); $vals = array_values($score);
    if ($vals[0] < 3 || $vals[0] <= $vals[1]) return '';   // poco claro o empate
    return $best;
}
/** Idioma para un envio nuevo: el de la fuente del catalogo si el dominio esta (sources.lang), si no, detectado del texto. */
/** <select name="lang"> para elegir el idioma de un envio (enviar, editar): «detectar» ('' → lang_for_story al guardar), los diez
 *  idiomas y «otro» ('??'). Nombres traducidos a la interfaz, codigos estables. $value: el actual o el detectado para proponerlo. */
function lang_select(string $value = '', string $name = 'lang'): string {
    $names = ['es' => 'Español', 'ca' => 'Catalán', 'eu' => 'Euskera', 'gl' => 'Gallego', 'en' => 'Inglés', 'fr' => 'Francés', 'de' => 'Alemán', 'it' => 'Italiano', 'pt' => 'Portugués', 'tr' => 'Turco'];
    $h = "<select name=\"" . h($name) . "\"><option value=\"\"" . ($value === '' ? ' selected' : '') . ">" . t('detectar automáticamente') . "</option>";
    foreach (array_keys(DDD_LANGS) as $c) $h .= "<option value=\"$c\"" . ($value === $c ? ' selected' : '') . ">" . h(t($names[$c])) . "</option>";
    return $h . "<option value=\"??\"" . ($value === '??' ? ' selected' : '') . ">" . t('otro idioma') . "</option></select>";
}
/** Idioma elegido en un formulario: un codigo de DDD_LANGS o '??'; cualquier otra cosa → '' (detectar). */
function lang_post(string $name = 'lang'): string { $v = post($name); return (isset(DDD_LANGS[$v]) || $v === '??') ? $v : ''; }
/** Etiqueta con el idioma de un envio en la fila de meta, solo cuando se sabe y no es el de la interfaz (asi no hace ruido). */
function lang_tag(array $s): string {
    $l = (string)($s['lang'] ?? '');
    if ($l === '' || $l === '??' || $l === lang() || !isset(DDD_LANGS[$l])) return '';
    return " · <span class=\"lang\" title=\"" . h(t('Idioma') . ': ' . DDD_LANGS[$l]) . "\">" . $l . "</span>";
}
function lang_for_story(PDO $pdo, ?string $url, string $title, ?string $content): string {
    $host = source_of($url);
    if ($host) {
        try {
            $st = $pdo->prepare("SELECT lang FROM sources WHERE lang <> '' AND SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(home, 'https://', ''), 'http://', ''), 'www.', ''), '/', 1) = ? LIMIT 1");
            $st->execute([$host]);
            if ($l = $st->fetchColumn()) return (string)$l;
        } catch (Throwable $e) {}
    }
    return lang_detect($title . ' ' . (string)$content);
}
/** Trozo SQL para las listas de envios: con «solo en mi idioma» marcado, fuera los de otro idioma (los sin idioma se ven). */
function stories_mylang_sql(string $alias = 's'): string {
    if (!function_exists('fuentes_solo_mi_idioma')) require_once __DIR__ . '/fuentes.php';
    if (!fuentes_solo_mi_idioma()) return '';
    $l = preg_replace('/[^a-z]/', '', lang());
    return " AND $alias.lang IN ('', '??', '$l')";
}
/** Cron: pone idioma a los envios que aun no lo tienen (los anteriores a migra-092), unos pocos por pasada. */
function lang_tick(PDO $pdo): array {
    try {
        $rows = $pdo->query("SELECT story_id, url, title, content FROM stories WHERE lang = '' ORDER BY story_id DESC LIMIT 40")->fetchAll();
    } catch (Throwable $e) { return []; }
    if (!$rows) return [];
    $n = 0; $up = $pdo->prepare("UPDATE stories SET lang = ? WHERE story_id = ?");
    foreach ($rows as $r) {
        $l = lang_for_story($pdo, $r['url'], (string)$r['title'], (string)$r['content']);
        if ($l === '') $l = '??';   // no se sabe: se marca para no volver a intentarlo (se ve para todos igual que '')
        $up->execute([$l, $r['story_id']]); $n++;
    }
    return ["idioma de envios: $n revisados"];
}

/* ---- aura -> halo --------------------------------------------------- */
function halo_style(float $aura): string {
    $max = (float)cfg('aura_max', 50);
    $t = max(0.0, min(1.0, ($aura - 5) / max(1, $max - 5)));   // 5 -> 0, 50 -> 1
    $op = 0.25 + 0.75 * $t;
    return "opacity:" . number_format($op, 2, '.', '') . ";border-width:" . (1 + (int)round(2 * $t)) . "px";
}

/* ---- iconos SVG inline (sin terceros) ---------------------------- */
function icon(string $n): string {
    $p = [
        'home'   => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/>',
        'cand'   => '<path d="M12 20V6"/><path d="m6 12 6-6 6 6"/>',
        'new'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 8v4.5l3 2"/>',
        'radar'  => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><path d="M12 12 18 6"/><circle cx="15.5" cy="9.2" r="1" fill="currentColor"/>',
        'art'    => '<path d="M6 3h9l4 4v14H6z"/><path d="M9 12h6M9 16h6"/>',
        'stats'  => '<path d="M4 20h16"/><path d="M7 16V10"/><path d="M12 16V5"/><path d="M17 16v-3"/>',
        'proc'   => '<path d="M3 12h4l3-7 4 14 3-7h4"/>',
        'users'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M15.5 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M17 13.6a6.5 6.5 0 0 1 4.5 6.4"/>',
        'msg'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'chat'   => '<path d="M4 5h11a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H9l-4 3v-3H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/><path d="M19 9h1a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-1v3l-4-3h-3"/>',
        'reply'  => '<path d="M20 12a8 8 0 0 1-11.6 7.1L4 20l1.1-3.9A8 8 0 1 1 20 12z"/>',
        'up'     => '<path d="M12 19V5"/><path d="m6 11 6-6 6 6"/>',
        'down'   => '<path d="M12 5v14"/><path d="m6 13 6 6 6-6"/>',
        'link'   => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'plus'   => '<path d="M12 5v14M5 12h14"/>',
        'edit'   => '<path d="M4 20h4L18.5 9.5l-4-4L4 16z"/><path d="m12.5 7.5 4 4"/>',   // lapiz: caja «Editar envio» del hilo (05/10/2026)
        'pin'    => '<path d="M9 4h6l-1 6 3 3H7l3-3z"/><path d="M12 13v7"/>',   // chincheta: destacar noticia (migra-111); relleno via CSS cuando esta destacada
        'skin'   => '<path d="M12 3a9 9 0 0 0 0 18c1.5 0 2-1 2-2s-1-1.5-1-2.5 1-1.5 2-1.5h1.5A4.5 4.5 0 0 0 21 10.5C21 6.4 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10.5" cy="7" r="1"/><circle cx="15.5" cy="7.5" r="1"/>',
        'theme'  => '<circle cx="12" cy="12" r="8.5"/><path d="M12 3.5v17"/><path d="M12 3.5a8.5 8.5 0 0 1 0 17z" fill="currentColor" stroke="none"/>',
        'lang'   => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c3 3 3 14 0 17"/><path d="M12 3.5c-3 3-3 14 0 17"/>',
        'share'  => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.4M8.2 13.2l7.6 4.4"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'news'   => '<rect x="3" y="5" width="15" height="15" rx="2"/><path d="M18 9h2a1 1 0 0 1 1 1v8a2 2 0 0 1-2 2"/><path d="M7 9h7M7 13h7M7 17h4"/>',
        'edit'   => '<path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17z"/><path d="m13.5 8.5 3 3"/>',
        'fav'    => '<path d="M6 4h12v17l-6-4.5L6 21z"/>',
        'focus'  => '<path d="M4 9V5h4M20 9V5h-4M4 15v4h4M20 15v4h-4"/><path d="M9 10h6M9 14h4"/>',   // modo sin distracciones: marco con dos lineas de texto
        'agg'    => '<circle cx="5" cy="6" r="1.4" fill="currentColor" stroke="none"/><path d="M9.5 6H20"/><circle cx="5" cy="12" r="1.4" fill="currentColor" stroke="none"/><path d="M9.5 12H20"/><circle cx="5" cy="18" r="1.4" fill="currentColor" stroke="none"/><path d="M9.5 18H20"/>',   // agregadores: lista de fuentes
        'tag'    => '<path d="M3 11V4h7l10 10-7 7z"/><circle cx="7.5" cy="8.5" r="1.3" fill="currentColor" stroke="none"/>',   // temas
        'comm'   => '<circle cx="12" cy="8" r="3.2"/><circle cx="5" cy="11" r="2.2"/><circle cx="19" cy="11" r="2.2"/><path d="M6.5 20a5.5 5.5 0 0 1 11 0"/><path d="M2 17.5a3.5 3.5 0 0 1 3-2.3M22 17.5a3.5 3.5 0 0 0-3-2.3"/>',   // comunidades
        'play'   => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M10 9.2v5.6l5-2.8z" fill="currentColor" stroke="none"/>',   // canales de YouTube: pantalla con triangulo (generico, no el logotipo)
        'letter' => '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5"/><path d="M9 12h7M9 16h7"/>',   // newsletter / substack: hoja con esquina doblada   // marcador: relleno via CSS cuando esta guardado
        'bell'   => '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20a2 2 0 0 0 4 0"/><path d="M12 3v2"/>',   // avisos (campanita)
        'help'   => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1 1-1 1.7"/><path d="M12 17h.01"/>',   // interrogacion: «Como empezar» en el Chat (migra-122)
        'mega'   => '<path d="M3 10v4h3l7 4V6l-7 4z"/><path d="M17 9a4.5 4.5 0 0 1 0 6"/><path d="M6 14v4h2"/>',   // megafono: mensaje a la comunidad en el Chat (migra-121)
        'sun'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5V5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8"/>',   // me gusta (comentarios)
        'tear'   => '<path d="M12 3.5c3.2 4.6 6 7.8 6 11.2a6 6 0 0 1-12 0c0-3.4 2.8-6.6 6-11.2z"/>',   // no me gusta (comentarios): lagrima; relleno via CSS cuando es la tuya
        'eye'    => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/>',   // lecturas del hilo (migra-120)
        'out'    => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',   // clics de salida al medio (migra-120)
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.3-4.3"/>',   // lupa: buscador (/buscar, migra-085)
    ][$n] ?? '';
    return "<svg class=\"i\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">$p</svg>";
}

/** Enlace «editar» de moderación junto a un envío o artículo (solo mods y admin); lleva a /editar. */
function mod_edit_link(array $s): string {
    if (!is_mod()) return '';
    return "<a class=\"modlink\" href=\"/editar?id={$s['story_id']}\" title=\"" . t('editar (moderación)') . "\">" . icon('edit') . "<span>" . t('editar') . "</span></a>";
}
/** El autor puede RETIRAR su envio (sin editarlo) fuera del plazo de edicion: mientras siga en el radar (o pendiente de moderacion)
 *  y nadie lo haya comentado. Sin limite de tiempo ni de aura: un error no deberia quedarse ahi una semana (migra-123, 08/10/2026). */
function can_retire_own_story(array $s): bool {
    $u = user();
    if (!$u || empty($s['user_id']) || (int)$s['user_id'] !== (int)$u['user_id']) return false;
    return in_array($s['status'], ['pending_mod', 'nuevas'], true) && (int)($s['comments'] ?? 0) === 0;
}
/** Enlace «editar» para el autor de un envío dentro del plazo (misma pagina /editar, campos limitados); pasado el plazo, «retirar»
 *  si aun puede (can_retire_own_story). '' si no procede. */
function own_edit_link(array $s): string {
    if (is_mod()) return '';
    if (can_edit_own_story($s)) return "<a class=\"modlink\" href=\"/editar?id={$s['story_id']}\" title=\"" . t('puedes corregirlo durante %d min', (int)cfg('story_edit_minutes', 15)) . "\">" . icon('edit') . "<span>" . t('editar') . "</span></a>";
    if (can_retire_own_story($s)) return "<a class=\"modlink\" href=\"/editar?id={$s['story_id']}\" title=\"" . t('mientras esté en el radar y nadie lo haya comentado') . "\">" . icon('edit') . "<span>" . t('retirar') . "</span></a>";
    return '';
}

/* ---- layout (estilo timeline) -------------------------------------- */
/** URL absoluta (esquema + host) de una ruta del sitio, para Open Graph y compartir. */
function abs_url(string $path): string {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
}
/** Metas Open Graph: titulo, descripcion, imagen (absoluta; por defecto el icono de 512 en vigor) y url (la actual). */
function og_tags(string $title, string $descr = '', string $image = '', string $url = ''): string {
    $img = $image !== '' ? (preg_match('#^https?://#i', $image) ? $image : abs_url($image)) : abs_url(icon_src(512));
    $h  = "<meta property=\"og:site_name\" content=\"" . h(cfg('site_name', 'Deltha')) . "\"><meta property=\"og:type\" content=\"website\">";
    $h .= "<meta property=\"og:title\" content=\"" . h($title) . "\">";
    if ($descr !== '') $h .= "<meta property=\"og:description\" content=\"" . h(mb_strimwidth($descr, 0, 200, '…')) . "\">";
    $h .= "<meta property=\"og:image\" content=\"" . h($img) . "\"><meta property=\"og:url\" content=\"" . h($url ?: abs_url((string)($_SERVER['REQUEST_URI'] ?? '/'))) . "\">";
    $h .= "<meta name=\"twitter:card\" content=\"" . ($image !== '' ? 'summary_large_image' : 'summary') . "\">";
    return $h;
}
/* ---- buscadores e IA (robots) -----------------------------------------
 * Deltha no se indexa por defecto: cada pagina sale con <meta name="robots" content="noindex, nofollow, noarchive">
 * y la cabecera X-Robots-Tag equivalente. Solo se indexan las paginas escaparate (que-es, algoritmo, normas,
 * privacidad: definen DDD_INDEX) y los articulos cuyo autor lo ha pedido (stories.indexable, si config.index_articles = 1),
 * que llaman a robots_index(true) antes de page_header(). Los rastreadores de IA se vetan aparte en /robots.txt.
 * ------------------------------------------------------------------ */
function robots_index(?bool $set = null): bool {
    static $v = null;
    if ($set !== null) $v = $set;
    return $v ?? defined('DDD_INDEX');
}
function robots_meta(): string {
    $c = robots_index() ? 'index, follow, noai, noimageai' : 'noindex, nofollow, noarchive, noai, noimageai';
    if (!headers_sent()) header('X-Robots-Tag: ' . $c);
    return "<meta name=\"robots\" content=\"$c\">";
}
/** ¿Puede este articulo entrar en los buscadores? Tiene que ser articulo, estar visible, marcado por el autor y permitido en config. */
function story_indexable(array $s): bool {
    return cfg('index_articles', '1') === '1' && ($s['content_type'] ?? '') === 'article' && (int)($s['indexable'] ?? 0) === 1
        && in_array($s['status'] ?? '', ['nuevas', 'destacadas', 'archived'], true);
}
/** $head: etiquetas extra para <head> (p. ej. el meta refresh de /procesos); $doc: titulo del documento (<title>) cuando
 *  no debe ser el de la barra (las paginas fijas llevan «Sobre Deltha» en la barra y su titulo real en la pestaña del navegador). */
/** <meta refresh> a la misma URL con auto=1, para que la analitica no cuente las recargas automaticas como paginas vistas (ana_hit) */
/** La propia pagina con auto=1: la recarga automatica no cuenta como visita nueva en la analitica. */
function refresh_url(): string {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string)(parse_url($uri, PHP_URL_PATH) ?: '/');
    parse_str((string)(parse_url($uri, PHP_URL_QUERY) ?? ''), $q);
    $q['auto'] = '1';
    return $path . '?' . http_build_query($q);
}
function refresh_meta(int $seconds): string {
    return '<meta http-equiv="refresh" content="' . $seconds . ';url=' . h(refresh_url()) . '">';
}
/** Miga delante del titulo de la cabecera: «Originales › Hilo», con la primera parte enlazada para volver atras. La fija la pagina antes
 *  de page_header() (hoy, el hilo de un original). */
$DDD_CRUMB = null;
function page_crumb(string $href, string $label): void { global $DDD_CRUMB; $DDD_CRUMB = [$href, $label]; }
/** Aviso a la comunidad (13/10/2026, admin → Avisos): un texto de hasta 150 palabras que, mientras este activado, sustituye a todos los
 *  avisos grises de cabecera (<p class="notice">) de las paginas publicas. Se hace al vuelo: page_header() abre un buffer y
 *  page_footer() cambia los notice por el aviso antes de enviar (salvo los <p class="notice keep">, que llevan una accion y se quedan
 *  debajo del aviso). Admin y moderacion quedan fuera. Vacio = no hay aviso activo. */
function aviso_comunidad(): string {
    static $h = null;
    if ($h !== null) return $h;
    $txt = trim((string)cfg('aviso_txt', ''));
    if ((string)cfg('aviso_on', '0') !== '1' || $txt === '') return $h = '';
    // (17/10/2026) se puede cerrar con la × (pedido por Kosako): cookie ddd_av con la huella del texto, un año; si el aviso cambia, vuelve a salir
    if (($_COOKIE[DDD_AVISO_COOKIE] ?? '') === aviso_clave($txt)) return $h = '';
    $html = saltos(h($txt));
    $html = preg_replace('~(https?://[^\s<]+)~u', '<a href="$1" rel="noopener">$1</a>', $html);   // enlaces sueltos, pinchables
    return $h = "<p class=\"notice aviso\"><a class=\"x\" href=\"/cerrar-aviso?k=" . aviso_clave($txt) . "\" rel=\"nofollow\" title=\"" . h(t('cerrar este aviso')) . "\" aria-label=\"" . h(t('cerrar este aviso')) . "\">×</a>$html</p>";
}
const DDD_AVISO_COOKIE = 'ddd_av';
/** Huella corta del texto del aviso: es lo que guarda la cookie de «ya lo he cerrado» y lo que comprueba /cerrar-aviso. */
function aviso_clave(string $txt): string { return substr(sha1($txt), 0, 12); }
function page_header(string $title, string $tab = '', string $head = '', string $doc = ''): void {
    global $DDD_TAB; $DDD_TAB = $tab;
    $robots = robots_meta();   // antes del primer echo: tambien manda la cabecera X-Robots-Tag
    ana_hit();                 // analitica propia: una fila por pagina vista (GET, sin bots, sin admin)
    $u = user();
    $theme = theme();   // null = sigue al sistema
    echo "<!doctype html><html lang=\"" . lang() . "\"" . ($theme ? " data-theme=\"$theme\"" : "") . " data-skin=\"" . skin() . "\" data-fam=\"" . skin_fam() . "\"" . (foco() ? ' data-foco="1"' : '') . "><head><meta charset=\"utf-8\">";
    echo "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1,viewport-fit=cover\">";
    echo "<meta name=\"color-scheme\" content=\"" . ($theme ?: 'light dark') . "\">";
    echo "<link rel=\"icon\" href=\"" . icon_src(64) . "\"><link rel=\"apple-touch-icon\" href=\"" . icon_src(180) . "\">";
    // PWA: manifiesto dinamico (lleva los iconos en vigor), color de barra y registro del service worker
    echo "<link rel=\"manifest\" href=\"/app/manifest.php\"><meta name=\"theme-color\" content=\"#ffffff\" media=\"(prefers-color-scheme: light)\"><meta name=\"theme-color\" content=\"#000000\" media=\"(prefers-color-scheme: dark)\">";
    echo "<meta name=\"apple-mobile-web-app-capable\" content=\"yes\"><meta name=\"apple-mobile-web-app-title\" content=\"" . h(cfg('site_name', 'Deltha')) . "\"><meta name=\"mobile-web-app-capable\" content=\"yes\">";
    echo "<script src=\"/app/register.js?v=" . DDD_CSS_VER . "\" data-v=\"" . DDD_CSS_VER . "\" defer></script>";
    echo "<script src=\"/app/tabs.js?v=" . DDD_CSS_VER . "\" defer></script>";   // encaja las pestañas de arriba en movil
    echo "<script src=\"/app/scroll.js?v=" . DDD_CSS_VER . "\" defer></script>";   // votar/guardar/comentar: vuelve al punto de scroll en que estabas (sin JS, manda el ancla)
    echo "<script src=\"/app/foto.js?v=" . DDD_CSS_VER . "\" defer></script>";   // previsualiza la foto elegida antes de enviar (enviar, articulo, editar)
    echo "<script src=\"/app/escribir.js?v=" . DDD_CSS_VER . "\" defer></script>";   // cursor detras del «#N» y refresco del Chat que no se lleva lo escrito
    if ($u && avisos_on() && (int)cfg('notif_poll_sec', 60) > 0) echo "<script src=\"/app/avisos.js?v=" . DDD_CSS_VER . "\" data-every=\"" . (int)cfg('notif_poll_sec', 60) . "\" defer></script>";   // refresca la burbuja de avisos sin recargar
    echo "<title>" . h($doc ?: $title) . " · " . h(cfg('site_name')) . "</title>";
    echo $robots;   // noindex salvo paginas escaparate y articulos que el autor abre a buscadores
    // Open Graph: lo que enseñan los cuadros de «compartir» (Windows, WhatsApp, Telegram...). Por defecto el icono del sitio en vigor;
    // una pagina (el hilo) puede mandar los suyos en $head y entonces no se repiten.
    if (!str_contains($head, 'og:image')) $head = og_tags($doc ?: $title) . $head;
    echo "<link rel=\"stylesheet\" href=\"/css/ddd.css?v=" . DDD_CSS_VER . "\">" . $head . "</head><body>";
    require_once __DIR__ . '/lateral.php';
    echo "<div class=\"cols\">";   // escritorio: rail | shell | side (las columnas laterales van ocultas en movil)
    rail($tab);
    echo "<div class=\"shell\">";
    echo "<header class=\"top\"><a class=\"logo\" href=\"/\" aria-label=\"" . h(cfg('site_name')) . "\"><img src=\"" . logo_src('light') . "\" alt=\"\" class=\"l\"><img src=\"" . logo_src('dark') . "\" alt=\"\" class=\"d\"></a>";
    global $DDD_CRUMB;
    echo "<span class=\"ttl\">" . ($DDD_CRUMB ? "<a class=\"crumb\" href=\"" . h($DDD_CRUMB[0]) . "\">" . h($DDD_CRUMB[1]) . "</a><span class=\"crumbsep\"> › </span>" : '') . h($title) . "</span>";
    if (buscar_on()) echo "<a class=\"lupa\" href=\"/buscar\" title=\"" . t('Buscar') . "\" aria-label=\"" . t('Buscar') . "\">" . icon('search') . "</a>";   // movil y tablet; en escritorio la caja va en el rail
    if ($u) echo avisos_bell('top') . "<a class=\"me\" href=\"/perfil\" title=\"" . t('perfil') . "\">" . ($u['avatar'] ? "<span class=\"av photo mini\" style=\"background-image:url('/media/avatars/" . h($u['avatar']) . "')\"></span>" : icon('user')) . "<span>" . h($u['login']) . "</span></a>";
    else    echo "<a class=\"me\" href=\"/login\">" . icon('user') . "<span>" . t('entrar') . "</span></a>";
    echo "</header>";
    nav_tabs($tab, 'tabs-top');
    nav_fuentes($tab);
    echo "<main>";
    if (aviso_comunidad() !== '' && !preg_match('~/(admin|moderacion)(\.php)?$~', (string)$_SERVER['SCRIPT_NAME'])) { $GLOBALS['DDD_AVISO_OB'] = true; ob_start(); }   // aviso a la comunidad: se sustituyen los notice en page_footer()
}
/* Dos menus: arriba el contenido (las dos portadas, el radar del que se nutren y los foros), abajo la comunidad y las
 * herramientas. href null = pestaña anunciada pero sin pagina todavia (Mensajes). */
function nav_tabs(string $tab, string $cls): void {
    $tabs = in_array($cls, ['tabs-top', 'rail-top'], true) ? [
        'medios'       => ['/?portada=medios', t('Medios'), 'news'],
        'substacks'    => ['/?portada=substacks', t('Substacks'), 'letter'],
        'canales'      => ['/?portada=canales', t('Canales'), 'play'],
        'radar'        => ['/radar', t('Radar'), 'radar'],
        'foros'        => ['/foros', t('Foros'), 'art'],   // antes «Originales» (migra-159): dentro, una pestaña por foro
        'chat'         => ['/chat', t('Chat'), 'chat'],
    ] : [
        'temas'        => ['/temas', t('Temas'), 'tag'],            // solo en el rail (01/10): en modo sin distracciones no hay columna derecha, que era
        'comunidades'  => ['/comunidades', t('Comunidades'), 'comm'],   // donde se veian temas y comunidades; en movil ya estan en la franja nav.tabs-fuentes
        'usuarios'     => ['/usuarios', t('Usuarios'), 'users'],
        'plantilla'    => ['/plantilla', t('Skin'), 'skin'],   // solo en el muelle (movil): el rail ya tiene su desplegable
        'procesos'     => ['/procesos', t('Procesos'), 'proc'],
        'estadisticas' => ['/estadisticas', t('Estadísticas'), 'stats'],
        'analitica'    => ['/analitica', t('Analítica'), 'stats'],   // solo registrados y solo en el rail (en movil, desde Estadisticas); la completa, desde aura analytics_full_aura (ana_full)
        'mensajes'     => ['/mensajes', t('Mensajes'), 'msg'],
    ];
    if (!portada_canales_on()) unset($tabs['canales']);
    if ($cls !== 'tabs-bottom') unset($tabs['plantilla']);
    if ($cls !== 'rail-bottom') unset($tabs['temas'], $tabs['comunidades']);
    if (isset($tabs['analitica']) && ($cls === 'tabs-bottom' || !user() || !ana_on())) unset($tabs['analitica']);
    if (isset($tabs['chat']) && (string)cfg('chat_enabled', '1') !== '1') unset($tabs['chat']);
    if (isset($tabs['chat']) && $cls === 'rail-top') $tabs['chat'][1] = t('Chat / Ayuda');   // en escritorio (rail) la pestaña anuncia tambien la ayuda; en movil sigue «Chat», que no cabe mas (07/10/2026)
    // pestañas de arriba: Radar va en el centro exacto en movil (v160). Lo de antes y lo de despues se envuelve en dos grupos
    // (span.g, display: contents salvo en movil) que ocupan mitades iguales; Radar lleva la clase «radar» y se destaca un poco.
    $keys = array_keys($tabs); $ri = $cls === 'tabs-top' ? array_search('radar', $keys, true) : false;
    $ctr = $ri !== false && $ri > 0 && $ri < count($keys) - 1;
    echo "<nav class=\"$cls" . ($ctr ? ' ctr' : '') . "\">" . ($ctr ? '<span class="g l">' : '');
    foreach ($tabs as $k => [$href, $label, $ic]) {
        $on = $k === $tab ? ' class="on"' : '';
        if ($ctr && $k === 'radar') { echo "</span><a class=\"radar" . ($k === $tab ? ' on' : '') . "\" href=\"" . h($href) . "\">" . icon($ic) . "<span>" . h($label) . "</span></a><span class=\"g r\">"; continue; }
        if ($href === null) { echo "<span class=\"soon\" title=\"" . t('pronto') . "\">" . icon($ic) . "<span>" . h($label) . "</span></span>"; continue; }
        $badge = ($k === 'mensajes' && ($n = unread_msgs()) > 0) ? "<i class=\"badge\">" . ($n > 99 ? '99+' : $n) . "</i>" : '';
        echo "<a href=\"" . h($href) . "\"$on>" . icon($ic) . $badge . "<span>" . h($label) . "</span></a>";
    }
    echo ($ctr ? '</span>' : '') . "</nav>";
}
/** Franja bajo las pestañas, solo cuando no hay rail (movil y tablet, < 1100 px): lo que en escritorio esta en el rail y en la columna
 *  derecha — Mis fuentes y Todas las fuentes (08/10/2026; antes una entrada por tipo), Temas y Comunidades — con icono y rotulo como el muelle de abajo. */
function nav_fuentes(string $tab): void {
    global $pdo; require_once __DIR__ . '/fuentes.php';
    $tabs = [];
    if (fuentes_on() && fuentes_tipos($pdo)) { $tabs['misfuentes'] = ['/mis-fuentes', t('Mis fuentes'), 'news']; $tabs['fuentes'] = ['/fuentes', t('Todas las fuentes'), 'agg']; }
    $tabs['temas'] = ['/temas', t('Temas'), 'tag'];
    $tabs['comunidades'] = ['/comunidades', t('Comunidades'), 'comm'];
    echo "<nav class=\"tabs-fuentes\">";
    foreach ($tabs as $k => [$href, $label, $ic]) echo "<a href=\"" . h($href) . "\" title=\"" . h($label) . "\"" . ($k === $tab ? ' class="on"' : '') . ">" . icon($ic) . "<span>" . h($label) . "</span></a>";   // icono y rotulo debajo (v159); «Mis canales» y «Mis agregadores» solo salen si estan encendidos en admin > Config
    echo "</nav>";
}
/** Mensajes privados sin leer del usuario actual (una consulta por pagina, cacheada) */
function unread_msgs(): int {
    static $n = null; global $pdo;
    if ($n !== null) return $n;
    $n = 0;
    if (user() && (string)cfg('msg_enabled', '1') === '1') {
        try { $st = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE to_id = ? AND read_at IS NULL"); $st->execute([user()['user_id']]); $n = (int)$st->fetchColumn(); }
        catch (Throwable $e) { /* tabla aun sin crear */ }
    }
    return $n;
}
function footer_links(): string {
    $h  = "<a href=\"/que-es\">" . t('¿Qué es Deltha?') . "</a> · <a href=\"/empezar\">" . t('Cómo empezar') . "</a> · <a href=\"https://contralgoritmia.com/\" rel=\"noopener\">" . t('Manifiesto contralgorítmico') . "</a> · <a href=\"/privacidad\">" . t('Privacidad y contacto') . "</a> · <a href=\"/cookies\">" . t('Cookies y JavaScript') . "</a> · <a href=\"/normas\">" . t('Normas de uso') . "</a> · <a href=\"/algoritmo\">" . t('Cómo funciona el δ') . "</a> · <a href=\"/comunidades\">" . t('Comunidades') . "</a>";
    // fuentes externas pintadas con Deltha: el catalogo entero (/fuentes, «Todas las fuentes»; antes un enlace por tipo)
    global $pdo; require_once __DIR__ . '/fuentes.php';
    if (fuentes_on() && fuentes_tipos($pdo)) $h .= " · <a href=\"/fuentes\">" . t('Todas las fuentes') . "</a>";
    $h .= " · <a href=\"/rss?portada=" . portada_actual() . "\" type=\"application/rss+xml\">RSS</a>";   // el feed de la portada que se esta viendo (o la ultima elegida)
    $h .= " · " . t('Sin cookies para quien no inicia sesión (salvo las de skin, tema, idioma y portada, si los eliges) · sin rastreo · sin algoritmo oculto');
    if (is_mod())   $h .= " · <a href=\"/moderacion\">" . t('moderación') . (open_reports() ? " (" . open_reports() . ")" : "") . "</a>";
    if (is_admin()) $h .= " · <a href=\"/admin\">admin</a>";
    return $h;
}
function page_footer(): void {
    if (!empty($GLOBALS['DDD_AVISO_OB'])) {   // aviso a la comunidad (aviso_comunidad): cada <p class="notice"> de la pagina pasa a ser el aviso
        $GLOBALS['DDD_AVISO_OB'] = false;
        $cuerpo = (string)ob_get_clean();
        // los <p class="notice keep"> no se tocan: llevan una accion (el «Escribe el tuyo» de /foros se perdia mientras habia aviso, 16/10/2026)
        $re = '~<p class="notice(?![^"]*\\b(?:keep|aviso)\\b)(?: [^"]*)?">.*?</p>~su';   // tampoco el propio aviso ya insertado
        $cuerpo = preg_replace($re, aviso_comunidad(), $cuerpo, 1, $n);   // el primero pasa a ser el aviso…
        if (!$n) $cuerpo = aviso_comunidad() . $cuerpo;   // pagina sin aviso gris (o solo con keep): el aviso va arriba del todo
        else $cuerpo = preg_replace($re, '', $cuerpo);   // …y los demas se quitan: /usuarios y /mis-fuentes llevan dos y salia duplicado (16/10/2026)
        echo $cuerpo;
    }
    echo "</main>";
    if (!empty($GLOBALS['DDD_SKINPROP_FORM'])) echo $GLOBALS['DDD_SKINPROP_FORM'];   // formulario de «ver con mi/su plantilla» (skin_propio_link)
    echo "<a class=\"fab\" href=\"/enviar\" aria-label=\"" . t('Enviar') . "\">" . icon('plus') . "</a>";
    // pie en flujo (movil): al final de cada pagina
    echo "<footer class=\"inflow\"><a class=\"ctr\" href=\"https://contralgoritmia.com/\" rel=\"noopener\" title=\"" . t('Manifiesto contralgorítmico') . "\"><img src=\"/img/contralgoritmia.png\" alt=\"" . t('Sello contralgorítmico') . "\"></a>";
    echo "<p>" . footer_links() . "</p></footer>";
    echo "</div>";
    side();
    echo "</div>";   // .cols
    // muelle fijo (PC: enlaces + iconos; movil: solo iconos)
    echo "<div class=\"dock\">";
    nav_tabs(current_tab(), 'tabs-bottom');
    echo "<footer class=\"docked\"><p>" . footer_links() . "</p></footer></div>";
    if (youtube_embed_on()) echo "<script src=\"/app/video.js?v=" . DDD_CSS_VER . "\" defer></script>";   // videos de YouTube: al pulsar la miniatura, el reproductor en su sitio; sin JS el enlace abre YouTube
    echo "<script src=\"/app/share.js?v=" . DDD_CSS_VER . "\" defer></script>";   // «compartir»: copia el enlace (o abre el menu nativo); sin JS el enlace lleva al hilo
    echo "</body></html>";
}
function current_tab(): string { global $DDD_TAB; return $DDD_TAB ?? ''; }
/** Buscador interno (/buscar): lupa en la cabecera y caja en el rail. config.search_enabled (migra-085). */
function buscar_on(): bool { return (string)cfg('search_enabled', '1') === '1'; }
