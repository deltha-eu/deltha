<?php
/** lib/ciclo.php — un ciclo de promocion. Devuelve un array de lineas de log. */
declare(strict_types=1);
require_once __DIR__ . '/aura.php';

function run_cycle(PDO $pdo): array {
    $log = [];
    $minAge   = (int)cfg('delta_min_age_min');
    $minPub   = (float)cfg('delta_min_publish');
    $minCand  = (float)cfg('delta_min_candidate', 0.5);
    $ttlHours = (int)cfg('thread_ttl_hours');
    $newTtl   = (int)cfg('nuevas_ttl_hours', 48);

    // 1. nuevas -> candidatas: cada ciclo sube UNA, la de mayor delta
    //    entre las que ya cumplen la antiguedad minima, y solo si su delta llega
    //    a delta_min_candidate: sin votos no se asciende.
    //    En dos pasos (SELECT + UPDATE por id): MySQL 8 no admite ORDER BY/LIMIT
    //    en un UPDATE con JOIN (error 1221); MariaDB si, y por eso funcionaba antes.
    $sel = $pdo->prepare("SELECT s.story_id FROM stories s JOIN topics t ON t.topic_id = s.topic_id
                           WHERE s.status = 'nuevas' AND s.sent_date <= NOW() - INTERVAL ? MINUTE AND " . delta_sql() . " >= ?
                           ORDER BY " . delta_sql() . " DESC, s.sent_date ASC LIMIT 1");
    $sel->execute([$minAge, $minCand]);
    $sube = (int)$sel->fetchColumn();
    if ($sube) {
        $pdo->prepare("UPDATE stories s JOIN topics t ON t.topic_id = s.topic_id
                          SET s.status = 'candidatas', s.delta = " . delta_sql() . ", s.delta_calc = NOW()
                        WHERE s.story_id = ? AND s.status = 'nuevas'")->execute([$sube]);
        $log[] = "candidata nueva: #$sube";
    } else {
        $log[] = "sin nuevas que asciendan (ninguna con >= $minAge min y delta >= $minCand)";
    }
    // 1b. lo que lleva nuevas_ttl_hours sin llegar a portada caduca: nuevas y candidatas se archivan
    if ($newTtl > 0) {
        $c = $pdo->prepare("UPDATE stories SET status='archived' WHERE status IN ('nuevas','candidatas') AND sent_date <= NOW() - INTERVAL ? HOUR");
        $c->execute([$newTtl]);
        if ($c->rowCount()) $log[] = "caducadas sin llegar a portada: {$c->rowCount()}";
    }

    // 2. delta de candidatas
    $pdo->prepare("UPDATE stories s JOIN topics t ON t.topic_id = s.topic_id
                      SET s.delta = " . delta_sql() . ", s.delta_calc = NOW()
                    WHERE s.status = 'candidatas'")->execute();

    // 3. publicar la mejor
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT story_id, user_id, delta,
                                    (SELECT COUNT(*) FROM stories WHERE status='candidatas') AS n_cand,
                                    (SELECT COUNT(DISTINCT COALESCE(user_id, anon_key, comment_id))
                                       FROM comments WHERE story_id = s.story_id AND status='ok') AS commenters
                               FROM stories s
                              WHERE status='candidatas' AND delta >= ?
                              ORDER BY delta DESC, sent_date ASC LIMIT 1 FOR UPDATE");
        $st->execute([$minPub]);
        $best = $st->fetch();
        if ($best) {
            $pdo->prepare("UPDATE stories SET status='destacadas', published_date=NOW() WHERE story_id=?")
                ->execute([$best['story_id']]);
            $pdo->prepare("INSERT INTO promo_log (story_id, delta, candidates, commenters) VALUES (?,?,?,?)")
                ->execute([$best['story_id'], $best['delta'], $best['n_cand'], $best['commenters']]);
            if ($best['user_id'] !== null) aura_add($pdo, (int)$best['user_id'], (float)cfg('aura_portada', 0.5), 'portada', (int)$best['story_id']);
            $log[] = "PUBLICADA #{$best['story_id']} delta={$best['delta']} ({$best['n_cand']} candidatas, {$best['commenters']} comentaristas)";
        } else {
            $log[] = "sin candidata publicable (delta < $minPub)";
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
