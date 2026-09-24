<?php
/** /estadisticas.php — cifras publicas del sitio: registrados, conectados ahora, actividad.
 *  Cada cifra que tiene detras una lista enlaza a ella: usuarios (/usuarios.php pide
 *  login si no lo hay), las dos portadas y el radar, y el flujo de /procesos.php filtrado
 *  por dia (d=hoy) o sin ventana (d=todo). Las huellas anonimas no tienen lista. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$onlineMin = (int)cfg('online_minutes', 5);
$u = $pdo->query("SELECT COUNT(*) total,
                         SUM(last_seen >= NOW() - INTERVAL $onlineMin MINUTE) online,
                         SUM(created  >= CURDATE()) hoy,
                         SUM(created  >= NOW() - INTERVAL 7 DAY) semana
                    FROM users WHERE verified = 1")->fetch();
$s = $pdo->query("SELECT SUM(status='destacadas' AND portada='medios') medios, SUM(status='destacadas' AND portada='substacks') substacks,
                         SUM(status='nuevas' AND content_type='link') radar,
                         SUM(published_date >= CURDATE()) publicadas_hoy,
                         SUM(status IN ('destacadas','archived')) publicadas_total
                    FROM stories")->fetch();
$anon = 0;
try { $anon = (int)$pdo->query("SELECT COUNT(*) FROM presence WHERE seen >= NOW() - INTERVAL $onlineMin MINUTE")->fetchColumn(); } catch (Throwable $e) {}
$c = $pdo->query("SELECT COUNT(*) total, SUM(created >= CURDATE()) hoy FROM comments WHERE status='ok'")->fetch();
$n = fn($v) => number_format((int)$v, 0, ',', '.');
// una ficha: enlace si hay lista detras (las de usuarios llevan a /usuarios.php, que pide login si no lo hay)
$tile = function ($num, string $label, ?string $href = null) use ($n) {
    $inner = "<b>" . $n($num) . "</b><span>" . h($label) . "</span>";
    return $href ? "<a class=\"tile\" href=\"" . h($href) . "\">$inner</a>" : "<div class=\"tile\">$inner</div>";
};

page_header(t('Estadísticas'), 'estadisticas');
echo "<div class=\"statspage\">" . t('<h1>Estadísticas</h1>') . "";
echo "<p class=\"notice\">" . t('Cifras en el momento de cargar la página. Cuenta a quien ha hecho algo en los últimos %d minutos. A quien lee sin cuenta lo contamos con una huella no reversible que se borra a los pocos minutos: ni cookies ni direcciones IP guardadas.', $onlineMin) . "</p>";
echo "<div class=\"grid\">";
echo $tile((int)$u['online'] + $anon, t('personas ahora'), '/usuarios.php?v=conectados');
echo $tile($u['online'], t('con cuenta, conectados'), '/usuarios.php?v=conectados');
echo $tile($anon, t('sin cuenta, leyendo'));
echo $tile($u['total'], t('usuarios registrados'), '/usuarios.php');
echo $tile($u['hoy'], t('registros hoy'), '/usuarios.php?v=hoy');
echo $tile($u['semana'], t('registros últimos 7 días'), '/usuarios.php?v=7d');
echo $tile($s['medios'], t('en Medios'), '/?portada=medios');
echo $tile($s['substacks'], t('en Substacks'), '/?portada=substacks');
echo $tile($s['radar'], t('en el radar'), '/radar.php');
echo $tile($s['publicadas_hoy'], t('publicadas hoy'), '/procesos.php?v=portadas&d=hoy');
echo $tile($s['publicadas_total'], t('publicadas en total'), '/procesos.php?v=portadas&d=todo');
echo $tile($c['hoy'], t('comentarios hoy'), '/procesos.php?v=comentarios&d=hoy');
echo $tile($c['total'], t('comentarios en total'), '/procesos.php?v=comentarios&d=todo');
echo "</div>";
if (user()) echo "<p class=\"end\"><a href=\"/usuarios.php\">" . t('Ver los últimos registrados') . "</a></p>";
echo "</div>";
page_footer();
