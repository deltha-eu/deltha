-- Diez fuentes mas (18/09/2026): cuatro medios y seis newsletters (Substack; 4freedoms.es y sumapositiva.com son
-- Substack con dominio propio, el feed es /feed). Ninguna destacada; se eligen en «Mis medios» / «Mis substacks».
-- Nota: el feed de La Pagina Definitiva llevaba sin novedades desde 2018 al añadirla; queda en el catalogo por si vuelve.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('lpd',            'medio',      'La Página Definitiva',   'https://www.lapaginadefinitiva.com/',   'La red está llena de basura. Nosotros somos la prueba', '', 3, 'https://www.lapaginadefinitiva.com/feed/',       '', 60,   0),
('hedonica',       'medio',      'Hedónica',               'https://www.hedonica.es/',              'Revista de libros',                                    '', 3, 'https://www.hedonica.es/feed/',                  '', 60,   0),
('lavanguardia',   'medio',      'La Vanguardia',          'https://www.lavanguardia.com/',         'Diario de Barcelona',                                  '', 1, 'https://www.lavanguardia.com/rss/home.xml',      '', 15,   0),
('eleconomista',   'medio',      'elEconomista',           'https://www.eleconomista.es/',          'Economía',                                             '', 1, 'https://www.eleconomista.es/rss/rss-economia.php', '', 15, 0),
('4freedoms',      'newsletter', 'Four Freedoms',          'https://www.4freedoms.es/',             'La política americana, a ras de suelo desde Connecticut', '', 2, 'https://www.4freedoms.es/feed',               '', 1440, 0),
('juanjosegomez',  'newsletter', 'A general theory of love', 'https://juanjosegomez.substack.com/', 'Juan José Gómez · poemas (Substack)',                  '', 8, 'https://juanjosegomez.substack.com/feed',        '', 1440, 0),
('edans',          'newsletter', 'Enrique Dans',           'https://edans.substack.com/',           'On innovation (Substack)',                             '', 6, 'https://edans.substack.com/feed',                '', 1440, 0),
('ekaizer',        'newsletter', 'Ernesto Ekaizer',        'https://ernestoekaizer.substack.com/',  'Newsletter (Substack)',                                '', 2, 'https://ernestoekaizer.substack.com/feed',       '', 1440, 0),
('pablomalo',      'newsletter', 'Pablo Malo',             'https://pablomalo.substack.com/',       'Newsletter (Substack)',                                '', 4, 'https://pablomalo.substack.com/feed',            '', 1440, 0),
('sumapositiva',   'newsletter', 'Suma Positiva',          'https://www.sumapositiva.com/',         'Newsletter (Substack)',                                '', 6, 'https://www.sumapositiva.com/feed',              '', 1440, 0);
