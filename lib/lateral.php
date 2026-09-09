<?php
/** lib/lateral.php — las dos columnas de escritorio (>= 1100 px): a la izquierda la navegacion en vertical
 *  (las dos barras de pestañas, «Publicar» y el perfil); a la derecha, lo que esta pasando, igual para todo el mundo
 *  y sin nada «para ti»: el proximo ciclo con la cola, las comunidades con actividad y el pie.
 *  En movil las dos van ocultas por CSS (display: none) y todo sigue como estaba. Sin JS. */
declare(strict_types=1);
require_once __DIR__ . '/cola.php';
require_once __DIR__ . '/render.php';   // story_query_base(), avatar()

/** Columna izquierda. */
function rail(string $tab): void {
    $u = user();
    echo "<aside class=\"rail\">";
    echo "<a class=\"logo\" href=\"/\" aria-label=\"" . h(cfg('site_name')) . "\"><img src=\"" . logo_src('light') . "\" alt=\"\" class=\"l\"><img src=\"" . logo_src('dark') . "\" alt=\"\" class=\"d\"></a>";
    nav_tabs($tab, 'rail-top');
    nav_tabs($tab, 'rail-bottom');
    rail_skins();
    echo "<a class=\"pub\" href=\"/enviar.php\">" . icon('plus') . "<span>" . t('Publicar') . "</span></a>";
    if ($u) echo "<a class=\"me\" href=\"/perfil.php\">" . avatar((float)$u['aura'], $u['login'], $u['avatar']) . "<span><b>" . h($u['login']) . "</b><small>" . t('aura') . " " . number_format((float)$u['aura'], 1, ',', '') . "</small></span></a>";
    else    echo "<a class=\"me\" href=\"/login.php\">" . icon('user') . "<span><b>" . t('entrar') . "</b><small>" . t('o crear una cuenta') . "</small></span></a>";
    echo "</aside>";
}

/** Tercer grupo del rail: cambio rapido de plantilla (tambien sin cuenta). Un <details> para no alargar el menu. */
function rail_skins(): void {
    $cur = skin();
    $u = user();
    $saved = $u ? (string)($u['skin'] ?? '') : (string)($_COOKIE[DDD_SKIN_COOKIE] ?? '');
    echo "<details class=\"rail-skins\"><summary>" . icon('skin') . "<span>" . t('Plantilla') . "<small>" . h(DDD_SKINS[$cur] ?? $cur) . "</small></span></summary><div class=\"list\">";
    foreach (DDD_SKINS as $k => $v) echo "<a href=\"/cambiar-skin.php?s=$k\"" . ($k === $cur ? ' class="on"' : '') . "><i class=\"sw sw-$k\"></i>" . h($v) . "</a>";
    if ($saved !== '') echo "<a class=\"reset\" href=\"/cambiar-skin.php?s=\">" . t('la del sitio') . "</a>";
    echo "</div></details>";
    // tema claro/oscuro: tres opciones en una fila
    $th = theme() ?? 'auto';
    echo "<div class=\"rail-opts\">" . icon('theme') . "<span>" . t('Aspecto') . "</span><span class=\"opts\">";
    foreach (['auto' => t('auto'), 'light' => t('claro'), 'dark' => t('oscuro')] as $k => $v) echo "<a href=\"/cambiar-skin.php?t=$k\"" . ($k === $th ? ' class="on"' : '') . ">" . h($v) . "</a>";
    echo "</span></div>";
    // idioma
    $cl = lang();
    echo "<div class=\"rail-opts\">" . icon('lang') . "<span>" . t('Idioma') . "</span><span class=\"opts\">";
    foreach (DDD_LANGS as $k => $v) echo "<a href=\"/cambiar-skin.php?l=$k\"" . ($k === $cl ? ' class="on"' : '') . " title=\"" . h($v) . "\">" . strtoupper($k) . "</a>";
    echo "</span></div>";
}

/** Columna derecha. */
function side(): void {
    global $pdo;
    echo "<aside class=\"side\">";
    try { side_ciclo($pdo); } catch (Throwable $e) {}
    try { side_temas($pdo); } catch (Throwable $e) {}
    try { side_comunidades($pdo); } catch (Throwable $e) {}
    try { side_votadas($pdo); } catch (Throwable $e) {}
    echo "<footer class=\"sidefoot\"><a class=\"ctr\" href=\"https://contralgoritmia.com/\" rel=\"noopener\" title=\"" . t('Manifiesto contralgorítmico') . "\"><img src=\"/img/contralgoritmia.png\" alt=\"" . t('Sello contralgorítmico') . "\"></a><p>" . footer_links() . "</p></footer>";
    echo "</aside>";
}

