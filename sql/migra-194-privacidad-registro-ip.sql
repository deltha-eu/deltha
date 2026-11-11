-- migra-194 (11/11/2026): la política de privacidad cuenta el registro de IP de la migra-193, en los diez idiomas.
--  · Apartado nuevo «Registro de direcciones IP» (id="registro-ip"), antes de «Cuánto tiempo»: qué se anota, para qué (multicuentas y
--    requerimientos formales de un juzgado o de la policía), que se borra a los {ipdays} días (marcador nuevo de page_body = config.ip_log_days),
--    quién lo consulta y la base jurídica. Si no encuentra el encabezado «Cuánto tiempo», lo añade al final.
--  · «Si comentas sin cuenta»: ya no dice «nunca la dirección IP»; remite al apartado nuevo.
--  · «Lo que no hacemos»: no se ceden datos a nadie, salvo requerimiento formal de un juzgado o de la policía.
--  · Fecha de última actualización: noviembre de 2026.
-- REPLACE sobre fragmentos del texto en vigor: si un párrafo se editó a mano en admin -> Páginas, su REPLACE no hace nada y hay que
-- retocarlo a mano. El SELECT del final dice, idioma por idioma, qué ha quedado aplicado (1) y qué no (0). Idempotente.
SET NAMES utf8mb4;

-- privacidad
UPDATE pages SET body = REPLACE(body, 'No vendemos ni cedemos datos a nadie.', 'No vendemos datos ni los cedemos a nadie; solo los entregaríamos a un juzgado o a la policía ante un requerimiento formal, porque la ley obliga.') WHERE slug = 'privacidad';
UPDATE pages SET body = REPLACE(body, 'dentro de un hilo), nunca la dirección IP.', 'dentro de un hilo). La dirección IP no forma parte del alias ni se publica; se anota aparte, como se explica en «Registro de direcciones IP».') WHERE slug = 'privacidad';
UPDATE pages SET body = REPLACE(body, '<h2>Cuánto tiempo</h2>', '<h2 id="registro-ip">Registro de direcciones IP</h2>
<p>Cuando te registras, inicias sesión o publicas algo (un envío, un artículo, un comentario o un mensaje del Chat), con cuenta o sin ella, anotamos la dirección IP desde la que lo haces y la fecha. Lo hacemos para dos cosas y ninguna más: detectar a quien maneja varias cuentas a la vez y poder atender un requerimiento formal de un juzgado o de la policía. No anotamos la IP de quien solo lee ni la de los votos, y no la usamos para estadísticas, perfiles ni publicidad.</p>
<p>Cada anotación se borra sola a los {ipdays} días. Solo la administración del sitio puede consultar ese registro, y cada consulta queda apuntada. La base jurídica es nuestro interés legítimo en mantener el sitio seguro y dentro de la ley; puedes oponerte escribiendo a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Cuánto tiempo</h2>') WHERE slug = 'privacidad' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">Registro de direcciones IP</h2>
<p>Cuando te registras, inicias sesión o publicas algo (un envío, un artículo, un comentario o un mensaje del Chat), con cuenta o sin ella, anotamos la dirección IP desde la que lo haces y la fecha. Lo hacemos para dos cosas y ninguna más: detectar a quien maneja varias cuentas a la vez y poder atender un requerimiento formal de un juzgado o de la policía. No anotamos la IP de quien solo lee ni la de los votos, y no la usamos para estadísticas, perfiles ni publicidad.</p>
<p>Cada anotación se borra sola a los {ipdays} días. Solo la administración del sitio puede consultar ese registro, y cada consulta queda apuntada. La base jurídica es nuestro interés legítimo en mantener el sitio seguro y dentro de la ley; puedes oponerte escribiendo a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>') WHERE slug = 'privacidad' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Última actualización: octubre de 2026.', 'Última actualización: noviembre de 2026.') WHERE slug = 'privacidad';

