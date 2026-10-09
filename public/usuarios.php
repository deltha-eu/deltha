<?php
/** /usuarios — la gente registrada, del mas reciente al mas antiguo, paginada (solo para registrados).
 *  ?v=conectados  con cuenta y actividad en los ultimos online_minutes (la cifra de /estadisticas)
 *  ?v=hoy | 7d    registrados hoy / en los ultimos 7 dias
 *  sin ?v         todos los registrados
 *  ?n=usuario | mod | admin   filtro por nivel (tercera fila de pestañas), combinable con vista y orden. Moderador = nivel mod o, sin ser
 *                 admin, aura >= config.mod_aura (como user_is_mod()); usuario = el resto
 *  ?q=texto      buscador (09/10/2026): nick que contenga el texto; los admin buscan tambien por correo. Se combina con todo lo demas
 *  ?o=registro | acceso | envios | comentarios   orden (fecha de registro, ultima conexion, mas envios, mas comentarios);
 *                 se combina con la vista; envios = publicados o en el radar (no rechazados ni descartados) */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/usuarios.php';   // alta de usuarios por el admin (user_create_form)
if (!user()) redirect('/login');

$onlineMin = (int)cfg('online_minutes', 5);
$VIEWS = ['' => t('Últimos registros'), 'conectados' => t('Conectados ahora'), 'hoy' => t('Registros hoy'), '7d' => t('Registros últimos 7 días')];
$v = (string)($_GET['v'] ?? '');
if (!isset($VIEWS[$v])) $v = '';
$WHERE = ['' => '', 'conectados' => " AND last_seen >= NOW() - INTERVAL $onlineMin MINUTE", 'hoy' => ' AND created >= CURDATE()', '7d' => ' AND created >= NOW() - INTERVAL 7 DAY'][$v];
$modAura = (float)cfg('mod_aura', 20);
$esMod = "(level = 'mod'" . ($modAura > 0 ? " OR (level <> 'admin' AND aura >= " . number_format($modAura, 3, '.', '') . ")" : '') . ")";
$NIVELES = ['' => [t('todos'), ''], 'usuario' => [t('Usuarios'), " AND level <> 'admin' AND NOT $esMod"], 'mod' => [t('Moderadores'), " AND $esMod"], 'admin' => [t('Admins'), " AND level = 'admin'"]];
$n = (string)($_GET['n'] ?? '');
if (!isset($NIVELES[$n])) $n = '';
$WHERE .= $NIVELES[$n][1];
$WHERE .= ghost_sql_not_in('user_id');
// buscador: por nick; el admin tambien por correo (es la misma busqueda que admin -> Usuarios)
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 64);
$qArgs = [];
if ($q !== '') { $like = '%' . addcslashes($q, '%_\\') . '%'; $WHERE .= is_admin() ? " AND (login LIKE ? OR email LIKE ?)" : " AND login LIKE ?"; $qArgs = is_admin() ? [$like, $like] : [$like]; }   // los velados por ghosteo (en ambos sentidos) no salen: su perfil no esta disponible (lib/ghost.php)
$ORDERS = ['registro' => t('fecha de registro'), 'acceso' => t('última conexión'), 'envios' => t('más envíos'), 'comentarios' => t('más comentarios')];
$o = (string)($_GET['o'] ?? ($v === 'conectados' ? 'acceso' : 'registro'));
if (!isset($ORDERS[$o])) $o = 'registro';
$ORDER = ['registro' => 'u.created DESC, u.user_id DESC', 'acceso' => 'u.last_seen DESC, u.user_id DESC',
          'envios' => 'n_stories DESC, u.created ASC', 'comentarios' => 'n_comments DESC, u.created ASC'][$o];
// enlace a esta pagina con vista, orden y nivel dados (se omiten los que valen lo de por defecto)
$url = function (string $v, string $o, string $n, string $amp = '&amp;') use ($q): string {
    $qs = [];
    if ($v !== '') $qs[] = "v=$v";
    if ($o !== '' && $o !== ($v === 'conectados' ? 'acceso' : 'registro')) $qs[] = "o=$o";
    if ($n !== '') $qs[] = "n=$n";
    if ($q !== '') $qs[] = 'q=' . rawurlencode($q);
    return '/usuarios' . ($qs ? '?' . implode($amp, $qs) : '');
};
$page = page_num();
$per  = (int)cfg('per_page', 20);

