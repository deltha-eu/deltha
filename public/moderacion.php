<?php
/** /moderacion — moderadores y admin, por pestañas (?t=, como /admin; 09/10/2026): IA (lo señalado por la IA local, lib/moderacion.php),
 *  Reportes (comentarios y chat), Fuentes (propuestas, alta y edición del catálogo), Votos (cuentas que votan juntas), Temas,
 *  Correos (listas negra y blanca de dominios) y Medios bloqueados. Los formularios sin action vuelven solos a su pestaña (POST a la misma URL). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/chat.php';
require __DIR__ . '/../lib/propuestas.php';
require __DIR__ . '/../lib/moderacion.php';
require_once __DIR__ . '/../lib/mail.php';   // listas de dominios de correo (email_domain_normalize)
if (!user()) redirect('/login');
if (!is_mod()) { http_response_code(403); page_header('Moderación'); echo "<p class=\"end\">Solo moderadores.</p>"; page_footer(); exit; }

$out = [];
mod_log_post($pdo, 'moderacion', $out);   // registro de acciones (migra-134): apunta accion, campos y resultado al terminar
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
    else { $pdo->prepare("UPDATE comment_reports SET status = 'dismissed', decided_by = ?, decided = NOW() WHERE comment_id = ? AND status = 'open'")->execute([user()['user_id'], $cid]); $out[] = "Reportes del comentario #$cid descartados."; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('act'), ['chat_borrar', 'chat_descartar'], true)) {
    $mid = (int)post('mid');
    if (post('act') === 'chat_borrar') { chat_delete_mod($pdo, $mid); $out[] = "Mensaje #$mid del chat eliminado (texto sustituido por el aviso)."; }
    else { $pdo->prepare("UPDATE chat_reports SET status = 'dismissed', decided_by = ?, decided = NOW() WHERE msg_id = ? AND status = 'open'")->execute([user()['user_id'], $mid]); $out[] = "Reportes del mensaje #$mid descartados."; }
    if (post('back') === '1') redirect("/chat?m=$mid#m$mid");   // «eliminar» desde el propio chat
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'ia') {   // decision humana sobre lo que ha señalado u ocultado la IA
    $item = (int)post('item');
    $r = mod_human($pdo, $item, post('dec') === 'remove' ? 'remove' : 'keep', (int)user()['user_id']);
    $out[] = $r ? "#$item: $r." : "#$item ya estaba resuelto.";
}

/* ---- alta directa de una fuente por un moderador (09/10/2026): mismos campos que al aprobar una propuesta, sin propuesta de por medio.
 *      Sin RSS se busca en la web (fprop_probar); si no se lee nada, no se crea y se enseña el error. ---- */
$altaF = ['name' => '', 'kind' => 'medio', 'home' => '', 'feed' => '', 'descr' => '', 'lang' => '', 'interval_min' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'fuente_alta') {
    require_once __DIR__ . '/../lib/fuentes.php';
    foreach ($altaF as $k => $v) $altaF[$k] = trim(post($k));
    // «Rellenar desde la web» o nombre vacio (13/10/2026): se leen nombre, RSS, descripcion e idioma de la web y se devuelve el formulario relleno, sin crear
    if (post('rellenar') !== '' || $altaF['name'] === '') {
        $rf = fprop_rellenar($altaF);
        $altaF = array_intersect_key($rf, $altaF) + $altaF;
        $out[] = $rf['error'] !== '' ? $rf['error'] : 'Datos tomados de la web (RSS: ' . h($rf['feed']) . ', ' . (int)$rf['items'] . ' noticias). Revísalos y pulsa «Dar de alta».';
        goto alta_fin;
    }
    $name = mb_substr($altaF['name'], 0, 48);
    $kind = in_array($altaF['kind'], ['medio', 'newsletter', 'youtube', 'agregador'], true) ? $altaF['kind'] : 'medio';
    $home = $altaF['home']; $feed = $altaF['feed'];
    foreach (['home', 'feed'] as $k) if ($$k !== '' && !preg_match('#^https?://#i', $$k)) $$k = 'https://' . $$k;
    $slang = preg_match('/^[a-z]{2}$/', $altaF['lang']) ? $altaF['lang'] : '';
    $iv = max(1, min(1440, (int)($altaF['interval_min'] ?: ($kind === 'newsletter' ? 1440 : ($kind === 'youtube' ? 180 : 60)))));
    if ($name === '') $out[] = 'Falta el nombre.';
    elseif (!preg_match('#^https?://[^\s]+$#i', $home)) $out[] = 'La web debe ser una URL http(s).';
    elseif (($ya = fprop_existe($pdo, $feed, $home)) !== null) $out[] = "Ya está en el catálogo o pendiente: $ya.";
    else {
        if ($kind === 'youtube' && $feed === '') { $e = null; $feed = (string)(youtube_feed_de($home, $e) ?? ''); }
        $r = fprop_probar($feed, $home);
        if (!$r['items'] && !in_array($kind, ['agregador'], true)) $out[] = 'No se ha podido leer ningún RSS de esa fuente: ' . (string)$r['error'] . ' Pon la dirección del RSS a mano si la conoces.';
        else {
            $feed = $r['feed'] !== '' ? (string)$r['feed'] : ($feed !== '' ? $feed : $home);
            if ($slang === '' && preg_match('/^[a-z]{2}$/', (string)$r['lang'])) $slang = (string)$r['lang'];
            $slug = fprop_slug($pdo, $name);
            try {
                $pdo->prepare("INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES (?,?,?,?,?,?,'',NULL,?,'',?,'ok',0)")
                    ->execute([$slug, $kind, $name, mb_substr($home, 0, 200), mb_substr($altaF['descr'], 0, 200) ?: null, $slang, mb_substr($feed, 0, 300), $iv]);
                $out[] = "«{$name}» añadida al catálogo (/fuente/{$slug})" . ($r['items'] ? ", RSS leído: {$r['items']} noticias" : '') . "; el lector la leerá en los próximos minutos. Los detalles (tema, destacada, skin) se afinan en admin → Fuentes.";
                $altaF = ['name' => '', 'kind' => 'medio', 'home' => '', 'feed' => '', 'descr' => '', 'lang' => '', 'interval_min' => ''];
            } catch (PDOException $e) { $out[] = 'No se ha podido crear la fuente: ' . $e->getMessage(); }
        }
    }
    alta_fin:
}

