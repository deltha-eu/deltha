-- 25/09/2026. Dos cosas:
-- 1) En «¿Que es Deltha?» (seccion «De donde viene», los nueve idiomas) la fuente de la idea del agregador pasa de Meneame a Digg.
--    REPLACE sobre pages.body: vale tambien para las paginas editadas despues en admin. En euskera cambia la declinacion (-tik → -etik).
-- 2) Los agregadores (fuentes de tipo «agregador»: Meneame, Mediatize, Renegados, Burbuja, Areopago) se pueden apagar con
--    config.sources_agregadores; por defecto apagados: no salen en el catalogo (/medios.php), ni en el rail, ni en la franja de fuentes,
--    ni en la columna derecha, ni en el pie, ni en /ultimo.php, y /fuente.php responde 404; el cron deja de leer sus RSS.
--    Siguen en admin > Fuentes por si se vuelven a activar (fuentes_activas() en lib/fuentes.php).
SET NAMES utf8mb4;
UPDATE pages SET body = REPLACE(body, '<b>Menéame</b>tik', '<b>Digg</b>etik') WHERE slug = 'que-es.eu';
UPDATE pages SET body = REPLACE(body, '<b>Menéame</b>', '<b>Digg</b>') WHERE slug LIKE 'que-es%';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('sources_agregadores', '0', '1 = los agregadores (Meneame, Mediatize, Renegados...) se ven y se leen; 0 = fuera de todo salvo admin > Fuentes');
