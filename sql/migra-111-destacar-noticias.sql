-- migra-111 — destacar noticias (03/10/2026).
-- Para centrar el interes en comentar mientras no hay masa critica: en cada tarjeta hay un boton «Destacar» (chincheta). Vale para
-- dos cosas: los ENVIOS de las portadas Medios / Substacks / Canales (tabla stories) y las NOTICIAS RSS de «Mis medios» / «Mis substacks»
-- (tabla source_items, /ultimo.php). La destacada sale la primera de su lista con un halo ligero y no baja hasta que se destaque otra
-- del mismo grupo (se desmarca sola) o se quite en el mismo sitio: entonces cae a la cola, en la posicion que le toca por fecha.
--  - Admin: una destacada por grupo, sin caducidad. Si destaca un envio que esta en el radar, pasa a su portada en ese momento.
--  - Usuarios: cada vez que su aura alcanza una unidad entera NUEVA (6,9 -> 7,0) ganan un «destacado» (users.pin_credits, se cuenta
--    en aura_add contra users.pin_mark, el maximo entero ya alcanzado: llegar a 6, bajar a 5,5 y volver a 6 no da otro); gastarlo
--    destaca una noticia durante pin_user_minutes (30). Solo lo que ya esta en portada (no sacan nada del radar).
--    Una activa por usuario: destacar otra sustituye la anterior (y gasta otro). Quitarla a mano no devuelve el destacado.
SET NAMES utf8mb4;

ALTER TABLE users ADD COLUMN pin_credits SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER aura,   -- destacados disponibles
                  ADD COLUMN pin_mark    SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER pin_credits;   -- maximo entero de aura ya alcanzado (no se repite el premio)
UPDATE users SET pin_mark = FLOOR(aura);   -- se empieza a contar desde el aura de hoy