-- privacidad.ca
UPDATE pages SET body = REPLACE(body, 'No venem ni cedim dades a ningú.', 'No venem dades ni les cedim a ningú; només les lliuraríem a un jutjat o a la policia davant d''un requeriment formal, perquè la llei hi obliga.') WHERE slug = 'privacidad.ca';
UPDATE pages SET body = REPLACE(body, 'dins d''un fil), mai l''adreça IP.', 'dins d''un fil). L''adreça IP no forma part de l''àlies ni es publica; s''anota a part, com s''explica a «Registre d''adreces IP».') WHERE slug = 'privacidad.ca';
UPDATE pages SET body = REPLACE(body, '<h2>Quant de temps</h2>', '<h2 id="registro-ip">Registre d''adreces IP</h2>
<p>Quan et registres, inicies sessió o publiques alguna cosa (un enviament, un article, un comentari o un missatge del Chat), amb compte o sense, anotem l''adreça IP des de la qual ho fas i la data. Ho fem per a dues coses i cap més: detectar qui fa servir diversos comptes alhora i poder atendre un requeriment formal d''un jutjat o de la policia. No anotem la IP de qui només llegeix ni la dels vots, i no la fem servir per a estadístiques, perfils ni publicitat.</p>
<p>Cada anotació s''esborra sola al cap de {ipdays} dies. Només l''administració del lloc pot consultar aquest registre, i cada consulta queda apuntada. La base jurídica és el nostre interès legítim a mantenir el lloc segur i dins de la llei; t''hi pots oposar escrivint a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Quant de temps</h2>') WHERE slug = 'privacidad.ca' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">Registre d''adreces IP</h2>
<p>Quan et registres, inicies sessió o publiques alguna cosa (un enviament, un article, un comentari o un missatge del Chat), amb compte o sense, anotem l''adreça IP des de la qual ho fas i la data. Ho fem per a dues coses i cap més: detectar qui fa servir diversos comptes alhora i poder atendre un requeriment formal d''un jutjat o de la policia. No anotem la IP de qui només llegeix ni la dels vots, i no la fem servir per a estadístiques, perfils ni publicitat.</p>
<p>Cada anotació s''esborra sola al cap de {ipdays} dies. Només l''administració del lloc pot consultar aquest registre, i cada consulta queda apuntada. La base jurídica és el nostre interès legítim a mantenir el lloc segur i dins de la llei; t''hi pots oposar escrivint a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>') WHERE slug = 'privacidad.ca' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Darrera actualització: octubre de 2026.', 'Darrera actualització: novembre de 2026.') WHERE slug = 'privacidad.ca';

-- privacidad.gl
UPDATE pages SET body = REPLACE(body, 'Non vendemos nin cedemos datos a ninguén.', 'Non vendemos datos nin os cedemos a ninguén; só llos entregariamos a un xulgado ou á policía ante un requirimento formal, porque a lei obriga.') WHERE slug = 'privacidad.gl';
UPDATE pages SET body = REPLACE(body, 'dentro dun fío), nunca o enderezo IP.', 'dentro dun fío). O enderezo IP non forma parte do alias nin se publica; anótase á parte, como se explica en «Rexistro de enderezos IP».') WHERE slug = 'privacidad.gl';
UPDATE pages SET body = REPLACE(body, '<h2>Canto tempo</h2>', '<h2 id="registro-ip">Rexistro de enderezos IP</h2>
<p>Cando te rexistras, inicias sesión ou publicas algo (un envío, un artigo, un comentario ou unha mensaxe do Chat), con conta ou sen ela, anotamos o enderezo IP desde o que o fas e a data. Facémolo para dúas cousas e ningunha máis: detectar a quen manexa varias contas á vez e poder atender un requirimento formal dun xulgado ou da policía. Non anotamos a IP de quen só le nin a dos votos, e non a usamos para estatísticas, perfís nin publicidade.</p>
<p>Cada anotación bórrase soa aos {ipdays} días. Só a administración do sitio pode consultar ese rexistro, e cada consulta queda apuntada. A base xurídica é o noso interese lexítimo en manter o sitio seguro e dentro da lei; podes opoñerte escribindo a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Canto tempo</h2>') WHERE slug = 'privacidad.gl' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">Rexistro de enderezos IP</h2>
<p>Cando te rexistras, inicias sesión ou publicas algo (un envío, un artigo, un comentario ou unha mensaxe do Chat), con conta ou sen ela, anotamos o enderezo IP desde o que o fas e a data. Facémolo para dúas cousas e ningunha máis: detectar a quen manexa varias contas á vez e poder atender un requirimento formal dun xulgado ou da policía. Non anotamos a IP de quen só le nin a dos votos, e non a usamos para estatísticas, perfís nin publicidade.</p>
<p>Cada anotación bórrase soa aos {ipdays} días. Só a administración do sitio pode consultar ese rexistro, e cada consulta queda apuntada. A base xurídica é o noso interese lexítimo en manter o sitio seguro e dentro da lei; podes opoñerte escribindo a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>') WHERE slug = 'privacidad.gl' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Última actualización: outubro de 2026.', 'Última actualización: novembro de 2026.') WHERE slug = 'privacidad.gl';

