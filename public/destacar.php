<?php
/** /destacar — destacar o quitar una noticia (POST w=story|item, i=id, act=poner|quitar), migra-111. Envios de las portadas
 *  (stories) y noticias RSS de «Mis medios» / «Mis substacks» (source_items). Sin JS: vuelve a la pagina de origen (la tarjeta queda
 *  con el halo o sin el). El boton lo pinta pin_form() (lib/destacar.php). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
$what = post('w') === 'item' ? 'item' : 'story';
$id = (int)post('i');
$err = '';
if ($id > 0 && pins_on()) {
    try {
        if (post('act') === 'quitar') { pin_quitar($pdo, $u, $what, $id); }
        elseif ($what === 'story') {
            $st = $pdo->prepare("SELECT * FROM stories WHERE story_id = ?");
            $st->execute([$id]);
            $s = $st->fetch();
            $err = $s ? pin_poner($pdo, $u, 'story', $id, pin_grupo_story($s), pin_en_portada($s)) : t('Esa noticia no existe.');
        } else {
            $st = $pdo->prepare("SELECT * FROM source_items WHERE item_id = ?");
            $st->execute([$id]);
            $i = $st->fetch();
            $src = null;
            if ($i) foreach (fuentes_activas($pdo) as $f) if ((int)$f['source_id'] === (int)$i['source_id']) { $src = $f; break; }
            $err = ($i && $src) ? pin_poner($pdo, $u, 'item', $id, pin_grupo_item($src)) : t('Esa noticia no existe.');
        }
    } catch (Throwable $e) { $err = t('No se ha podido destacar.'); }
}
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = '/';
header('Cache-Control: no-store');
if ($err !== '') { page_header(t('Destacar')); echo "<p class=\"notice\">" . h($err) . " <a href=\"" . h($back) . "\">" . t('Volver') . "</a></p>"; page_footer(); exit; }
redirect($back . ($what === 'story' ? "#s$id" : "#x$id"));
