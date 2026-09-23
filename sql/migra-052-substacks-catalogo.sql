-- Catalogo de Substacks (newsletters): 50 fuentes, lista de Angel del 23/09/2026. Tipo newsletter, en español, leidas cada 24 h.
-- Las que ya estaban (jorgecarrion, sergioparra, error500, uriondo, martinsacristan, 4freedoms, juanjosegomez, edans, ekaizer,
-- pablomalo, sumapositiva y «periodismo», que pasa a llamarse periodismo-digital) se actualizan: nombre, web, RSS, descripcion y tema;
-- conservan destacado, intervalo, estado y a quien las tenga elegidas. El tema al enviar sale de la categoria de la lista: se usa el
-- tema especifico si existe (economia, internacional, medios, literatura, sociedad) y si no el general (politica, cultura, actualidad…).
SET NAMES utf8mb4;
UPDATE IGNORE sources SET slug = 'periodismo-digital' WHERE slug = 'periodismo';   -- IGNORE: si ya existiera periodismo-digital, no hace nada
-- Política internacional
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('4freedoms', 'newsletter', 'Four Freedoms', 'https://www.4freedoms.es/', 'Roger Senserrich. Política y sociedad estadounidense desde Connecticut', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'internacional'), (SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.4freedoms.es/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Tecnología
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('martinsacristan', 'newsletter', 'Martín Sacristán / Los hilos del futuro', 'https://martinsacristan.substack.com/', 'Tecnología y su influencia sobre la sociedad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://martinsacristan.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- IA / Cultura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('arjonauta', 'newsletter', 'El Arjonauta', 'https://elarjonauta.substack.com/', 'Daniel Arjona. IA, cultura, filosofía y tecnología', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://elarjonauta.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Actualidad / Política
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('ekaizer', 'newsletter', 'Ernesto Ekaizer / Analítica', 'https://ernestoekaizer.substack.com/', 'Información y análisis judicial, económico y político', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://ernestoekaizer.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Ciencia
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('sergioparra', 'newsletter', 'Sergio Parra / Sapienciología', 'https://sergioparra.substack.com/', 'Ciencia, pensamiento crítico y divulgación', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://sergioparra.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Ciencia
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('jose-ramon-alonso', 'newsletter', 'El blog de José Ramón Alonso', 'https://jralonso.es/', 'Neurociencia, cerebro, psicología y divulgación científica', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://jralonso.es/feed/', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Tecnología
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('edans', 'newsletter', 'Enrique Dans', 'https://edans.substack.com/', 'Innovación, IA, internet, sociedad y economía digital', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://edans.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Medios
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('periodismo-digital', 'newsletter', 'Periodismo Digital', 'https://periodismo.substack.com/', 'Miquel Pellicer. Periodismo, medios, IA y transformación digital', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://periodismo.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Literatura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('juanjosegomez', 'newsletter', 'A general theory of love', 'https://juanjosegomez.substack.com/', 'Juan José Gómez Cadenas. Poesía y literatura', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://juanjosegomez.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Cultura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('ariana-basciani', 'newsletter', 'Cerrar sesión, abrir un libro', 'https://arianabasciani.substack.com/', 'Cultura, literatura, periodismo e internet', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://arianabasciani.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Psicología
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('pablomalo', 'newsletter', 'Pablo Malo', 'https://pablomalo.substack.com/', 'Psicología evolucionista, moral, filosofía y sociedad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://pablomalo.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Negocios
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('sumapositiva', 'newsletter', 'Suma Positiva', 'https://www.sumapositiva.com/', 'Samuel Gil. Tecnología, empresa, inversión, salud y modelos mentales', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.sumapositiva.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Tecnología
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('error500', 'newsletter', 'Error500', 'https://www.error500.net/', 'Antonio Ortiz. Tecnología, internet, innovación e inteligencia artificial', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.error500.net/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Cultura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('jorgecarrion', 'newsletter', 'Jorge Carrión / Solaris', 'https://jorgecarrion21.substack.com/', 'Literatura, cultura, tecnología y recomendaciones', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://jorgecarrion21.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Tecnología / Sociedad
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('uriondo', 'newsletter', 'Uriondo', 'https://uriondo.substack.com/', 'Movilidad, tecnología, sociedad y reflexiones personales', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://uriondo.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Medios
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('tendencias', 'newsletter', 'Tendenci@s', 'https://tendencias.substack.com/', 'Ismael Nafría. Medios digitales, periodismo, audiencias y negocio editorial', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://tendencias.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Medios
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('fleetstreet', 'newsletter', 'Fleet Street', 'https://fleetstreet.substack.com/', 'Mar Manrique. Periodismo, medios y cultura digital', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://fleetstreet.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Tecnología
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('xataka', 'newsletter', 'Xataka', 'https://xataka.substack.com/', 'Resumen diario de tecnología, ciencia e internet', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://xataka.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Ideas
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('aprendizaje-infinito', 'newsletter', 'Aprendizaje Infinito', 'https://www.aprendizajeinfinito.com/', 'Sergio San Juan. Aprendizaje, ideas, libros y pensamiento', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.aprendizajeinfinito.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Economía
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('kapital', 'newsletter', 'Kapital', 'https://joantubau.substack.com/', 'Joan Tubau. Dinero, inversión, psicología y filosofía', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://joantubau.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- IA
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('mafia-ia', 'newsletter', 'Mafia IA', 'https://aimafia.substack.com/', 'Inteligencia artificial aplicada a profesionales y emprendedores', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://aimafia.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Programación
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('webreactiva', 'newsletter', 'Web Reactiva', 'https://webreactiva.substack.com/', 'Daniel Primo. Programación, desarrollo, herramientas e IA', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://webreactiva.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Emprendimiento
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('movidas-emprender', 'newsletter', 'Movidas de emprender', 'https://movidasdeemprender.substack.com/', 'Chus Naharro. Emprendimiento, newsletters y negocio digital', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://movidasdeemprender.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Ciencia / Salud
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('gente-interesante', 'newsletter', 'Gente Interesante', 'https://www.oriolroda.com/', 'Oriol Roda. Ciencia, salud, pensamiento crítico y entrevistas', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.oriolroda.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Economía
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('pobre-millennial', 'newsletter', 'Pobre Millennial', 'https://pobremillenial.substack.com/', 'Finanzas personales, inversión, empresas y mercados', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://pobremillenial.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Diseño / Cultura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('de-ulm-a-cadiz', 'newsletter', 'De Ulm a Cádiz', 'https://www.deulmacadiz.com/', 'Javier Cañada. Diseño, tecnología, cultura y sociedad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.deulmacadiz.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Cultura / Diseño
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('superfluor', 'newsletter', 'Superfluor', 'https://superfluor.substack.com/', 'Iván Leal. Diseño, historias, lenguaje y curiosidades', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://superfluor.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Cultura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('el-guateque', 'newsletter', 'El Guateque', 'https://elguateque.substack.com/', 'Paloma Herce. Cultura, viajes, estilo, libros y vida cotidiana', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://elguateque.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Sociedad
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('pretty-in-pretty-out', 'newsletter', 'Pretty in, pretty out', 'https://palomaabad.substack.com/', 'Paloma Abad. Belleza, bienestar, sociedad y cultura', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'sociedad'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://palomaabad.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Literatura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('leer-por-leer', 'newsletter', 'Leer por leer', 'https://amayaascunce.substack.com/', 'Amaya Ascunce. Libros, literatura y recomendaciones', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://amayaascunce.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Curación
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('diario-substack', 'newsletter', 'Diario de Substack', 'https://columnas.substack.com/', 'Selección y reseña de artículos publicados en Substack en español', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://columnas.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- IA
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('para-todo-ia', 'newsletter', 'Para todo IA', 'https://iaparatodo.substack.com/', 'Juanan y Ángel. Herramientas, usos y aprendizaje práctico de IA', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://iaparatodo.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Economía / Sociedad
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('abundancia', 'newsletter', 'Abundancia', 'https://abundancia.maria-alvarez.com/', 'María Álvarez. Economía, tecnología, historia y sociedad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://abundancia.maria-alvarez.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Política
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('infopolitica', 'newsletter', 'InfoPolítica', 'https://www.infopolitica.es/', 'Eduardo Bayón. Política, geopolítica, sociedad y tecnología', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.infopolitica.es/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Geopolítica
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('cadenas-papel', 'newsletter', 'Las Cadenas de Papel', 'https://lascadenasdepapel.substack.com/', 'Dinero, poder, geopolítica, China, EE. UU. e IA', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'internacional'), (SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://lascadenasdepapel.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- IA / Política
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('estrategia-ia', 'newsletter', 'estrategIA', 'https://estrategiabyaleph.substack.com/', 'Inteligencia artificial aplicada a política, instituciones y gobierno', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://estrategiabyaleph.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Ciencia
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('bitacora-beagle', 'newsletter', 'La Bitácora del Beagle', 'https://bitacorabeagle.substack.com/', 'Julio Rodríguez. Genética, biomedicina y divulgación científica', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://bitacorabeagle.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Ciencia
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('nuevas-ciencias', 'newsletter', 'Nuevas Ciencias', 'https://nuevasciencias.substack.com/', 'Cosmos, evolución, mente, física, biología e inteligencia artificial', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://nuevasciencias.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Medios / Internet
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('content-curators', 'newsletter', 'Content Curators', 'https://jguallar.substack.com/', 'Javier Guallar. Curación de contenidos, comunicación e internet', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://jguallar.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Comunicación
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('las-imperdibles', 'newsletter', '#LasImperdibles', 'https://cristinaaced.substack.com/', 'Cristina Aced. Comunicación, reputación, tendencias digitales e IA', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://cristinaaced.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- IA / Medios
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('parentesis-media', 'newsletter', 'Paréntesis MEDia', 'https://parentesismedia.substack.com/', 'Inteligencia artificial, tecnología y medios', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://parentesismedia.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Europa
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('nexo-europa', 'newsletter', 'Nexo Europa', 'https://nexoeuropa.substack.com/', 'Nacho Alarcón. Unión Europea, política, economía y geopolítica', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'internacional'), (SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://nexoeuropa.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Cultura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('cinco-cosas', 'newsletter', '5 cosas que descubrí esta semana', 'https://santiaraujo.substack.com/', 'Santi Araújo. Arte, tecnología, libros, música y curiosidades', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://santiaraujo.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Tecnología
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('deepteches', 'newsletter', 'DeepTechES', 'https://deepteches.substack.com/', 'Mike Ivars. Deep tech, ciencia, startups e inversión tecnológica en España', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://deepteches.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Producto / Diseño
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('rodobo', 'newsletter', 'Rodobo', 'https://www.rodobo.es/', 'Juan Rodríguez Talavera. Producto, diseño, tecnología y trabajo', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.rodobo.es/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Tecnología
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('gemba', 'newsletter', 'Gemba', 'https://newsletter.gemba.es/', 'José Ramón Pérez Agüera. Producto, ingeniería, buscadores e inteligencia artificial', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://newsletter.gemba.es/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Negocios
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('multiversial', 'newsletter', 'MultiVersial', 'https://www.multiversial.es/', 'Carlos Molina. Negocios digitales, tecnología, startups e IA', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.multiversial.es/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Economía
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('margen-correcto', 'newsletter', 'El Margen Correcto', 'https://elmargencorrecto.substack.com/', 'Santiago Calvo. Economía española explicada mediante datos', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://elmargencorrecto.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Literatura
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('lectuletter', 'newsletter', 'Lectuletter', 'https://lecturalia.substack.com/', 'Lecturalia. Libros, novedades editoriales y cultura literaria', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://lecturalia.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
-- Libros / Medios
INSERT INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured) VALUES
('senalador-clipping', 'newsletter', 'Señalador Clipping ES', 'https://clippingeditoriales.substack.com/', 'Clipping diario de noticias del sector editorial español', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://clippingeditoriales.substack.com/feed', '', 1440, 'ok', 0)
ON DUPLICATE KEY UPDATE kind = 'newsletter', name = VALUES(name), home = VALUES(home), descr = VALUES(descr), lang = 'es', topic_id = VALUES(topic_id), feed_portada = VALUES(feed_portada);
