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
if ($s['content_type'] === 'article' && ($fo = foro_de($s)) && !foro_abierto($fo)) { http_response_code(403); page_header(t('Foros'), 'foros'); echo "<p class=\"end\">" . h(foro_cerrado_txt($fo)) . ' <a href="/foros">' . t('Volver a los foros') . "</a></p>"; page_footer(); exit; }   // foro con aura minima (migra-165)
if ($s['content_type'] === 'article') $GLOBALS['DDD_SIN_GHOST'] = true;   // en los foros no hay velo Ghost: nombres desde el primer minuto (20/10/2026)
if ($s['content_type'] === 'article') { foco_por_defecto(); $fo = foro_de($s); page_crumb($fo ? foro_url($fo) : '/foros', $fo ? (string)$fo['name'] : t('Foros')); }   // cabecera «Originales › Hilo» (el foro del hilo, migra-159): se vuelve a la lista con un clic; un texto propio se abre en modo sin distracciones salvo que el lector lo haya quitado
if (!empty($s['comm_skin'])) community_skin($s['comm_skin']);   // el hilo se ve con la plantilla de su comunidad
$rev = (int)$s['revealed'] === 1 || ghost_over($s['sent_date']);
$author = display_name(['login' => $s['login'], 'alias' => $s['author_alias'], 'user_id' => $s['user_id']], $rev);
$aura = $s['user_id'] ? (float)($s['author_aura'] ?? 5) : (float)cfg('aura_anon', 5);
$open = in_array($s['status'], ['nuevas', 'destacadas'], true);
$can_comment = $open && (user() || cfg('anon_can_comment') === '1');
$tab = $s['content_type'] === 'article' ? 'foros' : (['destacadas' => (string)$s['portada'], 'nuevas' => 'radar'][$s['status']] ?? '');

