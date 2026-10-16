<?php
/** lib/destacar.php — destacar noticias (migra-111).
 *
 *  Mientras no haya masa critica, el admin señala cada dia las noticias que cree que dan para comentar: un boton «Destacar»
 *  (chincheta) en la tarjeta y la noticia sale la primera de su lista con un halo ligero (.post.pinned). Vale para dos cosas:
 *   - envios ('story'): las portadas Medios / Substacks / Canales (/, story_post()). Si el admin destaca uno del radar, pasa a su
 *     portada en ese momento (los usuarios solo destacan lo que ya esta en portada).
 *   - noticias RSS ('item'): «Mis medios» / «Mis substacks» (/ultimo, fuente_post()).
 *  No baja hasta que se destaque otra del mismo grupo (la anterior se desmarca sola) o se quite en el mismo sitio: entonces vuelve a
 *  la posicion que le toca por fecha. Los usuarios tambien pueden: cada vez que su aura sube una unidad entera ganan un «destacado»
 *  (hasta el 09/10/2026: creditos; ahora usa aura, pin_cost / pin_cost_originales, migra-132) durante pin_user_minutes (30); una activa por
 *  usuario, destacar otra sustituye la anterior. Las del admin no caducan y hay una por grupo. Tabla pins. config.pins_enabled apaga todo.
 *  Cola (migra-114): en cada grupo solo hay una destacada de usuario a la vez; si alguien destaca mientras otra esta en marcha, la suya
 *  queda en cola (pins.starts en el futuro) y empieza cuando a la actual se le acabe su media hora; varias esperan por orden.
 *  Originales (migra-125): grupo 'originales', mismo boton en /originales y en el hilo; sin halo automatico en la primera (solo
 *  las que ponga el admin o un usuario con credito); la de usuario dura pin_user_minutes_originales (4 h).
 *  Foros (migra-159): el grupo de un texto propio es el slug de su foro ('originales', 'deltha', 'areopago'…: una destacada por foro);
 *  todos los foros usan la config de Originales (pin_cost_originales, pin_user_minutes_originales). pin_es_foro() distingue. */
/*  En todos los grupos, nunca dos destacadas a la vez: la de usuario tiene prioridad y la del admin se oculta mientras dure (vuelve sola al acabar). */
declare(strict_types=1);

function pins_on(): bool { return (string)cfg('pins_enabled', '1') === '1'; }
/** ¿Es el grupo un foro (y no una portada)? Los foros comparten la config de Originales. */
function pin_es_foro(string $grupo): bool { return $grupo !== '' && !isset(DDD_PORTADAS[$grupo]); }
/** Minutos que dura una destacada de usuario: en los foros, pin_user_minutes_originales (240); en las portadas, pin_user_minutes (30). */
function pin_minutos(string $grupo = ''): int { return max(1, (int)(pin_es_foro($grupo) ? cfg('pin_user_minutes_originales', 240) : cfg('pin_user_minutes', 30))); }

/** Grupo de un envio: su portada o, para los textos propios, el slug de su foro (migra-159; antes siempre 'originales') ('' = no se destaca: archivados). */
function pin_grupo_story(array $s): string {
    if (!in_array($s['status'] ?? '', ['nuevas', 'destacadas'], true)) return '';
    if (($s['content_type'] ?? '') === 'article') return (string)((foro_de($s)['slug'] ?? '') ?: 'originales');
    return (string)($s['portada'] ?? '');
}
/** ¿Esta el envio donde se destaca? En las portadas, solo lo publicado (un usuario no saca nada del radar; el admin si, y lo publica
 *  al destacarlo). Los originales no pasan por el radar: se destacan en /originales mientras su hilo este abierto. */
