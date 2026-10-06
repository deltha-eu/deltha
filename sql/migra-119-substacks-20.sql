-- migra-119 (06/10/2026): 20 substacks mas de la lista de Angel (con URL). «On Substack» ya estaba (084) y se omite. Mismo patron que
-- la 084/113: tipo newsletter, cada 24 h, sin destacar; lang segun la publicacion (en/de/es); tema especifico si existe, si no el
-- general, si no actualidad. Cada fila se salta si ya existe el slug o la misma web: no duplica nada.
-- Sin comprobar desde la sesion (Substack limita las peticiones): diezeit, jacobobergareche y jordiriscopsiquiatria son el subdominio
-- deducido del perfil substack.com/@…; los errores de lectura salen en admin > Fuentes > Substack.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'adamtooze', 'newsletter', 'Chartbook', 'https://adamtooze.substack.com/', 'Adam Tooze. Economía, historia y geopolítica', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://adamtooze.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%adamtooze.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'derspiegel', 'newsletter', 'DER SPIEGEL - The German View', 'https://derspiegel.substack.com/', 'DER SPIEGEL. La actualidad alemana contada en inglés', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://derspiegel.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%derspiegel.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'diezeit', 'newsletter', 'DIE ZEIT', 'https://diezeit.substack.com/', 'DIE ZEIT. Nachrichten, Hintergründe und Debatten', 'de', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://diezeit.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%diezeit.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'the-free-press', 'newsletter', 'The Free Press', 'https://www.thefp.com/', 'The Free Press. Periodismo y opinión desde EE. UU.', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.thefp.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%thefp.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'geld-fuer-die-welt', 'newsletter', 'Geld für die Welt', 'https://www.geldfuerdiewelt.de/', 'Maurice Höfgen. Economía y política monetaria', 'de', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.geldfuerdiewelt.de/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%geldfuerdiewelt.de%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'gargoladigital', 'newsletter', 'gárgola digital 🐸🍵', 'https://gargoladigital.substack.com/', 'Ainhoa Marzol. Cultura digital e internet', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://gargoladigital.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%gargoladigital.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'hansmartientennapel', 'newsletter', 'Hans-Martien ten Napel', 'https://hansmartientennapel.substack.com/', 'Hans-Martien ten Napel. Derecho, religión y política', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://hansmartientennapel.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%hansmartientennapel.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'timothygartonash', 'newsletter', 'History of the Present', 'https://timothygartonash.substack.com/', 'Timothy Garton Ash. Europa, historia del presente', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://timothygartonash.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%timothygartonash.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'jacobobergareche', 'newsletter', 'Jacobo Bergareche', 'https://jacobobergareche.substack.com/', 'Jacobo Bergareche. Columnas, crónicas y artículos', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://jacobobergareche.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%jacobobergareche.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'richardkatz', 'newsletter', 'Japan Economy Watch', 'https://richardkatz.substack.com/', 'Richard Katz. La economía japonesa', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://richardkatz.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%richardkatz.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'lamatinaleuropea', 'newsletter', 'La Matinal Europea', 'https://lamatinaleuropea.substack.com/', 'Christian Spillmann. La actualidad de la Unión Europea', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://lamatinaleuropea.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%lamatinaleuropea.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'mary-harrington', 'newsletter', 'Mary Harrington', 'https://www.maryharrington.co.uk/', 'Mary Harrington. Ensayo sobre sociedad, tecnología y feminismo', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'sociedad'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.maryharrington.co.uk/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%maryharrington.co.uk%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'nybooks', 'newsletter', 'The New York Review of Books', 'https://substack.nybooks.com/', 'The New York Review of Books. Libros, ideas y crítica', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://substack.nybooks.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%substack.nybooks.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'ourtime', 'newsletter', 'Origins of Our Time', 'https://ourtime.substack.com/', 'Tim Barker. Historia económica y política', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://ourtime.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%ourtime.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'postliberal-order', 'newsletter', 'Postliberal Order', 'https://www.postliberalorder.com/', 'The Postliberals. Pensamiento político posliberal', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.postliberalorder.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%postliberalorder.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'roddreher', 'newsletter', 'Rod Dreher\'s Diary', 'https://roddreher.substack.com/', 'Rod Dreher. Religión, cultura y política', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'sociedad'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://roddreher.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%roddreher.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'annapacheco', 'newsletter', 'Las personas y el momento', 'https://annapacheco.substack.com/', 'Anna Pacheco. Crónica y ensayo sobre la vida y el trabajo', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'sociedad'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://annapacheco.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%annapacheco.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'jordiriscopsiquiatria', 'newsletter', 'Jordi Risco Psiquiatra', 'https://jordiriscopsiquiatria.substack.com/', 'Jordi Risco. Psiquiatría y salud mental', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://jordiriscopsiquiatria.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%jordiriscopsiquiatria.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'traumatologageek', 'newsletter', 'La traumatóloga geek', 'https://www.traumatologageek.es/', 'La traumatóloga geek. Medicina, ciencia y tecnología', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.traumatologageek.es/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%traumatologageek.es%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'pauoller', 'newsletter', 'Uno más entre un millón', 'https://pauoller.substack.com/', 'Pau Oller. Newsletter personal', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://pauoller.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%pauoller.substack.com%');
