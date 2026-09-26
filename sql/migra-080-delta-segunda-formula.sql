-- migra-080 — el δ, segunda formula (26/09/2026). Tras la critica externa (proyecto: claude/algoritmo-critica-y-montecarlo.md):
--   δ = signo(n)·√|n| + bonus del tema (si n > 0) − decaimiento de la portada × max(0, medias horas − medias horas minimas)
--   n = votos que cuentan: uno por conexion y envio (votes.fp) y solo de cuentas con mas de vote_quarantine_hours.
--   Ciclo: minuto de ejecucion imprevisible dentro de la ventana (cycle_jitter), sorteo ponderado por δ (cycle_lottery),
--   cesion del turno vacio (cycle_cede_turno), umbral y decaimiento por portada, radar una semana.
--   Textos de /algoritmo.php reescritos en nueve idiomas (sustituye el cuerpo entero: si se retoco en admin > Paginas, reaplicar) y
--   duraciones en palabras en /que-es.php ({newttltxt}, {ttltxt}). Marcadores nuevos en page_body() (lib/app.php).

-- temas: el coeficiente multiplicador pasa a ser un bonus que se suma (1,10 -> +0,30)
ALTER TABLE topics ADD COLUMN bonus DECIMAL(4,2) NOT NULL DEFAULT 0.00 AFTER name;
UPDATE topics SET bonus = ROUND((coef - 1) * 3, 2) WHERE coef > 1;
ALTER TABLE topics DROP COLUMN coef;

-- votos: huella (HMAC de ip + envio con el secreto del sitio, truncado; nunca la IP) para que varias cuentas desde la misma conexion cuenten como un voto
ALTER TABLE votes ADD COLUMN fp CHAR(16) DEFAULT NULL AFTER value, ADD KEY idx_story_fp (story_id, fp);

-- envios: votos que cuentan (los recalcula el ciclo para el radar y votar.php al votar)
ALTER TABLE stories ADD COLUMN eff_pos INT NOT NULL DEFAULT 0 AFTER votes_neg, ADD COLUMN eff_neg INT NOT NULL DEFAULT 0 AFTER eff_pos;
UPDATE stories SET eff_pos = votes_pos, eff_neg = votes_neg;

-- config: umbral y decaimiento por portada (el umbral hereda el valor que hubiera), cuarentena, sorteo, cesion, jitter; radar una semana
INSERT IGNORE INTO config (ckey, cvalue, descr)
  SELECT 'delta_min_publish_medios', cvalue, 'Delta minimo para publicar en Medios' FROM config WHERE ckey = 'delta_min_publish';
INSERT IGNORE INTO config (ckey, cvalue, descr)
  SELECT 'delta_min_publish_substacks', cvalue, 'Delta minimo para publicar en Substacks' FROM config WHERE ckey = 'delta_min_publish';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('delta_min_publish_medios',    '1',    'Delta minimo para publicar en Medios'),
('delta_min_publish_substacks', '1',    'Delta minimo para publicar en Substacks'),
('decay_medios',                '0.05', 'Decremento de delta por media hora de espera en Medios (desde que es elegible)'),
('decay_substacks',             '0.02', 'Decremento de delta por media hora de espera en Substacks (mas lento: lo suyo no caduca con la actualidad)'),
('vote_quarantine_hours',       '24',   'Horas desde que se crea una cuenta hasta que sus votos cuentan en el delta (se ven desde el principio)'),
('cycle_lottery',               '1',    '1 = entre las que superan el minimo se sortea con probabilidad proporcional al delta; 0 = gana la de mayor delta'),
('cycle_cede_turno',            '1',    '1 = si a la portada del turno no le llega nada, publica en la otra'),
('cycle_jitter',                '1',    '1 = el minuto de ejecucion dentro de cada ventana de cycle_minutes depende del secreto del sitio (contra el sniping); 0 = minuto 0');
DELETE FROM config WHERE ckey IN ('delta_min_publish', 'decay_per_halfhour', 'decay_mode');
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('nuevas_ttl_hours', '168', 'Horas que puede pasar un envio en el radar sin llegar a portada antes de archivarse (168 = una semana; 0 = nunca)');
UPDATE config SET cvalue = '168', descr = 'Horas que puede pasar un envio en el radar sin llegar a portada antes de archivarse (168 = una semana; 0 = nunca)' WHERE ckey = 'nuevas_ttl_hours';
UPDATE config SET descr = 'Minutos entre ciclos del cron; los ciclos alternan portada (Medios, Substacks) y corren en un minuto imprevisible de cada ventana' WHERE ckey = 'cycle_minutes';

-- textos: «Como funciona el δ» en nueve idiomas
UPDATE pages SET body = '<h1>Cómo funciona el δ</h1>

<h2>Sin algoritmo oculto</h2>
<p>En {site} no hay una máquina que decida por ti qué ver. Lo que llega a portada lo decide una fórmula pública, corta y visible: se muestra bajo cada envío del radar, con sus números y su valor en ese momento. Esta página la explica entera. Si la entiendes, entiendes {site}.</p>

<h2>El δ (delta)</h2>
<p>Cada envío tiene un <b>δ</b>: una puntuación que resume cuánto lo apoya la comunidad y cuánto tiempo lleva esperando. Se calcula así:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(votos que cuentan) + bonus del tema − decaimiento × medias horas de espera</p>
<p><b>Los votos</b> son la voz de la gente: cada registrado puede votar una vez cada envío, a favor o en contra, y cambiar de opinión. No se puede votar lo propio. En el δ entra la diferencia entre positivos y negativos, con dos reglas: si varias cuentas votan el mismo envío desde la misma conexión, cuentan como un solo voto (se guarda una huella del voto, nunca la dirección); y los votos de una cuenta empiezan a contar {quartxt} después de crearse. Se ven desde el primer momento; el δ espera.</p>
<p><b>La raíz</b> aplana el entusiasmo: cuatro votos valen 2, nueve valen 3, cien valen 10. Con un puñado de votos se llega a portada; con muchos no se aplasta a los demás. Un neto negativo resta con la misma raíz.</p>
<p><b>El bonus del tema</b> es un pequeño empujón editorial y es el único parámetro que pone {site}: la mayoría de los temas no suman nada; {bonustemas} suman ese bonus a cualquier envío suyo con apoyo. Se suma, no multiplica: no agranda los votos negativos ni acelera el decaimiento. Los bonus son públicos y los verás en cada fórmula.</p>
<p><b>La antigüedad</b> resta: {decay} puntos por cada media hora de espera en Medios y {decays} en Substacks, contando desde que el envío puede llegar a portada (a los {minage} minutos), no desde que se envió. Es lo que hace que Medios sea de hoy y que Substacks pueda ser de siempre: una newsletter buena no caduca con la actualidad, y su reloj va más despacio.</p>
<h2>Dos portadas y un radar</h2>
<p>Todo lo que se envía entra en el <b>radar</b>. Desde ahí puede llegar a una de las dos portadas: <b>Medios</b>, para las noticias de periódicos, revistas y demás webs, y <b>Substacks</b>, para las newsletters. Un envío va a Substacks si enlaza a una dirección de substack.com o a una newsletter del catálogo de {site}; todo lo demás va a Medios. Cada envío compite solo con los de su portada.</p>
<p>Los <b>originales</b>, los textos que escribe la propia comunidad, no pasan por el radar ni van a portada: tienen su pestaña, ordenada por fecha, donde se leen, se votan y se comentan.</p>

<h2>El ciclo</h2>
<p>Cada <b>{cycle} minutos</b> ocurre un ciclo, siempre igual, sin intervención manual:</p>
<p><b>1. Del radar a la portada.</b> Los ciclos se turnan: uno publica en Medios, el siguiente en Substacks, y así sucesivamente. En cada ciclo, entre los envíos de la portada a la que le toca que llevan al menos <b>{minage} minutos</b> en el radar y tienen un δ de al menos <b>{minpub}</b> (Medios) o <b>{minpubs}</b> (Substacks), se publica <b>uno solo</b>, elegido <b>a suertes</b>: cada candidato tiene tantas papeletas como δ, así que el de mayor δ es el más probable, pero no está garantizado, y los que le siguen también salen a veces. Sin votos no se llega. Si a la portada del turno no le llega nada, el turno pasa a la otra; si tampoco, ese ciclo no se publica nada: la portada no se rellena por rellenar. El tiempo mínimo existe para que un envío no suba solo porque llegó en un momento tranquilo; tiene que ser visto y votado antes.</p>
<p><b>Cuándo, exactamente.</b> Dentro de cada tramo de {cycle} minutos, el minuto en que corre el ciclo cambia y no se anuncia; {site} dice «antes de las tal y cual». Así nadie puede guardarse los votos para soltarlos un segundo antes: quien quiera ganar tiene que convencer, no cronometrar. Y como es un sorteo, tampoco sirve afinar el último voto para sacarle una décima al de al lado.</p>
<p><b>2. Caducidad.</b> Lo que pasa <b>{newttltxt}</b> en el radar sin llegar a portada se archiva: el radar tampoco es para siempre.</p>
<p><b>3. El velo se levanta.</b> A los {gm} minutos de publicarse cualquier envío o comentario, el alias de su autor deja paso a su nombre. Antes, todos son alias con un halo: se juzgan las palabras, no la firma.</p>
<p><b>4. Archivo.</b> Lo que lleva {ttltxt} en portada se archiva. Se puede seguir leyendo, pero ya no se vota ni se comenta: la conversación fue lo que fue.</p>
<p>La sección <b>Procesos</b> muestra todo esto en directo: el radar ordenado por δ, con la portada a la que va cada envío y una estimación de cuándo llega, y un flujo con cada envío, voto, comentario y portada según ocurren.</p>