function pin_en_portada(array $s): bool {
    if (($s['content_type'] ?? '') === 'article') return in_array($s['status'] ?? '', ['nuevas', 'destacadas'], true);
    return ($s['status'] ?? '') === 'destacadas';
}
/** Grupo de una noticia RSS: el tipo de su fuente ('' = no se destaca: agregadores, YouTube). */
function pin_grupo_item(array $src): string { $k = (string)($src['kind'] ?? ''); return in_array($k, ['medio', 'newsletter'], true) ? $k : ''; }
/** Nombre de un grupo para los textos: portada o grupo del rail. */
function pin_grupo_nombre(string $what, string $grupo): string {
    if ($what === 'story') return pin_es_foro($grupo) ? (string)(foro($grupo)['name'] ?? ($grupo === 'originales' ? t('Originales') : $grupo)) : t(DDD_PORTADAS[$grupo] ?? $grupo);
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
        // en cola y el envio ya no esta donde se destaca (archivado, retirado, borrado): no llego a verse, se devuelve el aura (migra-132)
        try {
            foreach ($pdo->query("SELECT p.pin_id, p.user_id, p.grupo, p.ref_id FROM pins p LEFT JOIN stories s ON s.story_id = p.ref_id WHERE p.by_admin = 0 AND p.what = 'story' AND p.starts > NOW() AND (s.story_id IS NULL OR s.status NOT IN ('nuevas','destacadas'))")->fetchAll() as $z) {
                require_once __DIR__ . '/aura.php';
                aura_devolver($pdo, (int)$z['user_id'], pin_cost((string)$z['grupo']), 'destacar_devuelto', (int)$z['ref_id']);
                $pdo->prepare("DELETE FROM pins WHERE pin_id = ?")->execute([(int)$z['pin_id']]);
            }
        } catch (Throwable $e) {}
        foreach ($pdo->query("SELECT p.*, u.login, (p.starts IS NULL OR p.starts <= NOW()) AS activo FROM pins p JOIN users u ON u.user_id = p.user_id ORDER BY p.by_admin DESC, p.starts, p.created DESC") as $p) $pins[$p['what'] . ':' . (int)$p['ref_id']] = $p;
        // Nunca dos a la vez en un grupo (08/10/2026; antes salian la del admin y la de usuario juntas). La de un usuario manda: mientras
        // haya una de usuario en marcha en ese grupo, la del admin no se ve (ni halo, ni etiqueta, ni primera); se queda en la tabla y
        // vuelve sola cuando a la del usuario se le acabe su tiempo (media hora en las portadas, cuatro horas en Originales).
        $userOn = [];
        foreach ($pins as $p) if (!(int)$p['by_admin'] && (int)$p['activo'] === 1) $userOn[$p['what'] . '|' . $p['grupo']] = true;
        if ($userOn) foreach ($pins as $k => $p) if ((int)$p['by_admin'] && isset($userOn[$p['what'] . '|' . $p['grupo']])) unset($pins[$k]);
    } catch (Throwable $e) { /* antes de aplicar la migracion: sin destacadas */ }
    return $pins;
}
function pin_de(PDO $pdo, string $what, int $ref): ?array { return pins_activos($pdo)["$what:$ref"] ?? null; }
/** ¿Esta en marcha (halo y primera) o aun en cola? */
function pin_activo(array $pin): bool { return (int)($pin['activo'] ?? 1) === 1; }
/** ¿Es del usuario actual? */
function pin_mio(array $pin): bool { $u = user(); return $u && (int)$pin['user_id'] === (int)$u['user_id']; }
/** Sin ninguna destacada en el grupo, la primera tarjeta de la lista lleva el halo igualmente (la tarjeta queda bien y marca por
 *  donde empezar), sin etiqueta y con su boton «Destacar» normal. index.php y ultimo.php lo encienden en la pagina 1 cuando no hay
 *  destacadas; story_post() / fuente_post() lo consumen en la primera tarjeta que pintan (y lo apagan). pin_halo_primera(false) lo apaga. */
function pin_halo_primera(?bool $set = null): bool {
    static $on = false;
    if ($set !== null) return $on = $set && pins_on();
    if (!$on) return false;
    $on = false;
    return true;
}
/** Lo que usa destacar, en aura (migra-132; antes «creditos» por cada unidad nueva de aura): pin_cost (0,5) en las portadas y
 *  pin_cost_originales (1) en Originales. Se descuenta con aura_gastar() (lib/aura.php), que no deja bajar de aura_gasto_minimo. */
