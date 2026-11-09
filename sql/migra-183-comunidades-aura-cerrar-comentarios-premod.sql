-- migra-183 (09/11/2026): tres cosas pedidas por Ángel a raíz de las sugerencias de un usuario.
--  1) Más comunidades para quien tiene más aura: con aura > comm_more_min_aura el tope de comunidades que puede crear cada uno pasa
--     de comm_max_per_user (3) a comm_max_per_user_more (6). community_quota() en lib/app.php. 0 en el umbral = desactivado.
--  2) Cerrar los comentarios de un hilo (admin y moderadores): stories.comments_closed; el hilo sigue viéndose y votándose, pero
--     no admite comentarios nuevos. /cerrar-comentarios.php desde la caja «Editar envío» del hilo.
--  3) Premoderación de los anónimos (premod_anon = 1): sus comentarios, sus mensajes del chat y los hilos que abren en los foros
--     entran como pending_mod y quedan en /moderacion (pestaña IA, como «oculto») hasta que un moderador los apruebe o retire.
--     mod_premod() en lib/moderacion.php. No depende de mod_enabled.
-- Idempotente.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('comm_more_min_aura',     '20', 'Aura (estricta: mayor que) desde la que se pueden crear comm_max_per_user_more comunidades en vez de comm_max_per_user (0 = desactivado)'),
('comm_max_per_user_more', '6',  'Comunidades que puede crear quien supera comm_more_min_aura (y los moderadores)'),
('premod_anon',            '1',  '1 = lo que escriben los anonimos (comentarios, chat, hilos en los foros) queda pendiente de que lo apruebe un moderador; 0 = se publica al momento');

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stories' AND COLUMN_NAME = 'comments_closed');
SET @sql = IF(@ya = 0, "ALTER TABLE stories ADD COLUMN comments_closed TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'comentarios cerrados por moderacion (migra-183)' AFTER comments", "SELECT 'stories.comments_closed ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