-- privacidad.eu
UPDATE pages SET body = REPLACE(body, 'Ez diogu inori daturik saltzen ez lagatzen.', 'Ez diogu inori daturik saltzen ez lagatzen; epaitegi bati edo poliziari bakarrik emango genizkioke, errekerimendu formal baten aurrean, legeak hala behartzen duelako.') WHERE slug = 'privacidad.eu';
UPDATE pages SET body = REPLACE(body, 'hari batean berbera izan dadin), inoiz ez IP helbidea.', 'hari batean berbera izan dadin). IP helbidea ez da ezizenaren parte eta ez da argitaratzen; aparte jasotzen da, «IP helbideen erregistroa» atalean azaltzen den bezala.') WHERE slug = 'privacidad.eu';
UPDATE pages SET body = REPLACE(body, '<h2>Zenbat denboraz</h2>', '<h2 id="registro-ip">IP helbideen erregistroa</h2>
<p>Izena ematen duzunean, saioa hasten duzunean edo zerbait argitaratzen duzunean (bidalketa bat, artikulu bat, iruzkin bat edo Chateko mezu bat), kontuarekin edo konturik gabe, hori zein IP helbidetatik egiten duzun eta data jasotzen ditugu. Bi gauzatarako egiten dugu, eta ez beste ezertarako: aldi berean hainbat kontu erabiltzen dituena detektatzeko eta epaitegi baten edo poliziaren errekerimendu formal bati erantzun ahal izateko. Ez dugu irakurri besterik egiten ez duenaren IPa jasotzen, ezta botoena ere, eta ez dugu estatistiketarako, profiletarako edo publizitaterako erabiltzen.</p>
<p>Ohar bakoitza bere kabuz ezabatzen da {ipdays} egunera. Guneko administrazioak bakarrik kontsulta dezake erregistro hori, eta kontsulta bakoitza jasota geratzen da. Oinarri juridikoa gunea seguru eta legearen barruan mantentzeko dugun interes legitimoa da; aurka egin dezakezu <a href="mailto:info@deltha.eu">info@deltha.eu</a> helbidera idatziz.</p>

<h2>Zenbat denboraz</h2>') WHERE slug = 'privacidad.eu' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">IP helbideen erregistroa</h2>
<p>Izena ematen duzunean, saioa hasten duzunean edo zerbait argitaratzen duzunean (bidalketa bat, artikulu bat, iruzkin bat edo Chateko mezu bat), kontuarekin edo konturik gabe, hori zein IP helbidetatik egiten duzun eta data jasotzen ditugu. Bi gauzatarako egiten dugu, eta ez beste ezertarako: aldi berean hainbat kontu erabiltzen dituena detektatzeko eta epaitegi baten edo poliziaren errekerimendu formal bati erantzun ahal izateko. Ez dugu irakurri besterik egiten ez duenaren IPa jasotzen, ezta botoena ere, eta ez dugu estatistiketarako, profiletarako edo publizitaterako erabiltzen.</p>
<p>Ohar bakoitza bere kabuz ezabatzen da {ipdays} egunera. Guneko administrazioak bakarrik kontsulta dezake erregistro hori, eta kontsulta bakoitza jasota geratzen da. Oinarri juridikoa gunea seguru eta legearen barruan mantentzeko dugun interes legitimoa da; aurka egin dezakezu <a href="mailto:info@deltha.eu">info@deltha.eu</a> helbidera idatziz.</p>') WHERE slug = 'privacidad.eu' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Azken eguneratzea: 2026ko urria.', 'Azken eguneratzea: 2026ko azaroa.') WHERE slug = 'privacidad.eu';

