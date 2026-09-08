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
define('DDD_CSS_VER', 31);   // subir al cambiar ddd.css

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

/* ---- sesion solo si ya hay cookie (nunca para anonimos) ------------ */
$USER = null;
session_name(DDD_SESSION);
if (isset($_COOKIE[DDD_SESSION])) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
    if (!empty($_SESSION['uid'])) {
        $st = $pdo->prepare("SELECT user_id, login, level, aura, avatar, bio, theme FROM users WHERE user_id = ? AND verified = 1");
        $st->execute([$_SESSION['uid']]);
        $USER = $st->fetch() ?: null;
        if ($USER && PHP_SAPI !== 'cli') {   // ultima visita, como mucho una vez por minuto (para /estadisticas.php)
            $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ? AND (last_seen IS NULL OR last_seen < NOW() - INTERVAL 1 MINUTE)")
                ->execute([$USER['user_id']]);
        }
    }
}
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
 * tenga la clave ve la presentacion (/delta/) en vez de la portada.
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
if (!defined('DDD_PUBLIC') && !gate_open()) redirect('/delta/');

/* ---- helpers ------------------------------------------------------- */
/** Casilla «mostrar contraseña» para un formulario (la activa /app/pw.js; sin JS no aparece). */
function pw_toggle(): string {
    return "<span class=\"showpw\" hidden><label><input type=\"checkbox\"> mostrar contraseña</label></span><script src=\"/app/pw.js\" defer></script>";
}
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $to): never { header("Location: $to"); exit; }
function post(string $k, string $d = ''): string { return trim((string)($_POST[$k] ?? $d)); }
function client_ip(): string { return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

function hace(string $dt): string {
    $s = time() - strtotime($dt);
    if ($s < 60)    return 'ahora';
    if ($s < 3600)  return 'hace ' . intdiv($s, 60) . ' min';
    if ($s < 86400) return 'hace ' . intdiv($s, 3600) . ' h';
    return 'hace ' . intdiv($s, 86400) . ' d';
}
function queda(string $published): string {
    $ttl = (int)cfg('thread_ttl_hours', 48) * 3600;
    $s = strtotime($published) + $ttl - time();
    if ($s <= 0) return 'archivado';
    return $s < 3600 ? 'muere en ' . intdiv($s, 60) . ' min' : 'muere en ' . intdiv($s, 3600) . ' h';
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

/* ---- paginas editables (tabla pages, admin) ------------------------- */
function page_get(PDO $pdo, string $slug): array {
    $st = $pdo->prepare("SELECT slug, title, body, updated FROM pages WHERE slug = ?");
    $st->execute([$slug]);
    return $st->fetch() ?: ['slug' => $slug, 'title' => ucfirst($slug), 'body' => '<p>Sin contenido todavía.</p>', 'updated' => null];
}
/** Cuerpo HTML con {site}, {ttl} y {gm} sustituidos por la config actual */
function page_body(array $pg): string {
    return strtr($pg['body'], [
        '{site}' => h(cfg('site_name', 'Deltha')),
        '{ttl}'  => (string)(int)cfg('thread_ttl_hours', 48),
        '{gm}'   => (string)(int)cfg('ghost_minutes', 30),
    ]);
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
        return "{$net} votos{$coefTxt} × (1−{$decay})^{$hh} = " . number_format($val, 1, ',', '');
    }
    $val = $net * $coef - $decay * $hh;
    return "{$net} votos{$coefTxt} − " . number_format($decay * $hh, 2, ',', '') . " por antigüedad = " . number_format($val, 1, ',', '');
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
        'reply'  => '<path d="M20 12a8 8 0 0 1-11.6 7.1L4 20l1.1-3.9A8 8 0 1 1 20 12z"/>',
        'up'     => '<path d="M12 19V5"/><path d="m6 11 6-6 6 6"/>',
        'down'   => '<path d="M12 5v14"/><path d="m6 13 6 6 6-6"/>',
        'link'   => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'plus'   => '<path d="M12 5v14M5 12h14"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    ][$n] ?? '';
    return "<svg class=\"i\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">$p</svg>";
}

/* ---- layout (estilo timeline) -------------------------------------- */
/** $head: etiquetas extra para <head> (p. ej. el meta refresh de /procesos.php) */
function page_header(string $title, string $tab = '', string $head = ''): void {
    global $DDD_TAB; $DDD_TAB = $tab;
    $u = user();
    $theme = $u && in_array($u['theme'] ?? 'auto', ['light', 'dark'], true) ? $u['theme'] : null;   // null = sigue al sistema
    echo "<!doctype html><html lang=\"es\"" . ($theme ? " data-theme=\"$theme\"" : "") . "><head><meta charset=\"utf-8\">";
    echo "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1,viewport-fit=cover\">";
    echo "<meta name=\"color-scheme\" content=\"" . ($theme ?: 'light dark') . "\">";
    echo "<link rel=\"icon\" href=\"/img/favicon.png\"><link rel=\"apple-touch-icon\" href=\"/img/icon-180.png\">";
    echo "<title>" . h($title) . " · " . h(cfg('site_name')) . "</title>";
    echo "<link rel=\"stylesheet\" href=\"/css/ddd.css?v=" . DDD_CSS_VER . "\">" . $head . "</head><body>";
    echo "<div class=\"shell\">";
    echo "<header class=\"top\"><a class=\"logo\" href=\"/\" aria-label=\"" . h(cfg('site_name')) . "\"><img src=\"/img/logo.png\" alt=\"\" class=\"l\"><img src=\"/img/logo-dark.png\" alt=\"\" class=\"d\"></a>";
    echo "<span class=\"ttl\">" . h($title) . ($tab === 'portada' ? " <a class=\"que\" href=\"/delta/\">¿Qué es Deltha?</a>" : "") . "</span>";
    if ($u) echo "<a class=\"me\" href=\"/perfil.php\" title=\"perfil\">" . ($u['avatar'] ? "<span class=\"av photo mini\" style=\"background-image:url('/media/avatars/" . h($u['avatar']) . "')\"></span>" : icon('user')) . "<span>" . h($u['login']) . "</span></a>";
    else    echo "<a class=\"me\" href=\"/login.php\">" . icon('user') . "<span>entrar</span></a>";
    echo "</header>";
    nav_tabs($tab, 'tabs-top');
    echo "<main>";
}
/* Dos menus: arriba el contenido (las fases del ciclo), abajo la comunidad y las
 * herramientas. href null = pestaña anunciada pero sin pagina todavia (Mensajes). */
function nav_tabs(string $tab, string $cls): void {
    $tabs = $cls === 'tabs-top' ? [
        'portada'      => ['/', 'Portada', 'home'],
        'candidatas'   => ['/candidatas.php', 'Candidatas', 'cand'],
        'nuevas'       => ['/nuevas.php', 'Nuevas', 'new'],
        'articulos'    => ['/articulos.php', 'Artículos', 'art'],
    ] : [
        'usuarios'     => ['/usuarios.php', 'Usuarios', 'users'],
        'procesos'     => ['/procesos.php', 'Procesos', 'proc'],
        'estadisticas' => ['/estadisticas.php', 'Estadísticas', 'stats'],
        'mensajes'     => [null, 'Mensajes', 'msg'],
    ];
    echo "<nav class=\"$cls\">";
    foreach ($tabs as $k => [$href, $label, $ic]) {
        $on = $k === $tab ? ' class="on"' : '';
        if ($href === null) echo "<span class=\"soon\" title=\"pronto\">" . icon($ic) . "<span>" . h($label) . "</span></span>";
        else echo "<a href=\"" . h($href) . "\"$on>" . icon($ic) . "<span>" . h($label) . "</span></a>";
    }
    echo "</nav>";
}
function footer_links(): string {
    $h  = "<a href=\"/delta/\">¿Qué es Deltha?</a> · <a href=\"https://contralgoritmia.com/\" rel=\"noopener\">Manifiesto contralgorítmico</a> · <a href=\"/privacidad.php\">Privacidad y contacto</a> · <a href=\"/estadisticas.php\">Estadísticas</a> · <a href=\"/procesos.php\">Procesos</a> · Sin cookies para quien no inicia sesión · sin rastreo · sin algoritmo oculto";
    if (is_mod())   $h .= " · <a href=\"/moderacion.php\">moderación</a>";
    if (is_admin()) $h .= " · <a href=\"/admin.php\">admin</a>";
    return $h;
}
function page_footer(): void {
    echo "</main>";
    echo "<a class=\"fab\" href=\"/enviar.php\" aria-label=\"Enviar\">" . icon('plus') . "</a>";
    // pie en flujo (movil): al final de cada pagina
    echo "<footer class=\"inflow\"><a class=\"ctr\" href=\"https://contralgoritmia.com/\" rel=\"noopener\" title=\"Manifiesto contralgorítmico\"><img src=\"/img/contralgoritmia.png\" alt=\"Sello contralgorítmico\"></a>";
    echo "<p>" . footer_links() . "</p></footer>";
    echo "</div>";
    // muelle fijo (PC: enlaces + iconos; movil: solo iconos)
    echo "<div class=\"dock\"><footer class=\"docked\"><p>" . footer_links() . "</p></footer>";
    nav_tabs(current_tab(), 'tabs-bottom');
    echo "</div>";
    echo "</body></html>";
}
function current_tab(): string { global $DDD_TAB; return $DDD_TAB ?? ''; }
