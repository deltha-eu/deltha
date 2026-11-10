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
 *      periodo, nunca redacta la respuesta ni ve datos. Tope sigfrid_ia_timeout_ms (8 s); si no llega, la ayuda. Pensado para un modelo
 *      pequeño en el propio droplet (LFM3-1.7B con llama-server), no para la capa asincrona de lib/ia.php: el Chat no puede esperar
 *      a que el PC de la redaccion pida trabajo.
 *
 * Memoria del hilo (sigfrid_contexto): «¿y este mes?» o «¿y hoy?» tras una pregunta heredan su accion con el periodo nuevo.
 * Las respuestas salen de consultas fijas (sigfrid_accion) y solo cuentan lo que cualquiera ve en la web: nada de admin ni privado.
 * Los textos van en el idioma de quien pregunta (t(): la peticion es la suya). Nunca rompe el Chat: todo va en try/catch.
 */
declare(strict_types=1);

/** Acciones que Sigfrid sabe hacer (las mismas para las reglas y para el esquema del modelo). */
const SIGFRID_ACCIONES = ['radar', 'portada', 'concurso_ganador', 'concurso_tema', 'medio_leido', 'noticia_top', 'comunidad_activa', 'temas', 'top_enviador', 'conectados', 'proximo_ciclo', 'mis_envios', 'ayuda'];
/** Acciones a las que el periodo (hoy / semana / mes) les cambia la respuesta: una continuacion «¿y este mes?» solo tiene sentido con estas. */
/** Lo que contesta a cualquier pregunta que no vaya de Deltha (decision de Angel, 09/11/2026; sin traducir: es un chiste). Veinte variantes
 *  al estilo de Chiquito de la Calzada, una al azar cada vez (sigfrid_fuera()); todas dicen lo mismo: que solo atiende cosas de Deltha. */
const SIGFRID_FUERAS = [
    'Estimado fistro diodenal, solo atiendo cuestiones relacionadas con Deltha. ¡Hasta luego Lucas!',
    '¡No puedor, no puedor! Eso no va de Deltha, pecador de la pradera. Pregúntame por el radar o la portada y hablamos.',
    'Te das cuen, torpedo: aquí solo se habla de Deltha. Lo demás, por la gloria de mi madre, no me lo sé.',
    '¡Jarl! Eso es del mundo exterior. Yo soy un fistro de Deltha y de Deltha no me muevo. ¡Condemor!',
    'Pecadorrr de la praderaaa, que eso no es de Deltha. Pregúntame cosas de aquí dentro, que es donde sé ir.',
    'Siéntate ahí, criaturita, que te lo explico: solo atiendo cuestiones de Deltha. Lo tuyo no lo veo, ¡hasta luego Lucas!',
    'Eso no me lo sé, cobarde. Que yo soy el ayudante de Deltha, no el teletexto. ¡Al ataquerrr… pero con el radar!',
    '¡Fistro diodenal! Eso se sale de Deltha por la tangente. Vuelve con una pregunta de la casa y te la miro.',
    'Por la gloria de mi madre que eso no va de Deltha. ¿Te da cuen? Pregúntame por las comunidades, que ahí me duelen prendas.',
    'No puedorrr. Mi cabeza está en el radar y en la portada de Deltha; lo de fuera, que lo mire otro fistro.',
    '¡Gromenauer! Eso no es de Deltha ni con ganas. Si quieres saber quién va ganando el concurso, eso sí, pecador.',
    'Jarl, qué pregunta más sexy, pero no va de Deltha. Yo solo sé de lo que pasa aquí dentro. ¡Hasta luego Lucas!',
    'Te lo digo con cariño, torpedo: eso no es de Deltha. Pregúntame por el medio más leído y te caerás de espaldas.',
    'Eso es del grupo Toro, pecador, no de Deltha. Yo atiendo lo de aquí: radar, portada, foros y poco más.',
    'Condemor, condemor… eso se escapa de Deltha. Tráeme una pregunta de la casa y verás qué finura.',
    '¡Ay, mi fistro! Eso no va de Deltha. Yo soy un bot de barrio: solo sé lo que pasa en esta web.',
    'Caballo blanco, caballo negro, pero de Deltha. Lo demás no lo atiendo, pecador de la pradera.',
    'No te digo trigo por no llamarte Rodrigor, pero eso no va de Deltha. Pregunta por aquí dentro y te lo miro.',
    'Ese tema me pilla fuera de Deltha, diodenal. Lo que sí: quién ha enviado más, qué hay en portada y cuántos estamos. ¡Jarl!',
    'Hasta luego Lucas, que eso no es de Deltha. Cuando quieras saber del radar o del concurso, aquí me tienes, fistro.',
];
const SIGFRID_FUERA = SIGFRID_FUERAS[0];   // la de siempre, por si alguien la referencia
/** Las de Chiquito solo en castellano; en los demas idiomas, una frase neutra en el idioma de quien pregunta (10/11/2026). */
function sigfrid_fuera(?string $lang = null): string {
    $lang = $lang ?? lang();
    if ($lang !== 'es') return t_in($lang, 'Solo atiendo cuestiones relacionadas con Deltha: pregúntame por el radar, la portada, los medios o las comunidades.');
    return SIGFRID_FUERAS[random_int(0, count(SIGFRID_FUERAS) - 1)];
}
const SIGFRID_CON_PERIODO = ['medio_leido', 'noticia_top', 'comunidad_activa', 'temas', 'top_enviador', 'mis_envios'];

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

