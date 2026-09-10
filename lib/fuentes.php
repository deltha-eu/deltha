<?php
/**
 * lib/fuentes.php — fuentes externas «pintadas» con Deltha (solo lectura).
 *
 * Una fuente (tabla sources) es otro sitio con RSS. Dos tipos (sources.kind):
 *  - agregador (Menéame): feed de portada y feed de cola, fases nuevas -> portada, votos y comentarios de la
 *    fuente; se ve en /fuente.php?f=slug con Portada / Candidatas / Nuevas.
 *  - medio (Jot Down, Revista Mercurio...): un solo feed, sin fases ni votos; cada noticia lleva un boton
 *    «enviar a Nuevas» que abre /enviar.php prerrellenado. Cada usuario elige hasta media_max_per_user medios
 *    en su perfil («Mis medios», tabla user_media) y los tiene en el rail bajo Idioma y en el pie.
 *
 * El cron (promocionar.php, cada minuto) llama a fuentes_tick(): lee los feeds de cada fuente cuando vence su
 * interval_min y guarda los items en source_items, uno por id externo (meneame:link_id o guid), actualizando
 * fase, votos y comentarios. Nada de esto entra en stories, en el δ, en Procesos ni en las estadisticas.
 *
 * Imagenes (politica del sitio: sin recursos de terceros): la miniatura del agregador se descarga tal cual a
 * /media/fuentes (image = 'fuentes/x.webp'); en los medios se usa la imagen del feed o, si no hay, la og:image
 * de la noticia, procesada como en un envio (imagen_desde_bytes -> /media/x.jpg, image = 'x.jpg').
 */
declare(strict_types=1);
require_once __DIR__ . '/imagen.php';   // http_get(), imagen_desde_bytes(), imagen_desde_og()

const DDD_FUENTES_DIR = DDD_MEDIA_DIR . '/fuentes';

function fuentes_on(): bool { return (string)cfg('sources_enabled', '1') === '1'; }

/** Fuentes activas (cacheado por peticion). $kind: '' todas | agregador | medio */
function fuentes_activas(PDO $pdo, string $kind = ''): array {
    static $rows = null;
    if ($rows === null) {
        try { $rows = $pdo->query("SELECT * FROM sources WHERE status = 'ok' ORDER BY kind, name")->fetchAll(); }
        catch (Throwable $e) { $rows = []; }   // tabla aun sin crear
    }
    if ($kind === '') return $rows;
    return array_values(array_filter($rows, fn($s) => ($s['kind'] ?? 'agregador') === $kind));
}
function fuente_by_slug(PDO $pdo, string $slug): ?array {
    foreach (fuentes_activas($pdo) as $s) if ($s['slug'] === $slug) return $s;
    return null;
}
function fuente_url(array $src, string $view = ''): string {
    return '/fuente.php?f=' . rawurlencode($src['slug']) . ($view && $view !== 'portada' ? '&v=' . $view : '');
}
function fuente_es_medio(array $src): bool { return ($src['kind'] ?? 'agregador') === 'medio'; }

