<?php
/** lib/destacar.php — destacar noticias (migra-111).
 *
 *  Mientras no haya masa critica, el admin señala cada dia las noticias que cree que dan para comentar: un boton «Destacar»
 *  (chincheta) en la tarjeta y la noticia sale la primera de su lista con un halo ligero (.post.pinned). Vale para dos cosas:
 *   - envios ('story'): las portadas Medios / Substacks / Canales (/, story_post()). Si el admin destaca uno del radar, pasa a su
 *     portada en ese momento (los usuarios solo destacan lo que ya esta en portada).
 *   - noticias RSS ('item'): «Mis medios» / «Mis substacks» (/ultimo.php, fuente_post()).
 *  No baja hasta que se destaque otra del mismo grupo (la anterior se desmarca sola) o se quite en el mismo sitio: entonces vuelve a
 *  la posicion que le toca por fecha. Los usuarios tambien pueden: cada vez que su aura sube una unidad entera ganan un «destacado»
 *  (users.pin_credits, se cuenta en aura_add()), y gastarlo destaca una noticia durante pin_user_minutes (30); una activa por
 *  usuario, destacar otra sustituye la anterior. Las del admin no caducan y hay una por grupo. Tabla pins. config.pins_enabled apaga todo. */
declare(strict_types=1);

function pins_on(): bool { return (string)cfg('pins_enabled', '1') === '1'; }
function pin_minutos(): int { return max(1, (int)cfg('pin_user_minutes', 30)); }

/** Grupo de un envio: su portada ('' = no se destaca: originales, sin portada). */
function pin_grupo_story(array $s): string {
    if (($s['content_type'] ?? '') === 'article' || !in_array($s['status'] ?? '', ['nuevas', 'destacadas'], true)) return '';
    return (string)($s['portada'] ?? '');
}
/** Grupo de una noticia RSS: el tipo de su fuente ('' = no se destaca: agregadores, YouTube). */
function pin_grupo_item(array $src): string { $k = (string)($src['kind'] ?? ''); return in_array($k, ['medio', 'newsletter'], true) ? $k : ''; }
/** Nombre de un grupo para los textos: portada o grupo del rail. */
function pin_grupo_nombre(string $what, string $grupo): string {
    if ($what === 'story') return t(DDD_PORTADAS[$grupo] ?? $grupo);
    return t($grupo === 'medio' ? 'Mis medios' : 'Mis substacks');
}

/** Destacadas vivas: "what:ref" => fila (con login de quien destaco), cacheado por peticion. De paso borra las caducadas. */
function pins_activos(PDO $pdo): array {
    static $pins = null;
    if ($pins !== null) return $pins;
    $pins = [];
    if (!pins_on()) return $pins;
    try {
        $pdo->exec("DELETE FROM pins WHERE expires IS NOT NULL AND expires <= NOW()");
        foreach ($pdo->query("SELECT p.*, u.login FROM pins p JOIN users u ON u.user_id = p.user_id ORDER BY p.by_admin DESC, p.created DESC") as $p) $pins[$p['what'] . ':' . (int)$p['ref_id']] = $p;
    } catch (Throwable $e) { /* antes de aplicar la migracion: sin destacadas */ }
    return $pins;
}
function pin_de(PDO $pdo, string $what, int $ref): ?array { return pins_activos($pdo)["$what:$ref"] ?? null; }
/** Destacados que le quedan al usuario actual (los gana al subir de unidad de aura). Una consulta por peticion, solo si hace falta
 *  (no va en el SELECT de user() para que el sitio no se rompa entre subir el codigo y aplicar la migracion). */
function pin_credits(): int {
    static $n = null;
    if ($n !== null) return $n;
    $u = user();
    if (!$u) return $n = 0;
    global $pdo;
    try { $st = $pdo->prepare("SELECT pin_credits FROM users WHERE user_id = ?"); $st->execute([(int)$u['user_id']]); return $n = (int)$st->fetchColumn(); }
    catch (Throwable $e) { return $n = 0; }
}

/** Boton de la tarjeta: «Destacar» (admin siempre; usuario con destacados, solo sobre lo que ya esta en portada) o «Destacada»
 *  (pulsar = quitar: el admin sobre cualquiera, el usuario sobre la suya). Sin JS: POST a /destacar.php (w=story|item, i=id,
 *  act=poner|quitar), que vuelve a la pagina de origen. $grupo '' = no se destaca. */
function pin_form(PDO $pdo, string $what, int $ref, string $grupo, bool $enPortada = true): string {
    $u = user();
    if (!$u || !pins_on() || $grupo === '') return '';
    $pin = pin_de($pdo, $what, $ref);
    $nombre = pin_grupo_nombre($what, $grupo);
    $hid = "<input type=\"hidden\" name=\"w\" value=\"$what\"><input type=\"hidden\" name=\"i\" value=\"$ref\">";
    if ($pin) {
        if (!is_admin() && (int)$pin['user_id'] !== (int)$u['user_id']) return '';   // destacada por otro: solo se ve el halo
        return "<form class=\"pin on\" method=\"post\" action=\"/destacar.php\">$hid<input type=\"hidden\" name=\"act\" value=\"quitar\">"
             . "<button title=\"" . h(t('quitar el destacado: vuelve a su sitio por fecha')) . "\">" . icon('pin') . "<span>" . t('Destacada') . "</span></button></form>";
    }
    if (is_admin()) $title = t('Destacar: irá la primera en %s hasta que destaques otra o la quites', $nombre);
    elseif ($enPortada && pin_credits() > 0) $title = t('Destacar: irá la primera en %s durante %d min. Te quedan %d destacados (ganas uno cada vez que tu aura sube una unidad).', $nombre, pin_minutos(), pin_credits());
    else return '';
    return "<form class=\"pin\" method=\"post\" action=\"/destacar.php\">$hid<input type=\"hidden\" name=\"act\" value=\"poner\">"
         . "<button title=\"" . h($title) . "\">" . icon('pin') . "<span>" . t('Destacar') . (is_admin() ? '' : " <small>" . pin_credits() . "</small>") . "</span></button></form>";
}
/** Etiqueta de la fila de meta de una destacada: «destacada» (admin) o «destacada por nick · 25 min» (usuario). */
function pin_tag(array $pin): string {
    if ((int)$pin['by_admin']) return "<span class=\"pintag\">" . t('destacada') . "</span>";
    $min = max(1, (int)ceil((strtotime((string)$pin['expires']) - time()) / 60));
    return "<span class=\"pintag\">" . t('destacada por %s', "<a href=\"/u.php?u=" . rawurlencode((string)$pin['login']) . "\">" . h((string)$pin['login']) . "</a>") . " · " . t('%d min', $min) . "</span>";
}

