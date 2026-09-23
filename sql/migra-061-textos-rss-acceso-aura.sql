-- Textos de «¿Que es Deltha?» y «Como funciona el δ» (es + ca, eu, gl, en, fr, de, it):
--  * el RSS entra en la definicion (lector de fuentes: medios, newsletters, agregadores) con seccion propia en las dos paginas;
--  * «cambia como lo ves, nunca lo que ves» se matiza: portada comun, pero fuentes elegidas y pagina de entrada personalizan el acceso;
--  * efimeridad precisada: al archivarse, los hilos siguen legibles; lo que termina es votar y comentar;
--  * puertas del aura: comunidades, edicion de comentarios y de envios pasan de «mas de 10» a «mas de 6» (el aura maxima es 10).
-- Marcadores nuevos en page_body(): {mediamax} {newsmax} {propaura}.
-- Sustituye el cuerpo entero de las 16 filas: si se habia retocado alguna a mano en admin > Paginas, volver a aplicar el retoque.
SET NAMES utf8mb4;
UPDATE config SET cvalue = '6' WHERE ckey IN ('comm_min_aura', 'comment_edit_min_aura', 'story_edit_min_aura');
UPDATE pages SET body = '<h1>¿Qué es Deltha?</h1>

<p>{site} es cuatro cosas a la vez: <b>un agregador de noticias</b>, donde la gente envía enlaces y decide con sus votos qué llega a portada; <b>un lector de fuentes</b>, que recoge por RSS lo que publican medios, newsletters y otros agregadores y lo muestra, sin algoritmo, de lo más reciente a lo más antiguo; <b>una red social contralgorítmica</b>, sin máquina que elija por ti qué ver; y <b>un espacio para el debate</b>, con hilos que se abren, se discuten y se cierran.</p>

<h2>De dónde viene</h2>
<p>De cinco sitios. Del <a href="https://contralgoritmia.com/" rel="noopener">manifiesto contralgorítmico</a> viene la idea de fondo: recuperar el criterio humano frente a la hegemonía del algoritmo. De <b>Menéame</b>, el agregador: la comunidad decide qué llega a portada. De <b>X</b>, el diseño: una línea de tiempo limpia y rápida. De <b>4chan</b>, la efimeridad: los hilos nacen, se discuten y se cierran. Y del <b>modelo de negocio de Jot Down</b>, la forma de sostenerse: sin publicidad programática ni rastreo, una comunidad que sostiene lo que lee.</p>

<h2>Cómo funciona</h2>
<p><b>Es efímera, pero no borra.</b> Cuando un envío lleva {ttl} horas en portada, se archiva con su conversación: se puede seguir leyendo, pero ya no se vota ni se comenta. Lo que no llega a portada se archiva a las {newttl} horas de enviarse. Lo que importa es la conversación de ahora, no la que se acumula.</p>
<p><b>No deja huella.</b> Sin cookies de rastreo, sin publicidad programática, sin terceros. Puedes comprobarlo abriendo las herramientas de tu navegador.</p>
<p><b>Es horizontal.</b> Todo se publica bajo un alias y el nombre del autor solo aparece a los {gm} minutos, para que se juzguen las palabras antes que a quien las dice.</p>
<p><b>Se ve por dentro.</b> La sección Procesos enseña en directo la cola, el δ de cada envío y cada voto, comentario y portada según ocurren.</p>
<p><b>Sin algoritmo oculto.</b> Lo que llega a portada lo decide una fórmula pública, corta y visible bajo cada envío: el <b>δ</b>. Está explicada entera en <a href="/algoritmo.php">Cómo funciona el δ</a>.</p>

<h2>Lo que llega por RSS</h2>
<p><b>{site} también lee.</b> Además de lo que envía la gente, recoge por RSS lo que publican medios, newsletters (de Substack y de otros sitios) y otros agregadores, y lo muestra con su propio aspecto, lo último primero. Sin algoritmo y sin rastreo: el RSS es un formato abierto que cualquier web puede ofrecer.</p>
<p><b>Tus fuentes, a mano.</b> En tu perfil eliges hasta {mediamax} medios y {newsmax} newsletters; si no eliges, verás los destacados. Los agregadores, como Menéame, se muestran tal cual, con sus propios votos y comentarios. ¿Echas algo en falta? Con {propaura} de aura o más puedes proponer un medio o una newsletter, y la moderación lo revisa.</p>
<p><b>Leer no es votar.</b> Nada de lo que llega por RSS entra solo en la cola de {site}. Cada noticia tiene un botón para enviarla a Nuevas, y desde ahí sigue el camino de cualquier envío: votos, δ y ciclo.</p>

<h2>El aura</h2>
<p>No hay karma. Hay <b>aura</b>, y mide una sola cosa: tu compromiso con la comunidad. Se gana estando y aportando, no gustando: +{auraport} por cada envío tuyo que llega a portada, +{auraenv} por cada envío o artículo, +{auracom} por cada comentario, como mucho +{auracap} al día. Y baja: cada día pierdes {auradec}, hasta el mínimo de {aurastart}. No se acumula: se sostiene. Mide quién está, no quién estuvo.</p>
<p><b>Nunca da altavoz.</b> El aura no sube noticias ni ordena comentarios. Todo el mundo ve la misma portada, en el mismo orden. Se ve como luz alrededor de tu alias, nada más. Lo único que abre son puertas: con más de {commaura} puedes crear comunidades; con más de {editaura}, corregir tus comentarios recién escritos, y con más de {seditaura}, tus envíos.</p>

<h2>¿Qué se puede publicar?</h2>
<p><b>Todo lo que permite la ley.</b> El límite no lo pone una línea editorial ni un gusto: lo pone la ley. Fuera quedan los insultos, los delitos de odio, las amenazas y todo lo que sea delito. Están escritas en las <a href="/normas.php">normas de uso</a>.</p>
<p><b>Modera una IA que vive en nuestros servidores.</b> Ningún envío ni comentario sale de ellos para ser revisado: no hay terceros mirando lo que escribes. Cuando duda, decide una persona. Cualquiera puede reportar un comentario.</p>
<p><b>Y hay un correo para todo lo demás.</b> Quejas, felicitaciones y propuestas: <b>info@deltha.eu</b>.</p>

<h2>Temas y comunidades</h2>
<p><b>Los temas los pone {site}.</b> Pocos y estables: actualidad, ciencia, cultura… Cada envío lleva uno, y el tema puede dar un pequeño empujón al δ (un coeficiente público).</p>
<p><b>Las comunidades las crea la gente.</b> Con más de {commaura} de aura puedes abrir hasta {commmax}: un nombre, un color y de qué va. Un envío puede sumarse a una o varias, y quien la creó (o un moderador) la cuida. Cada comunidad puede elegir una skin propia, que se aplica a su página y a sus hilos. Y tú puedes elegir una comunidad o un tema como página de entrada.</p>

<h2>A tu manera</h2>
<p><b>{skins} skins.</b> Misma portada, mismos números, distinta piel: de revista, de foro, de terminal, de manga, de fanzine… Se elige en la pestaña Skins, y cada una tiene versión clara y oscura.</p>
<p><b>En tu idioma y en tu bolsillo.</b> Ocho idiomas: español, catalán, euskera, gallego, inglés, francés, alemán e italiano. Se instala como app en el móvil, con mensajes privados y chat entre registrados, y puedes guardar envíos en favoritos.</p>
<p><b>Lo que es de todos y lo que es tuyo.</b> La portada, las candidatas y las nuevas son las mismas para todo el mundo, en el mismo orden. Lo que sí personalizas es el camino: qué medios y newsletters tienes a mano, y si al abrir {site} entras en la portada, en un tema o en una comunidad. Cambia por dónde llegas, no lo que decide la comunidad.</p>

<h2>Fuera de los buscadores</h2>
<p><b>Lo que pasa en {site} se queda en {site}.</b> Ningún envío, comentario ni perfil aparece en Google ni en otros buscadores: todas las páginas llevan la marca <i>noindex</i>. Solo se dejan ver las páginas que explican el proyecto, como esta.</p>
<p><b>Salvo que tú lo quieras.</b> Si escribes un artículo, puedes marcar la casilla para que los buscadores lo indexen. Es tu texto y decides tú; se puede cambiar al editarlo.</p>
<p><b>Y ninguna IA se entrena con lo que escribes.</b> Los rastreadores de inteligencia artificial (los de OpenAI, Anthropic, Google, Meta, Common Crawl y compañía) tienen la entrada vetada, incluso en los artículos abiertos a buscadores.</p>

<h2>¿Quieres ser propietario de {site}?</h2>
<p>{site} es un proyecto abierto a todo el mundo. Para ser copropietario no te pedimos dinero: solo que uses la plataforma. <b>El 50 % de la propiedad de {site} se repartirá entre todos los usuarios registrados que tengan aura {auramax} el {auradl}.</b></p>
<p>¿Quieres probarla? <a href="/registro.php">Regístrate</a> o, si ya tienes cuenta, <a href="/login.php">entra</a>.</p>' WHERE slug = 'que-es';
UPDATE pages SET body = '<h1>Cómo funciona el δ</h1>

<h2>Sin algoritmo oculto</h2>
<p>En {site} no hay una máquina que decida por ti qué ver. Lo que llega a portada lo decide una fórmula pública, corta y visible: se muestra bajo cada envío en Candidatas y Nuevas, con sus números y su valor en ese momento. Esta página la explica entera. Si la entiendes, entiendes {site}.</p>

<h2>El δ (delta)</h2>
<p>Cada envío tiene un <b>δ</b>: una puntuación que resume cuánto lo apoya la comunidad y cuánto tiempo lleva esperando. Se calcula así:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votos positivos − votos negativos) × coeficiente del tema − {decay} × medias horas desde el envío</p>
<p><b>Los votos</b> son la voz de la gente: cada registrado puede votar una vez cada envío, a favor o en contra, y cambiar de opinión. No se puede votar lo propio.</p>
<p><b>El coeficiente del tema</b> es un pequeño empujón editorial y es el único parámetro que pone {site}: la mayoría de los temas valen 1,00; cultura, ciencia o relatos valen 1,10, es decir, un 10 % más por voto. Los coeficientes son públicos y los verás en cada fórmula.</p>
<p><b>La antigüedad</b> resta: {decay} puntos por cada media hora que pasa desde que se envió. Es lo que hace que {site} sea de hoy y no de siempre: una noticia con dos votos que lleva un día en cola vale menos que una recién llegada con los mismos votos. Nada dura para siempre en la cola.</p>

<h2>El ciclo</h2>
<p>Cada <b>{cycle} minutos</b> ocurre un ciclo, siempre igual, sin excepciones ni intervención manual:</p>
<p><b>1. De Nuevas a Candidatas.</b> Entre los envíos que llevan al menos <b>{minage} minutos</b> en Nuevas, asciende <b>uno solo</b>: el de mayor δ, siempre que su δ sea al menos <b>{mincand}</b>. Sin votos no se asciende, y si ningún envío llega, ese ciclo no asciende nada. El tiempo mínimo existe para que un envío no suba solo porque llegó en un momento tranquilo; tiene que ser visto y votado antes. Lo que pasa <b>{newttl} horas</b> sin llegar a portada se archiva: la cola tampoco es para siempre.</p>
<p><b>2. De Candidatas a Portada.</b> Entre todas las candidatas, se publica en portada <b>la de mayor δ</b>, siempre que su δ sea al menos <b>{minpub}</b>. Si ninguna llega, ese ciclo no se publica nada. La portada no se rellena por rellenar.</p>
<p><b>3. El velo se levanta.</b> A los {gm} minutos de publicarse cualquier envío o comentario, el alias de su autor deja paso a su nombre. Antes, todos son alias con un halo: se juzgan las palabras, no la firma.</p>
<p><b>4. Archivo.</b> Lo que lleva {ttl} horas en portada se archiva. Se puede seguir leyendo, pero ya no se vota ni se comenta: la conversación fue lo que fue.</p>
<p>La sección <b>Procesos</b> muestra todo esto en directo: la cola ordenada por δ con una estimación de cuándo asciende cada envío, y un flujo con cada envío, voto, comentario y portada según ocurren.</p>

<h2>Temas y comunidades</h2>
<p>Cada envío se clasifica dos veces. <b>El tema</b> lo pone {site}: son pocos, estables (actualidad, ciencia, cultura…) y cada uno tiene su coeficiente público. Todo envío lleva un tema.</p>
<p><b>La comunidad</b> la pone la gente. Cualquiera con más de <b>{commaura} de aura</b> puede crear hasta <b>{commmax}</b>: un nombre, un color y una frase que diga de qué va. Un envío puede sumarse a una comunidad además de a su tema, y así se agrupa lo que a un grupo le interesa sin que {site} tenga que decidirlo. Quien crea una comunidad la cuida: edita su nombre, su descripción, su color y su plantilla visual; los moderadores pueden hacer lo mismo y, si hace falta, ocultarla.</p>
<p>Las comunidades <b>no tocan el δ</b>: no dan coeficiente, no suben ni bajan nada. Son un lugar, no un altavoz. Lo que sí puedes hacer es elegir una comunidad (o un tema) como <b>página de entrada</b> en tu perfil: al abrir {site} llegarás allí, y la pestaña Portada seguirá siendo la portada de todos.</p>

<h2>Lo que llega por RSS</h2>
<p>{site} también recoge por RSS lo que publican medios, newsletters y otros agregadores. Ahí <b>no hay δ ni ciclo</b>: cada fuente se muestra por orden de publicación, lo último primero, sin ordenar por gustos ni por éxito. En los agregadores se ven sus propios votos y comentarios, que son los de su comunidad, no los de {site}.</p>
<p>Lo que llega por RSS <b>no entra solo en la cola</b>. Si alguien lo envía a Nuevas con el botón de cada noticia, a partir de ahí es un envío más: votos, δ y ciclo, como todos.</p>

<h2>Lo que el δ no hace</h2>
<p>No aprende de ti. No sabe qué te gusta. No te enseña una portada distinta que a tu vecino. No premia a quien tiene más seguidores, porque aquí nadie tiene seguidores. No mira la hora a la que sueles entrar ni cuánto tiempo pasas leyendo. La misma fórmula, los mismos números, la misma portada para todos.</p>

<h2>Lo que sí eliges tú</h2>
<p>La portada, las candidatas y las nuevas son <b>las mismas para todos</b>, en el mismo orden. Lo que sí cambia de una persona a otra es <b>cómo se ven</b> y <b>por dónde entras</b>. Para cómo se ven, en tu perfil eliges la <b>plantilla visual</b> ({skins}, de revista a fanzine, cada una con versión clara y oscura), el <b>tema claro u oscuro</b> y el <b>idioma</b> (español, catalán, euskera, gallego, inglés, francés, alemán o italiano); una comunidad puede tener su propia plantilla, que verás en su página y en sus hilos.</p>
<p>Para por dónde entras, eliges tu <b>página de entrada</b> (la portada, un tema o una comunidad) y <b>tus fuentes</b>: hasta {mediamax} medios y {newsmax} newsletters siempre a mano. Eso personaliza el acceso, no el juicio: nada de lo que eliges altera la portada, el orden de nada ni el δ de ningún envío. {site} se instala además como aplicación en el móvil y tiene mensajes privados entre registrados.</p>

<h2>El aura</h2>
<p>Las personas no tienen δ; tienen <b>aura</b>, que es otra cosa y mide una sola: el compromiso con la comunidad. Se gana estando y aportando (+{auraport} por cada envío tuyo que llega a portada, +{auraenv} por cada envío o artículo, +{auracom} por cada comentario, con un tope de +{auracap} al día) y se pierde no estando ({auradec} cada día, nunca por debajo de {aurastart}). El máximo es {auramax}.</p>
<p>El aura <b>nunca da altavoz</b>: no sube noticias, no ordena comentarios, no pesa en el δ. Se ve como luz alrededor de tu alias, y abre algunas puertas de confianza: con más de {commaura} de aura puedes crear comunidades (hasta {commmax}); con más de {editaura}, corregir tus propios comentarios durante {editmin} minutos tras publicarlos, y con más de {seditaura}, tus envíos durante {seditmin} minutos mientras no lleguen a portada (lo corregido queda marcado como editado). Con {propaura} o más puedes proponer medios y newsletters.</p>

<h2>Los números de hoy</h2>
<p>Todos los parámetros de esta página son los que están en vigor ahora mismo en {site}; si el equipo los cambia, esta página cambia con ellos. Ciclo cada {cycle} minutos · {minage} minutos mínimos en Nuevas · δ mínimo para portada {minpub} · decaimiento {decay} por media hora · velo de {gm} minutos · δ mínimo para ser candidata {mincand} · vida en portada de {ttl} horas · caducidad sin portada a las {newttl} horas · comunidades con aura mayor que {commaura}, hasta {commmax} por persona.</p>' WHERE slug = 'algoritmo';
UPDATE pages SET body = '<h1>What is Deltha?</h1>

