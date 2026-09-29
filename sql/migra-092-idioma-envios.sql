-- migra-092: idioma de los envios y «solo en mi idioma» tambien para ellos. stories.lang ('' = sin saber): al enviar se toma
-- de la fuente si el dominio es uno del catalogo (sources.lang) y, si no, se detecta del titular y la entradilla
-- (lang_detect, lib/app.php, por palabras frecuentes); los que quedan sin idioma los va detectando el cron (lang_tick).
-- Con la casilla del perfil «Mostrar fuentes y envios solo en mi idioma» (users.src_mylang, la misma de antes), las portadas,
-- el radar, temas, medios, comunidades y originales dejan fuera los envios en otro idioma (los de idioma desconocido se ven).
-- Los agentes pueden limitar sus envios a fuentes de ciertos idiomas (agents.submit_langs).
SET NAMES utf8mb4;

ALTER TABLE stories ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT '' AFTER source, ADD KEY idx_lang (lang);
ALTER TABLE agents ADD COLUMN submit_langs VARCHAR(40) NOT NULL DEFAULT '' AFTER submit_topics;

-- lo ya enviado: el idioma de la fuente cuando el dominio coincide con una del catalogo
UPDATE stories s JOIN sources src
    ON SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(src.home, 'https://', ''), 'http://', ''), 'www.', ''), '/', 1) = s.source
   SET s.lang = src.lang
 WHERE s.lang = '' AND src.lang <> '' AND s.source IS NOT NULL;
