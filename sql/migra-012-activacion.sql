-- Activacion de cuenta por correo + dominios de correo vetados (moderacion) + limite de registros por IP.
ALTER TABLE users
    ADD COLUMN verified   TINYINT(1) NOT NULL DEFAULT 0 AFTER pass,
    ADD COLUMN token      CHAR(32) DEFAULT NULL AFTER verified,
    ADD COLUMN token_sent DATETIME DEFAULT NULL AFTER token;
-- las cuentas que ya existen quedan activadas
UPDATE users SET verified = 1;

CREATE TABLE IF NOT EXISTS banned_domains (
    domain   VARCHAR(120) NOT NULL PRIMARY KEY,
    reason   VARCHAR(120) DEFAULT NULL,
    added_by INT UNSIGNED DEFAULT NULL,
    created  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT IGNORE INTO banned_domains (domain, reason) VALUES
('mailinator.com','correo desechable'),('guerrillamail.com','correo desechable'),('10minutemail.com','correo desechable'),
('yopmail.com','correo desechable'),('temp-mail.org','correo desechable'),('tempmail.com','correo desechable'),
('sharklasers.com','correo desechable'),('trashmail.com','correo desechable'),('getnada.com','correo desechable'),
('dispostable.com','correo desechable'),('maildrop.cc','correo desechable'),('throwawaymail.com','correo desechable'),
('emailondeck.com','correo desechable'),('fakeinbox.com','correo desechable'),('mohmal.com','correo desechable');

-- intentos de registro por IP (huella HMAC, se limpia sola)
CREATE TABLE IF NOT EXISTS reg_attempts (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fp      CHAR(16) NOT NULL,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX ix_fp (fp, created)
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('site_url',   'https://deltha.eu', 'URL publica del sitio, sin barra final (para los enlaces de los correos)'),
('mail_from',  'noreply@deltha.eu', 'Remitente de los correos del sistema'),
('reg_per_ip_hour', '3', 'Registros maximos por IP y hora'),
('token_hours', '48', 'Horas de validez del enlace de activacion');
