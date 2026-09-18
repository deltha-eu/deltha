-- «Plantilla» pasa a llamarse «skin» en la interfaz; la skin «comunidad» se rehace como foro clasico (aire Areopago / SMF);
-- en admin -> Config, skin_default se elige entre todas las skins. Y Areopago entra como fuente agregador con esa skin.
SET NAMES utf8mb4;
UPDATE config SET descr = 'Skin por defecto (anonimos y quien no elija): cualquiera de las existentes' WHERE ckey = 'skin_default';
-- Areopago (foro SMF): feeds RSS 2.0 del propio SMF; «portada» = temas nuevos, «nuevas» = ultimos mensajes. Sin votos.
INSERT IGNORE INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min, featured) VALUES
('areopago', 'agregador', 'Areópago', 'https://www.areopago.eu/', 'Aquí lo sabemos todo y si no, nos lo inventamos · foro', 'comunidad', NULL,
 'https://www.areopago.eu/index.php?action=.xml;type=rss2;sa=news;limit=30', 'https://www.areopago.eu/index.php?action=.xml;type=rss2;sa=recent;limit=30', 30, 0);
