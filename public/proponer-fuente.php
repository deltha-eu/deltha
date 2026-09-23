<?php
/** /proponer-fuente.php — proponer un medio o un substack/newsletter con RSS para el catalogo. Solo cuentas con aura >= source_propose_aura
 *  (moderadores y admin, siempre). No se publica: se prueba el RSS y queda pendiente en /moderacion.php#fuentes, donde un moderador
 *  lo aprueba o lo rechaza; quien lo propuso recibe un mensaje privado. Abajo, las propuestas propias y su estado. Ver lib/propuestas.php. */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/propuestas.php';

$u = user();
if (!$u) redirect('/login.php');
$puede = fprop_puede($u);
$err = null;
$f = ['kind' => in_array($_GET['k'] ?? '', ['medio', 'newsletter'], true) ? $_GET['k'] : 'medio', 'name' => '', 'home' => '', 'feed' => '', 'descr' => '', 'note' => ''];

if ($puede && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['kind']  = post('kind') === 'newsletter' ? 'newsletter' : 'medio';
    $f['name']  = mb_substr(trim(post('name')), 0, 48);
    $f['home']  = trim(post('home'));
    $f['feed']  = trim(post('feed'));
    $f['descr'] = mb_substr(trim(post('descr')), 0, 200);
    $f['note']  = mb_substr(trim(post('note')), 0, 500);
    if ($f['home'] !== '' && !preg_match('#^https?://#i', $f['home'])) $f['home'] = 'https://' . $f['home'];
    if ($f['feed'] !== '' && !preg_match('#^https?://#i', $f['feed'])) $f['feed'] = 'https://' . $f['feed'];
    $uid = (int)$u['user_id'];
    $pend = $pdo->prepare("SELECT COUNT(*) FROM source_proposals WHERE user_id = ? AND status = 'pending'"); $pend->execute([$uid]);
    $dia  = $pdo->prepare("SELECT COUNT(*) FROM source_proposals WHERE user_id = ? AND created > NOW() - INTERVAL 1 DAY"); $dia->execute([$uid]);
    if (mb_strlen($f['name']) < 2) $err = t('Falta el nombre del medio.');
    elseif (!fprop_url_ok($f['home'])) $err = t('La dirección de la web no es válida.');
    elseif ($f['feed'] !== '' && !fprop_url_ok($f['feed'])) $err = t('La dirección del RSS no es válida.');
    elseif (($b = source_blocked($pdo, $f['home'])) || ($f['feed'] !== '' && ($b = source_blocked($pdo, $f['feed'])))) $err = t('Ese medio está bloqueado en este sitio (%s): no se admiten sus enlaces.', h($b));
    elseif (($ya = fprop_existe($pdo, $f['feed'], $f['home'])) !== null) $err = t('Ese medio ya está en el catálogo o pendiente de revisión: %s.', h($ya));
    elseif ((int)$pend->fetchColumn() >= max(1, (int)cfg('source_propose_max_pending', 3))) $err = t('Ya tienes %d propuestas pendientes de revisión; espera a que las revisen.', max(1, (int)cfg('source_propose_max_pending', 3)));
    elseif ((int)$dia->fetchColumn() >= max(1, (int)cfg('source_propose_per_day', 5))) $err = t('Has propuesto muchas fuentes hoy; vuelve mañana.');
    else {
        $r = fprop_probar($f['feed'], $f['home']);
        if ($r['items'] && ($ya = fprop_existe($pdo, $r['feed'], '')) !== null) $err = t('Ese medio ya está en el catálogo o pendiente de revisión: %s.', h($ya));   // el RSS encontrado en la web ya lo tenemos
        else {
            $pdo->prepare("INSERT INTO source_proposals (user_id, kind, name, home, feed, descr, note, lang, test_items, test_info) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$uid, $f['kind'], $f['name'], $f['home'], $r['feed'] !== '' ? $r['feed'] : $f['home'], $f['descr'] ?: null, $f['note'] ?: null, $r['lang'], $r['items'], fprop_info($r)]);
            redirect('/proponer-fuente.php?ok=' . ($r['items'] ? '1' : '2'));
        }
    }
}

page_header(t('Proponer un medio'));
// sin <h1>: el titulo ya sale en la cabecera (page_header)
if (!$puede) {
    echo "<p class=\"notice\">" . t('Pueden proponer medios y substacks las cuentas con aura %s o más. La tuya es %s: sigue enviando y comentando y podrás hacerlo.', num_pg(lang(), cfg('source_propose_aura', 6)), num_pg(lang(), round((float)$u['aura'], 1))) . "</p>";
} else {
    if (($_GET['ok'] ?? '') === '1') echo "<p class=\"notice\">" . t('Gracias. Hemos leído su RSS sin problemas y la propuesta queda pendiente: un moderador la revisará y te avisará por mensaje privado.') . "</p>";
    if (($_GET['ok'] ?? '') === '2') echo "<p class=\"notice\">" . t('Gracias. No hemos podido leer su RSS, pero la propuesta queda pendiente: un moderador lo revisará y te avisará por mensaje privado.') . "</p>";
    if ($err) echo "<p class=\"error\">$err</p>";
    echo "<p class=\"notice\">" . t('Propón un medio o un substack que tenga RSS. No se añade directamente: un moderador comprueba que su RSS se lee bien antes de que entre en el <a href="/medios.php">catálogo</a>.') . "</p>";
    echo "<form class=\"plain\" method=\"post\">";
    echo "<label>" . t('Tipo') . "</label><select name=\"kind\"><option value=\"medio\"" . ($f['kind'] === 'medio' ? ' selected' : '') . ">" . t('Medio') . "</option><option value=\"newsletter\"" . ($f['kind'] === 'newsletter' ? ' selected' : '') . ">" . t('Substack o newsletter') . "</option></select>";
    echo "<label>" . t('Nombre') . "</label><input type=\"text\" name=\"name\" maxlength=\"48\" required value=\"" . h($f['name']) . "\">";
    echo "<label>" . t('Web') . "</label><input type=\"url\" name=\"home\" required value=\"" . h($f['home']) . "\" placeholder=\"https://…\">";
    echo "<label>" . t('RSS (opcional)') . "</label><input type=\"url\" name=\"feed\" value=\"" . h($f['feed']) . "\" placeholder=\"https://…/feed\"><small class=\"hint\">" . t('Si lo dejas vacío, lo buscamos en la web.') . "</small>";
    echo "<label>" . t('Descripción (opcional)') . "</label><input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h($f['descr']) . "\" placeholder=\"" . h(t('De qué trata, en una línea')) . "\">";
    echo "<label>" . t('Nota para moderación (opcional)') . "</label><textarea name=\"note\" maxlength=\"500\">" . h($f['note']) . "</textarea>";
    echo "<button>" . t('Enviar propuesta') . "</button></form>";
}

$mias = $pdo->prepare("SELECT name, kind, status, reply, created FROM source_proposals WHERE user_id = ? ORDER BY created DESC LIMIT 20");
$mias->execute([(int)$u['user_id']]);
if ($mias = $mias->fetchAll()) {
    $EST = ['pending' => t('pendiente'), 'ok' => t('aceptada'), 'rejected' => t('rechazada')];
    echo "<h3 class=\"sec\">" . t('Tus propuestas') . "</h3><ul class=\"fprops\">";
    foreach ($mias as $m) echo "<li><b>" . h($m['name']) . "</b> <span class=\"meta\">· " . ($m['kind'] === 'newsletter' ? t('Substack o newsletter') : t('Medio')) . " · " . h(hace($m['created'])) . " · <span class=\"st-{$m['status']}\">" . h($EST[$m['status']] ?? $m['status']) . "</span>" . ($m['reply'] ? " · " . h($m['reply']) : '') . "</span></li>";
    echo "</ul>";
}
page_footer();
