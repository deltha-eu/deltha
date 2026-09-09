<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/ciclo.php';
if (!is_admin()) { http_response_code(403); exit('Solo admin'); }

$out = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = post('act');
    if ($act === 'config') {
        $st = $pdo->prepare("UPDATE config SET cvalue = ? WHERE ckey = ?");
        foreach (($_POST['cfg'] ?? []) as $k => $v) $st->execute([trim((string)$v), (string)$k]);
        $out[] = 'Config guardada.';
    } elseif ($act === 'pagina') {
        $pdo->prepare("INSERT INTO pages (slug, title, body) VALUES (?,?,?) ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body)")
            ->execute([post('slug', 'privacidad'), post('title', 'Privacidad y contacto'), (string)($_POST['body'] ?? '')]);
        $out[] = 'Página guardada.';
    } elseif ($act === 'topics') {
        $st = $pdo->prepare("UPDATE topics SET coef = ?, color = ? WHERE topic_id = ?");
        foreach (($_POST['coef'] ?? []) as $id => $c) {
            $col = (string)($_POST['color'][$id] ?? '');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $col)) $col = '#534AB7';
            $st->execute([(float)$c, $col, (int)$id]);
        }
        $out[] = 'Temas guardados.';
    } elseif ($act === 'tema_nuevo') {
        $name = mb_strtolower(trim(post('name')));
        $coef = (float)(post('coef') ?: 1);
        $col  = post('color') ?: '#534AB7';
        if (!preg_match('/^[\p{L}\p{N} \-]{2,32}$/u', $name)) $out[] = 'Nombre de tema no válido (2–32 letras, números, espacio o guion).';
        elseif ($coef < 0.1 || $coef > 5) $out[] = 'El coeficiente debe estar entre 0,10 y 5,00.';
        elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $col)) $out[] = 'Color no válido (formato #rrggbb).';
        else {
            try { $pdo->prepare("INSERT INTO topics (name, coef, color) VALUES (?,?,?)")->execute([$name, $coef, $col]); $out[] = "Tema «" . h($name) . "» creado."; }
            catch (PDOException $e) { $out[] = 'Ese tema ya existe.'; }
        }
    } elseif ($act === 'ciclo') {
        $out = run_cycle($pdo);
        if (!$out) $out[] = 'Ciclo sin cambios.';
    } elseif ($act === 'reloj') {
        $m = max(1, (int)post('min'));
        $pdo->prepare("UPDATE stories SET sent_date = sent_date - INTERVAL ? MINUTE,
                        published_date = IF(published_date IS NULL, NULL, published_date - INTERVAL ? MINUTE)")->execute([$m, $m]);
        $pdo->prepare("UPDATE comments SET created = created - INTERVAL ? MINUTE")->execute([$m]);
        $out[] = "Reloj adelantado $m min (todas las fechas retrasadas).";
    } elseif ($act === 'mods') {
        $ids = array_map('intval', explode(',', post('ids')));
        $on  = array_map('intval', array_keys($_POST['mod'] ?? []));
        $up1 = $pdo->prepare("UPDATE users SET level = 'mod' WHERE user_id = ? AND level = 'normal'");
        $dn  = $pdo->prepare("UPDATE users SET level = 'normal' WHERE user_id = ? AND level = 'mod'");
        foreach ($ids as $id) { if (in_array($id, $on, true)) $up1->execute([$id]); else $dn->execute([$id]); }
        $out[] = 'Moderadores guardados.';
    } elseif ($act === 'humano') {
        $item = (int)post('item'); $dec = post('dec');
        $q = $pdo->prepare("SELECT item_type, ref_id FROM mod_queue WHERE item_id = ?"); $q->execute([$item]);
        if ($it = $q->fetch()) {
            if ($it['item_type'] === 'story') {
                $pdo->prepare("UPDATE stories SET status = ? WHERE story_id = ?")->execute([$dec === 'ok' ? 'nuevas' : 'rejected', $it['ref_id']]);
            } else {
                $pdo->prepare("UPDATE comments SET status = ? WHERE comment_id = ?")->execute([$dec === 'ok' ? 'ok' : 'rejected', $it['ref_id']]);
                if ($dec === 'ok') $pdo->prepare("UPDATE stories s JOIN comments c ON c.story_id = s.story_id SET s.comments = s.comments + 1 WHERE c.comment_id = ?")->execute([$it['ref_id']]);
            }
            $pdo->prepare("UPDATE mod_queue SET status = ?, processed = NOW() WHERE item_id = ?")->execute([$dec === 'ok' ? 'ok' : 'reject', $item]);
            $out[] = "Item $item: $dec.";
        }
    } elseif ($act === 'verificar') {
        $id = (int)post('uid');
        $pdo->prepare("UPDATE users SET verified = 1, token = NULL, token_sent = NULL WHERE user_id = ?")->execute([$id]);
        $out[] = "Usuario $id: correo validado a mano.";
    } elseif ($act === 'borrar') {
        $id = (int)post('uid');
        $q = $pdo->prepare("SELECT user_id, login, level FROM users WHERE user_id = ?"); $q->execute([$id]);
        $victima = $q->fetch();
        if (!$victima) $out[] = "Usuario $id no existe.";
        elseif ($victima['level'] === 'admin') $out[] = "No se borra a un admin (quítale el nivel antes).";
        elseif (post('confirmar') !== $victima['login']) {
            $pedir_confirmacion = $victima;   // se pinta abajo
        } else {
            $out = array_merge($out, borrar_usuario($pdo, $id));
        }
    }
    // recargar config tras cambios
    $CFG = [];
    foreach ($pdo->query("SELECT ckey, cvalue FROM config") as $r) $CFG[$r['ckey']] = $r['cvalue'];
}

