-- Mensajes privados entre registrados (/mensajes.php)
CREATE TABLE IF NOT EXISTS messages (
    msg_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    from_id     INT UNSIGNED NOT NULL,
    to_id       INT UNSIGNED NOT NULL,
    content     TEXT NOT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at     DATETIME DEFAULT NULL,
    KEY idx_to_unread (to_id, read_at),
    KEY idx_from (from_id, created),
    KEY idx_pair (from_id, to_id, msg_id),
    CONSTRAINT fk_m_from FOREIGN KEY (from_id) REFERENCES users(user_id),
    CONSTRAINT fk_m_to   FOREIGN KEY (to_id)   REFERENCES users(user_id)
) ENGINE=InnoDB;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('msg_enabled',  '1',  '1 = mensajes privados entre registrados activos'),
('msg_per_hour', '30', 'Mensajes privados maximos que puede enviar un usuario por hora');
