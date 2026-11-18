<?php
/** /votos-coincidentes — cuentas que votan a la vez (18/11/2026). Solo administradores.
 *  Para ver grupos de cuentas coordinadas (o de una misma mano) sin tocar la IP: la política de privacidad dice que la IP de los
 *  votos no se anota, así que aquí se mira solo qué envío vota cada cuenta, en qué sentido y a qué hora. No se compara la huella
 *  de conexión (votes.fp): sale de la IP y la política solo cuenta con ella para que el δ no sume dos votos de una misma conexión.
 *  Una «coincidencia» de dos cuentas = las dos votan el mismo envío, en el mismo sentido y con menos de `min` minutos de diferencia.
 *    /votos-coincidentes                       parejas con más coincidencias y grupos que forman
 *    /votos-coincidentes?u=nick                solo las parejas de esa cuenta
 *    /votos-coincidentes?a=nick&b=nick         los votos en que coinciden esas dos
 *    &d=días (30, máx. 180) &min=minutos (10) &n=coincidencias mínimas (5) &pct=% mínimo para formar grupo (50)
 *  El % es coincidencias / votos de la cuenta que menos vota de las dos en el periodo: dos cuentas muy activas coinciden a menudo
 *  por azar en los envíos populares, pero no en casi todos sus votos ni con minutos de diferencia. Indicio, no prueba. */
require __DIR__ . '/../lib/app.php';
if (!user()) redirect('/login');
if (!is_admin()) { http_response_code(403); page_header('Votos coincidentes'); echo "<p class=\"end\">Solo administradores.</p>"; page_footer(); exit; }
header('Cache-Control: no-store');

$dias = max(1, min(180, (int)($_GET['d'] ?? 30)));
$mins = max(1, min(240, (int)($_GET['min'] ?? 10)));
$minN = max(2, min(500, (int)($_GET['n'] ?? 5)));
$pct  = max(1, min(100, (int)($_GET['pct'] ?? 50)));
$qu = trim(mb_substr((string)($_GET['u'] ?? ''), 0, 32));
$qa = trim(mb_substr((string)($_GET['a'] ?? ''), 0, 32));
$qb = trim(mb_substr((string)($_GET['b'] ?? ''), 0, 32));
$qs = "d=$dias&amp;min=$mins&amp;n=$minN&amp;pct=$pct";

$fecha = fn($d) => h(date('d/m/Y H:i:s', strtotime((string)$d)));
$ua    = fn($l) => "<a href=\"/votos-coincidentes?u=" . rawurlencode((string)$l) . "&amp;$qs\">" . h((string)$l) . "</a>";
$uid_de = function (string $login) use ($pdo): ?array {
    $st = $pdo->prepare("SELECT user_id, login, aura, created FROM users WHERE login = ?"); $st->execute([$login]);
    return $st->fetch() ?: null;
};

page_header('Votos coincidentes');
echo "<div class=\"admin\"><nav class=\"tabs\"><a href=\"/admin\">← Admin</a><a href=\"/ips\">IP</a><a href=\"/votos-coincidentes\" class=\"on\">Votos coincidentes</a></nav><h1>Votos coincidentes</h1>";
echo "<p><small>Cuentas que votan <b>el mismo envío, en el mismo sentido y con menos de $mins min de diferencia</b>, en los últimos $dias días. "
   . "No usa la IP ni la huella de conexión de los votos: solo qué vota cada cuenta y cuándo. "
   . "El % es sobre los votos de la cuenta que menos vota de las dos. Es un indicio, no una prueba: mira el detalle antes de actuar. Solo lo ve el administrador.</small></p>";
echo "<form method=\"get\" action=\"/votos-coincidentes\" class=\"buscar-u\"><input type=\"text\" name=\"u\" value=\"" . h($qu) . "\" placeholder=\"usuario (opcional)\" maxlength=\"32\"> "
   . "<label>días <input type=\"number\" name=\"d\" min=\"1\" max=\"180\" value=\"$dias\" style=\"width:5em\"></label> "
   . "<label>minutos <input type=\"number\" name=\"min\" min=\"1\" max=\"240\" value=\"$mins\" style=\"width:5em\"></label> "
   . "<label>mínimo <input type=\"number\" name=\"n\" min=\"2\" max=\"500\" value=\"$minN\" style=\"width:5em\"></label> "
   . "<label>% grupo <input type=\"number\" name=\"pct\" min=\"1\" max=\"100\" value=\"$pct\" style=\"width:5em\"></label> "
   . "<button class=\"btn sm\">Ver</button></form>";

