<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/imagen.php';
require __DIR__ . '/../lib/aura.php';

if (!user() && cfg('anon_can_submit') !== '1') redirect('/login.php');

$topics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY topic_id")->fetchAll();
$err = null;
$step = post('step') ?: '1';
$f = ['url' => '', 'title' => '', 'content' => '', 'topic' => 0, 'image' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['url'] = post('url');
    if ($f['url'] !== '' && !preg_match('#^https?://[^\s]+$#i', $f['url'])) { $err = 'La URL no es válida.'; $step = '1'; }

    if (!$err && $f['url'] !== '') {
        $d = $pdo->prepare("SELECT story_id FROM stories WHERE url = ? AND status NOT IN ('rejected','discard') AND sent_date > NOW() - INTERVAL 30 DAY");
        $d->execute([$f['url']]);
        if ($dup = $d->fetchColumn()) { $err = "Ese enlace ya está enviado: <a href=\"/hilo.php?id=$dup\">verlo</a>."; $step = '1'; }
    }

    if (!$err && $step === '1') {
        if ($f['url'] === '') redirect('/articulo.php');
        // paso 1 -> leer la pagina y pasar al paso 2 con todo prerrellenado
        if ($f['url'] !== '') {
            $m = extraer_meta($f['url']);
            $f['title'] = $m['title']; $f['content'] = $m['description']; $f['image'] = $m['image'];
        }
        $step = '2';
    } elseif (!$err) {
        // paso 2 -> guardar
        $f['title']   = mb_substr(post('title'), 0, 200);
        $f['content'] = mb_substr(post('content'), 0, 2000);
        $f['topic']   = (int)post('topic');
        $f['image']   = post('image') ?: null;
        if ($f['image'] && (!preg_match('/^[a-f0-9]{24}\.jpg$/', $f['image']) || !is_file(DDD_MEDIA_DIR . '/' . $f['image']))) $f['image'] = null;
        if (post('quitar') === '1') $f['image'] = null;
        if ($up = imagen_desde_subida($_FILES['photo'] ?? [])) $f['image'] = $up;

        if (mb_strlen($f['title']) < 8) $err = 'El titular es demasiado corto.';
        elseif (!in_array($f['topic'], array_column($topics, 'topic_id'), false)) $err = 'Elige un tema.';

        if (!$err) {
            $u = user();
            $status = cfg('mod_enabled') === '1' ? 'pending_mod' : 'nuevas';
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO stories (content_type, user_id, topic_id, title, url, source, content, image, status)
                           VALUES ('link', ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$u['user_id'] ?? null, $f['topic'], $f['title'], $f['url'] ?: null, source_of($f['url']), $f['content'] ?: null, $f['image'], $status]);
            $sid = (int)$pdo->lastInsertId();
            $alias = $u ? alias_user($pdo, $sid, (int)$u['user_id']) : alias_anon($sid)[0];
            $pdo->prepare("UPDATE stories SET author_alias = ? WHERE story_id = ?")->execute([$alias, $sid]);
            if ($status === 'pending_mod') $pdo->prepare("INSERT INTO mod_queue (item_type, ref_id) VALUES ('story', ?)")->execute([$sid]);
            if ($u && $status === 'nuevas') aura_add($pdo, (int)$u['user_id'], (float)cfg('aura_envio', 0.1), 'envio', $sid);
            $pdo->commit();
            redirect("/hilo.php?id=$sid");
        }
    }
}

page_header('Enviar');
echo "<h1>Enviar</h1>";
if (!user()) echo "<p class=\"notice\">Envías como anónimo. Si <a href=\"/login.php\">entras</a>, publicarás con un alias temporal y tu nombre aparecerá a los " . (int)cfg('ghost_minutes', 30) . " minutos.</p>";
if ($err) echo "<p class=\"error\">$err</p>";

if ($step === '1') {
    echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"step\" value=\"1\">";
    echo "<label>Enlace a una noticia</label><input type=\"url\" name=\"url\" value=\"" . h($f['url']) . "\" placeholder=\"https://…\" autofocus>";
    echo "<button>Continuar</button></form>";
    echo "<p class=\"or\">— o —</p>";
    echo "<a class=\"choice\" href=\"/articulo.php\">" . icon('art') . "<div><b>Escribe un artículo</b><span>Texto propio de hasta " . (int)cfg('article_max_words', 500) . " palabras, con foto si quieres. Entra en Nuevas como cualquier envío y puede llegar a portada igual.</span></div></a>";
} else {
    echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"step\" value=\"2\">";
    echo "<input type=\"hidden\" name=\"url\" value=\"" . h($f['url']) . "\">";
    if ($f['url']) echo "<p class=\"meta\">" . h(source_of($f['url'])) . " · <a href=\"/enviar.php\">cambiar enlace</a></p>";
    echo "<label>Titular</label><input type=\"text\" name=\"title\" maxlength=\"200\" required value=\"" . h($f['title']) . "\">";
    echo "<label>Entradilla</label><textarea name=\"content\" maxlength=\"2000\">" . h($f['content']) . "</textarea>";
    echo "<label>Tema</label><select name=\"topic\">";
    foreach ($topics as $t) {
        $sel = ((int)$f['topic'] === (int)$t['topic_id']) ? ' selected' : '';
        echo "<option value=\"{$t['topic_id']}\"$sel>" . h($t['name']) . "</option>";
    }
    echo "</select>";
    if ($f['image']) {
        echo "<input type=\"hidden\" name=\"image\" value=\"" . h($f['image']) . "\">";
        echo "<label>Foto encontrada en la página</label><div class=\"media sm\" style=\"background-image:url('/media/" . h($f['image']) . "')\"></div>";
        echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"> no usar esta foto</label>";
        echo "<label>O sube otra</label>";
    } else {
        echo "<label>Foto" . ($f['url'] ? " (la página no ofrece ninguna usable)" : "") . "</label>";
    }
    echo "<input type=\"file\" name=\"photo\" accept=\"image/*\">";
    echo "<button>Enviar</button></form>";
}
page_footer();
