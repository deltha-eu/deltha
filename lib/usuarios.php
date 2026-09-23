<?php
/**
 * lib/usuarios.php — ficha de moderación de un usuario, la misma en su perfil público (/u.php, desplegable «Editar este perfil»)
 * y en /editar-usuario.php, que es quien guarda.
 *   Moderadores (sobre usuarios normales) y admin (sobre cualquiera): nombre de usuario, frase y foto.
 *   Solo admin: correo, correo validado, nueva contraseña, nivel (normal / moderador) y aura a mano, con motivo; el cambio de
 *   aura queda en aura_log con reason «admin: motivo» y ref_id = el admin, y no cuenta para el tope diario de aura_add().
 */
declare(strict_types=1);

/** ¿Puede el usuario actual editar la ficha de $p? El admin, a cualquiera (también a sí mismo); un moderador, a los normales que no sea él. */
function modedit_puede(array $p): bool {
    $me = user();
    if (!$me || !is_mod()) return false;
    if (is_admin()) return true;
    return $p['level'] === 'normal' && (int)$me['user_id'] !== (int)$p['user_id'];
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
        if ($p['level'] === 'admin') $h .= "<label>Nivel</label><p class=\"meta\" style=\"padding:0\">admin</p>";
        else $h .= "<label>Nivel</label><select name=\"level\"><option value=\"normal\"" . ($p['level'] === 'normal' ? ' selected' : '') . ">normal</option><option value=\"mod\"" . ($p['level'] === 'mod' ? ' selected' : '') . ">moderador</option></select>";
        $h .= "<label>Correo</label><input type=\"email\" name=\"email\" required value=\"" . h($p['email']) . "\" autocomplete=\"off\">";
        $h .= "<label><input type=\"checkbox\" name=\"verified\" value=\"1\"" . ((int)$p['verified'] ? ' checked' : '') . "> correo validado (sin marcar, no puede iniciar sesión)</label>";
        $h .= "<label>Nueva contraseña (vacío = no cambiar)</label><input type=\"password\" name=\"pass\" data-pw minlength=\"8\" autocomplete=\"new-password\" placeholder=\"mínimo 8 caracteres\">";
        $h .= "<label>Repite la nueva contraseña</label><input type=\"password\" name=\"pass2\" data-pw minlength=\"8\" autocomplete=\"new-password\">";
        $h .= pw_toggle();
    } else {
        $h .= "<label>Correo</label><p class=\"meta\" style=\"padding:0\">" . h($p['email']) . ((int)$p['verified'] ? '' : ' (sin validar)') . " <small>(solo lo cambia el admin)</small></p>";
    }
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
