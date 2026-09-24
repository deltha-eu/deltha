<?php
/** lib/render.php — posts estilo timeline, comentarios, paginacion */
declare(strict_types=1);

function display_name(array $row, bool $revealed): string {
    if ($revealed && !empty($row['login'])) return $row['login'];
    return $row['alias'] ?: t('anónimo');
}

/** $from permite entrar por otra tabla (p. ej. story_communities sc JOIN stories s ...) manteniendo el alias s. */
function story_query_base(string $from = 'FROM stories s'): string {
    return "SELECT s.*, t.name AS topic, t.coef, t.color, u.login, u.aura AS author_aura, u.avatar, co.name AS comm_name, co.slug AS comm_slug, co.skin AS comm_skin,
                   (SELECT GROUP_CONCAT(CONCAT(c2.slug, '|', c2.name) ORDER BY (c2.community_id = s.community_id) DESC, c2.name SEPARATOR '\\n')
                      FROM story_communities sc2 JOIN communities c2 ON c2.community_id = sc2.community_id AND c2.status = 'ok' WHERE sc2.story_id = s.story_id) AS comm_list
              $from
              JOIN topics t ON t.topic_id = s.topic_id
         LEFT JOIN users  u ON u.user_id  = s.user_id
         LEFT JOIN communities co ON co.community_id = s.community_id AND co.status = 'ok'";
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
/** El medio (dominio de la fuente), enlazado a todo lo enviado de ese medio */
function source_link(array $s): string {
    return "<a href=\"/medio.php?m=" . rawurlencode((string)$s['source']) . "\">" . h($s['source']) . "</a>";
}

/** Post de timeline. $mode: full (portadas, originales) | compact (radar) */
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
    $srcLbl = $s['content_type'] === 'article' ? ' · ' . t('original') : ($s['source'] ? " · " . source_link($s) : '');
    echo "<div class=\"l1\"><b>" . h($author) . "</b><span class=\"meta\"><span class=\"sep\"> · </span>" . topic_link($s) . community_links($s) . $srcLbl . " · " . h($when) . (!empty($s['edited_at']) ? " · " . t('editado') : "") . "</span></div>";
    echo "<span class=\"dval\" title=\"" . t('delta en vivo') . "\">δ " . number_format(delta_live($s), 1, ',', '') . "</span>";   // solo lo pinta la plantilla Delta
    echo "<h2><a href=\"" . ((int)$s['comments'] > 0 ? $hrefC : $href) . "\">" . h($s['title']) . "</a></h2>";
    if ($mode === 'full' && $s['content']) echo "<p class=\"lead\">" . h(mb_strimwidth($s['content'], 0, 240, '…')) . "</p>";
    media_block($s, $mode === 'full' ? 'media' : 'media sm');
    if ($s['status'] === 'destacadas' && $s['published_date']) {
        echo "<div class=\"life\" title=\"" . h(queda($s['published_date'])) . "\"><span style=\"width:" . vida_pct($s['published_date']) . "%\"></span></div>";
    }
    if ($mode === 'full') {
        $st = $pdo->prepare("SELECT c.alias, c.aura, c.content, c.created, c.deleted, u.login, u.avatar FROM comments c LEFT JOIN users u ON u.user_id = c.user_id
                             WHERE c.story_id = ? AND c.status='ok' ORDER BY c.created DESC LIMIT 2");
        $st->execute([$s['story_id']]);
        $last = array_reverse($st->fetchAll());
        if ($last) {
            echo "<div class=\"peek\">";
            foreach ($last as $c) {
                $rc = ghost_over($c['created']);
                $n = display_name($c, $rc);
                echo "<div class=\"pk\">" . who_avatar($c, $rc, (float)$c['aura'], $n) . "<div><b>" . h($n) . "</b> " . (!empty($c['deleted']) ? "<i>" . h(t(DDD_DELETED_TXT)) . "</i>" : h(mb_strimwidth($c['content'], 0, 150, '…'))) . "</div></div>";
            }
            echo "</div>";
        }
    }
    if ($mode === 'compact') {
        if ($s['status'] === 'nuevas' && $s['content_type'] !== 'article') {   // en el radar: formula, portada de destino y estimacion
            require_once __DIR__ . '/cola.php';
            global $DDD_ETA;   // la pagina puede dejar calculadas las estimaciones (cola_eta) para no repetirlas por envio
            $p = portada_de($pdo, $s);
            $st = isset($DDD_ETA[(int)$s['story_id']]) ? eta_txt($DDD_ETA[(int)$s['story_id']], $p) : t('hacia %s', t(DDD_PORTADAS[$p])) . ' · ' . radar_falta($s);
            echo "<div class=\"delta\">δ " . h(delta_explica($s)) . " · " . h($st) . "</div>";
        }
    }
    echo "<div class=\"actions\">";
    echo "<a href=\"$hrefC\">" . icon('reply') . "<span>{$s['comments']}</span></a>";
    vote_widget($s);
    echo mod_edit_link($s) . own_edit_link($s);   // mods y admin: «editar» a un clic; el autor, en plazo, tambien
    if (is_fav((int)$s['story_id'])) echo fav_link($s);   // en el feed solo si ya esta guardado: marca y permite quitarlo (se guarda desde el hilo)
    echo share_link($s);
    echo "</div>";
    echo "</div></article>";
}

function vote_widget(array $s): void {
    $id = (int)$s['story_id'];
    $can = user() && in_array($s['status'], ['nuevas','destacadas'], true) && (int)$s['user_id'] !== (int)user()['user_id'];
    if ($can) {
        echo "<form class=\"vote\" method=\"post\" action=\"/votar.php\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
        echo "<button name=\"v\" value=\"1\" title=\"" . t('positivo') . "\">" . icon('up') . "<span>{$s['votes_pos']}</span></button>";
        echo "<button name=\"v\" value=\"-1\" title=\"" . t('negativo') . "\">" . icon('down') . "<span>{$s['votes_neg']}</span></button></form>";
    } else {
        echo "<span class=\"vote\"><span>" . icon('up') . "<span>{$s['votes_pos']}</span></span><span>" . icon('down') . "<span>{$s['votes_neg']}</span></span></span>";
    }
}

function comment_block(array $c, bool $has_next = false, ?array $parent = null, bool $can_reply = false): void {
    $revealed = ghost_over($c['created']);
    $n = display_name($c, $revealed);
    $del = !empty($c['deleted']);
    $cls = 'post reply' . ($has_next ? ' has-next' : '') . (empty($c['root_id']) ? ' root' : ' child') . ($del ? ' deleted' : '');
    echo "<article class=\"$cls\" id=\"c{$c['comment_id']}\">" . who_avatar($c, $revealed, (float)$c['aura'], $n) . "<div class=\"pc\">";
    $nameHtml = ($revealed && !empty($c['login'])) ? "<a href=\"/u.php?u=" . rawurlencode($c['login']) . "\">" . h($n) . "</a>" : h($n);
    echo "<div class=\"l1\"><b>$nameHtml</b><span class=\"meta\"> · " . h(hace($c['created'])) . (!empty($c['edited_at']) && !$del ? " · " . t('editado') : "") . "</span></div>";
    if ($parent && !empty($c['root_id']) && (int)$c['parent_id'] !== (int)$c['root_id']) {
        $pn = display_name($parent, ghost_over($parent['created']));
        echo "<div class=\"meta reto\">" . t('en respuesta a %s', h($pn)) . "</div>";
    }
    if ($del) echo "<p class=\"delmsg\"><i>" . h(t(DDD_DELETED_TXT)) . "</i> · <a href=\"/normas.php\">" . t('normas') . "</a></p>";
    else      echo "<p>" . nl2br(h($c['content'])) . "</p>";
    $acts = [];
    if ($can_reply && !$del) $acts[] = "<a href=\"/hilo.php?id={$c['story_id']}&amp;r={$c['comment_id']}#responder\">" . icon('reply') . "<span>" . t('Responder') . "</span></a>";
    if (!$del && !is_mod() && can_edit_own_comment($c)) $acts[] = "<a class=\"modlink\" href=\"/editar-comentario.php?id={$c['comment_id']}\" title=\"" . t('puedes corregirlo durante %d min', (int)cfg('comment_edit_minutes', 10)) . "\">" . t('editar') . "</a>";
    if (!$del) $acts[] = "<a class=\"replink\" href=\"/reportar.php?id={$c['comment_id']}\" title=\"" . t('avisar a los moderadores') . "\">" . t('reportar') . "</a>";
    if (is_mod() && !$del) $acts[] = "<a class=\"modlink\" href=\"/editar-comentario.php?id={$c['comment_id']}\">" . t('editar · eliminar') . "</a>";
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
        echo "<div class=\"meta\">" . t('Respondiendo a') . " <b>" . h($pn) . "</b> · <a href=\"/hilo.php?id=$story_id\">" . t('cancelar') . "</a></div>";
    }
    $ph = $u ? t('Escribe con alias; tu nombre aparecerá en %d min', (int)cfg('ghost_minutes', 30)) : t('Escribe como anónimo');
    echo "<textarea name=\"content\" maxlength=\"4000\" placeholder=\"" . h($ph) . "\" required" . ($parent ? " autofocus" : "") . "></textarea>";
    echo "<button>" . ($parent ? t('Responder') : t('Comentar')) . "</button></div></form>";
}

