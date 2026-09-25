<?php
/** /analitica.php — analitica propia, para registrados: visitantes, sesiones y paginas vistas de ayer, del mes y del año.
 *  Cifras cerradas por dias (ana_daily, lib/analitica.php): cambian una vez al dia. El detalle en vivo esta en admin -> Analitica. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
if (!user()) redirect('/login.php');

$ayer = date('Y-m-d', strtotime('yesterday'));
$mes  = date('Y-m-01');
$anio = date('Y-01-01');
$first = $pdo->query("SELECT MIN(day) FROM ana_daily")->fetchColumn();
$dAyer = ana_sum($pdo, $ayer, $ayer);
$dMes  = $ayer >= $mes  ? ana_sum($pdo, $mes, $ayer)  : ['visitors' => 0, 'sessions' => 0, 'pageviews' => 0];   // dia 1: el mes aun no tiene dias cerrados
$dAnio = $ayer >= $anio ? ana_sum($pdo, $anio, $ayer) : ['visitors' => 0, 'sessions' => 0, 'pageviews' => 0];
$n = fn($v) => number_format((int)$v, 0, ',', '.');
$tile = fn($num, string $label) => "<div class=\"tile\"><b>" . $n($num) . "</b><span>" . h($label) . "</span></div>";

page_header(t('Analítica'), 'analitica');
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
if (is_admin()) echo "<p class=\"end\"><a href=\"/admin.php?t=analitica\">" . t('Analítica completa (admin)') . "</a></p>";
echo "</div>";
page_footer();
