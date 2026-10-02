-- migra-108 (02/10/2026): cada usuario puede añadirse fuentes que no estan en el catalogo pegando su direccion (medio, substack o canal
-- de YouTube) en sus pestañas «Mis medios / Mis substacks / Mis canales». Si el feed se lee, la fuente se crea al momento y el la sigue,
-- pero queda FUERA del catalogo publico (sources.listed = 0) hasta que un moderador la aprueba en /moderacion.php#fuentes. Si no se
-- lee, queda como propuesta pendiente y un moderador la añade a mano.
--   sources.listed    1 = en el catalogo publico (menus, /medios.php, lateral, buscador); 0 = solo la ven quienes la siguen
--   sources.added_by  quien la añadio (NULL = admin o moderacion)
--   source_add_aura     aura minima (>=) para añadirse fuentes asi; moderadores y admin, siempre
--   source_add_per_day  fuentes que una persona puede añadir en 24 horas
SET NAMES utf8mb4;
ALTER TABLE sources ADD COLUMN listed TINYINT(1) NOT NULL DEFAULT 1, ADD COLUMN added_by INT UNSIGNED DEFAULT NULL;
ALTER TABLE source_proposals MODIFY kind ENUM('medio','newsletter','youtube') NOT NULL DEFAULT 'medio';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('source_add_aura',    '5',  'Aura minima (mayor o igual) para añadirse fuentes fuera del catalogo pegando su direccion en «Mis medios»; moderadores y admin, siempre'),
('source_add_per_day', '10', 'Fuentes que un usuario puede añadirse en 24 horas');