<p>{site} is four things at once: <b>a news aggregator</b>, where people submit links and their votes decide what makes the front page; <b>a feed reader</b>, which picks up via RSS what media outlets, newsletters and other aggregators publish and shows it, with no algorithm, newest first; <b>a counter-algorithmic social network</b>, with no machine choosing what you see; and <b>a place for debate</b>, with threads that open, get argued over and then close.</p>

<h2>Where it comes from</h2>
<p>From five places. From the <a href="https://contralgoritmia.com/" rel="noopener">counter-algorithmic manifesto</a> comes the core idea: reclaiming human judgement from the hegemony of the algorithm. From <b>Menéame</b>, the aggregator: the community decides what makes the front page. From <b>X</b>, the design: a clean, fast timeline. From <b>4chan</b>, the ephemerality: threads are born, argued over and then closed. And from <b>Jot Down''s business model</b>, the way to stay afloat: no programmatic advertising, no tracking, just a community that supports what it reads.</p>

<h2>How it works</h2>
<p><b>It''s ephemeral, but it doesn''t delete.</b> Once a post has been on the front page for {ttl} hours, it is archived along with its conversation: you can still read it, but you can no longer vote or comment. Posts that never make the front page are archived {newttl} hours after being submitted. What matters is the conversation happening now, not what piles up.</p>
<p><b>It leaves no trace.</b> No tracking cookies, no programmatic advertising, no third parties. You can check for yourself with your browser''s developer tools.</p>
<p><b>It''s horizontal.</b> Everything is published under an alias, and the author''s name only appears after {gm} minutes, so the words are judged before the person saying them.</p>
<p><b>You can see inside it.</b> The Processes section shows the queue live, the δ of every post, and every vote, comment and front-page story as it happens.</p>
<p><b>No hidden algorithm.</b> What makes the front page is decided by a public formula, short and visible under every post: the <b>δ</b>. It''s explained in full in <a href="/algoritmo.php">How δ works</a>.</p>

<h2>What comes in via RSS</h2>
<p><b>{site} reads too.</b> Besides what people submit, it picks up via RSS what media outlets, newsletters (on Substack and elsewhere) and other aggregators publish, and shows it in its own look, newest first. No algorithm and no tracking: RSS is an open format any website can offer.</p>
<p><b>Your sources, close at hand.</b> In your profile you choose up to {mediamax} media outlets and {newsmax} newsletters; if you don''t choose, you''ll see the featured ones. Aggregators such as Menéame are shown as they are, with their own votes and comments. Missing something? With {propaura} aura or more you can suggest an outlet or a newsletter, and the moderators review it.</p>
<p><b>Reading isn''t voting.</b> Nothing that comes in via RSS enters the {site} queue by itself. Every story has a button to send it to New, and from there it follows the same path as any other post: votes, δ and cycle.</p>

<h2>Aura</h2>
<p>There''s no karma. There''s <b>aura</b>, and it measures just one thing: your commitment to the community. You earn it by being here and contributing, not by being liked: +{auraport} for each of your posts that makes the front page, +{auraenv} for each post or article, +{auracom} for each comment, up to +{auracap} a day. And it goes down: every day you lose {auradec}, down to a minimum of {aurastart}. It doesn''t pile up: it has to be kept up. It measures who''s here, not who used to be.</p>
<p><b>It never gives you a megaphone.</b> Aura doesn''t push stories up or rank comments. Everyone sees the same front page, in the same order. It shows as a glow around your alias, nothing more. All it does is open doors: with more than {commaura} you can create communities; with more than {editaura}, correct comments you''ve just written; and with more than {seditaura}, your posts.</p>

<h2>What can you post?</h2>
<p><b>Anything the law allows.</b> The limit isn''t set by an editorial line or anyone''s taste: it''s set by the law. Insults, hate crimes, threats and anything else that''s a crime are out. It''s all written down in the <a href="/normas.php">rules of use</a>.</p>
<p><b>Moderation is done by an AI that lives on our servers.</b> No post or comment leaves them to be reviewed: there are no third parties looking at what you write. When it''s unsure, a person decides. Anyone can report a comment.</p>
<p><b>And there''s an email address for everything else.</b> Complaints, compliments and suggestions: <b>info@deltha.eu</b>.</p>

<h2>Topics and communities</h2>
<p><b>Topics are set by {site}.</b> Few and stable: current affairs, science, culture… Every post has one, and the topic can give the δ a small nudge (a public coefficient).</p>
<p><b>Communities are created by people.</b> With more than {commaura} aura you can start up to {commmax}: a name, a colour and what it''s about. A post can join one or several, and whoever created the community (or a moderator) looks after it. Each community can pick its own skin, applied to its page and its threads. And you can choose a community or a topic as your landing page.</p>

<h2>Your way</h2>
<p><b>{skins} skins.</b> Same front page, same numbers, different skin: magazine, forum, terminal, manga, fanzine… You pick one in the Skins tab, and each comes in a light and a dark version.</p>
<p><b>In your language and in your pocket.</b> Eight languages: Spanish, Catalan, Basque, Galician, English, French, German and Italian. It installs as an app on your phone, with private messages and chat between registered users, and you can save posts to your favourites.</p>
<p><b>What''s everyone''s and what''s yours.</b> The front page, Candidates and New are the same for everyone, in the same order. What you do personalise is the way in: which outlets and newsletters you keep close at hand, and whether opening {site} takes you to the front page, a topic or a community. It changes how you get there, not what the community decides.</p>

<h2>Off the search engines</h2>
<p><b>What happens on {site} stays on {site}.</b> No post, comment or profile shows up on Google or any other search engine: every page carries the <i>noindex</i> tag. Only the pages that explain the project, like this one, are visible.</p>
<p><b>Unless you want it to.</b> If you write an article, you can tick the box to let search engines index it. It''s your text and your call; you can change it when you edit it.</p>
<p><b>And no AI trains on what you write.</b> Artificial intelligence crawlers (from OpenAI, Anthropic, Google, Meta, Common Crawl and the like) are barred at the door, even from articles open to search engines.</p>

<h2>Want to own {site}?</h2>
<p>{site} is a project open to everyone. To become a co-owner we don''t ask you for money: just that you use the platform. <b>50% of the ownership of {site} will be shared among all registered users who have {auramax} aura on {auradl}.</b></p>
<p>Want to try it? <a href="/registro.php">Sign up</a> or, if you already have an account, <a href="/login.php">log in</a>.</p>' WHERE slug = 'que-es.en';
UPDATE pages SET body = '<h1>How δ works</h1>

<h2>No hidden algorithm</h2>
<p>On {site} there''s no machine deciding what you see. What makes the front page is decided by a public formula, short and visible: it''s shown under every post in Candidates and New, with its numbers and its value at that moment. This page explains it in full. If you understand it, you understand {site}.</p>

<h2>The δ (delta)</h2>
<p>Every post has a <b>δ</b>: a score that sums up how much the community backs it and how long it has been waiting. It''s calculated like this:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (upvotes − downvotes) × topic coefficient − {decay} × half-hours since posting</p>
<p><b>Votes</b> are the people''s voice: every registered user can vote once on each post, for or against, and change their mind. You can''t vote on your own.</p>
<p><b>The topic coefficient</b> is a small editorial nudge and the only parameter set by {site}: most topics are worth 1.00; culture, science or fiction are worth 1.10, that is, 10% more per vote. The coefficients are public and you''ll see them in every formula.</p>
<p><b>Age</b> subtracts: {decay} points for every half hour since it was posted. That''s what makes {site} about today, not forever: a story with two votes that has been in the queue for a day is worth less than a fresh one with the same votes. Nothing lasts forever in the queue.</p>

<h2>The cycle</h2>
<p>Every <b>{cycle} minutes</b> a cycle runs, always the same, with no exceptions and no manual intervention:</p>
<p><b>1. From New to Candidates.</b> Among the posts that have spent at least <b>{minage} minutes</b> in New, <b>just one</b> moves up: the one with the highest δ, as long as its δ is at least <b>{mincand}</b>. No votes, no promotion, and if no post qualifies, nothing moves up that cycle. The minimum time exists so a post doesn''t rise just because it arrived at a quiet moment; it has to be seen and voted on first. Anything that goes <b>{newttl} hours</b> without making the front page is archived: the queue isn''t forever either.</p>
<p><b>2. From Candidates to Front page.</b> Of all the candidates, <b>the one with the highest δ</b> goes to the front page, as long as its δ is at least <b>{minpub}</b>. If none qualifies, nothing is published that cycle. The front page isn''t filled just for the sake of it.</p>
<p><b>3. The veil lifts.</b> {gm} minutes after any post or comment is published, its author''s alias gives way to their name. Until then, everyone is an alias with a halo: the words are judged, not the signature.</p>
<p><b>4. Archive.</b> Anything that has been on the front page for {ttl} hours is archived. You can still read it, but you can no longer vote or comment: the conversation was what it was.</p>
<p>The <b>Processes</b> section shows all this live: the queue sorted by δ with an estimate of when each post will move up, and a feed of every post, vote, comment and front-page story as it happens.</p>

<h2>Topics and communities</h2>
<p>Every post is classified twice. <b>The topic</b> is set by {site}: there are few of them, they''re stable (current affairs, science, culture…) and each has its own public coefficient. Every post has a topic.</p>
<p><b>The community</b> is set by people. Anyone with more than <b>{commaura} aura</b> can create up to <b>{commmax}</b>: a name, a colour and a sentence saying what it''s about. A post can join a community as well as its topic, so whatever interests a group gets gathered together without {site} having to decide it. Whoever creates a community looks after it: they edit its name, description, colour and visual template; moderators can do the same and, if needed, hide it.</p>
<p>Communities <b>don''t touch the δ</b>: they give no coefficient, they don''t push anything up or down. They''re a place, not a megaphone. What you can do is choose a community (or a topic) as your <b>landing page</b> in your profile: when you open {site} you''ll land there, and the Front page tab will still be everyone''s front page.</p>

<h2>What comes in via RSS</h2>
<p>{site} also picks up via RSS what media outlets, newsletters and other aggregators publish. There, <b>there is no δ and no cycle</b>: each source is shown in order of publication, newest first, not sorted by taste or by success. Aggregators show their own votes and comments, which belong to their community, not to {site}.</p>
<p>What comes in via RSS <b>doesn''t enter the queue by itself</b>. If someone sends it to New with the button on each story, from then on it''s just another post: votes, δ and cycle, like all the rest.</p>

<h2>What δ doesn''t do</h2>
<p>It doesn''t learn from you. It doesn''t know what you like. It doesn''t show you a different front page from your neighbour''s. It doesn''t reward whoever has the most followers, because nobody here has followers. It doesn''t track what time you usually drop by or how long you spend reading. The same formula, the same numbers, the same front page for everyone.</p>

<h2>What you do choose</h2>
<p>The front page, Candidates and New are <b>the same for everyone</b>, in the same order. What does change from one person to another is <b>how things look</b> and <b>how you get in</b>. For how things look, in your profile you choose the <b>visual template</b> ({skins} of them, from magazine to fanzine, each with a light and a dark version), the <b>light or dark theme</b> and the <b>language</b> (Spanish, Catalan, Basque, Galician, English, French, German or Italian); a community can have its own template, which you''ll see on its page and in its threads.</p>
<p>For how you get in, you choose your <b>landing page</b> (the front page, a topic or a community) and <b>your sources</b>: up to {mediamax} media outlets and {newsmax} newsletters always close at hand. That personalises access, not judgement: nothing you choose alters the front page, the order of anything or the δ of any post. {site} also installs as an app on your phone and has private messages between registered users.</p>

<h2>Aura</h2>
<p>People don''t have a δ; they have <b>aura</b>, which is something else and measures just one thing: commitment to the community. You earn it by being here and contributing (+{auraport} for each of your posts that makes the front page, +{auraenv} for each post or article, +{auracom} for each comment, capped at +{auracap} a day) and you lose it by not being here ({auradec} every day, never below {aurastart}). The maximum is {auramax}.</p>
<p>Aura <b>never gives you a megaphone</b>: it doesn''t push stories up, doesn''t rank comments, doesn''t weigh on the δ. It shows as a glow around your alias, and it opens a few doors of trust: with more than {commaura} aura you can create communities (up to {commmax}); with more than {editaura}, correct your own comments for {editmin} minutes after posting them; and with more than {seditaura}, edit your posts for {seditmin} minutes as long as they haven''t made the front page (anything corrected is marked as edited). With {propaura} or more you can suggest media outlets and newsletters.</p>

<h2>Today''s numbers</h2>
<p>All the parameters on this page are the ones in force right now on {site}; if the team changes them, this page changes with them. Cycle every {cycle} minutes · minimum {minage} minutes in New · minimum δ for the front page {minpub} · decay {decay} per half hour · {gm}-minute veil · minimum δ to become a candidate {mincand} · {ttl} hours of life on the front page · expiry without reaching the front page after {newttl} hours · communities for aura above {commaura}, up to {commmax} per person.</p>' WHERE slug = 'algoritmo.en';
UPDATE pages SET body = '<h1>Què és Deltha?</h1>

<p>{site} és quatre coses alhora: <b>un agregador de notícies</b>, on la gent envia enllaços i decideix amb els seus vots què arriba a portada; <b>un lector de fonts</b>, que recull per RSS el que publiquen mitjans, butlletins i altres agregadors i ho mostra, sense algorisme, del més nou al més antic; <b>una xarxa social contraalgorítmica</b>, sense cap màquina que triï per tu què has de veure; i <b>un espai per al debat</b>, amb fils que s''obren, es discuteixen i es tanquen.</p>

<h2>D''on ve</h2>
<p>De cinc llocs. Del <a href="https://contralgoritmia.com/" rel="noopener">manifest contraalgorítmic</a> ve la idea de fons: recuperar el criteri humà davant l''hegemonia de l''algorisme. De <b>Menéame</b>, l''agregador: la comunitat decideix què arriba a portada. De <b>X</b>, el disseny: una línia de temps neta i ràpida. De <b>4chan</b>, l''efimeritat: els fils neixen, es discuteixen i es tanquen. I del <b>model de negoci de Jot Down</b>, la manera de sostenir-se: sense publicitat programàtica ni rastreig, una comunitat que sosté el que llegeix.</p>

<h2>Com funciona</h2>
<p><b>És efímera, però no esborra.</b> Quan un enviament fa {ttl} hores que és a portada, s''arxiva amb la seva conversa: es pot continuar llegint, però ja no s''hi vota ni s''hi comenta. El que no arriba a portada s''arxiva {newttl} hores després d''enviar-se. El que importa és la conversa d''ara, no la que s''acumula.</p>
<p><b>No deixa rastre.</b> Sense galetes de rastreig, sense publicitat programàtica, sense tercers. Ho pots comprovar obrint les eines del teu navegador.</p>
<p><b>És horitzontal.</b> Tot es publica sota un àlies i el nom de l''autor només apareix al cap de {gm} minuts, perquè es jutgin les paraules abans que qui les diu.</p>
<p><b>Es veu per dins.</b> La secció Processos mostra en directe la cua, el δ de cada enviament i cada vot, comentari i portada a mesura que passen.</p>
<p><b>Sense algorisme ocult.</b> El que arriba a portada ho decideix una fórmula pública, curta i visible sota cada enviament: el <b>δ</b>. Està explicada sencera a <a href="/algoritmo.php">Com funciona el δ</a>.</p>

<h2>El que arriba per RSS</h2>
<p><b>{site} també llegeix.</b> A més del que envia la gent, recull per RSS el que publiquen mitjans, butlletins (de Substack i d''altres llocs) i altres agregadors, i ho mostra amb el seu propi aspecte, el més nou primer. Sense algorisme i sense rastreig: l''RSS és un format obert que qualsevol web pot oferir.</p>
<p><b>Les teves fonts, a mà.</b> Al teu perfil tries fins a {mediamax} mitjans i {newsmax} butlletins; si no en tries, veuràs els destacats. Els agregadors, com Menéame, es mostren tal com són, amb els seus propis vots i comentaris. Hi trobes a faltar alguna cosa? Amb {propaura} d''aura o més pots proposar un mitjà o un butlletí, i la moderació el revisa.</p>
<p><b>Llegir no és votar.</b> Res del que arriba per RSS no entra sol a la cua de {site}. Cada notícia té un botó per enviar-la a Noves, i des d''allà segueix el camí de qualsevol enviament: vots, δ i cicle.</p>

<h2>L''aura</h2>
<p>No hi ha karma. Hi ha <b>aura</b>, i mesura una sola cosa: el teu compromís amb la comunitat. Es guanya estant-hi i aportant, no agradant: +{auraport} per cada enviament teu que arriba a portada, +{auraenv} per cada enviament o article, +{auracom} per cada comentari, com a màxim +{auracap} al dia. I baixa: cada dia en perds {auradec}, fins al mínim de {aurastart}. No s''acumula: se sosté. Mesura qui hi és, no qui hi era.</p>
<p><b>Mai no dona altaveu.</b> L''aura no puja notícies ni ordena comentaris. Tothom veu la mateixa portada, en el mateix ordre. Es veu com una llum al voltant del teu àlies, res més. L''únic que obre són portes: amb més de {commaura} pots crear comunitats; amb més de {editaura}, corregir els comentaris que acabes d''escriure, i amb més de {seditaura}, els teus enviaments.</p>