<h2>Temas y comunidades</h2>
<p>Cada envío se clasifica dos veces. <b>El tema</b> lo pone {site}: son pocos, estables (actualidad, ciencia, cultura…) y cada uno tiene su bonus público (casi siempre cero). Todo envío lleva un tema.</p>
<p><b>La comunidad</b> la pone la gente. Cualquiera con más de <b>{commaura} de aura</b> puede crear hasta <b>{commmax}</b>: un nombre, un color y una frase que diga de qué va. Un envío puede sumarse a una comunidad además de a su tema, y así se agrupa lo que a un grupo le interesa sin que {site} tenga que decidirlo. Quien crea una comunidad la cuida: edita su nombre, su descripción, su color y su plantilla visual; los moderadores pueden hacer lo mismo y, si hace falta, ocultarla.</p>
<p>Las comunidades <b>no tocan el δ</b>: no dan bonus, no suben ni bajan nada. Son un lugar, no un altavoz. Lo que sí puedes hacer es elegir una comunidad (o un tema) como <b>página de entrada</b> en tu perfil: al abrir {site} llegarás allí, y las pestañas Medios y Substacks seguirán siendo las portadas de todos.</p>

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
<p>Todos los parámetros de esta página son los que están en vigor ahora mismo en {site}; si el equipo los cambia, esta página cambia con ellos. Ciclo cada {cycle} minutos, alternando Medios y Substacks, a suertes según el δ · {minage} minutos mínimos en el radar · δ mínimo para portada {minpub} (Medios) y {minpubs} (Substacks) · decaimiento {decay} (Medios) y {decays} (Substacks) por media hora de espera · los votos cuentan {quartxt} después de crearse la cuenta · bonus: {bonustemas} · velo de {gm} minutos · vida en portada de {ttltxt} · caducidad en el radar tras {newttltxt} · comunidades con aura mayor que {commaura}, hasta {commmax} por persona.</p>' WHERE slug = 'algoritmo';
UPDATE pages SET body = '<h1>How δ works</h1>

<h2>No hidden algorithm</h2>
<p>On {site} there''s no machine deciding what you see. What makes the front page is decided by a public formula, short and visible: it''s shown under every post in the radar, with its numbers and its value at that moment. This page explains it in full. If you understand it, you understand {site}.</p>

<h2>The δ (delta)</h2>
<p>Every post has a <b>δ</b>: a score that sums up how much the community backs it and how long it has been waiting. It is worked out like this:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(votes that count) + topic bonus − decay × half hours of waiting</p>
<p><b>Votes</b> are the people''s voice: every registered member can vote once on each post, up or down, and change their mind. You cannot vote for your own. What goes into δ is the difference between up and down votes, with two rules: if several accounts vote on the same post from the same connection, they count as a single vote (a fingerprint of the vote is kept, never the address); and an account''s votes start counting {quartxt} after it is created. They are visible from the first moment; δ waits.</p>
<p><b>The square root</b> flattens enthusiasm: four votes are worth 2, nine are worth 3, a hundred are worth 10. A handful of votes gets you to the front page; a great many do not crush everyone else. A negative net subtracts with the same root.</p>
<p><b>The topic bonus</b> is a small editorial nudge and the only parameter {site} sets: most topics add nothing; {bonustemas} add that bonus to any of their posts with support. It adds, it does not multiply: it does not enlarge down votes or speed up decay. The bonuses are public and you will see them in every formula.</p>
<p><b>Age</b> subtracts: {decay} points for every half hour of waiting on Media and {decays} on Substacks, counted from the moment the post can reach the front page (at {minage} minutes), not from when it was submitted. It is what makes Media about today and lets Substacks be about always: a good newsletter does not expire with the news, and its clock runs slower.</p>
<h2>Two front pages and a radar</h2>
<p>Everything submitted goes into the <b>radar</b>. From there it can reach one of the two front pages: <b>Media</b>, for stories from newspapers, magazines and other websites, and <b>Substacks</b>, for newsletters. A post goes to Substacks if it links to a substack.com address or to a newsletter in the {site} catalogue; everything else goes to Media. Each post competes only with the posts heading for its own front page.</p>
<p><b>Originals</b>, the texts the community writes itself, don''t go through the radar or reach a front page: they have their own tab, sorted by date, where they''re read, voted on and commented on.</p>

<h2>The cycle</h2>
<p>Every <b>{cycle} minutes</b> a cycle runs, always the same, with no manual intervention:</p>
<p><b>1. From the radar to the front page.</b> Cycles take turns: one publishes to Media, the next to Substacks, and so on. In each cycle, among the posts of the front page whose turn it is that have been in the radar for at least <b>{minage} minutes</b> and have a δ of at least <b>{minpub}</b> (Media) or <b>{minpubs}</b> (Substacks), <b>one</b> is published, chosen <b>by lot</b>: each candidate holds as many tickets as its δ, so the highest δ is the likeliest, but not guaranteed, and the ones behind it come out too now and then. Without votes you do not get there. If the front page whose turn it is has nothing ready, the turn passes to the other; if neither has, nothing is published that cycle: the front page is not filled for the sake of it. The minimum time exists so that a post does not rise just because it arrived in a quiet moment; it has to be seen and voted first.</p>
<p><b>When, exactly.</b> Within each {cycle}-minute stretch, the minute the cycle runs changes and is not announced; {site} says “before such-and-such”. So nobody can hold their votes back to drop them a second early: whoever wants to win has to persuade, not time it. And since it is a draw, fine-tuning the last vote to edge out the neighbour by a tenth is no use either.</p>
<p><b>2. Expiry.</b> Whatever spends <b>{newttltxt}</b> in the radar without reaching the front page is archived: the radar is not forever either.</p>
<p><b>3. The veil lifts.</b> {gm} minutes after any post or comment is published, its author''s alias gives way to their name. Until then, everyone is an alias with a halo: the words are judged, not the signature.</p>
<p><b>4. Archive.</b> Whatever has been on the front page for {ttltxt} is archived. You can still read it, but it can no longer be voted on or commented: the conversation was what it was.</p>
<p>The <b>Processes</b> section shows all this live: the radar sorted by δ, with the front page each post is heading for and an estimate of when it will get there, and a feed of every post, vote, comment and front-page story as it happens.</p>

<h2>Topics and communities</h2>
<p>Every post is classified twice. <b>The topic</b> is set by {site}: there are few of them, they''re stable (current affairs, science, culture…) and each has its own public bonus (almost always zero). Every post has a topic.</p>
<p><b>The community</b> is set by people. Anyone with more than <b>{commaura} aura</b> can create up to <b>{commmax}</b>: a name, a colour and a sentence saying what it''s about. A post can join a community as well as its topic, so whatever interests a group gets gathered together without {site} having to decide it. Whoever creates a community looks after it: they edit its name, description, colour and visual template; moderators can do the same and, if needed, hide it.</p>
<p>Communities <b>don''t touch the δ</b>: they give no bonus, they don''t push anything up or down. They''re a place, not a megaphone. What you can do is choose a community (or a topic) as your <b>landing page</b> in your profile: when you open {site} you''ll land there, and the Media and Substacks tabs will still be everyone''s front pages.</p>

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
<p>All the parameters on this page are the ones in force right now on {site}; if the team changes them, this page changes with them. Cycle every {cycle} minutes, alternating Media and Substacks, drawn by δ · minimum {minage} minutes in the radar · minimum δ for the front page {minpub} (Media) and {minpubs} (Substacks) · decay {decay} (Media) and {decays} (Substacks) per half hour of waiting · votes count {quartxt} after the account is created · bonus: {bonustemas} · {gm}-minute veil · {ttltxt} of life on the front page · expiry in the radar after {newttltxt} · communities for aura above {commaura}, up to {commmax} per person.</p>' WHERE slug = 'algoritmo.en';
UPDATE pages SET body = '<h1>Com funciona el δ</h1>

<h2>Sense algorisme ocult</h2>
<p>A {site} no hi ha cap màquina que decideixi per tu què has de veure. El que arriba a portada ho decideix una fórmula pública, curta i visible: es mostra sota cada enviament del radar, amb els seus números i el seu valor en aquell moment. Aquesta pàgina l''explica sencera. Si l''entens, entens {site}.</p>

<h2>El δ (delta)</h2>
<p>Cada enviament té un <b>δ</b>: una puntuació que resumeix quant el recolza la comunitat i quant de temps fa que espera. Es calcula així:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(vots que compten) + bonus del tema − decaïment × mitges hores d''espera</p>
<p><b>Els vots</b> són la veu de la gent: cada registrat pot votar una vegada cada enviament, a favor o en contra, i canviar d''opinió. No es pot votar el que és propi. Al δ hi entra la diferència entre positius i negatius, amb dues regles: si diversos comptes voten el mateix enviament des de la mateixa connexió, compten com un sol vot (es desa una empremta del vot, mai l''adreça); i els vots d''un compte comencen a comptar {quartxt} després de crear-se. Es veuen des del primer moment; el δ espera.</p>
<p><b>L''arrel</b> aplana l''entusiasme: quatre vots valen 2, nou valen 3, cent valen 10. Amb un grapat de vots s''arriba a portada; amb molts no s''aixafa els altres. Un net negatiu resta amb la mateixa arrel.</p>
<p><b>El bonus del tema</b> és una petita empenta editorial i és l''únic paràmetre que posa {site}: la majoria dels temes no sumen res; {bonustemas} sumen aquest bonus a qualsevol enviament seu amb suport. Se suma, no multiplica: no engrandeix els vots negatius ni accelera el decaïment. Els bonus són públics i els veuràs a cada fórmula.</p>
<p><b>L''antiguitat</b> resta: {decay} punts per cada mitja hora d''espera a Mitjans i {decays} a Substacks, comptant des que l''enviament pot arribar a portada (als {minage} minuts), no des que es va enviar. És el que fa que Mitjans sigui d''avui i que Substacks pugui ser de sempre: una bona newsletter no caduca amb l''actualitat, i el seu rellotge va més lent.</p>
<h2>Dues portades i un radar</h2>
<p>Tot el que s''envia entra al <b>radar</b>. Des d''allà pot arribar a una de les dues portades: <b>Mitjans</b>, per a les notícies de diaris, revistes i altres webs, i <b>Substacks</b>, per als butlletins. Un enviament va a Substacks si enllaça a una adreça de substack.com o a un butlletí del catàleg de {site}; tota la resta va a Mitjans. Cada enviament competeix només amb els de la seva portada.</p>
<p>Els <b>originals</b>, els textos que escriu la mateixa comunitat, no passen pel radar ni van a portada: tenen la seva pestanya, ordenada per data, on es llegeixen, es voten i es comenten.</p>