try {
    /* ---- detalle de una pareja ---- */
    if ($qa !== '' && $qb !== '') {
        $A = $uid_de($qa); $B = $uid_de($qb);
        if (!$A || !$B || (int)$A['user_id'] === (int)$B['user_id']) echo "<p class=\"error\">Hacen falta dos cuentas distintas que existan.</p>";
        else {
            $st = $pdo->prepare("SELECT s.story_id, s.title, s.status, au.login AS autor, x.value, x.created AS ta, y.created AS tb,
                                        TIMESTAMPDIFF(SECOND, x.created, y.created) AS dif
                                   FROM votes x JOIN votes y ON y.story_id = x.story_id AND y.value = x.value
                                   JOIN stories s ON s.story_id = x.story_id LEFT JOIN users au ON au.user_id = s.user_id
                                  WHERE x.user_id = ? AND y.user_id = ? AND x.created > NOW() - INTERVAL $dias DAY
                                    AND ABS(TIMESTAMPDIFF(SECOND, x.created, y.created)) <= ?
                                  ORDER BY x.created DESC LIMIT 500");
            $st->execute([(int)$A['user_id'], (int)$B['user_id'], $mins * 60]);
            $rows = $st->fetchAll();
            $tot = $pdo->prepare("SELECT user_id, COUNT(*) FROM votes WHERE user_id IN (?,?) AND created > NOW() - INTERVAL $dias DAY GROUP BY user_id");
            $tot->execute([(int)$A['user_id'], (int)$B['user_id']]); $T = $tot->fetchAll(PDO::FETCH_KEY_PAIR);
            echo "<h3>" . $ua($A['login']) . " y " . $ua($B['login']) . ": " . count($rows) . " coincidencias</h3>";
            echo "<p><small>" . h($A['login']) . ": " . (int)($T[$A['user_id']] ?? 0) . " votos en el periodo, cuenta del " . h(date('d/m/Y', strtotime((string)$A['created']))) . " · "
               . h($B['login']) . ": " . (int)($T[$B['user_id']] ?? 0) . " votos, cuenta del " . h(date('d/m/Y', strtotime((string)$B['created']))) . ". «Dif.» = cuánto después votó la segunda (negativo: antes).</small></p>";
            if (!$rows) echo "<p class=\"end\">Ninguna con estos límites.</p>";
            else {
                echo "<table class=\"modlog\"><tr><th>envío</th><th>de</th><th>voto</th><th>" . h($A['login']) . "</th><th>dif.</th></tr>";
                foreach ($rows as $r) {
                    $d = (int)$r['dif']; $ad = abs($d);
                    $dif = ($d < 0 ? '−' : '+') . ($ad >= 60 ? intdiv($ad, 60) . ' min ' : '') . ($ad % 60) . ' s';
                    echo "<tr><td><a href=\"/hilo/" . (int)$r['story_id'] . "\">" . h(mb_strimwidth((string)$r['title'], 0, 70, '…')) . "</a></td>"
                       . "<td>" . ($r['autor'] !== null ? $ua($r['autor']) : '<small class="meta">anónimo</small>') . "</td>"
                       . "<td>" . ((int)$r['value'] > 0 ? '+1' : '−1') . "</td><td style=\"white-space:nowrap\">" . $fecha($r['ta']) . "</td><td style=\"white-space:nowrap\">$dif</td></tr>";
                }
                echo "</table>";
            }
        }
    /* ---- parejas y grupos ---- */
    } else {
        $foco = null;
        if ($qu !== '' && !($foco = $uid_de($qu))) echo "<p class=\"error\">Ese usuario no existe.</p>";
        $w = $foco ? " AND (x.user_id = " . (int)$foco['user_id'] . " OR y.user_id = " . (int)$foco['user_id'] . ")" : '';
        $st = $pdo->prepare("SELECT x.user_id AS a, y.user_id AS b, COUNT(*) AS n, MAX(GREATEST(x.created, y.created)) AS ultimo
                               FROM votes x JOIN votes y ON y.story_id = x.story_id AND y.value = x.value AND x.user_id < y.user_id
                              WHERE x.created > NOW() - INTERVAL $dias DAY AND ABS(TIMESTAMPDIFF(SECOND, x.created, y.created)) <= ?$w
                              GROUP BY x.user_id, y.user_id HAVING n >= ? ORDER BY n DESC, ultimo DESC LIMIT 300");
        $st->execute([$mins * 60, $minN]);
        $pares = $st->fetchAll();
        $ids = []; foreach ($pares as $p) { $ids[(int)$p['a']] = 1; $ids[(int)$p['b']] = 1; }
        $U = []; $T = [];
        if ($ids) {
            $in = implode(',', array_keys($ids));
            foreach ($pdo->query("SELECT user_id, login, aura, level, created FROM users WHERE user_id IN ($in)") as $r) $U[(int)$r['user_id']] = $r;
            $T = $pdo->query("SELECT user_id, COUNT(*) FROM votes WHERE user_id IN ($in) AND created > NOW() - INTERVAL $dias DAY GROUP BY user_id")->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        $share = fn($p) => (int)round(100 * (int)$p['n'] / max(1, min((int)($T[$p['a']] ?? 0), (int)($T[$p['b']] ?? 0))));
        usort($pares, fn($p, $q) => ($share($q) <=> $share($p)) ?: ((int)$q['n'] <=> (int)$p['n']));   // primero las que coinciden en casi todo

        // grupos: componentes conexas de las parejas que pasan del % (union-find)
        $padre = [];
        $raiz = function (int $x) use (&$padre, &$raiz): int { $padre[$x] ??= $x; return $padre[$x] === $x ? $x : ($padre[$x] = $raiz($padre[$x])); };
        foreach ($pares as $p) if ($share($p) >= $pct) $padre[$raiz((int)$p['a'])] = $raiz((int)$p['b']);
        $grupos = [];
        foreach (array_keys($padre) as $x) $grupos[$raiz($x)][] = $x;
        $grupos = array_filter($grupos, fn($g) => count($g) >= 2);
        usort($grupos, fn($g, $h) => count($h) <=> count($g));

        echo "<h3>Grupos (" . count($grupos) . ")" . ($foco ? " · con " . h($foco['login']) : '') . "</h3>";
        echo "<p><small>Cuentas enlazadas por parejas con al menos $minN coincidencias y un $pct % o más de los votos de la que menos vota.</small></p>";
        if (!$grupos) echo "<p class=\"end\">Ninguno con estos límites.</p>";
        foreach ($grupos as $g) {
            echo "<p><b>" . count($g) . " cuentas:</b> ";
            $out = [];
            foreach ($g as $x) $out[] = (isset($U[$x]) ? $ua($U[$x]['login']) : "#$x") . " <small class=\"meta\">(aura " . number_format((float)($U[$x]['aura'] ?? 0), 1, ',', '') . ", " . (int)($T[$x] ?? 0) . " votos, desde " . (isset($U[$x]) ? h(date('d/m/Y', strtotime((string)$U[$x]['created']))) : '?') . ")</small>";
            echo implode(' · ', $out) . "</p>";
        }

        echo "<h3>Parejas (" . count($pares) . ")</h3>";
        if (!$pares) echo "<p class=\"end\">Ninguna pareja llega a $minN coincidencias.</p>";
        else {
            echo "<table class=\"modlog\"><tr><th>cuenta</th><th>cuenta</th><th>coinciden</th><th>%</th><th>votos de cada una</th><th>última</th><th></th></tr>";
            foreach ($pares as $p) {
                $la = $U[(int)$p['a']]['login'] ?? null; $lb = $U[(int)$p['b']]['login'] ?? null;
                if ($la === null || $lb === null) continue;
                $s = $share($p);
                echo "<tr><td>" . $ua($la) . "</td><td>" . $ua($lb) . "</td><td>" . (int)$p['n'] . "</td><td>" . ($s >= $pct ? "<b>$s %</b>" : "$s %") . "</td>"
                   . "<td>" . (int)($T[$p['a']] ?? 0) . " · " . (int)($T[$p['b']] ?? 0) . "</td>"
                   . "<td style=\"white-space:nowrap\">" . $fecha($p['ultimo']) . "</td>"
                   . "<td><a href=\"/votos-coincidentes?a=" . rawurlencode($la) . "&amp;b=" . rawurlencode($lb) . "&amp;$qs\">detalle</a></td></tr>";
            }
            echo "</table>";
        }
    }
} catch (Throwable $e) { echo "<p class=\"error\">No se ha podido calcular: " . h($e->getMessage()) . "</p>"; }
echo "</div>";
page_footer();
