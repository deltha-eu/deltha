-- migra-129: recuperacion de contraseña por correo (08/10/2026).
-- /recuperar (usuario o correo) envia un enlace /nueva-clave?u=ID&t=TOKEN que vale reset_hours (2) y se usa una vez.
-- Columnas aparte de token/token_sent (que son los de activacion) para no pisarse.
ALTER TABLE users
    ADD COLUMN reset_token CHAR(32) DEFAULT NULL AFTER token_sent,
    ADD COLUMN reset_sent  DATETIME DEFAULT NULL AFTER reset_token;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('reset_hours', '2', 'Horas que vale el enlace de recuperar contraseña (/recuperar). Se puede pedir uno cada 10 minutos por cuenta');
