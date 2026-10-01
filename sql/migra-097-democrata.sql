-- migra-097 (01/10/2026): Demócrata al catalogo de Medios (pedido por Angel). El feed NO se pudo comprobar al añadirlo: devuelve 403
-- a las peticiones automaticas desde fuera (como paso con National Geographic); si el lector del droplet tambien lo recibe, se vera el
-- error en admin -> Fuentes. Tema: politica (si no existe, actualidad). No destacado. Se lee cada 60 min.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('democrata', 'medio', 'Demócrata', 'https://www.democrata.es/', 'Información política y parlamentaria', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.democrata.es/uploads/feeds/feed_democrata_es.xml', '', 60, 'ok', 0);
