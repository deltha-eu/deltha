<?php
/** /avisos — la lista de avisos (campanita): todos mezclados, del mas nuevo al mas viejo, paginados; dos colores (leido / sin leer).
 *  Pulsar uno lo marca leido y lleva a su origen (?ir=N); «marcar todo como leído» arriba (POST). ?n=1 devuelve solo el numero
 *  de no leidos en JSON, para que /app/avisos.js refresque la burbuja sin recargar. Se pueden borrar: uno (×), los leidos o los de Sigfrid
 *  (09/11/2026). lib/avisos.php */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
$me = user();
if (isset($_GET['n'])) {   // contador para el sondeo desde JS (sin sesion: 0)
    header('Content-Type: application/json'); header('Cache-Control: no-store');
    echo json_encode(['n' => $me ? avisos_unread() : 0]); exit;
}
if (!$me) redirect('/login');
$myId = (int)$me['user_id'];
if (!avisos_on()) { page_header(t('Avisos'), 'avisos'); echo "<p class=\"end\">" . t('Los avisos están desactivados.') . "</p>"; page_footer(); exit; }

if (($ir = (int)($_GET['ir'] ?? 0)) > 0) {   // pulsado: leido y al origen
    $st = $pdo->prepare("SELECT n.*, u.login FROM notifications n LEFT JOIN users u ON u.user_id = n.from_id WHERE n.notif_id = ? AND n.user_id = ?");
    $st->execute([$ir, $myId]);
    if (!($n = $st->fetch())) redirect('/avisos');
    $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE notif_id = ? AND read_at IS NULL")->execute([$ir]);
    redirect(aviso_href($n));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'leidos') {
    $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL")->execute([$myId]);
    redirect('/avisos');
}
// borrar avisos (09/11/2026): uno (la ×), los ya leidos, o los de Sigfrid (sus respuestas en el Chat; from_id = la cuenta del bot). Solo los propios.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'borrar') {
    $que = post('que');
    if ($que === 'uno') $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND notif_id = ?")->execute([$myId, (int)post('id')]);
    elseif ($que === 'leidos') $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND read_at IS NOT NULL")->execute([$myId]);
    elseif ($que === 'sigfrid') { $sid = (int)($pdo->query("SELECT user_id FROM users WHERE login = " . $pdo->quote((string)cfg('sigfrid_login', 'Sigfrid')))->fetchColumn() ?: 0); if ($sid) $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND from_id = ?")->execute([$myId, $sid]); }
    redirect('/avisos' . (page_num() > 1 ? '?p=' . page_num() : ''));
}

$per = max(10, (int)cfg('notif_per_page', 30));
$page = page_num();
$total = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $myId")->fetchColumn();
$st = $pdo->prepare("SELECT n.*, u.login, u.avatar, u.aura FROM notifications n LEFT JOIN users u ON u.user_id = n.from_id WHERE n.user_id = ? ORDER BY n.created DESC, n.notif_id DESC LIMIT ? OFFSET ?");
$st->bindValue(1, $myId, PDO::PARAM_INT); $st->bindValue(2, $per, PDO::PARAM_INT); $st->bindValue(3, ($page - 1) * $per, PDO::PARAM_INT); $st->execute();
$rows = $st->fetchAll();
$unread = avisos_unread();

page_header(t('Avisos'), 'avisos');
echo "<div class=\"avisos\">";
echo "<div class=\"avhead\"><span class=\"meta\">" . ($unread ? t('%d sin leer', $unread) : t('Todo leído')) . "</span>";
echo "<span class=\"avacts\">";
if ($unread) echo "<form method=\"post\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"leidos\"><button class=\"link\">" . t('marcar todo como leído') . "</button></form>";
if ($total) {   // borrar (09/11/2026)
    $sigLogin = (string)cfg('sigfrid_login', 'Sigfrid');
    $tieneSig = (int)$pdo->query("SELECT COUNT(*) FROM notifications n JOIN users u ON u.user_id = n.from_id WHERE n.user_id = $myId AND u.login = " . $pdo->quote($sigLogin))->fetchColumn();
    if ($total > $unread) echo ($unread ? ' · ' : '') . "<form method=\"post\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"que\" value=\"leidos\"><button class=\"link\">" . t('borrar los leídos') . "</button></form>";
    if ($tieneSig) echo " · <form method=\"post\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"que\" value=\"sigfrid\"><button class=\"link\">" . t('borrar los de %s', h($sigLogin)) . "</button></form>";
}
echo "</span></div>";
if (!$rows) echo "<p class=\"end\">" . t('Todavía no tienes avisos. Te avisaremos cuando alguien te mencione con @%s, responda a un comentario tuyo o te escriba.', h($me['login'])) . "</p>";
foreach ($rows as $n) {
    $read = !empty($n['read_at']);
    $who = aviso_who($n);
    $anon = empty($n['login']) || $who !== $n['login'];   // alias del hilo (Ghost) o anonimo del Chat: sin foto ni enlace al perfil
    echo "<div class=\"avrow\"><a class=\"fz aviso" . ($read ? ' leida' : ' nueva') . "\" href=\"/avisos?ir=" . (int)$n['notif_id'] . "\">";
    echo $anon ? "<span class=\"av sys\">" . aviso_icon($n) . "</span>" : avatar((float)$n['aura'], (string)$n['login'], $n['avatar']);
    echo "<div class=\"fzt\"><div class=\"t\">" . aviso_text($n, h($who)) . "<span class=\"meta\"> · " . h(hace($n['created'])) . "</span></div>";
    if ($n['snippet'] !== '') echo "<div class=\"prev\">" . h($n['snippet']) . "</div>";
    echo "</div>" . aviso_icon($n) . "</a>";
    // la × borra este aviso (09/11/2026): fuera del <a>, que un form no puede ir dentro de un enlace
    echo "<form method=\"post\" class=\"quitar\"><input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"que\" value=\"uno\"><input type=\"hidden\" name=\"id\" value=\"" . (int)$n['notif_id'] . "\"><button class=\"link x\" title=\"" . h(t('borrar este aviso')) . "\" aria-label=\"" . h(t('borrar este aviso')) . "\">×</button></form></div>";
}
echo "</div>";
paginate('/avisos', $page, $total, $per);
page_footer();