<h2>Què s''hi pot publicar?</h2>
<p><b>Tot el que permet la llei.</b> El límit no el posa una línia editorial ni un gust: el posa la llei. En queden fora els insults, els delictes d''odi, les amenaces i tot el que sigui delicte. Estan escrites a les <a href="/normas.php">normes d''ús</a>.</p>
<p><b>Modera una IA que viu als nostres servidors.</b> Cap enviament ni comentari no en surt per ser revisat: no hi ha tercers mirant el que escrius. Quan dubta, decideix una persona. Qualsevol pot denunciar un comentari.</p>
<p><b>I hi ha un correu per a tota la resta.</b> Queixes, felicitacions i propostes: <b>info@deltha.eu</b>.</p>

<h2>Temes i comunitats</h2>
<p><b>Els temes els posa {site}.</b> Pocs i estables: actualitat, ciència, cultura… Cada enviament en porta un, i el tema pot donar una petita empenta al δ (un coeficient públic).</p>
<p><b>Les comunitats les crea la gent.</b> Amb més de {commaura} d''aura en pots obrir fins a {commmax}: un nom, un color i de què va. Un enviament pot sumar-se a una o a diverses, i qui la va crear (o un moderador) en té cura. Cada comunitat pot triar una plantilla pròpia, que s''aplica a la seva pàgina i als seus fils. I tu pots triar una comunitat o un tema com a pàgina d''entrada.</p>

<h2>A la teva manera</h2>
<p><b>{skins} plantilles.</b> La mateixa portada, els mateixos números, una pell diferent: de revista, de fòrum, de terminal, de manga, de fanzín… Es tria a la pestanya Plantilla, i cadascuna té versió clara i fosca.</p>
<p><b>En la teva llengua i a la butxaca.</b> Vuit idiomes: castellà, català, basc, gallec, anglès, francès, alemany i italià. S''instal·la com a aplicació al mòbil, amb missatges privats i xat entre registrats, i pots desar enviaments a favorits.</p>
<p><b>El que és de tothom i el que és teu.</b> La portada, les candidates i les noves són les mateixes per a tothom, en el mateix ordre. El que sí que personalitzes és el camí: quins mitjans i butlletins tens a mà, i si en obrir {site} entres a la portada, en un tema o en una comunitat. Canvia per on arribes, no el que decideix la comunitat.</p>

<h2>Fora dels cercadors</h2>
<p><b>El que passa a {site} es queda a {site}.</b> Cap enviament, comentari ni perfil no apareix a Google ni a altres cercadors: totes les pàgines porten la marca <i>noindex</i>. Només es deixen veure les pàgines que expliquen el projecte, com aquesta.</p>
<p><b>Llevat que tu ho vulguis.</b> Si escrius un article, pots marcar la casella perquè els cercadors l''indexin. És el teu text i hi decideixes tu; es pot canviar en editar-lo.</p>
<p><b>I cap IA no s''entrena amb el que escrius.</b> Els rastrejadors d''intel·ligència artificial (els d''OpenAI, Anthropic, Google, Meta, Common Crawl i companyia) tenen l''entrada vetada, fins i tot als articles oberts als cercadors.</p>

<h2>Vols ser propietari de {site}?</h2>
<p>{site} és un projecte obert a tothom. Per ser-ne copropietari no et demanem diners: només que facis servir la plataforma. <b>El 50 % de la propietat de {site} es repartirà entre tots els usuaris registrats que tinguin aura {auramax} el {auradl}.</b></p>
<p>Vols provar-la? <a href="/registro.php">Registra''t</a> o, si ja tens compte, <a href="/login.php">entra</a>.</p>' WHERE slug = 'que-es.ca';
UPDATE pages SET body = '<h1>Com funciona el δ</h1>

<h2>Sense algorisme ocult</h2>
<p>A {site} no hi ha cap màquina que decideixi per tu què has de veure. El que arriba a portada ho decideix una fórmula pública, curta i visible: es mostra sota cada enviament a Candidates i Noves, amb els seus números i el seu valor en aquell moment. Aquesta pàgina l''explica sencera. Si l''entens, entens {site}.</p>

<h2>El δ (delta)</h2>
<p>Cada enviament té un <b>δ</b>: una puntuació que resumeix quant el recolza la comunitat i quant de temps fa que espera. Es calcula així:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (vots positius − vots negatius) × coeficient del tema − {decay} × mitges hores des de l''enviament</p>
<p><b>Els vots</b> són la veu de la gent: cada registrat pot votar una vegada cada enviament, a favor o en contra, i canviar d''opinió. No es pot votar allò que és propi.</p>
<p><b>El coeficient del tema</b> és una petita empenta editorial i és l''únic paràmetre que posa {site}: la majoria dels temes valen 1,00; cultura, ciència o relats valen 1,10, és a dir, un 10 % més per vot. Els coeficients són públics i els veuràs a cada fórmula.</p>
<p><b>L''antiguitat</b> resta: {decay} punts per cada mitja hora que passa des que es va enviar. És el que fa que {site} sigui d''avui i no de sempre: una notícia amb dos vots que fa un dia que és a la cua val menys que una d''acabada d''arribar amb els mateixos vots. Res no dura per sempre a la cua.</p>

<h2>El cicle</h2>
<p>Cada <b>{cycle} minuts</b> hi ha un cicle, sempre igual, sense excepcions ni intervenció manual:</p>
<p><b>1. De Noves a Candidates.</b> D''entre els enviaments que fa almenys <b>{minage} minuts</b> que són a Noves, en puja <b>només un</b>: el de δ més alt, sempre que el seu δ sigui almenys <b>{mincand}</b>. Sense vots no es puja, i si cap enviament no hi arriba, aquell cicle no puja res. El temps mínim existeix perquè un enviament no pugi només perquè ha arribat en un moment tranquil; primer l''han de veure i votar. El que passa <b>{newttl} hores</b> sense arribar a portada s''arxiva: la cua tampoc no és per sempre.</p>
<p><b>2. De Candidates a Portada.</b> D''entre totes les candidates, es publica a portada <b>la de δ més alt</b>, sempre que el seu δ sigui almenys <b>{minpub}</b>. Si cap no hi arriba, aquell cicle no es publica res. La portada no s''omple per omplir-la.</p>
<p><b>3. El vel s''aixeca.</b> Al cap de {gm} minuts de publicar-se qualsevol enviament o comentari, l''àlies del seu autor deixa pas al seu nom. Abans, tots són àlies amb un halo: es jutgen les paraules, no la signatura.</p>
<p><b>4. Arxiu.</b> El que fa {ttl} hores que és a portada s''arxiva. Es pot continuar llegint, però ja no es vota ni es comenta: la conversa va ser el que va ser.</p>
<p>La secció <b>Processos</b> mostra tot això en directe: la cua ordenada per δ amb una estimació de quan pujarà cada enviament, i un flux amb cada enviament, vot, comentari i portada a mesura que passen.</p>

<h2>Temes i comunitats</h2>
<p>Cada enviament es classifica dues vegades. <b>El tema</b> el posa {site}: són pocs, estables (actualitat, ciència, cultura…) i cadascun té el seu coeficient públic. Tot enviament porta un tema.</p>
<p><b>La comunitat</b> la posa la gent. Qualsevol amb més de <b>{commaura} d''aura</b> en pot crear fins a <b>{commmax}</b>: un nom, un color i una frase que digui de què va. Un enviament pot sumar-se a una comunitat a més del seu tema, i així s''agrupa el que interessa a un grup sense que {site} ho hagi de decidir. Qui crea una comunitat en té cura: n''edita el nom, la descripció, el color i la plantilla visual; els moderadors poden fer el mateix i, si cal, amagar-la.</p>
<p>Les comunitats <b>no toquen el δ</b>: no donen coeficient, no pugen ni baixen res. Són un lloc, no un altaveu. El que sí que pots fer és triar una comunitat (o un tema) com a <b>pàgina d''entrada</b> al teu perfil: quan obris {site} hi arribaràs, i la pestanya Portada continuarà sent la portada de tothom.</p>

<h2>El que arriba per RSS</h2>
<p>{site} també recull per RSS el que publiquen mitjans, butlletins i altres agregadors. Allà <b>no hi ha δ ni cicle</b>: cada font es mostra per ordre de publicació, el més nou primer, sense ordenar per gustos ni per èxit. Als agregadors s''hi veuen els seus propis vots i comentaris, que són els de la seva comunitat, no els de {site}.</p>
<p>El que arriba per RSS <b>no entra sol a la cua</b>. Si algú l''envia a Noves amb el botó de cada notícia, a partir d''aquí és un enviament més: vots, δ i cicle, com tots.</p>

<h2>El que el δ no fa</h2>
<p>No aprèn de tu. No sap què t''agrada. No et mostra una portada diferent de la del teu veí. No premia qui té més seguidors, perquè aquí ningú no té seguidors. No mira a quina hora acostumes a entrar ni quant de temps passes llegint. La mateixa fórmula, els mateixos números, la mateixa portada per a tothom.</p>

<h2>El que sí que tries tu</h2>
<p>La portada, les candidates i les noves són <b>les mateixes per a tothom</b>, en el mateix ordre. El que sí que canvia d''una persona a una altra és <b>com es veuen</b> i <b>per on entres</b>. Per a com es veuen, al teu perfil tries la <b>plantilla visual</b> ({skins}, de revista a fanzín, cadascuna amb versió clara i fosca), el <b>tema clar o fosc</b> i l''<b>idioma</b> (castellà, català, basc, gallec, anglès, francès, alemany o italià); una comunitat pot tenir la seva pròpia plantilla, que veuràs a la seva pàgina i als seus fils.</p>
<p>Per a per on entres, tries la teva <b>pàgina d''entrada</b> (la portada, un tema o una comunitat) i <b>les teves fonts</b>: fins a {mediamax} mitjans i {newsmax} butlletins sempre a mà. Això personalitza l''accés, no el judici: res del que tries no altera la portada, l''ordre de res ni el δ de cap enviament. A més, {site} s''instal·la com a aplicació al mòbil i té missatges privats entre registrats.</p>

<h2>L''aura</h2>
<p>Les persones no tenen δ; tenen <b>aura</b>, que és una altra cosa i en mesura una de sola: el compromís amb la comunitat. Es guanya estant-hi i aportant (+{auraport} per cada enviament teu que arriba a portada, +{auraenv} per cada enviament o article, +{auracom} per cada comentari, amb un límit de +{auracap} al dia) i es perd no estant-hi ({auradec} cada dia, mai per sota de {aurastart}). El màxim és {auramax}.</p>
<p>L''aura <b>mai no dona altaveu</b>: no puja notícies, no ordena comentaris, no pesa en el δ. Es veu com una llum al voltant del teu àlies, i obre algunes portes de confiança: amb més de {commaura} d''aura pots crear comunitats (fins a {commmax}); amb més de {editaura}, corregir els teus propis comentaris durant {editmin} minuts després de publicar-los, i amb més de {seditaura}, els teus enviaments durant {seditmin} minuts mentre no arribin a portada (el que es corregeix queda marcat com a editat). Amb {propaura} o més pots proposar mitjans i butlletins.</p>

<h2>Els números d''avui</h2>
<p>Tots els paràmetres d''aquesta pàgina són els que estan en vigor ara mateix a {site}; si l''equip els canvia, aquesta pàgina canvia amb ells. Cicle cada {cycle} minuts · {minage} minuts mínims a Noves · δ mínim per a portada {minpub} · decaïment {decay} per mitja hora · vel de {gm} minuts · δ mínim per ser candidata {mincand} · vida a portada de {ttl} hores · caducitat sense portada al cap de {newttl} hores · comunitats amb aura superior a {commaura}, fins a {commmax} per persona.</p>' WHERE slug = 'algoritmo.ca';
UPDATE pages SET body = '<h1>Zer da Deltha?</h1>

<p>{site} lau gauza da aldi berean: <b>albiste-agregatzaile bat</b>, non jendeak estekak bidaltzen dituen eta botoekin erabakitzen duen zer iristen den azalera; <b>iturri-irakurle bat</b>, hedabideek, buletinek eta beste agregatzaile batzuek argitaratzen dutena RSS bidez jaso eta algoritmorik gabe erakusten duena, berrienetik hasita; <b>sare sozial kontralgoritmiko bat</b>, zer ikusi behar duzun zure ordez aukeratzen duen makinarik gabe; eta <b>eztabaidarako gune bat</b>, ireki, eztabaidatu eta itxi egiten diren hariekin.</p>

<h2>Nondik dator</h2>
<p>Bost gunetatik. <a href="https://contralgoritmia.com/" rel="noopener">Manifestu kontralgoritmikotik</a> dator funtsezko ideia: giza irizpidea berreskuratzea algoritmoaren hegemoniaren aurrean. <b>Menéame</b>tik, agregatzailea: komunitateak erabakitzen du zer iristen den azalera. <b>X</b> saretik, diseinua: denbora-lerro garbi eta azkarra. <b>4chan</b>etik, iragankortasuna: hariak sortu, eztabaidatu eta itxi egiten dira. Eta <b>Jot Downen negozio-eredutik</b>, bizirauteko modua: publizitate programatikorik eta jarraipenik gabe, irakurtzen duenari eusten dion komunitate bat.</p>

<h2>Nola funtzionatzen du</h2>
<p><b>Iragankorra da, baina ez du ezer ezabatzen.</b> Bidalketa batek {ttl} ordu azalean daramatzanean, bere elkarrizketarekin batera artxibatzen da: irakurtzen jarrai daiteke, baina ezin da gehiago bozkatu ez iruzkindu. Azalera iristen ez dena bidali eta {newttl} ordura artxibatzen da. Orain gertatzen den elkarrizketa da garrantzitsua, ez metatzen dena.</p>
<p><b>Ez du arrastorik uzten.</b> Jarraipen-cookierik gabe, publizitate programatikorik gabe, hirugarrenik gabe. Zeuk egiazta dezakezu, nabigatzailearen tresnak irekita.</p>
<p><b>Horizontala da.</b> Dena ezizen batekin argitaratzen da, eta egilearen izena {gm} minuturen buruan baino ez da agertzen: lehenik hitzak epaitzen dira, gero esaten dituena.</p>
<p><b>Barrutik ikusten da.</b> Prozesuak atalak zuzenean erakusten ditu ilara, bidalketa bakoitzaren δ-a eta boto, iruzkin eta azal-igoera bakoitza, gertatu ahala.</p>
<p><b>Ezkutuko algoritmorik gabe.</b> Azalera zer iristen den formula publiko, labur eta ikusgai batek erabakitzen du, bidalketa bakoitzaren azpian: <b>δ</b>-k. Osorik azalduta dago hemen: <a href="/algoritmo.php">Nola funtzionatzen duen δ-k</a>.</p>

<h2>RSS bidez iristen dena</h2>
<p><b>{site}-k irakurri ere egiten du.</b> Jendeak bidaltzen duenaz gain, hedabideek, buletinek (Substack-ekoek eta beste batzuek) eta beste agregatzaile batzuek argitaratzen dutena jasotzen du RSS bidez, eta bere itxurarekin erakusten du, berriena lehenengo. Algoritmorik eta jarraipenik gabe: RSS edozein webgunek eskain dezakeen formatu irekia da.</p>
<p><b>Zure iturriak, eskura.</b> Zure profilean {mediamax} hedabide eta {newsmax} buletin arte aukeratzen dituzu; aukeratzen ez baduzu, nabarmendutakoak ikusiko dituzu. Agregatzaileak, Menéame adibidez, diren bezala erakusten dira, beren boto eta iruzkinekin. Zerbait falta zaizu? {propaura} aura edo gehiagorekin hedabide edo buletin bat proposa dezakezu, eta moderazioak berrikusten du.</p>
<p><b>Irakurtzea ez da bozkatzea.</b> RSS bidez iristen den ezer ez da bere kasa sartzen {site}-ren ilaran. Albiste bakoitzak botoi bat du Berriak atalera bidaltzeko, eta handik aurrera beste edozein bidalketaren bidea egiten du: botoak, δ eta zikloa.</p>

