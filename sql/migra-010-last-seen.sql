-- Ultima visita de cada usuario registrado (se actualiza como mucho una vez por minuto). Para /estadisticas.php.
ALTER TABLE users ADD COLUMN last_seen DATETIME NULL DEFAULT NULL, ADD INDEX ix_last_seen (last_seen);
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('online_minutes', '5', 'Minutos sin actividad tras los que un usuario deja de contar como conectado (/estadisticas.php)');