function paginate(string $base, int $page, int $total, ?int $per = null): void {
    $per = $per ?: (int)cfg('per_page', 20);
    $pages = max(1, (int)ceil($total / $per));
    if ($pages <= 1) { echo "<p class=\"end\">" . t('Esto es todo lo que hay ahora.') . "</p>"; return; }
    $sep = str_contains($base, '?') ? '&amp;' : '?';   // /procesos.php?v=votos&p=2
    echo "<nav class=\"pages\">";
    if ($page > 1)      echo "<a href=\"$base{$sep}p=" . ($page - 1) . "\">← " . t('anterior') . "</a>";
    echo "<span>$page / $pages</span>";
    if ($page < $pages) echo "<a href=\"$base{$sep}p=" . ($page + 1) . "\">" . t('siguiente') . " →</a>";
    echo "</nav>";
}
function page_num(): int { return max(1, (int)($_GET['p'] ?? 1)); }
function page_offset(int $page): int { return ($page - 1) * (int)cfg('per_page', 20); }

/** Descripcion corta de cada skin (perfil, /plantilla.php, comunidades). '' = la del sitio. */
function skin_descs(): array {
    return ['' => t('La del sitio (%s)', DDD_SKINS[cfg('skin_default', 'editorial')]),
        'editorial' => t('Editorial — limpia, aire de revista'), 'comunidad' => t('Comunidad — foro clásico: barras azules, mensajes con columna de autor, las respuestas por delante'), 'delta' => t('Delta — la identidad gráfica de Deltha, δ a la vista'), 'flujo' => t('Flujo — fotos al lado, más noticias en pantalla'), 'retro' => t('Retrocomputer — ordenador clásico, crema y verde, monoespaciada'), 'manga' => t('Manga — blanco y negro de página impresa, bordes gruesos'), 'punk' => t('Punk — fanzine fotocopiado: negro, fucsia, amarillo ácido'), 'popart' => t('Pop Art — revista gráfica de los sesenta: amarillo, rojo, bordes negros'), 'zen' => t('Zen — agua, flor y silencio; casi un lector'), 'orange' => t('Orange — guiño a Menéame: densa, naranja, votos grandes'), 'foro' => t('Foro — tarjetas sobre gris y pastillas de votos, comentarios y compartir'),
        'hyperpop' => t('Hyperpop / Y2K — nostalgia y futuro: brillante, irreverente, optimista'), 'neobrutal' => t('Neo-Brutal — directo, sin florituras: la información al grano'), 'stickerbomb' => t('Stickerbomb — tu Deltha, tu estilo: pegatinas, personalizable y único'), 'riso' => t('Riso / Zine — estética independiente: cultura en estado puro'), 'aero' => t('Frutiger Aero — optimismo tecnológico: un internet más limpio'), 'hud' => t('Game HUD — información en modo misión'), 'acid' => t('Acid Web — energía, contraste, actitud: para mentes inquietas'), 'pixel' => t('Pixel / 16-bit — espíritu gamer: clásico y atemporal'), 'dopamine' => t('Dopamine — color, energía y claridad: noticias que inspiran'), 'www1998' => t('WWW 1998 — la web antes de los algoritmos: pura esencia'), 'solarpunk' => t('Solarpunk — tecnología con propósito: un futuro más verde'),
        'galactica' => t('Galáctica — puente de mando: acero, ámbar y papel con las esquinas cortadas'), 'impresionismo' => t('Impresionismo — luz de tarde y pinceladas sueltas sobre el lienzo'),
        'grotesque' => t('Grotesque — agregador oscuro y denso, carbón y grotesca, la caja de votos al lado del titular'), 'phantom' => t('Phantom — tarjetas blancas sobre gris, azul eléctrico, miniatura al lado'), 'bw' => t('B/W — Editorial sin un solo color: fotos, avatares y laterales en blanco y negro'), 'surrealismo' => t('Surrealismo — cielo con nubes, cartas que flotan, marcos que se derriten y manzanas verdes')];
}
/** Enlaces «ver antes de elegir»: cada plantilla con ?skin= (solo esa pagina, no se guarda). */
function skin_preview_links(): string {
    $a = [];
    foreach (DDD_SKINS as $k => $v) $a[] = "<a href=\"/?skin=$k\">" . h($v) . "</a>";
    return implode(' · ', $a);
}

