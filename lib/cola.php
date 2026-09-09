<?php
/** lib/cola.php — estimacion de la cola de Nuevas (la usan /procesos.php y la columna lateral de escritorio). */
declare(strict_types=1);

/* ---- estimacion: en que ciclo asciende cada nueva -------------------
 * Reproduce la regla del cron (lib/ciclo.php): en cada ciclo sube UNA, la de
 * mayor delta entre las que llevan >= delta_min_age_min y tienen delta >=
 * delta_min_candidate. Se asume que los deltas no cambian (no llegan votos):
 * es una estimacion, y asi se dice. Las que no llegan al minimo no tienen ETA. */
/** Instante del proximo ciclo: el cron llama cada minuto y promocionar.php actua
 *  cuando el minuto de la hora es multiplo de cycle_minutes (mismo criterio). */
function proximo_ciclo(): int {
    $cycle = max(1, (int)cfg('cycle_minutes', 5));
    $t = time() - (time() % 60) + 60;                 // siguiente minuto en punto
    while (((int)date('i', $t)) % $cycle !== 0) $t += 60;
    return $t;
}
function cola_eta(array $rows): array {
    $cycle  = max(1, (int)cfg('cycle_minutes', 5)) * 60;
    $minAge = (int)cfg('delta_min_age_min', 30) * 60;
    $minCand = (float)cfg('delta_min_candidate', 0.5);
    $t = proximo_ciclo();
    $pend = array_values(array_filter($rows, fn($r) => (float)$r['delta_live'] >= $minCand));   // ya vienen por delta DESC, sent_date ASC
    $eta = []; $guard = 0;
    while ($pend && $guard++ < 5000) {
        foreach ($pend as $i => $r) {
            if (strtotime($r['sent_date']) + $minAge <= $t) {   // la primera elegible = la de mayor delta
                $eta[(int)$r['story_id']] = $t;
                unset($pend[$i]);
                break;
            }
        }
        $t += $cycle;
    }
    return $eta;
}
function eta_txt(int $ts): string {
    $m = max(1, (int)ceil(($ts - time()) / 60));
    if ($m <= max(1, (int)cfg('cycle_minutes', 5))) return t('candidata en el próximo ciclo (~%d min)', $m);
    if ($m < 120) return t('candidata en ~%d min', $m);
    return t('candidata en ~%d h', (int)round($m / 60));
}

