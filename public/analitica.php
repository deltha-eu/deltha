<?php
/** /analitica — analitica propia, para registrados, en tres niveles (13/11/2026; para todo el mundo esta /estadisticas):
 *   · cualquier registrado: visitantes, sesiones y paginas vistas de ayer, del mes y del año (cifras cerradas por dias, ana_daily) y la actividad;
 *   · desde aura analytics_live_aura (10; ana_live()), la version limitada: ademas, «Ahora» y «Hoy» de lib/analitica-admin.php;
 *   · con aura por encima de analytics_full_aura (50) y el admin (ana_full()), todo: ademas, salidas por visitante, uso de las
 *     relacionadas y de «Por fechas» hacia abajo (paginas, procedencia, paises…). No se carga por defecto: entran viendo la
 *     limitada, con un boton «Ver la analitica completa» (?todo=1) que la añade.
 *  Con «Ahora» a la vista, recarga automatica cada minuto (auto=1 para que no cuente como pagina vista). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
if (!user()) redirect('/login');

$ayer = date('Y-m-d', strtotime('yesterday'));
$mes  = date('Y-m-01');
$anio = date('Y-01-01');
$first = $pdo->query("SELECT MIN(day) FROM ana_daily")->fetchColumn();
if (($since = ana_since()) !== '' && (!$first || $first < $since)) $first = $since;   // lo anterior a la apertura (beta) no se enseña
$dAyer = ana_sum($pdo, $ayer, $ayer);
$dMes  = $ayer >= $mes  ? ana_sum($pdo, $mes, $ayer)  : ['visitors' => 0, 'sessions' => 0, 'pageviews' => 0];   // dia 1: el mes aun no tiene dias cerrados
$dAnio = $ayer >= $anio ? ana_sum($pdo, $anio, $ayer) : ['visitors' => 0, 'sessions' => 0, 'pageviews' => 0];
$n = fn($v) => number_format((int)$v, 0, ',', '.');
$tile = fn($num, string $label) => "<div class=\"tile\"><b>" . $n($num) . "</b><span>" . h($label) . "</span></div>";

$puedeTodo = ana_full(); $live = ana_live();
$full = $puedeTodo && ($_GET['todo'] ?? '') === '1';   // la completa solo al pedirla: por defecto, la limitada (no se cargan sus consultas)
page_header(t('Analítica'), 'analitica', $live ? refresh_meta(60) : '');
echo "<div class=\"statspage\">";
echo "<p class=\"notice ayuda\" data-k=\"analitica\">" . t('Cifras cerradas por días: se actualizan una vez al día, de madrugada. Medición propia, sin cookies ni servicios de terceros: contamos cada página vista con una huella no reversible que cambia cada día, así que «visitantes» son personas distintas por día (el mes y el año suman los días).') . "</p>";
if ($first && $first > $anio) echo "<p class=\"meta\">" . t('Datos desde el %s.', date('d/m/Y', strtotime($first))) . "</p>";
foreach ([['visitors', t('visitantes')], ['sessions', t('sesiones')], ['pageviews', t('páginas vistas')]] as [$k, $lbl]) {
    echo "<h3 class=\"anah\">" . h(ucfirst($lbl)) . "</h3><div class=\"grid\">";
    echo $tile($dAyer[$k], t('ayer'));
    echo $tile($dMes[$k], t('este mes'));
    echo $tile($dAnio[$k], t('este año'));
    echo "</div>";
}
if ($full) {   // salidas por visitante y uso de las relacionadas: solo en la version completa (13/11/2026)
// salidas por visitante (13/11/2026, pedido por Angel): ¿la gente pulsa o solo mira titulares? Clics que llevan a un medio (clicks_daily,
// los mismos de /estadisticas) entre los visitantes de esos dias. Mes y año: suma de salidas entre suma de visitantes de cada dia.
$sal = function (string $from, string $to) use ($pdo): int {
    if (($since = ana_since()) !== '' && $from < $since) $from = $since;
    try { $st = $pdo->prepare("SELECT COALESCE(SUM(n),0) FROM clicks_daily WHERE day BETWEEN ? AND ?"); $st->execute([$from, $to]); return (int)$st->fetchColumn(); }
    catch (Throwable $e) { return 0; }   // tabla aun sin crear (migra-144)
};
$tasa = function (int $salidas, int $visitantes, string $label) use ($n): string {
    return "<div class=\"tile\"><b>" . ($visitantes > 0 ? number_format($salidas / $visitantes, 2, ',', '.') : '—') . "</b><span>" . h($label) . " · " . h(t('%s salidas', $n($salidas))) . "</span></div>";
};
echo "<h3 class=\"anah\">" . t('Salidas por visitante') . "</h3><p class=\"meta\" style=\"padding:4px 0 0\">" . t('Clics que llevan a un medio, un substack o un canal, divididos entre los visitantes del mismo periodo: cuánto se pulsa además de mirar titulares.') . "</p><div class=\"grid\">";
echo $tasa($sal($ayer, $ayer), (int)$dAyer['visitors'], t('ayer'));
echo $tasa($ayer >= $mes  ? $sal($mes, $ayer)  : 0, (int)$dMes['visitors'], t('este mes'));
echo $tasa($ayer >= $anio ? $sal($anio, $ayer) : 0, (int)$dAnio['visitors'], t('este año'));
echo "</div>";
// uso de las «Relacionadas» del hilo (migra-200, 13/11/2026): de cada 100 veces que se enseña la caja, cuantas se pulsa
$relT = function (string $from, string $to, string $label) use ($pdo, $n): string {
    if (($since = ana_since()) !== '' && $from < $since) $from = $since;
    $r = $from <= $to ? rel_sum($pdo, $from, $to) : ['shown' => 0, 'clicks' => 0];
    return "<div class=\"tile\"><b>" . ($r['shown'] > 0 ? number_format(100 * $r['clicks'] / $r['shown'], 1, ',', '.') . ' %' : '—') . "</b><span>" . h($label) . " · " . h(t('%s clics de %s veces mostradas', $n($r['clicks']), $n($r['shown']))) . "</span></div>";
};
echo "<h3 class=\"anah\">" . t('Uso de las relacionadas') . "</h3><p class=\"meta\" style=\"padding:4px 0 0\">" . t('De las veces que se enseña la caja «Relacionadas» bajo un envío, en cuántas se pulsa una de sus noticias. Se mide desde el 13/11/2026.') . "</p><div class=\"grid\">";
echo $relT($ayer, $ayer, t('ayer'));
echo $relT($mes, $ayer, t('este mes'));
echo $relT($anio, $ayer, t('este año'));
echo "</div>";
}
// actividad (19/10/2026, pedido por Angel): envios, portadas, votos y comentarios de ayer, del mes y del año, de las tablas de verdad
$cnt = function (string $sql, string $from, string $to) use ($pdo): int { $st = $pdo->prepare($sql); $st->execute([$from, $to]); return (int)$st->fetchColumn(); };
$act = [
    ['envíos',      "SELECT COUNT(*) FROM stories WHERE sent_date >= ? AND sent_date < ? + INTERVAL 1 DAY"],
    ['portadas',    "SELECT COUNT(*) FROM stories WHERE status = 'destacadas' AND published_date >= ? AND published_date < ? + INTERVAL 1 DAY"],
    ['votos',       "SELECT COUNT(*) FROM votes WHERE created >= ? AND created < ? + INTERVAL 1 DAY"],
    ['comentarios', "SELECT COUNT(*) FROM comments WHERE deleted = 0 AND created >= ? AND created < ? + INTERVAL 1 DAY"],
];
foreach ($act as [$lbl, $sql]) {
    echo "<h3 class=\"anah\">" . h(ucfirst(t($lbl))) . "</h3><div class=\"grid\">";
    echo $tile($cnt($sql, $ayer, $ayer), t('ayer'));
    echo $tile($ayer >= $mes  ? $cnt($sql, $mes, $ayer)  : 0, t('este mes'));
    echo $tile($ayer >= $anio ? $cnt($sql, $anio, $ayer) : 0, t('este año'));
    echo "</div>";
}
if ($live) {
    require __DIR__ . '/../lib/analitica-admin.php';
    echo "<div class=\"anafull\">" . ($full ? "<h2>" . t('Analítica completa') . "</h2>" : '');
    ana_admin($pdo, $full ? '/analitica?todo=1' : '/analitica', !$full);   // limitada: «Ahora» y «Hoy»; de «Por fechas» hacia abajo, solo la completa
    echo "</div>";
}
$fmt = fn(string $k, float $d) => rtrim(rtrim(number_format((float)cfg($k, $d), 2, ',', '.'), '0'), ',');
if (!$live && (float)cfg('analytics_live_aura', 10) > 0) echo "<p class=\"meta\">" . t('Con aura %s o más verás también lo que se está leyendo ahora y el día de hoy.', $fmt('analytics_live_aura', 10)) . "</p>";
elseif ($puedeTodo) echo $full ? "<p class=\"meta\"><a href=\"/analitica\">" . t('Ver menos') . "</a></p>"
                              : "<form method=\"get\" action=\"/analitica\" class=\"anatodo\"><input type=\"hidden\" name=\"todo\" value=\"1\"><button>" . t('Ver la analítica completa') . "</button></form>";
elseif ((float)cfg('analytics_full_aura', 50) > 0) echo "<p class=\"meta\">" . t('Con más de %s de aura verás aquí la analítica completa: cualquier rango de fechas con páginas, procedencia, países y dispositivos.', $fmt('analytics_full_aura', 50)) . "</p>";
echo "</div>";
page_footer();
