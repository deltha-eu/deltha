-- Medios: mas medios en el catalogo, y «destacado» = los que ven quienes no han elegido (anonimos y cuentas sin eleccion); se marca en admin -> Fuentes.
SET NAMES utf8mb4;

ALTER TABLE sources ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
UPDATE sources SET featured = 1 WHERE slug IN ('jotdown', 'mercurio');

INSERT IGNORE INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('anfibia',        'medio', 'Revista Anfibia',      'https://www.revistaanfibia.com/',      'Crónicas y ensayos desde Buenos Aires', '', 3, 'https://www.revistaanfibia.com/feed/',       '', 30, 1),
('natgeo',         'medio', 'National Geographic',  'https://www.nationalgeographic.com.es/', 'Ciencia, naturaleza e historia',      '', 4, 'https://www.nationalgeographic.com.es/rss',  '', 30, 0),
('bbc',            'medio', 'BBC Mundo',            'https://www.bbc.com/mundo',            'Noticias internacionales en español',   '', 1, 'https://feeds.bbci.co.uk/mundo/rss.xml',     '', 15, 0),
('cgtn',           'medio', 'CGTN Español',         'https://espanol.cgtn.com/',            'Televisión china en español',           '', 1, 'https://espanol.cgtn.com/rss',               '', 30, 0),
('pijamasurf',     'medio', 'Pijama Surf',          'https://pijamasurf.com/',              'Información alternativa',               '', 3, 'https://pijamasurf.com/feed/',               '', 30, 0),
('culturainquieta','medio', 'Cultura Inquieta',     'https://culturainquieta.com/',         'Arte, música y cultura',                '', 3, 'https://culturainquieta.com/feed/',          '', 30, 0),
('hipertextual',   'medio', 'Hipertextual',         'https://hipertextual.com/',            'Tecnología y ciencia',                  '', 6, 'https://hipertextual.com/feed',              '', 15, 0);
