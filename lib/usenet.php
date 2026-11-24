<?php
/** Usenet dentro de Foros (migra-206, 19/11/2026), pedido por Ángel: «que aparezcan en config todos y marcamos».
 *  Deltha habla NNTP (RFC 3977) con un servidor de noticias (config usenet_host / usenet_port / usenet_user / usenet_pass, que se
 *  editan en admin → Usenet): trae la lista completa de grupos a usenet_groups (LIST ACTIVE + LIST NEWSGROUPS), el admin marca los
 *  que quiere (usenet_groups.activo) y el cron (usenet_tick, desde scripts/promocionar.php) lee solo esos y guarda los mensajes en
 *  usenet_msgs. Se ven en /usenet (pestaña «Usenet» de Foros), en solo lectura: desde Deltha no se escribe en Usenet.
 *  Lo que NO se guarda: binarios y adjuntos (yEnc, uuencode, no-texto), mensajes de mas de usenet_max_kb, los enviados a mas de
 *  usenet_max_crosspost grupos (spam), los de control y los que piden no archivarse (X-No-Archive: yes). Del remitente se guarda
 *  solo el nombre, nunca la direccion de correo, y en el texto las direcciones salen recortadas.
 *  Hilos: thr_hash = md5 del primer Message-ID de References (el que abrio el hilo) o del propio si no hay; el primer mensaje de
 *  un grupo con un thr_hash abre hilo (root_id = 0) y los siguientes cuelgan de el, este o no el original en la base. */

/** Cliente NNTP minimo, por sockets (sin extension imap). Lanza RuntimeException si el servidor no contesta o rechaza la sesion. */
final class Nntp {
    /** @var resource */
    private $fp;
    public function __construct(string $host, int $port = 563, string $user = '', string $pass = '', int $timeout = 12) {
        $tls = in_array($port, [563, 443], true);   // 563 = NNTP sobre TLS; 119 = sin cifrar
        $fp = @stream_socket_client(($tls ? 'tls://' : 'tcp://') . "$host:$port", $en, $es, $timeout);
        if (!$fp) throw new RuntimeException("no conecta con $host:$port" . ($es ? " ($es)" : ''));
        stream_set_timeout($fp, $timeout);
        $this->fp = $fp;
        [$c, $l] = $this->linea();
        if ($c !== 200 && $c !== 201) throw new RuntimeException("el servidor no da la bienvenida: $l");
        $this->cmd('MODE READER');   // los servidores de transito lo piden; el resto lo ignora
        if ($user !== '') {
            [$c] = $this->cmd('AUTHINFO USER ' . self::limpio($user));
            if ($c === 381) [$c] = $this->cmd('AUTHINFO PASS ' . self::limpio($pass));
            if ($c !== 281) throw new RuntimeException("el servidor rechaza el usuario o la clave (respuesta $c)");
        }
    }
    private static function limpio(string $s): string { return str_replace(["\r", "\n"], '', $s); }
    /** Una linea de respuesta: [codigo, texto]. */
    private function linea(): array {
        $l = fgets($this->fp, 8192);
        if ($l === false) throw new RuntimeException('el servidor ha cortado o no contesta a tiempo');
        $l = rtrim($l, "\r\n");
        return [(int)substr($l, 0, 3), $l];
    }
    /** Envia una orden y devuelve [codigo, linea de estado]. */
    public function cmd(string $s): array {
        if (@fwrite($this->fp, self::limpio($s) . "\r\n") === false) throw new RuntimeException('no se puede escribir al servidor');
        return $this->linea();
    }
    /** Lee un bloque de varias lineas (termina en «.»), ya sin el punto de relleno. Corta con excepcion si pasa de $tope bytes. */
    public function bloque(int $tope = 33554432): array {
        $out = []; $n = 0;
        while (true) {
            $l = fgets($this->fp, 1048576);
            if ($l === false) throw new RuntimeException('el servidor ha cortado a mitad de una respuesta');
            $l = rtrim($l, "\r\n");
            if ($l === '.') return $out;
            if ($l !== '' && $l[0] === '.') $l = substr($l, 1);
            if (($n += strlen($l) + 1) <= $tope) $out[] = $l;   // por encima del tope se sigue leyendo (hay que vaciar la respuesta) pero no se guarda
        }
    }
    public function cerrar(): void { if (is_resource($this->fp)) { @fwrite($this->fp, "QUIT\r\n"); @fclose($this->fp); } }
}

