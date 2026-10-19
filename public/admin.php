<?php
require __DIR__ . '/../lib/app.php';
/** Grupos de fuentes con interruptor en admin > Config: clave => [rotulo, descripcion, valor por defecto si la clave aun no existe]. */
const DDD_CFG_GRUPOS = [
    'sources_youtube'     => ['Canales (YouTube)', '«Mis canales»: activado = los canales de YouTube se ven y se leen; desactivado = fuera de todo salvo admin > Fuentes, y tampoco hay portada Canales (los vídeos de YouTube van a Medios).', '1'],
    'sources_agregadores' => ['Agregadores', '«Mis agregadores»: activado = los agregadores (Menéame, Mediatize, Renegados…) se ven y se leen; desactivado = fuera de todo salvo admin > Fuentes.', '0'],
];
/** Opciones de processes_votes_who (quién ve la pestaña Votos de /procesos): valor => etiqueta. */
const DDD_PROCESSES_VOTES_WHO = [
    'todos'       => 'Todos, también los anónimos',
    'usuarios'    => 'Solo usuarios registrados',
    'moderadores' => 'Solo moderadores',
    'admin'       => 'Solo admin',
];
require __DIR__ . '/../lib/ciclo.php';
require __DIR__ . '/../lib/fuentes.php';
if (!is_admin()) { http_response_code(403); exit('Solo admin'); }

$out = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $out[] = 'El envío superaba post_max_size de PHP (' . ini_get('post_max_size') . ') y se ha descartado entero.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = post('act');
    mod_log_post($pdo, 'admin', $out);   // registro de acciones (migra-134)
    if ($act === 'config') {
        $st = $pdo->prepare("UPDATE config SET cvalue = ? WHERE ckey = ?");
        // interruptores de grupos de fuentes («Mis canales», «Mis agregadores»): se crean si aun no existen (migra-070 / 103 sin aplicar)
        $up = $pdo->prepare("INSERT INTO config (ckey, cvalue, descr) VALUES (?,?,?) ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue)");
        foreach (DDD_CFG_GRUPOS as $k => [, $descr]) if (isset($_POST['cfg'][$k])) { $up->execute([$k, $_POST['cfg'][$k] === '1' ? '1' : '0', $descr]); unset($_POST['cfg'][$k]); }
        foreach (($_POST['cfg'] ?? []) as $k => $v) {
            if ($k === 'skin_default' && !isset(DDD_SKINS[(string)$v])) $v = 'editorial';   // solo skins existentes
            if ($k === 'processes_votes_who' && !isset(DDD_PROCESSES_VOTES_WHO[(string)$v])) $v = 'todos';   // solo grupos existentes
            $st->execute([trim((string)$v), (string)$k]);
        }
        $out[] = 'Config guardada.';
    } elseif ($act === 'aviso') {
        // aviso a la comunidad (13/10/2026): texto de hasta 150 palabras + interruptor; sustituye los avisos grises de cabecera mientras este activado
        $txt = trim(str_replace(["\r\n", "\r"], "\n", (string)post('aviso_txt')));
        $palabras = $txt === '' ? 0 : count(preg_split('/\s+/u', $txt, -1, PREG_SPLIT_NO_EMPTY));
        if ($palabras > 150) $out[] = "El aviso tiene $palabras palabras; el máximo son 150. No se ha guardado.";
        else {
            $up = $pdo->prepare("INSERT INTO config (ckey, cvalue, descr) VALUES (?,?,?) ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue)");
            $up->execute(['aviso_txt', mb_substr($txt, 0, 2000), 'Aviso a la comunidad: texto (hasta 150 palabras) que sustituye los avisos grises de cabecera']);
            $on = post('aviso_on') === '1' && $txt !== '';
            $up->execute(['aviso_on', $on ? '1' : '0', 'Aviso a la comunidad: 1 = activado']);
            $out[] = $on ? "Aviso guardado y activado ($palabras palabras): se ve en todas las cabeceras." : ($txt === '' && post('aviso_on') === '1' ? 'No se activa un aviso vacío.' : 'Aviso guardado, desactivado.');
        }
    } elseif ($act === 'pagina') {
        // slug base + idioma: la española es «que-es»; las traducciones, «que-es.en», «que-es.fr»…
        $base = in_array(post('slug'), ['privacidad', 'normas', 'algoritmo', 'que-es', 'empezar'], true) ? post('slug') : 'privacidad';
        $pl = (string)post('pl');
        $pslug = ($pl !== 'es' && isset(DDD_LANGS[$pl])) ? "$base.$pl" : $base;
        if (post('borrar') === '1' && $pslug !== $base) {
            $pdo->prepare("DELETE FROM pages WHERE slug = ?")->execute([$pslug]);
            $out[] = "Traducción $pslug borrada: en ese idioma se verá la española.";
        } else {
            $pdo->prepare("INSERT INTO pages (slug, title, body) VALUES (?,?,?) ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body)")
                ->execute([$pslug, post('title', 'Página'), (string)($_POST['body'] ?? '')]);
            $out[] = "Página $pslug guardada.";
        }
    } elseif ($act === 'topics') {
        $st = $pdo->prepare("UPDATE topics SET bonus = ?, color = ? WHERE topic_id = ?");
        $sn = $pdo->prepare("UPDATE topics SET name = ? WHERE topic_id = ?");
        foreach (($_POST['bonus'] ?? []) as $id => $c) {
            $col = (string)($_POST['color'][$id] ?? '');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $col)) $col = '#534AB7';
            $st->execute([max(0.0, min(5.0, (float)str_replace(',', '.', (string)$c))), $col, (int)$id]);
            // nombre (para poner tildes o renombrar): mismas reglas que al crear; si no vale, se deja el que habia
            $name = mb_strtolower(trim((string)($_POST['name'][$id] ?? '')));
            if ($name !== '' && preg_match('/^[\p{L}\p{N} \-]{2,32}$/u', $name)) $sn->execute([$name, (int)$id]);
            elseif ($name !== '') $out[] = "Nombre de tema no válido («" . h($name) . "»): 2–32 letras, números, espacio o guion.";
        }
        $out[] = 'Temas guardados.';
    } elseif ($act === 'lateral') {   // orden y visibilidad de los modulos de la columna derecha
        require_once __DIR__ . '/../lib/lateral.php';
        $pos = []; foreach (($_POST['pos'] ?? []) as $k => $v) if (isset(side_modules()[$k]) && !empty($_POST['on'][$k])) $pos[$k] = (int)$v;
        asort($pos);
        $pdo->prepare("INSERT INTO config (ckey, cvalue, descr) VALUES ('side_order', ?, 'Modulos de la columna derecha (escritorio), en orden; lo que no este no se pinta') ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue)")->execute([implode(',', array_keys($pos))]);
        $out[] = 'Lateral guardado: ' . (implode(', ', array_keys($pos)) ?: 'sin módulos');
    } elseif ($act === 'foros') {   // foros (migra-159): activar/desactivar, nombre, descripcion, orden de pestañas y orden de hilos
        $st = $pdo->prepare("UPDATE forums SET status = ?, name = ?, descr = ?, position = ?, orden = ?, min_aura = ? WHERE forum_id = ?");
        foreach (($_POST['fname'] ?? []) as $id => $nm) {
            $nm = trim((string)$nm);
            if ($nm === '' || mb_strlen($nm) > 48) { $out[] = "Nombre de foro no válido (#$id): 1–48 caracteres."; continue; }
            $st->execute([!empty($_POST['fon'][$id]) ? 'on' : 'off', $nm, mb_substr(trim((string)($_POST['fdescr'][$id] ?? '')), 0, 300), max(1, min(250, (int)($_POST['fpos'][$id] ?? 1))), ($_POST['forden'][$id] ?? '') === 'actividad' ? 'actividad' : 'fecha', max(0, (float)str_replace(',', '.', (string)($_POST['faura'][$id] ?? '0'))), (int)$id]);   // aura minima (migra-165)
        }
        $out[] = 'Foros guardados.';
    } elseif ($act === 'foro_nuevo') {
        $slug = mb_strtolower(trim(post('slug')));
        $nm = trim(post('name'));
        if (!preg_match('/^[a-z0-9-]{2,16}$/', $slug)) $out[] = "Slug no válido («" . h($slug) . "»): 2–16 letras minúsculas sin tilde, números o guion. Es la URL (/foros?f=slug) y no se cambia luego.";
        elseif ($nm === '' || mb_strlen($nm) > 48) $out[] = 'Pon un nombre (1–48 caracteres).';
        elseif ($pdo->query("SELECT COUNT(*) FROM forums WHERE slug = " . $pdo->quote($slug))->fetchColumn()) $out[] = "Ya hay un foro con el slug «" . h($slug) . "».";
        else {
            $pos = (int)$pdo->query("SELECT COALESCE(MAX(position), 0) + 1 FROM forums")->fetchColumn();
            $pdo->prepare("INSERT INTO forums (slug, name, descr, status, position, orden) VALUES (?,?,?,?,?,?)")
                ->execute([$slug, $nm, mb_substr(trim(post('descr')), 0, 300), post('on') === '1' ? 'on' : 'off', $pos, post('orden') === 'actividad' ? 'actividad' : 'fecha']);
            $out[] = "Foro «" . h($nm) . "» creado (/foros?f=$slug), " . (post('on') === '1' ? 'activado.' : 'apagado: solo lo ves tú hasta que lo actives.');
        }
    } elseif ($act === 'tema_borrar') {
        if (post('seguro') !== '1') $out[] = 'Marca la casilla de confirmación para borrar el tema.';
        else { $e = topic_delete($pdo, (int)post('topic_id'), (int)post('to')); $out[] = $e ?: 'Tema borrado; sus envíos han pasado al tema elegido.'; }
    } elseif ($act === 'tema_nuevo') {
        $name = mb_strtolower(trim(post('name')));
        $bonus = (float)str_replace(',', '.', post('bonus') ?: '0');
        $col  = post('color') ?: '#534AB7';
        if (!preg_match('/^[\p{L}\p{N} \-]{2,32}$/u', $name)) $out[] = 'Nombre de tema no válido (2–32 letras, números, espacio o guion).';
        elseif ($bonus < 0 || $bonus > 5) $out[] = 'El bonus debe estar entre 0 y 5.';
        elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $col)) $out[] = 'Color no válido (formato #rrggbb).';
        else {
            try { $pdo->prepare("INSERT INTO topics (name, bonus, color) VALUES (?,?,?)")->execute([$name, $bonus, $col]); $out[] = "Tema «" . h($name) . "» creado."; }
            catch (PDOException $e) { $out[] = 'Ese tema ya existe.'; }
        }
    } elseif ($act === 'fuente_nueva' && (post('rellenar') !== '' || trim(post('name')) === '')) {
        // «Rellenar desde la web» (13/10/2026): con solo la web se buscan RSS, nombre, descripcion e idioma y se devuelve el formulario relleno, sin crear
        require_once __DIR__ . '/../lib/propuestas.php';
        $rf = fprop_rellenar(['name' => post('name'), 'kind' => post('kind'), 'home' => post('home'), 'feed' => post('feed_portada'), 'descr' => post('descr'), 'lang' => post('lang')]);
        $fuentePre = ['name' => $rf['name'], 'kind' => $rf['kind'], 'home' => $rf['home'], 'feed_portada' => $rf['feed'], 'descr' => $rf['descr'], 'lang' => $rf['lang'], 'interval_min' => (int)post('interval_min') ?: 60,
                      'slug' => trim(post('slug')) !== '' ? post('slug') : substr(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', (string)$rf['name']))), 0, 24)];
        $fuentePre['slug'] = trim($fuentePre['slug'], '-');
        $out[] = $rf['error'] !== '' ? $rf['error'] : 'Datos tomados de la web (RSS: ' . h($rf['feed']) . ', ' . (int)$rf['items'] . ' noticias). Revísalos y pulsa «Crear fuente».';
    } elseif ($act === 'fuente' || $act === 'fuente_nueva') {
        // alta / edicion de una fuente externa (Meneame, medios con RSS)
        $id = (int)post('source_id');
        $slug = strtolower(trim(post('slug')));
        $kind = in_array(post('kind'), ['agregador', 'newsletter', 'youtube'], true) ? post('kind') : 'medio';
        $name = mb_substr(trim(post('name')), 0, 48);
        $home = trim(post('home')); $fp = trim(post('feed_portada')); $fn = trim(post('feed_nuevas'));
        // canal de YouTube: basta la direccion del canal en «web» (o en «RSS»); el feed se saca de ahi (youtube_feed_de)
        $yterr = null;
        if ($kind === 'youtube') { $fn = ''; $fp = youtube_feed_de($fp !== '' ? $fp : $home, $yterr) ?? ''; }
        $skin = isset(DDD_SKINS[post('skin')]) ? post('skin') : '';
        $topic = (int)post('topic_id') ?: null;
        $iv = max(1, min(1440, (int)(post('interval_min') ?: 60)));
        $status = post('status') === 'off' ? 'off' : 'ok';
        $descr = mb_substr(trim(post('descr')), 0, 200) ?: null;
        $featured = post('featured') === '1' ? 1 : 0;
        $slang = preg_match('/^[a-z]{2}$/', post('lang')) ? post('lang') : '';   // '' = detectar del RSS
        if ($slang === '' && preg_match('#^https?://[^\s]+$#i', $fp) && ($x = http_get($fp, 8, 4000000)) !== null) $slang = fuentes_feed_lang($x);
        if (!preg_match('/^[a-z0-9\-]{2,24}$/', $slug)) $out[] = 'Slug no válido (2–24 letras minúsculas, números o guion).';
        elseif ($name === '') $out[] = 'Falta el nombre.';
        elseif ($kind === 'youtube' && $fp === '') $out[] = 'Canal de YouTube: ' . h((string)$yterr) . '. Pega en «RSS» el feed a mano: https://www.youtube.com/feeds/videos.xml?channel_id=UC… (el id sale en YouTube → el canal → «Compartir canal» → «Copiar ID del canal»).';
        elseif (!preg_match('#^https?://[^\s]+$#i', $home) || !preg_match('#^https?://[^\s]+$#i', $fp) || ($fn !== '' && !preg_match('#^https?://[^\s]+$#i', $fn))) $out[] = 'Web y feeds deben ser URLs http(s).';
        else {
            try {
                if ($act === 'fuente_nueva') {
                    $pdo->prepare("INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                        ->execute([$slug, $kind, $name, $home, $descr, $slang, $skin, $topic, $fp, $fn, $iv, $status, $featured]);
                    $out[] = "Fuente «" . h($name) . "» creada, idioma: " . ($slang !== '' ? $slang : 'sin detectar (el feed no lo dice: elígelo a mano)') . ". Se leerá en el próximo minuto del cron (o ahora con «leer»).";
                } else {
                    $pdo->prepare("UPDATE sources SET slug = ?, kind = ?, name = ?, home = ?, descr = ?, lang = ?, skin = ?, topic_id = ?, feed_portada = ?, feed_nuevas = ?, interval_min = ?, status = ?, featured = ? WHERE source_id = ?")
                        ->execute([$slug, $kind, $name, $home, $descr, $slang, $skin, $topic, $fp, $fn, $iv, $status, $featured, $id]);
                    $out[] = "Fuente «" . h($name) . "» guardada.";
                }
            } catch (PDOException $e) { $out[] = 'Ese slug ya existe.'; }
            try { $pdo->prepare("UPDATE sources SET relay = ? WHERE slug = ?")->execute([post('relay') === '1' ? 1 : 0, $slug]); } catch (Throwable $e) { /* migra-098 sin aplicar */ }   // lector local
        }
    } elseif ($act === 'fuente_leer_todas') {
        // desde la web, como mucho ~25 s (Cloudflare corta a los 100 s con un 524): las mas atrasadas primero; el resto lo sigue el cron
        @set_time_limit(90);
        $lk = (string)post('kind');   // '' = todas; o solo un tipo (newsletter | medio | youtube | agregador), 15/10/2026
        if (!in_array($lk, ['newsletter', 'medio', 'youtube', 'agregador'], true)) $lk = '';
        $out = fuentes_tick($pdo, true, 50, 25, $lk);
        if (!$out) $out[] = $lk === '' ? 'No hay fuentes activas.' : 'No hay fuentes activas de ese tipo (o están apagadas en Config).';
        elseif (str_contains((string)end($out), 'pendientes')) $out[] = 'Las que faltan las lee el cron en los próximos minutos (o vuelve a pulsar el botón).';
    } elseif ($act === 'fuente_foto') {
        // diagnostico: por que no hay foto en la ultima noticia sin foto de esta fuente
        $q = $pdo->prepare("SELECT i.title, i.url FROM source_items i WHERE i.source_id = ? AND i.image IS NULL ORDER BY i.pub_date DESC LIMIT 1"); $q->execute([(int)post('source_id')]);
        if ($it = $q->fetch()) { $out[] = "Probando la foto de «" . $it['title'] . "»"; $out = array_merge($out, fuente_diag_imagen($it['url'])); }
        else $out[] = 'Todas las noticias de esa fuente tienen foto (o no hay noticias).';
    } elseif ($act === 'fuente_leer') {
        $q = $pdo->prepare("SELECT * FROM sources WHERE source_id = ?"); $q->execute([(int)post('source_id')]);
        if ($src = $q->fetch()) { try { $out = array_merge($out, fuente_actualizar($pdo, $src)); } catch (Throwable $e) { $out[] = 'ERROR: ' . $e->getMessage(); } }
    } elseif ($act === 'fuentes_destacar') {
        // Selector de destacadas de un tipo (admin -> Fuentes): lo marcado queda featured = 1, el resto del tipo, 0
        $fk = (string)post('kind');
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['f'] ?? []))));
        $pdo->prepare("UPDATE sources SET featured = 0 WHERE kind = ?")->execute([$fk]);
        if ($ids) $pdo->prepare("UPDATE sources SET featured = 1 WHERE kind = ? AND source_id IN (" . implode(',', $ids) . ")")->execute([$fk]);
        $out[] = count($ids) . " fuentes destacadas en " . h($fk) . ".";
    } elseif ($act === 'fuente_borrar') {
        $q = $pdo->prepare("SELECT source_id, name FROM sources WHERE source_id = ?"); $q->execute([(int)post('source_id')]);
        if ($src = $q->fetch()) {
            foreach ($pdo->query("SELECT image FROM source_items WHERE source_id = " . (int)$src['source_id'] . " AND image IS NOT NULL") as $r) @unlink(DDD_MEDIA_DIR . '/' . $r['image']);
            $pdo->prepare("DELETE FROM sources WHERE source_id = ?")->execute([$src['source_id']]);   // items y elecciones caen en cascada
            $out[] = "Fuente «" . h($src['name']) . "» borrada con sus noticias.";
        }
    } elseif ($act === 'ciclo') {
        $out = run_cycle($pdo);
        if (!$out) $out[] = 'Ciclo sin cambios.';
    } elseif ($act === 'reloj') {
        $m = max(1, (int)post('min'));
        $pdo->prepare("UPDATE stories SET sent_date = sent_date - INTERVAL ? MINUTE,
                        published_date = IF(published_date IS NULL, NULL, published_date - INTERVAL ? MINUTE)")->execute([$m, $m]);
        $pdo->prepare("UPDATE comments SET created = created - INTERVAL ? MINUTE")->execute([$m]);
        $out[] = "Reloj adelantado $m min (todas las fechas retrasadas).";
    } elseif ($act === 'mods') {
        // niveles de la pagina: normal | mod | admin por usuario (select lvl[id]); el propio no se toca
        $ids = array_map('intval', explode(',', post('ids')));
        $lv  = $_POST['lvl'] ?? [];
        $up  = $pdo->prepare("UPDATE users SET level = ? WHERE user_id = ? AND level <> ?");
        $n = 0;
        foreach ($ids as $id) {
            if ($id === (int)user()['user_id']) continue;
            $l = (string)($lv[$id] ?? '');
            if (!in_array($l, ['normal', 'mod', 'admin'], true)) continue;
            $up->execute([$l, $id, $l]); $n += $up->rowCount();
        }
        $out[] = $n ? "Niveles guardados ($n " . ($n === 1 ? 'cambio' : 'cambios') . ")." : 'Niveles sin cambios.';
    } elseif ($act === 'humano') {
        $item = (int)post('item'); $dec = post('dec');
        $q = $pdo->prepare("SELECT item_type, ref_id FROM mod_queue WHERE item_id = ?"); $q->execute([$item]);
        if ($it = $q->fetch()) {
            if ($it['item_type'] === 'story') {
                $pdo->prepare("UPDATE stories SET status = ? WHERE story_id = ?")->execute([$dec === 'ok' ? 'nuevas' : 'rejected', $it['ref_id']]);
            } else {
                $pdo->prepare("UPDATE comments SET status = ? WHERE comment_id = ?")->execute([$dec === 'ok' ? 'ok' : 'rejected', $it['ref_id']]);
                if ($dec === 'ok') $pdo->prepare("UPDATE stories s JOIN comments c ON c.story_id = s.story_id SET s.comments = s.comments + 1 WHERE c.comment_id = ?")->execute([$it['ref_id']]);
            }
            $pdo->prepare("UPDATE mod_queue SET status = ?, processed = NOW() WHERE item_id = ?")->execute([$dec === 'ok' ? 'ok' : 'reject', $item]);
            $out[] = "Item $item: $dec.";
        }
    } elseif ($act === 'verificar') {
        $id = (int)post('uid');
        $pdo->prepare("UPDATE users SET verified = 1, token = NULL, token_sent = NULL WHERE user_id = ?")->execute([$id]);
        $out[] = "Usuario $id: correo validado a mano.";
    } elseif ($act === 'logo') {
        // icono cuadrado (favicon, pantalla de inicio, PWA)
        if (post('quitar_icon') === '1') {
            if ($b = cfg('logo_icon')) foreach (glob(DDD_MEDIA_DIR . '/logo/' . $b . '-*.png') ?: [] as $f) @unlink($f);
            config_set($pdo, 'logo_icon', ''); $out[] = 'Icono: restaurado el de serie.';
        } elseif (!empty($_FILES['logo_icon']['name'])) {
            $r = icono_desde_subida($_FILES['logo_icon']);
            if (str_starts_with($r, 'ERR:')) $out[] = 'Icono: ' . substr($r, 4);
            else { if ($b = cfg('logo_icon')) foreach (glob(DDD_MEDIA_DIR . '/logo/' . $b . '-*.png') ?: [] as $f) @unlink($f); config_set($pdo, 'logo_icon', $r); $out[] = 'Icono guardado (favicon, iOS y PWA). Los navegadores tardan en soltar el favicon viejo: cierra la pestaña y vuelve a abrirla.'; }
        }
        foreach (['light' => 'claro', 'dark' => 'oscuro'] as $m => $lbl) {
            $key = "logo_$m"; $actual = cfg($key);
            if (post("quitar_$m") === '1') {
                if ($actual) @unlink(DDD_MEDIA_DIR . '/logo/' . $actual);
                config_set($pdo, $key, '');
                $out[] = "Logo $lbl: restaurado el de serie.";
            } elseif (!empty($_FILES["logo_$m"]['name'])) {
                $r = logo_desde_subida($_FILES["logo_$m"], $m);
                if (is_string($r) && str_starts_with($r, 'ERR:')) $out[] = "Logo $lbl: " . substr($r, 4);
                else { if ($actual) @unlink(DDD_MEDIA_DIR . '/logo/' . $actual); config_set($pdo, $key, $r); $out[] = "Logo $lbl guardado."; }
            }
        }
    } elseif ($act === 'borrar') {
        $id = (int)post('uid');
        $q = $pdo->prepare("SELECT user_id, login, level FROM users WHERE user_id = ?"); $q->execute([$id]);
        $victima = $q->fetch();
        if (!$victima) $out[] = "Usuario $id no existe.";
        elseif ($victima['level'] === 'admin') $out[] = "No se borra a un admin (bájale el nivel antes, en su ficha o en la columna «nivel»).";
        elseif (post('confirmar') !== $victima['login']) {
            $pedir_confirmacion = $victima;   // se pinta abajo
        } else {
            $out = array_merge($out, borrar_usuario($pdo, $id));
        }
    }
    // recargar config tras cambios
    $CFG = [];
    foreach ($pdo->query("SELECT ckey, cvalue FROM config") as $r) $CFG[$r['ckey']] = $r['cvalue'];
}

