<?php
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/usuarios.php';
require __DIR__ . '/../lib/fuentes.php';

$login = trim((string)($_GET['u'] ?? ''));
$st = $pdo->prepare("SELECT user_id, login, level, aura, avatar, bio, created, last_seen FROM users WHERE login = ?");
$st->execute([$login]);
$p = $st->fetch();
if (!$p) { http_response_code(404); page_header(t('No existe')); echo "<p class=\"end\">" . t('No existe ese usuario.') . "</p>"; page_footer(); exit; }
$own = user() && (int)user()['user_id'] === (int)$p['user_id'];

page_header($p['login']);
echo "<div class=\"profile\">" . avatar((float)$p['aura'], $p['login'], $p['avatar']) . "<div><h1>" . h($p['login']) . ((string)cfg('agent_badge', '0') === '1' && agent_is($pdo, (int)$p['user_id']) ? " <span class=\"botchip\" title=\"" . h(t('Cuenta automática: comenta con un modelo de IA que corre en un PC de la redacción')) . "\">" . t('bot') . "</span>" : "") . "</h1>";
if ($p['bio']) echo "<p class=\"bio\">" . h($p['bio']) . "</p>";
$gm = (int)cfg('ghost_minutes', 30);
$uid = (int)$p['user_id'];
// Solo lo revelado es publico (ghost_minutes); el dueño ve ademas lo suyo vivo
$wS = $own ? "s.user_id = $uid AND s.status NOT IN ('rejected','discard')" : "s.user_id = $uid AND (s.revealed = 1 OR s.sent_date <= NOW() - INTERVAL $gm MINUTE) AND s.status NOT IN ('rejected','discard')";
$wC = "c.user_id = $uid AND c.status = 'ok'" . ($own ? '' : " AND c.created <= NOW() - INTERVAL $gm MINUTE") . " AND s.status NOT IN ('pending_mod','rejected','discard')";
$nPub = (int)$pdo->query("SELECT COUNT(*) FROM stories s WHERE $wS")->fetchColumn();
$nCom = (int)$pdo->query("SELECT COUNT(*) FROM comments c JOIN stories s ON s.story_id = c.story_id WHERE $wC")->fetchColumn();
// pestañas Envíos / Comentarios (05/10/2026) y Medios / Substacks que sigue (migra-116: si lo permite en su perfil; el dueño siempre)
$verF = fuentes_on() && ($own || show_follows($pdo, $uid));
$fk = ['medios' => 'medio', 'substacks' => 'newsletter'];
$tab = (string)($_GET['t'] ?? '');
if (!in_array($tab, ['comentarios', 'medios', 'substacks'], true) || (isset($fk[$tab]) && !($verF && fuente_kind_on($fk[$tab])))) $tab = 'envios';
$sigue = [];
if ($verF) foreach ($fk as $tt => $kk) if (fuente_kind_on($kk)) $sigue[$tt] = mis_medios($pdo, ['user_id' => $uid], $kk);
$base = "/u.php?u=" . rawurlencode($p['login']);
echo "<p class=\"meta\">" . t('aura') . " " . number_format((float)$p['aura'], 1, ',', '') . " · " . t('desde') . " " . date('M Y', strtotime($p['created'])) . ($p['last_seen'] ? " · " . h(t('último acceso %s', hace($p['last_seen']))) : '')
   . " · <a class=\"cnt\" href=\"$base\">" . t('%d envíos', $nPub) . "</a> · <a class=\"cnt\" href=\"$base&amp;t=comentarios\">" . t('%d comentarios', $nCom) . "</a>";
if ($own) echo " · " . t('<a href="/perfil.php">editar</a>') . "";
if (!$own && user() && (string)cfg('msg_enabled', '1') === '1') echo " · <a href=\"/mensajes.php?u=" . rawurlencode($p['login']) . "\">" . t('enviar mensaje') . "</a>";
$canMod = modedit_puede($p);   // moderadores: usuarios normales; admin: cualquiera (lib/usuarios.php)
if ($canMod) echo " · <a href=\"#modedit\" onclick=\"document.getElementById('modedit').open = true\">" . t('editar perfil') . "</a>";
echo "</p></div></div>";
// moderadores y admin: edicion del perfil aqui mismo, sin pasar por moderacion ni admin
if ($canMod) {
    echo "<details class=\"editbox\" id=\"modedit\"" . (isset($_GET['ok']) || isset($_GET['err']) ? ' open' : '') . "><summary>Editar este perfil (" . (is_admin() ? 'admin' : 'moderación') . ")</summary>";
    if (isset($_GET['ok'])) echo "<p class=\"notice\">" . (['2' => 'Perfil guardado y contraseña cambiada.', 'creado' => 'Usuario creado. Ya puede entrar con su nombre y contraseña (o, si no marcaste «correo validado», cuando abra el correo de activación).'][(string)$_GET['ok']] ?? 'Perfil guardado.') . "</p>";
    if (isset($_GET['err'])) echo "<p class=\"error\">" . h((string)$_GET['err']) . "</p>";
    $ex = $pdo->prepare("SELECT user_id, login, email, level, aura, avatar, bio, verified FROM users WHERE user_id = ?"); $ex->execute([$p['user_id']]);
    echo modedit_form($pdo, $ex->fetch(), 'u');
    echo "</details>";
}