/** ¿Hay servidor configurado? */
function usenet_configurado(): bool { return trim((string)cfg('usenet_host', '')) !== ''; }
/** ¿Puede este lector ver /usenet? Con usenet_on = 0, solo el admin (como un foro apagado). */
function usenet_visible(): bool { return is_admin() || (string)cfg('usenet_on', '0') === '1'; }
/** Conexion con el servidor de la config. */
function usenet_conectar(): Nntp {
    if (!usenet_configurado()) throw new RuntimeException('falta el servidor (admin → Usenet)');
    return new Nntp(trim((string)cfg('usenet_host')), (int)cfg('usenet_port', 563) ?: 563, trim((string)cfg('usenet_user', '')), (string)cfg('usenet_pass', ''));
}
/** Grupos marcados, por nombre. Sin la migra-206, ninguno. */
function usenet_grupos_activos(PDO $pdo): array {
    static $g = null;
    if ($g === null) { try { $g = $pdo->query("SELECT * FROM usenet_groups WHERE activo = 1 ORDER BY name")->fetchAll(); } catch (Throwable $e) { $g = []; } }
    return $g;
}
/** ¿Sale la pestaña «Usenet» en Foros? Al admin, en cuanto hay servidor; al resto, con usenet_on y algun grupo marcado. */
function usenet_pestana(PDO $pdo): bool {
    if (is_admin()) return usenet_configurado();
    return (string)cfg('usenet_on', '0') === '1' && (bool)usenet_grupos_activos($pdo);
}

