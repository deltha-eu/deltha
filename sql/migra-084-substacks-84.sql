-- migra-084 — 84 substacks mas de la lista de Angel del 27/09/2026 (nombre | web | RSS | categoria | idioma). Tipo newsletter, cada 24 h,
-- sin destacar; lang segun la lista (ES/EN -> es); descripcion = categoria; tema al enviar segun la categoria (especifico si existe:
-- medios, economia, literatura, sociedad; si no el general). Nueve ya estaban por otra migracion y se omiten (Bitacora del Beagle,
-- Nuevas Ciencias, Diario de Substack, Superfluor, Content curators, 5 cosas que descubri, LasImperdibles, Error500, Tendenci@s).
-- Feeds sin comprobar (sin salida a internet desde la sesion): los errores de lectura salen en admin > Fuentes > Substack.
-- Cada fila se salta si ya existe el slug o la misma web (p. ej. propuesta por un usuario): no se duplica nada.
SET NAMES utf8mb4;
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'synapsesandlilies', 'newsletter', 'Synapses & Lilies', 'https://synapsesandlilies.substack.com/', 'Ciencia / neurociencia', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://synapsesandlilies.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%synapsesandlilies.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'miclub', 'newsletter', 'Club de Autores', 'https://miclub.substack.com/', 'Escritura / autores', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://miclub.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%miclub.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'fcolom', 'newsletter', 'de sostenible a antifrágil', 'https://fcolom.substack.com/', 'Arquitectura / sostenibilidad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://fcolom.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%fcolom.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'ingeniero-de-letras', 'newsletter', 'Ingeniero de letras', 'https://newsletter.ingenierodeletras.com/', 'Ciencia / tecnología / humanidades', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://newsletter.ingenierodeletras.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%newsletter.ingenierodeletras.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'mapas-milhaud', 'newsletter', 'Mapas Milhaud', 'https://newsletter.mapasmilhaud.com/', 'Mapas / historia / geografía', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://newsletter.mapasmilhaud.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%newsletter.mapasmilhaud.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'geeknifer', 'newsletter', 'Escríbeme pronto', 'https://geeknifer.substack.com/', 'Cultura / escritura / tecnología', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://geeknifer.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%geeknifer.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'scistories', 'newsletter', 'Science Narrative', 'https://scistories.substack.com/', 'Ciencia / historia de la ciencia', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://scistories.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%scistories.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'backstory-strategy', 'newsletter', 'Backstory & Strategy', 'https://www.backstoryandstrategy.com/', 'Medios / estrategia', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.backstoryandstrategy.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%backstoryandstrategy.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'the-media-stack', 'newsletter', 'The Media Stack', 'https://www.themediastack.co.uk/', 'Medios / publicidad / negocio', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.themediastack.co.uk/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%themediastack.co.uk%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'cronicasdelelefante', 'newsletter', 'Crónicas del Elefante', 'https://cronicasdelelefante.substack.com/', 'Medios / comunicación', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://cronicasdelelefante.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%cronicasdelelefante.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'eldespertador', 'newsletter', 'El Despertador', 'https://eldespertador.substack.com/', 'Actualidad / noticias', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://eldespertador.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%eldespertador.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'pressanddemocracy', 'newsletter', 'Press and Democracy', 'https://pressanddemocracy.substack.com/', 'Periodismo / medios / democracia', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://pressanddemocracy.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%pressanddemocracy.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'jagarciaaviles', 'newsletter', 'El boletín de Jose Alberto García Avilés', 'https://jagarciaaviles.substack.com/', 'Periodismo / innovación', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://jagarciaaviles.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%jagarciaaviles.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'claquetaia', 'newsletter', 'Claqueta, ¡IA!', 'https://claquetaia.substack.com/', 'IA / audiovisual', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://claquetaia.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%claquetaia.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'cartographers-tale', 'newsletter', 'A Cartographer''s Tale', 'https://www.cartographerstale.com/', 'Mapas / historia / geografía', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.cartographerstale.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%cartographerstale.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'the-digital-creator', 'newsletter', 'The Digital Creator', 'https://newsletter.thedigitalcreator.co/', 'IA / creadores / marketing', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://newsletter.thedigitalcreator.co/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%newsletter.thedigitalcreator.co%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'sofiaenlasnubes', 'newsletter', 'en las nubes', 'https://sofiaenlasnubes.substack.com/', 'Medios / cultura digital', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://sofiaenlasnubes.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%sofiaenlasnubes.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'kristinattheolympics', 'newsletter', 'Kristin''s Substack', 'https://kristinattheolympics.substack.com/', 'Deportes / diario personal', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'deportes'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://kristinattheolympics.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%kristinattheolympics.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'ambosmundos', 'newsletter', 'Ambos Mundos', 'https://ambosmundos.substack.com/', 'Medios / comunicación', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://ambosmundos.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%ambosmundos.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'jointstatement', 'newsletter', 'Statement on the Federal Reserve', 'https://jointstatement.substack.com/', 'Economía / Reserva Federal', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://jointstatement.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%jointstatement.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'technicolorart', 'newsletter', 'Technicolor', 'https://technicolorart.substack.com/', 'Arte / ilustración / creatividad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://technicolorart.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%technicolorart.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'phusions', 'newsletter', 'phusions+', 'https://phusions.substack.com/', 'Arte / cultura / tecnología', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://phusions.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%phusions.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'globeopinion', 'newsletter', 'Globe Opinion', 'https://globeopinion.substack.com/', 'Opinión / actualidad / cultura', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://globeopinion.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%globeopinion.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'erikoz', 'newsletter', 'La Trastienda del Hype - Erik Oz', 'https://erikoz.substack.com/', 'Cultura pop / marcas / audiencias', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://erikoz.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%erikoz.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'foresights', 'newsletter', 'Future Foresights', 'https://foresights.substack.com/', 'Tendencias / innovación / tecnología', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://foresights.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%foresights.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'wearedigitaldiplomacy', 'newsletter', 'Content Is Not King', 'https://wearedigitaldiplomacy.substack.com/', 'Comunicación digital / diplomacia', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://wearedigitaldiplomacy.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%wearedigitaldiplomacy.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'mireiapuig', 'newsletter', 'Rumiantemente', 'https://mireiapuig.substack.com/', 'Psicología', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://mireiapuig.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%mireiapuig.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'viajes-creativos', 'newsletter', 'Laboratorio de viajes creativos', 'https://laboratoriodeviajescreativos.substack.com/', 'Viajes / creatividad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://laboratoriodeviajescreativos.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%laboratoriodeviajescreativos.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'writebuildscale', 'newsletter', 'Write • Build • Scale', 'https://writebuildscale.substack.com/', 'Escritura / creadores / negocio', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://writebuildscale.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%writebuildscale.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'conversacioncontinua', 'newsletter', 'Conversación continua', 'https://conversacioncontinua.substack.com/', 'Comunicación / tecnología / política', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://conversacioncontinua.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%conversacioncontinua.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'the-preamble', 'newsletter', 'The Preamble', 'https://thepreamble.com/', 'Actualidad / política / historia', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://thepreamble.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%thepreamble.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'oscarbroc', 'newsletter', 'Toni Garcia Ramon & Òscar Broc', 'https://oscarbroc.substack.com/', 'Cultura / gastronomía / ocio', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://oscarbroc.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%oscarbroc.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'xavierpeytibi', 'newsletter', 'Xavier Peytibi. Comunicación y política', 'https://xavierpeytibi.substack.com/', 'Comunicación política / campañas', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://xavierpeytibi.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%xavierpeytibi.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'pickingsplinters', 'newsletter', 'The Audience Is Always Right', 'https://pickingsplinters.substack.com/', 'Medios / audiencias', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://pickingsplinters.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%pickingsplinters.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'maramirandagarca', 'newsletter', 'María’s Substack', 'https://maramirandagarca.substack.com/', 'Personal / comunicación', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://maramirandagarca.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%maramirandagarca.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'letrasypantallas', 'newsletter', 'Letras y Pantallas', 'https://letrasypantallas.substack.com/', 'Cultura / audiovisual / escritura', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://letrasypantallas.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%letrasypantallas.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'poetaviajera', 'newsletter', 'Bitácora de Viaje', 'https://poetaviajera.substack.com/', 'Viajes / creatividad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://poetaviajera.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%poetaviajera.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'politica-creativa', 'newsletter', 'Política Creativa', 'https://www.politicacreativa.com/', 'Comunicación política / campañas', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.politicacreativa.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%politicacreativa.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'puntosdesutura', 'newsletter', 'Puntos de Sutura', 'https://puntosdesutura.substack.com/', 'Sociedad / ideas / comunicación', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://puntosdesutura.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%puntosdesutura.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'press-gazette-us', 'newsletter', 'Press Gazette Future of Media US', 'https://pressgazettefutureofmediaus.substack.com/', 'Medios / negocio periodístico', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://pressgazettefutureofmediaus.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%pressgazettefutureofmediaus.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'stop-the-presses', 'newsletter', 'Stop the Presses', 'https://www.stopthepresses.news/', 'Periodismo / medios / política', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.stopthepresses.news/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%stopthepresses.news%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'bibliofilia', 'newsletter', 'Bibliofilia', 'https://bibliofilia.substack.com/', 'Libros / cultura', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'literatura'), (SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://bibliofilia.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%bibliofilia.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'lacharquita', 'newsletter', 'Boletín Dominical', 'https://lacharquita.substack.com/', 'Periodismo / internet', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://lacharquita.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%lacharquita.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'koreb', 'newsletter', 'koreb’s Substack', 'https://koreb.substack.com/', 'Tecnología / economía / actualidad', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://koreb.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%koreb.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'rebel-intel', 'newsletter', 'Rebel Intel', 'https://intel.goodrebels.com/', 'IA / marketing / comunicación', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://intel.goodrebels.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%intel.goodrebels.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT '7x7', 'newsletter', '7x7', 'https://7x7.news/', 'Actualidad internacional', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://7x7.news/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%7x7.news%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'marcelogantman', 'newsletter', 'Big Data Sports', 'https://marcelogantman.substack.com/', 'Deportes / datos', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://marcelogantman.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%marcelogantman.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'escribe', 'newsletter', 'Escribe en Substack', 'https://escribe.substack.com/', 'Substack / escritura / creadores', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://escribe.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%escribe.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'claramontesinos', 'newsletter', '¡A las Claras!', 'https://claramontesinos.substack.com/', 'Sociedad / cultura / opinión', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://claramontesinos.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%claramontesinos.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'unelospuntos', 'newsletter', 'Une los puntos', 'https://unelospuntos.substack.com/', 'Periodismo / análisis / actualidad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://unelospuntos.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%unelospuntos.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'newsletter-seo-ia', 'newsletter', 'Newsletter SEO IA', 'https://newsletter.jmswebs.com/', 'SEO / IA', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://newsletter.jmswebs.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%newsletter.jmswebs.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'lisrosello', 'newsletter', 'La ensamblajista', 'https://lisrosello.substack.com/', 'Sociedad / tecnología / futuros', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://lisrosello.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%lisrosello.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'mkarolian', 'newsletter', 'mkarolian', 'https://mkarolian.substack.com/', 'Medios / IA / periodismo', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://mkarolian.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%mkarolian.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'privacidad', 'newsletter', 'Economicón', 'https://privacidad.substack.com/', 'Privacidad / sociedad digital', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://privacidad.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%privacidad.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'spencerrscott', 'newsletter', 'As If We Were Staying', 'https://spencerrscott.substack.com/', 'Clima / ecología / sociedad', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'ciencia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://spencerrscott.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%spencerrscott.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'planetamaunaloa', 'newsletter', 'Planeta Mauna Loa', 'https://planetamaunaloa.substack.com/', 'Medio ambiente / clima', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://planetamaunaloa.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%planetamaunaloa.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'audiogen', 'newsletter', 'AudioGen.fm', 'https://audiogen.substack.com/', 'Audio / radio / medios', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://audiogen.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%audiogen.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'causasyazares', 'newsletter', 'Causas y Azares', 'https://causasyazares.substack.com/', 'Actualidad / lecturas / análisis', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://causasyazares.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%causasyazares.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'ladobnews', 'newsletter', 'LadoBnews - Por Irina Sternik', 'https://www.ladobnews.com.ar/', 'Tecnología / cultura / sociedad', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.ladobnews.com.ar/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%ladobnews.com.ar%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'dougshapiro', 'newsletter', 'The Mediator', 'https://dougshapiro.substack.com/', 'Medios / negocio / cultura', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://dougshapiro.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%dougshapiro.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'public-notice', 'newsletter', 'Public Notice', 'https://www.publicnotice.co/', 'Actualidad / política / medios', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.publicnotice.co/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%publicnotice.co%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'donmoynihan', 'newsletter', 'Can We Still Govern?', 'https://donmoynihan.substack.com/', 'Gobierno / políticas públicas', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://donmoynihan.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%donmoynihan.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'nicolaspiccoli', 'newsletter', 'Newsletter | Nico Piccoli', 'https://nicolaspiccoli.substack.com/', 'Periodismo / medios', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://nicolaspiccoli.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%nicolaspiccoli.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'reportaro', 'newsletter', 'El Boletín de Reportaro', 'https://reportaro.substack.com/', 'Periodismo / medios / comunicación', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://reportaro.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%reportaro.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'periodistia', 'newsletter', 'Almas Rotas', 'https://periodistia.substack.com/', 'Periodismo / salud mental', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://periodistia.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%periodistia.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'embedded', 'newsletter', 'Embedded', 'https://embedded.substack.com/', 'Internet / cultura digital', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'tecnologia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://embedded.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%embedded.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'fwiw', 'newsletter', 'FWIW', 'https://www.fwiw.news/', 'Política / campañas / estrategia digital', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.fwiw.news/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%fwiw.news%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'boletines', 'newsletter', 'Utopías', 'https://boletines.substack.com/', 'Actualidad / sociedad / ideas', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'cultura'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://boletines.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%boletines.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'derecho-en-movimiento', 'newsletter', 'Derecho en movimiento', 'https://www.derechoenmovimiento.com/', 'Derecho / actualidad jurídica', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'sociedad'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.derechoenmovimiento.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%derechoenmovimiento.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'carlosguadian', 'newsletter', 'CluPad (por Carlos Guadián)', 'https://carlosguadian.substack.com/', 'Tecnología / política / IA', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://carlosguadian.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%carlosguadian.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'jamesbreiner', 'newsletter', 'Your News Biz', 'https://jamesbreiner.substack.com/', 'Medios / negocio periodístico', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://jamesbreiner.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%jamesbreiner.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'compolmatters', 'newsletter', 'Compol matters', 'https://compolmatters.substack.com/', 'Comunicación política', 'es', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'politica'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://compolmatters.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%compolmatters.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'futureproofnews', 'newsletter', 'Future Proof', 'https://futureproofnews.substack.com/', 'Medios / futuro del periodismo', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://futureproofnews.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%futureproofnews.substack.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'seo-for-google-news', 'newsletter', 'SEO for Google News', 'https://www.seoforgooglenews.com/', 'SEO / Google News / Discover', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'medios'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://www.seoforgooglenews.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%seoforgooglenews.com%');
INSERT IGNORE INTO sources (slug, kind, name, home, descr, lang, skin, topic_id, feed_portada, feed_nuevas, interval_min, status, featured)
  SELECT 'nikitawalia', 'newsletter', 'thinking out loud', 'https://nikitawalia.substack.com/', 'Cultura / estrategia / negocio', 'en', '', COALESCE((SELECT topic_id FROM topics WHERE name = 'economia'), (SELECT topic_id FROM topics WHERE name = 'actualidad')), 'https://nikitawalia.substack.com/feed', '', 1440, 'ok', 0
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sources x WHERE x.home LIKE '%nikitawalia.substack.com%');
