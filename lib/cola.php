<?php
/** lib/cola.php — estimacion del radar (la usan /procesos.php, /radar.php y la columna lateral de escritorio). */
declare(strict_types=1);

/* ---- estimacion: en que ciclo llega a portada cada enlace del radar ---
 * Reproduce la regla del cron (lib/ciclo.php): los ciclos alternan portada (Medios, Substacks, Medios...) y en
 * cada uno se publica, en la portada del turno, el enlace de mayor delta entre los suyos que llevan
 * >= delta_min_age_min y tienen delta >= delta_min_publish. Se asume que los deltas no cambian (no llegan
 * votos): es una estimacion, y asi se dice. Los que no llegan al minimo no tienen ETA. */
/** Instante del proximo ciclo: el cron llama cada minuto y promocionar.php actua
 *  cuando el minuto de la hora es multiplo de cycle_minutes (mismo criterio). */
function proximo_ciclo(): int {
    $cycle = max(1, (int)cfg('cycle_minutes', 5));
    $t = time() - (time() % 60) + 60;                 // siguiente minuto en punto
    while (((int)date('i', $t)) % $cycle !== 0) $t += 60;
    return $t;
}
/** $rows: enlaces del radar ya ordenados por delta DESC, sent_date ASC, con delta_live. Devuelve [story_id => instante]. */
function cola_eta(array $rows): array {
    global $pdo;
    $cycle  = max(1, (int)cfg('cycle_minutes', 5)) * 60;
    $minAge = (int)cfg('delta_min_age_min', 30) * 60;
    $minPub = (float)cfg('delta_min_publish', 1);
    $t = proximo_ciclo();
    $pend = [];
    foreach ($rows as $r) {
        if (($r['content_type'] ?? 'link') === 'article' || (float)$r['delta_live'] < $minPub) continue;
        $pend[portada_de($pdo, $r)][] = $r;
    }
    $eta = []; $guard = 0;
    while (($pend['medios'] ?? []) || ($pend['substacks'] ?? [])) {
        if ($guard++ > 5000) break;
        $p = portada_turno($t);
        foreach ($pend[$p] ?? [] as $i => $r) {
            if (strtotime($r['sent_date']) + $minAge <= $t) {   // la primera elegible = la de mayor delta
                $eta[(int)$r['story_id']] = $t;
                unset($pend[$p][$i]);
                break;
            }
        }
        $t += $cycle;
    }
    return $eta;
}
function eta_txt(int $ts, string $portada = ''): string {
    $m = max(1, (int)ceil(($ts - time()) / 60));
    $p = t(DDD_PORTADAS[$portada] ?? 'Portada');
    if ($m <= max(1, (int)cfg('cycle_minutes', 5))) return t('%s en el próximo ciclo (~%d min)', $p, $m);
    if ($m < 120) return t('%s en ~%d min', $p, $m);
    return t('%s en ~%d h', $p, (int)round($m / 60));
}
/** Linea de estado de un enlace del radar sin estimacion: le falta tiempo o δ. */
function radar_falta(array $s): string {
    $minPub = (float)cfg('delta_min_publish', 1);
    $age = (int)cfg('delta_min_age_min', 30) - intdiv(time() - strtotime($s['sent_date']), 60);
    if (delta_live($s) < $minPub) return t('necesita δ ≥ %s para llegar a portada', num_es($minPub)) . ($age > 0 ? ' · ' . t('elegible en %d min', $age) : '');
    return $age > 0 ? t('elegible en %d min', $age) : t('sin estimación');
}
