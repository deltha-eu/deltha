<?php
/**
 * /procesos.php — la «fisgona» de Deltha.
 *
 *  ?v=cola          el radar ordenado por delta en vivo, con la estimacion de cuando
 *                   llega cada enlace a su portada (simulando el ciclo: una por ciclo,
 *                   alternando Medios y Substacks, la de mayor delta de la portada del
 *                   turno entre las que cumplen delta_min_age_min y delta_min_publish_<portada>, a suertes segun el δ)
 *  ?v=todo          flujo cronologico de toda la actividad
 *  ?v=moderacion    lo que espera a un moderador (con lo que ha dicho la IA) y el historico de decisiones:
 *                   retirado o se queda, quien lo decidio y por que. A la vista de todo el mundo.
 *  ?v=envios | comentarios | votos | portadas   flujo filtrado
 *  &d=hoy | todo    solo lo de hoy / sin ventana de dias (desde /estadisticas.php). «todo»
 *                   solo en las vistas de un tipo, no en ?v=todo: el UNION sin ventana no seria barato
 *
 * Los votantes aparecen con su alias Ghost del hilo (el mismo que tendrian al
 * comentar) hasta que pasan los ghost_minutes desde el voto. La pagina se recarga
 * sola cada 60 s (meta refresh, sin JS). El flujo se limita a procesos_days dias
 * (los indices idx_created de votes/comments lo hacen barato) y para anonimos se
 * emite Cache-Control: max-age=60 para que Cloudflare la sirva desde su cache.
 */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$VIEWS = ['cola' => t('Radar'), 'todo' => t('Todo'), 'moderacion' => t('Moderación'), 'envios' => t('Envíos'), 'comentarios' => t('Comentarios'), 'votos' => t('Votos'), 'portadas' => t('Portadas')];
$v = (string)($_GET['v'] ?? 'cola');
if (!isset($VIEWS[$v])) $v = 'cola';
$page = page_num();
$per  = (int)cfg('per_page', 20);
$VIVAS = "('nuevas','destacadas','archived')";   // lo que es publico
$DIAS  = max(1, (int)cfg('procesos_days', 7));                    // ventana del flujo: lo demas ya esta en los hilos
$d = (string)($_GET['d'] ?? '');
if (!in_array($d, ['hoy', 'todo'], true) || $v === 'cola' || ($d === 'todo' && $v === 'todo')) $d = '';
// condicion de fecha de cada parte del flujo, con el alias de la tabla y su columna
$desde = fn(string $col) => $d === 'hoy' ? "$col >= CURDATE()" : ($d === 'todo' ? '1=1' : "$col >= NOW() - INTERVAL $DIAS DAY");
[$dS, $dC, $dV, $dP] = [$desde('s.sent_date'), $desde('c.created'), $desde('vt.created'), $desde('p.created')];
$qd = $d ? "&d=$d" : '';

/* Cache: la pagina es identica para todos los anonimos (sin cookie), asi que
 * Cloudflare puede servirla durante el minuto que dura el refresco y el servidor
 * la genera una vez. Los registrados ven sus botones de voto: sin cache. */
if (user()) header('Cache-Control: private, no-cache');
else        header('Cache-Control: public, max-age=60');
header('Vary: Cookie');

// La estimacion de la cola (proximo_ciclo, cola_eta, eta_txt) vive en lib/cola.php: la usa tambien la columna lateral.
require_once __DIR__ . '/../lib/cola.php';

