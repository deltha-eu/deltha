<?php
/** lib/seguir.php — seguir un hilo (migra-192, 11/11/2026). Un registrado marca un hilo (tabla thread_follows) y recibe un
 *  aviso por cada comentario nuevo que se publique en él (tipo thread_comment, se crea en lib/avisos.php desde avisos_comment()).
 *  Con users.follow_own = 1 («Seguir hilos propios», casilla del perfil) los hilos que abre se marcan solos: lo hace
 *  hilo_seguir_propio() en cada sitio que inserta en `stories` (/enviar, /articulo y los envíos de los agentes).
 *  El botón es un POST a /seguir, como /favorito: sin JS vuelve a la página de origen. */
declare(strict_types=1);

/** Ids de los hilos que sigue el usuario actual (una consulta por página, cacheada). */
function hilo_seguidos(): array {
    static $ids = null; global $pdo;
    if ($ids !== null) return $ids;
    $ids = [];
    if (user()) {
        try { $st = $pdo->prepare("SELECT story_id FROM thread_follows WHERE user_id = ?"); $st->execute([user()['user_id']]); $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)); }
        catch (Throwable $e) { /* migra-192 sin aplicar */ }
    }
    return $ids;
}
function hilo_sigo(int $story_id): bool { return in_array($story_id, hilo_seguidos(), true); }

/** Seguir o dejar de seguir un hilo. Nunca lanza: si la tabla aún no existe, la navegación no se resiente. */
function hilo_seguir(PDO $pdo, int $uid, int $sid, bool $on = true): void {
    if ($uid <= 0 || $sid <= 0) return;
    try {
        if ($on) $pdo->prepare("INSERT IGNORE INTO thread_follows (user_id, story_id) VALUES (?, ?)")->execute([$uid, $sid]);
        else     $pdo->prepare("DELETE FROM thread_follows WHERE user_id = ? AND story_id = ?")->execute([$uid, $sid]);
    } catch (Throwable $e) { error_log('seguir hilo: ' . $e->getMessage()); }
}

/** «Seguir hilos propios» del usuario (users.follow_own), por defecto desactivado. Cacheado por usuario y petición. */
function seguir_propios(PDO $pdo, int $uid): bool {
    static $c = [];
    if (isset($c[$uid])) return $c[$uid];
    try { $st = $pdo->prepare("SELECT follow_own FROM users WHERE user_id = ?"); $st->execute([$uid]); return $c[$uid] = (int)$st->fetchColumn() === 1; }
    catch (Throwable $e) { return $c[$uid] = false; }   // migra-192 sin aplicar: la opción está apagada
}

/** Se llama justo después de insertar un hilo: si el autor tiene «Seguir hilos propios», el hilo queda seguido desde ya. */
function hilo_seguir_propio(PDO $pdo, ?int $uid, int $sid): void {
    if ($uid && $uid > 0 && seguir_propios($pdo, $uid)) hilo_seguir($pdo, $uid, $sid);
}

/** Quiénes siguen el hilo (ids de usuario), para avisarles de un comentario nuevo. */
function hilo_seguidores(PDO $pdo, int $sid): array {
    try { $st = $pdo->prepare("SELECT user_id FROM thread_follows WHERE story_id = ?"); $st->execute([$sid]); return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)); }
    catch (Throwable $e) { return []; }
}

/** Al archivarse un hilo deja de aceptar comentarios: se eliminan sus suscripciones, pero se conserva el histórico de avisos. */
function hilo_seguir_limpiar_archivados(PDO $pdo): int {
    try {
        $st = $pdo->query("DELETE tf FROM thread_follows tf JOIN stories s ON s.story_id = tf.story_id WHERE s.status = 'archived'");
        return $st->rowCount();
    } catch (Throwable $e) {
        error_log('limpiar seguimientos de hilos archivados: ' . $e->getMessage());
        return 0;
    }
}

/** Botón «Seguir hilo» / «Siguiendo» de la barra del hilo, solo para registrados; sin JS: un POST a /seguir que vuelve a la página. */
function hilo_seguir_form(array $s): string {
    if (!user() || !in_array($s['status'] ?? '', ['nuevas', 'candidatas', 'destacadas'], true)) return '';
    $id = (int)$s['story_id'];
    $on = hilo_sigo($id);
    return "<form class=\"seguir" . ($on ? ' on' : '') . "\" method=\"post\" action=\"/seguir\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"" . ($on ? 'dejar' : 'seguir') . "\">"
         . "<button title=\"" . h($on ? t('dejar de seguir este hilo') : t('seguir este hilo')) . "\">" . ($on ? t('Siguiendo') : t('Seguir hilo')) . "</button></form>";
}
