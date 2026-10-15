<?php
/**
 * /procesos — la «fisgona» de Deltha.
 *
 *  ?v=cola          el radar ordenado por delta en vivo, con la estimacion de cuando
 *                   llega cada enlace a su portada (simulando el ciclo: una por ciclo,
 *                   alternando Medios y Substacks, la de mayor delta de la portada del
 *                   turno entre las que cumplen delta_min_age_min y delta_min_publish_<portada>, a suertes segun el δ)
 *  ?v=todo          flujo cronologico de toda la actividad
 *  ?v=moderacion    lo que espera a un moderador (con lo que ha dicho la IA) y el historico de decisiones:
 *                   retirado o se queda, quien lo decidio y por que. A la vista de todo el mundo.
 *  ?v=envios | comentarios | votos | portadas   flujo filtrado
 *  ?v=registros   SOLO ADMIN: historico permanente de registros (tabla reg_log, migra-102): cada intento de alta, la mas reciente primero,
 *                   con su correo y como acabo: activada, a medias (sin confirmar el correo, o borrada por no activar) o rechazada en el formulario.
 *  &d=hoy | todo    solo lo de hoy / sin ventana de dias (desde /estadisticas). «todo»
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
if (is_admin()) $VIEWS['registros'] = t('Registros');   // solo admin: ni la pestaña ni la vista existen para los demas
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
foreach ($VIEWS as $k => $label) echo "<a href=\"/procesos" . ($k === 'cola' ? '' : "?v=$k") . "\"" . ($k === $v ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";
if ($v !== 'cola' && $v !== 'moderacion') {   // filtro de fecha: ventana normal / solo hoy / todo (este ultimo no en la vista mezclada)
    $F = ['' => t('últimos %d días', $DIAS), 'hoy' => t('Hoy')] + ($v === 'todo' ? [] : ['todo' => t('todo el histórico')]);
    echo "<nav class=\"sub fecha\">";
    foreach ($F as $k => $label) echo "<a href=\"/procesos?v=$v" . ($k ? "&amp;d=$k" : '') . "\"" . ($k === $d ? ' class="on"' : '') . ">" . h($label) . "</a>";
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
        echo "<a class=\"t\" href=\"/hilo/$sid\">" . h($s['title']) . "</a>";
        echo "<span class=\"meta\">" . h($who) . " · " . topic_link($s) . " · " . h(hace($s['sent_date'])) . "</span>";
        echo "<div class=\"delta\" title=\"" . h(delta_explica($s)) . "\">δ " . number_format((float)$s['delta_live'], 1, ',', '') . " · " . h($etaTxt) . "</div></div>";
        echo "<span class=\"vt\">" . icon('up') . (int)$s['votes_pos'] . " " . icon('down') . (int)$s['votes_neg'] . " " . icon('reply') . (int)$s['comments'] . "</span></div>";
        $n++;
    }
    if ($n === 0) echo "<p class=\"end\">" . t('No hay nada en el radar. <a href="/enviar">Envía algo</a>.') . "</p>";
    paginate('/procesos', $page, $total);
    page_footer();
    exit;
}

/* ---- moderacion a la vista: lo pendiente y el historico ---------------
 * Mismo contenido que /moderacion pero sin el texto senalado (aqui no se reproduce lo que se retiro
 * ni lo que esta oculto: se enlaza y punto) y con el historico, que se guarda entero: nada se borra.
 * Dos fuentes: lo que senala la IA (mod_queue) y lo que reporta la gente (comment_reports, chat_reports; quien decide y
 * cuando en decided_by / decided, migra-082). Un comentario reportado que ademas senalo la IA sale por las dos vias. */
