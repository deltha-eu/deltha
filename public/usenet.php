<?php
/** /usenet — grupos de noticias de Usenet dentro de Foros (migra-206, 19/11/2026), en solo lectura. Los grupos los marca el admin en
 *  admin → Usenet (lista completa del servidor, con casillas) y los lee el cron (lib/usenet.php). Tres vistas:
 *  /usenet (grupos marcados) · /usenet?g=grupo (hilos, el de ultima actividad arriba) · /usenet?g=grupo&h=N (los mensajes del hilo).
 *  Con usenet_on = 0 solo lo ve el admin, como un foro apagado. Nada de esto se vota, se comenta ni va al radar o a las portadas. */
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
    page_footer(); exit;
}

if ($g) {   // ---- hilos de un grupo
    $gid = (int)$g['group_id'];
    $per = (int)cfg('per_page', 20);
    $st = $pdo->prepare("SELECT msg_id, subject, author, sent, replies, last_act FROM usenet_msgs WHERE group_id = ? AND root_id = 0 ORDER BY last_act DESC, msg_id DESC LIMIT $per OFFSET " . page_offset($page));
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
        echo "<div class=\"meta\"><b>" . h($s['author']) . "</b> · " . h(hace($s['sent'])) . "</div></div>";
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
