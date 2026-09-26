<?php
/** lib/ciclo.php — un ciclo de promocion. Devuelve un array de lineas de log.
 *
 *  Todo lo que se envia entra en el radar (status 'nuevas'). Hay dos portadas, Medios y Substacks, y el ciclo las alterna
 *  (portada_turno de la ventana). En cada ciclo publica UNO en la portada del turno, elegido entre los enlaces de su radar que
 *  llevan al menos delta_min_age_min, tienen al menos delta_min_votes votos netos que cuentan y δ >= delta_min_publish_<portada>:
 *    - cycle_lottery = 1 (por defecto): sorteo ponderado por δ (el doble de δ, el doble de probabilidad). No gana siempre el
 *      maximo: afinar el ultimo voto no garantiza nada y las segundas y terceras tambien respiran.
 *    - cycle_lottery = 0: el de mayor δ (empate: el mas antiguo).
 *  Si a la portada del turno no le llega nada y cycle_cede_turno = 1, el turno pasa a la otra portada (Substacks conserva los
 *  suyos; solo se ceden los vacios). Si tampoco, ese ciclo no se publica nada. Los originales (articulos) no van a portada.
 *  El minuto de ejecucion dentro de cada ventana es imprevisible (ciclo_toca, lib/app.php). */
declare(strict_types=1);
require_once __DIR__ . '/aura.php';

/** Elige entre las candidatas ($rows con delta, sent_date, ya ordenadas por delta DESC, sent_date ASC). */
function ciclo_elige(array $rows): ?array {
    if (!$rows) return null;
    if (cfg('cycle_lottery', '1') !== '1') return $rows[0];
    $w = array_map(fn($r) => max(0.01, (float)$r['delta']), $rows);   // pesos: el δ (todas superan el minimo, que es > 0)
    $x = random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX * array_sum($w);
    foreach ($rows as $i => $r) { $x -= $w[$i]; if ($x <= 0) return $r; }
    return $rows[array_key_last($rows)];
}

function run_cycle(PDO $pdo, ?int $now = null): array {
    $log = [];
    $now      = $now ?? time();
    $minAge   = (int)cfg('delta_min_age_min', 30);
    $ttlHours = (int)cfg('thread_ttl_hours', 48);
    $newTtl   = (int)cfg('nuevas_ttl_hours', 168);
    $turno    = portada_turno(ciclo_ventana($now));

    // 1. lo que lleva nuevas_ttl_hours en el radar sin llegar a portada caduca (se archiva)
    if ($newTtl > 0) {
        $c = $pdo->prepare("UPDATE stories SET status='archived' WHERE status = 'nuevas' AND sent_date <= NOW() - INTERVAL ? HOUR");
        $c->execute([$newTtl]);
        if ($c->rowCount()) $log[] = "caducadas en el radar sin llegar a portada: {$c->rowCount()}";
    }

    // 2. portada de destino, votos que cuentan y delta de todo el radar. La portada se recalcula en cada ciclo: asi da igual por
    //    donde entro el envio (formulario, RSS, edicion del enlace) o si el catalogo de newsletters ha cambiado.
    $rows = $pdo->query("SELECT story_id, content_type, url, source, portada FROM stories WHERE status = 'nuevas'")->fetchAll();
    $upd = $pdo->prepare("UPDATE stories SET portada = ? WHERE story_id = ?");
    foreach ($rows as $r) {
        $p = portada_de($pdo, $r);
        if ($p !== $r['portada']) $upd->execute([$p, $r['story_id']]);
    }
    eff_votes_update($pdo);   // un voto por conexion y envio; las cuentas en cuarentena aun no cuentan
    $pdo->prepare("UPDATE stories s JOIN topics t ON t.topic_id = s.topic_id
                      SET s.delta = " . delta_sql() . ", s.delta_calc = NOW()
                    WHERE s.status = 'nuevas'")->execute();

    // 3. publicar en la portada del turno (o, si esta vacia, en la otra) una de sus candidatas
    //    En dos pasos (SELECT + UPDATE por id): MySQL 8 no admite ORDER BY/LIMIT en un UPDATE con JOIN (error 1221).
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT s.story_id, s.user_id, s.delta, s.sent_date,
                                    (SELECT COUNT(*) FROM stories x WHERE x.status = 'nuevas' AND x.portada = ?) AS n_radar,
                                    (SELECT COUNT(DISTINCT COALESCE(user_id, anon_key, comment_id))
                                       FROM comments WHERE story_id = s.story_id AND status='ok') AS commenters
                               FROM stories s
                              WHERE s.status = 'nuevas' AND s.portada = ? AND s.content_type = 'link'
                                AND s.sent_date <= NOW() - INTERVAL ? MINUTE AND (s.eff_pos - s.eff_neg) >= ? AND s.delta >= ?
                              ORDER BY s.delta DESC, s.sent_date ASC LIMIT 200 FOR UPDATE");
        $orden = [$turno];
        if (cfg('cycle_cede_turno', '1') === '1') $orden[] = $turno === 'medios' ? 'substacks' : 'medios';
        $best = null; $donde = $turno; $cands = [];
        foreach ($orden as $p) {
            $st->execute([$p, $p, $minAge, delta_min_votes(), delta_minpub($p)]);
            $cands = $st->fetchAll();
            if ($cands) { $best = ciclo_elige($cands); $donde = $p; break; }
            $log[] = "turno de $p: nada publicable (ninguna con >= $minAge min, >= " . delta_min_votes() . " votos que cuenten y delta >= " . num_es(delta_minpub($p)) . ")" . ($p === $turno && count($orden) > 1 ? " → se cede el turno" : "");
        }
        if ($best) {
            $pdo->prepare("UPDATE stories SET status='destacadas', published_date=NOW() WHERE story_id=? AND status='nuevas'")
                ->execute([$best['story_id']]);
            $pdo->prepare("INSERT INTO promo_log (story_id, portada, delta, candidates, commenters) VALUES (?,?,?,?,?)")
                ->execute([$best['story_id'], $donde, $best['delta'], $best['n_radar'], $best['commenters']]);
            if ($best['user_id'] !== null) aura_add($pdo, (int)$best['user_id'], (float)cfg('aura_portada', 0.5), 'portada', (int)$best['story_id']);
            $como = cfg('cycle_lottery', '1') === '1' ? "sorteo entre " . count($cands) . ", δ máx " . num_es((float)$cands[0]['delta']) : "la de mayor δ";
            $log[] = "PUBLICADA en $donde" . ($donde !== $turno ? " (turno cedido por $turno)" : "") . " #{$best['story_id']} delta={$best['delta']} ($como; {$best['n_radar']} en su radar, {$best['commenters']} comentaristas)";
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
