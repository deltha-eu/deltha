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
        $st = $pdo->prepare("UPDATE topics SET coef = ? WHERE topic_id = ?");
        foreach (($_POST['coef'] ?? []) as $id => $c) $st->execute([(float)$c, (int)$id]);
        $out[] = 'Temas guardados.';
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
    }
    // recargar config tras cambios
    $CFG = [];
    foreach ($pdo->query("SELECT ckey, cvalue FROM config") as $r) $CFG[$r['ckey']] = $r['cvalue'];
}

page_header('Admin');
echo "<div class=\"admin\"><h1>Admin</h1>";
if ($out) echo "<pre>" . h(implode("\n", $out)) . "</pre>";

$counts = $pdo->query("SELECT status, COUNT(*) n FROM stories GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
echo "<p>"; foreach ($counts as $k => $n) echo h($k) . ": $n &nbsp; "; echo "</p>";

echo "<form method=\"post\" style=\"display:inline\"><input type=\"hidden\" name=\"act\" value=\"ciclo\"><button class=\"btn\">Ejecutar ciclo ahora</button></form> ";
echo "<form method=\"post\" style=\"display:inline\"><input type=\"hidden\" name=\"act\" value=\"reloj\">Adelantar reloj <input type=\"text\" name=\"min\" value=\"30\" size=\"5\" style=\"width:60px\"> min <button class=\"btn\">Ir</button></form>";

echo "<h3 style=\"margin-top:20px\">Config</h3><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"config\"><table>";
foreach ($pdo->query("SELECT * FROM config ORDER BY ckey") as $c) {
    echo "<tr><td><code>" . h($c['ckey']) . "</code><br><small>" . h($c['descr']) . "</small></td><td style=\"width:45%\"><input type=\"text\" name=\"cfg[" . h($c['ckey']) . "]\" value=\"" . h($c['cvalue']) . "\"></td></tr>";
}
echo "</table><p><button class=\"btn\">Guardar config</button></p></form>";

echo "<h3>Temas</h3><form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"topics\"><table><tr><th>tema</th><th>coef</th></tr>";
foreach ($pdo->query("SELECT * FROM topics ORDER BY topic_id") as $t) {
    echo "<tr><td>" . h($t['name']) . "</td><td><input type=\"text\" name=\"coef[{$t['topic_id']}]\" value=\"" . h($t['coef']) . "\"></td></tr>";
}
echo "</table><p><button class=\"btn\">Guardar temas</button> · <a href=\"/moderacion.php#temas\">añadir tema</a></p></form>";

$pg = page_get($pdo, 'privacidad');
echo "<h3>Página de privacidad</h3><p><small>HTML. Cada apartado es un <code>&lt;h2&gt;</code> seguido de sus <code>&lt;p&gt;</code>. Los marcadores <code>{site}</code>, <code>{ttl}</code> y <code>{gm}</code> se sustituyen por el nombre del sitio, las horas de vida de un hilo y los minutos del velo. Se muestra en <a href=\"/privacidad.php\">/privacidad.php</a>" . ($pg['updated'] ? " · última edición " . h($pg['updated']) : "") . ".</small></p>";
echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"pagina\"><input type=\"hidden\" name=\"slug\" value=\"privacidad\">";
echo "<p><input type=\"text\" name=\"title\" value=\"" . h($pg['title']) . "\" placeholder=\"Título\"></p>";
echo "<textarea name=\"body\" rows=\"24\">" . h($pg['body']) . "</textarea>";
echo "<p><button class=\"btn\">Guardar página</button></p></form>";

$nq = (int)$pdo->query("SELECT COUNT(*) FROM mod_queue WHERE status IN ('needs_human','queued','processing')")->fetchColumn();
echo "<h3>Cola de moderación</h3><p>$nq pendientes · <a href=\"/moderacion.php\">ir a moderación</a> (la ven también los moderadores)</p>";

$q = trim((string)($_GET['q'] ?? ''));
$up = max(1, (int)($_GET['up'] ?? 1)); $per = 50;
$w = $q !== '' ? "WHERE login LIKE ? OR email LIKE ?" : "";
$args = $q !== '' ? ["%$q%", "%$q%"] : [];
$c = $pdo->prepare("SELECT COUNT(*) FROM users $w"); $c->execute($args); $nU = (int)$c->fetchColumn();
$l = $pdo->prepare("SELECT user_id, login, email, level, aura, created, verified FROM users $w ORDER BY created DESC, user_id DESC LIMIT $per OFFSET " . (($up - 1) * $per));
$l->execute($args);
echo "<h3 id=\"usuarios\">Usuarios ($nU)</h3>";
echo "<form method=\"get\" action=\"/admin.php#usuarios\" style=\"display:flex;gap:6px;margin-bottom:8px\"><input type=\"text\" name=\"q\" value=\"" . h($q) . "\" placeholder=\"usuario o correo\" style=\"flex:1\"><button class=\"btn\">Buscar</button>" . ($q !== '' ? " <a class=\"btn\" href=\"/admin.php#usuarios\">todos</a>" : "") . "</form>";
$rows = $l->fetchAll();
echo "<form method=\"post\"><input type=\"hidden\" name=\"act\" value=\"mods\"><input type=\"hidden\" name=\"ids\" value=\"" . implode(',', array_column($rows, 'user_id')) . "\">";
echo "<table><tr><th>#</th><th>usuario</th><th>correo</th><th>aura</th><th>registro</th><th>moderador</th></tr>";
foreach ($rows as $r) {
    $cell = $r['level'] === 'admin' ? 'admin' : "<input type=\"checkbox\" name=\"mod[{$r['user_id']}]\" value=\"1\"" . ($r['level'] === 'mod' ? ' checked' : '') . ">";
    echo "<tr><td>{$r['user_id']}</td><td><a href=\"/u.php?u=" . rawurlencode($r['login']) . "\">" . h($r['login']) . "</a></td><td>" . h($r['email']) . ((int)$r['verified'] === 0 ? " <small>(sin activar)</small>" : "") . "</td><td>" . h($r['aura']) . "</td><td>" . h($r['created']) . "</td><td style=\"text-align:center\">$cell</td></tr>";
}
echo "</table><p><button class=\"btn\">Guardar moderadores</button></p></form>";
$pages = max(1, (int)ceil($nU / $per));
if ($pages > 1) {
    $qs = $q !== '' ? "&q=" . rawurlencode($q) : "";
    echo "<p>";
    if ($up > 1) echo "<a href=\"/admin.php?up=" . ($up - 1) . "$qs#usuarios\">← anterior</a> ";
    echo "$up / $pages";
    if ($up < $pages) echo " <a href=\"/admin.php?up=" . ($up + 1) . "$qs#usuarios\">siguiente →</a>";
    echo "</p>";
}

echo "<h3>Últimas promociones</h3><table>";
foreach ($pdo->query("SELECT p.*, s.title FROM promo_log p JOIN stories s ON s.story_id = p.story_id ORDER BY p.id DESC LIMIT 15") as $p) {
    echo "<tr><td>" . h($p['created']) . "</td><td>" . h(mb_strimwidth($p['title'], 0, 60, '…')) . "</td><td>δ " . h($p['delta']) . "</td><td>{$p['candidates']} cand · {$p['commenters']} hablando</td></tr>";
}
echo "</table></div>";
page_footer();