/** Proximo ciclo + las primeras de la cola (mini fisgona). */
function side_ciclo(PDO $pdo): void {
    $next = max(1, (int)ceil((proximo_ciclo() - time()) / 60));
    $st = $pdo->query(str_replace('SELECT s.*,', 'SELECT s.*, ' . delta_sql() . ' AS delta_live,', story_query_base()) . " WHERE s.status='nuevas' ORDER BY delta_live DESC, s.sent_date ASC LIMIT 40");
    $rows = $st->fetchAll();
    $total = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE status='nuevas'")->fetchColumn();
    $eta = cola_eta($rows);
    echo "<section class=\"mod ciclo\"><h3>" . t('Próximo ciclo') . "<span>" . t('en %d min', $next) . "</span></h3>";
    echo "<p class=\"meta\">" . t('%d en cola · sube la de mayor δ', $total) . "</p>";
    $n = 0;
    foreach ($rows as $s) {
        if (!isset($eta[(int)$s['story_id']])) continue;   // solo las que llegan al δ minimo
        $sid = (int)$s['story_id'];
        echo "<a class=\"row\" href=\"/hilo.php?id=$sid\"><b>δ " . number_format((float)$s['delta_live'], 1, ',', '') . "</b><span class=\"t\">" . h(mb_strimwidth($s['title'], 0, 70, '…')) . "</span><small>" . h(eta_txt($eta[$sid])) . "</small></a>";
        if (++$n >= 3) break;
    }
    if ($n === 0) echo "<p class=\"meta\">" . t('Nada llega aún al δ mínimo.') . "</p>";
    echo "<p class=\"more\"><a href=\"/procesos.php\">" . t('ver la cola entera') . "</a></p></section>";
}

/** Temas (las categorias que pone Deltha): pastillas con el color del tema y los envios de la ultima semana; el coeficiente, si no es 1. */
function side_temas(PDO $pdo): void {
    $rows = $pdo->query("SELECT t.topic_id, t.name, t.color, t.coef,
                                (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id AND s.status IN ('nuevas','candidatas','destacadas','archived') AND s.sent_date >= NOW() - INTERVAL 7 DAY) n
                           FROM topics t ORDER BY n DESC, t.name")->fetchAll();
    if (!$rows) return;
    echo "<section class=\"mod temas\"><h3>" . t('Temas') . "<span>" . t('esta semana') . "</span></h3><div class=\"chips\">";
    foreach ($rows as $r) {
        $coef = (float)$r['coef'];
        echo "<a class=\"chip\" href=\"/tema.php?t=" . (int)$r['topic_id'] . "\" style=\"--cc:" . h($r['color']) . "\" title=\"" . ($coef != 1.0 ? h(t('coeficiente %s', number_format($coef, 2, ',', ''))) : '') . "\">" . h($r['name']) . "<b>" . (int)$r['n'] . "</b>" . ($coef != 1.0 ? "<small>×" . number_format($coef, 2, ',', '') . "</small>" : '') . "</a>";
    }
    echo "</div></section>";
}

/** Las mas votadas de las ultimas horas (votos publicos; los clics no se cuentan porque no hay rastreo). */
function side_votadas(PDO $pdo): void {
    $h = (int)cfg('thread_ttl_hours', 48);
    $st = $pdo->prepare("SELECT s.story_id, s.title, s.votes_pos, s.votes_neg, s.comments, s.status
                           FROM stories s WHERE s.status IN ('nuevas','candidatas','destacadas','archived') AND s.sent_date >= NOW() - INTERVAL ? HOUR AND s.votes_pos > 0
                          ORDER BY s.votes_pos DESC, s.comments DESC, s.sent_date DESC LIMIT 6");
    $st->execute([$h]);
    $rows = $st->fetchAll();
    if (!$rows) return;
    echo "<section class=\"mod votadas\"><h3>" . t('Las más votadas') . "<span>" . t('%d h', $h) . "</span></h3>";
    $i = 0;
    foreach ($rows as $r) {
        $i++;
        echo "<a class=\"row\" href=\"/hilo.php?id=" . (int)$r['story_id'] . "\"><b><i>$i</i>" . icon('up') . (int)$r['votes_pos'] . "</b><span class=\"t\">" . h(mb_strimwidth($r['title'], 0, 80, '…')) . "</span></a>";
    }
    echo "</section>";
}

/** Comunidades con actividad: la ultima noticia de cada una, mas reciente primero. */
function side_comunidades(PDO $pdo): void {
    $rows = $pdo->query("SELECT c.name, c.slug, c.color, s.story_id, s.title, s.comments, s.sent_date
                           FROM communities c
                           JOIN stories s ON s.story_id = (SELECT story_id FROM stories WHERE community_id = c.community_id AND status IN ('nuevas','candidatas','destacadas','archived') ORDER BY sent_date DESC LIMIT 1)
                          WHERE c.status = 'ok' ORDER BY s.sent_date DESC LIMIT 8")->fetchAll();
    if (!$rows) return;
    echo "<section class=\"mod comm\"><h3>" . t('En comunidades') . "</h3>";
    foreach ($rows as $r) {
        echo "<a class=\"row\" href=\"/hilo.php?id=" . (int)$r['story_id'] . "\" style=\"--cc:" . h($r['color']) . "\"><i class=\"n\">" . (int)$r['comments'] . "</i><span class=\"t\"><b>" . h($r['name']) . "</b> " . h(mb_strimwidth($r['title'], 0, 80, '…')) . "</span></a>";
    }
    echo "<p class=\"more\"><a href=\"/comunidades.php\">" . t('todas las comunidades') . "</a></p></section>";
}
