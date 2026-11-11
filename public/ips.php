<?php
/** /ips — registro de IP con caducidad (migra-193, lib/iplog.php). Solo administradores: ni moderadores ni moderación por aura.
 *  Sirve para dos cosas: ver si varias cuentas comparten conexión (multicuentas) y localizar la IP de una publicación concreta
 *  cuando llega un requerimiento formal de un juzgado o de la policía. Cada consulta queda en el registro de moderación.
 *    /ips?u=nick                desde qué IP actúa la cuenta y qué otras cuentas han actuado desde ellas
 *    /ips?ip=1.2.3.4            todo lo apuntado desde esa IP
 *    /ips?k=comment&ref=123     la IP de un envío (story), comentario (comment) o mensaje del Chat (chat) */
require __DIR__ . '/../lib/app.php';
if (!user()) redirect('/login');
if (!is_admin()) { http_response_code(403); page_header('Registro de IP'); echo "<p class=\"end\">Solo administradores.</p>"; page_footer(); exit; }
header('Cache-Control: no-store');

$days = ip_log_days();
$qu  = trim(mb_substr((string)($_GET['u'] ?? ''), 0, 32));
$qip = trim(mb_substr((string)($_GET['ip'] ?? ''), 0, 45));
$qk  = (string)($_GET['k'] ?? '');
$qref = (int)($_GET['ref'] ?? 0);
if (!in_array($qk, ['story', 'comment', 'chat'], true)) $qk = '';

$fecha = fn($d) => h(date('d/m/Y H:i:s', strtotime((string)$d)));
$ipa   = fn($ip) => "<a href=\"/ips?ip=" . rawurlencode((string)$ip) . "\"><code>" . h((string)$ip) . "</code></a>";
$ua    = fn($l) => "<a href=\"/ips?u=" . rawurlencode((string)$l) . "\">" . h((string)$l) . "</a>";
/** Tabla de filas del registro. */
$tabla = function (array $rows) use ($pdo, $fecha, $ipa, $ua) {
    if (!$rows) { echo "<p class=\"end\">Nada apuntado.</p>"; return; }
    echo "<table class=\"modlog\"><tr><th>fecha y hora</th><th>quién</th><th>qué</th><th>IP</th></tr>";
    foreach ($rows as $r) {
        $href = ip_log_href($pdo, $r);
        $que = h(IP_LOG_KINDS[$r['kind']] ?? $r['kind']) . ((int)$r['ref_id'] ? ' #' . (int)$r['ref_id'] : '');
        echo "<tr><td style=\"white-space:nowrap\">" . $fecha($r['created']) . "</td>"
           . "<td>" . ($r['user_id'] ? ($r['login'] !== null ? $ua($r['login']) : '<small class="meta">cuenta #' . (int)$r['user_id'] . ' (ya no existe)</small>') : '<small class="meta">anónimo</small>') . "</td>"
           . "<td>" . ($href !== '' ? "<a href=\"" . h($href) . "\">$que</a>" : $que) . "</td><td>" . $ipa($r['ip']) . "</td></tr>";
    }
    echo "</table>";
};

page_header('Registro de IP');
echo "<div class=\"admin\"><h1>Registro de IP</h1>";
echo "<p><small>Desde qué IP se registra, entra y publica cada cual (los votos no se apuntan). Se guarda para dos cosas: detectar multicuentas y responder a un requerimiento formal, por escrito, de un juzgado o de la policía. "
   . ($days > 0 ? "Cada fila se borra sola a los <b>$days días</b> (<code>ip_log_days</code> en Config)." : "<b>Ahora está apagado</b> (<code>ip_log_days</code> = 0): no se guarda nada.")
   . " Solo lo ve el administrador y <b>cada consulta queda apuntada</b> en el <a href=\"/moderacion?t=registro\">registro de moderación</a>.</small></p>";
echo "<form method=\"get\" action=\"/ips\" class=\"buscar-u\"><input type=\"text\" name=\"u\" value=\"" . h($qu) . "\" placeholder=\"usuario\" maxlength=\"32\"> <input type=\"text\" name=\"ip\" value=\"" . h($qip) . "\" placeholder=\"IP\" maxlength=\"45\"> <button class=\"btn sm\">Buscar</button></form>";
echo "<form method=\"get\" action=\"/ips\" class=\"buscar-u\"><select name=\"k\">";
foreach (['story' => 'envío', 'comment' => 'comentario', 'chat' => 'mensaje del Chat'] as $k => $n) echo "<option value=\"$k\"" . ($qk === $k ? ' selected' : '') . ">$n</option>";
echo "</select> <input type=\"number\" name=\"ref\" min=\"1\" value=\"" . ($qref ?: '') . "\" placeholder=\"número (#)\"> <button class=\"btn sm\">Ver su IP</button></form>";

