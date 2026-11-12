<?php
/** /rss.php — feed RSS 2.0 de una portada: /rss?portada=medios | substacks | canales (sin ?portada=, Substacks).
 *  Lo mismo que se ve en la portada (status = destacadas, por fecha de publicacion), sin filtro de idioma ni destacadas
 *  de usuario: lo ultimo arriba, hasta rss_items (30). <link> al articulo, <guid> y <comments> al hilo de Deltha.
 *  No pasa por page_header(): sin analitica, sin cookies de portada y con cache de cinco minutos (y 304 si no hay nada nuevo).
 *
 *  Por tema (12/11/2026, para los widgets de Jot Down y Jot Down Sport): /rss?tema=deportes (el nombre del tema, sin importar
 *  mayusculas ni tildes) o /rss?tema=5 (su numero, el de /tema/5). Trae las noticias (enlaces) del tema en cualquier fase visible
 *  (radar, portada o archivadas), de las tres portadas, por fecha de envio: en la portada sola un tema pequeño no tendria nada
 *  que enseñar. Con &fase=portada, solo las que han llegado a portada, por fecha de publicacion. Añadiendo &portada= se limita
 *  ademas a esa portada. Un tema que no existe da 404. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$q = (string)($_GET['portada'] ?? '');
$n = max(1, min(100, (int)cfg('rss_items', 30)));
$site = (string)cfg('site_name', 'Deltha');

$tq = trim(mb_substr((string)($_GET['tema'] ?? ''), 0, 40));
$topic = null;
if ($tq !== '') {   // feed de un tema: por numero o por nombre (la collation de topics.name no distingue mayusculas ni tildes)
    $tt = $pdo->prepare(ctype_digit($tq) ? "SELECT topic_id, name FROM topics WHERE topic_id = ?" : "SELECT topic_id, name FROM topics WHERE name = ?");
    $tt->execute([ctype_digit($tq) ? (int)$tq : $tq]);
    $topic = $tt->fetch();
    if (!$topic) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo "Ese tema no existe.\n"; exit; }
}

if ($topic) {
    $soloPortada = (string)($_GET['fase'] ?? '') === 'portada';
    $pf = isset(portadas()[$q]) ? $q : '';   // opcional: ademas, solo una portada
    $campo = $soloPortada ? 'published_date' : 'sent_date';
    $st = $pdo->prepare(story_query_base() . " WHERE s.topic_id = ? AND s.content_type = 'link' AND s.status IN (" . ($soloPortada ? "'destacadas','archived'" : "'nuevas','destacadas','archived'") . ")"
                      . ($soloPortada ? " AND s.published_date IS NOT NULL" : '') . ($pf !== '' ? " AND s.portada = ?" : '') . " ORDER BY s.$campo DESC LIMIT ?");
    $i = 1;
    $st->bindValue($i++, (int)$topic['topic_id'], PDO::PARAM_INT);
    if ($pf !== '') $st->bindValue($i++, $pf);
    $st->bindValue($i, $n, PDO::PARAM_INT);
    $st->execute();
    $items = $st->fetchAll();
    $nombre = mb_convert_case((string)$topic['name'], MB_CASE_TITLE, 'UTF-8');
    $titulo = $site . ' · ' . $nombre;
    $descr = $nombre . ' · ' . $site;
    $self = abs_url('/rss?tema=' . (int)$topic['topic_id'] . ($soloPortada ? '&fase=portada' : '') . ($pf !== '' ? '&portada=' . $pf : ''));
    $home = abs_url('/tema/' . (int)$topic['topic_id']);
    $last = $items ? (int)strtotime((string)($items[0][$campo] ?: $items[0]['sent_date'])) : time();
} else {
    $p = isset(portadas()[$q]) ? $q : 'substacks';
    $st = $pdo->prepare(story_query_base() . " WHERE s.status = 'destacadas' AND s.portada = ? ORDER BY s.published_date DESC LIMIT ?");
    $st->bindValue(1, $p);
    $st->bindValue(2, $n, PDO::PARAM_INT);
    $st->execute();
    $items = $st->fetchAll();
    $titulo = $site . ' · ' . t(DDD_PORTADAS[$p]);
    $descr = t(DDD_PORTADAS[$p]) . ' · ' . $site;
    $self = abs_url('/rss?portada=' . $p);
    $home = abs_url('/?portada=' . $p);
    $last = $items ? (int)strtotime((string)$items[0]['published_date']) : time();
}

header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last) . ' GMT');
if (($ims = (string)($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '')) !== '' && strtotime($ims) >= $last) { http_response_code(304); exit; }

$x = fn(?string $s) => htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$rfc = fn(string $dt) => gmdate('D, d M Y H:i:s', (int)strtotime($dt)) . ' GMT';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n<channel>\n";
echo "<title>" . $x($titulo) . "</title>\n";
echo "<link>" . $x($home) . "</link>\n";
echo "<atom:link href=\"" . $x($self) . "\" rel=\"self\" type=\"application/rss+xml\"/>\n";
echo "<description>" . $x($descr) . "</description>\n";
echo "<language>" . $x(lang()) . "</language>\n";
echo "<lastBuildDate>" . $rfc(gmdate('Y-m-d H:i:s', $last)) . "</lastBuildDate>\n";
echo "<ttl>5</ttl>\n";
echo "<image><url>" . $x(abs_url(icon_src(512))) . "</url><title>" . $x($site) . "</title><link>" . $x($home) . "</link></image>\n";

foreach ($items as $s) {
    $hilo = abs_url('/hilo/' . (int)$s['story_id']);
    $url = (string)($s['url'] ?: $hilo);
    $rev = (int)$s['revealed'] === 1 || ghost_over($s['sent_date']);
    $author = display_name(['login' => $s['login'], 'alias' => $s['author_alias'], 'user_id' => $s['user_id']], $rev);
    $lead = $s['content'] ? mb_strimwidth(md_plano((string)$s['content']), 0, 400, '…') : '';
    $meta = ($s['source'] ? h((string)$s['source']) . ' · ' : '') . ((int)$s['votes_pos'] - (int)$s['votes_neg']) . ' ' . h(t('votos')) . ' · '
          . '<a href="' . h($hilo) . '">' . (int)$s['comments'] . ' ' . h(t('comentarios')) . '</a>';
    $desc = ($lead !== '' ? '<p>' . h($lead) . '</p>' : '') . '<p>' . $meta . '</p>';

    echo "<item>\n";
    echo "<title>" . $x((string)$s['title']) . "</title>\n";
    echo "<link>" . $x($url) . "</link>\n";
    echo "<guid isPermaLink=\"true\">" . $x($hilo) . "</guid>\n";
    echo "<comments>" . $x($hilo) . "</comments>\n";
    echo "<pubDate>" . $rfc((string)($topic && !$soloPortada ? $s['sent_date'] : ($s['published_date'] ?: $s['sent_date']))) . "</pubDate>\n";
    echo "<dc:creator>" . $x($author) . "</dc:creator>\n";
    echo "<category>" . $x((string)$s['topic']) . "</category>\n";
    echo "<description>" . $x($desc) . "</description>\n";
    if ($s['image'] && is_file($f = __DIR__ . '/media/' . $s['image'])) {   // la foto, como adjunto (los lectores la pintan como miniatura)
        $ext = strtolower(pathinfo((string)$s['image'], PATHINFO_EXTENSION));
        $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', 'avif' => 'image/avif'][$ext] ?? 'application/octet-stream';
        echo "<enclosure url=\"" . $x(abs_url('/media/' . $s['image'])) . "\" length=\"" . (int)filesize($f) . "\" type=\"$mime\"/>\n";
    }
    echo "</item>\n";
}
echo "</channel>\n</rss>\n";
