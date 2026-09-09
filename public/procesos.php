<?php
/**
 * /procesos.php — la «fisgona» de Deltha.
 *
 *  ?v=cola          nuevas ordenadas por delta en vivo, con la estimacion de cuando
 *                   asciende cada una a candidatas (simulando el ciclo: una por ciclo,
 *                   la de mayor delta entre las que ya cumplen delta_min_age_min)
 *  ?v=todo          flujo cronologico de toda la actividad
 *  ?v=envios | comentarios | votos | portadas   flujo filtrado
 *
 * Los votantes aparecen con su alias Ghost del hilo (el mismo que tendrian al
 * comentar) hasta que pasan los ghost_minutes desde el voto. La pagina se recarga
 * sola cada 60 s (meta refresh, sin JS). El flujo se limita a procesos_days dias
 * (los indices idx_created de votes/comments lo hacen barato) y para anonimos se
 * emite Cache-Control: max-age=60 para que Cloudflare la sirva desde su cache.
 */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$VIEWS = ['cola' => t('En cola'), 'todo' => t('Todo'), 'envios' => t('Envíos'), 'comentarios' => t('Comentarios'), 'votos' => t('Votos'), 'portadas' => t('Portadas')];
$v = (string)($_GET['v'] ?? 'cola');
if (!isset($VIEWS[$v])) $v = 'cola';
$page = page_num();
$per  = (int)cfg('per_page', 20);
$VIVAS = "('nuevas','candidatas','destacadas','archived')";   // lo que es publico
$DIAS  = max(1, (int)cfg('procesos_days', 7));                    // ventana del flujo: lo demas ya esta en los hilos

/* Cache: la pagina es identica para todos los anonimos (sin cookie), asi que
 * Cloudflare puede servirla durante el minuto que dura el refresco y el servidor
 * la genera una vez. Los registrados ven sus botones de voto: sin cache. */
if (user()) header('Cache-Control: private, no-cache');
else        header('Cache-Control: public, max-age=60');
header('Vary: Cookie');

/* ---- estimacion: en que ciclo asciende cada nueva -------------------
 * Reproduce la regla del cron (lib/ciclo.php): en cada ciclo sube UNA, la de
 * mayor delta entre las que llevan >= delta_min_age_min y tienen delta >=
 * delta_min_candidate. Se asume que los deltas no cambian (no llegan votos):
 * es una estimacion, y asi se dice. Las que no llegan al minimo no tienen ETA. */
/** Instante del proximo ciclo: el cron llama cada minuto y promocionar.php actua
 *  cuando el minuto de la hora es multiplo de cycle_minutes (mismo criterio). */
function proximo_ciclo(): int {
    $cycle = max(1, (int)cfg('cycle_minutes', 5));
    $t = time() - (time() % 60) + 60;                 // siguiente minuto en punto
    while (((int)date('i', $t)) % $cycle !== 0) $t += 60;
    return $t;
}
function cola_eta(array $rows): array {
    $cycle  = max(1, (int)cfg('cycle_minutes', 5)) * 60;
    $minAge = (int)cfg('delta_min_age_min', 30) * 60;
    $minCand = (float)cfg('delta_min_candidate', 0.5);
    $t = proximo_ciclo();
    $pend = array_values(array_filter($rows, fn($r) => (float)$r['delta_live'] >= $minCand));   // ya vienen por delta DESC, sent_date ASC
    $eta = []; $guard = 0;
    while ($pend && $guard++ < 5000) {
        foreach ($pend as $i => $r) {
            if (strtotime($r['sent_date']) + $minAge <= $t) {   // la primera elegible = la de mayor delta
                $eta[(int)$r['story_id']] = $t;
                unset($pend[$i]);
                break;
            }
        }
        $t += $cycle;
    }
    return $eta;
}
function eta_txt(int $ts): string {
    $m = max(1, (int)ceil(($ts - time()) / 60));
    if ($m <= max(1, (int)cfg('cycle_minutes', 5))) return t('candidata en el próximo ciclo (~%d min)', $m);
    if ($m < 120) return t('candidata en ~%d min', $m);
    return t('candidata en ~%d h', (int)round($m / 60));
}

