-- Topes del lector de fuentes por pasada del cron: con 50 substacks nuevos vencidos a la vez, una sola pasada descargaba y convertia
-- cientos de fotos, saturaba la CPU y la web respondia 524 (Cloudflare). Ahora cada minuto se leen como mucho sources_per_tick fuentes
-- y no se empieza ninguna mas pasados sources_tick_seconds; el resto en los minutos siguientes, las mas atrasadas primero.
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('sources_per_tick',     '3',  'Fuentes: como mucho N fuentes leidas en cada pasada del cron (cada minuto); las demas esperan a la siguiente'),
('sources_tick_seconds', '40', 'Fuentes: pasados N segundos en una pasada del cron no se empieza a leer ninguna fuente mas');
