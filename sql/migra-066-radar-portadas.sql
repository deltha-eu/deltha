-- Radar y dos portadas (24/09/2026). Deltha se aleja del esquema de Meneame:
--  * desaparece «Candidatas»: todo lo enviado espera en el radar (status 'nuevas', que se queda como nombre interno);
--  * dos portadas, Medios y Substacks (stories.portada). Un enlace va a Substacks si su dominio es *.substack.com o el de una
--    fuente del catalogo de tipo newsletter; lo demas, a Medios. Los originales (articulos) no van a ninguna portada;
--  * el ciclo alterna portada: cada cycle_minutes publica en una, el enlace de mayor δ de esa portada con al menos
--    delta_min_age_min y δ >= delta_min_publish. delta_min_candidate deja de existir;
--  * promo_log guarda en que portada se publico;
--  * textos de «¿Que es Deltha?» y «Como funciona el δ» en los nueve idiomas (sustituyen el cuerpo entero: si se retoco
--    alguno a mano en admin > Paginas, volver a aplicar el retoque). El marcador {mincand} desaparece.
SET NAMES utf8mb4;

ALTER TABLE stories ADD COLUMN portada ENUM('medios','substacks') DEFAULT NULL AFTER status,
                    ADD KEY idx_portada (portada, status, published_date);
ALTER TABLE promo_log ADD COLUMN portada ENUM('medios','substacks') DEFAULT NULL AFTER story_id;

-- las candidatas vuelven al radar y el estado desaparece del ENUM
UPDATE stories SET status = 'nuevas' WHERE status = 'candidatas';
ALTER TABLE stories MODIFY status ENUM('pending_mod','rejected','nuevas','destacadas','archived','discard') NOT NULL DEFAULT 'nuevas';

-- portada de todos los enlaces (tambien los ya publicados y archivados, para que cada portada tenga su historia)
UPDATE stories SET portada = CASE
    WHEN source = 'substack.com' OR source LIKE '%.substack.com'
      OR source IN (SELECT h FROM (SELECT DISTINCT LOWER(TRIM(LEADING 'www.' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(home, '://', -1), '/', 1))) AS h
                                     FROM sources WHERE kind = 'newsletter') nl)
    THEN 'substacks' ELSE 'medios' END
 WHERE content_type = 'link';
UPDATE promo_log p JOIN stories s ON s.story_id = p.story_id SET p.portada = s.portada;

DELETE FROM config WHERE ckey = 'delta_min_candidate';
UPDATE config SET descr = 'Minutos desde el envio antes de poder llegar a portada' WHERE ckey = 'delta_min_age_min';
UPDATE config SET descr = 'Delta minimo para publicar en portada (Medios o Substacks, segun el turno del ciclo)' WHERE ckey = 'delta_min_publish';
UPDATE config SET descr = 'Horas desde el envio tras las que lo que sigue en el radar sin llegar a portada se archiva (0 = nunca)' WHERE ckey = 'nuevas_ttl_hours';
UPDATE config SET descr = 'Fuentes: «populares» = nuevas de las ultimas N horas ordenadas por votos' WHERE ckey = 'sources_popular_hours';
UPDATE config SET descr = 'Fuentes: votos minimos de una nueva para salir en «populares»' WHERE ckey = 'sources_popular_min_votes';

-- «¿Que es Deltha?» y «Como funciona el δ»: radar, dos portadas y originales (cuerpo entero, nueve idiomas)
UPDATE pages SET body = '<h1>¿Qué es Deltha?</h1>

<p>{site} es cuatro cosas a la vez: <b>un agregador de noticias</b>, donde la gente envía enlaces y decide con sus votos qué llega a sus dos portadas, Medios y Substacks; <b>un lector de fuentes</b>, que recoge por RSS lo que publican medios, newsletters y otros agregadores y lo muestra, sin algoritmo, de lo más reciente a lo más antiguo; <b>una red social contralgorítmica</b>, sin máquina que elija por ti qué ver; y <b>un espacio para el debate</b>, con hilos que se abren, se discuten y se cierran.</p>

<h2>De dónde viene</h2>
<p>De cinco sitios. Del <a href="https://contralgoritmia.com/" rel="noopener">manifiesto contralgorítmico</a> viene la idea de fondo: recuperar el criterio humano frente a la hegemonía del algoritmo. De <b>Menéame</b>, el agregador: la comunidad decide qué llega a portada. De <b>X</b>, el diseño: una línea de tiempo limpia y rápida. De <b>4chan</b>, la efimeridad: los hilos nacen, se discuten y se cierran. Y del <b>modelo de negocio de Jot Down</b>, la forma de sostenerse: sin publicidad programática ni rastreo, una comunidad que sostiene lo que lee.</p>

<h2>Cómo funciona</h2>
<p><b>Dos portadas y un radar.</b> Todo lo que se envía entra en el <b>radar</b>. De ahí sale, votado, hacia una de las dos portadas: <b>Medios</b>, para las noticias de periódicos, revistas y demás webs, y <b>Substacks</b>, para las newsletters. Los <b>originales</b>, los textos que escribe la propia comunidad, tienen su pestaña y no compiten por portada.</p>
<p><b>Es efímera, pero no borra.</b> Cuando un envío lleva {ttl} horas en portada, se archiva con su conversación: se puede seguir leyendo, pero ya no se vota ni se comenta. Lo que no sale del radar se archiva a las {newttl} horas de enviarse. Lo que importa es la conversación de ahora, no la que se acumula.</p>
<p><b>No deja huella.</b> Sin cookies de rastreo, sin publicidad programática, sin terceros. Puedes comprobarlo abriendo las herramientas de tu navegador.</p>
<p><b>Es horizontal.</b> Todo se publica bajo un alias y el nombre del autor solo aparece a los {gm} minutos, para que se juzguen las palabras antes que a quien las dice.</p>
<p><b>Se ve por dentro.</b> La sección Procesos enseña en directo el radar, el δ de cada envío y cada voto, comentario y portada según ocurren.</p>
<p><b>Sin algoritmo oculto.</b> Lo que llega a cada portada lo decide una fórmula pública, corta y visible bajo cada envío: el <b>δ</b>. Está explicada entera en <a href="/algoritmo.php">Cómo funciona el δ</a>.</p>

<h2>Lo que llega por RSS</h2>
<p><b>{site} también lee.</b> Además de lo que envía la gente, recoge por RSS lo que publican medios, newsletters (de Substack y de otros sitios) y otros agregadores, y lo muestra con su propio aspecto, lo último primero. Sin algoritmo y sin rastreo: el RSS es un formato abierto que cualquier web puede ofrecer.</p>
<p><b>Tus fuentes, a mano.</b> En tu perfil eliges hasta {mediamax} medios y {newsmax} newsletters; si no eliges, verás los destacados. Los agregadores, como Menéame, se muestran tal cual, con sus propios votos y comentarios. ¿Echas algo en falta? Con {propaura} de aura o más puedes proponer un medio o una newsletter, y la moderación lo revisa.</p>
<p><b>Leer no es votar.</b> Nada de lo que llega por RSS entra solo en el radar de {site}. Cada noticia tiene un botón para enviarla al radar, y desde ahí sigue el camino de cualquier envío: votos, δ y ciclo.</p>

<h2>El aura</h2>
<p>No hay karma. Hay <b>aura</b>, y mide una sola cosa: tu compromiso con la comunidad. Se gana estando y aportando, no gustando: +{auraport} por cada envío tuyo que llega a portada, +{auraenv} por cada envío u original, +{auracom} por cada comentario, como mucho +{auracap} al día. Y baja: cada día pierdes {auradec}, hasta el mínimo de {aurastart}. No se acumula: se sostiene. Mide quién está, no quién estuvo.</p>
<p><b>Nunca da altavoz.</b> El aura no sube noticias ni ordena comentarios. Todo el mundo ve las mismas portadas, en el mismo orden. Se ve como luz alrededor de tu alias, nada más. Lo único que abre son puertas: con más de {commaura} puedes crear comunidades; con más de {editaura}, corregir tus comentarios recién escritos, y con más de {seditaura}, tus envíos.</p>

<h2>¿Qué se puede publicar?</h2>
<p><b>Todo lo que permite la ley.</b> El límite no lo pone una línea editorial ni un gusto: lo pone la ley. Fuera quedan los insultos, los delitos de odio, las amenazas y todo lo que sea delito. Están escritas en las <a href="/normas.php">normas de uso</a>.</p>
<p><b>Modera una IA que vive en nuestros servidores.</b> Ningún envío ni comentario sale de ellos para ser revisado: no hay terceros mirando lo que escribes. Cuando duda, decide una persona. Cualquiera puede reportar un comentario.</p>
<p><b>Y hay un correo para todo lo demás.</b> Quejas, felicitaciones y propuestas: <b>info@deltha.eu</b>.</p>

<h2>Temas y comunidades</h2>
<p><b>Los temas los pone {site}.</b> Pocos y estables: actualidad, ciencia, cultura… Cada envío lleva uno, y el tema puede dar un pequeño empujón al δ (un coeficiente público).</p>
<p><b>Las comunidades las crea la gente.</b> Con más de {commaura} de aura puedes abrir hasta {commmax}: un nombre, un color y de qué va. Un envío puede sumarse a una o varias, y quien la creó (o un moderador) la cuida. Cada comunidad puede elegir una skin propia, que se aplica a su página y a sus hilos. Y tú puedes elegir una comunidad o un tema como página de entrada.</p>

<h2>A tu manera</h2>
<p><b>{skins} skins.</b> Mismas portadas, mismos números, distinta piel: de revista, de foro, de terminal, de manga, de fanzine… Se elige en la pestaña Skins, y cada una tiene versión clara y oscura.</p>
<p><b>En tu idioma y en tu bolsillo.</b> Nueve idiomas: español, catalán, euskera, gallego, inglés, francés, alemán, italiano y portugués. Se instala como app en el móvil, con mensajes privados y chat entre registrados, y puedes guardar envíos en favoritos.</p>
<p><b>Lo que es de todos y lo que es tuyo.</b> Las dos portadas y el radar son los mismos para todo el mundo, en el mismo orden. Lo que sí personalizas es el camino: qué medios y newsletters tienes a mano, y si al abrir {site} entras en una portada (la última que abriste), en un tema o en una comunidad. Cambia por dónde llegas, no lo que decide la comunidad.</p>

<h2>Fuera de los buscadores</h2>
<p><b>Lo que pasa en {site} se queda en {site}.</b> Ningún envío, comentario ni perfil aparece en Google ni en otros buscadores: todas las páginas llevan la marca <i>noindex</i>. Solo se dejan ver las páginas que explican el proyecto, como esta.</p>
<p><b>Salvo que tú lo quieras.</b> Si escribes un original, puedes marcar la casilla para que los buscadores lo indexen. Es tu texto y decides tú; se puede cambiar al editarlo.</p>
<p><b>Y ninguna IA se entrena con lo que escribes.</b> Los rastreadores de inteligencia artificial (los de OpenAI, Anthropic, Google, Meta, Common Crawl y compañía) tienen la entrada vetada, incluso en los originales abiertos a buscadores.</p>

<h2>¿Quieres ser propietario de {site}?</h2>
<p>{site} es un proyecto abierto a todo el mundo. Para ser copropietario no te pedimos dinero: solo que uses la plataforma. <b>El 50 % de la propiedad de {site} se repartirá entre todos los usuarios registrados que tengan aura {auramax} el {auradl}.</b></p>
<p>¿Quieres probarla? <a href="/registro.php">Regístrate</a> o, si ya tienes cuenta, <a href="/login.php">entra</a>.</p>' WHERE slug = 'que-es';
UPDATE pages SET body = '<h1>What is Deltha?</h1>

<p>{site} is four things at once: <b>a news aggregator</b>, where people submit links and their votes decide what makes its two front pages, Media and Substacks; <b>a feed reader</b>, which picks up via RSS what media outlets, newsletters and other aggregators publish and shows it, with no algorithm, newest first; <b>a counter-algorithmic social network</b>, with no machine choosing what you see; and <b>a place for debate</b>, with threads that open, get argued over and then close.</p>

<h2>Where it comes from</h2>
<p>From five places. From the <a href="https://contralgoritmia.com/" rel="noopener">counter-algorithmic manifesto</a> comes the core idea: reclaiming human judgement from the hegemony of the algorithm. From <b>Menéame</b>, the aggregator: the community decides what makes the front page. From <b>X</b>, the design: a clean, fast timeline. From <b>4chan</b>, the ephemerality: threads are born, argued over and then closed. And from <b>Jot Down''s business model</b>, the way to stay afloat: no programmatic advertising, no tracking, just a community that supports what it reads.</p>

<h2>How it works</h2>
<p><b>Two front pages and a radar.</b> Everything submitted goes into the <b>radar</b>. From there, once voted on, it heads for one of the two front pages: <b>Media</b>, for stories from newspapers, magazines and other websites, and <b>Substacks</b>, for newsletters. <b>Originals</b>, the texts the community writes itself, have their own tab and don''t compete for a front page.</p>
<p><b>It''s ephemeral, but it doesn''t delete.</b> Once a post has been on the front page for {ttl} hours, it is archived along with its conversation: you can still read it, but you can no longer vote or comment. Posts that never leave the radar are archived {newttl} hours after being submitted. What matters is the conversation happening now, not what piles up.</p>
<p><b>It leaves no trace.</b> No tracking cookies, no programmatic advertising, no third parties. You can check for yourself with your browser''s developer tools.</p>
<p><b>It''s horizontal.</b> Everything is published under an alias, and the author''s name only appears after {gm} minutes, so the words are judged before the person saying them.</p>
<p><b>You can see inside it.</b> The Processes section shows the radar live, the δ of every post, and every vote, comment and front-page story as it happens.</p>
<p><b>No hidden algorithm.</b> What makes each front page is decided by a public formula, short and visible under every post: the <b>δ</b>. It''s explained in full in <a href="/algoritmo.php">How δ works</a>.</p>

<h2>What comes in via RSS</h2>
<p><b>{site} reads too.</b> Besides what people submit, it picks up via RSS what media outlets, newsletters (on Substack and elsewhere) and other aggregators publish, and shows it in its own look, newest first. No algorithm and no tracking: RSS is an open format any website can offer.</p>
<p><b>Your sources, close at hand.</b> In your profile you choose up to {mediamax} media outlets and {newsmax} newsletters; if you don''t choose, you''ll see the featured ones. Aggregators such as Menéame are shown as they are, with their own votes and comments. Missing something? With {propaura} aura or more you can suggest an outlet or a newsletter, and the moderators review it.</p>
<p><b>Reading isn''t voting.</b> Nothing that comes in via RSS enters the {site} radar by itself. Every story has a button to send it to the radar, and from there it follows the same path as any other post: votes, δ and cycle.</p>

<h2>Aura</h2>
<p>There''s no karma. There''s <b>aura</b>, and it measures just one thing: your commitment to the community. You earn it by being here and contributing, not by being liked: +{auraport} for each of your posts that makes the front page, +{auraenv} for each post or original, +{auracom} for each comment, up to +{auracap} a day. And it goes down: every day you lose {auradec}, down to a minimum of {aurastart}. It doesn''t pile up: it has to be kept up. It measures who''s here, not who used to be.</p>
<p><b>It never gives you a megaphone.</b> Aura doesn''t push stories up or rank comments. Everyone sees the same front pages, in the same order. It shows as a glow around your alias, nothing more. All it does is open doors: with more than {commaura} you can create communities; with more than {editaura}, correct comments you''ve just written; and with more than {seditaura}, your posts.</p>

<h2>What can you post?</h2>
<p><b>Anything the law allows.</b> The limit isn''t set by an editorial line or anyone''s taste: it''s set by the law. Insults, hate crimes, threats and anything else that''s a crime are out. It''s all written down in the <a href="/normas.php">rules of use</a>.</p>
<p><b>Moderation is done by an AI that lives on our servers.</b> No post or comment leaves them to be reviewed: there are no third parties looking at what you write. When it''s unsure, a person decides. Anyone can report a comment.</p>
<p><b>And there''s an email address for everything else.</b> Complaints, compliments and suggestions: <b>info@deltha.eu</b>.</p>

<h2>Topics and communities</h2>
<p><b>Topics are set by {site}.</b> Few and stable: current affairs, science, culture… Every post has one, and the topic can give the δ a small nudge (a public coefficient).</p>
<p><b>Communities are created by people.</b> With more than {commaura} aura you can start up to {commmax}: a name, a colour and what it''s about. A post can join one or several, and whoever created the community (or a moderator) looks after it. Each community can pick its own skin, applied to its page and its threads. And you can choose a community or a topic as your landing page.</p>

<h2>Your way</h2>
<p><b>{skins} skins.</b> Same front pages, same numbers, different skin: magazine, forum, terminal, manga, fanzine… You pick one in the Skins tab, and each comes in a light and a dark version.</p>
<p><b>In your language and in your pocket.</b> Nine languages: Spanish, Catalan, Basque, Galician, English, French, German, Italian and Portuguese. It installs as an app on your phone, with private messages and chat between registered users, and you can save posts to your favourites.</p>
<p><b>What''s everyone''s and what''s yours.</b> The two front pages and the radar are the same for everyone, in the same order. What you do personalise is the way in: which outlets and newsletters you keep close at hand, and whether opening {site} takes you to a front page (the last one you opened), a topic or a community. It changes how you get there, not what the community decides.</p>

<h2>Off the search engines</h2>
<p><b>What happens on {site} stays on {site}.</b> No post, comment or profile shows up on Google or any other search engine: every page carries the <i>noindex</i> tag. Only the pages that explain the project, like this one, are visible.</p>
<p><b>Unless you want it to.</b> If you write an original, you can tick the box to let search engines index it. It''s your text and your call; you can change it when you edit it.</p>
<p><b>And no AI trains on what you write.</b> Artificial intelligence crawlers (from OpenAI, Anthropic, Google, Meta, Common Crawl and the like) are barred at the door, even from originals open to search engines.</p>

<h2>Want to own {site}?</h2>
<p>{site} is a project open to everyone. To become a co-owner we don''t ask you for money: just that you use the platform. <b>50% of the ownership of {site} will be shared among all registered users who have {auramax} aura on {auradl}.</b></p>
<p>Want to try it? <a href="/registro.php">Sign up</a> or, if you already have an account, <a href="/login.php">log in</a>.</p>' WHERE slug = 'que-es.en';
UPDATE pages SET body = '<h1>Què és Deltha?</h1>

<p>{site} és quatre coses alhora: <b>un agregador de notícies</b>, on la gent envia enllaços i decideix amb els seus vots què arriba a les seves dues portades, Mitjans i Substacks; <b>un lector de fonts</b>, que recull per RSS el que publiquen mitjans, butlletins i altres agregadors i ho mostra, sense algorisme, del més nou al més antic; <b>una xarxa social contraalgorítmica</b>, sense cap màquina que triï per tu què has de veure; i <b>un espai per al debat</b>, amb fils que s''obren, es discuteixen i es tanquen.</p>

<h2>D''on ve</h2>
<p>De cinc llocs. Del <a href="https://contralgoritmia.com/" rel="noopener">manifest contraalgorítmic</a> ve la idea de fons: recuperar el criteri humà davant l''hegemonia de l''algorisme. De <b>Menéame</b>, l''agregador: la comunitat decideix què arriba a portada. De <b>X</b>, el disseny: una línia de temps neta i ràpida. De <b>4chan</b>, l''efimeritat: els fils neixen, es discuteixen i es tanquen. I del <b>model de negoci de Jot Down</b>, la manera de sostenir-se: sense publicitat programàtica ni rastreig, una comunitat que sosté el que llegeix.</p>

<h2>Com funciona</h2>
<p><b>Dues portades i un radar.</b> Tot el que s''envia entra al <b>radar</b>. D''allà en surt, votat, cap a una de les dues portades: <b>Mitjans</b>, per a les notícies de diaris, revistes i altres webs, i <b>Substacks</b>, per als butlletins. Els <b>originals</b>, els textos que escriu la mateixa comunitat, tenen la seva pestanya i no competeixen per la portada.</p>
<p><b>És efímera, però no esborra.</b> Quan un enviament fa {ttl} hores que és a portada, s''arxiva amb la seva conversa: es pot continuar llegint, però ja no s''hi vota ni s''hi comenta. El que no surt del radar s''arxiva {newttl} hores després d''enviar-se. El que importa és la conversa d''ara, no la que s''acumula.</p>
<p><b>No deixa rastre.</b> Sense galetes de rastreig, sense publicitat programàtica, sense tercers. Ho pots comprovar obrint les eines del teu navegador.</p>
<p><b>És horitzontal.</b> Tot es publica sota un àlies i el nom de l''autor només apareix al cap de {gm} minuts, perquè es jutgin les paraules abans que qui les diu.</p>
<p><b>Es veu per dins.</b> La secció Processos mostra en directe el radar, el δ de cada enviament i cada vot, comentari i portada a mesura que passen.</p>
<p><b>Sense algorisme ocult.</b> El que arriba a cada portada ho decideix una fórmula pública, curta i visible sota cada enviament: el <b>δ</b>. Està explicada sencera a <a href="/algoritmo.php">Com funciona el δ</a>.</p>

