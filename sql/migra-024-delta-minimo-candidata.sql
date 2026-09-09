-- Delta minimo para ascender de Nuevas a Candidatas (sin votos no se asciende) y caducidad de lo que no llega a portada.
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('delta_min_candidate', '0.5', 'Delta minimo para que una nueva ascienda a candidata (cada ciclo sube la de mayor delta si llega a este minimo)'),
('nuevas_ttl_hours',    '48',  'Horas desde el envio tras las que una nueva o candidata que no ha llegado a portada se archiva (0 = nunca)');
-- Texto de «Como funciona el δ»: el paso 1 del ciclo explica el minimo y la caducidad (marcadores {mincand} y {newttl}).
UPDATE pages SET body = REPLACE(body,
 'asciende <b>uno solo</b>: el de mayor δ. Ese tiempo mínimo existe para que un envío no suba solo porque llegó en un momento tranquilo; tiene que ser visto y votado antes.</p>',
 'asciende <b>uno solo</b>: el de mayor δ, siempre que su δ sea al menos <b>{mincand}</b>. Sin votos no se asciende, y si ningún envío llega, ese ciclo no asciende nada. El tiempo mínimo existe para que un envío no suba solo porque llegó en un momento tranquilo; tiene que ser visto y votado antes. Lo que pasa <b>{newttl} horas</b> sin llegar a portada se archiva: la cola tampoco es para siempre.</p>')
WHERE slug = 'algoritmo';
UPDATE pages SET body = REPLACE(body,
 '· vida en portada de {ttl} horas ·',
 '· δ mínimo para ser candidata {mincand} · vida en portada de {ttl} horas · caducidad sin portada a las {newttl} horas ·')
WHERE slug = 'algoritmo';
