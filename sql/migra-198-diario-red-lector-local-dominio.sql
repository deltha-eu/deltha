-- migra-198 (13/11/2026): corrige la migra-197. Diario Red está en diario-red.com (feed https://www.diario-red.com/rss/), no en
-- diario.red, así que la 197 no encontraba ninguna fila y no marcaba nada. Esta la pasa de verdad al lector local (relay = 1):
-- el cron del servidor deja de leerla y la trae el lector de TIBERIO (local/lector).
-- Idempotente.
SET NAMES utf8mb4;

UPDATE sources SET relay = 1, last_error = NULL
 WHERE feed_portada LIKE '%diario-red.com/%' OR feed_nuevas LIKE '%diario-red.com/%' OR home LIKE '%diario-red.com%';
