<?php
/** /analitica — analitica propia, para registrados: visitantes, sesiones y paginas vistas de ayer, del mes y del año.
 *  Cifras cerradas por dias (ana_daily, lib/analitica.php): cambian una vez al dia. Desde aura analytics_full_aura (10) y para el admin
 *  (ana_full()), debajo va la analitica completa de admin (lib/analitica-admin.php: ahora, hoy, por fechas, paginas, procedencia, paises…),
 *  con recarga automatica cada minuto (auto=1 para que no cuente como pagina vista). 16/10/2026. */
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

$full = ana_full();
page_header(t('Analítica'), 'analitica', $full ? refresh_meta(60) : '');
echo "<div class=\"statspage\">";
echo "<p class=\"notice\">" . t('Cifras cerradas por días: se actualizan una vez al día, de madrugada. Medición propia, sin cookies ni servicios de terceros: contamos cada página vista con una huella no reversible que cambia cada día, así que «visitantes» son personas distintas por día (el mes y el año suman los días).') . "</p>";
if ($first && $first > $anio) echo "<p class=\"meta\">" . t('Datos desde el %s.', date('d/m/Y', strtotime($first))) . "</p>";
foreach ([['visitors', t('visitantes')], ['sessions', t('sesiones')], ['pageviews', t('páginas vistas')]] as [$k, $lbl]) {
    echo "<h3 class=\"anah\">" . h(ucfirst($lbl)) . "</h3><div class=\"grid\">";
    echo $tile($dAyer[$k], t('ayer'));
    echo $tile($dMes[$k], t('este mes'));
    echo $tile($dAnio[$k], t('este año'));
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
if ($full) {
    require __DIR__ . '/../lib/analitica-admin.php';
    echo "<div class=\"anafull\"><h2>" . t('Analítica completa') . "</h2>";
    ana_admin($pdo, '/analitica');
    echo "</div>";
} elseif ((float)cfg('analytics_full_aura', 10) > 0) echo "<p class=\"meta\">" . t('Con aura %s o más verás aquí la analítica completa: lo que se está leyendo ahora, el día de hoy y cualquier rango de fechas con páginas, procedencia, países y dispositivos.', rtrim(rtrim(number_format((float)cfg('analytics_full_aura', 10), 2, ',', '.'), '0'), ',')) . "</p>";
echo "</div>";
page_footer();