<h2>El que arriba per RSS</h2>
<p><b>{site} també llegeix.</b> A més del que envia la gent, recull per RSS el que publiquen mitjans, butlletins (de Substack i d''altres llocs) i altres agregadors, i ho mostra amb el seu propi aspecte, el més nou primer. Sense algorisme i sense rastreig: l''RSS és un format obert que qualsevol web pot oferir.</p>
<p><b>Les teves fonts, a mà.</b> Al teu perfil tries fins a {mediamax} mitjans i {newsmax} butlletins; si no en tries, veuràs els destacats. Els agregadors, com Menéame, es mostren tal com són, amb els seus propis vots i comentaris. Hi trobes a faltar alguna cosa? Amb {propaura} d''aura o més pots proposar un mitjà o un butlletí, i la moderació el revisa.</p>
<p><b>Llegir no és votar.</b> Res del que arriba per RSS no entra sol al radar de {site}. Cada notícia té un botó per enviar-la al radar, i des d''allà segueix el camí de qualsevol enviament: vots, δ i cicle.</p>

<h2>L''aura</h2>
<p>No hi ha karma. Hi ha <b>aura</b>, i mesura una sola cosa: el teu compromís amb la comunitat. Es guanya estant-hi i aportant, no agradant: +{auraport} per cada enviament teu que arriba a portada, +{auraenv} per cada enviament o original, +{auracom} per cada comentari, com a màxim +{auracap} al dia. I baixa: cada dia en perds {auradec}, fins al mínim de {aurastart}. No s''acumula: se sosté. Mesura qui hi és, no qui hi era.</p>
<p><b>Mai no dona altaveu.</b> L''aura no puja notícies ni ordena comentaris. Tothom veu les mateixes portades, en el mateix ordre. Es veu com una llum al voltant del teu àlies, res més. L''únic que obre són portes: amb més de {commaura} pots crear comunitats; amb més de {editaura}, corregir els comentaris que acabes d''escriure, i amb més de {seditaura}, els teus enviaments.</p>

<h2>Què s''hi pot publicar?</h2>
<p><b>Tot el que permet la llei.</b> El límit no el posa una línia editorial ni un gust: el posa la llei. En queden fora els insults, els delictes d''odi, les amenaces i tot el que sigui delicte. Estan escrites a les <a href="/normas.php">normes d''ús</a>.</p>
<p><b>Modera una IA que viu als nostres servidors.</b> Cap enviament ni comentari no en surt per ser revisat: no hi ha tercers mirant el que escrius. Quan dubta, decideix una persona. Qualsevol pot denunciar un comentari.</p>
<p><b>I hi ha un correu per a tota la resta.</b> Queixes, felicitacions i propostes: <b>info@deltha.eu</b>.</p>

<h2>Temes i comunitats</h2>
<p><b>Els temes els posa {site}.</b> Pocs i estables: actualitat, ciència, cultura… Cada enviament en porta un, i el tema pot donar una petita empenta al δ (un coeficient públic).</p>
<p><b>Les comunitats les crea la gent.</b> Amb més de {commaura} d''aura en pots obrir fins a {commmax}: un nom, un color i de què va. Un enviament pot sumar-se a una o a diverses, i qui la va crear (o un moderador) en té cura. Cada comunitat pot triar una plantilla pròpia, que s''aplica a la seva pàgina i als seus fils. I tu pots triar una comunitat o un tema com a pàgina d''entrada.</p>

<h2>A la teva manera</h2>
<p><b>{skins} plantilles.</b> Les mateixes portades, els mateixos números, una pell diferent: de revista, de fòrum, de terminal, de manga, de fanzín… Es tria a la pestanya Plantilla, i cadascuna té versió clara i fosca.</p>
<p><b>En la teva llengua i a la butxaca.</b> Nou idiomes: castellà, català, basc, gallec, anglès, francès, alemany, italià i portuguès. S''instal·la com a aplicació al mòbil, amb missatges privats i xat entre registrats, i pots desar enviaments a favorits.</p>
<p><b>El que és de tothom i el que és teu.</b> Les dues portades i el radar són els mateixos per a tothom, en el mateix ordre. El que sí que personalitzes és el camí: quins mitjans i butlletins tens a mà, i si en obrir {site} entres en una portada (l''última que vas obrir), en un tema o en una comunitat. Canvia per on arribes, no el que decideix la comunitat.</p>

<h2>Fora dels cercadors</h2>
<p><b>El que passa a {site} es queda a {site}.</b> Cap enviament, comentari ni perfil no apareix a Google ni a altres cercadors: totes les pàgines porten la marca <i>noindex</i>. Només es deixen veure les pàgines que expliquen el projecte, com aquesta.</p>
<p><b>Llevat que tu ho vulguis.</b> Si escrius un original, pots marcar la casella perquè els cercadors l''indexin. És el teu text i hi decideixes tu; es pot canviar en editar-lo.</p>
<p><b>I cap IA no s''entrena amb el que escrius.</b> Els rastrejadors d''intel·ligència artificial (els d''OpenAI, Anthropic, Google, Meta, Common Crawl i companyia) tenen l''entrada vetada, fins i tot als originals oberts als cercadors.</p>

<h2>Vols ser propietari de {site}?</h2>
<p>{site} és un projecte obert a tothom. Per ser-ne copropietari no et demanem diners: només que facis servir la plataforma. <b>El 50 % de la propietat de {site} es repartirà entre tots els usuaris registrats que tinguin aura {auramax} el {auradl}.</b></p>
<p>Vols provar-la? <a href="/registro.php">Registra''t</a> o, si ja tens compte, <a href="/login.php">entra</a>.</p>' WHERE slug = 'que-es.ca';
UPDATE pages SET body = '<h1>Zer da Deltha?</h1>

<p>{site} lau gauza da aldi berean: <b>albiste-agregatzaile bat</b>, non jendeak estekak bidaltzen dituen eta botoekin erabakitzen duen zer iristen den bere bi azaletara, Hedabideak eta Substackak; <b>iturri-irakurle bat</b>, hedabideek, buletinek eta beste agregatzaile batzuek argitaratzen dutena RSS bidez jaso eta algoritmorik gabe erakusten duena, berrienetik hasita; <b>sare sozial kontralgoritmiko bat</b>, zer ikusi behar duzun zure ordez aukeratzen duen makinarik gabe; eta <b>eztabaidarako gune bat</b>, ireki, eztabaidatu eta itxi egiten diren hariekin.</p>

<h2>Nondik dator</h2>
<p>Bost gunetatik. <a href="https://contralgoritmia.com/" rel="noopener">Manifestu kontralgoritmikotik</a> dator funtsezko ideia: giza irizpidea berreskuratzea algoritmoaren hegemoniaren aurrean. <b>Menéame</b>tik, agregatzailea: komunitateak erabakitzen du zer iristen den azalera. <b>X</b> saretik, diseinua: denbora-lerro garbi eta azkarra. <b>4chan</b>etik, iragankortasuna: hariak sortu, eztabaidatu eta itxi egiten dira. Eta <b>Jot Downen negozio-eredutik</b>, bizirauteko modua: publizitate programatikorik eta jarraipenik gabe, irakurtzen duenari eusten dion komunitate bat.</p>

<h2>Nola funtzionatzen du</h2>
<p><b>Bi azal eta radar bat.</b> Bidaltzen den guztia <b>radarrean</b> sartzen da. Handik, bozkatuta, bi azaletako batera ateratzen da: <b>Hedabideak</b>, egunkari, aldizkari eta gainerako webguneetako albisteentzat, eta <b>Substackak</b>, buletinentzat. <b>Originalek</b>, komunitateak berak idatzitako testuek, beren fitxa dute eta ez dute azalerako lehiatzen.</p>
<p><b>Iragankorra da, baina ez du ezer ezabatzen.</b> Bidalketa batek {ttl} ordu azalean daramatzanean, bere elkarrizketarekin batera artxibatzen da: irakurtzen jarrai daiteke, baina ezin da gehiago bozkatu ez iruzkindu. Radarretik ateratzen ez dena bidali eta {newttl} ordura artxibatzen da. Orain gertatzen den elkarrizketa da garrantzitsua, ez metatzen dena.</p>
<p><b>Ez du arrastorik uzten.</b> Jarraipen-cookierik gabe, publizitate programatikorik gabe, hirugarrenik gabe. Zeuk egiazta dezakezu, nabigatzailearen tresnak irekita.</p>
<p><b>Horizontala da.</b> Dena ezizen batekin argitaratzen da, eta egilearen izena {gm} minuturen buruan baino ez da agertzen: lehenik hitzak epaitzen dira, gero esaten dituena.</p>
<p><b>Barrutik ikusten da.</b> Prozesuak atalak zuzenean erakusten ditu radarra, bidalketa bakoitzaren δ-a eta boto, iruzkin eta azal-igoera bakoitza, gertatu ahala.</p>
<p><b>Ezkutuko algoritmorik gabe.</b> Azal bakoitzera zer iristen den formula publiko, labur eta ikusgai batek erabakitzen du, bidalketa bakoitzaren azpian: <b>δ</b>-k. Osorik azalduta dago hemen: <a href="/algoritmo.php">Nola funtzionatzen duen δ-k</a>.</p>

<h2>RSS bidez iristen dena</h2>
<p><b>{site}-k irakurri ere egiten du.</b> Jendeak bidaltzen duenaz gain, hedabideek, buletinek (Substack-ekoek eta beste batzuek) eta beste agregatzaile batzuek argitaratzen dutena jasotzen du RSS bidez, eta bere itxurarekin erakusten du, berriena lehenengo. Algoritmorik eta jarraipenik gabe: RSS edozein webgunek eskain dezakeen formatu irekia da.</p>
<p><b>Zure iturriak, eskura.</b> Zure profilean {mediamax} hedabide eta {newsmax} buletin arte aukeratzen dituzu; aukeratzen ez baduzu, nabarmendutakoak ikusiko dituzu. Agregatzaileak, Menéame adibidez, diren bezala erakusten dira, beren boto eta iruzkinekin. Zerbait falta zaizu? {propaura} aura edo gehiagorekin hedabide edo buletin bat proposa dezakezu, eta moderazioak berrikusten du.</p>
<p><b>Irakurtzea ez da bozkatzea.</b> RSS bidez iristen den ezer ez da bere kasa sartzen {site}-ren radarrean. Albiste bakoitzak botoi bat du radarrera bidaltzeko, eta handik aurrera beste edozein bidalketaren bidea egiten du: botoak, δ eta zikloa.</p>

<h2>Aura</h2>
<p>Ez dago karmarik. <b>Aura</b> dago, eta gauza bakarra neurtzen du: komunitatearekiko zure konpromisoa. Egonez eta ekarriz irabazten da, ez gustatuz: +{auraport} azalera iristen den zure bidalketa bakoitzeko, +{auraenv} bidalketa edo original bakoitzeko, +{auracom} iruzkin bakoitzeko, gehienez +{auracap} egunean. Eta jaitsi egiten da: egunero {auradec} galtzen duzu, {aurastart} gutxienekora iritsi arte. Ez da metatzen: eutsi egiten zaio. Nor dagoen neurtzen du, ez nor egon zen.</p>
<p><b>Ez dizu inoiz bozgorailurik ematen.</b> Aurak ez ditu albisteak igotzen ez iruzkinak ordenatzen. Denek azal berak ikusten dituzte, ordena berean. Zure ezizenaren inguruko argi gisa ikusten da, besterik ez. Ateak baino ez ditu irekitzen: {commaura} baino gehiagorekin komunitateak sor ditzakezu; {editaura} baino gehiagorekin, idatzi berri dituzun iruzkinak zuzendu; eta {seditaura} baino gehiagorekin, zure bidalketak.</p>

<h2>Zer argitara daiteke?</h2>
<p><b>Legeak baimentzen duen guztia.</b> Muga ez du lerro editorial batek edo gustu batek jartzen: legeak jartzen du. Kanpoan geratzen dira irainak, gorroto-delituak, mehatxuak eta delitu den guztia. <a href="/normas.php">Erabilera-arauetan</a> daude idatzita.</p>
<p><b>Gure zerbitzarietan bizi den AA batek moderatzen du.</b> Bidalketa eta iruzkin bat ere ez da haietatik ateratzen berrikusteko: ez dago hirugarrenik idazten duzuna begira. Zalantza duenean, pertsona batek erabakitzen du. Edonork sala dezake iruzkin bat.</p>
<p><b>Eta gainerako guztirako, helbide elektroniko bat.</b> Kexak, zorionak eta proposamenak: <b>info@deltha.eu</b>.</p>

<h2>Gaiak eta komunitateak</h2>
<p><b>Gaiak {site}-k jartzen ditu.</b> Gutxi eta egonkorrak: gaurkotasuna, zientzia, kultura… Bidalketa bakoitzak bat darama, eta gaiak bultzadatxo bat eman diezaioke δ-ari (koefiziente publiko bat).</p>
<p><b>Komunitateak jendeak sortzen ditu.</b> {commaura} aura baino gehiagorekin {commmax} arte ireki ditzakezu: izen bat, kolore bat eta zertaz doan. Bidalketa bat komunitate batera edo gehiagotara batu daiteke, eta sortu zuenak (edo moderatzaile batek) zaintzen du. Komunitate bakoitzak bere txantiloia aukera dezake, bere orrian eta harietan aplikatzen dena. Eta zuk komunitate bat edo gai bat aukera dezakezu sarrera-orri gisa.</p>

<h2>Zure erara</h2>
<p><b>{skins} txantiloi.</b> Azal berak, zenbaki berak, itxura desberdina: aldizkari, foro, terminal, manga, fanzine… itxurakoak. Txantiloia fitxan aukeratzen da, eta bakoitzak bertsio argia eta iluna ditu.</p>
<p><b>Zure hizkuntzan eta zure poltsikoan.</b> Bederatzi hizkuntza: gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana, italiera eta portugesa. Mugikorrean aplikazio gisa instalatzen da, erregistratuen arteko mezu pribatuekin eta txatarekin, eta bidalketak gogokoetan gorde ditzakezu.</p>
<p><b>Denona dena eta zurea dena.</b> Bi azalak eta radarra berdinak dira guztientzat, ordena berean. Zuk pertsonalizatzen duzuna bidea da: zein hedabide eta buletin dituzun eskura, eta {site} irekitzean azal batean (ireki zenuen azkenean), gai batean edo komunitate batean sartzen zaren. Nondik iristen zaren aldatzen du, ez komunitateak erabakitzen duena.</p>

<h2>Bilatzaileetatik kanpo</h2>
<p><b>{site}-n gertatzen dena {site}-n geratzen da.</b> Bidalketa, iruzkin eta profil bat ere ez da agertzen Googlen ez beste bilatzaileetan: orri guztiek <i>noindex</i> marka daramate. Proiektua azaltzen duten orriak baino ez dira ikusgai, hau bezala.</p>
<p><b>Zuk nahi ezean.</b> Original bat idazten baduzu, laukia marka dezakezu bilatzaileek indexa dezaten. Zure testua da eta zuk erabakitzen duzu; editatzean alda daiteke.</p>
<p><b>Eta AArik ez da entrenatzen idazten duzunarekin.</b> Adimen artifizialeko arakatzaileek (OpenAI, Anthropic, Google, Meta, Common Crawl eta abarrenek) sarrera debekatuta dute, baita bilatzaileentzat irekitako originaletan ere.</p>

<h2>{site}-ren jabe izan nahi duzu?</h2>
<p>{site} mundu guztiari irekitako proiektua da. Jabekide izateko ez dizugu dirurik eskatzen: plataforma erabiltzea baino ez. <b>{site}-ren jabetzaren % 50 {auradl} egunean {auramax} aura duten erabiltzaile erregistratu guztien artean banatuko da.</b></p>
<p>Probatu nahi duzu? <a href="/registro.php">Erregistratu</a> edo, kontua baduzu jada, <a href="/login.php">sartu</a>.</p>' WHERE slug = 'que-es.eu';
UPDATE pages SET body = '<h1>Que é Deltha?</h1>

<p>{site} é catro cousas á vez: <b>un agregador de novas</b>, onde a xente envía ligazóns e decide cos seus votos que chega ás súas dúas portadas, Medios e Substacks; <b>un lector de fontes</b>, que recolle por RSS o que publican medios, boletíns e outros agregadores e o amosa, sen algoritmo, do máis novo ao máis vello; <b>unha rede social contralgorítmica</b>, sen máquina que escolla por ti que ver; e <b>un espazo para o debate</b>, con fíos que se abren, se discuten e se pechan.</p>

<h2>De onde vén</h2>
<p>De cinco sitios. Do <a href="https://contralgoritmia.com/" rel="noopener">manifesto contralgorítmico</a> vén a idea de fondo: recuperar o criterio humano fronte á hexemonía do algoritmo. De <b>Menéame</b>, o agregador: a comunidade decide que chega á portada. De <b>X</b>, o deseño: unha liña de tempo limpa e rápida. De <b>4chan</b>, a efemeridade: os fíos nacen, discútense e péchanse. E do <b>modelo de negocio de Jot Down</b>, a maneira de soster o proxecto: sen publicidade programática nin rastrexo, unha comunidade que sostén o que le.</p>

<h2>Como funciona</h2>
<p><b>Dúas portadas e un radar.</b> Todo o que se envía entra no <b>radar</b>. De aí sae, votado, cara a unha das dúas portadas: <b>Medios</b>, para as novas de xornais, revistas e demais webs, e <b>Substacks</b>, para os boletíns. Os <b>orixinais</b>, os textos que escribe a propia comunidade, teñen a súa lapela e non compiten por portada.</p>
<p><b>É efémera, pero non borra.</b> Cando un envío leva {ttl} horas na portada, arquívase coa súa conversa: pódese seguir lendo, pero xa non se vota nin se comenta. O que non sae do radar arquívase ás {newttl} horas de enviarse. O que importa é a conversa de agora, non a que se acumula.</p>
<p><b>Non deixa pegada.</b> Sen cookies de rastrexo, sen publicidade programática, sen terceiros. Podes comprobalo abrindo as ferramentas do teu navegador.</p>
<p><b>É horizontal.</b> Todo se publica baixo un alias e o nome de quen escribe só aparece aos {gm} minutos, para que se xulguen as palabras antes ca quen as di.</p>
<p><b>Vese por dentro.</b> A sección Procesos amosa en directo o radar, o δ de cada envío e cada voto, comentario e portada segundo ocorren.</p>
<p><b>Sen algoritmo oculto.</b> O que chega a cada portada decídeo unha fórmula pública, curta e visible baixo cada envío: o <b>δ</b>. Está explicada enteira en <a href="/algoritmo.php">Como funciona o δ</a>.</p>

<h2>O que chega por RSS</h2>
<p><b>{site} tamén le.</b> Ademais do que envía a xente, recolle por RSS o que publican medios, boletíns (de Substack e doutros sitios) e outros agregadores, e amósao co seu propio aspecto, o máis novo primeiro. Sen algoritmo e sen rastrexo: o RSS é un formato aberto que calquera web pode ofrecer.</p>
<p><b>As túas fontes, a man.</b> No teu perfil escolles ata {mediamax} medios e {newsmax} boletíns; se non escolles, verás os destacados. Os agregadores, como Menéame, amósanse tal cal, cos seus propios votos e comentarios. Botas algo en falta? Con {propaura} de aura ou máis podes propoñer un medio ou un boletín, e a moderación revísao.</p>
<p><b>Ler non é votar.</b> Nada do que chega por RSS entra só no radar de {site}. Cada nova ten un botón para enviala ao radar, e dende aí segue o camiño de calquera envío: votos, δ e ciclo.</p>