<h2>Aura</h2>
<p>Ez dago karmarik. <b>Aura</b> dago, eta gauza bakarra neurtzen du: komunitatearekiko zure konpromisoa. Egonez eta ekarriz irabazten da, ez gustatuz: +{auraport} azalera iristen den zure bidalketa bakoitzeko, +{auraenv} bidalketa edo artikulu bakoitzeko, +{auracom} iruzkin bakoitzeko, gehienez +{auracap} egunean. Eta jaitsi egiten da: egunero {auradec} galtzen duzu, {aurastart} gutxienekora iritsi arte. Ez da metatzen: eutsi egiten zaio. Nor dagoen neurtzen du, ez nor egon zen.</p>
<p><b>Ez dizu inoiz bozgorailurik ematen.</b> Aurak ez ditu albisteak igotzen ez iruzkinak ordenatzen. Denek azal bera ikusten dute, ordena berean. Zure ezizenaren inguruko argi gisa ikusten da, besterik ez. Ateak baino ez ditu irekitzen: {commaura} baino gehiagorekin komunitateak sor ditzakezu; {editaura} baino gehiagorekin, idatzi berri dituzun iruzkinak zuzendu; eta {seditaura} baino gehiagorekin, zure bidalketak.</p>

<h2>Zer argitara daiteke?</h2>
<p><b>Legeak baimentzen duen guztia.</b> Muga ez du lerro editorial batek edo gustu batek jartzen: legeak jartzen du. Kanpoan geratzen dira irainak, gorroto-delituak, mehatxuak eta delitu den guztia. <a href="/normas.php">Erabilera-arauetan</a> daude idatzita.</p>
<p><b>Gure zerbitzarietan bizi den AA batek moderatzen du.</b> Bidalketa eta iruzkin bat ere ez da haietatik ateratzen berrikusteko: ez dago hirugarrenik idazten duzuna begira. Zalantza duenean, pertsona batek erabakitzen du. Edonork sala dezake iruzkin bat.</p>
<p><b>Eta gainerako guztirako, helbide elektroniko bat.</b> Kexak, zorionak eta proposamenak: <b>info@deltha.eu</b>.</p>

<h2>Gaiak eta komunitateak</h2>
<p><b>Gaiak {site}-k jartzen ditu.</b> Gutxi eta egonkorrak: gaurkotasuna, zientzia, kultura… Bidalketa bakoitzak bat darama, eta gaiak bultzadatxo bat eman diezaioke δ-ari (koefiziente publiko bat).</p>
<p><b>Komunitateak jendeak sortzen ditu.</b> {commaura} aura baino gehiagorekin {commmax} arte ireki ditzakezu: izen bat, kolore bat eta zertaz doan. Bidalketa bat komunitate batera edo gehiagotara batu daiteke, eta sortu zuenak (edo moderatzaile batek) zaintzen du. Komunitate bakoitzak bere txantiloia aukera dezake, bere orrian eta harietan aplikatzen dena. Eta zuk komunitate bat edo gai bat aukera dezakezu sarrera-orri gisa.</p>

<h2>Zure erara</h2>
<p><b>{skins} txantiloi.</b> Azal bera, zenbaki berak, itxura desberdina: aldizkari, foro, terminal, manga, fanzine… itxurakoak. Txantiloia fitxan aukeratzen da, eta bakoitzak bertsio argia eta iluna ditu.</p>
<p><b>Zure hizkuntzan eta zure poltsikoan.</b> Zortzi hizkuntza: gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana eta italiera. Mugikorrean aplikazio gisa instalatzen da, erregistratuen arteko mezu pribatuekin eta txatarekin, eta bidalketak gogokoetan gorde ditzakezu.</p>
<p><b>Denona dena eta zurea dena.</b> Azala, hautagaiak eta berriak berdinak dira guztientzat, ordena berean. Zuk pertsonalizatzen duzuna bidea da: zein hedabide eta buletin dituzun eskura, eta {site} irekitzean azalean, gai batean edo komunitate batean sartzen zaren. Nondik iristen zaren aldatzen du, ez komunitateak erabakitzen duena.</p>

<h2>Bilatzaileetatik kanpo</h2>
<p><b>{site}-n gertatzen dena {site}-n geratzen da.</b> Bidalketa, iruzkin eta profil bat ere ez da agertzen Googlen ez beste bilatzaileetan: orri guztiek <i>noindex</i> marka daramate. Proiektua azaltzen duten orriak baino ez dira ikusgai, hau bezala.</p>
<p><b>Zuk nahi ezean.</b> Artikulu bat idazten baduzu, laukia marka dezakezu bilatzaileek indexa dezaten. Zure testua da eta zuk erabakitzen duzu; editatzean alda daiteke.</p>
<p><b>Eta AArik ez da entrenatzen idazten duzunarekin.</b> Adimen artifizialeko arakatzaileek (OpenAI, Anthropic, Google, Meta, Common Crawl eta abarrenek) sarrera debekatuta dute, baita bilatzaileentzat irekitako artikuluetan ere.</p>

<h2>{site}-ren jabe izan nahi duzu?</h2>
<p>{site} mundu guztiari irekitako proiektua da. Jabekide izateko ez dizugu dirurik eskatzen: plataforma erabiltzea baino ez. <b>{site}-ren jabetzaren % 50 {auradl} egunean {auramax} aura duten erabiltzaile erregistratu guztien artean banatuko da.</b></p>
<p>Probatu nahi duzu? <a href="/registro.php">Erregistratu</a> edo, kontua baduzu jada, <a href="/login.php">sartu</a>.</p>' WHERE slug = 'que-es.eu';
UPDATE pages SET body = '<h1>Nola funtzionatzen duen δ-k</h1>

<h2>Ezkutuko algoritmorik gabe</h2>
<p>{site}-n ez dago zure ordez zer ikusi erabakitzen duen makinarik. Azalera zer iristen den formula publiko, labur eta ikusgai batek erabakitzen du: Hautagaiak eta Berriak ataletako bidalketa bakoitzaren azpian ageri da, bere zenbakiekin eta une horretako balioarekin. Orri honek osorik azaltzen du. Ulertzen baduzu, {site} ulertzen duzu.</p>

<h2>δ (delta)</h2>
<p>Bidalketa bakoitzak <b>δ</b> bat du: komunitateak zenbat babesten duen eta zenbat denbora daraman zain laburbiltzen duen puntuazioa. Honela kalkulatzen da:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (aldeko botoak − kontrako botoak) × gaiaren koefizientea − {decay} × bidalketatik igarotako ordu erdiak</p>
<p><b>Botoak</b> jendearen ahotsa dira: erregistratu bakoitzak behin bozka dezake bidalketa bakoitza, alde edo kontra, eta iritziz alda dezake. Ezin da norberarena bozkatu.</p>
<p><b>Gaiaren koefizientea</b> bultzada editorial txiki bat da, eta {site}-k jartzen duen parametro bakarra: gai gehienek 1,00 balio dute; kulturak, zientziak edo kontakizunek 1,10, hau da, % 10 gehiago boto bakoitzeko. Koefizienteak publikoak dira, eta formula bakoitzean ikusiko dituzu.</p>
<p><b>Antzinatasunak</b> kendu egiten du: {decay} puntu, bidali zenetik igarotzen den ordu erdi bakoitzeko. Horri esker da {site} gaurkoa eta ez betikoa: egun bat ilaran daraman bi botoko albiste batek gutxiago balio du boto berak dituen iritsi berri batek baino. Ilaran ezerk ez du betiko irauten.</p>

<h2>Zikloa</h2>
<p><b>{cycle} minuturo</b> ziklo bat gertatzen da, beti berdin, salbuespenik eta eskuzko esku-hartzerik gabe:</p>
<p><b>1. Berrietatik Hautagaietara.</b> Berriak atalean gutxienez <b>{minage} minutu</b> daramatzaten bidalketen artean, <b>bakarra</b> igotzen da: δ handiena duena, betiere haren δ-a gutxienez <b>{mincand}</b> bada. Botorik gabe ez da igotzen, eta bidalketa batek ere ez badu lortzen, ziklo horretan ez da ezer igotzen. Gutxieneko denbora horren helburua da bidalketa bat ez igotzea une lasai batean iritsi delako bakarrik; lehenago ikusi eta bozkatu behar da. Azalera iritsi gabe <b>{newttl} ordu</b> igarotzen dituena artxibatu egiten da: ilara ere ez da betirako.</p>
<p><b>2. Hautagaietatik Azalera.</b> Hautagai guztien artean, <b>δ handiena duena</b> argitaratzen da azalean, betiere haren δ-a gutxienez <b>{minpub}</b> bada. Batek ere ez badu lortzen, ziklo horretan ez da ezer argitaratzen. Azala ez da betetzeagatik betetzen.</p>
<p><b>3. Beloa altxatzen da.</b> Edozein bidalketa edo iruzkin argitaratu eta {gm} minutura, egilearen ezizenak bere izenari uzten dio lekua. Ordura arte, denak dira halodun ezizenak: hitzak epaitzen dira, ez sinadura.</p>
<p><b>4. Artxiboa.</b> Azalean {ttl} ordu daramatzana artxibatu egiten da. Irakurtzen jarrai daiteke, baina jada ez da bozkatzen ez iruzkintzen: elkarrizketa izan zena izan zen.</p>
<p><b>Prozesuak</b> atalak hori guztia erakusten du zuzenean: δ-ren arabera ordenatutako ilara, bidalketa bakoitza noiz igoko den aurreikusita, eta bidalketa, boto, iruzkin eta azal-igoera bakoitzaren jarioa, gertatu ahala.</p>

<h2>Gaiak eta komunitateak</h2>
<p>Bidalketa bakoitza bi aldiz sailkatzen da. <b>Gaia</b> {site}-k jartzen du: gutxi dira, egonkorrak (gaurkotasuna, zientzia, kultura…), eta bakoitzak bere koefiziente publikoa du. Bidalketa orok darama gai bat.</p>
<p><b>Komunitatea</b> jendeak jartzen du. <b>{commaura} aura</b> baino gehiago duen edonork sor ditzake <b>{commmax}</b> arte: izen bat, kolore bat eta zertaz doan esaten duen esaldi bat. Bidalketa bat komunitate batera bil daiteke bere gaiaz gain, eta horrela talde bati interesatzen zaiona biltzen da, {site}-k erabaki beharrik gabe. Komunitate bat sortzen duenak zaintzen du: haren izena, deskribapena, kolorea eta txantiloi bisuala editatzen ditu; moderatzaileek gauza bera egin dezakete eta, behar izanez gero, ezkutatu.</p>
<p>Komunitateek <b>ez dute δ ukitzen</b>: ez dute koefizienterik ematen, ez dute ezer igotzen ez jaisten. Leku bat dira, ez bozgorailu bat. Egin dezakezuna da komunitate bat (edo gai bat) aukeratzea zure profilean <b>sarrera-orri</b> gisa: {site} irekitzean hara iritsiko zara, eta Azala fitxak denen azala izaten jarraituko du.</p>

<h2>RSS bidez iristen dena</h2>
<p>{site}-k hedabideek, buletinek eta beste agregatzaile batzuek argitaratzen dutena ere jasotzen du RSS bidez. Hor <b>ez dago δ-rik ez ziklorik</b>: iturri bakoitza argitalpen-ordenan erakusten da, berriena lehenengo, gustuen edo arrakastaren arabera ordenatu gabe. Agregatzaileetan beren boto eta iruzkinak ikusten dira, beren komunitatearenak, ez {site}-renak.</p>
<p>RSS bidez iristen dena <b>ez da bere kasa sartzen ilaran</b>. Norbaitek Berriak atalera bidaltzen badu albiste bakoitzaren botoiarekin, handik aurrera beste bidalketa bat da: botoak, δ eta zikloa, guztiak bezala.</p>

<h2>δ-k egiten ez duena</h2>
<p>Ez du zugandik ikasten. Ez daki zer gustatzen zaizun. Ez dizu zure auzokoari baino azal desberdinik erakusten. Ez du saritzen jarraitzaile gehien dituena, hemen inork ez baitu jarraitzailerik. Ez dio begiratzen zein ordutan sartu ohi zaren, ezta zenbat denbora ematen duzun irakurtzen ere. Formula bera, zenbaki berak, azal bera denentzat.</p>

<h2>Zuk aukeratzen duzuna</h2>
<p>Azala, hautagaiak eta berriak <b>berdinak dira guztientzat</b>, ordena berean. Pertsona batetik bestera aldatzen dena <b>nola ikusten diren</b> eta <b>nondik sartzen zaren</b> da. Nola ikusten diren erabakitzeko, zure profilean aukeratzen dituzu <b>txantiloi bisuala</b> ({skins}, aldizkaritik fanzinera, bakoitza bertsio argi eta ilunarekin), <b>gai argia edo iluna</b> eta <b>hizkuntza</b> (gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana edo italiera); komunitate batek bere txantiloia izan dezake, bere orrian eta harietan ikusiko duzuna.</p>
<p>Nondik sartzen zaren erabakitzeko, zure <b>sarrera-orria</b> aukeratzen duzu (azala, gai bat edo komunitate bat) eta <b>zure iturriak</b>: {mediamax} hedabide eta {newsmax} buletin arte, beti eskura. Horrek sarbidea pertsonalizatzen du, ez iritzia: aukeratzen duzun ezerk ez du aldatzen azala, ezeren ordena ezta inongo bidalketaren δ-a ere. Gainera, {site} mugikorrean aplikazio gisa instalatzen da, eta erregistratuen arteko mezu pribatuak ditu.</p>

<h2>Aura</h2>
<p>Pertsonek ez dute δ-rik; <b>aura</b> dute, beste gauza bat dena eta gauza bakarra neurtzen duena: komunitatearekiko konpromisoa. Egonez eta ekarriz irabazten da (+{auraport} azalera iristen den zure bidalketa bakoitzeko, +{auraenv} bidalketa edo artikulu bakoitzeko, +{auracom} iruzkin bakoitzeko, egunean gehienez +{auracap}) eta ez egonez galtzen da (egunero {auradec}, inoiz ez {aurastart}-tik behera). Gehienezkoa {auramax} da.</p>
<p>Aurak <b>ez dizu inoiz bozgorailurik ematen</b>: ez ditu albisteak igotzen, ez ditu iruzkinak ordenatzen, ez du eraginik δ-n. Zure ezizenaren inguruko argi gisa ikusten da, eta konfiantzazko ate batzuk irekitzen ditu: {commaura} aura baino gehiagorekin komunitateak sor ditzakezu ({commmax} arte); {editaura} baino gehiagorekin, zure iruzkinak zuzendu argitaratu eta hurrengo {editmin} minutuetan; eta {seditaura} baino gehiagorekin, zure bidalketak {seditmin} minutuz, azalera iritsi ez diren bitartean (zuzendutakoa editatu gisa markatuta geratzen da). {propaura} edo gehiagorekin hedabideak eta buletinak proposa ditzakezu.</p>

<h2>Gaurko zenbakiak</h2>
<p>Orri honetako parametro guztiak {site}-n une honetan indarrean daudenak dira; taldeak aldatzen baditu, orri hau ere haiekin batera aldatzen da. Zikloa {cycle} minuturo · gutxienez {minage} minutu Berriak atalean · azalerako gutxieneko δ: {minpub} · beherakada: {decay} ordu erdiko · {gm} minutuko beloa · hautagai izateko gutxieneko δ: {mincand} · {ttl} orduko bizitza azalean · azalera iritsi gabe {newttl} ordura iraungitzen da · komunitateak {commaura} baino aura handiagoarekin, gehienez {commmax} pertsonako.</p>' WHERE slug = 'algoritmo.eu';
UPDATE pages SET body = '<h1>Que é Deltha?</h1>

<p>{site} é catro cousas á vez: <b>un agregador de novas</b>, onde a xente envía ligazóns e decide cos seus votos que chega á portada; <b>un lector de fontes</b>, que recolle por RSS o que publican medios, boletíns e outros agregadores e o amosa, sen algoritmo, do máis novo ao máis vello; <b>unha rede social contralgorítmica</b>, sen máquina que escolla por ti que ver; e <b>un espazo para o debate</b>, con fíos que se abren, se discuten e se pechan.</p>

<h2>De onde vén</h2>
<p>De cinco sitios. Do <a href="https://contralgoritmia.com/" rel="noopener">manifesto contralgorítmico</a> vén a idea de fondo: recuperar o criterio humano fronte á hexemonía do algoritmo. De <b>Menéame</b>, o agregador: a comunidade decide que chega á portada. De <b>X</b>, o deseño: unha liña de tempo limpa e rápida. De <b>4chan</b>, a efemeridade: os fíos nacen, discútense e péchanse. E do <b>modelo de negocio de Jot Down</b>, a maneira de soster o proxecto: sen publicidade programática nin rastrexo, unha comunidade que sostén o que le.</p>

<h2>Como funciona</h2>
<p><b>É efémera, pero non borra.</b> Cando un envío leva {ttl} horas na portada, arquívase coa súa conversa: pódese seguir lendo, pero xa non se vota nin se comenta. O que non chega á portada arquívase ás {newttl} horas de enviarse. O que importa é a conversa de agora, non a que se acumula.</p>
<p><b>Non deixa pegada.</b> Sen cookies de rastrexo, sen publicidade programática, sen terceiros. Podes comprobalo abrindo as ferramentas do teu navegador.</p>
<p><b>É horizontal.</b> Todo se publica baixo un alias e o nome de quen escribe só aparece aos {gm} minutos, para que se xulguen as palabras antes ca quen as di.</p>
<p><b>Vese por dentro.</b> A sección Procesos amosa en directo a cola, o δ de cada envío e cada voto, comentario e portada segundo ocorren.</p>
<p><b>Sen algoritmo oculto.</b> O que chega á portada decídeo unha fórmula pública, curta e visible baixo cada envío: o <b>δ</b>. Está explicada enteira en <a href="/algoritmo.php">Como funciona o δ</a>.</p>