try {
    if ($qu !== '') {
        $st = $pdo->prepare("SELECT user_id, login, level, created FROM users WHERE login = ?"); $st->execute([$qu]);
        $p = $st->fetch();
        if (!$p) echo "<p class=\"error\">Ese usuario no existe.</p>";
        else {
            $uid = (int)$p['user_id'];
            ip_log_audit($pdo, "usuario #$uid {$p['login']}");
            echo "<h3><a href=\"/u/" . rawurlencode($p['login']) . "\">" . h($p['login']) . "</a> <small class=\"meta\">· cuenta #$uid · desde " . h(date('d/m/Y', strtotime((string)$p['created']))) . "</small></h3>";

            $comunes = ip_log_cuentas_comunes($pdo, $uid);
            echo "<h3>Otras cuentas desde las mismas IP (" . count($comunes) . ")</h3>";
            echo "<p><small>Coincidir no prueba que sean la misma persona: una casa, una oficina, una universidad o una red móvil comparten IP. Pesa más cuantas más IP distintas comparten y cuanto más cerca están las fechas.</small></p>";
            if (!$comunes) echo "<p class=\"end\">Ninguna.</p>";
            else {
                echo "<table class=\"modlog\"><tr><th>cuenta</th><th>nivel</th><th>creada</th><th>IP en común</th><th>apuntes desde ellas</th><th>último</th></tr>";
                foreach ($comunes as $c) echo "<tr><td>" . $ua($c['login']) . "</td><td>" . h((string)$c['level']) . "</td><td>" . h(date('d/m/Y', strtotime((string)$c['created']))) . "</td><td>" . (int)$c['ips'] . "</td><td>" . (int)$c['n'] . "</td><td>" . $fecha($c['last']) . "</td></tr>";
                echo "</table>";
            }

            $ips = ip_log_de_usuario($pdo, $uid);
            echo "<h3>Sus IP (" . count($ips) . ")</h3>";
            if (!$ips) echo "<p class=\"end\">Nada apuntado" . ($days > 0 ? " en los últimos $days días" : '') . ".</p>";
            else {
                echo "<table class=\"modlog\"><tr><th>IP</th><th>apuntes</th><th>primero</th><th>último</th></tr>";
                foreach ($ips as $i) echo "<tr><td>" . $ipa($i['ip']) . "</td><td>" . (int)$i['n'] . "</td><td>" . $fecha($i['first']) . "</td><td>" . $fecha($i['last']) . "</td></tr>";
                echo "</table>";
            }
            echo "<h3>Últimos apuntes</h3>";
            $tabla(ip_log_filas($pdo, 'user', $uid, 200));
        }
    } elseif ($qip !== '') {
        if (!filter_var($qip, FILTER_VALIDATE_IP)) echo "<p class=\"error\">Eso no es una dirección IP.</p>";
        else {
            ip_log_audit($pdo, "ip " . ip_log_mask($qip));   // el registro de moderación lo leen también los moderadores: la IP va recortada
            echo "<h3>Apuntes desde <code>" . h($qip) . "</code></h3>";
            $tabla(ip_log_filas($pdo, 'ip', $qip, 500));
        }
    } elseif ($qk !== '' && $qref > 0) {
        ip_log_audit($pdo, IP_LOG_KINDS[$qk] . " #$qref");
        echo "<h3>IP de: " . h(IP_LOG_KINDS[$qk]) . " #$qref</h3>";
        $tabla(ip_log_filas($pdo, 'item', [$qk, $qref], 20));
    } else {
        $n = $pdo->query("SELECT COUNT(*) AS n, MIN(created) AS first FROM ip_log")->fetch();
        echo "<p><small>" . (int)$n['n'] . " apuntes" . ($n['first'] ? ", el más antiguo del " . h(date('d/m/Y', strtotime((string)$n['first']))) : '') . ". Busca por usuario, por IP o por el número de un envío, comentario o mensaje.</small></p>";
    }
} catch (Throwable $e) {
    echo "<p class=\"error\">Falta la migración 193 (tabla <code>ip_log</code>).</p>";
}
echo "</div>";
page_footer();
