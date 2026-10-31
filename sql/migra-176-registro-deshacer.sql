-- migra-176 (30/10/2026): deshacer las acciones de moderación desde la pestaña Registro de /moderacion.
--  * undo_ops: operaciones inversas en JSON (se llama así porque UNDO es palabra reservada en MySQL). Las prepara lib/app.php
--    (mod_undo_row / mod_undo_created) con el estado de la fila ANTES de tocarla; mod_log_post() las sella al terminar la
--    petición con los valores que quedaron, para no deshacer a ciegas.
--  * undone / undone_by: cuándo y quién pulsó «Deshacer». Una acción deshecha no se vuelve a deshacer.
--  Idempotente.
SET NAMES utf8mb4;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mod_log' AND COLUMN_NAME = 'undo_ops');
SET @sql = IF(@ya = 0, "ALTER TABLE mod_log ADD COLUMN undo_ops TEXT NULL COMMENT 'operaciones inversas en JSON (migra-176)' AFTER ip", "SELECT 'mod_log.undo_ops ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mod_log' AND COLUMN_NAME = 'undone');
SET @sql = IF(@ya = 0, "ALTER TABLE mod_log ADD COLUMN undone DATETIME NULL COMMENT 'cuándo se deshizo (migra-176)' AFTER undo_ops", "SELECT 'mod_log.undone ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mod_log' AND COLUMN_NAME = 'undone_by');
SET @sql = IF(@ya = 0, "ALTER TABLE mod_log ADD COLUMN undone_by INT UNSIGNED NULL COMMENT 'quién lo deshizo (migra-176)' AFTER undone", "SELECT 'mod_log.undone_by ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
