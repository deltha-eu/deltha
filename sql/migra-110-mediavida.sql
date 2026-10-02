-- migra-110 (03/10/2026): Mediavida como fuente de solo lectura. No tiene RSS: el lector lee la tabla de hilos de /foro/spy
-- (actividad reciente, como los index.rss de XenForo) con fuentes_parse_mediavida() en lib/fuentes.php. Sin extracto (la tabla no lo
-- trae); el numero es el de respuestas y la fecha, la del ultimo mensaje. Tipo «agregador», una sola vista: solo se ve si
-- config.sources_agregadores = 1 (migra-070). Si mediavida.com veta la IP del droplet: marcar «lector local» en admin -> Fuentes.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('mediavida', 'agregador', 'Mediavida', 'https://www.mediavida.com/foro', 'Comunidad de foros desde 1999: videojuegos, tecnología, política, deportes… · hilos con actividad', 'es', 'comunidad', NULL,
 'https://www.mediavida.com/foro/spy', '', 15, 0);
