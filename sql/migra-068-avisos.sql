-- Avisos (campanita): menciones con @nick en comentarios y en el Chat, respuestas a lo tuyo y mensajes privados.
-- Una sola tabla y una sola burbuja con el numero de no leidos; la lista (/avisos.php) guarda el historico
-- (nunca se borra: solo cambia read_at). lib/avisos.php
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    notif_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,                    -- a quien avisa
    from_id     INT UNSIGNED DEFAULT NULL,                -- quien lo provoca (NULL = anonimo del Chat o cuenta borrada)
    from_name   VARCHAR(64) NOT NULL DEFAULT '',          -- como firmaba entonces: alias del hilo (comentarios), alias anonimo (Chat) o nick
    kind        ENUM('mention_comment','reply_comment','mention_chat','reply_chat','dm') NOT NULL,
    ref_id      INT UNSIGNED NOT NULL DEFAULT 0,          -- comment_id | chat.msg_id | messages.msg_id (el ultimo de la racha)
    story_id    INT UNSIGNED DEFAULT NULL,                -- hilo del comentario (para construir el enlace)
    snippet     VARCHAR(120) NOT NULL DEFAULT '',         -- trozo del texto en el momento de crearlo (si luego se edita o borra, el aviso sigue teniendo sentido)
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at     DATETIME DEFAULT NULL,
    KEY idx_user_unread (user_id, read_at),
    KEY idx_user_list (user_id, created),                -- la lista va por fecha: un aviso de privado agrupado sube al recibir otro mensaje
    KEY idx_dm_open (user_id, from_id, kind, read_at),   -- los privados seguidos de la misma persona se agrupan en un aviso mientras no se lea
    CONSTRAINT fk_n_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_n_from FOREIGN KEY (from_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('notif_enabled',  '1',  '1 = avisos (campanita): menciones @nick, respuestas y privados'),
('notif_per_page', '30', 'Avisos por pagina en /avisos.php'),
('notif_poll_sec', '60', 'Segundos entre consultas del contador de avisos desde el navegador (0 = solo al cargar la pagina)');
