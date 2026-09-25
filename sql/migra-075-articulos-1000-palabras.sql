-- Articulos (Originales): tope de 1000 palabras, editable en Admin -> Config (article_max_words).
-- El tope de caracteres ya no va fijo en el codigo: se deriva del de palabras (x12).
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('article_max_words', '1000', 'Palabras maximas de un articulo');
UPDATE config SET cvalue = '1000', descr = 'Palabras maximas de un articulo (Originales)' WHERE ckey = 'article_max_words';
