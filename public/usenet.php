<?php
/** /usenet — grupos de noticias de Usenet dentro de Foros (migra-206, 19/11/2026), en solo lectura. Los grupos los marca el admin en
 *  admin → Usenet (lista completa del servidor, con casillas) y los lee el cron (lib/usenet.php). Tres vistas:
 *  /usenet (grupos marcados) · /usenet?g=grupo (hilos, el de ultima actividad arriba) · /usenet?g=grupo&h=N (los mensajes del hilo).
 *  Con usenet_on = 0 solo lo ve el admin, como un foro apagado. Nada de esto se vota ni va al radar o a las portadas.
 *  Comentarios (migra-212): bajo los mensajes del hilo, los registrados pueden comentar; el comentario se queda en Deltha (no se envia a
 *  Usenet) y el hilo comentado ya no caduca. POST act=comentar (g, h, content) y act=quitar (c: el autor o un moderador). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/render.php';
require __DIR__ . '/../lib/usenet.php';
foco_por_defecto();

function usenet_404(string $txt): never { http_response_code(404); page_header(t('Foros') . ' · Usenet', 'foros'); echo "<p class=\"end\">" . h($txt) . ' <a href="/usenet">' . t('Volver a los grupos') . "</a></p>"; page_footer(); exit; }
if (!usenet_visible()) { http_response_code(404); page_header(t('Foros'), 'foros'); echo "<p class=\"end\">" . t('Ese foro no existe.') . "</p>"; page_footer(); exit; }

$grupos = usenet_grupos_activos($pdo);
$gname = strtolower(trim((string)($_GET['g'] ?? '')));
$hid = (int)($_GET['h'] ?? 0);
$g = null;
foreach ($grupos as $x) if ($x['name'] === $gname) $g = $x;
if ($gname !== '' && !$g) usenet_404(t('Ese grupo no existe.'));
$page = page_num();

// ---- comentar / retirar un comentario (se quedan en Deltha)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = user();
    if (!$u) redirect('/login');
    $act = post('act');
    try {
        if ($act === 'quitar') {
            $rid = usenet_comentario_quitar($pdo, (int)post('c'), $u);
            if ($rid && $g) redirect(usenet_url($g['name'], $rid) . '#comentarios');
        } elseif ($act === 'comentar' && $g && $hid && usenet_comentarios_on()) {
            $st = $pdo->prepare("SELECT msg_id, group_id FROM usenet_msgs WHERE msg_id = ? AND group_id = ? AND root_id = 0"); $st->execute([$hid, (int)$g['group_id']]);
            if ($root = $st->fetch()) {
                $r = usenet_comentar($pdo, $root, $u, (string)($_POST['content'] ?? ''));
                if (is_int($r)) redirect(usenet_url($g['name'], $hid) . "#uc$r");
                $_SESSION['flash_err'] = $r;
                redirect(usenet_url($g['name'], $hid) . '#comentarios');
            }
        }
    } catch (Throwable $e) { error_log('usenet comentar: ' . $e->getMessage()); }
    redirect($g ? usenet_url($g['name'], $hid) : '/usenet');
}

/** Pestañas de Foros con «Usenet» encendida. */
function usenet_nav(): void {
    echo "<nav class=\"sub foros\">";
    foreach (foros(is_admin()) as $x) echo "<a href=\"" . h(foro_url($x)) . "\">" . h($x['name']) . ($x['status'] !== 'on' ? " <small>(" . t('apagado') . ")</small>" : '') . "</a>";
    echo "<a href=\"/usenet\" class=\"on\">Usenet" . ((string)cfg('usenet_on', '0') !== '1' ? " <small>(" . t('apagado') . ")</small>" : '') . "</a></nav>";
    if ((string)cfg('usenet_on', '0') !== '1') echo "<p class=\"notice keep\">" . t('Usenet está apagado: solo lo ves tú (admin). Se enciende en admin → Usenet.') . "</p>";
}

