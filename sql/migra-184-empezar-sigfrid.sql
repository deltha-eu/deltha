-- migra-184 (09/11/2026): entrada «Sigfrid, el ayudante del Chat» en la pagina «Como empezar» (/empezar, pages slug 'empezar' y
-- 'empezar.<idioma>', migra-122 y migra-139), en los diez idiomas. Se inserta justo antes del apartado «Mis medios y mis Substacks»
-- (despues de «El Chat»); si Angel hubiera renombrado ese titulo en admin > Paginas, se añade al final. Idempotente: solo si la
-- pagina no menciona ya a Sigfrid. No toca el resto del texto.
SET NAMES utf8mb4;

UPDATE pages SET body = IF(LOCATE('<h2>Mis medios y mis Substacks</h2>', body) > 0, REPLACE(body, '<h2>Mis medios y mis Substacks</h2>', CONCAT('<h2>Sigfrid, el ayudante del Chat</h2>
<p>En el Chat vive <b>Sigfrid</b>, un bot. Menciónalo con <b>@Sigfrid</b> y pregúntale qué hay en el radar o en portada, quién va ganando el concurso de relatos, cuál es el tema de la semana, cuál es el medio más leído, quién ha enviado más, cuántos estamos conectados o cómo van tus envíos. Entiende «hoy», «esta semana» y «este mes», y si después le dices «¿y este mes?» sabe de qué hablabas. Responde debajo de tu mensaje con enlaces a los hilos. Solo lee datos públicos: no modera, no opina y no se mete donde no lo llaman. Escribe «@Sigfrid ayuda» para ver todo lo que sabe hacer.</p>

', '<h2>Mis medios y mis Substacks</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, el ayudante del Chat</h2>
<p>En el Chat vive <b>Sigfrid</b>, un bot. Menciónalo con <b>@Sigfrid</b> y pregúntale qué hay en el radar o en portada, quién va ganando el concurso de relatos, cuál es el tema de la semana, cuál es el medio más leído, quién ha enviado más, cuántos estamos conectados o cómo van tus envíos. Entiende «hoy», «esta semana» y «este mes», y si después le dices «¿y este mes?» sabe de qué hablabas. Responde debajo de tu mensaje con enlaces a los hilos. Solo lee datos públicos: no modera, no opina y no se mete donde no lo llaman. Escribe «@Sigfrid ayuda» para ver todo lo que sabe hacer.</p>

'))
 WHERE slug = 'empezar' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>My media and my Substacks</h2>', body) > 0, REPLACE(body, '<h2>My media and my Substacks</h2>', CONCAT('<h2>Sigfrid, the Chat assistant</h2>
<p><b>Sigfrid</b>, a bot, lives in the Chat. Mention it with <b>@Sigfrid</b> and ask what is on the radar or the front page, who is winning the story contest, what this week''s theme is, which outlet is most read, who has posted the most, how many of us are online or how your posts are doing. It understands “today”, “this week” and “this month”, and if you then ask “and this month?” it knows what you were talking about. It replies under your message with links to the threads. It only reads public data: it does not moderate, does not give opinions and stays out of conversations it is not called into. Type “@Sigfrid help” to see everything it can do.</p>

', '<h2>My media and my Substacks</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, the Chat assistant</h2>
<p><b>Sigfrid</b>, a bot, lives in the Chat. Mention it with <b>@Sigfrid</b> and ask what is on the radar or the front page, who is winning the story contest, what this week''s theme is, which outlet is most read, who has posted the most, how many of us are online or how your posts are doing. It understands “today”, “this week” and “this month”, and if you then ask “and this month?” it knows what you were talking about. It replies under your message with links to the threads. It only reads public data: it does not moderate, does not give opinions and stays out of conversations it is not called into. Type “@Sigfrid help” to see everything it can do.</p>

'))
 WHERE slug = 'empezar.en' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>Els meus mitjans i els meus Substacks</h2>', body) > 0, REPLACE(body, '<h2>Els meus mitjans i els meus Substacks</h2>', CONCAT('<h2>Sigfrid, l''ajudant del Xat</h2>
<p>Al Xat hi viu <b>Sigfrid</b>, un bot. Menciona''l amb <b>@Sigfrid</b> i pregunta-li què hi ha al radar o a portada, qui va guanyant el concurs de relats, quin és el tema de la setmana, quin és el mitjà més llegit, qui ha enviat més, quants estem connectats o com van els teus enviaments. Entén «avui», «aquesta setmana» i «aquest mes», i si després li dius «i aquest mes?» sap de què parlaves. Respon sota el teu missatge amb enllaços als fils. Només llegeix dades públiques: no modera, no opina i no es fica on no el criden. Escriu «@Sigfrid ajuda» per veure tot el que sap fer.</p>

', '<h2>Els meus mitjans i els meus Substacks</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, l''ajudant del Xat</h2>
<p>Al Xat hi viu <b>Sigfrid</b>, un bot. Menciona''l amb <b>@Sigfrid</b> i pregunta-li què hi ha al radar o a portada, qui va guanyant el concurs de relats, quin és el tema de la setmana, quin és el mitjà més llegit, qui ha enviat més, quants estem connectats o com van els teus enviaments. Entén «avui», «aquesta setmana» i «aquest mes», i si després li dius «i aquest mes?» sap de què parlaves. Respon sota el teu missatge amb enllaços als fils. Només llegeix dades públiques: no modera, no opina i no es fica on no el criden. Escriu «@Sigfrid ajuda» per veure tot el que sap fer.</p>

'))
 WHERE slug = 'empezar.ca' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>Nire hedabideak eta nire Substack-ak</h2>', body) > 0, REPLACE(body, '<h2>Nire hedabideak eta nire Substack-ak</h2>', CONCAT('<h2>Sigfrid, Txataren laguntzailea</h2>
<p>Txatean <b>Sigfrid</b> bizi da, bot bat. Aipatu <b>@Sigfrid</b> idatziz eta galdetu zer dagoen radarrean edo azalean, nork irabazten duen ipuin lehiaketa, zein den asteko gaia, zein den hedabide irakurriena, nork bidali duen gehien, zenbat gauden konektatuta edo nola doazen zure bidalketak. «Gaur», «aste honetan» eta «hilabete honetan» ulertzen ditu, eta gero «eta hilabete honetan?» galdetzen badiozu, badaki zertaz ari zinen. Zure mezuaren azpian erantzuten du, harietarako estekekin. Datu publikoak bakarrik irakurtzen ditu: ez du moderatzen, ez du iritzirik ematen eta ez da sartzen deitzen ez dioten tokian. Idatzi «@Sigfrid laguntza» egin dezakeen guztia ikusteko.</p>

', '<h2>Nire hedabideak eta nire Substack-ak</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, Txataren laguntzailea</h2>
<p>Txatean <b>Sigfrid</b> bizi da, bot bat. Aipatu <b>@Sigfrid</b> idatziz eta galdetu zer dagoen radarrean edo azalean, nork irabazten duen ipuin lehiaketa, zein den asteko gaia, zein den hedabide irakurriena, nork bidali duen gehien, zenbat gauden konektatuta edo nola doazen zure bidalketak. «Gaur», «aste honetan» eta «hilabete honetan» ulertzen ditu, eta gero «eta hilabete honetan?» galdetzen badiozu, badaki zertaz ari zinen. Zure mezuaren azpian erantzuten du, harietarako estekekin. Datu publikoak bakarrik irakurtzen ditu: ez du moderatzen, ez du iritzirik ematen eta ez da sartzen deitzen ez dioten tokian. Idatzi «@Sigfrid laguntza» egin dezakeen guztia ikusteko.</p>

'))
 WHERE slug = 'empezar.eu' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>Os meus medios e os meus Substacks</h2>', body) > 0, REPLACE(body, '<h2>Os meus medios e os meus Substacks</h2>', CONCAT('<h2>Sigfrid, o axudante do Chat</h2>
<p>No Chat vive <b>Sigfrid</b>, un bot. Menciónao con <b>@Sigfrid</b> e pregúntalle que hai no radar ou en portada, quen vai gañando o concurso de relatos, cal é o tema da semana, cal é o medio máis lido, quen enviou máis, cantos estamos conectados ou como van os teus envíos. Entende «hoxe», «esta semana» e «este mes», e se despois lle dis «e este mes?» sabe do que falabas. Responde debaixo da túa mensaxe con ligazóns aos fíos. Só le datos públicos: non modera, non opina e non se mete onde non o chaman. Escribe «@Sigfrid axuda» para ver todo o que sabe facer.</p>

', '<h2>Os meus medios e os meus Substacks</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, o axudante do Chat</h2>
<p>No Chat vive <b>Sigfrid</b>, un bot. Menciónao con <b>@Sigfrid</b> e pregúntalle que hai no radar ou en portada, quen vai gañando o concurso de relatos, cal é o tema da semana, cal é o medio máis lido, quen enviou máis, cantos estamos conectados ou como van os teus envíos. Entende «hoxe», «esta semana» e «este mes», e se despois lle dis «e este mes?» sabe do que falabas. Responde debaixo da túa mensaxe con ligazóns aos fíos. Só le datos públicos: non modera, non opina e non se mete onde non o chaman. Escribe «@Sigfrid axuda» para ver todo o que sabe facer.</p>

'))
 WHERE slug = 'empezar.gl' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>Mes médias et mes Substacks</h2>', body) > 0, REPLACE(body, '<h2>Mes médias et mes Substacks</h2>', CONCAT('<h2>Sigfrid, l''assistant du Chat</h2>
<p>Dans le Chat vit <b>Sigfrid</b>, un bot. Mentionnez-le avec <b>@Sigfrid</b> et demandez-lui ce qu''il y a dans le radar ou à la une, qui gagne le concours de nouvelles, quel est le thème de la semaine, quel média est le plus lu, qui a le plus envoyé, combien nous sommes en ligne ou comment vont vos envois. Il comprend « aujourd''hui », « cette semaine » et « ce mois-ci », et si vous lui dites ensuite « et ce mois-ci ? », il sait de quoi vous parliez. Il répond sous votre message avec des liens vers les fils. Il ne lit que des données publiques : il ne modère pas, ne donne pas d''avis et ne se mêle pas de ce qui ne le regarde pas. Écrivez « @Sigfrid aide » pour voir tout ce qu''il sait faire.</p>

', '<h2>Mes médias et mes Substacks</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, l''assistant du Chat</h2>
<p>Dans le Chat vit <b>Sigfrid</b>, un bot. Mentionnez-le avec <b>@Sigfrid</b> et demandez-lui ce qu''il y a dans le radar ou à la une, qui gagne le concours de nouvelles, quel est le thème de la semaine, quel média est le plus lu, qui a le plus envoyé, combien nous sommes en ligne ou comment vont vos envois. Il comprend « aujourd''hui », « cette semaine » et « ce mois-ci », et si vous lui dites ensuite « et ce mois-ci ? », il sait de quoi vous parliez. Il répond sous votre message avec des liens vers les fils. Il ne lit que des données publiques : il ne modère pas, ne donne pas d''avis et ne se mêle pas de ce qui ne le regarde pas. Écrivez « @Sigfrid aide » pour voir tout ce qu''il sait faire.</p>

'))
 WHERE slug = 'empezar.fr' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>Meine Medien und meine Substacks</h2>', body) > 0, REPLACE(body, '<h2>Meine Medien und meine Substacks</h2>', CONCAT('<h2>Sigfrid, der Chat-Assistent</h2>
<p>Im Chat lebt <b>Sigfrid</b>, ein Bot. Erwähne ihn mit <b>@Sigfrid</b> und frag ihn, was im Radar oder auf der Titelseite ist, wer den Geschichtenwettbewerb anführt, was das Thema der Woche ist, welches Medium am meisten gelesen wird, wer am meisten eingereicht hat, wie viele online sind oder wie deine Beiträge laufen. Er versteht „heute“, „diese Woche“ und „diesen Monat“, und wenn du danach „und diesen Monat?“ fragst, weiß er, wovon du gesprochen hast. Er antwortet unter deiner Nachricht mit Links zu den Threads. Er liest nur öffentliche Daten: er moderiert nicht, gibt keine Meinung ab und mischt sich nicht ein, wo er nicht gerufen wird. Schreib „@Sigfrid hilfe“, um alles zu sehen, was er kann.</p>

', '<h2>Meine Medien und meine Substacks</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, der Chat-Assistent</h2>
<p>Im Chat lebt <b>Sigfrid</b>, ein Bot. Erwähne ihn mit <b>@Sigfrid</b> und frag ihn, was im Radar oder auf der Titelseite ist, wer den Geschichtenwettbewerb anführt, was das Thema der Woche ist, welches Medium am meisten gelesen wird, wer am meisten eingereicht hat, wie viele online sind oder wie deine Beiträge laufen. Er versteht „heute“, „diese Woche“ und „diesen Monat“, und wenn du danach „und diesen Monat?“ fragst, weiß er, wovon du gesprochen hast. Er antwortet unter deiner Nachricht mit Links zu den Threads. Er liest nur öffentliche Daten: er moderiert nicht, gibt keine Meinung ab und mischt sich nicht ein, wo er nicht gerufen wird. Schreib „@Sigfrid hilfe“, um alles zu sehen, was er kann.</p>

'))
 WHERE slug = 'empezar.de' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>Le mie testate e i miei Substack</h2>', body) > 0, REPLACE(body, '<h2>Le mie testate e i miei Substack</h2>', CONCAT('<h2>Sigfrid, l''assistente della Chat</h2>
<p>Nella Chat vive <b>Sigfrid</b>, un bot. Menzionalo con <b>@Sigfrid</b> e chiedigli cosa c''è nel radar o in prima pagina, chi sta vincendo il concorso di racconti, qual è il tema della settimana, qual è la testata più letta, chi ha inviato di più, quanti siamo online o come vanno i tuoi invii. Capisce «oggi», «questa settimana» e «questo mese», e se poi gli chiedi «e questo mese?» sa di cosa parlavi. Risponde sotto il tuo messaggio con link alle discussioni. Legge solo dati pubblici: non modera, non dà opinioni e non si intromette dove non è chiamato. Scrivi «@Sigfrid aiuto» per vedere tutto ciò che sa fare.</p>

', '<h2>Le mie testate e i miei Substack</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, l''assistente della Chat</h2>
<p>Nella Chat vive <b>Sigfrid</b>, un bot. Menzionalo con <b>@Sigfrid</b> e chiedigli cosa c''è nel radar o in prima pagina, chi sta vincendo il concorso di racconti, qual è il tema della settimana, qual è la testata più letta, chi ha inviato di più, quanti siamo online o come vanno i tuoi invii. Capisce «oggi», «questa settimana» e «questo mese», e se poi gli chiedi «e questo mese?» sa di cosa parlavi. Risponde sotto il tuo messaggio con link alle discussioni. Legge solo dati pubblici: non modera, non dà opinioni e non si intromette dove non è chiamato. Scrivi «@Sigfrid aiuto» per vedere tutto ciò che sa fare.</p>

'))
 WHERE slug = 'empezar.it' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>Os meus meios e os meus Substacks</h2>', body) > 0, REPLACE(body, '<h2>Os meus meios e os meus Substacks</h2>', CONCAT('<h2>Sigfrid, o ajudante do Chat</h2>
<p>No Chat vive <b>Sigfrid</b>, um bot. Menciona-o com <b>@Sigfrid</b> e pergunta-lhe o que há no radar ou na capa, quem vai ganhando o concurso de contos, qual é o tema da semana, qual é o meio mais lido, quem enviou mais, quantos estamos ligados ou como vão os teus envios. Entende «hoje», «esta semana» e «este mês», e se depois lhe disseres «e este mês?» sabe do que falavas. Responde por baixo da tua mensagem com ligações para as conversas. Só lê dados públicos: não modera, não opina e não se mete onde não é chamado. Escreve «@Sigfrid ajuda» para ver tudo o que sabe fazer.</p>

', '<h2>Os meus meios e os meus Substacks</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, o ajudante do Chat</h2>
<p>No Chat vive <b>Sigfrid</b>, um bot. Menciona-o com <b>@Sigfrid</b> e pergunta-lhe o que há no radar ou na capa, quem vai ganhando o concurso de contos, qual é o tema da semana, qual é o meio mais lido, quem enviou mais, quantos estamos ligados ou como vão os teus envios. Entende «hoje», «esta semana» e «este mês», e se depois lhe disseres «e este mês?» sabe do que falavas. Responde por baixo da tua mensagem com ligações para as conversas. Só lê dados públicos: não modera, não opina e não se mete onde não é chamado. Escreve «@Sigfrid ajuda» para ver tudo o que sabe fazer.</p>

'))
 WHERE slug = 'empezar.pt' AND body NOT LIKE '%Sigfrid%';

UPDATE pages SET body = IF(LOCATE('<h2>Yayınlarım ve Substack''lerim</h2>', body) > 0, REPLACE(body, '<h2>Yayınlarım ve Substack''lerim</h2>', CONCAT('<h2>Sigfrid, Sohbet yardımcısı</h2>
<p>Sohbette <b>Sigfrid</b> adında bir bot yaşıyor. <b>@Sigfrid</b> ile etiketle ve radarda ya da manşette ne olduğunu, öykü yarışmasını kimin kazanmakta olduğunu, haftanın konusunun ne olduğunu, en çok okunan yayının hangisi olduğunu, en çok kimin gönderdiğini, kaç kişinin çevrimiçi olduğunu ya da gönderilerinin nasıl gittiğini sor. «Bugün», «bu hafta» ve «bu ay» ifadelerini anlar; sonra «peki bu ay?» dersen neden bahsettiğini bilir. Mesajının altına konulara bağlantılarla yanıt verir. Yalnızca herkese açık verileri okur: moderasyon yapmaz, görüş bildirmez ve çağrılmadığı yere karışmaz. Yapabildiği her şeyi görmek için «@Sigfrid yardım» yaz.</p>

', '<h2>Yayınlarım ve Substack''lerim</h2>')), CONCAT(body, '\n\n', '<h2>Sigfrid, Sohbet yardımcısı</h2>
<p>Sohbette <b>Sigfrid</b> adında bir bot yaşıyor. <b>@Sigfrid</b> ile etiketle ve radarda ya da manşette ne olduğunu, öykü yarışmasını kimin kazanmakta olduğunu, haftanın konusunun ne olduğunu, en çok okunan yayının hangisi olduğunu, en çok kimin gönderdiğini, kaç kişinin çevrimiçi olduğunu ya da gönderilerinin nasıl gittiğini sor. «Bugün», «bu hafta» ve «bu ay» ifadelerini anlar; sonra «peki bu ay?» dersen neden bahsettiğini bilir. Mesajının altına konulara bağlantılarla yanıt verir. Yalnızca herkese açık verileri okur: moderasyon yapmaz, görüş bildirmez ve çağrılmadığı yere karışmaz. Yapabildiği her şeyi görmek için «@Sigfrid yardım» yaz.</p>

'))
 WHERE slug = 'empezar.tr' AND body NOT LIKE '%Sigfrid%';