echo "<nav class=\"sub\"><a href=\"$base\"" . ($tab === 'envios' ? ' class="on"' : '') . ">" . t('Envíos') . " <small>$nPub</small></a><a href=\"$base&amp;t=comentarios\"" . ($tab === 'comentarios' ? ' class="on"' : '') . ">" . t('Comentarios') . " <small>$nCom</small></a>"
   . (isset($sigue['medios']) ? "<a href=\"$base&amp;t=medios\"" . ($tab === 'medios' ? ' class="on"' : '') . ">" . t('Medios') . " <small>" . count($sigue['medios']) . "</small></a>" : '')
   . (isset($sigue['substacks']) ? "<a href=\"$base&amp;t=substacks\"" . ($tab === 'substacks' ? ' class="on"' : '') . ">" . t('Substacks') . " <small>" . count($sigue['substacks']) . "</small></a>" : '')
   . "</nav>";
$page = page_num(); $per = (int)cfg('per_page', 20);
if ($tab === 'envios') {
    $q = $pdo->prepare(story_query_base() . " WHERE $wS ORDER BY s.sent_date DESC LIMIT $per OFFSET " . page_offset($page));
    $q->execute();
    $n = 0;
    foreach ($q as $s) {
        if ($own && !(int)$s['revealed'] && !ghost_over($s['sent_date'])) echo "<p class=\"notice\" style=\"margin-bottom:0\">" . t('Vivo, solo lo ves tú (alias ') . "" . h($s['author_alias']) . "):</p>";
        story_post($pdo, $s, 'compact'); $n++;
    }
    if ($n === 0) echo "<p class=\"end\">" . ($own ? t('Aún no has enviado nada.') : t('Nada revelado todavía: sus hilos siguen vivos o no ha enviado.')) . "</p>";
    elseif ($nPub > $per) paginate($base, $page, $nPub);
} elseif (isset($fk[$tab])) {
    // medios o substacks que sigue, el que ha publicado mas recientemente primero, cada uno con «Seguir» para quien mira; «Seguir todos» arriba
    $lista = $sigue[$tab]; $kk = $fk[$tab];
    $ult = fuentes_ultima_noticia($pdo);
    usort($lista, fn($a, $b) => strcmp((string)($ult[$b['source_id']] ?? ''), (string)($ult[$a['source_id']] ?? '')));
    if (!$lista) echo "<p class=\"end\">" . ($own ? t('Todavía no sigues ninguno aquí.') : t('No sigue ninguno todavía.')) . "</p>";
    else {
        $faltan = user() && !$own ? count(array_filter($lista, fn($m) => !fuente_es_fav($m))) : 0;
        echo "<div class=\"ufollow\"><p class=\"notice\">" . ($own ? t('Esto es lo que ven los demás en tu ficha; se cambia en <a href="/perfil.php#ficha-fuentes">tu perfil</a>.') : t('Los que sigue %s.', h($p['login'])))
           . ($faltan ? " <form class=\"inl\" method=\"post\" action=\"/fav-fuente.php\"><input type=\"hidden\" name=\"all_of\" value=\"$uid\"><input type=\"hidden\" name=\"k\" value=\"$kk\"><button class=\"link\">" . t('Seguir todos') . " ($faltan)</button></form>" : '') . "</p>";
        foreach ($lista as $m) {
            $descr = h((string)$m['descr'] ?: source_of($m['home']));
            if (($m['lang'] ?? '') !== '' && $m['lang'] !== lang()) $descr = "<b>" . h(strtoupper($m['lang'])) . "</b> · " . $descr;
            echo "<div class=\"fz medio\"><a class=\"fzl\" href=\"" . h(fuente_url($m)) . "\">" . avatar((float)cfg('aura_anon', 5), $m['name']) . "<span class=\"fzt\"><span class=\"t\">" . h($m['name']) . "</span><br><span class=\"meta\">" . $descr
               . (isset($ult[$m['source_id']]) ? " · " . h(hace($ult[$m['source_id']])) : '') . "</span></span></a>" . fuente_fav_form($m) . "</div>";
        }
        echo "</div>";
    }
} else {
    // comentarios revelados (o todos, al dueño), el mas reciente primero, cada uno con el titulo de su hilo encima; sin responder ni votar desde aqui
    $q = $pdo->prepare("SELECT c.*, u.login, u.avatar, s.title AS story_title FROM comments c JOIN users u ON u.user_id = c.user_id JOIN stories s ON s.story_id = c.story_id WHERE $wC ORDER BY c.created DESC LIMIT $per OFFSET " . page_offset($page));
    $q->execute();
    $rows = $q->fetchAll();
    avisos_preload_nicks($pdo, array_column($rows, 'content'));
    echo "<div class=\"ucoms\">";
    foreach ($rows as $c) {
        echo "<p class=\"hilotit\">" . t('en') . " <a href=\"/hilo.php?id={$c['story_id']}#c{$c['comment_id']}\">" . h($c['story_title']) . "</a></p>";
        comment_block($c, false, null, false, false);
    }
    echo "</div>";
    if (!$rows) echo "<p class=\"end\">" . ($own ? t('Aún no has comentado.') : t('Nada revelado todavía: sus comentarios siguen vivos o no ha comentado.')) . "</p>";
    elseif ($nCom > $per) paginate("$base&amp;t=comentarios", $page, $nCom);
}
page_footer();