-- privacidad.en
UPDATE pages SET body = REPLACE(body, 'We don''t sell or hand over data to anyone.', 'We don''t sell data or hand it over to anyone; we would only give it to a court or the police in response to a formal request, because the law requires it.') WHERE slug = 'privacidad.en';
UPDATE pages SET body = REPLACE(body, 'within a thread), never the IP address.', 'within a thread). The IP address is not part of the alias and is not published; it is logged separately, as explained under “IP address log”.') WHERE slug = 'privacidad.en';
UPDATE pages SET body = REPLACE(body, '<h2>How long</h2>', '<h2 id="registro-ip">IP address log</h2>
<p>When you sign up, log in or post something (a submission, an article, a comment or a Chat message), with or without an account, we log the IP address you do it from and the date. We do this for two purposes and no others: to detect people running several accounts at once and to be able to respond to a formal request from a court or the police. We don''t log the IP of people who only read, or of votes, and we don''t use it for statistics, profiling or advertising.</p>
<p>Each entry is deleted automatically after {ipdays} days. Only the site''s administration can consult this log, and every lookup is recorded. The legal basis is our legitimate interest in keeping the site safe and within the law; you can object by writing to <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>How long</h2>') WHERE slug = 'privacidad.en' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">IP address log</h2>
<p>When you sign up, log in or post something (a submission, an article, a comment or a Chat message), with or without an account, we log the IP address you do it from and the date. We do this for two purposes and no others: to detect people running several accounts at once and to be able to respond to a formal request from a court or the police. We don''t log the IP of people who only read, or of votes, and we don''t use it for statistics, profiling or advertising.</p>
<p>Each entry is deleted automatically after {ipdays} days. Only the site''s administration can consult this log, and every lookup is recorded. The legal basis is our legitimate interest in keeping the site safe and within the law; you can object by writing to <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>') WHERE slug = 'privacidad.en' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Last updated: October 2026.', 'Last updated: November 2026.') WHERE slug = 'privacidad.en';

-- privacidad.fr
UPDATE pages SET body = REPLACE(body, 'Nous ne vendons ni ne cédons de données à personne.', 'Nous ne vendons pas de données et ne les cédons à personne ; nous ne les remettrions qu''à un tribunal ou à la police sur réquisition formelle, parce que la loi l''impose.') WHERE slug = 'privacidad.fr';
UPDATE pages SET body = REPLACE(body, 'dans un fil), jamais l''adresse IP.', 'dans un fil). L''adresse IP ne fait pas partie de l''alias et n''est pas publiée ; elle est notée à part, comme expliqué dans « Registre des adresses IP ».') WHERE slug = 'privacidad.fr';
UPDATE pages SET body = REPLACE(body, '<h2>Combien de temps</h2>', '<h2 id="registro-ip">Registre des adresses IP</h2>
<p>Quand tu t''inscris, te connectes ou publies quelque chose (un envoi, un article, un commentaire ou un message du Chat), avec ou sans compte, nous notons l''adresse IP depuis laquelle tu le fais et la date. Nous le faisons pour deux choses et rien d''autre : repérer ceux qui utilisent plusieurs comptes à la fois et pouvoir répondre à une réquisition formelle d''un tribunal ou de la police. Nous ne notons pas l''IP de ceux qui ne font que lire ni celle des votes, et nous ne l''utilisons ni pour des statistiques, ni pour du profilage, ni pour de la publicité.</p>
<p>Chaque entrée s''efface toute seule au bout de {ipdays} jours. Seule l''administration du site peut consulter ce registre, et chaque consultation est consignée. La base juridique est notre intérêt légitime à maintenir le site sûr et dans le cadre de la loi ; tu peux t''y opposer en écrivant à <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Combien de temps</h2>') WHERE slug = 'privacidad.fr' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">Registre des adresses IP</h2>
<p>Quand tu t''inscris, te connectes ou publies quelque chose (un envoi, un article, un commentaire ou un message du Chat), avec ou sans compte, nous notons l''adresse IP depuis laquelle tu le fais et la date. Nous le faisons pour deux choses et rien d''autre : repérer ceux qui utilisent plusieurs comptes à la fois et pouvoir répondre à une réquisition formelle d''un tribunal ou de la police. Nous ne notons pas l''IP de ceux qui ne font que lire ni celle des votes, et nous ne l''utilisons ni pour des statistiques, ni pour du profilage, ni pour de la publicité.</p>
<p>Chaque entrée s''efface toute seule au bout de {ipdays} jours. Seule l''administration du site peut consulter ce registre, et chaque consultation est consignée. La base juridique est notre intérêt légitime à maintenir le site sûr et dans le cadre de la loi ; tu peux t''y opposer en écrivant à <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>') WHERE slug = 'privacidad.fr' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Dernière mise à jour : octobre 2026.', 'Dernière mise à jour : novembre 2026.') WHERE slug = 'privacidad.fr';