<h2>El cicle</h2>
<p>Cada <b>{cycle} minuts</b> té lloc un cicle, sempre igual, sense intervenció manual:</p>
<p><b>1. Del radar a la portada.</b> Els cicles es tornen: un publica a Mitjans, el següent a Substacks, i així successivament. A cada cicle, entre els enviaments de la portada a què li toca que fa almenys <b>{minage} minuts</b> que són al radar i tenen un δ dalmenys <b>{minpub}</b> (Mitjans) o <b>{minpubs}</b> (Substacks), sen publica <b>un de sol</b>, triat <b>a sorts</b>: cada candidat té tantes paperetes com δ, així que el de major δ és el més probable, però no està garantit, i els que el segueixen també surten de vegades. Sense vots no shi arriba. Si a la portada del torn no li arriba res, el torn passa a laltra; si tampoc, aquell cicle no es publica res: la portada no somple per omplir. El temps mínim existeix perquè un enviament no pugi només perquè va arribar en un moment tranquil; ha de ser vist i votat abans.</p>
<p><b>Quan, exactament.</b> Dins de cada tram de {cycle} minuts, el minut en què corre el cicle canvia i no sanuncia; {site} diu «abans de les tal i tal». Així ningú no pot guardar-se els vots per deixar-los anar un segon abans: qui vulgui guanyar ha de convèncer, no cronometrar. I com que és un sorteig, tampoc no serveix afinar lúltim vot per treure-li una dècima al del costat.</p>
<p><b>2. Caducitat.</b> El que passa <b>{newttltxt}</b> al radar sense arribar a portada sarxiva: el radar tampoc no és per sempre.</p>
<p><b>3. El vel s''aixeca.</b> Al cap de {gm} minuts de publicar-se qualsevol enviament o comentari, l''àlies del seu autor deixa pas al seu nom. Abans, tots són àlies amb un halo: es jutgen les paraules, no la signatura.</p>
<p><b>4. Arxiu.</b> El que fa {ttltxt} que és a portada sarxiva. Es pot continuar llegint, però ja no es vota ni es comenta: la conversa va ser el que va ser.</p>
<p>La secció <b>Processos</b> mostra tot això en directe: el radar ordenat per δ, amb la portada a la qual va cada enviament i una estimació de quan hi arribarà, i un flux amb cada enviament, vot, comentari i portada a mesura que passen.</p>

<h2>Temes i comunitats</h2>
<p>Cada enviament es classifica dues vegades. <b>El tema</b> el posa {site}: són pocs, estables (actualitat, ciència, cultura…) i cadascun té el seu bonus públic (gairebé sempre zero). Tot enviament porta un tema.</p>
<p><b>La comunitat</b> la posa la gent. Qualsevol amb més de <b>{commaura} d''aura</b> en pot crear fins a <b>{commmax}</b>: un nom, un color i una frase que digui de què va. Un enviament pot sumar-se a una comunitat a més del seu tema, i així s''agrupa el que interessa a un grup sense que {site} ho hagi de decidir. Qui crea una comunitat en té cura: n''edita el nom, la descripció, el color i la plantilla visual; els moderadors poden fer el mateix i, si cal, amagar-la.</p>
<p>Les comunitats <b>no toquen el δ</b>: no donen bonus, no pugen ni baixen res. Són un lloc, no un altaveu. El que sí que pots fer és triar una comunitat (o un tema) com a <b>pàgina d''entrada</b> al teu perfil: quan obris {site} hi arribaràs, i les pestanyes Mitjans i Substacks continuaran sent les portades de tothom.</p>

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
<p>Tots els paràmetres d''aquesta pàgina són els que estan en vigor ara mateix a {site}; si l''equip els canvia, aquesta pàgina canvia amb ells. Cicle cada {cycle} minuts, alternant Mitjans i Substacks, a sorts segons el δ · {minage} minuts mínims al radar · δ mínim per a portada {minpub} (Mitjans) i {minpubs} (Substacks) · decaïment {decay} (Mitjans) i {decays} (Substacks) per mitja hora d''espera · els vots compten {quartxt} després de crear-se el compte · bonus: {bonustemas} · vel de {gm} minuts · vida a portada de {ttltxt} · caducitat al radar al cap de {newttltxt} · comunitats amb aura superior a {commaura}, fins a {commmax} per persona.</p>' WHERE slug = 'algoritmo.ca';
UPDATE pages SET body = '<h1>Como funciona o δ</h1>

<h2>Sen algoritmo oculto</h2>
<p>En {site} non hai unha máquina que decida por ti que ver. O que chega á portada decídeo unha fórmula pública, curta e visible: amósase baixo cada envío do radar, cos seus números e o seu valor nese momento. Esta páxina explícaa enteira. Se a entendes, entendes {site}.</p>

<h2>O δ (delta)</h2>
<p>Cada envío ten un <b>δ</b>: unha puntuación que resume canto o apoia a comunidade e canto tempo leva agardando. Calcúlase así:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(votos que contan) + bonus do tema − decaemento × medias horas de espera</p>
<p><b>Os votos</b> son a voz da xente: cada rexistrado pode votar unha vez cada envío, a favor ou en contra, e cambiar de opinión. Non se pode votar o propio. No δ entra a diferenza entre positivos e negativos, con dúas regras: se varias contas votan o mesmo envío desde a mesma conexión, contan como un só voto (gárdase unha pegada do voto, nunca o enderezo); e os votos dunha conta empezan a contar {quartxt} despois de crearse. Vense desde o primeiro momento; o δ agarda.</p>
<p><b>A raíz</b> aplana o entusiasmo: catro votos valen 2, nove valen 3, cen valen 10. Cun puñado de votos chégase á portada; con moitos non se esmagan os demais. Un neto negativo resta coa mesma raíz.</p>
<p><b>O bonus do tema</b> é un pequeno empurrón editorial e é o único parámetro que pon {site}: a maioría dos temas non suman nada; {bonustemas} suman ese bonus a calquera envío seu con apoio. Súmase, non multiplica: non agranda os votos negativos nin acelera o decaemento. Os bonus son públicos e velos en cada fórmula.</p>
<p><b>A antigüidade</b> resta: {decay} puntos por cada media hora de espera en Medios e {decays} en Substacks, contando desde que o envío pode chegar á portada (aos {minage} minutos), non desde que se enviou. É o que fai que Medios sexa de hoxe e que Substacks poida ser de sempre: un bo boletín non caduca coa actualidade, e o seu reloxo vai máis despacio.</p>
<h2>Dúas portadas e un radar</h2>
<p>Todo o que se envía entra no <b>radar</b>. Desde aí pode chegar a unha das dúas portadas: <b>Medios</b>, para as novas de xornais, revistas e demais webs, e <b>Substacks</b>, para os boletíns. Un envío vai a Substacks se a súa ligazón apunta a un enderezo de substack.com ou a un boletín do catálogo de {site}; todo o demais vai a Medios. Cada envío compite só cos da súa portada.</p>
<p>Os <b>orixinais</b>, os textos que escribe a propia comunidade, non pasan polo radar nin van á portada: teñen a súa lapela, ordenada por data, onde se len, se votan e se comentan.</p>

<h2>O ciclo</h2>
<p>Cada <b>{cycle} minutos</b> ocorre un ciclo, sempre igual, sen intervención manual:</p>
<p><b>1. Do radar á portada.</b> Os ciclos túrnanse: un publica en Medios, o seguinte en Substacks, e así sucesivamente. En cada ciclo, entre os envíos da portada á que lle toca que levan polo menos <b>{minage} minutos</b> no radar e teñen un δ de polo menos <b>{minpub}</b> (Medios) ou <b>{minpubs}</b> (Substacks), publícase <b>un só</b>, escollido <b>a sortes</b>: cada candidato ten tantas papeletas como δ, así que o de maior δ é o máis probable, pero non está garantido, e os que o seguen tamén saen ás veces. Sen votos non se chega. Se á portada da quenda non lle chega nada, a quenda pasa á outra; se tampouco, ese ciclo non se publica nada: a portada non se enche por encher. O tempo mínimo existe para que un envío non suba só porque chegou nun momento tranquilo; ten que ser visto e votado antes.</p>
<p><b>Cando, exactamente.</b> Dentro de cada tramo de {cycle} minutos, o minuto no que corre o ciclo cambia e non se anuncia; {site} di «antes das tal e tal». Así ninguén pode gardar os votos para soltalos un segundo antes: quen queira gañar ten que convencer, non cronometrar. E como é un sorteo, tampouco serve afinar o último voto para sacarlle unha décima ao do lado.</p>
<p><b>2. Caducidade.</b> O que pasa <b>{newttltxt}</b> no radar sen chegar á portada arquívase: o radar tampouco é para sempre.</p>
<p><b>3. O veo levántase.</b> Aos {gm} minutos de publicarse calquera envío ou comentario, o alias de quen o escribiu deixa paso ao seu nome. Antes, todos son alias cun halo: xúlganse as palabras, non a sinatura.</p>
<p><b>4. Arquivo.</b> O que leva {ttltxt} na portada arquívase. Pódese seguir lendo, pero xa non se vota nin se comenta: a conversa foi o que foi.</p>
<p>A sección <b>Procesos</b> amosa todo isto en directo: o radar ordenado por δ, coa portada á que vai cada envío e unha estimación de cando chega, e un fluxo con cada envío, voto, comentario e portada segundo ocorren.</p>

