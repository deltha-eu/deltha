<?php
/** /moderacion.php — cola de revision humana: lo que la IA no ha sabido decidir. Para moderadores y admin. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/chat.php';
if (!user()) redirect('/login.php');
if (!is_mod()) { http_response_code(403); page_header('Moderación'); echo "<p class=\"end\">Solo moderadores.</p>"; page_footer(); exit; }

$out = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'tema') {
    $name = mb_strtolower(trim(post('name')));
    if (!preg_match('/^[\\p{L}\\p{N} \\-]{2,32}$/u', $name)) $out[] = 'Nombre de tema no válido (2-32 letras).';
    else {
        try {
            $pdo->prepare("INSERT INTO topics (name, coef) VALUES (?, 1.00)")->execute([$name]);
            $out[] = "Tema «{$name}» creado.";
        } catch (PDOException $e) { $out[] = 'Ese tema ya existe.'; }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'dominio') {
    $d = mb_strtolower(trim(post('domain')));
    $d = preg_replace('~^.*@~', '', $d);   // admite pegar un correo entero
    if (!preg_match('/^[a-z0-9.-]+\\.[a-z]{2,}$/', $d)) $out[] = 'Dominio no válido.';
    else {
        $pdo->prepare("INSERT IGNORE INTO banned_domains (domain, reason, added_by) VALUES (?,?,?)")->execute([$d, mb_substr(post('reason'), 0, 120) ?: null, user()['user_id']]);
        $out[] = "Dominio $d vetado.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'dominio_quitar') {
    $pdo->prepare("DELETE FROM banned_domains WHERE domain = ?")->execute([post('domain')]);
    $out[] = 'Dominio retirado de la lista.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('act'), ['rep_borrar', 'rep_descartar'], true)) {
    $cid = (int)post('cid');
    if (post('act') === 'rep_borrar') { comment_delete($pdo, $cid); $out[] = "Comentario #$cid eliminado (texto sustituido por el aviso)."; }
    else { $pdo->prepare("UPDATE comment_reports SET status = 'dismissed' WHERE comment_id = ? AND status = 'open'")->execute([$cid]); $out[] = "Reportes del comentario #$cid descartados."; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('act'), ['chat_borrar', 'chat_descartar'], true)) {
    $mid = (int)post('mid');
    if (post('act') === 'chat_borrar') { chat_delete_mod($pdo, $mid); $out[] = "Mensaje #$mid del chat eliminado (texto sustituido por el aviso)."; }
    else { $pdo->prepare("UPDATE chat_reports SET status = 'dismissed' WHERE msg_id = ? AND status = 'open'")->execute([$mid]); $out[] = "Reportes del mensaje #$mid descartados."; }
    if (post('back') === '1') redirect("/chat.php?m=$mid#m$mid");   // «eliminar» desde el propio chat
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'humano') {
    $item = (int)post('item'); $dec = post('dec') === 'ok' ? 'ok' : 'reject';
    $q = $pdo->prepare("SELECT item_type, ref_id FROM mod_queue WHERE item_id = ? AND status IN ('needs_human','queued','processing')"); $q->execute([$item]);
    if ($it = $q->fetch()) {
        if ($it['item_type'] === 'story') {
            $pdo->prepare("UPDATE stories SET status = ? WHERE story_id = ?")->execute([$dec === 'ok' ? 'nuevas' : 'rejected', $it['ref_id']]);
        } else {
            $pdo->prepare("UPDATE comments SET status = ? WHERE comment_id = ?")->execute([$dec === 'ok' ? 'ok' : 'rejected', $it['ref_id']]);
            if ($dec === 'ok') $pdo->prepare("UPDATE stories s JOIN comments c ON c.story_id = s.story_id SET s.comments = s.comments + 1 WHERE c.comment_id = ?")->execute([$it['ref_id']]);
        }
        $pdo->prepare("UPDATE mod_queue SET status = ?, processed = NOW() WHERE item_id = ?")->execute([$dec, $item]);
        $out[] = "#$item: " . ($dec === 'ok' ? 'aprobado' : 'rechazado') . '.';
    }
}

$q = $pdo->query("SELECT m.*, IF(m.item_type='story', s.title, c.content) AS txt, IF(m.item_type='story', s.story_id, c.story_id) AS sid
                  FROM mod_queue m
                  LEFT JOIN stories s ON m.item_type='story' AND s.story_id = m.ref_id
                  LEFT JOIN comments c ON m.item_type='comment' AND c.comment_id = m.ref_id
                  WHERE m.status IN ('needs_human','queued','processing') ORDER BY m.created LIMIT 50")->fetchAll();

page_header('Moderación');
echo "<div class=\"admin\"><h1>Moderación</h1>";
if ((int)cfg('mod_enabled', 0) === 0) echo "<p class=\"notice\">La IA moderadora aún no está activa: de momento todo se publica directamente. Cuando esté en marcha, aquí aparecerá lo que no sepa decidir.</p>";
else echo "<p class=\"notice\">Aquí llega lo que la IA no ha sabido decidir. El criterio es la ley: insultos, delitos de odio, amenazas y cualquier conducta delictiva se rechazan; el resto se aprueba.</p>";
if ($out) echo "<pre>" . h(implode("\n", $out)) . "</pre>";

/* ---- comentarios reportados ---- */
$reps = $pdo->query("SELECT c.comment_id, c.story_id, c.content, c.alias, c.created, c.user_id, u.login, s.title,
                            COUNT(*) n, MIN(r.created) first_rep, GROUP_CONCAT(DISTINCT r.reason ORDER BY r.reason) reasons
                       FROM comment_reports r JOIN comments c ON c.comment_id = r.comment_id JOIN stories s ON s.story_id = c.story_id
                       LEFT JOIN users u ON u.user_id = c.user_id
                      WHERE r.status = 'open' AND c.status = 'ok' AND c.deleted = 0
                      GROUP BY c.comment_id ORDER BY n DESC, first_rep ASC LIMIT 100")->fetchAll();
$RZ = report_reasons();
echo "<h3 id=\"reportes\">Comentarios reportados (" . count($reps) . ")</h3>";
if (!$reps) echo "<p>No hay reportes pendientes.</p>";
foreach ($reps as $r) {
    $who = ($r['login'] && ghost_over($r['created'])) ? $r['login'] : $r['alias'];
    $motivos = implode(', ', array_map(fn($k) => $RZ[$k] ?? $k, explode(',', $r['reasons'])));
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . h($who) . "</b> en <a href=\"/hilo.php?id={$r['story_id']}#c{$r['comment_id']}\">" . h(mb_strimwidth($r['title'], 0, 70, '…')) . "</a>";
    echo "<span class=\"meta\"> · " . h(hace($r['created'])) . " · <b>{$r['n']}</b> " . ($r['n'] == 1 ? 'reporte' : 'reportes') . " · " . h($motivos) . "</span></div>";
    echo "<blockquote class=\"quote\">" . nl2br(h(mb_strimwidth($r['content'], 0, 600, '…'))) . "</blockquote>";
    echo "<div class=\"repacts\"><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"rep_borrar\"><input type=\"hidden\" name=\"cid\" value=\"{$r['comment_id']}\"><button class=\"btn danger\" title=\"sustituye el texto por el aviso\">Borrar comentario</button></form>";
    echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"rep_descartar\"><input type=\"hidden\" name=\"cid\" value=\"{$r['comment_id']}\"><button class=\"btn\" title=\"el comentario se queda; los reportes se cierran\">Descartar reporte</button></form>";
    echo "<a class=\"btn\" href=\"/editar-comentario.php?id={$r['comment_id']}\">editar</a></div></div>";
}
/* ---- mensajes del chat reportados ---- */
try {
    $creps = $pdo->query("SELECT c.msg_id, c.content, c.alias, c.created, c.user_id, u.login,
                                 COUNT(*) n, MIN(r.created) first_rep, GROUP_CONCAT(DISTINCT r.reason ORDER BY r.reason) reasons
                            FROM chat_reports r JOIN chat c ON c.msg_id = r.msg_id LEFT JOIN users u ON u.user_id = c.user_id
                           WHERE r.status = 'open' AND c.status = 'ok' AND c.deleted = 0
                           GROUP BY c.msg_id ORDER BY n DESC, first_rep ASC LIMIT 100")->fetchAll();
} catch (Throwable $e) { $creps = []; }
echo "<h3 id=\"chat\">Mensajes del chat reportados (" . count($creps) . ")</h3>";
if (!$creps) echo "<p>No hay reportes pendientes.</p>";
foreach ($creps as $r) {
    $motivos = implode(', ', array_map(fn($k) => $RZ[$k] ?? $k, explode(',', $r['reasons'])));
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . h(chat_who($r)) . "</b> <a href=\"/chat.php?m={$r['msg_id']}#m{$r['msg_id']}\">#{$r['msg_id']}</a>";
    echo "<span class=\"meta\"> · " . h(hace($r['created'])) . " · <b>{$r['n']}</b> " . ($r['n'] == 1 ? 'reporte' : 'reportes') . " · " . h($motivos) . "</span></div>";
    echo "<blockquote class=\"quote\">" . nl2br(h(mb_strimwidth($r['content'], 0, 600, '…'))) . "</blockquote>";
    echo "<div class=\"repacts\"><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"chat_borrar\"><input type=\"hidden\" name=\"mid\" value=\"{$r['msg_id']}\"><button class=\"btn danger\" title=\"sustituye el texto por el aviso\">Borrar mensaje</button></form>";
    echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"chat_descartar\"><input type=\"hidden\" name=\"mid\" value=\"{$r['msg_id']}\"><button class=\"btn\" title=\"el mensaje se queda; los reportes se cierran\">Descartar reporte</button></form></div></div>";
}
echo "<h3 id=\"temas\">Temas</h3><p>";
foreach ($pdo->query("SELECT topic_id, name FROM topics ORDER BY name") as $t) echo "<a class=\"tag\" href=\"/tema.php?t={$t['topic_id']}\">" . h($t['name']) . "</a> ";
echo "</p><form method=\"post\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap\"><input type=\"hidden\" name=\"act\" value=\"tema\">";
echo "<input type=\"text\" name=\"name\" placeholder=\"nuevo tema\" maxlength=\"32\" required style=\"flex:1;min-width:140px\"> <button class=\"btn\">Añadir tema</button></form>";
echo "<p><small>El tema de cada envío se cambia desde su hilo (desplegable bajo los votos). El coeficiente lo ajusta el admin.</small></p>";
$bd = $pdo->query("SELECT b.*, u.login FROM banned_domains b LEFT JOIN users u ON u.user_id = b.added_by ORDER BY b.domain")->fetchAll();
echo "<h3 id=\"dominios\">Dominios de correo no válidos (" . count($bd) . ")</h3>";
echo "<p><small>No se aceptan registros con correos de estos dominios (desechables, de spam…). Además se rechazan los dominios sin registros MX.</small></p>";
echo "<form method=\"post\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:8px\"><input type=\"hidden\" name=\"act\" value=\"dominio\">";
echo "<input type=\"text\" name=\"domain\" placeholder=\"dominio.com\" required style=\"flex:1;min-width:140px\"> <input type=\"text\" name=\"reason\" placeholder=\"motivo (opcional)\" style=\"flex:1;min-width:140px\"> <button class=\"btn\">Vetar dominio</button></form>";
if ($bd) {
    echo "<details><summary>Ver lista</summary><table>";
    foreach ($bd as $b) {
        echo "<tr><td><code>" . h($b['domain']) . "</code></td><td>" . h((string)$b['reason']) . "</td><td><small>" . h($b['login'] ?: 'sistema') . " · " . h(hace($b['created'])) . "</small></td>";
        echo "<td><form method=\"post\" style=\"display:inline\"><input type=\"hidden\" name=\"act\" value=\"dominio_quitar\"><input type=\"hidden\" name=\"domain\" value=\"" . h($b['domain']) . "\"><button class=\"btn\">quitar</button></form></td></tr>";
    }
    echo "</table></details>";
}
echo "<h3>Pendientes (" . count($q) . ")</h3>";
if (!$q) echo "<p class=\"end\">Nada pendiente.</p>";
else {
    echo "<table>";
    foreach ($q as $m) {
        $v = $m['verdict'] ? "<br><small>IA: " . h(mb_strimwidth((string)$m['verdict'], 0, 200, '…')) . "</small>" : '';
        echo "<tr><td>#{$m['item_id']} " . ($m['item_type'] === 'story' ? 'envío' : 'comentario') . " · " . h(hace($m['created'])) . ($m['sid'] ? " · <a href=\"/hilo.php?id={$m['sid']}\">hilo</a>" : '') . "<br>" . h(mb_strimwidth((string)$m['txt'], 0, 300, '…')) . $v . "</td><td style=\"white-space:nowrap\">";
        echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"humano\"><input type=\"hidden\" name=\"item\" value=\"{$m['item_id']}\">";
        echo "<button name=\"dec\" value=\"ok\">aprobar</button> <button name=\"dec\" value=\"reject\">rechazar</button></form></td></tr>";
    }
    echo "</table>";
}
echo "</div>";
page_footer();
