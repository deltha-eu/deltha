-- Pagina «¿Que es Deltha?» (/que-es.php): sustituye a la presentacion de diapositivas (/delta/), que desaparece.
-- Texto en pages (slug 'que-es'), editable en admin > Paginas como privacidad, normas y algoritmo.
-- Marcadores nuevos: {skins} (numero de skins) y {auradl} (fecha limite del reparto de propiedad, config.aura_deadline).
SET NAMES utf8mb4;
INSERT INTO pages (slug, title, body) VALUES ('que-es', '¿Qué es Deltha?', '<h1>¿Qué es Deltha?</h1>

<p>{site} es tres cosas a la vez: <b>un agregador de noticias</b>, donde la gente envía enlaces y decide con sus votos qué llega a portada; <b>una red social contralgorítmica</b>, sin máquina que elija por ti qué ver; y <b>un espacio para el debate</b>, con hilos que nacen, se discuten y desaparecen.</p>

<h2>De dónde viene</h2>
<p>De cinco sitios. Del <a href="https://contralgoritmia.com/" rel="noopener">manifiesto contralgorítmico</a> viene la idea de fondo: recuperar el criterio humano frente a la hegemonía del algoritmo. De <b>Menéame</b>, el agregador: la comunidad decide qué llega a portada. De <b>X</b>, el diseño: una línea de tiempo limpia y rápida. De <b>4chan</b>, la efimeridad: los hilos nacen, se discuten y desaparecen. Y del <b>modelo de negocio de Jot Down</b>, la forma de sostenerse: sin publicidad programática ni rastreo, una comunidad que sostiene lo que lee.</p>

<h2>Cómo funciona</h2>
<p><b>Es efímera.</b> Los envíos y los comentarios se archivan a las {ttl} horas. Lo que importa es lo que pasa ahora, no lo que se acumula.</p>
<p><b>No deja huella.</b> Sin cookies de rastreo, sin publicidad programática, sin terceros. Puedes comprobarlo abriendo las herramientas de tu navegador.</p>
<p><b>Es horizontal.</b> Todo se publica bajo un alias y el nombre del autor solo aparece a los {gm} minutos, para que se juzguen las palabras antes que a quien las dice.</p>
<p><b>Se ve por dentro.</b> La sección Procesos enseña en directo la cola, el δ de cada envío y cada voto, comentario y portada según ocurren.</p>
<p><b>Sin algoritmo oculto.</b> Lo que llega a portada lo decide una fórmula pública, corta y visible bajo cada envío: el <b>δ</b>. Está explicada entera en <a href="/algoritmo.php">Cómo funciona el δ</a>.</p>

<h2>El aura</h2>
<p>No hay karma. Hay <b>aura</b>, y mide una sola cosa: tu compromiso con la comunidad. Se gana estando y aportando, no gustando: +{auraport} por cada envío tuyo que llega a portada, +{auraenv} por cada envío o artículo, +{auracom} por cada comentario, como mucho +{auracap} al día. Y baja: cada día pierdes {auradec}, hasta el mínimo de {aurastart}. No se acumula: se sostiene. Mide quién está, no quién estuvo.</p>
<p><b>Nunca da altavoz.</b> El aura no sube noticias ni ordena comentarios. Todo el mundo ve lo mismo, en el mismo orden. Se ve como luz alrededor de tu alias, nada más. Lo único que abre son puertas: con más de {commaura} puedes crear comunidades y editar tus comentarios recién escritos.</p>

<h2>¿Qué se puede publicar?</h2>
<p><b>Todo lo que permite la ley.</b> El límite no lo pone una línea editorial ni un gusto: lo pone la ley. Fuera quedan los insultos, los delitos de odio, las amenazas y todo lo que sea delito. Están escritas en las <a href="/normas.php">normas de uso</a>.</p>
<p><b>Modera una IA que vive en nuestros servidores.</b> Ningún envío ni comentario sale de ellos para ser revisado: no hay terceros mirando lo que escribes. Cuando duda, decide una persona. Cualquiera puede reportar un comentario.</p>
<p><b>Y hay un correo para todo lo demás.</b> Quejas, felicitaciones y propuestas: <b>info@deltha.eu</b>.</p>

<h2>Temas y comunidades</h2>
<p><b>Los temas los pone {site}.</b> Pocos y estables: actualidad, ciencia, cultura… Cada envío lleva uno, y el tema puede dar un pequeño empujón al δ (un coeficiente público).</p>
<p><b>Las comunidades las crea la gente.</b> Con más de {commaura} de aura puedes abrir hasta {commmax}: un nombre, un color y de qué va. Un envío puede sumarse a una o varias, y quien la creó (o un moderador) la cuida. Cada comunidad puede elegir una skin propia, que se aplica a su página y a sus hilos. Y tú puedes elegir una comunidad o un tema como página de entrada.</p>

<h2>A tu manera</h2>
<p><b>{skins} skins.</b> Misma portada, mismos números, distinta piel: de revista, de foro, de terminal, de manga, de fanzine… Se elige en la pestaña Skins, y cada una tiene versión clara y oscura.</p>
<p><b>En tu idioma y en tu bolsillo.</b> Ocho idiomas: español, catalán, euskera, gallego, inglés, francés, alemán e italiano. Se instala como app en el móvil, con mensajes privados y chat entre registrados, y puedes guardar envíos en favoritos. Nada de esto cambia lo que ves: solo cómo lo ves.</p>

<h2>¿Quieres ser propietario de {site}?</h2>
<p>{site} es un proyecto abierto a todo el mundo. Para ser copropietario no te pedimos dinero: solo que uses la plataforma. <b>El 50 % de la propiedad de {site} se repartirá entre todos los usuarios registrados que tengan aura {auramax} el {auradl}.</b></p>
<p>¿Quieres probarla? <a href="/registro.php">Regístrate</a> o, si ya tienes cuenta, <a href="/login.php">entra</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