/* ---- texto: de lo que manda el servidor a UTF-8 limpio ---- */
/** A UTF-8: el juego de caracteres declarado si lo hay y vale; si no, UTF-8 si ya lo es, y si no Windows-1252 (lo habitual en los grupos antiguos). */
function usenet_utf8(string $s, string $charset = ''): string {
    $cs = strtolower(trim($charset, " \t\"'"));
    if ($cs !== '' && !in_array($cs, ['utf-8', 'utf8', 'us-ascii', 'ascii', 'unknown-8bit', 'x-unknown'], true)) {
        $r = @iconv($cs, 'UTF-8//IGNORE', $s);
        if ($r !== false && $r !== '') return $r;
    }
    if (mb_check_encoding($s, 'UTF-8')) return $s;
    $r = @iconv('WINDOWS-1252', 'UTF-8//IGNORE', $s);
    return $r !== false ? $r : mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
}
/** Una cabecera (Subject, From) legible: palabras codificadas MIME (=?iso-8859-1?Q?...?=) y bytes sueltos de 8 bits, a UTF-8, en una linea. */
function usenet_cabecera(string $s): string {
    if (str_contains($s, '=?')) { $d = @iconv_mime_decode($s, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8'); if ($d !== false) $s = $d; }
    $s = usenet_utf8($s);
    return trim((string)preg_replace('/[\x00-\x1F\x7F\s]+/u', ' ', $s));
}
/** El nombre del remitente, sin su correo: «Ana <a@b.es>», «a@b.es (Ana)» → Ana; con solo la direccion, lo de antes de la arroba. */
function usenet_autor(string $from): string {
    $f = usenet_cabecera($from);
    if (preg_match('/^\s*"?([^"<]*?)"?\s*<[^>]*>/u', $f, $m) && trim($m[1]) !== '') $n = $m[1];
    elseif (preg_match('/\(([^()]+)\)\s*$/u', $f, $m)) $n = $m[1];
    elseif (preg_match('/<?([^@<\s]+)@/u', $f, $m)) $n = $m[1];
    else $n = $f;
    $n = trim((string)preg_replace('/\S+@\S+/u', '', $n), " \t\"'<>");
    return mb_substr($n !== '' ? $n : '—', 0, 120);
}
/** Separa cabeceras y cuerpo de un articulo (lineas ya sin CRLF). Cabeceras: nombre en minusculas => valor (la primera aparicion), desplegadas. */
function usenet_partir(array $lines): array {
    $h = []; $k = null; $i = 0; $n = count($lines);
    for (; $i < $n; $i++) {
        $l = $lines[$i];
        if ($l === '') { $i++; break; }
        if (($l[0] === ' ' || $l[0] === "\t") && $k !== null) { $h[$k] .= ' ' . trim($l); continue; }
        $p = strpos($l, ':');
        if ($p === false) { $k = null; continue; }
        $nk = strtolower(trim(substr($l, 0, $p)));
        if (isset($h[$nk])) { $k = null; continue; }
        $k = $nk; $h[$k] = trim(substr($l, $p + 1));
    }
    return [$h, implode("\n", array_slice($lines, $i))];
}
/** Parametro de una cabecera tipo Content-Type (charset, boundary). */
function usenet_param(string $cab, string $name): string {
    if (!preg_match('/[;\s]' . preg_quote($name, '/') . '\s*=\s*(?:"([^"]*)"|([^\s;]+))/i', ' ' . $cab, $m)) return '';
    return ($m[1] ?? '') !== '' ? $m[1] : (string)($m[2] ?? '');
}
/** El texto de un articulo en UTF-8, o null si no es texto (binario, adjunto, vacio). De un multipart se toma la primera parte text/plain
 *  y, si no hay, la primera text/html pasada a texto. */
function usenet_cuerpo(array $h, string $body, int $nivel = 0): ?string {
    $ct = (string)($h['content-type'] ?? 'text/plain');
    $tipo = strtolower(trim(explode(';', $ct)[0]));
    if ($tipo === '') $tipo = 'text/plain';
    if (str_starts_with($tipo, 'multipart/')) {
        $b = usenet_param($ct, 'boundary');
        if ($b === '' || $nivel > 3) return null;
        $partes = explode("\n--" . $b, "\n" . $body);
        array_shift($partes);   // preambulo
        $html = null;
        foreach ($partes as $p) {
            if (str_starts_with($p, '--')) break;   // cierre
            [$ph, $pb] = usenet_partir(explode("\n", ltrim($p, "\n")));
            $pt = strtolower(trim(explode(';', (string)($ph['content-type'] ?? 'text/plain'))[0]));
            if (stripos((string)($ph['content-disposition'] ?? ''), 'attachment') !== false) continue;
            $t = usenet_cuerpo($ph, $pb, $nivel + 1);
            if ($t === null) continue;
            if ($pt !== 'text/html') return $t;
            $html ??= $t;
        }
        return $html;
    }
    if (!str_starts_with($tipo, 'text/')) return null;
    $cte = strtolower(trim((string)($h['content-transfer-encoding'] ?? '')));
    if ($cte === 'quoted-printable') $body = quoted_printable_decode($body);
    elseif ($cte === 'base64') { $d = base64_decode($body, false); if ($d === false) return null; $body = $d; }
    if (preg_match('/^=ybegin |^begin [0-7]{3,4} \S/m', $body)) return null;   // yEnc / uuencode: binarios
    $body = usenet_utf8($body, usenet_param($ct, 'charset'));
    if ($tipo === 'text/html') {
        $body = (string)preg_replace('~<(script|style)\b.*?</\1>~is', '', $body);
        $body = (string)preg_replace('~<br\s*/?>|</p>|</div>|</li>|</tr>|</h[1-6]>~i', "\n", $body);
        $body = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = (string)preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $body);
    $body = (string)preg_replace("/[ \t]+\n/", "\n", $body);
    $body = trim((string)preg_replace("/\n{3,}/", "\n\n", $body), "\n");
    return $body === '' ? null : $body;
}

/* ---- lista de grupos ---- */
/** Trae la lista completa de grupos del servidor (LIST ACTIVE) y sus descripciones (LIST NEWSGROUPS) a usenet_groups. Lo marcado no se toca.
 *  Devuelve cuantos grupos ha dado el servidor. Puede tardar: son decenas de miles de lineas. */
function usenet_grupos_traer(PDO $pdo, ?Nntp $n = null): int {
    $mia = $n === null;
    $n ??= usenet_conectar();
    [$c, $l] = $n->cmd('LIST ACTIVE');
    if ($c !== 215) { [$c, $l] = $n->cmd('LIST'); }
    if ($c !== 215) throw new RuntimeException("el servidor no da la lista de grupos: $l");
    $act = $n->bloque();
    $des = [];
    [$c] = $n->cmd('LIST NEWSGROUPS');
    if ($c === 215) foreach ($n->bloque() as $x) {
        $p = preg_split('/[ \t]+/', $x, 2);
        if (isset($p[1])) $des[strtolower($p[0])] = mb_substr(usenet_cabecera($p[1]), 0, 255);
    }
    if ($mia) $n->cerrar();
    $filas = [];
    foreach ($act as $x) {
        $p = preg_split('/[ \t]+/', trim($x));
        if (count($p) < 3 || !preg_match('/^[a-z0-9][a-z0-9+_.\-]{1,188}$/i', $p[0])) continue;
        $nm = strtolower($p[0]);
        $filas[$nm] = [$nm, $des[$nm] ?? '', strtolower(substr($p[3] ?? 'y', 0, 1)), max(0, (int)$p[2]), max(0, (int)$p[1])];
    }
    foreach (array_chunk(array_values($filas), 400) as $lote) {
        $args = [];
        foreach ($lote as $f) array_push($args, ...$f);
        $pdo->prepare("INSERT INTO usenet_groups (name, descr, posting, srv_low, srv_high, seen) VALUES " . implode(',', array_fill(0, count($lote), '(?,?,?,?,?,NOW())'))
            . " ON DUPLICATE KEY UPDATE descr = IF(VALUES(descr) <> '', VALUES(descr), descr), posting = VALUES(posting), srv_low = VALUES(srv_low), srv_high = VALUES(srv_high), seen = VALUES(seen)")->execute($args);
    }
    return count($filas);
}

/* ---- lectura de mensajes ---- */
/** Guarda un articulo (lineas de ARTICLE) en el grupo. Devuelve 'ok' o el motivo por el que se salta. */
function usenet_guardar(PDO $pdo, array $g, int $num, array $lines): string {
    [$h, $raw] = usenet_partir($lines);
    if (isset($h['control']) || str_starts_with(strtolower((string)($h['subject'] ?? '')), 'cmsg ')) return 'control';
    if (strtolower(trim((string)($h['x-no-archive'] ?? ''))) === 'yes') return 'no-archivar';
    $maxx = (int)cfg('usenet_max_crosspost', 4);
    if ($maxx > 0 && count(array_filter(array_map('trim', explode(',', (string)($h['newsgroups'] ?? ''))))) > $maxx) return 'crosspost';
    $mid = trim((string)($h['message-id'] ?? ''));
    if ($mid === '') return 'sin-id';
    preg_match_all('/<[^<>\s]+>/', (string)($h['references'] ?? ''), $r);
    $refs = $r[0];
    if (!$refs && preg_match('/<[^<>\s]+>/', (string)($h['in-reply-to'] ?? ''), $m)) $refs = [$m[0]];
    $body = usenet_cuerpo($h, $raw);
    if ($body === null) return 'no-texto';
    $max = max(4, (int)cfg('usenet_max_kb', 64)) * 1024;
    if (strlen($body) > $max) $body = mb_strcut($body, 0, $max, 'UTF-8') . "\n[…]";
    $ts = strtotime((string)($h['date'] ?? ''));
    if ($ts === false || $ts > time() + 86400 || $ts < 0) $ts = time();
    $sent = date('Y-m-d H:i:s', $ts);
    $subject = mb_substr(usenet_cabecera((string)($h['subject'] ?? '')), 0, 255);
    if ($subject === '') $subject = '(sin asunto)';
    $gid = (int)$g['group_id'];
    $thr = md5($refs ? $refs[0] : $mid);
    $st = $pdo->prepare("SELECT msg_id, root_id FROM usenet_msgs WHERE group_id = ? AND thr_hash = ? ORDER BY msg_id LIMIT 1");
    $st->execute([$gid, $thr]);
    $p = $st->fetch();
    $root = $p ? ((int)$p['root_id'] ?: (int)$p['msg_id']) : 0;
    $ins = $pdo->prepare("INSERT IGNORE INTO usenet_msgs (group_id, num, mid_hash, thr_hash, root_id, subject, author, sent, body, last_act) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $ins->execute([$gid, $num, md5($mid), $thr, $root, $subject, usenet_autor((string)($h['from'] ?? '')), $sent, $body, $sent]);
    if (!$ins->rowCount()) return 'repetido';
    if ($root) $pdo->prepare("UPDATE usenet_msgs SET replies = replies + 1, last_act = GREATEST(last_act, ?) WHERE msg_id = ?")->execute([$sent, $root]);
    $pdo->prepare("UPDATE usenet_groups SET msgs = msgs + 1, threads = threads + ?, last_msg = GREATEST(COALESCE(last_msg, ?), ?) WHERE group_id = ?")->execute([$root ? 0 : 1, $sent, $sent, $gid]);
    return 'ok';
}
/** Lee lo nuevo de un grupo marcado (como mucho usenet_per_fetch mensajes, y hasta $limite de reloj). Devuelve la linea para el registro del cron. */
function usenet_leer_grupo(PDO $pdo, Nntp $n, array $g, float $limite): string {
    $gid = (int)$g['group_id'];
    $fin = function (string $err, ?int $last = null, ?array $rango = null) use ($pdo, $gid) {
        $pdo->prepare("UPDATE usenet_groups SET last_fetch = NOW(), last_error = ?" . ($last !== null ? ", last_num = " . (int)$last : '')
            . ($rango ? ", srv_low = " . (int)$rango[0] . ", srv_high = " . (int)$rango[1] : '') . " WHERE group_id = ?")->execute([mb_substr($err, 0, 255), $gid]);
    };
    [$c, $l] = $n->cmd('GROUP ' . $g['name']);
    if ($c !== 211) { $fin("el servidor no abre el grupo: $l"); return "usenet {$g['name']}: ERROR $l"; }
    $p = preg_split('/\s+/', $l);
    $low = (int)($p[2] ?? 0); $high = (int)($p[3] ?? 0);
    $last = (int)$g['last_num'];
    $primera = max($low, $high - max(1, (int)cfg('usenet_first_n', 300)) + 1);
    $desde = ($last === 0 || $last > $high) ? $primera : $last + 1;   // sin leer aun, o el servidor ha renumerado: los ultimos usenet_first_n
    if ($high < 1 || $desde > $high) { $fin('', null, [$low, $high]); return ''; }
    $hasta = min($high, $desde + max(1, (int)cfg('usenet_per_fetch', 100)) - 1);
    $maxb = max(4, (int)cfg('usenet_max_kb', 64)) * 1024 * 2;   // el articulo en bruto (cabeceras, base64) abulta mas que su texto
    // resumen del tramo: que numeros existen y cuanto pesan, para no bajar binarios
    $nums = null;
    [$c] = $n->cmd("OVER $desde-$hasta");
    if ($c !== 224) [$c] = $n->cmd("XOVER $desde-$hasta");
    if ($c === 224) { $nums = []; foreach ($n->bloque() as $x) { $f = explode("\t", $x); if ((int)$f[0] > 0) $nums[(int)$f[0]] = (int)($f[6] ?? 0); } }
    else { $nums = array_fill_keys(range($desde, $hasta), 0); }
    $ok = 0; $salt = 0; $hecho = $desde - 1; $cortado = false;
    foreach ($nums as $num => $bytes) {
        if (microtime(true) > $limite) { $cortado = true; break; }
        $hecho = $num;
        if ($bytes > $maxb) { $salt++; continue; }
        [$c] = $n->cmd("ARTICLE $num");
        if ($c !== 220) continue;   // 423 / 430: ya no esta
        $lines = $n->bloque($maxb * 2);
        try { usenet_guardar($pdo, $g, $num, $lines) === 'ok' ? $ok++ : $salt++; }
        catch (Throwable $e) { $salt++; }
    }
    $fin('', $cortado ? $hecho : $hasta, [$low, $high]);
    return ($ok || $salt) ? "usenet {$g['name']}: $ok mensajes nuevos" . ($salt ? ", $salt saltados" : '') . ($hasta < $high || $cortado ? ' (quedan más)' : '') : '';
}
/** Cron (cada minuto, desde scripts/promocionar.php): lee los grupos marcados a los que les toca (usenet_interval_min), los mas atrasados
 *  primero, durante ~20 s como mucho. $force = todos ya, sin esperar al intervalo (boton «Leer ahora» de admin → Usenet). */
function usenet_tick(PDO $pdo, bool $force = false, int $segundos = 20): array {
    if (!usenet_configurado()) return [];
    try {
        $int = max(1, (int)cfg('usenet_interval_min', 15));
        $gs = $pdo->query("SELECT * FROM usenet_groups WHERE activo = 1" . ($force ? '' : " AND (last_fetch IS NULL OR last_fetch < DATE_SUB(NOW(), INTERVAL $int MINUTE))")
            . " ORDER BY (last_fetch IS NOT NULL), last_fetch LIMIT 40")->fetchAll();
    } catch (Throwable $e) { return []; }   // sin migra-206
    if (!$gs) return [];
    $out = [];
    try { $n = usenet_conectar(); }
    catch (Throwable $e) {   // servidor caido o clave mala: se apunta y no se reintenta hasta el siguiente intervalo
        $pdo->prepare("UPDATE usenet_groups SET last_fetch = NOW(), last_error = ? WHERE group_id IN (" . implode(',', array_map(fn($g) => (int)$g['group_id'], $gs)) . ")")->execute([mb_substr($e->getMessage(), 0, 255)]);
        return ['usenet: ERROR ' . $e->getMessage()];
    }
    $limite = microtime(true) + $segundos;
    foreach ($gs as $g) {
        if (microtime(true) > $limite) break;
        try { $l = usenet_leer_grupo($pdo, $n, $g, $limite); if ($l !== '') $out[] = $l; }
        catch (Throwable $e) { $out[] = "usenet {$g['name']}: ERROR " . $e->getMessage(); break; }   // conexion rota: el resto, en la siguiente pasada
    }
    $n->cerrar();
    return $out;
}
/** Poda (una vez al dia): con usenet_keep_days > 0, fuera los hilos sin actividad desde hace mas dias; y los mensajes de grupos desmarcados
 *  si $desmarcados (boton de admin → Usenet). Recuenta los grupos. */
function usenet_podar(PDO $pdo, bool $desmarcados = false): int {
    $n = 0;
    $d = (int)cfg('usenet_keep_days', 0);
    if ($d > 0) {
        $st = $pdo->prepare("DELETE m FROM usenet_msgs m JOIN usenet_msgs r ON r.msg_id = IF(m.root_id = 0, m.msg_id, m.root_id) WHERE r.last_act < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $st->execute([$d]); $n += $st->rowCount();
    }
    if ($desmarcados) {
        $n += $pdo->exec("DELETE m FROM usenet_msgs m JOIN usenet_groups g ON g.group_id = m.group_id WHERE g.activo = 0");
        $pdo->exec("UPDATE usenet_groups SET last_num = 0, last_fetch = NULL WHERE activo = 0");
    }
    if ($n) $pdo->exec("UPDATE usenet_groups g LEFT JOIN (SELECT group_id, COUNT(*) m, SUM(root_id = 0) t, MAX(sent) u FROM usenet_msgs GROUP BY group_id) x ON x.group_id = g.group_id
                        SET g.msgs = COALESCE(x.m, 0), g.threads = COALESCE(x.t, 0), g.last_msg = x.u WHERE g.msgs > 0 OR x.m IS NOT NULL");
    return $n;
}

/* ---- pintar ---- */
/** El texto de un mensaje, en HTML: escapado, con los enlaces pulsables, las citas («> …») y la firma («-- ») apagadas y los correos recortados. */
function usenet_texto(string $body): string {
    $out = []; $firma = false;
    foreach (explode("\n", $body) as $l) {
        if ($l === '-- ' || $l === '--') $firma = true;
        $e = '';
        foreach (preg_split('~(\bhttps?://[^\s<>"\']+)~i', $l, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $p) {
            if ($i % 2) { $u = rtrim($p, '.,;:!?)'); $e .= '<a href="' . h($u) . '" rel="nofollow noopener ugc" target="_blank">' . h($u) . '</a>' . h(substr($p, strlen($u))); }
            else $e .= h((string)preg_replace('/\b([A-Za-z0-9._%+\-]{1,64})@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}\b/', '$1@…', $p));
        }
        $out[] = ($firma || preg_match('/^\s{0,3}[>|]/', $l)) ? "<span class=\"q\">$e</span>" : $e;
    }
    return implode("\n", $out);
}
/** URL de un grupo y de un hilo. */
function usenet_url(string $grupo, int $hilo = 0): string { return '/usenet?g=' . rawurlencode($grupo) . ($hilo ? "&h=$hilo" : ''); }
