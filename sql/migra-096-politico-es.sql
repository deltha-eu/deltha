-- migra-096 (01/10/2026): Politico.es al catalogo de Medios (pedido por Angel). Feed WordPress comprobado ese dia: RSS 2.0 valido,
-- 10 items, idioma es, sin imagen en los metadatos (la foto saldra del <img> de content:encoded o de la og:image). Tema: politica
-- (si no existe, actualidad). No destacado; se sigue con el boton «Seguir». Se lee cada 60 min.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('politico', 'medio', 'Politico.es', 'https://politico.es/', 'Actualidad política diaria: geopolítica, noticias, políticos y sociedad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://politico.es/feed/', '', 60, 'ok', 0);
