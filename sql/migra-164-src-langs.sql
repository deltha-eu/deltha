-- migra-164: elegir varios idiomas para las fuentes y los envios (perfil y control rapido de /fuentes y /mis-fuentes).
-- users.src_mylang sigue siendo el interruptor (1 = filtrar por idioma) y users.src_langs guarda la lista elegida («es,en»).
-- Lista vacia = el idioma de la interfaz (lang()), como la casilla antigua; asi no hace falta rellenar las cuentas existentes
-- (las que tienen users.lang vacio usan el idioma del navegador y no se pueden resolver desde una migracion).
SET NAMES utf8mb4;

-- MySQL no tiene «ADD COLUMN IF NOT EXISTS» (solo MariaDB): se comprueba antes en information_schema.
SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'src_langs');
SET @sql = IF(@ya = 0, "ALTER TABLE users ADD COLUMN src_langs VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'idiomas elegidos («es,en»); vacio = el de la interfaz' AFTER src_mylang", "SELECT 'users.src_langs ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
