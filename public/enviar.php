<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/imagen.php';
require __DIR__ . '/../lib/aura.php';

if (!user() && cfg('anon_can_submit') !== '1') redirect('/login.php');

$topics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY topic_id")->fetchAll();
$comms  = $pdo->query("SELECT community_id, name FROM communities WHERE status = 'ok' ORDER BY name")->fetchAll();
$err = null;
$step = post('step') ?: '1';
$f = ['url' => '', 'title' => '', 'content' => '', 'topic' => 0, 'community' => 0, 'image' => null];

// Prerrelleno por GET (boton «enviar a Nuevas» de un medio en /fuente.php, o un marcador): ?url=…&title=…&content=…&topic=ID
// equivale a haber pasado el paso 1 con esa URL; titular y entradilla del feed mandan sobre los de la pagina si vienen.
$pre = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['url'])) {
    $pre = ['title' => mb_substr(trim((string)($_GET['title'] ?? '')), 0, 200), 'content' => mb_substr(trim((string)($_GET['content'] ?? '')), 0, 2000), 'topic' => (int)($_GET['topic'] ?? 0)];
    $_POST = ['step' => '1', 'url' => trim((string)$_GET['url'])];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $step = '1';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['url'] = post('url');
    if ($f['url'] !== '' && !preg_match('#^https?://[^\s]+$#i', $f['url'])) { $err = t('La URL no es válida.'); $step = '1'; }
    if (!$err && $f['url'] !== '' && ($bloq = source_blocked($pdo, $f['url']))) { $err = t('Ese medio está bloqueado en este sitio (%s): no se admiten sus enlaces.', h($bloq)); $step = '1'; }

    if (!$err && $f['url'] !== '') {
        $d = $pdo->prepare("SELECT story_id FROM stories WHERE url = ? AND status NOT IN ('rejected','discard') AND sent_date > NOW() - INTERVAL 30 DAY");
        $d->execute([$f['url']]);
        if ($dup = $d->fetchColumn()) { $err = t('Ese enlace ya está enviado: <a href="/hilo.php?id=%d">verlo</a>.', $dup); $step = '1'; }
    }

    if (!$err && $step === '1') {
        if ($f['url'] === '') redirect('/articulo.php');
        // paso 1 -> leer la pagina y pasar al paso 2 con todo prerrellenado
        if ($f['url'] !== '') {
            $m = extraer_meta($f['url']);
            $f['title'] = $m['title']; $f['content'] = $m['description']; $f['image'] = $m['image'];
            if ($pre) {
                if ($pre['title'] !== '') $f['title'] = $pre['title'];
                if ($pre['content'] !== '') $f['content'] = $pre['content'];
                if ($pre['topic'] && in_array($pre['topic'], array_column($topics, 'topic_id'), false)) $f['topic'] = $pre['topic'];
            }
        }
        $step = '2';
    } elseif (!$err) {
        // paso 2 -> guardar
        $f['title']   = mb_substr(post('title'), 0, 200);
        $f['content'] = mb_substr(post('content'), 0, 2000);
        $f['topic']   = (int)post('topic');
        $f['community'] = (int)post('community');
        if ($f['community'] && !in_array($f['community'], array_column($comms, 'community_id'), false)) $f['community'] = 0;
        $f['image']   = post('image') ?: null;
        if ($f['image'] && (!preg_match('/^[a-f0-9]{24}\.jpg$/', $f['image']) || !is_file(DDD_MEDIA_DIR . '/' . $f['image']))) $f['image'] = null;
        if (post('quitar') === '1') $f['image'] = null;
        if ($up = imagen_desde_subida($_FILES['photo'] ?? [])) $f['image'] = $up;

        if (mb_strlen($f['title']) < 8) $err = t('El titular es demasiado corto.');
        elseif (!in_array($f['topic'], array_column($topics, 'topic_id'), false)) $err = t('Elige un tema.');

        if (!$err) {
            $u = user();
            $status = cfg('mod_enabled') === '1' ? 'pending_mod' : 'nuevas';
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO stories (content_type, user_id, topic_id, community_id, title, url, source, content, image, status)
                           VALUES ('link', ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$u['user_id'] ?? null, $f['topic'], $f['community'] ?: null, $f['title'], $f['url'] ?: null, source_of($f['url']), $f['content'] ?: null, $f['image'], $status]);
            $sid = (int)$pdo->lastInsertId();
            if ($f['community']) $pdo->prepare("INSERT IGNORE INTO story_communities (story_id, community_id, added_by) VALUES (?,?,?)")->execute([$sid, $f['community'], $u['user_id'] ?? null]);
            $alias = $u ? alias_user($pdo, $sid, (int)$u['user_id']) : alias_anon($sid)[0];
            $pdo->prepare("UPDATE stories SET author_alias = ? WHERE story_id = ?")->execute([$alias, $sid]);
            if ($status === 'pending_mod') $pdo->prepare("INSERT INTO mod_queue (item_type, ref_id) VALUES ('story', ?)")->execute([$sid]);
            if ($u && $status === 'nuevas') aura_add($pdo, (int)$u['user_id'], (float)cfg('aura_envio', 0.1), 'envio', $sid);
            $pdo->commit();
            redirect("/hilo.php?id=$sid");
        }
    }
}

