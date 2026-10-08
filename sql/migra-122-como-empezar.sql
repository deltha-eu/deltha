-- migra-122 (08/10/2026): pagina «Como empezar» (/empezar), pedida por Angel: los lectores que llegan de Substack no han usado
-- un foro en su vida. FAQ de arranque en pages (slug 'empezar', editable en admin > Paginas como que-es, normas, privacidad y
-- algoritmo), enlazada en el pie («Como empezar») y en el Chat, a la derecha de «Por hilos / Por tiempo». Español e ingles;
-- el resto de idiomas ven la española hasta que se traduzca (page_get).
SET NAMES utf8mb4;
INSERT INTO pages (slug, title, body) VALUES ('empezar', 'Cómo empezar', '<h1>Cómo empezar</h1>

<p>{site} es un sitio donde la gente comparte lo que lee —artículos de medios, boletines de Substack, vídeos— y lo comenta. Aquí no hay un algoritmo que decida por ti: lo que sube a portada lo deciden los votos, con una regla única, pública e igual para todos. Si vienes de Substack o de las redes y nunca has usado un foro, esto es lo que necesitas saber. Se lee en cinco minutos.</p>

<h2>¿Hace falta cuenta?</h2>
<p>Para leer, no. Para votar, enviar enlaces, guardar favoritos y seguir medios, sí. Se crea en un minuto en <a href="/registro">Crear cuenta</a>: un nombre de usuario, un correo y una contraseña. Te llega un correo con un enlace de activación; si no lo ves, mira en la carpeta de spam, y si no llega, pide otro en <a href="/reenviar">reenviar</a>. No pedimos teléfono ni nada más.</p>

<h2>Las portadas</h2>
<p>Arriba hay tres: <b>Medios</b> (prensa y webs), <b>Substacks</b> (boletines y newsletters) y <b>Canales</b> (vídeos). La que ves al entrar es la última que abriste. Cada una tiene su propia cola y su propio ritmo: nada de una se mezcla con la otra.</p>

<h2>El radar</h2>
<p>Todo lo que alguien envía entra primero en el <b>Radar</b>, que es la sala de espera. Ahí la gente lo vota. Cada {cycle} minutos, el envío con más apoyo de la portada que toca sube a ella, siempre que llegue a un mínimo de votos. Debajo de cada envío ves la cuenta exacta (el <b>δ</b>): cuántos votos, qué suma el tema y cuánto resta el tiempo. No hay nada más; está todo explicado en <a href="/algoritmo">Cómo funciona el δ</a>.</p>

<h2>Votar</h2>
<p>Cada envío tiene una flecha hacia arriba y otra hacia abajo. Arriba es «esto merece portada»; abajo, «esto no». Un voto por persona y envío. Si te equivocas, pulsa otra vez la misma flecha en los primeros minutos y se anula. No votes tus propios envíos: no se puede.</p>

<h2>Enviar un enlace</h2>
<p>El botón <b>+</b> (abajo a la derecha, o <b>Enviar</b> en el menú). Pega la dirección del artículo, el boletín o el vídeo; {site} rellena el titular, la entradilla y la foto, y tú los retocas si quieres. Elige un <b>tema</b> y envía. Entra en el radar y el resto lo decide la gente. Los enlaces van sin códigos de seguimiento: los quitamos nosotros. Si ya lo había enviado alguien, te lleva a su hilo. Si tienes algo propio que decir, sin enlace, eso es un <a href="/articulo">Original</a>: un texto de hasta mil palabras que se lee, se vota y se comenta en su pestaña.</p>

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

<h2>Do I need an account?</h2>
<p>Not to read. To vote, submit links, save favourites and follow sources, yes. It takes a minute at <a href="/registro">Create account</a>: a username, an email and a password. You get an activation link by email; check your spam folder if you cannot see it, and ask for another at <a href="/reenviar">resend</a> if it never arrives. We do not ask for a phone number or anything else.</p>

<h2>The front pages</h2>
<p>There are three at the top: <b>Medios</b> (press and websites), <b>Substacks</b> (newsletters) and <b>Canales</b> (videos). The one you see when you arrive is the last one you opened. Each has its own queue and its own pace: nothing from one mixes with another.</p>

<h2>The radar</h2>
<p>Everything anyone submits goes first to the <b>Radar</b>, the waiting room. People vote there. Every {cycle} minutes, the most supported submission of the front page whose turn it is goes up, as long as it reaches a minimum number of votes. Under each submission you see the exact sum (the <b>δ</b>): how many votes, what the topic adds and what time takes away. That is all there is; it is explained in <a href="/algoritmo">How the δ works</a>.</p>

<h2>Voting</h2>
<p>Each submission has an up arrow and a down arrow. Up means "this deserves the front page"; down, "this does not". One vote per person and submission. If you make a mistake, press the same arrow again within the first minutes and it is undone. You cannot vote your own submissions.</p>

<h2>Submitting a link</h2>
<p>The <b>+</b> button (bottom right, or <b>Enviar</b> in the menu). Paste the address of the article, newsletter or video; {site} fills in the headline, the summary and the picture, and you adjust them if you like. Pick a <b>topic</b> and submit. It enters the radar and people decide the rest. Links go without tracking codes: we strip them. If someone already submitted it, you are taken to their thread. If you have something of your own to say, with no link, that is an <a href="/articulo">Original</a>: a text of up to a thousand words that is read, voted and discussed in its own tab.</p>

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
