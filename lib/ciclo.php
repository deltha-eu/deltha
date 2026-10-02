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
 *  CANALES (YouTube, migra-106) no entra en la alternancia: turno condicionado. Si han pasado canales_gap_min desde su ultima
 *  publicacion y tiene algun video publicable, el ciclo es suyo; si no, el ciclo sigue siendo de la portada del turno.
 *  MODO TEST (config.test_mode = 1, admin -> Ciclo): si el ciclo normal no publica nada y la ultima promocion tiene mas de
 *  TEST_MODE_MINUTES (60), publica de todos modos el enlace del radar con mas δ (primero la portada del turno, luego la otra),
 *  haya o no alcanzado la edad, los votos y el δ minimos. Para la beta, donde no hay gente para llenar la portada. Canales lleva
 *  en modo test su propio reloj: si lleva mas de TEST_MODE_MINUTES sin publicar y tiene videos en el radar, el ciclo es suyo.
 *  El minuto de ejecucion dentro de cada ventana es imprevisible (ciclo_toca, lib/app.php). */
declare(strict_types=1);
require_once __DIR__ . '/aura.php';

const TEST_MODE_MINUTES = 60;   // modo test: minutos sin promocion tras los que se publica el de mas δ aunque no llegue al umbral

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
        // modo test: la de mayor δ de una portada, sin umbrales
        $sf = $pdo->prepare("SELECT s.story_id, s.user_id, s.delta, s.sent_date,
                                    (SELECT COUNT(*) FROM stories x WHERE x.status = 'nuevas' AND x.portada = ?) AS n_radar,
                                    (SELECT COUNT(DISTINCT COALESCE(user_id, anon_key, comment_id))
                                       FROM comments WHERE story_id = s.story_id AND status='ok') AS commenters
                               FROM stories s
                              WHERE s.status = 'nuevas' AND s.portada = ? AND s.content_type = 'link'
                              ORDER BY s.delta DESC, s.sent_date ASC LIMIT 1 FOR UPDATE");
        $orden = [$turno];
        if (cfg('cycle_cede_turno', '1') === '1') $orden[] = $turno === 'medios' ? 'substacks' : 'medios';
        $best = null; $donde = $turno; $cands = []; $test = false;
        // Canales: va primero solo si ya ha pasado su intervalo; si no tiene nada publicable, no gasta el ciclo
        if (portada_canales_on()) {
            $uc = $pdo->query("SELECT MAX(created) FROM promo_log WHERE portada = 'canales'")->fetchColumn();
            $haceC = $uc ? intdiv($now - strtotime((string)$uc), 60) : PHP_INT_MAX;
            if (!$uc || $now - strtotime((string)$uc) >= canales_gap() * 60 - 30) array_unshift($orden, 'canales');
            // modo test: Canales lleva su propio reloj. Sin votos (beta) nunca tiene un video publicable, y el modo test general no
            // llega a ella: solo actua cuando el ciclo normal no publica nada, y Medios y Substacks casi siempre tienen algo. Si lleva
            // mas de TEST_MODE_MINUTES sin publicar y tiene videos en el radar, el ciclo es suyo y sale el de mas δ, sin umbrales.
            if (cfg('test_mode', '0') === '1' && $haceC >= TEST_MODE_MINUTES) {
                $sf->execute(['canales', 'canales']);
                $cands = $sf->fetchAll();
                if ($cands) { $best = $cands[0]; $donde = 'canales'; $test = true; }
            }
        }
        if (!$best) foreach ($orden as $p) {
            $st->execute([$p, $p, $minAge, delta_min_votes(), delta_minpub($p)]);
            $cands = $st->fetchAll();
            if ($cands) { $best = ciclo_elige($cands); $donde = $p; break; }
            if ($p === 'canales') continue;   // sin video publicable: el ciclo sigue siendo de la portada del turno, sin mas
            $log[] = "turno de $p: nada publicable (ninguna con >= $minAge min, >= " . delta_min_votes() . " votos que cuenten y delta >= " . num_es(delta_minpub($p)) . ")" . ($p === $turno && count($orden) > 1 ? " → se cede el turno" : "");
        }
        // modo test: sin nada publicable y con mas de TEST_MODE_MINUTES desde la ultima promocion, sale el de mas δ, sin umbrales
        if (!$best && cfg('test_mode', '0') === '1') {
            $ultima = $pdo->query("SELECT MAX(created) FROM promo_log")->fetchColumn();
            $hace   = $ultima ? intdiv($now - strtotime((string)$ultima), 60) : PHP_INT_MAX;
            if ($hace >= TEST_MODE_MINUTES) {
                foreach (array_merge([$turno, $turno === 'medios' ? 'substacks' : 'medios'], portada_canales_on() ? ['canales'] : []) as $p) {
                    $sf->execute([$p, $p]);
                    $cands = $sf->fetchAll();
                    if ($cands) { $best = $cands[0]; $donde = $p; $test = true; break; }
                }
                if (!$best) $log[] = "modo test: el radar esta vacio, nada que publicar";
            } else {
                $log[] = "modo test: la ultima promocion fue hace $hace min (< " . TEST_MODE_MINUTES . "), no se fuerza nada";
            }
        }
        if ($best) {
            $pdo->prepare("UPDATE stories SET status='destacadas', published_date=NOW() WHERE story_id=? AND status='nuevas'")
                ->execute([$best['story_id']]);
            $pdo->prepare("INSERT INTO promo_log (story_id, portada, delta, candidates, commenters) VALUES (?,?,?,?,?)")
                ->execute([$best['story_id'], $donde, $best['delta'], $best['n_radar'], $best['commenters']]);
            if ($best['user_id'] !== null) aura_add($pdo, (int)$best['user_id'], (float)cfg('aura_portada', 0.5), 'portada', (int)$best['story_id']);
            $como = $test ? "MODO TEST: la de mayor δ sin umbrales, " . TEST_MODE_MINUTES . " min sin promocion"
                  : (cfg('cycle_lottery', '1') === '1' ? "sorteo entre " . count($cands) . ", δ máx " . num_es((float)$cands[0]['delta']) : "la de mayor δ");
            $log[] = "PUBLICADA en $donde" . ($donde === 'canales' ? " (turno condicionado)" : ($donde !== $turno ? " (turno cedido por $turno)" : "")) . " #{$best['story_id']} delta={$best['delta']} ($como; {$best['n_radar']} en su radar, {$best['commenters']} comentaristas)";
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