if ($v === 'moderacion') {
    require_once __DIR__ . '/../lib/moderacion.php';
    $TY = ['story' => t('envío'), 'comment' => t('comentario'), 'chat' => t('chat')];
    $EST = ['hidden' => t('oculto mientras lo miran'), 'flag' => t('a la vista, señalado'), 'needs_human' => t('la IA no ha podido decidir')];
    // enlace al origen: el hilo, el comentario dentro del hilo o el mensaje del Chat; lo oculto no se enlaza
    $enlace = function (array $m): string {
        if ($m['item_type'] === 'chat')    return "/chat?m={$m['ref_id']}#m{$m['ref_id']}";
        if ($m['item_type'] === 'comment') return $m['sid'] ? "/hilo/{$m['sid']}#c{$m['ref_id']}" : '';
        return "/hilo/{$m['ref_id']}";
    };
    $SEL = "SELECT m.*, u.login AS modlogin, c.story_id AS sid
              FROM mod_queue m
         LEFT JOIN users u    ON u.user_id = m.decided_by
         LEFT JOIN comments c ON m.item_type = 'comment' AND c.comment_id = m.ref_id";
    try {
        $pend  = $pdo->query("$SEL WHERE m.status IN ('hidden','flag','needs_human') ORDER BY FIELD(m.status,'hidden','flag','needs_human'), m.score DESC, m.item_id DESC")->fetchAll();
    } catch (Throwable $e) { $pend = []; }   // sin la migracion 062
    // reportes de la gente, agrupados por comentario / mensaje: n reportes, motivos, primero y (si ya se decidio) quien y cuando
    $RZ = report_reasons();
    $REP = "SELECT 'comment' AS item_type, r.comment_id AS ref_id, c.story_id AS sid, r.status, COUNT(*) AS n,
                   GROUP_CONCAT(DISTINCT r.reason ORDER BY r.reason) AS reasons, MIN(r.created) AS created, MAX(r.decided) AS decided, MAX(u.login) AS modlogin
              FROM comment_reports r JOIN comments c ON c.comment_id = r.comment_id LEFT JOIN users u ON u.user_id = r.decided_by
             WHERE r.status %s GROUP BY r.comment_id, r.status
             UNION ALL
            SELECT 'chat', r.msg_id, NULL, r.status, COUNT(*), GROUP_CONCAT(DISTINCT r.reason ORDER BY r.reason), MIN(r.created), MAX(r.decided), MAX(u.login)
              FROM chat_reports r LEFT JOIN users u ON u.user_id = r.decided_by
             WHERE r.status %s GROUP BY r.msg_id, r.status";
    try { $repPend = $pdo->query(sprintf($REP, "= 'open'", "= 'open'") . " ORDER BY created DESC")->fetchAll(); } catch (Throwable $e) { $repPend = []; }
    $motivos = fn(array $r) => implode(', ', array_map(fn($k) => $RZ[$k] ?? $k, explode(',', (string)$r['reasons'])));
    // historico: decisiones humanas sobre lo de la IA + reportes cerrados, en una sola lista por fecha
    try {
        $H = "SELECT 'ia' AS src, m.item_type, m.ref_id, c.story_id AS sid, m.status, m.priority, u.login AS modlogin, COALESCE(m.processed, m.created) AS ts,
                     m.verdict, m.category, m.score, m.created, m.processed, NULL AS n, NULL AS reasons
                FROM mod_queue m LEFT JOIN users u ON u.user_id = m.decided_by LEFT JOIN comments c ON m.item_type = 'comment' AND c.comment_id = m.ref_id
               WHERE m.status IN ('human_ok','human_removed')
               UNION ALL
              SELECT 'rep', x.item_type, x.ref_id, x.sid, x.status, 1, x.modlogin, COALESCE(x.decided, x.created), NULL, NULL, NULL, x.created, x.decided, x.n, x.reasons
                FROM (" . sprintf($REP, "IN ('deleted','dismissed')", "IN ('deleted','dismissed')") . ") x";
        $total = (int)$pdo->query("SELECT COUNT(*) FROM ($H) h")->fetchColumn();
        $hist  = $pdo->prepare("$H ORDER BY ts DESC LIMIT ? OFFSET ?");
        $hist->bindValue(1, $per, PDO::PARAM_INT); $hist->bindValue(2, page_offset($page), PDO::PARAM_INT); $hist->execute();
        $hist = $hist->fetchAll();
    } catch (Throwable $e) { $hist = []; $total = 0; }

    echo "<p class=\"notice\">" . t('Todo se publica al momento. Después, una IA que corre en un ordenador de la redacción lo revisa con el criterio de las <a href="/normas">normas de uso</a> y señala lo dudoso; <b>quien decide es siempre una persona</b>. Aquí está lo que espera y todo lo que ya se decidió, con quién lo hizo.') . "</p>";

    echo "<h3 class=\"sec\">" . t('Pendiente (%d)', count($pend) + count($repPend)) . "</h3>";
    if (!$pend && !$repPend) echo "<p class=\"end\">" . t('Nada esperando a moderación.') . "</p>";
    foreach ($repPend as $r) {   // lo que ha reportado la gente y aun no ha mirado nadie
        $href = $enlace($r); $que = h($TY[$r['item_type']] ?? $r['item_type']) . " #" . (int)$r['ref_id'];
        echo "<div class=\"fz mod\"><span class=\"av sys\">" . icon('proc') . "</span><div class=\"fzt\">";
        echo "<div class=\"t\">" . ($href ? "<a href=\"" . h($href) . "\">$que</a>" : $que) . " <span class=\"meta\">· " . h(t('reportado por %d personas', (int)$r['n'])) . " · " . h(hace($r['created'])) . "</span></div>";
        echo "<div class=\"prev\">" . h(t('Motivos: %s', $motivos($r))) . "</div></div></div>";
    }
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
        $fuera = in_array($m['status'], ['human_removed', 'deleted'], true);
        $que   = h($TY[$m['item_type']] ?? $m['item_type']) . " #" . (int)$m['ref_id'];
        $href  = $enlace($m);
        echo "<div class=\"fz mod\"><span class=\"av sys\">" . icon($fuera ? 'down' : 'up') . "</span><div class=\"fzt\">";
        echo "<div class=\"t\">" . ($href ? "<a href=\"" . h($href) . "\">$que</a>" : $que) . " <span class=\"" . ($fuera ? 'neg' : 'pos') . "\">" . ($fuera ? t('retirado') : t('se queda')) . "</span>";
        $quien = $m['src'] === 'rep' ? t('reportado por %d personas', (int)$m['n']) : ((int)$m['priority'] ? t('reportado') : '');
        echo "<span class=\"meta\">" . ($quien ? " · " . h($quien) : '') . " · " . ($m['modlogin'] ? t('decide %s', '<a href="/u/' . rawurlencode($m['modlogin']) . '">' . h($m['modlogin']) . '</a>') : t('decide un moderador')) . " · " . h(hace($m['ts'])) . "</span></div>";
        echo "<div class=\"prev\">" . h($m['src'] === 'rep' ? t('Motivos: %s', $motivos($m)) : mod_note_line($m)) . "</div></div></div>";
    }
    paginate('/procesos?v=moderacion', $page, $total);
    page_footer();
    exit;
}

