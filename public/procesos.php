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
 * sola cada 60 s (meta refresh, sin JS).
 */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';

$VIEWS = ['cola' => 'En cola', 'todo' => 'Todo', 'envios' => 'Envíos', 'comentarios' => 'Comentarios', 'votos' => 'Votos', 'portadas' => 'Portadas'];
$v = (string)($_GET['v'] ?? 'cola');
if (!isset($VIEWS[$v])) $v = 'cola';
$page = page_num();
$per  = (int)cfg('per_page', 20);
$VIVAS = "('nuevas','candidatas','destacadas','archived')";   // lo que es publico

/* ---- estimacion: en que ciclo asciende cada nueva -------------------
 * Reproduce la regla del cron (lib/ciclo.php): en cada ciclo sube UNA, la de
 * mayor delta entre las que llevan >= delta_min_age_min. Se asume que los
 * deltas no cambian (no llegan votos): es una estimacion, y asi se dice. */
function cola_eta(array $rows): array {
    $cycle  = max(1, (int)cfg('cycle_minutes', 5)) * 60;
    $minAge = (int)cfg('delta_min_age_min', 30) * 60;
    $now = time();
    $t = $now - ($now % $cycle) + $cycle;            // el cron corre en multiplos de cycle_minutes
    $pend = array_values($rows);                      // ya vienen por delta DESC, sent_date ASC
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
    if ($m <= max(1, (int)cfg('cycle_minutes', 5))) return "candidata en el próximo ciclo (~$m min)";
    if ($m < 120) return "candidata en ~$m min";
    return "candidata en ~" . (int)round($m / 60) . " h";
}

page_header('Procesos', 'procesos', '<meta http-equiv="refresh" content="60">');
echo "<nav class=\"sub\">";
foreach ($VIEWS as $k => $label) echo "<a href=\"/procesos.php" . ($k === 'cola' ? '' : "?v=$k") . "\"" . ($k === $v ? ' class="on"' : '') . ">" . h($label) . "</a>";
echo "</nav>";

if ($v === 'cola') {
    $cycle = max(1, (int)cfg('cycle_minutes', 5));
    $next  = (int)ceil(($cycle * 60 - (time() % ($cycle * 60))) / 60);
    $st = $pdo->query(story_query_base() . " WHERE s.status='nuevas' ORDER BY " . delta_sql() . " DESC, s.sent_date ASC");
    $rows = $st->fetchAll();
    $total = count($rows);
    $eta = cola_eta($rows);
    echo "<p class=\"notice\">$total en cola · próximo ciclo en $next min · cada ciclo asciende <b>una</b> a candidatas: la de mayor δ entre las que llevan más de " . (int)cfg('delta_min_age_min') . " min. La estimación supone que no cambian los votos; se recalcula sola cada minuto.</p>";
    $n = 0;
    foreach (array_slice($rows, page_offset($page), $per) as $i => $s) {
        $pos = page_offset($page) + $i + 1;
        $s['eta_txt'] = "#$pos en la cola · " . (isset($eta[(int)$s['story_id']]) ? eta_txt($eta[(int)$s['story_id']]) : 'sin estimación');
        story_post($pdo, $s, 'compact'); $n++;
    }
    if ($n === 0) echo "<p class=\"end\">No hay nada en cola. <a href=\"/enviar.php\">Envía algo</a>.</p>";
    paginate('/procesos.php', $page, $total);
    page_footer();
    exit;
}

/* ---- flujo de actividad -------------------------------------------- */
$parts = [];
if (in_array($v, ['todo', 'envios'], true))
    $parts[] = "SELECT 'envio' AS kind, s.story_id, s.story_id AS ref, s.sent_date AS ts, s.user_id, s.author_alias AS alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM stories s WHERE s.status IN $VIVAS";
if (in_array($v, ['todo', 'comentarios'], true))
    $parts[] = "SELECT 'comentario' AS kind, c.story_id, c.comment_id AS ref, c.created AS ts, c.user_id, c.alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM comments c JOIN stories s ON s.story_id = c.story_id
                 WHERE c.status='ok' AND s.status IN $VIVAS";
if (in_array($v, ['todo', 'votos'], true))
    $parts[] = "SELECT 'voto' AS kind, vt.story_id, vt.story_id AS ref, vt.created AS ts, vt.user_id, ta.alias, vt.value,
                       s.title, s.status, s.revealed, s.sent_date, s.delta
                  FROM votes vt JOIN stories s ON s.story_id = vt.story_id
             LEFT JOIN thread_aliases ta ON ta.story_id = vt.story_id AND ta.user_id = vt.user_id
                 WHERE s.status IN $VIVAS";
if (in_array($v, ['todo', 'portadas'], true))
    $parts[] = "SELECT 'portada' AS kind, p.story_id, p.id AS ref, p.created AS ts, NULL AS user_id, NULL AS alias, NULL AS value,
                       s.title, s.status, s.revealed, s.sent_date, p.delta
                  FROM promo_log p JOIN stories s ON s.story_id = p.story_id
                 WHERE s.status IN $VIVAS";

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

echo "<p class=\"notice\">Lo que pasa en el sitio, en orden. Quien envía, comenta o vota aparece con su alias Ghost del hilo hasta " . (int)cfg('ghost_minutes', 30) . " min después; luego con su nombre.</p>";
if (!$ev) echo "<p class=\"end\">Todavía no hay actividad de este tipo.</p>";
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
            $txt = "<b>" . h($name) . "</b> envía $title";
            $ic = 'new'; break;
        case 'comentario':
            $rev = ghost_over($e['ts']);
            $name = display_name(['login' => $row['login'], 'alias' => $e['alias']], $rev);
            $lead = who_avatar($row, $rev, $aura, $name);
            $txt = "<b>" . h($name) . "</b> comenta en <a class=\"t\" href=\"$href#c{$e['ref']}\">" . h($e['title']) . "</a>";
            $ic = 'reply'; break;
        case 'voto':
            $rev = ghost_over($e['ts']);
            $alias = $e['alias'] ?: alias_user($pdo, $sid, (int)$e['user_id']);   // voto anterior a los alias: se le asigna ahora
            $name = display_name(['login' => $row['login'], 'alias' => $alias], $rev);
            $lead = who_avatar($row, $rev, $aura, $name);
            $pos = (int)$e['value'] > 0;
            $txt = "<b>" . h($name) . "</b> vota <span class=\"" . ($pos ? 'pos' : 'neg') . "\">" . ($pos ? '+1' : '−1') . "</span> a $title";
            $ic = $pos ? 'up' : 'down'; break;
        default: // portada
            $lead = "<span class=\"av sys\">" . icon('home') . "</span>";
            $txt = "$title llega a <b>portada</b> <span class=\"delta\">δ " . number_format((float)$e['delta'], 1, ',', '') . "</span>";
            $ic = 'home';
    }
    echo "<div class=\"fz k-{$e['kind']}\">$lead<div class=\"fzt\">$txt<span class=\"meta\"> · " . h(hace($e['ts'])) . "</span></div>" . icon($ic) . "</div>";
}
paginate("/procesos.php?v=$v", $page, $total);
page_footer();
