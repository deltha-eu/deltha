<?php
/**
 * lib/usuarios.php — ficha de moderación de un usuario, la misma en su perfil público (/u.php, desplegable «Editar este perfil»)
 * y en /editar-usuario.php, que es quien guarda.
 *   Moderadores (sobre usuarios que no moderan) y admin (sobre cualquiera): nombre de usuario, frase y foto.
 *   Solo admin: correo, correo validado, nueva contraseña, nivel (normal / moderador / admin; el suyo propio no se toca) y aura
 *   a mano, con motivo; el cambio de aura queda en aura_log con reason «admin: motivo» y ref_id = el admin, y no cuenta para el
 *   tope diario de aura_add(). Modera también, sin nivel, quien tenga aura >= config.mod_aura (user_is_mod(), lib/app.php).
 *   Solo admin, ademas: la seccion «Agente» (lib/agente.php): marcar la cuenta como bot comentarista, su personalidad y parametros.
 */
declare(strict_types=1);
require_once __DIR__ . '/agente.php';   // seccion «Agente» de la ficha (agent_form) y guardado (agent_from_post)

/** ¿Puede el usuario actual editar la ficha de $p? El admin, a cualquiera (también a sí mismo); un moderador, a quien no modere (por nivel ni por aura) y no sea él. */
function modedit_puede(array $p): bool {
    $me = user();
    if (!$me || !is_mod()) return false;
    if (is_admin()) return true;
    return !user_is_mod($p) && (int)$me['user_id'] !== (int)$p['user_id'];
}

/** Formulario de la ficha. $p: fila de users con user_id, login, email, level, aura, avatar, bio, verified. $back: 'u' = volver al perfil. */
function modedit_form(PDO $pdo, array $p, string $back = ''): string {
    $id = (int)$p['user_id'];
    $h = "<form class=\"plain modedit\" method=\"post\" action=\"/editar-usuario.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"id\" value=\"$id\">"
       . ($back !== '' ? "<input type=\"hidden\" name=\"back\" value=\"" . h($back) . "\">" : "");
    $h .= "<label>Nombre de usuario</label><input type=\"text\" name=\"login\" required pattern=\"[A-Za-z0-9_\\-]{3,32}\" value=\"" . h($p['login']) . "\" autocomplete=\"off\">";
    $h .= "<label>Frase (hasta 160 caracteres)</label><input type=\"text\" name=\"bio\" maxlength=\"160\" value=\"" . h($p['bio'] ?? '') . "\">";
    $h .= "<label>Foto</label><input type=\"file\" name=\"photo\" accept=\"image/*\">";
    if ($p['avatar']) $h .= "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"> quitar la foto actual</label>";
    if (is_admin()) {
        $max = (float)cfg('aura_max', 50);
        $h .= "<label>Aura</label><div class=\"aurarow\"><input type=\"number\" name=\"aura\" min=\"0\" max=\"" . h((string)$max) . "\" step=\"0.001\" value=\"" . h(rtrim(rtrim(number_format((float)$p['aura'], 3, '.', ''), '0'), '.')) . "\">"
            . "<input type=\"text\" name=\"aura_motivo\" maxlength=\"50\" placeholder=\"motivo del cambio (queda en el historial)\"></div>"
            . "<small class=\"hint\">Entre 0 y " . h(num_es($max)) . " (aura_max). Sigue bajando cada día hasta " . h(num_es(cfg('aura_start', 5))) . " como la de todos.</small>";
        $modaura = (float)cfg('mod_aura', 20);
        if ((int)user()['user_id'] === $id) $h .= "<label>Nivel</label><p class=\"meta\" style=\"padding:0\">admin <small>(el tuyo lo cambia otro admin)</small></p>";
        else {
            $h .= "<label>Nivel</label><select name=\"level\">";
            foreach (['normal' => 'normal', 'mod' => 'moderador', 'admin' => 'admin'] as $v => $lbl) $h .= "<option value=\"$v\"" . ($p['level'] === $v ? ' selected' : '') . ">$lbl</option>";
            $h .= "</select>";
            $h .= "<small class=\"hint\">El admin lo puede todo (admin, usuarios, config); el moderador, la cola de moderación. "
                . ($modaura > 0 ? "Con aura ≥ " . h(num_es($modaura)) . " (mod_aura) cualquier cuenta modera aunque su nivel sea normal" . (mod_origen($p) === 'aura' ? " — es el caso de esta" : "") . "." : "") . "</small>";
        }
        $h .= "<label>Correo</label><input type=\"email\" name=\"email\" required value=\"" . h($p['email']) . "\" autocomplete=\"off\">";
        $h .= "<label><input type=\"checkbox\" name=\"verified\" value=\"1\"" . ((int)$p['verified'] ? ' checked' : '') . "> correo validado (sin marcar, no puede iniciar sesión)</label>";
        $h .= "<label>Nueva contraseña (vacío = no cambiar)</label><input type=\"password\" name=\"pass\" data-pw minlength=\"8\" autocomplete=\"new-password\" placeholder=\"mínimo 8 caracteres\">";
        $h .= "<label>Repite la nueva contraseña</label><input type=\"password\" name=\"pass2\" data-pw minlength=\"8\" autocomplete=\"new-password\">";
        $h .= pw_toggle();
    } else {
        $h .= "<label>Correo</label><p class=\"meta\" style=\"padding:0\">" . h($p['email']) . ((int)$p['verified'] ? '' : ' (sin validar)') . " <small>(solo lo cambia el admin)</small></p>";
    }
    if (is_admin()) $h .= agent_form($pdo, $id, agent_get($pdo, $id));   // «esta cuenta es un agente» + personalidad y parametros (lib/agente.php)
    $h .= "<button>Guardar</button></form>";
    if (is_admin()) {
        // historial reciente de aura (sin el decaimiento diario, que es igual para todos)
        $lg = $pdo->prepare("SELECT l.amount, l.reason, l.created, u.login FROM aura_log l LEFT JOIN users u ON u.user_id = l.ref_id AND l.reason LIKE 'admin%' WHERE l.user_id = ? AND l.reason <> 'daily_decay' ORDER BY l.id DESC LIMIT 12");
        $lg->execute([$id]);
        if ($rows = $lg->fetchAll()) {
            $h .= "<details class=\"auralog\"><summary>Historial de aura (últimos movimientos)</summary><table>";
            foreach ($rows as $r) $h .= "<tr><td class=\"num\">" . ((float)$r['amount'] > 0 ? '+' : '') . h(num_es($r['amount'])) . "</td><td>" . h($r['reason']) . ($r['login'] ? " <small>(" . h($r['login']) . ")</small>" : '') . "</td><td><small>" . h(hace($r['created'])) . "</small></td></tr>";
            $h .= "</table></details>";
        }
        if ($p['level'] !== 'admin') $h .= "<p class=\"meta modedit-more\">Borrar la cuenta: <a href=\"/admin.php?t=usuarios&amp;q=" . rawurlencode($p['login']) . "\">en admin</a> (pide confirmación).</p>";
    }
    return $h;
}