<h2>A aura</h2>
<p>Non hai karma. Hai <b>aura</b>, e mide unha soa cousa: o teu compromiso coa comunidade. Gáñase estando e achegando, non gustando: +{auraport} por cada envío teu que chega á portada, +{auraenv} por cada envío ou orixinal, +{auracom} por cada comentario, como moito +{auracap} ao día. E baixa: cada día perdes {auradec}, ata o mínimo de {aurastart}. Non se acumula: sostense. Mide quen está, non quen estivo.</p>
<p><b>Nunca dá altofalante.</b> A aura non sobe novas nin ordena comentarios. Todo o mundo ve as mesmas portadas, na mesma orde. Vese como luz arredor do teu alias, nada máis. O único que abre son portas: con máis de {commaura} podes crear comunidades; con máis de {editaura}, corrixir os comentarios que acabas de escribir, e con máis de {seditaura}, os teus envíos.</p>

<h2>Que se pode publicar?</h2>
<p><b>Todo o que permite a lei.</b> O límite non o pon unha liña editorial nin un gusto: pono a lei. Fóra quedan os insultos, os delitos de odio, as ameazas e todo o que sexa delito. Están escritas nas <a href="/normas.php">normas de uso</a>.</p>
<p><b>Modera unha IA que vive nos nosos servidores.</b> Ningún envío nin comentario sae deles para ser revisado: non hai terceiros mirando o que escribes. Cando dubida, decide unha persoa. Calquera pode denunciar un comentario.</p>
<p><b>E hai un correo para todo o demais.</b> Queixas, parabéns e propostas: <b>info@deltha.eu</b>.</p>

<h2>Temas e comunidades</h2>
<p><b>Os temas ponos {site}.</b> Poucos e estables: actualidade, ciencia, cultura… Cada envío leva un, e o tema pode darlle un pequeno empurrón ao δ (un coeficiente público).</p>
<p><b>As comunidades créaas a xente.</b> Con máis de {commaura} de aura podes abrir ata {commmax}: un nome, unha cor e de que vai. Un envío pode sumarse a unha ou a varias, e quen a creou (ou un moderador) cóidaa. Cada comunidade pode escoller un modelo propio, que se aplica á súa páxina e aos seus fíos. E ti podes escoller unha comunidade ou un tema como páxina de entrada.</p>

<h2>Ao teu xeito</h2>
<p><b>{skins} modelos.</b> As mesmas portadas, os mesmos números, distinta pel: de revista, de foro, de terminal, de manga, de fanzine… Escóllese na lapela Modelo, e cada un ten versión clara e escura.</p>
<p><b>No teu idioma e no teu peto.</b> Nove idiomas: español, catalán, éuscaro, galego, inglés, francés, alemán, italiano e portugués. Instálase como app no móbil, con mensaxes privadas e chat entre rexistrados, e podes gardar envíos en favoritos.</p>
<p><b>O que é de todos e o que é teu.</b> As dúas portadas e o radar son os mesmos para todo o mundo, na mesma orde. O que si personalizas é o camiño: que medios e boletíns tes a man, e se ao abrir {site} entras nunha portada (a última que abriches), nun tema ou nunha comunidade. Cambia por onde chegas, non o que decide a comunidade.</p>

<h2>Fóra dos buscadores</h2>
<p><b>O que pasa en {site} queda en {site}.</b> Ningún envío, comentario nin perfil aparece en Google nin noutros buscadores: todas as páxinas levan a marca <i>noindex</i>. Só se deixan ver as páxinas que explican o proxecto, coma esta.</p>
<p><b>Agás que ti o queiras.</b> Se escribes un orixinal, podes marcar a caixa para que os buscadores o indexen. É o teu texto e decides ti; pódese cambiar ao editalo.</p>
<p><b>E ningunha IA se adestra co que escribes.</b> Os rastrexadores de intelixencia artificial (os de OpenAI, Anthropic, Google, Meta, Common Crawl e compañía) teñen a entrada vetada, mesmo nos orixinais abertos aos buscadores.</p>

<h2>Queres ser propietario de {site}?</h2>
<p>{site} é un proxecto aberto a todo o mundo. Para ser copropietario non che pedimos cartos: só que uses a plataforma. <b>O 50 % da propiedade de {site} repartirase entre todos os usuarios rexistrados que teñan aura {auramax} o {auradl}.</b></p>
<p>Queres probala? <a href="/registro.php">Rexístrate</a> ou, se xa tes conta, <a href="/login.php">entra</a>.</p>' WHERE slug = 'que-es.gl';
UPDATE pages SET body = '<h1>Qu''est-ce que Deltha ?</h1>

<p>{site}, c''est quatre choses à la fois : <b>un agrégateur d''actualités</b>, où les gens envoient des liens et décident par leurs votes de ce qui arrive sur ses deux unes, Médias et Substacks ; <b>un lecteur de sources</b>, qui récupère par RSS ce que publient des médias, des newsletters et d''autres agrégateurs et l''affiche, sans algorithme, du plus récent au plus ancien ; <b>un réseau social contre-algorithmique</b>, sans machine qui choisit pour toi ce que tu vois ; et <b>un espace de débat</b>, avec des fils qui s''ouvrent, se discutent et se ferment.</p>

<h2>D''où ça vient</h2>
<p>De cinq sites. Du <a href="https://contralgoritmia.com/" rel="noopener">manifeste contre-algorithmique</a> vient l''idée de fond : retrouver le jugement humain face à l''hégémonie de l''algorithme. De <b>Menéame</b>, l''agrégateur : la communauté décide de ce qui arrive en une. De <b>X</b>, le design : un fil chronologique propre et rapide. De <b>4chan</b>, l''éphémère : les fils naissent, se discutent et se ferment. Et du <b>modèle économique de Jot Down</b>, la façon de tenir debout : ni publicité programmatique ni pistage, une communauté qui fait vivre ce qu''elle lit.</p>

<h2>Comment ça marche</h2>
<p><b>Deux unes et un radar.</b> Tout ce qui est envoyé entre dans le <b>radar</b>. De là, porté par les votes, il part vers l''une des deux unes : <b>Médias</b>, pour les actualités des journaux, des magazines et des autres sites, et <b>Substacks</b>, pour les newsletters. Les <b>originaux</b>, les textes écrits par la communauté elle-même, ont leur propre onglet et ne concourent pas pour la une.</p>
<p><b>C''est éphémère, mais rien n''est effacé.</b> Quand un envoi est en une depuis {ttl} heures, il est archivé avec sa conversation : on peut toujours le lire, mais on ne peut plus voter ni commenter. Ce qui ne sort pas du radar est archivé {newttl} heures après l''envoi. Ce qui compte, c''est la conversation de maintenant, pas celle qui s''accumule.</p>
<p><b>Ça ne laisse pas de traces.</b> Pas de cookies de pistage, pas de publicité programmatique, pas de tiers. Tu peux le vérifier en ouvrant les outils de ton navigateur.</p>
<p><b>C''est horizontal.</b> Tout est publié sous un alias et le nom de l''auteur n''apparaît qu''au bout de {gm} minutes, pour qu''on juge les mots avant la personne qui les dit.</p>
<p><b>On voit l''intérieur.</b> La section Processus montre en direct le radar, le δ de chaque envoi et chaque vote, commentaire et passage en une au moment où ils se produisent.</p>
<p><b>Pas d''algorithme caché.</b> Ce qui arrive sur chaque une, c''est une formule publique, courte et visible sous chaque envoi qui le décide : le <b>δ</b>. Elle est expliquée en entier dans <a href="/algoritmo.php">Comment fonctionne le δ</a>.</p>

<h2>Ce qui arrive par RSS</h2>
<p><b>{site} lit aussi.</b> En plus de ce que les gens envoient, il récupère par RSS ce que publient des médias, des newsletters (sur Substack et ailleurs) et d''autres agrégateurs, et l''affiche avec son propre habillage, le plus récent d''abord. Sans algorithme et sans pistage : le RSS est un format ouvert que n''importe quel site peut proposer.</p>
<p><b>Tes sources, à portée de main.</b> Dans ton profil, tu choisis jusqu''à {mediamax} médias et {newsmax} newsletters ; si tu ne choisis pas, tu verras ceux mis en avant. Les agrégateurs, comme Menéame, s''affichent tels quels, avec leurs propres votes et commentaires. Il te manque quelque chose ? Avec {propaura} d''aura ou plus, tu peux proposer un média ou une newsletter, et la modération l''examine.</p>
<p><b>Lire n''est pas voter.</b> Rien de ce qui arrive par RSS n''entre tout seul dans le radar de {site}. Chaque actualité a un bouton pour l''envoyer dans le radar, et à partir de là elle suit le chemin de n''importe quel envoi : votes, δ et cycle.</p>

<h2>L''aura</h2>
<p>Il n''y a pas de karma. Il y a l''<b>aura</b>, et elle ne mesure qu''une chose : ton engagement envers la communauté. Elle se gagne en étant là et en contribuant, pas en plaisant : +{auraport} pour chaque envoi à toi qui arrive en une, +{auraenv} pour chaque envoi ou original, +{auracom} pour chaque commentaire, au maximum +{auracap} par jour. Et elle baisse : chaque jour tu perds {auradec}, jusqu''au minimum de {aurastart}. Elle ne s''accumule pas : elle s''entretient. Elle mesure qui est là, pas qui a été là.</p>
<p><b>Elle ne donne jamais de porte-voix.</b> L''aura ne fait pas monter d''actualités et ne classe pas les commentaires. Tout le monde voit les mêmes unes, dans le même ordre. Elle se voit comme une lumière autour de ton alias, rien de plus. Tout ce qu''elle ouvre, ce sont des portes : au-delà de {commaura}, tu peux créer des communautés ; au-delà de {editaura}, corriger tes commentaires tout juste écrits ; et au-delà de {seditaura}, tes envois.</p>

<h2>Que peut-on publier ?</h2>
<p><b>Tout ce que la loi permet.</b> La limite n''est fixée ni par une ligne éditoriale ni par un goût : c''est la loi qui la fixe. Restent dehors les insultes, les délits de haine, les menaces et tout ce qui constitue un délit. C''est écrit dans les <a href="/normas.php">règles d''usage</a>.</p>
<p><b>La modération est faite par une IA qui vit sur nos serveurs.</b> Aucun envoi ni commentaire n''en sort pour être examiné : il n''y a pas de tiers qui regardent ce que tu écris. En cas de doute, c''est une personne qui décide. Tout le monde peut signaler un commentaire.</p>
<p><b>Et il y a une adresse pour tout le reste.</b> Plaintes, félicitations et propositions : <b>info@deltha.eu</b>.</p>

<h2>Thèmes et communautés</h2>
<p><b>Les thèmes, c''est {site} qui les fixe.</b> Peu nombreux et stables : actualité, science, culture… Chaque envoi en a un, et le thème peut donner un petit coup de pouce au δ (un coefficient public).</p>
<p><b>Les communautés, ce sont les gens qui les créent.</b> Avec plus de {commaura} d''aura, tu peux en ouvrir jusqu''à {commmax} : un nom, une couleur et un sujet. Un envoi peut rejoindre une ou plusieurs communautés, et celui qui l''a créée (ou un modérateur) en prend soin. Chaque communauté peut choisir sa propre skin, qui s''applique à sa page et à ses fils. Et tu peux choisir une communauté ou un thème comme page d''arrivée.</p>

<h2>À ta façon</h2>
<p><b>{skins} skins.</b> Mêmes unes, mêmes chiffres, autre peau : magazine, forum, terminal, manga, fanzine… Tu la choisis dans l''onglet Skins, et chacune a une version claire et une version sombre.</p>
<p><b>Dans ta langue et dans ta poche.</b> Neuf langues : espagnol, catalan, basque, galicien, anglais, français, allemand, italien et portugais. Ça s''installe comme une appli sur le mobile, avec messages privés et chat entre inscrits, et tu peux enregistrer des envois dans tes favoris.</p>
<p><b>Ce qui est à tout le monde et ce qui est à toi.</b> Les deux unes et le radar sont les mêmes pour tout le monde, dans le même ordre. Ce que tu personnalises, c''est le chemin : les médias et newsletters que tu gardes à portée de main, et si en ouvrant {site} tu arrives sur l''une des unes (la dernière que tu as ouverte), sur un thème ou dans une communauté. Ça change par où tu arrives, pas ce que décide la communauté.</p>

<h2>Hors des moteurs de recherche</h2>
<p><b>Ce qui se passe sur {site} reste sur {site}.</b> Aucun envoi, commentaire ni profil n''apparaît dans Google ni dans les autres moteurs de recherche : toutes les pages portent la balise <i>noindex</i>. Seules les pages qui expliquent le projet, comme celle-ci, se laissent voir.</p>
<p><b>Sauf si tu le veux.</b> Si tu écris un original, tu peux cocher la case pour que les moteurs de recherche l''indexent. C''est ton texte, c''est toi qui décides ; tu peux changer d''avis en le modifiant.</p>
<p><b>Et aucune IA ne s''entraîne sur ce que tu écris.</b> Les robots d''intelligence artificielle (ceux d''OpenAI, Anthropic, Google, Meta, Common Crawl et compagnie) ont l''entrée interdite, même sur les originaux ouverts aux moteurs de recherche.</p>

<h2>Tu veux devenir propriétaire de {site} ?</h2>
<p>{site} est un projet ouvert à tout le monde. Pour en être copropriétaire, on ne te demande pas d''argent : seulement d''utiliser la plateforme. <b>50 % de la propriété de {site} sera réparti entre tous les utilisateurs inscrits qui auront une aura de {auramax} le {auradl}.</b></p>
<p>Tu veux l''essayer ? <a href="/registro.php">Inscris-toi</a> ou, si tu as déjà un compte, <a href="/login.php">connecte-toi</a>.</p>' WHERE slug = 'que-es.fr';
UPDATE pages SET body = '<h1>Was ist Deltha?</h1>

<p>{site} ist vier Dinge zugleich: <b>ein Nachrichten-Aggregator</b>, in dem Leute Links einreichen und mit ihren Stimmen entscheiden, was auf seine zwei Titelseiten kommt, Medien und Substacks; <b>ein Quellen-Reader</b>, der per RSS sammelt, was Medien, Newsletter und andere Aggregatoren veröffentlichen, und es ohne Algorithmus zeigt, das Neueste zuerst; <b>ein gegenalgorithmisches soziales Netzwerk</b>, ohne Maschine, die für dich auswählt, was du siehst; und <b>ein Ort für Debatten</b>, mit Threads, die eröffnet, diskutiert und wieder geschlossen werden.</p>

<h2>Woher es kommt</h2>
<p>Von fünf Orten. Aus dem <a href="https://contralgoritmia.com/" rel="noopener">gegenalgorithmischen Manifest</a> stammt die Grundidee: das menschliche Urteil gegen die Vorherrschaft des Algorithmus zurückzugewinnen. Von <b>Menéame</b> der Aggregator: Die Community entscheidet, was auf die Titelseite kommt. Von <b>X</b> das Design: eine klare, schnelle Timeline. Von <b>4chan</b> die Vergänglichkeit: Threads entstehen, werden diskutiert und geschlossen. Und vom <b>Geschäftsmodell von Jot Down</b> die Art, sich zu tragen: ohne programmatische Werbung und ohne Tracking – eine Community, die trägt, was sie liest.</p>

<h2>So funktioniert es</h2>
<p><b>Zwei Titelseiten und ein Radar.</b> Alles, was eingereicht wird, kommt ins <b>Radar</b>. Von dort geht es, per Abstimmung, auf eine der beiden Titelseiten: <b>Medien</b> für Nachrichten aus Zeitungen, Zeitschriften und anderen Websites und <b>Substacks</b> für Newsletter. Die <b>Originale</b>, die Texte, die die Community selbst schreibt, haben ihren eigenen Reiter und konkurrieren nicht um eine Titelseite.</p>
<p><b>Es ist vergänglich, aber es löscht nichts.</b> Steht ein Beitrag {ttl} Stunden auf der Titelseite, wird er mit seiner Diskussion archiviert: Man kann ihn weiter lesen, aber nicht mehr abstimmen oder kommentieren. Was das Radar nicht verlässt, wird {newttl} Stunden nach dem Einreichen archiviert. Wichtig ist das Gespräch von jetzt, nicht das, was sich ansammelt.</p>
<p><b>Es hinterlässt keine Spuren.</b> Keine Tracking-Cookies, keine programmatische Werbung, keine Dritten. Du kannst es selbst prüfen, mit den Werkzeugen deines Browsers.</p>
<p><b>Es ist horizontal.</b> Alles erscheint zuerst unter einem Alias, der Name des Autors erst nach {gm} Minuten. So werden die Worte beurteilt, bevor es um die Person geht.</p>
<p><b>Man sieht hinein.</b> Der Bereich Abläufe zeigt live das Radar, das δ jedes Beitrags und jede Stimme, jeden Kommentar und jede Titelseite, sobald sie passieren.</p>
<p><b>Kein verborgener Algorithmus.</b> Was auf die Titelseiten kommt, entscheidet eine öffentliche, kurze Formel, sichtbar unter jedem Beitrag: das <b>δ</b>. Sie wird vollständig erklärt unter <a href="/algoritmo.php">So funktioniert das δ</a>.</p>

<h2>Was per RSS hereinkommt</h2>
<p><b>{site} liest auch.</b> Neben dem, was Leute einreichen, sammelt es per RSS, was Medien, Newsletter (auf Substack und anderswo) und andere Aggregatoren veröffentlichen, und zeigt es im eigenen Design, das Neueste zuerst. Ohne Algorithmus und ohne Tracking: RSS ist ein offenes Format, das jede Website anbieten kann.</p>
<p><b>Deine Quellen, griffbereit.</b> In deinem Profil wählst du bis zu {mediamax} Medien und {newsmax} Newsletter; wählst du keine, siehst du die hervorgehobenen. Aggregatoren wie Menéame werden so gezeigt, wie sie sind, mit ihren eigenen Stimmen und Kommentaren. Fehlt dir etwas? Mit {propaura} Aura oder mehr kannst du ein Medium oder einen Newsletter vorschlagen, und die Moderation prüft den Vorschlag.</p>
<p><b>Lesen ist nicht abstimmen.</b> Nichts, was per RSS hereinkommt, landet von selbst im Radar von {site}. Jede Nachricht hat einen Knopf, um sie ins Radar zu schicken, und von dort geht sie denselben Weg wie jeder Beitrag: Stimmen, δ und Zyklus.</p>

<h2>Die Aura</h2>
<p>Es gibt kein Karma. Es gibt <b>Aura</b>, und sie misst nur eines: dein Engagement für die Community. Man gewinnt sie, indem man da ist und etwas beiträgt, nicht indem man gefällt: +{auraport} für jeden deiner Beiträge, der auf die Titelseite kommt, +{auraenv} für jeden Beitrag oder jedes Original, +{auracom} für jeden Kommentar, höchstens +{auracap} pro Tag. Und sie sinkt: Jeden Tag verlierst du {auradec}, bis zum Minimum von {aurastart}. Sie sammelt sich nicht an, sie will gehalten werden. Sie misst, wer da ist, nicht wer da war.</p>
<p><b>Sie macht niemanden lauter.</b> Die Aura hebt keine Nachrichten hoch und sortiert keine Kommentare. Alle sehen dieselben Titelseiten, in derselben Reihenfolge. Sie erscheint als Licht um deinen Alias, sonst nichts. Sie öffnet nur Türen: Mit mehr als {commaura} kannst du Communities gründen, mit mehr als {editaura} deine frisch geschriebenen Kommentare korrigieren und mit mehr als {seditaura} deine Beiträge.</p>

<h2>Was darf man veröffentlichen?</h2>
<p><b>Alles, was das Gesetz erlaubt.</b> Die Grenze setzt keine redaktionelle Linie und kein Geschmack, sondern das Gesetz. Draußen bleiben Beleidigungen, Hassdelikte, Drohungen und alles, was eine Straftat ist. Das steht in den <a href="/normas.php">Nutzungsregeln</a>.</p>
<p><b>Moderiert wird von einer KI, die auf unseren Servern lebt.</b> Kein Beitrag und kein Kommentar verlässt sie zur Prüfung: Keine Dritten schauen mit, was du schreibst. Im Zweifel entscheidet ein Mensch. Jeder kann einen Kommentar melden.</p>
<p><b>Und für alles andere gibt es eine E-Mail-Adresse.</b> Beschwerden, Lob und Vorschläge: <b>info@deltha.eu</b>.</p>

