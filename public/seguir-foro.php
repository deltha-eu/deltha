<?php
/** /seguir-foro — seguir o dejar de seguir un foro (POST f=forum_id, act=guardar|quitar; migra-170). Vuelve a la pagina de origen.
 *  El boton lo pinta foro_seguir_form() (lib/app.php); app/scroll.js lo envia con js=1 y vuelve al mismo punto. */
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
$fid = (int)post('f');
$ok = false;
foreach (foros(true) as $f) if ((int)$f['forum_id'] === $fid && $f['status'] === 'on' && foro_abierto($f)) $ok = true;
if ($ok) { try { foro_seguir_set($pdo, (int)$u['user_id'], $fid, post('act') !== 'quitar'); } catch (Throwable $e) {} }
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = '/foros';
header('Cache-Control: no-store');
redirect($back);
