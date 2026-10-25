<?php
/** /editar?id=N — edición de un envío. Moderadores y admin: todo (titular, texto, enlace, tema,
 *  comunidad, foto, eliminar). El autor, en plazo (aura > story_edit_min_aura, story_edit_minutes
 *  desde el envío y mientras no llegue a portada): titular, entradilla/texto, tema y foto — el
 *  enlace no se toca — y retirar el envío. Toda edición que cambie algo marca stories.edited_at. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/imagen.php';
if (!user()) redirect('/login');

$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT * FROM stories WHERE story_id = ?");
$st->execute([$id]);
$s = $st->fetch();
if (!$s) { http_response_code(404); page_header(t('Editar')); echo "<p class=\"end\">" . t('Ese envío no existe.') . "</p>"; page_footer(); exit; }
$own = !is_mod() && can_edit_own_story($s);   // el autor: aura > story_edit_min_aura, en plazo y aun no en portada
$retire = !is_mod() && !$own && can_retire_own_story($s);   // fuera de plazo: solo retirar, mientras siga en el radar y nadie haya comentado (migra-123)
if (!is_mod() && !$own && !$retire) {
    http_response_code(403); page_header(t('Editar'));
    $mine = (int)($s['user_id'] ?? 0) === (int)user()['user_id'];
    echo "<p class=\"end\">" . ($mine
        ? t('Ya no se puede editar: el plazo es de %d minutos desde el envío, hace falta aura mayor que %s y que el envío no haya llegado a portada.', (int)cfg('story_edit_minutes', 15), h(num_es(cfg('story_edit_min_aura', 10))))
        : t('Solo el autor (en los primeros minutos) o los moderadores.')) . " <a href=\"/hilo/$id\">" . t('Volver al hilo') . "</a></p>";
    page_footer(); exit;
}

$topics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY name")->fetchAll();
$comms  = $pdo->query("SELECT community_id, name FROM communities ORDER BY name")->fetchAll();
$isArt = $s['content_type'] === 'article';
$forosSel = $isArt && !is_mod() ? [] : ($isArt ? foros(is_admin()) : []);   // mover el hilo a otro foro (19/10/2026): mods, entre los encendidos; admin, tambien a los apagados
$maxLen = $isArt ? max(2000, (int)cfg('article_max_words', 1000) * 12) : 2000;   // articulos: tope de caracteres derivado del de palabras (config)
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_mod() && (int)$s['user_id'] !== (int)user()['user_id']) mod_log_post($pdo, 'editar-envio', $err, "envío #$id");   // registro (migra-134): un mod toca un envío ajeno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'eliminar') {
    $esMod = !$own && !$retire;   // un moderador elimina un envio ajeno: motivo obligatorio, al registro (mod_log, va en los campos) y al autor por mensaje (11/10/2026)
    $motivo = mb_substr(trim(preg_replace('/\s+/', ' ', post('motivo'))), 0, 300);
    if (post('seguro') !== '1') $err = ($own || $retire) ? t('Marca la casilla de confirmación para retirarlo.') : 'Marca la casilla de confirmación para eliminar.';
    elseif ($esMod && mb_strlen($motivo) < 3) $err = 'Indica el motivo: queda en el registro y se le envía al autor.';
    else {
        // no se borra fisicamente: pasa a "discard" y deja de verse en todas partes (el admin aun puede abrir el hilo)
        $pdo->prepare("UPDATE stories SET status = 'discard' WHERE story_id = ?")->execute([$id]);
        if ($esMod && !empty($s['user_id']) && (int)$s['user_id'] !== (int)user()['user_id']) {
            $ql = $pdo->prepare("SELECT lang FROM users WHERE user_id = ?"); $ql->execute([(int)$s['user_id']]);
            $al = (string)$ql->fetchColumn() ?: 'es';
            $txt = t_in($al, 'Hola. Un moderador ha retirado tu %s «%s». Motivo: %s. Si crees que es un error, responde a este mensaje.', $isArt ? t_in($al, 'artículo') : t_in($al, 'envío'), mb_strimwidth((string)$s['title'], 0, 120, '…'), $motivo);
            try { $pdo->prepare("INSERT INTO messages (from_id, to_id, content) VALUES (?,?,?)")->execute([(int)user()['user_id'], (int)$s['user_id'], $txt]); } catch (Throwable $e) {}
        }
        redirect('/');
    }
}
if ($retire && $_SERVER['REQUEST_METHOD'] === 'POST' && post('act') !== 'eliminar') redirect("/hilo/$id");   // fuera de plazo no se edita nada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') !== 'eliminar') {
    $title   = mb_substr(texto_plano(post('title')), 0, 200);   // sin etiquetas HTML (08/10/2026)
    $content = mb_substr(post('content'), 0, $maxLen);
    $topic   = (int)post('topic');
    $lang    = lang_post() ?: lang_for_story($pdo, $isArt ? null : (post('url') ?: (string)$s['url']), $title, $content);   // idioma: el elegido o, en «detectar», el que salga (08/10/2026)
    $idx     = $isArt && cfg('index_articles', '1') === '1' ? (post('indexable') === '1' ? 1 : 0) : (int)($s['indexable'] ?? 0);   // abrir el articulo a buscadores: no cuenta como edicion
    if (!$own) {   // la comunidad principal solo la cambia moderacion; las adoptadas se gestionan desde el hilo
        $comm = (int)post('community');
        if ($comm && !in_array($comm, array_column($comms, 'community_id'), false)) $comm = 0;
        story_set_main_community($pdo, $id, $comm, (int)user()['user_id']);
    }
    $url     = $isArt ? null : ($own ? (string)$s['url'] : url_limpia(post('url')));   // el autor no toca el enlace
    $image   = $s['image'];
    if (post('quitar') === '1') $image = null;
    if (post('extraer') === '1' && $url) {                 // foto del enlace (og:image / twitter:image)
        if ($og = imagen_desde_og($url)) $image = $og;
        else $err = t('La página del enlace no ofrece ninguna foto usable.');
    }
    if ($up = imagen_desde_subida($_FILES['photo'] ?? [])) $image = $up;
    elseif (($ie = imagen_subida_error($_FILES['photo'] ?? [])) !== null) $err = $ie;   // motivo concreto (12/10/2026)

    if (!$err && mb_strlen($title) < 8) $err = t('El titular es demasiado corto.');
    elseif (!$err && !in_array($topic, array_column($topics, 'topic_id'), false)) $err = t('Elige un tema.');
    elseif (!$err && !$isArt && !$own && $url !== '' && !preg_match('#^https?://[^\s]+$#i', $url)) $err = 'La URL no es válida.';
    elseif (!$err && !$isArt && !$own && $url !== '' && ($bloq = source_blocked($pdo, $url))) $err = "Ese medio está bloqueado ($bloq): no se admiten sus enlaces. Se gestiona en moderación → Medios bloqueados.";
    elseif (!$err && $isArt && mb_strlen(trim($content)) < 20) $err = t('El texto del artículo es demasiado corto.');

    if (!$err) {
        $changed = $title !== $s['title'] || $content !== (string)($s['content'] ?? '') || $topic !== (int)$s['topic_id']
                || (string)$image !== (string)($s['image'] ?? '') || (!$isArt && ($url ?: null) !== $s['url']);
        $mark = $changed ? ", edited_at = NOW()" : "";
        if ($isArt) {
            $pdo->prepare("UPDATE stories SET title=?, content=?, topic_id=?, image=?, indexable=?, lang=?$mark WHERE story_id=?")
                ->execute([$title, $content, $topic, $image, $idx, $lang, $id]);
            if ($content !== (string)($s['content'] ?? '')) avisos_story($pdo, $id, (int)user()['user_id'], (string)($s['author_alias'] ?? user()['login']), $content, avisos_nicks((string)$s['content']));   // solo los @nick nuevos (25/10/2026)
            if ($forosSel && ($fid = (int)post('forum')) && $fid !== (int)($s['forum_id'] ?? 0)) {   // cambio de foro: no marca «editado» (el texto no cambia)
                $okF = false; foreach ($forosSel as $f) if ((int)$f['forum_id'] === $fid) $okF = $f;
                if ($okF) $pdo->prepare("UPDATE stories SET forum_id = ? WHERE story_id = ?")->execute([$fid, $id]);   // el registro de arriba (editar-envio) ya guarda forum=N
            }
        } else {
            $pdo->prepare("UPDATE stories SET title=?, content=?, topic_id=?, image=?, url=?, source=?, lang=?$mark WHERE story_id=?")
                ->execute([$title, $content ?: null, $topic, $image, $url ?: null, $url ? source_of($url) : null, $lang, $id]);
        }
        redirect("/hilo/$id");
    }
    $s = array_merge($s, ['title' => $title, 'content' => $content, 'topic_id' => $topic, 'url' => $url, 'image' => $image, 'indexable' => $idx]);
}

page_header(($retire ? t('Retirar') : ($isArt ? t('Editar artículo') : t('Editar envío'))) . " #$id");   // sin <h1>: el titulo ya va en la cabecera
if ($retire) {
    echo "<p class=\"notice\">" . t('El plazo para corregirlo ha pasado, pero mientras siga en el radar y nadie lo haya comentado puedes retirarlo.') . " <a href=\"/hilo/$id\">" . t('Volver al hilo') . "</a>.</p>";
    if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
} elseif ($own) {
    $q = (int)cfg('story_edit_minutes', 15) * 60 - (time() - strtotime($s['sent_date']));
    echo "<p class=\"notice\">" . t('Puedes corregir tu envío durante %d minutos tras enviarlo, mientras no llegue a portada (te quedan %d min). Quedará marcado como editado.', (int)cfg('story_edit_minutes', 15), max(1, intdiv($q, 60))) . " <a href=\"/hilo/$id\">" . t('Volver al hilo') . "</a>.</p>";
} else {
    echo "<p class=\"notice\">Edición de moderación. Los cambios se ven al momento; el autor, los votos y el delta no cambian. <a href=\"/hilo/$id\">Volver al hilo</a>.</p>";
}
if (!$retire) {
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "<label>" . t('Titular') . "</label><input type=\"text\" name=\"title\" maxlength=\"200\" required value=\"" . h($s['title']) . "\">";
if (!$isArt && !$own) echo "<label>Enlace (vacío = sin enlace)</label><input type=\"url\" name=\"url\" value=\"" . h((string)$s['url']) . "\" placeholder=\"https://…\">";
if (!$isArt && $own && $s['url']) echo "<label>" . t('Enlace') . "</label><p class=\"meta\" style=\"padding:0\"><a href=\"" . h($s['url']) . "\" rel=\"noopener nofollow\">" . h((string)$s['source']) . "</a> · " . t('el enlace no se puede cambiar') . "</p>";
echo "<label>" . ($isArt ? t('Texto del artículo') : t('Entradilla')) . "</label><textarea name=\"content\" rows=\"" . ($isArt ? 16 : 5) . "\" maxlength=\"$maxLen\">" . h((string)$s['content']) . "</textarea>";
if ($isArt) echo md_ayuda();
echo "<label>" . t('Tema') . "</label><select name=\"topic\">";
foreach ($topics as $t) echo "<option value=\"{$t['topic_id']}\"" . ((int)$s['topic_id'] === (int)$t['topic_id'] ? ' selected' : '') . ">" . h($t['name']) . "</option>";
echo "</select>";
echo "<label>" . t('Idioma') . "</label>" . lang_select((string)($s['lang'] ?? ''));   // el guardado; «detectar» vuelve a mirarlo al guardar (08/10/2026)
if ($forosSel) {   // mover el hilo a otro foro (19/10/2026)
    echo "<label>" . t('Foro') . " <small>" . t('cambiarlo mueve el hilo, con sus comentarios, a la lista de ese foro') . "</small></label><select name=\"forum\">";
    if (!(int)($s['forum_id'] ?? 0)) echo "<option value=\"0\" selected>— " . t('sin foro (original antiguo)') . " —</option>";
    foreach ($forosSel as $f) echo "<option value=\"{$f['forum_id']}\"" . ((int)($s['forum_id'] ?? 0) === (int)$f['forum_id'] ? ' selected' : '') . ">" . h($f['name']) . ($f['status'] !== 'on' ? ' (' . t('apagado') . ')' : '') . "</option>";
    echo "</select>";
}
if (!$own) {
    echo "<label>Comunidad principal</label><select name=\"community\"><option value=\"0\">— ninguna —</option>";
    foreach ($comms as $co) echo "<option value=\"{$co['community_id']}\"" . ((int)$s['community_id'] === (int)$co['community_id'] ? ' selected' : '') . ">" . h($co['name']) . "</option>";
    echo "</select>";
}
if ($s['image']) {
    echo "<label>" . t('Foto actual') . "</label><div class=\"media sm\" style=\"background-image:url('/media/" . h($s['image']) . "')\"></div>";
    echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"> " . t('quitar la foto') . "</label>";
    echo "<label>" . t('O sustituirla por otra') . "</label>";
} else {
    echo "<label>" . t('Foto') . "</label>";
}
echo "<input type=\"file\" name=\"photo\" accept=\"image/*\">";
if (!$isArt && $s['url']) echo "<label><input type=\"checkbox\" name=\"extraer\" value=\"1\"> " . t('extraer la foto de la página del enlace al guardar (og:image)') . "</label>";
if ($isArt && cfg('index_articles', '1') === '1' && $s['user_id']) echo "<label><input type=\"checkbox\" name=\"indexable\" value=\"1\"" . ((int)($s['indexable'] ?? 0) === 1 ? ' checked' : '') . "> " . t('Dejar que los buscadores indexen este artículo') . "</label>";
echo "<button>" . t('Guardar cambios') . "</button></form>";
}   // fin del formulario de edicion (no sale en modo retirar)
$own = $own || $retire;   // de aqui abajo, el apartado Retirar es el mismo para el autor en plazo y fuera de el
echo "<h3 style=\"padding:16px 16px 0\">" . ($own ? t('Retirar') : 'Eliminar') . "</h3>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"eliminar\">";
if ($own) echo "<label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> " . t('sí, retirar este %s y sus comentarios de todas las listas (no se puede deshacer)', $isArt ? t('artículo') : t('envío')) . "</label>";
else      echo "<label>Motivo <small>(queda en el registro de moderación y se le envía al autor por mensaje)</small></label><input type=\"text\" name=\"motivo\" maxlength=\"300\" required placeholder=\"p. ej. enlace roto, duplicado, incumple las normas…\" value=\"" . h(post('motivo')) . "\">"
             . "<label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> sí, eliminar este " . ($isArt ? 'artículo' : 'envío') . " y sus comentarios de todas las listas</label>";
echo "<button class=\"danger\">" . ($own ? t('Retirar') : 'Eliminar') . "</button></form>";
page_footer();
