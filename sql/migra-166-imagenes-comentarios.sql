-- migra-166 (20/10/2026): imagenes en los comentarios (decision de Angel: en comentarios si, en el chat no; stickers y video subido, no).
--  * comments.image: ruta relativa a /media (c/AAAA/MM/<nombre>.jpg; carpeta por año y mes para que el repositorio no sea un directorio plano).
--    La imagen se guarda reducida (comment_image_px de lado largo, JPG sin EXIF) y se enseña como miniatura acotada (320x240) dentro de la sangria.
--  * Solo con cuenta y aura mayor que comment_image_min_aura; una por comentario. Al borrar el comentario (moderacion) se borra el fichero.
--  Idempotente.
SET NAMES utf8mb4;
SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments' AND COLUMN_NAME = 'image');
SET @sql = IF(@ya = 0, "ALTER TABLE comments ADD COLUMN image VARCHAR(80) NULL COMMENT 'imagen adjunta, ruta relativa a /media (migra-166)' AFTER content", "SELECT 'comments.image ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('comment_images',          '1',    '1 = los comentarios admiten una imagen adjunta; 0 = no'),
('comment_image_min_aura',  '6',    'Aura minima (estricta: mayor que) para adjuntar una imagen a un comentario'),
('comment_image_px',        '1000', 'Lado largo maximo (px) al que se reduce la imagen de un comentario');