page_header(t('Procesos'), 'procesos', refresh_meta(60));
echo "<nav class=\"sub\">";
foreach ($VIEWS as $k => $label) echo "<a href=\"/procesos.php" . ($k === 'cola' ? '' : "?v=$k") . "\"" . ($k === $v ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";
if ($v !== 'cola' && $v !== 'moderacion') {   // filtro de fecha: ventana normal / solo hoy / todo (este ultimo no en la vista mezclada)
    $F = ['' => t('últimos %d días', $DIAS), 'hoy' => t('Hoy')] + ($v === 'todo' ? [] : ['todo' => t('todo el histórico')]);
    echo "<nav class=\"sub fecha\">";
    foreach ($F as $k => $label) echo "<a href=\"/procesos.php?v=$v" . ($k ? "&amp;d=$k" : '') . "\"" . ($k === $d ? ' class="on"' : '') . ">" . h($label) . "</a>";
    echo "</nav>";
}

if ($v === 'cola') {
    $prox  = proximo_ciclo();
    $st = $pdo->query(str_replace('SELECT s.*,', 'SELECT s.*, ' . delta_sql() . ' AS delta_live,', story_query_base()) . " WHERE s.status='nuevas' AND s.content_type='link' ORDER BY delta_live DESC, s.sent_date ASC");
    $rows = $st->fetchAll();
    $total = count($rows);
    $eta = cola_eta($rows);
    echo "<p class=\"notice\">" . t('%d en el radar · próximo ciclo %s, para %s · los ciclos se turnan entre Medios y Substacks y cada uno publica <b>uno</b> en su portada, a suertes según el δ entre los suyos que llevan más de %d min y tienen δ ≥ %s (Medios) o %s (Substacks); si a una portada no le llega nada, el turno pasa a la otra. La estimación es orientativa: supone que sale la de mayor δ y que no cambian los votos; se recalcula sola cada minuto.', $total, proximo_ciclo_txt(), t(DDD_PORTADAS[portada_turno($prox)]), (int)cfg('delta_min_age_min', 30), num_es(delta_minpub('medios')), num_es(delta_minpub('substacks'))) . "</p>";
    $n = 0;
    // una linea por noticia: puesto, titular, quien/tema/cuando, delta, portada de destino y estimacion; sin avatar ni foto
    foreach (array_slice($rows, page_offset($page), $per) as $i => $s) {
        $pos = page_offset($page) + $i + 1;
        $rev = (int)$s['revealed'] === 1 || ghost_over($s['sent_date']);
        $who = display_name(['login' => $s['login'], 'alias' => $s['author_alias']], $rev);
        $sid = (int)$s['story_id'];
        $pd = portada_de($pdo, $s);
        $etaTxt = isset($eta[$sid]) ? eta_txt($eta[$sid], $pd) : t('hacia %s', t(DDD_PORTADAS[$pd])) . ' · ' . radar_falta($s);
        echo "<div class=\"fz cola\" id=\"s$sid\"><span class=\"n\">$pos</span><div class=\"fzt\">";
        echo "<a class=\"t\" href=\"/hilo.php?id=$sid\">" . h($s['title']) . "</a>";
        echo "<span class=\"meta\">" . h($who) . " · " . topic_link($s) . " · " . h(hace($s['sent_date'])) . "</span>";
        echo "<div class=\"delta\" title=\"" . h(delta_explica($s)) . "\">δ " . number_format((float)$s['delta_live'], 1, ',', '') . " · " . h($etaTxt) . "</div></div>";
        echo "<span class=\"vt\">" . icon('up') . (int)$s['votes_pos'] . " " . icon('down') . (int)$s['votes_neg'] . " " . icon('reply') . (int)$s['comments'] . "</span></div>";
        $n++;
    }
    if ($n === 0) echo "<p class=\"end\">" . t('No hay nada en el radar. <a href="/enviar.php">Envía algo</a>.') . "</p>";
    paginate('/procesos.php', $page, $total);
    page_footer();
    exit;
}

/* ---- moderacion a la vista: lo pendiente y el historico ---------------
 * Mismo contenido que /moderacion.php#ia pero sin el texto senalado (aqui no se reproduce lo que se retiro
 * ni lo que esta oculto: se enlaza y punto) y con el historico, que mod_queue guarda entero: nada se borra. */
if ($v === 'moderacion') {
    require_once __DIR__ . '/../lib/moderacion.php';
    $TY = ['story' => t('envío'), 'comment' => t('comentario'), 'chat' => t('chat')];
    $EST = ['hidden' => t('oculto mientras lo miran'), 'flag' => t('a la vista, señalado'), 'needs_human' => t('la IA no ha podido decidir')];
    // enlace al origen: el hilo, el comentario dentro del hilo o el mensaje del Chat; lo oculto no se enlaza
    $enlace = function (array $m): string {
        if ($m['item_type'] === 'chat')    return "/chat.php?m={$m['ref_id']}#m{$m['ref_id']}";
        if ($m['item_type'] === 'comment') return $m['sid'] ? "/hilo.php?id={$m['sid']}#c{$m['ref_id']}" : '';
        return "/hilo.php?id={$m['ref_id']}";
    };
    $SEL = "SELECT m.*, u.login AS modlogin, c.story_id AS sid
              FROM mod_queue m
         LEFT JOIN users u    ON u.user_id = m.decided_by
         LEFT JOIN comments c ON m.item_type = 'comment' AND c.comment_id = m.ref_id";
    try {
        $pend  = $pdo->query("$SEL WHERE m.status IN ('hidden','flag','needs_human') ORDER BY FIELD(m.status,'hidden','flag','needs_human'), m.score DESC, m.item_id DESC")->fetchAll();
        $total = (int)$pdo->query("SELECT COUNT(*) FROM mod_queue WHERE status IN ('human_ok','human_removed')")->fetchColumn();
        $hist  = $pdo->prepare("$SEL WHERE m.status IN ('human_ok','human_removed') ORDER BY m.processed DESC, m.item_id DESC LIMIT ? OFFSET ?");
        $hist->bindValue(1, $per, PDO::PARAM_INT); $hist->bindValue(2, page_offset($page), PDO::PARAM_INT); $hist->execute();
        $hist = $hist->fetchAll();
    } catch (Throwable $e) { $pend = $hist = []; $total = 0; }   // sin la migracion 062

    echo "<p class=\"notice\">" . t('Todo se publica al momento. Después, una IA que corre en un ordenador de la redacción lo revisa con el criterio de las <a href="/normas.php">normas de uso</a> y señala lo dudoso; <b>quien decide es siempre una persona</b>. Aquí está lo que espera y todo lo que ya se decidió, con quién lo hizo.') . "</p>";

    echo "<h3 class=\"sec\">" . t('Pendiente (%d)', count($pend)) . "</h3>";
    if (!$pend) echo "<p class=\"end\">" . t('Nada esperando a moderación.') . "</p>";
    foreach ($pend as $m) {
        $href = $m['status'] === 'hidden' ? '' : $enlace($m);
        $que  = h($TY[$m['item_type']] ?? $m['item_type']) . " #" . (int)$m['ref_id'];
        echo "<div class=\"fz mod\"><span class=\"av sys\">" . icon('proc') . "</span><div class=\"fzt\">";
        echo "<div class=\"t\">" . ($href ? "<a href=\"" . h($href) . "\">$que</a>" : $que) . " <span class=\"meta\">· " . h($EST[$m['status']] ?? $m['status']) . ((int)$m['priority'] ? ' · ' . t('reportado') : '') . " · " . h(hace($m['created'])) . "</span></div>";
        echo "<div class=\"prev\">" . h(mod_note_line($m)) . "</div></div></div>";
    }

    echo "<h3 class=\"sec\">" . t('Histórico (%d)', $total) . "</h3>";
    if (!$hist) echo "<p class=\"end\">" . t('Todavía no se ha decidido nada.') . "</p>";
    foreach ($hist as $m) {
        $fuera = $m['status'] === 'human_removed';
        $que   = h($TY[$m['item_type']] ?? $m['item_type']) . " #" . (int)$m['ref_id'];
        $href  = $enlace($m);
        echo "<div class=\"fz mod\"><span class=\"av sys\">" . icon($fuera ? 'down' : 'up') . "</span><div class=\"fzt\">";
        echo "<div class=\"t\">" . ($href ? "<a href=\"" . h($href) . "\">$que</a>" : $que) . " <span class=\"" . ($fuera ? 'neg' : 'pos') . "\">" . ($fuera ? t('retirado') : t('se queda')) . "</span>";
        echo "<span class=\"meta\">" . ((int)$m['priority'] ? ' · ' . t('reportado') : '') . " · " . ($m['modlogin'] ? t('decide %s', '<a href="/u.php?u=' . rawurlencode($m['modlogin']) . '">' . h($m['modlogin']) . '</a>') : t('decide un moderador')) . " · " . h(hace($m['processed'] ?: $m['created'])) . "</span></div>";
        echo "<div class=\"prev\">" . h(mod_note_line($m)) . "</div></div></div>";
    }
    paginate('/procesos.php?v=moderacion', $page, $total);
    page_footer();
    exit;
}

/* ---- flujo de actividad -------------------------------------------- */
$parts = [];
if (in_array($v, ['todo', 'envios'], true))
    $parts[] = "SELECT 'envio' AS kind, s.story_id, s.story_id AS ref, s.sent_date AS ts, s.user_id, s.author_alias AS alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM stories s WHERE s.status IN $VIVAS AND $dS";
if (in_array($v, ['todo', 'comentarios'], true))
    $parts[] = "SELECT 'comentario' AS kind, c.story_id, c.comment_id AS ref, c.created AS ts, c.user_id, c.alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM comments c JOIN stories s ON s.story_id = c.story_id
                 WHERE c.status='ok' AND $dC AND s.status IN $VIVAS";
if (in_array($v, ['todo', 'votos'], true))
    $parts[] = "SELECT 'voto' AS kind, vt.story_id, vt.story_id AS ref, vt.created AS ts, vt.user_id, ta.alias, vt.value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM votes vt JOIN stories s ON s.story_id = vt.story_id
             LEFT JOIN thread_aliases ta ON ta.story_id = vt.story_id AND ta.user_id = vt.user_id
                 WHERE $dV AND s.status IN $VIVAS";
if (in_array($v, ['todo', 'portadas'], true))
    $parts[] = "SELECT 'portada' AS kind, p.story_id, p.id AS ref, p.created AS ts, NULL AS user_id, NULL AS alias, p.portada AS value,
                       s.title, s.status, s.revealed, s.sent_date, p.delta
                  FROM promo_log p JOIN stories s ON s.story_id = p.story_id
                 WHERE $dP AND s.status IN $VIVAS";

$total = 0;
foreach ($parts as $p) $total += (int)$pdo->query("SELECT COUNT(*) FROM (" . $p . ") x")->fetchColumn();
$st = $pdo->prepare(implode("\nUNION ALL\n", $parts) . " ORDER BY ts DESC LIMIT ? OFFSET ?");
$st->bindValue(1, $per, PDO::PARAM_INT);
$st->bindValue(2, page_offset($page), PDO::PARAM_INT);
$st->execute();
$ev = $st->fetchAll();

// datos de los usuarios implicados (una consulta)
$ids = array_values(array_unique(array_filter(array_map(fn($e) => (int)$e['user_id'], $ev))));
$U = [];
if ($ids) {
    foreach ($pdo->query("SELECT user_id, login, avatar, aura FROM users WHERE user_id IN (" . implode(',', $ids) . ")") as $r) $U[(int)$r['user_id']] = $r;
}

$ventana = $d === 'hoy' ? t('solo hoy') : ($d === 'todo' ? t('todo el histórico') : t('últimos %d días', $DIAS));
echo "<p class=\"notice\">" . t('Lo que pasa en el sitio, en orden (%s). Quien envía, comenta o vota aparece con su alias Ghost del hilo hasta %d min después; luego con su nombre.', $ventana, (int)cfg('ghost_minutes', 30)) . "</p>";
if (!$ev) echo "<p class=\"end\">" . t('Todavía no hay actividad de este tipo.') . "</p>";
foreach ($ev as $e) {
    $sid = (int)$e['story_id'];
    $href = "/hilo.php?id=$sid";
    $title = "<a class=\"t\" href=\"$href\">" . h($e['title']) . "</a>";
    $u = $e['user_id'] ? ($U[(int)$e['user_id']] ?? null) : null;
    $aura = $u ? (float)$u['aura'] : (float)cfg('aura_anon', 5);
    $row = ['login' => $u['login'] ?? null, 'avatar' => $u['avatar'] ?? null];

    switch ($e['kind']) {
        case 'envio':
            $rev = (int)$e['revealed'] === 1 || ghost_over($e['sent_date']);
            $name = display_name(['login' => $row['login'], 'alias' => $e['alias']], $rev);
            $lead = who_avatar($row, $rev, $aura, $name);
            $txt = "<b>" . h($name) . "</b> " . t('envía') . " $title";
            $ic = 'new'; break;
        case 'comentario':
            $rev = ghost_over($e['ts']);
            $name = display_name(['login' => $row['login'], 'alias' => $e['alias']], $rev);
            $lead = who_avatar($row, $rev, $aura, $name);
            $txt = "<b>" . h($name) . "</b> " . t('comenta en') . " <a class=\"t\" href=\"$href#c{$e['ref']}\">" . h($e['title']) . "</a>";
            $ic = 'reply'; break;
        case 'voto':
            $rev = ghost_over($e['ts']);
            $alias = $e['alias'] ?: alias_user($pdo, $sid, (int)$e['user_id']);   // voto anterior a los alias: se le asigna ahora
            $name = display_name(['login' => $row['login'], 'alias' => $alias], $rev);
            $lead = who_avatar($row, $rev, $aura, $name);
            $pos = (int)$e['value'] > 0;
            $txt = "<b>" . h($name) . "</b> " . t('vota') . " <span class=\"" . ($pos ? 'pos' : 'neg') . "\">" . ($pos ? '+1' : '−1') . "</span> " . t('a') . " $title";
            $ic = $pos ? 'up' : 'down'; break;
        default: // portada
            $lead = "<span class=\"av sys\">" . icon('home') . "</span>";
            $txt = "$title " . (isset(DDD_PORTADAS[(string)$e['value']]) ? t('llega a <b>%s</b>', t(DDD_PORTADAS[$e['value']])) : t('llega a <b>portada</b>')) . " <span class=\"delta\">δ " . number_format((float)$e['delta'], 1, ',', '') . "</span>";
            $ic = 'home';
    }
    echo "<div class=\"fz k-{$e['kind']}\">$lead<div class=\"fzt\">$txt<span class=\"meta\"> · " . h(hace($e['ts'])) . "</span></div>" . icon($ic) . "</div>";
}
paginate("/procesos.php?v=$v$qd", $page, $total);
page_footer();
