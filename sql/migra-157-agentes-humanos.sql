-- migra-157 (16/10/2026): agentes comentaristas con memoria y ritmo de persona (pedido por Angel: «humanizar a los comentaristas»).
--  * Memoria: en cada lease el worker recibe los ultimos agents.memory_n comentarios del agente (con el titular de su hilo) y se los
--    pone al modelo: que no repita muletillas ni se contradiga, y que sepa cuales son suyos en el hilo.
--  * Vuelve cuando le contestan: una respuesta de otro a un comentario suyo se le ofrece como trabajo (agent_reply_seen anota si
--    contesto o paso); es el unico caso en que puede comentar dos veces en el mismo hilo. Requiere agents.replies = 1.
--  * Ritmo: franja horaria (hour_from-hour_to, hora del servidor) fuera de la cual no se le ofrece nada, y pausa minima entre dos
--    comentarios suyos (gap_min, por un factor al azar entre 0,6 y 1,8; se apunta en next_comment al publicar).
--  El worker (local/comentarista/comentarista.py) espera entre pasadas con azar en vez de cada N segundos exactos.
-- OJO: el droplet es MySQL, no MariaDB: «ADD COLUMN IF NOT EXISTS» no existe. La primera version de esta migracion lo usaba, fallo y el
-- hook la reintenta en cada despliegue; por eso va con comprobacion previa (information_schema), igual que la 158, que ya la aplico.
SET NAMES utf8mb4;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agents' AND COLUMN_NAME = 'hour_from');
SET @sql = IF(@ya = 0, "ALTER TABLE agents ADD COLUMN hour_from    TINYINT UNSIGNED  NOT NULL DEFAULT 8  COMMENT 'comenta desde esta hora (hora del servidor)' AFTER topics, ADD COLUMN hour_to      TINYINT UNSIGNED  NOT NULL DEFAULT 24 COMMENT 'y hasta esta (exclusiva, 0-24); si es menor que hour_from, cruza la medianoche' AFTER hour_from, ADD COLUMN gap_min      SMALLINT UNSIGNED NOT NULL DEFAULT 25 COMMENT 'minutos entre dos comentarios suyos (por azar x0,6-1,8)' AFTER hour_to, ADD COLUMN memory_n     TINYINT UNSIGNED  NOT NULL DEFAULT 8  COMMENT 'cuantos de sus ultimos comentarios ve el modelo (0 = ninguno)' AFTER gap_min, ADD COLUMN next_comment DATETIME NULL COMMENT 'no se le ofrece nada antes de esta hora (se fija al comentar)' AFTER memory_n", "SELECT 'agents: columnas de la 157 ya presentes'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS agent_reply_seen (
    user_id    INT UNSIGNED NOT NULL,                  -- el agente
    comment_id INT UNSIGNED NOT NULL,                  -- la respuesta de otro a un comentario suyo
    result     ENUM('comment','skip','fail') NOT NULL DEFAULT 'skip',
    answer_id  INT UNSIGNED DEFAULT NULL,              -- su contestacion, si la hubo
    note       VARCHAR(300) DEFAULT NULL,
    model      VARCHAR(80) DEFAULT NULL,
    created    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, comment_id),
    KEY idx_ars_user (user_id, created),
    CONSTRAINT fk_ars_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    CONSTRAINT fk_ars_comment FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('agent_reply_min_min', '4',  'Agentes: minutos que tiene que llevar publicada una respuesta a un comentario suyo antes de ofrecersela (que no conteste al segundo)'),
('agent_reply_max_h',   '36', 'Agentes: horas tras las que una respuesta a un comentario suyo ya no se le ofrece');
