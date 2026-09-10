<?php
/**
 * lib/fuentes.php — fuentes externas «pintadas» con Deltha (solo lectura).
 *
 * Una fuente (tabla sources) es otro sitio con RSS: Menéame, para empezar. El cron lee sus
 * feeds (portada y cola) cada interval_min minutos y guarda los items en source_items,
 * uno por id externo, actualizando fase, votos y comentarios en cada pasada. Nada de esto
 * entra en stories, en el δ, en Procesos ni en las estadisticas: se ve en /fuente.php?f=slug
 * con el aspecto de Deltha, y cada titular lleva al articulo; los comentarios, al hilo original.
 *
 * Fases: 'portada' (feed_portada) y 'nuevas' (feed_nuevas). Las «candidatas» no se guardan:
 * son las nuevas de las ultimas sources_popular_hours horas con >= sources_popular_min_votes
 * votos, por votos (lo que Meneame llama Populares; su RSS no las sirve aparte).
 *
 * Miniaturas: se descargan a /media/fuentes (politica del sitio: sin recursos de terceros).
 */
declare(strict_types=1);
require_once __DIR__ . '/imagen.php';   // http_get()

const DDD_FUENTES_DIR = DDD_MEDIA_DIR . '/fuentes';

function fuentes_on(): bool { return (string)cfg('sources_enabled', '1') === '1'; }

/** Fuentes activas (cacheado por peticion). */
function fuentes_activas(PDO $pdo): array {
    static $rows = null;
    if ($rows !== null) return $rows;
    try { $rows = $pdo->query("SELECT * FROM sources WHERE status = 'ok' ORDER BY source_id")->fetchAll(); }
    catch (Throwable $e) { $rows = []; }   // tabla aun sin crear
    return $rows;
}
function fuente_by_slug(PDO $pdo, string $slug): ?array {
    foreach (fuentes_activas($pdo) as $s) if ($s['slug'] === $slug) return $s;
    return null;
}
function fuente_url(array $src, string $view = ''): string {
    return '/fuente.php?f=' . rawurlencode($src['slug']) . ($view && $view !== 'portada' ? '&v=' . $view : '');
}

/* ---- lectura de feeds (cron) ---------------------------------------- */

/** Llamada cada minuto desde promocionar.php: lee las fuentes cuyo intervalo haya vencido. Devuelve lineas de log. */
function fuentes_tick(PDO $pdo, bool $force = false): array {
    if (!fuentes_on()) return [];
    $log = [];
    foreach (fuentes_activas($pdo) as $src) {
        $due = $force || !$src['last_fetch'] || time() - strtotime($src['last_fetch']) >= max(1, (int)$src['interval_min']) * 60 - 5;
        if (!$due) continue;
        try { $log = array_merge($log, fuente_actualizar($pdo, $src)); }
        catch (Throwable $e) {
            $log[] = "fuente {$src['slug']}: ERROR " . $e->getMessage();
            $pdo->prepare("UPDATE sources SET last_fetch = NOW(), last_error = ? WHERE source_id = ?")->execute([mb_substr($e->getMessage(), 0, 255), $src['source_id']]);
        }
    }
    return $log;
}

/** Lee los feeds de una fuente y sincroniza source_items. */
function fuente_actualizar(PDO $pdo, array $src): array {
    $log = []; $err = [];
    $timeout = max(3, (int)cfg('sources_timeout_sec', 8));
    $feeds = ['portada' => $src['feed_portada'], 'nuevas' => $src['feed_nuevas']];
    // primero la cola y luego la portada: si un item esta en las dos, gana 'portada'
    foreach (['nuevas', 'portada'] as $phase) {
        $url = trim((string)$feeds[$phase]);
        if ($url === '') continue;
        $xml = http_get($url, $timeout, 4000000);
        if ($xml === null) { $err[] = "$phase: sin respuesta"; continue; }
        $items = fuentes_parse_rss($xml);
        if (!$items) { $err[] = "$phase: feed vacio o ilegible"; continue; }
        [$new, $upd] = fuente_guardar($pdo, $src, $phase, $items);
        $log[] = "fuente {$src['slug']} $phase: " . count($items) . " items, $new nuevos, $upd actualizados";
    }
    // caducidad: lo que lleva sources_keep_days dias sin aparecer en ningun feed
    $keep = max(1, (int)cfg('sources_keep_days', 7));
    $st = $pdo->prepare("SELECT item_id, image FROM source_items WHERE source_id = ? AND last_seen < NOW() - INTERVAL ? DAY");
    $st->execute([$src['source_id'], $keep]);
    $old = $st->fetchAll();
    if ($old) {
        foreach ($old as $o) if ($o['image'] && is_file(DDD_FUENTES_DIR . '/' . $o['image'])) @unlink(DDD_FUENTES_DIR . '/' . $o['image']);
        $pdo->prepare("DELETE FROM source_items WHERE source_id = ? AND last_seen < NOW() - INTERVAL ? DAY")->execute([$src['source_id'], $keep]);
        $log[] = "fuente {$src['slug']}: " . count($old) . " caducados";
    }
    $pdo->prepare("UPDATE sources SET last_fetch = NOW(), last_error = ? WHERE source_id = ?")
        ->execute([$err ? mb_substr(implode(' · ', $err), 0, 255) : null, $src['source_id']]);
    if ($err) $log[] = "fuente {$src['slug']}: " . implode(' · ', $err);
    return $log;
}