<h2>Temas e comunidades</h2>
<p>Cada envío clasifícase dúas veces. <b>O tema</b> pono {site}: son poucos, estables (actualidade, ciencia, cultura…) e cada un ten o seu bonus público (case sempre cero). Todo envío leva un tema.</p>
<p><b>A comunidade</b> pona a xente. Calquera con máis de <b>{commaura} de aura</b> pode crear ata <b>{commmax}</b>: un nome, unha cor e unha frase que diga de que vai. Un envío pode sumarse a unha comunidade ademais de ao seu tema, e así agrúpase o que lle interesa a un grupo sen que {site} teña que decidilo. Quen crea unha comunidade cóidaa: edita o seu nome, a súa descrición, a súa cor e o seu modelo visual; os moderadores poden facer o mesmo e, se cómpre, ocultala.</p>
<p>As comunidades <b>non tocan o δ</b>: non dan bonus, non soben nin baixan nada. Son un lugar, non un altofalante. O que si podes facer é escoller unha comunidade (ou un tema) como <b>páxina de entrada</b> no teu perfil: ao abrir {site} chegarás alí, e as lapelas Medios e Substacks seguirán sendo as portadas de todos.</p>

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
<p>Todos os parámetros desta páxina son os que están en vigor agora mesmo en {site}; se o equipo os cambia, esta páxina cambia con eles. Ciclo cada {cycle} minutos, alternando Medios e Substacks, a sortes segundo o δ · {minage} minutos mínimos no radar · δ mínimo para portada {minpub} (Medios) e {minpubs} (Substacks) · decaemento {decay} (Medios) e {decays} (Substacks) por media hora de espera · os votos contan {quartxt} despois de crearse a conta · bonus: {bonustemas} · veo de {gm} minutos · vida na portada de {ttltxt} · caducidade no radar tras {newttltxt} · comunidades con aura maior que {commaura}, ata {commmax} por persoa.</p>' WHERE slug = 'algoritmo.gl';
UPDATE pages SET body = '<h1>Nola funtzionatzen duen δ-k</h1>

<h2>Ezkutuko algoritmorik gabe</h2>
<p>{site}-n ez dago zure ordez zer ikusi erabakitzen duen makinarik. Azalera zer iristen den formula publiko, labur eta ikusgai batek erabakitzen du: radarreko bidalketa bakoitzaren azpian ageri da, bere zenbakiekin eta une horretako balioarekin. Orri honek osorik azaltzen du. Ulertzen baduzu, {site} ulertzen duzu.</p>

<h2>δ (delta)</h2>
<p>Bidalketa bakoitzak <b>δ</b> bat du: komunitateak zenbat babesten duen eta zenbat denbora daraman itxaroten laburbiltzen duen puntuazioa. Honela kalkulatzen da:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(kontatzen duten botoak) + gaiaren bonusa − beherakada × itxaroteko ordu erdiak</p>
<p><b>Botoak</b> jendearen ahotsa dira: erregistratutako bakoitzak behin bozkatu dezake bidalketa bakoitza, alde edo kontra, eta iritziz aldatu. Norberarena ez da bozkatu daiteke. δ-n aldekoen eta kontrakoen arteko diferentzia sartzen da, bi arauekin: kontu batek baino gehiagok bidalketa bera konexio beretik bozkatzen badute, boto bakar gisa kontatzen dute (botoaren aztarna bat gordetzen da, ez inoiz helbidea); eta kontu baten botoak sortu eta {quartxt} igarota hasten dira kontatzen. Hasieratik ikusten dira; δ-k itxaroten du.</p>
<p><b>Erroak</b> gogo bizia leuntzen du: lau botok 2 balio dute, bederatzik 3, ehunek 10. Boto gutxi batzuekin azalera iristen da; askorekin ez dira besteak zapaltzen. Neto negatibo batek erro berarekin kentzen du.</p>
<p><b>Gaiaren bonusa</b> bultzada editorial txiki bat da eta {site}-k jartzen duen parametro bakarra: gai gehienek ez dute ezer gehitzen; {bonustemas} gaiek bonus hori gehitzen diote babesa duen edozein bidalketari. Gehitzen da, ez biderkatzen: ez ditu kontrako botoak handitzen, ez beherakada bizkortzen. Bonusak publikoak dira eta formula bakoitzean ikusiko dituzu.</p>
<p><b>Antzinatasunak</b> kentzen du: {decay} puntu itxaroteko ordu erdi bakoitzeko Hedabideetan eta {decays} Substacketan, bidalketa azalera iritsi daitekeenetik kontatuta ({minage} minutura), ez bidali zenetik. Horrek egiten du Hedabideak gaurkoak izatea eta Substackak betikoak izan ahal izatea: buletin on bat ez da iraungitzen gaurkotasunarekin, eta bere erlojua motelago doa.</p>
<h2>Bi azal eta radar bat</h2>
<p>Bidaltzen den guztia <b>radarrean</b> sartzen da. Handik bi azaletako batera irits daiteke: <b>Hedabideak</b>, egunkari, aldizkari eta gainerako webguneetako albisteentzat, eta <b>Substackak</b>, buletinentzat. Bidalketa bat Substacketara doa substack.com helbide batera edo {site}-ren katalogoko buletin batera estekatzen badu; gainerako guztia Hedabideetara doa. Bidalketa bakoitza bere azaleko bidalketekin bakarrik lehiatzen da.</p>
<p><b>Originalak</b>, komunitateak berak idatzitako testuak, ez dira radarretik pasatzen eta ez doaz azalera: beren fitxa dute, dataren arabera ordenatuta, eta han irakurtzen, bozkatzen eta iruzkintzen dira.</p>

<h2>Zikloa</h2>
<p><b>{cycle} minuturo</b> ziklo bat gertatzen da, beti berdin, eskuzko esku-hartzerik gabe:</p>
<p><b>1. Radarretik azalera.</b> Zikloak txandaka doaz: batek Hedabideetan argitaratzen du, hurrengoak Substacketan, eta horrela hurrenez hurren. Ziklo bakoitzean, txanda duen azaleko bidalketen artean, radarrean gutxienez <b>{minage} minutu</b> daramatzaten eta gutxienez <b>{minpub}</b> (Hedabideak) edo <b>{minpubs}</b> (Substackak) δ dutenen artean, <b>bakarra</b> argitaratzen da, <b>zozketaz</b> aukeratua: hautagai bakoitzak δ-a bezainbeste txartel ditu, beraz δ handienekoa da probableena, baina ez dago ziurtatuta, eta atzetik datozenak ere ateratzen dira batzuetan. Botorik gabe ez da iristen. Txandako azalari ezer iristen ez bazaio, txanda besteari pasatzen zaio; hari ere ez, ziklo horretan ez da ezer argitaratzen: azala ez da betetzeagatik betetzen. Gutxieneko denbora existitzen da bidalketa bat une lasai batean iritsi delako bakarrik ez igotzeko; aurretik ikusi eta bozkatu behar da.</p>
<p><b>Noiz, zehazki.</b> {cycle} minutuko tarte bakoitzaren barruan, zikloa exekutatzen den minutua aldatzen da eta ez da iragartzen; {site}-k «halako ordua baino lehen» esaten du. Horrela inork ezin ditu botoak gorde segundo bat lehenago askatzeko: irabazi nahi duenak konbentzitu behar du, ez kronometratu. Eta zozketa denez, ez du balio azken botoa fintzea ondokoari hamarren bat kentzeko.</p>
<p><b>2. Iraungitzea.</b> Radarrean <b>{newttltxt}</b> azalera iritsi gabe igarotzen duena artxibatu egiten da: radarra ere ez da betiko.</p>
<p><b>3. Beloa altxatzen da.</b> Edozein bidalketa edo iruzkin argitaratu eta {gm} minutura, egilearen ezizenak bere izenari uzten dio lekua. Ordura arte, denak dira halodun ezizenak: hitzak epaitzen dira, ez sinadura.</p>
<p><b>4. Artxiboa.</b> Azalean {ttltxt} daramatzana artxibatu egiten da. Irakurtzen jarrai daiteke, baina jada ez da bozkatzen, ez iruzkintzen: elkarrizketa izan zena izan zen.</p>
<p><b>Prozesuak</b> atalak hori guztia erakusten du zuzenean: δ-ren arabera ordenatutako radarra, bidalketa bakoitza zein azaletara doan eta noiz iritsiko den aurreikusita, eta bidalketa, boto, iruzkin eta azal-igoera bakoitzaren jarioa, gertatu ahala.</p>

