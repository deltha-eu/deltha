-- migra-103 (02/10/2026): canales de YouTube como cuarto tipo de fuente (junto a medios, substacks y agregadores).
-- Un canal es una fuente de tipo «youtube»: se lee su feed Atom publico (youtube.com/feeds/videos.xml, sin clave de API), cada video
-- es un item con su miniatura guardada en /media y se comporta como un medio (Seguir, «enviar al radar»), en su grupo «Mis canales».
-- No se incrusta el reproductor: el enlace abre el video en YouTube. El catalogo sigue siendo solo de admin (admin > Fuentes > YouTube).
--   sources_youtube  1 = se ven y se leen; 0 = fuera de todo salvo admin > Fuentes (como los agregadores)
--   youtube_shorts   0 = al dar de alta un canal se usa su lista «solo videos largos» (sin shorts ni directos); 1 = el feed completo
--   yt_max_per_user  cuantos canales salen en el menu de cada usuario
SET NAMES utf8mb4;
ALTER TABLE sources MODIFY kind ENUM('agregador','medio','newsletter','youtube') NOT NULL DEFAULT 'medio';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('sources_youtube', '1', '1 = los canales de YouTube (fuentes de tipo youtube) se ven y se leen; 0 = fuera de todo salvo admin > Fuentes'),
('youtube_shorts',  '0', 'Al dar de alta un canal de YouTube: 0 = feed solo de videos largos (sin shorts ni directos); 1 = feed completo del canal'),
('yt_max_per_user', '3', 'Canales de YouTube que salen en el menu de cada usuario («Mis canales»)');
-- modulo «Ultimo en YouTube» de la columna derecha, al final (no pinta nada mientras no haya canales con videos)
UPDATE config SET cvalue = CONCAT(cvalue, ',youtube') WHERE ckey = 'side_order' AND cvalue <> '' AND FIND_IN_SET('youtube', cvalue) = 0;
