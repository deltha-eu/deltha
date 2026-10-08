<?php
/** /baja — el usuario borra su propia cuenta (08/10/2026). POST desde /perfil con su contraseña.
 *
 *  Se borra la cuenta (nick, correo, contraseña, foto, bio, aura), sus votos (corrigiendo contadores), favoritos, fuentes que sigue,
 *  mensajes privados, avisos, aliases, ghosteos y destacados; el nick queda libre. Lo escrito se conserva anonimizado (user_id NULL:
 *  se ve como «anónimo», o con el alias de hilo si lo tenia) para no dejar huecos en las conversaciones de los demas; con la
 *  casilla «borrar también todo lo que he escrito» se borran ademas sus envios (con lo que colgaba de ellos) y sus comentarios
 *  (con las respuestas), como hace el borrado de admin. Las comunidades que fundo pasan al primer admin. Los administradores no
 *  pueden darse de baja (que otro admin les quite el nivel antes): asi nunca se queda el sitio sin admin. */
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u) redirect('/login');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/perfil#baja');
$uid = (int)$u['user_id'];

$st = $pdo->prepare("SELECT pass, level, avatar FROM users WHERE user_id = ?"); $st->execute([$uid]); $row = $st->fetch();
if (!$row || !password_verify(post('pass'), (string)$row['pass'])) redirect('/perfil?baja=pass#baja');
if ($row['level'] === 'admin') redirect('/perfil?baja=admin#baja');
$todo = post('todo') === '1';

$pdo->beginTransaction();
try {
    $q = fn(string $sql, array $p = []) => $pdo->prepare($sql)->execute($p);
    $opt = function (string $sql, array $p = []) use ($pdo) { try { $pdo->prepare($sql)->execute($p); } catch (Throwable $e) {} };   // tablas que puede que no existan

    if ($todo) {
        // 1. sus envios, con todo lo que cuelga de ellos (como admin -> borrar usuario)
        $h = $pdo->prepare("SELECT story_id FROM stories WHERE user_id = ?"); $h->execute([$uid]);
        foreach ($h->fetchAll(PDO::FETCH_COLUMN) as $sid) {
            $sid = (int)$sid;
            $q("DELETE FROM mod_queue WHERE item_type = 'comment' AND ref_id IN (SELECT comment_id FROM comments WHERE story_id = ?)", [$sid]);
            $q("DELETE FROM mod_queue WHERE item_type = 'story' AND ref_id = ?", [$sid]);
            $opt("DELETE FROM comment_reports WHERE comment_id IN (SELECT comment_id FROM comments WHERE story_id = ?)", [$sid]);
            $q("DELETE FROM comments WHERE story_id = ?", [$sid]);
            $q("DELETE FROM votes WHERE story_id = ?", [$sid]);
            $q("DELETE FROM thread_aliases WHERE story_id = ?", [$sid]);
            $q("DELETE FROM promo_log WHERE story_id = ?", [$sid]);
            $opt("DELETE FROM story_communities WHERE story_id = ?", [$sid]);
            $opt("DELETE FROM pins WHERE what = 'story' AND ref_id = ?", [$sid]);
            $opt("DELETE FROM favorites WHERE story_id = ?", [$sid]);
            $q("DELETE FROM stories WHERE story_id = ?", [$sid]);
        }
        // 2. sus comentarios en hilos ajenos + las respuestas que cuelgan de ellos
        $c = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ?"); $c->execute([$uid]);
        $todos = $frontera = array_map('intval', $c->fetchAll(PDO::FETCH_COLUMN));
        while ($frontera) {
            $in = implode(',', $frontera);
            $hijos = array_values(array_diff(array_map('intval', $pdo->query("SELECT comment_id FROM comments WHERE parent_id IN ($in)")->fetchAll(PDO::FETCH_COLUMN)), $todos));
            $todos = array_merge($todos, $hijos); $frontera = $hijos;
        }
        if ($todos) {
            $in = implode(',', $todos);
            foreach ($pdo->query("SELECT story_id, COUNT(*) n FROM comments WHERE comment_id IN ($in) AND status = 'ok' GROUP BY story_id") as $r)
                $q("UPDATE stories SET comments = GREATEST(0, comments - ?) WHERE story_id = ?", [(int)$r['n'], (int)$r['story_id']]);
            $pdo->exec("DELETE FROM mod_queue WHERE item_type = 'comment' AND ref_id IN ($in)");
            try { $pdo->exec("DELETE FROM comment_reports WHERE comment_id IN ($in)"); } catch (Throwable $e) {}
            $pdo->exec("DELETE FROM comments WHERE comment_id IN ($in)");
        }
    } else {
        // lo escrito se queda, sin dueño: se ve como anonimo (o con el alias de hilo que ya tenia)
        $q("UPDATE stories SET user_id = NULL WHERE user_id = ?", [$uid]);
        $q("UPDATE comments SET user_id = NULL WHERE user_id = ?", [$uid]);
    }
    $opt("UPDATE chat SET user_id = NULL, alias = COALESCE(alias, ?) WHERE user_id = ?", [t('anónimo'), $uid]);

    // 3. votos: se retiran y se corrigen los contadores
    $v = $pdo->prepare("SELECT story_id, value FROM votes WHERE user_id = ?"); $v->execute([$uid]);
    foreach ($v->fetchAll() as $r) $q("UPDATE stories SET " . ((int)$r['value'] > 0 ? 'votes_pos' : 'votes_neg') . " = GREATEST(0, " . ((int)$r['value'] > 0 ? 'votes_pos' : 'votes_neg') . " - 1) WHERE story_id = ?", [(int)$r['story_id']]);
    $q("DELETE FROM votes WHERE user_id = ?", [$uid]);

    // 4. el resto de su rastro (lo que tiene ON DELETE CASCADE cae solo: favoritos, fuentes, destacados, avisos, soles y lagrimas)
    $q("DELETE FROM thread_aliases WHERE user_id = ?", [$uid]);
    $q("DELETE FROM aura_log WHERE user_id = ?", [$uid]);
    $opt("DELETE FROM messages WHERE from_id = ? OR to_id = ?", [$uid, $uid]);
    $opt("UPDATE comment_reports SET user_id = NULL WHERE user_id = ?", [$uid]);
    $opt("DELETE FROM user_ghosts WHERE user_id = ? OR target_id = ?", [$uid, $uid]);
    $opt("UPDATE reg_log SET user_id = NULL WHERE user_id = ?", [$uid]);
    $opt("UPDATE story_communities SET added_by = NULL WHERE added_by = ?", [$uid]);
    $q("UPDATE banned_domains SET added_by = NULL WHERE added_by = ?", [$uid]);
    // comunidades que fundo: al primer admin, que decidira que hacer con ellas
    $adm = (int)$pdo->query("SELECT user_id FROM users WHERE level = 'admin' ORDER BY user_id LIMIT 1")->fetchColumn();
    if ($adm) $q("UPDATE communities SET owner_id = ? WHERE owner_id = ?", [$adm, $uid]);
    $q("DELETE FROM users WHERE user_id = ?", [$uid]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('baja: ' . $e->getMessage());
    redirect('/perfil?baja=error#baja');
}
if (!empty($row['avatar'])) @unlink(DDD_MEDIA_DIR . '/avatars/' . $row['avatar']);

// fuera de la sesion
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION = [];
session_destroy();
setcookie(DDD_SESSION, '', ['expires' => time() - 3600, 'path' => '/']);
header('Cache-Control: no-store');
redirect('/?baja=1');
