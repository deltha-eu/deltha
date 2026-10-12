<?php
/** /ghostear — ghostear a un usuario (ignorar) o deshacerlo. lib/ghost.php, sql/migra-086-ghostear.sql
 *  GET  ?u=nick            pagina de confirmacion (que va a pasar) con el boton
 *  POST u + act=ghostear   crea el velo y vuelve a «Mi perfil» (#ghost); el perfil del otro deja de estar disponible
 *  POST u + act=deshacer   quita el velo (desde la lista de «Mi perfil»)
 *  Solo registrados, nunca sobre uno mismo. Sin JS. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
$me = user();
if (!$me) redirect('/login');
if (!ghost_on()) redirect('/perfil');
$myId = (int)$me['user_id'];

$login = trim((string)($_SERVER['REQUEST_METHOD'] === 'POST' ? post('u') : ($_GET['u'] ?? '')));
$st = $pdo->prepare("SELECT user_id, login, aura, avatar, bio FROM users WHERE login = ? AND verified = 1");
$st->execute([$login]);
$p = $st->fetch();
if (!$p || (int)$p['user_id'] === $myId) { http_response_code(404); page_header(t('No existe')); echo "<p class=\"end\">" . t('No existe ese usuario.') . "</p>"; page_footer(); exit; }
$tid = (int)$p['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('act') === 'deshacer') { ghost_remove($pdo, $myId, $tid); redirect('/perfil?ghost=quitado#ghost'); }
    if (post('act') === 'ghostear') { ghost_add($pdo, $myId, $tid); redirect('/perfil?ghost=hecho#ghost'); }
    redirect('/perfil#ghost');
}

// ya velado (lo ghostee yo o me ghosteo): no hay nada que hacer aqui
if (ghost_veiled($tid)) redirect(ghost_folded($tid) ? '/perfil#ghost' : '/');

page_header(t('Silenciar'));
echo "<div class=\"ghostbox\">";
echo "<div class=\"profile\">" . avatar((float)$p['aura'], $p['login'], $p['avatar']) . "<div><h1>" . t('¿Silenciar a %s?', h($p['login'])) . "</h1>";
if ($p['bio']) echo "<p class=\"bio\">" . h($p['bio']) . "</p>";
echo "</div></div>";
echo "<p class=\"notice\">" . t('A partir de ahora, para %s serás siempre un Ghost: no verá tu nombre en tus envíos, comentarios ni menciones, no podrá ver tu perfil ni escribirte por privado. Y tú lo verás igual: como un Ghost más, con sus comentarios plegados, por si en algún momento quieres leerlos.', h($p['login'])) . "</p>";
echo "<p class=\"notice\">" . t('Nadie recibe aviso y no hay listas públicas. No afecta a los votos ni oculta nada al resto. Puedes deshacerlo cuando quieras desde tu perfil.') . "</p>";
echo "<form class=\"plain\" method=\"post\" action=\"/ghostear\"><input type=\"hidden\" name=\"u\" value=\"" . h($p['login']) . "\"><input type=\"hidden\" name=\"act\" value=\"ghostear\">";
echo "<button>" . t('Silenciar') . "</button> <a class=\"cancel\" href=\"/u/" . rawurlencode($p['login']) . "\">" . t('cancelar') . "</a></form>";
echo "</div>";
page_footer();