-- privacidad.de
UPDATE pages SET body = REPLACE(body, 'Wir verkaufen keine Daten und geben sie an niemanden weiter.', 'Wir verkaufen keine Daten und geben sie an niemanden weiter; nur einem Gericht oder der Polizei würden wir sie auf förmliche Anordnung herausgeben, weil das Gesetz es verlangt.') WHERE slug = 'privacidad.de';
UPDATE pages SET body = REPLACE(body, 'innerhalb eines Threads gleich bleibt), niemals die IP-Adresse.', 'innerhalb eines Threads gleich bleibt). Die IP-Adresse ist nicht Teil des Alias und wird nicht veröffentlicht; sie wird gesondert protokolliert, wie unter „Protokoll der IP-Adressen“ erklärt.') WHERE slug = 'privacidad.de';
UPDATE pages SET body = REPLACE(body, '<h2>Wie lange</h2>', '<h2 id="registro-ip">Protokoll der IP-Adressen</h2>
<p>Wenn du dich registrierst, dich anmeldest oder etwas veröffentlichst (eine Einreichung, einen Artikel, einen Kommentar oder eine Chat-Nachricht), mit oder ohne Konto, protokollieren wir die IP-Adresse, von der aus du das tust, und das Datum. Das tun wir für zwei Zwecke und keine weiteren: um zu erkennen, wer mehrere Konten gleichzeitig betreibt, und um einer förmlichen Anordnung eines Gerichts oder der Polizei nachkommen zu können. Die IP von Personen, die nur lesen, und die von Abstimmungen protokollieren wir nicht, und wir verwenden sie weder für Statistiken noch für Profile oder Werbung.</p>
<p>Jeder Eintrag wird nach {ipdays} Tagen automatisch gelöscht. Nur die Administration der Seite kann dieses Protokoll einsehen, und jede Abfrage wird festgehalten. Rechtsgrundlage ist unser berechtigtes Interesse, die Seite sicher und gesetzeskonform zu halten; du kannst widersprechen, indem du an <a href="mailto:info@deltha.eu">info@deltha.eu</a> schreibst.</p>

<h2>Wie lange</h2>') WHERE slug = 'privacidad.de' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">Protokoll der IP-Adressen</h2>
<p>Wenn du dich registrierst, dich anmeldest oder etwas veröffentlichst (eine Einreichung, einen Artikel, einen Kommentar oder eine Chat-Nachricht), mit oder ohne Konto, protokollieren wir die IP-Adresse, von der aus du das tust, und das Datum. Das tun wir für zwei Zwecke und keine weiteren: um zu erkennen, wer mehrere Konten gleichzeitig betreibt, und um einer förmlichen Anordnung eines Gerichts oder der Polizei nachkommen zu können. Die IP von Personen, die nur lesen, und die von Abstimmungen protokollieren wir nicht, und wir verwenden sie weder für Statistiken noch für Profile oder Werbung.</p>
<p>Jeder Eintrag wird nach {ipdays} Tagen automatisch gelöscht. Nur die Administration der Seite kann dieses Protokoll einsehen, und jede Abfrage wird festgehalten. Rechtsgrundlage ist unser berechtigtes Interesse, die Seite sicher und gesetzeskonform zu halten; du kannst widersprechen, indem du an <a href="mailto:info@deltha.eu">info@deltha.eu</a> schreibst.</p>') WHERE slug = 'privacidad.de' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Letzte Aktualisierung: Oktober 2026.', 'Letzte Aktualisierung: November 2026.') WHERE slug = 'privacidad.de';