if ($g && $hid) {   // ---- un hilo
    $gid = (int)$g['group_id'];
    $st = $pdo->prepare("SELECT * FROM usenet_msgs WHERE msg_id = ? AND group_id = ? AND root_id = 0"); $st->execute([$hid, $gid]);
    $root = $st->fetch();
    if (!$root) usenet_404(t('Ese hilo no existe.'));
    $per = 100; $total = 1 + (int)$root['replies'];
    $st = $pdo->prepare("SELECT * FROM usenet_msgs WHERE group_id = ? AND (msg_id = ? OR root_id = ?) ORDER BY (root_id = 0) DESC, sent, msg_id LIMIT $per OFFSET " . (($page - 1) * $per));
    $st->execute([$gid, $hid, $hid]);
    page_crumb(usenet_url($g['name']), $g['name']);
    page_header($root['subject'], 'foros');
    usenet_nav();
    echo "<div class=\"unet\"><h1>" . h($root['subject']) . "</h1><p class=\"meta\"><a href=\"" . h(usenet_url($g['name'])) . "\">" . h($g['name']) . "</a> · Usenet · " . t('%d mensajes', $total) . "</p>";
    $base = preg_replace('/^(re|rv|aw|fwd?)\s*:\s*/iu', '', (string)$root['subject']);
    foreach ($st as $m) {
        $otro = preg_replace('/^((re|rv|aw|fwd?)\s*:\s*)+/iu', '', (string)$m['subject']);
        echo "<article class=\"unet-msg\" id=\"m{$m['msg_id']}\"><div class=\"meta\"><b>" . h($m['author']) . "</b> · <span title=\"" . h($m['sent']) . "\">" . h(hace($m['sent'])) . "</span>"
           . ((int)$m['root_id'] && mb_strtolower($otro) !== mb_strtolower($base) ? " · " . h($m['subject']) : '') . "</div>";
        echo "<div class=\"unet-body\">" . usenet_texto($m['body']) . "</div></article>";
    }
    echo "</div>";
    paginate(h(usenet_url($g['name'], $hid)), $page, $total, $per);
    // comentarios de Deltha (migra-212): no se envian a Usenet; el hilo comentado no caduca
    $coms = usenet_comentarios($pdo, $hid);
    $u = user();
    if ($coms || usenet_comentarios_on()) {
        echo "<div class=\"unet-coms\" id=\"comentarios\"><h2>" . t('Comentarios en Deltha') . ($coms ? " · " . count($coms) : '') . "</h2>";
        echo "<p class=\"meta\">" . t('Lo que escribas aquí se queda en Deltha: no se envía a Usenet. Un hilo con comentarios se conserva; los demás se borran a los %d días sin actividad.', max(1, (int)cfg('usenet_keep_days', 7))) . "</p>";
        foreach ($coms as $c) {
            $baja = empty($c['login']);
            $n = $baja ? t('cuenta eliminada') : (string)$c['login'];
            $fold = !$baja && ghost_folded((int)$c['user_id']);
            echo "<article class=\"post reply root\" id=\"uc{$c['ucomment_id']}\">" . ($baja ? avatar((float)cfg('aura_anon', 5), '') : avatar((float)$c['aura'], $n, $c['avatar'] ?? null, '/u/' . rawurlencode($n))) . "<div class=\"pc\">";
            echo "<div class=\"l1\"><b>" . ($baja ? h($n) : "<a href=\"/u/" . rawurlencode($n) . "\">" . h($n) . "</a>") . "</b><span class=\"meta\"> · " . h(hace($c['created'])) . "</span></div>";
            if ($fold) echo "<details class=\"gfold\"><summary>" . t('Comentario de alguien a quien has silenciado') . "</summary>";
            echo md_comentario($c['content']);
            if ($fold) echo "</details>";
            if ($u && ((int)$c['user_id'] === (int)$u['user_id'] || is_mod()))
                echo "<div class=\"actions\"><form class=\"inl\" method=\"post\" action=\"" . h(usenet_url($g['name'], $hid)) . "\" data-q=\"" . h(t('¿Eliminar este comentario?')) . "\" onsubmit=\"return confirm(this.dataset.q)\"><input type=\"hidden\" name=\"act\" value=\"quitar\"><input type=\"hidden\" name=\"c\" value=\"{$c['ucomment_id']}\"><button class=\"modlink\">" . t('eliminar') . "</button></form></div>";
            echo "</div></article>";
        }
        if (!usenet_comentarios_on()) { /* solo lectura: lo ya comentado se ve */ }
        elseif (!$u) echo "<p class=\"end\">" . t('<a href="/login">Entra</a> para comentar.') . "</p>";
        else {
            echo "<form class=\"composer\" id=\"ucomentar\" method=\"post\" action=\"" . h(usenet_url($g['name'], $hid)) . "\"><input type=\"hidden\" name=\"act\" value=\"comentar\">" . avatar((float)$u['aura'], (string)$u['login']) . "<div class=\"cbox\">";
            echo "<textarea name=\"content\" maxlength=\"4000\" placeholder=\"" . h(t('Escribe como %s', (string)$u['login'])) . "\" required></textarea>" . md_ayuda();
            if (!empty($_SESSION['flash_err'])) { echo "<p class=\"error\">" . h((string)$_SESSION['flash_err']) . "</p>"; unset($_SESSION['flash_err']); }
            echo "<div class=\"crow\"><button>" . t('Comentar') . "</button></div></div></form>";
        }
        echo "</div>";
    }
    page_footer(); exit;
}