/** Destaca algo. Devuelve '' si bien o el motivo si no. $enPortada: false si es un envio del radar (solo el admin puede: lo publica). */
function pin_poner(PDO $pdo, array $u, string $what, int $ref, string $grupo, bool $enPortada = true): string {
    if (!pins_on() || $grupo === '') return t('Esta noticia no se puede destacar.');
    $uid = (int)$u['user_id'];
    if (pin_de($pdo, $what, $ref)) return t('Ya está destacada.');
    if (is_admin()) {
        $pdo->prepare("DELETE FROM pins WHERE by_admin = 1 AND what = ? AND grupo = ?")->execute([$what, $grupo]);   // una por grupo
        $pdo->prepare("INSERT INTO pins (what, ref_id, grupo, user_id, by_admin, expires) VALUES (?,?,?,?,1,NULL)")->execute([$what, $ref, $grupo, $uid]);
        // un envio del radar pasa a su portada ahora (con la fecha de ahora: al quitar el destacado cae donde le toque por ella)
        if ($what === 'story' && !$enPortada) $pdo->prepare("UPDATE stories SET status = 'destacadas', published_date = NOW() WHERE story_id = ? AND status = 'nuevas'")->execute([$ref]);
        return '';
    }
    if (!$enPortada) return t('Esta noticia no se puede destacar.');
    $st = $pdo->prepare("UPDATE users SET pin_credits = pin_credits - 1 WHERE user_id = ? AND pin_credits > 0");
    $st->execute([$uid]);
    if (!$st->rowCount()) return t('No te quedan destacados: ganas uno cada vez que tu aura sube una unidad.');
    $pdo->prepare("DELETE FROM pins WHERE user_id = ? AND by_admin = 0")->execute([$uid]);   // una activa por usuario
    $pdo->prepare("INSERT INTO pins (what, ref_id, grupo, user_id, by_admin, expires) VALUES (?,?,?,?,0, NOW() + INTERVAL ? MINUTE)")->execute([$what, $ref, $grupo, $uid, pin_minutos()]);
    return '';
}
/** Quita una destacada: el admin, cualquiera; un usuario, solo la suya (no se devuelve el destacado). */
function pin_quitar(PDO $pdo, array $u, string $what, int $ref): bool {
    $pin = pin_de($pdo, $what, $ref);
    if (!$pin || (!is_admin() && (int)$pin['user_id'] !== (int)$u['user_id'])) return false;
    $pdo->prepare("DELETE FROM pins WHERE what = ? AND ref_id = ?")->execute([$what, $ref]);
    return true;
}
/** Ids destacados de un grupo, en su orden (admin antes, luego por fecha de destacar). */
function pins_ids(PDO $pdo, string $what, string $grupo): array {
    $ids = [];
    foreach (pins_activos($pdo) as $p) if ($p['what'] === $what && $p['grupo'] === $grupo) $ids[] = (int)$p['ref_id'];
    return $ids;
}
/** Envios destacados de una portada, para pintarlos los primeros en / (pagina 1): filas de story_query_base(). */
function pins_stories(PDO $pdo, string $portada): array {
    $ids = pins_ids($pdo, 'story', $portada);
    if (!$ids) return [];
    $st = $pdo->prepare(story_query_base() . " WHERE s.story_id IN (" . implode(',', $ids) . ") AND s.status = 'destacadas'");
    $st->execute();
    $rows = array_column($st->fetchAll(), null, 'story_id');
    $out = [];
    foreach ($ids as $id) if (isset($rows[$id])) $out[] = $rows[$id];
    return $out;
}
/** Noticias RSS destacadas de un grupo, para /ultimo.php (pagina 1): [[fuente, item], …]. Salen aunque el usuario no siga esa fuente
 *  ni sea de su idioma: destacar es precisamente llevar la atencion a esa noticia. */
function pins_primero(PDO $pdo, string $kind): array {
    $ids = pins_ids($pdo, 'item', $kind);
    if (!$ids) return [];
    $st = $pdo->prepare("SELECT i.* FROM source_items i WHERE i.item_id IN (" . implode(',', $ids) . ")");
    $st->execute();
    $items = array_column($st->fetchAll(), null, 'item_id');
    $src = array_column(fuentes_activas($pdo), null, 'source_id');
    $out = [];
    foreach ($ids as $id) if (isset($items[$id], $src[(int)$items[$id]['source_id']])) $out[] = [$src[(int)$items[$id]['source_id']], $items[$id]];
    return $out;
}
