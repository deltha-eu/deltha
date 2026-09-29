-- migra-089: agente comentarista (bot con IA local). Un worker en el PC de la redaccion (local/comentarista/) pide hilos
-- candidatos a /api/agente.php, baja el articulo, se lo pasa al modelo local con la personalidad de local/comentarista/personalidad.md
-- y publica el comentario a nombre de la cuenta bot (config.agent_user), por el mismo camino que /comentar.php (alias Ghost,
-- cola de moderacion). El servidor nunca llama al PC. lib/agente.php
SET NAMES utf8mb4;

-- que hilos ha visto ya el agente (para no ofrecerselos dos veces) y que hizo con cada uno
CREATE TABLE IF NOT EXISTS agent_seen (
    story_id     INT UNSIGNED NOT NULL PRIMARY KEY,
    result       ENUM('leased','comment','skip','fail') NOT NULL DEFAULT 'leased',
    comment_id   INT UNSIGNED DEFAULT NULL,               -- el comentario publicado, si lo hubo
    note         VARCHAR(300) DEFAULT NULL,               -- motivo del modelo (por que comenta o por que calla) o el error
    model        VARCHAR(80) DEFAULT NULL,
    leased_until DATETIME DEFAULT NULL,
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_agent_created (created),
    CONSTRAINT fk_as_story FOREIGN KEY (story_id) REFERENCES stories(story_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('agent_enabled',      '0',  '1 = agente comentarista (bot con IA local): /api/agente.php reparte hilos al worker del PC'),
('agent_user',         '',   'Nick de la cuenta bot que firma los comentarios del agente (crearla en admin > Usuarios; vacio = apagado)'),
('agent_per_day',      '4',  'Comentarios del agente como mucho al dia'),
('agent_min_age_min',  '20', 'Minutos que debe tener un hilo antes de que el agente lo vea'),
('agent_max_age_h',    '24', 'Horas: hilos mas viejos no se le ofrecen'),
('agent_max_comments', '6',  'Solo hilos con como mucho estos comentarios (que aporte donde hay poco, no donde ya hay conversacion)'),
('agent_replies',      '0',  '1 = tambien puede responder a un comentario del hilo (parent); 0 = solo comentarios de raiz'),
('agent_skip_users',   '',   'Nicks (separados por comas) cuyos hilos no comenta: los otros bots, por ejemplo'),
('agent_topics',       '',   'Ids de temas (separados por comas) en los que comenta; vacio = todos'),
('agent_lease_sec',    '600','Segundos que un hilo queda reservado para el worker antes de volver a ofrecerse'),
('agent_worker_seen',  '',   'Ultima conexion del worker del agente');
