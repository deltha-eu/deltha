<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/imagen.php';
require __DIR__ . '/../lib/fuentes.php';
$u = user();
if (!$u) redirect('/login.php');
$err = null; $ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = mb_substr(post('bio'), 0, 160);
    $login = post('login') ?: $u['login'];
    if ($login !== $u['login']) {
        if (!preg_match('/^[a-z0-9_\-]{3,32}$/i', $login)) $err = t('Usuario: 3–32 caracteres, letras, números, guion o guion bajo.');
        else {
            $q = $pdo->prepare("SELECT 1 FROM users WHERE login = ? AND user_id <> ?"); $q->execute([$login, $u['user_id']]);
            if ($q->fetchColumn()) $err = t('Ese nombre de usuario ya está cogido.');
        }
    }
    $theme = in_array(post('theme'), ['auto', 'light', 'dark'], true) ? post('theme') : 'auto';
    $skin  = (string)($u['skin'] ?? '');   // la plantilla se elige en /plantilla.php
    $landing = preg_match('/^((t|c):\d{1,9}|u:(medio|sub|yt|agg))$/', post('landing')) ? post('landing') : '';
    $ulang = isset(DDD_LANGS[post('ulang')]) ? post('ulang') : '';
    $mylang = post('src_mylang') === '1' ? 1 : 0;   // solo fuentes (medios, substacks, agregadores) en mi idioma
    $avatar = $u['avatar'];
    if (!empty($_FILES['photo']['name'])) {
        $new = avatar_desde_subida($_FILES['photo']);
        if (!$new) $err = t('No se pudo procesar la foto (mínimo 64×64, máximo 6 MB).');
        else { if ($avatar) @unlink(DDD_MEDIA_DIR . '/avatars/' . $avatar); $avatar = $new; }
    }
    if (post('quitar') === '1' && $avatar) { @unlink(DDD_MEDIA_DIR . '/avatars/' . $avatar); $avatar = null; }
    if (!$err) {
        $pdo->prepare("UPDATE users SET login = ?, bio = ?, avatar = ?, theme = ?, skin = ?, landing = ?, lang = ?, src_mylang = ? WHERE user_id = ?")->execute([$login, $bio ?: null, $avatar, $theme, $skin, $landing, $ulang, $mylang, $u['user_id']]);
        try { $pdo->prepare("UPDATE users SET twofa = ? WHERE user_id = ?")->execute([post('twofa') === '1' ? 1 : 0, $u['user_id']]); } catch (Throwable $e) { /* migra-095 sin aplicar */ }
        redirect('/perfil.php?ok=1');
    }
}
if (isset($_GET['ok'])) $ok = t('Perfil guardado.');

