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
define('DDD_SESSION', 'ddd_s');
define('DDD_CSS_VER', 93);   // subir al cambiar ddd.css

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

/* ---- sesion solo si ya hay cookie (nunca para anonimos) ------------ */
$USER = null;
session_name(DDD_SESSION);
if (isset($_COOKIE[DDD_SESSION])) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
    if (!empty($_SESSION['uid'])) {
        $st = $pdo->prepare("SELECT user_id, login, level, aura, avatar, bio, theme, skin, landing, lang FROM users WHERE user_id = ? AND verified = 1");
        $st->execute([$_SESSION['uid']]);
        $USER = $st->fetch() ?: null;
        if ($USER && PHP_SAPI !== 'cli') {   // ultima visita, como mucho una vez por minuto (para /estadisticas.php)
            $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ? AND (last_seen IS NULL OR last_seen < NOW() - INTERVAL 1 MINUTE)")
                ->execute([$USER['user_id']]);
        }
    }
}
require_once __DIR__ . '/i18n.php';
i18n_load();
require_once __DIR__ . '/bloqueos.php';   // medios bloqueados (source_blocked)

/* ---- presencia anonima: huella no reversible (ip+navegador+dia, HMAC truncado)
 * guardada unos minutos, solo para contar visitantes en /estadisticas.php.
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
function is_mod(): bool { $u = user(); return $u && in_array($u['level'], ['mod', 'admin'], true); }

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
// (las nuevas no subian a candidatas, ni revelado, ni archivo, ni decaimiento de aura).
if (PHP_SAPI !== 'cli' && !defined('DDD_PUBLIC') && !gate_open()) redirect('/delta/clave.php');   // /que-es.php, privacidad, normas y algoritmo son publicas (DDD_PUBLIC)

/* ---- helpers ------------------------------------------------------- */
/** Casilla «mostrar contraseña» para un formulario (la activa /app/pw.js; sin JS no aparece). */
function pw_toggle(): string {
    return "<span class=\"showpw\" hidden><label><input type=\"checkbox\"> mostrar contraseña</label></span><script src=\"/app/pw.js\" defer></script>";
}
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $to): never { header("Location: $to"); exit; }
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
    return $s < 3600 ? t('muere en %d min', intdiv($s, 60)) : t('muere en %d h', intdiv($s, 3600));
}
function vida_pct(string $published): int {
    $ttl = (int)cfg('thread_ttl_hours', 48) * 3600;
    $used = time() - strtotime($published);
    return max(0, min(100, 100 - (int)round($used * 100 / $ttl)));
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
    if (!is_mod() && (float)$u['aura'] <= (float)cfg('comm_min_aura', 10)) return 0;   // mods y admin: sin requisito de aura
    $st = $pdo->prepare("SELECT COUNT(*) FROM communities WHERE owner_id = ?"); $st->execute([$u['user_id']]);
    return is_admin() ? 99 : max(0, $max - (int)$st->fetchColumn());
}
function community_link(array $s): string {
    return "<a href=\"/comunidad.php?c=" . rawurlencode((string)$s['comm_slug']) . "\">" . h($s['comm_name']) . "</a>";
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
const DDD_COMM_PUBLIC = "('nuevas','candidatas','destacadas','archived')";   // fases en las que un envio se puede adoptar y se ve en la comunidad
/** Comunidades en las que esta un envio (principal primero), con quien la añadio. */
function story_communities(PDO $pdo, int $story_id): array {
    $st = $pdo->prepare("SELECT c.community_id, c.name, c.slug, c.color, c.owner_id, c.status, sc.added_by, sc.created, (c.community_id = s.community_id) AS main
                           FROM story_communities sc JOIN communities c ON c.community_id = sc.community_id JOIN stories s ON s.story_id = sc.story_id
                          WHERE sc.story_id = ? ORDER BY main DESC, c.name");
    $st->execute([$story_id]);
    return $st->fetchAll();
}
/** Comunidades que administra el usuario: las suyas; los mods y el admin, todas las visibles. */
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
    if (!$status || !in_array($status, ['nuevas', 'candidatas', 'destacadas', 'archived'], true)) return t('Ese envío no se puede añadir a una comunidad.');
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
/** A donde aterriza el usuario al entrar: '' = portada | t:ID | c:ID */
function landing_url(PDO $pdo, ?array $u): string {
    $l = (string)($u['landing'] ?? '');
    if (preg_match('/^t:(\d+)$/', $l, $m)) {
        $st = $pdo->prepare("SELECT 1 FROM topics WHERE topic_id = ?"); $st->execute([(int)$m[1]]);
        if ($st->fetchColumn()) return '/tema.php?t=' . (int)$m[1];
    } elseif (preg_match('/^c:(\d+)$/', $l, $m)) {
        $st = $pdo->prepare("SELECT slug FROM communities WHERE community_id = ? AND status = 'ok'"); $st->execute([(int)$m[1]]);
        if ($slug = $st->fetchColumn()) return '/comunidad.php?c=' . rawurlencode($slug);
    }
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
    try { $pdo->prepare("UPDATE comment_reports SET status = 'deleted' WHERE comment_id = ? AND status = 'open'")->execute([$id]); } catch (Throwable $e) {}
    return $st->rowCount() > 0;
}
/** El usuario actual puede editar este comentario suyo? Aura > comment_edit_min_aura y menos de comment_edit_minutes desde que lo publico. */
function can_edit_own_comment(array $c): bool {
    $u = user();
    if (!$u || empty($c['user_id']) || (int)$c['user_id'] !== (int)$u['user_id'] || !empty($c['deleted'])) return false;
    if ((float)$u['aura'] <= (float)cfg('comment_edit_min_aura', 10)) return false;
    return time() - strtotime($c['created']) < (int)cfg('comment_edit_minutes', 10) * 60;
}
/** Reportes de comentarios pendientes (para el enlace de moderacion) */
function open_reports(): int {
    static $n = null; global $pdo;
    if ($n !== null) return $n;
    try { $n = (int)$pdo->query("SELECT COUNT(DISTINCT comment_id) FROM comment_reports WHERE status = 'open'")->fetchColumn(); } catch (Throwable $e) { $n = 0; }
    try { $n += (int)$pdo->query("SELECT COUNT(DISTINCT msg_id) FROM chat_reports WHERE status = 'open'")->fetchColumn(); } catch (Throwable $e) {}
    return $n;
}

/* ---- paginas editables (tabla pages, admin) ------------------------- */
function page_get(PDO $pdo, string $slug): array {
    $st = $pdo->prepare("SELECT slug, title, body, updated FROM pages WHERE slug = ?");
    $st->execute([$slug]);
    return $st->fetch() ?: ['slug' => $slug, 'title' => ucfirst($slug), 'body' => '<p>Sin contenido todavía.</p>', 'updated' => null];
}
/** Cuerpo HTML con {site}, {ttl}, {gm}… sustituidos por la config actual (lista en admin -> Paginas) */
/** Numero en formato español, sin ceros sobrantes (0.05 -> 0,05; 1.00 -> 1; 2.50 -> 2,5) */
function num_es($v): string { $s = rtrim(rtrim(number_format((float)$v, 3, ',', '.'), '0'), ','); return $s === '' ? '0' : $s; }
/** «31 de diciembre de 2026» a partir de una fecha Y-m-d (para {auradl}) */
function fecha_larga(string $ymd): string {
    $t = strtotime($ymd) ?: time();
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return (int)date('j', $t) . ' de ' . $meses[(int)date('n', $t) - 1] . ' de ' . date('Y', $t);
}
function page_body(array $pg): string {
    return strtr($pg['body'], [
        '{site}' => h(cfg('site_name', 'Deltha')),
        '{ttl}'  => (string)(int)cfg('thread_ttl_hours', 48),
        '{gm}'   => (string)(int)cfg('ghost_minutes', 30),
        // parametros del algoritmo (pagina «Como funciona el δ»)
        '{cycle}'    => (string)(int)cfg('cycle_minutes', 5),
        '{minage}'   => (string)(int)cfg('delta_min_age_min', 30),
        '{minpub}'   => num_es(cfg('delta_min_publish', 1)),
        '{mincand}'  => num_es(cfg('delta_min_candidate', 0.5)),
        '{newttl}'   => (string)(int)cfg('nuevas_ttl_hours', 48),
        '{decay}'    => num_es(cfg('decay_per_halfhour', 0.05)),
        '{auraport}' => num_es(cfg('aura_portada', 0.5)),
        '{auraenv}'  => num_es(cfg('aura_envio', 0.1)),
        '{auracom}'  => num_es(cfg('aura_comentario', 0.02)),
        '{auracap}'  => num_es(cfg('aura_daily_cap', 2)),
        '{auradec}'  => num_es(cfg('aura_daily_decay', 0.5)),
        '{aurastart}'=> num_es(cfg('aura_start', 5)),
        '{auramax}'  => num_es(cfg('aura_max', 50)),
        '{commaura}' => num_es(cfg('comm_min_aura', 10)),
        '{commmax}'  => (string)(int)cfg('comm_max_per_user', 3),
        '{editaura}' => num_es(cfg('comment_edit_min_aura', 10)),
        '{editmin}'  => (string)(int)cfg('comment_edit_minutes', 10),
        // pagina «¿Que es Deltha?»
        '{skins}'    => (string)count(DDD_SKINS),
        '{auradl}'   => fecha_larga(cfg('aura_deadline', '2026-12-31')),
    ]);
}

/* ---- plantillas visuales ----------------------------------------------
 * editorial (la clasica, con mas aire) | comunidad (tarjetas, usuarios)
 * | delta (identidad grafica: espiga, δ visible, fotos en gris) | flujo (fotos laterales, denso).
 * Orden de preferencia: ?skin= en la URL (probar sin guardar) > communities.skin (en la comunidad y sus hilos) > users.skin > cookie ddd_skin (solo anonimos que eligieron) > config.skin_default. */
const DDD_SKINS = ['editorial' => 'Editorial', 'comunidad' => 'Comunidad', 'delta' => 'Delta', 'flujo' => 'Flujo', 'retro' => 'Retrocomputer', 'manga' => 'Manga', 'punk' => 'Punk', 'popart' => 'Pop Art', 'zen' => 'Zen', 'orange' => 'Orange', 'foro' => 'Foro',
    'hyperpop' => 'Hyperpop / Y2K', 'neobrutal' => 'Neo-Brutal', 'stickerbomb' => 'Stickerbomb', 'riso' => 'Riso / Zine', 'aero' => 'Frutiger Aero', 'hud' => 'Game HUD', 'acid' => 'Acid Web', 'pixel' => 'Pixel / 16-bit', 'dopamine' => 'Dopamine', 'www1998' => 'WWW 1998', 'solarpunk' => 'Solarpunk',
    'galactica' => 'Galáctica', 'impresionismo' => 'Impresionismo'];
/** Cookie de plantilla para quien no tiene cuenta: solo se crea si la persona elige una plantilla en el menu (/cambiar-skin.php) o en /plantilla.php. */
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
function theme(): ?string {
    $u = user();
    $t = $u ? (string)($u['theme'] ?? 'auto') : (string)($_COOKIE['ddd_theme'] ?? 'auto');
    return in_array($t, ['light', 'dark'], true) ? $t : null;
}
/** Plantilla propia de la comunidad que se esta viendo (su pagina y sus hilos): la fija la pagina antes de page_header().
 *  Manda sobre la del usuario, pero no sobre ?skin= (probar). '' = la comunidad no tiene plantilla propia. */
$DDD_COMM_SKIN = '';
function community_skin(?string $skin): void { global $DDD_COMM_SKIN; $DDD_COMM_SKIN = isset(DDD_SKINS[(string)$skin]) ? (string)$skin : ''; }
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
/** Valor del delta en vivo de una noticia (misma formula que delta_sql) */
function delta_live(array $s): float {
    $net = (int)$s['votes_pos'] - (int)$s['votes_neg'];
    $hh  = intdiv(max(0, time() - strtotime($s['sent_date'])), 1800);
    $decay = (float)cfg('decay_per_halfhour', 0.05);
    $coef = (float)($s['coef'] ?? 1);
    return cfg('decay_mode') === 'multiplicative' ? $net * $coef * pow(1 - $decay, $hh) : $net * $coef - $decay * $hh;
}

/* ---- delta (misma formula que el cron, para mostrarla) ------------- */
/** Expresion SQL del delta en vivo (tablas: stories s, topics t). La usan
 *  el ciclo (para guardarlo) y las listas (para ordenar/mostrar al cargar). */
function delta_sql(): string {
    $decay = (float)cfg('decay_per_halfhour', 0.05);
    $hh = "FLOOR(TIMESTAMPDIFF(MINUTE, s.sent_date, NOW()) / 30)";
    return cfg('decay_mode') === 'multiplicative'
        ? "((s.votes_pos - s.votes_neg) * t.coef * POW(1 - $decay, $hh))"
        : "((s.votes_pos - s.votes_neg) * t.coef - $decay * $hh)";
}
function delta_explica(array $s): string {
    $net = (int)$s['votes_pos'] - (int)$s['votes_neg'];
    $hh  = intdiv(max(0, time() - strtotime($s['sent_date'])), 1800);
    $decay = (float)cfg('decay_per_halfhour', 0.05);
    $coef = (float)$s['coef'];
    $coefTxt = $coef != 1.0 ? " × {$s['topic']} " . number_format($coef, 2, ',', '') : '';
    if (cfg('decay_mode') === 'multiplicative') {
        $val = $net * $coef * pow(1 - $decay, $hh);
        return "{$net} " . t('votos') . "{$coefTxt} × (1−{$decay})^{$hh} = " . number_format($val, 1, ',', '');
    }
    $val = $net * $coef - $decay * $hh;
    return "{$net} " . t('votos') . "{$coefTxt} − " . number_format($decay * $hh, 2, ',', '') . " " . t('por antigüedad') . " = " . number_format($val, 1, ',', '');
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
        'skin'   => '<path d="M12 3a9 9 0 0 0 0 18c1.5 0 2-1 2-2s-1-1.5-1-2.5 1-1.5 2-1.5h1.5A4.5 4.5 0 0 0 21 10.5C21 6.4 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10.5" cy="7" r="1"/><circle cx="15.5" cy="7.5" r="1"/>',
        'theme'  => '<circle cx="12" cy="12" r="8.5"/><path d="M12 3.5v17"/><path d="M12 3.5a8.5 8.5 0 0 1 0 17z" fill="currentColor" stroke="none"/>',
        'lang'   => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c3 3 3 14 0 17"/><path d="M12 3.5c-3 3-3 14 0 17"/>',
        'share'  => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.4M8.2 13.2l7.6 4.4"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'news'   => '<rect x="3" y="5" width="15" height="15" rx="2"/><path d="M18 9h2a1 1 0 0 1 1 1v8a2 2 0 0 1-2 2"/><path d="M7 9h7M7 13h7M7 17h4"/>',
        'edit'   => '<path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17z"/><path d="m13.5 8.5 3 3"/>',
        'fav'    => '<path d="M6 4h12v17l-6-4.5L6 21z"/>',
        'agg'    => '<circle cx="5" cy="6" r="1.4" fill="currentColor" stroke="none"/><path d="M9.5 6H20"/><circle cx="5" cy="12" r="1.4" fill="currentColor" stroke="none"/><path d="M9.5 12H20"/><circle cx="5" cy="18" r="1.4" fill="currentColor" stroke="none"/><path d="M9.5 18H20"/>',   // agregadores: lista de fuentes
        'tag'    => '<path d="M3 11V4h7l10 10-7 7z"/><circle cx="7.5" cy="8.5" r="1.3" fill="currentColor" stroke="none"/>',   // temas
        'comm'   => '<circle cx="12" cy="8" r="3.2"/><circle cx="5" cy="11" r="2.2"/><circle cx="19" cy="11" r="2.2"/><path d="M6.5 20a5.5 5.5 0 0 1 11 0"/><path d="M2 17.5a3.5 3.5 0 0 1 3-2.3M22 17.5a3.5 3.5 0 0 0-3-2.3"/>',   // comunidades
        'letter' => '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5"/><path d="M9 12h7M9 16h7"/>',   // newsletter / substack: hoja con esquina doblada   // marcador: relleno via CSS cuando esta guardado
    ][$n] ?? '';
    return "<svg class=\"i\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">$p</svg>";
}

/** Enlace «editar» de moderación junto a un envío o artículo (solo mods y admin); lleva a /editar.php. */
function mod_edit_link(array $s): string {
    if (!is_mod()) return '';
    return "<a class=\"modlink\" href=\"/editar.php?id={$s['story_id']}\" title=\"" . t('editar (moderación)') . "\">" . icon('edit') . "<span>" . t('editar') . "</span></a>";
}

/* ---- layout (estilo timeline) -------------------------------------- */
/** $head: etiquetas extra para <head> (p. ej. el meta refresh de /procesos.php) */
function page_header(string $title, string $tab = '', string $head = ''): void {
    global $DDD_TAB; $DDD_TAB = $tab;
    $u = user();
    $theme = theme();   // null = sigue al sistema
    echo "<!doctype html><html lang=\"" . lang() . "\"" . ($theme ? " data-theme=\"$theme\"" : "") . " data-skin=\"" . skin() . "\"><head><meta charset=\"utf-8\">";
    echo "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1,viewport-fit=cover\">";
    echo "<meta name=\"color-scheme\" content=\"" . ($theme ?: 'light dark') . "\">";
    echo "<link rel=\"icon\" href=\"" . icon_src(64) . "\"><link rel=\"apple-touch-icon\" href=\"" . icon_src(180) . "\">";
    // PWA: manifiesto dinamico (lleva los iconos en vigor), color de barra y registro del service worker
    echo "<link rel=\"manifest\" href=\"/app/manifest.php\"><meta name=\"theme-color\" content=\"#ffffff\" media=\"(prefers-color-scheme: light)\"><meta name=\"theme-color\" content=\"#000000\" media=\"(prefers-color-scheme: dark)\">";
    echo "<meta name=\"apple-mobile-web-app-capable\" content=\"yes\"><meta name=\"apple-mobile-web-app-title\" content=\"" . h(cfg('site_name', 'Deltha')) . "\"><meta name=\"mobile-web-app-capable\" content=\"yes\">";
    echo "<script src=\"/app/register.js?v=" . DDD_CSS_VER . "\" data-v=\"" . DDD_CSS_VER . "\" defer></script>";
    echo "<title>" . h($title) . " · " . h(cfg('site_name')) . "</title>";
    echo "<link rel=\"stylesheet\" href=\"/css/ddd.css?v=" . DDD_CSS_VER . "\">" . $head . "</head><body>";
    require_once __DIR__ . '/lateral.php';
    echo "<div class=\"cols\">";   // escritorio: rail | shell | side (las columnas laterales van ocultas en movil)
    rail($tab);
    echo "<div class=\"shell\">";
    echo "<header class=\"top\"><a class=\"logo\" href=\"/\" aria-label=\"" . h(cfg('site_name')) . "\"><img src=\"" . logo_src('light') . "\" alt=\"\" class=\"l\"><img src=\"" . logo_src('dark') . "\" alt=\"\" class=\"d\"></a>";
    echo "<span class=\"ttl\">" . h($title) . "</span>";
    if ($u) echo "<a class=\"me\" href=\"/perfil.php\" title=\"" . t('perfil') . "\">" . ($u['avatar'] ? "<span class=\"av photo mini\" style=\"background-image:url('/media/avatars/" . h($u['avatar']) . "')\"></span>" : icon('user')) . "<span>" . h($u['login']) . "</span></a>";
    else    echo "<a class=\"me\" href=\"/login.php\">" . icon('user') . "<span>" . t('entrar') . "</span></a>";
    echo "</header>";
    nav_tabs($tab, 'tabs-top');
    nav_fuentes($tab);
    echo "<main>";
}
/* Dos menus: arriba el contenido (las fases del ciclo), abajo la comunidad y las
 * herramientas. href null = pestaña anunciada pero sin pagina todavia (Mensajes). */
function nav_tabs(string $tab, string $cls): void {
    $tabs = in_array($cls, ['tabs-top', 'rail-top'], true) ? [
        'portada'      => ['/', t('Portada'), 'home'],
        'candidatas'   => ['/candidatas.php', t('Candidatas'), 'cand'],
        'nuevas'       => ['/nuevas.php', t('Nuevas'), 'new'],
        'articulos'    => ['/articulos.php', t('Artículos'), 'art'],
        'chat'         => ['/chat.php', t('Chat'), 'chat'],
    ] : [
        'usuarios'     => ['/usuarios.php', t('Usuarios'), 'users'],
        'plantilla'    => ['/plantilla.php', t('Skin'), 'skin'],   // solo en el muelle (movil): el rail ya tiene su desplegable
        'procesos'     => ['/procesos.php', t('Procesos'), 'proc'],
        'estadisticas' => ['/estadisticas.php', t('Estadísticas'), 'stats'],
        'mensajes'     => ['/mensajes.php', t('Mensajes'), 'msg'],
    ];
    if ($cls !== 'tabs-bottom') unset($tabs['plantilla']);
    if (isset($tabs['chat']) && (string)cfg('chat_enabled', '1') !== '1') unset($tabs['chat']);
    echo "<nav class=\"$cls\">";
    foreach ($tabs as $k => [$href, $label, $ic]) {
        $on = $k === $tab ? ' class="on"' : '';
        if ($href === null) { echo "<span class=\"soon\" title=\"" . t('pronto') . "\">" . icon($ic) . "<span>" . h($label) . "</span></span>"; continue; }
        $badge = ($k === 'mensajes' && ($n = unread_msgs()) > 0) ? "<i class=\"badge\">" . ($n > 99 ? '99+' : $n) . "</i>" : '';
        echo "<a href=\"" . h($href) . "\"$on>" . icon($ic) . $badge . "<span>" . h($label) . "</span></a>";
    }
    echo "</nav>";
}
/** Franja bajo las pestañas, solo cuando no hay rail (movil y tablet, < 1100 px): lo que en escritorio esta en el rail y en la columna
 *  derecha — Substacks, Medios y Agregadores (/ultimo.php), Temas y Comunidades — con icono y rotulo como el muelle de abajo. */
function nav_fuentes(string $tab): void {
    global $pdo; require_once __DIR__ . '/fuentes.php';
    $tabs = [];
    if (fuentes_on()) foreach (DDD_ULTIMO_KINDS as $k => [, $ic]) if (fuentes_activas($pdo, $k)) $tabs["ultimo:$k"] = ["/ultimo.php?k=$k", t(['newsletter' => 'Substacks', 'medio' => 'Medios', 'agregador' => 'Agregadores'][$k]), $ic];
    $tabs['temas'] = ['/temas.php', t('Temas'), 'tag'];
    $tabs['comunidades'] = ['/comunidades.php', t('Comunidades'), 'comm'];
    echo "<nav class=\"tabs-fuentes\">";
    foreach ($tabs as $k => [$href, $label, $ic]) echo "<a href=\"" . h($href) . "\"" . ($k === $tab ? ' class="on"' : '') . ">" . icon($ic) . "<span>" . h($label) . "</span></a>";
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
    $h  = "<a href=\"/que-es.php\">" . t('¿Qué es Deltha?') . "</a> · <a href=\"https://contralgoritmia.com/\" rel=\"noopener\">" . t('Manifiesto contralgorítmico') . "</a> · <a href=\"/privacidad.php\">" . t('Privacidad y contacto') . "</a> · <a href=\"/normas.php\">" . t('Normas de uso') . "</a> · <a href=\"/algoritmo.php\">" . t('Cómo funciona el δ') . "</a> · <a href=\"/comunidades.php\">" . t('Comunidades') . "</a>";
    // fuentes externas pintadas con Deltha: un enlace por grupo al catalogo (/medios.php?k=…), no uno por agregador
    global $pdo; require_once __DIR__ . '/fuentes.php';
    if (fuentes_on()) {
        foreach (['newsletter' => t('Substacks'), 'medio' => t('Medios'), 'agregador' => t('Agregadores')] as $k => $label) if (fuentes_activas($pdo, $k)) $h .= " · <a href=\"/medios.php?k=$k\">" . h($label) . "</a>";
    }
    $h .= " · " . t('Sin cookies para quien no inicia sesión (salvo las de skin, tema e idioma, si los eliges) · sin rastreo · sin algoritmo oculto');
    if (is_mod())   $h .= " · <a href=\"/moderacion.php\">" . t('moderación') . (open_reports() ? " (" . open_reports() . ")" : "") . "</a>";
    if (is_admin()) $h .= " · <a href=\"/admin.php\">admin</a>";
    return $h;
}
function page_footer(): void {
    echo "</main>";
    echo "<a class=\"fab\" href=\"/enviar.php\" aria-label=\"" . t('Enviar') . "\">" . icon('plus') . "</a>";
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
    echo "<script src=\"/app/share.js?v=" . DDD_CSS_VER . "\" defer></script>";   // «compartir»: copia el enlace (o abre el menu nativo); sin JS el enlace lleva al hilo
    echo "</body></html>";
}
function current_tab(): string { global $DDD_TAB; return $DDD_TAB ?? ''; }
