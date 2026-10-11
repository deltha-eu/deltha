-- migra-136 (11/10/2026): Público (publico.es) como medio. Cerro sus RSS en diciembre de 2024, asi que se lee su portada con un parser
-- propio (fuentes_parse_publico, lib/fuentes.php; fuentes_parse_rss() lo reconoce por el contenido). Sin votos ni comentarios.
-- El droplet lo lee sin problema (HTTP 200 el 11/10/2026); si algun dia lo veta, marcar «lector local» en admin -> Fuentes.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('publico', 'medio', 'Público', 'https://www.publico.es/', 'Diario progresista: política, internacional, sociedad, cultura y opinión', 'es', '', NULL,
 'https://www.publico.es/', '', 30, 0);
