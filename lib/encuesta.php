<?php
/* Encuestas en los foros y en el Chat (migra-204, 16/11/2026). Pedido de Ángel: hasta 10 opciones, de 24 h a 30 días, un voto o
 * varios, cambiar el voto en los primeros 10 minutos, barras debajo y resultados a la vista siempre o solo al cerrar.
 *
 * Tablas: polls (una por hilo de foro o por mensaje del Chat), poll_options (con su contador), poll_votes (quien voto que; una fila
 * por opcion marcada). Votan los usuarios con cuenta; las ven todos. La crea quien abre el hilo o escribe el mensaje, en el mismo
 * formulario, desplegando «Encuesta». Config: poll_max_options (10), poll_min_hours (24), poll_max_days (30), poll_change_min (10). */

/** Duraciones que ofrece el formulario, en horas => texto. Entre poll_min_hours y poll_max_days. */
function encuesta_duraciones(): array {
    $min = max(1, (int)cfg('poll_min_hours', 24)); $max = max($min, (int)cfg('poll_max_days', 30) * 24);
    $todas = [24 => t('24 horas'), 72 => t('3 días'), 168 => t('7 días'), 360 => t('15 días'), 720 => t('30 días')];
    $out = [];
    foreach ($todas as $h => $txt) if ($h >= $min && $h <= $max) $out[$h] = $txt;
    return $out ?: [$min => t('%d horas', $min)];
}

/** Los campos de una encuesta nueva, plegados tras «Encuesta», para el formulario de abrir hilo o de escribir en el Chat. */
function encuesta_form_html(array $f = []): string {
    $max = max(2, (int)cfg('poll_max_options', 10));
    $abierta = !empty($f['poll_opts']);
    $h = "<details class=\"pollnew\"" . ($abierta ? ' open' : '') . "><summary>" . icon('poll') . t('Encuesta') . " <small>" . t('(opcional)') . "</small></summary>";
    $h .= "<label>" . t('Pregunta') . " <small>" . t('(si la dejas vacía, se usa el texto del mensaje)') . "</small></label><input type=\"text\" name=\"poll_q\" maxlength=\"200\" value=\"" . h((string)($f['poll_q'] ?? '')) . "\">";
    $h .= "<label>" . t('Opciones, una por línea (de 2 a %d)', $max) . "</label><textarea name=\"poll_opts\" rows=\"4\" maxlength=\"" . ($max * 121) . "\">" . h((string)($f['poll_opts'] ?? '')) . "</textarea>";
    $h .= "<div class=\"row\"><label>" . t('Dura') . " <select name=\"poll_dur\">";
    foreach (encuesta_duraciones() as $hh => $txt) $h .= "<option value=\"$hh\"" . ((int)($f['poll_dur'] ?? 168) === $hh ? ' selected' : '') . ">" . h($txt) . "</option>";
    $h .= "</select></label> <label><select name=\"poll_multi\"><option value=\"0\">" . t('un voto por persona') . "</option><option value=\"1\"" . (!empty($f['poll_multi']) ? ' selected' : '') . ">" . t('se pueden marcar varias') . "</option></select></label>";
    $h .= " <label><select name=\"poll_results\"><option value=\"siempre\">" . t('resultados a la vista desde el principio') . "</option><option value=\"final\"" . (($f['poll_results'] ?? '') === 'final' ? ' selected' : '') . ">" . t('resultados solo al cerrar') . "</option></select></label></div>";
    $h .= "<small class=\"meta\">" . t('Votan los usuarios con cuenta. El voto se puede cambiar durante los primeros %d minutos.', max(0, (int)cfg('poll_change_min', 10))) . "</small></details>";
    return $h;
}

/** Lee la encuesta del POST. null si no se rellenaron opciones; un string con el error si estan mal; si no, el array listo para crear. */
function encuesta_desde_post(string $textoPorDefecto = ''): array|string|null {
    $opts = array_values(array_unique(array_filter(array_map(fn($l) => mb_substr(trim($l), 0, 120), explode("\n", post('poll_opts'))), fn($l) => $l !== '')));
    if (!$opts) return null;
    $max = max(2, (int)cfg('poll_max_options', 10));
    if (count($opts) < 2) return t('La encuesta necesita al menos dos opciones distintas.');
    if (count($opts) > $max) return t('La encuesta admite como mucho %d opciones.', $max);
    $q = mb_substr(post('poll_q'), 0, 200);
    if ($q === '') $q = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($textoPorDefecto))), 0, 200);
    if ($q === '') return t('La encuesta necesita una pregunta.');
    $dur = (int)post('poll_dur');
    if (!isset(encuesta_duraciones()[$dur])) return t('Elige cuánto dura la encuesta.');
    return ['question' => $q, 'options' => $opts, 'hours' => $dur, 'multi' => post('poll_multi') === '1' ? 1 : 0, 'results' => post('poll_results') === 'final' ? 'final' : 'siempre'];
}

