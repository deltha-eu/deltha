-- migra-196 (12/11/2026): caja «Últimos medios añadidos» de la columna derecha (side_medios_nuevos, pedida por Ángel): los diez
-- últimos medios, substacks y canales dados de alta en el catálogo público.
--  · sources.listed_at: cuándo entró la fuente en el catálogo. La pone fuente_alta_marca() al crearla desde admin o moderación y
--    al publicar la que un usuario se había añadido para sí (listed 0 -> 1). Las anteriores se quedan en NULL: no se sabe su
--    fecha, y la caja las ordena detrás, por orden de creación (source_id).
--  · La caja no entra sola en la columna: se marca en admin -> Lateral, o cada usuario en «Mi barra lateral».
-- Idempotente.
SET NAMES utf8mb4;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sources' AND COLUMN_NAME = 'listed_at');
SET @sql = IF(@ya = 0, "ALTER TABLE sources ADD COLUMN listed_at DATETIME NULL DEFAULT NULL COMMENT 'cuando entro en el catalogo publico (migra-196); NULL = anterior a la migracion'", "SELECT 'sources.listed_at ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