<h2>Gaiak eta komunitateak</h2>
<p>Bidalketa bakoitza bi aldiz sailkatzen da. <b>Gaia</b> {site}-k jartzen du: gutxi dira, egonkorrak (gaurkotasuna, zientzia, kultura…), eta bakoitzak bere bonus publikoa du (ia beti zero). Bidalketa orok darama gai bat.</p>
<p><b>Komunitatea</b> jendeak jartzen du. <b>{commaura} aura</b> baino gehiago duen edonork sor ditzake <b>{commmax}</b> arte: izen bat, kolore bat eta zertaz doan esaten duen esaldi bat. Bidalketa bat komunitate batera bil daiteke bere gaiaz gain, eta horrela talde bati interesatzen zaiona biltzen da, {site}-k erabaki beharrik gabe. Komunitate bat sortzen duenak zaintzen du: haren izena, deskribapena, kolorea eta txantiloi bisuala editatzen ditu; moderatzaileek gauza bera egin dezakete eta, behar izanez gero, ezkutatu.</p>
<p>Komunitateek <b>ez dute δ ukitzen</b>: ez dute bonusik ematen, ez dute ezer igotzen ez jaisten. Leku bat dira, ez bozgorailu bat. Egin dezakezuna da komunitate bat (edo gai bat) aukeratzea zure profilean <b>sarrera-orri</b> gisa: {site} irekitzean hara iritsiko zara, eta Hedabideak eta Substackak fitxek denen azalak izaten jarraituko dute.</p>

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
<p>Orri honetako parametro guztiak {site}-n une honetan indarrean daudenak dira; taldeak aldatzen baditu, orri hau ere haiekin batera aldatzen da. Zikloa {cycle} minuturo, Hedabideak eta Substackak txandakatuz, zozketaz δ-ren arabera · gutxienez {minage} minutu radarrean · azalerako gutxieneko δ: {minpub} (Hedabideak) eta {minpubs} (Substackak) · beherakada: {decay} (Hedabideak) eta {decays} (Substackak) itxaroteko ordu erdiko · botoak kontua sortu eta {quartxt} igarota kontatzen dira · bonusa: {bonustemas} · {gm} minutuko beloa · {ttltxt} azalean · radarrean {newttltxt} igarota iraungitzen da · komunitateak {commaura} baino aura handiagoarekin, gehienez {commmax} pertsonako.</p>' WHERE slug = 'algoritmo.eu';
UPDATE pages SET body = '<h1>Comment fonctionne le δ</h1>

<h2>Pas d''algorithme caché</h2>
<p>Sur {site}, aucune machine ne décide pour toi de ce que tu vois. Ce qui arrive en une, c''est une formule publique, courte et visible qui le décide : elle s''affiche sous chaque envoi du radar, avec ses chiffres et sa valeur à cet instant. Cette page l''explique en entier. Si tu la comprends, tu comprends {site}.</p>

<h2>Le δ (delta)</h2>
<p>Chaque envoi a un <b>δ</b> : un score qui résume combien la communauté le soutient et depuis combien de temps il attend. Il se calcule ainsi :</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(votes qui comptent) + bonus du thème − déclin × demi-heures d''attente</p>
<p><b>Les votes</b> sont la voix des gens : chaque inscrit peut voter une fois par envoi, pour ou contre, et changer d''avis. On ne vote pas pour soi. Ce qui entre dans le δ, c''est la différence entre les pour et les contre, avec deux règles : si plusieurs comptes votent le même envoi depuis la même connexion, ils comptent pour un seul vote (on garde une empreinte du vote, jamais l''adresse) ; et les votes d''un compte commencent à compter {quartxt} après sa création. Ils se voient dès le premier instant ; le δ attend.</p>
<p><b>La racine</b> aplatit l''enthousiasme : quatre votes valent 2, neuf valent 3, cent valent 10. Avec une poignée de votes on arrive en une ; avec beaucoup, on n''écrase pas les autres. Un solde négatif retranche avec la même racine.</p>
<p><b>Le bonus du thème</b> est un petit coup de pouce éditorial et le seul paramètre que fixe {site} : la plupart des thèmes n''ajoutent rien ; {bonustemas} ajoutent ce bonus à chacun de leurs envois qui a du soutien. Il s''ajoute, il ne multiplie pas : il n''agrandit pas les votes contre et n''accélère pas le déclin. Les bonus sont publics et tu les verras dans chaque formule.</p>
<p><b>L''ancienneté</b> retranche : {decay} point par demi-heure d''attente sur Médias et {decays} sur Substacks, en comptant depuis que l''envoi peut arriver en une (à {minage} minutes), pas depuis son envoi. C''est ce qui fait que Médias est d''aujourd''hui et que Substacks peut être de toujours : une bonne newsletter n''expire pas avec l''actualité, et son horloge tourne plus lentement.</p>
<h2>Deux unes et un radar</h2>
<p>Tout ce qui est envoyé entre dans le <b>radar</b>. De là, il peut arriver sur l''une des deux unes : <b>Médias</b>, pour les actualités des journaux, des magazines et des autres sites, et <b>Substacks</b>, pour les newsletters. Un envoi va dans Substacks s''il pointe vers une adresse de substack.com ou vers une newsletter du catalogue de {site} ; tout le reste va dans Médias. Chaque envoi n''est en concurrence qu''avec ceux de sa une.</p>
<p>Les <b>originaux</b>, les textes écrits par la communauté elle-même, ne passent pas par le radar et ne vont pas en une : ils ont leur propre onglet, trié par date, où on les lit, on les vote et on les commente.</p>

<h2>Le cycle</h2>
<p>Toutes les <b>{cycle} minutes</b>, un cycle a lieu, toujours le même, sans intervention manuelle :</p>
<p><b>1. Du radar à la une.</b> Les cycles alternent : lun publie sur Médias, le suivant sur Substacks, et ainsi de suite. À chaque cycle, parmi les envois de la une dont cest le tour qui sont dans le radar depuis au moins <b>{minage} minutes</b> et ont un δ dau moins <b>{minpub}</b> (Médias) ou <b>{minpubs}</b> (Substacks), <b>un seul</b> est publié, choisi <b>au sort</b> : chaque candidat a autant de billets que de δ, si bien que le plus grand δ est le plus probable, mais pas garanti, et ceux qui le suivent sortent aussi parfois. Sans votes, on ny arrive pas. Si la une dont cest le tour na rien de prêt, le tour passe à lautre ; si elle non plus, rien nest publié ce cycle-là : on ne remplit pas la une pour la remplir. Le temps minimum existe pour quun envoi ne monte pas seulement parce quil est arrivé à un moment calme ; il doit être vu et voté avant.</p>
<p><b>Quand, exactement.</b> Dans chaque tranche de {cycle} minutes, la minute où tourne le cycle change et nest pas annoncée ; {site} dit « avant telle heure ». Ainsi personne ne peut garder ses votes pour les lâcher une seconde avant : qui veut gagner doit convaincre, pas chronométrer. Et comme cest un tirage, peaufiner le dernier vote pour prendre un dixième au voisin ne sert à rien non plus.</p>
<p><b>2. Expiration.</b> Ce qui passe <b>{newttltxt}</b> dans le radar sans arriver en une est archivé : le radar non plus nest pas pour toujours.</p>
<p><b>3. Le voile se lève.</b> {gm} minutes après la publication de n''importe quel envoi ou commentaire, l''alias de son auteur laisse place à son nom. Avant, tout le monde est un alias avec un halo : on juge les mots, pas la signature.</p>
<p><b>4. Archive.</b> Ce qui est en une depuis {ttltxt} est archivé. On peut encore le lire, mais on ne vote plus et on ne commente plus : la conversation a été ce quelle a été.</p>
<p>La section <b>Processus</b> montre tout cela en direct : le radar trié par δ, avec la une vers laquelle va chaque envoi et une estimation du moment où il y arrivera, et un flux avec chaque envoi, vote, commentaire et passage en une au moment où ils se produisent.</p>

<h2>Thèmes et communautés</h2>
<p>Chaque envoi est classé deux fois. <b>Le thème</b>, c''est {site} qui le fixe : ils sont peu nombreux, stables (actualité, science, culture…) et chacun a son bonus public (presque toujours zéro). Tout envoi a un thème.</p>
<p><b>La communauté</b>, ce sont les gens qui la choisissent. Toute personne ayant plus de <b>{commaura} d''aura</b> peut en créer jusqu''à <b>{commmax}</b> : un nom, une couleur et une phrase qui dit de quoi il s''agit. Un envoi peut rejoindre une communauté en plus de son thème, et ainsi se regroupe ce qui intéresse un groupe sans que {site} ait à en décider. Qui crée une communauté en prend soin : il en modifie le nom, la description, la couleur et l''habillage ; les modérateurs peuvent faire de même et, si besoin, la masquer.</p>
<p>Les communautés <b>ne touchent pas au δ</b> : elles ne donnent pas de bonus, ne font rien monter ni descendre. Ce sont des lieux, pas des porte-voix. Ce que tu peux faire, en revanche, c''est choisir une communauté (ou un thème) comme <b>page d''arrivée</b> dans ton profil : en ouvrant {site}, tu arriveras là, et les onglets Médias et Substacks resteront les unes de tout le monde.</p>

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
<p>Tous les paramètres de cette page sont ceux en vigueur en ce moment sur {site} ; si l''équipe les change, cette page change avec eux. Cycle toutes les {cycle} minutes, en alternant Médias et Substacks, au sort selon le δ · {minage} minutes minimum dans le radar · δ minimum pour la une {minpub} (Médias) et {minpubs} (Substacks) · déclin {decay} (Médias) et {decays} (Substacks) par demi-heure d''attente · les votes comptent {quartxt} après la création du compte · bonus : {bonustemas} · voile de {gm} minutes · durée de vie en une de {ttltxt} · expiration dans le radar au bout de {newttltxt} · communautés avec une aura supérieure à {commaura}, jusqu''à {commmax} par personne.</p>' WHERE slug = 'algoritmo.fr';
UPDATE pages SET body = '<h1>So funktioniert das δ</h1>