/* ---- editar una fuente del catalogo (09/10/2026): lo basico (nombre, tipo, web, RSS, descripcion, idioma, intervalo, encendida);
 *      slug, tema, skin, destacada y lector local se quedan en admin → Fuentes. ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'fuente_editar') {
    $sid = (int)post('source_id');
    $name = mb_substr(trim(post('name')), 0, 48);
    $kind = in_array(post('kind'), ['medio', 'newsletter', 'youtube', 'agregador'], true) ? post('kind') : 'medio';
    $home = trim(post('home')); $feed = trim(post('feed'));
    foreach (['home', 'feed'] as $k) if ($$k !== '' && !preg_match('#^https?://#i', $$k)) $$k = 'https://' . $$k;
    $slang = preg_match('/^[a-z]{2,5}$/', post('lang')) ? post('lang') : '';
    $iv = max(1, min(1440, (int)post('interval_min') ?: 60));
    $status = post('status') === 'off' ? 'off' : 'ok';
    if ($name === '') $out[] = 'Falta el nombre.';
    elseif (!preg_match('#^https?://[^\s]+$#i', $home)) $out[] = 'La web debe ser una URL http(s).';
    else {
        $pdo->prepare("UPDATE sources SET name = ?, kind = ?, home = ?, feed_portada = ?, descr = ?, lang = ?, interval_min = ?, status = ? WHERE source_id = ?")
            ->execute([$name, $kind, mb_substr($home, 0, 200), mb_substr($feed, 0, 300), mb_substr(trim(post('descr')), 0, 200) ?: null, $slang, $iv, $status, $sid]);
        $out[] = "«{$name}» guardada.";
    }
}

/* ---- fuentes con error (11/10/2026, /moderacion -> Errores): apagar, volver a encender o releer ahora ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('act'), ['fuente_apagar', 'fuente_encender', 'fuente_releer'], true)) {
    require_once __DIR__ . '/../lib/fuentes.php';
    $q = $pdo->prepare("SELECT * FROM sources WHERE source_id = ?"); $q->execute([(int)post('source_id')]);
    if (!($fsrc = $q->fetch())) $out[] = 'Esa fuente no existe.';
    elseif (post('act') === 'fuente_apagar') { $pdo->prepare("UPDATE sources SET status = 'off' WHERE source_id = ?")->execute([(int)$fsrc['source_id']]); $out[] = "«{$fsrc['name']}» apagada: no se lee ni sale en el catálogo. Se vuelve a encender desde aquí o desde admin → Fuentes."; }
    elseif (post('act') === 'fuente_encender') { $pdo->prepare("UPDATE sources SET status = 'ok', last_error = NULL WHERE source_id = ?")->execute([(int)$fsrc['source_id']]); $out[] = "«{$fsrc['name']}» encendida; el lector la leerá en los próximos minutos."; }
    else { try { $out = array_merge($out, fuente_actualizar($pdo, $fsrc)); $out[] = "«{$fsrc['name']}» releída."; } catch (Throwable $e) { $out[] = "«{$fsrc['name']}»: ERROR " . $e->getMessage(); } }
}

/* ---- fuentes propuestas por los usuarios (lib/propuestas.php) ---- */
$fprop_test = [];   // valores recién probados: el formulario los conserva aunque no todos sean columnas de source_proposals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('act'), ['fprop_probar', 'fprop_ok', 'fprop_no'], true)) {
    $q = $pdo->prepare("SELECT * FROM source_proposals WHERE prop_id = ? AND status = 'pending'"); $q->execute([(int)post('pid')]);
    if (!($p = $q->fetch())) $out[] = 'Esa propuesta ya no está pendiente.';
    elseif (post('act') === 'fprop_probar') {
        // Se prueba con lo escrito en el formulario (no con la fila antigua): «probar» no debe deshacer los cambios.
        $home = array_key_exists('home', $_POST) ? mb_substr(trim(post('home')), 0, 200) : $p['home'];
        $feed = array_key_exists('feed', $_POST) ? mb_substr(trim(post('feed')), 0, 300) : $p['feed'];
        $name = mb_substr(trim(post('name')), 0, 48) ?: $p['name'];
        $kind = in_array(post('kind'), ['medio', 'newsletter', 'youtube', 'agregador'], true) ? post('kind') : $p['kind'];
        $descr = array_key_exists('descr', $_POST) ? (mb_substr(trim(post('descr')), 0, 200) ?: null) : $p['descr'];
        $slang = preg_match('/^[a-z]{2}$/', post('lang')) ? post('lang') : '';
        $r = fprop_probar($feed, $home);
        $effFeed = $r['feed'] !== '' ? $r['feed'] : $feed;   // si la web anuncia otro RSS, se guarda el que funciona
        $effLang = $slang !== '' ? $slang : $r['lang'];
        $pdo->prepare("UPDATE source_proposals SET name = ?, kind = ?, home = ?, feed = ?, descr = ?, lang = ?, test_items = ?, test_info = ? WHERE prop_id = ?")
            ->execute([$name, $kind, $home, $effFeed, $descr, $effLang, $r['items'], fprop_info($r), $p['prop_id']]);
        $fprop_test[(int)$p['prop_id']] = [
            'name' => $name, 'kind' => $kind, 'home' => $home, 'feed' => $effFeed, 'descr' => $descr, 'lang' => $effLang,
            'topic_id' => (int)post('topic_id'),
            'interval_min' => max(1, min(1440, (int)post('interval_min'))),
        ];
        if (array_key_exists('slug', $_POST)) $fprop_test[(int)$p['prop_id']]['slug'] = mb_substr(strtolower(trim(post('slug'))), 0, 24);
        $out[] = "«{$name}»: " . ($r['items'] ? "{$r['items']} noticias en el RSS, idioma detectado " . ($r['lang'] ?: '?') . ", la última de {$r['last']}." : "el RSS no se lee — {$r['error']}");
    } elseif (post('act') === 'fprop_no') {
        $motivo = mb_substr(trim(post('reply')), 0, 300);
        $pdo->prepare("UPDATE source_proposals SET status = 'rejected', reply = ?, reviewed_by = ?, reviewed = NOW() WHERE prop_id = ?")->execute([$motivo ?: null, user()['user_id'], $p['prop_id']]);
        // la fuente ya existia (se la añadio el desde «Mis medios», migra-108): sigue siendo solo suya salvo que se marque «retirar»
        $retirada = $p['source_id'] && post('retirar') === '1';
        if ($retirada) $pdo->prepare("UPDATE sources SET status = 'off' WHERE source_id = ? AND listed = 0")->execute([(int)$p['source_id']]);
        fprop_avisar($pdo, (int)user()['user_id'], (int)$p['user_id'], "Hola. Tu propuesta de fuente «{$p['name']}» ({$p['home']}) no se ha añadido al catálogo" . ($p['source_id'] ? ($retirada ? ' y se ha retirado de tus medios' : '; la sigues teniendo en tus medios') : '') . "." . ($motivo !== '' ? " Motivo: $motivo" : '') . " Gracias por proponerla.");
        $out[] = "«{$p['name']}» rechazada; se le ha avisado por mensaje.";
    } else {
        $slug = strtolower(trim(post('slug')));
        $kind = in_array(post('kind'), ['newsletter', 'youtube', 'agregador'], true) ? post('kind') : 'medio';
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
                if ($p['source_id']) {   // ya creada por quien se la añadio (migra-108): se corrigen sus datos y pasa al catalogo publico
                    $sid = (int)$p['source_id'];
                    $pdo->prepare("UPDATE sources SET slug = ?, kind = ?, name = ?, home = ?, descr = ?, lang = ?, topic_id = ?, feed_portada = ?, interval_min = ?, status = 'ok', listed = 1 WHERE source_id = ?")
                        ->execute([$slug, $kind, $name, $home, $descr, $slang, $topic, $feed, $iv, $sid]);
                } else {
                    $pdo->prepare("INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES (?,?,?,?,?,?,'',?,?,'',?,'ok',0)")
                        ->execute([$slug, $kind, $name, $home, $descr, $slang, $topic, $feed, $iv]);
                    $sid = (int)$pdo->lastInsertId();
                }
                $pdo->prepare("UPDATE source_proposals SET status = 'ok', source_id = ?, reviewed_by = ?, reviewed = NOW() WHERE prop_id = ?")->execute([$sid, user()['user_id'], $p['prop_id']]);
                fprop_avisar($pdo, (int)user()['user_id'], (int)$p['user_id'], "Hola. Tu propuesta «{$name}» ya está en el catálogo: /fuente/{$slug} — puedes elegirla en tu perfil, en «" . ($kind === 'newsletter' ? 'Mis substacks' : ($kind === 'youtube' ? 'Mis canales' : 'Mis medios')) . "». Gracias por proponerla.");
                $out[] = "«{$name}» añadida al catálogo (/fuente/{$slug}); el lector la leerá en los próximos minutos. Se le ha avisado por mensaje.";
            } catch (PDOException $e) { $out[] = 'Ese slug ya existe: elige otro.'; }
        }
    }
}

