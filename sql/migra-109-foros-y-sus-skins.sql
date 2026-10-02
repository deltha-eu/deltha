-- migra-109 (02/10/2026): tres foros mas como fuente de solo lectura y una skin propia para cada uno (y para Burbuja, que ya estaba
-- desde la 045 con la skin Comunidad). Feeds comprobados el 02/10/2026:
--   HTCMania (vBulletin)  external.php?type=RSS2    hilos nuevos con el primer mensaje; sin contador de respuestas -> skin Acero
--   ForoMTB  (XenForo 1)  forums/-/index.rss        hilos con actividad, extracto y slash:comments                 -> skin Sendero
--   ForoBeta (XenForo 2)  forums/-/index.rss        idem, con el subforo en <category>                              -> skin Píldora
--   Burbuja  (XenForo 2)  ya existia                                                                                 -> skin Marino
-- Son de tipo «agregador» con una sola vista (feed_nuevas vacio), como Areopago: solo se ven y se leen si config.sources_agregadores = 1
-- (migra-070; por defecto, apagados). No se descargan las fotos de los mensajes (fuente_sin_fotos() en lib/fuentes.php).
-- Las cuatro skins heredan la estructura de Comunidad (DDD_SKIN_FAM en lib/app.php, <html data-fam="comunidad">).
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('htcmania', 'agregador', 'HTCMania', 'https://www.htcmania.com/', 'Foro de móviles, tabletas y relojes · hilos nuevos', 'es', 'acero', NULL,
 'https://www.htcmania.com/external.php?type=RSS2', '', 30, 0),
('foromtb',  'agregador', 'ForoMTB',  'https://www.foromtb.com/', 'Foro de ciclismo y bicicleta de montaña · hilos con actividad', 'es', 'sendero', NULL,
 'https://www.foromtb.com/forums/-/index.rss', '', 30, 0),
('forobeta', 'agregador', 'ForoBeta', 'https://forobeta.com/', 'Foro de webmasters: SEO, alojamiento y negocios en línea · hilos con actividad', 'es', 'pildora', NULL,
 'https://forobeta.com/forums/-/index.rss', '', 30, 0);
UPDATE sources SET skin = 'marino' WHERE slug = 'burbuja' AND skin IN ('', 'comunidad');
