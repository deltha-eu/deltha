<?php
/** /editar-chat?id=N — solo administradores: corregir el texto de un mensaje del Chat (los moderadores solo eliminan).
 *  El mensaje queda marcado como «editado» (chat.edited_at, migra-094); el texto original de la primera edicion se guarda
 *  en content_orig. Si cambia el primer #N citado, se actualiza reply_to (de el depende la vista por hilos). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/chat.php';
if (!user()) redirect('/login');
$id = (int)($_GET['id'] ?? post('id'));
$st = $pdo->prepare("SELECT * FROM chat WHERE msg_id = ? AND status = 'ok'");
$st->execute([$id]);
$m = $st->fetch();
$titulo = 'Editar mensaje del Chat';
$back = "/chat?m=$id#m$id";
if (!$m) { http_response_code(404); page_header($titulo); echo "<p class=\"end\">Ese mensaje no existe. <a href=\"/chat\">Volver al Chat</a></p>"; page_footer(); exit; }
if (!is_admin()) { http_response_code(403); page_header($titulo); echo "<p class=\"end\">Solo los administradores pueden editar mensajes del Chat. <a href=\"$back\">Volver al Chat</a></p>"; page_footer(); exit; }
if ((int)$m['deleted'] === 1) { page_header($titulo); echo "<p class=\"end\">Ese mensaje lo eliminó moderación y ya no se edita. <a href=\"$back\">Volver al Chat</a></p>"; page_footer(); exit; }

$maxLen = min(1000, max(50, (int)cfg('chat_max_len', 500)));
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mod_log_post($pdo, 'editar-chat', $err, "mensaje #$id");   // registro (migra-134)
    $txt = mb_substr(trim(preg_replace("/\r\n?/", "\n", post('content'))), 0, $maxLen);
    $anuncio = post('anuncio') === '1' ? 1 : 0;   // mensaje a la comunidad (migra-121): se cambia sin que cuente como edicion del texto
    if ($anuncio !== (int)$m['anuncio']) $pdo->prepare("UPDATE chat SET anuncio = ? WHERE msg_id = ?")->execute([$anuncio, $id]);
    if (mb_strlen($txt) < 1) $err = 'El mensaje no puede quedar vacío.';
    elseif ($txt !== (string)$m['content']) {
        $ref = chat_first_ref($txt);
        if ($ref) { $q = $pdo->prepare("SELECT 1 FROM chat WHERE msg_id = ? AND status = 'ok' AND msg_id < ?"); $q->execute([$ref, $id]); if (!$q->fetchColumn()) $ref = null; }
        $pdo->prepare("UPDATE chat SET content_orig = COALESCE(content_orig, content), content = ?, reply_to = ?, edited_at = NOW() WHERE msg_id = ?")->execute([$txt, $ref, $id]);
        redirect($back);
    } else redirect($back);
    $m['content'] = $txt;
}

page_header("$titulo #$id");
echo "<p class=\"notice\">Edición de administración: el mensaje de <b>" . h(chat_who($m + ['login' => null])) . "</b> quedará marcado como editado. <a href=\"$back\">Volver al Chat</a>.</p>";
if ($err) echo "<p class=\"error\">" . h($err) . "</p>";
echo "<form class=\"plain\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"$id\">";
echo "<label>Texto</label><textarea name=\"content\" rows=\"5\" maxlength=\"$maxLen\" required>" . h((string)$m['content']) . "</textarea>" . md_ayuda();
echo "<label class=\"check\"><input type=\"checkbox\" name=\"anuncio\" value=\"1\"" . ((int)$m['anuncio'] === 1 ? ' checked' : '') . "> Mensaje a la comunidad (sale resaltado y arriba del Chat)</label>";
echo "<button>Guardar</button></form>";
page_footer();
