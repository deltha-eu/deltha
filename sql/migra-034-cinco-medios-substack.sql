-- Cinco medios por usuario (antes tres) y seis newsletters (Substack) en el catalogo, leidas una vez al dia.
SET NAMES utf8mb4;
UPDATE config SET cvalue = '5' WHERE ckey = 'media_max_per_user';
INSERT IGNORE INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('jorgecarrion',   'medio', 'Jorge Carrión',        'https://jorgecarrion21.substack.com/',  'Newsletter (Substack)', '', 3, 'https://jorgecarrion21.substack.com/feed',  '', 1440, 0),
('periodismo',     'medio', 'Periodismo',           'https://periodismo.substack.com/',      'Newsletter (Substack)', '', 1, 'https://periodismo.substack.com/feed',      '', 1440, 0),
('sergioparra',    'medio', 'Sergio Parra',         'https://sergioparra.substack.com/',     'Newsletter (Substack)', '', 4, 'https://sergioparra.substack.com/feed',     '', 1440, 0),
('error500',       'medio', 'Error500',             'https://www.error500.net/',             'Antonio Ortiz · newsletter', '', 6, 'https://www.error500.net/feed',          '', 1440, 0),
('uriondo',        'medio', 'Uriondo',              'https://uriondo.substack.com/',         'Newsletter (Substack)', '', 1, 'https://uriondo.substack.com/feed',         '', 1440, 0),
('martinsacristan','medio', 'Martín Sacristán',     'https://martinsacristan.substack.com/', 'Newsletter (Substack)', '', 3, 'https://martinsacristan.substack.com/feed', '', 1440, 0);