if ($g) {   // ---- hilos de un grupo
    $gid = (int)$g['group_id'];
    $per = (int)cfg('per_page', 20);
    $st = $pdo->prepare("SELECT * FROM usenet_msgs WHERE group_id = ? AND root_id = 0 ORDER BY last_act DESC, msg_id DESC LIMIT $per OFFSET " . page_offset($page));   // * y no la lista de columnas: dcomments no existe antes de la migra-212
    $st->execute([$gid]);
    $filas = $st->fetchAll();
    page_crumb('/usenet', 'Usenet');
    page_header($g['name'] . ' · Usenet', 'foros');
    usenet_nav();
    echo "<p class=\"notice keep\"><b>" . h($g['name']) . "</b>" . ($g['descr'] !== '' ? ' — ' . h(rtrim($g['descr'], '. ')) : '') . ". " . t('Grupo de noticias de Usenet, en solo lectura: lo que se lee aquí se escribe fuera de Deltha.') . ' <a href="/usenet">' . t('Volver a los grupos') . "</a></p>";
    echo "<div class=\"hilos\">";
    foreach ($filas as $s) {
        $n = (int)$s['replies'];
        echo "<article class=\"trow\"><div class=\"tc\"><h2><a href=\"" . h(usenet_url($g['name'], (int)$s['msg_id'])) . "\">" . h($s['subject']) . "</a></h2>";
        $dc = (int)($s['dcomments'] ?? 0);
        echo "<div class=\"meta\"><b>" . h($s['author']) . "</b> · " . h(hace($s['sent'])) . ($dc ? " · <b class=\"unet-dc\">" . t('%d en Deltha', $dc) . "</b>" : '') . "</div></div>";
        echo "<div class=\"tn\"><span class=\"n\" title=\"" . t('respuestas') . "\">" . icon('reply') . "<span>$n</span></span>" . ($n > 0 ? "<span class=\"last\" title=\"" . t('última respuesta') . "\">" . h(hace($s['last_act'])) . "</span>" : '') . "</div></article>";
    }
    echo "</div>";
    if (!$filas) echo "<p class=\"end\">" . t('Todavía no hay mensajes de este grupo.') . "</p>";
    else paginate(h(usenet_url($g['name'])), $page, (int)$g['threads']);
    page_footer(); exit;
}

// ---- grupos marcados
page_header(t('Foros') . ' · Usenet', 'foros');
usenet_nav();
echo "<p class=\"notice keep\">" . t('Grupos de noticias de Usenet, en solo lectura: lo que se lee aquí se escribe fuera de Deltha.') . "</p>";
echo "<div class=\"hilos\">";
foreach ($grupos as $x) {
    echo "<article class=\"trow\"><div class=\"tc\"><h2><a href=\"" . h(usenet_url($x['name'])) . "\">" . h($x['name']) . "</a></h2>";
    echo "<div class=\"meta\">" . h($x['descr'] !== '' ? $x['descr'] : '—') . "</div></div>";
    echo "<div class=\"tn\"><span class=\"n\" title=\"" . t('hilos') . "\">" . icon('reply') . "<span>" . (int)$x['threads'] . "</span></span>" . ($x['last_msg'] ? "<span class=\"last\" title=\"" . t('último mensaje') . "\">" . h(hace($x['last_msg'])) . "</span>" : '') . "</div></article>";
}
echo "</div>";
if (!$grupos) echo "<p class=\"end\">" . t('Todavía no hay grupos.') . (is_admin() ? ' <a href="/admin?t=usenet">admin → Usenet</a>' : '') . "</p>";
page_footer();
