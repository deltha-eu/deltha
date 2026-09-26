<?php
/** lib/cola.php — estimacion del radar (la usan /procesos.php, /radar.php y la columna lateral de escritorio). */
declare(strict_types=1);

/* ---- estimacion: en que ciclo llega a portada cada enlace del radar ---
 * Reproduce la regla del cron (lib/ciclo.php): los ciclos alternan portada (Medios, Substacks, Medios...) y en
 * cada uno se publica, en la portada del turno, el enlace de mayor delta entre los suyos que llevan
 * >= delta_min_age_min y tienen delta >= delta_min_publish. Se asume que los deltas no cambian (no llegan
 * votos): es una estimacion, y asi se dice. Los que no llegan al minimo no tienen ETA. */
/** Inicio de la ventana del proximo ciclo pendiente. El minuto exacto de ejecucion dentro de la ventana no se publica (sniping):
 *  a la gente se le dice «antes de las hh:mm» (ciclo_antes_de). Con cycle_lottery la estimacion es ademas orientativa: entre las
 *  que superan el minimo se sortea por δ, asi que aqui se supone que sale la de mayor δ, que es la mas probable. */
function proximo_ciclo(): int { return ciclo_pendiente(); }
/** «antes de las 14:35» del ciclo pendiente. */
function proximo_ciclo_txt(): string { return t('antes de las %s', date('H:i', ciclo_antes_de(proximo_ciclo()))); }
/** $rows: enlaces del radar ya ordenados por delta DESC, sent_date ASC, con delta_live. Devuelve [story_id => instante]. */
function cola_eta(array $rows): array {
    global $pdo;
    $cycle  = max(1, (int)cfg('cycle_minutes', 5)) * 60;
    $minAge = (int)cfg('delta_min_age_min', 30) * 60;
    $t = proximo_ciclo();
    $pend = [];
    foreach ($rows as $r) {
        if (($r['content_type'] ?? 'link') === 'article') continue;
        $p = portada_de($pdo, $r);
        if ((int)$r['eff_pos'] - (int)$r['eff_neg'] < delta_min_votes() || (float)$r['delta_live'] < delta_minpub($p)) continue;
        $pend[$p][] = $r;
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
    $fin = ciclo_antes_de($ts);                       // $ts es el inicio de la ventana; a la gente se le da el final
    $m = max(1, (int)ceil(($fin - time()) / 60));
    $p = t(DDD_PORTADAS[$portada] ?? 'Portada');
    if ($ts <= proximo_ciclo()) return t('%s en el próximo ciclo, antes de las %s', $p, date('H:i', $fin));
    if ($m < 120) return t('%s antes de las %s (~%d min)', $p, date('H:i', $fin), $m);
    return t('%s en ~%d h', $p, (int)round($m / 60));
}
/** Linea de estado de un enlace del radar sin estimacion: le falta tiempo o δ. */
function radar_falta(array $s): string {
    global $pdo;
    $minPub = delta_minpub(portada_de($pdo, $s));
    $age = (int)cfg('delta_min_age_min', 30) - intdiv(time() - strtotime($s['sent_date']), 60);
    $falta = [];
    if ((int)$s['eff_pos'] - (int)$s['eff_neg'] < delta_min_votes()) $falta[] = t('necesita %d votos que cuenten', delta_min_votes());
    if (delta_live($s) < $minPub) $falta[] = t('necesita δ ≥ %s para llegar a portada', num_es($minPub));
    if ($falta) return implode(' · ', $falta) . ($age > 0 ? ' · ' . t('elegible en %d min', $age) : '');
    return $age > 0 ? t('elegible en %d min', $age) : t('sin estimación');
}