function pin_cost(string $grupo): float { require_once __DIR__ . '/aura.php'; return max(0.0, (float)(pin_es_foro($grupo) ? cfg('pin_cost_originales', 1) : cfg('pin_cost', 0.5))); }
/** ¿Tiene el usuario actual aura para un destacado en este grupo? */
function pin_puede_usar(string $grupo): bool { require_once __DIR__ . '/aura.php'; return aura_puede_gastar(user(), pin_cost($grupo)); }

/** Boton de la tarjeta: «Destacar» (admin siempre; usuario con destacados, solo sobre lo que ya esta en portada) o «Destacada»
 *  (pulsar = quitar: el admin sobre cualquiera, el usuario sobre la suya). Sin JS: POST a /destacar (w=story|item, i=id,
 *  act=poner|quitar), que vuelve a la pagina de origen. $grupo '' = no se destaca. */
function pin_form(PDO $pdo, string $what, int $ref, string $grupo, bool $enPortada = true): string {
    $u = user();
    if (!$u || !pins_on() || $grupo === '') return '';
    $pin = pin_de($pdo, $what, $ref);
    $nombre = pin_grupo_nombre($what, $grupo);
    $hid = "<input type=\"hidden\" name=\"w\" value=\"$what\"><input type=\"hidden\" name=\"i\" value=\"$ref\">";
    if ($pin) {
        if (!is_admin() && !pin_mio($pin)) return '';   // destacada por otro: solo se ve el halo
        if (!pin_activo($pin)) return "<form class=\"pin on cola\" method=\"post\" action=\"/destacar\">$hid<input type=\"hidden\" name=\"act\" value=\"quitar\">"
             . "<button title=\"" . h(t('En cola: empezará en %d min, cuando acabe la destacada actual. Pulsar = quitar (no se devuelve el aura).', pin_min_hasta((string)$pin['starts']))) . "\">" . icon('pin') . "<span>" . t('En cola') . "</span></button></form>";
        return "<form class=\"pin on\" method=\"post\" action=\"/destacar\">$hid<input type=\"hidden\" name=\"act\" value=\"quitar\">"
             . "<button title=\"" . h(t('quitar el destacado: vuelve a su sitio por fecha')) . "\">" . icon('pin') . "<span>" . t('Destacada') . "</span></button></form>";
    }
    if (is_admin()) $title = t('Destacar: irá la primera en %s hasta que destaques otra o la quites', $nombre);
    elseif ($enPortada && pin_puede_usar($grupo)) $title = t('Destacar: irá la primera en %s durante %d min. Usa %s de tu aura; te quedarán %s.', $nombre, pin_minutos($grupo), num_es(pin_cost($grupo)), num_es((float)$u['aura'] - pin_cost($grupo)));
    elseif ($enPortada && !is_admin()) $title = t('Destacar usa %s de aura y no se puede bajar de %s; tienes %s.', num_es(pin_cost($grupo)), num_es(aura_gasto_minimo()), num_es((float)$u['aura']));
    else return '';
    if (!is_admin() && !pin_puede_usar($grupo)) return "<span class=\"pin off\" title=\"" . h($title) . "\">" . icon('pin') . "<span>" . t('Destacar') . " <small>" . num_es(pin_cost($grupo)) . "</small></span></span>";   // sin aura suficiente: se ve lo que usa, sin boton
    return "<form class=\"pin\" method=\"post\" action=\"/destacar\">$hid<input type=\"hidden\" name=\"act\" value=\"poner\">"
         . "<button title=\"" . h($title) . "\">" . icon('pin') . "<span>" . t('Destacar') . (is_admin() ? '' : " <small>" . num_es(pin_cost($grupo)) . "</small>") . "</span></button></form>";
}
/** Minutos que faltan hasta una fecha (minimo 1). */
function pin_min_hasta(string $fecha): int { return max(1, (int)ceil((strtotime($fecha) - time()) / 60)); }
/** Etiqueta de la fila de meta de una destacada: «destacada» (admin), «destacada por nick · 25 min» (usuario) o, solo para quien la puso
 *  y el admin, «en cola · empieza en 12 min» (aun no se ve: espera a que acabe la destacada de usuario anterior del grupo). */
