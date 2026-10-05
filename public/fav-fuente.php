<?php
/** /fav-fuente.php — añadir o quitar un medio o substack de los favoritos del usuario (POST f=source_id, act=guardar|quitar), sin limite.
 *  Sin JS: vuelve a la pagina de origen. El boton «Seguir» lo pinta fuente_fav_form() (lib/fuentes.php). */
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/fuentes.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login.php');
$sid = (int)post('f');
// «Seguir todos» desde la ficha de otro usuario (POST all_of=user_id, k=kind): sus favoritos visibles de ese grupo, de golpe
if (post('all_of') !== '' && fuentes_on()) {
    $oid = (int)post('all_of'); $k = (string)post('k');
    if ($oid > 0 && isset(DDD_MEDIA_KINDS[$k]) && ($oid === (int)$u['user_id'] || show_follows($pdo, $oid))) {
        foreach (mis_medios($pdo, ['user_id' => $oid], $k) as $m) if (fuente_seguible($m)) { try { fuente_fav_set($pdo, (int)$u['user_id'], (int)$m['source_id'], true); } catch (Throwable $e) {} }
    }
    $back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
    if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = "/mis-fuentes.php?k=$k";
    header('Cache-Control: no-store');
    redirect($back);
}
$src = null;
if (fuentes_on()) foreach (fuentes_activas($pdo) as $s) if ((int)$s['source_id'] === $sid && fuente_seguible($s)) { $src = $s; break; }
if ($src) { try { fuente_fav_set($pdo, (int)$u['user_id'], $sid, post('act') !== 'quitar'); } catch (Throwable $e) { /* no romper la navegacion */ } }

$back = strtok((string)($_SERVER['HTTP_REFERER'] ?? ''), '#');
if ($back === '' || !preg_match('~^https?://' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '~') . '/~i', $back)) $back = '/mis-fuentes.php' . ($src ? '?k=' . $src['kind'] : '');
header('Cache-Control: no-store');
redirect($back);