<h2>Themen und Communities</h2>
<p><b>Die Themen legt {site} fest.</b> Wenige und beständige: Aktuelles, Wissenschaft, Kultur … Jeder Beitrag hat eines, und das Thema kann dem δ einen kleinen Schub geben (ein öffentlicher Koeffizient).</p>
<p><b>Die Communities gründen die Leute.</b> Mit mehr als {commaura} Aura kannst du bis zu {commmax} eröffnen: ein Name, eine Farbe und worum es geht. Ein Beitrag kann einer oder mehreren beitreten, und wer sie gegründet hat (oder ein Moderator) kümmert sich um sie. Jede Community kann ein eigenes Design wählen, das für ihre Seite und ihre Threads gilt. Und du kannst eine Community oder ein Thema als Startseite wählen.</p>

<h2>Wie du willst</h2>
<p><b>{skins} Designs.</b> Dieselben Titelseiten, dieselben Zahlen, eine andere Haut: Magazin, Forum, Terminal, Manga, Fanzine … Du wählst sie im Reiter Design, und jedes gibt es in Hell und Dunkel.</p>
<p><b>In deiner Sprache und in deiner Tasche.</b> Neun Sprachen: Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch, Italienisch und Portugiesisch. Es lässt sich als App aufs Handy installieren, mit privaten Nachrichten und Chat zwischen registrierten Nutzern, und du kannst Beiträge als Favoriten speichern.</p>
<p><b>Was allen gehört und was dir gehört.</b> Die beiden Titelseiten und das Radar sind für alle gleich, in derselben Reihenfolge. Was du selbst einstellst, ist der Weg dorthin: welche Medien und Newsletter du griffbereit hast und ob du beim Öffnen von {site} auf einer Titelseite (der zuletzt geöffneten), in einem Thema oder in einer Community landest. Das ändert, wo du ankommst, nicht, was die Community entscheidet.</p>

<h2>Raus aus den Suchmaschinen</h2>
<p><b>Was auf {site} passiert, bleibt auf {site}.</b> Kein Beitrag, kein Kommentar und kein Profil taucht bei Google oder anderen Suchmaschinen auf: Alle Seiten tragen die Markierung <i>noindex</i>. Sichtbar sind nur die Seiten, die das Projekt erklären, wie diese hier.</p>
<p><b>Es sei denn, du willst es.</b> Wenn du ein Original schreibst, kannst du ein Häkchen setzen, damit Suchmaschinen ihn indexieren. Es ist dein Text, und du entscheidest; beim Bearbeiten lässt es sich ändern.</p>
<p><b>Und keine KI wird mit dem trainiert, was du schreibst.</b> Die Crawler der künstlichen Intelligenz (von OpenAI, Anthropic, Google, Meta, Common Crawl und Co.) haben keinen Zutritt, auch nicht bei Originalen, die für Suchmaschinen freigegeben sind.</p>

<h2>Willst du Miteigentümer von {site} werden?</h2>
<p>{site} ist ein Projekt, das allen offensteht. Um Miteigentümer zu werden, verlangen wir kein Geld: nur, dass du die Plattform nutzt. <b>50 % des Eigentums an {site} werden unter allen registrierten Nutzern aufgeteilt, die am {auradl} eine Aura von {auramax} haben.</b></p>
<p>Willst du es ausprobieren? <a href="/registro.php">Registriere dich</a> oder, wenn du schon ein Konto hast, <a href="/login.php">melde dich an</a>.</p>' WHERE slug = 'que-es.de';
UPDATE pages SET body = '<h1>Cos''è Deltha?</h1>

<p>{site} è quattro cose insieme: <b>un aggregatore di notizie</b>, dove la gente invia link e decide con i propri voti cosa arriva nelle sue due prime pagine, Media e Substack; <b>un lettore di fonti</b>, che raccoglie via RSS ciò che pubblicano testate, newsletter e altri aggregatori e lo mostra, senza algoritmo, dal più recente al più vecchio; <b>un social network controalgoritmico</b>, senza una macchina che scelga per te cosa vedere; e <b>uno spazio di dibattito</b>, con discussioni che si aprono, si accendono e si chiudono.</p>

<h2>Da dove viene</h2>
<p>Da cinque posti. Dal <a href="https://contralgoritmia.com/" rel="noopener">manifesto controalgoritmico</a> viene l''idea di fondo: recuperare il giudizio umano contro l''egemonia dell''algoritmo. Da <b>Menéame</b>, l''aggregatore: è la comunità a decidere cosa arriva in prima pagina. Da <b>X</b>, il design: una timeline pulita e veloce. Da <b>4chan</b>, l''effimero: le discussioni nascono, si accendono e si chiudono. E dal <b>modello di business di Jot Down</b>, il modo di reggersi: niente pubblicità programmatica né tracciamento, una comunità che sostiene ciò che legge.</p>

<h2>Come funziona</h2>
<p><b>Due prime pagine e un radar.</b> Tutto ciò che viene inviato entra nel <b>radar</b>. Da lì esce, votato, verso una delle due prime pagine: <b>Media</b>, per le notizie di giornali, riviste e altri siti, e <b>Substack</b>, per le newsletter. Gli <b>originali</b>, i testi scritti dalla comunità stessa, hanno la loro scheda e non competono per la prima pagina.</p>
<p><b>È effimera, ma non cancella.</b> Quando un invio è in prima pagina da {ttl} ore, viene archiviato con la sua discussione: si può continuare a leggerlo, ma non si vota né si commenta più. Ciò che non esce dal radar viene archiviato {newttl} ore dopo l''invio. Conta la conversazione di adesso, non quella che si accumula.</p>
<p><b>Non lascia tracce.</b> Niente cookie di tracciamento, niente pubblicità programmatica, niente terze parti. Puoi verificarlo aprendo gli strumenti del tuo browser.</p>
<p><b>È orizzontale.</b> Tutto si pubblica sotto un alias e il nome dell''autore compare solo dopo {gm} minuti, così si giudicano le parole prima di chi le dice.</p>
<p><b>Si vede dentro.</b> La sezione Processi mostra in diretta il radar, il δ di ogni invio e ogni voto, commento e prima pagina man mano che accadono.</p>
<p><b>Niente algoritmo nascosto.</b> Cosa arriva in ciascuna prima pagina lo decide una formula pubblica, breve e visibile sotto ogni invio: il <b>δ</b>. È spiegata per intero in <a href="/algoritmo.php">Come funziona il δ</a>.</p>

<h2>Quello che arriva via RSS</h2>
<p><b>{site} legge anche.</b> Oltre a ciò che invia la gente, raccoglie via RSS ciò che pubblicano testate, newsletter (su Substack e altrove) e altri aggregatori, e lo mostra con il suo aspetto, dal più recente. Senza algoritmo e senza tracciamento: l''RSS è un formato aperto che qualsiasi sito può offrire.</p>
<p><b>Le tue fonti, a portata di mano.</b> Nel tuo profilo scegli fino a {mediamax} testate e {newsmax} newsletter; se non scegli, vedrai quelle in evidenza. Gli aggregatori, come Menéame, si vedono così come sono, con i loro voti e commenti. Ti manca qualcosa? Con {propaura} di aura o più puoi proporre una testata o una newsletter, e la moderazione la esamina.</p>
<p><b>Leggere non è votare.</b> Niente di ciò che arriva via RSS entra da solo nel radar di {site}. Ogni notizia ha un pulsante per inviarla al radar, e da lì segue il percorso di qualsiasi invio: voti, δ e ciclo.</p>

<h2>L''aura</h2>
<p>Non c''è karma. C''è l''<b>aura</b>, e misura una cosa sola: il tuo impegno verso la comunità. Si guadagna partecipando e contribuendo, non piacendo: +{auraport} per ogni tuo invio che arriva in prima pagina, +{auraenv} per ogni invio o originale, +{auracom} per ogni commento, al massimo +{auracap} al giorno. E scende: ogni giorno perdi {auradec}, fino al minimo di {aurastart}. Non si accumula: si mantiene. Misura chi c''è, non chi c''era.</p>
<p><b>Non dà mai un megafono.</b> L''aura non fa salire notizie né ordina commenti. Tutti vedono le stesse prime pagine, nello stesso ordine. Si vede come una luce intorno al tuo alias, nient''altro. Apre soltanto delle porte: con più di {commaura} puoi creare comunità; con più di {editaura}, correggere i commenti appena scritti; e con più di {seditaura}, i tuoi invii.</p>

<h2>Cosa si può pubblicare?</h2>
<p><b>Tutto ciò che la legge consente.</b> Il limite non lo fissa una linea editoriale né un gusto: lo fissa la legge. Restano fuori gli insulti, i crimini d''odio, le minacce e tutto ciò che è reato. È tutto scritto nelle <a href="/normas.php">regole d''uso</a>.</p>
<p><b>Modera un''IA che vive sui nostri server.</b> Nessun invio né commento ne esce per essere revisionato: non ci sono terze parti a guardare cosa scrivi. Quando ha dubbi, decide una persona. Chiunque può segnalare un commento.</p>
<p><b>E c''è un indirizzo email per tutto il resto.</b> Lamentele, complimenti e proposte: <b>info@deltha.eu</b>.</p>

<h2>Temi e comunità</h2>
<p><b>I temi li stabilisce {site}.</b> Pochi e stabili: attualità, scienza, cultura… Ogni invio ne ha uno, e il tema può dare una piccola spinta al δ (un coefficiente pubblico).</p>
<p><b>Le comunità le crea la gente.</b> Con più di {commaura} di aura puoi aprirne fino a {commmax}: un nome, un colore e di cosa parla. Un invio può aggiungersi a una o più comunità, e chi l''ha creata (o un moderatore) se ne prende cura. Ogni comunità può scegliere uno stile proprio, che si applica alla sua pagina e alle sue discussioni. E tu puoi scegliere una comunità o un tema come pagina di ingresso.</p>

<h2>A modo tuo</h2>
<p><b>{skins} stili.</b> Stesse prime pagine, stessi numeri, pelle diversa: da rivista, da forum, da terminale, da manga, da fanzine… Si sceglie nella scheda Stile, e ognuno ha la versione chiara e scura.</p>
<p><b>Nella tua lingua e in tasca.</b> Nove lingue: spagnolo, catalano, basco, galiziano, inglese, francese, tedesco, italiano e portoghese. Si installa come app sul telefono, con messaggi privati e chat tra utenti registrati, e puoi salvare gli invii nei preferiti.</p>
<p><b>Ciò che è di tutti e ciò che è tuo.</b> Le due prime pagine e il radar sono gli stessi per tutti, nello stesso ordine. Quello che personalizzi è il percorso: quali testate e newsletter tieni a portata di mano, e se aprendo {site} entri in una prima pagina (l''ultima che hai aperto), in un tema o in una comunità. Cambia da dove arrivi, non cosa decide la comunità.</p>

<h2>Fuori dai motori di ricerca</h2>
<p><b>Quello che succede su {site} resta su {site}.</b> Nessun invio, commento o profilo compare su Google o su altri motori di ricerca: tutte le pagine portano il marcatore <i>noindex</i>. Si lasciano vedere solo le pagine che spiegano il progetto, come questa.</p>
<p><b>A meno che non lo voglia tu.</b> Se scrivi un originale, puoi spuntare la casella perché i motori di ricerca lo indicizzino. È il tuo testo e decidi tu; puoi cambiarlo quando lo modifichi.</p>
<p><b>E nessuna IA si addestra con quello che scrivi.</b> I crawler di intelligenza artificiale (quelli di OpenAI, Anthropic, Google, Meta, Common Crawl e compagnia) hanno l''ingresso vietato, anche negli originali aperti ai motori di ricerca.</p>

<h2>Vuoi essere proprietario di {site}?</h2>
<p>{site} è un progetto aperto a tutti. Per diventarne comproprietario non ti chiediamo soldi: solo di usare la piattaforma. <b>Il 50 % della proprietà di {site} sarà ripartito tra tutti gli utenti registrati che avranno aura {auramax} il {auradl}.</b></p>
<p>Vuoi provarla? <a href="/registro.php">Registrati</a> o, se hai già un account, <a href="/login.php">accedi</a>.</p>' WHERE slug = 'que-es.it';
UPDATE pages SET body = '<h1>O que é Deltha?</h1>

<p>{site} é quatro coisas ao mesmo tempo: <b>um agregador de notícias</b>, onde as pessoas enviam links e decidem com os seus votos o que chega às suas duas capas, Meios e Newsletters; <b>um leitor de fontes</b>, que recolhe por RSS o que publicam meios, newsletters e outros agregadores e o mostra, sem algoritmo, do mais recente ao mais antigo; <b>uma rede social contralgorítmica</b>, sem máquina que escolha em seu lugar o que ver; e <b>um espaço para o debate</b>, com conversas que se abrem, se discutem e se fecham.</p>

<h2>De onde vem</h2>
<p>De cinco lugares. Do <a href="https://contralgoritmia.com/" rel="noopener">manifesto contralgorítmico</a> vem a ideia de fundo: recuperar o critério humano frente à hegemonia do algoritmo. Do <b>Menéame</b>, o agregador: a comunidade decide o que chega à capa. Do <b>X</b>, o design: uma linha do tempo limpa e rápida. Do <b>4chan</b>, a efemeridade: as conversas nascem, são discutidas e fecham. E do <b>modelo de negócio da Jot Down</b>, a forma de se sustentar: sem publicidade programática nem rastreamento, uma comunidade que sustenta o que lê.</p>

<h2>Como funciona</h2>
<p><b>Duas capas e um radar.</b> Tudo o que se envia entra no <b>radar</b>. Daí sai, votado, rumo a uma das duas capas: <b>Meios</b>, para as notícias de jornais, revistas e outros sites, e <b>Newsletters</b>, para as newsletters do Substack e afins. Os <b>originais</b>, os textos escritos pela própria comunidade, têm o seu separador e não competem pela capa.</p>
<p><b>É passageira, mas não apaga.</b> Quando um envio completa {ttl} horas na capa, é arquivado com a sua conversa: ainda pode ser lido, mas já não recebe votos nem comentários. O que não sai do radar é arquivado {newttl} horas depois do envio. O que importa é a conversa de agora, não a que se acumula.</p>
<p><b>Não deixa marcas.</b> Sem cookies de rastreamento, sem publicidade programática, sem terceiros. Para comprovar, basta abrir as ferramentas do navegador.</p>
<p><b>É horizontal.</b> Tudo é publicado sob um alias e o nome do autor só aparece passados {gm} minutos, para que as palavras sejam julgadas antes de quem as diz.</p>
<p><b>Tudo à vista.</b> Em Processos, é possível ver em tempo real o radar, o δ de cada envio e cada voto, comentário e publicação na capa à medida que acontecem.</p>
<p><b>Sem algoritmo oculto.</b> O que chega a cada capa é decidido por uma fórmula pública, curta e visível sob cada envio: o <b>δ</b>. Está explicada por inteiro em <a href="/algoritmo.php">Como funciona o δ</a>.</p>

<h2>O que chega por RSS</h2>
<p><b>{site} também lê.</b> Além do que as pessoas enviam, recolhe por RSS o que publicam meios, newsletters (do Substack e de outros sites) e outros agregadores, e mostra tudo com a sua própria aparência, o mais recente primeiro. Sem algoritmo e sem rastreamento: o RSS é um formato aberto que qualquer site pode oferecer.</p>
<p><b>As suas fontes, à mão.</b> No perfil, escolha até {mediamax} meios e {newsmax} newsletters; sem escolha, aparecem os destaques. Os agregadores, como o Menéame, são mostrados tal como são, com os seus próprios votos e comentários. Falta alguma coisa? Com {propaura} de aura ou mais, é possível propor um meio ou uma newsletter, e a moderação analisa a proposta.</p>
<p><b>Ler não é votar.</b> Nada do que chega por RSS entra sozinho no radar de {site}. Cada notícia tem um botão que a envia para o radar, e a partir daí segue o caminho de qualquer envio: votos, δ e ciclo.</p>

<h2>A aura</h2>
<p>Não há karma. Há <b>aura</b>, e mede uma única coisa: o compromisso com a comunidade. Ganha-se com presença e contribuição, não com popularidade: +{auraport} por cada envio próprio que chega à capa, +{auraenv} por cada envio ou original, +{auracom} por cada comentário, no máximo +{auracap} por dia. E diminui: menos {auradec} por dia, nunca abaixo de {aurastart}. Não se acumula: mantém-se. Mede quem está, não quem esteve.</p>
<p><b>Nunca dá megafone.</b> A aura não sobe notícias nem ordena comentários. Todas as pessoas veem as mesmas capas, na mesma ordem. Aparece como uma luz em volta do alias, e nada mais. A única coisa que abre são portas: com mais de {commaura}, é possível criar comunidades; com mais de {editaura}, corrigir os próprios comentários recém-escritos, e com mais de {seditaura}, os próprios envios.</p>

<h2>O que se pode publicar?</h2>
<p><b>Tudo o que a lei permite.</b> O limite não é posto por uma linha editorial nem por um gosto: é posto pela lei. Ficam de fora os insultos, os crimes de ódio, as ameaças e tudo o que seja crime. Está tudo nas <a href="/normas.php">normas de uso</a>.</p>
<p><b>Quem modera é uma IA que vive nos nossos servidores.</b> Nenhum envio ou comentário sai deles para ser analisado: ninguém de fora lê o que é escrito aqui. Na dúvida, decide uma pessoa. Qualquer pessoa pode denunciar um comentário.</p>
<p><b>E há um e-mail para todo o resto.</b> Queixas, elogios e propostas: <b>info@deltha.eu</b>.</p>

<h2>Temas e comunidades</h2>
<p><b>Os temas são definidos por {site}.</b> Poucos e estáveis: atualidade, ciência, cultura… Cada envio leva um, e o tema pode dar um pequeno empurrão ao δ (um coeficiente público).</p>
<p><b>As comunidades são criadas pelas pessoas.</b> Com mais de {commaura} de aura, é possível abrir até {commmax}: um nome, uma cor e o assunto. Um envio pode pertencer a uma ou a várias, e quem a criou (ou um moderador) cuida dela. Cada comunidade pode escolher um modelo próprio, que se aplica à sua página e às suas conversas. E é possível escolher uma comunidade ou um tema como página de entrada.</p>

<h2>À sua maneira</h2>
<p><b>{skins} modelos.</b> As mesmas capas, os mesmos números, outra pele: de revista, de fórum, de terminal, de manga, de fanzine… Escolha na página Modelos; cada um tem versão clara e escura.</p>
<p><b>No seu idioma e no bolso.</b> Nove idiomas: espanhol, catalão, basco, galego, inglês, francês, alemão, italiano e português. Pode ser instalado como app no telefone, com mensagens privadas e chat entre membros, e os envios podem ser guardados nos favoritos.</p>
<p><b>O que é de todos e o que é seu.</b> As duas capas e o radar são os mesmos para todas as pessoas, na mesma ordem. O que é personalizável é o caminho: que meios e newsletters ficam à mão e se, ao abrir {site}, a entrada é por uma capa (a última que abriu), por um tema ou por uma comunidade. Muda o caminho de chegada, não o que a comunidade decide.</p>

<h2>Fora dos motores de busca</h2>
<p><b>O que acontece em {site} fica em {site}.</b> Nenhum envio, comentário ou perfil aparece no Google nem em outros motores de busca: todas as páginas levam a marca <i>noindex</i>. Só ficam visíveis as páginas que explicam o projeto, como esta.</p>
<p><b>A não ser por vontade própria.</b> Quem escreve um original pode marcar a caixa para que os motores de busca o indexem. O texto é seu e a decisão também; pode ser alterada ao editar o original.</p>
<p><b>E nenhuma IA é treinada com o que se escreve aqui.</b> Os rastreadores de inteligência artificial (os da OpenAI, Anthropic, Google, Meta, Common Crawl e companhia) têm a entrada vedada, mesmo nos originais abertos aos motores de busca.</p>

<h2>Quer ser proprietário de {site}?</h2>
<p>{site} é um projeto aberto a todos. Para ser coproprietário, não pedimos dinheiro: apenas que use a plataforma. <b>50 % da propriedade de {site} será repartida entre todos os membros que tenham aura {auramax} em {auradl}.</b></p>
<p>Quer experimentar? <a href="/registro.php">Crie uma conta</a> ou, se já tiver conta, <a href="/login.php">entre</a>.</p>' WHERE slug = 'que-es.pt';
UPDATE pages SET body = '<h1>Cómo funciona el δ</h1>

<h2>Sin algoritmo oculto</h2>
<p>En {site} no hay una máquina que decida por ti qué ver. Lo que llega a portada lo decide una fórmula pública, corta y visible: se muestra bajo cada envío del radar, con sus números y su valor en ese momento. Esta página la explica entera. Si la entiendes, entiendes {site}.</p>

