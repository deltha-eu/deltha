<?php
/** /moderacion.php — moderadores y admin: lo que ha señalado la IA local (posmoderacion, lib/moderacion.php), reportes, dominios, medios, temas y fuentes. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/chat.php';
require __DIR__ . '/../lib/propuestas.php';
require __DIR__ . '/../lib/moderacion.php';
require_once __DIR__ . '/../lib/mail.php';   // listas de dominios de correo (email_domain_normalize)
if (!user()) redirect('/login.php');
if (!is_mod()) { http_response_code(403); page_header('Moderación'); echo "<p class=\"end\">Solo moderadores.</p>"; page_footer(); exit; }

$out = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'tema') {
    $name = mb_strtolower(trim(post('name')));
    if (!preg_match('/^[\\p{L}\\p{N} \\-]{2,32}$/u', $name)) $out[] = 'Nombre de tema no válido (2-32 letras).';
    else {
        try {
            $pdo->prepare("INSERT INTO topics (name, bonus) VALUES (?, 0)")->execute([$name]);
            $out[] = "Tema «{$name}» creado.";
        } catch (PDOException $e) { $out[] = 'Ese tema ya existe.'; }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'tema_editar') {   // renombrar y cambiar el color (el bonus sigue siendo del admin)
    $tid = (int)post('topic_id');
    $e = topic_rename($pdo, $tid, post('name'));
    if (!$e && preg_match('/^#[0-9a-fA-F]{6}$/', post('color'))) $pdo->prepare("UPDATE topics SET color = ? WHERE topic_id = ?")->execute([post('color'), $tid]);
    $out[] = $e ?: 'Tema guardado.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'tema_borrar') {
    if (post('seguro') !== '1') $out[] = 'Marca la casilla de confirmación para borrar el tema.';
    else { $e = topic_delete($pdo, (int)post('topic_id'), (int)post('to')); $out[] = $e ?: 'Tema borrado; sus envíos han pasado al tema elegido.'; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'dominio') {
    $d = email_domain_normalize(post('domain'));   // admite pegar un correo entero
    if ($d === null) $out[] = 'Dominio no válido.';
    else {
        $pdo->prepare("INSERT IGNORE INTO banned_domains (domain, reason, added_by) VALUES (?,?,?)")->execute([$d, mb_substr(post('reason'), 0, 120) ?: null, user()['user_id']]);
        try { $pdo->prepare("DELETE FROM allowed_domains WHERE domain = ?")->execute([$d]); } catch (Throwable $e) {}
        $out[] = "Dominio $d vetado (y quitado de la lista blanca si estaba).";
    }
}
// lista blanca de dominios de correo: proveedores reconocidos y revisiones manuales (se admiten sin comprobar su web)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('act'), ['blanca', 'dominio_a_blanca'], true)) {
    $d = email_domain_normalize(post('domain'));
    if ($d === null) $out[] = 'Dominio no válido.';
    else {
        $motivo = mb_substr(post('reason'), 0, 120) ?: (post('act') === 'dominio_a_blanca' ? 'revisado a mano' : null);
        $pdo->prepare("INSERT INTO allowed_domains (domain, reason, added_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE reason = VALUES(reason), added_by = VALUES(added_by)")->execute([$d, $motivo, user()['user_id']]);
        $pdo->prepare("DELETE FROM banned_domains WHERE domain = ?")->execute([$d]);
        $out[] = "Dominio $d en la lista blanca: ya se puede registrar una cuenta con él (pídele a la persona que lo intente de nuevo).";
    }
}
// probar un dominio: lo que ve el servidor al comprobar su web (no cambia ninguna lista)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'dominio_probar') {
    $d = email_domain_normalize(post('domain'));
    if ($d === null) $out[] = 'Dominio no válido.';
    else {
        $t0 = microtime(true);
        [$okw, $info] = email_web_check($d);
        $lista = email_domain_listed($pdo, 'banned_domains', $d) ? 'está en la lista negra' : (email_domain_listed($pdo, 'allowed_domains', $d) ? 'está en la lista blanca' : 'no está en ninguna lista');
        $dns = function_exists('checkdnsrr') ? ((checkdnsrr($d . '.', 'MX') ? 'MX sí' : 'MX no') . ', ' . (checkdnsrr($d . '.', 'A') ? 'A sí' : 'A no')) : 'DNS sin comprobar';
        $probado = "$d: $lista · $dns · web " . ($okw ? 'RESPONDE' : 'NO RESPONDE') . " (" . round(microtime(true) - $t0, 1) . " s) — $info";   // se pinta bajo el propio formulario
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'blanca_quitar') {
    $pdo->prepare("DELETE FROM allowed_domains WHERE domain = ?")->execute([post('domain')]);
    $out[] = 'Dominio retirado de la lista blanca (vuelve a pasar la comprobación normal).';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'dominio_quitar') {
    $pdo->prepare("DELETE FROM banned_domains WHERE domain = ?")->execute([post('domain')]);
    $out[] = 'Dominio retirado de la lista.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'medio') {
    $d = source_block_add($pdo, post('domain'), post('reason'), (int)user()['user_id']);   // admite pegar el enlace entero
    $out[] = $d ? "Medio $d bloqueado (y todos sus subdominios)." : 'Dominio o enlace no válido.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'medio_quitar') {
    source_block_remove($pdo, post('domain'));
    $out[] = 'Medio retirado de la lista de bloqueados.';
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'ia') {   // decision humana sobre lo que ha señalado u ocultado la IA
    $item = (int)post('item');
    $r = mod_human($pdo, $item, post('dec') === 'remove' ? 'remove' : 'keep', (int)user()['user_id']);
    $out[] = $r ? "#$item: $r." : "#$item ya estaba resuelto.";
}

/* ---- fuentes propuestas por los usuarios (lib/propuestas.php) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('act'), ['fprop_probar', 'fprop_ok', 'fprop_no'], true)) {
    $q = $pdo->prepare("SELECT * FROM source_proposals WHERE prop_id = ? AND status = 'pending'"); $q->execute([(int)post('pid')]);
    if (!($p = $q->fetch())) $out[] = 'Esa propuesta ya no está pendiente.';
    elseif (post('act') === 'fprop_probar') {
        $feed = trim(post('feed')) ?: $p['feed'];
        $r = fprop_probar($feed, $p['home']);
        $pdo->prepare("UPDATE source_proposals SET feed = ?, lang = ?, test_items = ?, test_info = ? WHERE prop_id = ?")
            ->execute([$r['feed'] !== '' ? $r['feed'] : $feed, $r['lang'], $r['items'], fprop_info($r), $p['prop_id']]);
        $out[] = "«{$p['name']}»: " . ($r['items'] ? "{$r['items']} noticias en el RSS, idioma " . ($r['lang'] ?: '?') . ", la última de {$r['last']}." : "el RSS no se lee — {$r['error']}");
    } elseif (post('act') === 'fprop_no') {
        $motivo = mb_substr(trim(post('reply')), 0, 300);
        $pdo->prepare("UPDATE source_proposals SET status = 'rejected', reply = ?, reviewed_by = ?, reviewed = NOW() WHERE prop_id = ?")->execute([$motivo ?: null, user()['user_id'], $p['prop_id']]);
        fprop_avisar($pdo, (int)user()['user_id'], (int)$p['user_id'], "Hola. Tu propuesta de fuente «{$p['name']}» ({$p['home']}) no se ha añadido al catálogo." . ($motivo !== '' ? " Motivo: $motivo" : '') . " Gracias por proponerla.");
        $out[] = "«{$p['name']}» rechazada; se le ha avisado por mensaje.";
    } else {
        $slug = strtolower(trim(post('slug')));
        $kind = post('kind') === 'newsletter' ? 'newsletter' : 'medio';
        $name = mb_substr(trim(post('name')), 0, 48);
        $home = trim(post('home')); $feed = trim(post('feed'));
        $descr = mb_substr(trim(post('descr')), 0, 200) ?: null;
        $slang = preg_match('/^[a-z]{2}$/', post('lang')) ? post('lang') : '';
        $topic = (int)post('topic_id') ?: null;
        $iv = max(1, min(1440, (int)(post('interval_min') ?: ($kind === 'newsletter' ? 1440 : 60))));
        if (!preg_match('/^[a-z0-9\-]{2,24}$/', $slug)) $out[] = 'Slug no válido (2–24 letras minúsculas, números o guion).';
        elseif ($name === '') $out[] = 'Falta el nombre.';
        elseif (!preg_match('#^https?://[^\s]+$#i', $home) || !preg_match('#^https?://[^\s]+$#i', $feed)) $out[] = 'Web y RSS deben ser URLs http(s).';
        else {
            try {
                $pdo->prepare("INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES (?,?,?,?,?,?,'',?,?,'',?,'ok',0)")
                    ->execute([$slug, $kind, $name, $home, $descr, $slang, $topic, $feed, $iv]);
                $sid = (int)$pdo->lastInsertId();
                $pdo->prepare("UPDATE source_proposals SET status = 'ok', source_id = ?, reviewed_by = ?, reviewed = NOW() WHERE prop_id = ?")->execute([$sid, user()['user_id'], $p['prop_id']]);
                fprop_avisar($pdo, (int)user()['user_id'], (int)$p['user_id'], "Hola. Tu propuesta «{$name}» ya está en el catálogo: /fuente.php?f={$slug} — puedes elegirla en tu perfil, en «" . ($kind === 'newsletter' ? 'Mis substacks' : 'Mis medios') . "». Gracias por proponerla.");
                $out[] = "«{$name}» añadida al catálogo (/fuente.php?f={$slug}); el lector la leerá en los próximos minutos. Se le ha avisado por mensaje.";
            } catch (PDOException $e) { $out[] = 'Ese slug ya existe: elige otro.'; }
        }
    }
}

try {
    $q = $pdo->query("SELECT m.*, u.login AS decider,
                             CASE m.item_type WHEN 'story' THEN s.title WHEN 'comment' THEN c.content ELSE ch.content END AS txt,
                             CASE m.item_type WHEN 'story' THEN s.story_id WHEN 'comment' THEN c.story_id ELSE NULL END AS sid
                        FROM mod_queue m
                        LEFT JOIN stories s  ON m.item_type = 'story'   AND s.story_id  = m.ref_id
                        LEFT JOIN comments c ON m.item_type = 'comment' AND c.comment_id = m.ref_id
                        LEFT JOIN chat ch    ON m.item_type = 'chat'    AND ch.msg_id   = m.ref_id
                        LEFT JOIN users u    ON u.user_id = m.decided_by
                       WHERE m.status IN ('hidden','flag','needs_human')
                       ORDER BY FIELD(m.status, 'hidden', 'flag', 'needs_human'), m.score DESC, m.item_id LIMIT 100")->fetchAll();
    $qstats = $pdo->query("SELECT SUM(status IN ('queued','processing')) cola, SUM(status = 'ok' AND processed > NOW() - INTERVAL 1 DAY) ok24,
                                  SUM(category IS NOT NULL AND processed > NOW() - INTERVAL 1 DAY) sen24 FROM mod_queue")->fetch();
} catch (Throwable $e) { $q = []; $qstats = null; }   // sin la migracion 062

page_header('Moderación');
echo "<div class=\"admin\"><h1>Moderación</h1>";
if (!mod_on()) echo "<p class=\"notice\">La IA moderadora está apagada (<code>mod_enabled = 0</code>): todo se publica y nada pasa por ella. Los reportes siguen llegando aquí.</p>";
else echo "<p class=\"notice\">Todo se publica al momento. La IA local (en el PC de la redacción) lo revisa después con el criterio de las <a href=\"/normas.php\">normas de uso</a> y señala aquí lo dudoso" . (cfg('mod_auto_hide') === '1' ? "; lo muy claro lo oculta hasta que lo veáis" : "; no oculta nada (<code>mod_auto_hide = 0</code>)") . ". Decidís vosotros. " . h(mod_worker_line()) . "</p>";
if ($out) echo "<pre>" . h(implode("\n", $out)) . "</pre>";

/* ---- revision de la IA local ---- */
$TY = ['story' => 'envío', 'comment' => 'comentario', 'chat' => 'chat'];
echo "<h3 id=\"ia\">Señalado por la IA (" . count($q) . ")</h3>";
if ($qstats) echo "<p><small>En cola: " . (int)$qstats['cola'] . " · últimas 24 h: " . (int)$qstats['ok24'] . " sin problemas, " . (int)$qstats['sen24'] . " señalados.</small></p>";
if (!$q) echo "<p>Nada pendiente.</p>";
foreach ($q as $m) {
    $link = $m['item_type'] === 'chat' ? "/chat.php?m={$m['ref_id']}#m{$m['ref_id']}" : ($m['item_type'] === 'comment' ? "/hilo.php?id={$m['sid']}#c{$m['ref_id']}" : "/hilo.php?id={$m['ref_id']}");
    $v = json_decode((string)$m['verdict'], true) ?: [];
    $estado = ['hidden' => '<b>oculto provisionalmente</b>', 'flag' => 'visible, señalado', 'needs_human' => 'la IA no ha podido decidir'][$m['status']];
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . $TY[$m['item_type']] . " #{$m['ref_id']}</b> " . ($m['status'] !== 'hidden' ? "<a href=\"$link\">ver</a>" : "") . "<span class=\"meta\"> · $estado · " . h(hace($m['created'])) . ($m['priority'] ? " · reportado" : "") . "</span></div>";
    echo "<p class=\"meta\">" . h(mod_note_line($m)) . "</p>";
    echo "<blockquote class=\"quote\">" . nl2br(h(mb_strimwidth((string)$m['txt'], 0, 600, '…'))) . "</blockquote>";
    if (!empty($v['cita'])) echo "<p class=\"meta\">Fragmento: «" . h($v['cita']) . "»</p>";
    echo "<div class=\"repacts\"><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"ia\"><input type=\"hidden\" name=\"item\" value=\"{$m['item_id']}\">";
    echo "<button class=\"btn danger\" name=\"dec\" value=\"remove\" title=\"" . ($m['item_type'] === 'story' ? 'el envío se rechaza' : 'se queda con el texto sustituido por el aviso') . "\">Retirar</button> ";
    echo "<button class=\"btn\" name=\"dec\" value=\"keep\" title=\"" . ($m['status'] === 'hidden' ? 'vuelve a verse' : 'se queda como está') . "\">" . ($m['status'] === 'hidden' ? 'Restaurar' : 'Se queda') . "</button></form></div></div>";
}

