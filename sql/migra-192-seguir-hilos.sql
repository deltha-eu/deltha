-- migra-192 (11/11/2026): seguir un hilo. Cualquier registrado puede marcar un hilo (tabla thread_follows) y recibe un aviso
-- por cada comentario nuevo que se publique en él (notifications.kind = thread_comment, lib/avisos.php). Opción «Seguir hilos
-- propios» en el perfil (users.follow_own, por defecto 0): con ella activada, los hilos que abre el usuario se marcan solos.
-- Idempotente.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS thread_follows (
  `user_id` int unsigned NOT NULL,
  `story_id` int unsigned NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`story_id`),
  KEY `idx_tf_story` (`story_id`),
  CONSTRAINT `fk_tf_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'follow_own');
SET @sql = IF(@ya = 0, "ALTER TABLE users ADD COLUMN follow_own TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = los hilos que abre se marcan solos para seguimiento (migra-192)' AFTER show_follows", "SELECT 'users.follow_own ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'kind' AND COLUMN_TYPE LIKE '%thread_comment%');
SET @sql = IF(@ya = 0, "ALTER TABLE notifications MODIFY kind ENUM('mention_comment','reply_comment','mention_chat','reply_chat','dm','mention_story','thread_comment') NOT NULL", "SELECT 'notifications.kind ya admite thread_comment'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
