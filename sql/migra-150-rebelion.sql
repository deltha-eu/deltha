-- migra-150 (13/10/2026): Rebelión (rebelion.org) como medio. Quito su RSS (/feed/ devuelve 410 Gone) pero deja abierta la API REST de
-- WordPress, asi que el «RSS» de la fuente es /wp-json/wp/v2/posts y lo lee fuentes_parse_wpjson (lib/fuentes.php), valido para
-- cualquier otro WordPress en la misma situacion. Sin votos ni comentarios.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('rebelion', 'medio', 'Rebelión', 'https://rebelion.org/', 'Periódico electrónico de información alternativa: internacional, economía, movimientos sociales y opinión', 'es', '', NULL,
 'https://rebelion.org/wp-json/wp/v2/posts?per_page=20&_embed=1', '', 60, 0);
