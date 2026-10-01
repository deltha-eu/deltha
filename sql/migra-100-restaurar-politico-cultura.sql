-- migra-100 (01/10/2026): vuelven al catalogo dos fuentes que el 01/10 dejaron de existir en la tabla sources (comprobado por Angel
-- en el droplet: quedaban «politico» —Politico, en ingles— y «democrata», pero no «politico-es» ni «culturainquieta»). Causa sin
-- determinar: ninguna migracion las borra (la 099 solo apuntaba al slug «politico» con feed de politico.es); el unico borrado de
-- fuentes del codigo es el boton «borrar» de admin -> Fuentes. Se recrean ya marcadas como lector local (TIBERIO), porque las dos
-- vetan la IP del droplet. Sus noticias las volvera a traer el lector; los «seguir» que tuvieran se perdieron con el borrado.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured, relay) VALUES
('politico-es', 'medio', 'Político ES', 'https://politico.es/', 'Actualidad política diaria: geopolítica, noticias, políticos y sociedad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://politico.es/feed/', '', 60, 'ok', 0, 1),
('culturainquieta', 'medio', 'Cultura Inquieta', 'https://culturainquieta.com/', 'Arte, música y cultura', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://culturainquieta.com/feed/', '', 30, 'ok', 0, 1);
