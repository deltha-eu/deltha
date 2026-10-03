<?php
/** /destacar.php — destacar o quitar una noticia de un medio o substack (POST i=item_id, act=poner|quitar), migra-111.
 *  Sin JS: vuelve a la pagina de origen (la tarjeta queda con el halo o sin el). El boton lo pinta pin_form() (lib/destacar.php). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/fuentes.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login.php');
$iid = (int)post('i');
$err = '';
if ($iid > 0 && pins_on()) {
    try {
        if (post('act') === 'quitar') { pin_quitar($pdo, $u, $iid); }
        else {
            $st = $pdo->prepare("SELECT * FROM source_items WHERE item_id = ?");
            $st->execute([$iid]);
            $i = $st->fetch();
            $src = null;
            if ($i) foreach (fuentes_activas($pdo) as $s) if ((int)$s['source_id'] === (int)$i['source_id']) { $src = $s; break; }
            $err = ($i && $src) ? pin_poner($pdo, $u, $src, $i) : t('Esa noticia no existe.');
        }
    } catch (Throwable $e) { $err = t('No se ha podido destacar.'); }
}
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = '/ultimo.php?k=medio';
header('Cache-Control: no-store');
if ($err !== '') { page_header(t('Destacar')); echo "<p class=\"notice\">" . h($err) . " <a href=\"" . h($back) . "\">" . t('Volver') . "</a></p>"; page_footer(); exit; }
redirect($back . "#x$iid");
