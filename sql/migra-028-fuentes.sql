-- Fuentes externas (solo lectura): otros sitios «pintados» con Deltha a partir de su RSS.
-- Primera fuente: Menéame (portada, populares y nuevas). Los items no entran en stories ni en el δ.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS sources (
    source_id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(24) NOT NULL,                      -- /fuente.php?f=slug
    name         VARCHAR(48) NOT NULL,
    home         VARCHAR(200) NOT NULL,                     -- web de la fuente
    skin         VARCHAR(16) NOT NULL DEFAULT '',           -- plantilla forzada al verla ('' = la de cada cual)
    feed_portada VARCHAR(300) NOT NULL,                     -- RSS de lo publicado
    feed_nuevas  VARCHAR(300) NOT NULL DEFAULT '',          -- RSS de la cola (vacio = la fuente no tiene cola)
    interval_min SMALLINT UNSIGNED NOT NULL DEFAULT 5,      -- cada cuantos minutos se leen los feeds
    status       ENUM('ok','off') NOT NULL DEFAULT 'ok',
    last_fetch   DATETIME DEFAULT NULL,
    last_error   VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS source_items (
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
    image       VARCHAR(80) DEFAULT NULL,                   -- miniatura descargada a /media/fuentes (sin hotlink)
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

INSERT IGNORE INTO sources (slug, name, home, skin, feed_portada, feed_nuevas, interval_min) VALUES
('meneame', 'Menéame', 'https://www.meneame.net/', 'orange', 'https://www.meneame.net/rss', 'https://www.meneame.net/rss?status=queued', 5);

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('sources_enabled',          '1',  '1 = fuentes externas visibles (/fuente.php) y lectura de sus RSS en el cron'),
('sources_popular_hours',    '24', 'Fuentes: «candidatas» = nuevas de las ultimas N horas ordenadas por votos'),
('sources_popular_min_votes','5',  'Fuentes: votos minimos de una nueva para salir en «candidatas»'),
('sources_keep_days',        '7',  'Fuentes: dias que se guardan los items que ya no aparecen en los feeds'),
('sources_timeout_sec',      '8',  'Fuentes: timeout al leer cada feed');