<h2>O que chega por RSS</h2>
<p><b>{site} tamén le.</b> Ademais do que envía a xente, recolle por RSS o que publican medios, boletíns (de Substack e doutros sitios) e outros agregadores, e amósao co seu propio aspecto, o máis novo primeiro. Sen algoritmo e sen rastrexo: o RSS é un formato aberto que calquera web pode ofrecer.</p>
<p><b>As túas fontes, a man.</b> No teu perfil escolles ata {mediamax} medios e {newsmax} boletíns; se non escolles, verás os destacados. Os agregadores, como Menéame, amósanse tal cal, cos seus propios votos e comentarios. Botas algo en falta? Con {propaura} de aura ou máis podes propoñer un medio ou un boletín, e a moderación revísao.</p>
<p><b>Ler non é votar.</b> Nada do que chega por RSS entra só na cola de {site}. Cada nova ten un botón para enviala a Novas, e dende aí segue o camiño de calquera envío: votos, δ e ciclo.</p>

<h2>A aura</h2>
<p>Non hai karma. Hai <b>aura</b>, e mide unha soa cousa: o teu compromiso coa comunidade. Gáñase estando e achegando, non gustando: +{auraport} por cada envío teu que chega á portada, +{auraenv} por cada envío ou artigo, +{auracom} por cada comentario, como moito +{auracap} ao día. E baixa: cada día perdes {auradec}, ata o mínimo de {aurastart}. Non se acumula: sostense. Mide quen está, non quen estivo.</p>
<p><b>Nunca dá altofalante.</b> A aura non sobe novas nin ordena comentarios. Todo o mundo ve a mesma portada, na mesma orde. Vese como luz arredor do teu alias, nada máis. O único que abre son portas: con máis de {commaura} podes crear comunidades; con máis de {editaura}, corrixir os comentarios que acabas de escribir, e con máis de {seditaura}, os teus envíos.</p>

<h2>Que se pode publicar?</h2>
<p><b>Todo o que permite a lei.</b> O límite non o pon unha liña editorial nin un gusto: pono a lei. Fóra quedan os insultos, os delitos de odio, as ameazas e todo o que sexa delito. Están escritas nas <a href="/normas.php">normas de uso</a>.</p>
<p><b>Modera unha IA que vive nos nosos servidores.</b> Ningún envío nin comentario sae deles para ser revisado: non hai terceiros mirando o que escribes. Cando dubida, decide unha persoa. Calquera pode denunciar un comentario.</p>
<p><b>E hai un correo para todo o demais.</b> Queixas, parabéns e propostas: <b>info@deltha.eu</b>.</p>

<h2>Temas e comunidades</h2>
<p><b>Os temas ponos {site}.</b> Poucos e estables: actualidade, ciencia, cultura… Cada envío leva un, e o tema pode darlle un pequeno empurrón ao δ (un coeficiente público).</p>
<p><b>As comunidades créaas a xente.</b> Con máis de {commaura} de aura podes abrir ata {commmax}: un nome, unha cor e de que vai. Un envío pode sumarse a unha ou a varias, e quen a creou (ou un moderador) cóidaa. Cada comunidade pode escoller un modelo propio, que se aplica á súa páxina e aos seus fíos. E ti podes escoller unha comunidade ou un tema como páxina de entrada.</p>

<h2>Ao teu xeito</h2>
<p><b>{skins} modelos.</b> A mesma portada, os mesmos números, distinta pel: de revista, de foro, de terminal, de manga, de fanzine… Escóllese na lapela Modelo, e cada un ten versión clara e escura.</p>
<p><b>No teu idioma e no teu peto.</b> Oito idiomas: español, catalán, éuscaro, galego, inglés, francés, alemán e italiano. Instálase como app no móbil, con mensaxes privadas e chat entre rexistrados, e podes gardar envíos en favoritos.</p>
<p><b>O que é de todos e o que é teu.</b> A portada, as candidatas e as novas son as mesmas para todo o mundo, na mesma orde. O que si personalizas é o camiño: que medios e boletíns tes a man, e se ao abrir {site} entras na portada, nun tema ou nunha comunidade. Cambia por onde chegas, non o que decide a comunidade.</p>

<h2>Fóra dos buscadores</h2>
<p><b>O que pasa en {site} queda en {site}.</b> Ningún envío, comentario nin perfil aparece en Google nin noutros buscadores: todas as páxinas levan a marca <i>noindex</i>. Só se deixan ver as páxinas que explican o proxecto, coma esta.</p>
<p><b>Agás que ti o queiras.</b> Se escribes un artigo, podes marcar a caixa para que os buscadores o indexen. É o teu texto e decides ti; pódese cambiar ao editalo.</p>
<p><b>E ningunha IA se adestra co que escribes.</b> Os rastrexadores de intelixencia artificial (os de OpenAI, Anthropic, Google, Meta, Common Crawl e compañía) teñen a entrada vetada, mesmo nos artigos abertos aos buscadores.</p>

<h2>Queres ser propietario de {site}?</h2>
<p>{site} é un proxecto aberto a todo o mundo. Para ser copropietario non che pedimos cartos: só que uses a plataforma. <b>O 50 % da propiedade de {site} repartirase entre todos os usuarios rexistrados que teñan aura {auramax} o {auradl}.</b></p>
<p>Queres probala? <a href="/registro.php">Rexístrate</a> ou, se xa tes conta, <a href="/login.php">entra</a>.</p>' WHERE slug = 'que-es.gl';
UPDATE pages SET body = '<h1>Como funciona o δ</h1>

<h2>Sen algoritmo oculto</h2>
<p>En {site} non hai unha máquina que decida por ti que ver. O que chega á portada decídeo unha fórmula pública, curta e visible: amósase baixo cada envío en Candidatas e Novas, cos seus números e o seu valor nese momento. Esta páxina explícaa enteira. Se a entendes, entendes {site}.</p>

<h2>O δ (delta)</h2>
<p>Cada envío ten un <b>δ</b>: unha puntuación que resume canto o apoia a comunidade e canto tempo leva esperando. Calcúlase así:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votos positivos − votos negativos) × coeficiente do tema − {decay} × medias horas desde o envío</p>
<p><b>Os votos</b> son a voz da xente: cada rexistrado pode votar unha vez cada envío, a favor ou en contra, e cambiar de opinión. Non se pode votar o propio.</p>
<p><b>O coeficiente do tema</b> é un pequeno empurrón editorial e é o único parámetro que pon {site}: a maioría dos temas valen 1,00; cultura, ciencia ou relatos valen 1,10, é dicir, un 10 % máis por voto. Os coeficientes son públicos e verás cada un na súa fórmula.</p>
<p><b>A antigüidade</b> resta: {decay} puntos por cada media hora que pasa desde que se enviou. É o que fai que {site} sexa de hoxe e non de sempre: unha nova con dous votos que leva un día na cola vale menos ca unha acabada de chegar cos mesmos votos. Nada dura para sempre na cola.</p>

<h2>O ciclo</h2>
<p>Cada <b>{cycle} minutos</b> ocorre un ciclo, sempre igual, sen excepcións nin intervención manual:</p>
<p><b>1. De Novas a Candidatas.</b> Entre os envíos que levan polo menos <b>{minage} minutos</b> en Novas, ascende <b>un só</b>: o de maior δ, sempre que o seu δ sexa polo menos <b>{mincand}</b>. Sen votos non se ascende, e se ningún envío chega, ese ciclo non ascende nada. O tempo mínimo existe para que un envío non suba só porque chegou nun momento tranquilo; ten que ser visto e votado antes. O que pasa <b>{newttl} horas</b> sen chegar á portada arquívase: a cola tampouco é para sempre.</p>
<p><b>2. De Candidatas a Portada.</b> Entre todas as candidatas, publícase na portada <b>a de maior δ</b>, sempre que o seu δ sexa polo menos <b>{minpub}</b>. Se ningunha chega, nese ciclo non se publica nada. A portada non se enche por encher.</p>
<p><b>3. O veo levántase.</b> Aos {gm} minutos de publicarse calquera envío ou comentario, o alias de quen o escribiu deixa paso ao seu nome. Antes, todos son alias cun halo: xúlganse as palabras, non a sinatura.</p>
<p><b>4. Arquivo.</b> O que leva {ttl} horas na portada arquívase. Pódese seguir lendo, pero xa non se vota nin se comenta: a conversa foi o que foi.</p>
<p>A sección <b>Procesos</b> amosa todo isto en directo: a cola ordenada por δ cunha estimación de cando ascende cada envío, e un fluxo con cada envío, voto, comentario e portada segundo ocorren.</p>

<h2>Temas e comunidades</h2>
<p>Cada envío clasifícase dúas veces. <b>O tema</b> pono {site}: son poucos, estables (actualidade, ciencia, cultura…) e cada un ten o seu coeficiente público. Todo envío leva un tema.</p>
<p><b>A comunidade</b> pona a xente. Calquera con máis de <b>{commaura} de aura</b> pode crear ata <b>{commmax}</b>: un nome, unha cor e unha frase que diga de que vai. Un envío pode sumarse a unha comunidade ademais de ao seu tema, e así agrúpase o que lle interesa a un grupo sen que {site} teña que decidilo. Quen crea unha comunidade cóidaa: edita o seu nome, a súa descrición, a súa cor e o seu modelo visual; os moderadores poden facer o mesmo e, se cómpre, ocultala.</p>
<p>As comunidades <b>non tocan o δ</b>: non dan coeficiente, non soben nin baixan nada. Son un lugar, non un altofalante. O que si podes facer é escoller unha comunidade (ou un tema) como <b>páxina de entrada</b> no teu perfil: ao abrir {site} chegarás alí, e a lapela Portada seguirá sendo a portada de todos.</p>

<h2>O que chega por RSS</h2>
<p>{site} tamén recolle por RSS o que publican medios, boletíns e outros agregadores. Aí <b>non hai δ nin ciclo</b>: cada fonte amósase por orde de publicación, o máis novo primeiro, sen ordenar por gustos nin por éxito. Nos agregadores vense os seus propios votos e comentarios, que son os da súa comunidade, non os de {site}.</p>
<p>O que chega por RSS <b>non entra só na cola</b>. Se alguén o envía a Novas co botón de cada nova, a partir de aí é un envío máis: votos, δ e ciclo, coma todos.</p>

<h2>O que o δ non fai</h2>
<p>Non aprende de ti. Non sabe que che gusta. Non che amosa unha portada distinta da do teu veciño. Non premia a quen ten máis seguidores, porque aquí ninguén ten seguidores. Non mira a hora á que adoitas entrar nin canto tempo pasas lendo. A mesma fórmula, os mesmos números, a mesma portada para todos.</p>

<h2>O que si escolles ti</h2>
<p>A portada, as candidatas e as novas son <b>as mesmas para todos</b>, na mesma orde. O que si cambia dunha persoa a outra é <b>como se ven</b> e <b>por onde entras</b>. Para como se ven, no teu perfil escolles o <b>modelo visual</b> ({skins}, de revista a fanzine, cada un con versión clara e escura), o <b>tema claro ou escuro</b> e o <b>idioma</b> (español, catalán, éuscaro, galego, inglés, francés, alemán ou italiano); unha comunidade pode ter o seu propio modelo, que verás na súa páxina e nos seus fíos.</p>
<p>Para por onde entras, escolles a túa <b>páxina de entrada</b> (a portada, un tema ou unha comunidade) e <b>as túas fontes</b>: ata {mediamax} medios e {newsmax} boletíns sempre a man. Iso personaliza o acceso, non o xuízo: nada do que escolles altera a portada, a orde de nada nin o δ de ningún envío. Ademais, {site} instálase como aplicación no móbil e ten mensaxes privadas entre rexistrados.</p>

<h2>A aura</h2>
<p>As persoas non teñen δ; teñen <b>aura</b>, que é outra cousa e mide unha soa: o compromiso coa comunidade. Gáñase estando e achegando (+{auraport} por cada envío teu que chega á portada, +{auraenv} por cada envío ou artigo, +{auracom} por cada comentario, cun teito de +{auracap} ao día) e pérdese non estando ({auradec} cada día, nunca por debaixo de {aurastart}). O máximo é {auramax}.</p>
<p>A aura <b>nunca dá altofalante</b>: non sobe novas, non ordena comentarios, non pesa no δ. Vese como luz arredor do teu alias, e abre algunhas portas de confianza: con máis de {commaura} de aura podes crear comunidades (ata {commmax}); con máis de {editaura}, corrixir os teus propios comentarios durante {editmin} minutos despois de publicalos, e con máis de {seditaura}, os teus envíos durante {seditmin} minutos mentres non cheguen á portada (o corrixido queda marcado como editado). Con {propaura} ou máis podes propoñer medios e boletíns.</p>

<h2>Os números de hoxe</h2>
<p>Todos os parámetros desta páxina son os que están en vigor agora mesmo en {site}; se o equipo os cambia, esta páxina cambia con eles. Ciclo cada {cycle} minutos · {minage} minutos mínimos en Novas · δ mínimo para portada {minpub} · decaemento {decay} por media hora · veo de {gm} minutos · δ mínimo para ser candidata {mincand} · vida na portada de {ttl} horas · caducidade sen portada ás {newttl} horas · comunidades con aura maior que {commaura}, ata {commmax} por persoa.</p>' WHERE slug = 'algoritmo.gl';
UPDATE pages SET body = '<h1>Qu''est-ce que Deltha ?</h1>

<p>{site}, c''est quatre choses à la fois : <b>un agrégateur d''actualités</b>, où les gens envoient des liens et décident par leurs votes de ce qui arrive en une ; <b>un lecteur de sources</b>, qui récupère par RSS ce que publient des médias, des newsletters et d''autres agrégateurs et l''affiche, sans algorithme, du plus récent au plus ancien ; <b>un réseau social contre-algorithmique</b>, sans machine qui choisit pour toi ce que tu vois ; et <b>un espace de débat</b>, avec des fils qui s''ouvrent, se discutent et se ferment.</p>

<h2>D''où ça vient</h2>
<p>De cinq sites. Du <a href="https://contralgoritmia.com/" rel="noopener">manifeste contre-algorithmique</a> vient l''idée de fond : retrouver le jugement humain face à l''hégémonie de l''algorithme. De <b>Menéame</b>, l''agrégateur : la communauté décide de ce qui arrive en une. De <b>X</b>, le design : un fil chronologique propre et rapide. De <b>4chan</b>, l''éphémère : les fils naissent, se discutent et se ferment. Et du <b>modèle économique de Jot Down</b>, la façon de tenir debout : ni publicité programmatique ni pistage, une communauté qui fait vivre ce qu''elle lit.</p>

<h2>Comment ça marche</h2>
<p><b>C''est éphémère, mais rien n''est effacé.</b> Quand un envoi est en une depuis {ttl} heures, il est archivé avec sa conversation : on peut toujours le lire, mais on ne peut plus voter ni commenter. Ce qui n''arrive pas en une est archivé {newttl} heures après l''envoi. Ce qui compte, c''est la conversation de maintenant, pas celle qui s''accumule.</p>
<p><b>Ça ne laisse pas de traces.</b> Pas de cookies de pistage, pas de publicité programmatique, pas de tiers. Tu peux le vérifier en ouvrant les outils de ton navigateur.</p>
<p><b>C''est horizontal.</b> Tout est publié sous un alias et le nom de l''auteur n''apparaît qu''au bout de {gm} minutes, pour qu''on juge les mots avant la personne qui les dit.</p>
<p><b>On voit l''intérieur.</b> La section Processus montre en direct la file d''attente, le δ de chaque envoi et chaque vote, commentaire et passage en une au moment où ils se produisent.</p>
<p><b>Pas d''algorithme caché.</b> Ce qui arrive en une, c''est une formule publique, courte et visible sous chaque envoi qui le décide : le <b>δ</b>. Elle est expliquée en entier dans <a href="/algoritmo.php">Comment fonctionne le δ</a>.</p>

