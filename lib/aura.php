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
    // (hasta el 09/10/2026 aqui se daban «destacados» por cada unidad entera nueva de aura; ahora destacar se paga en aura: aura_gastar())
    return $add;
}

/** El aura como recurso (migra-132): descuenta $amount al usuario si despues le queda al menos aura_gasto_minimo (por defecto el aura
 *  inicial, 5) y lo apunta en aura_log en negativo con el motivo («destacar», …). Devuelve true si se ha descontado. Una sola UPDATE
 *  con la condicion dentro: dos peticiones a la vez no pueden dejar a nadie por debajo del suelo. No cuenta para el tope diario. */
function aura_gastar(PDO $pdo, int $user_id, float $amount, string $reason, ?int $ref = null): bool {
    $amount = round($amount, 3);
    if ($amount <= 0) return true;
    $st = $pdo->prepare("UPDATE users SET aura = aura - ? WHERE user_id = ? AND aura - ? >= ?");
    $st->execute([$amount, $user_id, $amount, aura_gasto_minimo()]);
    if (!$st->rowCount()) return false;
    $pdo->prepare("INSERT INTO aura_log (user_id, amount, reason, ref_id) VALUES (?,?,?,?)")->execute([$user_id, -$amount, $reason, $ref]);
    return true;
}
/** Devuelve aura gastada (p. ej. un destacado en cola que nunca llego a verse). Sin tope diario ni maximo: es lo suyo. */
function aura_devolver(PDO $pdo, int $user_id, float $amount, string $reason, ?int $ref = null): void {
    $amount = round($amount, 3);
    if ($amount <= 0) return;
    $pdo->prepare("UPDATE users SET aura = aura + ? WHERE user_id = ?")->execute([$amount, $user_id]);
    $pdo->prepare("INSERT INTO aura_log (user_id, amount, reason, ref_id) VALUES (?,?,?,?)")->execute([$user_id, $amount, $reason, $ref]);
}
/** Suelo por debajo del cual no se puede usar aura (config.aura_gasto_minimo; si no, el aura inicial). */
function aura_gasto_minimo(): float { return (float)cfg('aura_gasto_minimo', cfg('aura_start', 5)); }
/** ¿Puede este usuario pagar $amount sin bajar del suelo? (para pintar o no un boton; el descuento real lo decide aura_gastar) */
function aura_puede_gastar(?array $u, float $amount): bool { return $u && (float)$u['aura'] - $amount >= aura_gasto_minimo() - 0.0005; }

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