page_header(t('Perfil'));
echo "<div class=\"profile\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<div><h1>" . h($u['login']) . "</h1>";
if ($u['bio']) echo "<p class=\"bio\">" . h($u['bio']) . "</p>";
echo "<p class=\"meta\">" . t('aura') . " " . number_format((float)$u['aura'], 1, ',', '') . " · <a href=\"/u.php?u=" . rawurlencode($u['login']) . "\">" . t('ver perfil público') . "</a> · <a href=\"/usuarios.php\">" . t('usuarios') . "</a> · <a href=\"/comunidades.php\">" . t('comunidades') . "</a>" . (is_mod() ? " · <a href=\"/moderacion.php\">" . t('moderación') . "</a>" : "") . (is_admin() ? " · <a href=\"/admin.php\">admin</a>" : "") . " · <a href=\"/salir.php\">" . t('salir') . "</a></p></div></div>";
profile_tabs('perfil');
echo "<p class=\"notice\">" . t('Tu foto y tu frase se ven en tu perfil y junto a tu nombre cuando se revela, a los %d minutos de cada aportación. Hasta entonces eres un alias con halo.', (int)cfg('ghost_minutes', 30)) . "</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($ok)  echo "<p class=\"notice\">" . h($ok) . "</p>";
// ghosteados (lib/ghost.php): a quien he dejado de ver, con fecha y «deshacer»; solo si hay alguno o se acaba de tocar
if (ghost_on() && (($gl = ghost_list($pdo, (int)$u['user_id'])) || isset($_GET['ghost']))) {
    echo "<div class=\"ghostlist\" id=\"ghost\"><h3 class=\"sec\">" . t('Ghosteados') . "</h3>";
    if (isset($_GET['ghost'])) echo "<p class=\"notice\">" . ($_GET['ghost'] === 'quitado' ? t('Hecho: volvéis a veros con normalidad.') : t('Hecho: para ese usuario ya eres un Ghost, y él para ti.')) . "</p>";
    if (!$gl) echo "<p class=\"end\">" . t('No has ghosteado a nadie.') . "</p>";
    foreach ($gl as $g) {
        echo "<div class=\"ulist\">" . avatar((float)$g['aura'], $g['login'], $g['avatar']) . "<div><b>" . h($g['login']) . "</b><span class=\"meta\"> · " . t('desde') . " " . h(hace($g['created'])) . "</span>";
        echo "<form class=\"inline\" method=\"post\" action=\"/ghostear.php\"><input type=\"hidden\" name=\"u\" value=\"" . h($g['login']) . "\"><input type=\"hidden\" name=\"act\" value=\"deshacer\"><button class=\"link\">" . t('deshacer') . "</button></form></div></div>";
    }
    if ($gl) echo "<small class=\"hint\">" . t('Ni ellos ven tu nombre ni tú el suyo; sus comentarios te salen plegados y no podéis escribiros. Nadie recibe aviso.') . "</small>";
    echo "</div>";
}
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\">";
echo "" . t('<label>Nombre de usuario</label>') . "<input type=\"text\" name=\"login\" required pattern=\"[A-Za-z0-9_\\-]{3,32}\" value=\"" . h($u['login']) . "\" autocomplete=\"username\">";
echo "" . t('<label>Frase (hasta 160 caracteres)</label>') . "<input type=\"text\" name=\"bio\" maxlength=\"160\" value=\"" . h($u['bio'] ?? '') . "\">";
echo "" . t('<label>Foto</label>') . "<input type=\"file\" name=\"photo\" accept=\"image/*\">";
if ($u['avatar']) echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"" . t('> quitar la foto actual</label>') . "";
echo "<label>" . t('Idioma') . "</label><select name=\"ulang\"><option value=\"\">" . t('El del navegador') . "</option>";
foreach (DDD_LANGS as $k => $v) echo "<option value=\"$k\"" . (($u['lang'] ?? '') === $k ? ' selected' : '') . ">$v</option>";
echo "</select>";
if (fuentes_on()) echo "<label id=\"fuentes-idioma\"><input type=\"checkbox\" name=\"src_mylang\" value=\"1\"" . (!empty($u['src_mylang']) ? ' checked' : '') . "> " . t('Mostrar fuentes y envíos solo en mi idioma') . "</label><small class=\"hint\">" . t('Medios, substacks y agregadores en otros idiomas dejan de salir en el menú, en las listas y en tu elección de medios, y los envíos en otro idioma dejan de salir en las portadas, el radar, los temas y las comunidades (los que no se sabe en qué idioma están, sí se ven). Tu idioma es el de la interfaz.') . "</small>";
// verificacion en dos pasos por correo (lib/twofa.php): casilla; a los administradores se les puede exigir desde admin -> Config (twofa_admin)
try {
    require_once __DIR__ . '/../lib/twofa.php';
    $r2 = twofa_row($pdo, (int)$u['user_id']);
    if ($r2 && twofa_email_ok((string)$r2['email'])) {
        $forz = $r2['level'] === 'admin' && twofa_admin_forced();
        echo "<label id=\"dos-pasos\">" . t('Verificación en dos pasos') . "</label><label><input type=\"checkbox\" name=\"twofa\" value=\"1\"" . ((int)$r2['twofa'] === 1 || $forz ? ' checked' : '') . ($forz ? ' disabled' : '') . "> " . t('Pedir un código por correo al entrar') . "</label>"
           . ($forz ? "<input type=\"hidden\" name=\"twofa\" value=\"1\">" : '')
           . "<small class=\"hint\">" . t('Además de la contraseña, al entrar te enviaremos un código de seis cifras a %s. Solo se pide al iniciar sesión, no en cada visita.', h(twofa_mask((string)$r2['email']))) . ($forz ? ' ' . t('Es obligatoria para administradores.') : '') . "</small>";
    }
} catch (Throwable $e) { /* migra-095 sin aplicar: sin casilla */ }
echo "" . t('<label>Tema</label>') . "<select name=\"theme\">";
foreach (['auto' => t('Automático (como el sistema)'), 'light' => t('Claro'), 'dark' => t('Oscuro')] as $k => $v) {
    echo "<option value=\"$k\"" . (($u['theme'] ?? 'auto') === $k ? ' selected' : '') . ">" . h($v) . "</option>";
}
echo "</select>";
$cur = (string)($u['skin'] ?? '');
echo "" . t('<label>Skin</label>') . "<p class=\"skinnow\"><b>" . h($cur !== '' ? DDD_SKINS[$cur] : skin_descs()['']) . "</b> · <a href=\"/plantilla.php\">" . t('elegir otra') . "</a></p><small class=\"hint\">" . t('Las skins se eligen en su propia pestaña, con muestra y descripción de cada una; el tema claro/oscuro de arriba se aplica a todas.') . "</small>";
echo "<label id=\"aterrizar\">" . t('Al abrir Deltha, entrar en…') . "</label><select name=\"landing\"><option value=\"\"" . t('>La portada</option>')
   . (fuentes_on() ? "<option value=\"u:medio\"" . (($u['landing'] ?? '') === 'u:medio' ? ' selected' : '') . ">" . t('Mis medios') . "</option><option value=\"u:sub\"" . (($u['landing'] ?? '') === 'u:sub' ? ' selected' : '') . ">" . t('Mis substacks') . "</option>" . (youtube_on() && fuentes_activas($pdo, 'youtube') ? "<option value=\"u:yt\"" . (($u['landing'] ?? '') === 'u:yt' ? ' selected' : '') . ">" . t('Mis canales') . "</option>" : '') . (agregadores_on() ? "<option value=\"u:agg\"" . (($u['landing'] ?? '') === 'u:agg' ? ' selected' : '') . ">" . t('Mis agregadores') . "</option>" : '') : '')
   . "<optgroup label=\"" . t('Un tema') . "\">";
