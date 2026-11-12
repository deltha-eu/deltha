-- migra-195 (12/11/2026): aviso a los moderadores cuando alguien escribe en la conversación de un elemento pendiente
-- (migra-191). A todos los que moderan (nivel mod/admin o aura >= mod_aura, como user_is_mod()) menos al autor del mensaje,
-- se les deja un aviso en la campanita (notifications.kind = mod_message) que lleva a la ventana de la conversación.
-- Idempotente.
SET NAMES utf8mb4;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'kind' AND COLUMN_TYPE LIKE '%mod_message%');
SET @sql = IF(@ya = 0, "ALTER TABLE notifications MODIFY kind ENUM('mention_comment','reply_comment','mention_chat','reply_chat','dm','mention_story','thread_comment','mod_message') NOT NULL", "SELECT 'notifications.kind ya admite mod_message'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
