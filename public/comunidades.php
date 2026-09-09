<?php
/** /comunidades.php — todas las comunidades y, para quien puede, el formulario de crear una.
 *  Puede crear: aura > comm_min_aura (config), hasta comm_max_per_user por usuario. El admin, siempre. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
$u = user();
$quota = community_quota($pdo, $u);
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $u) {
    $name = trim(preg_replace('/\s+/', ' ', post('name')));
    $descr = mb_substr(trim(post('descr')), 0, 200);
    $color = post('color') ?: '#534AB7';
    $slug = community_slug($name);
    if ($quota <= 0) $err = 'No puedes crear más comunidades.';
    elseif (mb_strlen($name) < 3 || mb_strlen($name) > 48) $err = 'El nombre debe tener entre 3 y 48 caracteres.';
    elseif (strlen($slug) < 2) $err = 'El nombre necesita letras o números.';
    elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $err = 'Color no válido.';
    else {
        try {
            $pdo->prepare("INSERT INTO communities (slug, name, descr, color, owner_id) VALUES (?,?,?,?,?)")->execute([$slug, $name, $descr ?: null, $color, $u['user_id']]);
            redirect('/comunidad.php?c=' . rawurlencode($slug) . '&nueva=1');
        } catch (PDOException $e) { $err = 'Ya existe una comunidad con ese nombre.'; }
    }
}

$rows = $pdo->query("SELECT c.*, u.login AS owner, (SELECT COUNT(*) FROM stories s WHERE s.community_id = c.community_id AND s.status IN ('nuevas','candidatas','destacadas','archived')) n,
                            (SELECT MAX(s.sent_date) FROM stories s WHERE s.community_id = c.community_id) last
                       FROM communities c JOIN users u ON u.user_id = c.owner_id WHERE c.status = 'ok' ORDER BY last DESC, c.created DESC")->fetchAll();

page_header('Comunidades');
echo "<h1>Comunidades</h1>";
echo "<p class=\"notice\">Los <b>temas</b> los pone " . h(cfg('site_name')) . "; las <b>comunidades</b> las crea la gente. Un envío tiene siempre un tema y, si quiere, una comunidad. Puede crear comunidades quien tenga aura mayor que " . h(num_es(cfg('comm_min_aura', 10))) . " (los moderadores, sin requisito de aura), hasta " . (int)cfg('comm_max_per_user', 3) . " cada uno. Puedes elegir una como página de entrada en <a href=\"/perfil.php\">tu perfil</a>.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
if (!$rows) echo "<p class=\"end\">Todavía no hay comunidades.</p>";
foreach ($rows as $c) {
    echo "<a class=\"fz comm\" href=\"/comunidad.php?c=" . rawurlencode($c['slug']) . "\"><span class=\"dot\" style=\"background:" . h($c['color']) . "\"></span><div class=\"fzt\"><b>" . h($c['name']) . "</b>";
    echo "<span class=\"meta\"> · {$c['n']} " . ($c['n'] == 1 ? 'envío' : 'envíos') . " · de " . h($c['owner']) . ($c['last'] ? " · " . h(hace($c['last'])) : '') . "</span>";
    if ($c['descr']) echo "<div class=\"prev\">" . h($c['descr']) . "</div>";
    echo "</div></a>";
}
if ($u && $quota > 0) {
    echo "<h3 style=\"padding:20px 16px 0\">Crear una comunidad</h3><p class=\"meta\" style=\"padding:4px 16px 0\">Puedes crear " . ($quota > 3 ? 'las que quieras' : "$quota más") . ".</p>";
    echo "<form class=\"plain\" method=\"post\"><label>Nombre</label><input type=\"text\" name=\"name\" maxlength=\"48\" required placeholder=\"p. ej. Cine clásico\" value=\"" . h(post('name')) . "\">";
    echo "<label>De qué va (hasta 200 caracteres)</label><input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h(post('descr')) . "\">";
    echo "<label>Color</label><input type=\"color\" name=\"color\" value=\"" . h(post('color') ?: '#534AB7') . "\">";
    echo "<button>Crear</button></form>";
} elseif ($u) {
    echo "<p class=\"end\">" . ((float)$u['aura'] <= (float)cfg('comm_min_aura', 10) ? "Para crear comunidades hace falta aura mayor que " . h(cfg('comm_min_aura', 10)) . " (tienes " . number_format((float)$u['aura'], 1, ',', '') . ")." : "Ya has creado tus " . (int)cfg('comm_max_per_user', 3) . " comunidades.") . "</p>";
}
page_footer();
