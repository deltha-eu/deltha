<?php
/** admin -> Analitica (y /analitica para aura >= analytics_full_aura, 16/10/2026: $base es la URL a la que apuntan el formulario y los
 *  atajos de fechas): lo que se esta leyendo ahora, el dia de hoy y un rango de fechas con paginas, procedencia,
 *  paises, dispositivos e idiomas. Todo desde hits (detalle, analytics_keep_days dias) y ana_daily (totales, siempre). */
declare(strict_types=1);

function ana_admin(PDO $pdo, string $base = '/admin?t=analitica'): void {
    $n = fn($v) => number_format((int)$v, 0, ',', '.');
    $bu = parse_url($base); parse_str((string)($bu['query'] ?? ''), $bq);   // el formulario GET necesita la ruta y los parametros fijos como hidden
    $link = fn(string $f, string $t) => h($base . (str_contains($base, '?') ? '&' : '?') . "from=$f&to=$t");
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['from'] ?? '')) ? $_GET['from'] : date('Y-m-d', strtotime('-29 days'));
    $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['to'] ?? ''))   ? $_GET['to']   : date('Y-m-d');
    if ($from > $to) [$from, $to] = [$to, $from];
    $since = ana_since();   // nada anterior a la apertura al publico (analytics_since): el rango se acota y el detalle tambien
    if ($since !== '' && $from < $since) $from = $since;
    if ($since !== '' && $to < $since) $to = $since;
    $hoy = date('Y-m-d');
    $keep = max(7, (int)cfg('analytics_keep_days', 90));
    $detFrom = date('Y-m-d', strtotime("-$keep days"));
    $bar = function (int $v, int $max, string $cls = '') { $w = $max > 0 ? max(1, (int)round(100 * $v / $max)) : 0; return "<span class=\"anabar $cls\"><i style=\"width:{$w}%\"></i></span>"; };

    // ---- ahora ----
    $live = $pdo->query("SELECT COUNT(DISTINCT fp) personas, COUNT(*) paginas FROM hits WHERE ts >= NOW() - INTERVAL 5 MINUTE")->fetch();
    $min30 = $pdo->query("SELECT COUNT(*) FROM hits WHERE ts >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
    $porMin = array_fill(0, 30, 0);
    foreach ($pdo->query("SELECT TIMESTAMPDIFF(MINUTE, ts, NOW()) m, COUNT(*) c FROM hits WHERE ts >= NOW() - INTERVAL 30 MINUTE GROUP BY m") as $r) if ((int)$r['m'] < 30) $porMin[29 - (int)$r['m']] = (int)$r['c'];
    $maxMin = max(1, max($porMin));
    echo "<h3>Ahora</h3><p><b>" . $n($live['personas']) . "</b> personas en los últimos 5 min · <b>" . $n($live['paginas']) . "</b> páginas en 5 min · <b>" . $n($min30) . "</b> en 30 min</p>";
    echo "<div class=\"anamin\" title=\"páginas vistas por minuto, últimos 30 minutos\">";
    foreach ($porMin as $c) echo "<i style=\"height:" . max(2, (int)round(100 * $c / $maxMin)) . "%\" title=\"$c\"></i>";
    echo "</div>";
    $st = $pdo->query("SELECT path, COUNT(*) c FROM hits WHERE ts >= NOW() - INTERVAL 5 MINUTE GROUP BY path ORDER BY c DESC LIMIT 10");
    $rows = $st->fetchAll();
    if ($rows) { echo "<table>"; foreach ($rows as $r) echo "<tr><td>" . ana_path_label($pdo, $r['path']) . "</td><td class=\"num\">{$r['c']}</td></tr>"; echo "</table>"; }
    echo "<p><small>La página se recarga sola cada minuto.</small></p>";

    // ---- hoy ----
    $h = ana_day_from_hits($pdo, $hoy);
    echo "<h3>Hoy (hasta ahora)</h3><div class=\"statspage\" style=\"padding:0\"><div class=\"grid\">";
    foreach ([[$h['visitors'], 'visitantes'], [$h['sessions'], 'sesiones'], [$h['pageviews'], 'páginas vistas'], [$h['logged'], 'páginas con cuenta']] as [$v, $l]) echo "<div class=\"tile\"><b>" . $n($v) . "</b><span>$l</span></div>";
    echo "</div></div>";

    // ---- rango ----
    echo "<h3>Por fechas</h3><form method=\"get\" class=\"anaform\" action=\"" . h($bu['path'] ?? '/') . "\">";
    foreach ($bq as $k => $v) echo "<input type=\"hidden\" name=\"" . h((string)$k) . "\" value=\"" . h((string)$v) . "\">";
    echo "del <input type=\"date\" name=\"from\" value=\"$from\"> al <input type=\"date\" name=\"to\" value=\"$to\"> <button class=\"btn\">Ver</button> ";
    foreach ([['hoy', 0], ['7 días', 6], ['30 días', 29], ['90 días', 89], ['este año', -1]] as [$l, $d]) {
        $f = $d === -1 ? date('Y-01-01') : date('Y-m-d', strtotime("-$d days"));
        if ($since !== '' && $f < $since) $f = $since;
        echo "<a href=\"" . $link($f, $hoy) . "\">$l</a> ";
    }
    if ($since !== '') echo "<a href=\"" . $link($since, $hoy) . "\">desde la apertura</a> ";
    echo "</form>";
    if ($since !== '') echo "<p><small>Se cuenta desde el " . date('d/m/Y', strtotime($since)) . ", el día que Deltha se abrió al público; lo anterior (la beta) no entra en ninguna cifra (<code>analytics_since</code> en Config).</small></p>";
    // dias: cerrados desde ana_daily, hoy desde hits
    $st = $pdo->prepare("SELECT * FROM ana_daily WHERE day BETWEEN ? AND ? ORDER BY day");
    $st->execute([$from, $to]);
    $days = []; foreach ($st as $r) $days[$r['day']] = $r;
    if ($to >= $hoy && $from <= $hoy) $days[$hoy] = ['day' => $hoy] + $h;
    ksort($days);
    $tot = ['visitors' => 0, 'sessions' => 0, 'pageviews' => 0, 'logged' => 0];
    foreach ($days as $d) foreach ($tot as $k => $_) $tot[$k] += (int)$d[$k];
    echo "<div class=\"statspage\" style=\"padding:0\"><div class=\"grid\">";
    foreach ([[$tot['visitors'], 'visitantes'], [$tot['sessions'], 'sesiones'], [$tot['pageviews'], 'páginas vistas'], [$tot['sessions'] ? round($tot['pageviews'] / $tot['sessions'], 1) : 0, 'páginas por sesión'], [count($days), 'días con datos']] as [$v, $l])
        echo "<div class=\"tile\"><b>" . (is_float($v) ? str_replace('.', ',', (string)$v) : $n($v)) . "</b><span>$l</span></div>";
    echo "</div></div>";
    if ($days) {
        $maxV = max(1, max(array_map(fn($d) => (int)$d['pageviews'], $days)));
        echo "<table class=\"anadays\"><tr><th>día</th><th class=\"num\">visitantes</th><th class=\"num\">sesiones</th><th class=\"num\">páginas</th><th class=\"num\">con cuenta</th><th style=\"width:30%\"></th></tr>";
        foreach (array_reverse($days) as $d) {
            $lbl = ['lun','mar','mié','jue','vie','sáb','dom'][(int)date('N', strtotime($d['day'])) - 1] . date(' d/m', strtotime($d['day'])) . ($d['day'] === $hoy ? ' <small>(hoy, parcial)</small>' : '');
            echo "<tr><td>$lbl</td><td class=\"num\">" . $n($d['visitors']) . "</td><td class=\"num\">" . $n($d['sessions']) . "</td><td class=\"num\">" . $n($d['pageviews']) . "</td><td class=\"num\">" . $n($d['logged']) . "</td><td>" . $bar((int)$d['pageviews'], $maxV) . "</td></tr>";
        }
        echo "</table>";
    } else echo "<p>Sin datos en ese rango.</p>";

    // ---- detalle (solo dentro de la ventana de hits) ----
    $dFrom = max($from, $detFrom);   // $from ya viene acotado por analytics_since
    if ($dFrom > $to) { echo "<p><small>El detalle (páginas, procedencia, países…) solo se conserva $keep días; este rango queda fuera.</small></p>"; return; }
    if ($dFrom > $from) echo "<p><small>El detalle de abajo cubre del $dFrom al $to (se conserva $keep días); los totales de arriba, todo el rango.</small></p>";
    $where = "ts >= '$dFrom' AND ts < '$to' + INTERVAL 1 DAY";
    $top = function (string $title, string $sql, callable $label) use ($pdo, $n, $bar) {
        $rows = $pdo->query($sql)->fetchAll();
        if (!$rows) return;
        $max = (int)$rows[0]['c'];
        echo "<h3>$title</h3><table>";
        foreach ($rows as $r) echo "<tr><td>" . $label($r) . "</td><td class=\"num\">" . $n($r['c']) . "</td><td style=\"width:35%\">" . $bar((int)$r['c'], $max) . "</td></tr>";
        echo "</table>";
    };
    echo "<div class=\"anacols\">";
    echo "<div>";
    $top('Páginas más vistas', "SELECT path k, COUNT(*) c FROM hits WHERE $where GROUP BY path ORDER BY c DESC LIMIT 30", fn($r) => ana_path_label($pdo, $r['k']));
    $top('Procedencia (sesiones)', "SELECT ref_host k, COUNT(*) c FROM hits WHERE $where AND new_session = 1 GROUP BY ref_host ORDER BY c DESC LIMIT 25", fn($r) => $r['k'] === '' ? '<i>directo o interno</i>' : h($r['k']));
    echo "</div><div>";
    $top('Países (sesiones)', "SELECT country k, COUNT(*) c FROM hits WHERE $where AND new_session = 1 GROUP BY country ORDER BY c DESC LIMIT 20", fn($r) => $r['k'] === '' ? '<i>desconocido</i>' : h($r['k']));
    $top('Dispositivos (sesiones)', "SELECT device k, COUNT(*) c FROM hits WHERE $where AND new_session = 1 GROUP BY device ORDER BY c DESC", fn($r) => ['desktop' => 'ordenador', 'mobile' => 'móvil', 'tablet' => 'tableta'][$r['k']] ?? h($r['k']));
    $top('Idioma del navegador (sesiones)', "SELECT lang k, COUNT(*) c FROM hits WHERE $where AND new_session = 1 GROUP BY lang ORDER BY c DESC LIMIT 12", fn($r) => $r['k'] === '' ? '<i>desconocido</i>' : h($r['k']));
    $top('Horas del día (páginas)', "SELECT HOUR(ts) k, COUNT(*) c FROM hits WHERE $where GROUP BY k ORDER BY k", fn($r) => sprintf('%02d h', $r['k']));
    echo "</div></div>";
    echo "<p><small>Visitantes = huellas distintas del día (HMAC de IP+navegador+día, truncado; la IP no se guarda y la huella cambia a diario, por eso el mes suma los días). Sesión = primera página tras " . (int)cfg('analytics_session_minutes', 30) . " min sin actividad. No se cuentan bots conocidos, precargas, POST, admin ni moderación." . (is_admin() ? " Cambia <code>analytics_enabled</code>, <code>analytics_session_minutes</code>, <code>analytics_keep_days</code> y <code>analytics_full_aura</code> en Config." : '') . "</small></p>";
}

