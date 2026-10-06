<?php
/** /rss.php — feed RSS 2.0 de una portada: /rss?portada=medios | substacks | canales (sin ?portada=, Substacks).
 *  Lo mismo que se ve en la portada (status = destacadas, por fecha de publicacion), sin filtro de idioma ni destacadas
 *  de usuario: lo ultimo arriba, hasta rss_items (30). <link> al articulo, <guid> y <comments> al hilo de Deltha.
 *  No pasa por page_header(): sin analitica, sin cookies de portada y con cache de cinco minutos (y 304 si no hay nada nuevo). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$q = (string)($_GET['portada'] ?? '');
$p = isset(portadas()[$q]) ? $q : 'substacks';
$n = max(1, min(100, (int)cfg('rss_items', 30)));

$st = $pdo->prepare(story_query_base() . " WHERE s.status = 'destacadas' AND s.portada = ? ORDER BY s.published_date DESC LIMIT ?");
$st->bindValue(1, $p);
$st->bindValue(2, $n, PDO::PARAM_INT);
$st->execute();
$items = $st->fetchAll();

$site = (string)cfg('site_name', 'Deltha');
$self = abs_url('/rss?portada=' . $p);
$home = abs_url('/?portada=' . $p);
$last = $items ? (int)strtotime((string)$items[0]['published_date']) : time();

header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last) . ' GMT');
if (($ims = (string)($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '')) !== '' && strtotime($ims) >= $last) { http_response_code(304); exit; }

$x = fn(?string $s) => htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$rfc = fn(string $dt) => gmdate('D, d M Y H:i:s', (int)strtotime($dt)) . ' GMT';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n<channel>\n";
echo "<title>" . $x($site . ' · ' . t(DDD_PORTADAS[$p])) . "</title>\n";
echo "<link>" . $x($home) . "</link>\n";
echo "<atom:link href=\"" . $x($self) . "\" rel=\"self\" type=\"application/rss+xml\"/>\n";
echo "<description>" . $x(t(DDD_PORTADAS[$p]) . ' · ' . $site) . "</description>\n";
echo "<language>" . $x(lang()) . "</language>\n";
echo "<lastBuildDate>" . $rfc(gmdate('Y-m-d H:i:s', $last)) . "</lastBuildDate>\n";
echo "<ttl>5</ttl>\n";
echo "<image><url>" . $x(abs_url(icon_src(512))) . "</url><title>" . $x($site) . "</title><link>" . $x($home) . "</link></image>\n";

foreach ($items as $s) {
    $hilo = abs_url('/hilo/' . (int)$s['story_id']);
    $url = (string)($s['url'] ?: $hilo);
    $rev = (int)$s['revealed'] === 1 || ghost_over($s['sent_date']);
    $author = display_name(['login' => $s['login'], 'alias' => $s['author_alias'], 'user_id' => $s['user_id']], $rev);
    $lead = $s['content'] ? mb_strimwidth($s['content_type'] === 'article' ? md_plano((string)$s['content']) : (string)$s['content'], 0, 400, '…') : '';
    $meta = ($s['source'] ? h((string)$s['source']) . ' · ' : '') . ((int)$s['votes_pos'] - (int)$s['votes_neg']) . ' ' . h(t('votos')) . ' · '
          . '<a href="' . h($hilo) . '">' . (int)$s['comments'] . ' ' . h(t('comentarios')) . '</a>';
    $desc = ($lead !== '' ? '<p>' . h($lead) . '</p>' : '') . '<p>' . $meta . '</p>';

    echo "<item>\n";
    echo "<title>" . $x((string)$s['title']) . "</title>\n";
    echo "<link>" . $x($url) . "</link>\n";
    echo "<guid isPermaLink=\"true\">" . $x($hilo) . "</guid>\n";
    echo "<comments>" . $x($hilo) . "</comments>\n";
    echo "<pubDate>" . $rfc((string)($s['published_date'] ?: $s['sent_date'])) . "</pubDate>\n";
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
