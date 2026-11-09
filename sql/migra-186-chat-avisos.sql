-- migra-186 (09/11/2026): franja «Mensajes a la comunidad» del Chat (migra-121), tras subir a ella avisos viejos y un mensaje
-- normal marcado por error. (1) chat_anuncio_dias: los avisos dejan de salir en la franja pasados N dias (0 = nunca caducan);
-- conservan la marca y el resaltado en su sitio. (2) chat.anuncio_by: quien puso la marca, para enseñarlo en la franja.
-- (3) Marcar un mensaje AJENO pide confirmacion (paso intermedio en /chat?anun=N); el propio y quitar la marca siguen siendo un clic.
-- Idempotente.
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('chat_anuncio_dias', '7', 'Dias que un mensaje a la comunidad sigue saliendo en la franja de arriba del Chat (0 = siempre); la marca y el resaltado no caducan');
SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat' AND COLUMN_NAME = 'anuncio_by');
SET @sql = IF(@ya = 0, "ALTER TABLE chat ADD COLUMN anuncio_by INT UNSIGNED NULL DEFAULT NULL COMMENT 'quien marco el mensaje a la comunidad (migra-186)' AFTER anuncio", "SELECT 'chat.anuncio_by ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