-- privacidad.it
UPDATE pages SET body = REPLACE(body, 'Non vendiamo né cediamo dati a nessuno.', 'Non vendiamo dati né li cediamo a nessuno; li consegneremmo soltanto a un tribunale o alla polizia a fronte di una richiesta formale, perché la legge lo impone.') WHERE slug = 'privacidad.it';
UPDATE pages SET body = REPLACE(body, 'all''interno di una discussione), mai l''indirizzo IP.', 'all''interno di una discussione). L''indirizzo IP non fa parte dell''alias e non viene pubblicato; viene annotato a parte, come spiegato in «Registro degli indirizzi IP».') WHERE slug = 'privacidad.it';
UPDATE pages SET body = REPLACE(body, '<h2>Per quanto tempo</h2>', '<h2 id="registro-ip">Registro degli indirizzi IP</h2>
<p>Quando ti registri, accedi o pubblichi qualcosa (un invio, un articolo, un commento o un messaggio della Chat), con o senza account, annotiamo l''indirizzo IP da cui lo fai e la data. Lo facciamo per due scopi e nessun altro: individuare chi usa più account contemporaneamente e poter rispondere a una richiesta formale di un tribunale o della polizia. Non annotiamo l''IP di chi si limita a leggere né quello dei voti, e non lo usiamo per statistiche, profilazione o pubblicità.</p>
<p>Ogni annotazione si cancella da sola dopo {ipdays} giorni. Solo l''amministrazione del sito può consultare questo registro, e ogni consultazione viene registrata. La base giuridica è il nostro legittimo interesse a mantenere il sito sicuro e nel rispetto della legge; puoi opporti scrivendo a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Per quanto tempo</h2>') WHERE slug = 'privacidad.it' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">Registro degli indirizzi IP</h2>
<p>Quando ti registri, accedi o pubblichi qualcosa (un invio, un articolo, un commento o un messaggio della Chat), con o senza account, annotiamo l''indirizzo IP da cui lo fai e la data. Lo facciamo per due scopi e nessun altro: individuare chi usa più account contemporaneamente e poter rispondere a una richiesta formale di un tribunale o della polizia. Non annotiamo l''IP di chi si limita a leggere né quello dei voti, e non lo usiamo per statistiche, profilazione o pubblicità.</p>
<p>Ogni annotazione si cancella da sola dopo {ipdays} giorni. Solo l''amministrazione del sito può consultare questo registro, e ogni consultazione viene registrata. La base giuridica è il nostro legittimo interesse a mantenere il sito sicuro e nel rispetto della legge; puoi opporti scrivendo a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>') WHERE slug = 'privacidad.it' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Ultimo aggiornamento: ottobre 2026.', 'Ultimo aggiornamento: novembre 2026.') WHERE slug = 'privacidad.it';

-- privacidad.pt
UPDATE pages SET body = REPLACE(body, 'Não vendemos nem cedemos dados a ninguém.', 'Não vendemos dados nem os cedemos a ninguém; só os entregaríamos a um tribunal ou à polícia perante um pedido formal, porque a lei o exige.') WHERE slug = 'privacidad.pt';
UPDATE pages SET body = REPLACE(body, 'dentro de uma conversa), nunca o endereço IP.', 'dentro de uma conversa). O endereço IP não faz parte do alias nem é publicado; é registado à parte, como se explica em «Registo de endereços IP».') WHERE slug = 'privacidad.pt';
UPDATE pages SET body = REPLACE(body, '<h2>Durante quanto tempo</h2>', '<h2 id="registro-ip">Registo de endereços IP</h2>
<p>Quando se regista, inicia sessão ou publica algo (um envio, um artigo, um comentário ou uma mensagem do Chat), com ou sem conta, registamos o endereço IP a partir do qual o faz e a data. Fazemo-lo para duas coisas e nenhuma outra: detetar quem usa várias contas ao mesmo tempo e poder responder a um pedido formal de um tribunal ou da polícia. Não registamos o IP de quem apenas lê nem o dos votos, e não o usamos para estatísticas, perfis ou publicidade.</p>
<p>Cada registo é apagado automaticamente ao fim de {ipdays} dias. Só a administração do site pode consultar esse registo, e cada consulta fica anotada. A base jurídica é o nosso interesse legítimo em manter o site seguro e dentro da lei; pode opor-se escrevendo para <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Durante quanto tempo</h2>') WHERE slug = 'privacidad.pt' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">Registo de endereços IP</h2>
<p>Quando se regista, inicia sessão ou publica algo (um envio, um artigo, um comentário ou uma mensagem do Chat), com ou sem conta, registamos o endereço IP a partir do qual o faz e a data. Fazemo-lo para duas coisas e nenhuma outra: detetar quem usa várias contas ao mesmo tempo e poder responder a um pedido formal de um tribunal ou da polícia. Não registamos o IP de quem apenas lê nem o dos votos, e não o usamos para estatísticas, perfis ou publicidade.</p>
<p>Cada registo é apagado automaticamente ao fim de {ipdays} dias. Só a administração do site pode consultar esse registo, e cada consulta fica anotada. A base jurídica é o nosso interesse legítimo em manter o site seguro e dentro da lei; pode opor-se escrevendo para <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>') WHERE slug = 'privacidad.pt' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Última atualização: outubro de 2026.', 'Última atualização: novembro de 2026.') WHERE slug = 'privacidad.pt';