/** Selector de skin de una comunidad ('' = ninguna: cada cual ve la suya). Lo usan comunidades.php y comunidad.php. */
function skin_select(string $cur): string {
    $h = "<label>" . t('Skin de la comunidad') . "</label><select name=\"skin\"><option value=\"\">" . t('Ninguna: cada cual ve la suya') . "</option>";
    foreach (DDD_SKINS as $k => $v) $h .= "<option value=\"$k\"" . ($cur === $k ? ' selected' : '') . ">" . h($v) . "</option>";
    return $h . "</select><small class=\"hint\">" . t('Se aplica a la página de la comunidad y a sus hilos, por encima de la skin de cada usuario. Para verlas: ') . skin_preview_links() . "</small>";
}

/** «Compartir»: enlace absoluto al hilo. Con JS (/app/share.js) copia el enlace al portapapeles o abre el menu nativo de compartir; sin JS, lleva al hilo. */
function share_link(array $s): string {
    $url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/hilo.php?id=' . (int)$s['story_id'];
    return "<a class=\"share\" href=\"" . h($url) . "\" data-share data-title=\"" . h($s['title']) . "\" data-copied=\"" . h(t('enlace copiado')) . "\" title=\"" . h(t('copiar el enlace')) . "\">" . icon('share') . "<span>" . t('compartir') . "</span></a>";
}

