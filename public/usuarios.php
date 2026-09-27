<?php
/** /usuarios.php — la gente registrada, del mas reciente al mas antiguo, paginada (solo para registrados).
 *  ?v=conectados  con cuenta y actividad en los ultimos online_minutes (la cifra de /estadisticas.php)
 *  ?v=hoy | 7d    registrados hoy / en los ultimos 7 dias
 *  sin ?v         todos los registrados
 *  ?o=registro | acceso | envios | comentarios   orden (fecha de registro, ultima conexion, mas envios, mas comentarios);
 *                 se combina con la vista; envios = publicados o en el radar (no rechazados ni descartados) */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/usuarios.php';   // alta de usuarios por el admin (user_create_form)
if (!user()) redirect('/login.php');

$onlineMin = (int)cfg('online_minutes', 5);
$VIEWS = ['' => t('Últimos registros'), 'conectados' => t('Conectados ahora'), 'hoy' => t('Registros hoy'), '7d' => t('Registros últimos 7 días')];
$v = (string)($_GET['v'] ?? '');
if (!isset($VIEWS[$v])) $v = '';
$WHERE = ['' => '', 'conectados' => " AND last_seen >= NOW() - INTERVAL $onlineMin MINUTE", 'hoy' => ' AND created >= CURDATE()', '7d' => ' AND created >= NOW() - INTERVAL 7 DAY'][$v];
$WHERE .= ghost_sql_not_in('user_id');   // los velados por ghosteo (en ambos sentidos) no salen: su perfil no esta disponible (lib/ghost.php)
$ORDERS = ['registro' => t('fecha de registro'), 'acceso' => t('última conexión'), 'envios' => t('más envíos'), 'comentarios' => t('más comentarios')];
$o = (string)($_GET['o'] ?? ($v === 'conectados' ? 'acceso' : 'registro'));
if (!isset($ORDERS[$o])) $o = 'registro';
$ORDER = ['registro' => 'u.created DESC, u.user_id DESC', 'acceso' => 'u.last_seen DESC, u.user_id DESC',
          'envios' => 'n_stories DESC, u.created ASC', 'comentarios' => 'n_comments DESC, u.created ASC'][$o];
$qs = ($v === '' ? '' : "v=$v") . ($o === ($v === 'conectados' ? 'acceso' : 'registro') ? '' : (($v === '' ? '' : '&') . "o=$o"));   // query string de la vista+orden actual
$page = page_num();
$per  = (int)cfg('per_page', 20);

$total = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE verified = 1$WHERE")->fetchColumn();
// envios y comentarios por persona (subconsultas: la lista va paginada y son cientos de usuarios, no miles)
$st = $pdo->prepare("SELECT u.login, u.aura, u.avatar, u.bio, u.created, u.last_seen,
                            (SELECT COUNT(*) FROM stories s WHERE s.user_id = u.user_id AND s.status IN ('nuevas','destacadas','archived')) AS n_stories,
                            (SELECT COUNT(*) FROM comments c WHERE c.user_id = u.user_id AND c.status = 'ok' AND c.deleted = 0) AS n_comments
                     FROM users u WHERE verified = 1$WHERE ORDER BY $ORDER LIMIT ? OFFSET ?");
$st->bindValue(1, $per, PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

page_header(t('Usuarios'), 'usuarios');
echo "<nav class=\"sub\">";
foreach ($VIEWS as $k => $label) echo "<a href=\"/usuarios.php" . ($k === '' ? '' : "?v=$k") . "\"" . ($k === $v ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";
// orden, como segunda fila de pestañas pequeñas (misma vista, distinto ?o=)
echo "<nav class=\"sub fecha\"><span class=\"meta\">" . t('ordenar por') . "</span>";
foreach ($ORDERS as $k => $label) echo "<a href=\"/usuarios.php?" . ($v === '' ? '' : "v=$v&amp;") . "o=$k\"" . ($k === $o ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";
echo "<p class=\"notice\">" . t('La gente de %s y sus <a href="/comunidades.php"><b>comunidades</b></a>', h(cfg('site_name'))) . (community_quota($pdo, user()) > 0 ? " · <a href=\"/comunidades.php#crear\">" . t('crear una comunidad') . "</a>" : "") . ".</p>";
echo "<p class=\"notice\">" . t('%d en total. Pulsa en un nombre para ver su perfil público.', $total) . "</p>";
echo user_create_form('usuarios');   // solo admin: «+ Crear un usuario»
foreach ($rows as $r) {
    echo "<div class=\"ulist\">" . avatar((float)$r['aura'], $r['login'], $r['avatar'], '/u.php?u=' . rawurlencode($r['login']));
    echo "<div><b><a href=\"/u.php?u=" . rawurlencode($r['login']) . "\">" . h($r['login']) . "</a></b>";
    // registro y ultimo acceso, para todo el mundo (users.last_seen: se actualiza como mucho una vez por minuto, lib/app.php)
    $when = t('registro %s', hace($r['created'])) . ($r['last_seen'] ? ' · ' . t('último acceso %s', hace($r['last_seen'])) : '');
    echo "<span class=\"meta\"> · " . t('aura') . " " . number_format((float)$r['aura'], 1, ',', '') . " · " . h($when) . " · " . h(t('%d envíos', (int)$r['n_stories'])) . " · " . h(t('%d comentarios', (int)$r['n_comments'])) . "</span>";
    if ($r['bio']) echo "<p class=\"bio\">" . h($r['bio']) . "</p>";
    echo "</div></div>";
}
if (!$rows) echo "<p class=\"end\">" . t('Nadie por aquí ahora mismo.') . "</p>";
paginate('/usuarios.php' . ($qs === '' ? '' : "?$qs"), $page, $total);
page_footer();