<h2>El δ (delta)</h2>
<p>Cada envío tiene un <b>δ</b>: una puntuación que resume cuánto lo apoya la comunidad y cuánto tiempo lleva esperando. Se calcula así:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votos positivos − votos negativos) × coeficiente del tema − {decay} × medias horas desde el envío</p>
<p><b>Los votos</b> son la voz de la gente: cada registrado puede votar una vez cada envío, a favor o en contra, y cambiar de opinión. No se puede votar lo propio.</p>
<p><b>El coeficiente del tema</b> es un pequeño empujón editorial y es el único parámetro que pone {site}: la mayoría de los temas valen 1,00; cultura, ciencia o relatos valen 1,10, es decir, un 10 % más por voto. Los coeficientes son públicos y los verás en cada fórmula.</p>
<p><b>La antigüedad</b> resta: {decay} puntos por cada media hora que pasa desde que se envió. Es lo que hace que {site} sea de hoy y no de siempre: una noticia con dos votos que lleva un día en el radar vale menos que una recién llegada con los mismos votos. Nada dura para siempre en el radar.</p>

<h2>Dos portadas y un radar</h2>
<p>Todo lo que se envía entra en el <b>radar</b>. Desde ahí puede llegar a una de las dos portadas: <b>Medios</b>, para las noticias de periódicos, revistas y demás webs, y <b>Substacks</b>, para las newsletters. Un envío va a Substacks si enlaza a una dirección de substack.com o a una newsletter del catálogo de {site}; todo lo demás va a Medios. Cada envío compite solo con los de su portada.</p>
<p>Los <b>originales</b>, los textos que escribe la propia comunidad, no pasan por el radar ni van a portada: tienen su pestaña, ordenada por fecha, donde se leen, se votan y se comentan.</p>

<h2>El ciclo</h2>
<p>Cada <b>{cycle} minutos</b> ocurre un ciclo, siempre igual, sin excepciones ni intervención manual:</p>
<p><b>1. Del radar a la portada.</b> Los ciclos se turnan: uno publica en Medios, el siguiente en Substacks, y así sucesivamente. En cada ciclo, entre los envíos de la portada a la que le toca que llevan al menos <b>{minage} minutos</b> en el radar, se publica <b>uno solo</b>: el de mayor δ, siempre que su δ sea al menos <b>{minpub}</b>. Sin votos no se llega, y si ninguno llega, ese ciclo no se publica nada: la portada no se rellena por rellenar. Así, cada portada recibe como mucho un envío cada dos ciclos. El tiempo mínimo existe para que un envío no suba solo porque llegó en un momento tranquilo; tiene que ser visto y votado antes.</p>
<p><b>2. Caducidad.</b> Lo que pasa <b>{newttl} horas</b> en el radar sin llegar a portada se archiva: el radar tampoco es para siempre.</p>
<p><b>3. El velo se levanta.</b> A los {gm} minutos de publicarse cualquier envío o comentario, el alias de su autor deja paso a su nombre. Antes, todos son alias con un halo: se juzgan las palabras, no la firma.</p>
<p><b>4. Archivo.</b> Lo que lleva {ttl} horas en portada se archiva. Se puede seguir leyendo, pero ya no se vota ni se comenta: la conversación fue lo que fue.</p>
<p>La sección <b>Procesos</b> muestra todo esto en directo: el radar ordenado por δ, con la portada a la que va cada envío y una estimación de cuándo llega, y un flujo con cada envío, voto, comentario y portada según ocurren.</p>

<h2>Temas y comunidades</h2>
<p>Cada envío se clasifica dos veces. <b>El tema</b> lo pone {site}: son pocos, estables (actualidad, ciencia, cultura…) y cada uno tiene su coeficiente público. Todo envío lleva un tema.</p>
<p><b>La comunidad</b> la pone la gente. Cualquiera con más de <b>{commaura} de aura</b> puede crear hasta <b>{commmax}</b>: un nombre, un color y una frase que diga de qué va. Un envío puede sumarse a una comunidad además de a su tema, y así se agrupa lo que a un grupo le interesa sin que {site} tenga que decidirlo. Quien crea una comunidad la cuida: edita su nombre, su descripción, su color y su plantilla visual; los moderadores pueden hacer lo mismo y, si hace falta, ocultarla.</p>
<p>Las comunidades <b>no tocan el δ</b>: no dan coeficiente, no suben ni bajan nada. Son un lugar, no un altavoz. Lo que sí puedes hacer es elegir una comunidad (o un tema) como <b>página de entrada</b> en tu perfil: al abrir {site} llegarás allí, y las pestañas Medios y Substacks seguirán siendo las portadas de todos.</p>

<h2>Lo que llega por RSS</h2>
<p>{site} también recoge por RSS lo que publican medios, newsletters y otros agregadores. Ahí <b>no hay δ ni ciclo</b>: cada fuente se muestra por orden de publicación, lo último primero, sin ordenar por gustos ni por éxito. En los agregadores se ven sus propios votos y comentarios, que son los de su comunidad, no los de {site}.</p>
<p>Lo que llega por RSS <b>no entra solo en el radar</b>. Si alguien lo envía al radar con el botón de cada noticia, a partir de ahí es un envío más: votos, δ y ciclo, como todos.</p>

<h2>Lo que el δ no hace</h2>
<p>No aprende de ti. No sabe qué te gusta. No te enseña una portada distinta que a tu vecino. No premia a quien tiene más seguidores, porque aquí nadie tiene seguidores. No mira la hora a la que sueles entrar ni cuánto tiempo pasas leyendo. La misma fórmula, los mismos números, las mismas portadas para todos.</p>

<h2>Lo que sí eliges tú</h2>
<p>Las dos portadas y el radar son <b>los mismos para todos</b>, en el mismo orden. Lo que sí cambia de una persona a otra es <b>cómo se ven</b> y <b>por dónde entras</b>. Para cómo se ven, en tu perfil eliges la <b>plantilla visual</b> ({skins}, de revista a fanzine, cada una con versión clara y oscura), el <b>tema claro u oscuro</b> y el <b>idioma</b> (español, catalán, euskera, gallego, inglés, francés, alemán, italiano o portugués); una comunidad puede tener su propia plantilla, que verás en su página y en sus hilos.</p>
<p>Para por dónde entras, eliges tu <b>página de entrada</b> (una portada, un tema o una comunidad; si eliges portada, se abre la última que viste) y <b>tus fuentes</b>: hasta {mediamax} medios y {newsmax} newsletters siempre a mano. Eso personaliza el acceso, no el juicio: nada de lo que eliges altera la portada, el orden de nada ni el δ de ningún envío. {site} se instala además como aplicación en el móvil y tiene mensajes privados entre registrados.</p>

<h2>El aura</h2>
<p>Las personas no tienen δ; tienen <b>aura</b>, que es otra cosa y mide una sola: el compromiso con la comunidad. Se gana estando y aportando (+{auraport} por cada envío tuyo que llega a portada, +{auraenv} por cada envío u original, +{auracom} por cada comentario, con un tope de +{auracap} al día) y se pierde no estando ({auradec} cada día, nunca por debajo de {aurastart}). El máximo es {auramax}.</p>
<p>El aura <b>nunca da altavoz</b>: no sube noticias, no ordena comentarios, no pesa en el δ. Se ve como luz alrededor de tu alias, y abre algunas puertas de confianza: con más de {commaura} de aura puedes crear comunidades (hasta {commmax}); con más de {editaura}, corregir tus propios comentarios durante {editmin} minutos tras publicarlos, y con más de {seditaura}, tus envíos durante {seditmin} minutos mientras no lleguen a portada (lo corregido queda marcado como editado). Con {propaura} o más puedes proponer medios y newsletters.</p>

<h2>Los números de hoy</h2>
<p>Todos los parámetros de esta página son los que están en vigor ahora mismo en {site}; si el equipo los cambia, esta página cambia con ellos. Ciclo cada {cycle} minutos, alternando Medios y Substacks · {minage} minutos mínimos en el radar · δ mínimo para portada {minpub} · decaimiento {decay} por media hora · velo de {gm} minutos · vida en portada de {ttl} horas · caducidad en el radar a las {newttl} horas · comunidades con aura mayor que {commaura}, hasta {commmax} por persona.</p>' WHERE slug = 'algoritmo';
UPDATE pages SET body = '<h1>How δ works</h1>

<h2>No hidden algorithm</h2>
<p>On {site} there''s no machine deciding what you see. What makes the front page is decided by a public formula, short and visible: it''s shown under every post in the radar, with its numbers and its value at that moment. This page explains it in full. If you understand it, you understand {site}.</p>

<h2>The δ (delta)</h2>
<p>Every post has a <b>δ</b>: a score that sums up how much the community backs it and how long it has been waiting. It''s calculated like this:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (upvotes − downvotes) × topic coefficient − {decay} × half-hours since posting</p>
<p><b>Votes</b> are the people''s voice: every registered user can vote once on each post, for or against, and change their mind. You can''t vote on your own.</p>
<p><b>The topic coefficient</b> is a small editorial nudge and the only parameter set by {site}: most topics are worth 1.00; culture, science or fiction are worth 1.10, that is, 10% more per vote. The coefficients are public and you''ll see them in every formula.</p>
<p><b>Age</b> subtracts: {decay} points for every half hour since it was posted. That''s what makes {site} about today, not forever: a story with two votes that has been in the radar for a day is worth less than a fresh one with the same votes. Nothing lasts forever in the radar.</p>

<h2>Two front pages and a radar</h2>
<p>Everything submitted goes into the <b>radar</b>. From there it can reach one of the two front pages: <b>Media</b>, for stories from newspapers, magazines and other websites, and <b>Substacks</b>, for newsletters. A post goes to Substacks if it links to a substack.com address or to a newsletter in the {site} catalogue; everything else goes to Media. Each post competes only with the posts heading for its own front page.</p>
<p><b>Originals</b>, the texts the community writes itself, don''t go through the radar or reach a front page: they have their own tab, sorted by date, where they''re read, voted on and commented on.</p>

<h2>The cycle</h2>
<p>Every <b>{cycle} minutes</b> a cycle runs, always the same, with no exceptions and no manual intervention:</p>
<p><b>1. From the radar to the front page.</b> Cycles take turns: one publishes to Media, the next to Substacks, and so on. In each cycle, among the posts for the front page whose turn it is that have spent at least <b>{minage} minutes</b> in the radar, <b>just one</b> is published: the one with the highest δ, as long as its δ is at least <b>{minpub}</b>. No votes, no front page, and if none qualifies, nothing is published that cycle: the front page isn''t filled just for the sake of it. So each front page gets at most one post every two cycles. The minimum time exists so a post doesn''t rise just because it arrived at a quiet moment; it has to be seen and voted on first.</p>
<p><b>2. Expiry.</b> Anything that spends <b>{newttl} hours</b> in the radar without making the front page is archived: the radar isn''t forever either.</p>
<p><b>3. The veil lifts.</b> {gm} minutes after any post or comment is published, its author''s alias gives way to their name. Until then, everyone is an alias with a halo: the words are judged, not the signature.</p>
<p><b>4. Archive.</b> Anything that has been on the front page for {ttl} hours is archived. You can still read it, but you can no longer vote or comment: the conversation was what it was.</p>
<p>The <b>Processes</b> section shows all this live: the radar sorted by δ, with the front page each post is heading for and an estimate of when it will get there, and a feed of every post, vote, comment and front-page story as it happens.</p>

<h2>Topics and communities</h2>
<p>Every post is classified twice. <b>The topic</b> is set by {site}: there are few of them, they''re stable (current affairs, science, culture…) and each has its own public coefficient. Every post has a topic.</p>
<p><b>The community</b> is set by people. Anyone with more than <b>{commaura} aura</b> can create up to <b>{commmax}</b>: a name, a colour and a sentence saying what it''s about. A post can join a community as well as its topic, so whatever interests a group gets gathered together without {site} having to decide it. Whoever creates a community looks after it: they edit its name, description, colour and visual template; moderators can do the same and, if needed, hide it.</p>
<p>Communities <b>don''t touch the δ</b>: they give no coefficient, they don''t push anything up or down. They''re a place, not a megaphone. What you can do is choose a community (or a topic) as your <b>landing page</b> in your profile: when you open {site} you''ll land there, and the Media and Substacks tabs will still be everyone''s front pages.</p>

<h2>What comes in via RSS</h2>
<p>{site} also picks up via RSS what media outlets, newsletters and other aggregators publish. There, <b>there is no δ and no cycle</b>: each source is shown in order of publication, newest first, not sorted by taste or by success. Aggregators show their own votes and comments, which belong to their community, not to {site}.</p>
<p>What comes in via RSS <b>doesn''t enter the radar by itself</b>. If someone sends it to the radar with the button on each story, from then on it''s just another post: votes, δ and cycle, like all the rest.</p>

<h2>What δ doesn''t do</h2>
<p>It doesn''t learn from you. It doesn''t know what you like. It doesn''t show you a different front page from your neighbour''s. It doesn''t reward whoever has the most followers, because nobody here has followers. It doesn''t track what time you usually drop by or how long you spend reading. The same formula, the same numbers, the same front pages for everyone.</p>

<h2>What you do choose</h2>
<p>The two front pages and the radar are <b>the same for everyone</b>, in the same order. What does change from one person to another is <b>how things look</b> and <b>how you get in</b>. For how things look, in your profile you choose the <b>visual template</b> ({skins} of them, from magazine to fanzine, each with a light and a dark version), the <b>light or dark theme</b> and the <b>language</b> (Spanish, Catalan, Basque, Galician, English, French, German, Italian or Portuguese); a community can have its own template, which you''ll see on its page and in its threads.</p>
<p>For how you get in, you choose your <b>landing page</b> (a front page, a topic or a community; if you choose a front page, the last one you viewed opens) and <b>your sources</b>: up to {mediamax} media outlets and {newsmax} newsletters always close at hand. That personalises access, not judgement: nothing you choose alters the front page, the order of anything or the δ of any post. {site} also installs as an app on your phone and has private messages between registered users.</p>

<h2>Aura</h2>
<p>People don''t have a δ; they have <b>aura</b>, which is something else and measures just one thing: commitment to the community. You earn it by being here and contributing (+{auraport} for each of your posts that makes the front page, +{auraenv} for each post or original, +{auracom} for each comment, capped at +{auracap} a day) and you lose it by not being here ({auradec} every day, never below {aurastart}). The maximum is {auramax}.</p>
<p>Aura <b>never gives you a megaphone</b>: it doesn''t push stories up, doesn''t rank comments, doesn''t weigh on the δ. It shows as a glow around your alias, and it opens a few doors of trust: with more than {commaura} aura you can create communities (up to {commmax}); with more than {editaura}, correct your own comments for {editmin} minutes after posting them; and with more than {seditaura}, edit your posts for {seditmin} minutes as long as they haven''t made the front page (anything corrected is marked as edited). With {propaura} or more you can suggest media outlets and newsletters.</p>

<h2>Today''s numbers</h2>
<p>All the parameters on this page are the ones in force right now on {site}; if the team changes them, this page changes with them. Cycle every {cycle} minutes, alternating Media and Substacks · minimum {minage} minutes in the radar · minimum δ for the front page {minpub} · decay {decay} per half hour · {gm}-minute veil · {ttl} hours of life on the front page · expiry in the radar after {newttl} hours · communities for aura above {commaura}, up to {commmax} per person.</p>' WHERE slug = 'algoritmo.en';
UPDATE pages SET body = '<h1>Com funciona el δ</h1>

<h2>Sense algorisme ocult</h2>
<p>A {site} no hi ha cap màquina que decideixi per tu què has de veure. El que arriba a portada ho decideix una fórmula pública, curta i visible: es mostra sota cada enviament del radar, amb els seus números i el seu valor en aquell moment. Aquesta pàgina l''explica sencera. Si l''entens, entens {site}.</p>

<h2>El δ (delta)</h2>
<p>Cada enviament té un <b>δ</b>: una puntuació que resumeix quant el recolza la comunitat i quant de temps fa que espera. Es calcula així:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (vots positius − vots negatius) × coeficient del tema − {decay} × mitges hores des de l''enviament</p>
<p><b>Els vots</b> són la veu de la gent: cada registrat pot votar una vegada cada enviament, a favor o en contra, i canviar d''opinió. No es pot votar allò que és propi.</p>
<p><b>El coeficient del tema</b> és una petita empenta editorial i és l''únic paràmetre que posa {site}: la majoria dels temes valen 1,00; cultura, ciència o relats valen 1,10, és a dir, un 10 % més per vot. Els coeficients són públics i els veuràs a cada fórmula.</p>
<p><b>L''antiguitat</b> resta: {decay} punts per cada mitja hora que passa des que es va enviar. És el que fa que {site} sigui d''avui i no de sempre: una notícia amb dos vots que fa un dia que és al radar val menys que una d''acabada d''arribar amb els mateixos vots. Res no dura per sempre al radar.</p>

<h2>Dues portades i un radar</h2>
<p>Tot el que s''envia entra al <b>radar</b>. Des d''allà pot arribar a una de les dues portades: <b>Mitjans</b>, per a les notícies de diaris, revistes i altres webs, i <b>Substacks</b>, per als butlletins. Un enviament va a Substacks si enllaça a una adreça de substack.com o a un butlletí del catàleg de {site}; tota la resta va a Mitjans. Cada enviament competeix només amb els de la seva portada.</p>
<p>Els <b>originals</b>, els textos que escriu la mateixa comunitat, no passen pel radar ni van a portada: tenen la seva pestanya, ordenada per data, on es llegeixen, es voten i es comenten.</p>

<h2>El cicle</h2>
<p>Cada <b>{cycle} minuts</b> hi ha un cicle, sempre igual, sense excepcions ni intervenció manual:</p>
<p><b>1. Del radar a la portada.</b> Els cicles s''alternen: un publica a Mitjans, el següent a Substacks, i així successivament. A cada cicle, d''entre els enviaments de la portada a la qual toca que fa almenys <b>{minage} minuts</b> que són al radar, se''n publica <b>només un</b>: el de δ més alt, sempre que el seu δ sigui almenys <b>{minpub}</b>. Sense vots no s''hi arriba, i si cap no hi arriba, aquell cicle no es publica res: la portada no s''omple per omplir-la. Així, cada portada rep com a màxim un enviament cada dos cicles. El temps mínim existeix perquè un enviament no pugi només perquè ha arribat en un moment tranquil; primer l''han de veure i votar.</p>
<p><b>2. Caducitat.</b> El que passa <b>{newttl} hores</b> al radar sense arribar a portada s''arxiva: el radar tampoc no és per sempre.</p>
<p><b>3. El vel s''aixeca.</b> Al cap de {gm} minuts de publicar-se qualsevol enviament o comentari, l''àlies del seu autor deixa pas al seu nom. Abans, tots són àlies amb un halo: es jutgen les paraules, no la signatura.</p>
<p><b>4. Arxiu.</b> El que fa {ttl} hores que és a portada s''arxiva. Es pot continuar llegint, però ja no es vota ni es comenta: la conversa va ser el que va ser.</p>
<p>La secció <b>Processos</b> mostra tot això en directe: el radar ordenat per δ, amb la portada a la qual va cada enviament i una estimació de quan hi arribarà, i un flux amb cada enviament, vot, comentari i portada a mesura que passen.</p>

<h2>Temes i comunitats</h2>
<p>Cada enviament es classifica dues vegades. <b>El tema</b> el posa {site}: són pocs, estables (actualitat, ciència, cultura…) i cadascun té el seu coeficient públic. Tot enviament porta un tema.</p>
<p><b>La comunitat</b> la posa la gent. Qualsevol amb més de <b>{commaura} d''aura</b> en pot crear fins a <b>{commmax}</b>: un nom, un color i una frase que digui de què va. Un enviament pot sumar-se a una comunitat a més del seu tema, i així s''agrupa el que interessa a un grup sense que {site} ho hagi de decidir. Qui crea una comunitat en té cura: n''edita el nom, la descripció, el color i la plantilla visual; els moderadors poden fer el mateix i, si cal, amagar-la.</p>
<p>Les comunitats <b>no toquen el δ</b>: no donen coeficient, no pugen ni baixen res. Són un lloc, no un altaveu. El que sí que pots fer és triar una comunitat (o un tema) com a <b>pàgina d''entrada</b> al teu perfil: quan obris {site} hi arribaràs, i les pestanyes Mitjans i Substacks continuaran sent les portades de tothom.</p>

