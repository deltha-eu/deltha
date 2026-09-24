<?php
/** lib/ciclo.php — un ciclo de promocion. Devuelve un array de lineas de log.
 *
 *  Todo lo que se envia entra en el radar (status 'nuevas'). Hay dos portadas, Medios y Substacks, y el ciclo
 *  las alterna: en cada ciclo publica, en la portada a la que le toca (portada_turno), el enlace del radar de
 *  mayor δ entre los de esa portada que llevan al menos delta_min_age_min y tienen δ >= delta_min_publish.
 *  Si ninguno llega, ese ciclo no se publica nada. Los originales (articulos) no van a ninguna portada. */
declare(strict_types=1);
require_once __DIR__ . '/aura.php';

function run_cycle(PDO $pdo, ?int $now = null): array {
    $log = [];
    $now      = $now ?? time();
    $minAge   = (int)cfg('delta_min_age_min', 30);
    $minPub   = (float)cfg('delta_min_publish', 1);
    $ttlHours = (int)cfg('thread_ttl_hours', 48);
    $newTtl   = (int)cfg('nuevas_ttl_hours', 48);
    $turno    = portada_turno($now - ($now % 60));

    // 1. lo que lleva nuevas_ttl_hours en el radar sin llegar a portada caduca (se archiva)
    if ($newTtl > 0) {
        $c = $pdo->prepare("UPDATE stories SET status='archived' WHERE status = 'nuevas' AND sent_date <= NOW() - INTERVAL ? HOUR");
        $c->execute([$newTtl]);
        if ($c->rowCount()) $log[] = "caducadas en el radar sin llegar a portada: {$c->rowCount()}";
    }

    // 2. portada de destino y delta de todo el radar. La portada se recalcula en cada ciclo: asi da igual por donde
    //    entro el envio (formulario, RSS, edicion del enlace) o si el catalogo de newsletters ha cambiado.
    $rows = $pdo->query("SELECT story_id, content_type, url, source, portada FROM stories WHERE status = 'nuevas'")->fetchAll();
    $upd = $pdo->prepare("UPDATE stories SET portada = ? WHERE story_id = ?");
    foreach ($rows as $r) {
        $p = portada_de($pdo, $r);
        if ($p !== $r['portada']) $upd->execute([$p, $r['story_id']]);
    }
    $pdo->prepare("UPDATE stories s JOIN topics t ON t.topic_id = s.topic_id
                      SET s.delta = " . delta_sql() . ", s.delta_calc = NOW()
                    WHERE s.status = 'nuevas'")->execute();

    // 3. publicar en la portada del turno la mejor de su radar
    //    En dos pasos (SELECT + UPDATE por id): MySQL 8 no admite ORDER BY/LIMIT en un UPDATE con JOIN (error 1221).
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT s.story_id, s.user_id, s.delta,
                                    (SELECT COUNT(*) FROM stories x WHERE x.status = 'nuevas' AND x.portada = ?) AS n_radar,
                                    (SELECT COUNT(DISTINCT COALESCE(user_id, anon_key, comment_id))
                                       FROM comments WHERE story_id = s.story_id AND status='ok') AS commenters
                               FROM stories s
                              WHERE s.status = 'nuevas' AND s.portada = ? AND s.content_type = 'link'
                                AND s.sent_date <= NOW() - INTERVAL ? MINUTE AND s.delta >= ?
                              ORDER BY s.delta DESC, s.sent_date ASC LIMIT 1 FOR UPDATE");
        $st->execute([$turno, $turno, $minAge, $minPub]);
        $best = $st->fetch();
        if ($best) {
            $pdo->prepare("UPDATE stories SET status='destacadas', published_date=NOW() WHERE story_id=? AND status='nuevas'")
                ->execute([$best['story_id']]);
            $pdo->prepare("INSERT INTO promo_log (story_id, portada, delta, candidates, commenters) VALUES (?,?,?,?,?)")
                ->execute([$best['story_id'], $turno, $best['delta'], $best['n_radar'], $best['commenters']]);
            if ($best['user_id'] !== null) aura_add($pdo, (int)$best['user_id'], (float)cfg('aura_portada', 0.5), 'portada', (int)$best['story_id']);
            $log[] = "PUBLICADA en $turno #{$best['story_id']} delta={$best['delta']} ({$best['n_radar']} en su radar, {$best['commenters']} comentaristas)";
        } else {
            $log[] = "turno de $turno: nada publicable (ninguna con >= $minAge min y delta >= $minPub)";
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        $log[] = "ERROR: " . $e->getMessage();
    }

    // 4. revelado sigiloso: cada envio muestra su nombre a los ghost_minutes de enviarse
    $r = $pdo->prepare("UPDATE stories SET revealed=1
                         WHERE revealed=0 AND sent_date <= NOW() - INTERVAL ? MINUTE");
    $r->execute([(int)cfg('ghost_minutes', 30)]);
    if ($r->rowCount()) $log[] = "revelados: {$r->rowCount()}";

    // 5. archivar hilos que cumplen su vida
    $a = $pdo->prepare("UPDATE stories SET status='archived'
                         WHERE status='destacadas' AND published_date <= NOW() - INTERVAL ? HOUR");
    $a->execute([$ttlHours]);
    if ($a->rowCount()) $log[] = "archivadas: {$a->rowCount()}";

    // 6. decaimiento diario del aura
    $d = aura_daily_decay($pdo);
    if ($d >= 0) $log[] = "decaimiento diario de aura aplicado ($d usuarios)";

    return $log;
}