/** Crea la encuesta (dentro de la transaccion del hilo o del mensaje, si la hay). */
function encuesta_crear(PDO $pdo, array $e, int $uid, ?int $storyId, ?int $msgId): int {
    $pdo->prepare("INSERT INTO polls (story_id, msg_id, user_id, question, multi, results, change_min, ends_at) VALUES (?,?,?,?,?,?,?, NOW() + INTERVAL ? HOUR)")
        ->execute([$storyId, $msgId, $uid, $e['question'], $e['multi'], $e['results'], max(0, (int)cfg('poll_change_min', 10)), (int)$e['hours']]);
    $pid = (int)$pdo->lastInsertId();
    $q = $pdo->prepare("INSERT INTO poll_options (poll_id, pos, text) VALUES (?,?,?)");
    foreach (array_values($e['options']) as $i => $o) $q->execute([$pid, $i + 1, $o]);
    return $pid;
}

/** La encuesta de un hilo (null si no tiene o si no esta la migra). */
function encuesta_de_hilo(PDO $pdo, int $sid): ?array {
    try { $q = $pdo->prepare("SELECT * FROM polls WHERE story_id = ? ORDER BY poll_id LIMIT 1"); $q->execute([$sid]); return $q->fetch() ?: null; } catch (Throwable $e) { return null; }
}
/** Las encuestas de unos mensajes del Chat: msg_id => encuesta. */
function encuestas_de_chat(PDO $pdo, array $msgIds): array {
    $msgIds = array_values(array_filter(array_map('intval', $msgIds)));
    if (!$msgIds) return [];
    try {
        $out = [];
        foreach ($pdo->query("SELECT * FROM polls WHERE msg_id IN (" . implode(',', $msgIds) . ")") as $p) $out[(int)$p['msg_id']] = $p;
        return $out;
    } catch (Throwable $e) { return []; }
}

/** Cuantos han votado (personas, no marcas) y las opciones con sus votos. */
function encuesta_datos(PDO $pdo, int $pid): array {
    $opts = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY pos"); $opts->execute([$pid]); $opts = $opts->fetchAll();
    $n = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM poll_votes WHERE poll_id = ?"); $n->execute([$pid]);
    return [$opts, (int)$n->fetchColumn()];
}

/** Lo que ha votado un usuario: option_id => fecha del voto (vacio si nada). */
function encuesta_mi_voto(PDO $pdo, int $pid, int $uid): array {
    $q = $pdo->prepare("SELECT option_id, created FROM poll_votes WHERE poll_id = ? AND user_id = ?"); $q->execute([$pid, $uid]);
    return $q->fetchAll(PDO::FETCH_KEY_PAIR);
}

function encuesta_cerrada(array $p): bool { return strtotime((string)$p['ends_at']) <= time(); }

/** «cierra en 3 d» / «cerrada hace 2 h». */
function encuesta_plazo(array $p): string {
    $s = strtotime((string)$p['ends_at']) - time();
    if ($s <= 0) return t('cerrada %s', hace((string)$p['ends_at']));
    if ($s < 3600) return t('cierra en %d min', intdiv($s, 60));
    if ($s < 86400 * 2) return t('cierra en %d h', intdiv($s, 3600));
    return t('cierra en %d días', (int)round($s / 86400));
}