/* ---- alta de usuarios por el admin (/crear-usuario.php; formulario en /usuarios.php y en admin > Usuarios) ----------------
 * Sin pasar por /registro.php: no comprueba el dominio del correo ni el tope por IP. Con «correo validado» la cuenta entra ya;
 * sin marcar, se envia el correo de activacion normal. */

/** Crea la cuenta. Devuelve [user_id, null] o [0, 'error']. $level: normal | mod | admin. */
function user_create(PDO $pdo, string $login, string $email, string $pass, string $level = 'normal', ?float $aura = null, bool $verified = true): array {
    $login = trim($login); $email = mb_strtolower(trim($email));
    if (!preg_match('/^[a-z0-9_\-]{3,32}$/i', $login)) return [0, 'Usuario: 3–32 caracteres, letras, números, guion o guion bajo.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 128) return [0, 'El correo no es válido.'];
    if (strlen($pass) < 8) return [0, 'La contraseña debe tener al menos 8 caracteres.'];
    $q = $pdo->prepare("SELECT login, email FROM users WHERE login = ? OR email = ? LIMIT 1"); $q->execute([$login, $email]);
    if ($r = $q->fetch()) return [0, strcasecmp((string)$r['login'], $login) === 0 ? 'Ese nombre de usuario ya existe.' : 'Ese correo ya lo usa otra cuenta.'];
    $level = in_array($level, ['mod', 'admin'], true) ? $level : 'normal';
    $aura = $aura === null ? (float)cfg('aura_start', 5) : max(0.0, min((float)cfg('aura_max', 50), $aura));
    try {
        $pdo->prepare("INSERT INTO users (login, email, pass, verified, aura, level) VALUES (?,?,?,?,?,?)")
            ->execute([$login, $email, password_hash($pass, PASSWORD_DEFAULT), $verified ? 1 : 0, round($aura, 3), $level]);
    } catch (PDOException $e) { return [0, 'Ese usuario o correo ya existe.']; }
    return [(int)$pdo->lastInsertId(), null];
}

/** Formulario de alta (solo admin). $back: 'usuarios' | 'admin' = adonde vuelve si hay error. */
function user_create_form(string $back): string {
    if (!is_admin()) return '';
    $open = isset($_GET['nuevo_err']) ? ' open' : '';
    $h = "<details class=\"editbox newuser\" id=\"nuevo-usuario\"$open><summary>+ Crear un usuario</summary>";
    if (isset($_GET['nuevo_err'])) $h .= "<p class=\"error\">" . h((string)$_GET['nuevo_err']) . "</p>";
    $h .= "<form class=\"plain\" method=\"post\" action=\"/crear-usuario.php\"><input type=\"hidden\" name=\"back\" value=\"" . h($back) . "\">";
    $h .= "<label>Nombre de usuario</label><input type=\"text\" name=\"login\" required pattern=\"[A-Za-z0-9_\\-]{3,32}\" autocomplete=\"off\" value=\"" . h((string)($_GET['nl'] ?? '')) . "\">";
    $h .= "<label>Correo</label><input type=\"email\" name=\"email\" required autocomplete=\"off\" value=\"" . h((string)($_GET['ne'] ?? '')) . "\"><small class=\"hint\">Obligatorio y único. Si la cuenta no va a recibir correo (una cuenta de revisión, por ejemplo), vale uno inventado y dejar marcado «correo validado».</small>";
    $h .= "<label>Contraseña</label><input type=\"password\" name=\"pass\" data-pw required minlength=\"8\" autocomplete=\"new-password\" placeholder=\"mínimo 8 caracteres\">";
    $h .= pw_toggle();
    $h .= "<label>Nivel</label><select name=\"level\"><option value=\"normal\">normal</option><option value=\"mod\">moderador</option><option value=\"admin\">admin</option></select>";
    $h .= "<label>Aura inicial</label><input type=\"number\" name=\"aura\" min=\"0\" max=\"" . h((string)cfg('aura_max', 50)) . "\" step=\"0.001\" value=\"" . h((string)cfg('aura_start', 5)) . "\">";
    $h .= "<label><input type=\"checkbox\" name=\"verified\" value=\"1\" checked> correo validado (entra ya; sin marcar, se le envía el correo de activación)</label>";
    $h .= "<button>Crear usuario</button></form></details>";
    return $h;
}