/**
 * Borra una cuenta con todo lo suyo, en cascada y de verdad (no "discard"):
 *  - sus envíos, con TODOS los comentarios, votos, alias, cola de moderación, log de promociones y foto de cada uno
 *    (también los comentarios y votos de otros usuarios en esos hilos: el hilo desaparece entero)
 *  - sus comentarios en hilos ajenos, con las respuestas que cuelgan de ellos (aunque sean de otros)
 *  - sus votos (ajustando los contadores de la noticia), alias por hilo, log de aura, avatar
 */
function borrar_usuario(PDO $pdo, int $uid): array {
    $log = []; $ficheros = [];
    $pdo->beginTransaction();
    try {
        $u = $pdo->prepare("SELECT login, avatar FROM users WHERE user_id = ? FOR UPDATE"); $u->execute([$uid]); $u = $u->fetch();
        if (!$u) throw new RuntimeException("no existe");
        if ($u['avatar']) $ficheros[] = DDD_MEDIA_DIR . '/avatars/' . $u['avatar'];

        // --- 1. hilos que envió: se llevan por delante todo lo que cuelga de ellos
        $st = $pdo->prepare("SELECT story_id, image FROM stories WHERE user_id = ?"); $st->execute([$uid]);
        $hilos = $st->fetchAll();
        $ncom_ajenos = 0;
        foreach ($hilos as $h) {
            $sid = (int)$h['story_id'];
            if ($h['image']) $ficheros[] = DDD_MEDIA_DIR . '/' . $h['image'];
            $c = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE story_id = ? AND (user_id IS NULL OR user_id <> ?)"); $c->execute([$sid, $uid]);
            $ncom_ajenos += (int)$c->fetchColumn();
            $pdo->prepare("DELETE FROM mod_queue WHERE item_type = 'comment' AND ref_id IN (SELECT comment_id FROM comments WHERE story_id = ?)")->execute([$sid]);
            $pdo->prepare("DELETE FROM mod_queue WHERE item_type = 'story' AND ref_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM comments WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM votes WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM thread_aliases WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM promo_log WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM stories WHERE story_id = ?")->execute([$sid]);
        }
        $log[] = count($hilos) . " envíos borrados (con $ncom_ajenos comentarios de otros que había en ellos)";

        // --- 2. sus comentarios en hilos ajenos + las respuestas que cuelgan de ellos
        $c = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ?"); $c->execute([$uid]);
        $ids = array_map('intval', $c->fetchAll(PDO::FETCH_COLUMN));
        $todos = $ids; $frontera = $ids;
        while ($frontera) {                       // descendientes por parent_id, nivel a nivel
            $in = implode(',', $frontera);
            $hijos = array_map('intval', $pdo->query("SELECT comment_id FROM comments WHERE parent_id IN ($in)")->fetchAll(PDO::FETCH_COLUMN));
            $hijos = array_values(array_diff($hijos, $todos));
            $todos = array_merge($todos, $hijos); $frontera = $hijos;
        }
        if ($todos) {
            $in = implode(',', $todos);
            // contadores de comentarios de cada hilo afectado (solo los que estaban en 'ok' cuentan)
            foreach ($pdo->query("SELECT story_id, COUNT(*) n FROM comments WHERE comment_id IN ($in) AND status = 'ok' GROUP BY story_id") as $r)
                $pdo->prepare("UPDATE stories SET comments = GREATEST(0, comments - ?) WHERE story_id = ?")->execute([(int)$r['n'], (int)$r['story_id']]);
            $pdo->exec("DELETE FROM mod_queue WHERE item_type = 'comment' AND ref_id IN ($in)");
            $pdo->exec("DELETE FROM comments WHERE comment_id IN ($in)");
        }
        $log[] = count($ids) . " comentarios suyos borrados (+" . (count($todos) - count($ids)) . " respuestas de otros que colgaban de ellos)";

        // --- 3. votos: se retiran y se corrigen los contadores
        $v = $pdo->prepare("SELECT story_id, value FROM votes WHERE user_id = ?"); $v->execute([$uid]);
        $nv = 0;
        foreach ($v->fetchAll() as $r) {
            $col = (int)$r['value'] > 0 ? 'votes_pos' : 'votes_neg';
            $pdo->prepare("UPDATE stories SET $col = GREATEST(0, $col - 1) WHERE story_id = ?")->execute([(int)$r['story_id']]);
            $nv++;
        }
        $pdo->prepare("DELETE FROM votes WHERE user_id = ?")->execute([$uid]);
        $log[] = "$nv votos retirados";

        // --- 4. resto de rastro y la cuenta
        $pdo->prepare("DELETE FROM thread_aliases WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM aura_log WHERE user_id = ?")->execute([$uid]);
        try { $pdo->prepare("DELETE FROM messages WHERE from_id = ? OR to_id = ?")->execute([$uid, $uid]); } catch (Throwable $e) {}
        $pdo->prepare("UPDATE banned_domains SET added_by = NULL WHERE added_by = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ["ERROR: no se ha borrado nada (" . $e->getMessage() . ")"];
    }
    $nf = 0;
    foreach ($ficheros as $f) if (is_file($f) && @unlink($f)) $nf++;
    $log[] = "cuenta «{$u['login']}» (#$uid) eliminada · $nf ficheros de media borrados";
    return $log;
}

$TABS = ['usuarios' => 'Usuarios', 'ciclo' => 'Ciclo', 'config' => 'Config', 'temas' => 'Temas', 'privacidad' => 'Privacidad'];
$tab = (string)($_GET['t'] ?? $_POST['t'] ?? 'usuarios');
if (!isset($TABS[$tab])) $tab = 'usuarios';
$T = "<input type=\"hidden\" name=\"t\" value=\"$tab\">";   // cada formulario vuelve a su pestaña

page_header('Admin');
echo "<div class=\"admin\">";
echo "<nav class=\"tabs\">";
foreach ($TABS as $k => $v) echo "<a href=\"/admin.php?t=$k\"" . ($k === $tab ? ' class="on"' : '') . ">$v</a>";
echo "</nav>";
if ($out) echo "<pre>" . h(implode("\n", $out)) . "</pre>";
if (!empty($pedir_confirmacion)) {
    $pc = $pedir_confirmacion;
    $ns = $pdo->prepare("SELECT (SELECT COUNT(*) FROM stories WHERE user_id = ?) s, (SELECT COUNT(*) FROM comments WHERE user_id = ?) c, (SELECT COUNT(*) FROM votes WHERE user_id = ?) v");
    $ns->execute([$pc['user_id'], $pc['user_id'], $pc['user_id']]); $ns = $ns->fetch();
    echo "<div class=\"error\" style=\"padding:12px 16px;margin-bottom:16px\"><strong>Borrar la cuenta «" . h($pc['login']) . "» (#{$pc['user_id']})</strong><br>"
       . "Se eliminarán de verdad sus {$ns['s']} envíos (con todos los comentarios y votos de cualquiera que haya en esos hilos), sus {$ns['c']} comentarios (con las respuestas de otros que cuelguen de ellos), sus {$ns['v']} votos, alias, aura y avatar. No hay vuelta atrás.<br>"
       . "<form method=\"post\" style=\"display:flex;gap:6px;margin-top:8px;align-items:center;flex-wrap:wrap\">$T<input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"uid\" value=\"{$pc['user_id']}\">"
       . "Escribe el nombre de usuario para confirmar: <input type=\"text\" name=\"confirmar\" autocomplete=\"off\" style=\"width:160px\"> <button class=\"btn\">Borrar definitivamente</button> <a class=\"btn\" href=\"/admin.php\">cancelar</a></form></div>";
}

if ($tab === 'usuarios') {
    $q = trim((string)($_GET['q'] ?? ''));
    $up = max(1, (int)($_GET['up'] ?? 1)); $per = 50;
    $w = $q !== '' ? "WHERE login LIKE ? OR email LIKE ?" : "";
    $args = $q !== '' ? ["%$q%", "%$q%"] : [];
    $c = $pdo->prepare("SELECT COUNT(*) FROM users $w"); $c->execute($args); $nU = (int)$c->fetchColumn();
    $l = $pdo->prepare("SELECT user_id, login, email, level, aura, created, verified FROM users $w ORDER BY created DESC, user_id DESC LIMIT $per OFFSET " . (($up - 1) * $per));
    $l->execute($args);
    echo "<h3 id=\"usuarios\">Usuarios ($nU)</h3>";
    echo "<form method=\"get\" action=\"/admin.php\" style=\"display:flex;gap:6px;margin-bottom:8px\"><input type=\"hidden\" name=\"t\" value=\"usuarios\"><input type=\"text\" name=\"q\" value=\"" . h($q) . "\" placeholder=\"usuario o correo\" style=\"flex:1\"><button class=\"btn\">Buscar</button>" . ($q !== '' ? " <a class=\"btn\" href=\"/admin.php\">todos</a>" : "") . "</form>";
    $rows = $l->fetchAll();
    echo "<form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"mods\"><input type=\"hidden\" name=\"ids\" value=\"" . implode(',', array_column($rows, 'user_id')) . "\">";
    echo "<table class=\"users\"><tr><th>#</th><th>usuario</th><th>aura</th><th>mod</th><th></th></tr>";
    foreach ($rows as $r) {
        $cell = $r['level'] === 'admin' ? 'admin' : "<input type=\"checkbox\" name=\"mod[{$r['user_id']}]\" value=\"1\"" . ($r['level'] === 'mod' ? ' checked' : '') . ">";
        // los botones usan formularios propios (atributo form=) para no anidarlos en el de moderadores
        $acc = "<a class=\"btn sm\" href=\"/editar-usuario.php?id={$r['user_id']}\" title=\"editar nick, frase, foto y correo\">editar</a> ";
        $acc .= (int)$r['verified'] === 0 ? "<button class=\"btn sm\" form=\"ver{$r['user_id']}\" title=\"marcar el correo como validado\">validar</button> " : "";
        if ($r['level'] !== 'admin') $acc .= "<button class=\"btn sm\" form=\"del{$r['user_id']}\" title=\"borrar la cuenta y todo lo suyo\">borrar</button>";
        $meta = h($r['email']) . ((int)$r['verified'] === 0 ? " (sin activar)" : "") . " · " . h(substr($r['created'], 0, 16));
        echo "<tr><td class=\"num\">{$r['user_id']}</td><td class=\"who\"><a href=\"/u.php?u=" . rawurlencode($r['login']) . "\">" . h($r['login']) . "</a><small>$meta</small></td><td>" . h($r['aura']) . "</td><td style=\"text-align:center\">$cell</td><td class=\"acc\">$acc</td></tr>";
    }
    echo "</table><p><button class=\"btn\">Guardar moderadores</button></p></form>";
    foreach ($rows as $r) {
        echo "<form id=\"ver{$r['user_id']}\" method=\"post\" action=\"/admin.php\">$T<input type=\"hidden\" name=\"act\" value=\"verificar\"><input type=\"hidden\" name=\"uid\" value=\"{$r['user_id']}\"></form>";
        echo "<form id=\"del{$r['user_id']}\" method=\"post\" action=\"/admin.php\">$T<input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"uid\" value=\"{$r['user_id']}\"></form>";
    }
    $pages = max(1, (int)ceil($nU / $per));
    if ($pages > 1) {
        $qs = $q !== '' ? "&q=" . rawurlencode($q) : "";
        echo "<p>";
        if ($up > 1) echo "<a href=\"/admin.php?t=usuarios&up=" . ($up - 1) . "$qs\">← anterior</a> ";
        echo "$up / $pages";
        if ($up < $pages) echo " <a href=\"/admin.php?t=usuarios&up=" . ($up + 1) . "$qs\">siguiente →</a>";
        echo "</p>";
    }

} elseif ($tab === 'ciclo') {
    $counts = $pdo->query("SELECT status, COUNT(*) n FROM stories GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "<h3>Estado</h3><p>"; foreach ($counts as $k => $n) echo h($k) . ": $n &nbsp; "; echo "</p>";
    echo "<p><form method=\"post\" style=\"display:inline\">$T<input type=\"hidden\" name=\"act\" value=\"ciclo\"><button class=\"btn\">Ejecutar ciclo ahora</button></form> ";
    echo "<form method=\"post\" style=\"display:inline\">$T<input type=\"hidden\" name=\"act\" value=\"reloj\">Adelantar reloj <input type=\"text\" name=\"min\" value=\"30\" size=\"5\" style=\"width:60px\"> min <button class=\"btn\">Ir</button></form></p>";
    echo "<h3>Tiempos</h3><form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"config\"><p>";
    echo "Un ciclo cada <input type=\"text\" name=\"cfg[cycle_minutes]\" value=\"" . h(cfg('cycle_minutes', 5)) . "\" style=\"width:60px\"> min &nbsp;·&nbsp; ";
    echo "una nueva puede ser candidata a los <input type=\"text\" name=\"cfg[delta_min_age_min]\" value=\"" . h(cfg('delta_min_age_min', 30)) . "\" style=\"width:60px\"> min &nbsp;·&nbsp; ";
    echo "vida en portada <input type=\"text\" name=\"cfg[thread_ttl_hours]\" value=\"" . h(cfg('thread_ttl_hours', 48)) . "\" style=\"width:60px\"> h ";
    echo "<button class=\"btn\">Guardar</button></p>";
    echo "<p><small>El cron llama al script cada minuto y el ciclo se ejecuta en los minutos múltiplos del intervalo; en cada ciclo sube una nueva a candidatas y se publica la mejor candidata. El cambio surte efecto en el siguiente minuto, sin tocar el servidor.</small></p></form>";
    $nq = (int)$pdo->query("SELECT COUNT(*) FROM mod_queue WHERE status IN ('needs_human','queued','processing')")->fetchColumn();
    echo "<h3>Cola de moderación</h3><p>$nq pendientes · <a href=\"/moderacion.php\">ir a moderación</a> (la ven también los moderadores)</p>";
    echo "<h3>Últimas promociones</h3><table>";
    foreach ($pdo->query("SELECT p.*, s.title FROM promo_log p JOIN stories s ON s.story_id = p.story_id ORDER BY p.id DESC LIMIT 15") as $p) {
        echo "<tr><td>" . h($p['created']) . "</td><td>" . h(mb_strimwidth($p['title'], 0, 60, '…')) . "</td><td>δ " . h($p['delta']) . "</td><td>{$p['candidates']} cand · {$p['commenters']} hablando</td></tr>";
    }
    echo "</table>";

} elseif ($tab === 'config') {
    echo "<h3>Config</h3><form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"config\"><table>";
    foreach ($pdo->query("SELECT * FROM config ORDER BY ckey") as $c) {
        echo "<tr><td><code>" . h($c['ckey']) . "</code><br><small>" . h($c['descr']) . "</small></td><td style=\"width:45%\"><input type=\"text\" name=\"cfg[" . h($c['ckey']) . "]\" value=\"" . h($c['cvalue']) . "\"></td></tr>";
    }
    echo "</table><p><button class=\"btn\">Guardar config</button></p></form>";

} elseif ($tab === 'temas') {
    echo "<h3>Temas</h3><form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"topics\"><table><tr><th>tema</th><th>envíos</th><th style=\"width:90px\">coef</th><th style=\"width:70px\">color</th></tr>";
    foreach ($pdo->query("SELECT t.*, (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id) n FROM topics t ORDER BY t.topic_id") as $t) {
        echo "<tr><td><a href=\"/tema.php?t={$t['topic_id']}\">" . h($t['name']) . "</a></td><td class=\"num\">{$t['n']}</td><td><input type=\"text\" name=\"coef[{$t['topic_id']}]\" value=\"" . h($t['coef']) . "\"></td>";
        echo "<td><input type=\"color\" name=\"color[{$t['topic_id']}]\" value=\"" . h($t['color']) . "\" title=\"color del velo y la etiqueta\"></td></tr>";
    }
    echo "</table><p><button class=\"btn\">Guardar temas</button></p></form>";
    echo "<h3>Nuevo tema</h3><form method=\"post\" style=\"display:flex;gap:8px;align-items:center;flex-wrap:wrap\">$T<input type=\"hidden\" name=\"act\" value=\"tema_nuevo\">";
    echo "<input type=\"text\" name=\"name\" placeholder=\"nombre (minúsculas)\" maxlength=\"32\" required style=\"flex:1;min-width:140px\"> coef <input type=\"text\" name=\"coef\" value=\"1.00\" style=\"width:60px\"> <input type=\"color\" name=\"color\" value=\"#534AB7\"> <button class=\"btn\">Crear tema</button></form>";
    echo "<p><small>El coeficiente multiplica los votos en el δ (1,10 da un 10 % de ventaja al tema). El color es el velo de la foto y la etiqueta. Los temas no se borran: si uno sobra, se le cambia el nombre.</small></p>";

} elseif ($tab === 'privacidad') {
    $pg = page_get($pdo, 'privacidad');
    echo "<h3>Página de privacidad</h3><p><small>HTML. Cada apartado es un <code>&lt;h2&gt;</code> seguido de sus <code>&lt;p&gt;</code>. Los marcadores <code>{site}</code>, <code>{ttl}</code> y <code>{gm}</code> se sustituyen por el nombre del sitio, las horas de vida de un hilo y los minutos del velo. Se muestra en <a href=\"/privacidad.php\">/privacidad.php</a>" . ($pg['updated'] ? " · última edición " . h($pg['updated']) : "") . ".</small></p>";
    echo "<form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"pagina\"><input type=\"hidden\" name=\"slug\" value=\"privacidad\">";
    echo "<p><input type=\"text\" name=\"title\" value=\"" . h($pg['title']) . "\" placeholder=\"Título\"></p>";
    echo "<textarea name=\"body\" rows=\"24\">" . h($pg['body']) . "</textarea>";
    echo "<p><button class=\"btn\">Guardar página</button></p></form>";
}
echo "</div>";
page_footer();
