-- migra-086: «Ghostear» a un usuario (ignorar). Un solo boton, sin niveles: si A ghostea a B, cada uno ve al otro
-- para siempre como un Ghost (el alias del hilo en envios y comentarios, y un alias estable por pareja en el Chat,
-- las menciones y los avisos): sin revelacion a los ghost_minutes. Los comentarios de B se le pliegan a A;
-- ninguno puede ver el perfil del otro ni escribirle un privado; no se avisan entre ellos. Nadie recibe aviso,
-- no hay listas publicas. Los votos no se tocan (los envios son de todos) y no se oculta nada al resto. lib/ghost.php
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS user_ghosts (
    user_id     INT UNSIGNED NOT NULL,                    -- quien ghostea
    target_id   INT UNSIGNED NOT NULL,                    -- a quien
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, target_id),
    KEY idx_ghost_target (target_id),                     -- «quien me ha ghosteado» (el velo es simetrico)
    CONSTRAINT fk_ug_user   FOREIGN KEY (user_id)   REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_ug_target FOREIGN KEY (target_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('ghost_users_enabled', '1', '1 = boton «Ghostear» (ignorar usuarios): cada uno ve al otro siempre como Ghost, comentarios plegados, sin perfil ni privados entre ambos');

-- «¿Que es Deltha?»: parrafo sobre ghostear y sobre los votos definitivos, antes de «¿Quieres ser propietario?», en los nueve idiomas.
-- Si la pagina se edito en admin y ese titulo ya no coincide, no se toca (añadirlo a mano en admin > Paginas).
UPDATE pages SET body = REPLACE(body, '<h2>¿Quieres ser propietario de {site}?</h2>',
'<h2>Ghostear a un usuario</h2>
<p>Si alguien te incomoda, puedes ghostearlo desde su perfil o desde cualquiera de sus comentarios. A partir de ese momento, para él serás siempre un Ghost: no verá tu nombre en tus envíos, comentarios ni menciones, no podrá ver tu perfil ni escribirte por chat. Y tú lo verás igual: como un Ghost más, con sus comentarios plegados, por si en algún momento quieres leerlos.</p>
<p>Nadie recibe aviso. Él seguirá viendo la web con normalidad, solo que donde antes había un nombre habrá un Ghost, como tantos otros. No hay listas públicas de ghosteados.</p>
<p>Ghostear no afecta a los votos: los envíos son de todos y cualquiera puede votarlos. Tampoco borra ni oculta nada para el resto de usuarios.</p>
<p>Puedes ver a quién has ghosteado y deshacerlo cuando quieras desde tu perfil.</p>

<h2>Sobre los votos</h2>
<p>En {site} el voto es definitivo: una vez emitido no se puede cambiar ni retirar. Durante los {gm} minutos en que un envío o comentario aparece como Ghost, nadie sabe quién lo ha escrito, así que los votos de ese tiempo son a lo que se dice, no a quien lo dice. Que no se puedan cambiar después es lo que hace que eso se cumpla.</p>

<h2>¿Quieres ser propietario de {site}?</h2>') WHERE slug = 'que-es';

UPDATE pages SET body = REPLACE(body, '<h2>Vols ser propietari de {site}?</h2>',
'<h2>Ghostejar un usuari</h2>
<p>Si algú t''incomoda, pots ghostejar-lo des del seu perfil o des de qualsevol dels seus comentaris. A partir d''aquell moment, per a ell seràs sempre un Ghost: no veurà el teu nom als teus enviaments, comentaris ni mencions, no podrà veure el teu perfil ni escriure''t per xat. I tu el veuràs igual: com un Ghost més, amb els seus comentaris plegats, per si en algun moment els vols llegir.</p>
<p>Ningú no rep cap avís. Ell continuarà veient el web amb normalitat, només que on abans hi havia un nom hi haurà un Ghost, com tants altres. No hi ha llistes públiques de ghostejats.</p>
<p>Ghostejar no afecta els vots: els enviaments són de tothom i qualsevol els pot votar. Tampoc no esborra ni amaga res per a la resta d''usuaris.</p>
<p>Pots veure a qui has ghostejat i desfer-ho quan vulguis des del teu perfil.</p>

<h2>Sobre els vots</h2>
<p>A {site} el vot és definitiu: un cop emès no es pot canviar ni retirar. Durant els {gm} minuts en què un enviament o un comentari apareix com a Ghost, ningú no sap qui l''ha escrit, així que els vots d''aquell temps són al que es diu, no a qui ho diu. Que no es puguin canviar després és el que fa que això es compleixi.</p>

<h2>Vols ser propietari de {site}?</h2>') WHERE slug = 'que-es.ca';

UPDATE pages SET body = REPLACE(body, '<h2>{site}-ren jabe izan nahi duzu?</h2>',
'<h2>Erabiltzaile bat ghosteatzea</h2>
<p>Norbaitek deseroso sentiarazten bazaitu, ghostea dezakezu bere profiletik edo bere edozein iruzkinetatik. Une horretatik aurrera, harentzat beti Ghost bat izango zara: ez du zure izena ikusiko zure bidalketetan, iruzkinetan ez aipamenetan, ezin izango du zure profila ikusi ezta txatez idatzi ere. Eta zuk berdin ikusiko duzu: beste Ghost bat bezala, bere iruzkinak tolestuta, noizbait irakurri nahi badituzu.</p>
<p>Inork ez du abisurik jasotzen. Berak webgunea normal ikusten jarraituko du; lehen izen bat zegoen tokian Ghost bat egongo da, beste hainbat bezala. Ez dago ghosteatutakoen zerrenda publikorik.</p>
<p>Ghosteatzeak ez die botoei eragiten: bidalketak guztionak dira eta edonork bozka ditzake. Ez du ezer ezabatzen ez ezkutatzen gainerako erabiltzaileentzat.</p>
<p>Nor ghosteatu duzun ikus dezakezu eta nahi duzunean desegin, zure profiletik.</p>

<h2>Botoei buruz</h2>
<p>{site}-n botoa behin betikoa da: behin emanda ezin da aldatu ez kendu. Bidalketa edo iruzkin bat Ghost gisa agertzen den {gm} minutuetan inork ez daki nork idatzi duen; beraz, tarte horretako botoak esaten denari doazkio, ez esaten duenari. Gero aldatu ezin izatea da hori betetzen dela bermatzen duena.</p>

<h2>{site}-ren jabe izan nahi duzu?</h2>') WHERE slug = 'que-es.eu';

UPDATE pages SET body = REPLACE(body, '<h2>Queres ser propietario de {site}?</h2>',
'<h2>Ghostear a un usuario</h2>
<p>Se alguén te incomoda, podes ghostealo desde o seu perfil ou desde calquera dos seus comentarios. A partir dese momento, para el serás sempre un Ghost: non verá o teu nome nos teus envíos, comentarios nin mencións, non poderá ver o teu perfil nin escribirche por chat. E ti veralo igual: coma un Ghost máis, cos seus comentarios pregados, por se nalgún momento queres lelos.</p>
<p>Ninguén recibe aviso. El seguirá vendo a web con normalidade, só que onde antes había un nome haberá un Ghost, coma tantos outros. Non hai listas públicas de ghosteados.</p>
<p>Ghostear non afecta os votos: os envíos son de todos e calquera pode votalos. Tampouco borra nin oculta nada para o resto de usuarios.</p>
<p>Podes ver a quen ghosteaches e desfacelo cando queiras desde o teu perfil.</p>

<h2>Sobre os votos</h2>
<p>En {site} o voto é definitivo: unha vez emitido non se pode cambiar nin retirar. Durante os {gm} minutos en que un envío ou comentario aparece como Ghost, ninguén sabe quen o escribiu, así que os votos dese tempo son ao que se di, non a quen o di. Que non se poidan cambiar despois é o que fai que iso se cumpra.</p>

<h2>Queres ser propietario de {site}?</h2>') WHERE slug = 'que-es.gl';

UPDATE pages SET body = REPLACE(body, '<h2>Want to own {site}?</h2>',
'<h2>Ghosting a user</h2>
<p>If someone bothers you, you can ghost them from their profile or from any of their comments. From then on, to them you will always be a Ghost: they will not see your name on your posts, comments or mentions, and they will not be able to see your profile or message you. And you will see them the same way: as one more Ghost, with their comments folded, in case you ever want to read them.</p>
<p>Nobody is notified. They will keep seeing the site as usual, except that where there used to be a name there will be a Ghost, like so many others. There are no public lists of ghosted users.</p>
<p>Ghosting does not affect votes: posts belong to everyone and anyone can vote on them. Nor does it delete or hide anything from other users.</p>
<p>You can see whom you have ghosted and undo it whenever you like from your profile.</p>

<h2>About votes</h2>
<p>On {site} a vote is final: once cast it cannot be changed or withdrawn. During the {gm} minutes in which a post or comment appears as a Ghost, nobody knows who wrote it, so the votes of that time go to what is said, not to who says it. Not being able to change them afterwards is what makes that hold.</p>

<h2>Want to own {site}?</h2>') WHERE slug = 'que-es.en';

UPDATE pages SET body = REPLACE(body, '<h2>Tu veux devenir propriétaire de {site} ?</h2>',
'<h2>Ghoster un utilisateur</h2>
<p>Si quelqu''un te gêne, tu peux le ghoster depuis son profil ou depuis n''importe lequel de ses commentaires. À partir de ce moment, pour lui tu seras toujours un Ghost : il ne verra pas ton nom sur tes envois, tes commentaires ni tes mentions, il ne pourra ni voir ton profil ni t''écrire en privé. Et tu le verras de la même façon : comme un Ghost de plus, avec ses commentaires repliés, au cas où tu voudrais un jour les lire.</p>
<p>Personne n''est prévenu. Il continuera à voir le site normalement, sauf que là où il y avait un nom il y aura un Ghost, comme tant d''autres. Il n''y a pas de listes publiques de ghostés.</p>
<p>Ghoster n''a aucun effet sur les votes : les envois sont à tout le monde et n''importe qui peut les voter. Cela n''efface ni ne cache rien non plus aux autres utilisateurs.</p>
<p>Tu peux voir qui tu as ghosté et revenir en arrière quand tu veux depuis ton profil.</p>

<h2>À propos des votes</h2>
<p>Sur {site}, le vote est définitif : une fois émis, il ne peut être ni changé ni retiré. Pendant les {gm} minutes où un envoi ou un commentaire apparaît comme Ghost, personne ne sait qui l''a écrit ; les votes de ce moment vont donc à ce qui est dit, pas à qui le dit. Qu''on ne puisse pas les changer ensuite, c''est ce qui garantit que cela tienne.</p>

<h2>Tu veux devenir propriétaire de {site} ?</h2>') WHERE slug = 'que-es.fr';

UPDATE pages SET body = REPLACE(body, '<h2>Willst du Miteigentümer von {site} werden?</h2>',
'<h2>Jemanden ghosten</h2>
<p>Wenn dich jemand stört, kannst du ihn von seinem Profil aus oder von jedem seiner Kommentare aus ghosten. Von da an bist du für ihn immer ein Ghost: Er sieht deinen Namen weder bei deinen Beiträgen noch bei deinen Kommentaren oder Erwähnungen, er kann dein Profil nicht sehen und dir nicht privat schreiben. Und du siehst ihn genauso: als einen Ghost unter vielen, mit eingeklappten Kommentaren, falls du sie doch einmal lesen willst.</p>
<p>Niemand wird benachrichtigt. Er sieht die Seite weiter wie gewohnt, nur dass dort, wo vorher ein Name stand, ein Ghost steht, wie so viele andere. Es gibt keine öffentlichen Listen von Geghosteten.</p>
<p>Ghosten hat keinen Einfluss auf die Stimmen: Die Beiträge gehören allen, und jeder kann darüber abstimmen. Es löscht oder versteckt auch nichts vor den übrigen Nutzern.</p>
<p>Du kannst in deinem Profil sehen, wen du geghostet hast, und es jederzeit rückgängig machen.</p>

<h2>Über die Stimmen</h2>
<p>Bei {site} ist eine Stimme endgültig: Einmal abgegeben, kann sie weder geändert noch zurückgenommen werden. In den {gm} Minuten, in denen ein Beitrag oder Kommentar als Ghost erscheint, weiß niemand, wer ihn geschrieben hat; die Stimmen aus dieser Zeit gelten also dem, was gesagt wird, nicht dem, der es sagt. Dass man sie danach nicht ändern kann, sorgt dafür, dass das auch gilt.</p>

<h2>Willst du Miteigentümer von {site} werden?</h2>') WHERE slug = 'que-es.de';

UPDATE pages SET body = REPLACE(body, '<h2>Vuoi essere proprietario di {site}?</h2>',
'<h2>Ghostare un utente</h2>
<p>Se qualcuno ti infastidisce, puoi ghostarlo dal suo profilo o da uno qualsiasi dei suoi commenti. Da quel momento, per lui sarai sempre un Ghost: non vedrà il tuo nome nei tuoi invii, commenti o menzioni, non potrà vedere il tuo profilo né scriverti in privato. E tu lo vedrai allo stesso modo: come un Ghost in più, con i suoi commenti ripiegati, nel caso un giorno tu voglia leggerli.</p>
<p>Nessuno riceve avvisi. Lui continuerà a vedere il sito normalmente, solo che dove prima c''era un nome ci sarà un Ghost, come tanti altri. Non ci sono liste pubbliche di ghostati.</p>
<p>Ghostare non tocca i voti: gli invii sono di tutti e chiunque può votarli. Non cancella né nasconde nulla agli altri utenti.</p>
<p>Puoi vedere chi hai ghostato e annullarlo quando vuoi dal tuo profilo.</p>

<h2>Sui voti</h2>
<p>Su {site} il voto è definitivo: una volta espresso non si può cambiare né ritirare. Nei {gm} minuti in cui un invio o un commento appare come Ghost, nessuno sa chi l''ha scritto, quindi i voti di quel tempo vanno a ciò che si dice, non a chi lo dice. Che non si possano cambiare dopo è ciò che fa sì che questo valga davvero.</p>

<h2>Vuoi essere proprietario di {site}?</h2>') WHERE slug = 'que-es.it';

UPDATE pages SET body = REPLACE(body, '<h2>Quer ser proprietário de {site}?</h2>',
'<h2>Ghostear um utilizador</h2>
<p>Se alguém o incomoda, pode ghosteá-lo a partir do perfil dele ou de qualquer um dos seus comentários. A partir desse momento, para ele será sempre um Ghost: não verá o seu nome nos seus envios, comentários nem menções, não poderá ver o seu perfil nem escrever-lhe em privado. E você vê-lo-á da mesma forma: como mais um Ghost, com os comentários dele dobrados, caso algum dia os queira ler.</p>
<p>Ninguém recebe aviso. Ele continuará a ver o site normalmente, só que onde antes havia um nome haverá um Ghost, como tantos outros. Não há listas públicas de ghosteados.</p>
<p>Ghostear não afeta os votos: os envios são de todos e qualquer pessoa pode votá-los. Também não apaga nem esconde nada aos restantes utilizadores.</p>
<p>Pode ver quem ghosteou e desfazê-lo quando quiser a partir do seu perfil.</p>

<h2>Sobre os votos</h2>
<p>No {site} o voto é definitivo: uma vez dado, não pode ser alterado nem retirado. Durante os {gm} minutos em que um envio ou comentário aparece como Ghost, ninguém sabe quem o escreveu, por isso os votos desse tempo vão para o que se diz, não para quem o diz. Não se poderem mudar depois é o que faz com que isso se cumpra.</p>

<h2>Quer ser proprietário de {site}?</h2>') WHERE slug = 'que-es.pt';
