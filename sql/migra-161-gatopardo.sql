-- migra-161 (17/10/2026): Gatopardo (gatopardo.com, revista mexicana de cronica y reportaje) como medio. Es un sitio Webflow sin RSS: se lee
-- su portada con un parser propio (fuentes_parse_gatopardo, lib/fuentes.php; fuentes_parse_rss() lo reconoce por el contenido) y la pagina
-- de cada articulo nuevo da titular, entradilla, foto y fecha (fuente_gatopardo_completar, sources_gatopardo_per_run paginas por pasada).
-- Sin votos ni comentarios. Si el droplet no puede abrir las paginas (403), marcar «lector local» en admin -> Fuentes.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('gatopardo', 'medio', 'Gatopardo', 'https://www.gatopardo.com/', 'Revista mexicana de crónica, reportaje y periodismo narrativo de América Latina', 'es', '',
 COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')),
 'https://www.gatopardo.com/', '', 120, 0);
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('sources_gatopardo_per_run', '6', 'Gatopardo: paginas de articulo que se leen por pasada para completar titular, entradilla y foto (0 = ninguna)');
