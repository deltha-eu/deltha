-- migra-098 (01/10/2026): lector local de feeds. Algunas webs (Politico.es, Democrata) rechazan la IP del droplet (403/503 a
-- cualquier navegador simulado: vetan los centros de datos). Con sources.relay = 1 el servidor deja de leer esa fuente y la lee el
-- worker local/lector/lector.py desde el PC de la redaccion (TIBERIO), que descarga el feed y las fotos y se los entrega a
-- /api/lector.php. El servidor nunca llama al PC. Se marca en admin -> Fuentes («lector local»).
SET NAMES utf8mb4;

ALTER TABLE sources ADD COLUMN relay TINYINT(1) NOT NULL DEFAULT 0 AFTER featured;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('relay_worker_seen', '', 'Ultima vez que el lector local de feeds (local/lector) hablo con el servidor');

-- las dos que lo motivaron (por la direccion del feed, sea cual sea su slug)
UPDATE sources SET relay = 1, last_error = NULL WHERE feed_portada LIKE '%politico.es/%' OR feed_portada LIKE '%democrata.es/%';
