-- migra-107 (02/10/2026): los videos de YouTube se reproducen dentro de Deltha al pulsar la miniatura (/app/video.js cambia la foto por
-- el reproductor de youtube-nocookie.com). Hasta que se pulsa, nada de YouTube se carga: la miniatura es la copia local de /media.
--   youtube_embed  1 = se reproducen aqui; 0 = el enlace abre YouTube.
-- REQUIERE en la CSP de Apache «frame-src https://www.youtube-nocookie.com» (ya en apache.conf.example): en el droplet,
--   sudo bash /var/www/deltha/scripts/vhost-actualizar.sh
-- Mientras la CSP no lo admita, video.js lo detecta y abre el video en YouTube, como antes.
-- Las paginas «Cookies y JavaScript» y «Privacidad y contacto» lo cuentan (los nueve idiomas, donde exista la pagina): es el unico
-- contenido de terceros del sitio. Los REPLACE corrigen las frases de la pagina de cookies que decian lo contrario (español e ingles).
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('youtube_embed', '1', '1 = los videos de YouTube se reproducen dentro de Deltha al pulsar la miniatura (youtube-nocookie.com); 0 = el enlace abre YouTube');
UPDATE pages SET body = CONCAT(body, '

<h2>Vídeos de YouTube</h2>
<p>Los vídeos de YouTube se muestran con una miniatura guardada en {site}: mientras no pulses reproducir, tu navegador no se conecta con YouTube. Al pulsar, el vídeo se carga desde <code>youtube-nocookie.com</code> y, desde ese momento, Google recibe tu dirección IP y puede guardar datos en tu navegador según su propia política de privacidad. Es el único contenido de terceros del sitio y solo se carga si tú lo pides. Sin JavaScript, el enlace abre el vídeo en YouTube.</p>') WHERE slug IN ('cookies', 'privacidad') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = CONCAT(body, '

<h2>YouTube videos</h2>
<p>YouTube videos are shown with a thumbnail stored on {site}: until you press play, your browser does not connect to YouTube. When you press it, the video loads from <code>youtube-nocookie.com</code> and, from that moment, Google receives your IP address and may store data in your browser under its own privacy policy. It is the only third-party content on the site and it loads only if you ask for it. Without JavaScript, the link opens the video on YouTube.</p>') WHERE slug IN ('cookies.en', 'privacidad.en') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = CONCAT(body, '

<h2>Vídeos de YouTube</h2>
<p>Els vídeos de YouTube es mostren amb una miniatura desada a {site}: mentre no premis reproduir, el teu navegador no es connecta amb YouTube. En prémer, el vídeo es carrega des de <code>youtube-nocookie.com</code> i, des d’aquell moment, Google rep la teva adreça IP i pot desar dades al teu navegador segons la seva pròpia política de privadesa. És l’únic contingut de tercers del lloc i només es carrega si tu ho demanes. Sense JavaScript, l’enllaç obre el vídeo a YouTube.</p>') WHERE slug IN ('cookies.ca', 'privacidad.ca') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = CONCAT(body, '

<h2>YouTube-Videos</h2>
<p>YouTube-Videos werden mit einem auf {site} gespeicherten Vorschaubild gezeigt: Solange du nicht auf Abspielen drückst, verbindet sich dein Browser nicht mit YouTube. Beim Abspielen wird das Video von <code>youtube-nocookie.com</code> geladen; ab diesem Moment erhält Google deine IP-Adresse und kann gemäß seiner eigenen Datenschutzerklärung Daten in deinem Browser speichern. Es ist der einzige Inhalt von Dritten auf der Website und wird nur geladen, wenn du es verlangst. Ohne JavaScript öffnet der Link das Video auf YouTube.</p>') WHERE slug IN ('cookies.de', 'privacidad.de') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = CONCAT(body, '

<h2>YouTubeko bideoak</h2>
<p>YouTubeko bideoak {site} gunean gordetako miniatura batekin erakusten dira: erreproduzitu sakatu arte, zure nabigatzailea ez da YouTuberekin konektatzen. Sakatzean, bideoa <code>youtube-nocookie.com</code> helbidetik kargatzen da eta, une horretatik aurrera, Googlek zure IP helbidea jasotzen du eta datuak gorde ditzake zure nabigatzailean, bere pribatutasun-politikaren arabera. Guneko hirugarrenen eduki bakarra da eta zuk eskatzen baduzu bakarrik kargatzen da. JavaScript gabe, estekak bideoa YouTuben irekitzen du.</p>') WHERE slug IN ('cookies.eu', 'privacidad.eu') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = CONCAT(body, '

<h2>Vidéos YouTube</h2>
<p>Les vidéos YouTube sont affichées avec une miniature enregistrée sur {site} : tant que vous n’appuyez pas sur lecture, votre navigateur ne se connecte pas à YouTube. Quand vous appuyez, la vidéo est chargée depuis <code>youtube-nocookie.com</code> et, à partir de ce moment, Google reçoit votre adresse IP et peut enregistrer des données dans votre navigateur selon sa propre politique de confidentialité. C’est le seul contenu tiers du site et il n’est chargé que si vous le demandez. Sans JavaScript, le lien ouvre la vidéo sur YouTube.</p>') WHERE slug IN ('cookies.fr', 'privacidad.fr') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = CONCAT(body, '

<h2>Vídeos de YouTube</h2>
<p>Os vídeos de YouTube amósanse cunha miniatura gardada en {site}: mentres non premas reproducir, o teu navegador non se conecta con YouTube. Ao premer, o vídeo cárgase desde <code>youtube-nocookie.com</code> e, desde ese momento, Google recibe o teu enderezo IP e pode gardar datos no teu navegador segundo a súa propia política de privacidade. É o único contido de terceiros do sitio e só se carga se ti o pides. Sen JavaScript, a ligazón abre o vídeo en YouTube.</p>') WHERE slug IN ('cookies.gl', 'privacidad.gl') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = CONCAT(body, '

<h2>Video di YouTube</h2>
<p>I video di YouTube sono mostrati con una miniatura salvata su {site}: finché non premi riproduci, il tuo browser non si collega a YouTube. Quando premi, il video viene caricato da <code>youtube-nocookie.com</code> e, da quel momento, Google riceve il tuo indirizzo IP e può salvare dati nel tuo browser secondo la propria informativa sulla privacy. È l’unico contenuto di terzi del sito e viene caricato solo se lo chiedi tu. Senza JavaScript, il link apre il video su YouTube.</p>') WHERE slug IN ('cookies.it', 'privacidad.it') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = CONCAT(body, '

<h2>Vídeos do YouTube</h2>
<p>Os vídeos do YouTube são mostrados com uma miniatura guardada em {site}: enquanto não carregares em reproduzir, o teu navegador não se liga ao YouTube. Ao carregar, o vídeo é carregado a partir de <code>youtube-nocookie.com</code> e, a partir desse momento, a Google recebe o teu endereço IP e pode guardar dados no teu navegador de acordo com a sua própria política de privacidade. É o único conteúdo de terceiros do sítio e só é carregado se o pedires. Sem JavaScript, a ligação abre o vídeo no YouTube.</p>') WHERE slug IN ('cookies.pt', 'privacidad.pt') AND body NOT LIKE '%youtube-nocookie%';
UPDATE pages SET body = REPLACE(body, 'No hay nada de terceros: ni una cookie, ni un script, ni una tipografía que venga de fuera.', 'No hay nada de terceros, con una excepción que solo se activa si tú quieres: el reproductor de YouTube (al final de esta página). Por lo demás, ni una cookie, ni un script, ni una tipografía que venga de fuera.') WHERE slug = 'cookies';
UPDATE pages SET body = REPLACE(body, 'Ocho archivos propios', 'Nueve archivos propios') WHERE slug = 'cookies';
UPDATE pages SET body = REPLACE(body, '<tr><td><code>pw.js</code>', '<tr><td><code>video.js</code></td><td>todas</td><td>al pulsar la miniatura de un vídeo de YouTube, pone el reproductor en su sitio</td></tr>
<tr><td><code>pw.js</code>') WHERE slug = 'cookies' AND body NOT LIKE '%video.js%';
UPDATE pages SET body = REPLACE(body, 'ni mapas o vídeos incrustados de otros', 'ni mapas incrustados de otros') WHERE slug = 'cookies';
UPDATE pages SET body = REPLACE(body, 'Nothing comes from anyone else: not a cookie, not a script, not a typeface.', 'Nothing comes from anyone else, with one exception that only switches on if you want it to: the YouTube player (at the end of this page). Otherwise, not a cookie, not a script, not a typeface.') WHERE slug = 'cookies.en';
UPDATE pages SET body = REPLACE(body, 'Eight files of our own', 'Nine files of our own') WHERE slug = 'cookies.en';
UPDATE pages SET body = REPLACE(body, '<tr><td><code>pw.js</code>', '<tr><td><code>video.js</code></td><td>every page</td><td>when you press the thumbnail of a YouTube video, puts the player in its place</td></tr>
<tr><td><code>pw.js</code>') WHERE slug = 'cookies.en' AND body NOT LIKE '%video.js%';
UPDATE pages SET body = REPLACE(body, 'no embedded maps or videos from anyone else', 'no embedded maps from anyone else') WHERE slug = 'cookies.en';
