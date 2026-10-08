-- migra-130 (08/10/2026): dos medios sin RSS, leidos con parsers propios en lib/fuentes.php (fuentes_parse_rss() los reconoce por el contenido).
--  · Asimov Press (press.asimov.com, ingles, ensayos de ciencia y biotecnologia): se lee la lista de /articles (fuentes_parse_asimov).
--  · Página Internacional (paginainternacional.com, prensa internacional traducida al español): su web es una app sin HTML util; se lee
--    la API JSON publica que usa la propia app, pagint.onrender.com/api/portada (fuentes_parse_pagint). El enlace lleva a su web.
-- Las dos sin votos ni comentarios (DDD_FEEDS_SIN_VOTOS). Si alguna veta la IP del droplet: marcar «lector local» en admin -> Fuentes.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('asimov-press', 'medio', 'Asimov Press', 'https://press.asimov.com/', 'Ensayos largos sobre ciencia, biotecnología y progreso científico', 'en', '', NULL,
 'https://press.asimov.com/articles', '', 120, 0),
('pagina-internacional', 'medio', 'Página Internacional', 'https://paginainternacional.com/', 'Lo mejor de la prensa internacional, seleccionado y traducido al español', 'es', '', NULL,
 'https://pagint.onrender.com/api/portada?pagination[start]=0&pagination[limit]=30', '', 60, 0);
