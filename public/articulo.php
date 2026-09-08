<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/imagen.php';
require __DIR__ . '/../lib/aura.php';

if (!user() && cfg('anon_can_submit') !== '1') redirect('/login.php');

$topics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY topic_id")->fetchAll();
$maxWords = (int)cfg('article_max_words', 500);
$err = null;
$f = ['title' => '', 'content' => '', 'topic' => 0];

function contar_palabras(string $t): int {
    $t = trim(preg_replace('/\s+/u', ' ', $t));
    return $t === '' ? 0 : count(explode(' ', $t));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['title']   = mb_substr(post('title'), 0, 200);
    $f['content'] = mb_substr(post('content'), 0, 12000);
    $f['topic']   = (int)post('topic');
    $words = contar_palabras($f['content']);

    if (mb_strlen($f['title']) < 8) $err = 'El titular es demasiado corto.';
    elseif ($words < 20) $err = 'El artículo es demasiado corto (mínimo 20 palabras).';
    elseif ($words > $maxWords) $err = "El artículo tiene $words palabras; el máximo es $maxWords.";
    elseif (!in_array($f['topic'], array_column($topics, 'topic_id'), false)) $err = 'Elige un tema.';

    if (!$err) {
        $image = imagen_desde_subida($_FILES['photo'] ?? []);
        $u = user();
        $status = cfg('mod_enabled') === '1' ? 'pending_mod' : 'nuevas';
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO stories (content_type, user_id, topic_id, title, url, source, content, image, status)
                       VALUES ('article', ?, ?, ?, NULL, NULL, ?, ?, ?)")
            ->execute([$u['user_id'] ?? null, $f['topic'], $f['title'], $f['content'], $image, $status]);
        $sid = (int)$pdo->lastInsertId();
        $alias = $u ? alias_user($pdo, $sid, (int)$u['user_id']) : alias_anon($sid)[0];
        $pdo->prepare("UPDATE stories SET author_alias = ? WHERE story_id = ?")->execute([$alias, $sid]);
        if ($status === 'pending_mod') $pdo->prepare("INSERT INTO mod_queue (item_type, ref_id) VALUES ('story', ?)")->execute([$sid]);
        if ($u && $status === 'nuevas') aura_add($pdo, (int)$u['user_id'], (float)cfg('aura_envio', 0.1), 'envio', $sid);
        $pdo->commit();
        redirect("/hilo.php?id=$sid");
    }
}

page_header('Escribir artículo', 'articulos');
echo "<h1>Escribir un artículo</h1>";
echo "<p class=\"notice\">Texto propio de hasta $maxWords palabras, con foto si quieres. Entra en Nuevas como cualquier envío y puede llegar a portada igual.</p>";
if (!user()) echo "<p class=\"notice\">Escribes como anónimo. Si <a href=\"/login.php\">entras</a>, firmarás con un alias y tu nombre aparecerá a los " . (int)cfg('ghost_minutes', 30) . " minutos.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\">";
echo "<label>Titular</label><input type=\"text\" name=\"title\" maxlength=\"200\" required value=\"" . h($f['title']) . "\">";
echo "<label>Texto (máximo $maxWords palabras)</label><textarea name=\"content\" rows=\"14\" required>" . h($f['content']) . "</textarea>";
echo "<label>Tema</label><select name=\"topic\">";
foreach ($topics as $t) {
    $sel = ((int)$f['topic'] === (int)$t['topic_id']) ? ' selected' : '';
    echo "<option value=\"{$t['topic_id']}\"$sel>" . h($t['name']) . "</option>";
}
echo "</select>";
echo "<label>Foto</label><input type=\"file\" name=\"photo\" accept=\"image/*\">";
echo "<button>Publicar artículo</button></form>";
page_footer();
