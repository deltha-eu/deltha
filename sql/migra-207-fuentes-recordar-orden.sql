-- migra-207 (21/11/2026): «Todas las fuentes» (/fuentes) recuerda el último orden elegido (última noticia, seguidores, más nuevas,
--  top hoy/semana/mes), pedido por Ángel. Se guarda en la cuenta (users.fuentes_orden; vacío = última noticia), no en una cookie:
--  quien no ha entrado sigue viendo el orden de siempre. La 206 es la de Usenet (PR #167). Idempotente.
SET NAMES utf8mb4;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'fuentes_orden');
SET @sql = IF(@ya = 0, "ALTER TABLE users ADD COLUMN fuentes_orden VARCHAR(12) NOT NULL DEFAULT '' COMMENT 'ultimo orden elegido en /fuentes; vacio = ultima noticia (migra-207)' AFTER follow_own", "SELECT 'users.fuentes_orden ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
