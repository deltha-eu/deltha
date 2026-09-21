<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare(story_query_base() . " WHERE s.story_id = ?");
$st->execute([$id]);
$s = $st->fetch();
if (!$s || (in_array($s['status'], ['pending_mod', 'rejected', 'discard'], true) && !is_admin())) {
    http_response_code(404);
    page_header(t('No existe'));
    echo "<p class=\"end\">" . t('Ese hilo no existe.') . "</p>";
    page_footer();
    exit;
}
if (!empty($s['comm_skin'])) community_skin($s['comm_skin']);   // el hilo se ve con la plantilla de su comunidad
$rev = (int)$s['revealed'] === 1 || ghost_over($s['sent_date']);
$author = display_name(['login' => $s['login'], 'alias' => $s['author_alias']], $rev);
$aura = $s['user_id'] ? (float)($s['author_aura'] ?? 5) : (float)cfg('aura_anon', 5);
$open = in_array($s['status'], ['nuevas', 'candidatas', 'destacadas'], true);
$can_comment = $open && (user() || cfg('anon_can_comment') === '1');
$tab = ['destacadas' => 'portada', 'candidatas' => 'candidatas', 'nuevas' => 'nuevas'][$s['status']] ?? '';

$cs = $pdo->prepare("SELECT c.*, u.login, u.avatar FROM comments c LEFT JOIN users u ON u.user_id = c.user_id
                     WHERE c.story_id = ? AND c.status='ok' ORDER BY c.created ASC");
$cs->execute([$id]);
$all = []; $byId = [];
foreach ($cs as $c) { $all[] = $c; $byId[$c['comment_id']] = $c; }
// conversaciones: raiz + sus respuestas, en orden cronologico
$threads = [];
foreach ($all as $c) { $k = $c['root_id'] ?: $c['comment_id']; $threads[$k][] = $c; }
$replyTo = (int)($_GET['r'] ?? 0);
$replyTo = ($replyTo && isset($byId[$replyTo]) && $can_comment) ? $byId[$replyTo] : null;

// Open Graph del hilo: titular, entradilla y la foto del envio (o el icono del sitio si no hay)
page_header(t('Hilo'), $tab, og_tags($s['title'], (string)$s['content'], $s['image'] ? '/media/' . $s['image'] : '', abs_url('/hilo.php?id=' . $id)), $s['title']);
echo "<div class=\"hilo\">";
echo "<article class=\"post main\" id=\"s$id\">" . who_avatar($s, $rev, $aura, $author) . "<div class=\"pc\">";
echo "<div class=\"l1\"><b>" . h($author) . "</b><span class=\"meta\"><span class=\"sep\"> · </span>" . topic_link($s) . community_links($s) . ($s['content_type'] === 'article' ? ' · ' . t('artículo') : ($s['source'] ? ' · ' . source_link($s) : '')) . " · " . h(hace($s['sent_date'])) . "</span>" . (is_mod() ? " · " . mod_edit_link($s) : '') . "</div>";
$dest = $s['url'] ?: null;
echo $dest ? "<h2><a href=\"" . h($dest) . "\" rel=\"noopener nofollow\">" . h($s['title']) . "</a></h2>" : "<h2>" . h($s['title']) . "</h2>";
if ($s['content_type'] === 'article') {
    media_block($s, 'media', null);
    // cada parrafo con su aire (.para): la separacion la da el CSS, no los <br>
    foreach (preg_split('/\R{2,}/', trim($s['content'])) as $para) echo "<p class=\"lead para\">" . nl2br(h(trim($para))) . "</p>";
} else {
    if ($s['content']) echo "<p class=\"lead\">" . nl2br(h($s['content'])) . "</p>";
    media_block($s, 'media', $dest);
}
if ($s['url']) echo "<a class=\"source\" href=\"" . h($s['url']) . "\" rel=\"noopener nofollow\">" . icon('link') . h($s['source']) . "</a>";
if ($s['status'] === 'destacadas') echo "<div class=\"life\"><span style=\"width:" . vida_pct($s['published_date']) . "%\"></span></div>";
if ($s['status'] === 'candidatas') echo "<div class=\"delta\">δ " . h(delta_explica($s)) . "</div>";
echo "</div></article>";

echo "<div class=\"stats\" id=\"comentarios\"><span><b>" . count($all) . "</b> " . t('comentarios') . "</span>";
if ($s['status'] === 'destacadas') echo "<span>" . h(queda($s['published_date'])) . "</span>";
if ($s['status'] === 'archived')   echo "<span>" . t('archivado') . "</span>";
echo "<span style=\"margin-left:auto\">"; vote_widget($s); echo "</span>" . fav_link($s) . share_link($s) . "</div>";
if (is_mod()) {   // moderadores y admin: cambiar el tema y la comunidad principal del envio
    echo "<form class=\"modbar\" method=\"post\" action=\"/cambiar-tema.php\"><input type=\"hidden\" name=\"id\" value=\"$id\"><label>Tema</label><select name=\"topic\">";
    foreach ($pdo->query("SELECT topic_id, name FROM topics ORDER BY name") as $t) {
        echo "<option value=\"{$t['topic_id']}\"" . ((int)$t['topic_id'] === (int)$s['topic_id'] ? ' selected' : '') . ">" . h($t['name']) . "</option>";
    }
    echo "</select><label>Comunidad principal</label><select name=\"community\"><option value=\"0\">— ninguna —</option>";
    foreach ($pdo->query("SELECT community_id, name FROM communities WHERE status = 'ok' ORDER BY name") as $co) {
        echo "<option value=\"{$co['community_id']}\"" . ((int)$co['community_id'] === (int)$s['community_id'] ? ' selected' : '') . ">" . h($co['name']) . "</option>";
    }
    echo "</select><button class=\"btn\">Cambiar</button> <a href=\"/editar.php?id=$id\">editar · eliminar</a></form>";
}
// comunidades del envio: cualquier dueño de comunidad puede añadir aqui un envio publico (nuevas, candidatas, portada o archivado)
// a la suya y quitarlo; los moderadores arbitran (añaden y quitan en cualquiera). El autor no interviene.
$u = user();
if ($u && in_array($s['status'], ['nuevas', 'candidatas', 'destacadas', 'archived'], true)) {
    $inComms = story_communities($pdo, $id);
    $mine = my_communities($pdo, $u);
    $inIds = array_map('intval', array_column($inComms, 'community_id'));
    $addable = array_values(array_filter($mine, fn($c) => !in_array((int)$c['community_id'], $inIds, true)));
    $max = max(1, (int)cfg('comm_max_per_story', 3));
    $canRemoveAny = false;
    foreach ($inComms as $c) if (is_mod() || (int)$c['owner_id'] === (int)$u['user_id']) $canRemoveAny = true;
    if ($canRemoveAny || ($addable && count($inComms) < $max)) {
        echo "<div class=\"modbar comms\"><label>" . t('Comunidades') . "</label>";
        foreach ($inComms as $c) {
            echo "<span class=\"chip\" style=\"--cc:" . h($c['color']) . "\"><i class=\"dot\"></i><a href=\"/comunidad.php?c=" . rawurlencode($c['slug']) . "\">" . h($c['name']) . "</a>";
            if ($c['main']) echo " <small>(" . t('principal') . ")</small>";
            if (is_mod() || (int)$c['owner_id'] === (int)$u['user_id'])
                echo "<form class=\"inl\" method=\"post\" action=\"/comunidad-envio.php\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"community\" value=\"{$c['community_id']}\"><input type=\"hidden\" name=\"act\" value=\"quitar\"><button class=\"x\" title=\"" . t('quitar de esta comunidad') . "\">×</button></form>";
            echo "</span>";
        }
        if ($addable && count($inComms) < $max) {
            echo "<form class=\"inl\" method=\"post\" action=\"/comunidad-envio.php\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"añadir\"><select name=\"community\">";
            foreach ($addable as $c) echo "<option value=\"{$c['community_id']}\">" . h($c['name']) . "</option>";
            echo "</select><button class=\"btn\">" . t('Añadir a la comunidad') . "</button></form>";
        } elseif ($addable) echo "<small>" . t('Un envío puede estar como mucho en %d comunidades.', $max) . "</small>";
        echo "</div>";
    }
}
if (isset($_GET['comm'])) echo "<p class=\"" . ($_GET['comm'] === 'ok' ? 'notice' : 'error') . "\">" . h((string)$_GET['comm'] === 'ok' ? t('Guardado.') : (string)$_GET['comm']) . "</p>";

if (isset($_GET['reportado'])) echo "<p class=\"notice\">" . t('Gracias: el comentario queda avisado a los moderadores.') . "</p>";
if ($can_comment && !$replyTo) composer($id);
elseif (!$open) echo "<p class=\"end\">" . t('Hilo cerrado.') . "</p>";
elseif (!$can_comment) echo "<p class=\"end\">" . t('<a href="/login.php">Entra</a> para comentar.') . "</p>";

foreach ($threads as $list) {
    echo "<div class=\"thread\">";
    $n = count($list);
    foreach ($list as $i => $c) {
        $isTarget = $replyTo && (int)$replyTo['comment_id'] === (int)$c['comment_id'];
        $hasNext = $i < $n - 1 || $isTarget;
        comment_block($c, $hasNext, $c['parent_id'] ? ($byId[$c['parent_id']] ?? null) : null, $can_comment);
        if ($isTarget) composer($id, $c, $i < $n - 1);
    }
    echo "</div>";
}
echo "</div>";
page_footer();