<h2>Kein verborgener Algorithmus</h2>
<p>Auf {site} gibt es keine Maschine, die für dich entscheidet, was du siehst. Was auf die Titelseite kommt, entscheidet eine öffentliche, kurze und sichtbare Formel: Sie steht unter jedem Beitrag im Radar, mit ihren Zahlen und ihrem aktuellen Wert. Diese Seite erklärt sie vollständig. Wer sie versteht, versteht {site}.</p>

<h2>Das δ (Delta)</h2>
<p>Jeder Beitrag hat ein <b>δ</b>: einen Wert, der zusammenfasst, wie sehr die Community ihn unterstützt und wie lange er schon wartet. Er wird so berechnet:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(zählende Stimmen) + Themenbonus − Abklingen × halbe Stunden Wartezeit</p>
<p><b>Die Stimmen</b> sind die Stimme der Leute: Jedes Mitglied kann pro Beitrag einmal abstimmen, dafür oder dagegen, und seine Meinung ändern. Für Eigenes kann man nicht stimmen. Ins δ geht die Differenz zwischen Für- und Gegenstimmen ein, mit zwei Regeln: Stimmen mehrere Konten über denselben Beitrag von derselben Verbindung ab, zählen sie als eine Stimme (gespeichert wird ein Fingerabdruck der Stimme, nie die Adresse); und die Stimmen eines Kontos zählen erst {quartxt} nach seiner Erstellung. Zu sehen sind sie vom ersten Moment an; das δ wartet.</p>
<p><b>Die Wurzel</b> dämpft die Begeisterung: Vier Stimmen sind 2 wert, neun 3, hundert 10. Mit einer Handvoll Stimmen kommt man auf die Titelseite; mit sehr vielen erdrückt man die anderen nicht. Ein negativer Saldo zieht mit derselben Wurzel ab.</p>
<p><b>Der Themenbonus</b> ist ein kleiner redaktioneller Schubs und der einzige Parameter, den {site} setzt: Die meisten Themen geben nichts dazu; {bonustemas} geben diesen Bonus jedem ihrer Beiträge mit Zuspruch. Er wird addiert, nicht multipliziert: Er vergrößert keine Gegenstimmen und beschleunigt kein Abklingen. Die Boni sind öffentlich, und du siehst sie in jeder Formel.</p>
<p><b>Das Alter</b> zieht ab: {decay} Punkte pro halbe Stunde Wartezeit unter Medien und {decays} unter Substacks, gezählt ab dem Moment, in dem der Beitrag auf die Titelseite kommen kann (nach {minage} Minuten), nicht ab dem Einreichen. Das macht Medien zur Seite von heute und lässt Substacks eine Seite von immer sein: Ein guter Newsletter verfällt nicht mit dem Tagesgeschehen, und seine Uhr geht langsamer.</p>
<h2>Zwei Titelseiten und ein Radar</h2>
<p>Alles, was eingereicht wird, kommt ins <b>Radar</b>. Von dort kann es auf eine der beiden Titelseiten gelangen: <b>Medien</b> für Nachrichten aus Zeitungen, Zeitschriften und anderen Websites und <b>Substacks</b> für Newsletter. Ein Beitrag geht zu Substacks, wenn er auf eine Adresse von substack.com oder auf einen Newsletter aus dem Katalog von {site} verlinkt; alles andere geht zu Medien. Jeder Beitrag konkurriert nur mit denen seiner Titelseite.</p>
<p>Die <b>Originale</b>, die Texte, die die Community selbst schreibt, gehen nicht durchs Radar und kommen auf keine Titelseite: Sie haben ihren eigenen Reiter, nach Datum sortiert, wo sie gelesen, bewertet und kommentiert werden.</p>

<h2>Der Zyklus</h2>
<p>Alle <b>{cycle} Minuten</b> läuft ein Zyklus, immer gleich, ohne manuelle Eingriffe:</p>
<p><b>1. Vom Radar auf die Titelseite.</b> Die Zyklen wechseln sich ab: Einer veröffentlicht unter Medien, der nächste unter Substacks, und so weiter. In jedem Zyklus wird unter den Beiträgen der Titelseite, die an der Reihe ist, die seit mindestens <b>{minage} Minuten</b> im Radar stehen und ein δ von mindestens <b>{minpub}</b> (Medien) bzw. <b>{minpubs}</b> (Substacks) haben, <b>genau einer</b> veröffentlicht, <b>per Los</b>: Jeder Kandidat hält so viele Lose, wie sein δ beträgt; das höchste δ ist also das wahrscheinlichste, aber nicht garantiert, und die dahinter kommen auch mal dran. Ohne Stimmen kommt man nicht hin. Hat die Titelseite, die an der Reihe ist, nichts, geht der Zug an die andere; hat auch sie nichts, wird in diesem Zyklus nichts veröffentlicht: Die Titelseite wird nicht um des Füllens willen gefüllt. Die Mindestzeit gibt es, damit ein Beitrag nicht nur deshalb aufsteigt, weil er in einem ruhigen Moment kam; er muss vorher gesehen und bewertet werden.</p>
<p><b>Wann genau.</b> Innerhalb jedes {cycle}-Minuten-Abschnitts wechselt die Minute, in der der Zyklus läuft, und sie wird nicht angekündigt; {site} sagt „vor so-und-so-viel Uhr“. So kann niemand seine Stimmen zurückhalten, um sie eine Sekunde vorher abzugeben: Wer gewinnen will, muss überzeugen, nicht die Uhr stoppen. Und weil es eine Verlosung ist, bringt es auch nichts, die letzte Stimme so zu setzen, dass man dem Nachbarn ein Zehntel abnimmt.</p>
<p><b>2. Ablauf.</b> Was <b>{newttltxt}</b> im Radar steht, ohne auf eine Titelseite zu kommen, wird archiviert: Auch das Radar ist nicht für immer.</p>
<p><b>3. Der Schleier hebt sich.</b> {gm} Minuten nach der Veröffentlichung eines Beitrags oder Kommentars weicht der Alias des Autors seinem Namen. Vorher sind alle Aliasse mit Halo: Beurteilt werden die Worte, nicht die Unterschrift.</p>
<p><b>4. Archiv.</b> Was seit {ttltxt} auf der Titelseite steht, wird archiviert. Man kann es weiter lesen, aber nicht mehr bewerten oder kommentieren: Das Gespräch war, was es war.</p>
<p>Der Bereich <b>Abläufe</b> zeigt all das live: das Radar, sortiert nach δ, mit der Titelseite, zu der jeder Beitrag geht, und einer Schätzung, wann er dort ankommt, und einen Strom mit jedem Beitrag, jeder Stimme, jedem Kommentar und jeder Titelseite, sobald sie passieren.</p>

<h2>Themen und Communities</h2>
<p>Jeder Beitrag wird zweimal eingeordnet. <b>Das Thema</b> legt {site} fest: Es sind wenige, beständige (Aktuelles, Wissenschaft, Kultur …), und jedes hat seinen öffentlichen Bonus (fast immer null). Jeder Beitrag hat ein Thema.</p>
<p><b>Die Community</b> bestimmen die Leute. Wer mehr als <b>{commaura} Aura</b> hat, kann bis zu <b>{commmax}</b> gründen: ein Name, eine Farbe und ein Satz, der sagt, worum es geht. Ein Beitrag kann zusätzlich zu seinem Thema einer Community beitreten, und so sammelt sich, was eine Gruppe interessiert, ohne dass {site} es entscheiden muss. Wer eine Community gründet, kümmert sich um sie: bearbeitet Name, Beschreibung, Farbe und visuelles Design; Moderatoren können dasselbe tun und sie, falls nötig, ausblenden.</p>
<p>Communities <b>berühren das δ nicht</b>: Sie geben keinen Bonus, heben nichts und senken nichts. Sie sind ein Ort, kein Lautsprecher. Was du aber tun kannst: in deinem Profil eine Community (oder ein Thema) als <b>Startseite</b> wählen. Wenn du {site} öffnest, landest du dort, und die Reiter Medien und Substacks bleiben die Titelseiten für alle.</p>

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
<p>Alle Parameter auf dieser Seite sind die, die gerade auf {site} gelten; ändert das Team sie, ändert sich diese Seite mit. Zyklus alle {cycle} Minuten, abwechselnd Medien und Substacks, per Los nach δ · mindestens {minage} Minuten im Radar · Mindest-δ für die Titelseite {minpub} (Medien) und {minpubs} (Substacks) · Abklingen {decay} (Medien) und {decays} (Substacks) pro halbe Stunde Wartezeit · Stimmen zählen {quartxt} nach Erstellung des Kontos · Bonus: {bonustemas} · Schleier von {gm} Minuten · {ttltxt} auf der Titelseite · Ablauf im Radar nach {newttltxt} · Communities ab einer Aura über {commaura}, bis zu {commmax} pro Person.</p>' WHERE slug = 'algoritmo.de';
UPDATE pages SET body = '<h1>Come funziona il δ</h1>