function config_set(PDO $pdo, string $k, string $v): void {
    $pdo->prepare("INSERT INTO config (ckey, cvalue, descr) VALUES (?,?,?) ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue)")->execute([$k, $v, "Logo (Admin > Logo)"]);
}

/**
 * Logo subido desde admin: PNG, SVG o WebP de hasta 1 MB, se guarda tal cual (sin recortar: el logo no se toca)
 * en /media/logo con nombre unico. Devuelve el nombre de fichero o "ERR:mensaje".
 */
function logo_desde_subida(array $file, string $mode): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) return 'ERR:no se recibió el fichero.';
    if ($file['size'] > 4000000) return 'ERR:máximo 4 MB.';
    $bytes = file_get_contents($file['tmp_name']);
    if ($bytes === false || strlen($bytes) < 50) return 'ERR:fichero vacío.';
    if (class_exists('finfo')) $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
    else { $gi = @getimagesizefromstring($bytes); $mime = $gi ? $gi['mime'] : 'text/plain'; }   // sin fileinfo: GD para PNG/WebP, y el SVG por su etiqueta
    $ext = ['image/png' => 'png', 'image/svg+xml' => 'svg', 'image/webp' => 'webp'][$mime] ?? null;
    // finfo no siempre reconoce el SVG (sin cabecera XML lo da como text/plain, text/html o text/xml): vale si el fichero es un <svg>
    if (!$ext && preg_match('#^(text/|application/xml)#', $mime) && preg_match('/<svg[\s>]/i', substr($bytes, 0, 4000))) $ext = 'svg';
    if (!$ext) return "ERR:formato no admitido ($mime); vale PNG, SVG o WebP, mejor con fondo transparente.";
    if ($ext === 'svg') {
        if (preg_match('/<script|\bon[a-z]+\s*=|javascript:|<foreignObject/i', $bytes)) return 'ERR:el SVG lleva scripts o eventos; expórtalo limpio.';
    } else {
        $info = @getimagesizefromstring($bytes);
        if (!$info) return 'ERR:no se pudo leer la imagen.';
        if ($info[1] < 30) return 'ERR:demasiado pequeño: al menos 30 px de alto (se muestra a 30 px; mejor 60–120 px).';
        // Se muestra a 30 px de alto: si viene grande, se reduce a 240 px de alto (8x) conservando la transparencia, y se guarda como PNG
        if ($info[1] > 240 || $info[0] > 2400) {
            $src = @imagecreatefromstring($bytes);
            if (!$src) return 'ERR:no se pudo abrir la imagen para reducirla.';
            $sw = imagesx($src); $sh = imagesy($src);
            $k = min(240 / $sh, 2400 / $sw);
            $nw = max(1, (int)round($sw * $k)); $nh = max(1, (int)round($sh * $k));
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
            imagedestroy($src);
            ob_start(); imagepng($dst, null, 6); $bytes = ob_get_clean(); imagedestroy($dst);
            if (!$bytes) return 'ERR:no se pudo reducir la imagen.';
            $ext = 'png';
        }
    }
    $dir = DDD_MEDIA_DIR . '/logo';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = "logo-$mode-" . bin2hex(random_bytes(6)) . ".$ext";
    return file_put_contents("$dir/$name", $bytes) ? $name : 'ERR:no se pudo escribir en /media/logo (permisos).';
}