/* ---- favoritos --------------------------------------------------- */
/** Ids de los envios guardados por el usuario actual (una consulta por pagina, cacheada). */
function fav_ids(): array {
    static $ids = null; global $pdo;
    if ($ids !== null) return $ids;
    $ids = [];
    if (user()) {
        try { $st = $pdo->prepare("SELECT story_id FROM favorites WHERE user_id = ?"); $st->execute([user()['user_id']]); $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)); }
        catch (Throwable $e) { /* tabla aun sin crear */ }
    }
    return $ids;
}
function is_fav(int $story_id): bool { return in_array($story_id, fav_ids(), true); }

/** Boton «guardar» / «guardado» (favoritos), solo para registrados; sin JS: un POST a /favorito.php que vuelve a la misma pagina. */
function fav_link(array $s): string {
    if (!user()) return '';
    $id = (int)$s['story_id'];
    $on = is_fav($id);
    return "<form class=\"fav" . ($on ? ' on' : '') . "\" method=\"post\" action=\"/favorito.php\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"act\" value=\"" . ($on ? 'quitar' : 'guardar') . "\">"
         . "<button title=\"" . h($on ? t('quitar de favoritos') : t('guardar en favoritos')) . "\">" . icon('fav') . "<span>" . ($on ? t('guardado') : t('guardar')) . "</span></button></form>";
}

/** Pestañas del perfil propio (perfil.php, favoritos.php): datos y favoritos. */
function profile_tabs(string $on): void {
    $tabs = ['perfil' => ['/perfil.php', t('Mi perfil')], 'favoritos' => ['/favoritos.php', t('Favoritos')]];
    echo "<nav class=\"sub\">";
    foreach ($tabs as $k => [$href, $label]) echo "<a href=\"$href\"" . ($k === $on ? ' class="on"' : '') . ">" . h($label) . "</a>";
    echo "</nav>";
}