<h2>Niente algoritmo nascosto</h2>
<p>Su {site} non c''è una macchina che decide per te cosa vedere. Cosa arriva in prima pagina lo decide una formula pubblica, breve e visibile: compare sotto ogni invio del radar, con i suoi numeri e il suo valore in quel momento. Questa pagina la spiega per intero. Se la capisci, capisci {site}.</p>

<h2>Il δ (delta)</h2>
<p>Ogni invio ha un <b>δ</b>: un punteggio che riassume quanto la comunità lo sostiene e da quanto tempo aspetta. Si calcola così:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(voti che contano) + bonus del tema − decadimento × mezz''ore di attesa</p>
<p><b>I voti</b> sono la voce della gente: ogni iscritto può votare una volta ciascun invio, a favore o contro, e cambiare idea. Non si può votare il proprio. Nel δ entra la differenza tra positivi e negativi, con due regole: se più account votano lo stesso invio dalla stessa connessione, contano come un solo voto (si conserva un''impronta del voto, mai l''indirizzo); e i voti di un account cominciano a contare {quartxt} dopo la sua creazione. Si vedono dal primo momento; il δ aspetta.</p>
<p><b>La radice</b> appiattisce l''entusiasmo: quattro voti valgono 2, nove valgono 3, cento valgono 10. Con una manciata di voti si arriva in prima pagina; con molti non si schiacciano gli altri. Un netto negativo sottrae con la stessa radice.</p>
<p><b>Il bonus del tema</b> è una piccola spinta editoriale ed è l''unico parametro che fissa {site}: la maggior parte dei temi non aggiunge nulla; {bonustemas} aggiungono quel bonus a qualsiasi loro invio con sostegno. Si somma, non moltiplica: non ingrandisce i voti negativi né accelera il decadimento. I bonus sono pubblici e li vedrai in ogni formula.</p>
<p><b>L''anzianità</b> sottrae: {decay} punti per ogni mezz''ora di attesa su Media e {decays} su Substack, contando da quando l''invio può arrivare in prima pagina (a {minage} minuti), non da quando è stato inviato. È ciò che rende Media di oggi e permette a Substack di essere di sempre: una buona newsletter non scade con l''attualità, e il suo orologio va più piano.</p>
<h2>Due prime pagine e un radar</h2>
<p>Tutto ciò che viene inviato entra nel <b>radar</b>. Da lì può arrivare a una delle due prime pagine: <b>Media</b>, per le notizie di giornali, riviste e altri siti, e <b>Substack</b>, per le newsletter. Un invio va in Substack se rimanda a un indirizzo di substack.com o a una newsletter del catalogo di {site}; tutto il resto va in Media. Ogni invio compete solo con quelli della sua prima pagina.</p>
<p>Gli <b>originali</b>, i testi scritti dalla comunità stessa, non passano per il radar e non vanno in prima pagina: hanno la loro scheda, ordinata per data, dove si leggono, si votano e si commentano.</p>

<h2>Il ciclo</h2>
<p>Ogni <b>{cycle} minuti</b> avviene un ciclo, sempre uguale, senza interventi manuali:</p>
<p><b>1. Dal radar alla prima pagina.</b> I cicli si alternano: uno pubblica su Media, il successivo su Substack, e così via. In ogni ciclo, tra gli invii della prima pagina di turno che stanno nel radar da almeno <b>{minage} minuti</b> e hanno un δ di almeno <b>{minpub}</b> (Media) o <b>{minpubs}</b> (Substack), se ne pubblica <b>uno solo</b>, scelto <b>a sorte</b>: ogni candidato ha tanti biglietti quanto il suo δ, così quello con il δ maggiore è il più probabile, ma non è garantito, e anche quelli che lo seguono escono ogni tanto. Senza voti non si arriva. Se alla prima pagina di turno non arriva nulla, il turno passa allaltra; se nemmeno a quella, in quel ciclo non si pubblica nulla: la prima pagina non si riempie tanto per riempirla. Il tempo minimo esiste perché un invio non salga solo perché è arrivato in un momento tranquillo; deve essere visto e votato prima.</p>
<p><b>Quando, esattamente.</b> Dentro ogni tratto di {cycle} minuti, il minuto in cui gira il ciclo cambia e non si annuncia; {site} dice «prima delle tale e tale». Così nessuno può tenersi i voti per mollarli un secondo prima: chi vuole vincere deve convincere, non cronometrare. E siccome è un sorteggio, non serve nemmeno affinare lultimo voto per strappare un decimo al vicino.</p>
<p><b>2. Scadenza.</b> Ciò che passa <b>{newttltxt}</b> nel radar senza arrivare in prima pagina viene archiviato: nemmeno il radar è per sempre.</p>
<p><b>3. Il velo si alza.</b> {gm} minuti dopo la pubblicazione di qualsiasi invio o commento, l''alias dell''autore lascia il posto al suo nome. Prima, sono tutti alias con un alone: si giudicano le parole, non la firma.</p>
<p><b>4. Archivio.</b> Ciò che è in prima pagina da {ttltxt} viene archiviato. Si può continuare a leggere, ma non si vota e non si commenta più: la conversazione è stata quella che è stata.</p>
<p>La sezione <b>Processi</b> mostra tutto questo in diretta: il radar ordinato per δ, con la prima pagina verso cui va ogni invio e una stima di quando ci arriverà, e un flusso con ogni invio, voto, commento e prima pagina man mano che accadono.</p>

<h2>Temi e comunità</h2>
<p>Ogni invio viene classificato due volte. <b>Il tema</b> lo stabilisce {site}: sono pochi, stabili (attualità, scienza, cultura…) e ognuno ha il suo bonus pubblico (quasi sempre zero). Ogni invio ha un tema.</p>
<p><b>La comunità</b> la sceglie la gente. Chiunque abbia più di <b>{commaura} di aura</b> può crearne fino a <b>{commmax}</b>: un nome, un colore e una frase che dica di cosa parla. Un invio può aggiungersi a una comunità oltre che al suo tema, e così si raccoglie ciò che interessa a un gruppo senza che {site} debba deciderlo. Chi crea una comunità se ne prende cura: ne modifica il nome, la descrizione, il colore e lo stile visivo; i moderatori possono fare lo stesso e, se serve, nasconderla.</p>
<p>Le comunità <b>non toccano il δ</b>: non danno bonus, non fanno salire né scendere niente. Sono un luogo, non un megafono. Quello che puoi fare è scegliere una comunità (o un tema) come <b>pagina di ingresso</b> nel tuo profilo: aprendo {site} arriverai lì, e le schede Media e Substack resteranno le prime pagine di tutti.</p>

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
<p>Tutti i parametri di questa pagina sono quelli in vigore in questo momento su {site}; se il team li cambia, questa pagina cambia con loro. Ciclo ogni {cycle} minuti, alternando Media e Substack, a sorte secondo il δ · {minage} minuti minimi nel radar · δ minimo per la prima pagina {minpub} (Media) e {minpubs} (Substack) · decadimento {decay} (Media) e {decays} (Substack) per mezz''ora di attesa · i voti contano {quartxt} dopo la creazione dell''account · bonus: {bonustemas} · velo di {gm} minuti · vita in prima pagina di {ttltxt} · scadenza nel radar dopo {newttltxt} · comunità con aura maggiore di {commaura}, fino a {commmax} per persona.</p>' WHERE slug = 'algoritmo.it';
UPDATE pages SET body = '<h1>Como funciona o δ</h1>

<h2>Sem algoritmo oculto</h2>
<p>Em {site} não há uma máquina que decida o que cada pessoa vê. O que chega à capa é decidido por uma fórmula pública, curta e visível: aparece sob cada envio do radar, com os seus números e o seu valor nesse momento. Esta página explica a fórmula por inteiro. Quem a entende, entende {site}.</p>

<h2>O δ (delta)</h2>
<p>Cada envio tem um <b>δ</b>: uma pontuação que resume quanto a comunidade o apoia e há quanto tempo espera. Calcula-se assim:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(votos que contam) + bónus do tema − decaimento × meias horas de espera</p>
<p><b>Os votos</b> são a voz das pessoas: cada registado pode votar uma vez em cada envio, a favor ou contra, e mudar de opinião. Não se pode votar no que é seu. No δ entra a diferença entre positivos e negativos, com duas regras: se várias contas votam no mesmo envio a partir da mesma ligação, contam como um só voto (guarda-se uma impressão do voto, nunca o endereço); e os votos de uma conta começam a contar {quartxt} depois de criada. Veem-se desde o primeiro momento; o δ espera.</p>
<p><b>A raiz</b> aplana o entusiasmo: quatro votos valem 2, nove valem 3, cem valem 10. Com um punhado de votos chega-se à capa; com muitos não se esmagam os outros. Um saldo negativo subtrai com a mesma raiz.</p>
<p><b>O bónus do tema</b> é um pequeno empurrão editorial e é o único parâmetro que {site} define: a maioria dos temas não soma nada; {bonustemas} somam esse bónus a qualquer envio seu com apoio. Soma-se, não multiplica: não aumenta os votos negativos nem acelera o decaimento. Os bónus são públicos e vê-los-á em cada fórmula.</p>
<p><b>A antiguidade</b> subtrai: {decay} pontos por cada meia hora de espera em Meios e {decays} em Newsletters, contando desde que o envio pode chegar à capa (aos {minage} minutos), não desde que foi enviado. É o que faz que Meios seja de hoje e que Newsletters possa ser de sempre: uma boa newsletter não caduca com a atualidade, e o seu relógio anda mais devagar.</p>
<h2>Duas capas e um radar</h2>
<p>Tudo o que se envia entra no <b>radar</b>. A partir daí, pode chegar a uma das duas capas: <b>Meios</b>, para as notícias de jornais, revistas e outros sites, e <b>Newsletters</b>, para as newsletters do Substack e afins. Um envio vai para Newsletters se tiver um link para um endereço de substack.com ou para uma newsletter do catálogo de {site}; tudo o resto vai para Meios. Cada envio compete apenas com os da sua capa.</p>
<p>Os <b>originais</b>, os textos escritos pela própria comunidade, não passam pelo radar nem vão para a capa: têm o seu separador, ordenado por data, onde são lidos, votados e comentados.</p>

