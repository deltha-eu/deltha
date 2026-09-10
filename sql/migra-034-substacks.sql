-- Dos grupos elegibles por usuario: «Mis medios» (3, tipo medio) y «Mis substacks» (3, tipo newsletter).
-- Seis newsletters (Substack) en el catalogo, leidas una vez al dia.
SET NAMES utf8mb4;
ALTER TABLE sources MODIFY kind ENUM('agregador','medio','newsletter') NOT NULL DEFAULT 'medio';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('news_max_per_user', '3', 'Newsletters (Substack) que puede elegir cada usuario en su perfil («Mis substacks»)');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('jorgecarrion',   'newsletter', 'Jorge Carrión',    'https://jorgecarrion21.substack.com/',  'Newsletter (Substack)',      '', 3, 'https://jorgecarrion21.substack.com/feed',  '', 1440, 1),
('periodismo',     'newsletter', 'Periodismo',       'https://periodismo.substack.com/',      'Newsletter (Substack)',      '', 1, 'https://periodismo.substack.com/feed',      '', 1440, 0),
('sergioparra',    'newsletter', 'Sergio Parra',     'https://sergioparra.substack.com/',     'Newsletter (Substack)',      '', 4, 'https://sergioparra.substack.com/feed',     '', 1440, 1),
('error500',       'newsletter', 'Error500',         'https://www.error500.net/',             'Antonio Ortiz · newsletter', '', 6, 'https://www.error500.net/feed',             '', 1440, 1),
('uriondo',        'newsletter', 'Uriondo',          'https://uriondo.substack.com/',         'Newsletter (Substack)',      '', 1, 'https://uriondo.substack.com/feed',         '', 1440, 0),
('martinsacristan','newsletter', 'Martín Sacristán', 'https://martinsacristan.substack.com/', 'Newsletter (Substack)',      '', 3, 'https://martinsacristan.substack.com/feed', '', 1440, 0);
