<?php
/** /fav-fuente.php — añadir o quitar un medio o substack de los favoritos del usuario (POST f=source_id, act=guardar|quitar), sin limite.
 *  Sin JS: vuelve a la pagina de origen. El boton «Seguir» lo pinta fuente_fav_form() (lib/fuentes.php). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/fuentes.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login.php');
$sid = (int)post('f');
$src = null;
if (fuentes_on()) foreach (fuentes_activas($pdo) as $s) if ((int)$s['source_id'] === $sid && fuente_es_medio($s)) { $src = $s; break; }
if ($src) { try { fuente_fav_set($pdo, (int)$u['user_id'], $sid, post('act') !== 'quitar'); } catch (Throwable $e) { /* no romper la navegacion */ } }

$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = '/mis-fuentes.php' . ($src ? '?k=' . $src['kind'] : '');
header('Cache-Control: no-store');
redirect($back);
