-- CGTN Español no publica RSS (ni /rss ni /rss.xml): se deja apagada hasta encontrar un feed valido.
UPDATE sources SET status = 'off', descr = 'Sin RSS conocido: apagada' WHERE slug = 'cgtn' AND last_fetch IS NOT NULL AND NOT EXISTS (SELECT 1 FROM source_items i WHERE i.source_id = sources.source_id);
UPDATE sources SET status = 'off', descr = 'Sin RSS conocido: apagada' WHERE slug = 'cgtn' AND last_fetch IS NULL;