/** Upsert de los items de un feed. Devuelve [nuevos, actualizados]. */
function fuente_guardar(PDO $pdo, array $src, string $phase, array $items): array {
    $sel = $pdo->prepare("SELECT item_id, phase, image FROM source_items WHERE source_id = ? AND ext_id = ?");
    $ins = $pdo->prepare("INSERT INTO source_items (source_id, ext_id, phase, title, url, thread_url, source, topic, author, content, image, votes, negatives, comments, pub_date)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    // al pasar a portada se toma la fecha de publicacion del feed; en la misma fase la fecha no cambia
    // (la fecha nueva se decide en PHP: MySQL evalua los SET de izquierda a derecha y phase ya tendria el valor nuevo)
    $upd = $pdo->prepare("UPDATE source_items SET phase = ?, title = ?, source = COALESCE(?, source), topic = COALESCE(?, topic), content = IF(? = '', content, ?),
                          image = COALESCE(image, ?), votes = ?, negatives = ?, comments = ?, pub_date = COALESCE(?, pub_date), last_seen = NOW() WHERE item_id = ?");
    $new = $updated = 0;
    foreach ($items as $it) {
        $sel->execute([$src['source_id'], $it['ext_id']]);
        $row = $sel->fetch();
        if ($row && $row['phase'] === 'portada' && $phase === 'nuevas') {   // ya esta en portada: la cola no la degrada
            $pdo->prepare("UPDATE source_items SET last_seen = NOW() WHERE item_id = ?")->execute([$row['item_id']]);
            continue;
        }
        $img = ($row && $row['image']) ? null : fuente_miniatura($src, $it);
        if ($row) {
            $newDate = $row['phase'] === $phase ? null : $it['pub_date'];   // cambio de fase (nuevas -> portada): fecha de publicacion
            $upd->execute([$phase, $it['title'], $it['source'], $it['topic'], $it['content'], $it['content'], $img, $it['votes'], $it['negatives'], $it['comments'], $newDate, $row['item_id']]);
            $updated++;
        } else {
            $ins->execute([$src['source_id'], $it['ext_id'], $phase, $it['title'], $it['url'], $it['thread_url'], $it['source'], $it['topic'], $it['author'], $it['content'], $img, $it['votes'], $it['negatives'], $it['comments'], $it['pub_date']]);
            $new++;
        }
    }
    return [$new, $updated];
}

/** Descarga la miniatura del item a /media/fuentes/<slug>-<ext_id>.<ext>. null si no hay o falla. */
function fuente_miniatura(array $src, array $it): ?string {
    static $fails = 0;   // si el CDN de la fuente no responde, no insistir item a item (cada intento agota su timeout)
    if (empty($it['thumb']) || $fails >= 3) return null;
    $bytes = http_get($it['thumb'], 3, 600000);
    if ($bytes === null || strlen($bytes) < 100) { $fails++; return null; }
    $info = @getimagesizefromstring($bytes);
    if (!$info) return null;
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? null;
    if (!$ext) return null;
    if (!is_dir(DDD_FUENTES_DIR)) @mkdir(DDD_FUENTES_DIR, 0775, true);
    $name = preg_replace('/[^a-z0-9]/', '', $src['slug']) . '-' . preg_replace('/[^A-Za-z0-9_-]/', '', $it['ext_id']) . '.' . $ext;
    return @file_put_contents(DDD_FUENTES_DIR . '/' . $name, $bytes) ? $name : null;
}

/** Parser del RSS 2.0 de Meneame (rss2.php) y, en general, de cualquier RSS con guid/link.
 *  Devuelve items normalizados: ext_id, title, url, thread_url, source, topic, author, content, thumb, votes, negatives, comments, pub_date. */
function fuentes_parse_rss(string $xml): array {
    $prev = libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    if (!$doc || !isset($doc->channel->item)) return [];
    $out = [];
    foreach ($doc->channel->item as $i) {
        $mn = $i->children('http://meneame.net/faq-es.php');
        $md = $i->children('http://search.yahoo.com/mrss/');
        $dc = $i->children('http://purl.org/dc/elements/1.1/');
        $thread = trim((string)$i->link);
        $extId = trim((string)($mn->link_id ?? ''));
        if ($extId === '') $extId = substr(sha1(trim((string)$i->guid) ?: $thread), 0, 32);
        if ($extId === '' || $thread === '') continue;
        $desc = (string)$i->description;
        // «noticia original» (dominio) al final de la descripcion de Meneame
        $source = null;
        if (preg_match('/noticia original<\/a>\s*\(([^)]+)\)/u', $desc, $m)) $source = strtolower(preg_replace('/^www\./', '', trim($m[1])));
        // articulo: en Meneame /go?id=N redirige al original; si no hay link_id, el propio link
        $url = $mn->link_id ? 'https://www.meneame.net/go?id=' . (int)$mn->link_id : $thread;
        // entradilla: solo el texto, sin la miniatura ni las etiquetas ni el enlace final
        $txt = preg_replace('/<p><strong>etiquetas<\/strong>.*$/su', '', $desc);
        $txt = preg_replace('/<p>[^<]*<a href="[^"]*go\?id=\d+"[^>]*>noticia original<\/a>.*$/su', '', $txt);
        $txt = trim(html_entity_decode(strip_tags(preg_replace('/<img[^>]*>/i', '', $txt)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $txt = preg_replace('/\s+/u', ' ', $txt);
        $thumb = isset($md->thumbnail) ? trim((string)$md->thumbnail->attributes()->url) : '';
        if ($thumb === '' && preg_match("/<img[^>]+src=['\"]([^'\"]+)['\"]/i", $desc, $m)) $thumb = $m[1];
        $ts = strtotime((string)$i->pubDate) ?: time();
        $out[] = [
            'ext_id'     => $extId,
            'title'      => mb_substr(trim(html_entity_decode((string)$i->title, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 200),
            'url'        => mb_substr($url, 0, 600),
            'thread_url' => mb_substr($thread, 0, 600),
            'source'     => $source ? mb_substr($source, 0, 100) : null,
            'topic'      => isset($mn->sub) && trim((string)$mn->sub) !== '' ? mb_substr(trim((string)$mn->sub), 0, 48) : (isset($i->category) ? mb_substr(trim((string)$i->category[0]), 0, 48) : null),
            'author'     => mb_substr(trim((string)($mn->user ?? $dc->creator ?? '')), 0, 64) ?: null,
            'content'    => mb_substr($txt, 0, 2000),
            'thumb'      => preg_match('#^https?://#i', $thumb) ? $thumb : '',
            'votes'      => (int)($mn->votes ?? 0),
            'negatives'  => (int)($mn->negatives ?? 0),
            'comments'   => (int)($mn->comments ?? 0),
            'pub_date'   => date('Y-m-d H:i:s', $ts),
        ];
    }
    return $out;
}

/* ---- vista ------------------------------------------------------------ */

/** Consulta de una vista: portada | candidatas | nuevas. Devuelve [sql_where_order, params] sobre source_items i. */
function fuente_vista_sql(string $view): array {
    if ($view === 'candidatas') {
        return ["i.phase = 'nuevas' AND i.pub_date > NOW() - INTERVAL ? HOUR AND i.votes >= ? ORDER BY i.votes DESC, i.pub_date DESC",
                [max(1, (int)cfg('sources_popular_hours', 24)), max(0, (int)cfg('sources_popular_min_votes', 5))]];
    }
    return [$view === 'nuevas' ? "i.phase = 'nuevas' ORDER BY i.pub_date DESC" : "i.phase = 'portada' ORDER BY i.pub_date DESC", []];
}

/** Post de un item externo: mismo aspecto que story_post(), sin votar, sin comentar, sin δ. */
function fuente_post(array $src, array $i, string $mode = 'full'): void {
    $author = $i['author'] ?: $src['name'];
    echo "<article class=\"post $mode ext\" id=\"x{$i['item_id']}\">";
    echo avatar((float)cfg('aura_anon', 5), $author);
    echo "<div class=\"pc\">";
    $meta = [];
    if ($i['topic'])  $meta[] = h($i['topic']);
    if ($i['source']) $meta[] = h($i['source']);
    $meta[] = h(hace($i['pub_date']));
    echo "<div class=\"l1\"><b>" . h($author) . "</b><span class=\"meta\"><span class=\"sep\"> · </span>" . implode(' · ', $meta) . "</span></div>";
    if ($i['image']) echo "<a class=\"thumb\" href=\"" . h($i['url']) . "\" rel=\"noopener nofollow\" style=\"background-image:url('/media/fuentes/" . h($i['image']) . "')\"></a>";
    echo "<h2><a href=\"" . h($i['url']) . "\" rel=\"noopener nofollow\">" . h($i['title']) . "</a></h2>";
    if ($i['content']) echo "<p class=\"lead\">" . h(mb_strimwidth($i['content'], 0, $mode === 'full' ? 240 : 160, '…')) . "</p>";
    echo "<div class=\"actions\">";
    echo "<a href=\"" . h($i['thread_url']) . "\" rel=\"noopener\" title=\"" . h(t('comentarios en %s', $src['name'])) . "\">" . icon('reply') . "<span>" . (int)$i['comments'] . "</span></a>";
    echo "<span class=\"vote\"><span>" . icon('up') . "<span>" . (int)$i['votes'] . "</span></span><span>" . icon('down') . "<span>" . (int)$i['negatives'] . "</span></span></span>";
    echo "<a class=\"share ext\" href=\"" . h($i['thread_url']) . "\" rel=\"noopener\">" . icon('link') . "<span>" . h(t('ver en %s', $src['name'])) . "</span></a>";
    echo "</div></div></article>";
}
