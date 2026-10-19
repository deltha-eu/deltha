<?php
/** /buscar?q=texto[&en=hilos|comentarios|fuentes|usuarios][&p=N] — buscador interno (migra-085).
 *  FULLTEXT de InnoDB en modo booleano sobre stories (title, content), comments (content) y source_items (title, content);
 *  usuarios por LIKE en el nick. Sin ?en= sale un resumen con los primeros de cada grupo y su total; con ?en= la lista
 *  paginada del grupo. Las palabras de menos de 3 letras (el minimo del indice: innodb_ft_min_token_size) se buscan con
 *  LIKE; una frase entre comillas se busca tal cual. La relevancia la da MATCH ... AGAINST; a igualdad, lo mas reciente.
 *  Solo lo visible: hilos en el radar, portadas o archivo (no pendientes ni rechazados), comentarios ok y no borrados,
 *  fuentes activas (y, si la persona filtra por idioma, solo esas). Usuarios: solo para quien tiene cuenta, como /usuarios. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

if ((string)cfg('search_enabled', '1') !== '1') redirect('/');

const BUSCAR_MIN = 2;          // letras minimas de la consulta
const BUSCAR_FT_MIN = 3;       // innodb_ft_min_token_size: por debajo, LIKE
const BUSCAR_RESUMEN = 5;      // resultados por grupo en el resumen

$q  = trim(mb_substr(preg_replace('/\s+/u', ' ', (string)($_GET['q'] ?? '')), 0, 80));
$en = (string)($_GET['en'] ?? '');
$GRUPOS = ['hilos' => t('Hilos'), 'comentarios' => t('Comentarios'), 'fuentes' => t('Fuentes')];
if (user()) $GRUPOS['usuarios'] = t('Usuarios');
if (!isset($GRUPOS[$en])) $en = '';
if (!fuentes_on()) unset($GRUPOS['fuentes']);
$page = page_num();
$per  = (int)cfg('per_page', 20);

/** Trocea la consulta: frases entre comillas y palabras. Devuelve [ft => cadena para AGAINST (o ''), like => palabras cortas, all => todas las piezas]. */
function buscar_terminos(string $q): array {
    $ft = []; $like = []; $all = [];
    preg_match_all('/"([^"]+)"|(\S+)/u', $q, $m, PREG_SET_ORDER);
    foreach ($m as $x) {
        $frase = isset($x[1]) && $x[1] !== '';
        $t = trim($frase ? $x[1] : $x[2]);
        $t = preg_replace('/[+\-~<>()@*"]/u', ' ', $t);   // operadores del modo booleano: fuera
        $t = trim(preg_replace('/\s+/u', ' ', $t));
        if ($t === '') continue;
        $all[] = $t;
        if ($frase) {
            // una frase entra en el indice si alguna palabra suya llega al minimo; si no, LIKE
            $ok = false; foreach (explode(' ', $t) as $w) if (mb_strlen($w) >= BUSCAR_FT_MIN) { $ok = true; break; }
            if ($ok) $ft[] = '+"' . $t . '"'; else $like[] = $t;
        } elseif (mb_strlen($t) >= BUSCAR_FT_MIN) $ft[] = '+' . $t . '*';   // prefijo: «vacuna» encuentra «vacunas», «vacunación»
        else $like[] = $t;
    }
    return ['ft' => implode(' ', $ft), 'like' => $like, 'all' => $all];
}
/** Condicion SQL y parametros para unas columnas: MATCH (si hay terminos largos) AND LIKE por cada palabra corta. */
function buscar_where(array $T, string $matchCols, array $likeCols): array {
    $w = []; $p = [];
    if ($T['ft'] !== '') { $w[] = "MATCH($matchCols) AGAINST (? IN BOOLEAN MODE)"; $p[] = $T['ft']; }
    foreach ($T['like'] as $t) {
        $w[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $likeCols)) . ')';
        foreach ($likeCols as $c) $p[] = '%' . addcslashes($t, '%_\\') . '%';
    }
    return [$w ? implode(' AND ', $w) : '0', $p];
}
/** ORDER BY: relevancia del MATCH (si lo hay) y despues la fecha. Devuelve [sql, params]. */
function buscar_orden(array $T, string $matchCols, string $fecha): array {
    if ($T['ft'] === '') return ["$fecha DESC", []];
    return ["MATCH($matchCols) AGAINST (? IN BOOLEAN MODE) DESC, $fecha DESC", [$T['ft']]];
}
/** Resalta los terminos en texto YA escapado (h()). */
function buscar_marca(string $html, array $T): string {
    if (!$T['all']) return $html;
    // sin distinguir acentos, como el indice (utf8mb4_unicode_ci): «sanchez» marca «Sánchez»
    $v = ['a' => '[aáàäâ]', 'e' => '[eéèëê]', 'i' => '[iíìïî]', 'o' => '[oóòöô]', 'u' => '[uúùüû]', 'n' => '[nñ]', 'c' => '[cç]'];
    $alt = implode('|', array_map(function ($t) use ($v) {
        $r = '';
        foreach (preg_split('//u', h($t), -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            $k = mb_strtolower(strtr($ch, ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ñ' => 'n', 'ç' => 'c']));
            $r .= $v[$k] ?? preg_quote($ch, '/');
        }
        return $r;
    }, $T['all']));
    return preg_replace("/($alt)(?![^<]*>)/iu", '<mark>$1</mark>', $html) ?? $html;   // (?![^<]*>): nunca dentro de una etiqueta
}
/** Extracto de un texto largo alrededor de la primera coincidencia (escapado y marcado). */
function buscar_extracto(string $txt, array $T, int $len = 220): string {
    $pos = null;
    foreach ($T['all'] as $t) { $p = mb_stripos($txt, $t); if ($p !== false && ($pos === null || $p < $pos)) $pos = $p; }
    $ini = max(0, ($pos ?? 0) - (int)($len / 3));
    if ($ini > 0) { $sp = mb_strrpos(mb_substr($txt, 0, $ini + 1), ' '); if ($sp !== false && $sp > $ini - 30) $ini = $sp + 1; }   // que empiece en palabra entera
    $x = mb_substr($txt, $ini, $len);
    return buscar_marca(h(($ini > 0 ? '…' : '') . $x . (mb_strlen($txt) > $ini + $len ? '…' : '')), $T);
}
function buscar_url(string $q, string $en = ''): string { return '/buscar?q=' . rawurlencode($q) . ($en !== '' ? "&en=$en" : ''); }

/* ---- consultas por grupo: [total, filas] ------------------------------------------------------------------- */
function buscar_hilos(PDO $pdo, array $T, int $limit, int $offset): array {
    [$w, $p] = buscar_where($T, 's.title, s.content', ['s.title', 's.content']);
    [$o, $po] = buscar_orden($T, 's.title, s.content', 's.sent_date');
    $base = " WHERE s.status IN ('nuevas','destacadas','archived')" . foros_cerrados_sql() . " AND $w";   // sin los foros de aura minima que no se pueden ver (migra-165)
    $st = $pdo->prepare("SELECT COUNT(*) FROM stories s$base"); $st->execute($p); $total = (int)$st->fetchColumn();
    $st = $pdo->prepare(story_query_base() . "$base ORDER BY $o LIMIT $limit OFFSET $offset"); $st->execute(array_merge($p, $po));
    return [$total, $st->fetchAll()];
}
function buscar_comentarios(PDO $pdo, array $T, int $limit, int $offset): array {
    [$w, $p] = buscar_where($T, 'c.content', ['c.content']);
    [$o, $po] = buscar_orden($T, 'c.content', 'c.created');
    $base = " FROM comments c JOIN stories s ON s.story_id = c.story_id AND s.status IN ('nuevas','destacadas','archived')" . foros_cerrados_sql() . "
              LEFT JOIN users u ON u.user_id = c.user_id
              WHERE c.status = 'ok' AND c.deleted = 0 AND $w";
    $st = $pdo->prepare("SELECT COUNT(*)$base"); $st->execute($p); $total = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT c.comment_id, c.story_id, c.user_id, c.alias, c.aura, c.content, c.created, c.likes, u.login, u.avatar, s.title AS story_title$base ORDER BY $o LIMIT $limit OFFSET $offset");
    $st->execute(array_merge($p, $po));
    return [$total, $st->fetchAll()];
}
function buscar_fuentes(PDO $pdo, array $T, int $limit, int $offset): array {
    $src = fuentes_visibles($pdo);   // activas y, si se filtra por idioma, solo esas (agregadores solo si estan encendidos)
    if (!$src) return [0, []];
    $ids = array_column($src, 'source_id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    [$w, $p] = buscar_where($T, 'i.title, i.content', ['i.title', 'i.content']);
    [$o, $po] = buscar_orden($T, 'i.title, i.content', 'i.pub_date');
    $base = " FROM source_items i WHERE i.source_id IN ($in) AND $w";
    $st = $pdo->prepare("SELECT COUNT(*)$base"); $st->execute(array_merge($ids, $p)); $total = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT i.*$base ORDER BY $o LIMIT $limit OFFSET $offset"); $st->execute(array_merge($ids, $p, $po));
    return [$total, $st->fetchAll()];
}
function buscar_usuarios(PDO $pdo, array $T, int $limit, int $offset): array {
    if (!user()) return [0, []];
    $w = []; $p = [];
    foreach ($T['all'] as $t) { $w[] = '(u.login LIKE ? OR u.bio LIKE ?)'; $p[] = '%' . addcslashes($t, '%_\\') . '%'; $p[] = $p[count($p) - 1]; }
    $base = " FROM users u WHERE u.verified = 1" . ghost_sql_not_in('u.user_id') . " AND " . ($w ? implode(' AND ', $w) : '0');   // sin los velados por ghosteo
    $st = $pdo->prepare("SELECT COUNT(*)$base"); $st->execute($p); $total = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT u.login, u.aura, u.avatar, u.bio, u.created, u.last_seen$base ORDER BY (u.login LIKE ?) DESC, u.aura DESC, u.created ASC LIMIT $limit OFFSET $offset");
    $st->execute(array_merge($p, [$T['all'] ? $T['all'][0] . '%' : '']));   // primero los que empiezan por lo buscado
    return [$total, $st->fetchAll()];
}

/* ---- pintado ----------------------------------------------------------------------------------------------- */
function buscar_pinta_comentario(array $c, array $T): void {
    $rev = ghost_over($c['created']);
    $n = display_name($c, $rev);
    $href = "/hilo/{$c['story_id']}#c{$c['comment_id']}";
    echo "<article class=\"post reply root found\">" . who_avatar($c, $rev, (float)$c['aura'], $n) . "<div class=\"pc\">";
    echo "<div class=\"l1\"><b>" . (shown($c, $rev) ? "<a href=\"/u/" . rawurlencode($c['login']) . "\">" . h($n) . "</a>" : h($n)) . "</b><span class=\"meta\"> · " . h(hace($c['created'])) . "</span></div>";
    echo "<p>" . buscar_extracto($c['content'], $T) . "</p>";
    echo "<div class=\"actions\"><a href=\"" . h($href) . "\">" . icon('reply') . "<span>" . t('en «%s»', h(mb_strimwidth($c['story_title'], 0, 80, '…'))) . "</span></a>";
    if ((int)$c['likes'] > 0) echo "<span class=\"suns\">" . icon('sun') . "<span>" . (int)$c['likes'] . "</span></span>";
    echo "</div></div></article>";
}
function buscar_pinta_usuario(array $r, array $T): void {
    echo "<div class=\"ulist\">" . avatar((float)$r['aura'], $r['login'], $r['avatar'], '/u/' . rawurlencode($r['login']));
    echo "<div><b><a href=\"/u/" . rawurlencode($r['login']) . "\">" . buscar_marca(h($r['login']), $T) . "</a></b>";
    echo "<span class=\"meta\"> · " . t('aura') . " " . number_format((float)$r['aura'], 1, ',', '') . " · " . h(t('registro %s', hace($r['created']))) . "</span>";
    if ($r['bio']) echo "<p class=\"bio\">" . buscar_marca(h($r['bio']), $T) . "</p>";
    echo "</div></div>";
}
function buscar_pinta(PDO $pdo, string $grupo, array $rows, array $T): void {
    global $srcPorId;
    foreach ($rows as $r) {
        if ($grupo === 'hilos') story_post($pdo, $r, 'compact');
        elseif ($grupo === 'comentarios') buscar_pinta_comentario($r, $T);
        elseif ($grupo === 'fuentes') { if (isset($srcPorId[$r['source_id']])) fuente_post($srcPorId[$r['source_id']], $r, 'compact'); }
        else buscar_pinta_usuario($r, $T);
    }
}
$srcPorId = fuentes_on() ? array_column(fuentes_visibles($pdo), null, 'source_id') : [];
$BUSCA = ['hilos' => 'buscar_hilos', 'comentarios' => 'buscar_comentarios', 'fuentes' => 'buscar_fuentes', 'usuarios' => 'buscar_usuarios'];

header('Cache-Control: private, no-cache');
page_header(t('Buscar'), 'buscar', '', $q !== '' ? t('Buscar') . ": $q" : '');
echo "<form class=\"buscar\" action=\"/buscar\" method=\"get\" role=\"search\">";
echo "<input type=\"search\" name=\"q\" value=\"" . h($q) . "\" placeholder=\"" . h(t('Buscar en %s', cfg('site_name'))) . "\" aria-label=\"" . t('Buscar') . "\" autofocus maxlength=\"80\">";
if ($en !== '') echo "<input type=\"hidden\" name=\"en\" value=\"$en\">";
echo "<button type=\"submit\" aria-label=\"" . t('Buscar') . "\">" . icon('search') . "</button></form>";

if ($q === '') {
    echo "<p class=\"notice\">" . (user() ? t('Busca en los hilos, los comentarios, las noticias de los medios y substacks y la gente.') : t('Busca en los hilos, los comentarios y las noticias de los medios y substacks.')) . " " . t('Las palabras se buscan por su comienzo («vacuna» encuentra «vacunas»); una frase entre comillas, tal cual.') . "</p>";
    page_footer(); exit;
}
$T = buscar_terminos($q);
if (mb_strlen(implode('', $T['all'])) < BUSCAR_MIN) {
    echo "<p class=\"end\">" . t('Escribe al menos %d letras.', BUSCAR_MIN) . "</p>";
    page_footer(); exit;
}

// pestañas: Todo · Hilos · Comentarios · Fuentes · Usuarios
echo "<nav class=\"sub\"><a href=\"" . h(buscar_url($q)) . "\"" . ($en === '' ? ' class="on"' : '') . ">" . t('Todo') . "</a>";
foreach ($GRUPOS as $k => $label) echo "<a href=\"" . h(buscar_url($q, $k)) . "\"" . ($k === $en ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";

if ($en === '') {
    $hay = false;
    foreach ($GRUPOS as $k => $label) {
        [$total, $rows] = $BUSCA[$k]($pdo, $T, BUSCAR_RESUMEN, 0);
        if ($total === 0) continue;
        $hay = true;
        echo "<h3 class=\"sec grupo\"><a href=\"" . h(buscar_url($q, $k)) . "\">" . h($label) . "</a> <span class=\"meta\">· " . h(t('%d resultados', $total)) . "</span></h3>";
        buscar_pinta($pdo, $k, $rows, $T);
        if ($total > BUSCAR_RESUMEN) echo "<p class=\"more\"><a href=\"" . h(buscar_url($q, $k)) . "\">" . t('ver todos') . " (" . $total . ")</a></p>";
    }
    if (!$hay) echo "<p class=\"end\">" . t('Nada encontrado para «%s».', h($q)) . "</p>";
} else {
    [$total, $rows] = $BUSCA[$en]($pdo, $T, $per, page_offset($page));
    echo "<p class=\"notice\">" . h(t('%d resultados', $total)) . " · " . h($GRUPOS[$en]) . " · «" . h($q) . "»</p>";
    buscar_pinta($pdo, $en, $rows, $T);
    if (!$rows) echo "<p class=\"end\">" . t('Nada encontrado para «%s».', h($q)) . "</p>";
    paginate(buscar_url($q, $en), $page, $total);
}
page_footer();
