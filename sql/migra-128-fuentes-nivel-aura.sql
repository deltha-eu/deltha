-- migra-128: canales de YouTube y agregadores solo desde un nivel de aura (08/10/2026).
-- Aunque esten encendidos (sources_youtube, sources_agregadores), «Mis canales» y «Mis agregadores» (y sus modulos de la columna
-- derecha y su grupo en Todas las fuentes) solo los ve quien tiene al menos esta aura; moderadores siempre. A quien acaba de
-- llegar le salen solo Mis medios y Mis substacks.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('fuentes_nivel_aura', '6', 'Aura minima para ver canales de YouTube y agregadores en Mis fuentes / Todas las fuentes (moderadores siempre). Solo cuenta si estan encendidos');
