-- Comentarios: dos reacciones, «me gusta» (sol) y «no me gusta» (lágrima). Solo registrados, una por comentario
-- y persona (se puede cambiar de sentido o retirar); no se vota el comentario propio ni los borrados, y solo en
-- hilos abiertos. Los contadores van en comments para no contar en cada render. No afectan al aura ni al delta.
-- La caja de comentar pasa además al final del hilo, debajo de los comentarios (public/hilo.php).
SET NAMES utf8mb4;

ALTER TABLE comments
    ADD COLUMN likes    INT UNSIGNED NOT NULL DEFAULT 0 AFTER aura,   -- soles
    ADD COLUMN dislikes INT UNSIGNED NOT NULL DEFAULT 0 AFTER likes;  -- lagrimas

CREATE TABLE IF NOT EXISTS comment_votes (
    comment_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    value       TINYINT NOT NULL,                      -- 1 = sol, -1 = lagrima
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (comment_id, user_id),
    KEY idx_user (user_id, created),
    CONSTRAINT fk_cv_comment FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE,
    CONSTRAINT fk_cv_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE
) ENGINE=InnoDB;
