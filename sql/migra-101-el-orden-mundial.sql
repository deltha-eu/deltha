-- migra-101 (01/10/2026): alta de El Orden Mundial. No estaba en sources (consulta de Angel en el droplet, sin filas). El servidor
-- lee su feed sin problema (http_get: HTTP 200, 10 noticias), asi que no va por el lector local.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured, relay) VALUES
('elordenmundial', 'medio', 'El Orden Mundial', 'https://elordenmundial.com/', 'Geopolítica y actualidad internacional explicadas con mapas y análisis', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'geopolitica'), (SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://elordenmundial.com/feed/', '', 60, 'ok', 0, 0);