<h2>El que arriba per RSS</h2>
<p>{site} també recull per RSS el que publiquen mitjans, butlletins i altres agregadors. Allà <b>no hi ha δ ni cicle</b>: cada font es mostra per ordre de publicació, el més nou primer, sense ordenar per gustos ni per èxit. Als agregadors s''hi veuen els seus propis vots i comentaris, que són els de la seva comunitat, no els de {site}.</p>
<p>El que arriba per RSS <b>no entra sol al radar</b>. Si algú l''envia al radar amb el botó de cada notícia, a partir d''aquí és un enviament més: vots, δ i cicle, com tots.</p>

<h2>El que el δ no fa</h2>
<p>No aprèn de tu. No sap què t''agrada. No et mostra una portada diferent de la del teu veí. No premia qui té més seguidors, perquè aquí ningú no té seguidors. No mira a quina hora acostumes a entrar ni quant de temps passes llegint. La mateixa fórmula, els mateixos números, les mateixes portades per a tothom.</p>

<h2>El que sí que tries tu</h2>
<p>Les dues portades i el radar són <b>els mateixos per a tothom</b>, en el mateix ordre. El que sí que canvia d''una persona a una altra és <b>com es veuen</b> i <b>per on entres</b>. Per a com es veuen, al teu perfil tries la <b>plantilla visual</b> ({skins}, de revista a fanzín, cadascuna amb versió clara i fosca), el <b>tema clar o fosc</b> i l''<b>idioma</b> (castellà, català, basc, gallec, anglès, francès, alemany, italià o portuguès); una comunitat pot tenir la seva pròpia plantilla, que veuràs a la seva pàgina i als seus fils.</p>
<p>Per a per on entres, tries la teva <b>pàgina d''entrada</b> (una portada, un tema o una comunitat; si tries portada, s''obre l''última que vas veure) i <b>les teves fonts</b>: fins a {mediamax} mitjans i {newsmax} butlletins sempre a mà. Això personalitza l''accés, no el judici: res del que tries no altera la portada, l''ordre de res ni el δ de cap enviament. A més, {site} s''instal·la com a aplicació al mòbil i té missatges privats entre registrats.</p>

<h2>L''aura</h2>
<p>Les persones no tenen δ; tenen <b>aura</b>, que és una altra cosa i en mesura una de sola: el compromís amb la comunitat. Es guanya estant-hi i aportant (+{auraport} per cada enviament teu que arriba a portada, +{auraenv} per cada enviament o original, +{auracom} per cada comentari, amb un límit de +{auracap} al dia) i es perd no estant-hi ({auradec} cada dia, mai per sota de {aurastart}). El màxim és {auramax}.</p>
<p>L''aura <b>mai no dona altaveu</b>: no puja notícies, no ordena comentaris, no pesa en el δ. Es veu com una llum al voltant del teu àlies, i obre algunes portes de confiança: amb més de {commaura} d''aura pots crear comunitats (fins a {commmax}); amb més de {editaura}, corregir els teus propis comentaris durant {editmin} minuts després de publicar-los, i amb més de {seditaura}, els teus enviaments durant {seditmin} minuts mentre no arribin a portada (el que es corregeix queda marcat com a editat). Amb {propaura} o més pots proposar mitjans i butlletins.</p>

<h2>Els números d''avui</h2>
<p>Tots els paràmetres d''aquesta pàgina són els que estan en vigor ara mateix a {site}; si l''equip els canvia, aquesta pàgina canvia amb ells. Cicle cada {cycle} minuts, alternant Mitjans i Substacks · {minage} minuts mínims al radar · δ mínim per a portada {minpub} · decaïment {decay} per mitja hora · vel de {gm} minuts · vida a portada de {ttl} hores · caducitat al radar al cap de {newttl} hores · comunitats amb aura superior a {commaura}, fins a {commmax} per persona.</p>' WHERE slug = 'algoritmo.ca';
UPDATE pages SET body = '<h1>Nola funtzionatzen duen δ-k</h1>

<h2>Ezkutuko algoritmorik gabe</h2>
<p>{site}-n ez dago zure ordez zer ikusi erabakitzen duen makinarik. Azalera zer iristen den formula publiko, labur eta ikusgai batek erabakitzen du: radarreko bidalketa bakoitzaren azpian ageri da, bere zenbakiekin eta une horretako balioarekin. Orri honek osorik azaltzen du. Ulertzen baduzu, {site} ulertzen duzu.</p>

<h2>δ (delta)</h2>
<p>Bidalketa bakoitzak <b>δ</b> bat du: komunitateak zenbat babesten duen eta zenbat denbora daraman zain laburbiltzen duen puntuazioa. Honela kalkulatzen da:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (aldeko botoak − kontrako botoak) × gaiaren koefizientea − {decay} × bidalketatik igarotako ordu erdiak</p>
<p><b>Botoak</b> jendearen ahotsa dira: erregistratu bakoitzak behin bozka dezake bidalketa bakoitza, alde edo kontra, eta iritziz alda dezake. Ezin da norberarena bozkatu.</p>
<p><b>Gaiaren koefizientea</b> bultzada editorial txiki bat da, eta {site}-k jartzen duen parametro bakarra: gai gehienek 1,00 balio dute; kulturak, zientziak edo kontakizunek 1,10, hau da, % 10 gehiago boto bakoitzeko. Koefizienteak publikoak dira, eta formula bakoitzean ikusiko dituzu.</p>
<p><b>Antzinatasunak</b> kendu egiten du: {decay} puntu, bidali zenetik igarotzen den ordu erdi bakoitzeko. Horri esker da {site} gaurkoa eta ez betikoa: egun bat radarrean daraman bi botoko albiste batek gutxiago balio du boto berak dituen iritsi berri batek baino. Radarrean ezerk ez du betiko irauten.</p>

<h2>Bi azal eta radar bat</h2>
<p>Bidaltzen den guztia <b>radarrean</b> sartzen da. Handik bi azaletako batera irits daiteke: <b>Hedabideak</b>, egunkari, aldizkari eta gainerako webguneetako albisteentzat, eta <b>Substackak</b>, buletinentzat. Bidalketa bat Substacketara doa substack.com helbide batera edo {site}-ren katalogoko buletin batera estekatzen badu; gainerako guztia Hedabideetara doa. Bidalketa bakoitza bere azaleko bidalketekin bakarrik lehiatzen da.</p>
<p><b>Originalak</b>, komunitateak berak idatzitako testuak, ez dira radarretik pasatzen eta ez doaz azalera: beren fitxa dute, dataren arabera ordenatuta, eta han irakurtzen, bozkatzen eta iruzkintzen dira.</p>

<h2>Zikloa</h2>
<p><b>{cycle} minuturo</b> ziklo bat gertatzen da, beti berdin, salbuespenik eta eskuzko esku-hartzerik gabe:</p>
<p><b>1. Radarretik azalera.</b> Zikloak txandaka doaz: batek Hedabideetan argitaratzen du, hurrengoak Substacketan, eta horrela hurrenez hurren. Ziklo bakoitzean, txanda duen azaleko bidalketen artean, radarrean gutxienez <b>{minage} minutu</b> daramatzatenetatik <b>bakarra</b> argitaratzen da: δ handiena duena, betiere haren δ-a gutxienez <b>{minpub}</b> bada. Botorik gabe ez da iristen, eta batek ere ez badu lortzen, ziklo horretan ez da ezer argitaratzen: azala ez da betetzeagatik betetzen. Horrela, azal bakoitzak gehienez bidalketa bat jasotzen du bi ziklotik behin. Gutxieneko denbora horren helburua da bidalketa bat ez igotzea une lasai batean iritsi delako bakarrik; lehenago ikusi eta bozkatu behar da.</p>
<p><b>2. Iraungitzea.</b> Radarrean <b>{newttl} ordu</b> azalera iritsi gabe igarotzen dituena artxibatu egiten da: radarra ere ez da betirako.</p>
<p><b>3. Beloa altxatzen da.</b> Edozein bidalketa edo iruzkin argitaratu eta {gm} minutura, egilearen ezizenak bere izenari uzten dio lekua. Ordura arte, denak dira halodun ezizenak: hitzak epaitzen dira, ez sinadura.</p>
<p><b>4. Artxiboa.</b> Azalean {ttl} ordu daramatzana artxibatu egiten da. Irakurtzen jarrai daiteke, baina jada ez da bozkatzen ez iruzkintzen: elkarrizketa izan zena izan zen.</p>
<p><b>Prozesuak</b> atalak hori guztia erakusten du zuzenean: δ-ren arabera ordenatutako radarra, bidalketa bakoitza zein azaletara doan eta noiz iritsiko den aurreikusita, eta bidalketa, boto, iruzkin eta azal-igoera bakoitzaren jarioa, gertatu ahala.</p>

<h2>Gaiak eta komunitateak</h2>
<p>Bidalketa bakoitza bi aldiz sailkatzen da. <b>Gaia</b> {site}-k jartzen du: gutxi dira, egonkorrak (gaurkotasuna, zientzia, kultura…), eta bakoitzak bere koefiziente publikoa du. Bidalketa orok darama gai bat.</p>
<p><b>Komunitatea</b> jendeak jartzen du. <b>{commaura} aura</b> baino gehiago duen edonork sor ditzake <b>{commmax}</b> arte: izen bat, kolore bat eta zertaz doan esaten duen esaldi bat. Bidalketa bat komunitate batera bil daiteke bere gaiaz gain, eta horrela talde bati interesatzen zaiona biltzen da, {site}-k erabaki beharrik gabe. Komunitate bat sortzen duenak zaintzen du: haren izena, deskribapena, kolorea eta txantiloi bisuala editatzen ditu; moderatzaileek gauza bera egin dezakete eta, behar izanez gero, ezkutatu.</p>
<p>Komunitateek <b>ez dute δ ukitzen</b>: ez dute koefizienterik ematen, ez dute ezer igotzen ez jaisten. Leku bat dira, ez bozgorailu bat. Egin dezakezuna da komunitate bat (edo gai bat) aukeratzea zure profilean <b>sarrera-orri</b> gisa: {site} irekitzean hara iritsiko zara, eta Hedabideak eta Substackak fitxek denen azalak izaten jarraituko dute.</p>

<h2>RSS bidez iristen dena</h2>
<p>{site}-k hedabideek, buletinek eta beste agregatzaile batzuek argitaratzen dutena ere jasotzen du RSS bidez. Hor <b>ez dago δ-rik ez ziklorik</b>: iturri bakoitza argitalpen-ordenan erakusten da, berriena lehenengo, gustuen edo arrakastaren arabera ordenatu gabe. Agregatzaileetan beren boto eta iruzkinak ikusten dira, beren komunitatearenak, ez {site}-renak.</p>
<p>RSS bidez iristen dena <b>ez da bere kasa sartzen radarrean</b>. Norbaitek radarrera bidaltzen badu albiste bakoitzaren botoiarekin, handik aurrera beste bidalketa bat da: botoak, δ eta zikloa, guztiak bezala.</p>

<h2>δ-k egiten ez duena</h2>
<p>Ez du zugandik ikasten. Ez daki zer gustatzen zaizun. Ez dizu zure auzokoari baino azal desberdinik erakusten. Ez du saritzen jarraitzaile gehien dituena, hemen inork ez baitu jarraitzailerik. Ez dio begiratzen zein ordutan sartu ohi zaren, ezta zenbat denbora ematen duzun irakurtzen ere. Formula bera, zenbaki berak, azal berak denentzat.</p>

<h2>Zuk aukeratzen duzuna</h2>
<p>Bi azalak eta radarra <b>berdinak dira guztientzat</b>, ordena berean. Pertsona batetik bestera aldatzen dena <b>nola ikusten diren</b> eta <b>nondik sartzen zaren</b> da. Nola ikusten diren erabakitzeko, zure profilean aukeratzen dituzu <b>txantiloi bisuala</b> ({skins}, aldizkaritik fanzinera, bakoitza bertsio argi eta ilunarekin), <b>gai argia edo iluna</b> eta <b>hizkuntza</b> (gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana, italiera edo portugesa); komunitate batek bere txantiloia izan dezake, bere orrian eta harietan ikusiko duzuna.</p>
<p>Nondik sartzen zaren erabakitzeko, zure <b>sarrera-orria</b> aukeratzen duzu (azal bat, gai bat edo komunitate bat; azala aukeratzen baduzu, ikusi zenuen azkena irekitzen da) eta <b>zure iturriak</b>: {mediamax} hedabide eta {newsmax} buletin arte, beti eskura. Horrek sarbidea pertsonalizatzen du, ez iritzia: aukeratzen duzun ezerk ez du aldatzen azala, ezeren ordena ezta inongo bidalketaren δ-a ere. Gainera, {site} mugikorrean aplikazio gisa instalatzen da, eta erregistratuen arteko mezu pribatuak ditu.</p>

<h2>Aura</h2>
<p>Pertsonek ez dute δ-rik; <b>aura</b> dute, beste gauza bat dena eta gauza bakarra neurtzen duena: komunitatearekiko konpromisoa. Egonez eta ekarriz irabazten da (+{auraport} azalera iristen den zure bidalketa bakoitzeko, +{auraenv} bidalketa edo original bakoitzeko, +{auracom} iruzkin bakoitzeko, egunean gehienez +{auracap}) eta ez egonez galtzen da (egunero {auradec}, inoiz ez {aurastart}-tik behera). Gehienezkoa {auramax} da.</p>
<p>Aurak <b>ez dizu inoiz bozgorailurik ematen</b>: ez ditu albisteak igotzen, ez ditu iruzkinak ordenatzen, ez du eraginik δ-n. Zure ezizenaren inguruko argi gisa ikusten da, eta konfiantzazko ate batzuk irekitzen ditu: {commaura} aura baino gehiagorekin komunitateak sor ditzakezu ({commmax} arte); {editaura} baino gehiagorekin, zure iruzkinak zuzendu argitaratu eta hurrengo {editmin} minutuetan; eta {seditaura} baino gehiagorekin, zure bidalketak {seditmin} minutuz, azalera iritsi ez diren bitartean (zuzendutakoa editatu gisa markatuta geratzen da). {propaura} edo gehiagorekin hedabideak eta buletinak proposa ditzakezu.</p>

<h2>Gaurko zenbakiak</h2>
<p>Orri honetako parametro guztiak {site}-n une honetan indarrean daudenak dira; taldeak aldatzen baditu, orri hau ere haiekin batera aldatzen da. Zikloa {cycle} minuturo, Hedabideak eta Substackak txandakatuz · gutxienez {minage} minutu radarrean · azalerako gutxieneko δ: {minpub} · beherakada: {decay} ordu erdiko · {gm} minutuko beloa · {ttl} orduko bizitza azalean · radarrean {newttl} ordura iraungitzen da · komunitateak {commaura} baino aura handiagoarekin, gehienez {commmax} pertsonako.</p>' WHERE slug = 'algoritmo.eu';
UPDATE pages SET body = '<h1>Como funciona o δ</h1>

<h2>Sen algoritmo oculto</h2>
<p>En {site} non hai unha máquina que decida por ti que ver. O que chega á portada decídeo unha fórmula pública, curta e visible: amósase baixo cada envío do radar, cos seus números e o seu valor nese momento. Esta páxina explícaa enteira. Se a entendes, entendes {site}.</p>

<h2>O δ (delta)</h2>
<p>Cada envío ten un <b>δ</b>: unha puntuación que resume canto o apoia a comunidade e canto tempo leva esperando. Calcúlase así:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votos positivos − votos negativos) × coeficiente do tema − {decay} × medias horas desde o envío</p>
<p><b>Os votos</b> son a voz da xente: cada rexistrado pode votar unha vez cada envío, a favor ou en contra, e cambiar de opinión. Non se pode votar o propio.</p>
<p><b>O coeficiente do tema</b> é un pequeno empurrón editorial e é o único parámetro que pon {site}: a maioría dos temas valen 1,00; cultura, ciencia ou relatos valen 1,10, é dicir, un 10 % máis por voto. Os coeficientes son públicos e verás cada un na súa fórmula.</p>
<p><b>A antigüidade</b> resta: {decay} puntos por cada media hora que pasa desde que se enviou. É o que fai que {site} sexa de hoxe e non de sempre: unha nova con dous votos que leva un día no radar vale menos ca unha acabada de chegar cos mesmos votos. Nada dura para sempre no radar.</p>

<h2>Dúas portadas e un radar</h2>
<p>Todo o que se envía entra no <b>radar</b>. Desde aí pode chegar a unha das dúas portadas: <b>Medios</b>, para as novas de xornais, revistas e demais webs, e <b>Substacks</b>, para os boletíns. Un envío vai a Substacks se a súa ligazón apunta a un enderezo de substack.com ou a un boletín do catálogo de {site}; todo o demais vai a Medios. Cada envío compite só cos da súa portada.</p>
<p>Os <b>orixinais</b>, os textos que escribe a propia comunidade, non pasan polo radar nin van á portada: teñen a súa lapela, ordenada por data, onde se len, se votan e se comentan.</p>

<h2>O ciclo</h2>
<p>Cada <b>{cycle} minutos</b> ocorre un ciclo, sempre igual, sen excepcións nin intervención manual:</p>
<p><b>1. Do radar á portada.</b> Os ciclos quendan: un publica en Medios, o seguinte en Substacks, e así sucesivamente. En cada ciclo, entre os envíos da portada á que lle toca que levan polo menos <b>{minage} minutos</b> no radar, publícase <b>un só</b>: o de maior δ, sempre que o seu δ sexa polo menos <b>{minpub}</b>. Sen votos non se chega, e se ningún chega, nese ciclo non se publica nada: a portada non se enche por encher. Así, cada portada recibe como moito un envío cada dous ciclos. O tempo mínimo existe para que un envío non suba só porque chegou nun momento tranquilo; ten que ser visto e votado antes.</p>
<p><b>2. Caducidade.</b> O que pasa <b>{newttl} horas</b> no radar sen chegar á portada arquívase: o radar tampouco é para sempre.</p>
<p><b>3. O veo levántase.</b> Aos {gm} minutos de publicarse calquera envío ou comentario, o alias de quen o escribiu deixa paso ao seu nome. Antes, todos son alias cun halo: xúlganse as palabras, non a sinatura.</p>
<p><b>4. Arquivo.</b> O que leva {ttl} horas na portada arquívase. Pódese seguir lendo, pero xa non se vota nin se comenta: a conversa foi o que foi.</p>
<p>A sección <b>Procesos</b> amosa todo isto en directo: o radar ordenado por δ, coa portada á que vai cada envío e unha estimación de cando chega, e un fluxo con cada envío, voto, comentario e portada segundo ocorren.</p>

<h2>Temas e comunidades</h2>
<p>Cada envío clasifícase dúas veces. <b>O tema</b> pono {site}: son poucos, estables (actualidade, ciencia, cultura…) e cada un ten o seu coeficiente público. Todo envío leva un tema.</p>
<p><b>A comunidade</b> pona a xente. Calquera con máis de <b>{commaura} de aura</b> pode crear ata <b>{commmax}</b>: un nome, unha cor e unha frase que diga de que vai. Un envío pode sumarse a unha comunidade ademais de ao seu tema, e así agrúpase o que lle interesa a un grupo sen que {site} teña que decidilo. Quen crea unha comunidade cóidaa: edita o seu nome, a súa descrición, a súa cor e o seu modelo visual; os moderadores poden facer o mesmo e, se cómpre, ocultala.</p>
<p>As comunidades <b>non tocan o δ</b>: non dan coeficiente, non soben nin baixan nada. Son un lugar, non un altofalante. O que si podes facer é escoller unha comunidade (ou un tema) como <b>páxina de entrada</b> no teu perfil: ao abrir {site} chegarás alí, e as lapelas Medios e Substacks seguirán sendo as portadas de todos.</p>

<h2>O que chega por RSS</h2>
<p>{site} tamén recolle por RSS o que publican medios, boletíns e outros agregadores. Aí <b>non hai δ nin ciclo</b>: cada fonte amósase por orde de publicación, o máis novo primeiro, sen ordenar por gustos nin por éxito. Nos agregadores vense os seus propios votos e comentarios, que son os da súa comunidade, non os de {site}.</p>
<p>O que chega por RSS <b>non entra só no radar</b>. Se alguén o envía ao radar co botón de cada nova, a partir de aí é un envío máis: votos, δ e ciclo, coma todos.</p>

<h2>O que o δ non fai</h2>
<p>Non aprende de ti. Non sabe que che gusta. Non che amosa unha portada distinta da do teu veciño. Non premia a quen ten máis seguidores, porque aquí ninguén ten seguidores. Non mira a hora á que adoitas entrar nin canto tempo pasas lendo. A mesma fórmula, os mesmos números, as mesmas portadas para todos.</p>