$cnt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE verified = 1$WHERE"); $cnt->execute($qArgs); $total = (int)$cnt->fetchColumn();
// envios y comentarios por persona (subconsultas: la lista va paginada y son cientos de usuarios, no miles)
$st = $pdo->prepare("SELECT u.login, u.level, u.aura, u.avatar, u.bio, u.created, u.last_seen,
                            (SELECT COUNT(*) FROM stories s WHERE s.user_id = u.user_id AND s.status IN ('nuevas','destacadas','archived')) AS n_stories,
                            (SELECT COUNT(*) FROM comments c WHERE c.user_id = u.user_id AND c.status = 'ok' AND c.deleted = 0) AS n_comments
                     FROM users u WHERE verified = 1$WHERE ORDER BY $ORDER LIMIT ? OFFSET ?");
foreach ($qArgs as $i => $a) $st->bindValue($i + 1, $a);
$st->bindValue(count($qArgs) + 1, $per, PDO::PARAM_INT);
$st->bindValue(count($qArgs) + 2, page_offset($page), PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

page_header(t('Usuarios'), 'usuarios');
echo "<nav class=\"sub\">";
foreach ($VIEWS as $k => $label) echo "<a href=\"" . $url($k, '', $n) . "\"" . ($k === $v ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";
// orden, como segunda fila de pestañas pequeñas (misma vista, distinto ?o=)
echo "<nav class=\"sub fecha\"><span class=\"meta\">" . t('ordenar por') . "</span>";
foreach ($ORDERS as $k => $label) echo "<a href=\"" . $url($v, $k, $n) . "\"" . ($k === $o ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";
// nivel: todos · Usuarios · Moderadores · Admins (misma vista y orden, distinto ?n=)
echo "<nav class=\"sub fecha\">";
foreach ($NIVELES as $k => [$label]) echo "<a href=\"" . $url($v, $o, $k) . "\"" . ($k === $n ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";
echo "<p class=\"notice\">" . t('La gente de %s y sus <a href="/comunidades"><b>comunidades</b></a>', h(cfg('site_name'))) . (community_quota($pdo, user()) > 0 ? " · <a href=\"/comunidades#crear\">" . t('crear una comunidad') . "</a>" : "") . ".</p>";
// buscador (09/10/2026): nick (admin: nick o correo); conserva vista, orden y nivel
echo "<form class=\"buscar-u\" method=\"get\" action=\"/usuarios\">" . ($v !== '' ? "<input type=\"hidden\" name=\"v\" value=\"" . h($v) . "\">" : '') . ($o !== ($v === 'conectados' ? 'acceso' : 'registro') ? "<input type=\"hidden\" name=\"o\" value=\"" . h($o) . "\">" : '') . ($n !== '' ? "<input type=\"hidden\" name=\"n\" value=\"" . h($n) . "\">" : '')
   . "<input type=\"search\" name=\"q\" value=\"" . h($q) . "\" maxlength=\"64\" placeholder=\"" . h(is_admin() ? t('nick o correo') : t('buscar por nick')) . "\"><button>" . t('Buscar') . "</button>" . ($q !== '' ? " <a href=\"" . h(str_replace('&amp;q=' . rawurlencode($q), '', str_replace('?q=' . rawurlencode($q), '', $url($v, $o, $n)))) . "\">" . t('todos') . "</a>" : '') . "</form>";
echo "<p class=\"notice\">" . ($q !== '' ? t('%d coinciden con «%s».', $total, h($q)) : t('%d en total. Pulsa en un nombre para ver su perfil público.', $total)) . "</p>";
echo user_create_form('usuarios');   // solo admin: «+ Crear un usuario»
foreach ($rows as $r) {
    echo "<div class=\"ulist\">" . avatar((float)$r['aura'], $r['login'], $r['avatar'], '/u/' . rawurlencode($r['login']));
    echo "<div><b><a href=\"/u/" . rawurlencode($r['login']) . "\">" . h($r['login']) . "</a></b>";
    // registro y ultimo acceso, para todo el mundo (users.last_seen: se actualiza como mucho una vez por minuto, lib/app.php)
    $when = t('registro %s', hace($r['created'])) . ($r['last_seen'] ? ' · ' . t('último acceso %s', hace($r['last_seen'])) : '');
    $niv = $r['level'] === 'admin' ? 'admin' : (user_is_mod($r) ? t('moderador') : '');
    echo "<span class=\"meta\"> · " . ($niv !== '' ? "<b>" . h($niv) . "</b> · " : '') . t('aura') . " " . number_format((float)$r['aura'], 1, ',', '') . " · " . h($when) . " · " . h(t('%d envíos', (int)$r['n_stories'])) . " · " . h(t('%d comentarios', (int)$r['n_comments'])) . "</span>";
    if ($r['bio']) echo "<p class=\"bio\">" . h($r['bio']) . "</p>";
    echo "</div></div>";
}
if (!$rows) echo "<p class=\"end\">" . t('Nadie por aquí ahora mismo.') . "</p>";
paginate($url($v, $o, $n, '&'), $page, $total);
page_footer();
