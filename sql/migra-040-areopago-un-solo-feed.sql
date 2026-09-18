-- Areopago: una sola vista («Areópago», sin Portada/Candidatas/Nuevas): el feed de ultimos mensajes del foro. Sin votos.
-- El numero del mensaje dentro del hilo (#11515) y el autor no vienen en el RSS: el lector los lee de la pagina de cada
-- mensaje nuevo (tope sources_smf_per_run por pasada) — fuente_smf_completar() en lib/fuentes.php.
SET NAMES utf8mb4;
UPDATE sources SET feed_portada = 'https://www.areopago.eu/index.php?action=.xml;type=rss2;sa=recent;limit=30', feed_nuevas = '', descr = 'Aquí lo sabemos todo y si no, nos lo inventamos · últimos mensajes del foro', last_error = NULL WHERE slug = 'areopago';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('sources_smf_per_run', '12', 'Foros SMF (Areopago): paginas de mensaje que se leen por pasada para sacar autor y numero del mensaje');
