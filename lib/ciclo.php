<?php
/** lib/ciclo.php — un ciclo de promocion. Devuelve un array de lineas de log. */
declare(strict_types=1);
require_once __DIR__ . '/aura.php';

function run_cycle(PDO $pdo): array {
    $log = [];
    $minAge   = (int)cfg('delta_min_age_min');
    $minPub   = (float)cfg('delta_min_publish');
    $ttlHours = (int)cfg('thread_ttl_hours');

    // 1. nuevas -> candidatas: cada ciclo sube UNA, la de mayor delta
    //    entre las que ya cumplen la antiguedad minima
    $n = $pdo->prepare("UPDATE stories s JOIN topics t ON t.topic_id = s.topic_id
                          SET s.status = 'candidatas', s.delta = " . delta_sql() . ", s.delta_calc = NOW()
                        WHERE s.status = 'nuevas' AND s.sent_date <= NOW() - INTERVAL ? MINUTE
                        ORDER BY " . delta_sql() . " DESC, s.sent_date ASC LIMIT 1");
    $n->execute([$minAge]);
    if ($n->rowCount()) $log[] = "candidata nueva: 1";
    else $log[] = "sin nuevas que asciendan (ninguna con >= $minAge min)";

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
