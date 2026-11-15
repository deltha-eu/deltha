-- migra-203 (15/11/2026): imagenes tambien en el Chat (decision de Angel; hasta ahora solo en los comentarios, migra-166).
--  * chat.image: ruta relativa a /media (c/AAAA/MM/<nombre>.jpg), la misma carpeta y el mismo tratamiento que las de los comentarios
--    (reducida a comment_image_px de lado largo, JPG sin EXIF). Una por mensaje.
--  * Solo con cuenta y aura mayor que chat_image_min_aura (mods y admin, siempre). Al borrar el mensaje (el autor, moderacion o
--    «borrar del todo») se borra el fichero.
--  Idempotente.
SET NAMES utf8mb4;
SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat' AND COLUMN_NAME = 'image');
SET @sql = IF(@ya = 0, "ALTER TABLE chat ADD COLUMN image VARCHAR(80) NULL COMMENT 'imagen adjunta, ruta relativa a /media (migra-203)' AFTER content_orig", "SELECT 'chat.image ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('chat_images',          '1',  '1 = los mensajes del Chat admiten una imagen adjunta; 0 = no'),
('chat_image_min_aura',  '6',  'Aura minima (estricta: mayor que) para adjuntar una imagen a un mensaje del Chat');
