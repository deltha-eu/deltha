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
    $landing = preg_match('/^(t|c):\d{1,9}$/', post('landing')) ? post('landing') : '';
    $ulang = isset(DDD_LANGS[post('ulang')]) ? post('ulang') : '';
    $avatar = $u['avatar'];
    if (!empty($_FILES['photo']['name'])) {
        $new = avatar_desde_subida($_FILES['photo']);
        if (!$new) $err = t('No se pudo procesar la foto (mínimo 64×64, máximo 6 MB).');
        else { if ($avatar) @unlink(DDD_MEDIA_DIR . '/avatars/' . $avatar); $avatar = $new; }
    }
    if (post('quitar') === '1' && $avatar) { @unlink(DDD_MEDIA_DIR . '/avatars/' . $avatar); $avatar = null; }
    if (!$err) {
        $pdo->prepare("UPDATE users SET login = ?, bio = ?, avatar = ?, theme = ?, skin = ?, landing = ?, lang = ? WHERE user_id = ?")->execute([$login, $bio ?: null, $avatar, $theme, $skin, $landing, $ulang, $u['user_id']]);
        if (fuentes_on()) foreach (DDD_MEDIA_KINDS as $kind => [, , $field]) { try { guardar_mis_medios($pdo, (int)$u['user_id'], (array)($_POST[$field] ?? []), $kind); } catch (Throwable $e) {} }
        redirect('/perfil.php?ok=1');
    }
}
if (isset($_GET['ok'])) $ok = t('Perfil guardado.');

page_header(t('Perfil'));
echo "<div class=\"profile\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<div><h1>" . h($u['login']) . "</h1>";
if ($u['bio']) echo "<p class=\"bio\">" . h($u['bio']) . "</p>";
echo "<p class=\"meta\">" . t('aura') . " " . number_format((float)$u['aura'], 1, ',', '') . " · <a href=\"/u.php?u=" . rawurlencode($u['login']) . "\">" . t('ver perfil público') . "</a> · <a href=\"/usuarios.php\">" . t('usuarios') . "</a> · <a href=\"/comunidades.php\">" . t('comunidades') . "</a>" . (is_mod() ? " · <a href=\"/moderacion.php\">" . t('moderación') . "</a>" : "") . (is_admin() ? " · <a href=\"/admin.php\">admin</a>" : "") . " · <a href=\"/salir.php\">" . t('salir') . "</a></p></div></div>";
echo "<p class=\"notice\">" . t('Tu foto y tu frase se ven en tu perfil y junto a tu nombre cuando se revela, a los %d minutos de cada aportación. Hasta entonces eres un alias con halo.', (int)cfg('ghost_minutes', 30)) . "</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($ok)  echo "<p class=\"notice\">" . h($ok) . "</p>";
echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\">";
echo "" . t('<label>Nombre de usuario</label>') . "<input type=\"text\" name=\"login\" required pattern=\"[A-Za-z0-9_\\-]{3,32}\" value=\"" . h($u['login']) . "\" autocomplete=\"username\">";
echo "" . t('<label>Frase (hasta 160 caracteres)</label>') . "<input type=\"text\" name=\"bio\" maxlength=\"160\" value=\"" . h($u['bio'] ?? '') . "\">";
echo "" . t('<label>Foto</label>') . "<input type=\"file\" name=\"photo\" accept=\"image/*\">";
if ($u['avatar']) echo "<label><input type=\"checkbox\" name=\"quitar\" value=\"1\"" . t('> quitar la foto actual</label>') . "";
echo "<label>" . t('Idioma') . "</label><select name=\"ulang\"><option value=\"\">" . t('El del navegador') . "</option>";
foreach (DDD_LANGS as $k => $v) echo "<option value=\"$k\"" . (($u['lang'] ?? '') === $k ? ' selected' : '') . ">$v</option>";
echo "</select>";
echo "" . t('<label>Tema</label>') . "<select name=\"theme\">";
foreach (['auto' => t('Automático (como el sistema)'), 'light' => t('Claro'), 'dark' => t('Oscuro')] as $k => $v) {
    echo "<option value=\"$k\"" . (($u['theme'] ?? 'auto') === $k ? ' selected' : '') . ">" . h($v) . "</option>";
}
echo "</select>";
$cur = (string)($u['skin'] ?? '');
echo "" . t('<label>Plantilla</label>') . "<p class=\"skinnow\"><b>" . h($cur !== '' ? DDD_SKINS[$cur] : skin_descs()['']) . "</b> · <a href=\"/plantilla.php\">" . t('elegir otra') . "</a></p><small class=\"hint\">" . t('Las plantillas se eligen en su propia pestaña, con muestra y descripción de cada una; el tema claro/oscuro de arriba se aplica a todas.') . "</small>";
echo "<label id=\"aterrizar\">" . t('Al abrir Deltha, entrar en…') . "</label><select name=\"landing\"><option value=\"\"" . t('>La portada</option>') . "<optgroup label=\"" . t('Un tema') . "\">";
foreach ($pdo->query("SELECT topic_id, name FROM topics ORDER BY name") as $t) echo "<option value=\"t:{$t['topic_id']}\"" . (($u['landing'] ?? '') === "t:{$t['topic_id']}" ? ' selected' : '') . ">" . h($t['name']) . "</option>";
echo "</optgroup><optgroup label=\"" . t('Una comunidad') . "\">";
foreach ($pdo->query("SELECT community_id, name FROM communities WHERE status = 'ok' ORDER BY name") as $co) echo "<option value=\"c:{$co['community_id']}\"" . (($u['landing'] ?? '') === "c:{$co['community_id']}" ? ' selected' : '') . ">" . h($co['name']) . "</option>";
echo "</optgroup></select><small class=\"hint\">" . t('Se aplica al entrar con tu cuenta y al abrir Deltha desde fuera (un marcador, la app); la pestaña Portada sigue llevando a la portada.') . "</small>";
if (fuentes_on()) foreach (DDD_MEDIA_KINDS as $kind => [, $label, $field]) {
    if (!($catalogo = fuentes_activas($pdo, $kind))) continue;
    $max = medios_max($kind);
    $mine = array_column(mis_medios($pdo, $u, $kind), 'source_id');
    echo "<label id=\"" . ($kind === 'medio' ? 'medios' : 'substacks') . "\">" . t($label) . "</label><small class=\"hint\">" . ($kind === 'medio'
        ? t('Hasta %d medios con RSS que verás con el aspecto de Deltha, en el menú lateral y en el pie; desde cada noticia puedes proponerla en Nuevas. El catálogo lo mantiene el sitio: <a href="/medios.php">ver todos</a>.', $max)
        : t('Hasta %d newsletters (Substack) que verás igual que los medios, en su propio grupo del menú.', $max)) . "</small>";
    for ($k = 0; $k < $max; $k++) {
        echo "<select name=\"{$field}[]\" class=\"medsel\"><option value=\"\">" . t('— ninguno —') . "</option>";
        foreach ($catalogo as $m) echo "<option value=\"{$m['source_id']}\"" . ((int)($mine[$k] ?? 0) === (int)$m['source_id'] ? ' selected' : '') . ">" . h($m['name']) . "</option>";
        echo "</select>";
    }
}
echo "" . t('<button>Guardar</button>') . "</form>";
page_footer();