function pin_tag(array $pin): string {
    if ((int)$pin['by_admin']) return "<span class=\"pintag\">" . t('destacada') . "</span>";
    if (!pin_activo($pin)) return (is_admin() || pin_mio($pin)) ? "<span class=\"pintag cola\">" . t('en cola · empieza en %d min', pin_min_hasta((string)$pin['starts'])) . "</span>" : '';
    $min = pin_min_hasta((string)$pin['expires']);
    // lo que costo (13/10/2026): pins.cost si lo hay (migra-154); si no, el precio actual del grupo. Que se vea que se pago con aura.
    $coste = isset($pin['cost']) && $pin['cost'] !== null ? (float)$pin['cost'] : pin_cost((string)$pin['grupo']);
    $paga = $coste > 0 ? " <small class=\"pincost\">(" . t('%s de aura', num_es($coste)) . ")</small>" : '';
    return "<span class=\"pintag\">" . t('destacada por %s', "<a href=\"/u/" . rawurlencode((string)$pin['login']) . "\">" . h((string)$pin['login']) . "</a>") . $paga . " · " . t('%d min', $min) . "</span>";
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
        // una noticia RSS que ya esta en Deltha («en Deltha» en la tarjeta): el admin destaca tambien su envio en la portada que le toca
        // (Medios / Substacks), sustituyendo la destacada del admin que hubiera alli; si el envio esta en el radar, pasa a portada.
        if ($what === 'item' && ($s = pin_story_de_item($pdo, $ref))) {
            $g = pin_grupo_story($s);
            if ($g !== '') {
                $sid = (int)$s['story_id'];
                $pdo->prepare("DELETE FROM pins WHERE what = 'story' AND (ref_id = ? OR (by_admin = 1 AND grupo = ?))")->execute([$sid, $g]);
                $pdo->prepare("INSERT INTO pins (what, ref_id, grupo, user_id, by_admin, expires) VALUES ('story',?,?,?,1,NULL)")->execute([$sid, $g, $uid]);
                if ($s['status'] === 'nuevas') $pdo->prepare("UPDATE stories SET status = 'destacadas', published_date = NOW() WHERE story_id = ? AND status = 'nuevas'")->execute([$sid]);
            }
        }
        return '';
    }
    if (!$enPortada) return t('Esta noticia no se puede destacar.');
    require_once __DIR__ . '/aura.php';
    if (!aura_gastar($pdo, $uid, pin_cost($grupo), 'destacar', $ref)) return t('No tienes aura suficiente: destacar usa %s y no se puede bajar de %s.', num_es(pin_cost($grupo)), num_es(aura_gasto_minimo()));
    $pdo->prepare("DELETE FROM pins WHERE user_id = ? AND by_admin = 0")->execute([$uid]);   // una (activa o en cola) por usuario (la anterior no se devuelve)
    // cola: empieza cuando acabe la ultima destacada de usuario del grupo (si no hay ninguna en marcha, ahora)
    $st = $pdo->prepare("SELECT MAX(expires) FROM pins WHERE by_admin = 0 AND what = ? AND grupo = ? AND expires > NOW()");
    $st->execute([$what, $grupo]);
    $desde = (string)($st->fetchColumn() ?: '');
    // cost: lo que se pago (migra-154), para la etiqueta «destacada por nick (0,5 de aura)» aunque el precio cambie despues
    try {
        if ($desde === '') $pdo->prepare("INSERT INTO pins (what, ref_id, grupo, user_id, by_admin, starts, expires, cost) VALUES (?,?,?,?,0, NOW(), NOW() + INTERVAL ? MINUTE, ?)")->execute([$what, $ref, $grupo, $uid, pin_minutos($grupo), pin_cost($grupo)]);
        else $pdo->prepare("INSERT INTO pins (what, ref_id, grupo, user_id, by_admin, starts, expires, cost) VALUES (?,?,?,?,0, ?, ? + INTERVAL ? MINUTE, ?)")->execute([$what, $ref, $grupo, $uid, $desde, $desde, pin_minutos($grupo), pin_cost($grupo)]);
    } catch (PDOException $e) {   // migra-154 aun sin aplicar: sin la columna
        if ($desde === '') $pdo->prepare("INSERT INTO pins (what, ref_id, grupo, user_id, by_admin, starts, expires) VALUES (?,?,?,?,0, NOW(), NOW() + INTERVAL ? MINUTE)")->execute([$what, $ref, $grupo, $uid, pin_minutos($grupo)]);
        else $pdo->prepare("INSERT INTO pins (what, ref_id, grupo, user_id, by_admin, starts, expires) VALUES (?,?,?,?,0, ?, ? + INTERVAL ? MINUTE)")->execute([$what, $ref, $grupo, $uid, $desde, $desde, pin_minutos($grupo)]);
    }
    return '';
}
/** Quita una destacada: el admin, cualquiera; un usuario, solo la suya (no se devuelve el aura). */
function pin_quitar(PDO $pdo, array $u, string $what, int $ref): bool {
    $pin = pin_de($pdo, $what, $ref);
    if (!$pin || (!is_admin() && (int)$pin['user_id'] !== (int)$u['user_id'])) return false;
    $pdo->prepare("DELETE FROM pins WHERE what = ? AND ref_id = ?")->execute([$what, $ref]);
    // el admin quita una noticia RSS: tambien el destacado (suyo) del envio correspondiente en portada
    if (is_admin() && $what === 'item' && ($s = pin_story_de_item($pdo, $ref))) $pdo->prepare("DELETE FROM pins WHERE what = 'story' AND ref_id = ? AND by_admin = 1")->execute([(int)$s['story_id']]);
    return true;
}
/** El envio en Deltha de una noticia RSS (misma URL, en el radar o en portada, ultimos 30 dias), o null. */
function pin_story_de_item(PDO $pdo, int $itemId): ?array {
    $st = $pdo->prepare("SELECT s.* FROM source_items i JOIN stories s ON s.url = i.url WHERE i.item_id = ? AND s.status IN ('nuevas','destacadas') AND s.sent_date > NOW() - INTERVAL 30 DAY ORDER BY s.status = 'destacadas' DESC, s.sent_date DESC LIMIT 1");
    $st->execute([$itemId]);
    $s = $st->fetch();
    return $s ?: null;
}
/** Ids destacados en marcha de un grupo, en su orden (admin antes, luego por fecha de empezar). Las que esperan en cola no cuentan. */
function pins_ids(PDO $pdo, string $what, string $grupo): array {
    $ids = [];
    foreach (pins_activos($pdo) as $p) if ($p['what'] === $what && $p['grupo'] === $grupo && pin_activo($p)) $ids[] = (int)$p['ref_id'];
    return $ids;
}
/** Envios destacados de una portada, para pintarlos los primeros en / (pagina 1): filas de story_query_base(). */
function pins_stories(PDO $pdo, string $portada): array {
    $ids = pins_ids($pdo, 'story', $portada);
    if (!$ids) return [];
    $st = $pdo->prepare(story_query_base() . " WHERE s.story_id IN (" . implode(',', $ids) . ") AND s.status " . (pin_es_foro($portada) ? "IN ('nuevas','destacadas') AND s.content_type = 'article'" : "= 'destacadas'"));
    $st->execute();
    $rows = array_column($st->fetchAll(), null, 'story_id');
    $out = [];
    foreach ($ids as $id) if (isset($rows[$id])) $out[] = $rows[$id];
    return $out;
}
/** Noticias RSS destacadas de un grupo, para /ultimo (pagina 1): [[fuente, item], …]. Salen aunque el usuario no siga esa fuente
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
