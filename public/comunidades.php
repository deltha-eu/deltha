<?php
/** /comunidades — todas las comunidades y, para quien puede, el formulario de crear una.
 *  Puede crear: aura > comm_min_aura (config), hasta comm_max_per_user por usuario. El admin, siempre. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/imagen.php';   // logo y fondo al crear (09/10/2026; antes solo al editar)
$u = user();
$quota = community_quota($pdo, $u);
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $u) {
    $name = trim(preg_replace('/\s+/', ' ', post('name')));
    $descr = mb_substr(trim(post('descr')), 0, 200);
    $color = post('color') ?: '#534AB7';
    $skin = isset(DDD_SKINS[post('skin')]) ? post('skin') : '';
    $slug = community_slug($name);
    if ($quota <= 0) $err = t('No puedes crear más comunidades.');
    elseif (mb_strlen($name) < 3 || mb_strlen($name) > 48) $err = t('El nombre debe tener entre 3 y 48 caracteres.');
    elseif (strlen($slug) < 2) $err = t('El nombre necesita letras o números.');
    elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $err = t('Color no válido.');
    else {
        // logo (cuadrado) y fondo (apaisado, 4:1) opcionales, como en «Editar la comunidad» (migra-126): /media/comunidades
        $logo = null; $cover = null;
        foreach (['logo' => [256, 256], 'cover' => [1600, 400]] as $campo => [$w, $hh]) {
            if (empty($_FILES[$campo]['name'])) continue;
            $new = comunidad_imagen_desde_subida($_FILES[$campo], $w, $hh);
            if (!$new) { $err = t('No se ha podido leer la imagen (JPG, PNG, WebP o AVIF, hasta %d MB).', (int)round((int)cfg('img_max_bytes', 6000000) / 1000000)); break; }
            $$campo = $new;
        }
        if (!$err) {
            try {
                $pdo->prepare("INSERT INTO communities (slug, name, descr, color, skin, logo, cover, owner_id) VALUES (?,?,?,?,?,?,?,?)")->execute([$slug, $name, $descr ?: null, $color, $skin, $logo, $cover, $u['user_id']]);
                try { comunidad_seguir_set($pdo, (int)$u['user_id'], (int)$pdo->lastInsertId(), true); } catch (Throwable $e) {}   // quien la crea, la sigue (migra-142)
                redirect('/comunidad/' . rawurlencode($slug) . '?nueva=1');
            } catch (PDOException $e) { $err = t('Ya existe una comunidad con ese nombre.'); }
        }
        if ($err) foreach ([$logo, $cover] as $f) if ($f) @unlink(DDD_MEDIA_DIR . '/comunidades/' . $f);   // no se creo: fuera las imagenes ya guardadas
    }
}

// dos vistas (12/10/2026, migra-142): «Mis comunidades» (las que sigo) y «Todas»; se abre la ultima usada (cookie ddd_cv)
$v = comunidades_vista($pdo);
header('Cache-Control: private, no-cache');
$mias = comunidades_seguidas($pdo, $u); $idsMias = array_map(fn($c) => (int)$c['community_id'], $mias);
$rows = $pdo->query("SELECT c.*, u.login AS owner, (SELECT COUNT(*) FROM story_communities sc JOIN stories s ON s.story_id = sc.story_id WHERE sc.community_id = c.community_id AND s.status IN " . DDD_COMM_PUBLIC . ") n,
                            (SELECT MAX(sc.created) FROM story_communities sc WHERE sc.community_id = c.community_id) last,
                            (SELECT COUNT(*) FROM user_communities uc WHERE uc.community_id = c.community_id) nseg
                       FROM communities c JOIN users u ON u.user_id = c.owner_id WHERE c.status = 'ok' ORDER BY last DESC, c.created DESC")->fetchAll();
$todas = $rows;
if ($v === 'mias') $rows = array_values(array_filter($rows, fn($c) => in_array((int)$c['community_id'], $idsMias, true)));

page_header(t('Comunidades'), 'comunidades');
echo "<nav class=\"sub tipos\">" . ($u ? "<a href=\"/comunidades?v=mias\"" . ($v === 'mias' ? ' class="on"' : '') . ">" . t('Mis comunidades') . (count($mias) ? " <small>" . count($mias) . "</small>" : '') . "</a>" : '')
   . "<a href=\"/comunidades?v=todas\"" . ($v === 'todas' ? ' class="on"' : '') . ">" . t('Todas') . " <small>" . count($todas) . "</small></a></nav>";
echo "<p class=\"notice\">" . t('Los <b>temas</b> los pone %s; las <b>comunidades</b> las crea la gente. Un envío tiene siempre un tema y, si quiere, una comunidad; además, quien creó una comunidad puede añadir a ella cualquier envío ya publicado desde su hilo (hasta %d comunidades por envío). Puede crear comunidades quien tenga aura mayor que %s (los moderadores, sin requisito de aura), hasta %d cada uno. Puedes elegir una como página de entrada en <a href="/perfil">tu perfil</a>.', h(cfg('site_name')), (int)cfg('comm_max_per_story', 3), h(num_es(cfg('comm_min_aura', 10))), (int)cfg('comm_max_per_user', 3)) . "</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if ($u && $quota > 0) {
    echo "<h3 id=\"crear\" style=\"padding:12px 16px 0\"" . t('>Crear una comunidad</h3>') . "<p class=\"meta\" style=\"padding:4px 16px 0\">" . ($quota > 3 ? t('Puedes crear las que quieras.') : t('Puedes crear %d más.', $quota)) . "</p>";
    echo "<form class=\"plain\" method=\"post\" enctype=\"multipart/form-data\">" . t('<label>Nombre</label>') . "<input type=\"text\" name=\"name\" maxlength=\"48\" required placeholder=\"" . t('p. ej. Cine clásico') . "\" value=\"" . h(post('name')) . "\">";
    echo "" . t('<label>De qué va (hasta 200 caracteres)</label>') . "<input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h(post('descr')) . "\">";
    echo "" . t('<label>Color</label>') . "<input type=\"color\" name=\"color\" value=\"" . h(post('color') ?: '#534AB7') . "\">";
    echo skin_select(post('skin'));
    echo "<label>" . t('Logo') . " <small>" . t('cuadrado; sustituye al punto de color') . "</small></label><input type=\"file\" name=\"logo\" accept=\"image/*\">";
    echo "<label>" . t('Imagen de fondo') . " <small>" . t('apaisada, se recorta a 4:1; va detrás de la cabecera') . "</small></label><input type=\"file\" name=\"cover\" accept=\"image/*\">";
    echo "" . t('<button>Crear</button>') . "</form>";
} elseif ($u) {
    echo "<p class=\"end\">" . ((float)$u['aura'] <= (float)cfg('comm_min_aura', 10) ? t('Para crear comunidades hace falta aura mayor que %s (tienes %s).', h(num_es(cfg('comm_min_aura', 10))), number_format((float)$u['aura'], 1, ',', '')) : t('Ya has creado tus %d comunidades.', (int)cfg('comm_max_per_user', 3))) . "</p>";
}
echo "<h3 style=\"padding:20px 16px 0\">" . ($v === 'mias' ? t('Mis comunidades') : t('Todas las comunidades')) . "</h3>";
if (!$rows) echo "<p class=\"end\">" . ($v === 'mias' ? t('Todavía no sigues ninguna comunidad: pulsa «Seguir» en las que te interesen de <a href="/comunidades?v=todas">Todas</a>.') : t('Todavía no hay comunidades.')) . "</p>";
foreach ($rows as $c) {
    // la fila deja de ser un solo enlace cuando hay boton «Seguir» (un form no cabe dentro de un <a>), como en el catalogo de fuentes
    $cuerpo = (!empty($c['logo']) ? "<img class=\"dot logo\" src=\"/media/comunidades/" . h($c['logo']) . "\" alt=\"\">" : "<span class=\"dot\" style=\"background:" . h($c['color']) . "\"></span>") . "<div class=\"fzt\"><b>" . h($c['name']) . "</b>"
           . "<span class=\"meta\"> · " . t('%d envíos', $c['n']) . ((int)$c['nseg'] ? " · " . t('%d siguen', (int)$c['nseg']) : '') . " · " . t('de') . " " . h($c['owner']) . ($c['last'] ? " · " . h(hace($c['last'])) : '') . "</span>"
           . ($c['descr'] ? "<div class=\"prev\">" . h($c['descr']) . "</div>" : '') . "</div>";
    if ($fav = comunidad_seguir_form($c)) echo "<div class=\"fz comm\"><a class=\"fzl\" href=\"/comunidad/" . rawurlencode($c['slug']) . "\">$cuerpo</a>$fav</div>";
    else echo "<a class=\"fz comm\" href=\"/comunidad/" . rawurlencode($c['slug']) . "\">$cuerpo</a>";
}
page_footer();
