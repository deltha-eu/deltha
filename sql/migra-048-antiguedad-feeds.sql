-- Antiguedad maxima de lo que entra por RSS: El Pais / Babelia cuelan articulos de hace anos entre los nuevos («hace 2156 d»).
-- Los items con pub_date anterior a sources_max_age_days se descartan al leer; los ya guardados que dejen de verse caducan solos
-- (sources_keep_days). 0 = sin limite. Ademas se borran ya los que hay guardados con mas de 45 dias en medios y newsletters.
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('sources_max_age_days', '45', 'Fuentes: se descartan al leer los items con fecha de mas de N dias (0 = sin limite)');
DELETE i FROM source_items i JOIN sources s ON s.source_id = i.source_id WHERE s.kind IN ('medio', 'newsletter') AND i.pub_date < NOW() - INTERVAL 45 DAY;