/** Ruta legible: los hilos con su titular, las portadas y paginas con su nombre */
function ana_path_label(PDO $pdo, string $path): string {
    static $titles = [];
    if (preg_match('~^/hilo(?:\.php\?id=|/)(\d+)$~', $path, $m)) {   // /hilo/123 (URL limpia, 05/10/2026) o /hilo.php?id=123 (hits antiguos)
        $id = (int)$m[1];
        if (!array_key_exists($id, $titles)) { $st = $pdo->prepare("SELECT title FROM stories WHERE story_id = ?"); $st->execute([$id]); $titles[$id] = $st->fetchColumn(); }
        $t = $titles[$id] ? h(mb_strimwidth((string)$titles[$id], 0, 70, '…')) : "hilo $id";
        return "<a href=\"" . h($path) . "\">$t</a>";
    }
    $names = ['/' => 'Portada (/ · la última elegida)', '/?portada=medios' => 'Portada Medios', '/?portada=substacks' => 'Portada Substacks', '/?portada=canales' => 'Portada Canales', '/radar' => 'Radar', '/originales' => 'Originales', '/chat' => 'Chat',
              '/usuarios' => 'Usuarios', '/procesos' => 'Procesos', '/estadisticas' => 'Estadísticas', '/analitica' => 'Analítica', '/mensajes' => 'Mensajes', '/que-es' => '¿Qué es Deltha?', '/algoritmo' => 'Cómo funciona el δ', '/normas' => 'Normas', '/privacidad' => 'Privacidad', '/temas' => 'Temas', '/comunidades' => 'Comunidades', '/plantilla' => 'Skins', '/login' => 'Entrar', '/registro' => 'Registro', '/enviar' => 'Enviar', '/articulo' => 'Escribir artículo', '/favoritos' => 'Favoritos', '/perfil' => 'Perfil'];
    $key = preg_replace('~\.php(?=\?|$)~', '', $path);   // hits antiguos con .php: mismo nombre
    return "<a href=\"" . h($path) . "\">" . h($names[$key] ?? $path) . "</a>";
}