/** Periodo que la frase dice expresamente, o null si no dice ninguno (para heredarlo de la pregunta anterior). */
function sigfrid_periodo_explicito(string $n): ?string {
    if (preg_match('/\b(hoy|today|avui|hoxe|gaur|oggi|heute|hoje|bugun|aujourd)/u', $n)) return 'hoy';
    if (preg_match('/\b(mes|month|mois|monat|hilabete|mese|ay)\b/u', $n)) return 'mes';
    if (preg_match('/\b(semana|week|setmana|astea?|settimana|woche|hafta)\b/u', $n)) return 'semana';
    return null;
}
/** Periodo pedido en la frase: hoy, semana (por defecto) o mes. */
function sigfrid_periodo(string $n): string { return sigfrid_periodo_explicito($n) ?? 'semana'; }
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
    if (preg_match('/\b(ayuda|help|comandos|que sabes|que puedes|que haces|quien eres|hola|aide|aiuto|hilfe|ajuda|laguntza|yardim|yardım)\b/u', $n)) return ['accion' => 'ayuda', 'periodo' => $p];
    if (preg_match('/\bradar\b|\b(cola|pendientes?)\b.*\bportada\b/u', $n)) return ['accion' => 'radar', 'periodo' => $p];
    $super = '/\bm[aá]s\s+(leid|visit|clic|lectur|vist|popular|votad|comentad|seguid|activ|movid)|\b(top|mejor|ranking|mas votos|mas lecturas|most|plus|piu|meist|gehien|en cok)\b/u';
    $superIn = '(?:' . substr($super, 1, -2) . ')';   // el mismo patron, agrupado, para meterlo dentro de otra expresion
    // lo mio: mis envios, mi aura, mis comentarios (solo con cuenta; a un anonimo se le dice que entre)
    if (preg_match('/\b(mis|mi|my|meus?|mes|miei|mie|meine?|nire|benim|els meus|os meus)\b.*\b(env[ií]os?|noticias?|aura|comentarios?|posts?|stats|estad[ií]sticas?|n[uú]meros|cuenta|perfil)\b|\bcu[aá]nt[ao]\s+aura\b|\bcomo voy\b/u', $n)) return ['accion' => 'mis_envios', 'periodo' => $p];
    // cuanta gente hay ahora
    if (preg_match('/\bconectad|\bonline\b|\ben l[ií]nea\b|\bcu[aá]nt[oa]s\s+(estamos|somos|hay|sois)\b|\b(usuarios?|gente|personas?)\b.*\b(hay|activ|ahora)\b|\bwho is here\b|\bnor dago\b/u', $n)) return ['accion' => 'conectados', 'periodo' => $p];
    // proximo ciclo (cuando sube la siguiente a candidatas / portada)
    if (preg_match('/\b(ciclo|cycle|zyklus|ziklo|d[oö]ng[uü]|cicle)\b|\bcu[aá]ndo\b.*\b(portada|sube|siguiente|pr[oó]xim)/u', $n)) return ['accion' => 'proximo_ciclo', 'periodo' => $p];
    // comunidades: la mas activa / con mas envios
    if (preg_match('/\bcomunidad(es)?\b|\bcommunit(y|ies)\b|\bcomunitats?\b|\bkomunitate|\btopluluk|\bgemeinschaft|\bcomunit[aà]/u', $n)) return ['accion' => 'comunidad_activa', 'periodo' => $p];
    // quien envia mas / usuario mas activo
    if (preg_match('/\b(qui[eé]n(es)?|who|qui|chi|wer|nork|kim)\b.*\b(env[ií]a|enviado|env[ií]os?|publica|publicado|manda|posted|posts|comenta|comentado)\b|\b(usuari[oa]s?|users?|utilisateurs?|utenti|benutzer|erabiltzaile|kullan[ıi]c[ıi])\b.*' . $superIn . '|' . $superIn . '.*\b(usuari[oa]s?|users?)\b/u', $n)) return ['accion' => 'top_enviador', 'periodo' => $p];
    // que hay en portada (sin «mas votado»: eso es noticia_top)
    if (preg_match('/\bportadas?\b|\bfront ?page\b|\bprima pagina\b|\btitelseite\b|\bazalean?\b|\bman[sş]et\b|\bla une\b/u', $n) && !preg_match($super, $n)) return ['accion' => 'portada', 'periodo' => $p];
    $concurso = preg_match('/\b(concurso|microrrelatos?|relatos?|certamen|contest|concours|concorso|wettbewerb|lehiaketa|yarisma|ganad\w*|vencedor|winner|gagnant|vincitore|gewinner|irabazle|kazanan)\b/u', $n) === 1;   // «ganador» a secas tambien es el concurso
    if ($concurso) {
        if (preg_match('/\b(tema|tem[aá]tica|consigna|asunto|de que va|sobre que|theme|thema|gaia|konu)\b/u', $n)) return ['accion' => 'concurso_tema', 'periodo' => $p];
        return ['accion' => 'concurso_ganador', 'periodo' => $p];   // ganador, quien va primero, el mas votado… y por defecto
    }
    if (preg_match('/\b(tema|consigna)\b.*\bsemana\b|\bsemana\b.*\b(tema|consigna)\b/u', $n)) return ['accion' => 'concurso_tema', 'periodo' => $p];
    // temas (secciones) con mas envios: «¿cuál es la temática del día?», «¿de qué se habla hoy?», «¿qué temas se mueven?» (10/11/2026)
    if (preg_match('/\btem[aá]tic\w*|\btemas\b|\btema del d[ií]a\b|\bde qu[eé] se (habla|va)\b|\bqu[eé] se (habla|mueve|cuece)\b|\btopics?\b|\bthemes?\b|\bthemen\b|\bargomenti\b|\bgaiak?\b|\bkonular\b|\bsujets\b/u', $n)) return ['accion' => 'temas', 'periodo' => $p];
    if (preg_match('/\b(noticias?|env[ií]os?|enlaces?|articulos?|historias?|hilos?|posts?)\b/u', $n) && preg_match($super, $n)) return ['accion' => 'noticia_top', 'periodo' => $p];
    if (preg_match('/\b(medios?|periodicos?|diarios?|fuentes?|substacks?|newsletters?|cabeceras?|canal(es)?|webs?)\b/u', $n) && preg_match($super, $n)) return ['accion' => 'medio_leido', 'periodo' => $p];
    if (preg_match($super, $n)) return ['accion' => 'noticia_top', 'periodo' => $p];   // «lo mas leido» a secas: las noticias
    return null;
}

/* ---- memoria del hilo (09/11/2026, tarde): «¿y este mes?», «¿y hoy?», «otra vez» heredan la accion de la pregunta anterior ------
 * Sigfrid contesta citando (#N) la pregunta, y el Chat guarda reply_to. Asi que la pregunta anterior de esta persona se encuentra
 * sin guardar nada: o bien cita (#N) una respuesta de Sigfrid, o bien se toma la ultima respuesta de Sigfrid a esta misma persona
 * (user_id o huella anonima) de los ultimos sigfrid_contexto_min minutos (30). De ahi se vuelve a la pregunta y se re-evalua su
 * intencion; si tambien era una continuacion, se sigue hacia atras (tope 3 saltos). */

