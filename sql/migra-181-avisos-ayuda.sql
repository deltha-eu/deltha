-- migra-181 (07/11/2026): avisos de ayuda (los <p class="notice ayuda"> grises que explican cada pagina) cerrables con una × y
-- opcion en el perfil de apagarlos o volver a encenderlos todos. users.help_hidden: '' = todos; '*' = ninguno (apagados en el perfil);
-- si no, claves de los cerrados separadas por comas. Sin cuenta, cookie ddd_ayuda con lo mismo. Idempotente.
SET NAMES utf8mb4;
SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'help_hidden');
SET @sql = IF(@ya = 0, "ALTER TABLE users ADD COLUMN help_hidden VARCHAR(1500) NOT NULL DEFAULT '' COMMENT 'avisos de ayuda cerrados (migra-181): claves separadas por comas; * = todos apagados' AFTER side_order", "SELECT 'users.help_hidden ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
