-- migra-162 (17/10/2026): Killbait (killbait.com, agregador de noticias sin clickbait hecho por estudiantes y profesionales de periodismo)
-- como medio, con su RSS en español (killbaitnews.github.io; trae la foto en <enclosure>). Su web es solo JavaScript y /enviar no le saca
-- la foto; con la fuente en el catalogo, al enviar una de sus URL la foto, el titular y la entradilla salen del item del RSS (enviar.php).
-- Hay feed en ingles aparte (…/en.rss) por si se quiere otra fuente.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('killbait', 'medio', 'Killbait', 'https://killbait.com/', 'Noticias sin clickbait ni sensacionalismo, en español, de España y América Latina', 'es', '',
 COALESCE((SELECT topic_id FROM topics WHERE name = 'actualidad'), NULL),
 'https://killbaitnews.github.io/rssfeeds/es.rss', '', 30, 0);