<h2>O que si escolles ti</h2>
<p>As dúas portadas e o radar son <b>os mesmos para todos</b>, na mesma orde. O que si cambia dunha persoa a outra é <b>como se ven</b> e <b>por onde entras</b>. Para como se ven, no teu perfil escolles o <b>modelo visual</b> ({skins}, de revista a fanzine, cada un con versión clara e escura), o <b>tema claro ou escuro</b> e o <b>idioma</b> (español, catalán, éuscaro, galego, inglés, francés, alemán, italiano ou portugués); unha comunidade pode ter o seu propio modelo, que verás na súa páxina e nos seus fíos.</p>
<p>Para por onde entras, escolles a túa <b>páxina de entrada</b> (unha portada, un tema ou unha comunidade; se escolles portada, ábrese a última que viches) e <b>as túas fontes</b>: ata {mediamax} medios e {newsmax} boletíns sempre a man. Iso personaliza o acceso, non o xuízo: nada do que escolles altera a portada, a orde de nada nin o δ de ningún envío. Ademais, {site} instálase como aplicación no móbil e ten mensaxes privadas entre rexistrados.</p>

<h2>A aura</h2>
<p>As persoas non teñen δ; teñen <b>aura</b>, que é outra cousa e mide unha soa: o compromiso coa comunidade. Gáñase estando e achegando (+{auraport} por cada envío teu que chega á portada, +{auraenv} por cada envío ou orixinal, +{auracom} por cada comentario, cun teito de +{auracap} ao día) e pérdese non estando ({auradec} cada día, nunca por debaixo de {aurastart}). O máximo é {auramax}.</p>
<p>A aura <b>nunca dá altofalante</b>: non sobe novas, non ordena comentarios, non pesa no δ. Vese como luz arredor do teu alias, e abre algunhas portas de confianza: con máis de {commaura} de aura podes crear comunidades (ata {commmax}); con máis de {editaura}, corrixir os teus propios comentarios durante {editmin} minutos despois de publicalos, e con máis de {seditaura}, os teus envíos durante {seditmin} minutos mentres non cheguen á portada (o corrixido queda marcado como editado). Con {propaura} ou máis podes propoñer medios e boletíns.</p>

<h2>Os números de hoxe</h2>
<p>Todos os parámetros desta páxina son os que están en vigor agora mesmo en {site}; se o equipo os cambia, esta páxina cambia con eles. Ciclo cada {cycle} minutos, alternando Medios e Substacks · {minage} minutos mínimos no radar · δ mínimo para portada {minpub} · decaemento {decay} por media hora · veo de {gm} minutos · vida na portada de {ttl} horas · caducidade no radar ás {newttl} horas · comunidades con aura maior que {commaura}, ata {commmax} por persoa.</p>' WHERE slug = 'algoritmo.gl';
UPDATE pages SET body = '<h1>Comment fonctionne le δ</h1>

<h2>Pas d''algorithme caché</h2>
<p>Sur {site}, aucune machine ne décide pour toi de ce que tu vois. Ce qui arrive en une, c''est une formule publique, courte et visible qui le décide : elle s''affiche sous chaque envoi du radar, avec ses chiffres et sa valeur à cet instant. Cette page l''explique en entier. Si tu la comprends, tu comprends {site}.</p>

<h2>Le δ (delta)</h2>
<p>Chaque envoi a un <b>δ</b> : un score qui résume à quel point la communauté le soutient et depuis combien de temps il attend. Il se calcule ainsi :</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votes positifs − votes négatifs) × coefficient du thème − {decay} × demi-heures depuis l''envoi</p>
<p><b>Les votes</b> sont la voix des gens : chaque inscrit peut voter une fois par envoi, pour ou contre, et changer d''avis. On ne peut pas voter pour ses propres envois.</p>
<p><b>Le coefficient du thème</b> est un petit coup de pouce éditorial, et c''est le seul paramètre que fixe {site} : la plupart des thèmes valent 1,00 ; culture, science ou récits valent 1,10, soit 10 % de plus par vote. Les coefficients sont publics et tu les verras dans chaque formule.</p>
<p><b>L''ancienneté</b> retire des points : {decay} par demi-heure écoulée depuis l''envoi. C''est ce qui fait que {site} parle d''aujourd''hui et pas de toujours : une actualité à deux votes qui est dans le radar depuis un jour vaut moins qu''une toute nouvelle avec les mêmes votes. Rien ne dure éternellement dans le radar.</p>

<h2>Deux unes et un radar</h2>
<p>Tout ce qui est envoyé entre dans le <b>radar</b>. De là, il peut arriver sur l''une des deux unes : <b>Médias</b>, pour les actualités des journaux, des magazines et des autres sites, et <b>Substacks</b>, pour les newsletters. Un envoi va dans Substacks s''il pointe vers une adresse de substack.com ou vers une newsletter du catalogue de {site} ; tout le reste va dans Médias. Chaque envoi n''est en concurrence qu''avec ceux de sa une.</p>
<p>Les <b>originaux</b>, les textes écrits par la communauté elle-même, ne passent pas par le radar et ne vont pas en une : ils ont leur propre onglet, trié par date, où on les lit, on les vote et on les commente.</p>

<h2>Le cycle</h2>
<p>Toutes les <b>{cycle} minutes</b> a lieu un cycle, toujours le même, sans exception ni intervention manuelle :</p>
<p><b>1. Du radar à la une.</b> Les cycles alternent : l''un publie dans Médias, le suivant dans Substacks, et ainsi de suite. À chaque cycle, parmi les envois de la une dont c''est le tour et qui sont dans le radar depuis au moins <b>{minage} minutes</b>, <b>un seul</b> est publié : celui au δ le plus élevé, à condition que son δ atteigne au moins <b>{minpub}</b>. Sans votes, on n''y arrive pas, et si aucun n''y arrive, ce cycle ne publie rien : la une ne se remplit pas pour se remplir. Ainsi, chaque une reçoit au plus un envoi tous les deux cycles. Le temps minimum existe pour qu''un envoi ne monte pas simplement parce qu''il est arrivé à un moment calme : il doit d''abord être vu et voté.</p>
<p><b>2. Expiration.</b> Ce qui passe <b>{newttl} heures</b> dans le radar sans arriver en une est archivé : le radar non plus n''est pas éternel.</p>
<p><b>3. Le voile se lève.</b> {gm} minutes après la publication de n''importe quel envoi ou commentaire, l''alias de son auteur laisse place à son nom. Avant, tout le monde est un alias avec un halo : on juge les mots, pas la signature.</p>
<p><b>4. Archivage.</b> Ce qui est en une depuis {ttl} heures est archivé. On peut continuer à le lire, mais on ne vote plus et on ne commente plus : la conversation a été ce qu''elle a été.</p>
<p>La section <b>Processus</b> montre tout cela en direct : le radar trié par δ, avec la une vers laquelle va chaque envoi et une estimation du moment où il y arrivera, et un flux avec chaque envoi, vote, commentaire et passage en une au moment où ils se produisent.</p>

<h2>Thèmes et communautés</h2>
<p>Chaque envoi est classé deux fois. <b>Le thème</b>, c''est {site} qui le fixe : ils sont peu nombreux, stables (actualité, science, culture…) et chacun a son coefficient public. Tout envoi a un thème.</p>
<p><b>La communauté</b>, ce sont les gens qui la choisissent. Toute personne ayant plus de <b>{commaura} d''aura</b> peut en créer jusqu''à <b>{commmax}</b> : un nom, une couleur et une phrase qui dit de quoi il s''agit. Un envoi peut rejoindre une communauté en plus de son thème, et ainsi se regroupe ce qui intéresse un groupe sans que {site} ait à en décider. Qui crée une communauté en prend soin : il en modifie le nom, la description, la couleur et l''habillage ; les modérateurs peuvent faire de même et, si besoin, la masquer.</p>
<p>Les communautés <b>ne touchent pas au δ</b> : elles ne donnent pas de coefficient, ne font rien monter ni descendre. Ce sont des lieux, pas des porte-voix. Ce que tu peux faire, en revanche, c''est choisir une communauté (ou un thème) comme <b>page d''arrivée</b> dans ton profil : en ouvrant {site}, tu arriveras là, et les onglets Médias et Substacks resteront les unes de tout le monde.</p>

<h2>Ce qui arrive par RSS</h2>
<p>{site} récupère aussi par RSS ce que publient des médias, des newsletters et d''autres agrégateurs. Là, <b>il n''y a ni δ ni cycle</b> : chaque source s''affiche dans l''ordre de publication, le plus récent d''abord, sans tri par goûts ni par succès. Les agrégateurs affichent leurs propres votes et commentaires, qui sont ceux de leur communauté, pas ceux de {site}.</p>
<p>Ce qui arrive par RSS <b>n''entre pas tout seul dans le radar</b>. Si quelqu''un l''envoie dans le radar avec le bouton de chaque actualité, à partir de là c''est un envoi comme un autre : votes, δ et cycle, comme tous.</p>

<h2>Ce que le δ ne fait pas</h2>
<p>Il n''apprend rien de toi. Il ne sait pas ce que tu aimes. Il ne te montre pas une une différente de celle de ton voisin. Il ne récompense pas ceux qui ont le plus d''abonnés, parce qu''ici personne n''a d''abonnés. Il ne regarde pas l''heure à laquelle tu te connectes d''habitude ni le temps que tu passes à lire. La même formule, les mêmes chiffres, les mêmes unes pour tout le monde.</p>

<h2>Ce que tu choisis, toi</h2>
<p>Les deux unes et le radar sont <b>les mêmes pour tout le monde</b>, dans le même ordre. Ce qui change d''une personne à l''autre, c''est <b>leur apparence</b> et <b>ton point d''entrée</b>. Pour l''apparence, tu choisis dans ton profil l''<b>habillage</b> ({skins}, du magazine au fanzine, chacun en version claire et sombre), le <b>thème clair ou sombre</b> et la <b>langue</b> (espagnol, catalan, basque, galicien, anglais, français, allemand, italien ou portugais) ; une communauté peut avoir son propre habillage, que tu verras sur sa page et dans ses fils.</p>
<p>Pour le point d''entrée, tu choisis ta <b>page d''arrivée</b> (l''une des unes, un thème ou une communauté ; si tu choisis la une, c''est la dernière que tu as vue qui s''ouvre) et <b>tes sources</b> : jusqu''à {mediamax} médias et {newsmax} newsletters toujours à portée de main. Cela personnalise l''accès, pas le jugement : rien de ce que tu choisis ne modifie la une, l''ordre de quoi que ce soit ni le δ d''un envoi. {site} s''installe aussi comme une application sur le mobile et propose des messages privés entre inscrits.</p>

<h2>L''aura</h2>
<p>Les personnes n''ont pas de δ ; elles ont une <b>aura</b>, qui est autre chose et ne mesure qu''une chose : l''engagement envers la communauté. Elle se gagne en étant là et en contribuant (+{auraport} pour chaque envoi à toi qui arrive en une, +{auraenv} pour chaque envoi ou original, +{auracom} pour chaque commentaire, avec un plafond de +{auracap} par jour) et se perd en n''étant pas là ({auradec} par jour, jamais en dessous de {aurastart}). Le maximum est de {auramax}.</p>
<p>L''aura <b>ne donne jamais de porte-voix</b> : elle ne fait pas monter d''actualités, ne classe pas les commentaires, ne pèse pas dans le δ. Elle se voit comme une lumière autour de ton alias, et ouvre quelques portes de confiance : avec plus de {commaura} d''aura tu peux créer des communautés (jusqu''à {commmax}) ; avec plus de {editaura}, corriger tes propres commentaires pendant {editmin} minutes après les avoir publiés ; et avec plus de {seditaura}, tes envois pendant {seditmin} minutes tant qu''ils ne sont pas en une (ce qui est corrigé est marqué comme modifié). Avec {propaura} ou plus, tu peux proposer des médias et des newsletters.</p>

<h2>Les chiffres du jour</h2>
<p>Tous les paramètres de cette page sont ceux en vigueur en ce moment même sur {site} ; si l''équipe les change, cette page change avec eux. Cycle toutes les {cycle} minutes, en alternant Médias et Substacks · {minage} minutes minimum dans le radar · δ minimum pour la une {minpub} · décroissance de {decay} par demi-heure · voile de {gm} minutes · durée de vie en une de {ttl} heures · expiration dans le radar au bout de {newttl} heures · communautés avec une aura supérieure à {commaura}, jusqu''à {commmax} par personne.</p>' WHERE slug = 'algoritmo.fr';
UPDATE pages SET body = '<h1>So funktioniert das δ</h1>

<h2>Kein verborgener Algorithmus</h2>
<p>Auf {site} gibt es keine Maschine, die für dich entscheidet, was du siehst. Was auf die Titelseite kommt, entscheidet eine öffentliche, kurze und sichtbare Formel: Sie steht unter jedem Beitrag im Radar, mit ihren Zahlen und ihrem aktuellen Wert. Diese Seite erklärt sie vollständig. Wer sie versteht, versteht {site}.</p>

<h2>Das δ (Delta)</h2>
<p>Jeder Beitrag hat ein <b>δ</b>: eine Punktzahl, die zusammenfasst, wie sehr die Community ihn unterstützt und wie lange er schon wartet. Sie wird so berechnet:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (positive Stimmen − negative Stimmen) × Themen-Koeffizient − {decay} × halbe Stunden seit dem Einreichen</p>
<p><b>Die Stimmen</b> sind die Stimme der Leute: Jeder registrierte Nutzer kann über jeden Beitrag einmal abstimmen, dafür oder dagegen, und seine Meinung ändern. Über Eigenes kann man nicht abstimmen.</p>
<p><b>Der Themen-Koeffizient</b> ist ein kleiner redaktioneller Schub und der einzige Parameter, den {site} festlegt: Die meisten Themen zählen 1,00; Kultur, Wissenschaft oder Erzählungen zählen 1,10, also 10 % mehr pro Stimme. Die Koeffizienten sind öffentlich, du siehst sie in jeder Formel.</p>
<p><b>Das Alter</b> zieht ab: {decay} Punkte für jede halbe Stunde seit dem Einreichen. Deshalb ist {site} von heute und nicht von immer: Eine Nachricht mit zwei Stimmen, die seit einem Tag im Radar steht, zählt weniger als eine frisch eingetroffene mit denselben Stimmen. Nichts bleibt ewig im Radar.</p>

<h2>Zwei Titelseiten und ein Radar</h2>
<p>Alles, was eingereicht wird, kommt ins <b>Radar</b>. Von dort kann es auf eine der beiden Titelseiten gelangen: <b>Medien</b> für Nachrichten aus Zeitungen, Zeitschriften und anderen Websites und <b>Substacks</b> für Newsletter. Ein Beitrag geht zu Substacks, wenn er auf eine Adresse von substack.com oder auf einen Newsletter aus dem Katalog von {site} verlinkt; alles andere geht zu Medien. Jeder Beitrag konkurriert nur mit denen seiner Titelseite.</p>
<p>Die <b>Originale</b>, die Texte, die die Community selbst schreibt, gehen nicht durchs Radar und kommen auf keine Titelseite: Sie haben ihren eigenen Reiter, nach Datum sortiert, wo sie gelesen, bewertet und kommentiert werden.</p>

<h2>Der Zyklus</h2>
<p>Alle <b>{cycle} Minuten</b> läuft ein Zyklus, immer gleich, ohne Ausnahmen und ohne manuelle Eingriffe:</p>
<p><b>1. Vom Radar auf die Titelseite.</b> Die Zyklen wechseln sich ab: Einer veröffentlicht unter Medien, der nächste unter Substacks, und so weiter. In jedem Zyklus wird unter den Beiträgen der Titelseite, die an der Reihe ist, und die seit mindestens <b>{minage} Minuten</b> im Radar stehen, <b>nur einer</b> veröffentlicht: der mit dem höchsten δ, sofern sein δ mindestens <b>{minpub}</b> beträgt. Ohne Stimmen kommt man nicht hin, und schafft es keiner, wird in diesem Zyklus nichts veröffentlicht: Die Titelseite wird nicht aufgefüllt, nur um sie zu füllen. So bekommt jede Titelseite höchstens einen Beitrag alle zwei Zyklen. Die Mindestzeit gibt es, damit ein Beitrag nicht nur deshalb aufsteigt, weil er in einem ruhigen Moment kam; er muss vorher gesehen und bewertet werden.</p>
<p><b>2. Ablauf.</b> Was <b>{newttl} Stunden</b> im Radar steht, ohne auf eine Titelseite zu kommen, wird archiviert: Auch das Radar ist nicht für immer.</p>
<p><b>3. Der Schleier hebt sich.</b> {gm} Minuten nach der Veröffentlichung eines Beitrags oder Kommentars weicht der Alias des Autors seinem Namen. Vorher sind alle Aliasse mit Halo: Beurteilt werden die Worte, nicht die Unterschrift.</p>
<p><b>4. Archiv.</b> Was seit {ttl} Stunden auf der Titelseite steht, wird archiviert. Man kann es weiter lesen, aber nicht mehr abstimmen oder kommentieren: Das Gespräch war, was es war.</p>
<p>Der Bereich <b>Abläufe</b> zeigt all das live: das Radar, sortiert nach δ, mit der Titelseite, zu der jeder Beitrag geht, und einer Schätzung, wann er dort ankommt, und einen Strom mit jedem Beitrag, jeder Stimme, jedem Kommentar und jeder Titelseite, sobald sie passieren.</p>

<h2>Themen und Communities</h2>
<p>Jeder Beitrag wird zweimal eingeordnet. <b>Das Thema</b> legt {site} fest: Es sind wenige, beständige (Aktuelles, Wissenschaft, Kultur …), und jedes hat seinen öffentlichen Koeffizienten. Jeder Beitrag hat ein Thema.</p>
<p><b>Die Community</b> bestimmen die Leute. Wer mehr als <b>{commaura} Aura</b> hat, kann bis zu <b>{commmax}</b> gründen: ein Name, eine Farbe und ein Satz, der sagt, worum es geht. Ein Beitrag kann zusätzlich zu seinem Thema einer Community beitreten, und so sammelt sich, was eine Gruppe interessiert, ohne dass {site} es entscheiden muss. Wer eine Community gründet, kümmert sich um sie: bearbeitet Name, Beschreibung, Farbe und visuelles Design; Moderatoren können dasselbe tun und sie, falls nötig, ausblenden.</p>
<p>Communities <b>berühren das δ nicht</b>: Sie geben keinen Koeffizienten, heben nichts und senken nichts. Sie sind ein Ort, kein Lautsprecher. Was du aber tun kannst: in deinem Profil eine Community (oder ein Thema) als <b>Startseite</b> wählen. Wenn du {site} öffnest, landest du dort, und die Reiter Medien und Substacks bleiben die Titelseiten für alle.</p>

<h2>Was per RSS hereinkommt</h2>
<p>{site} sammelt per RSS auch, was Medien, Newsletter und andere Aggregatoren veröffentlichen. Dort gibt es <b>kein δ und keinen Zyklus</b>: Jede Quelle wird in der Reihenfolge der Veröffentlichung gezeigt, das Neueste zuerst, ohne Sortierung nach Vorlieben oder Erfolg. Bei den Aggregatoren sieht man ihre eigenen Stimmen und Kommentare, die von ihrer Community stammen, nicht von {site}.</p>
<p>Was per RSS hereinkommt, <b>landet nicht von selbst im Radar</b>. Schickt es jemand mit dem Knopf an jeder Nachricht ins Radar, ist es von da an ein Beitrag wie jeder andere: Stimmen, δ und Zyklus, wie alle.</p>

<h2>Was das δ nicht tut</h2>
<p>Es lernt nichts über dich. Es weiß nicht, was dir gefällt. Es zeigt dir keine andere Titelseite als deinem Nachbarn. Es belohnt niemanden, der mehr Follower hat, denn hier hat niemand Follower. Es schaut nicht, wann du normalerweise vorbeikommst oder wie lange du liest. Dieselbe Formel, dieselben Zahlen, dieselben Titelseiten für alle.</p>

