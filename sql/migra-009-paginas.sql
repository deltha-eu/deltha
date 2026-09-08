-- Paginas editables desde admin (privacidad). El cuerpo es HTML; {site}, {ttl} y {gm} se sustituyen al mostrar.
CREATE TABLE IF NOT EXISTS pages (
    slug    VARCHAR(32) NOT NULL PRIMARY KEY,
    title   VARCHAR(120) NOT NULL,
    body    MEDIUMTEXT NOT NULL,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT IGNORE INTO pages (slug, title, body) VALUES ('privacidad', 'Privacidad y contacto', '<h1>Privacidad y contacto</h1>

<h2>Quién está detrás</h2>
<p><b>{site}</b> es un servicio de <b>Jot Down Group Media Company</b>, NIF B88863592, con domicilio en Calle Palillos, núm. 6, puerta 36, 41500 Alcalá de Guadaíra (Sevilla), España. Es la responsable del tratamiento de los datos que se describen aquí.</p>
<p>Para cualquier asunto relacionado con tus datos, y también para quejas, felicitaciones y propuestas, escribe a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Lo que no hacemos</h2>
<p>No usamos cookies con quien no inicia sesión. No hay rastreadores, ni analítica de terceros, ni publicidad programática, ni recursos cargados desde otros dominios: todo lo que muestra esta web sale de nuestros propios servidores. Puedes comprobarlo con las herramientas de desarrollo de tu navegador. No vendemos ni cedemos datos a nadie.</p>

<h2>Qué datos tratamos y para qué</h2>
<p><b>Si solo lees</b>, no tratamos ningún dato tuyo más allá de los registros técnicos del servidor (dirección IP, fecha, página solicitada y navegador), que existen en cualquier servidor web, sirven para mantener el servicio y detectar abusos, y se borran en un plazo breve. Para saber cuántas personas hay en el sitio en cada momento guardamos durante unos minutos una huella no reversible de tu visita (calculada a partir de la IP, el navegador y el día, y que no permite recuperarlos); se borra sola a los diez minutos y no se usa para nada más.</p>
<p><b>Si comentas sin cuenta</b>, tu comentario se publica bajo un alias generado a partir de una huella no reversible de tu dirección IP, el hilo y un secreto del servidor. Guardamos esa huella truncada (para que tu alias sea el mismo dentro de un hilo), nunca la dirección IP.</p>
<p><b>Si creas una cuenta</b>, guardamos tu nombre de usuario, tu correo electrónico y tu contraseña cifrada de forma irreversible. El correo sirve únicamente para gestionar tu cuenta; no enviamos boletines ni publicidad. Puedes añadir una frase y una foto de perfil; a las fotos les eliminamos los metadatos (EXIF) al subirlas. Con cuenta se crea una única cookie técnica de sesión, imprescindible para saber que eres tú, que desaparece al salir. Guardamos también la hora de tu última visita, que solo sirve para contar cuántas personas están conectadas en cada momento.</p>
<p><b>Si envías una noticia</b>, la URL, el titular, la entradilla y la foto que aportes se publican. Las fotos se recortan y se les eliminan los metadatos en el servidor.</p>
<p><b>Mientras el acceso sea privado</b>, quien entre con la clave de acceso recibe una cookie técnica que solo contiene una huella de esa clave. Dejará de existir cuando abramos el sitio.</p>

<h2>Cuánto tiempo</h2>
<p>{site} es efímera por diseño. Los envíos y sus comentarios se archivan a las {ttl} horas de llegar a portada. Tu nombre de usuario aparece junto a cada aportación a los {gm} minutos de publicarla; hasta entonces solo se ve el alias. Los datos de tu cuenta se conservan mientras la mantengas abierta.</p>

<h2>Moderación</h2>
<p>La moderación la hace una inteligencia artificial que se ejecuta en nuestros propios servidores: ningún envío ni comentario sale de ellos para ser revisado por terceros. Solo se filtra lo que la ley no permite (insultos, delitos de odio, amenazas y, en general, cualquier conducta delictiva). Cuando el sistema duda, decide una persona. Si crees que una decisión ha sido injusta, escríbenos.</p>

<h2>Tus derechos</h2>
<p>Puedes acceder a tus datos, rectificarlos, pedir que los borremos, oponerte a su tratamiento, limitarlo o solicitar su portabilidad, conforme al Reglamento (UE) 2016/679 y a la Ley Orgánica 3/2018. Basta con escribir a <a href="mailto:info@deltha.eu">info@deltha.eu</a> desde el correo con el que te registraste. Si no quedas conforme, puedes reclamar ante la Agencia Española de Protección de Datos (aepd.es).</p>
<p>La base jurídica del tratamiento es la ejecución del servicio que solicitas al registrarte o participar, y nuestro interés legítimo en mantener el sitio seguro y dentro de la ley.</p>

<h2>Cambios</h2>
<p>Si cambiamos esta política, lo diremos aquí. Última actualización: septiembre de 2026.</p>');
