-- Cuatro skins nuevas (21/09/2026): Grotesque (a la manera de Mediatize), Phantom (a la manera de Renegados), B/W y Surrealismo.
-- Las dos primeras se asignan a su agregador: /fuente.php?f=mediatize se ve con Grotesque y /fuente.php?f=renegados con Phantom.
SET NAMES utf8mb4;
UPDATE sources SET skin = 'grotesque' WHERE slug = 'mediatize';
UPDATE sources SET skin = 'phantom'   WHERE slug = 'renegados';