<h2>Was du selbst wählst</h2>
<p>Die beiden Titelseiten und das Radar sind <b>für alle gleich</b>, in derselben Reihenfolge. Was sich von Person zu Person ändert, ist, <b>wie sie aussehen</b> und <b>wo du hereinkommst</b>. Für das Aussehen wählst du in deinem Profil das <b>visuelle Design</b> ({skins}, vom Magazin bis zum Fanzine, jedes in Hell und Dunkel), das <b>helle oder dunkle Farbschema</b> und die <b>Sprache</b> (Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch, Italienisch oder Portugiesisch); eine Community kann ihr eigenes Design haben, das du auf ihrer Seite und in ihren Threads siehst.</p>
<p>Für den Einstieg wählst du deine <b>Startseite</b> (eine Titelseite, ein Thema oder eine Community; wählst du eine Titelseite, öffnet sich die zuletzt gesehene) und <b>deine Quellen</b>: bis zu {mediamax} Medien und {newsmax} Newsletter, immer griffbereit. Das personalisiert den Zugang, nicht das Urteil: Nichts, was du wählst, verändert die Titelseite, die Reihenfolge von irgendetwas oder das δ irgendeines Beitrags. {site} lässt sich außerdem als App aufs Handy installieren und hat private Nachrichten zwischen registrierten Nutzern.</p>

<h2>Die Aura</h2>
<p>Menschen haben kein δ; sie haben <b>Aura</b>, und die ist etwas anderes und misst nur eines: das Engagement für die Community. Man gewinnt sie, indem man da ist und beiträgt (+{auraport} für jeden deiner Beiträge, der auf die Titelseite kommt, +{auraenv} für jeden Beitrag oder jedes Original, +{auracom} für jeden Kommentar, mit einer Obergrenze von +{auracap} pro Tag), und man verliert sie, wenn man nicht da ist ({auradec} pro Tag, nie unter {aurastart}). Das Maximum ist {auramax}.</p>
<p>Die Aura <b>macht niemanden lauter</b>: Sie hebt keine Nachrichten hoch, sortiert keine Kommentare, zählt nicht im δ. Sie erscheint als Licht um deinen Alias und öffnet ein paar Türen des Vertrauens: Mit mehr als {commaura} Aura kannst du Communities gründen (bis zu {commmax}), mit mehr als {editaura} deine eigenen Kommentare {editmin} Minuten lang nach dem Veröffentlichen korrigieren und mit mehr als {seditaura} deine Beiträge {seditmin} Minuten lang, solange sie nicht auf der Titelseite stehen (Korrigiertes wird als bearbeitet markiert). Mit {propaura} oder mehr kannst du Medien und Newsletter vorschlagen.</p>

<h2>Die Zahlen von heute</h2>
<p>Alle Parameter auf dieser Seite sind die, die gerade auf {site} gelten; ändert das Team sie, ändert sich diese Seite mit. Zyklus alle {cycle} Minuten, abwechselnd Medien und Substacks · mindestens {minage} Minuten im Radar · Mindest-δ für die Titelseite {minpub} · Abzug {decay} pro halbe Stunde · Schleier von {gm} Minuten · {ttl} Stunden auf der Titelseite · Ablauf im Radar nach {newttl} Stunden · Communities ab einer Aura über {commaura}, bis zu {commmax} pro Person.</p>' WHERE slug = 'algoritmo.de';
UPDATE pages SET body = '<h1>Come funziona il δ</h1>

<h2>Niente algoritmo nascosto</h2>
<p>Su {site} non c''è una macchina che decide per te cosa vedere. Cosa arriva in prima pagina lo decide una formula pubblica, breve e visibile: compare sotto ogni invio del radar, con i suoi numeri e il suo valore in quel momento. Questa pagina la spiega per intero. Se la capisci, capisci {site}.</p>

<h2>Il δ (delta)</h2>
<p>Ogni invio ha un <b>δ</b>: un punteggio che riassume quanto lo sostiene la comunità e da quanto tempo è in attesa. Si calcola così:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (voti positivi − voti negativi) × coefficiente del tema − {decay} × mezz''ore dall''invio</p>
<p><b>I voti</b> sono la voce della gente: ogni utente registrato può votare una volta ogni invio, a favore o contro, e cambiare idea. Non si possono votare le proprie cose.</p>
<p><b>Il coefficiente del tema</b> è una piccola spinta editoriale ed è l''unico parametro che stabilisce {site}: la maggior parte dei temi vale 1,00; cultura, scienza o racconti valgono 1,10, cioè il 10 % in più per voto. I coefficienti sono pubblici e li vedrai in ogni formula.</p>
<p><b>L''anzianità</b> sottrae: {decay} punti per ogni mezz''ora che passa dall''invio. È ciò che rende {site} una cosa di oggi e non di sempre: una notizia con due voti che è nel radar da un giorno vale meno di una appena arrivata con gli stessi voti. Niente dura per sempre nel radar.</p>

<h2>Due prime pagine e un radar</h2>
<p>Tutto ciò che viene inviato entra nel <b>radar</b>. Da lì può arrivare a una delle due prime pagine: <b>Media</b>, per le notizie di giornali, riviste e altri siti, e <b>Substack</b>, per le newsletter. Un invio va in Substack se rimanda a un indirizzo di substack.com o a una newsletter del catalogo di {site}; tutto il resto va in Media. Ogni invio compete solo con quelli della sua prima pagina.</p>
<p>Gli <b>originali</b>, i testi scritti dalla comunità stessa, non passano per il radar e non vanno in prima pagina: hanno la loro scheda, ordinata per data, dove si leggono, si votano e si commentano.</p>

<h2>Il ciclo</h2>
<p>Ogni <b>{cycle} minuti</b> avviene un ciclo, sempre uguale, senza eccezioni né interventi manuali:</p>
<p><b>1. Dal radar alla prima pagina.</b> I cicli si alternano: uno pubblica in Media, il successivo in Substack, e così via. In ogni ciclo, tra gli invii della prima pagina di turno che sono nel radar da almeno <b>{minage} minuti</b>, se ne pubblica <b>uno solo</b>: quello con il δ più alto, purché il suo δ sia almeno <b>{minpub}</b>. Senza voti non ci si arriva, e se nessuno ci arriva, in quel ciclo non si pubblica niente: la prima pagina non si riempie tanto per riempirla. Così ogni prima pagina riceve al massimo un invio ogni due cicli. Il tempo minimo esiste perché un invio non salga solo perché è arrivato in un momento tranquillo; prima deve essere visto e votato.</p>
<p><b>2. Scadenza.</b> Ciò che passa <b>{newttl} ore</b> nel radar senza arrivare in prima pagina viene archiviato: nemmeno il radar è per sempre.</p>
<p><b>3. Il velo si alza.</b> {gm} minuti dopo la pubblicazione di qualsiasi invio o commento, l''alias dell''autore lascia il posto al suo nome. Prima, sono tutti alias con un alone: si giudicano le parole, non la firma.</p>
<p><b>4. Archivio.</b> Ciò che è in prima pagina da {ttl} ore viene archiviato. Si può continuare a leggerlo, ma non si vota né si commenta più: la conversazione è stata quello che è stata.</p>
<p>La sezione <b>Processi</b> mostra tutto questo in diretta: il radar ordinato per δ, con la prima pagina verso cui va ogni invio e una stima di quando ci arriverà, e un flusso con ogni invio, voto, commento e prima pagina man mano che accadono.</p>

<h2>Temi e comunità</h2>
<p>Ogni invio viene classificato due volte. <b>Il tema</b> lo stabilisce {site}: sono pochi, stabili (attualità, scienza, cultura…) e ognuno ha il suo coefficiente pubblico. Ogni invio ha un tema.</p>
<p><b>La comunità</b> la sceglie la gente. Chiunque abbia più di <b>{commaura} di aura</b> può crearne fino a <b>{commmax}</b>: un nome, un colore e una frase che dica di cosa parla. Un invio può aggiungersi a una comunità oltre che al suo tema, e così si raccoglie ciò che interessa a un gruppo senza che {site} debba deciderlo. Chi crea una comunità se ne prende cura: ne modifica il nome, la descrizione, il colore e lo stile visivo; i moderatori possono fare lo stesso e, se serve, nasconderla.</p>
<p>Le comunità <b>non toccano il δ</b>: non danno coefficienti, non fanno salire né scendere niente. Sono un luogo, non un megafono. Quello che puoi fare è scegliere una comunità (o un tema) come <b>pagina di ingresso</b> nel tuo profilo: aprendo {site} arriverai lì, e le schede Media e Substack resteranno le prime pagine di tutti.</p>

<h2>Quello che arriva via RSS</h2>
<p>{site} raccoglie via RSS anche ciò che pubblicano testate, newsletter e altri aggregatori. Lì <b>non ci sono né δ né ciclo</b>: ogni fonte si mostra in ordine di pubblicazione, dal più recente, senza ordinare per gusti né per successo. Negli aggregatori si vedono i loro voti e commenti, che sono quelli della loro comunità, non di {site}.</p>
<p>Ciò che arriva via RSS <b>non entra da solo nel radar</b>. Se qualcuno lo invia al radar con il pulsante di ogni notizia, da lì in poi è un invio come un altro: voti, δ e ciclo, come tutti.</p>

<h2>Cosa il δ non fa</h2>
<p>Non impara da te. Non sa cosa ti piace. Non ti mostra una prima pagina diversa da quella del tuo vicino. Non premia chi ha più follower, perché qui nessuno ha follower. Non guarda a che ora entri di solito né quanto tempo passi a leggere. La stessa formula, gli stessi numeri, le stesse prime pagine per tutti.</p>

<h2>Cosa scegli tu</h2>
<p>Le due prime pagine e il radar sono <b>gli stessi per tutti</b>, nello stesso ordine. Quello che cambia da una persona all''altra è <b>come si vedono</b> e <b>da dove entri</b>. Per come si vedono, nel tuo profilo scegli lo <b>stile visivo</b> ({skins}, dalla rivista alla fanzine, ognuno con versione chiara e scura), il <b>tema chiaro o scuro</b> e la <b>lingua</b> (spagnolo, catalano, basco, galiziano, inglese, francese, tedesco, italiano o portoghese); una comunità può avere il suo stile, che vedrai nella sua pagina e nelle sue discussioni.</p>
<p>Per da dove entri, scegli la tua <b>pagina di ingresso</b> (una prima pagina, un tema o una comunità; se scegli la prima pagina, si apre l''ultima che hai visto) e <b>le tue fonti</b>: fino a {mediamax} testate e {newsmax} newsletter sempre a portata di mano. Questo personalizza l''accesso, non il giudizio: niente di ciò che scegli altera la prima pagina, l''ordine di alcunché né il δ di alcun invio. {site} si installa anche come app sul telefono e ha messaggi privati tra utenti registrati.</p>

<h2>L''aura</h2>
<p>Le persone non hanno un δ; hanno l''<b>aura</b>, che è un''altra cosa e ne misura una sola: l''impegno verso la comunità. Si guadagna partecipando e contribuendo (+{auraport} per ogni tuo invio che arriva in prima pagina, +{auraenv} per ogni invio o originale, +{auracom} per ogni commento, con un tetto di +{auracap} al giorno) e si perde non partecipando ({auradec} ogni giorno, mai sotto {aurastart}). Il massimo è {auramax}.</p>
<p>L''aura <b>non dà mai un megafono</b>: non fa salire notizie, non ordina commenti, non pesa sul δ. Si vede come una luce intorno al tuo alias, e apre alcune porte di fiducia: con più di {commaura} di aura puoi creare comunità (fino a {commmax}); con più di {editaura}, correggere i tuoi commenti per {editmin} minuti dopo averli pubblicati; e con più di {seditaura}, i tuoi invii per {seditmin} minuti finché non arrivano in prima pagina (ciò che viene corretto resta contrassegnato come modificato). Con {propaura} o più puoi proporre testate e newsletter.</p>

<h2>I numeri di oggi</h2>
<p>Tutti i parametri di questa pagina sono quelli in vigore in questo momento su {site}; se il team li cambia, questa pagina cambia con loro. Ciclo ogni {cycle} minuti, alternando Media e Substack · {minage} minuti minimi nel radar · δ minimo per la prima pagina {minpub} · decadimento {decay} ogni mezz''ora · velo di {gm} minuti · vita in prima pagina di {ttl} ore · scadenza nel radar dopo {newttl} ore · comunità con aura maggiore di {commaura}, fino a {commmax} a persona.</p>' WHERE slug = 'algoritmo.it';
UPDATE pages SET body = '<h1>Como funciona o δ</h1>

<h2>Sem algoritmo oculto</h2>
<p>Em {site} não há uma máquina que decida o que cada pessoa vê. O que chega à capa é decidido por uma fórmula pública, curta e visível: aparece sob cada envio do radar, com os seus números e o seu valor nesse momento. Esta página explica a fórmula por inteiro. Quem a entende, entende {site}.</p>

<h2>O δ (delta)</h2>
<p>Cada envio tem um <b>δ</b>: uma pontuação que resume quanto apoio recebe da comunidade e há quanto tempo espera. O cálculo é este:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votos positivos − votos negativos) × coeficiente do tema − {decay} × meias horas desde o envio</p>
<p><b>Os votos</b> são a voz das pessoas: cada membro pode votar uma vez em cada envio, a favor ou contra, e mudar de opinião. Não é possível votar nos próprios envios.</p>
<p><b>O coeficiente do tema</b> é um pequeno empurrão editorial e é o único parâmetro definido por {site}: a maioria dos temas vale 1,00; cultura, ciência ou relatos valem 1,10, ou seja, 10 % a mais por voto. Os coeficientes são públicos e aparecem em cada fórmula.</p>
<p><b>A antiguidade</b> desconta: {decay} pontos por cada meia hora desde o envio. É isso que faz com que {site} seja de hoje e não de sempre: uma notícia com dois votos que está há um dia no radar vale menos do que uma recém-chegada com os mesmos votos. Nada dura para sempre no radar.</p>

<h2>Duas capas e um radar</h2>
<p>Tudo o que se envia entra no <b>radar</b>. A partir daí, pode chegar a uma das duas capas: <b>Meios</b>, para as notícias de jornais, revistas e outros sites, e <b>Newsletters</b>, para as newsletters do Substack e afins. Um envio vai para Newsletters se tiver um link para um endereço de substack.com ou para uma newsletter do catálogo de {site}; tudo o resto vai para Meios. Cada envio compete apenas com os da sua capa.</p>
<p>Os <b>originais</b>, os textos escritos pela própria comunidade, não passam pelo radar nem vão para a capa: têm o seu separador, ordenado por data, onde são lidos, votados e comentados.</p>

<h2>O ciclo</h2>
<p>A cada <b>{cycle} minutos</b> acontece um ciclo, sempre igual, sem exceções nem intervenção manual:</p>
<p><b>1. Do radar para a capa.</b> Os ciclos alternam: um publica em Meios, o seguinte em Newsletters, e assim sucessivamente. Em cada ciclo, entre os envios da capa a que cabe a vez que estão há pelo menos <b>{minage} minutos</b> no radar, é publicado <b>apenas um</b>: o de maior δ, desde que o seu δ seja de pelo menos <b>{minpub}</b>. Sem votos não se chega lá, e se nenhum chega lá, nesse ciclo nada é publicado: a capa não é preenchida só por preencher. Assim, cada capa recebe no máximo um envio a cada dois ciclos. O tempo mínimo existe para que um envio não suba só por ter chegado quando havia pouco movimento; tem de ser visto e votado antes.</p>
<p><b>2. Expiração.</b> O que passa <b>{newttl} horas</b> no radar sem chegar à capa é arquivado: o radar também não é para sempre.</p>
<p><b>3. Cai o véu.</b> Passados {gm} minutos da publicação de qualquer envio ou comentário, o alias do autor dá lugar ao seu nome. Antes disso, todos são alias com um halo: são julgadas as palavras, não a assinatura.</p>
<p><b>4. Arquivo.</b> O que está há {ttl} horas na capa é arquivado. Ainda pode ser lido, mas já não recebe votos nem comentários: a conversa foi o que foi.</p>
<p>A área <b>Processos</b> mostra tudo isto em tempo real: o radar ordenado por δ, com a capa para onde vai cada envio e uma estimativa de quando chega, e um fluxo com cada envio, voto, comentário e publicação na capa à medida que acontecem.</p>

<h2>Temas e comunidades</h2>
<p>Cada envio é classificado duas vezes. <b>O tema</b> é definido por {site}: são poucos, estáveis (atualidade, ciência, cultura…) e cada um tem o seu coeficiente público. Cada envio leva sempre um tema.</p>
<p><b>A comunidade</b> é definida pelas pessoas. Quem tiver mais de <b>{commaura} de aura</b> pode criar até <b>{commmax}</b>: um nome, uma cor e uma frase que diga do que se trata. Um envio pode pertencer a uma comunidade além do seu tema, e assim fica reunido o que interessa a um grupo sem que {site} tenha de decidir. Quem cria uma comunidade cuida dela: edita o nome, a descrição, a cor e o modelo visual; os moderadores podem fazer o mesmo e, se necessário, ocultá-la.</p>
<p>As comunidades <b>não mexem no δ</b>: não dão coeficiente, não sobem nem descem nada. São um lugar, não um megafone. O que é possível é escolher uma comunidade (ou um tema) como <b>página de entrada</b> no perfil: ao abrir {site}, é essa a página que aparece, e os separadores Meios e Newsletters continuam a ser as capas de todos.</p>

<h2>O que chega por RSS</h2>
<p>{site} também recolhe por RSS o que publicam meios, newsletters e outros agregadores. Aí <b>não há δ nem ciclo</b>: cada fonte é mostrada por ordem de publicação, o mais recente primeiro, sem ordenar por gostos nem por sucesso. Nos agregadores aparecem os seus próprios votos e comentários, que são os da sua comunidade, não os de {site}.</p>
<p>O que chega por RSS <b>não entra sozinho no radar</b>. Se alguém o envia para o radar com o botão de cada notícia, a partir daí é mais um envio: votos, δ e ciclo, como todos.</p>

<h2>O que o δ não faz</h2>
<p>Não aprende com o seu comportamento. Não conhece os seus gostos. Não mostra uma capa diferente da do vizinho. Não premia quem tem mais seguidores, porque aqui ninguém tem seguidores. Não olha para o horário em que costuma entrar nem para o tempo que dedica à leitura. A mesma fórmula, os mesmos números, as mesmas capas para todos.</p>

<h2>O que cada pessoa escolhe</h2>
<p>As duas capas e o radar são <b>os mesmos para todos</b>, na mesma ordem. O que muda de uma pessoa para outra é <b>como aparecem</b> e <b>por onde se entra</b>. Para a aparência, escolha no perfil o <b>modelo visual</b> ({skins}, da revista ao fanzine, cada um com versão clara e escura), o <b>tema claro ou escuro</b> e o <b>idioma</b> (espanhol, catalão, basco, galego, inglês, francês, alemão, italiano ou português); uma comunidade pode ter o seu próprio modelo, visível na sua página e nas suas conversas.</p>
<p>Para a entrada, escolha a <b>página de entrada</b> (uma capa, um tema ou uma comunidade; se escolher capa, abre-se a última que viu) e as <b>suas fontes</b>: até {mediamax} meios e {newsmax} newsletters sempre à mão. Isso personaliza o acesso, não o juízo: nada do que se escolhe altera a capa, a ordem de nada nem o δ de nenhum envio. {site} pode ainda ser instalado como aplicação no telefone e tem mensagens privadas entre membros.</p>

<h2>A aura</h2>
<p>As pessoas não têm δ; têm <b>aura</b>, que é outra coisa e mede uma só: o compromisso com a comunidade. Ganha-se com presença e contribuição (+{auraport} por cada envio próprio que chega à capa, +{auraenv} por cada envio ou original, +{auracom} por cada comentário, com um teto de +{auracap} por dia) e perde-se com a ausência ({auradec} por dia, nunca abaixo de {aurastart}). O máximo é {auramax}.</p>
<p>A aura <b>nunca dá megafone</b>: não sobe notícias, não ordena comentários, não pesa no δ. Aparece como uma luz em volta do alias e abre algumas portas de confiança: com mais de {commaura} de aura, é possível criar comunidades (até {commmax}); com mais de {editaura}, corrigir os próprios comentários durante {editmin} minutos após a publicação, e com mais de {seditaura}, os próprios envios durante {seditmin} minutos enquanto não chegarem à capa (o que for corrigido fica marcado como editado). Com {propaura} ou mais, é possível propor meios e newsletters.</p>

<h2>Os números de hoje</h2>
<p>Todos os parâmetros desta página são os que estão em vigor neste momento em {site}; se mudarem, esta página muda com eles. Ciclo a cada {cycle} minutos, alternando Meios e Newsletters · {minage} minutos mínimos no radar · δ mínimo para a capa {minpub} · decaimento de {decay} por meia hora · véu de {gm} minutos · vida na capa de {ttl} horas · expiração no radar às {newttl} horas · comunidades com aura superior a {commaura}, até {commmax} por pessoa.</p>' WHERE slug = 'algoritmo.pt';
