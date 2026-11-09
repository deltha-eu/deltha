<?php
/**
 * lib/sigfrid.php — Sigfrid, el ayudante del Chat (migra-182, 09/11/2026). El nombre es por el psiquiatra de IA de «Pórtico».
 *
 * Es una cuenta normal (config sigfrid_login, «Sigfrid») que responde en el Chat a quien le habla: con @Sigfrid en cualquier
 * parte del mensaje o empezando por «Sigfrid, …». Contesta siempre como respuesta (#N) al mensaje que le pregunta.
 *
 * Dos capas, y la primera basta para funcionar:
 *   1. Reglas en PHP (sigfrid_intencion): expresiones regulares sobre el texto sin acentos. Lo previsto —radar, ganador y tema del
 *      concurso de relatos, medio mas leido, noticia mas votada o leida, ayuda— se resuelve al instante y sin modelo.
 *   2. Si las reglas no entienden la frase y hay sigfrid_ia_url, se le pregunta de forma SINCRONA a un servidor OpenAI-compatible
 *      (/v1/chat/completions: llama-server, Strata, vLLM...) con un esquema JSON cerrado: el modelo solo elige la accion y el
 *      periodo, nunca redacta la respuesta ni ve datos. Tope sigfrid_ia_timeout_ms; si no llega, la ayuda. Pensado para un modelo
 *      pequeño en el propio droplet (LFM3-1.7B con llama-server), no para la capa asincrona de lib/ia.php: el Chat no puede esperar
 *      a que el PC de la redaccion pida trabajo.
 *
 * Las respuestas salen de consultas fijas (sigfrid_accion) y solo cuentan lo que cualquiera ve en la web: nada de admin ni privado.
 * Los textos van en el idioma de quien pregunta (t(): la peticion es la suya). Nunca rompe el Chat: todo va en try/catch.
 */
declare(strict_types=1);

/** Acciones que Sigfrid sabe hacer (las mismas para las reglas y para el esquema del modelo). */
const SIGFRID_ACCIONES = ['radar', 'concurso_ganador', 'concurso_tema', 'medio_leido', 'noticia_top', 'ayuda'];

function sigfrid_on(): bool { return (string)cfg('sigfrid_enabled', '1') === '1'; }

/** La cuenta de Sigfrid (fila de users) o null si no existe. */
function sigfrid_user(PDO $pdo): ?array {
    static $u = false;
    if ($u !== false) return $u;
    try { $q = $pdo->prepare("SELECT * FROM users WHERE login = ?"); $q->execute([(string)cfg('sigfrid_login', 'Sigfrid')]); $u = $q->fetch() ?: null; }
    catch (Throwable $e) { $u = null; }
    return $u;
}

/** ¿Le hablan a Sigfrid? «@Sigfrid …» en cualquier sitio, o el mensaje empieza por «Sigfrid» seguido de coma, dos puntos o espacio. */
function sigfrid_dirigido(string $content, string $login = ''): bool {
    $l = preg_quote($login !== '' ? $login : (string)cfg('sigfrid_login', 'Sigfrid'), '~');
    return (bool)preg_match("~(?<![\\w@])@$l\\b|^\\s*$l\\s*[,:]?\\s~iu", $content);
}

/** Texto en minusculas, sin acentos, sin la mencion a Sigfrid y con los espacios compactados: sobre esto van las reglas. */
function sigfrid_normaliza(string $t, string $login = ''): string {
    $l = preg_quote($login !== '' ? $login : (string)cfg('sigfrid_login', 'Sigfrid'), '~');
    $t = preg_replace("~(?<![\\w@])@$l\\b|^\\s*$l\\s*[,:]?~iu", ' ', $t);
    $t = mb_strtolower($t, 'UTF-8');
    $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'ç' => 'c']);
    return trim(preg_replace('/\s+/u', ' ', $t));
}

/** Periodo pedido en la frase: hoy, semana (por defecto) o mes. */
function sigfrid_periodo(string $n): string {
    if (preg_match('/\b(hoy|today|avui|hoxe|gaur|oggi|heute|hoje|bugun|aujourd)/u', $n)) return 'hoy';
    if (preg_match('/\b(mes|month|mois|monat|hilabete|mese|ay)\b/u', $n)) return 'mes';
    return 'semana';
}
/** Condicion SQL de fecha para un periodo sobre la columna $col. */
function sigfrid_desde(string $periodo, string $col): string {
    return $periodo === 'hoy' ? "$col >= CURDATE()" : ($periodo === 'mes' ? "$col >= NOW() - INTERVAL 30 DAY" : "$col >= NOW() - INTERVAL 7 DAY");
}
/** «hoy» / «esta semana» / «este mes» para la respuesta. */
function sigfrid_periodo_txt(string $periodo): string {
    return $periodo === 'hoy' ? t('hoy') : ($periodo === 'mes' ? t('este mes') : t('esta semana'));
}