/* ---- registros (solo admin): altas activadas y a medias ------------- */
if ($v === 'registros') {
    // Historico permanente (tabla reg_log, migra-102): una fila por intento de alta, pase o no del formulario. El estado de las cuentas que
    // siguen existiendo se lee de users (asi vale tambien si un admin la valida o desvalida a mano); el de las que ya no, de reg_log.
    $dR = $desde('r.created');
    $horas = (int)cfg('token_hours', 48);
    $rows = []; $total = $ok = $rech = 0;
    try {
        [$total, $ok, $rech] = $pdo->query("SELECT COUNT(*), COALESCE(SUM(u.verified = 1 OR (u.user_id IS NULL AND r.estado = 'activada')), 0), COALESCE(SUM(r.estado = 'rechazado'), 0)
                                              FROM reg_log r LEFT JOIN users u ON u.user_id = r.user_id WHERE $dR")->fetch(PDO::FETCH_NUM);
        $total = (int)$total; $ok = (int)$ok; $rech = (int)$rech;
        $st = $pdo->prepare("SELECT r.*, u.user_id AS uid, u.login AS ulogin, u.aura, u.avatar, u.verified, u.token, u.token_sent, u.last_seen
                               FROM reg_log r LEFT JOIN users u ON u.user_id = r.user_id WHERE $dR ORDER BY r.created DESC, r.reg_id DESC LIMIT ? OFFSET ?");
        $st->bindValue(1, $per, PDO::PARAM_INT);
        $st->bindValue(2, page_offset($page), PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();
    } catch (Throwable $e) { echo "<p class=\"error\">Falta la tabla reg_log (sql/migra-102-registro-historico.sql).</p>"; }
    echo "<p class=\"notice\">" . t('%d registros: %d activados, %d a medias (sin confirmar el correo) y %d que no pasaron del formulario. Histórico permanente; solo lo ven los admins.', $total, $ok, $total - $ok - $rech, $rech) . "</p>";
    foreach ($rows as $r) {
        $viva = $r['uid'] !== null; $act = $viva && (int)$r['verified'] === 1;
        if ($r['estado'] === 'rechazado') $estado = t('no pasó') . ' · ' . $r['motivo'];
        else {
            if ($act) $estado = t('activada') . ($r['last_seen'] ? ' · ' . t('último acceso %s', hace($r['last_seen'])) : '');
            elseif ($viva && (!$r['token'] || !$r['token_sent'])) $estado = t('a medias') . ' · ' . t('desactivada a mano');
            elseif ($viva) $estado = t('a medias') . ' · ' . (strtotime($r['token_sent']) < time() - $horas * 3600 ? t('enlace caducado') : t('enlace enviado %s', hace($r['token_sent'])));
            elseif ($r['estado'] === 'activada') $estado = t('activada') . ' · ' . t('cuenta borrada');
            else $estado = t('a medias') . ' · ' . ($r['estado'] === 'caducada' ? t('borrada por no activar') : t('cuenta borrada'));
            if ($r['motivo'] !== '') $estado .= ' · ' . $r['motivo'];
        }
        $login = $viva ? (string)$r['ulogin'] : (string)$r['login'];
        $nombre = $act ? "<a href=\"/u/" . rawurlencode($login) . "\">" . h($login) . "</a>" : h($login !== '' ? $login : '—');
        echo "<div class=\"ulist\">" . avatar((float)($viva ? $r['aura'] : cfg('aura_anon', 5)), $login !== '' ? $login : '?', $viva ? $r['avatar'] : null) . "<div><b>$nombre</b>"
           . "<span class=\"meta\"> · " . h($r['email']) . " · " . h(t('registro %s', hace($r['created']))) . "</span>"
           . "<p class=\"bio\">" . ($act ? h($estado) : "<b>" . h($estado) . "</b>") . "</p></div></div>";
    }
    if (!$rows) echo "<p class=\"end\">" . t('Ningún registro en este periodo.') . "</p>";
    paginate("/procesos?v=registros$qd", $page, $total);
    page_footer();
    exit;
}

/* ---- flujo de actividad -------------------------------------------- */
$parts = [];
if (in_array($v, ['todo', 'envios'], true))
    $parts[] = "SELECT 'envio' AS kind, s.story_id, s.story_id AS ref, s.sent_date AS ts, s.user_id, s.author_alias AS alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta, NULL AS txt
                  FROM stories s WHERE s.status IN $VIVAS AND $dS";
if (in_array($v, ['todo', 'comentarios'], true))
    $parts[] = "SELECT 'comentario' AS kind, c.story_id, c.comment_id AS ref, c.created AS ts, c.user_id, c.alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta, IF(c.deleted = 1, NULL, c.content) AS txt
                  FROM comments c JOIN stories s ON s.story_id = c.story_id
                 WHERE c.status='ok' AND $dC AND s.status IN $VIVAS";
if (in_array($v, ['todo', 'votos'], true))
    $parts[] = "SELECT 'voto' AS kind, vt.story_id, vt.story_id AS ref, vt.created AS ts, vt.user_id, ta.alias, vt.value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta, NULL AS txt
                  FROM votes vt JOIN stories s ON s.story_id = vt.story_id
             LEFT JOIN thread_aliases ta ON ta.story_id = vt.story_id AND ta.user_id = vt.user_id
                 WHERE $dV AND s.status IN $VIVAS";
if (in_array($v, ['todo', 'portadas'], true))
    $parts[] = "SELECT 'portada' AS kind, p.story_id, p.id AS ref, p.created AS ts, NULL AS user_id, NULL AS alias, p.portada AS value,
                       s.title, s.status, s.revealed, s.sent_date, p.delta, NULL AS txt
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

// publicadores que pasan del tope diario (lib/topes.php, 15/10/2026): «· N hoy de dominio» en rojo junto al envio
$TOPE = []; $maxPub = (int)cfg('send_domain_per_day', 5);
if ($maxPub > 0 && ($sids = array_values(array_unique(array_map(fn($e) => (int)$e['story_id'], array_filter($ev, fn($e) => $e['kind'] === 'envio')))))) {
    try {
        $pk = $pdo->query("SELECT story_id, pub_key FROM stories WHERE pub_key IS NOT NULL AND story_id IN (" . implode(',', $sids) . ")")->fetchAll(PDO::FETCH_KEY_PAIR);
        if ($pk) {
            $in = implode(',', array_fill(0, count(array_unique($pk)), '?'));
            $st = $pdo->prepare("SELECT pub_key, COUNT(*) FROM stories WHERE pub_key IN ($in) AND sent_date >= NOW() - INTERVAL 1 DAY AND status NOT IN ('rejected','discard') GROUP BY pub_key");
            $st->execute(array_values(array_unique($pk)));
            $cnt = $st->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($pk as $sid => $k) if (($cnt[$k] ?? 0) >= $maxPub) $TOPE[(int)$sid] = [$k, (int)$cnt[$k]];
        }
    } catch (Throwable $e) {}   // sin la columna (migracion pendiente) no se marca nada
}

$ventana = $d === 'hoy' ? t('solo hoy') : ($d === 'todo' ? t('todo el histórico') : t('últimos %d días', $DIAS));
echo "<p class=\"notice\">" . t('Lo que pasa en el sitio, en orden (%s). Quien envía, comenta o vota aparece con su alias Ghost del hilo hasta %d min después; luego con su nombre.', $ventana, (int)cfg('ghost_minutes', 30)) . "</p>";
if (!$ev) echo "<p class=\"end\">" . t('Todavía no hay actividad de este tipo.') . "</p>";
foreach ($ev as $e) {
    $sid = (int)$e['story_id'];
    $href = "/hilo/$sid";
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
            if (isset($TOPE[$sid])) $txt .= " <span class=\"tope\" title=\"" . h(t('este publicador ha llegado al máximo diario de envíos')) . "\">" . h(t('%d hoy de %s', $TOPE[$sid][1], str_starts_with($TOPE[$sid][0], 'yt:') ? t('ese canal de YouTube') : $TOPE[$sid][0])) . "</span>";
            $ic = 'new'; break;
        case 'comentario':
            $rev = ghost_over($e['ts']);
            $name = display_name(['login' => $row['login'], 'alias' => $e['alias']], $rev);
            $lead = who_avatar($row, $rev, $aura, $name);
            // el comentario (recortado) y, debajo, el hilo (11/10/2026; antes solo «comenta en <hilo>»)
            $frag = trim(preg_replace('/\s+/', ' ', (string)($e['txt'] ?? '')));
            $txt = "<b>" . h($name) . "</b>" . ($frag !== '' ? ": <span class=\"ctxt\">" . h(mb_strimwidth($frag, 0, 220, '…')) . "</span>" : ' ' . t('comenta'))
                 . "<br><small class=\"enhilo\">" . t('en') . " <a class=\"t\" href=\"$href#c{$e['ref']}\">" . h($e['title']) . "</a></small>";
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
paginate("/procesos?v=$v$qd", $page, $total);
page_footer();