<h2>Ce qui arrive par RSS</h2>
<p><b>{site} lit aussi.</b> En plus de ce que les gens envoient, il récupère par RSS ce que publient des médias, des newsletters (sur Substack et ailleurs) et d''autres agrégateurs, et l''affiche avec son propre habillage, le plus récent d''abord. Sans algorithme et sans pistage : le RSS est un format ouvert que n''importe quel site peut proposer.</p>
<p><b>Tes sources, à portée de main.</b> Dans ton profil, tu choisis jusqu''à {mediamax} médias et {newsmax} newsletters ; si tu ne choisis pas, tu verras ceux mis en avant. Les agrégateurs, comme Menéame, s''affichent tels quels, avec leurs propres votes et commentaires. Il te manque quelque chose ? Avec {propaura} d''aura ou plus, tu peux proposer un média ou une newsletter, et la modération l''examine.</p>
<p><b>Lire n''est pas voter.</b> Rien de ce qui arrive par RSS n''entre tout seul dans la file de {site}. Chaque actualité a un bouton pour l''envoyer dans Nouveautés, et à partir de là elle suit le chemin de n''importe quel envoi : votes, δ et cycle.</p>

<h2>L''aura</h2>
<p>Il n''y a pas de karma. Il y a l''<b>aura</b>, et elle ne mesure qu''une chose : ton engagement envers la communauté. Elle se gagne en étant là et en contribuant, pas en plaisant : +{auraport} pour chaque envoi à toi qui arrive en une, +{auraenv} pour chaque envoi ou article, +{auracom} pour chaque commentaire, au maximum +{auracap} par jour. Et elle baisse : chaque jour tu perds {auradec}, jusqu''au minimum de {aurastart}. Elle ne s''accumule pas : elle s''entretient. Elle mesure qui est là, pas qui a été là.</p>
<p><b>Elle ne donne jamais de porte-voix.</b> L''aura ne fait pas monter d''actualités et ne classe pas les commentaires. Tout le monde voit la même une, dans le même ordre. Elle se voit comme une lumière autour de ton alias, rien de plus. Tout ce qu''elle ouvre, ce sont des portes : au-delà de {commaura}, tu peux créer des communautés ; au-delà de {editaura}, corriger tes commentaires tout juste écrits ; et au-delà de {seditaura}, tes envois.</p>

<h2>Que peut-on publier ?</h2>
<p><b>Tout ce que la loi permet.</b> La limite n''est fixée ni par une ligne éditoriale ni par un goût : c''est la loi qui la fixe. Restent dehors les insultes, les délits de haine, les menaces et tout ce qui constitue un délit. C''est écrit dans les <a href="/normas.php">règles d''usage</a>.</p>
<p><b>La modération est faite par une IA qui vit sur nos serveurs.</b> Aucun envoi ni commentaire n''en sort pour être examiné : il n''y a pas de tiers qui regardent ce que tu écris. En cas de doute, c''est une personne qui décide. Tout le monde peut signaler un commentaire.</p>
<p><b>Et il y a une adresse pour tout le reste.</b> Plaintes, félicitations et propositions : <b>info@deltha.eu</b>.</p>

<h2>Thèmes et communautés</h2>
<p><b>Les thèmes, c''est {site} qui les fixe.</b> Peu nombreux et stables : actualité, science, culture… Chaque envoi en a un, et le thème peut donner un petit coup de pouce au δ (un coefficient public).</p>
<p><b>Les communautés, ce sont les gens qui les créent.</b> Avec plus de {commaura} d''aura, tu peux en ouvrir jusqu''à {commmax} : un nom, une couleur et un sujet. Un envoi peut rejoindre une ou plusieurs communautés, et celui qui l''a créée (ou un modérateur) en prend soin. Chaque communauté peut choisir sa propre skin, qui s''applique à sa page et à ses fils. Et tu peux choisir une communauté ou un thème comme page d''arrivée.</p>

<h2>À ta façon</h2>
<p><b>{skins} skins.</b> Même une, mêmes chiffres, autre peau : magazine, forum, terminal, manga, fanzine… Tu la choisis dans l''onglet Skins, et chacune a une version claire et une version sombre.</p>
<p><b>Dans ta langue et dans ta poche.</b> Huit langues : espagnol, catalan, basque, galicien, anglais, français, allemand et italien. Ça s''installe comme une appli sur le mobile, avec messages privés et chat entre inscrits, et tu peux enregistrer des envois dans tes favoris.</p>
<p><b>Ce qui est à tout le monde et ce qui est à toi.</b> La une, les candidates et les nouveautés sont les mêmes pour tout le monde, dans le même ordre. Ce que tu personnalises, c''est le chemin : les médias et newsletters que tu gardes à portée de main, et si en ouvrant {site} tu arrives sur la une, sur un thème ou dans une communauté. Ça change par où tu arrives, pas ce que décide la communauté.</p>

<h2>Hors des moteurs de recherche</h2>
<p><b>Ce qui se passe sur {site} reste sur {site}.</b> Aucun envoi, commentaire ni profil n''apparaît dans Google ni dans les autres moteurs de recherche : toutes les pages portent la balise <i>noindex</i>. Seules les pages qui expliquent le projet, comme celle-ci, se laissent voir.</p>
<p><b>Sauf si tu le veux.</b> Si tu écris un article, tu peux cocher la case pour que les moteurs de recherche l''indexent. C''est ton texte, c''est toi qui décides ; tu peux changer d''avis en le modifiant.</p>
<p><b>Et aucune IA ne s''entraîne sur ce que tu écris.</b> Les robots d''intelligence artificielle (ceux d''OpenAI, Anthropic, Google, Meta, Common Crawl et compagnie) ont l''entrée interdite, même sur les articles ouverts aux moteurs de recherche.</p>

<h2>Tu veux devenir propriétaire de {site} ?</h2>
<p>{site} est un projet ouvert à tout le monde. Pour en être copropriétaire, on ne te demande pas d''argent : seulement d''utiliser la plateforme. <b>50 % de la propriété de {site} sera réparti entre tous les utilisateurs inscrits qui auront une aura de {auramax} le {auradl}.</b></p>
<p>Tu veux l''essayer ? <a href="/registro.php">Inscris-toi</a> ou, si tu as déjà un compte, <a href="/login.php">connecte-toi</a>.</p>' WHERE slug = 'que-es.fr';
UPDATE pages SET body = '<h1>Comment fonctionne le δ</h1>

<h2>Pas d''algorithme caché</h2>
<p>Sur {site}, aucune machine ne décide pour toi de ce que tu vois. Ce qui arrive en une, c''est une formule publique, courte et visible qui le décide : elle s''affiche sous chaque envoi dans Candidates et Nouveautés, avec ses chiffres et sa valeur à cet instant. Cette page l''explique en entier. Si tu la comprends, tu comprends {site}.</p>

<h2>Le δ (delta)</h2>
<p>Chaque envoi a un <b>δ</b> : un score qui résume à quel point la communauté le soutient et depuis combien de temps il attend. Il se calcule ainsi :</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votes positifs − votes négatifs) × coefficient du thème − {decay} × demi-heures depuis l''envoi</p>
<p><b>Les votes</b> sont la voix des gens : chaque inscrit peut voter une fois par envoi, pour ou contre, et changer d''avis. On ne peut pas voter pour ses propres envois.</p>
<p><b>Le coefficient du thème</b> est un petit coup de pouce éditorial, et c''est le seul paramètre que fixe {site} : la plupart des thèmes valent 1,00 ; culture, science ou récits valent 1,10, soit 10 % de plus par vote. Les coefficients sont publics et tu les verras dans chaque formule.</p>
<p><b>L''ancienneté</b> retire des points : {decay} par demi-heure écoulée depuis l''envoi. C''est ce qui fait que {site} parle d''aujourd''hui et pas de toujours : une actualité à deux votes qui attend depuis un jour vaut moins qu''une toute nouvelle avec les mêmes votes. Rien ne dure éternellement dans la file.</p>

<h2>Le cycle</h2>
<p>Toutes les <b>{cycle} minutes</b> a lieu un cycle, toujours le même, sans exception ni intervention manuelle :</p>
<p><b>1. De Nouveautés à Candidates.</b> Parmi les envois présents depuis au moins <b>{minage} minutes</b> dans Nouveautés, <b>un seul</b> monte : celui au δ le plus élevé, à condition que son δ atteigne au moins <b>{mincand}</b>. Sans votes, on ne monte pas, et si aucun envoi n''y arrive, ce cycle ne fait rien monter. Le temps minimum existe pour qu''un envoi ne monte pas simplement parce qu''il est arrivé à un moment calme : il doit d''abord être vu et voté. Ce qui passe <b>{newttl} heures</b> sans arriver en une est archivé : la file non plus n''est pas éternelle.</p>
<p><b>2. De Candidates à la Une.</b> Parmi toutes les candidates, <b>celle au δ le plus élevé</b> est publiée en une, à condition que son δ atteigne au moins <b>{minpub}</b>. Si aucune n''y arrive, ce cycle ne publie rien. La une ne se remplit pas pour se remplir.</p>
<p><b>3. Le voile se lève.</b> {gm} minutes après la publication de n''importe quel envoi ou commentaire, l''alias de son auteur laisse place à son nom. Avant, tout le monde est un alias avec un halo : on juge les mots, pas la signature.</p>
<p><b>4. Archivage.</b> Ce qui est en une depuis {ttl} heures est archivé. On peut continuer à le lire, mais on ne vote plus et on ne commente plus : la conversation a été ce qu''elle a été.</p>
<p>La section <b>Processus</b> montre tout cela en direct : la file triée par δ avec une estimation du moment où chaque envoi montera, et un flux avec chaque envoi, vote, commentaire et passage en une au moment où ils se produisent.</p>

<h2>Thèmes et communautés</h2>
<p>Chaque envoi est classé deux fois. <b>Le thème</b>, c''est {site} qui le fixe : ils sont peu nombreux, stables (actualité, science, culture…) et chacun a son coefficient public. Tout envoi a un thème.</p>
<p><b>La communauté</b>, ce sont les gens qui la choisissent. Toute personne ayant plus de <b>{commaura} d''aura</b> peut en créer jusqu''à <b>{commmax}</b> : un nom, une couleur et une phrase qui dit de quoi il s''agit. Un envoi peut rejoindre une communauté en plus de son thème, et ainsi se regroupe ce qui intéresse un groupe sans que {site} ait à en décider. Qui crée une communauté en prend soin : il en modifie le nom, la description, la couleur et l''habillage ; les modérateurs peuvent faire de même et, si besoin, la masquer.</p>
<p>Les communautés <b>ne touchent pas au δ</b> : elles ne donnent pas de coefficient, ne font rien monter ni descendre. Ce sont des lieux, pas des porte-voix. Ce que tu peux faire, en revanche, c''est choisir une communauté (ou un thème) comme <b>page d''arrivée</b> dans ton profil : en ouvrant {site}, tu arriveras là, et l''onglet Une restera la une de tout le monde.</p>

<h2>Ce qui arrive par RSS</h2>
<p>{site} récupère aussi par RSS ce que publient des médias, des newsletters et d''autres agrégateurs. Là, <b>il n''y a ni δ ni cycle</b> : chaque source s''affiche dans l''ordre de publication, le plus récent d''abord, sans tri par goûts ni par succès. Les agrégateurs affichent leurs propres votes et commentaires, qui sont ceux de leur communauté, pas ceux de {site}.</p>
<p>Ce qui arrive par RSS <b>n''entre pas tout seul dans la file</b>. Si quelqu''un l''envoie dans Nouveautés avec le bouton de chaque actualité, à partir de là c''est un envoi comme un autre : votes, δ et cycle, comme tous.</p>

<h2>Ce que le δ ne fait pas</h2>
<p>Il n''apprend rien de toi. Il ne sait pas ce que tu aimes. Il ne te montre pas une une différente de celle de ton voisin. Il ne récompense pas ceux qui ont le plus d''abonnés, parce qu''ici personne n''a d''abonnés. Il ne regarde pas l''heure à laquelle tu te connectes d''habitude ni le temps que tu passes à lire. La même formule, les mêmes chiffres, la même une pour tout le monde.</p>

<h2>Ce que tu choisis, toi</h2>
<p>La une, les candidates et les nouveautés sont <b>les mêmes pour tout le monde</b>, dans le même ordre. Ce qui change d''une personne à l''autre, c''est <b>leur apparence</b> et <b>ton point d''entrée</b>. Pour l''apparence, tu choisis dans ton profil l''<b>habillage</b> ({skins}, du magazine au fanzine, chacun en version claire et sombre), le <b>thème clair ou sombre</b> et la <b>langue</b> (espagnol, catalan, basque, galicien, anglais, français, allemand ou italien) ; une communauté peut avoir son propre habillage, que tu verras sur sa page et dans ses fils.</p>
<p>Pour le point d''entrée, tu choisis ta <b>page d''arrivée</b> (la une, un thème ou une communauté) et <b>tes sources</b> : jusqu''à {mediamax} médias et {newsmax} newsletters toujours à portée de main. Cela personnalise l''accès, pas le jugement : rien de ce que tu choisis ne modifie la une, l''ordre de quoi que ce soit ni le δ d''un envoi. {site} s''installe aussi comme une application sur le mobile et propose des messages privés entre inscrits.</p>

<h2>L''aura</h2>
<p>Les personnes n''ont pas de δ ; elles ont une <b>aura</b>, qui est autre chose et ne mesure qu''une chose : l''engagement envers la communauté. Elle se gagne en étant là et en contribuant (+{auraport} pour chaque envoi à toi qui arrive en une, +{auraenv} pour chaque envoi ou article, +{auracom} pour chaque commentaire, avec un plafond de +{auracap} par jour) et se perd en n''étant pas là ({auradec} par jour, jamais en dessous de {aurastart}). Le maximum est de {auramax}.</p>
<p>L''aura <b>ne donne jamais de porte-voix</b> : elle ne fait pas monter d''actualités, ne classe pas les commentaires, ne pèse pas dans le δ. Elle se voit comme une lumière autour de ton alias, et ouvre quelques portes de confiance : avec plus de {commaura} d''aura tu peux créer des communautés (jusqu''à {commmax}) ; avec plus de {editaura}, corriger tes propres commentaires pendant {editmin} minutes après les avoir publiés ; et avec plus de {seditaura}, tes envois pendant {seditmin} minutes tant qu''ils ne sont pas en une (ce qui est corrigé est marqué comme modifié). Avec {propaura} ou plus, tu peux proposer des médias et des newsletters.</p>

<h2>Les chiffres du jour</h2>
<p>Tous les paramètres de cette page sont ceux en vigueur en ce moment même sur {site} ; si l''équipe les change, cette page change avec eux. Cycle toutes les {cycle} minutes · {minage} minutes minimum dans Nouveautés · δ minimum pour la une {minpub} · décroissance de {decay} par demi-heure · voile de {gm} minutes · δ minimum pour devenir candidate {mincand} · durée de vie en une de {ttl} heures · expiration sans une au bout de {newttl} heures · communautés avec une aura supérieure à {commaura}, jusqu''à {commmax} par personne.</p>' WHERE slug = 'algoritmo.fr';
UPDATE pages SET body = '<h1>Was ist Deltha?</h1>

<p>{site} ist vier Dinge zugleich: <b>ein Nachrichten-Aggregator</b>, in dem Leute Links einreichen und mit ihren Stimmen entscheiden, was auf die Titelseite kommt; <b>ein Quellen-Reader</b>, der per RSS sammelt, was Medien, Newsletter und andere Aggregatoren veröffentlichen, und es ohne Algorithmus zeigt, das Neueste zuerst; <b>ein gegenalgorithmisches soziales Netzwerk</b>, ohne Maschine, die für dich auswählt, was du siehst; und <b>ein Ort für Debatten</b>, mit Threads, die eröffnet, diskutiert und wieder geschlossen werden.</p>

<h2>Woher es kommt</h2>
<p>Von fünf Orten. Aus dem <a href="https://contralgoritmia.com/" rel="noopener">gegenalgorithmischen Manifest</a> stammt die Grundidee: das menschliche Urteil gegen die Vorherrschaft des Algorithmus zurückzugewinnen. Von <b>Menéame</b> der Aggregator: Die Community entscheidet, was auf die Titelseite kommt. Von <b>X</b> das Design: eine klare, schnelle Timeline. Von <b>4chan</b> die Vergänglichkeit: Threads entstehen, werden diskutiert und geschlossen. Und vom <b>Geschäftsmodell von Jot Down</b> die Art, sich zu tragen: ohne programmatische Werbung und ohne Tracking – eine Community, die trägt, was sie liest.</p>

<h2>So funktioniert es</h2>
<p><b>Es ist vergänglich, aber es löscht nichts.</b> Steht ein Beitrag {ttl} Stunden auf der Titelseite, wird er mit seiner Diskussion archiviert: Man kann ihn weiter lesen, aber nicht mehr abstimmen oder kommentieren. Was es nicht auf die Titelseite schafft, wird {newttl} Stunden nach dem Einreichen archiviert. Wichtig ist das Gespräch von jetzt, nicht das, was sich ansammelt.</p>
<p><b>Es hinterlässt keine Spuren.</b> Keine Tracking-Cookies, keine programmatische Werbung, keine Dritten. Du kannst es selbst prüfen, mit den Werkzeugen deines Browsers.</p>
<p><b>Es ist horizontal.</b> Alles erscheint zuerst unter einem Alias, der Name des Autors erst nach {gm} Minuten. So werden die Worte beurteilt, bevor es um die Person geht.</p>
<p><b>Man sieht hinein.</b> Der Bereich Abläufe zeigt live die Warteschlange, das δ jedes Beitrags und jede Stimme, jeden Kommentar und jede Titelseite, sobald sie passieren.</p>
<p><b>Kein verborgener Algorithmus.</b> Was auf die Titelseite kommt, entscheidet eine öffentliche, kurze Formel, sichtbar unter jedem Beitrag: das <b>δ</b>. Sie wird vollständig erklärt unter <a href="/algoritmo.php">So funktioniert das δ</a>.</p>

