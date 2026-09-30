<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/imagen.php';
require __DIR__ . '/../lib/aura.php';
require __DIR__ . '/../lib/moderacion.php';

if (!user() && cfg('anon_can_submit') !== '1') redirect('/login.php');

$topics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY topic_id")->fetchAll();
$comms  = $pdo->query("SELECT community_id, name FROM communities WHERE status = 'ok' ORDER BY name")->fetchAll();
$maxWords = (int)cfg('article_max_words', 1000);   // tope de palabras: Admin → Config
$maxChars = max(2000, $maxWords * 12);                // tope de caracteres derivado (holgura para palabras largas), por si llega mas de la cuenta
$err = null;
$f = ['title' => '', 'content' => '', 'topic' => 0, 'community' => 0, 'indexable' => 0];
$canIndex = user() && cfg('index_articles', '1') === '1';   // solo registrados: el anonimo no elige salir en buscadores

function contar_palabras(string $t): int {
    $t = trim(preg_replace('/\s+/u', ' ', $t));
    return $t === '' ? 0 : count(explode(' ', $t));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['title']   = mb_substr(post('title'), 0, 200);
    $f['content'] = mb_substr(post('content'), 0, $maxChars);
    $f['topic']   = (int)post('topic');
    $f['community'] = (int)post('community');
    $f['indexable'] = $canIndex && post('indexable') === '1' ? 1 : 0;
    if ($f['community'] && !in_array($f['community'], array_column($comms, 'community_id'), false)) $f['community'] = 0;
    $words = contar_palabras($f['content']);

    if (mb_strlen($f['title']) < 8) $err = t('El titular es demasiado corto.');
    elseif ($words < 20) $err = t('El artículo es demasiado corto (mínimo 20 palabras).');
    elseif ($words > $maxWords) $err = t('El artículo tiene %d palabras; el máximo es %d.', $words, $maxWords);
    elseif (!in_array($f['topic'], array_column($topics, 'topic_id'), false)) $err = t('Elige un tema.');

    if (!$err) {
        $image = imagen_desde_subida($_FILES['photo'] ?? []);
        $u = user();
        $status = 'nuevas';   // posmoderacion: la IA local lo revisa despues (lib/moderacion.php)
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO stories (content_type, user_id, topic_id, community_id, title, url, source, lang, content, image, status, indexable)
                       VALUES ('article', ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?)")
            ->execute([$u['user_id'] ?? null, $f['topic'], $f['community'] ?: null, $f['title'], lang_detect($f['title'] . ' ' . $f['content']), $f['content'], $image, $status, $f['indexable']]);
        $sid = (int)$pdo->lastInsertId();
        $alias = $u ? alias_user($pdo, $sid, (int)$u['user_id']) : alias_anon($sid)[0];
        $pdo->prepare("UPDATE stories SET author_alias = ? WHERE story_id = ?")->execute([$alias, $sid]);
        mod_enqueue($pdo, 'story', $sid);
        if ($u) aura_add($pdo, (int)$u['user_id'], (float)cfg('aura_envio', 0.1), 'envio', $sid);
        $pdo->commit();
        redirect("/hilo.php?id=$sid");
    }
}

page_header(t('Escribir un artículo'), 'originales');   // sin <h1>: el titulo ya va en la cabecera
echo "<p class=\"notice\">" . t('Texto propio de hasta %d palabras, con foto si quieres. Se publica en Originales, donde se lee, se vota y se comenta; no pasa por el radar ni va a las portadas.', $maxWords) . "</p>";
if (!user()) echo "<p class=\"notice\">" . t('Escribes como anónimo. Si <a href="/login.php">entras</a>, firmarás con un alias y tu nombre aparecerá a los %d minutos.', (int)cfg('ghost_minutes', 30)) . "</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\">";
echo "" . t('<label>Titular</label>') . "<input type=\"text\" name=\"title\" maxlength=\"200\" required value=\"" . h($f['title']) . "\">";
echo "<label>" . t('Texto (máximo %d palabras)', $maxWords) . "</label><textarea name=\"content\" rows=\"14\" required>" . h($f['content']) . "</textarea>";
echo "<p class=\"meta mdhelp\">" . t('Marcas: <code>**negrita**</code> · <code>*cursiva*</code> · una línea que empiece por <code>&gt;</code> es una cita · <code>[texto](https://enlace)</code>. Los párrafos se separan con una línea en blanco.') . "</p>";
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
echo "" . t('<label>Foto</label>') . "<input type=\"file\" name=\"photo\" accept=\"image/*\">";
if ($canIndex) echo "<label><input type=\"checkbox\" name=\"indexable\" value=\"1\"" . ($f['indexable'] ? ' checked' : '') . "> " . t('Dejar que los buscadores indexen este artículo') . "</label><p class=\"meta\" style=\"padding:0\">" . t('Por defecto nada de %s sale en Google ni en otros buscadores. Si lo marcas, solo este artículo podrá aparecer; los rastreadores de IA siguen vetados.', h(cfg('site_name', 'Deltha'))) . "</p>";
echo "" . t('<button>Publicar artículo</button>') . "</form>";
page_footer();
