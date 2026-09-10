<?php
/** /fuente.php?f=slug[&v=portada|candidatas|nuevas] — una fuente externa pintada con Deltha, solo lectura.
 *  Agregador (Meneame): tres vistas, votos y comentarios de la fuente. Medio (Jot Down...): sus ultimas noticias,
 *  con boton «enviar a Nuevas» que abre /enviar.php prerrellenado. Los items los lee el cron (lib/fuentes.php). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/fuentes.php';

$src = fuentes_on() ? fuente_by_slug($pdo, (string)($_GET['f'] ?? '')) : null;
if (!$src) { http_response_code(404); page_header(t('Fuentes')); echo "<p class=\"end\">" . t('Esa fuente no existe.') . "</p>"; page_footer(); exit; }
$medio = fuente_es_medio($src);

$views = ['portada' => t('Portada'), 'candidatas' => t('Candidatas'), 'nuevas' => t('Nuevas')];
if ($medio || trim((string)$src['feed_nuevas']) === '') $views = ['portada' => $views['portada']];
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
$rows = $st->fetchAll();
$sent = $medio ? fuente_ya_enviadas($pdo, array_column($rows, 'url')) : [];

// la plantilla de la fuente manda sobre la del usuario (no sobre ?skin=), como la de una comunidad
community_skin((string)$src['skin']);
// anonimos: cacheable un minuto (misma politica que /procesos.php)
if (user()) header('Cache-Control: private, no-cache'); else { header('Cache-Control: public, max-age=60'); header('Vary: Cookie'); }

page_header($medio ? $src['name'] : $src['name'] . ' · ' . $views[$view]);
if (count($views) > 1) {
    echo "<nav class=\"sub\">";
    foreach ($views as $k => $lbl) echo "<a href=\"" . h(fuente_url($src, $k)) . "\"" . ($k === $view ? ' class="on"' : '') . ">" . h($lbl) . "</a>";
    echo "</nav>";
}

$last = $src['last_fetch'] ? hace($src['last_fetch']) : t('todavía no');
if ($medio) {
    $intro = t('Las últimas noticias de <a href="%s" rel="noopener">%s</a> por RSS, vistas desde Deltha.', h($src['home']), h($src['name']));
    if ($src['descr']) $intro .= ' ' . h($src['descr']) . '.';
    $intro .= ' ' . t('Cada titular lleva a la noticia; con «enviar a Nuevas» la propones en Deltha como cualquier envío.');
    $u = user();
    $intro .= ' ' . ($u ? (mis_medios($pdo, $u) ? t('Es uno de <a href="/perfil.php#medios">tus medios</a>.') : t('Puedes elegir <a href="/perfil.php#medios">tus medios</a> en el perfil.')) : t('<a href="/login.php">Entra</a> para elegir tus medios.'));
} else {
    $intro = t('Solo lectura: lo que pasa en <a href="%s" rel="noopener">%s</a>, visto desde Deltha. Aquí no se vota ni se comenta: el titular lleva al artículo y los comentarios, al hilo original.', h($src['home']), h($src['name']));
    if ($view === 'candidatas') $intro .= ' ' . t('Candidatas: las nuevas de las últimas %d h con al menos %d votos, ordenadas por votos.', (int)cfg('sources_popular_hours', 24), (int)cfg('sources_popular_min_votes', 5));
}
$intro .= ' ' . t('Se lee cada %d min · última lectura: %s.', (int)$src['interval_min'], h($last));
if ($src['last_error'] && is_admin()) $intro .= " <b>" . h($src['last_error']) . "</b>";
echo "<p class=\"notice\">$intro</p>";

foreach ($rows as $i) fuente_post($src, $i, ($medio || $view === 'portada') ? 'full' : 'compact', $sent);
if (!$rows) echo "<p class=\"end\">" . ($src['last_fetch'] ? t('No hay nada en esta vista ahora mismo.') : t('El lector aún no ha pasado por esta fuente.')) . "</p>";
paginate(fuente_url($src, $view), $page, $total);
page_footer();
