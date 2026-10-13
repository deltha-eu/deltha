<?php
/** /estadisticas — cifras publicas del sitio: registrados, conectados ahora, actividad.
 *  Cada cifra que tiene detras una lista enlaza a ella: usuarios (/usuarios pide
 *  login si no lo hay), las dos portadas y el radar, y el flujo de /procesos filtrado
 *  por dia (d=hoy) o sin ventana (d=todo). Las huellas anonimas no tienen lista. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$onlineMin = (int)cfg('online_minutes', 5);
$u = $pdo->query("SELECT COUNT(*) total,
                         SUM(last_seen >= NOW() - INTERVAL $onlineMin MINUTE) online,
                         SUM(created  >= CURDATE()) hoy,
                         SUM(created  >= NOW() - INTERVAL 7 DAY) semana
                    FROM users WHERE verified = 1")->fetch();
$s = $pdo->query("SELECT SUM(status='destacadas' AND portada='medios') medios, SUM(status='destacadas' AND portada='substacks') substacks, SUM(status='destacadas' AND portada='canales') canales,
                         SUM(status='nuevas' AND content_type='link') radar,
                         SUM(published_date >= CURDATE()) publicadas_hoy,
                         SUM(status IN ('destacadas','archived')) publicadas_total
                    FROM stories")->fetch();
$anon = 0;
try { $anon = (int)$pdo->query("SELECT COUNT(*) FROM presence WHERE seen >= NOW() - INTERVAL $onlineMin MINUTE")->fetchColumn(); } catch (Throwable $e) {}
$c = $pdo->query("SELECT COUNT(*) total, SUM(created >= CURDATE()) hoy FROM comments WHERE status='ok'")->fetch();
// salidas a medios por dia (clicks_daily, migra-144): cuanto trafico derivamos; hoy, este mes y este año
$k = ['hoy' => 0, 'mes' => 0, 'anio' => 0];
try { $k = $pdo->query("SELECT COALESCE(SUM(IF(day = CURDATE(), n, 0)),0) hoy, COALESCE(SUM(IF(day >= DATE_FORMAT(CURDATE(), '%Y-%m-01'), n, 0)),0) mes, COALESCE(SUM(IF(day >= DATE_FORMAT(CURDATE(), '%Y-01-01'), n, 0)),0) anio FROM clicks_daily")->fetch() ?: $k; } catch (Throwable $e) {}
$n = fn($v) => number_format((int)$v, 0, ',', '.');
// una ficha: enlace si hay lista detras (las de usuarios llevan a /usuarios, que pide login si no lo hay)
$tile = function ($num, string $label, ?string $href = null) use ($n) {
    $inner = "<b>" . $n($num) . "</b><span>" . h($label) . "</span>";
    return $href ? "<a class=\"tile\" href=\"" . h($href) . "\">$inner</a>" : "<div class=\"tile\">$inner</div>";
};

page_header(t('Estadísticas'), 'estadisticas');
echo "<div class=\"statspage\">";   // sin <h1>: el titulo ya va en la cabecera
echo "<p class=\"notice\">" . t('Cifras en el momento de cargar la página. Cuenta a quien ha hecho algo en los últimos %d minutos. A quien lee sin cuenta lo contamos con una huella no reversible que se borra a los pocos minutos: ni cookies ni direcciones IP guardadas.', $onlineMin) . "</p>";
echo "<div class=\"grid\">";
echo $tile((int)$u['online'] + $anon, t('personas ahora'), '/usuarios?v=conectados');
echo $tile($u['online'], t('con cuenta, conectados'), '/usuarios?v=conectados');
echo $tile($anon, t('sin cuenta, leyendo'));
echo $tile($u['total'], t('usuarios registrados'), '/usuarios');
echo $tile($u['hoy'], t('registros hoy'), '/usuarios?v=hoy');
echo $tile($u['semana'], t('registros últimos 7 días'), '/usuarios?v=7d');
echo $tile($s['medios'], t('en Medios'), '/?portada=medios');
echo $tile($s['substacks'], t('en Substacks'), '/?portada=substacks');
if (portada_canales_on()) echo $tile($s['canales'], t('en Canales'), '/?portada=canales');
echo $tile($s['radar'], t('en el radar'), '/radar');
echo $tile($s['publicadas_hoy'], t('publicadas hoy'), '/procesos?v=portadas&d=hoy');
echo $tile($s['publicadas_total'], t('publicadas en total'), '/procesos?v=portadas&d=todo');
echo $tile($c['hoy'], t('comentarios hoy'), '/procesos?v=comentarios&d=hoy');
echo $tile($c['total'], t('comentarios en total'), '/procesos?v=comentarios&d=todo');
echo "</div>";
echo "<h3 class=\"anah\">" . t('Salidas a los medios') . "</h3><p class=\"meta\" style=\"padding:4px 0 0\">" . t('Clics en el titular de un envío que llevan a la noticia original: el tráfico que %s manda a los medios.', h(cfg('site_name'))) . ' ' . t('(Datos desde el %s.)', h(fecha_larga('2026-10-12', lang()))) . "</p><div class=\"grid\">";
echo $tile($k['hoy'], t('hoy'), '/estadisticas?v=medios&p=hoy');
echo $tile($k['mes'], t('este mes'), '/estadisticas?v=medios&p=mes');
echo $tile($k['anio'], t('este año'), '/estadisticas?v=medios&p=anio');
echo "</div>";
// top de medios (13/10/2026, clicks_source migra-147): al pulsar una ficha, los diez dominios con mas salidas en ese periodo
if (($_GET['v'] ?? '') === 'medios') {
    $P = ['hoy' => [t('hoy'), "day = CURDATE()"], 'mes' => [t('este mes'), "day >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"], 'anio' => [t('este año'), "day >= DATE_FORMAT(CURDATE(), '%Y-01-01')"]];
    $p = isset($P[(string)($_GET['p'] ?? '')]) ? (string)$_GET['p'] : 'mes';
    $top = [];
    try { $top = $pdo->query("SELECT source, SUM(n) n FROM clicks_source WHERE {$P[$p][1]} GROUP BY source ORDER BY n DESC, source LIMIT 10")->fetchAll(); } catch (Throwable $e) {}
    echo "<h3 class=\"anah\" id=\"medios\">" . t('Medios con más salidas: %s', $P[$p][0]) . "</h3>";
    echo "<p class=\"meta\" style=\"padding:4px 0 0\">"; foreach ($P as $pk => $pv) echo ($pk === $p ? "<b>" . h($pv[0]) . "</b>" : "<a href=\"/estadisticas?v=medios&amp;p=$pk#medios\">" . h($pv[0]) . "</a>") . ($pk !== 'anio' ? ' · ' : ''); echo "</p>";
    if (!$top) echo "<p class=\"end\">" . t('Todavía no hay salidas en este periodo.') . "</p>";
    else {
        echo "<ol class=\"topmedios\">";
        foreach ($top as $i => $r) echo "<li><a href=\"/medio/" . rawurlencode($r['source']) . "\">" . h($r['source']) . "</a><b>" . $n($r['n']) . "</b></li>";
        echo "</ol>";
    }
}
if (user()) echo "<p class=\"end\"><a href=\"/usuarios\">" . t('Ver los últimos registrados') . "</a>" . (ana_on() ? " · <a href=\"/analitica\">" . t('Analítica: visitantes, sesiones y páginas vistas') . "</a>" : '') . "</p>";
echo "</div>";
page_footer();
