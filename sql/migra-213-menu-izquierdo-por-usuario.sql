-- migra-213 (24/11/2026), pedido por Ángel: «Quiero que la barra lateral izquierda se pueda reordenar como la derecha. Mismas
--  condiciones». Cada pieza del menú de la izquierda (las pestañas, «Publicar», los desplegables de skin, aspecto, sin distracciones,
--  modo hacker e idioma, y los bloques «Mis fuentes» y «Todas las fuentes») se puede mover u ocultar desde «Mi barra lateral»
--  (/mi-columna), con la misma regla que las cajas de la columna derecha (migra-179): aura >= config.side_user_aura; moderadores y
--  admin, siempre.
--  · users.rail_order: las claves en orden, separadas por comas, con «-» delante las ocultas; NULL = el orden de Deltha.
--  Solo cambia el menú de escritorio (el rail); las pestañas y el muelle del móvil siguen igual. Idempotente.
SET NAMES utf8mb4;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'rail_order');
SET @sql = IF(@ya = 0, "ALTER TABLE users ADD COLUMN rail_order VARCHAR(400) NULL DEFAULT NULL COMMENT 'menu de la izquierda elegido por el usuario: claves en orden, con - delante las ocultas (migra-213); NULL = el de Deltha' AFTER side_order", "SELECT 'users.rail_order ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
