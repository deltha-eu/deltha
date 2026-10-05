-- migra-117 (05/10/2026): la IA local evalua lo que envian los agentes (pedido por Angel: «que evalue Bonsai lo que mandan»,
-- solo buenos envios y solo en español). Hasta ahora el cron elegia al azar entre las 12 noticias mas recientes (migra-091).
-- Con agents.submit_review = 1 el cron ya no envia por ese agente: el worker del PC (local/comentarista/comentarista.py) pide
-- las candidatas a /api/agente.php?act=submit_lease, el modelo pone nota de 0 a 10 a cada una segun el criterio del agente
-- (agents.submit_brief) y Deltha envia la mejor si llega a la nota minima (config agent_review_min); si ninguna llega, no se
-- envia nada y se vuelve a mirar en 30 min. Las notas quedan en agent_item_seen (registro en admin -> Agentes) y una noticia
-- suspendida no se le vuelve a ofrecer a ese agente. Si el PC esta apagado, esos agentes no envian (no hay vuelta al azar).
SET NAMES utf8mb4;

ALTER TABLE agents
    ADD COLUMN submit_review TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = la IA local evalua las candidatas antes de enviar' AFTER submit_langs,
    ADD COLUMN submit_brief  TEXT NULL COMMENT 'criterio de envio: que es una buena noticia para este agente' AFTER submit_review;

CREATE TABLE IF NOT EXISTS agent_item_seen (
    user_id  INT UNSIGNED NOT NULL,
    item_id  INT UNSIGNED NOT NULL,                       -- source_items.item_id (sin FK: los items caducan)
    score    TINYINT UNSIGNED NOT NULL DEFAULT 0,         -- nota del modelo, 0-10
    note     VARCHAR(200) NOT NULL DEFAULT '',
    title    VARCHAR(200) NOT NULL DEFAULT '',            -- copia del titular, para el registro cuando el item ya no este
    source   VARCHAR(120) NOT NULL DEFAULT '',
    sent     TINYINT(1) NOT NULL DEFAULT 0,               -- 1 = fue la elegida y se envio
    model    VARCHAR(80) DEFAULT NULL,
    created  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, item_id),
    KEY idx_ais_user (user_id, created),
    CONSTRAINT fk_ais_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('agent_review_min',   '7',  'Agentes con evaluacion: nota minima (0-10) que debe dar la IA local a una noticia para enviarla'),
('agent_review_cands', '20', 'Agentes con evaluacion: cuantas noticias candidatas se le pasan al modelo cada vez (5-40)');

-- Los tres agentes de la beta: evaluacion activada, solo fuentes en español y el criterio que ha dado Angel.
-- (La comparacion de login no distingue mayusculas ni tildes: vale «Sisifo» y «Sísifo».)
UPDATE agents a JOIN users u ON u.user_id = a.user_id
   SET a.submit_review = 1, a.submit_langs = 'es', a.next_submit = NOW(),
       a.submit_brief = 'Ciencia y tecnología: investigación científica, medicina y salud con base en estudios, espacio, física, biología, clima y medio ambiente con datos, informática, inteligencia artificial, ingeniería, energía e industria tecnológica. Puntúa alto los hallazgos, los estudios publicados, los avances explicados con rigor y los buenos reportajes de divulgación. Puntúa bajo todo lo que no sea ciencia o tecnología (política, sucesos, deporte, corazón), las notas de producto y las ofertas, los gadgets sin interés, la pseudociencia y los titulares sensacionalistas sobre salud.'
 WHERE u.login = 'Deogracias';

UPDATE agents a JOIN users u ON u.user_id = a.user_id
   SET a.submit_review = 1, a.submit_langs = 'es', a.next_submit = NOW(),
       a.submit_brief = 'Actualidad general, con preferencia por la política relevante: decisiones de gobiernos y parlamentos, leyes, tribunales, elecciones, economía política, política internacional y conflictos, investigaciones periodísticas y exclusivas. Puede enviar noticias de cualquier otro tema si son importantes. Puntúa alto lo que tiene consecuencias reales y lo que aporta información nueva. Puntúa bajo las declaraciones sin contenido, el cruce de reproches entre partidos, la polémica de redes sociales, los sondeos menores, las columnas de opinión sin datos y la política local sin alcance.'
 WHERE u.login = 'Quake';

UPDATE agents a JOIN users u ON u.user_id = a.user_id
   SET a.submit_review = 1, a.submit_langs = 'es', a.next_submit = NOW(),
       a.submit_brief = 'De todo: cualquier tema (cultura, sociedad, economía, ciencia, internacional, deporte, historia, tecnología, política) siempre que la pieza sea buena. Puntúa alto los reportajes, los análisis, las entrevistas con fondo, las historias bien contadas y las noticias importantes del día. Busca variedad: entre dos piezas de calidad parecida, prefiere la del tema menos habitual.'
 WHERE u.login = 'Sisifo';