/** Capa 1: reglas. Devuelve ['accion' => …, 'periodo' => …] o null si no entiende la frase. */
function sigfrid_intencion(string $content, string $login = ''): ?array {
    $n = sigfrid_normaliza($content, $login);
    if ($n === '') return ['accion' => 'ayuda', 'periodo' => 'semana'];
    $p = sigfrid_periodo($n);
    if (preg_match('/\b(ayuda|help|comandos|que sabes|que puedes|que haces|quien eres|hola|aide|aiuto|hilfe|ajuda|laguntza|yardim)\b/u', $n)) return ['accion' => 'ayuda', 'periodo' => $p];
    if (preg_match('/\bradar\b|\b(cola|pendientes?)\b.*\bportada\b/u', $n)) return ['accion' => 'radar', 'periodo' => $p];
    $concurso = preg_match('/\b(concurso|microrrelatos?|relatos?|certamen|contest|concours|concorso|wettbewerb|lehiaketa|yarisma|ganad\w*|vencedor|winner|gagnant|vincitore|gewinner|irabazle|kazanan)\b/u', $n) === 1;   // «ganador» a secas tambien es el concurso
    if ($concurso) {
        if (preg_match('/\b(tema|consigna|asunto|de que va|sobre que|theme|thema|gaia|konu)\b/u', $n)) return ['accion' => 'concurso_tema', 'periodo' => $p];
        return ['accion' => 'concurso_ganador', 'periodo' => $p];   // ganador, quien va primero, el mas votado… y por defecto
    }
    if (preg_match('/\b(tema|consigna)\b.*\bsemana\b|\bsemana\b.*\b(tema|consigna)\b/u', $n)) return ['accion' => 'concurso_tema', 'periodo' => $p];
    $super = '/\bm[aá]s\s+(leid|visit|clic|lectur|vist|popular|votad|comentad|seguid)|\b(top|mejor|ranking|mas votos|mas lecturas)\b/u';
    if (preg_match('/\b(noticias?|env[ií]os?|enlaces?|articulos?|historias?|hilos?|posts?)\b/u', $n) && preg_match($super, $n)) return ['accion' => 'noticia_top', 'periodo' => $p];
    if (preg_match('/\b(medios?|periodicos?|diarios?|fuentes?|substacks?|newsletters?|cabeceras?|canal(es)?|webs?)\b/u', $n) && preg_match($super, $n)) return ['accion' => 'medio_leido', 'periodo' => $p];
    if (preg_match($super, $n)) return ['accion' => 'noticia_top', 'periodo' => $p];   // «lo mas leido» a secas: las noticias
    return null;
}

