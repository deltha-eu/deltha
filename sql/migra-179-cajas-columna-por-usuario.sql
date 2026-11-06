-- migra-179 (06/11/2026): cajas de la columna derecha elegidas y ordenadas por cada usuario. Lo pidio Ángel tras el comentario de un
-- usuario («esas cajitas estarian mejor dando visibilidad a noticias de usuarios, en medios, substacks y radar»): cada cuenta con aura
-- >= side_user_aura elige en /mi-columna que cajas ve y en que orden (users.side_order, claves separadas por comas; NULL = el orden
-- de admin -> Lateral, config.side_order). Cajas nuevas: usuarios, radar, medios_deltha, comentarios_foros, comentarios_noticias.
-- Idempotente.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('side_user_aura', '10', 'Aura desde la que cada usuario elige y ordena las cajas de la columna derecha en /mi-columna (0 = nadie; moderadores y admin, siempre)');

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'side_order');
SET @sql = IF(@ya = 0, "ALTER TABLE users ADD COLUMN side_order VARCHAR(255) NULL DEFAULT NULL COMMENT 'cajas de la columna derecha elegidas por el usuario, en orden (migra-179); NULL = las de config.side_order' AFTER show_follows", "SELECT 'users.side_order ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
