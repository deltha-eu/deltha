<?php
/** lib/destacar.php — destacar noticias de medios y substacks (migra-111).
 *
 *  Mientras no haya masa critica, el admin señala cada dia las noticias que cree que dan para comentar: un boton «Destacar» en la
 *  tarjeta (fuente_post()) y la noticia sale la primera en «Mis medios» / «Mis substacks» (/ultimo.php) con un halo ligero
 *  (.post.pinned). No baja hasta que se destaque otra del mismo grupo (la anterior se desmarca sola) o se quite en el mismo sitio:
 *  entonces vuelve a la posicion que le toca por fecha. Los usuarios tambien pueden: cada vez que su aura sube una unidad entera
 *  ganan un «destacado» (users.pin_credits, se cuenta en aura_add()), y gastarlo destaca una noticia durante pin_user_minutes (30);
 *  una activa por usuario, destacar otra sustituye la anterior. Las del admin no caducan y hay una por grupo.
 *  Tabla source_pins (una fila por noticia). config.pins_enabled apaga el boton y el orden. */
declare(strict_types=1);

function pins_on(): bool { return (string)cfg('pins_enabled', '1') === '1'; }
/** Grupo en el que se destaca una noticia de esta fuente ('' = no se destaca: agregadores, YouTube). */
function pins_kind(array $src): string { $k = (string)($src['kind'] ?? ''); return in_array($k, ['medio', 'newsletter'], true) ? $k : ''; }
function pin_minutos(): int { return max(1, (int)cfg('pin_user_minutes', 30)); }

/** Destacadas vivas: item_id => fila (con login y aura de quien destaco), cacheado por peticion. De paso borra las caducadas. */
function pins_activos(PDO $pdo): array {
    static $pins = null;
    if ($pins !== null) return $pins;
    $pins = [];
    if (!pins_on()) return $pins;
    try {
        $pdo->exec("DELETE FROM source_pins WHERE expires IS NOT NULL AND expires <= NOW()");
        foreach ($pdo->query("SELECT p.*, u.login, u.aura FROM source_pins p JOIN users u ON u.user_id = p.user_id ORDER BY p.by_admin DESC, p.created DESC") as $p) $pins[(int)$p['item_id']] = $p;
    } catch (Throwable $e) { /* antes de aplicar la migracion: sin destacadas */ }
    return $pins;
}
function pin_de(PDO $pdo, int $itemId): ?array { return pins_activos($pdo)[$itemId] ?? null; }
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

/** Boton de la tarjeta: «Destacar» (admin siempre; usuario con destacados) o «Destacada · quitar» (admin sobre cualquiera; el usuario sobre la suya).
 *  Sin JS: POST a /destacar.php (i=item_id, act=poner|quitar), que vuelve a la pagina de origen. */
function pin_form(PDO $pdo, array $src, array $i): string {
    $u = user();
    if (!$u || !pins_on() || !($kind = pins_kind($src))) return '';
    $pin = pin_de($pdo, (int)$i['item_id']);
    $grupo = t($kind === 'medio' ? 'Mis medios' : 'Mis substacks');
    $hid = "<input type=\"hidden\" name=\"i\" value=\"" . (int)$i['item_id'] . "\">";
    if ($pin) {
        if (!is_admin() && (int)$pin['user_id'] !== (int)$u['user_id']) return '';   // destacada por otro: solo se ve el halo
        return "<form class=\"pin on\" method=\"post\" action=\"/destacar.php\">$hid<input type=\"hidden\" name=\"act\" value=\"quitar\">"
             . "<button title=\"" . h(t('quitar el destacado: vuelve a su sitio por fecha')) . "\">" . icon('pin') . "<span>" . t('Destacada') . "</span></button></form>";
    }
    if (is_admin()) $title = t('Destacar: irá la primera en %s hasta que destaques otra o la quites', $grupo);
    elseif (pin_credits() > 0) $title = t('Destacar: irá la primera en %s durante %d min. Te quedan %d destacados (ganas uno cada vez que tu aura sube una unidad).', $grupo, pin_minutos(), pin_credits());
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

/** Destaca una noticia. Devuelve '' si bien o el motivo si no. */
function pin_poner(PDO $pdo, array $u, array $src, array $i): string {
    if (!pins_on() || !($kind = pins_kind($src))) return t('Esta noticia no se puede destacar.');
    $iid = (int)$i['item_id']; $uid = (int)$u['user_id'];
    if (pin_de($pdo, $iid)) return t('Ya está destacada.');
    if (is_admin()) {
        // una por grupo: la anterior del admin en este grupo se desmarca sola
        $pdo->prepare("DELETE FROM source_pins WHERE by_admin = 1 AND kind = ?")->execute([$kind]);
        $pdo->prepare("INSERT INTO source_pins (item_id, kind, user_id, by_admin, expires) VALUES (?,?,?,1,NULL)")->execute([$iid, $kind, $uid]);
        return '';
    }
    $st = $pdo->prepare("UPDATE users SET pin_credits = pin_credits - 1 WHERE user_id = ? AND pin_credits > 0");
    $st->execute([$uid]);
    if (!$st->rowCount()) return t('No te quedan destacados: ganas uno cada vez que tu aura sube una unidad.');
    $pdo->prepare("DELETE FROM source_pins WHERE user_id = ? AND by_admin = 0")->execute([$uid]);   // una activa por usuario
    $pdo->prepare("INSERT INTO source_pins (item_id, kind, user_id, by_admin, expires) VALUES (?,?,?,0, NOW() + INTERVAL ? MINUTE)")->execute([$iid, $kind, $uid, pin_minutos()]);
    return '';
}
/** Quita una destacada: el admin, cualquiera; un usuario, solo la suya (no se devuelve el destacado). */
function pin_quitar(PDO $pdo, array $u, int $itemId): bool {
    $pin = pin_de($pdo, $itemId);
    if (!$pin || (!is_admin() && (int)$pin['user_id'] !== (int)$u['user_id'])) return false;
    $pdo->prepare("DELETE FROM source_pins WHERE item_id = ?")->execute([$itemId]);
    return true;
}

/** Las destacadas de un grupo, para pintarlas las primeras en /ultimo.php (pagina 1): [[fuente, item], …], las del admin antes.
 *  Salen aunque el usuario no siga esa fuente ni sea de su idioma: destacar es precisamente llevar la atencion a esa noticia. */
function pins_primero(PDO $pdo, string $kind): array {
    $ids = array_keys(array_filter(pins_activos($pdo), fn($p) => $p['kind'] === $kind));
    if (!$ids) return [];
    $st = $pdo->prepare("SELECT i.* FROM source_items i WHERE i.item_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
    $st->execute($ids);
    $items = array_column($st->fetchAll(), null, 'item_id');
    $src = array_column(fuentes_activas($pdo), null, 'source_id');
    $out = [];
    foreach ($ids as $id) if (isset($items[$id], $src[(int)$items[$id]['source_id']])) $out[] = [$src[(int)$items[$id]['source_id']], $items[$id]];
    return $out;
}
