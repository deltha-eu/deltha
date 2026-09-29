-- migra-089: agentes comentaristas (bots con IA local), uno por cuenta. En la ficha de un usuario (admin) se marca «esta cuenta
-- es un agente» y ahi mismo se ponen su personalidad (las instrucciones del modelo) y sus parametros, con una casilla «activo».
-- Puede haber varios agentes. Un worker en el PC de la redaccion (local/comentarista/) pide hilos a /api/agente.php para cada
-- agente activo, baja el articulo, se lo pasa al modelo local con la personalidad y publica el comentario a nombre de la cuenta
-- por el mismo camino que /comentar.php (alias Ghost, cola de moderacion). El servidor nunca llama al PC. lib/agente.php
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS agents (
    user_id      INT UNSIGNED NOT NULL PRIMARY KEY,           -- la cuenta bot
    enabled      TINYINT(1) NOT NULL DEFAULT 0,               -- la casilla «activo»
    persona      TEXT,                                        -- instrucciones del modelo (quien es, tono, cuando calla)
    per_day      TINYINT UNSIGNED NOT NULL DEFAULT 4,         -- comentarios al dia como mucho
    min_age_min  SMALLINT UNSIGNED NOT NULL DEFAULT 20,       -- edad minima del hilo
    max_age_h    SMALLINT UNSIGNED NOT NULL DEFAULT 24,       -- edad maxima
    max_comments TINYINT UNSIGNED NOT NULL DEFAULT 6,         -- solo hilos con como mucho estos comentarios
    replies      TINYINT(1) NOT NULL DEFAULT 0,               -- 1 = puede responder a un comentario del hilo
    skip_users   VARCHAR(300) NOT NULL DEFAULT '',            -- nicks (coma) cuyos hilos no comenta: los otros bots
    topics       VARCHAR(120) NOT NULL DEFAULT '',            -- ids de temas (coma); vacio = todos
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ag_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- que hilos ha visto ya cada agente (para no ofrecerselos dos veces) y que hizo con cada uno
CREATE TABLE IF NOT EXISTS agent_seen (
    user_id      INT UNSIGNED NOT NULL,
    story_id     INT UNSIGNED NOT NULL,
    result       ENUM('leased','comment','skip','fail') NOT NULL DEFAULT 'leased',
    comment_id   INT UNSIGNED DEFAULT NULL,               -- el comentario publicado, si lo hubo
    note         VARCHAR(300) DEFAULT NULL,               -- motivo del modelo (por que comenta o por que calla) o el error
    model        VARCHAR(80) DEFAULT NULL,
    leased_until DATETIME DEFAULT NULL,
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, story_id),
    KEY idx_agent_created (user_id, created),
    CONSTRAINT fk_as_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)     ON DELETE CASCADE,
    CONSTRAINT fk_as_story FOREIGN KEY (story_id) REFERENCES stories(story_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('agent_lease_sec',   '600', 'Agentes: segundos que un hilo queda reservado para el worker antes de volver a ofrecerse'),
('agent_worker_seen', '',    'Agentes: ultima conexion del worker del PC');
