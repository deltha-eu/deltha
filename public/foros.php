<?php
/** /foros — la seccion Foros (migra-159, 16/10/2026; antes /originales). Una pestaña por foro (tabla forums, admin > Foros): Originales
 *  (lo que era Originales: cada original es un hilo), Deltha (hablar del sitio) y los que se creen (Areopago, apagado hasta su importacion).
 *  /foros abre el primer foro activo; /foros?f=slug, ese. Los foros apagados solo los ve (y abre) el admin.
 *  Lista de hilos al estilo foro: titulo, autor, tema, respuestas y ultima actividad; se pulsa y se abre el hilo (/hilo/N, el de siempre).
 *  Orden segun el foro: 'fecha' (hilo mas reciente arriba) o 'actividad' (ultimo mensaje arriba). Las destacadas (pins, grupo = slug
 *  del foro) van las primeras en la pagina 1. Los hilos no pasan por el radar ni van a las portadas: se leen, se votan y se comentan aqui. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
foco_por_defecto();   // los foros se leen en modo sin distracciones salvo que el lector lo haya quitado (lib/app.php)

$visibles = foros(is_admin());
$slug = (string)($_GET['f'] ?? '');
$f = $slug !== '' ? foro($slug) : null;
if ($f && $f['status'] !== 'on' && !is_admin()) $f = null;   // apagado: para la gente no existe
if (!$f) {
    if ($slug !== '' || !$visibles) { http_response_code(404); page_header(t('Foros'), 'foros'); echo "<p class=\"end\">" . t('Ese foro no existe.') . "</p>"; page_footer(); exit; }
    redirect(foro_url(reset($visibles)));
}
$fid = (int)$f['forum_id'];
$page = page_num();
$ml = stories_mylang_sql();
// destacadas (migra-125): las primeras en la pagina 1, con halo; aqui no hay halo automatico en la primera
$pins = pins_stories($pdo, (string)$f['slug']);
$np = $pins ? " AND s.story_id NOT IN (" . implode(',', array_map(fn($x) => (int)$x['story_id'], $pins)) . ")" : '';
$where = "s.content_type='article' AND s.forum_id = ? AND s.status IN ('nuevas','destacadas','archived')$ml$np";
$cnt = $pdo->prepare("SELECT COUNT(*) FROM stories s WHERE $where"); $cnt->execute([$fid]); $total = (int)$cnt->fetchColumn();
// ultima actividad = el ultimo comentario visible o, sin comentarios, la fecha del hilo
$act = "GREATEST(s.sent_date, COALESCE((SELECT MAX(c.created) FROM comments c WHERE c.story_id = s.story_id AND c.status = 'ok'), s.sent_date))";
$orden = $f['orden'] === 'actividad' ? "last_act DESC, s.story_id DESC" : "s.sent_date DESC";
$st = $pdo->prepare(str_replace('SELECT s.*,', "SELECT s.*, $act AS last_act,", story_query_base()) . " WHERE $where ORDER BY $orden LIMIT ? OFFSET ?");
$st->bindValue(1, $fid, PDO::PARAM_INT);
$st->bindValue(2, (int)cfg('per_page', 20), PDO::PARAM_INT);
$st->bindValue(3, page_offset($page), PDO::PARAM_INT);
$st->execute();

/** Una fila de la lista de hilos: avatar del autor, titulo, y debajo autor · tema · respuestas · ultima actividad. */
function hilo_fila(PDO $pdo, array $s, bool $pin = false): void {
    $rev = (int)$s['revealed'] === 1 || ghost_over($s['sent_date']);
    $author = display_name(['login' => $s['login'], 'alias' => $s['author_alias'], 'user_id' => $s['user_id']], $rev);
    $aura = $s['user_id'] ? (float)($s['author_aura'] ?? cfg('aura_start', 5)) : (float)cfg('aura_anon', 5);
    $href = "/hilo/{$s['story_id']}";
    $n = (int)$s['comments'];
    $last = $s['last_act'] ?? $s['sent_date'];
    $p = $pin ? pin_de($pdo, 'story', (int)$s['story_id']) : null;
    // toda la fila es pulsable: el enlace del titulo se extiende a la fila con ::after (CSS); el avatar, por encima, sigue llevando al perfil
    echo "<article class=\"trow" . ($pin ? ' pinned' : '') . ($s['status'] === 'archived' ? ' closed' : '') . "\" id=\"s{$s['story_id']}\">";
    echo who_avatar($s, $rev, $aura, $author);
    echo "<div class=\"tc\"><h2>" . ($pin ? icon('pin') : '') . "<a href=\"" . ($n > 0 ? "$href#comentarios" : $href) . "\">" . h($s['title']) . "</a></h2>";
    echo "<div class=\"meta\"><b>" . h($author) . "</b> · " . h($s['topic']) . lang_tag($s) . " · " . h(hace($s['sent_date']))
       . ($s['status'] === 'archived' ? " · " . t('cerrado') : '')
       . ($p && pin_tag($p) !== '' ? " · " . pin_tag($p) : '') . "</div></div>";
    echo "<div class=\"tn\"><span class=\"n\" title=\"" . t('respuestas') . "\">" . icon('reply') . "<span>$n</span></span>"
       . ($n > 0 ? "<span class=\"last\" title=\"" . t('última respuesta') . "\">" . h(hace($last)) . "</span>" : '') . "</div>";
    echo "</article>";
}

page_header(t('Foros') . ' · ' . $f['name'], 'foros');
// pestañas: una por foro (los apagados, solo admin, en cursiva y con «(apagado)»); a la derecha, «Nuevo hilo»
echo "<nav class=\"sub foros\">";
foreach ($visibles as $x) echo "<a href=\"" . h(foro_url($x)) . "\"" . ((int)$x['forum_id'] === $fid ? ' class="on"' : '') . ">" . h($x['name']) . ($x['status'] !== 'on' ? " <small>(" . t('apagado') . ")</small>" : '') . "</a>";
echo "<a class=\"ayuda\" href=\"/articulo?f=" . h($f['slug']) . "\">" . icon('plus') . t('Nuevo hilo') . "</a>";
echo "</nav>";
if ($f['status'] !== 'on') echo "<p class=\"notice keep\">" . t('Este foro está apagado: solo lo ves tú (admin). Se enciende en admin → Foros.') . "</p>";
if ((string)$f['descr'] !== '') echo "<p class=\"notice keep\">" . h($f['descr']) . ' <a href="/articulo?f=' . h($f['slug']) . '">' . t('Abre un hilo') . '</a>.</p>';
echo "<div class=\"hilos\">";
$n = 0;
if ($page === 1) foreach ($pins as $s) { hilo_fila($pdo, $s, true); $n++; }   // destacadas, las primeras
foreach ($st as $s) { hilo_fila($pdo, $s); $n++; }
echo "</div>";
if ($n === 0) echo "<p class=\"end\">" . t('Todavía no hay hilos en este foro.') . ' <a href="/articulo?f=' . h($f['slug']) . '">' . t('Abre el primero') . '</a>.</p>';
paginate(foro_url($f), $page, $total);
page_footer();
