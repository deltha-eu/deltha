-- migra-113 (04/10/2026): lista de substacks de Angel (titulo | autor | RSS | web) + Natzir en Substacks y el blog de Semrush en Medios.
-- De los 11 substacks de la lista, 6 ya estaban (Ingeniero de letras, Superfluor, Abundancia, El Arjonauta, Mapas Milhaud y
-- Science Narrative: migras 052 y 084) y se omiten. Entran 5 newsletters nuevas + natzir.com (WordPress, feed /articulos/feed/,
-- va como newsletter para salir en «Mis substacks») + es.semrush.com/blog como medio.
-- Mismo patron que la 084: newsletter cada 24 h, medio cada 60 min, sin destacar, lang es, tema por nombre con respaldo en actualidad.
-- Cada fila se salta si ya existe el slug o la misma web (p. ej. propuesta por un usuario): no se duplica nada.
-- Feeds comprobados desde la sesion: Ubicaciones, Breiquin Nius, Natzir y Semrush responden (el de Semrush lleva sin novedades
-- desde 05/2025); Folio Editorial, Martadas y Libreranta no se pudieron leer (robots.txt): errores en admin > Fuentes > Substack.
SET NAMES utf8mb4;

INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'ubicaciones', 'newsletter', 'Ubicaciones', 'https://ubicaciones.substack.com/', 'Edu Rodríguez. Ensayos quincenales sobre historia, filosofía y cultura', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://ubicaciones.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%ubicaciones.substack.com%');

INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'breiquin', 'newsletter', 'Breiquin Nius', 'https://breiquin.substack.com/', 'Ignacio Sainz de Medrano. La actualidad comentada con retraso, distancia y humor', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://breiquin.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%breiquin.substack.com%');

INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'folioeditorial', 'newsletter', 'Folio Editorial', 'https://folioeditorial.substack.com/', 'Antonio J. Rodríguez. Libros, edición y vida literaria', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://folioeditorial.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%folioeditorial.substack.com%');

INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'martagnavarro', 'newsletter', 'Martadas', 'https://martagnavarro.substack.com/', 'Marta G. Navarro. Newsletter personal', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://martagnavarro.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%martagnavarro.substack.com%');

INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'libreranta', 'newsletter', 'Libreranta', 'https://libreranta.substack.com/', 'Raquel Blanco. Boletín semanal de libros y librerías', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://libreranta.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%libreranta.substack.com%');

-- Natzir Turrado (SEO). No es Substack: WordPress con el feed de la seccion de articulos. Entra como newsletter por peticion de Angel.
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'natzir', 'newsletter', 'Natzir', 'https://natzir.com/', 'Natzir Turrado. Artículos sobre SEO y buscadores', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://natzir.com/articulos/feed/', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%natzir.com%');

-- Blog de Semrush en español: medio.
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured, relay)
  SELECT 'semrush', 'medio', 'Semrush blog', 'https://es.semrush.com/blog/', 'Marketing digital, SEO y analítica', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://es.semrush.com/blog/feed/', '', 60, 'ok', 0, 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%semrush.com/blog%');