-- privacidad.tr
UPDATE pages SET body = REPLACE(body, 'Verileri kimseye satmıyor ve kimseye devretmiyoruz.', 'Verileri kimseye satmıyor ve kimseye devretmiyoruz; yalnızca bir mahkemenin veya polisin resmî talebi üzerine, yasa gerektirdiği için teslim ederiz.') WHERE slug = 'privacidad.tr';
UPDATE pages SET body = REPLACE(body, '(takma adın bir başlık içinde aynı kalsın diye), IP adresini ise asla.', '(takma adın bir başlık içinde aynı kalsın diye). IP adresi takma adın parçası değildir ve yayımlanmaz; «IP adresi kaydı» bölümünde açıklandığı gibi ayrıca kaydedilir.') WHERE slug = 'privacidad.tr';
UPDATE pages SET body = REPLACE(body, '<h2>Ne kadar süreyle</h2>', '<h2 id="registro-ip">IP adresi kaydı</h2>
<p>Kaydolduğunda, oturum açtığında veya bir şey yayımladığında (bir gönderi, bir makale, bir yorum veya bir Sohbet mesajı), hesabın olsun ya da olmasın, bunu hangi IP adresinden yaptığını ve tarihi kaydediyoruz. Bunu yalnızca iki amaçla yapıyoruz: aynı anda birden fazla hesap kullananları tespit etmek ve bir mahkemenin veya polisin resmî talebine yanıt verebilmek. Yalnızca okuyanların IP adresini ve oyların IP adresini kaydetmiyoruz; bu veriyi istatistik, profil çıkarma veya reklam için kullanmıyoruz.</p>
<p>Her kayıt {ipdays} gün sonra kendiliğinden silinir. Bu kayda yalnızca site yönetimi bakabilir ve her sorgu not edilir. Hukuki dayanak, siteyi güvenli ve yasaya uygun tutmaktaki meşru menfaatimizdir; <a href="mailto:info@deltha.eu">info@deltha.eu</a> adresine yazarak itiraz edebilirsin.</p>

<h2>Ne kadar süreyle</h2>') WHERE slug = 'privacidad.tr' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = CONCAT(body, '

<h2 id="registro-ip">IP adresi kaydı</h2>
<p>Kaydolduğunda, oturum açtığında veya bir şey yayımladığında (bir gönderi, bir makale, bir yorum veya bir Sohbet mesajı), hesabın olsun ya da olmasın, bunu hangi IP adresinden yaptığını ve tarihi kaydediyoruz. Bunu yalnızca iki amaçla yapıyoruz: aynı anda birden fazla hesap kullananları tespit etmek ve bir mahkemenin veya polisin resmî talebine yanıt verebilmek. Yalnızca okuyanların IP adresini ve oyların IP adresini kaydetmiyoruz; bu veriyi istatistik, profil çıkarma veya reklam için kullanmıyoruz.</p>
<p>Her kayıt {ipdays} gün sonra kendiliğinden silinir. Bu kayda yalnızca site yönetimi bakabilir ve her sorgu not edilir. Hukuki dayanak, siteyi güvenli ve yasaya uygun tutmaktaki meşru menfaatimizdir; <a href="mailto:info@deltha.eu">info@deltha.eu</a> adresine yazarak itiraz edebilirsin.</p>') WHERE slug = 'privacidad.tr' AND body NOT LIKE '%id="registro-ip"%';
UPDATE pages SET body = REPLACE(body, 'Son güncelleme: Ekim 2026.', 'Son güncelleme: Kasım 2026.') WHERE slug = 'privacidad.tr';

-- comprobación: 1 = aplicado, 0 = ese párrafo se había editado a mano y hay que retocarlo en admin -> Páginas
SELECT 'privacidad' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%explica en «Registro de direcciones IP».%' AS sin_cuenta, body LIKE '%uerimiento formal, porque la ley obliga.%' AS no_cedemos, body LIKE '%Última actualización: noviembre de 2026.%' AS fecha FROM pages WHERE slug = 'privacidad'
UNION ALL SELECT 'privacidad.ca' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%com s''explica a «Registre d''adreces IP».%' AS sin_cuenta, body LIKE '%riment formal, perquè la llei hi obliga.%' AS no_cedemos, body LIKE '%Darrera actualització: novembre de 2026.%' AS fecha FROM pages WHERE slug = 'privacidad.ca'
UNION ALL SELECT 'privacidad.gl' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%e explica en «Rexistro de enderezos IP».%' AS sin_cuenta, body LIKE '%equirimento formal, porque a lei obriga.%' AS no_cedemos, body LIKE '%Última actualización: novembro de 2026.%' AS fecha FROM pages WHERE slug = 'privacidad.gl'
UNION ALL SELECT 'privacidad.eu' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%rregistroa» atalean azaltzen den bezala.%' AS sin_cuenta, body LIKE '% aurrean, legeak hala behartzen duelako.%' AS no_cedemos, body LIKE '%Azken eguneratzea: 2026ko azaroa.%' AS fecha FROM pages WHERE slug = 'privacidad.eu'
UNION ALL SELECT 'privacidad.en' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%ly, as explained under “IP address log”.%' AS sin_cuenta, body LIKE '%al request, because the law requires it.%' AS no_cedemos, body LIKE '%Last updated: November 2026.%' AS fecha FROM pages WHERE slug = 'privacidad.en'
UNION ALL SELECT 'privacidad.fr' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%liqué dans « Registre des adresses IP ».%' AS sin_cuenta, body LIKE '%ion formelle, parce que la loi l''impose.%' AS no_cedemos, body LIKE '%Dernière mise à jour : novembre 2026.%' AS fecha FROM pages WHERE slug = 'privacidad.fr'
UNION ALL SELECT 'privacidad.de' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%ter „Protokoll der IP-Adressen“ erklärt.%' AS sin_cuenta, body LIKE '%erausgeben, weil das Gesetz es verlangt.%' AS no_cedemos, body LIKE '%Letzte Aktualisierung: November 2026.%' AS fecha FROM pages WHERE slug = 'privacidad.de'
UNION ALL SELECT 'privacidad.it' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%iegato in «Registro degli indirizzi IP».%' AS sin_cuenta, body LIKE '%esta formale, perché la legge lo impone.%' AS no_cedemos, body LIKE '%Ultimo aggiornamento: novembre 2026.%' AS fecha FROM pages WHERE slug = 'privacidad.it'
UNION ALL SELECT 'privacidad.pt' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%se explica em «Registo de endereços IP».%' AS sin_cuenta, body LIKE '% um pedido formal, porque a lei o exige.%' AS no_cedemos, body LIKE '%Última atualização: novembro de 2026.%' AS fecha FROM pages WHERE slug = 'privacidad.pt'
UNION ALL SELECT 'privacidad.tr' AS pagina, body LIKE '%id="registro-ip"%' AS apartado, body LIKE '%ünde açıklandığı gibi ayrıca kaydedilir.%' AS sin_cuenta, body LIKE '%e, yasa gerektirdiği için teslim ederiz.%' AS no_cedemos, body LIKE '%Son güncelleme: Kasım 2026.%' AS fecha FROM pages WHERE slug = 'privacidad.tr';