// my_vote: sol (1) o lagrima (-1) que el usuario actual ya puso a cada comentario (NULL si ninguno o anonimo)
$cs = $pdo->prepare("SELECT c.*, u.login, u.avatar, cv.value AS my_vote FROM comments c LEFT JOIN users u ON u.user_id = c.user_id
                     LEFT JOIN comment_votes cv ON cv.comment_id = c.comment_id AND cv.user_id = ?
                     WHERE c.story_id = ? AND c.status='ok' ORDER BY c.created ASC");
$cs->execute([(int)(user()['user_id'] ?? 0), $id]);
$can_vote = $open && (bool)user();   // sol / lagrima: registrados, hilo abierto (el comentario propio se excluye en comment_block)
$all = []; $byId = [];
foreach ($cs as $c) { $all[] = $c; $byId[$c['comment_id']] = $c; }
avisos_preload_nicks($pdo, array_column($all, 'content'));   // @nick → enlace al perfil en comment_block()
// Dos vistas de los comentarios (subpestañas «Por hilos» / «Por tiempo» bajo la barra del envio):
//  · por hilos (la de siempre, ahora en arbol): cada conversacion = comentario raiz + sus respuestas, cada respuesta justo debajo
//    del comentario al que contesta y sangrada segun su nivel (tope 6), sin rayas;
//  · por tiempo (?v=tiempo): todos seguidos del mas antiguo al mas nuevo, con «en respuesta a X» en los que contestan a otro;
//  · por votos (?v=votos): todos seguidos, el mejor valorado primero (soles menos lagrimas); a igualdad, el mas antiguo primero.
$vista = (string)($_GET['v'] ?? '');
if (!in_array($vista, ['tiempo', 'votos'], true)) $vista = '';
$porTiempo = $vista !== '';   // lista plana (por tiempo o por votos), no arbol
$GLOBALS['DDD_HILO_V'] = $vista;
$threads = []; $nivel = [];
if ($porTiempo) {
    $plana = $all;   // ya viene por fecha
    if ($vista === 'votos') usort($plana, fn($a, $b) => (((int)$b['likes'] - (int)$b['dislikes']) <=> ((int)$a['likes'] - (int)$a['dislikes'])) ?: (strcmp((string)$a['created'], (string)$b['created']) ?: ((int)$a['comment_id'] <=> (int)$b['comment_id'])));
    foreach ($plana as $c) $threads[] = [$c];
} else {
    $grupos = [];
    foreach ($all as $c) { $k = $c['root_id'] ?: $c['comment_id']; $grupos[$k][] = $c; }
    foreach ($grupos as $k => $list) {
        $enGrupo = array_column($list, null, 'comment_id');
        $raiz = isset($enGrupo[$k]) ? (int)$k : (int)$list[0]['comment_id'];   // si la raiz ya no esta visible, abre el primero
        $hijos = [];
        foreach ($list as $c) {
            $cid = (int)$c['comment_id'];
            if ($cid === $raiz) continue;
            $p = (int)$c['parent_id'];
            $hijos[(isset($enGrupo[$p]) && $p !== $cid) ? $p : $raiz][] = $cid;   // padre no visible: cuelga de la raiz
        }
        $orden = []; $pila = [[$raiz, 0]];
        while ($pila) {
            [$cid, $lv] = array_pop($pila);
            $orden[] = $enGrupo[$cid]; $nivel[$cid] = min(6, $lv);
            foreach (array_reverse($hijos[$cid] ?? []) as $h) $pila[] = [$h, $lv + 1];
        }
        $threads[] = $orden;
    }
}
$replyTo = (int)($_GET['r'] ?? 0);
$replyTo = ($replyTo && isset($byId[$replyTo]) && $can_comment) ? $byId[$replyTo] : null;

robots_index(story_indexable($s));   // solo articulos abiertos a buscadores por su autor; lo demas, noindex
// Open Graph del hilo: titular, entradilla y la foto del envio (o el icono del sitio si no hay)
page_header(t('Hilo'), $tab, og_tags($s['title'], $s['content_type'] === 'article' ? md_plano((string)$s['content']) : (string)$s['content'], $s['image'] ? '/media/' . $s['image'] : '', abs_url('/hilo/' . $id)), $s['title']);
echo "<div class=\"hilo " . ($porTiempo ? 'plana' : 'arbol') . "\">";
echo "<article class=\"post main\" id=\"s$id\">" . who_avatar($s, $rev, $aura, $author) . "<div class=\"pc\">";
echo "<div class=\"l1\"><b>" . h($author) . "</b>" . mod_real(['login' => $s['login'], 'user_id' => $s['user_id']], $rev) . "<span class=\"meta\"><span class=\"sep\"> · </span>" . topic_link($s) . community_links($s) . ($s['content_type'] === 'article' ? ' · ' . t('artículo') : ($s['source'] ? ' · ' . source_link($s) : '')) . lang_tag($s) . " · " . h(hace($s['sent_date'])) . (!empty($s['edited_at']) ? " · " . t('editado') : "") . "</span>" . (($el = mod_edit_link($s) . own_edit_link($s)) !== '' ? " · " . $el : '') . "</div>";
story_cuenta($pdo, $s, 'views');   // una lectura por persona y dia (lib/contadores.php)
$dest = $s['url'] ? ir_url($s) : null;   // la salida al medio pasa por /ir, que cuenta el clic
if (foco()) media_block($s, 'media', $s['content_type'] === 'article' ? null : $dest);   // sin distracciones: la foto, antes del titular, flota a su derecha
echo $dest ? "<h2><a href=\"" . h($dest) . "\" rel=\"noopener nofollow\">" . h($s['title']) . "</a></h2>" : "<h2>" . h($s['title']) . "</h2>";
if ($s['content_type'] === 'article') {
    if (!foco()) media_block($s, 'media', null);
    // cada parrafo con su aire (.para): la separacion la da el CSS, no los <br>
    echo md_articulo((string)$s['content']);   // **negrita** *cursiva* > cita [enlace](url), parrafo a parrafo (.para)
} else {
    if ($s['content']) echo "<p class=\"lead\">" . saltos(h($s['content'])) . "</p>";
    if (!foco()) media_block($s, 'media', $dest);
}
if ($s['url']) echo "<a class=\"source\" href=\"" . h($dest) . "\" rel=\"noopener nofollow\">" . icon('link') . h($s['source']) . "</a>";
if ($s['status'] === 'destacadas') echo "<div class=\"life\"><span style=\"width:" . vida_pct($s['published_date']) . "%\"></span></div>";
if ($s['status'] === 'nuevas' && $s['content_type'] !== 'article') echo "<div class=\"delta\">δ " . h(delta_explica($s)) . " · " . h(t('en el radar, hacia %s', t(DDD_PORTADAS[portada_de($pdo, $s)]))) . "</div>";
echo "</div></article>";

echo "<div class=\"stats\" id=\"comentarios\"><span class=\"cmt\" title=\"" . count($all) . " " . t('comentarios') . "\">" . icon('reply') . "<b>" . count($all) . "</b></span>" . story_contadores($s);   // icono + cifra, como en el feed: la palabra partia la barra en dos lineas
if ($s['status'] === 'destacadas') echo "<span>" . h(queda($s['published_date'])) . "</span>";
if ($s['status'] === 'archived')   echo "<span>" . t('archivado') . "</span>";
echo "<span style=\"margin-left:auto\">"; vote_widget($s); echo "</span>";
// «+ Seguir <medio>»: el medio o substack del envio (solo si su dominio es de una fuente del catalogo)
if (user() && $s['content_type'] !== 'article' && $s['source']) { require_once __DIR__ . '/../lib/fuentes.php'; if (fuentes_on() && ($fsrc = fuente_por_dominio($pdo, (string)$s['source']))) echo fuente_fav_form($fsrc, true); }
echo fav_link($s) . share_link($s) . pin_form($pdo, 'story', (int)$s['story_id'], pin_grupo_story($s), pin_en_portada($s)) . "</div>";   // «Destacar» (migra-111)
// Herramientas del envio (05/10/2026): una sola caja plegada bajo la barra, en vez de dos barras con todo seguido. Moderadores y admin:
// tema, comunidad principal y «editar · eliminar»; dueños de comunidad (y moderadores): en que comunidades esta el envio. Cada cosa en su fila.
$u = user();
$rows = [];
if (is_mod()) {
    $f = "<form class=\"inl\" method=\"post\" action=\"/cambiar-tema\"><input type=\"hidden\" name=\"id\" value=\"$id\"><select name=\"topic\" title=\"" . h(t('Tema')) . "\">";
    foreach ($pdo->query("SELECT topic_id, name FROM topics ORDER BY name") as $t) $f .= "<option value=\"{$t['topic_id']}\"" . ((int)$t['topic_id'] === (int)$s['topic_id'] ? ' selected' : '') . ">" . h($t['name']) . "</option>";
    $f .= "</select><select name=\"community\" title=\"" . h(t('Comunidad principal')) . "\"><option value=\"0\">" . t('sin comunidad principal') . "</option>";
    foreach ($pdo->query("SELECT community_id, name FROM communities WHERE status = 'ok' ORDER BY name") as $co) $f .= "<option value=\"{$co['community_id']}\"" . ((int)$co['community_id'] === (int)$s['community_id'] ? ' selected' : '') . ">" . h($co['name']) . "</option>";
    $f .= "</select><button class=\"btn\">" . t('Guardar') . "</button></form>";
    $rows[] = [t('Tema'), $f];
}
// comunidades del envio: cualquier dueño de comunidad puede añadir aqui un envio publico (radar, portada o archivado)
// a la suya y quitarlo; los moderadores arbitran (añaden y quitan en cualquiera). El autor no interviene.
if ($u && in_array($s['status'], ['nuevas', 'destacadas', 'archived'], true)) {
    $inComms = story_communities($pdo, $id);
    $mine = my_communities($pdo, $u);
    $inIds = array_map('intval', array_column($inComms, 'community_id'));
    $addable = array_values(array_filter($mine, fn($c) => !in_array((int)$c['community_id'], $inIds, true)));
    $max = max(1, (int)cfg('comm_max_per_story', 3));
    $canRemoveAny = false;
    foreach ($inComms as $c) if (is_mod() || (int)$c['owner_id'] === (int)$u['user_id']) $canRemoveAny = true;
    if ($canRemoveAny || ($addable && count($inComms) < $max)) {
        $f = '';
        foreach ($inComms as $c) {
            $f .= "<span class=\"chip\" style=\"--cc:" . h($c['color']) . "\"><i class=\"dot\"></i><a href=\"/comunidad/" . rawurlencode($c['slug']) . "\">" . h($c['name']) . "</a>";
            if ($c['main']) $f .= " <small>(" . t('principal') . ")</small>";
            if (is_mod() || (int)$c['owner_id'] === (int)$u['user_id'])
                $f .= "<form class=\"inl\" method=\"post\" action=\"/comunidad-envio\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"community\" value=\"{$c['community_id']}\"><input type=\"hidden\" name=\"act\" value=\"quitar\"><button class=\"x\" title=\"" . t('quitar de esta comunidad') . "\">×</button></form>";
            $f .= "</span>";
        }
        if ($addable && count($inComms) < $max) {
            $f .= "<form class=\"inl\" method=\"post\" action=\"/comunidad-envio\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"añadir\"><select name=\"community\">";
            foreach ($addable as $c) $f .= "<option value=\"{$c['community_id']}\">" . h($c['name']) . "</option>";
            $f .= "</select><button class=\"btn\">" . t('Añadir a la comunidad') . "</button></form>";
        } elseif ($addable) $f .= "<small>" . t('Un envío puede estar como mucho en %d comunidades.', $max) . "</small>";
        $rows[] = [t('Comunidades'), $f];
    }
}
if ($rows) {
    // abierta si se acaba de guardar algo (?comm=), para ver el aviso; si no, plegada
    echo "<details class=\"modtools\"" . (isset($_GET['comm']) ? ' open' : '') . "><summary>" . icon('edit') . "<span>" . (is_mod() ? t('Editar envío') : t('Comunidades')) . "</span>"
       . (is_mod() ? "<a href=\"/editar?id=$id\">" . t('editar · eliminar') . "</a>" : '') . "</summary>";
    foreach ($rows as [$lbl, $html]) echo "<div class=\"row\"><label>" . h($lbl) . "</label><div>$html</div></div>";
    echo "</details>";
}
if (isset($_GET['comm'])) echo "<p class=\"" . ($_GET['comm'] === 'ok' ? 'notice' : 'error') . "\">" . h((string)$_GET['comm'] === 'ok' ? t('Guardado.') : (string)$_GET['comm']) . "</p>";

if (isset($_GET['reportado'])) echo "<p class=\"notice\">" . t('Gracias: el comentario queda avisado a los moderadores.') . "</p>";

if (count($all) > 1) echo "<nav class=\"sub cvista\"><a href=\"/hilo/$id#comentarios\"" . ($porTiempo ? '' : ' class="on"') . ">" . t('Por hilos') . "</a><a href=\"/hilo/$id?v=tiempo#comentarios\"" . ($vista === 'tiempo' ? ' class="on"' : '') . ">" . t('Por tiempo') . "</a><a href=\"/hilo/$id?v=votos#comentarios\"" . ($vista === 'votos' ? ' class="on"' : '') . ">" . t('Por votos') . "</a></nav>";
foreach ($threads as $list) {
    echo "<div class=\"thread\">";
    $n = count($list);
    foreach ($list as $i => $c) {
        $isTarget = $replyTo && (int)$replyTo['comment_id'] === (int)$c['comment_id'];
        $padre = $c['parent_id'] ? ($byId[$c['parent_id']] ?? null) : null;
        // en arbol no hay raya de union ni «en respuesta a» (la sangria ya lo dice); por tiempo, el rotulo sale siempre que responde a otro
        if ($porTiempo) comment_block($c, $isTarget, $padre, $can_comment, $can_vote, 0, true);
        else            comment_block($c, false, null, $can_comment, $can_vote, $nivel[(int)$c['comment_id']] ?? 0);
        if ($isTarget) composer($id, $c, false);
    }
    echo "</div>";
}
// la caja de comentar va al final, debajo de los comentarios (al responder a uno, sale bajo ese comentario)
if ($can_comment && !$replyTo) composer($id);
elseif (!$open) echo "<p class=\"end\">" . t('Hilo cerrado.') . "</p>";
elseif (!$can_comment) echo "<p class=\"end\">" . t('<a href="/login">Entra</a> para comentar.') . "</p>";
echo "</div>";
page_footer();
