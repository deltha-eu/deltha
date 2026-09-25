-- Chat: sol (me gusta) y lagrima (no me gusta) en cada mensaje, igual que en los comentarios (migra-073).
-- Solo registrados, una reaccion por persona y mensaje (se cambia o se retira), nunca sobre lo propio ni lo borrado.
SET NAMES utf8mb4;

ALTER TABLE chat
    ADD COLUMN likes    INT UNSIGNED NOT NULL DEFAULT 0 AFTER content_orig,   -- soles
    ADD COLUMN dislikes INT UNSIGNED NOT NULL DEFAULT 0 AFTER likes;          -- lagrimas

CREATE TABLE IF NOT EXISTS chat_votes (
    msg_id      INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    value       TINYINT NOT NULL,                      -- 1 = sol, -1 = lagrima
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (msg_id, user_id),
    KEY idx_user (user_id, created),
    CONSTRAINT fk_chv_msg  FOREIGN KEY (msg_id)  REFERENCES chat(msg_id)   ON DELETE CASCADE,
    CONSTRAINT fk_chv_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