/**
 * Icono del sitio: PNG/WebP cuadrado de 192 px o mas. Genera en /media/logo las variantes 64 (favicon), 180 (iOS),
 * 192 y 512 (PWA) y 512m (maskable: el icono al 80 %% sobre fondo blanco, para que Android lo recorte sin comerselo).
 * Devuelve la base del nombre (icon-<hex>) o "ERR:mensaje".
 */
function icono_desde_subida(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) return 'ERR:no se recibió el fichero.';
    if ($file['size'] > 4000000) return 'ERR:máximo 4 MB.';
    $bytes = file_get_contents($file['tmp_name']);
    $src = $bytes ? @imagecreatefromstring($bytes) : false;
    if (!$src) return 'ERR:no se pudo leer la imagen (PNG o WebP).';
    $sw = imagesx($src); $sh = imagesy($src);
    if (min($sw, $sh) < 192) { imagedestroy($src); return 'ERR:demasiado pequeño: al menos 192×192 px (mejor 512×512).'; }
    if (abs($sw - $sh) > max($sw, $sh) * 0.05) { imagedestroy($src); return "ERR:tiene que ser cuadrado (es de {$sw}×{$sh})."; }
    $side = min($sw, $sh); $cx = intdiv($sw - $side, 2); $cy = intdiv($sh - $side, 2);
    $dir = DDD_MEDIA_DIR . '/logo'; if (!is_dir($dir)) mkdir($dir, 0775, true);
    $base = 'icon-' . bin2hex(random_bytes(6));
    foreach ([64 => '64', 180 => '180', 192 => '192', 512 => '512'] as $px => $suf) {
        $dst = imagecreatetruecolor($px, $px); imagealphablending($dst, false); imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, $cx, $cy, $px, $px, $side, $side);
        $ok = imagepng($dst, "$dir/$base-$suf.png", 6); imagedestroy($dst);
        if (!$ok) { imagedestroy($src); return 'ERR:no se pudo escribir en /media/logo (permisos).'; }
    }
    // maskable: fondo blanco y el icono al 80 %% centrado (zona segura de Android)
    $dst = imagecreatetruecolor(512, 512); imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagealphablending($dst, true);
    imagecopyresampled($dst, $src, 51, 51, $cx, $cy, 410, 410, $side, $side);
    imagepng($dst, "$dir/$base-512m.png", 6); imagedestroy($dst); imagedestroy($src);
    return $base;
}

/**
 * Borra una cuenta con todo lo suyo, en cascada y de verdad (no "discard"):
 *  - sus envíos, con TODOS los comentarios, votos, alias, cola de moderación, log de promociones y foto de cada uno
 *    (también los comentarios y votos de otros usuarios en esos hilos: el hilo desaparece entero)
 *  - sus comentarios en hilos ajenos, con las respuestas que cuelgan de ellos (aunque sean de otros)
 *  - sus votos (ajustando los contadores de la noticia), alias por hilo, log de aura, avatar
 */
