-- migra-090: agentes por cuenta (arreglo de la 089). La primera version de migra-089 (config global agent_* y agent_seen sin
-- user_id) ya se habia aplicado cuando se reescribio con la tabla agents; el hook la dio por hecha y no volvio a correrla.
-- Aqui se deja todo como pide lib/agente.php: agent_seen se recrea (solo tenia reservas, nada que conservar), se crea agents
-- y se quitan las claves de config que ya no se usan. Idempotente: se puede pasar dos veces.
SET NAMES utf8mb4;

DROP TABLE IF EXISTS agent_seen;

CREATE TABLE IF NOT EXISTS agents (
    user_id      INT UNSIGNED NOT NULL PRIMARY KEY,
    enabled      TINYINT(1) NOT NULL DEFAULT 0,
    persona      TEXT,
    per_day      TINYINT UNSIGNED NOT NULL DEFAULT 4,
    min_age_min  SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    max_age_h    SMALLINT UNSIGNED NOT NULL DEFAULT 24,
    max_comments TINYINT UNSIGNED NOT NULL DEFAULT 6,
    replies      TINYINT(1) NOT NULL DEFAULT 0,
    skip_users   VARCHAR(300) NOT NULL DEFAULT '',
    topics       VARCHAR(120) NOT NULL DEFAULT '',
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ag_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE agent_seen (
    user_id      INT UNSIGNED NOT NULL,
    story_id     INT UNSIGNED NOT NULL,
    result       ENUM('leased','comment','skip','fail') NOT NULL DEFAULT 'leased',
    comment_id   INT UNSIGNED DEFAULT NULL,
    note         VARCHAR(300) DEFAULT NULL,
    model        VARCHAR(80) DEFAULT NULL,
    leased_until DATETIME DEFAULT NULL,
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, story_id),
    KEY idx_agent_created (user_id, created),
    CONSTRAINT fk_as_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)     ON DELETE CASCADE,
    CONSTRAINT fk_as_story FOREIGN KEY (story_id) REFERENCES stories(story_id) ON DELETE CASCADE
) ENGINE=InnoDB;

DELETE FROM config WHERE ckey IN ('agent_enabled','agent_user','agent_per_day','agent_min_age_min','agent_max_age_h','agent_max_comments','agent_replies','agent_skip_users','agent_topics');
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('agent_lease_sec',   '600', 'Agentes: segundos que un hilo queda reservado para el worker antes de volver a ofrecerse'),
('agent_worker_seen', '',    'Agentes: ultima conexion del worker del PC'),
('agent_badge',       '0',   'Agentes: 1 = etiqueta «bot» junto al nick en el perfil publico; 0 = nada lo distingue de una cuenta normal');
