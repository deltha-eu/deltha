-- Chat (pestaña tras Articulos): mensajes cortos en un solo flujo, sin anidar; se responde citando #ID.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS chat (
    msg_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,                 -- registrado (con su nick)
    alias       VARCHAR(64) NOT NULL DEFAULT '',           -- anonimo: alias estable por IP (Apellido-Lugar)
    anon_key    CHAR(16) DEFAULT NULL,                     -- huella HMAC del anonimo: para borrar lo propio y para el limite por hora
    reply_to    INT UNSIGNED DEFAULT NULL,                 -- primer #ID citado en el texto (para «respuestas: #…» en el mensaje citado)
    content     VARCHAR(1000) NOT NULL,
    content_orig VARCHAR(1000) DEFAULT NULL,               -- texto original cuando lo borra moderacion
    deleted     TINYINT(1) NOT NULL DEFAULT 0,             -- 1 = borrado por moderacion (texto sustituido, sigue en el flujo)
    status      ENUM('ok','gone') NOT NULL DEFAULT 'ok',   -- gone = lo borro su autor (no se muestra)
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_created (status, created),
    KEY idx_reply (reply_to),
    KEY idx_user (user_id, created),
    KEY idx_anon (anon_key, created),
    CONSTRAINT fk_chat_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_reports (
    report_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    msg_id      INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED DEFAULT NULL,
    fp          CHAR(16) DEFAULT NULL,
    reason      ENUM('insulto','odio','acoso','datos','ilegal','spam','otro') NOT NULL DEFAULT 'otro',
    status      ENUM('open','deleted','dismissed') NOT NULL DEFAULT 'open',
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user (msg_id, user_id),
    UNIQUE KEY uq_fp   (msg_id, fp),
    KEY idx_status (status, created),
    CONSTRAINT fk_chr_msg FOREIGN KEY (msg_id) REFERENCES chat(msg_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('chat_enabled',        '1',   '1 = pestaña Chat visible y abierta'),
('chat_anon',           '1',   '1 = los anonimos pueden escribir en el Chat (con alias estable); 0 = solo leen'),
('chat_per_hour',       '30',  'Mensajes por hora y persona en el Chat (registrados)'),
('chat_anon_per_hour',  '10',  'Mensajes por hora y persona en el Chat (anonimos)'),
('chat_max_len',        '500', 'Longitud maxima de un mensaje del Chat (hasta 1000)'),
('chat_delete_minutes', '5',   'Minutos en los que el autor puede borrar su propio mensaje del Chat'),
('chat_per_page',       '50',  'Mensajes por pagina en el Chat');
