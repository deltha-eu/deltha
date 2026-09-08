<?php
/** lib/render.php — posts estilo timeline, comentarios, paginacion */
declare(strict_types=1);

function display_name(array $row, bool $revealed): string {
    if ($revealed && !empty($row['login'])) return $row['login'];
    return $row['alias'] ?: 'anónimo';
}

function story_query_base(): string {
    return "SELECT s.*, t.name AS topic, t.coef, t.color, u.login, u.aura AS author_aura, u.avatar
              FROM stories s
              JOIN topics t ON t.topic_id = s.topic_id
         LEFT JOIN users  u ON u.user_id  = s.user_id";
}

/** Avatar = halo de aura (sin foto: la identidad es el aura) */
function avatar(float $aura, string $initial = '', ?string $photo = null, ?string $link = null): string {
    $max = (float)cfg('aura_max', 50);
    $t = max(0.0, min(1.0, ($aura - 5) / max(1, $max - 5)));
    $ring = number_format(0.2 + 0.8 * $t, 2, '.', '');
    $glow = (int)round(8 * $t);
    $bg = $photo ? ";background-image:url('/media/avatars/" . h($photo) . "')" : '';
    $inner = $photo ? '' : h(mb_substr($initial, 0, 1));
    $html = "<span class=\"av" . ($photo ? ' photo' : '') . "\" style=\"--ring:$ring;--glow:{$glow}px$bg\">$inner</span>";
    return $link ? "<a href=\"" . h($link) . "\" class=\"avl\">$html</a>" : $html;
}
/** Foto y enlace de perfil solo si el nombre esta revelado */
function who_avatar(array $row, bool $revealed, float $aura, string $name): string {
    if ($revealed && !empty($row['login'])) return avatar($aura, $name, $row['avatar'] ?? null, '/u.php?u=' . rawurlencode($row['login']));
    return avatar($aura, $name);
}

function media_block(array $s, string $cls = 'media', ?string $href = null): void {
    if ($s['image']) {
        $href = $href ?? "/hilo.php?id={$s['story_id']}";
        $ext = str_starts_with($href, 'http') ? ' rel="noopener nofollow"' : '';
        echo "<div class=\"$cls\"><a class=\"pic\" href=\"" . h($href) . "\"$ext style=\"background-image:url('/media/" . h($s['image']) . "')\"></a>";
        echo topic_tag($s) . "</div>";
    }
}
/** Etiqueta del tema, con su color, enlazada al feed del tema */
function topic_tag(array $s): string {
    return "<a class=\"tag\" href=\"/tema.php?t=" . (int)$s['topic_id'] . "\">" . h($s['topic']) . "</a>";
}
function topic_link(array $s): string {
    return "<a href=\"/tema.php?t=" . (int)$s['topic_id'] . "\">" . h($s['topic']) . "</a>";
}