<h2>Was per RSS hereinkommt</h2>
<p><b>{site} liest auch.</b> Neben dem, was Leute einreichen, sammelt es per RSS, was Medien, Newsletter (auf Substack und anderswo) und andere Aggregatoren veröffentlichen, und zeigt es im eigenen Design, das Neueste zuerst. Ohne Algorithmus und ohne Tracking: RSS ist ein offenes Format, das jede Website anbieten kann.</p>
<p><b>Deine Quellen, griffbereit.</b> In deinem Profil wählst du bis zu {mediamax} Medien und {newsmax} Newsletter; wählst du keine, siehst du die hervorgehobenen. Aggregatoren wie Menéame werden so gezeigt, wie sie sind, mit ihren eigenen Stimmen und Kommentaren. Fehlt dir etwas? Mit {propaura} Aura oder mehr kannst du ein Medium oder einen Newsletter vorschlagen, und die Moderation prüft den Vorschlag.</p>
<p><b>Lesen ist nicht abstimmen.</b> Nichts, was per RSS hereinkommt, landet von selbst in der Warteschlange von {site}. Jede Nachricht hat einen Knopf, um sie nach Neu zu schicken, und von dort geht sie denselben Weg wie jeder Beitrag: Stimmen, δ und Zyklus.</p>

<h2>Die Aura</h2>
<p>Es gibt kein Karma. Es gibt <b>Aura</b>, und sie misst nur eines: dein Engagement für die Community. Man gewinnt sie, indem man da ist und etwas beiträgt, nicht indem man gefällt: +{auraport} für jeden deiner Beiträge, der auf die Titelseite kommt, +{auraenv} für jeden Beitrag oder Artikel, +{auracom} für jeden Kommentar, höchstens +{auracap} pro Tag. Und sie sinkt: Jeden Tag verlierst du {auradec}, bis zum Minimum von {aurastart}. Sie sammelt sich nicht an, sie will gehalten werden. Sie misst, wer da ist, nicht wer da war.</p>
<p><b>Sie macht niemanden lauter.</b> Die Aura hebt keine Nachrichten hoch und sortiert keine Kommentare. Alle sehen dieselbe Titelseite, in derselben Reihenfolge. Sie erscheint als Licht um deinen Alias, sonst nichts. Sie öffnet nur Türen: Mit mehr als {commaura} kannst du Communities gründen, mit mehr als {editaura} deine frisch geschriebenen Kommentare korrigieren und mit mehr als {seditaura} deine Beiträge.</p>

<h2>Was darf man veröffentlichen?</h2>
<p><b>Alles, was das Gesetz erlaubt.</b> Die Grenze setzt keine redaktionelle Linie und kein Geschmack, sondern das Gesetz. Draußen bleiben Beleidigungen, Hassdelikte, Drohungen und alles, was eine Straftat ist. Das steht in den <a href="/normas.php">Nutzungsregeln</a>.</p>
<p><b>Moderiert wird von einer KI, die auf unseren Servern lebt.</b> Kein Beitrag und kein Kommentar verlässt sie zur Prüfung: Keine Dritten schauen mit, was du schreibst. Im Zweifel entscheidet ein Mensch. Jeder kann einen Kommentar melden.</p>
<p><b>Und für alles andere gibt es eine E-Mail-Adresse.</b> Beschwerden, Lob und Vorschläge: <b>info@deltha.eu</b>.</p>

<h2>Themen und Communities</h2>
<p><b>Die Themen legt {site} fest.</b> Wenige und beständige: Aktuelles, Wissenschaft, Kultur … Jeder Beitrag hat eines, und das Thema kann dem δ einen kleinen Schub geben (ein öffentlicher Koeffizient).</p>
<p><b>Die Communities gründen die Leute.</b> Mit mehr als {commaura} Aura kannst du bis zu {commmax} eröffnen: ein Name, eine Farbe und worum es geht. Ein Beitrag kann einer oder mehreren beitreten, und wer sie gegründet hat (oder ein Moderator) kümmert sich um sie. Jede Community kann ein eigenes Design wählen, das für ihre Seite und ihre Threads gilt. Und du kannst eine Community oder ein Thema als Startseite wählen.</p>

<h2>Wie du willst</h2>
<p><b>{skins} Designs.</b> Dieselbe Titelseite, dieselben Zahlen, eine andere Haut: Magazin, Forum, Terminal, Manga, Fanzine … Du wählst sie im Reiter Design, und jedes gibt es in Hell und Dunkel.</p>
<p><b>In deiner Sprache und in deiner Tasche.</b> Acht Sprachen: Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch und Italienisch. Es lässt sich als App aufs Handy installieren, mit privaten Nachrichten und Chat zwischen registrierten Nutzern, und du kannst Beiträge als Favoriten speichern.</p>
<p><b>Was allen gehört und was dir gehört.</b> Titelseite, Kandidaten und Neu sind für alle gleich, in derselben Reihenfolge. Was du selbst einstellst, ist der Weg dorthin: welche Medien und Newsletter du griffbereit hast und ob du beim Öffnen von {site} auf der Titelseite, in einem Thema oder in einer Community landest. Das ändert, wo du ankommst, nicht, was die Community entscheidet.</p>

<h2>Raus aus den Suchmaschinen</h2>
<p><b>Was auf {site} passiert, bleibt auf {site}.</b> Kein Beitrag, kein Kommentar und kein Profil taucht bei Google oder anderen Suchmaschinen auf: Alle Seiten tragen die Markierung <i>noindex</i>. Sichtbar sind nur die Seiten, die das Projekt erklären, wie diese hier.</p>
<p><b>Es sei denn, du willst es.</b> Wenn du einen Artikel schreibst, kannst du ein Häkchen setzen, damit Suchmaschinen ihn indexieren. Es ist dein Text, und du entscheidest; beim Bearbeiten lässt es sich ändern.</p>
<p><b>Und keine KI wird mit dem trainiert, was du schreibst.</b> Die Crawler der künstlichen Intelligenz (von OpenAI, Anthropic, Google, Meta, Common Crawl und Co.) haben keinen Zutritt, auch nicht bei Artikeln, die für Suchmaschinen freigegeben sind.</p>

<h2>Willst du Miteigentümer von {site} werden?</h2>
<p>{site} ist ein Projekt, das allen offensteht. Um Miteigentümer zu werden, verlangen wir kein Geld: nur, dass du die Plattform nutzt. <b>50 % des Eigentums an {site} werden unter allen registrierten Nutzern aufgeteilt, die am {auradl} eine Aura von {auramax} haben.</b></p>
<p>Willst du es ausprobieren? <a href="/registro.php">Registriere dich</a> oder, wenn du schon ein Konto hast, <a href="/login.php">melde dich an</a>.</p>' WHERE slug = 'que-es.de';
UPDATE pages SET body = '<h1>So funktioniert das δ</h1>

<h2>Kein verborgener Algorithmus</h2>
<p>Auf {site} gibt es keine Maschine, die für dich entscheidet, was du siehst. Was auf die Titelseite kommt, entscheidet eine öffentliche, kurze und sichtbare Formel: Sie steht unter jedem Beitrag in Kandidaten und Neu, mit ihren Zahlen und ihrem aktuellen Wert. Diese Seite erklärt sie vollständig. Wer sie versteht, versteht {site}.</p>

<h2>Das δ (Delta)</h2>
<p>Jeder Beitrag hat ein <b>δ</b>: eine Punktzahl, die zusammenfasst, wie sehr die Community ihn unterstützt und wie lange er schon wartet. Sie wird so berechnet:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (positive Stimmen − negative Stimmen) × Themen-Koeffizient − {decay} × halbe Stunden seit dem Einreichen</p>
<p><b>Die Stimmen</b> sind die Stimme der Leute: Jeder registrierte Nutzer kann über jeden Beitrag einmal abstimmen, dafür oder dagegen, und seine Meinung ändern. Über Eigenes kann man nicht abstimmen.</p>
<p><b>Der Themen-Koeffizient</b> ist ein kleiner redaktioneller Schub und der einzige Parameter, den {site} festlegt: Die meisten Themen zählen 1,00; Kultur, Wissenschaft oder Erzählungen zählen 1,10, also 10 % mehr pro Stimme. Die Koeffizienten sind öffentlich, du siehst sie in jeder Formel.</p>
<p><b>Das Alter</b> zieht ab: {decay} Punkte für jede halbe Stunde seit dem Einreichen. Deshalb ist {site} von heute und nicht von immer: Eine Nachricht mit zwei Stimmen, die seit einem Tag in der Warteschlange steht, zählt weniger als eine frisch eingetroffene mit denselben Stimmen. Nichts bleibt ewig in der Warteschlange.</p>

<h2>Der Zyklus</h2>
<p>Alle <b>{cycle} Minuten</b> läuft ein Zyklus, immer gleich, ohne Ausnahmen und ohne manuelle Eingriffe:</p>
<p><b>1. Von Neu zu Kandidaten.</b> Unter den Beiträgen, die seit mindestens <b>{minage} Minuten</b> in Neu stehen, steigt <b>nur einer</b> auf: der mit dem höchsten δ, sofern sein δ mindestens <b>{mincand}</b> beträgt. Ohne Stimmen kein Aufstieg, und schafft es kein Beitrag, steigt in diesem Zyklus nichts auf. Die Mindestzeit gibt es, damit ein Beitrag nicht nur deshalb aufsteigt, weil er in einem ruhigen Moment kam; er muss vorher gesehen und bewertet werden. Was <b>{newttl} Stunden</b> lang nicht auf die Titelseite kommt, wird archiviert: Auch die Warteschlange ist nicht für immer.</p>
<p><b>2. Von Kandidaten zur Titelseite.</b> Unter allen Kandidaten kommt <b>der mit dem höchsten δ</b> auf die Titelseite, sofern sein δ mindestens <b>{minpub}</b> beträgt. Schafft es keiner, wird in diesem Zyklus nichts veröffentlicht. Die Titelseite wird nicht aufgefüllt, nur um sie zu füllen.</p>
<p><b>3. Der Schleier hebt sich.</b> {gm} Minuten nach der Veröffentlichung eines Beitrags oder Kommentars weicht der Alias des Autors seinem Namen. Vorher sind alle Aliasse mit Halo: Beurteilt werden die Worte, nicht die Unterschrift.</p>
<p><b>4. Archiv.</b> Was seit {ttl} Stunden auf der Titelseite steht, wird archiviert. Man kann es weiter lesen, aber nicht mehr abstimmen oder kommentieren: Das Gespräch war, was es war.</p>
<p>Der Bereich <b>Abläufe</b> zeigt all das live: die Warteschlange, sortiert nach δ, mit einer Schätzung, wann jeder Beitrag aufsteigt, und einen Strom mit jedem Beitrag, jeder Stimme, jedem Kommentar und jeder Titelseite, sobald sie passieren.</p>

<h2>Themen und Communities</h2>
<p>Jeder Beitrag wird zweimal eingeordnet. <b>Das Thema</b> legt {site} fest: Es sind wenige, beständige (Aktuelles, Wissenschaft, Kultur …), und jedes hat seinen öffentlichen Koeffizienten. Jeder Beitrag hat ein Thema.</p>
<p><b>Die Community</b> bestimmen die Leute. Wer mehr als <b>{commaura} Aura</b> hat, kann bis zu <b>{commmax}</b> gründen: ein Name, eine Farbe und ein Satz, der sagt, worum es geht. Ein Beitrag kann zusätzlich zu seinem Thema einer Community beitreten, und so sammelt sich, was eine Gruppe interessiert, ohne dass {site} es entscheiden muss. Wer eine Community gründet, kümmert sich um sie: bearbeitet Name, Beschreibung, Farbe und visuelles Design; Moderatoren können dasselbe tun und sie, falls nötig, ausblenden.</p>
<p>Communities <b>berühren das δ nicht</b>: Sie geben keinen Koeffizienten, heben nichts und senken nichts. Sie sind ein Ort, kein Lautsprecher. Was du aber tun kannst: in deinem Profil eine Community (oder ein Thema) als <b>Startseite</b> wählen. Wenn du {site} öffnest, landest du dort, und der Reiter Titelseite bleibt die Titelseite für alle.</p>

<h2>Was per RSS hereinkommt</h2>
<p>{site} sammelt per RSS auch, was Medien, Newsletter und andere Aggregatoren veröffentlichen. Dort gibt es <b>kein δ und keinen Zyklus</b>: Jede Quelle wird in der Reihenfolge der Veröffentlichung gezeigt, das Neueste zuerst, ohne Sortierung nach Vorlieben oder Erfolg. Bei den Aggregatoren sieht man ihre eigenen Stimmen und Kommentare, die von ihrer Community stammen, nicht von {site}.</p>
<p>Was per RSS hereinkommt, <b>landet nicht von selbst in der Warteschlange</b>. Schickt es jemand mit dem Knopf an jeder Nachricht nach Neu, ist es von da an ein Beitrag wie jeder andere: Stimmen, δ und Zyklus, wie alle.</p>

<h2>Was das δ nicht tut</h2>
<p>Es lernt nichts über dich. Es weiß nicht, was dir gefällt. Es zeigt dir keine andere Titelseite als deinem Nachbarn. Es belohnt niemanden, der mehr Follower hat, denn hier hat niemand Follower. Es schaut nicht, wann du normalerweise vorbeikommst oder wie lange du liest. Dieselbe Formel, dieselben Zahlen, dieselbe Titelseite für alle.</p>

<h2>Was du selbst wählst</h2>
<p>Titelseite, Kandidaten und Neu sind <b>für alle gleich</b>, in derselben Reihenfolge. Was sich von Person zu Person ändert, ist, <b>wie sie aussehen</b> und <b>wo du hereinkommst</b>. Für das Aussehen wählst du in deinem Profil das <b>visuelle Design</b> ({skins}, vom Magazin bis zum Fanzine, jedes in Hell und Dunkel), das <b>helle oder dunkle Farbschema</b> und die <b>Sprache</b> (Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch oder Italienisch); eine Community kann ihr eigenes Design haben, das du auf ihrer Seite und in ihren Threads siehst.</p>
<p>Für den Einstieg wählst du deine <b>Startseite</b> (die Titelseite, ein Thema oder eine Community) und <b>deine Quellen</b>: bis zu {mediamax} Medien und {newsmax} Newsletter, immer griffbereit. Das personalisiert den Zugang, nicht das Urteil: Nichts, was du wählst, verändert die Titelseite, die Reihenfolge von irgendetwas oder das δ irgendeines Beitrags. {site} lässt sich außerdem als App aufs Handy installieren und hat private Nachrichten zwischen registrierten Nutzern.</p>

<h2>Die Aura</h2>
<p>Menschen haben kein δ; sie haben <b>Aura</b>, und die ist etwas anderes und misst nur eines: das Engagement für die Community. Man gewinnt sie, indem man da ist und beiträgt (+{auraport} für jeden deiner Beiträge, der auf die Titelseite kommt, +{auraenv} für jeden Beitrag oder Artikel, +{auracom} für jeden Kommentar, mit einer Obergrenze von +{auracap} pro Tag), und man verliert sie, wenn man nicht da ist ({auradec} pro Tag, nie unter {aurastart}). Das Maximum ist {auramax}.</p>
<p>Die Aura <b>macht niemanden lauter</b>: Sie hebt keine Nachrichten hoch, sortiert keine Kommentare, zählt nicht im δ. Sie erscheint als Licht um deinen Alias und öffnet ein paar Türen des Vertrauens: Mit mehr als {commaura} Aura kannst du Communities gründen (bis zu {commmax}), mit mehr als {editaura} deine eigenen Kommentare {editmin} Minuten lang nach dem Veröffentlichen korrigieren und mit mehr als {seditaura} deine Beiträge {seditmin} Minuten lang, solange sie nicht auf der Titelseite stehen (Korrigiertes wird als bearbeitet markiert). Mit {propaura} oder mehr kannst du Medien und Newsletter vorschlagen.</p>

<h2>Die Zahlen von heute</h2>
<p>Alle Parameter auf dieser Seite sind die, die gerade auf {site} gelten; ändert das Team sie, ändert sich diese Seite mit. Zyklus alle {cycle} Minuten · mindestens {minage} Minuten in Neu · Mindest-δ für die Titelseite {minpub} · Abzug {decay} pro halbe Stunde · Schleier von {gm} Minuten · Mindest-δ für Kandidaten {mincand} · {ttl} Stunden auf der Titelseite · Ablauf ohne Titelseite nach {newttl} Stunden · Communities ab einer Aura über {commaura}, bis zu {commmax} pro Person.</p>' WHERE slug = 'algoritmo.de';
UPDATE pages SET body = '<h1>Cos''è Deltha?</h1>

