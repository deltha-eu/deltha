-- Delta de Dirac — esquema v0 (web privada)
-- MySQL 8 / MariaDB 10.6+, InnoDB, utf8mb4
-- Uso:  mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS deltha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE deltha;

CREATE TABLE config (
    ckey    VARCHAR(64) NOT NULL PRIMARY KEY,
    cvalue  VARCHAR(255) NOT NULL,
    descr   VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

INSERT INTO config (ckey, cvalue, descr) VALUES
('site_name',           'Deltha', 'Nombre del sitio'),
('access_key',          'CAMBIAR', 'Clave temporal de acceso: mientras no este vacia, quien no la tenga ve /delta/ en vez de la portada. Cambiarla expulsa a todos.'),
('mod_enabled',         '0',    '1 = la IA local revisa lo nuevo DESPUES de publicarse (posmoderacion); 0 = no se encola nada'),
('delta_min_age_min',   '30',   'Minutos desde el envio antes de poder llegar a portada (y desde los que descuenta la antigüedad)'),
('decay_medios',        '0.05', 'Decremento de delta por media hora de espera en Medios (desde que es elegible)'),
('decay_substacks',     '0.02', 'Decremento de delta por media hora de espera en Substacks (mas lento: lo suyo no caduca con la actualidad)'),
('delta_min_publish_medios',    '1', 'Delta minimo para publicar en Medios'),
('delta_min_publish_substacks', '1', 'Delta minimo para publicar en Substacks'),
('delta_min_votes',      '2',    'Votos netos que cuentan necesarios para ser candidato a portada; el bonus del tema no sustituye a ninguno'),
('vote_quarantine_hours', '24', 'Horas desde que se crea una cuenta hasta que sus votos cuentan en el delta (se ven desde el principio)'),
('cycle_lottery',       '1',    '1 = entre las que superan el minimo se sortea con probabilidad proporcional al delta; 0 = gana la de mayor delta'),
('cycle_cede_turno',    '1',    '1 = si a la portada del turno no le llega nada, publica en la otra'),
('cycle_jitter',        '1',    '1 = el minuto de ejecucion dentro de cada ventana de cycle_minutes depende del secreto del sitio (contra el sniping); 0 = minuto 0'),
('test_mode',           '0',    '1 = modo test (beta): si el ciclo no publica nada y la ultima promocion tiene mas de 60 min, publica el enlace del radar con mas delta sin exigir edad, votos ni delta minimos'),
('aura_portada',        '0.5',  'Aura por envio que llega a portada'),
('aura_envio',          '0.1',  'Aura por envio o articulo publicado en nuevas'),
('aura_comentario',     '0.02', 'Aura por comentario'),
('aura_daily_cap',      '2',    'Tope de aura que se puede ganar al dia'),
('aura_daily_decay',    '0.5',  'Aura que se pierde cada dia (nunca por debajo de aura_start)'),
('aura_deadline',       '2026-12-31', 'Fecha del reparto de propiedad entre usuarios con aura maxima'),
('aura_anon',           '5',    'Aura del anonimo'),
('aura_start',          '5',    'Aura inicial del registrado'),
('aura_max',            '50',   'Tope de aura'),
('thread_ttl_hours',    '48',   'Vida del hilo (destacadas -> archivo)'),
('nuevas_ttl_hours',    '168',  'Horas que puede pasar un envio en el radar sin llegar a portada antes de archivarse (168 = una semana; 0 = nunca)'),
('ghost_minutes',       '30',   'Minutos que dura el alias Ghost de cada envio/comentario antes de mostrar el nombre real'),
('per_page',            '20',   'Envios por pagina'),
('cycle_minutes',       '5',    'Minutos entre ciclos del cron; los ciclos alternan portada (Medios, Substacks) y corren en un minuto imprevisible de cada ventana'),
('procesos_days',       '7',    'Dias de actividad que muestra el flujo de /procesos.php'),
('msg_enabled',         '1',    '1 = mensajes privados entre registrados activos'),
('msg_per_hour',        '30',   'Mensajes privados maximos que puede enviar un usuario por hora'),
('notif_enabled',       '1',    '1 = avisos (campanita): menciones @nick, respuestas y privados'),
('notif_per_page',      '30',   'Avisos por pagina en /avisos.php'),
('notif_poll_sec',      '60',   'Segundos entre consultas del contador de avisos desde el navegador (0 = solo al cargar la pagina)'),
('skin_default',        'editorial', 'Skin por defecto (anonimos y quien no elija): cualquiera de DDD_SKINS'),
('comm_min_aura',       '6',    'Aura minima (estricta: mayor que) para poder crear comunidades'),
('comm_max_per_user',   '3',    'Comunidades que puede crear cada usuario'),
('comm_max_per_story',  '3',    'Comunidades en las que puede estar un mismo envio (contando la principal)'),
('side_order',          'ciclo,temas,comunidades,votadas,agregadores,medios,substacks', 'Modulos de la columna derecha (escritorio), en orden; lo que no este no se pinta'),
('sources_smf_per_run', '12',   'Foros SMF (Areopago): paginas de mensaje que se leen por pasada para sacar autor y numero del mensaje'),
('comment_edit_minutes','10',   'Minutos tras publicar en los que el autor puede editar su comentario'),
('comment_edit_min_aura','6',   'Aura minima (estricta: mayor que) para poder editar los comentarios propios'),
('article_max_words',   '1000', 'Palabras maximas de un articulo'),
('anon_alias_mode',     'ip_hash', 'ip_hash = alias estable por hilo sin cookie | random = alias nuevo por comentario'),
('anon_can_comment',    '1',    '1 = los anonimos pueden comentar'),
('anon_can_submit',     '1',    '1 = los anonimos pueden enviar'),
('img_w',               '1200', 'Ancho de la foto procesada'),
('img_h',               '900',  'Alto de la foto procesada (4:3)'),
('img_max_bytes',       '6000000', 'Tamano maximo de subida'),
('og_timeout_sec',      '3',    'Timeout para extraer og:image'),
('curl_impersonate',    '/opt/curl-impersonate/curl_chrome116', 'Ruta a curl-impersonate (vacio = usar curl de PHP)'),
('sources_enabled',      '1',    '1 = fuentes externas visibles (/fuente.php) y lectura de sus RSS en el cron'),
('sources_agregadores',  '0',    '1 = los agregadores (Meneame, Mediatize, Renegados...) se ven y se leen; 0 = fuera de todo salvo admin > Fuentes'),
('sources_popular_hours','24',   'Fuentes: «populares» = nuevas de las ultimas N horas ordenadas por votos'),
('sources_popular_min_votes','5','Fuentes: votos minimos de una nueva para salir en «populares»'),
('sources_keep_days',    '7',    'Fuentes: dias que se guardan los items que ya no aparecen en los feeds'),
('sources_timeout_sec',  '8',    'Fuentes: timeout al leer cada feed'),
('media_max_per_user',   '3',    'Medios que puede elegir cada usuario en su perfil («Mis medios»)'),
('news_max_per_user',    '3',    'Newsletters (Substack) que puede elegir cada usuario en su perfil («Mis substacks»)'),
('sources_og_image',     '1',    '1 = para los medios sin imagen en el RSS se busca la og:image de la noticia'),
('sources_og_per_run',   '10',   'Fuentes: og:image maximas que se descargan en cada pasada del cron'),
('mod_max_retries',     '3',    'Fallos del modelo con un mismo elemento antes de pasarlo a revision humana'),
('mod_auto_hide',       '0',    '1 = la IA oculta provisionalmente lo muy claro (confianza >= mod_hide_score) hasta que lo vea un moderador; 0 = solo señala'),
('mod_hide_score',      '0.90', 'Confianza minima para ocultar provisionalmente (con mod_auto_hide = 1)'),
('mod_flag_score',      '0.60', 'Confianza minima para señalar a los moderadores; por debajo se da por bueno'),
('mod_lease_sec',       '300',  'Segundos que el worker tiene reservado un elemento antes de que vuelva a la cola'),
('mod_worker_seen',     '',     'Ultima conexion del worker de IA local (lo escribe /api/moderacion.php)');

CREATE TABLE users (
    user_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login       VARCHAR(32) NOT NULL,
    email       VARCHAR(128) NOT NULL,
    pass        VARCHAR(255) NOT NULL,
    level       ENUM('normal','admin') NOT NULL DEFAULT 'normal',
    avatar      VARCHAR(64) DEFAULT NULL,           -- fichero en /media/avatars
    bio         VARCHAR(160) DEFAULT NULL,
    theme       ENUM('auto','light','dark') NOT NULL DEFAULT 'auto',
    skin        VARCHAR(16) NOT NULL DEFAULT '',   -- plantilla visual ('' = la del sitio, config.skin_default)
    landing     VARCHAR(24) NOT NULL DEFAULT '',   -- aterrizaje: '' portada | t:ID tema | c:ID comunidad
    lang        VARCHAR(5) NOT NULL DEFAULT '',    -- idioma de la interfaz: '' navegador | es | en | fr
    src_mylang  TINYINT(1) NOT NULL DEFAULT 0,     -- 1 = mostrar solo fuentes en mi idioma (migra-051)
    aura        DECIMAL(6,3) NOT NULL DEFAULT 5.000,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_login (login),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB;

CREATE TABLE topics (
    topic_id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(32) NOT NULL,
    bonus       DECIMAL(4,2) NOT NULL DEFAULT 0.00,   -- se SUMA al delta de los envios del tema con apoyo neto (antes coef multiplicador; migra-080)
    color       CHAR(7) NOT NULL DEFAULT '#534AB7',   -- velo de la foto
    UNIQUE KEY uq_name (name)
) ENGINE=InnoDB;

INSERT INTO topics (name, bonus, color) VALUES
('actualidad', 0.00, '#534AB7'),
('politica',   0.00, '#993C1D'),
('cultura',    0.30, '#0F6E56'),
('ciencia',    0.30, '#185FA5'),
('deportes',   0.00, '#3B6D11'),
('tecnologia', 0.00, '#5F5E5A'),
('ocio',       0.00, '#993556'),
('relatos',    0.30, '#6B3FA0');

-- content_type: link = enlace externo | article = original (texto propio, pestaña Originales; no va a portada)
CREATE TABLE stories (
    story_id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    content_type    ENUM('link','article') NOT NULL DEFAULT 'link',
    user_id         INT UNSIGNED DEFAULT NULL,       -- NULL = anonimo
    author_alias    VARCHAR(48) DEFAULT NULL,        -- alias Ghost del autor en este hilo
    revealed        TINYINT(1) NOT NULL DEFAULT 0,
    topic_id        SMALLINT UNSIGNED NOT NULL,
    community_id    INT UNSIGNED DEFAULT NULL,       -- comunidad (opcional), segunda clasificacion
    title           VARCHAR(200) NOT NULL,
    url             VARCHAR(600) DEFAULT NULL,
    source          VARCHAR(100) DEFAULT NULL,       -- dominio de la fuente (eldiario.es)
    content         TEXT,
    image           VARCHAR(64) DEFAULT NULL,        -- nombre de fichero en /media
    status          ENUM('pending_mod','rejected','nuevas','destacadas','archived','discard')
                    NOT NULL DEFAULT 'nuevas',        -- nuevas = en el radar; destacadas = en una portada
    portada         ENUM('medios','substacks') DEFAULT NULL,   -- portada de destino de un enlace; NULL = original (articulo)
    sent_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_at       DATETIME DEFAULT NULL,           -- ultima edicion (autor en plazo o moderacion)
    indexable       TINYINT(1) NOT NULL DEFAULT 0,   -- articulo abierto a buscadores por su autor (migra-049)
    published_date  DATETIME DEFAULT NULL,
    votes_pos       INT NOT NULL DEFAULT 0,
    votes_neg       INT NOT NULL DEFAULT 0,
    eff_pos         INT NOT NULL DEFAULT 0,          -- votos que cuentan en el delta: uno por conexion (votes.fp) y solo de cuentas maduras (migra-080)
    eff_neg         INT NOT NULL DEFAULT 0,
    comments        INT NOT NULL DEFAULT 0,
    delta           DECIMAL(8,3) NOT NULL DEFAULT 0.000,
    delta_calc      DATETIME DEFAULT NULL,
    KEY idx_status_sent (status, sent_date),
    KEY idx_status_delta (status, delta),
    KEY idx_status_pub (status, published_date),
    KEY idx_portada (portada, status, published_date),
    KEY idx_source (source),
    KEY idx_comm (community_id, sent_date),
    CONSTRAINT fk_st_user  FOREIGN KEY (user_id)  REFERENCES users(user_id),
    CONSTRAINT fk_st_topic FOREIGN KEY (topic_id) REFERENCES topics(topic_id)
) ENGINE=InnoDB;

-- Alias Ghost de cada registrado en cada hilo (estable dentro del hilo)
CREATE TABLE thread_aliases (
    story_id    INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    alias       VARCHAR(48) NOT NULL,
    PRIMARY KEY (story_id, user_id),
    CONSTRAINT fk_ta_story FOREIGN KEY (story_id) REFERENCES stories(story_id),
    CONSTRAINT fk_ta_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE comments (
    comment_id  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    story_id    INT UNSIGNED NOT NULL,
    parent_id   INT UNSIGNED DEFAULT NULL,           -- comentario al que responde
    root_id     INT UNSIGNED DEFAULT NULL,           -- raiz de la conversacion (NULL = es raiz)
    user_id     INT UNSIGNED DEFAULT NULL,           -- NULL = anonimo
    alias       VARCHAR(48) NOT NULL,                -- lo que se muestra mientras no hay revelado
    anon_key    CHAR(16) DEFAULT NULL,               -- hash truncado (ip+hilo+secreto) para agrupar anonimos; no reversible
    aura        DECIMAL(6,3) NOT NULL DEFAULT 5.000,  -- aura del autor en el momento de comentar (para el halo)
    likes       INT UNSIGNED NOT NULL DEFAULT 0,      -- soles (me gusta): contador de comment_votes (migra-073)
    dislikes    INT UNSIGNED NOT NULL DEFAULT 0,      -- lagrimas (no me gusta)
    content     TEXT NOT NULL,
    content_orig TEXT DEFAULT NULL,                  -- texto original si fue borrado por moderacion
    status      ENUM('pending_mod','rejected','ok') NOT NULL DEFAULT 'ok',
    deleted     TINYINT(1) NOT NULL DEFAULT 0,        -- borrado por moderacion: content sustituido por el aviso
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_at   DATETIME DEFAULT NULL,               -- ultima edicion (autor en plazo o moderacion)
    KEY idx_story (story_id, created),
    KEY idx_root (story_id, root_id, created),
    KEY idx_created (created),
    CONSTRAINT fk_c_story FOREIGN KEY (story_id) REFERENCES stories(story_id),
    CONSTRAINT fk_c_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Solo registrados votan
CREATE TABLE votes (
    story_id    INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    value       TINYINT NOT NULL,
    fp          CHAR(16) DEFAULT NULL,               -- huella HMAC(ip + envio) truncada, nunca la IP: varias cuentas desde la misma conexion cuentan como un voto (migra-080)
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (story_id, user_id),
    KEY idx_created (created),
    KEY idx_story_fp (story_id, fp),
    CONSTRAINT fk_v_story FOREIGN KEY (story_id) REFERENCES stories(story_id),
    CONSTRAINT fk_v_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Reacciones a comentarios: sol (1) o lagrima (-1), una por persona y comentario; se puede cambiar o retirar (migra-073)
CREATE TABLE comment_votes (
    comment_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    value       TINYINT NOT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (comment_id, user_id),
    KEY idx_user (user_id, created),
    CONSTRAINT fk_cv_comment FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE,
    CONSTRAINT fk_cv_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE mod_queue (
    item_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    item_type   ENUM('story','comment','chat') NOT NULL DEFAULT 'story',
    ref_id      INT UNSIGNED NOT NULL,
    status      ENUM('queued','processing','ok','flag','hidden','needs_human','human_ok','human_removed','reject') NOT NULL DEFAULT 'queued',
    priority    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    verdict     TEXT DEFAULT NULL,
    reason      VARCHAR(64) DEFAULT NULL,
    category    VARCHAR(16) DEFAULT NULL,
    score       DECIMAL(4,3) DEFAULT NULL,
    prev_status VARCHAR(16) DEFAULT NULL,
    leased_until DATETIME DEFAULT NULL,
    model       VARCHAR(80) DEFAULT NULL,
    decided_by  INT UNSIGNED DEFAULT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed   DATETIME DEFAULT NULL,
    KEY idx_status (status, created),
    KEY idx_ref (item_type, ref_id),
    KEY idx_lease (status, priority, item_id)
) ENGINE=InnoDB;

CREATE TABLE promo_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    story_id    INT UNSIGNED NOT NULL,
    portada     ENUM('medios','substacks') DEFAULT NULL,  -- portada en la que se publico
    delta       DECIMAL(8,3) NOT NULL,
    candidates  SMALLINT UNSIGNED NOT NULL,              -- enlaces que habia en el radar de esa portada
    commenters  SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- comentaristas unicos en el momento de publicar (instrumentacion)
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_story (story_id)
) ENGINE=InnoDB;

CREATE TABLE aura_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    amount      DECIMAL(7,3) NOT NULL,
    reason      VARCHAR(64) NOT NULL,
    ref_id      INT UNSIGNED DEFAULT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id)
) ENGINE=InnoDB;

-- Mensajes privados entre registrados
CREATE TABLE messages (
    msg_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    from_id     INT UNSIGNED NOT NULL,
    to_id       INT UNSIGNED NOT NULL,
    content     TEXT NOT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at     DATETIME DEFAULT NULL,
    KEY idx_to_unread (to_id, read_at),
    KEY idx_from (from_id, created),
    KEY idx_pair (from_id, to_id, msg_id),
    CONSTRAINT fk_m_from FOREIGN KEY (from_id) REFERENCES users(user_id),
    CONSTRAINT fk_m_to   FOREIGN KEY (to_id)   REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Reportes de comentarios (cualquiera puede reportar; los revisan los moderadores)
CREATE TABLE comment_reports (
    report_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    comment_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED DEFAULT NULL,
    fp          CHAR(16) DEFAULT NULL,
    reason      ENUM('insulto','odio','acoso','datos','ilegal','spam','otro') NOT NULL DEFAULT 'otro',
    status      ENUM('open','deleted','dismissed') NOT NULL DEFAULT 'open',
    decided_by  INT UNSIGNED DEFAULT NULL,             -- moderador que cerro los reportes (migra-082)
    decided     DATETIME DEFAULT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user (comment_id, user_id),
    UNIQUE KEY uq_fp   (comment_id, fp),
    KEY idx_status (status, created),
    CONSTRAINT fk_cr_comment FOREIGN KEY (comment_id) REFERENCES comments(comment_id)
) ENGINE=InnoDB;

-- Comunidades creadas por los usuarios (segunda clasificacion, ortogonal a los temas)
CREATE TABLE communities (
    community_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(40) NOT NULL,
    name         VARCHAR(48) NOT NULL,
    descr        VARCHAR(200) DEFAULT NULL,
    color        CHAR(7) NOT NULL DEFAULT '#534AB7',
    owner_id     INT UNSIGNED NOT NULL,
    status       ENUM('ok','hidden') NOT NULL DEFAULT 'ok',
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_slug (slug),
    UNIQUE KEY uq_name (name),
    KEY idx_owner (owner_id),
    CONSTRAINT fk_co_owner FOREIGN KEY (owner_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Fuentes externas (solo lectura): otros sitios pintados con Deltha a partir de su RSS (migra-028)
-- Un envio puede estar en varias comunidades (la principal sigue en stories.community_id; las demas las «adopta» el dueño
-- de la comunidad desde el hilo, o los mods). Tope config.comm_max_per_story. Migracion 037.
CREATE TABLE story_communities (
    story_id     INT UNSIGNED NOT NULL,
    community_id INT UNSIGNED NOT NULL,
    added_by     INT UNSIGNED DEFAULT NULL,
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (story_id, community_id),
    KEY idx_comm (community_id, created),
    CONSTRAINT fk_sc_story FOREIGN KEY (story_id)     REFERENCES stories(story_id)         ON DELETE CASCADE,
    CONSTRAINT fk_sc_comm  FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE,
    CONSTRAINT fk_sc_user  FOREIGN KEY (added_by)     REFERENCES users(user_id)            ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE sources (
    source_id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(24) NOT NULL,                      -- /fuente.php?f=slug
    kind         ENUM('agregador','medio','newsletter') NOT NULL DEFAULT 'medio',   -- agregador: fases y votos (Meneame) | medio: ultimas noticias, elegible en «Mis medios» | newsletter: idem, en «Mis substacks»
    name         VARCHAR(48) NOT NULL,
    home         VARCHAR(200) NOT NULL,                     -- web de la fuente
    descr        VARCHAR(200) DEFAULT NULL,
    lang         VARCHAR(5) NOT NULL DEFAULT '',            -- idioma (es, en…; '' = sin saber, se detecta del RSS) (migra-051)
    skin         VARCHAR(16) NOT NULL DEFAULT '',           -- plantilla forzada al verla ('' = la de cada cual)
    topic_id     SMALLINT UNSIGNED DEFAULT NULL,            -- tema propuesto al enviar una noticia de este medio
    feed_portada VARCHAR(300) NOT NULL,                     -- RSS de lo publicado
    feed_nuevas  VARCHAR(300) NOT NULL DEFAULT '',          -- RSS de la cola (vacio = la fuente no tiene cola)
    interval_min SMALLINT UNSIGNED NOT NULL DEFAULT 5,      -- cada cuantos minutos se leen los feeds
    status       ENUM('ok','off') NOT NULL DEFAULT 'ok',
    featured     TINYINT(1) NOT NULL DEFAULT 0,           -- medio que ven quienes no han elegido (anonimos); se marca en admin
    last_fetch   DATETIME DEFAULT NULL,
    last_error   VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE source_items (
    item_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    source_id   SMALLINT UNSIGNED NOT NULL,
    ext_id      VARCHAR(32) NOT NULL,                       -- id en la fuente (meneame:link_id o guid)
    phase       ENUM('nuevas','portada') NOT NULL DEFAULT 'nuevas',
    title       VARCHAR(200) NOT NULL,
    url         VARCHAR(600) NOT NULL,                      -- el articulo (en Meneame, /go?id=N redirige al original)
    thread_url  VARCHAR(600) NOT NULL,                      -- el hilo en la fuente (comentarios)
    source      VARCHAR(100) DEFAULT NULL,                  -- dominio del medio original
    topic       VARCHAR(48) DEFAULT NULL,                   -- sub / categoria en la fuente
    author      VARCHAR(64) DEFAULT NULL,
    content     TEXT,                                       -- entradilla en texto plano
    image       VARCHAR(100) DEFAULT NULL,                  -- ruta relativa a /media: fuentes/x.webp (miniatura del agregador) o x.jpg (og:image procesada)
    votes       INT NOT NULL DEFAULT 0,
    negatives   INT NOT NULL DEFAULT 0,
    comments    INT NOT NULL DEFAULT 0,
    pub_date    DATETIME NOT NULL,                          -- fecha del feed (en portada, la de publicacion)
    first_seen  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ext (source_id, ext_id),
    KEY idx_phase_date (source_id, phase, pub_date),
    KEY idx_phase_votes (source_id, phase, votes),
    KEY idx_seen (last_seen),
    CONSTRAINT fk_si_source FOREIGN KEY (source_id) REFERENCES sources(source_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min) VALUES
('meneame',  'agregador', 'Menéame',          'https://www.meneame.net/',        NULL, 'orange', NULL, 'https://www.meneame.net/rss', 'https://www.meneame.net/rss?status=queued', 5),
('jotdown',  'medio',     'Jot Down',         'https://www.jotdown.es/',         'Contemporary Culture Mag', '', 3, 'https://www.jotdown.es/feed/', '', 60),
('mercurio', 'medio',     'Revista Mercurio', 'https://www.revistamercurio.es/', 'Revista de libros y cultura', '', 3, 'https://revistamercurio.es/feed/', '', 60);
UPDATE sources SET featured = 1 WHERE slug IN ('jotdown', 'mercurio');
INSERT INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('anfibia',        'medio', 'Revista Anfibia',      'https://www.revistaanfibia.com/',      'Crónicas y ensayos desde Buenos Aires', '', 3, 'https://www.revistaanfibia.com/feed/',       '', 30, 1),
('natgeo',         'medio', 'National Geographic',  'https://www.nationalgeographic.com.es/', 'Ciencia, naturaleza e historia',      '', 4, 'https://www.nationalgeographic.com.es/feeds/rss',  '', 30, 0),
('bbc',            'medio', 'BBC Mundo',            'https://www.bbc.com/mundo',            'Noticias internacionales en español',   '', 1, 'https://feeds.bbci.co.uk/mundo/rss.xml',     '', 15, 0),
('cgtn',           'medio', 'CGTN Español',         'https://espanol.cgtn.com/',            'Televisión china en español',           '', 1, 'https://espanol.cgtn.com/rss',               '', 30, 0),
('pijamasurf',     'medio', 'Pijama Surf',          'https://pijamasurf.com/',              'Información alternativa',               '', 3, 'https://pijamasurf.com/feed/',               '', 30, 0),
('culturainquieta','medio', 'Cultura Inquieta',     'https://culturainquieta.com/',         'Arte, música y cultura',                '', 3, 'https://culturainquieta.com/feed/',          '', 30, 0),
('hipertextual',   'medio', 'Hipertextual',         'https://hipertextual.com/',            'Tecnología y ciencia',                  '', 6, 'https://hipertextual.com/feed',              '', 60, 0);
INSERT INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('jorgecarrion',   'newsletter', 'Jorge Carrión',        'https://jorgecarrion21.substack.com/',  'Newsletter (Substack)', '', 3, 'https://jorgecarrion21.substack.com/feed',  '', 1440, 1),
('periodismo',     'newsletter', 'Periodismo',           'https://periodismo.substack.com/',      'Newsletter (Substack)', '', 1, 'https://periodismo.substack.com/feed',      '', 1440, 0),
('sergioparra',    'newsletter', 'Sergio Parra',         'https://sergioparra.substack.com/',     'Newsletter (Substack)', '', 4, 'https://sergioparra.substack.com/feed',     '', 1440, 1),
('error500',       'newsletter', 'Error500',             'https://www.error500.net/',             'Antonio Ortiz · newsletter', '', 6, 'https://www.error500.net/feed',          '', 1440, 1),
('uriondo',        'newsletter', 'Uriondo',              'https://uriondo.substack.com/',         'Newsletter (Substack)', '', 1, 'https://uriondo.substack.com/feed',         '', 1440, 0),
('martinsacristan','newsletter', 'Martín Sacristán',     'https://martinsacristan.substack.com/', 'Newsletter (Substack)', '', 3, 'https://martinsacristan.substack.com/feed', '', 1440, 0);

-- Medios elegidos por cada usuario («Mis medios», hasta config.media_max_per_user)
CREATE TABLE user_media (
    user_id   INT UNSIGNED NOT NULL,
    source_id SMALLINT UNSIGNED NOT NULL,
    pos       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id, source_id),
    CONSTRAINT fk_um_user   FOREIGN KEY (user_id)   REFERENCES users(user_id)     ON DELETE CASCADE,
    CONSTRAINT fk_um_source FOREIGN KEY (source_id) REFERENCES sources(source_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Favoritos: envios y articulos guardados por cada usuario desde el hilo; pestaña Favoritos del perfil. Migracion 043.
CREATE TABLE favorites (
    user_id     INT UNSIGNED NOT NULL,
    story_id    INT UNSIGNED NOT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, story_id),
    KEY idx_fav_created (user_id, created),
    CONSTRAINT fk_fav_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)   ON DELETE CASCADE,
    CONSTRAINT fk_fav_story FOREIGN KEY (story_id) REFERENCES stories(story_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Fuentes propuestas por los usuarios, pendientes de moderacion (migra-057)
CREATE TABLE source_proposals (
    prop_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,                       -- quien la propone
    kind        ENUM('medio','newsletter') NOT NULL DEFAULT 'medio',
    name        VARCHAR(48) NOT NULL,
    home        VARCHAR(200) NOT NULL,                       -- web
    feed        VARCHAR(300) NOT NULL,                       -- RSS (el dado o el encontrado en la web)
    descr       VARCHAR(200) DEFAULT NULL,
    note        VARCHAR(500) DEFAULT NULL,                   -- nota de quien propone para moderacion
    lang        VARCHAR(5) NOT NULL DEFAULT '',              -- idioma que declara el feed ('' = no lo dice)
    test_items  SMALLINT UNSIGNED NOT NULL DEFAULT 0,        -- noticias leidas en la ultima prueba (0 = no se lee)
    test_info   VARCHAR(600) DEFAULT NULL,                   -- ultimos titulos o el error de la prueba
    status      ENUM('pending','ok','rejected') NOT NULL DEFAULT 'pending',
    reply       VARCHAR(300) DEFAULT NULL,                   -- motivo del rechazo (se le envia por mensaje)
    source_id   SMALLINT UNSIGNED DEFAULT NULL,              -- la fuente creada al aprobarla
    reviewed_by INT UNSIGNED DEFAULT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed    DATETIME DEFAULT NULL,
    KEY idx_fp_status (status, created),
    KEY idx_fp_user (user_id, created),
    CONSTRAINT fk_fp_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Lista blanca de dominios de correo (migra-058; la negra es banned_domains, migra-012)
CREATE TABLE IF NOT EXISTS allowed_domains (
    domain   VARCHAR(120) NOT NULL PRIMARY KEY,
    reason   VARCHAR(120) DEFAULT NULL,
    added_by INT UNSIGNED DEFAULT NULL,
    created  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Avisos (campanita): menciones @nick, respuestas y privados (migra-068; lib/avisos.php)
CREATE TABLE IF NOT EXISTS notifications (
    notif_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,                    -- a quien avisa
    from_id     INT UNSIGNED DEFAULT NULL,                -- quien lo provoca (NULL = anonimo del Chat o cuenta borrada)
    from_name   VARCHAR(64) NOT NULL DEFAULT '',          -- como firmaba entonces: alias del hilo (comentarios), alias anonimo (Chat) o nick
    kind        ENUM('mention_comment','reply_comment','mention_chat','reply_chat','dm') NOT NULL,
    ref_id      INT UNSIGNED NOT NULL DEFAULT 0,          -- comment_id | chat.msg_id | messages.msg_id (el ultimo de la racha)
    story_id    INT UNSIGNED DEFAULT NULL,                -- hilo del comentario (para construir el enlace)
    snippet     VARCHAR(120) NOT NULL DEFAULT '',         -- trozo del texto en el momento de crearlo (si luego se edita o borra, el aviso sigue teniendo sentido)
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at     DATETIME DEFAULT NULL,
    KEY idx_user_unread (user_id, read_at),
    KEY idx_user_list (user_id, created),                -- la lista va por fecha: un aviso de privado agrupado sube al recibir otro mensaje
    KEY idx_dm_open (user_id, from_id, kind, read_at),   -- los privados seguidos de la misma persona se agrupan en un aviso mientras no se lea
    CONSTRAINT fk_n_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_n_from FOREIGN KEY (from_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