/** Post de timeline. $mode: full (portada) | compact (candidatas/nuevas) */
function story_post(PDO $pdo, array $s, string $mode = "full"): void {
    $rev = (int)$s['revealed'] === 1 || ghost_over($s['sent_date']);
    $author = display_name(['login' => $s['login'], 'alias' => $s['author_alias']], $rev);
    $aura = $s['user_id'] ? (float)($s['author_aura'] ?? cfg('aura_start', 5)) : (float)cfg('aura_anon', 5);
    $href = "/hilo.php?id={$s['story_id']}";
    $hrefC = $href . '#comentarios';                       // el hilo, ya bajado a los comentarios
    $when = $s['status'] === 'destacadas' && $s['published_date'] ? hace($s['published_date']) : hace($s['sent_date']);

    echo "<article class=\"post $mode\" id=\"s{$s['story_id']}\">";
    echo who_avatar($s, $rev, $aura, $author);
    echo "<div class=\"pc\">";
    $srcLbl = $s['content_type'] === 'article' ? ' · artículo' : ($s['source'] ? " · " . h($s['source']) : '');
    echo "<div class=\"l1\"><b>" . h($author) . "</b><span class=\"meta\"> · " . topic_link($s) . $srcLbl . " · " . h($when) . "</span></div>";
    echo "<h2><a href=\"" . ((int)$s['comments'] > 0 ? $hrefC : $href) . "\">" . h($s['title']) . "</a></h2>";
    if ($mode === 'full' && $s['content']) echo "<p class=\"lead\">" . h(mb_strimwidth($s['content'], 0, 240, '…')) . "</p>";
    media_block($s, $mode === 'full' ? 'media' : 'media sm');
    if ($s['status'] === 'destacadas' && $s['published_date']) {
        echo "<div class=\"life\" title=\"" . h(queda($s['published_date'])) . "\"><span style=\"width:" . vida_pct($s['published_date']) . "%\"></span></div>";
    }
    if ($mode === 'full') {
        $st = $pdo->prepare("SELECT c.alias, c.aura, c.content, c.created, u.login, u.avatar FROM comments c LEFT JOIN users u ON u.user_id = c.user_id
                             WHERE c.story_id = ? AND c.status='ok' ORDER BY c.created DESC LIMIT 2");
        $st->execute([$s['story_id']]);
        $last = array_reverse($st->fetchAll());
        if ($last) {
            echo "<div class=\"peek\">";
            foreach ($last as $c) {
                $rc = ghost_over($c['created']);
                $n = display_name($c, $rc);
                echo "<div class=\"pk\">" . who_avatar($c, $rc, (float)$c['aura'], $n) . "<div><b>" . h($n) . "</b> " . h(mb_strimwidth($c['content'], 0, 150, '…')) . "</div></div>";
            }
            echo "</div>";
        }
    }
    if ($mode === 'compact') {
        if ($s['status'] === 'candidatas') echo "<div class=\"delta\">δ " . h(delta_explica($s)) . "</div>";
        elseif ($s['status'] === 'nuevas') {
            $age = (int)cfg('delta_min_age_min') - intdiv(time() - strtotime($s['sent_date']), 60);
            $st = $age > 0 ? "elegible en $age min" : "elegible: sube la de mayor delta cada ciclo";
            echo "<div class=\"delta\">δ " . h(delta_explica($s)) . " · $st</div>";
        }
    }
    echo "<div class=\"actions\">";
    echo "<a href=\"$hrefC\">" . icon('reply') . "<span>{$s['comments']}</span></a>";
    vote_widget($s);
    echo "</div>";
    echo "</div></article>";
}

function vote_widget(array $s): void {
    $id = (int)$s['story_id'];
    $can = user() && in_array($s['status'], ['nuevas','candidatas','destacadas'], true) && (int)$s['user_id'] !== (int)user()['user_id'];
    if ($can) {
        echo "<form class=\"vote\" method=\"post\" action=\"/votar.php\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
        echo "<button name=\"v\" value=\"1\" title=\"positivo\">" . icon('up') . "<span>{$s['votes_pos']}</span></button>";
        echo "<button name=\"v\" value=\"-1\" title=\"negativo\">" . icon('down') . "<span>{$s['votes_neg']}</span></button></form>";
    } else {
        echo "<span class=\"vote\"><span>" . icon('up') . "<span>{$s['votes_pos']}</span></span><span>" . icon('down') . "<span>{$s['votes_neg']}</span></span></span>";
    }
}

function comment_block(array $c, bool $has_next = false, ?array $parent = null, bool $can_reply = false): void {
    $revealed = ghost_over($c['created']);
    $n = display_name($c, $revealed);
    $cls = 'post reply' . ($has_next ? ' has-next' : '') . (empty($c['root_id']) ? ' root' : ' child');
    echo "<article class=\"$cls\" id=\"c{$c['comment_id']}\">" . who_avatar($c, $revealed, (float)$c['aura'], $n) . "<div class=\"pc\">";
    $nameHtml = ($revealed && !empty($c['login'])) ? "<a href=\"/u.php?u=" . rawurlencode($c['login']) . "\">" . h($n) . "</a>" : h($n);
    echo "<div class=\"l1\"><b>$nameHtml</b><span class=\"meta\"> · " . h(hace($c['created'])) . "</span></div>";
    if ($parent && !empty($c['root_id']) && (int)$c['parent_id'] !== (int)$c['root_id']) {
        $pn = display_name($parent, ghost_over($parent['created']));
        echo "<div class=\"meta reto\">en respuesta a " . h($pn) . "</div>";
    }
    echo "<p>" . nl2br(h($c['content'])) . "</p>";
    $acts = [];
    if ($can_reply) $acts[] = "<a href=\"/hilo.php?id={$c['story_id']}&amp;r={$c['comment_id']}#responder\">" . icon('reply') . "<span>Responder</span></a>";
    if (is_mod())   $acts[] = "<a class=\"modlink\" href=\"/editar-comentario.php?id={$c['comment_id']}\">editar · eliminar</a>";
    if ($acts) echo "<div class=\"actions\">" . implode('', $acts) . "</div>";
    echo "</div></article>";
}

/** Cuadro de comentario. $parent = comentario al que responde (null = raiz). */
function composer(int $story_id, ?array $parent = null, bool $has_next = false): void {
    $u = user();
    $cls = 'composer' . ($parent ? ' inreply' : '') . ($has_next ? ' has-next' : '');
    echo "<form class=\"$cls\" id=\"responder\" method=\"post\" action=\"/comentar.php\"><input type=\"hidden\" name=\"id\" value=\"$story_id\">";
    if ($parent) echo "<input type=\"hidden\" name=\"parent\" value=\"{$parent['comment_id']}\">";
    echo avatar($u ? (float)$u['aura'] : (float)cfg('aura_anon', 5), $u ? $u['login'] : '');
    echo "<div class=\"cbox\">";
    if ($parent) {
        $pn = display_name($parent, ghost_over($parent['created']));
        echo "<div class=\"meta\">Respondiendo a <b>" . h($pn) . "</b> · <a href=\"/hilo.php?id=$story_id\">cancelar</a></div>";
    }
    $ph = $u ? "Escribe con alias; tu nombre aparecerá en " . (int)cfg('ghost_minutes', 30) . " min" : "Escribe como anónimo";
    echo "<textarea name=\"content\" maxlength=\"4000\" placeholder=\"" . h($ph) . "\" required" . ($parent ? " autofocus" : "") . "></textarea>";
    echo "<button>" . ($parent ? "Responder" : "Comentar") . "</button></div></form>";
}

function paginate(string $base, int $page, int $total): void {
    $per = (int)cfg('per_page', 20);
    $pages = max(1, (int)ceil($total / $per));
    if ($pages <= 1) { echo "<p class=\"end\">Esto es todo lo que hay ahora.</p>"; return; }
    echo "<nav class=\"pages\">";
    if ($page > 1)      echo "<a href=\"$base?p=" . ($page - 1) . "\">← anterior</a>";
    echo "<span>$page / $pages</span>";
    if ($page < $pages) echo "<a href=\"$base?p=" . ($page + 1) . "\">siguiente →</a>";
    echo "</nav>";
}
function page_num(): int { return max(1, (int)($_GET['p'] ?? 1)); }
function page_offset(int $page): int { return ($page - 1) * (int)cfg('per_page', 20); }
