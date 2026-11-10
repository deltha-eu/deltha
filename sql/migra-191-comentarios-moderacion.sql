-- migra-191 (10/11/2026): conversación de los moderadores sobre cada elemento pendiente de la pestaña IA de /moderacion.
-- Antes de decidir, varios moderadores dejan mensajes en el propio elemento para ponerse de acuerdo. La conversación se
-- guarda por elemento (item_type + ref_id), así que sigue disponible cuando la decisión ya está en el registro (mod_log),
-- donde se lee en solo lectura.
-- mod_log gana item_type y ref_id para poder enlazar cada acción con la conversación de su elemento.

CREATE TABLE IF NOT EXISTS `mod_comments` (
  `comment_id` int unsigned NOT NULL AUTO_INCREMENT,
  `item_type` enum('story','comment','chat') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'story',
  `ref_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `body` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `idx_item` (`item_type`,`ref_id`,`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mod_log' AND COLUMN_NAME = 'item_type');
SET @sql := IF(@hay = 0,
    'ALTER TABLE `mod_log` ADD COLUMN `item_type` enum(''story'',''comment'',''chat'') COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT ''elemento del que trata la acción (migra-191)'' AFTER `action`',
    'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mod_log' AND COLUMN_NAME = 'ref_id');
SET @sql := IF(@hay = 0,
    'ALTER TABLE `mod_log` ADD COLUMN `ref_id` int unsigned NULL DEFAULT NULL COMMENT ''id del elemento (migra-191)'' AFTER `item_type`',
    'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
