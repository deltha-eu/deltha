<?php
/** lib/twofa.php — verificacion en dos pasos por correo (migra-095).
 *  Quien la activa en su perfil (users.twofa = 1) o es administrador con config.twofa_admin = 1, tras acertar usuario y
 *  contraseña recibe un codigo de seis cifras en su correo y lo escribe en /login2; hasta entonces la sesion solo guarda
 *  pending_uid (no uid), asi que no ha entrado. El codigo caduca a los twofa_minutes (10), admite TWOFA_TRIES intentos y se
 *  guarda como HMAC con el secreto del sitio (login_codes, una fila por usuario). Como mucho TWOFA_SENDS envios por hora.
 *  Sin aplicacion de codigos ni codigos de recuperacion: si alguien pierde el acceso, lo recupera con su propio correo.
 *  Urgencia (el correo no llega y un admin no puede entrar):  UPDATE users SET twofa = 0 WHERE login = '…';  y/o
 *  UPDATE config SET cvalue = '0' WHERE ckey = 'twofa_admin'; */
declare(strict_types=1);
require_once __DIR__ . '/mail.php';

const TWOFA_TRIES = 5;   // intentos por codigo
const TWOFA_SENDS = 5;   // codigos enviados por hora y usuario

/** Datos del usuario para decidir si se le pide codigo; null si la migracion aun no esta aplicada (entonces no se pide). */
function twofa_row(PDO $pdo, int $uid): ?array {
    try { $st = $pdo->prepare("SELECT user_id, email, level, twofa FROM users WHERE user_id = ?"); $st->execute([$uid]); return $st->fetch() ?: null; }
    catch (Throwable $e) { return null; }
}
/** ¿Tiene un correo al que se le pueda escribir? (el admin creado por install.sh lleva «nick@localhost») */
function twofa_email_ok(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/@localhost$/i', $email) && str_contains((string)substr(strrchr($email, '@') ?: '', 1), '.');
}
/** ¿Se le exige a todos los administradores? (config.twofa_admin, apagado de serie: se enciende cuando conste que el correo llega) */
function twofa_admin_forced(): bool { return (string)cfg('twofa_admin', '0') === '1'; }
/** ¿A este usuario hay que pedirle el codigo al entrar? Sin correo valido nunca (no podria recibirlo). */
function twofa_needed(?array $r): bool {
    if (!$r || !twofa_email_ok((string)$r['email'])) return false;
    return (int)$r['twofa'] === 1 || ($r['level'] === 'admin' && twofa_admin_forced());
}
function twofa_hash(int $uid, string $code): string { global $DDD_SECRET; return hash_hmac('sha256', "2fa|$uid|$code", $DDD_SECRET); }
/** Correo con la parte local tapada, para decir adonde se envio sin enseñarlo entero: a***@dominio.es */
function twofa_mask(string $email): string {
    [$l, $d] = array_pad(explode('@', $email, 2), 2, '');
    return mb_substr($l, 0, 1) . '***@' . $d;
}
/** Genera y envia un codigo nuevo. 'ok' | 'wait' (hay uno de hace menos de un minuto: vale ese) | 'limit' (demasiados en una hora) | 'fail' (el correo no salio). */
function twofa_send(PDO $pdo, int $uid, string $email): string {
    $st = $pdo->prepare("SELECT sent, sends, expires FROM login_codes WHERE user_id = ?"); $st->execute([$uid]);
    $prev = $st->fetch();
    $sends = 0;
    if ($prev) {
        $hace = time() - strtotime((string)$prev['sent']);
        if ($hace < 60 && strtotime((string)$prev['expires']) > time()) return 'wait';
        $sends = $hace < 3600 ? (int)$prev['sends'] : 0;
        if ($sends >= TWOFA_SENDS) return 'limit';
    }
    $min = max(2, (int)cfg('twofa_minutes', 10));
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $pdo->prepare("REPLACE INTO login_codes (user_id, code_hash, expires, tries, sent, sends) VALUES (?, ?, NOW() + INTERVAL $min MINUTE, 0, NOW(), ?)")->execute([$uid, twofa_hash($uid, $code), $sends + 1]);
    $site = (string)cfg('site_name', 'Deltha');
    $body = t("Hola.\n\nTu código para entrar en %s es:\n\n%s\n\nCaduca en %d minutos. Si no eres tú quien intenta entrar, no lo compartas con nadie.\n\n— %s\n", $site, $code, $min, $site);
    return send_mail($email, t('Tu código para entrar en %s: %s', $site, $code), $body) ? 'ok' : 'fail';
}
/** Comprueba el codigo. 'ok' (y lo gasta) | 'bad' | 'gone' (caducado, agotados los intentos o no hay ninguno). */
function twofa_check(PDO $pdo, int $uid, string $code): string {
    $code = preg_replace('/\D/', '', $code);
    $st = $pdo->prepare("SELECT code_hash, tries, expires FROM login_codes WHERE user_id = ?"); $st->execute([$uid]);
    $r = $st->fetch();
    if (!$r || strtotime((string)$r['expires']) < time() || (int)$r['tries'] >= TWOFA_TRIES) return 'gone';
    if (strlen($code) === 6 && hash_equals((string)$r['code_hash'], twofa_hash($uid, $code))) {
        $pdo->prepare("UPDATE login_codes SET expires = NOW() - INTERVAL 1 SECOND WHERE user_id = ?")->execute([$uid]);   // de un solo uso; la fila se queda para el limite de envios
        return 'ok';
    }
    $pdo->prepare("UPDATE login_codes SET tries = tries + 1 WHERE user_id = ?")->execute([$uid]);
    return (int)$r['tries'] + 1 >= TWOFA_TRIES ? 'gone' : 'bad';
}
/** Abre la sesion del usuario y lo lleva a su pagina de entrada (lo comparten login.php y login2.php). */
function login_finish(PDO $pdo, int $uid): never {
    session_arranca();   // dura session_days (lib/app.php), no hasta cerrar el navegador
    session_regenerate_id(true);
    unset($_SESSION['pending_uid'], $_SESSION['pending_at']);
    $_SESSION['uid'] = $uid;
    if (($_COOKIE['ddd_srclang'] ?? '') === '1') {   // marco «Solo fuentes en mi idioma» antes de entrar: pasa a su cuenta
        $pdo->prepare("UPDATE users SET src_mylang = 1 WHERE user_id = ?")->execute([$uid]);
        pref_cookie('ddd_srclang', '');
    }
    $lu = $pdo->prepare("SELECT landing FROM users WHERE user_id = ?"); $lu->execute([$uid]);
    redirect(landing_url($pdo, ['landing' => (string)$lu->fetchColumn()]));
}