/** Medios elegidos por un usuario (en su orden). Vacio si no ha elegido. */
function mis_medios(PDO $pdo, ?array $u): array {
    static $cache = [];
    if (!$u) return [];
    $uid = (int)$u['user_id'];
    if (isset($cache[$uid])) return $cache[$uid];
    try {
        $st = $pdo->prepare("SELECT s.* FROM user_media um JOIN sources s ON s.source_id = um.source_id WHERE um.user_id = ? AND s.status = 'ok' AND s.kind = 'medio' ORDER BY um.pos");
        $st->execute([$uid]);
        return $cache[$uid] = $st->fetchAll();
    } catch (Throwable $e) { return $cache[$uid] = []; }
}
/** Guarda la eleccion (lista de source_id, en orden; se recorta a media_max_per_user y a medios activos). */
function guardar_mis_medios(PDO $pdo, int $uid, array $ids): void {
    $ok = array_column(fuentes_activas($pdo, 'medio'), 'source_id');
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => in_array($i, $ok, false))));
    $ids = array_slice($ids, 0, max(1, (int)cfg('media_max_per_user', 3)));
    $pdo->prepare("DELETE FROM user_media WHERE user_id = ?")->execute([$uid]);
    $ins = $pdo->prepare("INSERT INTO user_media (user_id, source_id, pos) VALUES (?,?,?)");
    foreach ($ids as $i => $sid) $ins->execute([$uid, $sid, $i + 1]);
}
/** Medios que se ofrecen en el menu y el pie: los elegidos por el usuario o, si no hay eleccion (o sin cuenta), el catalogo (hasta $max). */
function medios_menu(PDO $pdo, ?array $u, int $max = 6): array {
    $mine = mis_medios($pdo, $u);
    return $mine ?: array_slice(fuentes_activas($pdo, 'medio'), 0, $max);
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
        foreach ($old as $o) if ($o['image'] && is_file(DDD_MEDIA_DIR . '/' . $o['image'])) @unlink(DDD_MEDIA_DIR . '/' . $o['image']);
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
        $img = ($row && $row['image']) ? null : fuente_imagen($src, $it);
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

/** Imagen de un item, como ruta relativa a /media, o null.
 *  Agregador: la miniatura del feed tal cual, en /media/fuentes. Medio: la imagen del feed o la og:image de la noticia,
 *  procesadas como un envio (recorte 4:3, JPEG limpio) en /media; con tope sources_og_per_run por pasada. */
function fuente_imagen(array $src, array $it): ?string {
    static $fails = 0, $og = 0;   // si el CDN no responde, no insistir item a item; y no descargar mas de N paginas por pasada
    if ($fails >= 3) return null;
    if (!fuente_es_medio($src)) {
        if (empty($it['thumb'])) return null;
        $bytes = http_get($it['thumb'], 3, 600000);
        if ($bytes === null || strlen($bytes) < 100) { $fails++; return null; }
        $info = @getimagesizefromstring($bytes);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? null;
        if (!$ext) return null;
        if (!is_dir(DDD_FUENTES_DIR)) @mkdir(DDD_FUENTES_DIR, 0775, true);
        $name = preg_replace('/[^a-z0-9]/', '', $src['slug']) . '-' . preg_replace('/[^A-Za-z0-9_-]/', '', $it['ext_id']) . '.' . $ext;
        return @file_put_contents(DDD_FUENTES_DIR . '/' . $name, $bytes) ? 'fuentes/' . $name : null;
    }
    if (!empty($it['thumb'])) {
        $bytes = http_get($it['thumb'], 3, (int)cfg('img_max_bytes', 6000000));
        if ($bytes === null) $fails++;
        elseif ($n = imagen_desde_bytes($bytes)) return $n;
    }
    if ((string)cfg('sources_og_image', '1') === '1' && $og < max(0, (int)cfg('sources_og_per_run', 10))) {
        $og++;
        return imagen_desde_og($it['url']);
    }
    return null;
}

/** Parser de RSS 2.0: el de Meneame (rss2.php, espacio de nombres meneame:) y el generico (WordPress y similares).
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
        $ce = $i->children('http://purl.org/rss/1.0/modules/content/');
        $link = trim((string)$i->link);
        if ($link === '') continue;
        $desc = (string)$i->description;
        $full = isset($ce->encoded) ? (string)$ce->encoded : '';
        $meneame = isset($mn->link_id) && trim((string)$mn->link_id) !== '';
        $extId = $meneame ? trim((string)$mn->link_id) : substr(sha1(trim((string)$i->guid) ?: $link), 0, 32);

        // imagen: media:content / media:thumbnail / enclosure / primer <img> del contenido
        $thumb = '';
        if (isset($md->content)) foreach ($md->content as $mc) { $u = trim((string)$mc->attributes()->url); if ($u !== '' && (!isset($mc->attributes()->medium) || (string)$mc->attributes()->medium === 'image')) { $thumb = $u; break; } }
        if ($thumb === '' && isset($md->thumbnail)) $thumb = trim((string)$md->thumbnail->attributes()->url);
        if ($thumb === '' && isset($i->enclosure) && str_starts_with((string)$i->enclosure->attributes()->type, 'image/')) $thumb = trim((string)$i->enclosure->attributes()->url);
        if ($thumb === '' && preg_match("/<img[^>]+src=['\"]([^'\"]+)['\"]/i", $full . $desc, $m)) $thumb = html_entity_decode($m[1]);

        if ($meneame) {
            $source = null;
            if (preg_match('/noticia original<\/a>\s*\(([^)]+)\)/u', $desc, $m)) $source = strtolower(preg_replace('/^www\./', '', trim($m[1])));
            $url = 'https://www.meneame.net/go?id=' . (int)$mn->link_id;   // redirige al articulo original
            $thread = $link;
            $txt = preg_replace('/<p><strong>etiquetas<\/strong>.*$/su', '', $desc);
            $txt = preg_replace('/<p>[^<]*<a href="[^"]*go\?id=\d+"[^>]*>noticia original<\/a>.*$/su', '', $txt);
            $topic = trim((string)($mn->sub ?? '')) ?: null;
        } else {
            $url = $link;                                                  // el articulo es el propio enlace
            $thread = trim((string)$i->comments) ?: $link;
            $source = source_of($link);
            $txt = $desc !== '' ? $desc : $full;
            $topic = isset($i->category) ? trim((string)$i->category[0]) : null;
        }
        $txt = trim(html_entity_decode(strip_tags(preg_replace('/<img[^>]*>/i', '', $txt)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $txt = trim(preg_replace(['/\s+/u', '/\s*\[…\]\s*$/u', '/\s*\[\.\.\.\]\s*$/u'], [' ', '…', '…'], $txt));
        $ts = strtotime((string)$i->pubDate) ?: (strtotime((string)($dc->date ?? '')) ?: time());
        $out[] = [
            'ext_id'     => $extId,
            'title'      => mb_substr(trim(html_entity_decode((string)$i->title, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 200),
            'url'        => mb_substr($url, 0, 600),
            'thread_url' => mb_substr($thread, 0, 600),
            'source'     => $source ? mb_substr($source, 0, 100) : null,
            'topic'      => $topic ? mb_substr($topic, 0, 48) : null,
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

/** URL del formulario de envio prerrellenado con una noticia de un medio. */
function fuente_enviar_url(array $src, array $i): string {
    $q = ['url' => $i['url'], 'title' => $i['title'], 'content' => mb_substr((string)$i['content'], 0, 600)];
    if (!empty($src['topic_id'])) $q['topic'] = (int)$src['topic_id'];
    return '/enviar.php?' . http_build_query($q);
}
/** story_id de los envios ya hechos con estas URLs (para marcar «ya enviada» y enlazar al hilo). */
function fuente_ya_enviadas(PDO $pdo, array $urls): array {
    $urls = array_values(array_unique(array_filter($urls)));
    if (!$urls) return [];
    $st = $pdo->prepare("SELECT url, story_id FROM stories WHERE url IN (" . implode(',', array_fill(0, count($urls), '?')) . ") AND status NOT IN ('rejected','discard') AND sent_date > NOW() - INTERVAL 30 DAY");
    $st->execute($urls);
    return $st->fetchAll(PDO::FETCH_KEY_PAIR);
}

/** Post de un item externo: mismo aspecto que story_post(), sin votar, sin comentar, sin δ.
 *  $sent: [url => story_id] de los ya enviados a Deltha (medios). */
function fuente_post(array $src, array $i, string $mode = 'full', array $sent = []): void {
    $medio = fuente_es_medio($src);
    $author = $i['author'] ?: $src['name'];
    $rel = 'rel="noopener nofollow"';
    echo "<article class=\"post $mode ext" . ($medio ? ' medio' : '') . "\" id=\"x{$i['item_id']}\">";
    echo avatar((float)cfg('aura_anon', 5), $author);
    echo "<div class=\"pc\">";
    $meta = [];
    if ($medio) $meta[] = "<a href=\"" . h(fuente_url($src)) . "\">" . h($src['name']) . "</a>";
    if ($i['topic'])  $meta[] = h($i['topic']);
    if (!$medio && $i['source']) $meta[] = h($i['source']);
    $meta[] = h(hace($i['pub_date']));
    echo "<div class=\"l1\"><b>" . h($author) . "</b><span class=\"meta\"><span class=\"sep\"> · </span>" . implode(' · ', $meta) . "</span></div>";
    $isThumb = $i['image'] && str_starts_with($i['image'], 'fuentes/');
    if ($isThumb) echo "<a class=\"thumb\" href=\"" . h($i['url']) . "\" $rel style=\"background-image:url('/media/" . h($i['image']) . "')\"></a>";
    echo "<h2><a href=\"" . h($i['url']) . "\" $rel>" . h($i['title']) . "</a></h2>";
    if ($i['content']) echo "<p class=\"lead\">" . h(mb_strimwidth($i['content'], 0, $mode === 'full' ? 240 : 160, '…')) . "</p>";
    if ($i['image'] && !$isThumb) echo "<div class=\"media" . ($mode === 'full' ? '' : ' sm') . "\"><a class=\"pic\" href=\"" . h($i['url']) . "\" $rel style=\"background-image:url('/media/" . h($i['image']) . "')\"></a></div>";
    echo "<div class=\"actions\">";
    if ($medio) {
        if (isset($sent[$i['url']])) echo "<a class=\"sent\" href=\"/hilo.php?id=" . (int)$sent[$i['url']] . "\" title=\"" . h(t('ya está en Deltha: ver el hilo')) . "\">" . icon('reply') . "<span>" . t('en Deltha') . "</span></a>";
        elseif (user() || cfg('anon_can_submit') === '1') echo "<a class=\"send\" href=\"" . h(fuente_enviar_url($src, $i)) . "\" title=\"" . h(t('enviar esta noticia a Nuevas')) . "\">" . icon('plus') . "<span>" . t('enviar a Nuevas') . "</span></a>";
        echo "<a class=\"share ext\" href=\"" . h($i['url']) . "\" $rel>" . icon('link') . "<span>" . h(t('leer en %s', $src['name'])) . "</span></a>";
    } else {
        echo "<a href=\"" . h($i['thread_url']) . "\" rel=\"noopener\" title=\"" . h(t('comentarios en %s', $src['name'])) . "\">" . icon('reply') . "<span>" . (int)$i['comments'] . "</span></a>";
        echo "<span class=\"vote\"><span>" . icon('up') . "<span>" . (int)$i['votes'] . "</span></span><span>" . icon('down') . "<span>" . (int)$i['negatives'] . "</span></span></span>";
        echo "<a class=\"share ext\" href=\"" . h($i['thread_url']) . "\" rel=\"noopener\">" . icon('link') . "<span>" . h(t('ver en %s', $src['name'])) . "</span></a>";
    }
    echo "</div></div></article>";
}
