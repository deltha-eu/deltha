-- migra-099 (01/10/2026): ajustes de datos para las tres webs que vetan al servidor (pedido por Angel; Claude no tiene acceso
-- directo a la base del droplet, asi que va como migracion y la aplica el despliegue).
SET NAMES utf8mb4;

-- 1) Cultura Inquieta: Cloudflare veta la IP del droplet en las paginas de las noticias (el feed no trae imagen), asi que salian
--    sin foto. Pasa al lector local (TIBERIO), que trae el feed y las fotos. Politico.es y Democrata ya lo estan (migra-098).
UPDATE sources SET relay = 1, last_error = NULL
 WHERE feed_portada LIKE '%culturainquieta.com%' OR feed_portada LIKE '%politico.es/%' OR feed_portada LIKE '%democrata.es/%';

-- 2) «Politico ES» (slug politico-es) apunto un tiempo por error al feed de politico.eu: fuera las noticias que no son de politico.es.
DELETE i FROM source_items i JOIN sources s ON s.source_id = i.source_id
 WHERE s.slug = 'politico-es' AND i.url NOT LIKE '%politico.es%';

-- 3) Si la migra-096 llego a crear una segunda fuente «Politico.es» (slug politico) con el mismo feed, sobra: se borra solo si
--    existe la buena (politico-es). Sus noticias y los «seguir» caen en cascada.
DELETE s FROM sources s JOIN sources o ON o.slug = 'politico-es'
 WHERE s.slug = 'politico' AND s.feed_portada LIKE '%politico.es/%';
