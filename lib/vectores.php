<?php
/**
 * lib/vectores.php — vectores de titulares (migra-175, 30/10/2026): «¿es la misma noticia?» al momento, sin el PC.
 *
 * Un servicio pequeño en el propio droplet (scripts/vectores/servidor.py, 127.0.0.1:8090, modelo multilingüe de frases via
 * fastembed/ONNX, sin GPU) convierte un titular en un vector de 384 numeros en decenas de milisegundos. Al publicar o corregir
 * un envio se guarda su vector (story_vectors); en el paso 2 de /enviar se compara el titular nuevo con los de los ultimos
 * vectores_dias por coseno (milisegundos en PHP) y solo salen los que pasan de parecidas_vector_min. Lo mismo, con un umbral
 * mas bajo (relacionadas_vector_min), rellena story_related al publicar: la caja «Relacionadas» del hilo.
 *
 * Nada de esto espera: pedir el vector tiene un tope de vectores_timeout_ms (300) y, si el servicio no responde, se cae al
 * filtro de palabras (relacionadas_parecidas()) y, si tampoco, no sale nada. El envio nunca se retrasa ni se bloquea.
 * El modelo del PC (lib/ia.php) ya no interviene aqui: se queda para lo que necesita leer y razonar (moderacion, agentes).
 */

function vec_on(): bool { return cfg('vectores_on', '1') === '1' && trim((string)cfg('vectores_url', 'http://127.0.0.1:8090')) !== ''; }

/** Texto que se vectoriza de un envio: titular y, si hay, el principio de la entradilla. */
function vec_texto_story(array $s): string {
    $t = trim((string)$s['title']);
    $c = trim(preg_replace('/\s+/u', ' ', (string)($s['content'] ?? '')));
    return $c !== '' ? $t . '. ' . mb_substr($c, 0, 200) : $t;
}

/**
 * Pide al servicio los vectores de varios textos. Devuelve [i => [float...]] (normalizados: el coseno es el producto escalar)
 * o null si el servicio no esta o no responde a tiempo. $timeoutMs manda sobre vectores_timeout_ms (el relleno del ciclo usa mas).
 */
function vec_calcular(array $textos, ?int $timeoutMs = null): ?array {
    if (!vec_on() || !$textos || !function_exists('curl_init')) return null;
    $url = rtrim((string)cfg('vectores_url', 'http://127.0.0.1:8090'), '/') . '/embed';
    $ms = $timeoutMs ?? max(50, (int)cfg('vectores_timeout_ms', 300));
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['texts' => array_values($textos)], JSON_UNESCAPED_UNICODE),
                            CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_CONNECTTIMEOUT_MS => min(200, $ms), CURLOPT_TIMEOUT_MS => $ms, CURLOPT_NOSIGNAL => true]);
    $out = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($out === false || $code !== 200) return null;
    $j = json_decode((string)$out, true);
    if (!is_array($j) || !isset($j['vectors']) || count($j['vectors']) !== count($textos)) return null;
    $GLOBALS['VEC_MODELO'] = (string)($j['model'] ?? '');   // el servicio dice que modelo es; se guarda con cada vector
    return array_values($j['vectors']);
}

/** Estado del servicio para admin: ['ok' => bool, 'model' => ..., 'dim' => ..., 'ms' => ...]. */
function vec_estado(): array {
    if (!vec_on()) return ['ok' => false, 'error' => 'apagado (vectores_on = 0 o sin vectores_url)'];
    $t0 = microtime(true);
    $v = vec_calcular(['prueba'], 2000);
    return $v ? ['ok' => true, 'dim' => count($v[0]), 'model' => (string)($GLOBALS['VEC_MODELO'] ?? ''), 'ms' => (int)((microtime(true) - $t0) * 1000)] : ['ok' => false, 'error' => 'no responde en ' . cfg('vectores_url', 'http://127.0.0.1:8090')];
}

function vec_pack(array $v): string { return pack('g*', ...$v); }          // float32 little-endian: 384 x 4 = 1,5 KB por envio
function vec_unpack(string $b): array { return array_values(unpack('g*', $b)); }
function vec_cos(array $a, array $b): float { $s = 0.0; $n = min(count($a), count($b)); for ($i = 0; $i < $n; $i++) $s += $a[$i] * $b[$i]; return $s; }

/** Guarda (o renueva) el vector de un envio. Devuelve true si lo ha guardado. */
function story_vector_guardar(PDO $pdo, int $sid, ?int $timeoutMs = null): bool {
    $q = $pdo->prepare("SELECT story_id, title, content FROM stories WHERE story_id = ?"); $q->execute([$sid]);
    if (!($s = $q->fetch())) return false;
    $v = vec_calcular([vec_texto_story($s)], $timeoutMs);
    if (!$v) return false;
    try {
        $pdo->prepare("INSERT INTO story_vectors (story_id, dim, vec, model) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE dim = VALUES(dim), vec = VALUES(vec), model = VALUES(model), updated = NOW()")
            ->execute([$sid, count($v[0]), vec_pack($v[0]), mb_substr((string)($GLOBALS['VEC_MODELO'] ?? ''), 0, 80)]);
        return true;
    } catch (Throwable $e) { return false; }
}