page_header(t('Enviar'));
echo "" . t('<h1>Enviar</h1>') . "";
if (!user()) echo "<p class=\"notice\">" . t('Envías como anónimo. Si <a href="/login.php">entras</a>, publicarás con un alias temporal y tu nombre aparecerá a los %d minutos.', (int)cfg('ghost_minutes', 30)) . "</p>";
if ($err) echo "<p class=\"error\">$err</p>";

if ($step === '1') {
    echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"step\" value=\"1\">";
    echo "" . t('<label>Enlace a una noticia</label>') . "<input type=\"url\" name=\"url\" value=\"" . h($f['url']) . "\" placeholder=\"https://…\" autofocus>";
    echo "" . t('<button>Continuar</button>') . "</form>";
    echo "<p class=\"or\">" . t('— o —') . "</p>";
    echo "<a class=\"choice\" href=\"/articulo.php\">" . icon('art') . "<div>" . t('<b>Escribe un artículo</b>') . "<span>" . t('Texto propio de hasta %d palabras, con foto si quieres. Entra en Nuevas como cualquier envío y puede llegar a portada igual.', (int)cfg('article_max_words', 500)) . "</span></div></a>";
} else {
    echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"step\" value=\"2\">";
    echo "<input type=\"hidden\" name=\"url\" value=\"" . h($f['url']) . "\">";
    if ($f['url']) echo "<p class=\"meta\">" . h(source_of($f['url'])) . "" . t(' · <a href="/enviar.php">cambiar enlace</a>') . "</p>";
    echo "" . t('<label>Titular</label>') . "<input type=\"text\" name=\"title\" maxlength=\"200\" required value=\"" . h($f['title']) . "\">";
    echo "" . t('<label>Entradilla</label>') . "<textarea name=\"content\" maxlength=\"2000\">" . h($f['content']) . "</textarea>";
    echo "" . t('<label>Tema</label>') . "<select name=\"topic\">";
    foreach ($topics as $t) {
        $sel = ((int)$f['topic'] === (int)$t['topic_id']) ? ' selected' : '';
        echo "<option value=\"{$t['topic_id']}\"$sel>" . h($t['name']) . "</option>";
    }
    echo "</select>";
    if (!$comms && community_quota($pdo, user()) > 0) echo "<p class=\"meta\" style=\"padding:0\">" . t('Todavía no hay comunidades: <a href="/comunidades.php#crear">crea la primera</a>.') . "</p>";
    if ($comms) {
        echo "" . t('<label>Comunidad (opcional)</label>') . "<select name=\"community\"><option value=\"0\"" . t('>— ninguna —</option>') . "";
        foreach ($comms as $co) echo "<option value=\"{$co['community_id']}\"" . ((int)$f['community'] === (int)$co['community_id'] ? ' selected' : '') . ">" . h($co['name']) . "</option>";
        echo "</select>";
    }
    if ($f['image']) {
        echo "<input type=\"hidden\" name=\"image\" value=\"" . h($f['image']) . "\">";
        echo "" . t('<label>Foto encontrada en la página</label>') . "<div class=\"media sm\" style=\"background-image:url('/media/" . h($f['image']) . "')\"></div>";
        echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"" . t('> no usar esta foto</label>') . "";
        echo "" . t('<label>O sube otra</label>') . "";
    } else {
        echo "<label>" . t('Foto') . ($f['url'] ? " " . t('(la página no ofrece ninguna usable)') : "") . "</label>";
    }
    echo "<input type=\"file\" name=\"photo\" accept=\"image/*\">";
    echo "" . t('<button>Enviar</button>') . "</form>";
}
page_footer();
