-- El rail («Mis agregadores») muestra como maximo tres agregadores, como los otros dos grupos: los destacados en admin.
-- Destacados: Meneame, Mediatize y Renegados; Areopago y Burbuja quedan en el catalogo (/medios.php?k=agregador) y en la columna derecha.
SET NAMES utf8mb4;
UPDATE sources SET featured = IF(slug IN ('meneame', 'mediatize', 'renegados'), 1, 0) WHERE kind = 'agregador';