/** La encuesta pintada: pregunta, opciones para votar (si se puede) y barras (si se ven). $back: adonde volver tras votar. */
function encuesta_html(PDO $pdo, array $p, ?array $u, string $back): string {
    $pid = (int)$p['poll_id'];
    [$opts, $nvot] = encuesta_datos($pdo, $pid);
    if (!$opts) return '';
    $uid = (int)($u['user_id'] ?? 0);
    $mios = $uid ? encuesta_mi_voto($pdo, $pid, $uid) : [];
    $cerrada = encuesta_cerrada($p);
    $votada = !empty($mios);
    $puedeCambiar = $votada && !$cerrada && (time() - strtotime((string)min($mios))) < max(0, (int)$p['change_min']) * 60;
    $verRes = $p['results'] === 'siempre' || $cerrada;
    $total = array_sum(array_map(fn($o) => (int)$o['votes'], $opts));
    $h = "<div class=\"poll" . ($cerrada ? ' closed' : '') . "\" id=\"poll$pid\">";
    if (!empty($_SESSION['flash_err']) && str_ends_with($back, "#poll$pid")) { $h .= "<p class=\"error\">" . h((string)$_SESSION['flash_err']) . "</p>"; unset($_SESSION['flash_err']); }   // el error de /encuesta en el hilo, junto a la encuesta (en el Chat sale arriba)
    $h .= "<div class=\"pollq\">" . icon('poll') . "<b>" . h((string)$p['question']) . "</b> <small class=\"meta\">" . h(encuesta_plazo($p)) . " · " . ($nvot === 1 ? t('1 persona ha votado') : t('%d personas han votado', $nvot)) . ((int)$p['multi'] ? ' · ' . t('se pueden marcar varias') : '') . "</small></div>";
    $votar = $uid && !$cerrada && !$votada;
    if ($votar) $h .= "<form method=\"post\" action=\"/encuesta\" class=\"pollvote\"><input type=\"hidden\" name=\"act\" value=\"votar\"><input type=\"hidden\" name=\"poll\" value=\"$pid\"><input type=\"hidden\" name=\"back\" value=\"" . h($back) . "\">";
    foreach ($opts as $o) {
        $oid = (int)$o['option_id'];
        $pct = $total > 0 ? round((int)$o['votes'] * 100 / $total) : 0;
        $mio = isset($mios[$oid]);
        $h .= "<div class=\"pollopt" . ($mio ? ' mine' : '') . "\">";
        if ($votar) $h .= "<label><input type=\"" . ((int)$p['multi'] ? 'checkbox' : 'radio') . "\" name=\"opt[]\" value=\"$oid\"" . ((int)$p['multi'] ? '' : ' required') . "> " . h((string)$o['text']) . "</label>";
        else $h .= "<span class=\"polltxt\">" . ($mio ? icon('ok') : '') . h((string)$o['text']) . "</span>";
        if ($verRes) $h .= "<span class=\"pollpct\">$pct% <small>(" . (int)$o['votes'] . ")</small></span><div class=\"bar\"><span style=\"width:$pct%\"></span></div>";
        $h .= "</div>";
    }
    if ($votar) $h .= "<div class=\"row\"><button>" . t('Votar') . "</button></div></form>";
    elseif (!$uid && !$cerrada) $h .= "<p class=\"meta\"><a href=\"/login\">" . t('entrar') . "</a> " . t('para votar') . "</p>";
    if (!$verRes) $h .= "<p class=\"meta\">" . t('Los resultados se verán al cerrar la encuesta.') . ($votada ? ' ' . t('Tu voto está registrado.') : '') . "</p>";
    if ($puedeCambiar) $h .= "<form method=\"post\" action=\"/encuesta\" class=\"inline\"><input type=\"hidden\" name=\"act\" value=\"cambiar\"><input type=\"hidden\" name=\"poll\" value=\"$pid\"><input type=\"hidden\" name=\"back\" value=\"" . h($back) . "\"><button class=\"link\">" . t('cambiar mi voto') . "</button> <small class=\"meta\">" . t('(solo durante %d minutos)', (int)$p['change_min']) . "</small></form>";
    return $h . "</div>";
}

/** Vota (una o varias opciones). Devuelve null si todo fue bien o el error. */
function encuesta_votar(PDO $pdo, int $pid, int $uid, array $optIds): ?string {
    $q = $pdo->prepare("SELECT * FROM polls WHERE poll_id = ?"); $q->execute([$pid]); $p = $q->fetch();
    if (!$p) return t('Esa encuesta no existe.');
    if (encuesta_cerrada($p)) return t('La encuesta ya está cerrada.');
    $optIds = array_values(array_unique(array_filter(array_map('intval', $optIds))));
    if (!$optIds) return t('Marca una opción.');
    if (!(int)$p['multi'] && count($optIds) > 1) return t('En esta encuesta solo se puede marcar una opción.');
    $v = $pdo->prepare("SELECT option_id FROM poll_options WHERE poll_id = ?"); $v->execute([$pid]); $validas = array_map('intval', $v->fetchAll(PDO::FETCH_COLUMN));
    foreach ($optIds as $o) if (!in_array($o, $validas, true)) return t('Esa opción no es de esta encuesta.');
    if (encuesta_mi_voto($pdo, $pid, $uid)) return t('Ya has votado en esta encuesta.');
    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT IGNORE INTO poll_votes (poll_id, user_id, option_id) VALUES (?,?,?)");
    $up = $pdo->prepare("UPDATE poll_options SET votes = votes + 1 WHERE option_id = ?");
    foreach ($optIds as $o) { $ins->execute([$pid, $uid, $o]); if ($ins->rowCount()) $up->execute([$o]); }
    $pdo->commit();
    return null;
}

/** Retira el voto para volver a votar, solo en los primeros change_min minutos. */
function encuesta_cambiar(PDO $pdo, int $pid, int $uid): ?string {
    $q = $pdo->prepare("SELECT * FROM polls WHERE poll_id = ?"); $q->execute([$pid]); $p = $q->fetch();
    if (!$p) return t('Esa encuesta no existe.');
    if (encuesta_cerrada($p)) return t('La encuesta ya está cerrada.');
    $mios = encuesta_mi_voto($pdo, $pid, $uid);
    if (!$mios) return t('No has votado en esta encuesta.');
    if ((time() - strtotime((string)min($mios))) >= max(0, (int)$p['change_min']) * 60) return t('El voto solo se puede cambiar durante los primeros %d minutos.', (int)$p['change_min']);
    $pdo->beginTransaction();
    $up = $pdo->prepare("UPDATE poll_options SET votes = GREATEST(0, votes - 1) WHERE option_id = ?");
    foreach (array_keys($mios) as $o) $up->execute([(int)$o]);
    $pdo->prepare("DELETE FROM poll_votes WHERE poll_id = ? AND user_id = ?")->execute([$pid, $uid]);
    $pdo->commit();
    return null;
}
