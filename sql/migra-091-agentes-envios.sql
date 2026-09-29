-- migra-091: los agentes tambien envian noticias al radar, tal cual llegan por RSS de los medios y substacks (source_items):
-- titular y entradilla de la fuente, foto del feed si la hay, tema el de la fuente. Lo hace el servidor en el cron
-- (agentes_tick, lib/agente.php, desde scripts/promocionar.php): no hace falta modelo ni el PC. En la ficha del agente:
-- envios al dia (0 = no envia), de que fuentes (medios, substacks, ambas) y temas para enviar (vacio = todos).
-- agent_sent recuerda que items se han enviado ya (una noticia no la envian dos agentes). El agente no comenta lo suyo.
SET NAMES utf8mb4;

ALTER TABLE agents
    ADD COLUMN submits_per_day TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER topics,
    ADD COLUMN submit_kinds    ENUM('medio','newsletter','ambos') NOT NULL DEFAULT 'ambos' AFTER submits_per_day,
    ADD COLUMN submit_topics   VARCHAR(120) NOT NULL DEFAULT '' AFTER submit_kinds,
    ADD COLUMN next_submit     DATETIME DEFAULT NULL AFTER submit_topics;   -- cuando le toca el siguiente (con azar; NULL = ya)

CREATE TABLE IF NOT EXISTS agent_sent (
    item_id   INT UNSIGNED NOT NULL PRIMARY KEY,          -- source_items.item_id
    user_id   INT UNSIGNED NOT NULL,
    story_id  INT UNSIGNED DEFAULT NULL,
    created   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sent_user (user_id, created),
    CONSTRAINT fk_ase_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)     ON DELETE CASCADE,
    CONSTRAINT fk_ase_story FOREIGN KEY (story_id) REFERENCES stories(story_id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('agent_submit_max_age_h', '24', 'Agentes: solo envian noticias de las fuentes publicadas en las ultimas N horas');