/** Capa 2: el modelo pequeño (si hay sigfrid_ia_url). Solo clasifica: devuelve ['accion', 'periodo'] o null si no hay, falla o tarda. */
function sigfrid_ia(string $content, string $login = ''): ?array {
    $url = trim((string)cfg('sigfrid_ia_url', ''));
    if ($url === '' || !function_exists('curl_init')) return null;
    $texto = sigfrid_normaliza($content, $login);
    if ($texto === '') return null;
    $acciones = implode(' | ', SIGFRID_ACCIONES);
    $system = "Eres el clasificador de Sigfrid, el ayudante del chat de Deltha (un agregador de noticias en español con un concurso semanal de relatos en el foro Originales). "
            . "Lee la frase del usuario y elige UNA accion: radar (que hay en el radar, lo que espera para llegar a portada); concurso_ganador (quien gana o va ganando el concurso de relatos); "
            . "concurso_tema (el tema o consigna del concurso de esta semana); medio_leido (que medio, periodico, substack o fuente es el mas leido o con mas clics); "
            . "noticia_top (la noticia o envio mas votado o mas leido); ayuda (cualquier otra cosa, saludos, o si no esta claro). "
            . "El periodo es hoy, semana o mes (semana si no se dice). Responde SOLO con JSON: {\"accion\": \"$acciones\", \"periodo\": \"hoy|semana|mes\"}.";
    $schema = ['type' => 'object', 'properties' => ['accion' => ['type' => 'string', 'enum' => SIGFRID_ACCIONES], 'periodo' => ['type' => 'string', 'enum' => ['hoy', 'semana', 'mes']]], 'required' => ['accion', 'periodo'], 'additionalProperties' => false];
    $body = ['model' => (string)cfg('sigfrid_ia_model', 'sigfrid'), 'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $texto]],
             'temperature' => 0, 'max_tokens' => 60, 'stream' => false,
             'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'sigfrid', 'strict' => true, 'schema' => $schema]],
             'chat_template_kwargs' => ['enable_thinking' => false], 'reasoning_effort' => 'none'];
    $ms = max(500, (int)cfg('sigfrid_ia_timeout_ms', 4000));
    $hdr = ['Content-Type: application/json'];
    if (($k = trim((string)cfg('sigfrid_ia_key', ''))) !== '') $hdr[] = "Authorization: Bearer $k";
    $ch = curl_init(rtrim($url, '/') . (str_contains($url, '/chat/completions') ? '' : '/v1/chat/completions'));
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => $hdr,
                            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT_MS => min(500, $ms), CURLOPT_TIMEOUT_MS => $ms, CURLOPT_NOSIGNAL => true]);
    $out = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($out === false || $code !== 200) return null;
    $j = json_decode((string)$out, true);
    $txt = (string)($j['choices'][0]['message']['content'] ?? '');
    if ($txt === '' || !preg_match('/\{.*\}/su', $txt, $m)) return null;   // por si el modelo envuelve el JSON en texto
    $r = json_decode($m[0], true);
    if (!is_array($r) || !in_array($r['accion'] ?? '', SIGFRID_ACCIONES, true)) return null;
    return ['accion' => (string)$r['accion'], 'periodo' => in_array($r['periodo'] ?? '', ['hoy', 'semana', 'mes'], true) ? (string)$r['periodo'] : 'semana'];
}

/* ---- las acciones: consultas fijas sobre lo que cualquiera ve en la web ---------------------------------------------------- */

/** Lo que Sigfrid sabe hacer, para la respuesta de ayuda y para el perfil. */
function sigfrid_ayuda(): string {
    return t('Soy Sigfrid, el ayudante del Chat. Pregúntame con @%s delante:', (string)cfg('sigfrid_login', 'Sigfrid')) . ' '
         . t('«muéstrame el radar» · «¿quién va ganando el concurso de relatos?» · «¿cuál es el tema de la semana?» · «¿cuál es el medio más leído?» · «¿cuál es la noticia más votada hoy?»')
         . ' ' . t('Entiendo «hoy», «esta semana» (por defecto) y «este mes».');
}

/** El radar: cuantos esperan por portada y los tres con mas δ. */
function sigfrid_radar(PDO $pdo): string {
    $tot = 0; $por = [];
    foreach ($pdo->query("SELECT portada, COUNT(*) n FROM stories WHERE status = 'nuevas' AND content_type = 'link' GROUP BY portada") as $r) { $por[(string)$r['portada']] = (int)$r['n']; $tot += (int)$r['n']; }
    if ($tot === 0) return t('El radar está vacío: no hay nada esperando para llegar a portada.') . ' ' . sigfrid_enlace(t('Radar'), '/radar');
    $partes = [];
    foreach (portadas() as $k => $lbl) if (!empty($por[$k])) $partes[] = $por[$k] . ' ' . t('hacia %s', t($lbl));
    $top = $pdo->query("SELECT s.story_id, s.title, " . delta_sql() . " AS d FROM stories s JOIN topics t ON t.topic_id = s.topic_id WHERE s.status = 'nuevas' AND s.content_type = 'link' ORDER BY d DESC, s.sent_date ASC LIMIT 3")->fetchAll();
    $lista = array_map(fn($s) => sigfrid_enlace((string)$s['title'], "/hilo/{$s['story_id']}") . ' (δ ' . num_es(round((float)$s['d'], 2)) . ')', $top);
    return t('En el radar hay %d envíos', $tot) . ($partes ? ' (' . implode(', ', $partes) . ')' : '') . '. ' . t('Los más cerca de portada:') . ' ' . implode(' · ', $lista) . '. ' . sigfrid_enlace(t('Ver el radar'), '/radar');
}