CREATE TABLE IF NOT EXISTS pins (
    pin_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    what        ENUM('story','item') NOT NULL,          -- story = envio (stories.story_id) | item = noticia RSS (source_items.item_id)
    ref_id      INT UNSIGNED NOT NULL,
    grupo       VARCHAR(16) NOT NULL,                   -- lista en la que va la primera: portada (medios|substacks|canales) o tipo de fuente (medio|newsletter)
    user_id     INT UNSIGNED NOT NULL,                  -- quien la destaco
    by_admin    TINYINT(1) NOT NULL DEFAULT 0,          -- 1 = destacada por el admin (sin caducidad, una por grupo)
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires     DATETIME DEFAULT NULL,                  -- NULL = no caduca (admin); usuarios: created + pin_user_minutes
    UNIQUE KEY uq_ref (what, ref_id),
    KEY idx_grupo (what, grupo, by_admin, expires),
    KEY idx_user (user_id),
    CONSTRAINT fk_pin_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('pins_enabled', '1', 'Destacar noticias (boton chincheta en la tarjeta de envios y noticias RSS; 0 = sin boton ni destacadas)'),
('pin_user_minutes', '30', 'Minutos que dura una noticia destacada por un usuario (las del admin no caducan)');

-- Texto para la gente en «¿Qué es Deltha?», delante de «Ghostear a un usuario» (migra-086), en los nueve idiomas.
-- Si ese <h2> se edito en admin -> Paginas, el REPLACE no encuentra nada: pegar el apartado a mano.
UPDATE pages SET body = REPLACE(body, '<h2>Ghostear a un usuario</h2>',
'<h2>Destacar una noticia</h2>
<p>Mientras {site} coge ritmo, el admin destaca cada día las noticias que cree que dan para comentar: la destacada va la primera de su portada, con un halo, y allí se queda hasta que se destaca otra o se quita; entonces vuelve al sitio que le toca por fecha.</p>
<p>Tú también puedes. Cada vez que tu aura alcanza una unidad entera nueva (de 6,9 a 7, por ejemplo) ganas un destacado, y cuando tienes alguno te aparece el botón en las tarjetas de lo que ya está en portada: la noticia que elijas irá la primera durante media hora. Solo cuentan las unidades que no habías alcanzado antes: llegar a 6, bajar a 5,5 y volver a 6 no da otro. Puedes tener una destacada a la vez; destacar otra sustituye la anterior.</p>

<h2>Ghostear a un usuario</h2>') WHERE slug = 'que-es';

UPDATE pages SET body = REPLACE(body, '<h2>Ghostejar un usuari</h2>',
'<h2>Destacar una notícia</h2>
<p>Mentre {site} agafa ritme, l''admin destaca cada dia les notícies que creu que donen per comentar: la destacada va la primera de la seva portada, amb un halo, i s''hi queda fins que se''n destaca una altra o es treu; aleshores torna al lloc que li toca per data.</p>
<p>Tu també pots. Cada vegada que la teva aura arriba a una unitat sencera nova (de 6,9 a 7, per exemple) guanyes un destacat, i quan en tens algun t''apareix el botó a les targetes del que ja és a portada: la notícia que triïs anirà la primera durant mitja hora. Només compten les unitats que no havies assolit abans: arribar a 6, baixar a 5,5 i tornar a 6 no en dona un altre. Pots tenir una destacada alhora; destacar-ne una altra substitueix l''anterior.</p>

<h2>Ghostejar un usuari</h2>') WHERE slug = 'que-es.ca';

UPDATE pages SET body = REPLACE(body, '<h2>Erabiltzaile bat ghosteatzea</h2>',
'<h2>Albiste bat nabarmentzea</h2>
<p>{site} martxa hartzen duen bitartean, adminak egunero nabarmentzen ditu komentatzeko modukoak iruditzen zaizkion albisteak: nabarmendua bere azalean lehena doa, argi-koroa batekin, eta hor geratzen da beste bat nabarmendu edo kendu arte; orduan dataren arabera dagokion lekura itzultzen da.</p>
<p>Zuk ere egin dezakezu. Zure aurak unitate oso berri bat lortzen duen bakoitzean (6,9tik 7ra, adibidez) nabarmentze bat irabazten duzu, eta baduzunean botoia agertzen zaizu dagoeneko azalean dagoenaren txarteletan: aukeratzen duzun albistea lehena izango da ordu erdiz. Aurretik lortu gabeko unitateek bakarrik balio dute: 6ra iritsi, 5,5era jaitsi eta berriro 6ra igotzeak ez du beste bat ematen. Nabarmendu bakarra izan dezakezu aldi berean; beste bat nabarmentzeak aurrekoa ordezkatzen du.</p>

<h2>Erabiltzaile bat ghosteatzea</h2>') WHERE slug = 'que-es.eu';

UPDATE pages SET body = REPLACE(body, '<h2>Ghostear a un usuario</h2>',
'<h2>Destacar unha noticia</h2>
<p>Mentres {site} colle ritmo, o admin destaca cada día as noticias que cre que dan para comentar: a destacada vai a primeira da súa portada, cun halo, e alí queda ata que se destaca outra ou se quita; entón volve ao sitio que lle toca por data.</p>
<p>Ti tamén podes. Cada vez que a túa aura acada unha unidade enteira nova (de 6,9 a 7, por exemplo) gañas un destacado, e cando tes algún aparéceche o botón nas tarxetas do que xa está en portada: a noticia que escollas irá a primeira durante media hora. Só contan as unidades que non acadaras antes: chegar a 6, baixar a 5,5 e volver a 6 non dá outro. Podes ter unha destacada á vez; destacar outra substitúe a anterior.</p>

<h2>Ghostear a un usuario</h2>') WHERE slug = 'que-es.gl';

UPDATE pages SET body = REPLACE(body, '<h2>Ghosting a user</h2>',
'<h2>Featuring a story</h2>
<p>While {site} gets going, the admin features each day the stories he thinks are worth discussing: the featured one goes first on its front page, with a halo, and stays there until another is featured or it is removed; then it goes back to where its date puts it.</p>
<p>You can too. Every time your aura reaches a new whole unit (from 6.9 to 7, say) you earn a highlight, and when you have one the button appears on the cards of what is already on a front page: the story you pick goes first for half an hour. Only units you had never reached before count: getting to 6, dropping to 5.5 and climbing back to 6 does not earn another. You can have one featured story at a time; featuring another replaces it.</p>

<h2>Ghosting a user</h2>') WHERE slug = 'que-es.en';

UPDATE pages SET body = REPLACE(body, '<h2>Ghoster un utilisateur</h2>',
'<h2>Mettre une actualité en avant</h2>
<p>Pendant que {site} prend son rythme, l''admin met chaque jour en avant les actualités qui lui semblent mériter discussion : celle qui est mise en avant passe en tête de sa une, avec un halo, et y reste jusqu''à ce qu''une autre soit mise en avant ou qu''on la retire ; elle reprend alors la place que lui donne sa date.</p>
<p>Vous le pouvez aussi. Chaque fois que votre aura atteint une nouvelle unité entière (de 6,9 à 7, par exemple) vous gagnez une mise en avant, et quand vous en avez une le bouton apparaît sur les cartes de ce qui est déjà en une : l''actualité que vous choisissez passe en tête pendant une demi-heure. Seules comptent les unités jamais atteintes auparavant : arriver à 6, redescendre à 5,5 et remonter à 6 n''en donne pas une autre. Vous ne pouvez en avoir qu''une à la fois ; en mettre une autre en avant remplace la précédente.</p>

<h2>Ghoster un utilisateur</h2>') WHERE slug = 'que-es.fr';

UPDATE pages SET body = REPLACE(body, '<h2>Jemanden ghosten</h2>',
'<h2>Eine Meldung hervorheben</h2>
<p>Während {site} in Schwung kommt, hebt der Admin jeden Tag die Meldungen hervor, die ihm diskussionswürdig scheinen: Die hervorgehobene steht auf ihrer Startseite an erster Stelle, mit einem Schein, und bleibt dort, bis eine andere hervorgehoben oder sie entfernt wird; dann rückt sie an den Platz zurück, den ihr Datum ihr gibt.</p>
<p>Du kannst das auch. Jedes Mal, wenn deine Aura eine neue ganze Einheit erreicht (von 6,9 auf 7 etwa), bekommst du eine Hervorhebung, und sobald du eine hast, erscheint der Knopf auf den Karten dessen, was schon auf einer Startseite steht: Die Meldung, die du wählst, steht eine halbe Stunde lang vorn. Nur Einheiten zählen, die du vorher nie erreicht hattest: Auf 6 kommen, auf 5,5 fallen und wieder auf 6 steigen bringt keine weitere. Du kannst jeweils eine Meldung hervorheben; eine andere hervorzuheben ersetzt die vorige.</p>

<h2>Jemanden ghosten</h2>') WHERE slug = 'que-es.de';

UPDATE pages SET body = REPLACE(body, '<h2>Ghostare un utente</h2>',
'<h2>Mettere in evidenza una notizia</h2>
<p>Mentre {site} prende ritmo, l''admin mette in evidenza ogni giorno le notizie che gli sembrano da commentare: quella in evidenza va per prima nella sua prima pagina, con un alone, e lì resta finché non se ne evidenzia un''altra o non viene tolta; allora torna al posto che le spetta per data.</p>
<p>Puoi farlo anche tu. Ogni volta che la tua aura raggiunge una nuova unità intera (da 6,9 a 7, per esempio) guadagni un''evidenza, e quando ne hai una ti compare il pulsante nelle schede di ciò che è già in prima pagina: la notizia che scegli andrà per prima per mezz''ora. Contano solo le unità mai raggiunte prima: arrivare a 6, scendere a 5,5 e risalire a 6 non ne dà un''altra. Puoi averne una in evidenza alla volta; evidenziarne un''altra sostituisce la precedente.</p>

<h2>Ghostare un utente</h2>') WHERE slug = 'que-es.it';

UPDATE pages SET body = REPLACE(body, '<h2>Ghostear um utilizador</h2>',
'<h2>Destacar uma notícia</h2>
<p>Enquanto {site} ganha ritmo, o admin destaca todos os dias as notícias que acha que dão para comentar: a destacada vai em primeiro na sua capa, com um halo, e aí fica até se destacar outra ou ser retirada; então volta ao lugar que lhe cabe por data.</p>
<p>Tu também podes. De cada vez que a tua aura atinge uma unidade inteira nova (de 6,9 para 7, por exemplo) ganhas um destaque, e quando tens algum aparece-te o botão nos cartões do que já está em capa: a notícia que escolheres irá em primeiro durante meia hora. Só contam as unidades que nunca tinhas atingido: chegar a 6, descer a 5,5 e voltar a 6 não dá outro. Podes ter uma destacada de cada vez; destacar outra substitui a anterior.</p>

<h2>Ghostear um utilizador</h2>') WHERE slug = 'que-es.pt';

