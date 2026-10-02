-- Historico permanente de registros (admin -> Procesos -> Registros): una fila por intento de alta, pase o no del formulario.
--   estado: pendiente (cuenta creada, correo sin confirmar) | activada | caducada (se borro sola por no activar) | rechazado (no paso del formulario)
--   motivo: por que se rechazo, o una nota («creada por un admin», «no se pudo enviar el correo»)
-- Sin clave ajena a users a proposito: la fila sobrevive al borrado de la cuenta. No se guarda la IP.
CREATE TABLE IF NOT EXISTS reg_log (
    reg_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    login       VARCHAR(64) NOT NULL DEFAULT '',
    email       VARCHAR(128) NOT NULL DEFAULT '',
    user_id     INT UNSIGNED DEFAULT NULL,
    estado      VARCHAR(16) NOT NULL DEFAULT 'pendiente',
    motivo      VARCHAR(160) NOT NULL DEFAULT '',
    activated   DATETIME DEFAULT NULL,
    KEY idx_reglog_created (created),
    KEY idx_reglog_user (user_id)
) ENGINE=InnoDB;

-- Las cuentas que ya existen entran en el historico con su fecha de alta (una sola vez: si ya estan, no se repiten).
INSERT INTO reg_log (created, login, email, user_id, estado)
SELECT u.created, u.login, u.email, u.user_id, IF(u.verified = 1, 'activada', 'pendiente')
  FROM users u
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT user_id FROM reg_log) r WHERE r.user_id = u.user_id);
