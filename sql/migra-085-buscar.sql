-- migra-085: buscador interno (/buscar.php) — indices FULLTEXT de InnoDB sobre lo que se busca.
-- Sin nada externo: MATCH ... AGAINST en modo booleano, con la collation utf8mb4_unicode_ci (sin distinguir
-- acentos ni mayusculas). Las palabras de menos de innodb_ft_min_token_size (3) letras no entran en el indice:
-- buscar.php las resuelve con LIKE. Uso:  mysql -u root -p deltha < sql/migra-085-buscar.sql

ALTER TABLE stories      ADD FULLTEXT ft_stories  (title, content);
ALTER TABLE comments     ADD FULLTEXT ft_comments (content);
ALTER TABLE source_items ADD FULLTEXT ft_items    (title, content);

INSERT INTO config (ckey, cvalue, descr) VALUES
('search_enabled', '1', '1 = lupa y /buscar.php (hilos, comentarios, noticias de las fuentes y usuarios)')
ON DUPLICATE KEY UPDATE descr = VALUES(descr);
