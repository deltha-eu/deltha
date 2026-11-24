-- migra-212 (24/11/2026), dos cosas pedidas por Ángel:
--  1. «Los hilos en los foros no caducan, déjalos todos abiertos.» Los hilos de foro (stories.content_type = 'article') ya no se
--     archivan en el ciclo (lib/ciclo.php) y los que estaban archivados vuelven a abrirse. Para que al reabrirlos no le salgan de
--     golpe como «sin leer» a quien sigue el foro, antes se dan por leídos (story_reads) para sus seguidores. Los comentarios que
--     un moderador cerró a mano (stories.comments_closed, migra-183) siguen cerrados: eso es otra cosa.
--  2. «Permite que los usuarios comenten las news pero solo en Deltha. Los hilos comentados se mantienen, los no comentados de
--     Usenet desaparecen a la semana.» Tabla usenet_comments: comentarios de usuarios registrados bajo un hilo de /usenet, que se
--     quedan en Deltha (no se envían a Usenet). usenet_msgs.dcomments los cuenta en el mensaje que abre el hilo. La poda diaria
--     (usenet_podar, usenet_keep_days, que pasa de 0 a 7) borra los hilos sin actividad desde hace una semana salvo los comentados.
--     ip_log.kind admite 'usenet' (el comentario se apunta como cualquier otra publicación, migra-193).
-- Idempotente.
-- Corregida el mismo 24/11/2026 (aviso de SLM): la clave foránea de usenet_comments se llamaba fk_uc_user, que ya existe en
--  user_communities (migra-142). En MySQL 8 el nombre de una FK es único en toda la base, no por tabla: ERROR 1826 y la migración
--  no pasaba de ahí. Ahora es fk_usenet_comments_user. (En MariaDB no fallaba, y por eso no saltó en las pruebas.)
SET NAMES utf8mb4;

-- 1. foros: todos los hilos abiertos
INSERT IGNORE INTO story_reads (user_id, story_id, read_at)
SELECT uf.user_id, s.story_id, NOW() FROM stories s JOIN user_forums uf ON uf.forum_id = s.forum_id
 WHERE s.content_type = 'article' AND s.status = 'archived';
UPDATE stories SET status = 'nuevas' WHERE content_type = 'article' AND status = 'archived';

-- 2. comentarios de Deltha en los hilos de Usenet
CREATE TABLE IF NOT EXISTS usenet_comments (
  ucomment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  root_id     BIGINT UNSIGNED NOT NULL,                 -- usenet_msgs.msg_id del mensaje que abre el hilo
  group_id    INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NULL,                        -- NULL = cuenta dada de baja (lo escrito se conserva anonimo, como en comments)
  content     TEXT NOT NULL,
  status      ENUM('ok','rejected') NOT NULL DEFAULT 'ok',   -- rejected = retirado por su autor o por moderacion
  created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ucomment_id),
  KEY idx_uc_hilo (root_id, status, created),
  KEY idx_uc_user (user_id, created),
  CONSTRAINT fk_usenet_comments_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usenet_msgs' AND COLUMN_NAME = 'dcomments');
SET @sql = IF(@ya = 0, "ALTER TABLE usenet_msgs ADD COLUMN dcomments INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'comentarios de Deltha visibles (solo en el que abre hilo; migra-212)' AFTER replies", "SELECT 'usenet_msgs.dcomments ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ip_log' AND COLUMN_NAME = 'kind' AND COLUMN_TYPE LIKE '%''usenet''%');
SET @sql = IF(@ya = 0, "ALTER TABLE ip_log MODIFY COLUMN kind ENUM('registro','login','story','comment','chat','usenet') COLLATE utf8mb4_unicode_ci NOT NULL", "SELECT 'ip_log.kind ya admite usenet'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE config SET cvalue = '7' WHERE ckey = 'usenet_keep_days' AND cvalue = '0';
UPDATE config SET descr = 'Usenet: dias que se conservan los hilos sin actividad; pasados, se borran salvo los que tienen comentarios de Deltha, que se quedan; 0 = no se borra nada'
 WHERE ckey = 'usenet_keep_days';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('usenet_comments', '1', 'Usenet (migra-212): 1 = los usuarios registrados pueden comentar los hilos de /usenet; los comentarios se quedan en Deltha, no se envian a Usenet. 0 = solo lectura');
