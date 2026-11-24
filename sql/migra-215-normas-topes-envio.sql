-- migra-215 (24/11/2026), pedido por Ángel: que los topes de envío (migra-155 y migra-214) consten en las normas de uso, en todos los
--  idiomas y leyendo las cifras de la config. Añade a la página «normas» (y a sus nueve traducciones, normas.<idioma>) el apartado
--  «Cuánto se puede enviar», con marcadores que page_body() sustituye por la config en vigor:
--    {sendday} = send_person_per_day · {senddaym} = send_person_per_day_medios · {senddays} = send_person_per_day_substacks
--    {sendhour} = send_user_per_hour · {sendanon} = send_anon_per_hour · {senddom} = send_domain_per_day
--  El apartado entra justo antes del último <h2> de cada página (el de cierre, «Y lo demás»); si una página no tuviera ningún <h2>,
--  al final. No toca nada más del texto, así que respeta lo que se haya editado en admin → Páginas.
--  Idempotente: una página que ya lleva {sendday} no se vuelve a tocar.
SET NAMES utf8mb4;

SET @t = '<h2>Cuánto se puede enviar</h2>\n<p>Para que la portada dependa de lo que la comunidad vota y no de quién envía más, los envíos tienen tope. Cada persona puede enviar hasta <b>{sendday} noticias en 24 horas</b>, y de ellas como mucho {senddaym} a la portada de Medios y {senddays} a la de Substacks. Además, no se pueden enviar más de {sendhour} por hora con cuenta ({sendanon} sin ella), y de un mismo medio no se admiten más de {senddom} noticias al día, las envíe quien las envíe, salvo que el medio esté en el catálogo de fuentes.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>Quant es pot enviar</h2>\n<p>Perquè la portada depengui del que la comunitat vota i no de qui envia més, els enviaments tenen límit. Cada persona pot enviar fins a <b>{sendday} notícies en 24 hores</b>, i d''aquestes com a màxim {senddaym} a la portada de Mitjans i {senddays} a la de Substacks. A més, no se''n poden enviar més de {sendhour} per hora amb compte ({sendanon} sense), i d''un mateix mitjà no s''admeten més de {senddom} notícies al dia, les enviï qui les enviï, tret que el mitjà sigui al catàleg de fonts.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.ca' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.ca' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>Canto se pode enviar</h2>\n<p>Para que a portada dependa do que a comunidade vota e non de quen envía máis, os envíos teñen tope. Cada persoa pode enviar ata <b>{sendday} novas en 24 horas</b>, e delas como moito {senddaym} á portada de Medios e {senddays} á de Substacks. Ademais, non se poden enviar máis de {sendhour} por hora con conta ({sendanon} sen ela), e dun mesmo medio non se admiten máis de {senddom} novas ao día, as envíe quen as envíe, agás que o medio estea no catálogo de fontes.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.gl' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.gl' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>Zenbat bidal daitekeen</h2>\n<p>Azala komunitateak bozkatzen duenaren araberakoa izan dadin, eta ez gehien bidaltzen duenaren araberakoa, bidalketek muga dute. Pertsona bakoitzak gehienez <b>{sendday} albiste bidal ditzake 24 orduan</b>, eta horietatik gehienez {senddaym} Hedabideak azalera eta {senddays} Substackak azalera. Gainera, ezin dira orduko {sendhour} baino gehiago bidali kontuarekin ({sendanon} konturik gabe), eta hedabide beretik ez dira egunean {senddom} albiste baino gehiago onartzen, edonork bidalita ere, hedabidea iturrien katalogoan egon ezean.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.eu' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.eu' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>How much you can submit</h2>\n<p>So that the front page depends on what the community votes for and not on who submits the most, submissions are capped. Each person can submit up to <b>{sendday} stories in 24 hours</b>, of which at most {senddaym} to the Media front page and {senddays} to the Substacks one. In addition, no more than {sendhour} per hour with an account ({sendanon} without one), and no more than {senddom} stories a day are accepted from the same outlet, whoever submits them, unless the outlet is in the source catalogue.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.en' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.en' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>Combien on peut envoyer</h2>\n<p>Pour que la une dépende de ce que la communauté vote et non de qui envoie le plus, les envois sont plafonnés. Chaque personne peut envoyer jusqu''à <b>{sendday} articles en 24 heures</b>, dont au maximum {senddaym} vers la une Médias et {senddays} vers la une Substacks. De plus, pas plus de {sendhour} par heure avec un compte ({sendanon} sans compte), et on n''accepte pas plus de {senddom} articles par jour d''un même média, quel que soit l''expéditeur, sauf si le média figure au catalogue des sources.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.fr' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.fr' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>Wie viel man einsenden kann</h2>\n<p>Damit die Titelseite davon abhängt, wofür die Community stimmt, und nicht davon, wer am meisten einsendet, sind Einsendungen begrenzt. Jede Person kann bis zu <b>{sendday} Nachrichten in 24 Stunden</b> einsenden, davon höchstens {senddaym} für die Medien-Titelseite und {senddays} für die Substacks-Titelseite. Außerdem nicht mehr als {sendhour} pro Stunde mit Konto ({sendanon} ohne), und von demselben Medium werden pro Tag höchstens {senddom} Nachrichten angenommen, egal wer sie einsendet, es sei denn, das Medium steht im Quellenkatalog.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.de' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.de' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>Quanto si può inviare</h2>\n<p>Perché la prima pagina dipenda da ciò che la comunità vota e non da chi invia di più, gli invii hanno un limite. Ogni persona può inviare fino a <b>{sendday} notizie in 24 ore</b>, di cui al massimo {senddaym} alla prima pagina Media e {senddays} a quella Substack. Inoltre, non più di {sendhour} all''ora con un account ({sendanon} senza), e di una stessa testata non si accettano più di {senddom} notizie al giorno, chiunque le invii, a meno che la testata sia nel catalogo delle fonti.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.it' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.it' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>Quanto se pode enviar</h2>\n<p>Para que a capa dependa do que a comunidade vota e não de quem envia mais, os envios têm limite. Cada pessoa pode enviar até <b>{sendday} notícias em 24 horas</b>, e dessas no máximo {senddaym} para a capa de Meios e {senddays} para a de Newsletters. Além disso, não se podem enviar mais de {sendhour} por hora com conta ({sendanon} sem ela), e de um mesmo meio não se aceitam mais de {senddom} notícias por dia, envie-as quem as enviar, salvo se o meio estiver no catálogo de fontes.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.pt' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.pt' AND body NOT LIKE '%{sendday}%';

SET @t = '<h2>Ne kadar gönderilebilir</h2>\n<p>Ana sayfanın kimin daha çok gönderdiğine değil, topluluğun neye oy verdiğine bağlı olması için gönderilerin bir sınırı vardır. Her kişi <b>24 saatte en fazla {sendday} haber</b> gönderebilir; bunların en fazla {senddaym} tanesi Medya ana sayfasına, {senddays} tanesi Substacks ana sayfasına gidebilir. Ayrıca hesapla saatte en fazla {sendhour} ({sendanon} hesapsız) gönderilebilir ve aynı yayından, kim gönderirse göndersin, günde en fazla {senddom} haber kabul edilir; yayın kaynak kataloğundaysa bu sınır uygulanmaz.</p>\n\n';
UPDATE pages SET body = CONCAT(LEFT(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 3), @t, SUBSTRING(body, CHAR_LENGTH(body) - LOCATE('>2h<', REVERSE(body)) - 2))
 WHERE slug = 'normas.tr' AND body NOT LIKE '%{sendday}%' AND body LIKE '%<h2>%';
UPDATE pages SET body = CONCAT(body, '\n\n', @t) WHERE slug = 'normas.tr' AND body NOT LIKE '%{sendday}%';

