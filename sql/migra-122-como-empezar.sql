-- migra-122 (08/10/2026): pagina «Como empezar» (/empezar), pedida por Angel: los lectores que llegan de Substack no han usado
-- un foro en su vida. FAQ de arranque en pages (slug 'empezar' y 'empezar.<idioma>'), editable en admin > Paginas como que-es,
-- normas, privacidad y algoritmo; enlazada en el pie («Como empezar») y en el Chat, a la derecha de «Por hilos / Por tiempo».
-- Nueve idiomas. Arriba del todo, lo del movil (instalar la app sin tienda y donde esta el menu del pie); el Radar se presenta
-- como la portada central por la que pasa todo; Canales no se menciona (apagada). Marcadores: {site} {cycle} {ttl} {gm}.
SET NAMES utf8mb4;
INSERT INTO pages (slug, title, body) VALUES ('empezar', 'Cómo empezar', '<h1>Cómo empezar</h1>

<p>{site} es un sitio donde la gente comparte lo que lee —artículos de medios, boletines de Substack, vídeos— y lo comenta. Aquí no hay un algoritmo que decida por ti: lo que sube a portada lo deciden los votos, con una regla única, pública e igual para todos. Si vienes de Substack o de las redes y nunca has usado un foro, esto es lo que necesitas saber. Se lee en cinco minutos.</p>

<h2>En el móvil: instala la app</h2>
<p>{site} funciona como una app sin pasar por ninguna tienda (ya llegará). Se instala desde el navegador en diez segundos y queda con su icono en la pantalla de inicio, a pantalla completa y sin la barra de direcciones. En <b>Android</b> (Chrome, Edge, Brave, Samsung Internet): abre deltha.eu, pulsa el menú de los tres puntos ⋮ y elige <b>«Instalar aplicación»</b> o <b>«Añadir a pantalla de inicio»</b>; a veces el propio navegador te lo ofrece con un aviso abajo. En <b>iPhone y iPad</b> tiene que ser Safari: pulsa el botón de <b>compartir</b> (el cuadrado con la flecha hacia arriba), baja hasta <b>«Añadir a pantalla de inicio»</b> y confirma. En el ordenador, Chrome y Edge enseñan un icono de instalar a la derecha de la barra de direcciones. Si alguna vez falla, bórrala del inicio y repite.</p>

<h2>Dónde está todo lo demás</h2>
<p>En el móvil solo caben dos menús: arriba, <b>Medios · Radar · Substacks</b> y <b>Originales</b>; abajo, <b>Chat</b>, <b>Temas</b>, <b>Comunidades</b>, <b>Usuarios</b>, <b>Procesos</b>, <b>Estadísticas</b> y <b>Mensajes</b>. El resto está <b>al final de cada página</b>: baja hasta abajo del todo y encontrarás los enlaces a <b>¿Qué es {site}?</b>, esta guía, el <b>manifiesto contralgorítmico</b>, las <b>normas</b>, la <b>privacidad y contacto</b>, lo de <b>cookies</b>, <b>cómo funciona el δ</b>, las comunidades y el catálogo de <b>medios</b> y <b>substacks</b> para seguirlos. En el ordenador ese mismo menú va fijo bajo la columna de la izquierda. Si buscas algo y no lo ves, casi seguro está ahí.</p>

<h2>¿Hace falta cuenta?</h2>
<p>Para leer, no. Para votar, enviar enlaces, guardar favoritos y seguir medios, sí. Se crea en un minuto en <a href="/registro">Crear cuenta</a>: un nombre de usuario, un correo y una contraseña. Te llega un correo con un enlace de activación; si no lo ves, mira en la carpeta de spam, y si no llega, pide otro en <a href="/reenviar">reenviar</a>. No pedimos teléfono ni nada más.</p>

<h2>Las portadas</h2>
<p>Arriba hay tres: <b>Medios</b> (prensa y webs), <b>Radar</b>, en el centro, y <b>Substacks</b> (boletines y newsletters). Medios y Substacks son las portadas de lo que ya ha pasado el filtro de los votos; cada una tiene su propia cola y su propio ritmo, y nada de una se mezcla con la otra. La que ves al entrar es la última que abriste.</p>

<h2>El Radar</h2>
<p>Es la portada por la que pasa todo. Cualquier cosa que alguien envía aparece primero ahí, y ahí es donde se vota: si quieres influir en lo que sube, el Radar es tu sitio. Cada {cycle} minutos, el envío con más apoyo de la portada que toca (una vez Medios, otra Substacks) salta a ella, siempre que llegue a un mínimo de votos. Debajo de cada envío ves la cuenta exacta (el <b>δ</b>): cuántos votos, qué suma el tema y cuánto resta el tiempo. No hay nada más; está todo explicado en <a href="/algoritmo">Cómo funciona el δ</a>.</p>

<h2>Votar</h2>
<p>Cada envío tiene una flecha hacia arriba y otra hacia abajo. Arriba es «esto merece portada»; abajo, «esto no». Un voto por persona y envío. Si te equivocas, pulsa otra vez la misma flecha en los primeros minutos y se anula. No votes tus propios envíos: no se puede.</p>

<h2>Enviar un enlace</h2>
<p>El botón <b>+</b> (abajo a la derecha, o <b>Enviar</b> en el menú). Pega la dirección del artículo, el boletín o el vídeo; {site} rellena el titular, la entradilla y la foto, y tú los retocas si quieres. Elige un <b>tema</b> y envía. Entra en el Radar y el resto lo decide la gente. Los enlaces van sin códigos de seguimiento: los quitamos nosotros. Si ya lo había enviado alguien, te lleva a su hilo. Si tienes algo propio que decir, sin enlace, eso es un <a href="/articulo">Original</a>: un texto de hasta mil palabras que se lee, se vota y se comenta en su pestaña.</p>

<h2>Comentar</h2>
<p>Cada envío abre un <b>hilo</b>. Pulsa el titular (o el número de comentarios) y abajo del todo tienes la caja para escribir. Para contestar a alguien en concreto, <b>Responder</b> debajo de su comentario: tu respuesta queda colgada de la suya. Los comentarios llevan un <b>sol</b> (me gusta) y una <b>lágrima</b> (no me gusta); no cambian la portada, solo dicen qué aporta y qué no. Los hilos se archivan a las {ttl} horas: se pueden leer, pero ya no se escribe en ellos. Aquí la conversación es efímera a propósito.</p>

<h2>El modo Ghost</h2>
<p>Cuando envías, votas o comentas, durante los primeros {gm} minutos tu nombre no aparece: se ve un alias. Pasado ese rato, se revela. Así lo primero que se juzga es lo que dices, no quién lo dice. Si alguien te resulta insoportable, en su ficha tienes <b>Ghostear</b>: dejas de verlo sin que nadie se entere.</p>

<h2>El Chat</h2>
<p>Es un único flujo de mensajes cortos para hablar de lo que sea, incluida {site}. Escribe <b>#123</b> para citar el mensaje 123 (se convierte en enlace) y <b>@nombre</b> para mencionar a alguien. <b>Por hilos</b> agrupa cada mensaje con sus respuestas; <b>Por tiempo</b> los enseña por orden de llegada. Si algo no te funciona o no sabes cómo se hace, pregunta ahí: es también el canal de ayuda, y los mensajes marcados con un megáfono son avisos del equipo a toda la comunidad.</p>

<h2>Mis medios y mis Substacks</h2>
<p>En <a href="/medios">Medios</a> está el catálogo de fuentes con RSS. Pulsa <b>Seguir</b> en las que leas y sus últimas noticias te esperan en la columna de la izquierda y en <b>Último</b>; desde ahí, <b>enviar al radar</b> las mete en juego con un clic. Si falta tu boletín o tu medio, propónlo desde esa misma página.</p>

<h2>El aura</h2>
<p>Es tu reputación, en forma de número que crece despacio: sube cuando lo que envías llega a portada o lo que comentas gusta, y baja un poco cada día sin hacer nada. Con aura se desbloquean cosas (por ejemplo, <b>Destacar</b> una noticia unos minutos en portada). No hay que perseguirla: llega sola.</p>

<h2>A tu gusto</h2>
<p>En <a href="/perfil">tu perfil</a> eliges foto, idioma (nueve), tema claro u oscuro, la <b>skin</b> (hay varias: clásica de foro, prensa, oscura…) y el <b>modo sin distracciones</b>, que quita columnas y deja solo la lectura. Sin cuenta también puedes cambiar skin, tema e idioma desde el menú: se guardan en tu navegador y nada más.</p>

<h2>Lo que no hacemos</h2>
<p>No hay publicidad programática, ni rastreadores, ni cookies de terceros, ni ningún criterio oculto para ordenar lo que ves. Las reglas de convivencia están en <a href="/normas">Normas de uso</a>; qué guardamos y cómo escribirnos, en <a href="/privacidad">Privacidad y contacto</a>. Si ves algo que no debería estar, <b>reportar</b> está debajo de cada envío, comentario y mensaje.</p>

<p>Eso es todo. Lo demás se aprende usándolo. <a href="/registro">Crea tu cuenta</a> o, si ya la tienes, <a href="/login">entra</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('empezar.en', 'Getting started', '<h1>Getting started</h1>

<p>{site} is a place where people share what they read —press articles, Substack newsletters, videos— and talk about it. There is no algorithm deciding for you: what reaches the front page is decided by votes, with a single rule that is public and the same for everyone. If you come from Substack or social networks and have never used a forum, this is what you need to know. Five minutes.</p>

<h2>On your phone: install the app</h2>
<p>{site} works as an app without going through any store (that will come). It installs from the browser in ten seconds and gets its own icon on the home screen, full screen and with no address bar. On <b>Android</b> (Chrome, Edge, Brave, Samsung Internet): open deltha.eu, tap the three-dot menu ⋮ and choose <b>"Install app"</b> or <b>"Add to Home screen"</b>; sometimes the browser offers it by itself with a banner at the bottom. On <b>iPhone and iPad</b> it has to be Safari: tap the <b>share</b> button (the square with the arrow pointing up), scroll down to <b>"Add to Home Screen"</b> and confirm. On a computer, Chrome and Edge show an install icon at the right of the address bar. If it ever misbehaves, remove it from the home screen and repeat.</p>

<h2>Where everything else is</h2>
<p>On a phone only two menus fit: at the top, <b>Medios · Radar · Substacks</b> and <b>Originales</b>; at the bottom, <b>Chat</b>, <b>Topics</b>, <b>Communities</b>, <b>Users</b>, <b>Processes</b>, <b>Statistics</b> and <b>Messages</b>. The rest is <b>at the end of every page</b>: scroll all the way down and you will find the links to <b>What is {site}?</b>, this guide, the <b>contra-algorithmic manifesto</b>, the <b>rules</b>, <b>privacy and contact</b>, the <b>cookies</b> note, <b>how the δ works</b>, the communities and the catalogue of <b>media</b> and <b>substacks</b> to follow. On a computer that same menu sits fixed under the left column. If you are looking for something and cannot see it, it is almost certainly there.</p>

<h2>Do I need an account?</h2>
<p>Not to read. To vote, submit links, save favourites and follow sources, yes. It takes a minute at <a href="/registro">Create account</a>: a username, an email and a password. You get an activation link by email; check your spam folder if you cannot see it, and ask for another at <a href="/reenviar">resend</a> if it never arrives. We do not ask for a phone number or anything else.</p>

<h2>The front pages</h2>
<p>There are three at the top: <b>Medios</b> (press and websites), <b>Radar</b>, in the middle, and <b>Substacks</b> (newsletters). Medios and Substacks are the front pages of what has already passed the filter of the votes; each has its own queue and its own pace, and nothing from one mixes with the other. The one you see when you arrive is the last one you opened.</p>

<h2>The Radar</h2>
<p>It is the front page everything goes through. Anything anyone submits shows up there first, and that is where voting happens: if you want a say in what goes up, the Radar is your place. Every {cycle} minutes, the most supported submission of the front page whose turn it is (Medios one time, Substacks the next) jumps to it, as long as it reaches a minimum number of votes. Under each submission you see the exact sum (the <b>δ</b>): how many votes, what the topic adds and what time takes away. That is all there is; it is explained in <a href="/algoritmo">How the δ works</a>.</p>

<h2>Voting</h2>
<p>Each submission has an up arrow and a down arrow. Up means "this deserves the front page"; down, "this does not". One vote per person and submission. If you make a mistake, press the same arrow again within the first minutes and it is undone. You cannot vote your own submissions.</p>

<h2>Submitting a link</h2>
<p>The <b>+</b> button (bottom right, or <b>Enviar</b> in the menu). Paste the address of the article, newsletter or video; {site} fills in the headline, the summary and the picture, and you adjust them if you like. Pick a <b>topic</b> and submit. It enters the Radar and people decide the rest. Links go without tracking codes: we strip them. If someone already submitted it, you are taken to their thread. If you have something of your own to say, with no link, that is an <a href="/articulo">Original</a>: a text of up to a thousand words that is read, voted and discussed in its own tab.</p>

<h2>Commenting</h2>
<p>Each submission opens a <b>thread</b>. Click the headline (or the number of comments) and the writing box is at the bottom. To answer someone in particular, <b>Reply</b> under their comment: your answer hangs from theirs. Comments have a <b>sun</b> (like) and a <b>tear</b> (dislike); they do not change the front page, they only say what adds something and what does not. Threads are archived after {ttl} hours: they can still be read, but no longer written in. Conversation here is ephemeral on purpose.</p>

<h2>Ghost mode</h2>
<p>When you submit, vote or comment, your name is hidden for the first {gm} minutes: an alias shows instead. After that, it is revealed. So the first thing judged is what you say, not who says it. If someone becomes unbearable, their profile has <b>Ghost</b>: you stop seeing them and nobody is told.</p>

<h2>The Chat</h2>
<p>A single stream of short messages to talk about anything, {site} included. Write <b>#123</b> to quote message 123 (it becomes a link) and <b>@name</b> to mention someone. <b>By threads</b> groups each message with its replies; <b>By time</b> shows them in order of arrival. If something does not work or you do not know how to do it, ask there: it is also the help channel, and messages marked with a megaphone are notices from the team to the whole community.</p>

<h2>My media and my Substacks</h2>
<p><a href="/medios">Medios</a> holds the catalogue of sources with RSS. Press <b>Follow</b> on the ones you read and their latest items wait for you in the left column and in <b>Latest</b>; from there, <b>send to the radar</b> puts them in play with one click. If your newsletter or outlet is missing, propose it from that same page.</p>

<h2>Aura</h2>
<p>It is your reputation, as a number that grows slowly: it rises when what you submit reaches the front page or what you comment is liked, and it drops a little every day you do nothing. Aura unlocks things (for instance, <b>featuring</b> an item on the front page for a few minutes). No need to chase it: it comes on its own.</p>

<h2>Your way</h2>
<p>In <a href="/perfil">your profile</a> you choose a picture, the language (nine), light or dark theme, the <b>skin</b> (several: classic forum, press, dark…) and <b>distraction-free mode</b>, which removes the columns and leaves only the reading. Without an account you can also change skin, theme and language from the menu: they are saved in your browser and nowhere else.</p>

<h2>What we do not do</h2>
<p>No programmatic advertising, no trackers, no third-party cookies, no hidden criterion to sort what you see. The rules of coexistence are in <a href="/normas">Rules</a>; what we keep and how to write to us, in <a href="/privacidad">Privacy and contact</a>. If you see something that should not be there, <b>report</b> is under every submission, comment and message.</p>

<p>That is all. The rest is learned by using it. <a href="/registro">Create your account</a> or, if you already have one, <a href="/login">log in</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('empezar.ca', 'Com començar', '<h1>Com començar</h1>

<p>{site} és un lloc on la gent comparteix el que llegeix —articles de mitjans, butlletins de Substack, vídeos— i en parla. Aquí no hi ha cap algoritme que decideixi per tu: el que puja a portada ho decideixen els vots, amb una regla única, pública i igual per a tothom. Si véns de Substack o de les xarxes i no has fet servir mai un fòrum, això és el que has de saber. Es llegeix en cinc minuts.</p>

<h2>Al mòbil: instal·la l''app</h2>
<p>{site} funciona com una app sense passar per cap botiga (ja arribarà). S''instal·la des del navegador en deu segons i queda amb la seva icona a la pantalla d''inici, a pantalla completa i sense la barra d''adreces. A <b>Android</b> (Chrome, Edge, Brave, Samsung Internet): obre deltha.eu, prem el menú dels tres punts ⋮ i tria <b>«Instal·la l''aplicació»</b> o <b>«Afegeix a la pantalla d''inici»</b>; de vegades el mateix navegador t''ho ofereix amb un avís a baix. A <b>iPhone i iPad</b> ha de ser Safari: prem el botó de <b>compartir</b> (el quadrat amb la fletxa cap amunt), baixa fins a <b>«Afegeix a la pantalla d''inici»</b> i confirma. A l''ordinador, Chrome i Edge mostren una icona d''instal·lar a la dreta de la barra d''adreces. Si alguna vegada falla, esborra-la de l''inici i repeteix.</p>

<h2>On és tota la resta</h2>
<p>Al mòbil només hi caben dos menús: a dalt, <b>Medios · Radar · Substacks</b> i <b>Originales</b>; a baix, <b>Xat</b>, <b>Temes</b>, <b>Comunitats</b>, <b>Usuaris</b>, <b>Processos</b>, <b>Estadístiques</b> i <b>Missatges</b>. La resta és <b>al final de cada pàgina</b>: baixa fins a baix de tot i trobaràs els enllaços a <b>Què és {site}?</b>, aquesta guia, el <b>manifest contraalgorítmic</b>, les <b>normes</b>, la <b>privacitat i contacte</b>, allò de les <b>galetes</b>, <b>com funciona la δ</b>, les comunitats i el catàleg de <b>mitjans</b> i <b>substacks</b> per seguir-los. A l''ordinador aquest mateix menú va fix sota la columna de l''esquerra. Si busques alguna cosa i no la veus, gairebé segur que és allà.</p>

<h2>Cal tenir compte?</h2>
<p>Per llegir, no. Per votar, enviar enllaços, desar preferits i seguir mitjans, sí. Es crea en un minut a <a href="/registro">Crear compte</a>: un nom d''usuari, un correu i una contrasenya. T''arriba un correu amb un enllaç d''activació; si no el veus, mira a la carpeta de correu brossa, i si no arriba, demana''n un altre a <a href="/reenviar">reenviar</a>. No demanem telèfon ni res més.</p>

<h2>Les portades</h2>
<p>A dalt n''hi ha tres: <b>Medios</b> (premsa i webs), <b>Radar</b>, al centre, i <b>Substacks</b> (butlletins i newsletters). Medios i Substacks són les portades del que ja ha passat el filtre dels vots; cadascuna té la seva pròpia cua i el seu propi ritme, i res de l''una es barreja amb l''altra. La que veus en entrar és l''última que vas obrir.</p>

<h2>El Radar</h2>
<p>És la portada per on passa tot. Qualsevol cosa que algú envia apareix primer allà, i allà és on es vota: si vols influir en el que puja, el Radar és el teu lloc. Cada {cycle} minuts, l''enviament amb més suport de la portada a qui toca (una vegada Medios, l''altra Substacks) hi salta, sempre que arribi a un mínim de vots. Sota cada enviament veus el compte exacte (la <b>δ</b>): quants vots, què suma el tema i quant resta el temps. No hi ha res més; és tot explicat a <a href="/algoritmo">Com funciona la δ</a>.</p>

<h2>Votar</h2>
<p>Cada enviament té una fletxa cap amunt i una altra cap avall. Amunt és «això mereix portada»; avall, «això no». Un vot per persona i enviament. Si t''equivoques, prem una altra vegada la mateixa fletxa els primers minuts i s''anul·la. No votis els teus propis enviaments: no es pot.</p>

<h2>Enviar un enllaç</h2>
<p>El botó <b>+</b> (a baix a la dreta, o <b>Enviar</b> al menú). Enganxa l''adreça de l''article, el butlletí o el vídeo; {site} omple el titular, l''entradeta i la foto, i tu els retoques si vols. Tria un <b>tema</b> i envia. Entra al Radar i la resta ho decideix la gent. Els enllaços van sense codis de seguiment: els traiem nosaltres. Si ja l''havia enviat algú, et porta al seu fil. Si tens alguna cosa pròpia a dir, sense enllaç, això és un <a href="/articulo">Original</a>: un text de fins a mil paraules que es llegeix, es vota i es comenta a la seva pestanya.</p>

<h2>Comentar</h2>
<p>Cada enviament obre un <b>fil</b>. Prem el titular (o el nombre de comentaris) i a baix de tot tens la caixa per escriure. Per contestar a algú en concret, <b>Respondre</b> sota el seu comentari: la teva resposta queda penjada de la seva. Els comentaris porten un <b>sol</b> (m''agrada) i una <b>llàgrima</b> (no m''agrada); no canvien la portada, només diuen què aporta i què no. Els fils s''arxiven a les {ttl} hores: es poden llegir, però ja no s''hi escriu. Aquí la conversa és efímera a propòsit.</p>

<h2>El mode Ghost</h2>
<p>Quan envies, votes o comentes, durant els primers {gm} minuts el teu nom no apareix: es veu un àlies. Passat aquest temps, es revela. Així el primer que es jutja és el que dius, no qui ho diu. Si algú et resulta insuportable, a la seva fitxa tens <b>Ghostejar</b>: deixes de veure''l sense que ningú se n''assabenti.</p>

<h2>El Xat</h2>
<p>És un únic flux de missatges curts per parlar del que sigui, {site} inclosa. Escriu <b>#123</b> per citar el missatge 123 (es converteix en enllaç) i <b>@nom</b> per mencionar algú. <b>Per fils</b> agrupa cada missatge amb les seves respostes; <b>Per temps</b> els mostra per ordre d''arribada. Si alguna cosa no et funciona o no saps com es fa, pregunta allà: és també el canal d''ajuda, i els missatges marcats amb un megàfon són avisos de l''equip a tota la comunitat.</p>

<h2>Els meus mitjans i els meus Substacks</h2>
<p>A <a href="/medios">Medios</a> hi ha el catàleg de fonts amb RSS. Prem <b>Seguir</b> a les que llegeixis i les seves últimes notícies t''esperen a la columna de l''esquerra i a <b>Últim</b>; des d''allà, <b>enviar al radar</b> les posa en joc amb un clic. Si falta el teu butlletí o el teu mitjà, proposa''l des d''aquesta mateixa pàgina.</p>

<h2>L''aura</h2>
<p>És la teva reputació, en forma de número que creix a poc a poc: puja quan el que envies arriba a portada o el que comentes agrada, i baixa una mica cada dia sense fer res. Amb aura es desbloquegen coses (per exemple, <b>Destacar</b> una notícia uns minuts a portada). No cal perseguir-la: arriba sola.</p>

<h2>Al teu gust</h2>
<p>Al <a href="/perfil">teu perfil</a> tries foto, idioma (nou), tema clar o fosc, la <b>skin</b> (n''hi ha diverses: clàssica de fòrum, premsa, fosca…) i el <b>mode sense distraccions</b>, que treu columnes i deixa només la lectura. Sense compte també pots canviar skin, tema i idioma des del menú: es desen al teu navegador i res més.</p>

<h2>El que no fem</h2>
<p>No hi ha publicitat programàtica, ni rastrejadors, ni galetes de tercers, ni cap criteri ocult per ordenar el que veus. Les regles de convivència són a <a href="/normas">Normes d''ús</a>; què guardem i com escriure''ns, a <a href="/privacidad">Privacitat i contacte</a>. Si veus alguna cosa que no hi hauria de ser, <b>reportar</b> és sota cada enviament, comentari i missatge.</p>

<p>Això és tot. La resta s''aprèn fent-la servir. <a href="/registro">Crea el teu compte</a> o, si ja el tens, <a href="/login">entra</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('empezar.eu', 'Nola hasi', '<h1>Nola hasi</h1>

<p>{site} jendeak irakurtzen duena —hedabideetako artikuluak, Substack-eko buletinak, bideoak— partekatu eta komentatzen duen lekua da. Hemen ez dago zure ordez erabakitzen duen algoritmorik: azalera zer igotzen den botoek erabakitzen dute, arau bakar, publiko eta denontzat berdin batekin. Substack-etik edo sareetatik bazatoz eta inoiz fororik erabili ez baduzu, hau da jakin behar duzuna. Bost minututan irakurtzen da.</p>

<h2>Mugikorrean: instalatu aplikazioa</h2>
<p>{site} aplikazio gisa dabil inongo dendatik pasatu gabe (iritsiko da). Nabigatzailetik hamar segundotan instalatzen da eta bere ikonoa hasierako pantailan geratzen da, pantaila osoan eta helbide-barrarik gabe. <b>Android</b>-en (Chrome, Edge, Brave, Samsung Internet): ireki deltha.eu, sakatu hiru puntuko menua ⋮ eta aukeratu <b>«Instalatu aplikazioa»</b> edo <b>«Gehitu hasierako pantailan»</b>; batzuetan nabigatzaileak berak eskaintzen dizu behean abisu batekin. <b>iPhone eta iPad</b>-en Safari izan behar du: sakatu <b>partekatu</b> botoia (gora begira dagoen gezia duen laukia), jaitsi <b>«Gehitu hasierako pantailan»</b> arte eta berretsi. Ordenagailuan, Chrome eta Edge-k instalatzeko ikono bat erakusten dute helbide-barraren eskuinean. Noizbait huts egiten badu, ezabatu hasieratik eta errepikatu.</p>

<h2>Non dagoen gainerako guztia</h2>
<p>Mugikorrean bi menu baino ez dira sartzen: goian, <b>Medios · Radar · Substacks</b> eta <b>Originales</b>; behean, <b>Txata</b>, <b>Gaiak</b>, <b>Komunitateak</b>, <b>Erabiltzaileak</b>, <b>Prozesuak</b>, <b>Estatistikak</b> eta <b>Mezuak</b>. Gainerakoa <b>orrialde bakoitzaren amaieran</b> dago: jaitsi beheraino eta hauetarako estekak aurkituko dituzu: <b>Zer da {site}?</b>, gida hau, <b>manifestu kontralgoritmikoa</b>, <b>arauak</b>, <b>pribatutasuna eta kontaktua</b>, <b>cookie</b>-en oharra, <b>nola dabilen δ</b>, komunitateak eta jarraitzeko <b>hedabideen</b> eta <b>substack</b>-en katalogoa. Ordenagailuan menu bera finko dago ezkerreko zutabearen azpian. Zerbait bilatu eta ikusten ez baduzu, ia ziur hor dago.</p>

<h2>Konturik behar al da?</h2>
<p>Irakurtzeko, ez. Bozkatzeko, estekak bidaltzeko, gogokoak gordetzeko eta hedabideak jarraitzeko, bai. Minutu batean sortzen da <a href="/registro">Sortu kontua</a> atalean: erabiltzaile-izena, posta elektronikoa eta pasahitza. Aktibazio-esteka duen mezu bat jasoko duzu; ikusten ez baduzu, begiratu spam karpetan, eta iristen ez bada, eskatu beste bat <a href="/reenviar">berbidali</a> atalean. Ez dugu telefonorik ezta beste ezer eskatzen.</p>

<h2>Azalak</h2>
<p>Goian hiru daude: <b>Medios</b> (prentsa eta webguneak), <b>Radar</b>, erdian, eta <b>Substacks</b> (buletinak eta newsletterrak). Medios eta Substacks botoen iragazkia dagoeneko pasatu duenaren azalak dira; bakoitzak bere ilara eta bere erritmoa ditu, eta batekoa ez da bestearekin nahasten. Sartzean ikusten duzuna azkena ireki zenuena da.</p>

<h2>Radarra</h2>
<p>Dena pasatzen den azala da. Norbaitek bidaltzen duen edozer hor agertzen da lehenengo, eta hor bozkatzen da: zer igotzen den eragin nahi baduzu, Radarra da zure lekua. {cycle} minuturo, txanda duen azalaren (behin Medios, hurrengoan Substacks) babes gehien duen bidalketa hara salto egiten du, gutxieneko boto kopurura iristen bada. Bidalketa bakoitzaren azpian kontu zehatza ikusten duzu (<b>δ</b>): zenbat boto, gaiak zenbat gehitzen duen eta denborak zenbat kentzen duen. Ez dago besterik; dena azalduta dago <a href="/algoritmo">Nola dabilen δ</a> atalean.</p>

<h2>Bozkatzea</h2>
<p>Bidalketa bakoitzak gorako gezi bat eta beherako beste bat ditu. Gorakoa «honek azala merezi du» da; beherakoa, «honek ez». Boto bat pertsona eta bidalketa bakoitzeko. Oker bazaude, sakatu berriro gezi bera lehen minutuetan eta baliogabetu egiten da. Ez bozkatu zure bidalketak: ezin da.</p>

<h2>Esteka bat bidaltzea</h2>
<p><b>+</b> botoia (behean eskuinean, edo <b>Enviar</b> menuan). Itsatsi artikuluaren, buletinaren edo bideoaren helbidea; {site}-k titularra, sarrera eta argazkia betetzen ditu, eta zuk nahi baduzu ukituak egin. Aukeratu <b>gai</b> bat eta bidali. Radarrean sartzen da eta gainerakoa jendeak erabakitzen du. Estekak jarraipen-koderik gabe doaz: guk kentzen ditugu. Norbaitek dagoeneko bidalia bazuen, haren harira eramango zaitu. Zerbait propioa esateko baduzu, estekarik gabe, hori <a href="/articulo">Original</a> bat da: gehienez mila hitzeko testua, bere fitxan irakurri, bozkatu eta komentatzen dena.</p>

<h2>Komentatzea</h2>
<p>Bidalketa bakoitzak <b>hari</b> bat irekitzen du. Sakatu titularra (edo iruzkin kopurua) eta beheraino idazteko koadroa duzu. Norbaiti zehazki erantzuteko, <b>Erantzun</b> haren iruzkinaren azpian: zure erantzuna harenari lotuta geratzen da. Iruzkinek <b>eguzki</b> bat (gustuko dut) eta <b>malko</b> bat (ez dut gustuko) dituzte; ez dute azala aldatzen, zerk ekartzen duen eta zerk ez bakarrik esaten dute. Hariak {ttl} ordura artxibatzen dira: irakur daitezke, baina jada ez da haietan idazten. Hemen elkarrizketa nahita da iragankorra.</p>

<h2>Ghost modua</h2>
<p>Bidali, bozkatu edo komentatzen duzunean, lehen {gm} minutuetan zure izena ez da agertzen: ezizen bat ikusten da. Denbora hori pasata, agerian geratzen da. Horrela, lehenik epaitzen dena diozuna da, ez nork dioen. Norbait jasanezina egiten bazaizu, haren fitxan <b>Ghostear</b> duzu: ikusteari uzten diozu inor konturatu gabe.</p>

<h2>Txata</h2>
<p>Edozertaz hitz egiteko mezu laburren fluxu bakarra da, {site} barne. Idatzi <b>#123</b> 123 mezua aipatzeko (esteka bihurtzen da) eta <b>@izena</b> norbait aipatzeko. <b>Harika</b> mezu bakoitza bere erantzunekin biltzen du; <b>Denboraz</b> iristen diren ordenan erakusten ditu. Zerbaitek funtzionatzen ez badizu edo nola egiten den ez badakizu, galdetu hor: laguntza-kanala ere bada, eta megafono batekin markatutako mezuak taldeak komunitate osoari egindako abisuak dira.</p>

<h2>Nire hedabideak eta nire Substack-ak</h2>
<p><a href="/medios">Medios</a> atalean RSS duten iturrien katalogoa dago. Sakatu <b>Jarraitu</b> irakurtzen dituzunetan eta haien azken albisteak ezkerreko zutabean eta <b>Azkena</b> atalean itxarongo dizute; handik, <b>bidali radarrera</b> botoiak klik batekin jokoan jartzen ditu. Zure buletina edo hedabidea falta bada, proposatu orrialde horretatik bertatik.</p>

<h2>Aura</h2>
<p>Zure ospea da, poliki hazten den zenbaki baten moduan: igo egiten da bidaltzen duzuna azalera iristen denean edo komentatzen duzuna gustatzen denean, eta pixka bat jaisten da ezer egin gabe egunero. Aurarekin gauzak desblokeatzen dira (adibidez, albiste bat minutu batzuez azalean <b>nabarmentzea</b>). Ez dago atzetik ibili beharrik: berez iristen da.</p>

<h2>Zure gustura</h2>
<p><a href="/perfil">Zure profilean</a> argazkia, hizkuntza (bederatzi), gai argia edo iluna, <b>skin</b>-a (hainbat daude: foro klasikoa, prentsa, iluna…) eta <b>distrakziorik gabeko modua</b> aukeratzen dituzu, zutabeak kendu eta irakurketa bakarrik uzten duena. Konturik gabe ere alda ditzakezu skin-a, gaia eta hizkuntza menutik: zure nabigatzailean gordetzen dira eta besterik ez.</p>

<h2>Egiten ez duguna</h2>
<p>Ez dago publizitate programatikorik, ez jarraitzailerik, ez hirugarrenen cookierik, ezta ikusten duzuna ordenatzeko ezkutuko irizpiderik ere. Elkarbizitza-arauak <a href="/normas">Erabilera-arauak</a> atalean daude; zer gordetzen dugun eta nola idatzi, <a href="/privacidad">Pribatutasuna eta kontaktua</a> atalean. Egon behar ez lukeen zerbait ikusten baduzu, <b>salatu</b> bidalketa, iruzkin eta mezu bakoitzaren azpian dago.</p>

<p>Hori da dena. Gainerakoa erabiliz ikasten da. <a href="/registro">Sortu zure kontua</a> edo, dagoeneko baduzu, <a href="/login">sartu</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('empezar.gl', 'Como empezar', '<h1>Como empezar</h1>

<p>{site} é un sitio onde a xente comparte o que le —artigos de medios, boletíns de Substack, vídeos— e o comenta. Aquí non hai un algoritmo que decida por ti: o que sobe a portada decídeno os votos, cunha regra única, pública e igual para todos. Se vés de Substack ou das redes e nunca usaches un foro, isto é o que necesitas saber. Lese en cinco minutos.</p>

<h2>No móbil: instala a app</h2>
<p>{site} funciona como unha app sen pasar por ningunha tenda (xa chegará). Instálase desde o navegador en dez segundos e queda coa súa icona na pantalla de inicio, a pantalla completa e sen a barra de enderezos. En <b>Android</b> (Chrome, Edge, Brave, Samsung Internet): abre deltha.eu, preme o menú dos tres puntos ⋮ e escolle <b>«Instalar aplicación»</b> ou <b>«Engadir á pantalla de inicio»</b>; ás veces o propio navegador ofrécecho cun aviso abaixo. En <b>iPhone e iPad</b> ten que ser Safari: preme o botón de <b>compartir</b> (o cadrado coa frecha cara arriba), baixa ata <b>«Engadir á pantalla de inicio»</b> e confirma. No ordenador, Chrome e Edge amosan unha icona de instalar á dereita da barra de enderezos. Se algunha vez falla, bórraa do inicio e repite.</p>

<h2>Onde está todo o demais</h2>
<p>No móbil só caben dous menús: arriba, <b>Medios · Radar · Substacks</b> e <b>Originales</b>; abaixo, <b>Chat</b>, <b>Temas</b>, <b>Comunidades</b>, <b>Usuarios</b>, <b>Procesos</b>, <b>Estatísticas</b> e <b>Mensaxes</b>. O resto está <b>ao final de cada páxina</b>: baixa ata abaixo de todo e atoparás as ligazóns a <b>Que é {site}?</b>, esta guía, o <b>manifesto contralgorítmico</b>, as <b>normas</b>, a <b>privacidade e contacto</b>, o das <b>cookies</b>, <b>como funciona o δ</b>, as comunidades e o catálogo de <b>medios</b> e <b>substacks</b> para seguilos. No ordenador ese mesmo menú vai fixo baixo a columna da esquerda. Se buscas algo e non o ves, case seguro que está aí.</p>

<h2>Fai falta conta?</h2>
<p>Para ler, non. Para votar, enviar ligazóns, gardar favoritos e seguir medios, si. Créase nun minuto en <a href="/registro">Crear conta</a>: un nome de usuario, un correo e un contrasinal. Chégache un correo cunha ligazón de activación; se non a ves, mira no cartafol de spam, e se non chega, pide outra en <a href="/reenviar">reenviar</a>. Non pedimos teléfono nin nada máis.</p>

<h2>As portadas</h2>
<p>Arriba hai tres: <b>Medios</b> (prensa e webs), <b>Radar</b>, no centro, e <b>Substacks</b> (boletíns e newsletters). Medios e Substacks son as portadas do que xa pasou o filtro dos votos; cada unha ten a súa propia cola e o seu propio ritmo, e nada dunha se mestura coa outra. A que ves ao entrar é a última que abriches.</p>

<h2>O Radar</h2>
<p>É a portada pola que pasa todo. Calquera cousa que alguén envía aparece primeiro aí, e aí é onde se vota: se queres influír no que sobe, o Radar é o teu sitio. Cada {cycle} minutos, o envío con máis apoio da portada á que lle toca (unha vez Medios, outra Substacks) salta a ela, sempre que chegue a un mínimo de votos. Debaixo de cada envío ves a conta exacta (o <b>δ</b>): cantos votos, que suma o tema e canto resta o tempo. Non hai nada máis; está todo explicado en <a href="/algoritmo">Como funciona o δ</a>.</p>

<h2>Votar</h2>
<p>Cada envío ten unha frecha cara arriba e outra cara abaixo. Arriba é «isto merece portada»; abaixo, «isto non». Un voto por persoa e envío. Se te equivocas, preme outra vez a mesma frecha nos primeiros minutos e anúlase. Non votes os teus propios envíos: non se pode.</p>

<h2>Enviar unha ligazón</h2>
<p>O botón <b>+</b> (abaixo á dereita, ou <b>Enviar</b> no menú). Pega o enderezo do artigo, o boletín ou o vídeo; {site} enche o titular, a entradiña e a foto, e ti retócalos se queres. Escolle un <b>tema</b> e envía. Entra no Radar e o resto decídeo a xente. As ligazóns van sen códigos de seguimento: quitámolos nós. Se xa o enviara alguén, lévate ao seu fío. Se tes algo propio que dicir, sen ligazón, iso é un <a href="/articulo">Orixinal</a>: un texto de ata mil palabras que se le, se vota e se comenta na súa lapela.</p>

<h2>Comentar</h2>
<p>Cada envío abre un <b>fío</b>. Preme o titular (ou o número de comentarios) e abaixo de todo tes a caixa para escribir. Para contestar a alguén en concreto, <b>Responder</b> debaixo do seu comentario: a túa resposta queda colgada da súa. Os comentarios levan un <b>sol</b> (gústame) e unha <b>bágoa</b> (non me gusta); non cambian a portada, só din que achega e que non. Os fíos arquívanse ás {ttl} horas: pódense ler, pero xa non se escribe neles. Aquí a conversa é efémera a propósito.</p>

<h2>O modo Ghost</h2>
<p>Cando envías, votas ou comentas, durante os primeiros {gm} minutos o teu nome non aparece: vese un alias. Pasado ese tempo, revélase. Así o primeiro que se xulga é o que dis, non quen o di. Se alguén che resulta insoportable, na súa ficha tes <b>Ghostear</b>: deixas de velo sen que ninguén se decate.</p>

<h2>O Chat</h2>
<p>É un único fluxo de mensaxes curtas para falar do que sexa, {site} incluída. Escribe <b>#123</b> para citar a mensaxe 123 (convértese en ligazón) e <b>@nome</b> para mencionar a alguén. <b>Por fíos</b> agrupa cada mensaxe coas súas respostas; <b>Por tempo</b> amósaas por orde de chegada. Se algo non che funciona ou non sabes como se fai, pregunta aí: é tamén a canle de axuda, e as mensaxes marcadas cun megáfono son avisos do equipo a toda a comunidade.</p>

<h2>Os meus medios e os meus Substacks</h2>
<p>En <a href="/medios">Medios</a> está o catálogo de fontes con RSS. Preme <b>Seguir</b> nas que leas e as súas últimas noticias espéranche na columna da esquerda e en <b>Último</b>; desde aí, <b>enviar ao radar</b> méteas en xogo cun clic. Se falta o teu boletín ou o teu medio, propono desde esa mesma páxina.</p>

<h2>A aura</h2>
<p>É a túa reputación, en forma de número que medra amodo: sobe cando o que envías chega a portada ou o que comentas gusta, e baixa un pouco cada día sen facer nada. Con aura desbloquéanse cousas (por exemplo, <b>Destacar</b> unha noticia uns minutos en portada). Non hai que perseguila: chega soa.</p>

<h2>Ao teu gusto</h2>
<p>No <a href="/perfil">teu perfil</a> escolles foto, idioma (nove), tema claro ou escuro, a <b>skin</b> (hai varias: clásica de foro, prensa, escura…) e o <b>modo sen distraccións</b>, que quita columnas e deixa só a lectura. Sen conta tamén podes cambiar skin, tema e idioma desde o menú: gárdanse no teu navegador e nada máis.</p>

<h2>O que non facemos</h2>
<p>Non hai publicidade programática, nin rastrexadores, nin cookies de terceiros, nin ningún criterio oculto para ordenar o que ves. As regras de convivencia están en <a href="/normas">Normas de uso</a>; que gardamos e como escribirnos, en <a href="/privacidad">Privacidade e contacto</a>. Se ves algo que non debería estar, <b>reportar</b> está debaixo de cada envío, comentario e mensaxe.</p>

<p>Iso é todo. O demais apréndese usándoo. <a href="/registro">Crea a túa conta</a> ou, se xa a tes, <a href="/login">entra</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('empezar.fr', 'Pour commencer', '<h1>Pour commencer</h1>

<p>{site} est un endroit où les gens partagent ce qu''ils lisent —articles de presse, lettres Substack, vidéos— et en discutent. Ici, aucun algorithme ne décide à votre place : ce qui monte en une est décidé par les votes, selon une règle unique, publique et la même pour tous. Si vous venez de Substack ou des réseaux et n''avez jamais utilisé un forum, voici ce qu''il faut savoir. Cinq minutes de lecture.</p>

<h2>Sur le téléphone : installez l''app</h2>
<p>{site} fonctionne comme une app sans passer par aucune boutique (ça viendra). Elle s''installe depuis le navigateur en dix secondes et garde son icône sur l''écran d''accueil, en plein écran et sans barre d''adresse. Sur <b>Android</b> (Chrome, Edge, Brave, Samsung Internet) : ouvrez deltha.eu, touchez le menu à trois points ⋮ et choisissez <b>« Installer l''application »</b> ou <b>« Ajouter à l''écran d''accueil »</b> ; parfois le navigateur le propose lui-même par un bandeau en bas. Sur <b>iPhone et iPad</b>, il faut Safari : touchez le bouton <b>partager</b> (le carré avec la flèche vers le haut), descendez jusqu''à <b>« Sur l''écran d''accueil »</b> et confirmez. Sur ordinateur, Chrome et Edge affichent une icône d''installation à droite de la barre d''adresse. Si un jour ça coince, supprimez-la de l''accueil et recommencez.</p>

<h2>Où se trouve tout le reste</h2>
<p>Sur un téléphone, seuls deux menus tiennent : en haut, <b>Medios · Radar · Substacks</b> et <b>Originales</b> ; en bas, <b>Chat</b>, <b>Thèmes</b>, <b>Communautés</b>, <b>Utilisateurs</b>, <b>Processus</b>, <b>Statistiques</b> et <b>Messages</b>. Le reste se trouve <b>à la fin de chaque page</b> : descendez tout en bas et vous trouverez les liens vers <b>Qu''est-ce que {site} ?</b>, ce guide, le <b>manifeste contre-algorithmique</b>, les <b>règles</b>, la <b>confidentialité et le contact</b>, la note sur les <b>cookies</b>, <b>comment fonctionne le δ</b>, les communautés et le catalogue des <b>médias</b> et <b>substacks</b> à suivre. Sur ordinateur, ce même menu est fixé sous la colonne de gauche. Si vous cherchez quelque chose sans le voir, c''est presque sûrement là.</p>

<h2>Faut-il un compte ?</h2>
<p>Pour lire, non. Pour voter, envoyer des liens, enregistrer des favoris et suivre des médias, oui. Il se crée en une minute sur <a href="/registro">Créer un compte</a> : un nom d''utilisateur, un e-mail et un mot de passe. Vous recevez un e-mail avec un lien d''activation ; si vous ne le voyez pas, regardez dans les indésirables, et s''il n''arrive pas, demandez-en un autre sur <a href="/reenviar">renvoyer</a>. Nous ne demandons ni téléphone ni rien d''autre.</p>

<h2>Les unes</h2>
<p>En haut, il y en a trois : <b>Medios</b> (presse et sites), <b>Radar</b>, au centre, et <b>Substacks</b> (lettres et newsletters). Medios et Substacks sont les unes de ce qui a déjà passé le filtre des votes ; chacune a sa propre file et son propre rythme, et rien de l''une ne se mélange à l''autre. Celle que vous voyez en arrivant est la dernière que vous avez ouverte.</p>

<h2>Le Radar</h2>
<p>C''est la une par laquelle tout passe. Tout ce que quelqu''un envoie y apparaît d''abord, et c''est là qu''on vote : si vous voulez peser sur ce qui monte, le Radar est votre place. Toutes les {cycle} minutes, l''envoi le plus soutenu de la une dont c''est le tour (une fois Medios, une fois Substacks) y saute, à condition d''atteindre un minimum de votes. Sous chaque envoi, vous voyez le compte exact (le <b>δ</b>) : combien de votes, ce qu''ajoute le thème et ce que retire le temps. Il n''y a rien de plus ; tout est expliqué dans <a href="/algoritmo">Comment fonctionne le δ</a>.</p>

<h2>Voter</h2>
<p>Chaque envoi a une flèche vers le haut et une vers le bas. Le haut, c''est « ça mérite la une » ; le bas, « ça non ». Un vote par personne et par envoi. En cas d''erreur, appuyez à nouveau sur la même flèche dans les premières minutes et il s''annule. On ne vote pas ses propres envois : c''est impossible.</p>

<h2>Envoyer un lien</h2>
<p>Le bouton <b>+</b> (en bas à droite, ou <b>Enviar</b> dans le menu). Collez l''adresse de l''article, de la lettre ou de la vidéo ; {site} remplit le titre, le chapeau et la photo, et vous les retouchez si vous voulez. Choisissez un <b>thème</b> et envoyez. Ça entre dans le Radar et les gens décident du reste. Les liens partent sans codes de suivi : nous les retirons. Si quelqu''un l''avait déjà envoyé, vous êtes conduit à son fil. Si vous avez quelque chose à dire par vous-même, sans lien, c''est un <a href="/articulo">Original</a> : un texte de mille mots au plus, qu''on lit, vote et commente dans son onglet.</p>

<h2>Commenter</h2>
<p>Chaque envoi ouvre un <b>fil</b>. Cliquez sur le titre (ou le nombre de commentaires) et la zone d''écriture est tout en bas. Pour répondre à quelqu''un en particulier, <b>Répondre</b> sous son commentaire : votre réponse s''accroche à la sienne. Les commentaires ont un <b>soleil</b> (j''aime) et une <b>larme</b> (je n''aime pas) ; ils ne changent pas la une, ils disent seulement ce qui apporte et ce qui n''apporte pas. Les fils sont archivés au bout de {ttl} heures : on peut encore les lire, mais plus y écrire. Ici la conversation est éphémère à dessein.</p>

<h2>Le mode Ghost</h2>
<p>Quand vous envoyez, votez ou commentez, votre nom n''apparaît pas pendant les {gm} premières minutes : on voit un alias. Passé ce délai, il se révèle. Ainsi la première chose jugée est ce que vous dites, pas qui le dit. Si quelqu''un devient insupportable, sa fiche propose <b>Ghoster</b> : vous cessez de le voir sans que personne le sache.</p>

<h2>Le Chat</h2>
<p>Un seul flux de messages courts pour parler de tout, {site} compris. Écrivez <b>#123</b> pour citer le message 123 (il devient un lien) et <b>@nom</b> pour mentionner quelqu''un. <b>Par fils</b> regroupe chaque message avec ses réponses ; <b>Par temps</b> les montre par ordre d''arrivée. Si quelque chose ne marche pas ou que vous ne savez pas comment faire, demandez là : c''est aussi le canal d''aide, et les messages marqués d''un mégaphone sont des avis de l''équipe à toute la communauté.</p>

<h2>Mes médias et mes Substacks</h2>
<p><a href="/medios">Medios</a> contient le catalogue des sources avec RSS. Appuyez sur <b>Suivre</b> pour celles que vous lisez et leurs dernières nouvelles vous attendent dans la colonne de gauche et dans <b>Dernier</b> ; de là, <b>envoyer au radar</b> les met en jeu d''un clic. S''il manque votre lettre ou votre média, proposez-le depuis cette même page.</p>

<h2>L''aura</h2>
<p>C''est votre réputation, sous forme d''un nombre qui grandit lentement : il monte quand ce que vous envoyez atteint la une ou quand vos commentaires plaisent, et baisse un peu chaque jour sans rien faire. L''aura débloque des choses (par exemple, <b>Mettre en avant</b> une nouvelle quelques minutes en une). Inutile de courir après : elle vient toute seule.</p>

<h2>À votre goût</h2>
<p>Dans <a href="/perfil">votre profil</a>, vous choisissez la photo, la langue (neuf), le thème clair ou sombre, la <b>skin</b> (plusieurs : forum classique, presse, sombre…) et le <b>mode sans distraction</b>, qui retire les colonnes et ne laisse que la lecture. Sans compte, vous pouvez aussi changer skin, thème et langue depuis le menu : c''est enregistré dans votre navigateur et nulle part ailleurs.</p>

<h2>Ce que nous ne faisons pas</h2>
<p>Pas de publicité programmatique, pas de traceurs, pas de cookies tiers, aucun critère caché pour ordonner ce que vous voyez. Les règles de vie commune sont dans <a href="/normas">Règles d''utilisation</a> ; ce que nous conservons et comment nous écrire, dans <a href="/privacidad">Confidentialité et contact</a>. Si vous voyez quelque chose qui ne devrait pas être là, <b>signaler</b> se trouve sous chaque envoi, commentaire et message.</p>

<p>C''est tout. Le reste s''apprend en l''utilisant. <a href="/registro">Créez votre compte</a> ou, si vous l''avez déjà, <a href="/login">connectez-vous</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('empezar.de', 'Erste Schritte', '<h1>Erste Schritte</h1>

<p>{site} ist ein Ort, an dem Leute teilen, was sie lesen —Presseartikel, Substack-Newsletter, Videos— und darüber reden. Hier entscheidet kein Algorithmus für dich: Was auf die Titelseite kommt, entscheiden die Stimmen, nach einer einzigen Regel, die öffentlich ist und für alle gleich gilt. Wenn du von Substack oder aus den sozialen Netzwerken kommst und noch nie ein Forum benutzt hast, musst du Folgendes wissen. Fünf Minuten Lesezeit.</p>

<h2>Am Handy: installiere die App</h2>
<p>{site} funktioniert wie eine App, ohne über einen Store zu gehen (der kommt noch). Sie installiert sich in zehn Sekunden aus dem Browser und bekommt ihr Symbol auf dem Startbildschirm, im Vollbild und ohne Adressleiste. Auf <b>Android</b> (Chrome, Edge, Brave, Samsung Internet): öffne deltha.eu, tippe auf das Dreipunkt-Menü ⋮ und wähle <b>„App installieren“</b> oder <b>„Zum Startbildschirm hinzufügen“</b>; manchmal bietet der Browser es selbst mit einem Hinweis unten an. Auf <b>iPhone und iPad</b> muss es Safari sein: tippe auf <b>Teilen</b> (das Quadrat mit dem Pfeil nach oben), scrolle bis <b>„Zum Home-Bildschirm“</b> und bestätige. Am Computer zeigen Chrome und Edge rechts in der Adressleiste ein Installationssymbol. Wenn es einmal hakt, lösche sie vom Startbildschirm und wiederhole es.</p>

<h2>Wo der ganze Rest ist</h2>
<p>Am Handy passen nur zwei Menüs: oben <b>Medios · Radar · Substacks</b> und <b>Originales</b>; unten <b>Chat</b>, <b>Themen</b>, <b>Communitys</b>, <b>Nutzer</b>, <b>Prozesse</b>, <b>Statistiken</b> und <b>Nachrichten</b>. Der Rest steht <b>am Ende jeder Seite</b>: scrolle ganz nach unten und du findest die Links zu <b>Was ist {site}?</b>, dieser Anleitung, dem <b>kontralgorithmischen Manifest</b>, den <b>Regeln</b>, <b>Datenschutz und Kontakt</b>, dem Hinweis zu <b>Cookies</b>, <b>wie das δ funktioniert</b>, den Communitys und dem Katalog der <b>Medien</b> und <b>Substacks</b>, denen du folgen kannst. Am Computer sitzt dasselbe Menü fest unter der linken Spalte. Wenn du etwas suchst und nicht findest, ist es fast sicher dort.</p>

<h2>Brauche ich ein Konto?</h2>
<p>Zum Lesen nicht. Zum Abstimmen, Links einreichen, Favoriten speichern und Medien folgen schon. Es ist in einer Minute unter <a href="/registro">Konto erstellen</a> angelegt: Nutzername, E-Mail und Passwort. Du bekommst eine E-Mail mit einem Aktivierungslink; wenn du sie nicht siehst, schau in den Spam-Ordner, und wenn sie nicht ankommt, fordere unter <a href="/reenviar">erneut senden</a> eine neue an. Wir fragen weder nach Telefonnummer noch nach sonst etwas.</p>

<h2>Die Titelseiten</h2>
<p>Oben gibt es drei: <b>Medios</b> (Presse und Websites), <b>Radar</b> in der Mitte und <b>Substacks</b> (Newsletter). Medios und Substacks sind die Titelseiten dessen, was den Filter der Stimmen schon passiert hat; jede hat ihre eigene Warteschlange und ihr eigenes Tempo, und nichts von der einen mischt sich mit der anderen. Die, die du beim Ankommen siehst, ist die zuletzt geöffnete.</p>

<h2>Das Radar</h2>
<p>Das ist die Seite, durch die alles geht. Alles, was jemand einreicht, erscheint zuerst dort, und dort wird abgestimmt: Wenn du mitbestimmen willst, was aufsteigt, ist das Radar dein Platz. Alle {cycle} Minuten springt der Beitrag mit der meisten Unterstützung der Titelseite, die an der Reihe ist (einmal Medios, einmal Substacks), dorthin, sofern er eine Mindestzahl an Stimmen erreicht. Unter jedem Beitrag siehst du die genaue Rechnung (das <b>δ</b>): wie viele Stimmen, was das Thema dazugibt und was die Zeit abzieht. Mehr gibt es nicht; alles ist erklärt unter <a href="/algoritmo">Wie das δ funktioniert</a>.</p>

<h2>Abstimmen</h2>
<p>Jeder Beitrag hat einen Pfeil nach oben und einen nach unten. Oben heißt „das verdient die Titelseite“; unten „das nicht“. Eine Stimme pro Person und Beitrag. Wenn du dich vertust, drücke in den ersten Minuten noch einmal denselben Pfeil, und sie wird zurückgenommen. Eigene Beiträge kann man nicht bewerten.</p>

<h2>Einen Link einreichen</h2>
<p>Der <b>+</b>-Knopf (unten rechts, oder <b>Enviar</b> im Menü). Füge die Adresse des Artikels, Newsletters oder Videos ein; {site} füllt Titel, Vorspann und Bild aus, und du passt sie an, wenn du magst. Wähle ein <b>Thema</b> und schicke ab. Es landet im Radar, den Rest entscheiden die Leute. Links gehen ohne Tracking-Codes raus: die entfernen wir. Hat es schon jemand eingereicht, landest du in dessen Thread. Hast du etwas Eigenes zu sagen, ohne Link, ist das ein <a href="/articulo">Original</a>: ein Text bis tausend Wörter, der in seinem eigenen Reiter gelesen, bewertet und kommentiert wird.</p>

<h2>Kommentieren</h2>
<p>Jeder Beitrag öffnet einen <b>Thread</b>. Klicke auf den Titel (oder die Zahl der Kommentare), und ganz unten ist das Schreibfeld. Um jemandem gezielt zu antworten, <b>Antworten</b> unter seinem Kommentar: deine Antwort hängt dann an seiner. Kommentare haben eine <b>Sonne</b> (gefällt mir) und eine <b>Träne</b> (gefällt mir nicht); sie ändern nichts an der Titelseite, sie sagen nur, was etwas beiträgt und was nicht. Threads werden nach {ttl} Stunden archiviert: lesen geht noch, schreiben nicht mehr. Das Gespräch ist hier mit Absicht flüchtig.</p>

<h2>Der Ghost-Modus</h2>
<p>Wenn du einreichst, abstimmst oder kommentierst, erscheint dein Name in den ersten {gm} Minuten nicht: Man sieht einen Alias. Danach wird er enthüllt. So wird zuerst beurteilt, was du sagst, nicht wer es sagt. Wird dir jemand unerträglich, gibt es in seinem Profil <b>Ghosten</b>: Du siehst ihn nicht mehr, ohne dass es jemand erfährt.</p>

<h2>Der Chat</h2>
<p>Ein einziger Strom kurzer Nachrichten, um über alles zu reden, {site} eingeschlossen. Schreibe <b>#123</b>, um Nachricht 123 zu zitieren (wird zum Link), und <b>@name</b>, um jemanden zu erwähnen. <b>Nach Threads</b> gruppiert jede Nachricht mit ihren Antworten; <b>Nach Zeit</b> zeigt sie in der Reihenfolge des Eintreffens. Wenn etwas nicht funktioniert oder du nicht weißt, wie es geht, frag dort: Es ist auch der Hilfekanal, und Nachrichten mit einem Megafon sind Hinweise des Teams an die ganze Community.</p>

<h2>Meine Medien und meine Substacks</h2>
<p>Unter <a href="/medios">Medios</a> steht der Katalog der Quellen mit RSS. Drücke <b>Folgen</b> bei denen, die du liest, und ihre neuesten Meldungen warten in der linken Spalte und unter <b>Neuestes</b>; von dort bringt <b>ans Radar senden</b> sie mit einem Klick ins Spiel. Fehlt dein Newsletter oder dein Medium, schlage es auf derselben Seite vor.</p>

<h2>Die Aura</h2>
<p>Das ist dein Ruf, als Zahl, die langsam wächst: Sie steigt, wenn dein Beitrag die Titelseite erreicht oder dein Kommentar gefällt, und sinkt jeden Tag ein wenig, an dem du nichts tust. Aura schaltet Dinge frei (zum Beispiel, eine Meldung ein paar Minuten auf der Titelseite <b>hervorzuheben</b>). Man muss ihr nicht nachjagen: Sie kommt von selbst.</p>

<h2>Nach deinem Geschmack</h2>
<p>In <a href="/perfil">deinem Profil</a> wählst du Foto, Sprache (neun), helles oder dunkles Design, den <b>Skin</b> (es gibt mehrere: klassisches Forum, Presse, dunkel…) und den <b>ablenkungsfreien Modus</b>, der die Spalten entfernt und nur das Lesen übrig lässt. Ohne Konto kannst du Skin, Design und Sprache ebenfalls im Menü ändern: Das wird in deinem Browser gespeichert und sonst nirgends.</p>

<h2>Was wir nicht tun</h2>
<p>Keine programmatische Werbung, keine Tracker, keine Drittanbieter-Cookies, kein verstecktes Kriterium, um zu sortieren, was du siehst. Die Regeln des Miteinanders stehen in den <a href="/normas">Nutzungsregeln</a>; was wir speichern und wie du uns schreibst, unter <a href="/privacidad">Datenschutz und Kontakt</a>. Siehst du etwas, das nicht da sein sollte: <b>melden</b> steht unter jedem Beitrag, Kommentar und jeder Nachricht.</p>

<p>Das war''s. Den Rest lernt man beim Benutzen. <a href="/registro">Erstelle dein Konto</a> oder, wenn du schon eins hast, <a href="/login">melde dich an</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('empezar.it', 'Per cominciare', '<h1>Per cominciare</h1>

<p>{site} è un posto dove la gente condivide ciò che legge —articoli di testate, newsletter di Substack, video— e ne discute. Qui non c''è un algoritmo che decide per te: cosa sale in prima pagina lo decidono i voti, con una regola unica, pubblica e uguale per tutti. Se arrivi da Substack o dai social e non hai mai usato un forum, ecco quello che devi sapere. Si legge in cinque minuti.</p>

<h2>Sul telefono: installa l''app</h2>
<p>{site} funziona come un''app senza passare da nessuno store (arriverà). Si installa dal browser in dieci secondi e resta con la sua icona nella schermata iniziale, a schermo intero e senza barra degli indirizzi. Su <b>Android</b> (Chrome, Edge, Brave, Samsung Internet): apri deltha.eu, tocca il menu con i tre puntini ⋮ e scegli <b>«Installa app»</b> o <b>«Aggiungi a schermata Home»</b>; a volte è il browser stesso a proporlo con un avviso in basso. Su <b>iPhone e iPad</b> dev''essere Safari: tocca il pulsante <b>condividi</b> (il quadrato con la freccia verso l''alto), scorri fino a <b>«Aggiungi alla schermata Home»</b> e conferma. Sul computer, Chrome ed Edge mostrano un''icona di installazione a destra della barra degli indirizzi. Se un giorno fa i capricci, toglila dalla Home e ripeti.</p>

<h2>Dove sta tutto il resto</h2>
<p>Sul telefono ci stanno solo due menu: in alto, <b>Medios · Radar · Substacks</b> e <b>Originales</b>; in basso, <b>Chat</b>, <b>Temi</b>, <b>Comunità</b>, <b>Utenti</b>, <b>Processi</b>, <b>Statistiche</b> e <b>Messaggi</b>. Il resto sta <b>in fondo a ogni pagina</b>: scorri fino in basso e troverai i link a <b>Che cos''è {site}?</b>, questa guida, il <b>manifesto contro-algoritmico</b>, le <b>regole</b>, la <b>privacy e i contatti</b>, la nota sui <b>cookie</b>, <b>come funziona il δ</b>, le comunità e il catalogo di <b>testate</b> e <b>substack</b> da seguire. Sul computer quello stesso menu sta fisso sotto la colonna di sinistra. Se cerchi qualcosa e non la vedi, quasi di sicuro è lì.</p>

<h2>Serve un account?</h2>
<p>Per leggere, no. Per votare, inviare link, salvare preferiti e seguire testate, sì. Si crea in un minuto su <a href="/registro">Crea account</a>: un nome utente, un''email e una password. Ti arriva un''email con un link di attivazione; se non la vedi, guarda nello spam, e se non arriva, chiedine un''altra su <a href="/reenviar">reinvia</a>. Non chiediamo telefono né altro.</p>

<h2>Le prime pagine</h2>
<p>In alto ce ne sono tre: <b>Medios</b> (stampa e siti), <b>Radar</b>, al centro, e <b>Substacks</b> (newsletter). Medios e Substacks sono le prime pagine di ciò che ha già passato il filtro dei voti; ognuna ha la sua coda e il suo ritmo, e niente dell''una si mescola con l''altra. Quella che vedi entrando è l''ultima che hai aperto.</p>

<h2>Il Radar</h2>
<p>È la pagina da cui passa tutto. Qualsiasi cosa qualcuno invii compare prima lì, ed è lì che si vota: se vuoi influire su cosa sale, il Radar è il tuo posto. Ogni {cycle} minuti, l''invio con più sostegno della pagina a cui tocca (una volta Medios, una volta Substacks) ci salta, purché raggiunga un minimo di voti. Sotto ogni invio vedi il conto esatto (il <b>δ</b>): quanti voti, cosa aggiunge il tema e quanto toglie il tempo. Non c''è altro; è tutto spiegato in <a href="/algoritmo">Come funziona il δ</a>.</p>

<h2>Votare</h2>
<p>Ogni invio ha una freccia in su e una in giù. Su vuol dire «questo merita la prima pagina»; giù, «questo no». Un voto per persona e per invio. Se sbagli, premi di nuovo la stessa freccia nei primi minuti e si annulla. Non votare i tuoi invii: non si può.</p>

<h2>Inviare un link</h2>
<p>Il pulsante <b>+</b> (in basso a destra, o <b>Enviar</b> nel menu). Incolla l''indirizzo dell''articolo, della newsletter o del video; {site} compila titolo, sommario e foto, e tu li ritocchi se vuoi. Scegli un <b>tema</b> e invia. Entra nel Radar e il resto lo decide la gente. I link partono senza codici di tracciamento: li togliamo noi. Se qualcuno l''aveva già inviato, ti porta alla sua discussione. Se hai qualcosa di tuo da dire, senza link, quello è un <a href="/articulo">Originale</a>: un testo fino a mille parole che si legge, si vota e si commenta nella sua scheda.</p>

<h2>Commentare</h2>
<p>Ogni invio apre una <b>discussione</b>. Clicca sul titolo (o sul numero di commenti) e in fondo trovi la casella per scrivere. Per rispondere a qualcuno in particolare, <b>Rispondi</b> sotto il suo commento: la tua risposta resta appesa alla sua. I commenti hanno un <b>sole</b> (mi piace) e una <b>lacrima</b> (non mi piace); non cambiano la prima pagina, dicono solo cosa aggiunge e cosa no. Le discussioni si archiviano dopo {ttl} ore: si possono leggere, ma non ci si scrive più. Qui la conversazione è effimera di proposito.</p>

<h2>La modalità Ghost</h2>
<p>Quando invii, voti o commenti, nei primi {gm} minuti il tuo nome non compare: si vede un alias. Passato quel tempo, si rivela. Così la prima cosa a essere giudicata è ciò che dici, non chi lo dice. Se qualcuno ti risulta insopportabile, nella sua scheda c''è <b>Ghost</b>: smetti di vederlo senza che nessuno lo sappia.</p>

<h2>La Chat</h2>
<p>È un unico flusso di messaggi brevi per parlare di qualsiasi cosa, {site} compresa. Scrivi <b>#123</b> per citare il messaggio 123 (diventa un link) e <b>@nome</b> per menzionare qualcuno. <b>Per discussioni</b> raggruppa ogni messaggio con le sue risposte; <b>Per tempo</b> li mostra in ordine di arrivo. Se qualcosa non ti funziona o non sai come si fa, chiedi lì: è anche il canale di aiuto, e i messaggi segnati con un megafono sono avvisi del team a tutta la comunità.</p>

<h2>Le mie testate e i miei Substack</h2>
<p>In <a href="/medios">Medios</a> c''è il catalogo delle fonti con RSS. Premi <b>Segui</b> su quelle che leggi e le loro ultime notizie ti aspettano nella colonna di sinistra e in <b>Ultimo</b>; da lì, <b>invia al radar</b> le mette in gioco con un clic. Se manca la tua newsletter o la tua testata, proponila da quella stessa pagina.</p>

<h2>L''aura</h2>
<p>È la tua reputazione, sotto forma di un numero che cresce piano: sale quando ciò che invii arriva in prima pagina o ciò che commenti piace, e scende un po'' ogni giorno senza fare niente. Con l''aura si sbloccano cose (per esempio, <b>Mettere in evidenza</b> una notizia per qualche minuto in prima pagina). Non serve inseguirla: arriva da sola.</p>

<h2>A modo tuo</h2>
<p>Nel <a href="/perfil">tuo profilo</a> scegli foto, lingua (nove), tema chiaro o scuro, la <b>skin</b> (ce ne sono diverse: forum classico, stampa, scura…) e la <b>modalità senza distrazioni</b>, che toglie le colonne e lascia solo la lettura. Senza account puoi comunque cambiare skin, tema e lingua dal menu: si salvano nel tuo browser e basta.</p>

<h2>Quello che non facciamo</h2>
<p>Niente pubblicità programmatica, niente tracker, niente cookie di terze parti, nessun criterio nascosto per ordinare ciò che vedi. Le regole di convivenza sono in <a href="/normas">Regole d''uso</a>; cosa conserviamo e come scriverci, in <a href="/privacidad">Privacy e contatti</a>. Se vedi qualcosa che non dovrebbe esserci, <b>segnala</b> sta sotto ogni invio, commento e messaggio.</p>

<p>È tutto. Il resto si impara usandolo. <a href="/registro">Crea il tuo account</a> o, se ce l''hai già, <a href="/login">entra</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('empezar.pt', 'Como começar', '<h1>Como começar</h1>

<p>{site} é um sítio onde as pessoas partilham o que leem —artigos de meios, boletins do Substack, vídeos— e o comentam. Aqui não há um algoritmo que decida por ti: o que sobe à primeira página é decidido pelos votos, com uma regra única, pública e igual para todos. Se vens do Substack ou das redes e nunca usaste um fórum, isto é o que precisas de saber. Lê-se em cinco minutos.</p>

<h2>No telemóvel: instala a app</h2>
<p>{site} funciona como uma app sem passar por nenhuma loja (há de chegar). Instala-se a partir do navegador em dez segundos e fica com o seu ícone no ecrã inicial, em ecrã inteiro e sem a barra de endereços. No <b>Android</b> (Chrome, Edge, Brave, Samsung Internet): abre deltha.eu, toca no menu dos três pontos ⋮ e escolhe <b>«Instalar aplicação»</b> ou <b>«Adicionar ao ecrã principal»</b>; às vezes o próprio navegador oferece-o com um aviso em baixo. No <b>iPhone e iPad</b> tem de ser o Safari: toca no botão de <b>partilhar</b> (o quadrado com a seta para cima), desce até <b>«Adicionar ao ecrã principal»</b> e confirma. No computador, o Chrome e o Edge mostram um ícone de instalar à direita da barra de endereços. Se alguma vez falhar, apaga-a do ecrã inicial e repete.</p>

<h2>Onde está tudo o resto</h2>
<p>No telemóvel só cabem dois menus: em cima, <b>Medios · Radar · Substacks</b> e <b>Originales</b>; em baixo, <b>Chat</b>, <b>Temas</b>, <b>Comunidades</b>, <b>Utilizadores</b>, <b>Processos</b>, <b>Estatísticas</b> e <b>Mensagens</b>. O resto está <b>no fim de cada página</b>: desce até ao fundo e encontras as ligações para <b>O que é {site}?</b>, este guia, o <b>manifesto contra-algorítmico</b>, as <b>regras</b>, a <b>privacidade e contacto</b>, a nota sobre <b>cookies</b>, <b>como funciona o δ</b>, as comunidades e o catálogo de <b>meios</b> e <b>substacks</b> para os seguir. No computador esse mesmo menu fica fixo sob a coluna da esquerda. Se procuras algo e não o vês, quase de certeza está ali.</p>

<h2>É preciso conta?</h2>
<p>Para ler, não. Para votar, enviar ligações, guardar favoritos e seguir meios, sim. Cria-se num minuto em <a href="/registro">Criar conta</a>: um nome de utilizador, um e-mail e uma palavra-passe. Recebes um e-mail com uma ligação de ativação; se não a vires, procura na pasta de spam, e se não chegar, pede outra em <a href="/reenviar">reenviar</a>. Não pedimos telefone nem mais nada.</p>

<h2>As primeiras páginas</h2>
<p>Em cima há três: <b>Medios</b> (imprensa e sites), <b>Radar</b>, ao centro, e <b>Substacks</b> (boletins e newsletters). Medios e Substacks são as primeiras páginas do que já passou o filtro dos votos; cada uma tem a sua própria fila e o seu próprio ritmo, e nada de uma se mistura com a outra. A que vês ao entrar é a última que abriste.</p>

<h2>O Radar</h2>
<p>É a página por onde tudo passa. Qualquer coisa que alguém envia aparece primeiro ali, e é ali que se vota: se queres influenciar o que sobe, o Radar é o teu lugar. A cada {cycle} minutos, o envio com mais apoio da página a que toca a vez (uma vez Medios, outra Substacks) salta para ela, desde que chegue a um mínimo de votos. Por baixo de cada envio vês a conta exata (o <b>δ</b>): quantos votos, o que soma o tema e quanto desconta o tempo. Não há mais nada; está tudo explicado em <a href="/algoritmo">Como funciona o δ</a>.</p>

<h2>Votar</h2>
<p>Cada envio tem uma seta para cima e outra para baixo. Para cima é «isto merece primeira página»; para baixo, «isto não». Um voto por pessoa e envio. Se te enganares, carrega outra vez na mesma seta nos primeiros minutos e anula-se. Não votes nos teus próprios envios: não se pode.</p>

<h2>Enviar uma ligação</h2>
<p>O botão <b>+</b> (em baixo à direita, ou <b>Enviar</b> no menu). Cola o endereço do artigo, do boletim ou do vídeo; {site} preenche o título, a entrada e a foto, e tu retocas se quiseres. Escolhe um <b>tema</b> e envia. Entra no Radar e o resto decidem as pessoas. As ligações vão sem códigos de rastreio: tiramo-los nós. Se alguém já o tinha enviado, leva-te ao seu tópico. Se tens algo próprio para dizer, sem ligação, isso é um <a href="/articulo">Original</a>: um texto de até mil palavras que se lê, se vota e se comenta no seu separador.</p>

<h2>Comentar</h2>
<p>Cada envio abre um <b>tópico</b>. Carrega no título (ou no número de comentários) e no fundo tens a caixa para escrever. Para responder a alguém em concreto, <b>Responder</b> por baixo do seu comentário: a tua resposta fica pendurada da dele. Os comentários têm um <b>sol</b> (gosto) e uma <b>lágrima</b> (não gosto); não mudam a primeira página, só dizem o que acrescenta e o que não. Os tópicos arquivam-se às {ttl} horas: podem ler-se, mas já não se escreve neles. Aqui a conversa é efémera de propósito.</p>

<h2>O modo Ghost</h2>
<p>Quando envias, votas ou comentas, durante os primeiros {gm} minutos o teu nome não aparece: vê-se um alias. Passado esse tempo, revela-se. Assim a primeira coisa a ser julgada é o que dizes, não quem o diz. Se alguém te for insuportável, na sua ficha tens <b>Ghostear</b>: deixas de o ver sem que ninguém saiba.</p>

<h2>O Chat</h2>
<p>É um único fluxo de mensagens curtas para falar do que for, {site} incluída. Escreve <b>#123</b> para citar a mensagem 123 (transforma-se em ligação) e <b>@nome</b> para mencionar alguém. <b>Por tópicos</b> agrupa cada mensagem com as suas respostas; <b>Por tempo</b> mostra-as por ordem de chegada. Se algo não te funciona ou não sabes como se faz, pergunta ali: é também o canal de ajuda, e as mensagens marcadas com um megafone são avisos da equipa a toda a comunidade.</p>

<h2>Os meus meios e os meus Substacks</h2>
<p>Em <a href="/medios">Medios</a> está o catálogo de fontes com RSS. Carrega em <b>Seguir</b> nas que lês e as suas últimas notícias esperam-te na coluna da esquerda e em <b>Último</b>; a partir daí, <b>enviar ao radar</b> põe-nas em jogo com um clique. Se falta o teu boletim ou o teu meio, propõe-no a partir dessa mesma página.</p>

<h2>A aura</h2>
<p>É a tua reputação, na forma de um número que cresce devagar: sobe quando o que envias chega à primeira página ou o que comentas agrada, e desce um pouco cada dia sem fazer nada. Com aura desbloqueiam-se coisas (por exemplo, <b>Destacar</b> uma notícia uns minutos na primeira página). Não é preciso persegui-la: chega sozinha.</p>

<h2>Ao teu gosto</h2>
<p>No <a href="/perfil">teu perfil</a> escolhes foto, idioma (nove), tema claro ou escuro, a <b>skin</b> (há várias: clássica de fórum, imprensa, escura…) e o <b>modo sem distrações</b>, que tira colunas e deixa só a leitura. Sem conta também podes mudar skin, tema e idioma a partir do menu: guardam-se no teu navegador e mais nada.</p>

<h2>O que não fazemos</h2>
<p>Não há publicidade programática, nem rastreadores, nem cookies de terceiros, nem nenhum critério oculto para ordenar o que vês. As regras de convivência estão em <a href="/normas">Regras de utilização</a>; o que guardamos e como nos escrever, em <a href="/privacidad">Privacidade e contacto</a>. Se vires algo que não devia lá estar, <b>reportar</b> está por baixo de cada envio, comentário e mensagem.</p>

<p>É tudo. O resto aprende-se a usar. <a href="/registro">Cria a tua conta</a> ou, se já a tens, <a href="/login">entra</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
