-- migra-106 (02/10/2026): tercera portada, Canales, para los enlaces a YouTube (youtube.com, youtu.be), con TURNO CONDICIONADO:
-- no entra en la alternancia Medios / Substacks. Un ciclo es suyo solo si tiene algun video publicable (edad, votos y δ minimos) y
-- han pasado canales_gap_min desde su ultima publicacion; si no, el ciclo es de la portada del turno, como siempre.
--   portada_canales            1 = activa; 0 = YouTube vuelve a Medios y la pestaña desaparece
--   canales_gap_min            minutos minimos entre dos publicaciones en Canales
--   delta_min_publish_canales  δ minimo para publicar
--   decay_canales              decaimiento por media hora (como Substacks: un video no caduca con la actualidad)
SET NAMES utf8mb4;
ALTER TABLE stories   MODIFY portada ENUM('medios','substacks','canales') DEFAULT NULL;
ALTER TABLE promo_log MODIFY portada ENUM('medios','substacks','canales') DEFAULT NULL;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('portada_canales',           '1',    '1 = los enlaces a YouTube van a la portada Canales (turno condicionado); 0 = van a Medios y la pestaña no se ve'),
('canales_gap_min',           '15',   'Minutos minimos entre dos publicaciones en Canales'),
('delta_min_publish_canales', '1',    'Delta minimo para publicar en Canales'),
('decay_canales',             '0.02', 'Decremento de delta por media hora de espera en Canales');
-- los videos de YouTube que ya estaban en la portada de Medios pasan a Canales (los del radar los recoloca el ciclo)
UPDATE stories SET portada = 'canales' WHERE portada = 'medios' AND content_type = 'link' AND source IN ('youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be');
