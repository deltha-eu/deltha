-- Tres agregadores mas (21/09/2026): Mediatize (derivado de Meneame: su RSS lleva link_id, votos, negativos y comentarios en el espacio
-- de nombres mediatize: y declara los modulos estandar con https://; el lector ya lo entiende), Renegados (RSS generico: sin votos, se
-- muestra el numero de comentarios; portada = rss/published, cola = rss/queued) y Burbuja (foro XenForo, un solo feed de temas nuevos
-- con slash:comments; sin votos, con la skin de foro como Areopago).
-- Tardigram y Forocoches no entran: no se ha podido comprobar que tengan RSS (robots.txt bloquea al lector y Forocoches no expone feed).
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('mediatize', 'agregador', 'Mediatize', 'https://www.mediatize.info/', 'Agregador de noticias alternativo · portada, cola y votos', '', NULL,
 'https://www.mediatize.info/rss', 'https://www.mediatize.info/rss?status=queued', 15, 0),
('renegados', 'agregador', 'Renegados', 'https://app.renegados.es/es', 'Agregador «ético y transparente» · publicadas y cola', '', NULL,
 'https://repostea.renegados.es/rss/published', 'https://repostea.renegados.es/rss/queued', 15, 0),
('burbuja',   'agregador', 'Burbuja',   'https://www.burbuja.info/inmobiliaria/', 'Foro de economía, política y actualidad · temas nuevos', 'comunidad', NULL,
 'https://www.burbuja.info/inmobiliaria/forums/-/index.rss', '', 30, 0);
