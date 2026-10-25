-- migra-169 (25/10/2026): @nick en los hilos de foro (articulos). Hasta ahora solo contaban en comentarios y Chat: en el texto
-- de un hilo ni se enlazaba al perfil ni se avisaba al mencionado.
--  * notifications.kind: nuevo tipo mention_story (→ /hilo/S). lib/avisos.php: avisos_story(); se llama al publicar (/articulo)
--    y al editar (/editar, solo los nicks nuevos). lib/render.php: md_articulo() enlaza los @nick como los comentarios.
--  Idempotente.
SET NAMES utf8mb4;
SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'kind' AND COLUMN_TYPE LIKE '%mention_story%');
SET @sql = IF(@ya = 0, "ALTER TABLE notifications MODIFY kind ENUM('mention_comment','reply_comment','mention_chat','reply_chat','dm','mention_story') NOT NULL", "SELECT 'notifications.kind ya admite mention_story'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