/** El hilo de concurso mas reciente del foro Originales (titulo que casa con sigfrid_concurso_regex), o null. */
function sigfrid_concurso(PDO $pdo): ?array {
    $f = foro('originales');
    $re = trim((string)cfg('sigfrid_concurso_regex', 'concurso|microrrelato|relato')) ?: 'concurso';
    $w = "s.content_type = 'article' AND s.status IN ('nuevas','destacadas','archived') AND s.title REGEXP ?" . ($f ? " AND s.forum_id = " . (int)$f['forum_id'] : '');
    $q = $pdo->prepare("SELECT s.story_id, s.title, s.content, s.status, s.sent_date, u.login FROM stories s LEFT JOIN users u ON u.user_id = s.user_id WHERE $w ORDER BY s.sent_date DESC LIMIT 1");
    $q->execute([$re]);
    return $q->fetch() ?: null;
}

/** Quien gana (o va ganando) el concurso: el comentario del hilo con mas soles menos lagrimas; empate, el mas antiguo. */
function sigfrid_concurso_ganador(PDO $pdo): string {
    $c = sigfrid_concurso($pdo);
    if (!$c) return t('No encuentro ningún hilo de concurso en el foro Originales.') . ' ' . sigfrid_enlace(t('Foros'), '/foros?f=originales');
    $hilo = sigfrid_enlace((string)$c['title'], "/hilo/{$c['story_id']}");
    $q = $pdo->prepare("SELECT c.comment_id, c.likes, c.dislikes, u.login FROM comments c LEFT JOIN users u ON u.user_id = c.user_id WHERE c.story_id = ? AND c.status = 'ok' ORDER BY (c.likes - c.dislikes) DESC, c.created ASC LIMIT 3");
    $q->execute([(int)$c['story_id']]);
    $rows = $q->fetchAll();
    if (!$rows) return t('En %s todavía no hay ningún relato.', $hilo);
    $cerrado = $c['status'] === 'archived';
    $fmt = fn($r) => '**' . ($r['login'] !== null ? $r['login'] : t('anónimo')) . '** (' . t('%d soles, %d lágrimas', (int)$r['likes'], (int)$r['dislikes']) . ', ' . sigfrid_enlace(t('leer'), "/hilo/{$c['story_id']}#c{$r['comment_id']}") . ')';
    $primero = array_shift($rows);
    $txt = ($cerrado ? t('En %s ganó %s', $hilo, $fmt($primero)) : t('En %s va ganando %s', $hilo, $fmt($primero)));
    if ($rows) $txt .= '; ' . t('le siguen %s', implode(' ' . t('y') . ' ', array_map($fmt, $rows)));
    return $txt . '.';
}

/** El tema de la semana: titulo y arranque del hilo de concurso. */
function sigfrid_concurso_tema(PDO $pdo): string {
    $c = sigfrid_concurso($pdo);
    if (!$c) return t('No encuentro ningún hilo de concurso en el foro Originales.') . ' ' . sigfrid_enlace(t('Foros'), '/foros?f=originales');
    $cuerpo = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$c['content'])));
    $cuerpo = mb_strimwidth($cuerpo, 0, 280, '…');
    return t('El concurso de esta semana es %s', sigfrid_enlace((string)$c['title'], "/hilo/{$c['story_id']}")) . ($c['login'] ? ' (' . t('lo abrió %s', '**' . $c['login'] . '**') . ')' : '') . ($cuerpo !== '' ? ': ' . $cuerpo : '.');
}

/** Medios con mas lecturas (entradas al hilo) en el periodo, con sus clics. stories.source es el dominio; el nombre sale del catalogo. */
function sigfrid_medio_leido(PDO $pdo, string $periodo): string {
    $rows = $pdo->query("SELECT source, SUM(views) v, SUM(clicks) c, COUNT(*) n FROM stories WHERE content_type = 'link' AND status IN ('nuevas','destacadas','archived') AND source IS NOT NULL AND source <> '' AND "
                       . sigfrid_desde($periodo, 'sent_date') . " GROUP BY source HAVING v > 0 ORDER BY v DESC, c DESC LIMIT 3")->fetchAll();
    if (!$rows) return t('No hay lecturas registradas %s.', sigfrid_periodo_txt($periodo));
    $fmt = function ($r) use ($pdo) {
        $src = function_exists('fuente_por_dominio') ? fuente_por_dominio($pdo, (string)$r['source']) : null;
        $nombre = $src ? sigfrid_enlace((string)$src['name'], fuente_url($src)) : '**' . $r['source'] . '**';
        return $nombre . ' (' . t('%d lecturas, %d clics, %d noticias', (int)$r['v'], (int)$r['c'], (int)$r['n']) . ')';
    };
    $primero = array_shift($rows);
    return t('El medio más leído %s es %s', sigfrid_periodo_txt($periodo), $fmt($primero)) . ($rows ? '; ' . t('le siguen %s', implode(' ' . t('y') . ' ', array_map($fmt, $rows))) : '') . '.';
}