foreach ($pdo->query("SELECT topic_id, name FROM topics ORDER BY name") as $t) echo "<option value=\"t:{$t['topic_id']}\"" . (($u['landing'] ?? '') === "t:{$t['topic_id']}" ? ' selected' : '') . ">" . h($t['name']) . "</option>";
echo "</optgroup><optgroup label=\"" . t('Una comunidad') . "\">";
foreach ($pdo->query("SELECT community_id, name FROM communities WHERE status = 'ok' ORDER BY name") as $co) echo "<option value=\"c:{$co['community_id']}\"" . (($u['landing'] ?? '') === "c:{$co['community_id']}" ? ' selected' : '') . ">" . h($co['name']) . "</option>";
echo "</optgroup></select><small class=\"hint\">" . t('Se aplica al entrar con tu cuenta y al abrir Deltha desde fuera (un marcador, la app); la pestaña Portada sigue llevando a la portada.') . "</small>";
// los medios y substacks favoritos ya no se eligen aqui (eran tres desplegables): pestañas «Mis medios» y «Mis substacks» (/mis-fuentes.php)
if (fuentes_on()) { require_once __DIR__ . '/../lib/propuestas.php'; if (fprop_puede($u)) echo "<p class=\"fprop-cta\">" . t('¿Falta alguno? <a href="%s">Propón un medio o un substack</a>.', '/proponer-fuente.php') . "</p>"; }
echo "" . t('<button>Guardar</button>') . "</form>";
page_footer();
