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
('mod_enabled',         '0',    '1 = los envios pasan por el portero; 0 = directos a nuevas'),
('delta_min_age_min',   '30',   'Minutos desde el envio antes de ser candidata'),
('decay_per_halfhour',  '0.05', 'Decremento de delta por cada media hora desde el envio'),
('decay_mode',          'additive', 'additive | multiplicative'),
('delta_min_publish',   '1',    'Delta minimo para publicarse'),
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
('ghost_minutes',       '30',   'Minutos que dura el alias Ghost de cada envio/comentario antes de mostrar el nombre real'),
('per_page',            '20',   'Envios por pagina'),
('cycle_minutes',       '5',    'Minutos entre ciclos del cron (para estimar en /procesos.php cuando asciende cada nueva)'),
('procesos_days',       '7',    'Dias de actividad que muestra el flujo de /procesos.php'),
('article_max_words',   '500',  'Palabras maximas de un articulo'),
('anon_alias_mode',     'ip_hash', 'ip_hash = alias estable por hilo sin cookie | random = alias nuevo por comentario'),
('anon_can_comment',    '1',    '1 = los anonimos pueden comentar'),
('anon_can_submit',     '1',    '1 = los anonimos pueden enviar'),
('img_w',               '1200', 'Ancho de la foto procesada'),
('img_h',               '900',  'Alto de la foto procesada (4:3)'),
('img_max_bytes',       '6000000', 'Tamano maximo de subida'),
('og_timeout_sec',      '3',    'Timeout para extraer og:image'),
('curl_impersonate',    '/opt/curl-impersonate/curl_chrome116', 'Ruta a curl-impersonate (vacio = usar curl de PHP)'),
('mod_model',           'qwen2.5:7b-instruct', 'Modelo Ollama (cuando mod_enabled=1)'),
('mod_ollama_url',      'http://127.0.0.1:11434/api/chat', 'Endpoint Ollama'),
('mod_timeout_sec',     '30',   'Timeout modelo'),
('mod_max_retries',     '3',    'Reintentos antes de needs_human');

CREATE TABLE users (
    user_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login       VARCHAR(32) NOT NULL,
    email       VARCHAR(128) NOT NULL,
    pass        VARCHAR(255) NOT NULL,
    level       ENUM('normal','admin') NOT NULL DEFAULT 'normal',
    avatar      VARCHAR(64) DEFAULT NULL,           -- fichero en /media/avatars
    bio         VARCHAR(160) DEFAULT NULL,
    theme       ENUM('auto','light','dark') NOT NULL DEFAULT 'auto',
    aura        DECIMAL(6,3) NOT NULL DEFAULT 5.000,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_login (login),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB;

CREATE TABLE topics (
    topic_id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(32) NOT NULL,
    coef        DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    color       CHAR(7) NOT NULL DEFAULT '#534AB7',   -- velo de la foto
    UNIQUE KEY uq_name (name)
) ENGINE=InnoDB;

INSERT INTO topics (name, coef, color) VALUES
('actualidad', 1.00, '#534AB7'),
('politica',   1.00, '#993C1D'),
('cultura',    1.10, '#0F6E56'),
('ciencia',    1.10, '#185FA5'),
('deportes',   1.00, '#3B6D11'),
('tecnologia', 1.00, '#5F5E5A'),
('ocio',       1.00, '#993556');

-- content_type: link = enlace externo | article = articulo propio (pestaña Articulos, futuro)
CREATE TABLE stories (
    story_id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    content_type    ENUM('link','article') NOT NULL DEFAULT 'link',
    user_id         INT UNSIGNED DEFAULT NULL,       -- NULL = anonimo
    author_alias    VARCHAR(48) DEFAULT NULL,        -- alias Ghost del autor en este hilo
    revealed        TINYINT(1) NOT NULL DEFAULT 0,
    topic_id        SMALLINT UNSIGNED NOT NULL,
    title           VARCHAR(200) NOT NULL,
    url             VARCHAR(600) DEFAULT NULL,
    source          VARCHAR(100) DEFAULT NULL,       -- dominio de la fuente (eldiario.es)
    content         TEXT,
    image           VARCHAR(64) DEFAULT NULL,        -- nombre de fichero en /media
    status          ENUM('pending_mod','rejected','nuevas','candidatas','destacadas','archived','discard')
                    NOT NULL DEFAULT 'nuevas',
    sent_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    published_date  DATETIME DEFAULT NULL,
    votes_pos       INT NOT NULL DEFAULT 0,
    votes_neg       INT NOT NULL DEFAULT 0,
    comments        INT NOT NULL DEFAULT 0,
    delta           DECIMAL(8,3) NOT NULL DEFAULT 0.000,
    delta_calc      DATETIME DEFAULT NULL,
    KEY idx_status_sent (status, sent_date),
    KEY idx_status_delta (status, delta),
    KEY idx_status_pub (status, published_date),
    KEY idx_source (source),
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
    content     TEXT NOT NULL,
    status      ENUM('pending_mod','rejected','ok') NOT NULL DEFAULT 'ok',
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (story_id, user_id),
    KEY idx_created (created),
    CONSTRAINT fk_v_story FOREIGN KEY (story_id) REFERENCES stories(story_id),
    CONSTRAINT fk_v_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE mod_queue (
    item_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    item_type   ENUM('story','comment') NOT NULL DEFAULT 'story',
    ref_id      INT UNSIGNED NOT NULL,
    status      ENUM('queued','processing','ok','reject','needs_human') NOT NULL DEFAULT 'queued',
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    verdict     TEXT DEFAULT NULL,
    reason      VARCHAR(64) DEFAULT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed   DATETIME DEFAULT NULL,
    KEY idx_status (status, created),
    KEY idx_ref (item_type, ref_id)
) ENGINE=InnoDB;

CREATE TABLE promo_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    story_id    INT UNSIGNED NOT NULL,
    delta       DECIMAL(8,3) NOT NULL,
    candidates  SMALLINT UNSIGNED NOT NULL,
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