/** ¿La frase es una continuacion de lo anterior y no una pregunta completa? Corta y con un periodo, o «y …», o «otra vez». */
function sigfrid_es_continuacion(string $n): bool {
    if ($n === '') return false;
    $palabras = count(preg_split('/\s+/u', $n));
    if (preg_match('/^(y|and|et|e|eta|ve|und|i)\b/u', $n) && $palabras <= 8) return true;
    if (preg_match('/\b(otra vez|de nuevo|again|repite|repetir|actualiza|encore|ancora|nochmal|berriro|tekrar|outra vez|un altre cop)\b/u', $n)) return true;
    return sigfrid_periodo_explicito($n) !== null && $palabras <= 6;
}

/** La pregunta a la que Sigfrid respondio por ultima vez a esta persona (o la que cita el mensaje $mid), con su intencion. */
function sigfrid_contexto(PDO $pdo, int $mid, string $login, int $salto = 0): ?array {
    if ($salto > 3) return null;
    $sig = sigfrid_user($pdo);
    if (!$sig) return null;
    $q = $pdo->prepare("SELECT user_id, anon_key, reply_to FROM chat WHERE msg_id = ?"); $q->execute([$mid]);
    if (!($m = $q->fetch())) return null;
    $resp = null;
    if ($m['reply_to']) {   // cita (#N) una respuesta de Sigfrid
        $r = $pdo->prepare("SELECT msg_id, reply_to FROM chat WHERE msg_id = ? AND user_id = ? AND reply_to IS NOT NULL"); $r->execute([(int)$m['reply_to'], (int)$sig['user_id']]);
        $resp = $r->fetch() ?: null;
    }
    if (!$resp) {   // la ultima respuesta de Sigfrid a esta misma persona, reciente
        $min = max(1, (int)cfg('sigfrid_contexto_min', 30));
        $r = $pdo->prepare("SELECT s.msg_id, s.reply_to FROM chat s JOIN chat p ON p.msg_id = s.reply_to
                             WHERE s.user_id = ? AND s.msg_id < ? AND s.created >= NOW() - INTERVAL $min MINUTE AND " . ($m['user_id'] ? "p.user_id = ?" : "p.user_id IS NULL AND p.anon_key = ?") . "
                             ORDER BY s.msg_id DESC LIMIT 1");
        $r->execute([(int)$sig['user_id'], $mid, $m['user_id'] ? (int)$m['user_id'] : (string)$m['anon_key']]);
        $resp = $r->fetch() ?: null;
    }
    if (!$resp || !$resp['reply_to']) return null;
    $q = $pdo->prepare("SELECT msg_id, content FROM chat WHERE msg_id = ?"); $q->execute([(int)$resp['reply_to']]);
    if (!($preg = $q->fetch())) return null;
    $in = sigfrid_intencion((string)$preg['content'], $login);
    if ($in !== null && $in['accion'] !== 'ayuda') return $in;
    if (sigfrid_es_continuacion(sigfrid_normaliza((string)$preg['content'], $login))) return sigfrid_contexto($pdo, (int)$preg['msg_id'], $login, $salto + 1);   // tambien era «¿y hoy?»: mas atras
    return null;
}

/** Capa 2: el modelo pequeño (si hay sigfrid_ia_url). Solo clasifica: devuelve ['accion', 'periodo'] o null si no hay, falla o tarda. */
function sigfrid_ia(string $content, string $login = ''): ?array {
    $url = trim((string)cfg('sigfrid_ia_url', ''));
    if ($url === '' || !function_exists('curl_init')) return null;
    $texto = sigfrid_normaliza($content, $login);
    if ($texto === '') return null;
    $acciones = implode(' | ', SIGFRID_ACCIONES);
    // Corto a proposito (09/11/2026): el droplet lee el prompt a ~90 tokens/s y el mensaje largo (330 tokens) se pasaba del tope.
    // El esquema JSON cerrado ya limita las acciones; aqui solo la pista de cada una. ~120 tokens.
    // Primero decide si la frase va de este sitio (deltha), luego la accion. Con ejemplos: un modelo de 1,7B sin ellos encajaba «¿cómo quedó el
    // Depor?» en radar o conectados (09/11/2026). ~230 tokens; el prompt se cachea entre peticiones.
    $system = "Clasificas frases que la gente escribe a Sigfrid, el ayudante de Deltha, un agregador de noticias. Devuelve JSON. "
            . "1) deltha: true solo si la frase pregunta por Deltha mismo (sus noticias, portada, radar, comunidades, foros, concurso de relatos, medios, usuarios, aura, conectados, ciclo). "
            . "false si pregunta por el mundo exterior: resultados deportivos, equipos, el tiempo, cultura general, chistes, programacion, otras webs, aunque sea una noticia. "
            . "2) accion: radar=cola de lo que espera para portada; portada=lo que hay en portada; concurso_ganador=quien gana el concurso de relatos; concurso_tema=tema del concurso; "
            . "medio_leido=medio mas leido; noticia_top=noticia mas votada o leida; comunidad_activa=comunidad con mas movimiento; temas=el asunto o noticia del que mas se habla (tematica del dia); top_enviador=usuario que mas envia; "
            . "conectados=cuanta gente hay conectada; proximo_ciclo=cuando sube la siguiente; mis_envios=los envios o aura de quien pregunta; "
            . "conversar=va de Deltha pero no pide exactamente ninguna de las anteriores (vaga, de ambiente, compara o mezcla); ayuda=saludo o pide ayuda. "
            . "Elige una accion concreta solo si la frase la pide claramente; en la duda, conversar. Si deltha es false, accion=ayuda. 3) periodo: hoy, semana (defecto) o mes. "
            . "Ejemplos: «¿cómo quedó el Depor?» → {\"deltha\":false,\"accion\":\"ayuda\",\"periodo\":\"semana\"}; «¿qué tiempo hace?» → deltha false; "
            . "«muéstrame el radar» → {\"deltha\":true,\"accion\":\"radar\",\"periodo\":\"semana\"}; «¿cuántos estamos conectados?» → conectados; "
            . "«¿hay ambiente por aquí?» → {\"deltha\":true,\"accion\":\"conversar\",\"periodo\":\"semana\"}; «¿cuál fue el medio más leído este mes?» → medio_leido, mes.";
    $schema = ['type' => 'object', 'properties' => ['deltha' => ['type' => 'boolean'], 'accion' => ['type' => 'string', 'enum' => array_merge(SIGFRID_ACCIONES, ['conversar'])], 'periodo' => ['type' => 'string', 'enum' => ['hoy', 'semana', 'mes']]], 'required' => ['deltha', 'accion', 'periodo'], 'additionalProperties' => false];
    $body = ['model' => (string)cfg('sigfrid_ia_model', 'sigfrid'), 'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $texto]],
             'temperature' => 0, 'max_tokens' => 60, 'stream' => false,
             'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'sigfrid', 'strict' => true, 'schema' => $schema]],
             'chat_template_kwargs' => ['enable_thinking' => false], 'reasoning_effort' => 'none'];
    $ms = max(500, (int)cfg('sigfrid_ia_timeout_ms', 8000));
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
    if (!is_array($r) || !in_array($r['accion'] ?? '', array_merge(SIGFRID_ACCIONES, ['conversar']), true)) return null;
    if (array_key_exists('deltha', $r) && $r['deltha'] === false) return ['accion' => 'fuera', 'periodo' => 'semana'];   // no va de Deltha
    if ($r['accion'] === 'conversar') return ['accion' => 'ayuda', 'periodo' => 'semana', 'conversar' => true];   // va de Deltha pero no es ninguna accion concreta → capa 3 (o ayuda si no se puede)
    return ['accion' => (string)$r['accion'], 'periodo' => in_array($r['periodo'] ?? '', ['hoy', 'semana', 'mes'], true) ? (string)$r['periodo'] : 'semana'];
}

/* ---- las acciones: consultas fijas sobre lo que cualquiera ve en la web ---------------------------------------------------- */

/** Lo que Sigfrid sabe hacer, para la respuesta de ayuda y para el perfil. */
function sigfrid_ayuda(): string {
    return t('Soy Sigfrid, el ayudante del Chat. Pregúntame con @%s delante:', (string)cfg('sigfrid_login', 'Sigfrid')) . ' '
         . t('«muéstrame el radar» · «¿quién va ganando el concurso de relatos?» · «¿cuál es el tema de la semana?» · «¿cuál es el medio más leído?» · «¿cuál es la noticia más votada hoy?»')
         . ' · ' . t('«¿qué hay en portada?» · «¿cuál es la comunidad más activa?» · «¿cuál es la temática del día?» · «¿quién ha enviado más?» · «¿cuántos estamos conectados?» · «¿cuándo es el próximo ciclo?» · «¿cómo van mis envíos?»')
         . ' ' . t('Entiendo «hoy», «esta semana» (por defecto) y «este mes».') . ' ' . t('Y si después me preguntas «¿y este mes?», sé de qué hablabas.');
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

/** Lo que hay en portada: cuantas por portada y las tres ultimas en llegar. */
function sigfrid_portada(PDO $pdo): string {
    $tot = 0; $por = [];
    foreach ($pdo->query("SELECT portada, COUNT(*) n FROM stories WHERE status = 'destacadas' GROUP BY portada") as $r) { $por[(string)$r['portada']] = (int)$r['n']; $tot += (int)$r['n']; }
    if ($tot === 0) return t('Ahora mismo no hay nada en portada.') . ' ' . sigfrid_enlace(t('Portada'), '/');
    $partes = [];
    foreach (portadas() as $k => $lbl) if (!empty($por[$k])) $partes[] = $por[$k] . ' ' . t('en %s', t($lbl));
    $top = $pdo->query("SELECT story_id, title, votes_pos, votes_neg FROM stories WHERE status = 'destacadas' ORDER BY published_date DESC LIMIT 3")->fetchAll();
    $lista = array_map(fn($s) => sigfrid_enlace((string)$s['title'], "/hilo/{$s['story_id']}") . ' (' . t('%d votos', (int)$s['votes_pos'] - (int)$s['votes_neg']) . ')', $top);
    return t('En portada hay %d noticias', $tot) . ($partes ? ' (' . implode(', ', $partes) . ')' : '') . '. ' . t('Las últimas en llegar:') . ' ' . implode(' · ', $lista) . '. ' . sigfrid_enlace(t('Portada'), '/');
}

/** Comunidades con mas movimiento en el periodo: envios añadidos (story_communities.created) y comentarios en sus hilos. */
function sigfrid_comunidad_activa(PDO $pdo, string $periodo): string {
    $rows = $pdo->query("SELECT c.community_id, c.name, c.slug,
                                (SELECT COUNT(*) FROM story_communities sc JOIN stories s ON s.story_id = sc.story_id WHERE sc.community_id = c.community_id AND s.status IN ('nuevas','destacadas','archived') AND " . sigfrid_desde($periodo, 'sc.created') . ") n,
                                (SELECT COUNT(*) FROM comments co JOIN story_communities sc2 ON sc2.story_id = co.story_id WHERE sc2.community_id = c.community_id AND co.status = 'ok' AND " . sigfrid_desde($periodo, 'co.created') . ") nc
                           FROM communities c WHERE c.status = 'ok' HAVING n + nc > 0 ORDER BY n + nc DESC, n DESC, c.name ASC LIMIT 3")->fetchAll();
    if (!$rows) return t('Ninguna comunidad ha tenido movimiento %s.', sigfrid_periodo_txt($periodo)) . ' ' . sigfrid_enlace(t('Comunidades'), '/comunidades');
    $fmt = fn($r) => sigfrid_enlace((string)$r['name'], '/comunidad/' . rawurlencode((string)$r['slug'])) . ' (' . t('%d envíos, %d comentarios', (int)$r['n'], (int)$r['nc']) . ')';
    $primero = array_shift($rows);
    return t('La comunidad más activa %s es %s', sigfrid_periodo_txt($periodo), $fmt($primero)) . ($rows ? '; ' . t('le siguen %s', implode(' ' . t('y') . ' ', array_map($fmt, $rows))) : '') . '.';
}

/** «¿Cuál es la temática del día?» = el asunto del que mas se habla: grupos de envios relacionados (story_related, vectores de titulares,
 *  migra-175) dentro del periodo, el mayor primero (y a igual tamaño, el mas votado). Cada grupo se presenta por su envio mas votado. Si
 *  ningun asunto se repite (o aun no hay vectores), responde con los temas-seccion (sigfrid_temas). Ángel, 10/11/2026: «el tema como la
 *  noticia más mencionada, no como categoría». */
function sigfrid_asuntos(PDO $pdo, string $periodo): string {
    $rows = $pdo->query("SELECT story_id, title, votes_pos - votes_neg v, comments, status FROM stories WHERE status IN ('nuevas','destacadas','archived') AND content_type = 'link' AND " . sigfrid_desde($periodo, 'sent_date'))->fetchAll();
    if (!$rows) return sigfrid_temas($pdo, $periodo);
    $S = []; foreach ($rows as $r) $S[(int)$r['story_id']] = $r;
    $ids = implode(',', array_keys($S));
    $padre = []; foreach ($S as $id => $_) $padre[$id] = $id;
    $raiz = function (int $x) use (&$padre, &$raiz): int { while ($padre[$x] !== $x) { $padre[$x] = $padre[$padre[$x]]; $x = $padre[$x]; } return $x; };
    try {
        foreach ($pdo->query("SELECT story_id, related_id FROM story_related WHERE story_id IN ($ids) AND related_id IN ($ids)") as $r) {
            $a = $raiz((int)$r['story_id']); $b = $raiz((int)$r['related_id']); if ($a !== $b) $padre[$b] = $a;
        }
    } catch (Throwable $e) { return sigfrid_temas($pdo, $periodo); }
    $grupos = []; foreach ($S as $id => $r) $grupos[$raiz($id)][] = $r;
    $grupos = array_filter($grupos, fn($g) => count($g) >= 2);
    if (!$grupos) return t('%s ningún asunto se repite entre los envíos.', ucfirst(sigfrid_periodo_txt($periodo))) . ' ' . sigfrid_temas($pdo, $periodo);
    $peso = fn(array $g) => [count($g), array_sum(array_map(fn($r) => (int)$r['v'], $g)), array_sum(array_map(fn($r) => (int)$r['comments'], $g))];
    usort($grupos, fn($a, $b) => $peso($b) <=> $peso($a));
    // el grupo mayor, con sus enfoques (hasta cuatro titulares, el mas votado primero); los siguientes, solo por su titular mas votado
    $orden = function (array $g): array { usort($g, fn($a, $b) => [(int)$b['v'], $b['status'] === 'destacadas'] <=> [(int)$a['v'], $a['status'] === 'destacadas']); return $g; };
    $enlace = fn(array $r) => sigfrid_enlace((string)$r['title'], '/hilo/' . (int)$r['story_id']);
    $primero = $orden(array_shift($grupos)); [$n, $v, $c] = $peso($primero);
    $txt = t('%s el asunto con más envíos es el de %s: %d envíos desde distintos enfoques (%d votos, %d comentarios)', ucfirst(sigfrid_periodo_txt($periodo)), $enlace($primero[0]), $n, $v, $c)
         . (count($primero) > 1 ? ' — ' . implode(' · ', array_map($enlace, array_slice($primero, 1, 3))) . (count($primero) > 4 ? ' …' : '') : '') . '.';
    if ($grupos) $txt .= ' ' . t('Le siguen %s', implode(' ' . t('y') . ' ', array_map(function (array $g) use ($orden, $enlace, $peso) { $g = $orden($g); [$n] = $peso($g); return $enlace($g[0]) . ' (' . t('%d envíos', $n) . ')'; }, array_slice($grupos, 0, 2)))) . '.';
    return $txt;
}

/** Los temas (secciones) con mas envios en el periodo, con sus votos (lo usa sigfrid_asuntos cuando ningun asunto se repite). */
function sigfrid_temas(PDO $pdo, string $periodo): string {
    $rows = $pdo->query("SELECT t.topic_id, t.name, COUNT(*) n, COALESCE(SUM(s.votes_pos - s.votes_neg), 0) v, SUM(s.status = 'destacadas') port
                           FROM stories s JOIN topics t ON t.topic_id = s.topic_id
                          WHERE s.status IN ('nuevas','destacadas','archived') AND s.content_type = 'link' AND " . sigfrid_desde($periodo, 's.sent_date') . "
                          GROUP BY t.topic_id ORDER BY n DESC, v DESC, t.name ASC LIMIT 4")->fetchAll();
    if (!$rows) return t('%s no se ha enviado nada todavía.', ucfirst(sigfrid_periodo_txt($periodo))) . ' ' . sigfrid_enlace(t('Radar'), '/radar');
    $fmt = fn($r) => sigfrid_enlace((string)$r['name'], '/tema/' . (int)$r['topic_id']) . ' (' . t('%d envíos, %d votos', (int)$r['n'], (int)$r['v']) . ((int)$r['port'] > 0 ? ', ' . t('%d en portada', (int)$r['port']) : '') . ')';
    $primero = array_shift($rows);
    return t('El tema con más envíos %s es %s', sigfrid_periodo_txt($periodo), $fmt($primero)) . ($rows ? '; ' . t('le siguen %s', implode(', ', array_map($fmt, $rows))) : '') . '.';
}

/** Quien mas ha enviado (y quien mas ha comentado) en el periodo. Solo cuentas con nombre: los anonimos no se suman a nadie. */
function sigfrid_top_enviador(PDO $pdo, string $periodo): string {
    $env = $pdo->query("SELECT u.login, COUNT(*) n FROM stories s JOIN users u ON u.user_id = s.user_id WHERE s.status IN ('nuevas','destacadas','archived') AND " . sigfrid_desde($periodo, 's.sent_date') . " GROUP BY u.user_id ORDER BY n DESC, u.login ASC LIMIT 3")->fetchAll();
    if (!$env) return t('Nadie con cuenta ha enviado nada %s.', sigfrid_periodo_txt($periodo));
    $fmt = fn($r, $k) => sigfrid_enlace((string)$r['login'], '/u/' . rawurlencode((string)$r['login'])) . ' (' . t($k, (int)$r['n']) . ')';
    $primero = array_shift($env);
    $txt = t('Quien más ha enviado %s es %s', sigfrid_periodo_txt($periodo), $fmt($primero, '%d envíos')) . ($env ? '; ' . t('le siguen %s', implode(' ' . t('y') . ' ', array_map(fn($r) => $fmt($r, '%d envíos'), $env))) : '') . '.';
    $com = $pdo->query("SELECT u.login, COUNT(*) n FROM comments c JOIN users u ON u.user_id = c.user_id WHERE c.status = 'ok' AND " . sigfrid_desde($periodo, 'c.created') . " GROUP BY u.user_id ORDER BY n DESC, u.login ASC LIMIT 1")->fetch();
    if ($com) $txt .= ' ' . t('Quien más ha comentado, %s.', $fmt($com, '%d comentarios'));
    return $txt;
}

/** Cuanta gente hay ahora: cuentas vistas en los ultimos online_minutes y visitantes anonimos (presence). */
function sigfrid_conectados(PDO $pdo): string {
    $min = max(1, (int)cfg('online_minutes', 5));
    $reg = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE last_seen >= NOW() - INTERVAL $min MINUTE")->fetchColumn();
    $anon = 0;
    try { $anon = (int)$pdo->query("SELECT COUNT(*) FROM presence WHERE seen >= NOW() - INTERVAL $min MINUTE")->fetchColumn(); } catch (Throwable $e) {}
    return t('Ahora mismo hay %d personas con cuenta y %d visitantes sin cuenta (últimos %d minutos).', $reg, $anon, $min) . ' ' . sigfrid_enlace(t('Estadísticas'), '/estadisticas');
}

/** Cuando es el proximo ciclo (la siguiente noticia que sube del radar a candidatas) y cuantas esperan. */
function sigfrid_proximo_ciclo(PDO $pdo): string {
    require_once __DIR__ . '/cola.php';
    $n = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE status = 'nuevas' AND content_type = 'link'")->fetchColumn();
    return t('El próximo ciclo es %s (cada %d minutos sube una del radar).', proximo_ciclo_txt(), max(1, (int)cfg('cycle_minutes', 5))) . ' ' . ($n > 0 ? t('Esperan %d envíos.', $n) : t('El radar está vacío.')) . ' ' . sigfrid_enlace(t('Cómo funciona el δ'), '/algoritmo');
}

/** Los numeros de quien pregunta: envios del periodo (y cuantos llegaron a portada), comentarios y aura. Sin cuenta, que entre. */
function sigfrid_mis_envios(PDO $pdo, ?array $u, string $periodo): string {
    if (!$u) return t('Eso solo lo sé de quien tiene cuenta: entra y vuelve a preguntarme.') . ' ' . sigfrid_enlace(t('Entrar'), '/login');
    $uid = (int)$u['user_id'];
    $q = $pdo->prepare("SELECT COUNT(*) n, SUM(status = 'destacadas' OR (status = 'archived' AND published_date IS NOT NULL)) port, COALESCE(SUM(votes_pos - votes_neg), 0) v FROM stories WHERE user_id = ? AND status IN ('nuevas','destacadas','archived') AND " . sigfrid_desde($periodo, 'sent_date'));
    $q->execute([$uid]); $s = $q->fetch();
    $q = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ? AND status = 'ok' AND " . sigfrid_desde($periodo, 'created')); $q->execute([$uid]);
    $nc = (int)$q->fetchColumn();
    $q = $pdo->prepare("SELECT aura FROM users WHERE user_id = ?"); $q->execute([$uid]);
    $aura = (float)$q->fetchColumn();
    return t('%s has enviado %d noticias (%d llegaron a portada, %d votos en total) y has escrito %d comentarios.', ucfirst(sigfrid_periodo_txt($periodo)), (int)$s['n'], (int)$s['port'], (int)$s['v'], $nc)
         . ' ' . t('Tu aura es %s.', num_es(round($aura, 1))) . ' ' . sigfrid_enlace(t('Tu perfil'), '/u/' . rawurlencode((string)$u['login']));
}

/** Enlace en las marcas del Chat: [texto](url). md_inline() solo admite URL absolutas (https://…), de ahi abs_url(); el texto va sin
 *  corchetes ni parentesis para no romper la marca. */
function sigfrid_enlace(string $texto, string $url): string {
    return '[' . strtr(mb_strimwidth(trim($texto), 0, 90, '…'), ['[' => '(', ']' => ')']) . '](' . (preg_match('#^https?://#', $url) ? $url : abs_url($url)) . ')';
}

/** Ejecuta una accion y devuelve el texto de la respuesta (marcas del Chat). */
function sigfrid_accion(PDO $pdo, string $accion, string $periodo, ?array $u = null): string {
    switch ($accion) {
        case 'radar':            return sigfrid_radar($pdo);
        case 'fuera':            return sigfrid_fuera();   // la pregunta no va de Deltha (lo dice el clasificador)
        case 'portada':          return sigfrid_portada($pdo);
        case 'comunidad_activa': return sigfrid_comunidad_activa($pdo, $periodo);
        case 'temas':            return sigfrid_asuntos($pdo, $periodo);
        case 'top_enviador':     return sigfrid_top_enviador($pdo, $periodo);
        case 'conectados':       return sigfrid_conectados($pdo);
        case 'proximo_ciclo':    return sigfrid_proximo_ciclo($pdo);
        case 'mis_envios':       return sigfrid_mis_envios($pdo, $u, $periodo);
        case 'concurso_ganador': return sigfrid_concurso_ganador($pdo);
        case 'concurso_tema':    return sigfrid_concurso_tema($pdo);
        case 'medio_leido':      return sigfrid_medio_leido($pdo, $periodo);
        case 'noticia_top':      return sigfrid_noticia_top($pdo, $periodo);
        default:                 return sigfrid_ayuda();
    }
}

/* ---- capa 3: Sigfrid conversacional (migra-187, 09/11/2026) -----------------------------------------------------------------------
 * Cuando la pregunta no encaja en ninguna accion, Sigfrid contesta al momento «un momento, que lo miro», encola una tarea «sigfrid»
 * en lib/ia.php (la sirve el worker del PC de la redaccion, con un modelo grande) con la pregunta, los mensajes anteriores del hilo
 * y un PAQUETE con todo lo que Sigfrid sabe sacar, y cuando llega el texto redactado sustituye el mensaje de espera. El modelo no
 * consulta nada ni ejecuta nada: solo redacta con el paquete. Si mete una cifra o un enlace que no estan en el paquete, se descarta.
 * Si el worker lleva mas de 3 min sin conectarse, o la tarea esta apagada, no se promete nada y Sigfrid cae en la ayuda de siempre. */

/** Todo lo que Sigfrid sabe ahora mismo, ya redactado por las acciones (en el idioma de quien pregunta, con sus enlaces). */
function sigfrid_paquete(PDO $pdo, ?array $u): string {
    $partes = [];
    foreach (['radar', 'portada', 'concurso_ganador', 'concurso_tema', 'medio_leido', 'noticia_top', 'comunidad_activa', 'temas', 'top_enviador', 'conectados', 'proximo_ciclo'] as $a) {
        try { $partes[] = "[$a] " . sigfrid_accion($pdo, $a, 'semana', $u); } catch (Throwable $e) {}
    }
    if ($u) { try { $partes[] = "[mis_envios] " . sigfrid_accion($pdo, 'mis_envios', 'semana', $u); } catch (Throwable $e) {} }
    return implode("\n", $partes);
}

/** Los mensajes anteriores del hilo (lo que cita #N, hasta tres hacia atras), para que el modelo sepa de que se hablaba. */
function sigfrid_hilo(PDO $pdo, int $mid): string {
    $out = []; $id = $mid;
    for ($i = 0; $i < 3 && $id > 0; $i++) {
        $q = $pdo->prepare("SELECT c.reply_to, c.content, c.alias, u.login FROM chat c LEFT JOIN users u ON u.user_id = c.user_id WHERE c.msg_id = ?"); $q->execute([$id]);
        if (!($m = $q->fetch()) || !$m['reply_to']) break;
        $id = (int)$m['reply_to'];
        $q->execute([$id]);
        if ($p = $q->fetch()) array_unshift($out, '#' . $id . ' ' . chat_who($p) . ': ' . mb_substr(preg_replace('/\s+/u', ' ', (string)$p['content']), 0, 300));
    }
    return implode("\n", $out);
}

/** ¿Puede Sigfrid prometer una respuesta redactada? Tarea encendida y worker visto hace poco. */
function sigfrid_conversar_posible(PDO $pdo): bool {
    if (!function_exists('ia_tarea_activa') || !ia_tarea_activa($pdo, 'sigfrid')) return false;
    $seen = (string)cfg('ia_worker_seen', '');
    return $seen !== '' && time() - strtotime($seen) < 180;
}

/** Publica el «un momento» citando la pregunta y encola el trabajo. Devuelve false si no se puede (y Sigfrid sigue con la ayuda). */
function sigfrid_conversar(PDO $pdo, int $mid, string $content, ?array $u, string $quien, array $sig): bool {
    if (!sigfrid_conversar_posible($pdo)) return false;
    $login = (string)$sig['login'];
    $pregunta = trim(sigfrid_normaliza_mencion($content, $login));
    if ($pregunta === '') return false;
    $txt = "#$mid " . ($quien !== '' ? "$quien " : '') . t('Un momento, que lo miro…');
    $pdo->prepare("INSERT INTO chat (user_id, alias, anon_key, reply_to, content, anuncio) VALUES (?,?,?,?,?,0)")->execute([(int)$sig['user_id'], '', null, $mid, $txt]);
    $nid = (int)$pdo->lastInsertId();
    $hilo = sigfrid_hilo($pdo, $mid);
    $user = "Idioma de la respuesta: " . lang() . ". Quien pregunta: " . ($u ? $u['login'] : 'una persona sin cuenta') . ".\n"
          . ($hilo !== '' ? "<datos>\nMensajes anteriores del hilo:\n$hilo\n</datos>\n" : '')
          . "<datos>\nPREGUNTA: $pregunta\n</datos>\n"
          . "<datos>\nPAQUETE DE DATOS (lo que Sigfrid sabe ahora mismo; cada linea empieza por el nombre de la accion):\n" . sigfrid_paquete($pdo, $u) . "\n</datos>";
    $job = ia_pedir($pdo, 'sigfrid', ['user' => $user, 'mid' => $mid, 'quien' => $quien, 'login' => $login, 'lang' => lang()], 'chat', $nid);
    if ($job === null) {   // no se pudo encolar (tope diario, tarea apagada entre medias): se retira el «un momento» y que siga la ayuda
        $pdo->prepare("DELETE FROM chat WHERE msg_id = ?")->execute([$nid]);
        return false;
    }
    return true;
}

/** El texto sin la mencion a Sigfrid pero con mayusculas y acentos (para el modelo, no para las reglas). */
function sigfrid_normaliza_mencion(string $t, string $login): string {
    $l = preg_quote($login, '~');
    return trim(preg_replace('/\s+/u', ' ', preg_replace("~(?<![\\w@])@$l\\b|^\\s*$l\\s*[,:]?~iu", ' ', $t)));
}

/** La respuesta del modelo solo vale si todas sus cifras (de 2+ digitos) y todos sus enlaces estan en el paquete que se le dio. */
function sigfrid_respuesta_valida(string $txt, string $paquete): bool {
    if (trim($txt) === '' || mb_strlen($txt) > 900) return false;
    preg_match_all('/\d[\d.,]*\d/u', $txt, $nums);
    foreach ($nums[0] as $n) if (mb_strlen(preg_replace('/\D/', '', $n)) >= 2 && !str_contains($paquete, $n)) return false;
    preg_match_all('~https?://\S+?(?=[)\s]|$)~u', $txt, $urls);
    foreach ($urls[0] as $url) if (!str_contains($paquete, $url)) return false;
    return !preg_match('/<\/?(script|iframe|img)/i', $txt);
}

ia_handler('sigfrid', function (PDO $pdo, array $job, array $res): void {
    $nid = (int)$job['ref_id']; $in = $job['input'];
    $mid = (int)($in['mid'] ?? 0); $quien = (string)($in['quien'] ?? '');
    $paquete = (string)($in['user'] ?? '');
    $txt = trim((string)($res['text'] ?? $res['respuesta'] ?? ''));
    $txt = preg_replace('/^#\d+\s+/', '', $txt);   // por si repite la cita
    // fuera de Deltha (migra-188): el modelo devuelve FUERA_DE_DELTHA y Sigfrid despacha con una de Chiquito (castellano) o la neutra en el idioma de quien pregunta
    $lg = (string)($in['lang'] ?? 'es');   // idioma de quien pregunto (el handler corre en /api/ia.php, donde lang() es el del worker)
    if (str_contains($txt, 'FUERA_DE_DELTHA')) $txt = sigfrid_fuera($lg);
    elseif (!sigfrid_respuesta_valida($txt, $paquete)) $txt = t_in($lg, 'No he sabido responder a eso.') . ' ' . t_in($lg, 'Escribe «@%s ayuda» para ver lo que sé hacer.', (string)($in['login'] ?? cfg('sigfrid_login', 'Sigfrid')));
    $final = mb_substr("#$mid " . ($quien !== '' ? "$quien " : '') . $txt, 0, 1000);
    $sig = sigfrid_user($pdo);
    $pdo->prepare("UPDATE chat SET content = ? WHERE msg_id = ? AND user_id = ?")->execute([$final, $nid, (int)($sig['user_id'] ?? 0)]);
    if ($sig && function_exists('avisos_chat')) avisos_chat($pdo, $nid, (int)$sig['user_id'], (string)$sig['login'], $final, $mid);   // «te ha respondido Sigfrid», ahora que hay respuesta
});

/** Mensajes de Sigfrid que esperan al PC en esta pagina (ids de chat) → el Chat se refresca mas deprisa mientras tanto. */
function sigfrid_pendientes(PDO $pdo, array $ids): array {
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) return [];
    try {
        $in = implode(',', $ids);
        return array_map('intval', $pdo->query("SELECT ref_id FROM ia_jobs WHERE task_key = 'sigfrid' AND ref_type = 'chat' AND ref_id IN ($in) AND status IN ('queued','processing')")->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) { return []; }
}

/** Esperas vencidas (sigfrid_espera_seg) o trabajos fallidos: el «un momento» pasa a ser una disculpa. La llama /chat antes de pintar.
 *  Se marca el trabajo (result._caducado) para no repetirlo; el texto de espera puede estar en cualquier idioma, asi que no se busca. */
function sigfrid_caducar(PDO $pdo): void {
    $seg = max(30, (int)cfg('sigfrid_espera_seg', 120));
    try {
        $q = $pdo->query("SELECT job_id, ref_id, input, status FROM ia_jobs WHERE task_key = 'sigfrid' AND ref_type = 'chat'
                           AND ((status IN ('queued','processing') AND created < NOW() - INTERVAL $seg SECOND) OR status IN ('failed','needs_human'))
                           AND (result IS NULL OR result NOT LIKE '%_caducado%')");
        foreach ($q->fetchAll() as $j) {
            $in = json_decode((string)$j['input'], true) ?: [];
            $txt = "#" . (int)($in['mid'] ?? 0) . ' ' . (($in['quien'] ?? '') !== '' ? $in['quien'] . ' ' : '') . t('No he podido mirarlo a tiempo.') . ' ' . t('Escribe «@%s ayuda» para ver lo que sé hacer.', (string)($in['login'] ?? cfg('sigfrid_login', 'Sigfrid')));
            $pdo->prepare("UPDATE chat SET content = ? WHERE msg_id = ?")->execute([mb_substr($txt, 0, 1000), (int)$j['ref_id']]);
            $pdo->prepare("UPDATE ia_jobs SET status = IF(status IN ('queued','processing'), 'needs_human', status), result = ? WHERE job_id = ?")
                ->execute([json_encode(['_caducado' => 1, '_error' => 'sin respuesta en ' . $seg . ' s'], JSON_UNESCAPED_UNICODE), (int)$j['job_id']]);
        }
    } catch (Throwable $e) { /* sin la migracion 173 no hay cola */ }
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
        $in = sigfrid_intencion($content, $login);
        if ($in === null) {   // memoria del hilo: «¿y este mes?» hereda la accion de la pregunta anterior; el periodo, si lo dice, es el nuevo
            $n = sigfrid_normaliza($content, $login);
            if (sigfrid_es_continuacion($n) && ($ctx = sigfrid_contexto($pdo, $mid, $login))) {
                $pe = sigfrid_periodo_explicito($n);
                $in = ['accion' => $ctx['accion'], 'periodo' => $pe ?? $ctx['periodo']];
                if ($pe !== null && !in_array($in['accion'], SIGFRID_CON_PERIODO, true)) $in = null;   // «¿y hoy?» tras el radar no significa nada: que lo decida el modelo o la ayuda
            }
        }
        $quien = $u ? '@' . $u['login'] : ($alias !== '' ? $alias : '');
        if ($in === null) {
            $in = sigfrid_ia($content, $login);
            // capa 3 (migra-187): ni las reglas ni el clasificador lo encajan en una accion → lo redacta el PC con todos los datos a la vista
            // 'fuera' del clasificador tambien pasa por aqui (09/11/2026, 22:50): LFM3 se equivocaba en las dos direcciones («el comentario mas
            // reciente» → fuera), asi que la ultima palabra sobre si va de Deltha la tiene Qwen (FUERA_DE_DELTHA); sin PC, se queda la frase.
            if (($in === null || in_array($in['accion'], ['ayuda', 'fuera'], true)) && sigfrid_conversar($pdo, $mid, $content, $u, $quien, $sig)) return;
            $in = $in ?? ['accion' => 'ayuda', 'periodo' => 'semana'];
        }
        $txt = "#$mid " . ($quien !== '' ? "$quien " : '') . sigfrid_accion($pdo, $in['accion'], $in['periodo'], $u);
        $txt = mb_substr($txt, 0, 1000);   // chat.content es VARCHAR(1000); Sigfrid no pasa por chat_max_len
        $pdo->prepare("INSERT INTO chat (user_id, alias, anon_key, reply_to, content, anuncio) VALUES (?,?,?,?,?,0)")->execute([(int)$sig['user_id'], '', null, $mid, $txt]);
        $nid = (int)$pdo->lastInsertId();
        if (function_exists('avisos_chat')) avisos_chat($pdo, $nid, (int)$sig['user_id'], $login, $txt, $mid);   // «te ha respondido Sigfrid»
    } catch (Throwable $e) { error_log('sigfrid: ' . $e->getMessage()); }
}