/* ---- comentarios reportados ---- */
$reps = $pdo->query("SELECT c.comment_id, c.story_id, c.content, c.alias, c.created, c.user_id, u.login, s.title,
                            COUNT(*) n, MIN(r.created) first_rep, GROUP_CONCAT(DISTINCT r.reason ORDER BY r.reason) reasons
                       FROM comment_reports r JOIN comments c ON c.comment_id = r.comment_id JOIN stories s ON s.story_id = c.story_id
                       LEFT JOIN users u ON u.user_id = c.user_id
                      WHERE r.status = 'open' AND c.status = 'ok' AND c.deleted = 0
                      GROUP BY c.comment_id ORDER BY n DESC, first_rep ASC LIMIT 100")->fetchAll();
$RZ = report_reasons();
$notes = mod_notes($pdo, 'comment', array_column($reps, 'comment_id'));
echo "<h3 id=\"reportes\">Comentarios reportados (" . count($reps) . ")</h3>";
if (!$reps) echo "<p>No hay reportes pendientes.</p>";
foreach ($reps as $r) {
    $who = ($r['login'] && ghost_over($r['created'])) ? $r['login'] : $r['alias'];
    $motivos = implode(', ', array_map(fn($k) => $RZ[$k] ?? $k, explode(',', $r['reasons'])));
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . h($who) . "</b> en <a href=\"/hilo.php?id={$r['story_id']}#c{$r['comment_id']}\">" . h(mb_strimwidth($r['title'], 0, 70, '…')) . "</a>";
    echo "<span class=\"meta\"> · " . h(hace($r['created'])) . " · <b>{$r['n']}</b> " . ($r['n'] == 1 ? 'reporte' : 'reportes') . " · " . h($motivos) . "</span></div>";
    echo "<blockquote class=\"quote\">" . nl2br(h(mb_strimwidth($r['content'], 0, 600, '…'))) . "</blockquote>";
    if ($nl = mod_note_line($notes[(int)$r['comment_id']] ?? null)) echo "<p class=\"meta\">" . h($nl) . "</p>";
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
$cnotes = mod_notes($pdo, 'chat', array_column($creps, 'msg_id'));
echo "<h3 id=\"chat\">Mensajes del chat reportados (" . count($creps) . ")</h3>";
if (!$creps) echo "<p>No hay reportes pendientes.</p>";
foreach ($creps as $r) {
    $motivos = implode(', ', array_map(fn($k) => $RZ[$k] ?? $k, explode(',', $r['reasons'])));
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . h(chat_who($r)) . "</b> <a href=\"/chat.php?m={$r['msg_id']}#m{$r['msg_id']}\">#{$r['msg_id']}</a>";
    echo "<span class=\"meta\"> · " . h(hace($r['created'])) . " · <b>{$r['n']}</b> " . ($r['n'] == 1 ? 'reporte' : 'reportes') . " · " . h($motivos) . "</span></div>";
    echo "<blockquote class=\"quote\">" . nl2br(h(mb_strimwidth($r['content'], 0, 600, '…'))) . "</blockquote>";
    if ($nl = mod_note_line($cnotes[(int)$r['msg_id']] ?? null)) echo "<p class=\"meta\">" . h($nl) . "</p>";
    echo "<div class=\"repacts\"><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"chat_borrar\"><input type=\"hidden\" name=\"mid\" value=\"{$r['msg_id']}\"><button class=\"btn danger\" title=\"sustituye el texto por el aviso\">Borrar mensaje</button></form>";
    echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"chat_descartar\"><input type=\"hidden\" name=\"mid\" value=\"{$r['msg_id']}\"><button class=\"btn\" title=\"el mensaje se queda; los reportes se cierran\">Descartar reporte</button></form></div></div>";
}
/* ---- fuentes propuestas ---- */
try { $props = $pdo->query("SELECT p.*, u.login, u.aura FROM source_proposals p JOIN users u ON u.user_id = p.user_id WHERE p.status = 'pending' ORDER BY p.created LIMIT 50")->fetchAll(); } catch (Throwable $e) { $props = []; }
echo "<h3 id=\"fuentes\">Fuentes propuestas (" . count($props) . ")</h3>";
echo "<p><small>Medios y substacks propuestos por cuentas con aura " . h((string)cfg('source_propose_aura', 6)) . " o más. Al proponerlos se prueba el RSS; compruébalo (títulos recientes, idioma, que sea el medio que dice) y apruébalo o recházalo. Al aprobar entra activo y sin destacar; en los dos casos quien lo propuso recibe un mensaje tuyo.</small></p>";
if (!$props) echo "<p>No hay propuestas pendientes.</p>";
else $ptopics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY name")->fetchAll();
foreach ($props as $p) {
    $pid = (int)$p['prop_id']; $ok = (int)$p['test_items'] > 0;
    echo "<div class=\"repitem fprop\"><div class=\"rephead\"><b>" . h($p['name']) . "</b> · " . ($p['kind'] === 'newsletter' ? 'substack/newsletter' : 'medio');
    echo "<span class=\"meta\"> · propuesta por <a href=\"/u.php?u=" . rawurlencode($p['login']) . "\">" . h($p['login']) . "</a> (aura " . h(num_es(round((float)$p['aura'], 1))) . ") · " . h(hace($p['created'])) . "</span></div>";
    echo "<p class=\"meta\">web: <a href=\"" . h($p['home']) . "\" rel=\"nofollow noopener\" target=\"_blank\">" . h($p['home']) . "</a><br>RSS: <a href=\"" . h($p['feed']) . "\" rel=\"nofollow noopener\" target=\"_blank\">" . h($p['feed']) . "</a>" . ($p['descr'] ? "<br>" . h($p['descr']) : '') . "</p>";
    if ($p['note']) echo "<blockquote class=\"quote\">" . nl2br(h($p['note'])) . "</blockquote>";
    echo "<pre class=\"ftest " . ($ok ? 'ok' : 'err') . "\">" . ($ok ? "RSS leído: {$p['test_items']} noticias · " : '') . h((string)$p['test_info']) . "</pre>";
    // aprobar: datos editables, como en admin > Fuentes
    echo "<form method=\"post\" class=\"fprop-ok\"><input type=\"hidden\" name=\"act\" value=\"fprop_ok\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><table>";
    echo "<tr><td>nombre</td><td><input type=\"text\" name=\"name\" maxlength=\"48\" required value=\"" . h($p['name']) . "\"></td></tr>";
    echo "<tr><td>slug</td><td><input type=\"text\" name=\"slug\" maxlength=\"24\" required pattern=\"[a-z0-9\\-]{2,24}\" value=\"" . h(fprop_slug($pdo, $p['name'])) . "\"></td></tr>";
    echo "<tr><td>tipo</td><td><select name=\"kind\"><option value=\"medio\"" . ($p['kind'] === 'medio' ? ' selected' : '') . ">medio</option><option value=\"newsletter\"" . ($p['kind'] === 'newsletter' ? ' selected' : '') . ">substack/newsletter</option></select></td></tr>";
    echo "<tr><td>web</td><td><input type=\"url\" name=\"home\" required value=\"" . h($p['home']) . "\"></td></tr>";
    echo "<tr><td>RSS</td><td><input type=\"url\" name=\"feed\" required value=\"" . h($p['feed']) . "\"> <button class=\"btn sm\" name=\"act\" value=\"fprop_probar\" formnovalidate title=\"volver a leer este RSS\">probar</button></td></tr>";
    echo "<tr><td>descripción</td><td><input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h((string)$p['descr']) . "\"></td></tr>";
    echo "<tr><td>idioma</td><td><select name=\"lang\"><option value=\"\">— detectar del RSS —</option>";
    foreach (DDD_LANGS as $lc => $ln) echo "<option value=\"$lc\"" . ($p['lang'] === $lc ? ' selected' : '') . ">" . h($ln) . " ($lc)</option>";
    echo "</select></td></tr><tr><td>tema al enviar</td><td><select name=\"topic_id\"><option value=\"0\">— el que elija quien envía —</option>";
    foreach ($ptopics as $tp) echo "<option value=\"{$tp['topic_id']}\">" . h($tp['name']) . "</option>";
    echo "</select></td></tr><tr><td>leer cada</td><td><input type=\"number\" name=\"interval_min\" min=\"1\" max=\"1440\" value=\"" . ($p['kind'] === 'newsletter' ? 1440 : 60) . "\"> minutos</td></tr>";
    echo "<tr><td></td><td><button class=\"btn\">Aprobar y añadir al catálogo</button></td></tr></table></form>";
    echo "<form method=\"post\" class=\"repacts\"><input type=\"hidden\" name=\"act\" value=\"fprop_no\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"text\" name=\"reply\" maxlength=\"300\" placeholder=\"motivo (se le envía)\" style=\"flex:1;min-width:180px\"> <button class=\"btn danger\">Rechazar</button></form></div>";
}
/* ---- voto coordinado ---- */
try { $vc = votos_coordinados($pdo); } catch (Throwable $e) { $vc = ['pares' => [], 'conexion' => []]; }   // sin la migracion 080
echo "<h3 id=\"votos\">Cuentas que votan juntas (" . count($vc['pares']) . " parejas · " . count($vc['conexion']) . " envíos con varias cuentas desde una conexión)</h3>";
echo "<p><small>Última semana. Parejas de cuentas que coinciden en cuatro o más envíos, en el mismo sentido y con menos de 10 minutos de diferencia; y envíos votados por más de una cuenta desde la misma conexión (en el δ esos votos ya cuentan como uno). El δ no resta nada por esto: solo se enseña. Dos amigos que leen lo mismo también salen aquí; mirad antes de actuar.</small></p>";
if (!$vc['pares']) echo "<p>Ninguna pareja llamativa.</p>";
else {
    echo "<table><tr><th>cuentas</th><th>coincidencias</th><th>sentido</th><th>votos de cada una (7 d)</th><th>última</th></tr>";
    foreach ($vc['pares'] as $r) echo "<tr><td><a href=\"/u.php?u=" . rawurlencode($r['la']) . "\">" . h($r['la']) . "</a> · <a href=\"/u.php?u=" . rawurlencode($r['lb']) . "\">" . h($r['lb']) . "</a></td><td class=\"num\"><b>{$r['n']}</b></td><td>{$r['pos']} ↑ · " . ($r['n'] - $r['pos']) . " ↓</td><td>{$r['na']} · {$r['nb']}</td><td>" . h(hace($r['hasta'])) . "</td></tr>";
    echo "</table>";
}
if ($vc['conexion']) {
    echo "<table><tr><th>envío</th><th>cuentas desde la misma conexión</th><th>último voto</th></tr>";
    foreach ($vc['conexion'] as $r) echo "<tr><td><a href=\"/hilo.php?id={$r['story_id']}\">" . h(mb_strimwidth($r['title'], 0, 60, '…')) . "</a></td><td><b>{$r['cuentas']}</b>: " . h($r['logins']) . "</td><td>" . h(hace($r['ultimo'])) . "</td></tr>";
    echo "</table>";
}

$topics = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id) n FROM topics t ORDER BY t.name")->fetchAll();
echo "<h3 id=\"temas\">Temas (" . count($topics) . ")</h3>";
echo "<table><tr><th>tema</th><th>envíos</th><th>color</th><th></th><th>borrar (moviendo sus envíos a…)</th></tr>";
foreach ($topics as $t) {
    echo "<tr><td><form method=\"post\" id=\"te{$t['topic_id']}\" style=\"display:flex;gap:6px;align-items:center\"><input type=\"hidden\" name=\"act\" value=\"tema_editar\"><input type=\"hidden\" name=\"topic_id\" value=\"{$t['topic_id']}\">";
    echo "<input type=\"text\" name=\"name\" value=\"" . h($t['name']) . "\" maxlength=\"32\" required style=\"width:150px\"></form></td>";
    echo "<td class=\"num\"><a href=\"/tema.php?t={$t['topic_id']}\">{$t['n']}</a></td>";
    echo "<td><input type=\"color\" name=\"color\" form=\"te{$t['topic_id']}\" value=\"" . h($t['color']) . "\"></td><td><button class=\"btn\" form=\"te{$t['topic_id']}\">Guardar</button></td>";
    if (count($topics) > 1) {
        echo "<td><form method=\"post\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap\"><input type=\"hidden\" name=\"act\" value=\"tema_borrar\"><input type=\"hidden\" name=\"topic_id\" value=\"{$t['topic_id']}\"><select name=\"to\">";
        foreach ($topics as $o) if ((int)$o['topic_id'] !== (int)$t['topic_id']) echo "<option value=\"{$o['topic_id']}\">" . h($o['name']) . "</option>";
        echo "</select><label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> seguro</label><button class=\"btn danger\">Borrar</button></form></td>";
    } else echo "<td></td>";
    echo "</tr>";
}
echo "</table>";
echo "<form method=\"post\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-top:8px\"><input type=\"hidden\" name=\"act\" value=\"tema\">";
echo "<input type=\"text\" name=\"name\" placeholder=\"nuevo tema\" maxlength=\"32\" required style=\"flex:1;min-width:140px\"> <button class=\"btn\">Añadir tema</button></form>";
echo "<p><small>Renombrar cambia el nombre en todos sus envíos. Borrar un tema mueve sus envíos (y los medios que lo proponían, y quien lo tenía como página de entrada) al tema elegido; no se pierde nada. El tema de cada envío se cambia desde su hilo (desplegable bajo los votos). El bonus del δ lo ajusta el admin.</small></p>";
$bd = $pdo->query("SELECT b.*, u.login FROM banned_domains b LEFT JOIN users u ON u.user_id = b.added_by ORDER BY b.domain")->fetchAll();
$nauto = count(array_filter($bd, fn($b) => str_starts_with((string)$b['reason'], 'auto:')));
echo "<h3 id=\"dominios\">Dominios de correo no válidos — lista negra (" . count($bd) . ($nauto ? ", $nauto automáticos" : "") . ")</h3>";
echo "<p><small>No se aceptan registros con correos de estos dominios ni de sus subdominios (desechables, de spam…). Al registrarse, si el dominio no está en la lista blanca, se comprueba que su web responde (" . ((string)cfg('email_web_check', '1') === '1' ? 'activado' : '<b>desactivado</b>') . ": <code>email_web_check</code>); si no responde, entra aquí solo con el motivo «auto: …» y la persona ve el aviso de email temporal. Si alguien escribe diciendo que es un error, pasa su dominio a la lista blanca y que se registre de nuevo. Además se rechazan los dominios sin registros MX.</small></p>";
echo "<form method=\"post\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:8px\"><input type=\"hidden\" name=\"act\" value=\"dominio\">";
echo "<input type=\"text\" name=\"domain\" placeholder=\"dominio.com\" required style=\"flex:1;min-width:140px\"> <input type=\"text\" name=\"reason\" placeholder=\"motivo (opcional)\" style=\"flex:1;min-width:140px\"> <button class=\"btn\">Vetar dominio</button></form>";
if ($bd) {
    echo "<details><summary>Ver lista</summary><table>";
    foreach ($bd as $b) {
        echo "<tr><td><code>" . h($b['domain']) . "</code></td><td>" . h((string)$b['reason']) . "</td><td><small>" . h($b['login'] ?: 'sistema') . " · " . h(hace($b['created'])) . "</small></td>";
        echo "<td style=\"white-space:nowrap\"><form method=\"post\" style=\"display:inline\"><input type=\"hidden\" name=\"act\" value=\"dominio_quitar\"><input type=\"hidden\" name=\"domain\" value=\"" . h($b['domain']) . "\"><button class=\"btn\">quitar</button></form> ";
        echo "<form method=\"post\" style=\"display:inline\"><input type=\"hidden\" name=\"act\" value=\"dominio_a_blanca\"><input type=\"hidden\" name=\"domain\" value=\"" . h($b['domain']) . "\"><button class=\"btn\" title=\"quitar de aquí y admitirlo siempre\">a la lista blanca</button></form></td></tr>";
    }
    echo "</table></details>";
}
try { $wd = $pdo->query("SELECT w.*, u.login FROM allowed_domains w LEFT JOIN users u ON u.user_id = w.added_by ORDER BY w.domain")->fetchAll(); } catch (Throwable $e) { $wd = []; }
echo "<form method=\"post\" action=\"/moderacion.php#probado\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:8px 0\"><input type=\"hidden\" name=\"act\" value=\"dominio_probar\"><input type=\"text\" name=\"domain\" placeholder=\"probar un dominio: iol.ie\" required style=\"flex:1;min-width:140px\"> <button class=\"btn\" title=\"ver qué contesta su web desde el servidor, sin tocar las listas\">Probar</button></form>";
if (!empty($probado)) echo "<pre id=\"probado\">" . h($probado) . "</pre>";
echo "<h3 id=\"dominios-ok\">Dominios de correo admitidos — lista blanca (" . count($wd) . ")</h3>";
echo "<p><small>Proveedores reconocidos (Gmail, Outlook, Yahoo, iCloud, Proton…) y dominios revisados a mano: se admiten, con sus subdominios, sin comprobar su web. La lista negra manda sobre esta.</small></p>";
echo "<form method=\"post\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:8px\"><input type=\"hidden\" name=\"act\" value=\"blanca\">";
echo "<input type=\"text\" name=\"domain\" placeholder=\"dominio.com o correo entero\" required style=\"flex:1;min-width:140px\"> <input type=\"text\" name=\"reason\" placeholder=\"motivo (opcional)\" maxlength=\"120\" style=\"flex:1;min-width:140px\"> <button class=\"btn\">Admitir dominio</button></form>";
if ($wd) {
    echo "<details><summary>Ver lista</summary><table>";
    foreach ($wd as $w) {
        echo "<tr><td><code>" . h($w['domain']) . "</code></td><td>" . h((string)$w['reason']) . "</td><td><small>" . h($w['login'] ?: 'sistema') . " · " . h(hace($w['created'])) . "</small></td>";
        echo "<td><form method=\"post\" style=\"display:inline\"><input type=\"hidden\" name=\"act\" value=\"blanca_quitar\"><input type=\"hidden\" name=\"domain\" value=\"" . h($w['domain']) . "\"><button class=\"btn\">quitar</button></form></td></tr>";
    }
    echo "</table></details>";
}
try { $bs = $pdo->query("SELECT b.*, u.login FROM banned_sources b LEFT JOIN users u ON u.user_id = b.added_by ORDER BY b.domain")->fetchAll(); } catch (Throwable $e) { $bs = []; }
echo "<h3 id=\"medios\">Medios bloqueados (" . count($bs) . ")</h3>";
echo "<p><small>No se admiten enlaces a estos medios (por ejemplo, los sancionados por la UE como RT o Sputnik). Se bloquea el dominio y todos sus subdominios; vale pegar el enlace entero. Se comprueba al enviar y al editar un envío.</small></p>";
echo "<form method=\"post\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:8px\"><input type=\"hidden\" name=\"act\" value=\"medio\">";
echo "<input type=\"text\" name=\"domain\" placeholder=\"https://medio.com/… o medio.com\" required style=\"flex:2;min-width:200px\"> <input type=\"text\" name=\"reason\" placeholder=\"motivo (opcional)\" maxlength=\"120\" style=\"flex:1;min-width:140px\"> <button class=\"btn\">Bloquear medio</button></form>";
if ($bs) {
    echo "<details><summary>Ver lista</summary><table>";
    foreach ($bs as $b) {
        echo "<tr><td><code>" . h($b['domain']) . "</code></td><td>" . h((string)$b['reason']) . "</td><td><small>" . h($b['login'] ?: 'sistema') . " · " . h(hace($b['created'])) . "</small></td>";
        echo "<td><form method=\"post\" style=\"display:inline\"><input type=\"hidden\" name=\"act\" value=\"medio_quitar\"><input type=\"hidden\" name=\"domain\" value=\"" . h($b['domain']) . "\"><button class=\"btn\">quitar</button></form></td></tr>";
    }
    echo "</table></details>";
}
echo "</div>";
page_footer();