try {
    $q = $pdo->query("SELECT m.*, u.login AS decider,
                             CASE m.item_type WHEN 'story' THEN s.title WHEN 'comment' THEN c.content ELSE ch.content END AS txt,
                             CASE m.item_type WHEN 'story' THEN s.story_id WHEN 'comment' THEN c.story_id ELSE NULL END AS sid,
                             cs.title AS stitle, cs.status AS sstatus, COALESCE(au.login, c.alias, ch.alias) AS autor,
                             pc.comment_id AS pid, COALESCE(pu.login, pc.alias) AS pautor, pc.content AS ptxt, pc.status AS pstatus,
                             chr.msg_id AS rid, COALESCE(ru.login, chr.alias) AS rautor, chr.content AS rtxt
                        FROM mod_queue m
                        LEFT JOIN stories s  ON m.item_type = 'story'   AND s.story_id  = m.ref_id
                        LEFT JOIN comments c ON m.item_type = 'comment' AND c.comment_id = m.ref_id
                        LEFT JOIN chat ch    ON m.item_type = 'chat'    AND ch.msg_id   = m.ref_id
                        LEFT JOIN stories cs ON cs.story_id = c.story_id
                        LEFT JOIN users au   ON au.user_id = COALESCE(c.user_id, ch.user_id)
                        LEFT JOIN comments pc ON pc.comment_id = c.parent_id
                        LEFT JOIN users pu   ON pu.user_id = pc.user_id
                        LEFT JOIN chat chr   ON chr.msg_id = ch.reply_to
                        LEFT JOIN users ru   ON ru.user_id = chr.user_id
                        LEFT JOIN users u    ON u.user_id = m.decided_by
                       WHERE m.status IN ('hidden','flag','needs_human')
                       ORDER BY FIELD(m.status, 'hidden', 'flag', 'needs_human'), m.score DESC, m.item_id LIMIT 100")->fetchAll();
    $qstats = $pdo->query("SELECT SUM(status IN ('queued','processing')) cola, SUM(status = 'ok' AND processed > NOW() - INTERVAL 1 DAY) ok24,
                                  SUM(category IS NOT NULL AND processed > NOW() - INTERVAL 1 DAY) sen24 FROM mod_queue")->fetch();
} catch (Throwable $e) { $q = []; $qstats = null; }   // sin la migracion 062

$TABS = ['ia' => 'IA', 'reportes' => 'Reportes', 'fuentes' => 'Fuentes', 'errores' => 'Errores', 'votos' => 'Votos', 'temas' => 'Temas', 'dominios' => 'Correos', 'medios' => 'Medios bloqueados'];
$tab = (string)($_GET['t'] ?? $_POST['t'] ?? 'ia');
if (!isset($TABS[$tab])) $tab = 'ia';
// pendientes en la pestaña: lo que espera decision (señalado por la IA, reportes abiertos, propuestas de fuentes)
$pend = ['ia' => count($q)];
try { $pend['reportes'] = (int)$pdo->query("SELECT (SELECT COUNT(DISTINCT r.comment_id) FROM comment_reports r JOIN comments c ON c.comment_id = r.comment_id WHERE r.status = 'open' AND c.status = 'ok' AND c.deleted = 0) + (SELECT COUNT(DISTINCT r.msg_id) FROM chat_reports r JOIN chat c ON c.msg_id = r.msg_id WHERE r.status = 'open' AND c.status = 'ok' AND c.deleted = 0)")->fetchColumn(); } catch (Throwable $e) { $pend['reportes'] = 0; }
try { $pend['fuentes'] = (int)$pdo->query("SELECT COUNT(*) FROM source_proposals WHERE status = 'pending'")->fetchColumn(); } catch (Throwable $e) { $pend['fuentes'] = 0; }
try { $pend['errores'] = (int)$pdo->query("SELECT COUNT(*) FROM sources WHERE status = 'ok' AND last_error IS NOT NULL AND last_error <> ''")->fetchColumn(); } catch (Throwable $e) { $pend['errores'] = 0; }
page_header('Moderación');
echo "<div class=\"admin\"><h1>Moderación</h1>";
echo "<nav class=\"tabs\">";
foreach ($TABS as $k => $v) echo "<a href=\"/moderacion?t=$k\"" . ($k === $tab ? ' class="on"' : '') . ">$v" . (!empty($pend[$k]) ? " <small>{$pend[$k]}</small>" : '') . "</a>";
echo "</nav>";
if ($tab === 'ia') { if (!mod_on()) echo "<p class=\"notice\">La IA moderadora está apagada (<code>mod_enabled = 0</code>): todo se publica y nada pasa por ella. Los reportes siguen llegando aquí.</p>";
else echo "<p class=\"notice\">Todo se publica al momento. La IA local (en el PC de la redacción) lo revisa después con el criterio de las <a href=\"/normas\">normas de uso</a> y señala aquí lo dudoso" . (cfg('mod_auto_hide') === '1' ? "; lo muy claro" . (($hc = trim((string)cfg('mod_hide_cats', 'spam'))) !== '' ? " (" . h($hc) . ")" : "") . (($ht = trim((string)cfg('mod_hide_types', 'comment'))) !== '' ? " en " . h(str_replace(['comment', 'story', 'chat'], ['comentarios', 'envíos', 'chat'], $ht)) : "") . (cfg('mod_hide_links_only', '1') === '1' ? ", y solo con enlace," : "") . " lo oculta hasta que lo veáis" : "; no oculta nada (<code>mod_auto_hide = 0</code>)") . ". Decidís vosotros. " . h(mod_worker_line()) . "</p>";
}
if ($out) echo "<pre>" . h(implode("\n", $out)) . "</pre>";

if ($tab === 'ia') {
/* ---- revision de la IA local ---- */
$TY = ['story' => 'envío', 'comment' => 'comentario', 'chat' => 'chat'];
echo "<h3 id=\"ia\">Señalado por la IA (" . count($q) . ")</h3>";
if ($qstats) echo "<p><small>En cola: " . (int)$qstats['cola'] . " · últimas 24 h: " . (int)$qstats['ok24'] . " sin problemas, " . (int)$qstats['sen24'] . " señalados.</small></p>";
if (!$q) echo "<p>Nada pendiente.</p>";
foreach ($q as $m) {
    $link = $m['item_type'] === 'chat' ? "/chat?m={$m['ref_id']}#m{$m['ref_id']}" : ($m['item_type'] === 'comment' ? "/hilo/{$m['sid']}#c{$m['ref_id']}" : "/hilo/{$m['ref_id']}");
    $v = json_decode((string)$m['verdict'], true) ?: [];
    $estado = ['hidden' => '<b>oculto provisionalmente</b>', 'flag' => 'visible, señalado', 'needs_human' => 'la IA no ha podido decidir'][$m['status']];
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . $TY[$m['item_type']] . " #{$m['ref_id']}</b> " . ($m['status'] !== 'hidden' ? "<a href=\"$link\">ver</a>" : "") . "<span class=\"meta\"> · $estado · " . h(hace($m['created'])) . ($m['priority'] ? " · reportado" : "") . "</span></div>";
    echo "<p class=\"meta\">" . h(mod_note_line($m)) . "</p>";
    // donde esta (28/10/2026): hilo al que pertenece el comentario y de que cuelga; en el chat, a que mensaje responde
    if ($m['item_type'] === 'comment') {
        $ctx = "Por <b>" . h((string)$m['autor']) . "</b> en el hilo <a href=\"/hilo/{$m['sid']}\">«" . h(mb_strimwidth((string)$m['stitle'], 0, 90, '…')) . "»</a>"
             . (in_array($m['sstatus'], ['pending_mod', 'rejected', 'discard'], true) ? " <i>(hilo no visible: {$m['sstatus']})</i>" : "");
        if ($m['pid']) $ctx .= " · responde a <a href=\"/hilo/{$m['sid']}#c{$m['pid']}\">#{$m['pid']}</a> de <b>" . h((string)$m['pautor']) . "</b>: «" . h(mb_strimwidth(preg_replace('/\s+/', ' ', (string)$m['ptxt']), 0, 120, '…')) . "»" . ($m['pstatus'] !== 'ok' ? " <i>(no visible)</i>" : "");
        else $ctx .= " · comentario raíz";
        echo "<p class=\"meta\">$ctx</p>";
    } elseif ($m['item_type'] === 'chat') {
        echo "<p class=\"meta\">Por <b>" . h((string)$m['autor']) . "</b>" . ($m['rid'] ? " · responde a <a href=\"/chat?m={$m['rid']}#m{$m['rid']}\">#{$m['rid']}</a> de <b>" . h((string)$m['rautor']) . "</b>: «" . h(mb_strimwidth(preg_replace('/\s+/', ' ', (string)$m['rtxt']), 0, 120, '…')) . "»" : "") . "</p>";
    }
    echo "<blockquote class=\"quote\">" . saltos(h(mb_strimwidth((string)$m['txt'], 0, 600, '…'))) . "</blockquote>";
    if (!empty($v['cita'])) echo "<p class=\"meta\">Fragmento: «" . h($v['cita']) . "»</p>";
    echo "<div class=\"repacts\"><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"ia\"><input type=\"hidden\" name=\"item\" value=\"{$m['item_id']}\">";
    echo "<button class=\"btn danger\" name=\"dec\" value=\"remove\" title=\"" . ($m['item_type'] === 'story' ? 'el envío se rechaza' : ($m['status'] === 'hidden' ? 'desaparece sin dejar rastro (estaba oculto)' : 'se queda con el texto sustituido por el aviso')) . "\">" . ($m['status'] === 'hidden' && $m['item_type'] !== 'story' ? 'Eliminar' : 'Retirar') . "</button> ";
    echo "<button class=\"btn\" name=\"dec\" value=\"keep\" title=\"" . ($m['status'] === 'hidden' ? 'vuelve a verse' : 'se queda como está') . "\">" . ($m['status'] === 'hidden' ? 'Restaurar' : 'Se queda') . "</button></form></div></div>";
}

} elseif ($tab === 'reportes') {
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
    $who = ($r['login'] && ghost_over_comment($r['created'])) ? $r['login'] : $r['alias'];
    $motivos = implode(', ', array_map(fn($k) => $RZ[$k] ?? $k, explode(',', $r['reasons'])));
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . h($who) . "</b> en <a href=\"/hilo/{$r['story_id']}#c{$r['comment_id']}\">" . h(mb_strimwidth($r['title'], 0, 70, '…')) . "</a>";
    echo "<span class=\"meta\"> · " . h(hace($r['created'])) . " · <b>{$r['n']}</b> " . ($r['n'] == 1 ? 'reporte' : 'reportes') . " · " . h($motivos) . "</span></div>";
    echo "<blockquote class=\"quote\">" . saltos(h(mb_strimwidth($r['content'], 0, 600, '…'))) . "</blockquote>";
    if ($nl = mod_note_line($notes[(int)$r['comment_id']] ?? null)) echo "<p class=\"meta\">" . h($nl) . "</p>";
    echo "<div class=\"repacts\"><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"rep_borrar\"><input type=\"hidden\" name=\"cid\" value=\"{$r['comment_id']}\"><button class=\"btn danger\" title=\"sustituye el texto por el aviso\">Borrar comentario</button></form>";
    echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"rep_descartar\"><input type=\"hidden\" name=\"cid\" value=\"{$r['comment_id']}\"><button class=\"btn\" title=\"el comentario se queda; los reportes se cierran\">Descartar reporte</button></form>";
    echo "<a class=\"btn\" href=\"/editar-comentario?id={$r['comment_id']}\">editar</a></div></div>";
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
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . h(chat_who($r)) . "</b> <a href=\"/chat?m={$r['msg_id']}#m{$r['msg_id']}\">#{$r['msg_id']}</a>";
    echo "<span class=\"meta\"> · " . h(hace($r['created'])) . " · <b>{$r['n']}</b> " . ($r['n'] == 1 ? 'reporte' : 'reportes') . " · " . h($motivos) . "</span></div>";
    echo "<blockquote class=\"quote\">" . saltos(h(mb_strimwidth($r['content'], 0, 600, '…'))) . "</blockquote>";
    if ($nl = mod_note_line($cnotes[(int)$r['msg_id']] ?? null)) echo "<p class=\"meta\">" . h($nl) . "</p>";
    echo "<div class=\"repacts\"><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"chat_borrar\"><input type=\"hidden\" name=\"mid\" value=\"{$r['msg_id']}\"><button class=\"btn danger\" title=\"sustituye el texto por el aviso\">Borrar mensaje</button></form>";
    echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"chat_descartar\"><input type=\"hidden\" name=\"mid\" value=\"{$r['msg_id']}\"><button class=\"btn\" title=\"el mensaje se queda; los reportes se cierran\">Descartar reporte</button></form></div></div>";
}
} elseif ($tab === 'fuentes') {
/* ---- fuentes propuestas ---- */
try { $props = $pdo->query("SELECT p.*, u.login, u.aura FROM source_proposals p JOIN users u ON u.user_id = p.user_id WHERE p.status = 'pending' ORDER BY p.created LIMIT 50")->fetchAll(); } catch (Throwable $e) { $props = []; }
echo "<h3 id=\"fuentes\">Fuentes propuestas (" . count($props) . ")</h3>";
echo "<p><small>Medios, substacks y canales propuestos (aura " . h((string)cfg('source_propose_aura', 6)) . " o más) o añadidos por su cuenta desde «Mis medios» (aura " . h((string)cfg('source_add_aura', 5)) . " o más): estos últimos ya funcionan para quien los añadió, pero no salen en el catálogo público hasta que los apruebes. Al proponerlos se prueba el RSS; compruébalo (títulos recientes, idioma, que sea el medio que dice) y apruébalo o recházalo. Al aprobar entra activo y sin destacar; en los dos casos quien lo propuso recibe un mensaje tuyo.</small></p>";
// alta directa (09/10/2026): un moderador da de alta una fuente sin esperar a que alguien la proponga
echo "<details class=\"editbox\" id=\"alta\"" . ($altaF['name'] !== '' || $altaF['home'] !== '' || post('act') === 'fuente_alta' ? ' open' : '') . "><summary><b>Dar de alta una fuente</b> · basta la web: «Rellenar desde la web» busca el RSS y saca nombre, descripción e idioma</summary>";
echo "<form method=\"post\" action=\"/moderacion?t=fuentes#alta\" class=\"fprop-ok\"><input type=\"hidden\" name=\"act\" value=\"fuente_alta\"><table>";   // vuelve aqui mismo, abierto, por si hay otra que dar de alta (13/10/2026)
echo "<tr><td>nombre</td><td><input type=\"text\" name=\"name\" maxlength=\"48\" value=\"" . h($altaF['name']) . "\" placeholder=\"vacío = tomarlo de la web\"></td></tr>";
echo "<tr><td>tipo</td><td><select name=\"kind\">"; foreach (['medio' => 'medio', 'newsletter' => 'substack/newsletter', 'youtube' => 'canal de YouTube', 'agregador' => 'agregador/foro'] as $ak => $al) echo "<option value=\"$ak\"" . ($altaF['kind'] === $ak ? ' selected' : '') . ">$al</option>"; echo "</select></td></tr>";
echo "<tr><td>web</td><td><input type=\"url\" name=\"home\" required value=\"" . h($altaF['home']) . "\" placeholder=\"https://…\"> <small>en un canal de YouTube, la dirección del canal</small></td></tr>";
echo "<tr><td>RSS</td><td><input type=\"url\" name=\"feed\" value=\"" . h($altaF['feed']) . "\" placeholder=\"vacío = buscarlo en la web\"></td></tr>";
echo "<tr><td>descripción</td><td><input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h($altaF['descr']) . "\"></td></tr>";
echo "<tr><td>idioma</td><td><select name=\"lang\"><option value=\"\">— detectar del RSS —</option>"; foreach (DDD_LANGS as $lc => $ln) echo "<option value=\"$lc\"" . ($altaF['lang'] === $lc ? ' selected' : '') . ">" . h($ln) . " ($lc)</option>"; echo "</select></td></tr>";
echo "<tr><td>leer cada</td><td><input type=\"number\" name=\"interval_min\" min=\"1\" max=\"1440\" value=\"" . h($altaF['interval_min']) . "\" placeholder=\"60\"> minutos (vacío: 60; substack 1440; YouTube 180)</td></tr>";
echo "<tr><td></td><td><button class=\"btn\">Dar de alta</button> <button class=\"btn sm\" name=\"rellenar\" value=\"1\" formnovalidate title=\"con solo la web: busca el RSS y saca nombre, descripción e idioma; luego revisas y das de alta\">Rellenar desde la web</button></td></tr></table></form></details>";
// buscar y editar fuentes del catalogo (09/10/2026): ?fq= por nombre, slug, descripcion, web o RSS, sin mayusculas ni tildes, en todos los tipos
$fq = trim(mb_substr((string)($_GET['fq'] ?? ''), 0, 64));
echo "<details class=\"editbox\"" . ($fq !== '' ? ' open' : '') . "><summary><b>Buscar y editar una fuente</b> · nombre, tipo, web, RSS, descripción, idioma, intervalo y encendida/apagada</summary>";
echo "<form method=\"get\" action=\"/moderacion\" class=\"buscar-u\"><input type=\"hidden\" name=\"t\" value=\"fuentes\"><input type=\"search\" name=\"fq\" value=\"" . h($fq) . "\" maxlength=\"64\" placeholder=\"nombre, slug, descripción, web o RSS\"><button class=\"btn sm\">Buscar</button>" . ($fq !== '' ? " <a href=\"/moderacion?t=fuentes\">cerrar</a>" : '') . "</form>";
if ($fq !== '') {
    $llano = fn(string $t): string => strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u', 'ã' => 'a', 'õ' => 'o', 'ç' => 'c', 'ä' => 'a', 'ö' => 'o', 'ß' => 'ss']);
    $fqn = $llano($fq);
    $hall = array_values(array_filter($pdo->query("SELECT s.*, (SELECT COUNT(*) FROM source_items i WHERE i.source_id = s.source_id) n FROM sources s ORDER BY s.kind, s.name")->fetchAll(),
        fn($f) => str_contains($llano(implode(' ', [$f['name'], $f['slug'], (string)$f['descr'], (string)$f['home'], (string)$f['feed_portada']])), $fqn)));
    echo "<p class=\"meta\">" . count($hall) . " coinciden con «" . h($fq) . "»" . (count($hall) > 30 ? ' (se enseñan 30: afina la búsqueda)' : '') . ".</p>";
    foreach (array_slice($hall, 0, 30) as $f) {
        $sid = (int)$f['source_id'];
        echo "<details class=\"editbox\"><summary><b>" . h($f['name']) . "</b> · " . h($f['kind']) . ($f['status'] === 'off' ? ' · <i>apagada</i>' : '') . ' · ' . (($f['lang'] ?? '') !== '' ? h($f['lang']) : '<i>idioma ?</i>') . " · {$f['n']} noticias" . ($f['last_fetch'] ? ' · leída ' . h(hace($f['last_fetch'])) : ' · sin leer') . ($f['last_error'] ? " · <span style=\"color:var(--neg)\">" . h($f['last_error']) . "</span>" : '') . " · <a href=\"/fuente/" . h($f['slug']) . "\">ver</a></summary>";
        echo "<form method=\"post\" action=\"/moderacion?t=fuentes&amp;fq=" . rawurlencode($fq) . "\" class=\"fprop-ok\"><input type=\"hidden\" name=\"act\" value=\"fuente_editar\"><input type=\"hidden\" name=\"source_id\" value=\"$sid\"><table>";
        echo "<tr><td>nombre</td><td><input type=\"text\" name=\"name\" maxlength=\"48\" required value=\"" . h($f['name']) . "\"> <small>/fuente/" . h($f['slug']) . "</small></td></tr>";
        echo "<tr><td>tipo</td><td><select name=\"kind\">"; foreach (['medio' => 'medio', 'newsletter' => 'substack/newsletter', 'youtube' => 'canal de YouTube', 'agregador' => 'agregador/foro'] as $ak => $al) echo "<option value=\"$ak\"" . ($f['kind'] === $ak ? ' selected' : '') . ">$al</option>"; echo "</select></td></tr>";
        echo "<tr><td>web</td><td><input type=\"url\" name=\"home\" required value=\"" . h($f['home']) . "\"></td></tr>";
        echo "<tr><td>RSS</td><td><input type=\"url\" name=\"feed\" value=\"" . h((string)$f['feed_portada']) . "\"></td></tr>";
        echo "<tr><td>descripción</td><td><input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h((string)$f['descr']) . "\"></td></tr>";
        $fl = (string)($f['lang'] ?? '');
        echo "<tr><td>idioma</td><td><select name=\"lang\"><option value=\"\">— detectar del RSS —</option>"; foreach (DDD_LANGS + ($fl !== '' && !isset(DDD_LANGS[$fl]) ? [$fl => $fl] : []) as $lc => $ln) echo "<option value=\"$lc\"" . ($fl === $lc ? ' selected' : '') . ">" . h($ln) . " ($lc)</option>"; echo "</select></td></tr>";
        echo "<tr><td>leer cada</td><td><input type=\"number\" name=\"interval_min\" min=\"1\" max=\"1440\" value=\"" . (int)$f['interval_min'] . "\"> minutos</td></tr>";
        echo "<tr><td>estado</td><td><select name=\"status\"><option value=\"ok\">activa</option><option value=\"off\"" . ($f['status'] === 'off' ? ' selected' : '') . ">apagada</option></select></td></tr>";
        echo "<tr><td></td><td><button class=\"btn\">Guardar</button> <small>slug, tema, skin, destacada y lector local: admin → Fuentes</small></td></tr></table></form></details>";
    }
}
echo "</details>";
if (!$props) echo "<p>No hay propuestas pendientes.</p>";
else $ptopics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY name")->fetchAll();
foreach ($props as $p) {
    $pid = (int)$p['prop_id']; $ok = (int)$p['test_items'] > 0;
    if (isset($fprop_test[$pid])) $p = array_merge($p, $fprop_test[$pid]);   // lo recién probado manda sobre la fila de la BD
    $pslug = '';
    if ($p['source_id']) { $qs = $pdo->prepare("SELECT slug FROM sources WHERE source_id = ?"); $qs->execute([(int)$p['source_id']]); $pslug = (string)$qs->fetchColumn(); }
    echo "<div class=\"repitem fprop\"><div class=\"rephead\"><b>" . h($p['name']) . "</b> · " . ($p['kind'] === 'newsletter' ? 'substack/newsletter' : ($p['kind'] === 'youtube' ? 'canal de YouTube' : 'medio')) . ($pslug !== '' ? " · <a href=\"/fuente/" . h($pslug) . "\">ya activa, fuera del catálogo</a>" : '');
    echo "<span class=\"meta\"> · propuesta por <a href=\"/u/" . rawurlencode($p['login']) . "\">" . h($p['login']) . "</a> (aura " . h(num_es(round((float)$p['aura'], 1))) . ") · " . h(hace($p['created'])) . "</span></div>";
    echo "<p class=\"meta\">web: <a href=\"" . h($p['home']) . "\" rel=\"nofollow noopener\" target=\"_blank\">" . h($p['home']) . "</a><br>RSS: <a href=\"" . h($p['feed']) . "\" rel=\"nofollow noopener\" target=\"_blank\">" . h($p['feed']) . "</a>" . ($p['descr'] ? "<br>" . h($p['descr']) : '') . "</p>";
    if ($p['note']) echo "<blockquote class=\"quote\">" . saltos(h($p['note'])) . "</blockquote>";
    echo "<pre class=\"ftest " . ($ok ? 'ok' : 'err') . "\">" . ($ok ? "RSS leído: {$p['test_items']} noticias · " : '') . h((string)$p['test_info']) . "</pre>";
    // aprobar: datos editables, como en admin > Fuentes
    echo "<form method=\"post\" class=\"fprop-ok\"><input type=\"hidden\" name=\"act\" value=\"fprop_ok\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><table>";
    echo "<tr><td>nombre</td><td><input type=\"text\" name=\"name\" maxlength=\"48\" required value=\"" . h($p['name']) . "\"></td></tr>";
    $slugv = array_key_exists('slug', $p) ? (string)$p['slug'] : ($pslug !== '' ? $pslug : fprop_slug($pdo, $p['name']));
    echo "<tr><td>slug</td><td><input type=\"text\" name=\"slug\" maxlength=\"24\" required pattern=\"[a-z0-9\\-]{2,24}\" value=\"" . h($slugv) . "\"></td></tr>";
    echo "<tr><td>tipo</td><td><select name=\"kind\"><option value=\"medio\"" . ($p['kind'] === 'medio' ? ' selected' : '') . ">medio</option><option value=\"newsletter\"" . ($p['kind'] === 'newsletter' ? ' selected' : '') . ">substack/newsletter</option><option value=\"youtube\"" . ($p['kind'] === 'youtube' ? ' selected' : '') . ">canal de YouTube</option><option value=\"agregador\"" . ($p['kind'] === 'agregador' ? ' selected' : '') . ">agregador/foro</option></select></td></tr>";
    echo "<tr><td>web</td><td><input type=\"url\" name=\"home\" required value=\"" . h($p['home']) . "\"></td></tr>";
    echo "<tr><td>RSS</td><td><input type=\"url\" name=\"feed\" required value=\"" . h($p['feed']) . "\"> <button class=\"btn sm\" name=\"act\" value=\"fprop_probar\" formnovalidate title=\"probar con esta web y este RSS; se guardan los cambios\">probar</button></td></tr>";
    echo "<tr><td>descripción</td><td><input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h((string)$p['descr']) . "\"></td></tr>";
    echo "<tr><td>idioma</td><td><select name=\"lang\"><option value=\"\">— detectar del RSS —</option>";
    foreach (DDD_LANGS as $lc => $ln) echo "<option value=\"$lc\"" . ($p['lang'] === $lc ? ' selected' : '') . ">" . h($ln) . " ($lc)</option>";
    $topicv = (int)($p['topic_id'] ?? 0);
    echo "</select></td></tr><tr><td>tema al enviar</td><td><select name=\"topic_id\"><option value=\"0\"" . ($topicv === 0 ? ' selected' : '') . ">— el que elija quien envía —</option>";
    foreach ($ptopics as $tp) echo "<option value=\"{$tp['topic_id']}\"" . ($topicv === (int)$tp['topic_id'] ? ' selected' : '') . ">" . h($tp['name']) . "</option>";
    $ivv = (int)($p['interval_min'] ?? 0);
    if ($ivv < 1) $ivv = $p['kind'] === 'newsletter' ? 1440 : ($p['kind'] === 'youtube' ? 180 : 60);
    echo "</select></td></tr><tr><td>leer cada</td><td><input type=\"number\" name=\"interval_min\" min=\"1\" max=\"1440\" value=\"$ivv\"> minutos</td></tr>";
    echo "<tr><td></td><td><button class=\"btn\">Aprobar y añadir al catálogo</button></td></tr></table></form>";
    echo "<form method=\"post\" class=\"repacts\"><input type=\"hidden\" name=\"act\" value=\"fprop_no\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"text\" name=\"reply\" maxlength=\"300\" placeholder=\"motivo (se le envía)\" style=\"flex:1;min-width:180px\"> " . ($pslug !== '' ? "<label><input type=\"checkbox\" name=\"retirar\" value=\"1\"> retirarla también de sus medios</label> " : '') . "<button class=\"btn danger\">" . ($pslug !== '' ? 'No al catálogo' : 'Rechazar') . "</button></form></div>";
}
} elseif ($tab === 'errores') {
/* ---- fuentes que dan error (11/10/2026): las que fallaron en su ultima lectura, con el error, y el historial (source_errors, migra-137) ---- */
$fe = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM source_items i WHERE i.source_id = s.source_id) n, (SELECT MAX(pub_date) FROM source_items i WHERE i.source_id = s.source_id) ult
                     FROM sources s WHERE s.last_error IS NOT NULL AND s.last_error <> '' ORDER BY (s.status = 'ok') DESC, s.last_fetch DESC")->fetchAll();
echo "<h3>Fuentes que dan error (" . count(array_filter($fe, fn($f) => $f['status'] === 'ok')) . " activas)</h3>";
echo "<p><small>Fuentes cuya última lectura falló (RSS caído, 403, HTML que ya no se entiende, error de base de datos…). El error desaparece de aquí en cuanto una lectura sale bien. <b>Apagar</b> la deja fuera del catálogo y del lector hasta que alguien la encienda; <b>releer</b> la intenta ahora mismo. Si el error es 403 o certificado, suele arreglarse con «lector local» (ficha de la fuente en admin → Fuentes). Las que llevan muchos días sin publicar nada probablemente han cerrado.</small></p>";
if (!$fe) echo "<p>Ninguna fuente con error. 🙂</p>";
foreach ($fe as $f) {
    $sid = (int)$f['source_id']; $off = $f['status'] === 'off';
    echo "<div class=\"repitem\"><div class=\"rephead\"><b>" . h($f['name']) . "</b> · " . h($f['kind']) . ($off ? ' · <i>apagada</i>' : '') . (!empty($f['relay']) ? ' · lector local' : '') . " · <a href=\"/fuente/" . h($f['slug']) . "\">ver</a> · <a href=\"" . h($f['home']) . "\" rel=\"noopener nofollow\" target=\"_blank\">web</a>"
       . "<span class=\"meta\"> · {$f['n']} noticias" . ($f['ult'] ? ', la última ' . h(hace($f['ult'])) : ', ninguna') . " · intentado " . ($f['last_fetch'] ? h(hace($f['last_fetch'])) : 'nunca') . "</span></div>";
    echo "<pre class=\"ftest err\">" . h((string)$f['last_error']) . "</pre>";
    echo "<div class=\"repacts\">";
    if (!$off) echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"fuente_apagar\"><input type=\"hidden\" name=\"source_id\" value=\"$sid\"><button class=\"btn danger\" title=\"deja de leerse y de salir en el catálogo\">Apagar</button></form>";
    else echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"fuente_encender\"><input type=\"hidden\" name=\"source_id\" value=\"$sid\"><button class=\"btn\">Encender</button></form>";
    echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"fuente_releer\"><input type=\"hidden\" name=\"source_id\" value=\"$sid\"><button class=\"btn\" title=\"intentar leerla ahora\">Releer</button></form>";
    echo "<a class=\"btn\" href=\"/moderacion?t=fuentes&amp;fq=" . rawurlencode($f['slug']) . "\">editar</a></div></div>";
}
try {
    $hist = $pdo->query("SELECT e.*, s.name, s.slug, s.status FROM source_errors e JOIN sources s ON s.source_id = e.source_id ORDER BY e.last_seen DESC LIMIT 100")->fetchAll();
    echo "<h3>Historial (" . count($hist) . " últimos)</h3><p><small>Cada error distinto de cada fuente, con cuántas veces se ha repetido y cuándo fue la primera y la última. Sirve para ver si una fuente falla a ratos o lleva días caída.</small></p>";
    if (!$hist) echo "<p>Sin errores registrados todavía.</p>";
    else {
        echo "<table class=\"modlog\"><tr><th>fuente</th><th>error</th><th>veces</th><th>primera</th><th>última</th></tr>";
        foreach ($hist as $e) echo "<tr><td><a href=\"/fuente/" . h($e['slug']) . "\">" . h($e['name']) . "</a>" . ($e['status'] === 'off' ? ' <i>(apagada)</i>' : '') . "</td><td><small>" . h($e['error']) . "</small></td><td class=\"num\">{$e['times']}</td><td style=\"white-space:nowrap\">" . h(hace($e['first_seen'])) . "</td><td style=\"white-space:nowrap\">" . h(hace($e['last_seen'])) . "</td></tr>";
        echo "</table>";
    }
} catch (Throwable $e) { echo "<p class=\"error\">Falta la tabla source_errors (migra-137).</p>"; }
} elseif ($tab === 'votos') {
/* ---- voto coordinado ---- */
try { $vc = votos_coordinados($pdo); } catch (Throwable $e) { $vc = ['pares' => [], 'conexion' => []]; }   // sin la migracion 080
echo "<h3 id=\"votos\">Cuentas que votan juntas (" . count($vc['pares']) . " parejas · " . count($vc['conexion']) . " envíos con varias cuentas desde una conexión)</h3>";
echo "<p><small>Última semana. Parejas de cuentas que coinciden en cuatro o más envíos, en el mismo sentido y con menos de 10 minutos de diferencia; y envíos votados por más de una cuenta desde la misma conexión (en el δ esos votos ya cuentan como uno). El δ no resta nada por esto: solo se enseña. Dos amigos que leen lo mismo también salen aquí; mirad antes de actuar.</small></p>";
if (!$vc['pares']) echo "<p>Ninguna pareja llamativa.</p>";
else {
    echo "<table><tr><th>cuentas</th><th>coincidencias</th><th>sentido</th><th>votos de cada una (7 d)</th><th>última</th></tr>";
    foreach ($vc['pares'] as $r) echo "<tr><td><a href=\"/u/" . rawurlencode($r['la']) . "\">" . h($r['la']) . "</a> · <a href=\"/u/" . rawurlencode($r['lb']) . "\">" . h($r['lb']) . "</a></td><td class=\"num\"><b>{$r['n']}</b></td><td>{$r['pos']} ↑ · " . ($r['n'] - $r['pos']) . " ↓</td><td>{$r['na']} · {$r['nb']}</td><td>" . h(hace($r['hasta'])) . "</td></tr>";
    echo "</table>";
}
if ($vc['conexion']) {
    echo "<table><tr><th>envío</th><th>cuentas desde la misma conexión</th><th>último voto</th></tr>";
    foreach ($vc['conexion'] as $r) echo "<tr><td><a href=\"/hilo/{$r['story_id']}\">" . h(mb_strimwidth($r['title'], 0, 60, '…')) . "</a></td><td><b>{$r['cuentas']}</b>: " . h($r['logins']) . "</td><td>" . h(hace($r['ultimo'])) . "</td></tr>";
    echo "</table>";
}

} elseif ($tab === 'temas') {
$topics = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id) n FROM topics t ORDER BY t.name")->fetchAll();
echo "<h3 id=\"temas\">Temas (" . count($topics) . ")</h3>";
echo "<table><tr><th>tema</th><th>envíos</th><th>color</th><th></th><th>borrar (moviendo sus envíos a…)</th></tr>";
foreach ($topics as $t) {
    echo "<tr><td><form method=\"post\" id=\"te{$t['topic_id']}\" style=\"display:flex;gap:6px;align-items:center\"><input type=\"hidden\" name=\"act\" value=\"tema_editar\"><input type=\"hidden\" name=\"topic_id\" value=\"{$t['topic_id']}\">";
    echo "<input type=\"text\" name=\"name\" value=\"" . h($t['name']) . "\" maxlength=\"32\" required style=\"width:150px\"></form></td>";
    echo "<td class=\"num\"><a href=\"/tema/{$t['topic_id']}\">{$t['n']}</a></td>";
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
} elseif ($tab === 'dominios') {
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
echo "<form method=\"post\" action=\"/moderacion?t=dominios#probado\" style=\"display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:8px 0\"><input type=\"hidden\" name=\"act\" value=\"dominio_probar\"><input type=\"text\" name=\"domain\" placeholder=\"probar un dominio: iol.ie\" required style=\"flex:1;min-width:140px\"> <button class=\"btn\" title=\"ver qué contesta su web desde el servidor, sin tocar las listas\">Probar</button></form>";
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
} elseif ($tab === 'medios') {
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
}
echo "</div>";
page_footer();