/**
 * Los envios mas parecidos a un vector: [ [story_id, title, source, content_type, sent_date, sim], ... ] ordenados por parecido,
 * solo los que pasan de $min. Compara contra los vectores de los ultimos vectores_dias (radar, portada y archivadas), en PHP.
 */
function vec_vecinos(PDO $pdo, array $v, float $min, int $max, int $excluir = 0): array {
    $dias = max(1, (int)cfg('vectores_dias', 90));
    try {
        $q = $pdo->prepare("SELECT s.story_id, s.title, s.source, s.content_type, s.sent_date, x.vec FROM story_vectors x JOIN stories s ON s.story_id = x.story_id
                             WHERE s.story_id <> ? AND s.status IN ('nuevas','destacadas','archived') AND s.sent_date >= NOW() - INTERVAL ? DAY ORDER BY s.story_id DESC LIMIT 6000");
        $q->execute([$excluir, $dias]);
    } catch (Throwable $e) { return []; }
    $out = [];
    foreach ($q as $r) {
        $sim = vec_cos($v, vec_unpack((string)$r['vec']));
        if ($sim < $min) continue;
        unset($r['vec']); $r['sim'] = $sim; $out[] = $r;
    }
    usort($out, fn($a, $b) => $b['sim'] <=> $a['sim']);
    return array_slice($out, 0, max(1, $max));
}

/**
 * Para el paso 2 de /enviar: envios que son (casi seguro) la misma noticia que este titular. Null si el servicio de vectores no
 * ha respondido (el que llama cae entonces al filtro de palabras); [] si respondio y no hay ninguna.
 */
function vec_parecidas(PDO $pdo, string $title, string $content = '', int $max = 3): ?array {
    if (trim($title) === '') return [];
    $v = vec_calcular([vec_texto_story(['title' => $title, 'content' => $content])]);
    if (!$v) return null;
    return vec_vecinos($pdo, $v[0], (float)cfg('parecidas_vector_min', 0.75), $max);
}

/** Al publicar o corregir: guarda el vector y rellena story_related (en los dos sentidos) con los vecinos que pasan de relacionadas_vector_min. Devuelve cuantos, o null si no hubo vector. */
function story_relacionadas_calcular(PDO $pdo, int $sid, ?int $timeoutMs = null): ?int {
    if (!story_vector_guardar($pdo, $sid, $timeoutMs)) return null;   // null = sin vector (servicio parado o hilo inexistente): lo reintenta el ciclo
    try {
        $q = $pdo->prepare("SELECT vec FROM story_vectors WHERE story_id = ?"); $q->execute([$sid]);
        $v = vec_unpack((string)$q->fetchColumn());
        $vecinos = vec_vecinos($pdo, $v, (float)cfg('relacionadas_vector_min', 0.65), max(1, (int)cfg('relacionadas_max', 5)) * 2, $sid);
        $pdo->prepare("DELETE FROM story_related WHERE story_id = ?")->execute([$sid]);
        $ins = $pdo->prepare("INSERT INTO story_related (story_id, related_id, score, note) VALUES (?,?,?,NULL) ON DUPLICATE KEY UPDATE score = VALUES(score)");
        foreach ($vecinos as $r) { $sc = round(min(1.0, $r['sim']), 3); $ins->execute([$sid, $r['story_id'], $sc]); $ins->execute([$r['story_id'], $sid, $sc]); }
        return count($vecinos);
    } catch (Throwable $e) { return 0; }
}

/** Relleno por tandas (lo llama el ciclo): envios recientes sin vector. Devuelve cuantos ha hecho. */
function vectores_rellenar(PDO $pdo, int $n = 40): int {
    if (!vec_on()) return 0;
    try {
        $q = $pdo->prepare("SELECT s.story_id FROM stories s LEFT JOIN story_vectors x ON x.story_id = s.story_id
                             WHERE x.story_id IS NULL AND s.status IN ('nuevas','destacadas','archived') AND s.sent_date >= NOW() - INTERVAL ? DAY ORDER BY s.story_id DESC LIMIT " . max(1, $n));
        $q->execute([max(1, (int)cfg('vectores_dias', 90))]);
        $ids = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) { return 0; }
    if (!$ids) return 0;
    $hechos = 0;
    foreach ($ids as $sid) { if (!story_vector_guardar($pdo, $sid, 5000)) break; $hechos++; }   // si el servicio no esta, se para y lo intenta el ciclo siguiente
    return $hechos;
}

/** Relacionadas de un hilo, para mostrarlas (story_related): filas con story_id, title, source, sent_date, score, note. */
function story_relacionadas(PDO $pdo, int $sid): array {
    try {
        $q = $pdo->prepare("SELECT s.story_id, s.title, s.source, s.content_type, s.sent_date, r.score, r.note FROM story_related r JOIN stories s ON s.story_id = r.related_id
                             WHERE r.story_id = ? AND s.status IN ('nuevas','destacadas','archived') ORDER BY r.score DESC, s.sent_date DESC LIMIT " . max(1, (int)cfg('relacionadas_max', 5)));
        $q->execute([$sid]);
        return $q->fetchAll();
    } catch (Throwable $e) { return []; }
}

/* ---- respaldo sin servicio: parecido por palabras del titular -------------------------------------------------------------- */

/** Candidatas por texto completo (ft_stories: titulo + entradilla) de los ultimos vectores_dias: [story_id => fila]. Tope de 300 ms. */
function relacionadas_candidatas(PDO $pdo, array $s, int $max = 15): array {
    $texto = trim((string)$s['title'] . ' ' . mb_substr((string)($s['content'] ?? ''), 0, 300));
    if (mb_strlen($texto) < 12) return [];
    $dias = max(1, (int)cfg('vectores_dias', 90));
    try {
        $q = $pdo->prepare("SELECT /*+ MAX_EXECUTION_TIME(300) */ story_id, title, source, content, content_type, sent_date, MATCH(title, content) AGAINST (? IN NATURAL LANGUAGE MODE) rel
                              FROM stories WHERE story_id <> ? AND status IN ('nuevas','destacadas','archived') AND sent_date >= NOW() - INTERVAL ? DAY
                               AND MATCH(title, content) AGAINST (? IN NATURAL LANGUAGE MODE)
                             ORDER BY rel DESC LIMIT " . max(1, $max));
        $q->execute([$texto, (int)($s['story_id'] ?? 0), $dias, $texto]);
        $out = [];
        foreach ($q as $r) $out[(int)$r['story_id']] = $r;
        return $out;
    } catch (Throwable $e) { return []; }
}

/** Palabras con sustancia de un texto, normalizadas para comparar: sin acentos, en minusculas, sin las vacias (articulos, preposiciones,
 *  pronombres, verbos comodin), de 4 letras o mas, recortadas a 6 para que casen singular/plural y derivados. Conjunto [palabra => true]. */
function palabras_clave(string $t): array {
    static $vacias = null;
    if ($vacias === null) $vacias = array_flip(explode(' ', 'para como pero mas menos este esta esto estos estas aquel aquella sobre entre desde hasta contra segun durante mediante ante bajo tras cada todo toda todos todas otro otra otros otras mismo misma algo alguien nada nadie donde cuando porque aunque mientras tambien ademas solo tiene tienen tener hace hacen hacer dice dicen decir puede pueden poder sera seran sido siendo esta estan estar haber habia hay han ser fue fueron eran unos unas ellos ellas nosotros vosotros usted ustedes sino quien quienes cual cuales cuanto cuanta mucho mucha muchos muchas poco poca pocos pocas nuevo nueva nuevos nuevas gran grande grandes primer primera primero segundo ultimo ultima anos ano'));
    $t = mb_strtolower($t);
    $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'ñ' => 'n', 'ç' => 'c']);
    $out = [];
    foreach (preg_split('/[^a-z0-9]+/', $t, -1, PREG_SPLIT_NO_EMPTY) as $w) {
        if (strlen($w) < 4 || isset($vacias[$w])) continue;
        $out[substr($w, 0, 6)] = true;
    }
    return $out;
}

/** Respaldo de vec_parecidas() cuando el servicio no responde: de las candidatas por texto completo, solo las que comparten con el titular
 *  al menos parecidas_min_comunes (3) palabras con sustancia y parecidas_min (0,5) de las del titular mas corto. Hasta $max. */
function relacionadas_parecidas(PDO $pdo, string $title, string $content = '', int $max = 3): array {
    $a = palabras_clave($title);
    if (count($a) < 2) return [];
    $minComunes = max(1, (int)cfg('parecidas_min_comunes', 3));
    $minFrac = (float)cfg('parecidas_min', 0.5);
    $out = [];
    foreach (relacionadas_candidatas($pdo, ['story_id' => 0, 'title' => $title, 'content' => $content], 15) as $r) {
        $b = palabras_clave((string)$r['title']);
        if (count($b) < 2) continue;
        $comunes = count(array_intersect_key($a, $b));
        $frac = $comunes / min(count($a), count($b));
        if ($comunes < $minComunes || $frac < $minFrac) continue;
        $r['sim'] = $frac; $out[] = $r;
    }
    usort($out, fn($x, $y) => $y['sim'] <=> $x['sim']);
    return array_slice($out, 0, max(1, $max));
}

/** Lo que usa /enviar: vectores si el servicio responde; si no, palabras. Siempre al momento. */
function parecidas_al_enviar(PDO $pdo, string $title, string $content = '', int $max = 3): array {
    $r = vec_parecidas($pdo, $title, $content, $max);
    return $r ?? relacionadas_parecidas($pdo, $title, $content, $max);
}
