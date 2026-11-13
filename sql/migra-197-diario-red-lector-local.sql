-- migra-197 (13/11/2026): Diario Red pasa al lector local (pedido por Ángel). Tiene RSS, pero desde el droplet la lectura da error:
-- veto a la IP del centro de datos, como Politico.es, Demócrata o Cultura Inquieta (migra-098/099). Con relay = 1 el cron del
-- servidor deja de leerla y la trae el lector de TIBERIO (local/lector), que entrega el feed y las fotos por /api/lector.php.
-- La fuente se dio de alta desde admin (no hay migración con su slug), así que se localiza por el dominio del feed o de la web.
-- Idempotente.
SET NAMES utf8mb4;

UPDATE sources SET relay = 1, last_error = NULL
 WHERE feed_portada LIKE '%diario.red/%' OR feed_nuevas LIKE '%diario.red/%' OR home LIKE '%diario.red%'
    OR feed_portada LIKE '%diariored.canalred.tv/%' OR home LIKE '%diariored.canalred.tv%';