<h2>O ciclo</h2>
<p>A cada <b>{cycle} minutos</b> ocorre um ciclo, sempre igual, sem intervenção manual:</p>
<p><b>1. Do radar à capa.</b> Os ciclos alternam: um publica em Meios, o seguinte em Newsletters, e assim sucessivamente. Em cada ciclo, entre os envios da capa a que toca que estão há pelo menos <b>{minage} minutos</b> no radar e têm um δ de pelo menos <b>{minpub}</b> (Meios) ou <b>{minpubs}</b> (Newsletters), publica-se <b>um só</b>, escolhido <b>à sorte</b>: cada candidato tem tantos bilhetes quanto o seu δ, por isso o de maior δ é o mais provável, mas não está garantido, e os que se seguem também saem às vezes. Sem votos não se chega. Se à capa da vez não chega nada, a vez passa à outra; se também não, nesse ciclo não se publica nada: a capa não se enche por encher. O tempo mínimo existe para que um envio não suba só porque chegou num momento tranquilo; tem de ser visto e votado antes.</p>
<p><b>Quando, exatamente.</b> Dentro de cada troço de {cycle} minutos, o minuto em que o ciclo corre muda e não se anuncia; {site} diz «antes das tal e tal». Assim ninguém pode guardar os votos para os largar um segundo antes: quem quiser ganhar tem de convencer, não cronometrar. E como é um sorteio, também não serve afinar o último voto para tirar uma décima ao do lado.</p>
<p><b>2. Caducidade.</b> O que passa <b>{newttltxt}</b> no radar sem chegar à capa é arquivado: o radar também não é para sempre.</p>
<p><b>3. Cai o véu.</b> Passados {gm} minutos da publicação de qualquer envio ou comentário, o alias do autor dá lugar ao seu nome. Antes disso, todos são alias com um halo: são julgadas as palavras, não a assinatura.</p>
<p><b>4. Arquivo.</b> O que está há {ttltxt} na capa é arquivado. Pode continuar a ler-se, mas já não se vota nem se comenta: a conversa foi o que foi.</p>
<p>A área <b>Processos</b> mostra tudo isto em tempo real: o radar ordenado por δ, com a capa para onde vai cada envio e uma estimativa de quando chega, e um fluxo com cada envio, voto, comentário e publicação na capa à medida que acontecem.</p>

<h2>Temas e comunidades</h2>
<p>Cada envio é classificado duas vezes. <b>O tema</b> é definido por {site}: são poucos, estáveis (atualidade, ciência, cultura…) e cada um tem o seu bónus público (quase sempre zero). Cada envio leva sempre um tema.</p>
<p><b>A comunidade</b> é definida pelas pessoas. Quem tiver mais de <b>{commaura} de aura</b> pode criar até <b>{commmax}</b>: um nome, uma cor e uma frase que diga do que se trata. Um envio pode pertencer a uma comunidade além do seu tema, e assim fica reunido o que interessa a um grupo sem que {site} tenha de decidir. Quem cria uma comunidade cuida dela: edita o nome, a descrição, a cor e o modelo visual; os moderadores podem fazer o mesmo e, se necessário, ocultá-la.</p>
<p>As comunidades <b>não mexem no δ</b>: não dão bónus, não sobem nem descem nada. São um lugar, não um megafone. O que é possível é escolher uma comunidade (ou um tema) como <b>página de entrada</b> no perfil: ao abrir {site}, é essa a página que aparece, e os separadores Meios e Newsletters continuam a ser as capas de todos.</p>

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
<p>Todos os parâmetros desta página são os que estão em vigor neste momento em {site}; se a equipa os mudar, esta página muda com eles. Ciclo a cada {cycle} minutos, alternando Meios e Newsletters, à sorte segundo o δ · {minage} minutos mínimos no radar · δ mínimo para a capa {minpub} (Meios) e {minpubs} (Newsletters) · decaimento {decay} (Meios) e {decays} (Newsletters) por meia hora de espera · os votos contam {quartxt} depois de criada a conta · bónus: {bonustemas} · véu de {gm} minutos · vida na capa de {ttltxt} · expiração no radar após {newttltxt} · comunidades com aura superior a {commaura}, até {commmax} por pessoa.</p>' WHERE slug = 'algoritmo.pt';

-- «¿Que es Deltha?»: duraciones en palabras y bonus en vez de coeficiente
UPDATE pages SET body = REPLACE(body, 'se archiva a las {newttl} horas de enviarse', 'se archiva {newttltxt} después de enviarse') WHERE slug = 'que-es';
UPDATE pages SET body = REPLACE(body, 'Cuando un envío lleva {ttl} horas en portada', 'Cuando un envío lleva {ttltxt} en portada') WHERE slug = 'que-es';
UPDATE pages SET body = REPLACE(body, '(un coeficiente público)', '(un bonus público que se suma)') WHERE slug = 'que-es';
UPDATE pages SET body = REPLACE(body, 'archived {newttl} hours after being submitted', 'archived {newttltxt} after being submitted') WHERE slug = 'que-es.en';
UPDATE pages SET body = REPLACE(body, 'has been on the front page for {ttl} hours', 'has been on the front page for {ttltxt}') WHERE slug = 'que-es.en';
UPDATE pages SET body = REPLACE(body, '(a public coefficient)', '(a public bonus that is added)') WHERE slug = 'que-es.en';
UPDATE pages SET body = REPLACE(body, 's''arxiva {newttl} hores després d''enviar-se', 's''arxiva {newttltxt} després d''enviar-se') WHERE slug = 'que-es.ca';
UPDATE pages SET body = REPLACE(body, 'fa {ttl} hores que és a portada', 'fa {ttltxt} que és a portada') WHERE slug = 'que-es.ca';
UPDATE pages SET body = REPLACE(body, '(un coeficient públic)', '(un bonus públic que se suma)') WHERE slug = 'que-es.ca';
UPDATE pages SET body = REPLACE(body, 'arquívase ás {newttl} horas de enviarse', 'arquívase {newttltxt} despois de enviarse') WHERE slug = 'que-es.gl';
UPDATE pages SET body = REPLACE(body, 'Cando un envío leva {ttl} horas na portada', 'Cando un envío leva {ttltxt} na portada') WHERE slug = 'que-es.gl';
UPDATE pages SET body = REPLACE(body, '(un coeficiente público)', '(un bonus público que se suma)') WHERE slug = 'que-es.gl';
UPDATE pages SET body = REPLACE(body, 'bidali eta {newttl} ordura artxibatzen da', 'bidali eta {newttltxt} igarota artxibatzen da') WHERE slug = 'que-es.eu';
UPDATE pages SET body = REPLACE(body, '{ttl} ordu azalean daramatzanean', '{ttltxt} azalean daramatzanean') WHERE slug = 'que-es.eu';
UPDATE pages SET body = REPLACE(body, '(koefiziente publiko bat)', '(gehitzen den bonus publiko bat)') WHERE slug = 'que-es.eu';
UPDATE pages SET body = REPLACE(body, 'est archivé {newttl} heures après l''envoi', 'est archivé {newttltxt} après l''envoi') WHERE slug = 'que-es.fr';
UPDATE pages SET body = REPLACE(body, 'est en une depuis {ttl} heures', 'est en une depuis {ttltxt}') WHERE slug = 'que-es.fr';
UPDATE pages SET body = REPLACE(body, '(un coefficient public)', '(un bonus public qui s''ajoute)') WHERE slug = 'que-es.fr';
UPDATE pages SET body = REPLACE(body, 'wird {newttl} Stunden nach dem Einreichen archiviert', 'wird {newttltxt} nach dem Einreichen archiviert') WHERE slug = 'que-es.de';
UPDATE pages SET body = REPLACE(body, 'Steht ein Beitrag {ttl} Stunden auf der Titelseite', 'Steht ein Beitrag {ttltxt} auf der Titelseite') WHERE slug = 'que-es.de';
UPDATE pages SET body = REPLACE(body, '(ein öffentlicher Koeffizient)', '(ein öffentlicher Bonus, der addiert wird)') WHERE slug = 'que-es.de';
UPDATE pages SET body = REPLACE(body, 'viene archiviato {newttl} ore dopo l''invio', 'viene archiviato {newttltxt} dopo l''invio') WHERE slug = 'que-es.it';
UPDATE pages SET body = REPLACE(body, 'è in prima pagina da {ttl} ore', 'è in prima pagina da {ttltxt}') WHERE slug = 'que-es.it';
UPDATE pages SET body = REPLACE(body, '(un coefficiente pubblico)', '(un bonus pubblico che si somma)') WHERE slug = 'que-es.it';
UPDATE pages SET body = REPLACE(body, 'é arquivado {newttl} horas depois do envio', 'é arquivado {newttltxt} depois do envio') WHERE slug = 'que-es.pt';
UPDATE pages SET body = REPLACE(body, 'completa {ttl} horas na capa', 'completa {ttltxt} na capa') WHERE slug = 'que-es.pt';
UPDATE pages SET body = REPLACE(body, '(um coeficiente público)', '(um bónus público que se soma)') WHERE slug = 'que-es.pt';
