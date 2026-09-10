<?php
/** /fuente.php?f=slug&v=portada|candidatas|nuevas — una fuente externa (Meneame) pintada con Deltha, solo lectura.
 *  Los items vienen de source_items (los lee el cron, lib/fuentes.php); no se vota ni se comenta aqui:
 *  el titular lleva al articulo y los comentarios al hilo en la fuente. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

$src = fuentes_on() ? fuente_by_slug($pdo, (string)($_GET['f'] ?? '')) : null;
if (!$src) { http_response_code(404); page_header(t('Fuentes')); echo "<p class=\"end\">" . t('Esa fuente no existe.') . "</p>"; page_footer(); exit; }

$views = ['portada' => t('Portada'), 'candidatas' => t('Candidatas'), 'nuevas' => t('Nuevas')];
if (trim((string)$src['feed_nuevas']) === '') $views = ['portada' => $views['portada']];
$view = isset($views[(string)($_GET['v'] ?? '')]) ? (string)$_GET['v'] : 'portada';

[$where, $params] = fuente_vista_sql($view);
$page = page_num();
$per = (int)cfg('per_page', 20);
$whereOnly = preg_replace('/\s+ORDER BY.*$/s', '', $where);
$cnt = $pdo->prepare("SELECT COUNT(*) FROM source_items i WHERE i.source_id = ? AND $whereOnly");
$cnt->execute(array_merge([$src['source_id']], $params));
$total = (int)$cnt->fetchColumn();
$st = $pdo->prepare("SELECT i.* FROM source_items i WHERE i.source_id = ? AND $where LIMIT $per OFFSET " . page_offset($page));
$st->execute(array_merge([$src['source_id']], $params));

// la plantilla de la fuente manda sobre la del usuario (no sobre ?skin=), como la de una comunidad
community_skin((string)$src['skin']);
// anonimos: cacheable un minuto (misma politica que /procesos.php)
if (user()) header('Cache-Control: private, no-cache'); else { header('Cache-Control: public, max-age=60'); header('Vary: Cookie'); }

page_header($src['name'] . ' · ' . $views[$view]);
echo "<nav class=\"sub\">";
foreach ($views as $k => $lbl) echo "<a href=\"" . h(fuente_url($src, $k)) . "\"" . ($k === $view ? ' class="on"' : '') . ">" . h($lbl) . "</a>";
echo "</nav>";

$last = $src['last_fetch'] ? hace($src['last_fetch']) : t('todavía no');
$intro = t('Solo lectura: lo que pasa en <a href="%s" rel="noopener">%s</a>, visto desde Deltha. Aquí no se vota ni se comenta: el titular lleva al artículo y los comentarios, al hilo original.', h($src['home']), h($src['name']));
if ($view === 'candidatas') $intro .= ' ' . t('Candidatas: las nuevas de las últimas %d h con al menos %d votos, ordenadas por votos.', (int)cfg('sources_popular_hours', 24), (int)cfg('sources_popular_min_votes', 5));
$intro .= ' ' . t('Se lee cada %d min · última lectura: %s.', (int)$src['interval_min'], h($last));
if ($src['last_error'] && is_admin()) $intro .= " <b>" . h($src['last_error']) . "</b>";
echo "<p class=\"notice\">$intro</p>";

$n = 0;
foreach ($st as $i) { fuente_post($src, $i, $view === 'portada' ? 'full' : 'compact'); $n++; }
if ($n === 0) echo "<p class=\"end\">" . ($src['last_fetch'] ? t('No hay nada en esta vista ahora mismo.') : t('El lector aún no ha pasado por esta fuente.')) . "</p>";
paginate(fuente_url($src, $view), $page, $total);
page_footer();
