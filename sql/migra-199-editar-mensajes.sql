-- migra-199 (13/11/2026): editar un mensaje privado propio durante unos minutos (pedido por Ángel, por si se cuela una errata).
--  · messages.edited_at: cuándo lo corrigió su autor; la conversación lo marca como «editado».
--  · config.msg_edit_minutes (10): minutos tras enviarlo en los que el autor puede corregirlo. 0 = no se puede editar.
-- Idempotente.
SET NAMES utf8mb4;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'edited_at');
SET @sql = IF(@ya = 0, "ALTER TABLE messages ADD COLUMN edited_at DATETIME NULL DEFAULT NULL COMMENT 'cuando lo corrigio su autor (migra-199)' AFTER read_at", "SELECT 'messages.edited_at ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('msg_edit_minutes', '10', 'Minutos tras enviar un mensaje privado en los que su autor puede corregirlo (0 = no se puede)');
