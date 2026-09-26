-- 26/09/2026. Pagina publica «Cookies y JavaScript» (/cookies.php), enlazada en el pie tras «Privacidad y contacto»:
-- el inventario de lo que se guarda en el navegador y del codigo que corre en el. Texto en pages, editable en
-- admin > Paginas como privacidad, normas y algoritmo; traducciones en pages con slug 'cookies.<idioma>'
-- (de momento ingles; los demas idiomas caen al español hasta que se traduzcan, como hace page_get()).
-- Se mantiene a mano: al tocar una cookie o un script, actualizar tambien esta pagina.
SET NAMES utf8mb4;
INSERT INTO pages (slug, title, body) VALUES ('cookies', 'Cookies y JavaScript', '<h1>Cookies y JavaScript</h1>

<p>Esta página es el inventario completo de lo que {site} guarda en tu navegador y del código que ejecuta en él. No hay nada de terceros: ni una cookie, ni un script, ni una tipografía que venga de fuera. Se mantiene a mano y a la vista, y puedes comprobarlo todo abriendo las herramientas de tu navegador.</p>

<h2>Cookies</h2>
<p>Siete, todas propias y ninguna para seguirte. <b>Quien no inicia sesión ni elige nada no recibe ninguna.</b></p>
<div class="tabla"><table>
<tr><th>Cookie</th><th>Cuándo se crea</th><th>Cuánto dura</th><th>Para qué</th></tr>
<tr><td><code>ddd_s</code></td><td>al iniciar sesión</td><td>30 días, se renueva en cada visita</td><td>mantener la sesión abierta</td></tr>
<tr><td><code>ddd_k</code></td><td>al acertar la clave, mientras la web está en privado</td><td>30 días</td><td>no volver a pedirte la clave</td></tr>
<tr><td><code>ddd_skin</code></td><td>al elegir una skin sin tener cuenta</td><td>1 año</td><td>recordar la skin</td></tr>
<tr><td><code>ddd_theme</code></td><td>al elegir claro u oscuro sin tener cuenta</td><td>1 año</td><td>recordar el aspecto</td></tr>
<tr><td><code>ddd_lang</code></td><td>al elegir idioma sin tener cuenta</td><td>1 año</td><td>recordar el idioma</td></tr>
<tr><td><code>ddd_srclang</code></td><td>al marcar «Solo fuentes en mi idioma» sin tener cuenta</td><td>1 año</td><td>recordar esa casilla</td></tr>
<tr><td><code>ddd_portada</code></td><td>al abrir Medios o Substacks</td><td>1 año</td><td>devolverte a la última portada que viste</td></tr>
</table></div>
<p>Todas van marcadas <code>HttpOnly</code> (el JavaScript no puede leerlas), <code>SameSite=Lax</code> (no viajan a otros sitios) y, cuando la conexión es HTTPS, <code>Secure</code>. Con cuenta, las preferencias se guardan en tu perfil y esas cookies se borran. Al salir se borra la de sesión.</p>

<h2>Lo demás que queda en tu navegador</h2>
<ul>
<li><b>Una nota de posición</b> (<code>sessionStorage</code>): al votar, guardar o comentar se apunta por dónde ibas para devolverte ahí. Se borra al usarla y caduca a los 15 segundos.</li>
<li><b>Una caché de archivos propios</b> (service worker): la hoja de estilos, los logos, los iconos y la página que se ve sin conexión. Nada de contenido: los envíos, los comentarios y las fotos se piden siempre al servidor.</li>
</ul>
<p>No se usa <code>localStorage</code> ni ninguna base de datos del navegador.</p>

<h2>JavaScript</h2>
<p>Ocho archivos propios, unos 10 KB en total, servidos desde este mismo dominio. Ninguna biblioteca de terceros. <b>Sin JavaScript la web funciona entera</b>: se lee, se vota, se comenta, se envía y se cambia de idioma o de skin igual.</p>
<div class="tabla"><table>
<tr><th>Archivo</th><th>Dónde</th><th>Qué hace</th></tr>
<tr><td><code>register.js</code></td><td>todas las páginas</td><td>pone en marcha la caché de archivos propios</td></tr>
<tr><td><code>tabs.js</code></td><td>todas</td><td>encaja las pestañas de arriba cuando la pantalla es estrecha</td></tr>
<tr><td><code>scroll.js</code></td><td>todas</td><td>te devuelve al punto donde estabas tras votar, guardar o comentar</td></tr>
<tr><td><code>escribir.js</code></td><td>todas</td><td>pone el cursor detrás de la cita al responder y evita que una recarga se lleve lo que estás escribiendo</td></tr>
<tr><td><code>avisos.js</code></td><td>solo con la sesión iniciada</td><td>actualiza el número de la campanita cada minuto</td></tr>
<tr><td><code>share.js</code></td><td>todas</td><td>«compartir»: abre el menú del móvil o copia el enlace</td></tr>
<tr><td><code>sw.js</code></td><td>en segundo plano</td><td>guarda los archivos propios y enseña una página cuando te quedas sin conexión</td></tr>
<tr><td><code>pw.js</code></td><td>formularios con contraseña</td><td>el botón de ver la contraseña que escribes</td></tr>
</table></div>
<p>La única petición que hace el JavaScript por su cuenta es la del número de avisos, y va a este mismo sitio. Nada sale de aquí.</p>

<h2>Lo que no hay</h2>
<ul>
<li>Ni Google Analytics, ni píxeles, ni botones de redes, ni anuncios, ni mapas o vídeos incrustados de otros.</li>
<li>Ningún perfil publicitario, ningún dato vendido ni cedido.</li>
<li>Ninguna cookie para contar visitas: las visitas se cuentan con una huella del día, un resumen irreversible de tu conexión y tu navegador que cambia cada día y del que no se puede volver atrás. Nunca se guarda tu dirección IP. Está contado en <a href="/privacidad.php">Privacidad y contacto</a>.</li>
</ul>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('cookies.en', 'Cookies and JavaScript', '<h1>Cookies and JavaScript</h1>

<p>This page is the full inventory of what {site} stores in your browser and of the code it runs there. Nothing comes from anyone else: not a cookie, not a script, not a typeface. It is kept by hand and in the open, and you can check all of it with your browser''s developer tools.</p>

<h2>Cookies</h2>
<p>Seven, all our own and none of them for tracking you. <b>If you neither log in nor choose anything, you get none at all.</b></p>
<div class="tabla"><table>
<tr><th>Cookie</th><th>When it is set</th><th>How long it lasts</th><th>What for</th></tr>
<tr><td><code>ddd_s</code></td><td>when you log in</td><td>30 days, renewed on every visit</td><td>keeping you logged in</td></tr>
<tr><td><code>ddd_k</code></td><td>when you enter the key, while the site is private</td><td>30 days</td><td>not asking you for the key again</td></tr>
<tr><td><code>ddd_skin</code></td><td>when you pick a skin without an account</td><td>1 year</td><td>remembering the skin</td></tr>
<tr><td><code>ddd_theme</code></td><td>when you pick light or dark without an account</td><td>1 year</td><td>remembering the theme</td></tr>
<tr><td><code>ddd_lang</code></td><td>when you pick a language without an account</td><td>1 year</td><td>remembering the language</td></tr>
<tr><td><code>ddd_srclang</code></td><td>when you tick «Only sources in my language» without an account</td><td>1 year</td><td>remembering that box</td></tr>
<tr><td><code>ddd_portada</code></td><td>when you open Media or Substacks</td><td>1 year</td><td>taking you back to the front page you last read</td></tr>
</table></div>
<p>All of them are marked <code>HttpOnly</code> (JavaScript cannot read them), <code>SameSite=Lax</code> (they never travel to other sites) and, over HTTPS, <code>Secure</code>. With an account, your preferences live in your profile and those cookies are deleted. Logging out deletes the session one.</p>

<h2>What else stays in your browser</h2>
<ul>
<li><b>A scroll note</b> (<code>sessionStorage</code>): when you vote, save or comment, where you were is written down so you can be put back there. It is deleted as soon as it is used and expires after 15 seconds.</li>
<li><b>A cache of our own files</b> (service worker): the stylesheet, the logos, the icons and the offline page. No content: posts, comments and photos are always requested from the server.</li>
</ul>
<p>No <code>localStorage</code> and no browser databases.</p>

<h2>JavaScript</h2>
<p>Eight files of our own, about 10 KB altogether, served from this domain. No third-party libraries. <b>Without JavaScript the site works in full</b>: you can read, vote, comment, post and change language or skin just the same.</p>
<div class="tabla"><table>
<tr><th>File</th><th>Where</th><th>What it does</th></tr>
<tr><td><code>register.js</code></td><td>every page</td><td>starts the cache of our own files</td></tr>
<tr><td><code>tabs.js</code></td><td>every page</td><td>fits the top tabs on narrow screens</td></tr>
<tr><td><code>scroll.js</code></td><td>every page</td><td>puts you back where you were after voting, saving or commenting</td></tr>
<tr><td><code>escribir.js</code></td><td>every page</td><td>puts the cursor after the quote when you reply, and keeps a reload from wiping what you are writing</td></tr>
<tr><td><code>avisos.js</code></td><td>only when logged in</td><td>updates the number on the bell once a minute</td></tr>
<tr><td><code>share.js</code></td><td>every page</td><td>«share»: opens the phone''s menu or copies the link</td></tr>
<tr><td><code>sw.js</code></td><td>in the background</td><td>keeps our own files and shows a page when you go offline</td></tr>
<tr><td><code>pw.js</code></td><td>forms with a password</td><td>the button that shows the password you are typing</td></tr>
</table></div>
<p>The only request JavaScript makes on its own is the one for the number of notifications, and it goes to this very site. Nothing leaves here.</p>

<h2>What there isn''t</h2>
<ul>
<li>No Google Analytics, no pixels, no social buttons, no ads, no embedded maps or videos from anyone else.</li>
<li>No advertising profile, no data sold or handed over.</li>
<li>No cookie for counting visits: they are counted with a fingerprint of the day, an irreversible summary of your connection and your browser that changes daily and cannot be traced back. Your IP address is never stored. It is explained in <a href="/privacidad.php">Privacy and contact</a>.</li>
</ul>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
