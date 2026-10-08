<?php
/** lib/aura.php — el aura mide compromiso: sube con lo que aportas (con tope diario), baja cada dia, nunca por debajo del suelo. */
declare(strict_types=1);

/** Suma aura a un usuario respetando el tope diario. Devuelve lo realmente sumado. */
function aura_add(PDO $pdo, int $user_id, float $amount, string $reason, ?int $ref = null): float {
    if ($amount <= 0) return 0.0;
    $cap = (float)cfg('aura_daily_cap', 2);
    $max = (float)cfg('aura_max', 50);
    // lo que pone el admin a mano (reason «admin…», /editar-usuario) no cuenta para el tope diario
    $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM aura_log WHERE user_id = ? AND amount > 0 AND created >= CURDATE() AND reason NOT LIKE 'admin%'");
    $st->execute([$user_id]);
    $today = (float)$st->fetchColumn();
    $room = max(0.0, $cap - $today);
    $add = round(min($amount, $room), 3);
    if ($add <= 0) return 0.0;
    $st = $pdo->prepare("SELECT aura FROM users WHERE user_id = ?");
    $st->execute([$user_id]);
    $antes = (float)$st->fetchColumn();
    $pdo->prepare("UPDATE users SET aura = LEAST(aura + ?, ?) WHERE user_id = ?")->execute([$add, $max, $user_id]);
    $pdo->prepare("INSERT INTO aura_log (user_id, amount, reason, ref_id) VALUES (?,?,?,?)")->execute([$user_id, $add, $reason, $ref]);
    // destacados (migra-111): cada unidad entera NUEVA que alcanza el aura (6,9 -> 7,0) da un «destacar noticia» (lib/destacar.php).
    // Se compara con el maximo entero ya alcanzado (users.pin_mark): llegar a 6, bajar a 5,5 por el decaimiento y volver a 6 no da otro.
    $tope = (int)floor(min($antes + $add, $max));
    try { $pdo->prepare("UPDATE users SET pin_credits = pin_credits + (? - pin_mark), pin_mark = ? WHERE user_id = ? AND pin_mark < ?")->execute([$tope, $tope, $user_id, $tope]); } catch (Throwable $e) { /* sin migra-111 */ }
    return $add;
}

/** Decaimiento diario: una vez por dia natural, a todos los registrados, sin bajar del suelo. Devuelve filas afectadas o -1 si ya se hizo hoy. */
function aura_daily_decay(PDO $pdo): int {
    $last = cfg('aura_last_decay', '');
    $today = date('Y-m-d');
    if ($last === $today) return -1;
    $dec = (float)cfg('aura_daily_decay', 0.5);
    $floor = (float)cfg('aura_start', 5);
    $pdo->prepare("INSERT INTO config (ckey, cvalue, descr) VALUES ('aura_last_decay', ?, 'Ultimo dia en que se aplico el decaimiento') ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue)")->execute([$today]);
    $st = $pdo->prepare("UPDATE users SET aura = GREATEST(?, aura - ?) WHERE aura > ?");
    $st->execute([$floor, $dec, $floor]);
    $n = $st->rowCount();
    if ($n) $pdo->prepare("INSERT INTO aura_log (user_id, amount, reason) SELECT user_id, ?, 'daily_decay' FROM users WHERE aura > ?")->execute([-$dec, $floor]);
    return $n;
}