/** La noticia mas votada y la mas leida del periodo (publicadas en portada o aun en el radar). */
function sigfrid_noticia_top(PDO $pdo, string $periodo): string {
    $w = "content_type = 'link' AND status IN ('nuevas','destacadas','archived') AND " . sigfrid_desde($periodo, 'sent_date');
    $v = $pdo->query("SELECT story_id, title, votes_pos, votes_neg, views FROM stories WHERE $w ORDER BY (votes_pos - votes_neg) DESC, views DESC LIMIT 1")->fetch();
    if (!$v) return t('No hay noticias %s.', sigfrid_periodo_txt($periodo));
    $l = $pdo->query("SELECT story_id, title, views FROM stories WHERE $w ORDER BY views DESC, (votes_pos - votes_neg) DESC LIMIT 1")->fetch();
    $txt = t('La noticia más votada %s es %s', sigfrid_periodo_txt($periodo), sigfrid_enlace((string)$v['title'], "/hilo/{$v['story_id']}") . ' (' . t('%d votos', (int)$v['votes_pos'] - (int)$v['votes_neg']) . ')');
    if ($l && (int)$l['story_id'] !== (int)$v['story_id']) $txt .= '; ' . t('la más leída, %s', sigfrid_enlace((string)$l['title'], "/hilo/{$l['story_id']}") . ' (' . t('%d lecturas', (int)$l['views']) . ')');
    elseif ($l) $txt .= ' ' . t('y también la más leída (%d lecturas)', (int)$l['views']);
    return $txt . '.';
}

/** Enlace en las marcas del Chat: [texto](url). md_inline() solo admite URL absolutas (https://…), de ahi abs_url(); el texto va sin
 *  corchetes ni parentesis para no romper la marca. */
function sigfrid_enlace(string $texto, string $url): string {
    return '[' . strtr(mb_strimwidth(trim($texto), 0, 90, '…'), ['[' => '(', ']' => ')']) . '](' . (preg_match('#^https?://#', $url) ? $url : abs_url($url)) . ')';
}

/** Ejecuta una accion y devuelve el texto de la respuesta (marcas del Chat). */
function sigfrid_accion(PDO $pdo, string $accion, string $periodo): string {
    switch ($accion) {
        case 'radar':            return sigfrid_radar($pdo);
        case 'concurso_ganador': return sigfrid_concurso_ganador($pdo);
        case 'concurso_tema':    return sigfrid_concurso_tema($pdo);
        case 'medio_leido':      return sigfrid_medio_leido($pdo, $periodo);
        case 'noticia_top':      return sigfrid_noticia_top($pdo, $periodo);
        default:                 return sigfrid_ayuda();
    }
}

/**
 * Punto de entrada desde /chat tras guardar el mensaje $mid con texto $content de $u (null = anonimo con alias $alias).
 * Si el mensaje va para Sigfrid, publica su respuesta como mensaje del Chat citando #$mid y avisa a quien pregunto.
 */
function sigfrid_contestar(PDO $pdo, int $mid, string $content, ?array $u, string $alias = ''): void {
    if (!sigfrid_on()) return;
    try {
        $sig = sigfrid_user($pdo);
        if (!$sig || ($u && (int)$u['user_id'] === (int)$sig['user_id'])) return;   // no se contesta a si mismo
        $login = (string)$sig['login'];
        if (!sigfrid_dirigido($content, $login)) return;
        $in = sigfrid_intencion($content, $login) ?? sigfrid_ia($content, $login) ?? ['accion' => 'ayuda', 'periodo' => 'semana'];
        $quien = $u ? '@' . $u['login'] : ($alias !== '' ? $alias : '');
        $txt = "#$mid " . ($quien !== '' ? "$quien " : '') . sigfrid_accion($pdo, $in['accion'], $in['periodo']);
        $txt = mb_substr($txt, 0, 1000);   // chat.content es VARCHAR(1000); Sigfrid no pasa por chat_max_len
        $pdo->prepare("INSERT INTO chat (user_id, alias, anon_key, reply_to, content, anuncio) VALUES (?,?,?,?,?,0)")->execute([(int)$sig['user_id'], '', null, $mid, $txt]);
        $nid = (int)$pdo->lastInsertId();
        if (function_exists('avisos_chat')) avisos_chat($pdo, $nid, (int)$sig['user_id'], $login, $txt, $mid);   // «te ha respondido Sigfrid»
    } catch (Throwable $e) { error_log('sigfrid: ' . $e->getMessage()); }
}