function borrar_usuario(PDO $pdo, int $uid): array {
    $log = []; $ficheros = [];
    $pdo->beginTransaction();
    try {
        $u = $pdo->prepare("SELECT login, avatar FROM users WHERE user_id = ? FOR UPDATE"); $u->execute([$uid]); $u = $u->fetch();
        if (!$u) throw new RuntimeException("no existe");
        if ($u['avatar']) $ficheros[] = DDD_MEDIA_DIR . '/avatars/' . $u['avatar'];

        // --- 1. hilos que envió: se llevan por delante todo lo que cuelga de ellos
        $st = $pdo->prepare("SELECT story_id, image FROM stories WHERE user_id = ?"); $st->execute([$uid]);
        $hilos = $st->fetchAll();
        $ncom_ajenos = 0;
        foreach ($hilos as $h) {
            $sid = (int)$h['story_id'];
            if ($h['image']) $ficheros[] = DDD_MEDIA_DIR . '/' . $h['image'];
            $c = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE story_id = ? AND (user_id IS NULL OR user_id <> ?)"); $c->execute([$sid, $uid]);
            $ncom_ajenos += (int)$c->fetchColumn();
            $pdo->prepare("DELETE FROM mod_queue WHERE item_type = 'comment' AND ref_id IN (SELECT comment_id FROM comments WHERE story_id = ?)")->execute([$sid]);
            $pdo->prepare("DELETE FROM mod_queue WHERE item_type = 'story' AND ref_id = ?")->execute([$sid]);
            try { $pdo->prepare("DELETE FROM comment_reports WHERE comment_id IN (SELECT comment_id FROM comments WHERE story_id = ?)")->execute([$sid]); } catch (Throwable $e) {}
            $pdo->prepare("DELETE FROM comments WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM votes WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM thread_aliases WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM promo_log WHERE story_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM stories WHERE story_id = ?")->execute([$sid]);
        }
        $log[] = count($hilos) . " envíos borrados (con $ncom_ajenos comentarios de otros que había en ellos)";

        // --- 2. sus comentarios en hilos ajenos + las respuestas que cuelgan de ellos
        $c = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ?"); $c->execute([$uid]);
        $ids = array_map('intval', $c->fetchAll(PDO::FETCH_COLUMN));
        $todos = $ids; $frontera = $ids;
        while ($frontera) {                       // descendientes por parent_id, nivel a nivel
            $in = implode(',', $frontera);
            $hijos = array_map('intval', $pdo->query("SELECT comment_id FROM comments WHERE parent_id IN ($in)")->fetchAll(PDO::FETCH_COLUMN));
            $hijos = array_values(array_diff($hijos, $todos));
            $todos = array_merge($todos, $hijos); $frontera = $hijos;
        }
        if ($todos) {
            $in = implode(',', $todos);
            // contadores de comentarios de cada hilo afectado (solo los que estaban en 'ok' cuentan)
            foreach ($pdo->query("SELECT story_id, COUNT(*) n FROM comments WHERE comment_id IN ($in) AND status = 'ok' GROUP BY story_id") as $r)
                $pdo->prepare("UPDATE stories SET comments = GREATEST(0, comments - ?) WHERE story_id = ?")->execute([(int)$r['n'], (int)$r['story_id']]);
            $pdo->exec("DELETE FROM mod_queue WHERE item_type = 'comment' AND ref_id IN ($in)");
            try { $pdo->exec("DELETE FROM comment_reports WHERE comment_id IN ($in)"); } catch (Throwable $e) {}
            $pdo->exec("DELETE FROM comments WHERE comment_id IN ($in)");
        }
        $log[] = count($ids) . " comentarios suyos borrados (+" . (count($todos) - count($ids)) . " respuestas de otros que colgaban de ellos)";

        // --- 3. votos: se retiran y se corrigen los contadores
        $v = $pdo->prepare("SELECT story_id, value FROM votes WHERE user_id = ?"); $v->execute([$uid]);
        $nv = 0;
        foreach ($v->fetchAll() as $r) {
            $col = (int)$r['value'] > 0 ? 'votes_pos' : 'votes_neg';
            $pdo->prepare("UPDATE stories SET $col = GREATEST(0, $col - 1) WHERE story_id = ?")->execute([(int)$r['story_id']]);
            $nv++;
        }
        $pdo->prepare("DELETE FROM votes WHERE user_id = ?")->execute([$uid]);
        $log[] = "$nv votos retirados";

        // --- 4. resto de rastro y la cuenta
        $pdo->prepare("DELETE FROM thread_aliases WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM aura_log WHERE user_id = ?")->execute([$uid]);
        try { $pdo->prepare("DELETE FROM messages WHERE from_id = ? OR to_id = ?")->execute([$uid, $uid]); } catch (Throwable $e) {}
        try { $pdo->prepare("DELETE FROM comment_reports WHERE user_id = ?")->execute([$uid]); } catch (Throwable $e) {}
        $pdo->prepare("UPDATE banned_domains SET added_by = NULL WHERE added_by = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ["ERROR: no se ha borrado nada (" . $e->getMessage() . ")"];
    }
    $nf = 0;
    foreach ($ficheros as $f) if (is_file($f) && @unlink($f)) $nf++;
    $log[] = "cuenta «{$u['login']}» (#$uid) eliminada · $nf ficheros de media borrados";
    return $log;
}

$TABS = ['usuarios' => 'Usuarios', 'ciclo' => 'Ciclo', 'config' => 'Config', 'temas' => 'Temas', 'foros' => 'Foros', 'fuentes' => 'Fuentes', 'lateral' => 'Lateral', 'logo' => 'Logo', 'privacidad' => 'Páginas', 'analitica' => 'Analítica', 'agentes' => 'Agentes', 'avisos' => 'Avisos', 'registro' => 'Registro'];
$tab = (string)($_GET['t'] ?? $_POST['t'] ?? 'usuarios');
if (!isset($TABS[$tab])) $tab = 'usuarios';
$T = "<input type=\"hidden\" name=\"t\" value=\"$tab\">";   // cada formulario vuelve a su pestaña

page_header('Admin', '', $tab === 'analitica' ? '<meta http-equiv="refresh" content="60">' : '');
echo "<div class=\"admin\">";
echo "<nav class=\"tabs\">";
foreach ($TABS as $k => $v) echo "<a href=\"/admin?t=$k\"" . ($k === $tab ? ' class="on"' : '') . ">$v</a>";
echo "</nav>";
if ($out) echo "<pre>" . h(implode("\n", $out)) . "</pre>";
if (!empty($pedir_confirmacion)) {
    $pc = $pedir_confirmacion;
    $ns = $pdo->prepare("SELECT (SELECT COUNT(*) FROM stories WHERE user_id = ?) s, (SELECT COUNT(*) FROM comments WHERE user_id = ?) c, (SELECT COUNT(*) FROM votes WHERE user_id = ?) v");
    $ns->execute([$pc['user_id'], $pc['user_id'], $pc['user_id']]); $ns = $ns->fetch();
    echo "<div class=\"error\" style=\"padding:12px 16px;margin-bottom:16px\"><strong>Borrar la cuenta «" . h($pc['login']) . "» (#{$pc['user_id']})</strong><br>"
       . "Se eliminarán de verdad sus {$ns['s']} envíos (con todos los comentarios y votos de cualquiera que haya en esos hilos), sus {$ns['c']} comentarios (con las respuestas de otros que cuelguen de ellos), sus {$ns['v']} votos, alias, aura y avatar. No hay vuelta atrás.<br>"
       . "<form method=\"post\" style=\"display:flex;gap:6px;margin-top:8px;align-items:center;flex-wrap:wrap\">$T<input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"uid\" value=\"{$pc['user_id']}\">"
       . "Escribe el nombre de usuario para confirmar: <input type=\"text\" name=\"confirmar\" autocomplete=\"off\" style=\"width:160px\"> <button class=\"btn\">Borrar definitivamente</button> <a class=\"btn\" href=\"/admin\">cancelar</a></form></div>";
}

if ($tab === 'usuarios') {
    $q = trim((string)($_GET['q'] ?? ''));
    $up = max(1, (int)($_GET['up'] ?? 1)); $per = 50;
    $w = $q !== '' ? "WHERE login LIKE ? OR email LIKE ?" : "";
    $args = $q !== '' ? ["%$q%", "%$q%"] : [];
    $c = $pdo->prepare("SELECT COUNT(*) FROM users $w"); $c->execute($args); $nU = (int)$c->fetchColumn();
    $l = $pdo->prepare("SELECT user_id, login, email, level, aura, created, last_seen, verified FROM users $w ORDER BY created DESC, user_id DESC LIMIT $per OFFSET " . (($up - 1) * $per));
    $l->execute($args);
    echo "<h3 id=\"usuarios\">Usuarios ($nU)</h3>";
    require_once __DIR__ . '/../lib/usuarios.php';
    echo user_create_form('admin');   // «+ Crear un usuario» (lo guarda /crear-usuario)
    echo "<form method=\"get\" action=\"/admin\" style=\"display:flex;gap:6px;margin-bottom:8px\"><input type=\"hidden\" name=\"t\" value=\"usuarios\"><input type=\"text\" name=\"q\" value=\"" . h($q) . "\" placeholder=\"usuario o correo\" style=\"flex:1\"><button class=\"btn\">Buscar</button>" . ($q !== '' ? " <a class=\"btn\" href=\"/admin\">todos</a>" : "") . "</form>";
    $rows = $l->fetchAll();
    echo "<form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"mods\"><input type=\"hidden\" name=\"ids\" value=\"" . implode(',', array_column($rows, 'user_id')) . "\">";
    $modaura = (float)cfg('mod_aura', 20);
    echo "<p class=\"meta\">Nivel: <b>admin</b> lo puede todo; <b>moderador</b> ve y resuelve la cola de moderación. " . ($modaura > 0 ? "Además modera, sin cambiar de nivel, cualquier cuenta con aura ≥ " . h(num_es($modaura)) . " (<code>mod_aura</code>; marcadas «mod por aura»)." : "") . " El nivel propio lo cambia otro admin.</p>";
    echo "<table class=\"users\"><tr><th>#</th><th>usuario</th><th>aura</th><th>nivel</th><th></th></tr>";
    foreach ($rows as $r) {
        if ((int)$r['user_id'] === (int)user()['user_id']) $cell = 'admin <small>(tú)</small>';
        else {
            $cell = "<select name=\"lvl[{$r['user_id']}]\">";
            foreach (['normal' => 'normal', 'mod' => 'moderador', 'admin' => 'admin'] as $v => $lbl) $cell .= "<option value=\"$v\"" . ($r['level'] === $v ? ' selected' : '') . ">$lbl</option>";
            $cell .= "</select>" . (mod_origen($r) === 'aura' ? "<br><small>mod por aura</small>" : "");
        }
        // los botones usan formularios propios (atributo form=) para no anidarlos en el de moderadores
        $acc = "<a class=\"btn sm\" href=\"/editar-usuario?id={$r['user_id']}\" title=\"editar nick, frase, foto y correo\">editar</a> ";
        $acc .= (int)$r['verified'] === 0 ? "<button class=\"btn sm\" form=\"ver{$r['user_id']}\" title=\"marcar el correo como validado\">validar</button> " : "";
        if ($r['level'] !== 'admin') $acc .= "<button class=\"btn sm\" form=\"del{$r['user_id']}\" title=\"borrar la cuenta y todo lo suyo\">borrar</button>";
        $meta = h($r['email']) . ((int)$r['verified'] === 0 ? " (sin activar)" : "") . " · registro " . h(substr($r['created'], 0, 16)) . ($r['last_seen'] ? " · último acceso " . h(hace($r['last_seen'])) : "");
        echo "<tr><td class=\"num\">{$r['user_id']}</td><td class=\"who\"><a href=\"/u/" . rawurlencode($r['login']) . "\">" . h($r['login']) . "</a><small>$meta</small></td><td>" . h($r['aura']) . "</td><td style=\"text-align:center\">$cell</td><td class=\"acc\">$acc</td></tr>";
    }
    echo "</table><p><button class=\"btn\">Guardar niveles</button></p></form>";
    foreach ($rows as $r) {
        echo "<form id=\"ver{$r['user_id']}\" method=\"post\" action=\"/admin\">$T<input type=\"hidden\" name=\"act\" value=\"verificar\"><input type=\"hidden\" name=\"uid\" value=\"{$r['user_id']}\"></form>";
        echo "<form id=\"del{$r['user_id']}\" method=\"post\" action=\"/admin\">$T<input type=\"hidden\" name=\"act\" value=\"borrar\"><input type=\"hidden\" name=\"uid\" value=\"{$r['user_id']}\"></form>";
    }
    $pages = max(1, (int)ceil($nU / $per));
    if ($pages > 1) {
        $qs = $q !== '' ? "&q=" . rawurlencode($q) : "";
        echo "<p>";
        if ($up > 1) echo "<a href=\"/admin?t=usuarios&up=" . ($up - 1) . "$qs\">← anterior</a> ";
        echo "$up / $pages";
        if ($up < $pages) echo " <a href=\"/admin?t=usuarios&up=" . ($up + 1) . "$qs\">siguiente →</a>";
        echo "</p>";
    }
    // bajas voluntarias (migra-127): quien se ha ido, cuando, que tenia y por que
    try {
        $bj = $pdo->query("SELECT * FROM bajas ORDER BY created DESC LIMIT 200")->fetchAll();
        echo "<details class=\"editbox\"><summary><b>Bajas</b> · " . count($bj) . " · usuarios que han borrado su cuenta desde el perfil, con el motivo que dieron</summary>";
        if (!$bj) echo "<p class=\"end\">Ninguna todavía.</p>";
        else {
            echo "<table><tr><th>cuándo</th><th>nick</th><th>llevaba</th><th>aura</th><th class=\"num\">envíos</th><th class=\"num\">coment.</th><th>borró lo escrito</th><th>motivo</th></tr>";
            foreach ($bj as $b) {
                $dias = max(0, (int)floor((strtotime($b['created']) - strtotime($b['registrado'])) / 86400));
                echo "<tr><td>" . h(date('d/m/Y H:i', strtotime($b['created']))) . "</td><td>" . h($b['login']) . "</td><td>$dias d</td><td>" . h(num_es($b['aura'])) . "</td><td class=\"num\">{$b['envios']}</td><td class=\"num\">{$b['comentarios']}</td><td>" . ((int)$b['todo'] ? 'sí' : 'no') . "</td><td>" . h(DDD_BAJA_MOTIVOS[$b['motivo']] ?? $b['motivo']) . ($b['motivo_txt'] !== null && $b['motivo_txt'] !== '' ? ": <i>" . h($b['motivo_txt']) . "</i>" : '') . "</td></tr>";
            }
            echo "</table>";
        }
        echo "</details>";
    } catch (Throwable $e) { /* migra-127 sin aplicar */ }

} elseif ($tab === 'ciclo') {
    $counts = $pdo->query("SELECT status, COUNT(*) n FROM stories GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "<h3>Estado</h3><p>"; foreach ($counts as $k => $n) echo h($k) . ": $n &nbsp; "; echo "</p>";
    echo "<p><form method=\"post\" style=\"display:inline\">$T<input type=\"hidden\" name=\"act\" value=\"ciclo\"><button class=\"btn\">Ejecutar ciclo ahora</button></form> ";
    echo "<form method=\"post\" style=\"display:inline\">$T<input type=\"hidden\" name=\"act\" value=\"reloj\">Adelantar reloj <input type=\"text\" name=\"min\" value=\"30\" size=\"5\" style=\"width:60px\"> min <button class=\"btn\">Ir</button></form></p>";
    echo "<h3>Tiempos</h3><form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"config\"><p>";
    echo "Un ciclo cada <input type=\"text\" name=\"cfg[cycle_minutes]\" value=\"" . h(cfg('cycle_minutes', 5)) . "\" style=\"width:60px\"> min &nbsp;·&nbsp; ";
    echo "un envío puede llegar a portada a los <input type=\"text\" name=\"cfg[delta_min_age_min]\" value=\"" . h(cfg('delta_min_age_min', 30)) . "\" style=\"width:60px\"> min &nbsp;·&nbsp; ";
    echo "vida en portada <input type=\"text\" name=\"cfg[thread_ttl_hours]\" value=\"" . h(cfg('thread_ttl_hours', 48)) . "\" style=\"width:60px\"> h</p><p>";
    echo "δ mínimo para portada: Medios <input type=\"text\" name=\"cfg[delta_min_publish_medios]\" value=\"" . h(cfg('delta_min_publish_medios', 1)) . "\" style=\"width:60px\"> · Substacks <input type=\"text\" name=\"cfg[delta_min_publish_substacks]\" value=\"" . h(cfg('delta_min_publish_substacks', 1)) . "\" style=\"width:60px\"> · Canales <input type=\"text\" name=\"cfg[delta_min_publish_canales]\" value=\"" . h(cfg('delta_min_publish_canales', 1)) . "\" style=\"width:60px\"> &nbsp;·&nbsp; ";
    echo "decaimiento por media hora: Medios <input type=\"text\" name=\"cfg[decay_medios]\" value=\"" . h(cfg('decay_medios', 0.05)) . "\" style=\"width:60px\"> · Substacks <input type=\"text\" name=\"cfg[decay_substacks]\" value=\"" . h(cfg('decay_substacks', 0.02)) . "\" style=\"width:60px\"> · Canales <input type=\"text\" name=\"cfg[decay_canales]\" value=\"" . h(cfg('decay_canales', 0.02)) . "\" style=\"width:60px\"></p><p>";
    echo "portada Canales (YouTube) <select name=\"cfg[portada_canales]\"><option value=\"1\"" . (cfg('portada_canales', '1') === '1' ? ' selected' : '') . ">sí</option><option value=\"0\"" . (cfg('portada_canales', '1') !== '1' ? ' selected' : '') . ">no</option></select> &nbsp;·&nbsp; publica como mucho cada <input type=\"text\" name=\"cfg[canales_gap_min]\" value=\"" . h(cfg('canales_gap_min', 15)) . "\" style=\"width:60px\"> min, y solo ocupa un ciclo cuando tiene un vídeo publicable (turno condicionado)</p><p>";
    echo "caducan sin llegar a portada a las <input type=\"text\" name=\"cfg[nuevas_ttl_hours]\" value=\"" . h(cfg('nuevas_ttl_hours', 168)) . "\" style=\"width:60px\"> h (0 = nunca; 168 = una semana) &nbsp;·&nbsp; ";
    echo "candidato con al menos <input type=\"text\" name=\"cfg[delta_min_votes]\" value=\"" . h(cfg('delta_min_votes', 2)) . "\" style=\"width:60px\"> votos netos que cuenten &nbsp;·&nbsp; ";
    echo "los votos de una cuenta cuentan en el δ a las <input type=\"text\" name=\"cfg[vote_quarantine_hours]\" value=\"" . h(cfg('vote_quarantine_hours', 24)) . "\" style=\"width:60px\"> h de crearse</p><p>";
    foreach (['cycle_lottery' => 'sorteo ponderado por δ (0 = gana siempre el mayor δ)', 'cycle_cede_turno' => 'si a una portada no le llega nada, el turno pasa a la otra', 'cycle_jitter' => 'minuto de ejecución imprevisible dentro de cada ventana (0 = siempre el minuto 0)'] as $k => $lbl) {
        echo "<label style=\"margin-right:14px\"><select name=\"cfg[$k]\"><option value=\"1\"" . (cfg($k, '1') === '1' ? ' selected' : '') . ">sí</option><option value=\"0\"" . (cfg($k, '1') !== '1' ? ' selected' : '') . ">no</option></select> $lbl</label>";
    }
    echo "</p><h3>Modo test</h3><p><label><select name=\"cfg[test_mode]\"><option value=\"1\"" . (cfg('test_mode', '0') === '1' ? ' selected' : '') . ">sí</option><option value=\"0\"" . (cfg('test_mode', '0') !== '1' ? ' selected' : '') . ">no</option></select> modo test: si el ciclo no publica nada y la última promoción tiene más de " . TEST_MODE_MINUTES . " min, sale igualmente el enlace del radar con más δ (primero la portada del turno), sin edad, votos ni δ mínimos</label></p>";
    echo "<p><button class=\"btn\">Guardar</button></p>";
    echo "<p><small>El cron llama al script cada minuto; cada ventana del intervalo tiene un minuto de ejecución que depende del secreto del sitio (fuera nadie lo sabe: contra el <i>sniping</i>, la web dice «antes de las hh:mm»). Los ciclos se turnan entre Medios y Substacks (el de ahora le toca a <b>" . h(DDD_PORTADAS[portada_turno(ciclo_ventana(time()))]) . "</b>, si se ejecuta a mano) y cada uno publica en su portada <b>uno</b> del radar de esa portada, elegido a suertes con probabilidad proporcional al δ entre los que llevan los minutos indicados, tienen los votos netos que cuentan indicados (la puerta es en votos, no en δ: con la raíz un solo voto ya daba δ = 1) y llegan al δ mínimo de su portada; si no hay ninguno y está activada la cesión, publica en la otra. Los originales no van a portada. Lo que pasa las horas indicadas sin llegar a portada se archiva. Fórmula: δ = signo(n)·√|n| + bonus del tema (si n &gt; 0) − decaimiento × max(0, medias horas − medias horas mínimas), con n = votos netos que cuentan (uno por conexión y envío, y solo de cuentas con más de las horas de cuarentena). El cambio surte efecto en el siguiente minuto, sin tocar el servidor.</small></p></form>";
    $nq = (int)$pdo->query("SELECT COUNT(*) FROM mod_queue WHERE status IN ('needs_human','queued','processing')")->fetchColumn();
    echo "<h3>Cola de moderación</h3><p>$nq pendientes de la IA · " . open_reports() . " comentarios reportados · <a href=\"/moderacion\">ir a moderación</a> (la ven también los moderadores)</p>";
    echo "<h3>Últimas promociones</h3><table>";
    foreach ($pdo->query("SELECT p.*, s.title FROM promo_log p JOIN stories s ON s.story_id = p.story_id ORDER BY p.id DESC LIMIT 15") as $p) {
        echo "<tr><td>" . h($p['created']) . "</td><td>" . h((string)($p['portada'] ?? '')) . "</td><td>" . h(mb_strimwidth($p['title'], 0, 60, '…')) . "</td><td>δ " . h($p['delta']) . "</td><td>{$p['candidates']} en su radar · {$p['commenters']} hablando</td></tr>";
    }
    echo "</table>";

} elseif ($tab === 'config') {
    echo "<h3>Config</h3><form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"config\">";
    // grupos de fuentes que se pueden apagar enteros: desactivado = fuera del menu, la franja de iconos, el catalogo, el perfil y el pie
    echo "<h4>Grupos de fuentes</h4><table>";
    foreach (DDD_CFG_GRUPOS as $k => [$label, $descr, $def]) {
        $on = (string)cfg($k, $def) === '1';
        echo "<tr><td><b>" . h($label) . "</b> <code>$k</code><br><small>" . h($descr) . "</small></td><td style=\"width:45%\"><select name=\"cfg[$k]\"><option value=\"1\"" . ($on ? ' selected' : '') . ">activado</option><option value=\"0\"" . ($on ? '' : ' selected') . ">desactivado</option></select></td></tr>";
    }
    echo "</table><h4>Resto</h4><table>";
    foreach ($pdo->query("SELECT * FROM config ORDER BY ckey") as $c) {
        if (isset(DDD_CFG_GRUPOS[$c['ckey']])) continue;   // van arriba, como interruptores
        if (str_starts_with($c['ckey'], 'logo_')) continue;   // se gestiona en la pestaña Logo (logo_light, logo_dark, logo_icon)
        if ($c['ckey'] === 'processes_votes_who') {   // quien ve la pestaña Votos de /procesos
            echo "<tr><td><code>processes_votes_who</code><br><small>" . h($c['descr']) . "</small></td><td style=\"width:45%\"><select name=\"cfg[processes_votes_who]\">";
            foreach (DDD_PROCESSES_VOTES_WHO as $k => $lbl) echo "<option value=\"$k\"" . ($c['cvalue'] === $k ? ' selected' : '') . ">" . h($lbl) . "</option>";
            echo "</select></td></tr>";
            continue;
        }
        if ($c['ckey'] === 'skin_default') {   // la skin por defecto se elige entre todas las que hay (DDD_SKINS)
            echo "<tr><td><code>skin_default</code><br><small>Skin por defecto (anónimos y quien no elija): cualquiera de las " . count(DDD_SKINS) . "</small></td><td style=\"width:45%\"><select name=\"cfg[skin_default]\">";
            foreach (DDD_SKINS as $k => $v) echo "<option value=\"$k\"" . ($c['cvalue'] === $k ? ' selected' : '') . ">" . h($v) . " ($k)</option>";
            echo "</select></td></tr>";
            continue;
        }
        echo "<tr><td><code>" . h($c['ckey']) . "</code><br><small>" . h($c['descr']) . "</small></td><td style=\"width:45%\"><input type=\"text\" name=\"cfg[" . h($c['ckey']) . "]\" value=\"" . h($c['cvalue']) . "\"></td></tr>";
    }
    echo "</table><p><button class=\"btn\">Guardar config</button></p></form>";

} elseif ($tab === 'agentes') {
    // agentes comentaristas (lib/agente.php): quien es agente, si esta activo, cuanto lleva hoy; ?a=uid abre su registro (agent_seen)
    require_once __DIR__ . '/../lib/agente.php';
    echo "<h3>Agentes comentaristas</h3>";
    if (!agents_on()) echo "<p class=\"meta\">Falta la migración 089 (tabla <code>agents</code>).</p>";
    else {
        $ags = $pdo->query("SELECT a.*, u.login, u.verified FROM agents a JOIN users u ON u.user_id = a.user_id ORDER BY a.enabled DESC, u.login")->fetchAll();
        echo "<p class=\"meta\">Una cuenta se convierte en agente desde su ficha (<b>editar</b> en Usuarios o «editar perfil» en su perfil público): allí van la personalidad y los parámetros, y la casilla <b>activo</b>. El worker del PC de la redacción (<code>local/comentarista/</code>) sirve a todos los activos; última conexión: <b>" . h((string)cfg('agent_worker_seen', '') ?: 'nunca') . "</b>.</p>";
        if (!$ags) echo "<p class=\"end\">No hay ninguna cuenta marcada como agente.</p>";
        else {
            echo "<table class=\"users\"><tr><th>agente</th><th>estado</th><th>hoy</th><th>vistos</th><th>parámetros</th><th></th></tr>";
            foreach ($ags as $a) {
                $st = $pdo->prepare("SELECT result, COUNT(*) n FROM agent_seen WHERE user_id = ? GROUP BY result"); $st->execute([$a['user_id']]);
                $c = array_column($st->fetchAll(), 'n', 'result');
                $params = (int)$a['per_day'] . " com./día" . ((int)($a['submits_per_day'] ?? 0) > 0 ? " · " . (int)$a['submits_per_day'] . " envíos/día (" . h($a['submit_kinds'] ?? 'ambos') . ($a['submit_topics'] !== '' ? ", temas " . h($a['submit_topics']) : "") . (($a['submit_langs'] ?? '') !== '' ? ", idiomas " . h($a['submit_langs']) : "") . ((int)($a['submit_review'] ?? 0) ? ", <b>evalúa la IA</b>" : ", al azar") . ")" : "") . " · " . (int)$a['min_age_min'] . " min–" . (int)$a['max_age_h'] . " h · ≤ " . (int)$a['max_comments'] . " com." . ((int)$a['replies'] ? " · responde" : "") . (isset($a['hour_from']) ? " · " . (int)$a['hour_from'] . "-" . (int)$a['hour_to'] . " h · pausa " . (int)$a['gap_min'] . " min · recuerda " . (int)$a['memory_n'] . (agent_awake($a) ? "" : " · <b>ahora no</b>") : "") . ($a['topics'] !== '' ? " · temas " . h($a['topics']) : "") . ($a['skip_users'] !== '' ? " · evita " . h($a['skip_users']) : "");
                echo "<tr><td class=\"who\"><a href=\"/u/" . rawurlencode($a['login']) . "\">" . h($a['login']) . "</a><small>" . (trim((string)$a['persona']) === '' ? 'sin personalidad (usa personalidad.md)' : mb_strimwidth(preg_replace('/\s+/', ' ', (string)$a['persona']), 0, 90, '…')) . "</small></td>"
                   . "<td>" . ((int)$a['enabled'] ? '<b>activo</b>' : 'apagado') . ((int)$a['verified'] ? '' : ' (cuenta sin validar)') . "</td><td class=\"num\">" . agent_today($pdo, (int)$a['user_id']) . "</td>"
                   . "<td class=\"num\">" . (int)($c['comment'] ?? 0) . " com. · " . (int)($c['skip'] ?? 0) . " pasa · " . (int)($c['fail'] ?? 0) . " fallo</td><td><small>$params</small></td>"
                   . "<td class=\"acc\"><a class=\"btn sm\" href=\"/editar-usuario?id={$a['user_id']}\">ficha</a> <a class=\"btn sm\" href=\"/admin?t=agentes&amp;a={$a['user_id']}\">registro</a></td></tr>";
            }
            echo "</table>";
        }
        if (($aid = (int)($_GET['a'] ?? 0)) > 0) {
            $st = $pdo->prepare("SELECT v.*, s.title FROM agent_seen v JOIN stories s ON s.story_id = v.story_id WHERE v.user_id = ? ORDER BY v.created DESC LIMIT 100"); $st->execute([$aid]);
            echo "<h3>Registro</h3><table class=\"users\"><tr><th>cuándo</th><th>hilo</th><th>resultado</th><th>nota del modelo</th></tr>";
            foreach ($st->fetchAll() as $r) echo "<tr><td><small>" . h(hace($r['created'])) . "</small></td><td class=\"who\"><a href=\"/hilo/{$r['story_id']}" . ($r['comment_id'] ? "#c{$r['comment_id']}" : "") . "\">" . h(mb_strimwidth((string)$r['title'], 0, 70, '…')) . "</a></td><td>" . h($r['result']) . "</td><td><small>" . h((string)$r['note']) . ($r['model'] ? " · " . h($r['model']) : "") . "</small></td></tr>";
            echo "</table>";
            if (agent_review_on()) {   // migra-117: lo que la IA local ha puntuado para sus envios
                $st = $pdo->prepare("SELECT z.*, x.story_id FROM agent_item_seen z LEFT JOIN agent_sent x ON x.item_id = z.item_id AND x.user_id = z.user_id WHERE z.user_id = ? ORDER BY z.created DESC, z.score DESC LIMIT 150"); $st->execute([$aid]);
                $rows = $st->fetchAll();
                echo "<h3>Envíos evaluados por la IA</h3>";
                if (!$rows) echo "<p class=\"end\">Todavía no ha evaluado ninguna noticia para este agente.</p>";
                else {
                    echo "<p class=\"meta\">Nota mínima para enviar: <b>" . agent_review_min() . "</b> (Config → <code>agent_review_min</code>). Las suspendidas no se le vuelven a ofrecer.</p>";
                    echo "<table class=\"users\"><tr><th>cuándo</th><th>noticia</th><th>nota</th><th>motivo del modelo</th><th></th></tr>";
                    foreach ($rows as $r) echo "<tr><td><small>" . h(hace($r['created'])) . "</small></td><td class=\"who\">" . h(mb_strimwidth((string)$r['title'], 0, 80, '…')) . "<small>" . h((string)$r['source']) . "</small></td><td class=\"num\">" . ((int)$r['score'] >= agent_review_min() ? '<b>' . (int)$r['score'] . '</b>' : (int)$r['score']) . "</td><td><small>" . h((string)$r['note']) . "</small></td><td>" . ((int)$r['sent'] ? ($r['story_id'] ? "<a href=\"/hilo/" . (int)$r['story_id'] . "\">enviada</a>" : 'enviada') : '') . "</td></tr>";
                    echo "</table>";
                }
            }
        }
    }
} elseif ($tab === 'analitica') {
    require __DIR__ . '/../lib/analitica-admin.php';
    ana_admin($pdo);

} elseif ($tab === 'avisos') {   // aviso a la comunidad (13/10/2026)
    $atxt = (string)cfg('aviso_txt', ''); $aon = (string)cfg('aviso_on', '0') === '1';
    try { $r = $pdo->query("SELECT ckey, cvalue FROM config WHERE ckey IN ('aviso_txt','aviso_on')")->fetchAll(PDO::FETCH_KEY_PAIR); $atxt = (string)($r['aviso_txt'] ?? $atxt); $aon = ($r['aviso_on'] ?? ($aon ? '1' : '0')) === '1'; } catch (Throwable $e) {}
    echo "<h3>Avisos a la comunidad</h3><p><small>Un texto de hasta <b>150 palabras</b> que, mientras la casilla esté marcada, <b>sustituye a todos los avisos grises de cabecera</b> (los que explican cada sección: portadas, radar, fuentes, comunidades, estadísticas…) en todas las páginas públicas; en las que no tienen aviso gris, sale arriba del todo. Texto plano: los saltos de línea se respetan y las direcciones http(s) salen como enlaces. Se enseña tal cual en todos los idiomas. Al desmarcar la casilla, vuelven las explicaciones normales.</small></p>";
    echo "<form method=\"post\" class=\"plain\">$T<input type=\"hidden\" name=\"act\" value=\"aviso\">";
    echo "<p><textarea name=\"aviso_txt\" rows=\"6\" maxlength=\"2000\" style=\"width:100%;max-width:720px\" placeholder=\"Escribe aquí el aviso…\">" . h($atxt) . "</textarea></p>";
    echo "<p><label><input type=\"checkbox\" name=\"aviso_on\" value=\"1\"" . ($aon ? ' checked' : '') . "> <b>Activado</b>: sustituir los avisos de cabecera por este texto</label></p>";
    echo "<p><button class=\"btn\">Guardar</button>" . ($aon && $atxt !== '' ? " <small>Ahora mismo <b>activo</b>: " . count(preg_split('/\\s+/u', trim($atxt), -1, PREG_SPLIT_NO_EMPTY)) . " palabras.</small>" : " <small>Ahora mismo desactivado.</small>") . "</p></form>";
    if ($atxt !== '') echo "<h4>Vista previa</h4><p class=\"notice aviso\">" . preg_replace('~(https?://[^\\s<]+)~u', '<a href="$1" rel="noopener">$1</a>', saltos(h($atxt))) . "</p>";

} elseif ($tab === 'registro') {   // registro de acciones de moderadores y admin (mod_log, migra-134)
    $rq = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 64)); $ru = trim(mb_substr((string)($_GET['u'] ?? ''), 0, 32));
    $rp = max(1, (int)($_GET['p'] ?? 1)); $rper = 100;
    echo "<h3>Registro de moderación y administración</h3><p><small>Cada acción hecha desde /moderacion, /admin, la ficha de usuario, el chat (mensajes a la comunidad), destacados del admin y las ediciones de envíos, comentarios, mensajes y comunidades ajenos hechas por un moderador. Se apunta quién, qué (página:acción), los campos enviados (sin contraseñas) y el resultado que vio en pantalla. Las lecturas no se registran; los destacados de los usuarios están en su aura.</small></p>";
    try {
        $quien = $pdo->query("SELECT DISTINCT u.login FROM mod_log l JOIN users u ON u.user_id = l.user_id ORDER BY u.login")->fetchAll(PDO::FETCH_COLUMN);
        echo "<form method=\"get\" action=\"/admin\" class=\"buscar-u\"><input type=\"hidden\" name=\"t\" value=\"registro\"><select name=\"u\"><option value=\"\">— todos —</option>";
        foreach ($quien as $ql) echo "<option value=\"" . h($ql) . "\"" . ($ru === $ql ? ' selected' : '') . ">" . h($ql) . "</option>";
        echo "</select><input type=\"search\" name=\"q\" value=\"" . h($rq) . "\" maxlength=\"64\" placeholder=\"acción, campo o resultado (p. ej. fuente_editar, #123, ghost)\"><button class=\"btn sm\">Buscar</button>" . ($rq !== '' || $ru !== '' ? " <a href=\"/admin?t=registro\">todo</a>" : '') . "</form>";
        $w = []; $args = [];
        if ($ru !== '') { $w[] = "u.login = ?"; $args[] = $ru; }
        if ($rq !== '') { $w[] = "(l.action LIKE ? OR l.detail LIKE ? OR l.result LIKE ?)"; $like = '%' . $rq . '%'; array_push($args, $like, $like, $like); }
        $ws = $w ? ' WHERE ' . implode(' AND ', $w) : '';
        $cn = $pdo->prepare("SELECT COUNT(*) FROM mod_log l JOIN users u ON u.user_id = l.user_id$ws"); $cn->execute($args); $rtotal = (int)$cn->fetchColumn();
        $st = $pdo->prepare("SELECT l.*, u.login FROM mod_log l JOIN users u ON u.user_id = l.user_id$ws ORDER BY l.log_id DESC LIMIT $rper OFFSET " . (($rp - 1) * $rper)); $st->execute($args); $rl = $st->fetchAll();
        echo "<p><small>$rtotal " . ($rtotal === 1 ? 'acción' : 'acciones') . ($rq !== '' || $ru !== '' ? ' que coinciden' : '') . ".</small></p>";
        if (!$rl) echo "<p class=\"end\">Nada registrado todavía.</p>";
        else {
            echo "<table class=\"modlog\"><tr><th>cuándo</th><th>quién</th><th>acción</th><th>detalle</th><th>resultado</th></tr>";
            foreach ($rl as $r) echo "<tr><td style=\"white-space:nowrap\" title=\"" . h($r['created']) . "\">" . h(hace($r['created'])) . "</td><td><a href=\"/u/" . rawurlencode($r['login']) . "\">" . h($r['login']) . "</a></td><td><code>" . h($r['action']) . "</code></td><td><small>" . h((string)$r['detail']) . "</small></td><td><small>" . h((string)$r['result']) . "</small></td></tr>";
            echo "</table>";
            if ($rtotal > $rper) {
                $base = "/admin?t=registro" . ($ru !== '' ? '&amp;u=' . rawurlencode($ru) : '') . ($rq !== '' ? '&amp;q=' . rawurlencode($rq) : '');
                echo "<p>" . ($rp > 1 ? "<a class=\"btn\" href=\"$base&amp;p=" . ($rp - 1) . "\">← más recientes</a> " : '') . ($rp * $rper < $rtotal ? "<a class=\"btn\" href=\"$base&amp;p=" . ($rp + 1) . "\">anteriores →</a>" : '') . "</p>";
            }
        }
    } catch (Throwable $e) { echo "<p class=\"error\">Falta la tabla mod_log (migra-134): " . h($e->getMessage()) . "</p>"; }

} elseif ($tab === 'temas') {
    echo "<h3>Temas</h3><form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"topics\"><table><tr><th>tema</th><th>envíos</th><th style=\"width:90px\">bonus δ</th><th style=\"width:70px\">color</th></tr>";
    foreach ($pdo->query("SELECT t.*, (SELECT COUNT(*) FROM stories s WHERE s.topic_id = t.topic_id) n FROM topics t ORDER BY t.name") as $t) {
        echo "<tr><td><input type=\"text\" name=\"name[{$t['topic_id']}]\" value=\"" . h($t['name']) . "\" maxlength=\"32\" style=\"width:12em\"> <a href=\"/tema/{$t['topic_id']}\" title=\"ver el tema\">ver</a></td><td class=\"num\">{$t['n']}</td><td><input type=\"text\" name=\"bonus[{$t['topic_id']}]\" value=\"" . h(num_es($t['bonus'])) . "\"></td>";
        echo "<td><input type=\"color\" name=\"color[{$t['topic_id']}]\" value=\"" . h($t['color']) . "\" title=\"color del velo y la etiqueta\"></td></tr>";
    }
    echo "</table><p><button class=\"btn\">Guardar temas</button></p></form>";
    echo "<h3>Nuevo tema</h3><form method=\"post\" style=\"display:flex;gap:8px;align-items:center;flex-wrap:wrap\">$T<input type=\"hidden\" name=\"act\" value=\"tema_nuevo\">";
    echo "<input type=\"text\" name=\"name\" placeholder=\"nombre (minúsculas)\" maxlength=\"32\" required style=\"flex:1;min-width:140px\"> bonus δ <input type=\"text\" name=\"bonus\" value=\"0\" style=\"width:60px\"> <input type=\"color\" name=\"color\" value=\"#534AB7\"> <button class=\"btn\">Crear tema</button></form>";
    $tl = $pdo->query("SELECT topic_id, name FROM topics ORDER BY name")->fetchAll();
    if (count($tl) > 1) {
        echo "<h3>Borrar un tema</h3><form method=\"post\" style=\"display:flex;gap:8px;align-items:center;flex-wrap:wrap\">$T<input type=\"hidden\" name=\"act\" value=\"tema_borrar\">";
        echo "borrar <select name=\"topic_id\">"; foreach ($tl as $t) echo "<option value=\"{$t['topic_id']}\">" . h($t['name']) . "</option>"; echo "</select>";
        echo " y mover sus envíos a <select name=\"to\">"; foreach ($tl as $t) echo "<option value=\"{$t['topic_id']}\">" . h($t['name']) . "</option>"; echo "</select>";
        echo " <label><input type=\"checkbox\" name=\"seguro\" value=\"1\"> seguro</label> <button class=\"btn danger\">Borrar tema</button></form>";
    }
    echo "<p><small>El bonus se <b>suma</b> al δ de los envíos del tema que tienen apoyo neto (con 0,3, un envío con un voto que cuenta vale 1,3 en vez de 1). Antes era un coeficiente multiplicador, que también amplificaba los votos negativos y el decaimiento; se cambió el 26/09/2026 (migra-080). El color es el velo de la foto y la etiqueta. Al borrar un tema, sus envíos (y los medios que lo proponían, y quien lo tenía como página de entrada) pasan al tema elegido. Los moderadores también renombran y borran temas desde /moderacion.</small></p>";

} elseif ($tab === 'foros') {   // foros (migra-159): lo que era Originales, ahora una pestaña por foro en /foros
    echo "<h3>Foros</h3><p><small>La sección <a href=\"/foros\">Foros</a> enseña una pestaña por foro, por el orden indicado. <b>Activo</b> = lo ve todo el mundo; desmarcado = apagado: solo lo ves tú (en cursiva, con «apagado»), y sus hilos siguen abriéndose por URL. El slug es la URL (<code>/foros?f=slug</code>) y el grupo de destacados; no se cambia. La descripción es el aviso gris de cabecera del foro. <b>Orden de los hilos</b>: por fecha (el hilo más reciente arriba, como Originales) o por actividad (el último mensaje arriba, como un foro clásico). Las destacadas van siempre las primeras.</small></p>";
    try {
        $fl = $pdo->query("SELECT f.*, (SELECT COUNT(*) FROM stories s WHERE s.forum_id = f.forum_id AND s.content_type = 'article' AND s.status IN ('nuevas','destacadas','archived')) n FROM forums f ORDER BY f.position, f.forum_id")->fetchAll();
        echo "<form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"foros\"><table><tr><th>activo</th><th>slug</th><th>nombre</th><th>descripción (aviso de cabecera)</th><th>hilos</th><th style=\"width:60px\">orden</th><th>hilos por</th><th title=\"0 = abierto; N = solo quien tenga más de N de aura lo ve y escribe (admin y mods siempre)\">aura mín.</th></tr>";
        foreach ($fl as $x) {
            $id = (int)$x['forum_id'];
            echo "<tr><td style=\"text-align:center\"><input type=\"checkbox\" name=\"fon[$id]\" value=\"1\"" . ($x['status'] === 'on' ? ' checked' : '') . "></td>";
            echo "<td><code>" . h($x['slug']) . "</code> <a href=\"/foros?f=" . h($x['slug']) . "\" title=\"ver el foro\">ver</a></td>";
            echo "<td><input type=\"text\" name=\"fname[$id]\" value=\"" . h($x['name']) . "\" maxlength=\"48\" style=\"width:11em\"></td>";
            echo "<td><input type=\"text\" name=\"fdescr[$id]\" value=\"" . h($x['descr']) . "\" maxlength=\"300\" style=\"width:100%;min-width:16em\"></td>";
            echo "<td class=\"num\">{$x['n']}</td><td><input type=\"text\" name=\"fpos[$id]\" value=\"{$x['position']}\" style=\"width:3em\"></td>";
            echo "<td><select name=\"forden[$id]\"><option value=\"fecha\"" . ($x['orden'] === 'fecha' ? ' selected' : '') . ">fecha</option><option value=\"actividad\"" . ($x['orden'] === 'actividad' ? ' selected' : '') . ">actividad</option></select></td>";
            echo "<td><input type=\"text\" name=\"faura[$id]\" value=\"" . h(num_es((float)($x['min_aura'] ?? 0))) . "\" style=\"width:4em\"></td></tr>";
        }
        echo "</table><p><button class=\"btn\">Guardar foros</button></p></form>";
        echo "<h3>Nuevo foro</h3><form method=\"post\" style=\"display:flex;gap:8px;align-items:center;flex-wrap:wrap\">$T<input type=\"hidden\" name=\"act\" value=\"foro_nuevo\">";
        echo "<input type=\"text\" name=\"slug\" placeholder=\"slug (url, p. ej. microrrelatos)\" maxlength=\"16\" pattern=\"[a-z0-9-]{2,16}\" required style=\"width:13em\"> <input type=\"text\" name=\"name\" placeholder=\"nombre\" maxlength=\"48\" required style=\"width:11em\"> <input type=\"text\" name=\"descr\" placeholder=\"descripción\" maxlength=\"300\" style=\"flex:1;min-width:16em\"> hilos por <select name=\"orden\"><option value=\"fecha\">fecha</option><option value=\"actividad\" selected>actividad</option></select> <label><input type=\"checkbox\" name=\"on\" value=\"1\"> activo</label> <button class=\"btn\">Crear foro</button></form>";
        echo "<p><small>No hay borrado: un foro que sobre se apaga. <b>Aura mín.</b> (migra-165): 0 = abierto; N = el foro, sus hilos y sus búsquedas solo existen para quien tenga más de N de aura (admin y moderadores, siempre); Hardusers nace con 20. Lo que había en Originales está, hilo a hilo, en el foro «originales» (migra-159). Las normas de cada foro van en su descripción; la de Originales (nada ya publicado en otro sitio) la enseña además el formulario de nuevo hilo.</small></p>";
    } catch (Throwable $e) { echo "<p class=\"error\">Falta la tabla forums (migra-159): " . h($e->getMessage()) . "</p>"; }

} elseif ($tab === 'fuentes') {
    // subpestañas por tipo de fuente: Substack (newsletter), Medios, YouTube, Agregadores; los formularios vuelven a la subpestaña (campo oculto k)
    $FK = ['newsletter' => 'Substack', 'medio' => 'Medios', 'youtube' => 'YouTube', 'agregador' => 'Agregadores'];
    $fk = (string)($_GET['k'] ?? $_POST['k'] ?? 'newsletter');
    if (in_array($_POST['act'] ?? '', ['fuente', 'fuente_nueva'], true) && isset($FK[$_POST['kind'] ?? ''])) $fk = $_POST['kind'];   // al guardar, a la subpestaña del tipo elegido
    if (!isset($FK[$fk])) $fk = 'newsletter';
    // filtro por idioma dentro de la subpestaña: il = codigo (es, en, pt…), '?' = sin idioma, '' = todos; los formularios lo conservan
    $il = (string)($_GET['il'] ?? $_POST['il'] ?? '');
    if ($il !== '?' && !preg_match('/^[a-z]{2,5}$/', $il)) $il = '';
    // buscador (09/10/2026): nombre, slug, descripcion, web o RSS, sin mayusculas ni tildes; busca en TODOS los tipos e idiomas
    $fq = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 64));
    $llano = fn(string $t): string => strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u', 'ã' => 'a', 'õ' => 'o', 'ç' => 'c', 'ä' => 'a', 'ö' => 'o', 'ß' => 'ss']);
    $fqn = $llano($fq);
    $T .= "<input type=\"hidden\" name=\"k\" value=\"$fk\"><input type=\"hidden\" name=\"il\" value=\"" . h($il) . "\">";
    $topics = $pdo->query("SELECT topic_id, name FROM topics ORDER BY name")->fetchAll();
    $fuente_form = function (array $f, string $act) use ($T, $topics): string {
        $h = "<form method=\"post\" class=\"fuente\">$T<input type=\"hidden\" name=\"act\" value=\"$act\"><input type=\"hidden\" name=\"source_id\" value=\"" . (int)($f['source_id'] ?? 0) . "\"><table>";
        $h .= "<tr><td>nombre</td><td><input type=\"text\" name=\"name\" maxlength=\"48\"" . ($act === 'fuente_nueva' ? ' placeholder="vacío = tomarlo de la web"' : ' required') . " value=\"" . h($f['name'] ?? '') . "\"></td></tr>";
        $h .= "<tr><td>slug</td><td><input type=\"text\" name=\"slug\" maxlength=\"24\"" . ($act === 'fuente_nueva' ? '' : ' required') . " pattern=\"[a-z0-9\\-]{2,24}\" value=\"" . h($f['slug'] ?? '') . "\" placeholder=\"minúsculas, números o guion: sale en /fuente/slug\"></td></tr>";
        $h .= "<tr><td>tipo</td><td><select name=\"kind\"><option value=\"medio\"" . (($f['kind'] ?? 'medio') === 'medio' ? ' selected' : '') . ">medio (últimas noticias, elegible en «Mis medios»)</option><option value=\"newsletter\"" . (($f['kind'] ?? '') === 'newsletter' ? ' selected' : '') . ">newsletter / Substack (igual que un medio, en «Mis substacks»)</option><option value=\"youtube\"" . (($f['kind'] ?? '') === 'youtube' ? ' selected' : '') . ">canal de YouTube (igual que un medio, en «Mis canales»)</option><option value=\"agregador\"" . (($f['kind'] ?? '') === 'agregador' ? ' selected' : '') . ">agregador (portada + cola, votos)</option></select></td></tr>";
        $h .= "<tr><td>web</td><td><input type=\"url\" name=\"home\" required value=\"" . h($f['home'] ?? '') . "\" placeholder=\"https://…\"></td></tr>";
        $h .= "<tr><td>RSS</td><td><input type=\"url\" name=\"feed_portada\" value=\"" . h($f['feed_portada'] ?? '') . "\" placeholder=\"https://…/feed/ (canal de YouTube: vacío, se saca de la web)\"></td></tr>";
        $h .= "<tr><td>RSS de la cola</td><td><input type=\"url\" name=\"feed_nuevas\" value=\"" . h($f['feed_nuevas'] ?? '') . "\" placeholder=\"solo agregadores (vacío en un medio)\"></td></tr>";
        $h .= "<tr><td>descripción</td><td><input type=\"text\" name=\"descr\" maxlength=\"200\" value=\"" . h($f['descr'] ?? '') . "\"></td></tr>";
        // idioma de la fuente, para el filtro de idiomas de fuentes y envios del perfil. Vacio = se toma del RSS al guardar (y en cada lectura mientras falte)
        $fl = (string)($f['lang'] ?? '');
        $h .= "<tr><td>idioma</td><td><select name=\"lang\" class=\"auto\"><option value=\"\">— detectar del RSS —</option>";
        foreach (DDD_LANGS + ($fl !== '' && !isset(DDD_LANGS[$fl]) ? [$fl => $fl] : []) as $lc => $ln) $h .= "<option value=\"$lc\"" . ($fl === $lc ? ' selected' : '') . ">" . h($ln) . " ($lc)</option>";
        $h .= "</select></td></tr>";
        $h .= "<tr><td>tema al enviar</td><td><select name=\"topic_id\"><option value=\"0\">— el que elija quien envía —</option>";
        foreach ($topics as $t) $h .= "<option value=\"{$t['topic_id']}\"" . ((int)($f['topic_id'] ?? 0) === (int)$t['topic_id'] ? ' selected' : '') . ">" . h($t['name']) . "</option>";
        $h .= "</select></td></tr>";
        $h .= "<tr><td>plantilla</td><td><select name=\"skin\"><option value=\"\">la de cada cual</option>";
        foreach (DDD_SKINS as $k => $v) $h .= "<option value=\"$k\"" . (($f['skin'] ?? '') === $k ? ' selected' : '') . ">" . h($v) . "</option>";
        $h .= "</select></td></tr>";
        $h .= "<tr><td>leer cada</td><td><input type=\"number\" name=\"interval_min\" min=\"1\" max=\"1440\" value=\"" . (int)($f['interval_min'] ?? 60) . "\"> minutos</td></tr>";
        $h .= "<tr><td>estado</td><td><select name=\"status\" class=\"auto\"><option value=\"ok\">activa</option><option value=\"off\"" . (($f['status'] ?? 'ok') === 'off' ? ' selected' : '') . ">apagada</option></select></td></tr>";
        $h .= "<tr><td>lector local</td><td><label><input type=\"checkbox\" name=\"relay\" value=\"1\"" . (!empty($f['relay']) ? ' checked' : '') . "> la web de esta fuente rechaza al servidor (403/503): el feed y las fotos los trae el <b>lector local</b> del PC de la redacción (<code>local/lector</code>); el servidor no la lee. Lector visto: " . h((string)cfg('relay_worker_seen', '') ?: 'nunca') . "</label></td></tr>";
        $h .= "<tr><td>destacado</td><td><label><input type=\"checkbox\" name=\"featured\" value=\"1\"" . (!empty($f['featured']) ? ' checked' : '') . "> lo ve en «Mis fuentes» (menú y /mis-fuentes) quien no sigue ninguna fuente de ese tipo o no tiene cuenta. Si no hay ninguna destacada del tipo, se enseñan las que han publicado más recientemente</label></td></tr>";
        $h .= "<tr><td></td><td><button class=\"btn\">" . ($act === 'fuente_nueva' ? 'Crear fuente' : 'Guardar') . "</button>" . ($act === 'fuente_nueva' ? " <button class=\"btn sm\" name=\"rellenar\" value=\"1\" formnovalidate title=\"con solo la web: busca el RSS y saca nombre, slug, descripción e idioma; luego revisas y creas\">Rellenar desde la web</button>" : '') . "</td></tr>";
        return $h . "</table></form>";
    };
    echo "<h3>Fuentes externas</h3><p><small>Sitios con RSS «pintados» con Deltha, solo lectura (<a href=\"/fuentes\">catálogo público</a>, «Todas las fuentes»). <b>Medio</b>: sus últimas noticias, que los usuarios siguen con el botón «Seguir», sin límite («Mis medios»; en el menú salen los <code>media_max_per_user</code> más recientes), con botón «enviar a Nuevas» (tema propuesto: el de aquí). <b>Newsletter</b>: igual que un medio, pero se elige en el grupo aparte «Mis substacks» (hasta <code>news_max_per_user</code>). <b>Destacado</b>: los medios que ven en el menú y el pie los anónimos y las cuentas que aún no han elegido (hasta <code>media_max_per_user</code>). <b>Canal de YouTube</b>: igual que un medio, en el grupo «Mis canales»; en «web» va la dirección del canal (<code>https://www.youtube.com/@nombre</code>) y el RSS se deja vacío: se calcula al guardar (solo vídeos largos, sin shorts ni directos, salvo <code>youtube_shorts=1</code>). La miniatura se guarda en /media y el vídeo se ve en YouTube. <code>sources_youtube=0</code> los apaga todos. <b>Agregador</b> (Menéame): portada + cola con fases y votos de la fuente. El cron las lee cada <code>interval_min</code> minutos; las imágenes se guardan en /media (og:image si el RSS no trae). <code>sources_enabled=0</code> en Config apaga todo.</small></p>";
    // «Leer ahora»: todas o solo un tipo (los botones llevan name=kind; el de la subpestaña abierta va primero), 15/10/2026
    $lb = "<button class=\"btn\" name=\"kind\" value=\"\">Leer todas las fuentes ahora</button>";
    foreach ([$fk => $FK[$fk] ?? ''] + $FK as $k => $v) if ($v !== '') $lb .= " <button class=\"btn sm\" name=\"kind\" value=\"$k\">solo " . ($k === 'newsletter' ? 'substacks' : ($k === 'youtube' ? 'canales' : mb_strtolower($v))) . "</button>";
    echo "<form method=\"post\" class=\"fuentes-top\">$T<input type=\"hidden\" name=\"act\" value=\"fuente_leer_todas\"><input type=\"hidden\" name=\"k\" value=\"" . h($fk) . "\">$lb<small>sin esperar al intervalo; como mucho 50 fuentes y ~25 s por pulsación, las más atrasadas primero; el resto lo sigue el cron</small></form>";
    $nk = $pdo->query("SELECT kind, COUNT(*) FROM sources GROUP BY kind")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "<nav class=\"sub\">";
    foreach ($FK as $k => $v) echo "<a href=\"/admin?t=fuentes&amp;k=$k\"" . ($k === $fk && $fq === '' ? ' class="on"' : '') . ">$v <small>" . (int)($nk[$k] ?? 0) . "</small></a>";
    echo "</nav>";
    echo "<form method=\"get\" action=\"/admin\" class=\"buscar-u\"><input type=\"hidden\" name=\"t\" value=\"fuentes\"><input type=\"hidden\" name=\"k\" value=\"$fk\"><input type=\"search\" name=\"q\" value=\"" . h($fq) . "\" maxlength=\"64\" placeholder=\"buscar fuente: nombre, slug, descripción, web o RSS (en todos los tipos)\"><button class=\"btn sm\">Buscar</button>" . ($fq !== '' ? " <a href=\"/admin?t=fuentes&amp;k=$fk\">todas</a>" : '') . "</form>";
    if ($fq !== '') {
        $rows = array_values(array_filter($pdo->query("SELECT s.*, (SELECT COUNT(*) FROM source_items i WHERE i.source_id = s.source_id) n, (SELECT COUNT(*) FROM user_media um WHERE um.source_id = s.source_id) nu FROM sources s ORDER BY s.kind, s.name")->fetchAll(),
            fn($f) => str_contains($llano(implode(' ', [$f['name'], $f['slug'], (string)$f['descr'], (string)$f['home'], (string)$f['feed_portada'], (string)$f['feed_nuevas']])), $fqn)));
        echo "<p class=\"notice\">" . count($rows) . " coinciden con «" . h($fq) . "» (todos los tipos e idiomas).</p>";
        if (!$rows) echo "<p class=\"end\">Ninguna fuente coincide.</p>";
    } else {
    // idiomas de este tipo con su numero de fuentes: primero los de la interfaz (en su orden), luego otros codigos y al final «sin idioma»
    $nl = $pdo->prepare("SELECT COALESCE(lang, '') l, COUNT(*) FROM sources WHERE kind = ? GROUP BY COALESCE(lang, '')");
    $nl->execute([$fk]); $nl = $nl->fetchAll(PDO::FETCH_KEY_PAIR);
    $ord = array_merge(array_intersect(array_keys(DDD_LANGS), array_keys($nl)), array_diff(array_keys($nl), array_keys(DDD_LANGS), ['']));
    if (isset($nl[''])) $ord[] = '';
    if (count($nl) > 1 || $il !== '') {
        echo "<nav class=\"sub\"><a href=\"/admin?t=fuentes&amp;k=$fk\"" . ($il === '' ? ' class="on"' : '') . ">todos <small>" . array_sum($nl) . "</small></a>";
        foreach ($ord as $l) {
            $code = $l === '' ? '?' : $l;
            $lbl = $l === '' ? 'sin idioma' : (DDD_LANGS[$l] ?? $l);
            echo "<a href=\"/admin?t=fuentes&amp;k=$fk&amp;il=" . urlencode($code) . "\"" . ($il === $code ? ' class="on"' : '') . " title=\"" . h($code) . "\">" . h($lbl) . " <small>" . (int)$nl[$l] . "</small></a>";
        }
        echo "</nav>";
    }
    $wl = $il === '' ? '' : ($il === '?' ? " AND COALESCE(s.lang, '') = ''" : " AND s.lang = ?");
    $rows = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM source_items i WHERE i.source_id = s.source_id) n, (SELECT COUNT(*) FROM user_media um WHERE um.source_id = s.source_id) nu FROM sources s WHERE s.kind = ?$wl ORDER BY s.name");
    $rows->execute($il === '' || $il === '?' ? [$fk] : [$fk, $il]); $rows = $rows->fetchAll();
    if (!$rows) echo "<p class=\"end\">" . ($il === '' ? "No hay fuentes de este tipo." : "No hay fuentes de este tipo en ese idioma.") . "</p>";
    }   // fin sin busqueda
    // Destacadas del tipo: las que ven en «Mis medios» / «Mis substacks» / … quienes no siguen ninguna fuente (o no tienen cuenta).
    // Siempre con TODAS las fuentes encendidas del tipo (sin el filtro de idioma), porque al guardar se desmarcan las que no esten.
    $todas = $pdo->prepare("SELECT source_id, name, featured, lang FROM sources WHERE kind = ? AND status = 'ok' ORDER BY name"); $todas->execute([$fk]); $todas = $todas->fetchAll();
    if ($todas && $fq === '') {   // buscando no se enseña: la busqueda cruza tipos
        $nd = count(array_filter($todas, fn($f) => !empty($f['featured'])));
        echo "<details class=\"editbox\"" . ($nd ? '' : ' open') . "><summary><b>Destacadas</b> · $nd de " . count($todas) . " · las que ven en el menú quienes no siguen ninguna fuente de este tipo" . ($nd ? '' : " · <i>ninguna: se enseñan las que han publicado más recientemente</i>") . "</summary>";
        echo "<form method=\"post\" action=\"/admin?t=fuentes&amp;k=$fk\">$T<input type=\"hidden\" name=\"act\" value=\"fuentes_destacar\"><input type=\"hidden\" name=\"kind\" value=\"" . h($fk) . "\"><p style=\"columns:3 14em\">";
        foreach ($todas as $f) echo "<label style=\"display:block\"><input type=\"checkbox\" name=\"f[]\" value=\"{$f['source_id']}\"" . (!empty($f['featured']) ? ' checked' : '') . "> " . h($f['name']) . (($f['lang'] ?? '') !== '' ? " <small>" . h($f['lang']) . "</small>" : '') . "</label>";
        echo "</p><p><button class=\"btn sm\">guardar destacadas</button> <small>en el menú salen las " . medios_max($fk) . " que han publicado más recientemente de estas; en /mis-fuentes, todas</small></p></form></details>";
    }
    foreach ($rows as $f) echo "<form id=\"leer{$f['source_id']}\" method=\"post\" action=\"/admin\">$T<input type=\"hidden\" name=\"act\" value=\"fuente_leer\"><input type=\"hidden\" name=\"source_id\" value=\"{$f['source_id']}\"></form><form id=\"foto{$f['source_id']}\" method=\"post\" action=\"/admin\">$T<input type=\"hidden\" name=\"act\" value=\"fuente_foto\"><input type=\"hidden\" name=\"source_id\" value=\"{$f['source_id']}\"></form>";
    foreach ($rows as $f) {
        echo "<details class=\"editbox\"><summary><b>" . h($f['name']) . "</b> · " . h($f['kind']) . ($f['status'] === 'off' ? " · <i>apagada</i>" : "") . (!empty($f['featured']) ? " · destacado" : "") . (!empty($f['relay']) ? " · <b>lector local</b>" : "") . " · " . (($f['lang'] ?? '') !== '' ? h($f['lang']) : "<i>idioma ?</i>") . " · {$f['n']} noticias · {$f['nu']} usuarios · " . ($f['last_fetch'] ? "leída " . h(hace($f['last_fetch'])) : "sin leer") . ($f['last_error'] ? " · <span style=\"color:var(--neg)\">" . h($f['last_error']) . "</span>" : "") . " · <a href=\"" . h(fuente_url($f)) . "\">ver</a> <button class=\"btn sm\" form=\"leer{$f['source_id']}\" title=\"leer el RSS ahora\">leer ahora</button> <button class=\"btn sm\" form=\"foto{$f['source_id']}\" title=\"diagnostico: por que no hay foto en la ultima noticia sin foto\">probar foto</button></summary>";
        echo $fuente_form($f, 'fuente');
        echo "<p><form method=\"post\" onsubmit=\"return confirm('¿Borrar la fuente y todas sus noticias?')\">$T<input type=\"hidden\" name=\"act\" value=\"fuente_borrar\"><input type=\"hidden\" name=\"source_id\" value=\"{$f['source_id']}\"><button class=\"btn sm\">borrar</button></form></p></details>";
    }
    echo "<h3>Nueva fuente</h3>" . $fuente_form($fuentePre ?? ['interval_min' => 60, 'kind' => $fk], 'fuente_nueva');

} elseif ($tab === 'lateral') {
    require_once __DIR__ . '/../lib/lateral.php';
    $order = side_order(); $mods = side_modules();
    echo "<h3>Columna derecha (escritorio)</h3><p><small>Cada módulo con su posición (se ordenan de menor a mayor) y si se ve. Un módulo sin datos (p. ej. sin comunidades con actividad) no se pinta aunque esté activo. Los tres agregadores/medios dependen de <code>sources_enabled</code>.</small></p>";
    echo "<form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"lateral\"><table><tr><th style=\"width:60px\">pos.</th><th>módulo</th><th style=\"width:60px\">se ve</th></tr>";
    $i = 0; $rows = [];
    foreach ($order as $k) $rows[$k] = ++$i;
    foreach ($mods as $k => $m) if (!isset($rows[$k])) $rows[$k] = ++$i;
    asort($rows);
    foreach ($rows as $k => $n) {
        echo "<tr><td><input type=\"number\" name=\"pos[$k]\" value=\"$n\" min=\"1\" max=\"99\" style=\"width:56px\"></td><td>" . h($mods[$k][0]) . " <small><code>$k</code></small></td>";
        echo "<td style=\"text-align:center\"><input type=\"checkbox\" name=\"on[$k]\" value=\"1\"" . (in_array($k, $order, true) ? ' checked' : '') . "></td></tr>";
    }
    echo "</table><p><button class=\"btn\">Guardar lateral</button></p></form>";
    echo "<p><small>Orden actual: <code>" . h(implode(',', $order)) . "</code> (config <code>side_order</code>).</small></p>";

} elseif ($tab === 'logo') {
    echo "<h3>Logo de " . h(cfg('site_name')) . "</h3>";
    echo "<p><small>Se muestra a 30 px de alto en la cabecera. PNG, SVG o WebP con fondo transparente, hasta 4 MB; no se recorta, y si el PNG/WebP es grande se reduce a 240 px de alto (de sobra para los 30 px de la cabecera). El claro se usa sobre fondos claros y el oscuro sobre fondos oscuros (tema oscuro y plantillas Punk o Delta). Si solo subes el claro, se usará también en oscuro. La plantilla Retrocomputer tiñe el logo de verde a partir de un dibujo negro.</small></p>";
    echo "<form method=\"post\" enctype=\"multipart/form-data\">$T<input type=\"hidden\" name=\"act\" value=\"logo\"><table>";
    foreach (['light' => ['Claro', '#f6f5f0'], 'dark' => ['Oscuro', '#141414']] as $m => [$lbl, $bg]) {
        $propio = cfg("logo_$m");
        echo "<tr><td style=\"width:40%\"><strong>$lbl</strong><br><small>" . ($propio ? "personalizado: <code>" . h($propio) . "</code>" : "el de serie (<code>/img/logo" . ($m === 'dark' ? '-dark' : '') . ".png</code>)" . ($m === 'dark' && cfg('logo_light') ? ", pero se está usando el claro personalizado" : "")) . "</small>";
        echo "<div style=\"margin-top:8px;padding:12px;background:$bg;border:1px solid var(--line);border-radius:6px;display:inline-block\"><img src=\"" . h(logo_src($m)) . "?" . time() . "\" alt=\"\" style=\"height:30px;display:block\"></div></td>";
        echo "<td><input type=\"file\" name=\"logo_$m\" accept=\"image/png,image/svg+xml,image/webp\">";
        if ($propio) echo "<br><label><input type=\"checkbox\" name=\"quitar_$m\" value=\"1\"> volver al de serie</label>";
        echo "</td></tr>";
    }
    $propio = (string)cfg('logo_icon');
    echo "<tr><td style=\"width:40%\"><strong>Icono</strong><br><small>" . t('favicon, pantalla de inicio del móvil y app') . " · " . ($propio ? "personalizado: <code>" . h($propio) . "-*.png</code>" : "los de serie (<code>/img/favicon.png</code>, <code>/img/icon-180.png</code>, <code>/app/icon-*.png</code>)") . "</small>";
    echo "<div style=\"margin-top:8px;display:flex;gap:10px;align-items:flex-end\"><img src=\"" . h(icon_src(180)) . "?" . time() . "\" alt=\"\" style=\"width:60px;height:60px;border-radius:12px;border:1px solid var(--line)\"><img src=\"" . h(icon_src(64)) . "?" . time() . "\" alt=\"\" style=\"width:32px;height:32px\"><img src=\"" . h(icon_src(64)) . "?" . time() . "\" alt=\"\" style=\"width:16px;height:16px\"></div></td>";
    echo "<td><input type=\"file\" name=\"logo_icon\" accept=\"image/png,image/webp\"><br><small>PNG o WebP <b>cuadrado</b>, mínimo 192 px (mejor 512); se generan solos el favicon y los tamaños de iOS y Android.</small>";
    if ($propio) echo "<br><label><input type=\"checkbox\" name=\"quitar_icon\" value=\"1\"> volver a los de serie</label>";
    echo "</td></tr>";
    echo "</table><p><button class=\"btn\">Guardar logo</button></p></form>";
    $ldir = DDD_MEDIA_DIR . '/logo';
    echo "<p><small>Los ficheros van a <code>/media/logo</code>; al subir uno nuevo se borra el anterior. Si el navegador sigue enseñando el viejo, recarga con Ctrl+F5.<br>"
       . "Estado: carpeta <code>" . h($ldir) . "</code> " . (is_dir($ldir) ? (is_writable($ldir) ? "existe y es escribible" : "<b>existe pero no es escribible</b>") : (is_writable(DDD_MEDIA_DIR) ? "no existe aún (se creará al subir)" : "<b>no existe y /media no es escribible</b>"))
       . " · <code>upload_max_filesize</code> " . h(ini_get('upload_max_filesize')) . " · <code>post_max_size</code> " . h(ini_get('post_max_size')) . " · fileinfo " . (class_exists('finfo') ? "sí" : "<b>no (hace falta la extensión fileinfo)</b>")
       . " · config: logo_light=<code>" . h((string)cfg('logo_light')) . "</code> logo_dark=<code>" . h((string)cfg('logo_dark')) . "</code> logo_icon=<code>" . h((string)cfg('logo_icon')) . "</code></small></p>";

} elseif ($tab === 'privacidad') {
    echo "<p><small>HTML. Cada apartado es un <code>&lt;h2&gt;</code> seguido de sus <code>&lt;p&gt;</code>. Marcadores que se sustituyen por la config en vigor: <code>{site}</code> <code>{ttl}</code> <code>{gm}</code> <code>{cycle}</code> <code>{minage}</code> <code>{minpub}</code> <code>{minpubs}</code> (δ mínimo Medios / Substacks) <code>{minvotos}</code> (votos que cuentan necesarios) <code>{newttl}</code> <code>{newttltxt}</code> <code>{ttltxt}</code> (duraciones en palabras: «una semana», «dos días») <code>{decay}</code> <code>{decays}</code> (decaimiento Medios / Substacks) <code>{quar}</code> <code>{quartxt}</code> (cuarentena de votos) <code>{bonustemas}</code> (temas con bonus: «cultura, ciencia y relatos (+0,3)») <code>{auraport}</code> <code>{auraenv}</code> <code>{auracom}</code> <code>{auracap}</code> <code>{auradec}</code> <code>{aurastart}</code> <code>{auramax}</code> <code>{commaura}</code> <code>{commmax}</code> <code>{editaura}</code> <code>{editmin}</code> <code>{seditaura}</code> <code>{seditmin}</code> <code>{mediamax}</code> <code>{newsmax}</code> <code>{propaura}</code> <code>{skins}</code> (número de skins) <code>{auradl}</code> (fecha de <code>aura_deadline</code>, «31 de diciembre de 2027»).</small></p>";
    // Idioma que se edita: el español es el original; cada traduccion es una fila propia (slug.idioma). Sin traduccion,
    // el visitante de ese idioma ve la española, y aqui el editor sale relleno con ella para traducirla.
    $pl = (string)($_GET['pl'] ?? 'es');
    if (!isset(DDD_LANGS[$pl])) $pl = 'es';
    echo "<p class=\"langsel\"><b>Idioma:</b> ";
    foreach (DDD_LANGS as $lc => $ln) echo $lc === $pl ? " <b>" . h($ln) . "</b> ·" : " <a href=\"?t=privacidad&amp;pl=$lc\">" . h($ln) . "</a> ·";
    echo "</p>";
    if ($pl !== 'es') echo "<p><small>Estás editando la traducción al <b>" . h(DDD_LANGS[$pl]) . "</b>. Los marcadores (<code>{site}</code>, <code>{ttl}</code>…) se dejan tal cual. Si cambias el texto español, las traducciones no se actualizan solas: revísalas aquí.</small></p>";
    foreach (['que-es' => '¿Qué es Deltha?', 'empezar' => 'Cómo empezar', 'privacidad' => 'Privacidad y contacto', 'normas' => 'Normas de uso', 'algoritmo' => 'Cómo funciona el δ'] as $slug => $lbl) {
        $pg = page_get($pdo, $slug, $pl);
        $falta = $pl !== 'es' && $pg['lang'] !== $pl;   // no hay traduccion: sale la española
        $url = "/$slug" . ($pl !== 'es' ? "?lang=$pl" : "");
        echo "<h3>$lbl" . ($pl !== 'es' ? " · " . h(DDD_LANGS[$pl]) : "") . "</h3><p><small>Se muestra en <a href=\"$url\">$url</a>"
           . ($falta ? " · <b>sin traducir</b>: se ve la española (abajo, para traducirla)" : ($pg['updated'] ? " · última edición " . h($pg['updated']) : "")) . ".</small></p>";
        echo "<form method=\"post\">$T<input type=\"hidden\" name=\"act\" value=\"pagina\"><input type=\"hidden\" name=\"slug\" value=\"$slug\"><input type=\"hidden\" name=\"pl\" value=\"$pl\">";
        echo "<p><input type=\"text\" name=\"title\" value=\"" . h($pg['title']) . "\" placeholder=\"Título\"></p>";
        echo "<textarea name=\"body\" rows=\"20\">" . h($pg['body']) . "</textarea>";
        echo "<p><button class=\"btn\">Guardar página</button>";
        if ($pl !== 'es' && !$falta) echo " <label><input type=\"checkbox\" name=\"borrar\" value=\"1\"> borrar esta traducción (se verá la española)</label>";
        echo "</p></form>";
    }
}
echo "</div>";
page_footer();
