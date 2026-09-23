<?php
/** /usuarios.php — la gente registrada, del mas reciente al mas antiguo, paginada (solo para registrados).
 *  ?v=conectados  con cuenta y actividad en los ultimos online_minutes (la cifra de /estadisticas.php)
 *  ?v=hoy | 7d    registrados hoy / en los ultimos 7 dias
 *  sin ?v         todos los registrados */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/usuarios.php';   // alta de usuarios por el admin (user_create_form)
if (!user()) redirect('/login.php');

$onlineMin = (int)cfg('online_minutes', 5);
$VIEWS = ['' => t('Últimos registros'), 'conectados' => t('Conectados ahora'), 'hoy' => t('Registros hoy'), '7d' => t('Registros últimos 7 días')];
$v = (string)($_GET['v'] ?? '');
if (!isset($VIEWS[$v])) $v = '';
$WHERE = ['' => '', 'conectados' => " AND last_seen >= NOW() - INTERVAL $onlineMin MINUTE", 'hoy' => ' AND created >= CURDATE()', '7d' => ' AND created >= NOW() - INTERVAL 7 DAY'][$v];
$ORDER = $v === 'conectados' ? 'last_seen DESC, user_id DESC' : 'created DESC, user_id DESC';
$page = page_num();
$per  = (int)cfg('per_page', 20);

$total = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE verified = 1$WHERE")->fetchColumn();
$st = $pdo->prepare("SELECT login, aura, avatar, bio, created, last_seen FROM users WHERE verified = 1$WHERE ORDER BY $ORDER LIMIT ? OFFSET ?");
$st->bindValue(1, $per, PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

page_header(t('Usuarios'), 'usuarios');
echo "<nav class=\"sub\">";
foreach ($VIEWS as $k => $label) echo "<a href=\"/usuarios.php" . ($k === '' ? '' : "?v=$k") . "\"" . ($k === $v ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";
echo "<p class=\"notice\">" . t('La gente de %s y sus <a href="/comunidades.php"><b>comunidades</b></a>', h(cfg('site_name'))) . (community_quota($pdo, user()) > 0 ? " · <a href=\"/comunidades.php#crear\">" . t('crear una comunidad') . "</a>" : "") . ".</p>";
echo "<h1>" . h($VIEWS[$v]) . "</h1><p class=\"notice\">" . t('%d en total. Pulsa en un nombre para ver su perfil público.', $total) . "</p>";
echo user_create_form('usuarios');   // solo admin: «+ Crear un usuario»
foreach ($rows as $r) {
    echo "<div class=\"ulist\">" . avatar((float)$r['aura'], $r['login'], $r['avatar'], '/u.php?u=' . rawurlencode($r['login']));
    echo "<div><b><a href=\"/u.php?u=" . rawurlencode($r['login']) . "\">" . h($r['login']) . "</a></b>";
    // registro y ultimo acceso, para todo el mundo (users.last_seen: se actualiza como mucho una vez por minuto, lib/app.php)
    $when = t('registro %s', hace($r['created'])) . ($r['last_seen'] ? ' · ' . t('último acceso %s', hace($r['last_seen'])) : '');
    echo "<span class=\"meta\"> · " . t('aura') . " " . number_format((float)$r['aura'], 1, ',', '') . " · " . h($when) . "</span>";
    if ($r['bio']) echo "<p class=\"bio\">" . h($r['bio']) . "</p>";
    echo "</div></div>";
}
if (!$rows) echo "<p class=\"end\">" . t('Nadie por aquí ahora mismo.') . "</p>";
paginate('/usuarios.php' . ($v === '' ? '' : "?v=$v"), $page, $total);
page_footer();
