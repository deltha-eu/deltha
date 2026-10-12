<?php
/** lib/ghost.php — «Ghostear» a un usuario (ignorar). Tabla user_ghosts (sql/migra-086-ghostear.sql).
 *
 *  Un solo boton, sin niveles. Si A ghostea a B, entre los dos cae un velo simetrico y permanente:
 *   - cada uno ve al otro siempre como Ghost: en envios y comentarios el alias del hilo (sin revelacion a los
 *     ghost_minutes); en el Chat, las menciones @nick y los avisos, un alias estable por pareja (ghost_alias);
 *     sin foto ni enlace al perfil;
 *   - los comentarios de B se le pliegan a A (details cerrado, por si quiere leerlos);
 *   - ninguno puede ver el perfil del otro (/u → «no existe») ni escribirle un privado; no se avisan entre ellos;
 *   - nadie recibe aviso, no hay listas publicas. Los votos no se tocan (los envios son de todos) y no se
 *     oculta nada al resto.
 *  El boton solo aparece cuando el nombre ya esta revelado (perfil o comentario pasados los ghost_minutes): antes
 *  serviria para desanonimizar (ghostear a un alias y ver desaparecer sus otros comentarios). El filtro va siempre
 *  por el user_id real, tambien cuando escribe como Ghost.
 *  Registrados solamente: un anonimo ve los nombres como en cualquier web abierta, pero no puede actuar. */
declare(strict_types=1);

/** Activo (config.ghost_users_enabled y tabla creada). */
function ghost_on(): bool {
    static $on = null; global $pdo;
    if ($on !== null) return $on;
    if ((string)cfg('ghost_users_enabled', '1') !== '1') return $on = false;
    try { $pdo->query("SELECT 1 FROM user_ghosts LIMIT 1"); return $on = true; } catch (Throwable $e) { return $on = false; }
}

/** Ids velados para el usuario actual: a quien he ghosteado ('mine') y quien me ha ghosteado ('me'). Una consulta por pagina. */
function ghost_sets(): array {
    static $s = null; global $pdo;
    if ($s !== null) return $s;
    $s = ['mine' => [], 'me' => []];
    if (user() && ghost_on()) {
        try {
            $me = (int)user()['user_id'];
            $st = $pdo->prepare("SELECT user_id, target_id FROM user_ghosts WHERE user_id = ? OR target_id = ?");
            $st->execute([$me, $me]);
            foreach ($st as $r) {
                if ((int)$r['user_id'] === $me) $s['mine'][(int)$r['target_id']] = true;
                else                             $s['me'][(int)$r['user_id']] = true;
            }
        } catch (Throwable $e) {}
    }
    return $s;
}
/** ¿Veo a este usuario como Ghost (lo he ghosteado o me ha ghosteado)? */
function ghost_veiled($uid): bool {
    $uid = (int)$uid;
    if ($uid <= 0 || !user()) return false;
    $s = ghost_sets();
    return isset($s['mine'][$uid]) || isset($s['me'][$uid]);
}
/** ¿Lo he ghosteado yo (sus comentarios me salen plegados)? */
function ghost_folded($uid): bool { $uid = (int)$uid; return $uid > 0 && isset(ghost_sets()['mine'][$uid]); }

/** Trozo SQL para dejar fuera de una lista de usuarios a los velados (ambos sentidos): " AND $col NOT IN (...)" o ''. */
function ghost_sql_not_in(string $col): string {
    if (!user()) return '';
    $s = ghost_sets();
    $ids = array_keys($s['mine'] + $s['me']);
    return $ids ? " AND $col NOT IN (" . implode(',', array_map('intval', $ids)) . ")" : '';
}

/** Velo entre dos usuarios cualesquiera, en cualquier direccion (para los avisos, donde el «yo» es quien escribe). */
function ghost_pair(PDO $pdo, ?int $a, ?int $b): bool {
    if (!$a || !$b || $a === $b || !ghost_on()) return false;
    try {
        $st = $pdo->prepare("SELECT 1 FROM user_ghosts WHERE (user_id = ? AND target_id = ?) OR (user_id = ? AND target_id = ?) LIMIT 1");
        $st->execute([$a, $b, $b, $a]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) { return false; }
}

/** Alias estable con el que el usuario actual ve a $uid fuera de los hilos (Chat, @menciones, avisos): Apellido-Lugar,
 *  derivado de un HMAC de la pareja. Distinto para cada pareja y cada sentido; no se guarda. */
function ghost_alias(int $uid): string {
    global $DDD_SECRET;
    $n = hexdec(substr(hash_hmac('sha256', 'ghost|' . (int)(user()['user_id'] ?? 0) . '|' . $uid, $DDD_SECRET), 0, 8));
    return DDD_NOMBRES[$n % count(DDD_NOMBRES)] . '-' . DDD_LUGARES[intdiv($n, 997) % count(DDD_LUGARES)];
}

/** Ghostear / deshacer. Nunca a uno mismo. Devuelve false si no procede. */
function ghost_add(PDO $pdo, int $me, int $target): bool {
    if (!ghost_on() || $me <= 0 || $target <= 0 || $me === $target) return false;
    $pdo->prepare("INSERT IGNORE INTO user_ghosts (user_id, target_id) VALUES (?, ?)")->execute([$me, $target]);
    return true;
}
function ghost_remove(PDO $pdo, int $me, int $target): void {
    if (!ghost_on()) return;
    $pdo->prepare("DELETE FROM user_ghosts WHERE user_id = ? AND target_id = ?")->execute([$me, $target]);
}
/** A quien he ghosteado, con fecha, del mas reciente al mas antiguo (para «Mi perfil»). */
function ghost_list(PDO $pdo, int $me): array {
    if (!ghost_on()) return [];
    $st = $pdo->prepare("SELECT g.target_id, g.created, u.login, u.aura, u.avatar FROM user_ghosts g JOIN users u ON u.user_id = g.target_id WHERE g.user_id = ? ORDER BY g.created DESC");
    $st->execute([$me]);
    return $st->fetchAll();
}

/** Enlace «ghostear» junto a un comentario o en un perfil: solo para registrados, sobre otro registrado, con el nombre
 *  ya revelado ($shown) y no velado todavia. $login = nick del otro. '' si no procede. */
function ghost_link(?int $uid, ?string $login, bool $shown, string $cls = 'replink'): string {
    if (!ghost_on() || !user() || !$uid || !$login || !$shown || $uid === (int)user()['user_id'] || ghost_veiled($uid)) return '';
    return "<a class=\"$cls ghostlink\" href=\"/ghostear?u=" . rawurlencode($login) . "\" title=\"" . h(t('dejar de ver a este usuario: para ti y para él seréis siempre Ghost')) . "\">" . t('silenciar') . "</a>";
}