page_header(t('Procesos'), 'procesos', '<meta http-equiv="refresh" content="60">');
echo "<nav class=\"sub\">";
foreach ($VIEWS as $k => $label) echo "<a href=\"/procesos.php" . ($k === 'cola' ? '' : "?v=$k") . "\"" . ($k === $v ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";

if ($v === 'cola') {
    $next  = max(1, (int)ceil((proximo_ciclo() - time()) / 60));
    $st = $pdo->query(str_replace('SELECT s.*,', 'SELECT s.*, ' . delta_sql() . ' AS delta_live,', story_query_base()) . " WHERE s.status='nuevas' ORDER BY delta_live DESC, s.sent_date ASC");
    $rows = $st->fetchAll();
    $total = count($rows);
    $eta = cola_eta($rows);
    echo "<p class=\"notice\">" . t('%d en cola · próximo ciclo en %d min · cada ciclo asciende <b>una</b> a candidatas: la de mayor δ entre las que llevan más de %d min y tienen δ ≥ %s. La estimación supone que no cambian los votos; se recalcula sola cada minuto.', $total, $next, (int)cfg('delta_min_age_min'), num_es(cfg('delta_min_candidate', 0.5))) . "</p>";
    $n = 0;
    // una linea por noticia: puesto, titular, quien/tema/cuando, delta y estimacion; sin avatar ni foto
    foreach (array_slice($rows, page_offset($page), $per) as $i => $s) {
        $pos = page_offset($page) + $i + 1;
        $rev = (int)$s['revealed'] === 1 || ghost_over($s['sent_date']);
        $who = display_name(['login' => $s['login'], 'alias' => $s['author_alias']], $rev);
        $sid = (int)$s['story_id'];
        $etaTxt = isset($eta[$sid]) ? eta_txt($eta[$sid]) : ((float)$s['delta_live'] < (float)cfg('delta_min_candidate', 0.5) ? t('necesita δ ≥ %s para ascender', num_es(cfg('delta_min_candidate', 0.5))) : t('sin estimación'));
        echo "<div class=\"fz cola\" id=\"s$sid\"><span class=\"n\">$pos</span><div class=\"fzt\">";
        echo "<a class=\"t\" href=\"/hilo.php?id=$sid\">" . h($s['title']) . "</a>";
        echo "<span class=\"meta\">" . h($who) . " · " . topic_link($s) . " · " . h(hace($s['sent_date'])) . "</span>";
        echo "<div class=\"delta\" title=\"" . h(delta_explica($s)) . "\">δ " . number_format((float)$s['delta_live'], 1, ',', '') . " · " . h($etaTxt) . "</div></div>";
        echo "<span class=\"vt\">" . icon('up') . (int)$s['votes_pos'] . " " . icon('down') . (int)$s['votes_neg'] . " " . icon('reply') . (int)$s['comments'] . "</span></div>";
        $n++;
    }
    if ($n === 0) echo "<p class=\"end\">" . t('No hay nada en cola. <a href="/enviar.php">Envía algo</a>.') . "</p>";
    paginate('/procesos.php', $page, $total);
    page_footer();
    exit;
}

/* ---- flujo de actividad -------------------------------------------- */
$parts = [];
if (in_array($v, ['todo', 'envios'], true))
    $parts[] = "SELECT 'envio' AS kind, s.story_id, s.story_id AS ref, s.sent_date AS ts, s.user_id, s.author_alias AS alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM stories s WHERE s.status IN $VIVAS AND s.sent_date >= NOW() - INTERVAL $DIAS DAY";
if (in_array($v, ['todo', 'comentarios'], true))
    $parts[] = "SELECT 'comentario' AS kind, c.story_id, c.comment_id AS ref, c.created AS ts, c.user_id, c.alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM comments c JOIN stories s ON s.story_id = c.story_id
                 WHERE c.status='ok' AND c.created >= NOW() - INTERVAL $DIAS DAY AND s.status IN $VIVAS";
if (in_array($v, ['todo', 'votos'], true))
    $parts[] = "SELECT 'voto' AS kind, vt.story_id, vt.story_id AS ref, vt.created AS ts, vt.user_id, ta.alias, vt.value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM votes vt JOIN stories s ON s.story_id = vt.story_id
             LEFT JOIN thread_aliases ta ON ta.story_id = vt.story_id AND ta.user_id = vt.user_id
                 WHERE vt.created >= NOW() - INTERVAL $DIAS DAY AND s.status IN $VIVAS";
if (in_array($v, ['todo', 'portadas'], true))
    $parts[] = "SELECT 'portada' AS kind, p.story_id, p.id AS ref, p.created AS ts, NULL AS user_id, NULL AS alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, p.delta
                  FROM promo_log p JOIN stories s ON s.story_id = p.story_id
                 WHERE p.created >= NOW() - INTERVAL $DIAS DAY AND s.status IN $VIVAS";

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

echo "<p class=\"notice\">" . t('Lo que pasa en el sitio, en orden (últimos %d días). Quien envía, comenta o vota aparece con su alias Ghost del hilo hasta %d min después; luego con su nombre.', $DIAS, (int)cfg('ghost_minutes', 30)) . "</p>";
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
            $txt = "$title " . t('llega a <b>portada</b>') . " <span class=\"delta\">δ " . number_format((float)$e['delta'], 1, ',', '') . "</span>";
            $ic = 'home';
    }
    echo "<div class=\"fz k-{$e['kind']}\">$lead<div class=\"fzt\">$txt<span class=\"meta\"> · " . h(hace($e['ts'])) . "</span></div>" . icon($ic) . "</div>";
}
paginate("/procesos.php?v=$v", $page, $total);
page_footer();
