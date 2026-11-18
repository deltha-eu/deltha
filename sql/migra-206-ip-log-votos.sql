-- migra-206 (18/11/2026): el registro de IP apunta también los votos a envíos (decisión de Ángel: sin eso no se ven las cuentas
--  que votan a la vez; la migra-193 los dejó fuera a propósito). ip_log.kind admite 'vote' (ref_id = el envío) y la política de
--  privacidad deja de decir que la IP de los votos no se anota, en los diez idiomas. Los soles y lágrimas a comentarios siguen fuera.
--  REPLACE sobre el texto que puso la migra-194: si esa frase se editó a mano en admin -> Páginas, no hace nada y hay que retocarla
--  a mano. El SELECT del final dice, idioma por idioma, si ha quedado aplicado (1) o no (0). Idempotente.
SET NAMES utf8mb4;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ip_log' AND COLUMN_NAME = 'kind' AND COLUMN_TYPE LIKE '%''vote''%');
SET @sql = IF(@ya = 0, "ALTER TABLE ip_log MODIFY kind ENUM('registro','login','story','comment','chat','vote') NOT NULL", "SELECT 'ip_log.kind ya admite vote'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE pages SET body = REPLACE(body, 'No anotamos la IP de quien solo lee ni la de los votos, y', 'Anotamos también la de cada voto a un envío. No anotamos la IP de quien solo lee, y') WHERE slug = 'privacidad';
UPDATE pages SET body = REPLACE(body, 'No anotem la IP de qui només llegeix ni la dels vots, i', 'Anotem també la de cada vot a un enviament. No anotem la IP de qui només llegeix, i') WHERE slug = 'privacidad.ca';
UPDATE pages SET body = REPLACE(body, 'Non anotamos a IP de quen só le nin a dos votos, e', 'Anotamos tamén a de cada voto a un envío. Non anotamos a IP de quen só le, e') WHERE slug = 'privacidad.gl';
UPDATE pages SET body = REPLACE(body, 'Ez dugu irakurri besterik egiten ez duenaren IPa jasotzen, ezta botoena ere, eta', 'Bidalketa bati emandako boto bakoitzarena ere jasotzen dugu. Ez dugu irakurri besterik egiten ez duenaren IPa jasotzen, eta') WHERE slug = 'privacidad.eu';
UPDATE pages SET body = REPLACE(body, 'We don''t log the IP of people who only read, or of votes, and', 'We also log it for each vote on a post. We don''t log the IP of people who only read, and') WHERE slug = 'privacidad.en';
UPDATE pages SET body = REPLACE(body, 'Nous ne notons pas l''IP de ceux qui ne font que lire ni celle des votes, et', 'Nous notons aussi celle de chaque vote sur un envoi. Nous ne notons pas l''IP de ceux qui ne font que lire, et') WHERE slug = 'privacidad.fr';
UPDATE pages SET body = REPLACE(body, 'Die IP von Personen, die nur lesen, und die von Abstimmungen protokollieren wir nicht, und', 'Wir protokollieren sie auch bei jeder Stimme für einen Beitrag. Die IP von Personen, die nur lesen, protokollieren wir nicht, und') WHERE slug = 'privacidad.de';
UPDATE pages SET body = REPLACE(body, 'Non annotiamo l''IP di chi si limita a leggere né quello dei voti, e', 'Annotiamo anche quello di ogni voto a un invio. Non annotiamo l''IP di chi si limita a leggere, e') WHERE slug = 'privacidad.it';
UPDATE pages SET body = REPLACE(body, 'Não registamos o IP de quem apenas lê nem o dos votos, e', 'Registamos também o de cada voto num envio. Não registamos o IP de quem apenas lê, e') WHERE slug = 'privacidad.pt';
UPDATE pages SET body = REPLACE(body, 'Yalnızca okuyanların IP adresini ve oyların IP adresini kaydetmiyoruz;', 'Bir gönderiye verilen her oyun IP adresini de kaydediyoruz. Yalnızca okuyanların IP adresini kaydetmiyoruz;') WHERE slug = 'privacidad.tr';

SELECT 'privacidad' AS pagina, body LIKE '%Anotamos también la de cada voto a un envío. No anotamos la IP de quien solo lee, y%' AS votos FROM pages WHERE slug = 'privacidad'
UNION ALL SELECT 'privacidad.ca' AS pagina, body LIKE '%Anotem també la de cada vot a un enviament. No anotem la IP de qui només llegeix, i%' AS votos FROM pages WHERE slug = 'privacidad.ca'
UNION ALL SELECT 'privacidad.gl' AS pagina, body LIKE '%Anotamos tamén a de cada voto a un envío. Non anotamos a IP de quen só le, e%' AS votos FROM pages WHERE slug = 'privacidad.gl'
UNION ALL SELECT 'privacidad.eu' AS pagina, body LIKE '%Bidalketa bati emandako boto bakoitzarena ere jasotzen dugu. Ez dugu irakurri besterik egiten ez duenaren IPa jasotzen, eta%' AS votos FROM pages WHERE slug = 'privacidad.eu'
UNION ALL SELECT 'privacidad.en' AS pagina, body LIKE '%We also log it for each vote on a post. We don''t log the IP of people who only read, and%' AS votos FROM pages WHERE slug = 'privacidad.en'
UNION ALL SELECT 'privacidad.fr' AS pagina, body LIKE '%Nous notons aussi celle de chaque vote sur un envoi. Nous ne notons pas l''IP de ceux qui ne font que lire, et%' AS votos FROM pages WHERE slug = 'privacidad.fr'
UNION ALL SELECT 'privacidad.de' AS pagina, body LIKE '%Wir protokollieren sie auch bei jeder Stimme für einen Beitrag. Die IP von Personen, die nur lesen, protokollieren wir nicht, und%' AS votos FROM pages WHERE slug = 'privacidad.de'
UNION ALL SELECT 'privacidad.it' AS pagina, body LIKE '%Annotiamo anche quello di ogni voto a un invio. Non annotiamo l''IP di chi si limita a leggere, e%' AS votos FROM pages WHERE slug = 'privacidad.it'
UNION ALL SELECT 'privacidad.pt' AS pagina, body LIKE '%Registamos também o de cada voto num envio. Não registamos o IP de quem apenas lê, e%' AS votos FROM pages WHERE slug = 'privacidad.pt'
UNION ALL SELECT 'privacidad.tr' AS pagina, body LIKE '%Bir gönderiye verilen her oyun IP adresini de kaydediyoruz. Yalnızca okuyanların IP adresini kaydetmiyoruz;%' AS votos FROM pages WHERE slug = 'privacidad.tr';
