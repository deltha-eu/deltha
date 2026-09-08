<?php
/** /estadisticas.php — cifras publicas del sitio: registrados, conectados ahora, actividad. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$onlineMin = (int)cfg('online_minutes', 5);
$u = $pdo->query("SELECT COUNT(*) total,
                         SUM(last_seen >= NOW() - INTERVAL $onlineMin MINUTE) online,
                         SUM(created  >= CURDATE()) hoy,
                         SUM(created  >= NOW() - INTERVAL 7 DAY) semana
                    FROM users WHERE verified = 1")->fetch();
$s = $pdo->query("SELECT SUM(status='destacadas') portada, SUM(status='candidatas') candidatas, SUM(status='nuevas') nuevas,
                         SUM(published_date >= CURDATE()) publicadas_hoy,
                         SUM(status IN ('destacadas','archived')) publicadas_total
                    FROM stories")->fetch();
$anon = 0;
try { $anon = (int)$pdo->query("SELECT COUNT(*) FROM presence WHERE seen >= NOW() - INTERVAL $onlineMin MINUTE")->fetchColumn(); } catch (Throwable $e) {}
$c = $pdo->query("SELECT COUNT(*) total, SUM(created >= CURDATE()) hoy FROM comments WHERE status='ok'")->fetch();
$n = fn($v) => number_format((int)$v, 0, ',', '.');

page_header('Estadísticas', 'estadisticas');
echo "<div class=\"statspage\"><h1>Estadísticas</h1>";
echo "<p class=\"notice\">Cifras en el momento de cargar la página. Cuenta a quien ha hecho algo en los últimos $onlineMin minutos. A quien lee sin cuenta lo contamos con una huella no reversible que se borra a los pocos minutos: ni cookies ni direcciones IP guardadas.</p>";
echo "<div class=\"grid\">";
echo "<div class=\"tile\"><b>" . $n((int)$u['online'] + $anon) . "</b><span>personas ahora</span></div>";
echo "<div class=\"tile\"><b>" . $n($u['online']) . "</b><span>con cuenta, conectados</span></div>";
echo "<div class=\"tile\"><b>" . $n($anon) . "</b><span>sin cuenta, leyendo</span></div>";
echo "<div class=\"tile\"><b>" . $n($u['total']) . "</b><span>usuarios registrados</span></div>";
echo "<div class=\"tile\"><b>" . $n($u['hoy']) . "</b><span>registros hoy</span></div>";
echo "<div class=\"tile\"><b>" . $n($u['semana']) . "</b><span>registros últimos 7 días</span></div>";
echo "<div class=\"tile\"><b>" . $n($s['portada']) . "</b><span>en portada</span></div>";
echo "<div class=\"tile\"><b>" . $n($s['candidatas']) . "</b><span>candidatas</span></div>";
echo "<div class=\"tile\"><b>" . $n($s['nuevas']) . "</b><span>nuevas</span></div>";
echo "<div class=\"tile\"><b>" . $n($s['publicadas_hoy']) . "</b><span>publicadas hoy</span></div>";
echo "<div class=\"tile\"><b>" . $n($s['publicadas_total']) . "</b><span>publicadas en total</span></div>";
echo "<div class=\"tile\"><b>" . $n($c['hoy']) . "</b><span>comentarios hoy</span></div>";
echo "<div class=\"tile\"><b>" . $n($c['total']) . "</b><span>comentarios en total</span></div>";
echo "</div>";
if (user()) echo "<p class=\"end\"><a href=\"/usuarios.php\">Ver los últimos registrados</a></p>";
echo "</div>";
page_footer();