<p>{site} è quattro cose insieme: <b>un aggregatore di notizie</b>, dove la gente invia link e decide con i propri voti cosa arriva in prima pagina; <b>un lettore di fonti</b>, che raccoglie via RSS ciò che pubblicano testate, newsletter e altri aggregatori e lo mostra, senza algoritmo, dal più recente al più vecchio; <b>un social network controalgoritmico</b>, senza una macchina che scelga per te cosa vedere; e <b>uno spazio di dibattito</b>, con discussioni che si aprono, si accendono e si chiudono.</p>

<h2>Da dove viene</h2>
<p>Da cinque posti. Dal <a href="https://contralgoritmia.com/" rel="noopener">manifesto controalgoritmico</a> viene l''idea di fondo: recuperare il giudizio umano contro l''egemonia dell''algoritmo. Da <b>Menéame</b>, l''aggregatore: è la comunità a decidere cosa arriva in prima pagina. Da <b>X</b>, il design: una timeline pulita e veloce. Da <b>4chan</b>, l''effimero: le discussioni nascono, si accendono e si chiudono. E dal <b>modello di business di Jot Down</b>, il modo di reggersi: niente pubblicità programmatica né tracciamento, una comunità che sostiene ciò che legge.</p>

<h2>Come funziona</h2>
<p><b>È effimera, ma non cancella.</b> Quando un invio è in prima pagina da {ttl} ore, viene archiviato con la sua discussione: si può continuare a leggerlo, ma non si vota né si commenta più. Ciò che non arriva in prima pagina viene archiviato {newttl} ore dopo l''invio. Conta la conversazione di adesso, non quella che si accumula.</p>
<p><b>Non lascia tracce.</b> Niente cookie di tracciamento, niente pubblicità programmatica, niente terze parti. Puoi verificarlo aprendo gli strumenti del tuo browser.</p>
<p><b>È orizzontale.</b> Tutto si pubblica sotto un alias e il nome dell''autore compare solo dopo {gm} minuti, così si giudicano le parole prima di chi le dice.</p>
<p><b>Si vede dentro.</b> La sezione Processi mostra in diretta la coda, il δ di ogni invio e ogni voto, commento e prima pagina man mano che accadono.</p>
<p><b>Niente algoritmo nascosto.</b> Cosa arriva in prima pagina lo decide una formula pubblica, breve e visibile sotto ogni invio: il <b>δ</b>. È spiegata per intero in <a href="/algoritmo.php">Come funziona il δ</a>.</p>

<h2>Quello che arriva via RSS</h2>
<p><b>{site} legge anche.</b> Oltre a ciò che invia la gente, raccoglie via RSS ciò che pubblicano testate, newsletter (su Substack e altrove) e altri aggregatori, e lo mostra con il suo aspetto, dal più recente. Senza algoritmo e senza tracciamento: l''RSS è un formato aperto che qualsiasi sito può offrire.</p>
<p><b>Le tue fonti, a portata di mano.</b> Nel tuo profilo scegli fino a {mediamax} testate e {newsmax} newsletter; se non scegli, vedrai quelle in evidenza. Gli aggregatori, come Menéame, si vedono così come sono, con i loro voti e commenti. Ti manca qualcosa? Con {propaura} di aura o più puoi proporre una testata o una newsletter, e la moderazione la esamina.</p>
<p><b>Leggere non è votare.</b> Niente di ciò che arriva via RSS entra da solo nella coda di {site}. Ogni notizia ha un pulsante per inviarla a Nuove, e da lì segue il percorso di qualsiasi invio: voti, δ e ciclo.</p>

<h2>L''aura</h2>
<p>Non c''è karma. C''è l''<b>aura</b>, e misura una cosa sola: il tuo impegno verso la comunità. Si guadagna partecipando e contribuendo, non piacendo: +{auraport} per ogni tuo invio che arriva in prima pagina, +{auraenv} per ogni invio o articolo, +{auracom} per ogni commento, al massimo +{auracap} al giorno. E scende: ogni giorno perdi {auradec}, fino al minimo di {aurastart}. Non si accumula: si mantiene. Misura chi c''è, non chi c''era.</p>
<p><b>Non dà mai un megafono.</b> L''aura non fa salire notizie né ordina commenti. Tutti vedono la stessa prima pagina, nello stesso ordine. Si vede come una luce intorno al tuo alias, nient''altro. Apre soltanto delle porte: con più di {commaura} puoi creare comunità; con più di {editaura}, correggere i commenti appena scritti; e con più di {seditaura}, i tuoi invii.</p>

<h2>Cosa si può pubblicare?</h2>
<p><b>Tutto ciò che la legge consente.</b> Il limite non lo fissa una linea editoriale né un gusto: lo fissa la legge. Restano fuori gli insulti, i crimini d''odio, le minacce e tutto ciò che è reato. È tutto scritto nelle <a href="/normas.php">regole d''uso</a>.</p>
<p><b>Modera un''IA che vive sui nostri server.</b> Nessun invio né commento ne esce per essere revisionato: non ci sono terze parti a guardare cosa scrivi. Quando ha dubbi, decide una persona. Chiunque può segnalare un commento.</p>
<p><b>E c''è un indirizzo email per tutto il resto.</b> Lamentele, complimenti e proposte: <b>info@deltha.eu</b>.</p>

<h2>Temi e comunità</h2>
<p><b>I temi li stabilisce {site}.</b> Pochi e stabili: attualità, scienza, cultura… Ogni invio ne ha uno, e il tema può dare una piccola spinta al δ (un coefficiente pubblico).</p>
<p><b>Le comunità le crea la gente.</b> Con più di {commaura} di aura puoi aprirne fino a {commmax}: un nome, un colore e di cosa parla. Un invio può aggiungersi a una o più comunità, e chi l''ha creata (o un moderatore) se ne prende cura. Ogni comunità può scegliere uno stile proprio, che si applica alla sua pagina e alle sue discussioni. E tu puoi scegliere una comunità o un tema come pagina di ingresso.</p>

<h2>A modo tuo</h2>
<p><b>{skins} stili.</b> Stessa prima pagina, stessi numeri, pelle diversa: da rivista, da forum, da terminale, da manga, da fanzine… Si sceglie nella scheda Stile, e ognuno ha la versione chiara e scura.</p>
<p><b>Nella tua lingua e in tasca.</b> Otto lingue: spagnolo, catalano, basco, galiziano, inglese, francese, tedesco e italiano. Si installa come app sul telefono, con messaggi privati e chat tra utenti registrati, e puoi salvare gli invii nei preferiti.</p>
<p><b>Ciò che è di tutti e ciò che è tuo.</b> La prima pagina, le candidate e le nuove sono le stesse per tutti, nello stesso ordine. Quello che personalizzi è il percorso: quali testate e newsletter tieni a portata di mano, e se aprendo {site} entri in prima pagina, in un tema o in una comunità. Cambia da dove arrivi, non cosa decide la comunità.</p>

<h2>Fuori dai motori di ricerca</h2>
<p><b>Quello che succede su {site} resta su {site}.</b> Nessun invio, commento o profilo compare su Google o su altri motori di ricerca: tutte le pagine portano il marcatore <i>noindex</i>. Si lasciano vedere solo le pagine che spiegano il progetto, come questa.</p>
<p><b>A meno che non lo voglia tu.</b> Se scrivi un articolo, puoi spuntare la casella perché i motori di ricerca lo indicizzino. È il tuo testo e decidi tu; puoi cambiarlo quando lo modifichi.</p>
<p><b>E nessuna IA si addestra con quello che scrivi.</b> I crawler di intelligenza artificiale (quelli di OpenAI, Anthropic, Google, Meta, Common Crawl e compagnia) hanno l''ingresso vietato, anche negli articoli aperti ai motori di ricerca.</p>

<h2>Vuoi essere proprietario di {site}?</h2>
<p>{site} è un progetto aperto a tutti. Per diventarne comproprietario non ti chiediamo soldi: solo di usare la piattaforma. <b>Il 50 % della proprietà di {site} sarà ripartito tra tutti gli utenti registrati che avranno aura {auramax} il {auradl}.</b></p>
<p>Vuoi provarla? <a href="/registro.php">Registrati</a> o, se hai già un account, <a href="/login.php">accedi</a>.</p>' WHERE slug = 'que-es.it';
UPDATE pages SET body = '<h1>Come funziona il δ</h1>

<h2>Niente algoritmo nascosto</h2>
<p>Su {site} non c''è una macchina che decide per te cosa vedere. Cosa arriva in prima pagina lo decide una formula pubblica, breve e visibile: compare sotto ogni invio in Candidate e Nuove, con i suoi numeri e il suo valore in quel momento. Questa pagina la spiega per intero. Se la capisci, capisci {site}.</p>

<h2>Il δ (delta)</h2>
<p>Ogni invio ha un <b>δ</b>: un punteggio che riassume quanto lo sostiene la comunità e da quanto tempo è in attesa. Si calcola così:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (voti positivi − voti negativi) × coefficiente del tema − {decay} × mezz''ore dall''invio</p>
<p><b>I voti</b> sono la voce della gente: ogni utente registrato può votare una volta ogni invio, a favore o contro, e cambiare idea. Non si possono votare le proprie cose.</p>
<p><b>Il coefficiente del tema</b> è una piccola spinta editoriale ed è l''unico parametro che stabilisce {site}: la maggior parte dei temi vale 1,00; cultura, scienza o racconti valgono 1,10, cioè il 10 % in più per voto. I coefficienti sono pubblici e li vedrai in ogni formula.</p>
<p><b>L''anzianità</b> sottrae: {decay} punti per ogni mezz''ora che passa dall''invio. È ciò che rende {site} una cosa di oggi e non di sempre: una notizia con due voti che è in coda da un giorno vale meno di una appena arrivata con gli stessi voti. Niente dura per sempre in coda.</p>

<h2>Il ciclo</h2>
<p>Ogni <b>{cycle} minuti</b> avviene un ciclo, sempre uguale, senza eccezioni né interventi manuali:</p>
<p><b>1. Da Nuove a Candidate.</b> Tra gli invii che sono in Nuove da almeno <b>{minage} minuti</b>, ne sale <b>uno solo</b>: quello con il δ più alto, purché il suo δ sia almeno <b>{mincand}</b>. Senza voti non si sale, e se nessun invio ci arriva, in quel ciclo non sale niente. Il tempo minimo esiste perché un invio non salga solo perché è arrivato in un momento tranquillo; prima deve essere visto e votato. Ciò che passa <b>{newttl} ore</b> senza arrivare in prima pagina viene archiviato: nemmeno la coda è per sempre.</p>
<p><b>2. Da Candidate a Prima pagina.</b> Tra tutte le candidate, va in prima pagina <b>quella con il δ più alto</b>, purché il suo δ sia almeno <b>{minpub}</b>. Se nessuna ci arriva, in quel ciclo non si pubblica niente. La prima pagina non si riempie tanto per riempirla.</p>
<p><b>3. Il velo si alza.</b> {gm} minuti dopo la pubblicazione di qualsiasi invio o commento, l''alias dell''autore lascia il posto al suo nome. Prima, sono tutti alias con un alone: si giudicano le parole, non la firma.</p>
<p><b>4. Archivio.</b> Ciò che è in prima pagina da {ttl} ore viene archiviato. Si può continuare a leggerlo, ma non si vota né si commenta più: la conversazione è stata quello che è stata.</p>
<p>La sezione <b>Processi</b> mostra tutto questo in diretta: la coda ordinata per δ con una stima di quando salirà ogni invio, e un flusso con ogni invio, voto, commento e prima pagina man mano che accadono.</p>

<h2>Temi e comunità</h2>
<p>Ogni invio viene classificato due volte. <b>Il tema</b> lo stabilisce {site}: sono pochi, stabili (attualità, scienza, cultura…) e ognuno ha il suo coefficiente pubblico. Ogni invio ha un tema.</p>
<p><b>La comunità</b> la sceglie la gente. Chiunque abbia più di <b>{commaura} di aura</b> può crearne fino a <b>{commmax}</b>: un nome, un colore e una frase che dica di cosa parla. Un invio può aggiungersi a una comunità oltre che al suo tema, e così si raccoglie ciò che interessa a un gruppo senza che {site} debba deciderlo. Chi crea una comunità se ne prende cura: ne modifica il nome, la descrizione, il colore e lo stile visivo; i moderatori possono fare lo stesso e, se serve, nasconderla.</p>
<p>Le comunità <b>non toccano il δ</b>: non danno coefficienti, non fanno salire né scendere niente. Sono un luogo, non un megafono. Quello che puoi fare è scegliere una comunità (o un tema) come <b>pagina di ingresso</b> nel tuo profilo: aprendo {site} arriverai lì, e la scheda Prima pagina resterà la prima pagina di tutti.</p>

<h2>Quello che arriva via RSS</h2>
<p>{site} raccoglie via RSS anche ciò che pubblicano testate, newsletter e altri aggregatori. Lì <b>non ci sono né δ né ciclo</b>: ogni fonte si mostra in ordine di pubblicazione, dal più recente, senza ordinare per gusti né per successo. Negli aggregatori si vedono i loro voti e commenti, che sono quelli della loro comunità, non di {site}.</p>
<p>Ciò che arriva via RSS <b>non entra da solo nella coda</b>. Se qualcuno lo invia a Nuove con il pulsante di ogni notizia, da lì in poi è un invio come un altro: voti, δ e ciclo, come tutti.</p>

<h2>Cosa il δ non fa</h2>
<p>Non impara da te. Non sa cosa ti piace. Non ti mostra una prima pagina diversa da quella del tuo vicino. Non premia chi ha più follower, perché qui nessuno ha follower. Non guarda a che ora entri di solito né quanto tempo passi a leggere. La stessa formula, gli stessi numeri, la stessa prima pagina per tutti.</p>

<h2>Cosa scegli tu</h2>
<p>La prima pagina, le candidate e le nuove sono <b>le stesse per tutti</b>, nello stesso ordine. Quello che cambia da una persona all''altra è <b>come si vedono</b> e <b>da dove entri</b>. Per come si vedono, nel tuo profilo scegli lo <b>stile visivo</b> ({skins}, dalla rivista alla fanzine, ognuno con versione chiara e scura), il <b>tema chiaro o scuro</b> e la <b>lingua</b> (spagnolo, catalano, basco, galiziano, inglese, francese, tedesco o italiano); una comunità può avere il suo stile, che vedrai nella sua pagina e nelle sue discussioni.</p>
<p>Per da dove entri, scegli la tua <b>pagina di ingresso</b> (la prima pagina, un tema o una comunità) e <b>le tue fonti</b>: fino a {mediamax} testate e {newsmax} newsletter sempre a portata di mano. Questo personalizza l''accesso, non il giudizio: niente di ciò che scegli altera la prima pagina, l''ordine di alcunché né il δ di alcun invio. {site} si installa anche come app sul telefono e ha messaggi privati tra utenti registrati.</p>

<h2>L''aura</h2>
<p>Le persone non hanno un δ; hanno l''<b>aura</b>, che è un''altra cosa e ne misura una sola: l''impegno verso la comunità. Si guadagna partecipando e contribuendo (+{auraport} per ogni tuo invio che arriva in prima pagina, +{auraenv} per ogni invio o articolo, +{auracom} per ogni commento, con un tetto di +{auracap} al giorno) e si perde non partecipando ({auradec} ogni giorno, mai sotto {aurastart}). Il massimo è {auramax}.</p>
<p>L''aura <b>non dà mai un megafono</b>: non fa salire notizie, non ordina commenti, non pesa sul δ. Si vede come una luce intorno al tuo alias, e apre alcune porte di fiducia: con più di {commaura} di aura puoi creare comunità (fino a {commmax}); con più di {editaura}, correggere i tuoi commenti per {editmin} minuti dopo averli pubblicati; e con più di {seditaura}, i tuoi invii per {seditmin} minuti finché non arrivano in prima pagina (ciò che viene corretto resta contrassegnato come modificato). Con {propaura} o più puoi proporre testate e newsletter.</p>

<h2>I numeri di oggi</h2>
<p>Tutti i parametri di questa pagina sono quelli in vigore in questo momento su {site}; se il team li cambia, questa pagina cambia con loro. Ciclo ogni {cycle} minuti · {minage} minuti minimi in Nuove · δ minimo per la prima pagina {minpub} · decadimento {decay} ogni mezz''ora · velo di {gm} minuti · δ minimo per diventare candidata {mincand} · vita in prima pagina di {ttl} ore · scadenza senza prima pagina dopo {newttl} ore · comunità con aura maggiore di {commaura}, fino a {commmax} a persona.</p>' WHERE slug = 'algoritmo.it';
