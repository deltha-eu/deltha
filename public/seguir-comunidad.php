<?php
/** /seguir-comunidad — seguir o dejar de seguir una comunidad (POST c=community_id, act=guardar|quitar; migra-142). Vuelve a la pagina de origen.
 *  El boton lo pinta comunidad_seguir_form() (lib/app.php); app/scroll.js lo envia con js=1 y vuelve al mismo punto. */
require __DIR__ . '/../lib/app.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login');
$cid = (int)post('c');
$q = $pdo->prepare("SELECT community_id FROM communities WHERE community_id = ? AND status = 'ok'"); $q->execute([$cid]);
if ($q->fetchColumn()) { try { comunidad_seguir_set($pdo, (int)$u['user_id'], $cid, post('act') !== 'quitar'); } catch (Throwable $e) {} }
$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = '/comunidades';
header('Cache-Control: no-store');
redirect($back);
