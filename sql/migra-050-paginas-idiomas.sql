-- Paginas explicativas traducidas: ¿Que es Deltha?, Como funciona el δ, Normas de uso y Privacidad en ca, eu, gl, en, fr, de, it.
-- Cada traduccion es una fila de pages con slug «<pagina>.<idioma>» (que-es.en, normas.fr…); page_get() la sirve segun el idioma
-- de la interfaz y, si no existe, cae en la española. Se editan en admin > Paginas eligiendo el idioma arriba.
-- INSERT IGNORE: no pisa traducciones que ya se hayan retocado a mano. Traducidas del texto de las migraciones 009-049; si la
-- española se habia retocado en admin, revisar las traducciones.
SET NAMES utf8mb4;
INSERT IGNORE INTO pages (slug, title, body) VALUES ('que-es.ca', 'Què és Deltha?', '<h1>Què és Deltha?</h1>

<p>{site} és tres coses alhora: <b>un agregador de notícies</b>, on la gent envia enllaços i decideix amb els seus vots què arriba a portada; <b>una xarxa social contraalgorítmica</b>, sense cap màquina que triï per tu què has de veure; i <b>un espai per al debat</b>, amb fils que neixen, es discuteixen i desapareixen.</p>

<h2>D''on ve</h2>
<p>De cinc llocs. Del <a href="https://contralgoritmia.com/" rel="noopener">manifest contraalgorítmic</a> ve la idea de fons: recuperar el criteri humà davant l''hegemonia de l''algorisme. De <b>Menéame</b>, l''agregador: la comunitat decideix què arriba a portada. De <b>X</b>, el disseny: una línia de temps neta i ràpida. De <b>4chan</b>, l''efimeritat: els fils neixen, es discuteixen i desapareixen. I del <b>model de negoci de Jot Down</b>, la manera de sostenir-se: sense publicitat programàtica ni rastreig, una comunitat que sosté el que llegeix.</p>

<h2>Com funciona</h2>
<p><b>És efímera.</b> Els enviaments i els comentaris s''arxiven al cap de {ttl} hores. El que importa és el que passa ara, no el que s''acumula.</p>
<p><b>No deixa rastre.</b> Sense galetes de rastreig, sense publicitat programàtica, sense tercers. Ho pots comprovar obrint les eines del teu navegador.</p>
<p><b>És horitzontal.</b> Tot es publica sota un àlies i el nom de l''autor només apareix al cap de {gm} minuts, perquè es jutgin les paraules abans que qui les diu.</p>
<p><b>Es veu per dins.</b> La secció Processos mostra en directe la cua, el δ de cada enviament i cada vot, comentari i portada a mesura que passen.</p>
<p><b>Sense algorisme ocult.</b> El que arriba a portada ho decideix una fórmula pública, curta i visible sota cada enviament: el <b>δ</b>. Està explicada sencera a <a href="/algoritmo.php">Com funciona el δ</a>.</p>

<h2>L''aura</h2>
<p>No hi ha karma. Hi ha <b>aura</b>, i mesura una sola cosa: el teu compromís amb la comunitat. Es guanya estant-hi i aportant, no agradant: +{auraport} per cada enviament teu que arriba a portada, +{auraenv} per cada enviament o article, +{auracom} per cada comentari, com a màxim +{auracap} al dia. I baixa: cada dia en perds {auradec}, fins al mínim de {aurastart}. No s''acumula: se sosté. Mesura qui hi és, no qui hi era.</p>
<p><b>Mai no dona altaveu.</b> L''aura no puja notícies ni ordena comentaris. Tothom veu el mateix, en el mateix ordre. Es veu com una llum al voltant del teu àlies, res més. L''únic que obre són portes: amb més de {commaura} pots crear comunitats i editar els comentaris que acabes d''escriure.</p>

<h2>Què s''hi pot publicar?</h2>
<p><b>Tot el que permet la llei.</b> El límit no el posa una línia editorial ni un gust: el posa la llei. En queden fora els insults, els delictes d''odi, les amenaces i tot el que sigui delicte. Estan escrites a les <a href="/normas.php">normes d''ús</a>.</p>
<p><b>Modera una IA que viu als nostres servidors.</b> Cap enviament ni comentari no en surt per ser revisat: no hi ha tercers mirant el que escrius. Quan dubta, decideix una persona. Qualsevol pot denunciar un comentari.</p>
<p><b>I hi ha un correu per a tota la resta.</b> Queixes, felicitacions i propostes: <b>info@deltha.eu</b>.</p>

<h2>Temes i comunitats</h2>
<p><b>Els temes els posa {site}.</b> Pocs i estables: actualitat, ciència, cultura… Cada enviament en porta un, i el tema pot donar una petita empenta al δ (un coeficient públic).</p>
<p><b>Les comunitats les crea la gent.</b> Amb més de {commaura} d''aura en pots obrir fins a {commmax}: un nom, un color i de què va. Un enviament pot sumar-se a una o a diverses, i qui la va crear (o un moderador) en té cura. Cada comunitat pot triar una plantilla pròpia, que s''aplica a la seva pàgina i als seus fils. I tu pots triar una comunitat o un tema com a pàgina d''entrada.</p>

<h2>A la teva manera</h2>
<p><b>{skins} plantilles.</b> La mateixa portada, els mateixos números, una pell diferent: de revista, de fòrum, de terminal, de manga, de fanzín… Es tria a la pestanya Plantilla, i cadascuna té versió clara i fosca.</p>
<p><b>En la teva llengua i a la butxaca.</b> Vuit idiomes: castellà, català, basc, gallec, anglès, francès, alemany i italià. S''instal·la com a aplicació al mòbil, amb missatges privats i xat entre registrats, i pots desar enviaments a favorits. Res d''això no canvia el que veus: només com ho veus.</p>

<h2>Fora dels cercadors</h2>
<p><b>El que passa a {site} es queda a {site}.</b> Cap enviament, comentari ni perfil no apareix a Google ni a altres cercadors: totes les pàgines porten la marca <i>noindex</i>. Només es deixen veure les pàgines que expliquen el projecte, com aquesta.</p>
<p><b>Llevat que tu ho vulguis.</b> Si escrius un article, pots marcar la casella perquè els cercadors l''indexin. És el teu text i hi decideixes tu; es pot canviar en editar-lo.</p>
<p><b>I cap IA no s''entrena amb el que escrius.</b> Els rastrejadors d''intel·ligència artificial (els d''OpenAI, Anthropic, Google, Meta, Common Crawl i companyia) tenen l''entrada vetada, fins i tot als articles oberts als cercadors.</p>

<h2>Vols ser propietari de {site}?</h2>
<p>{site} és un projecte obert a tothom. Per ser-ne copropietari no et demanem diners: només que facis servir la plataforma. <b>El 50 % de la propietat de {site} es repartirà entre tots els usuaris registrats que tinguin aura {auramax} el {auradl}.</b></p>
<p>Vols provar-la? <a href="/registro.php">Registra''t</a> o, si ja tens compte, <a href="/login.php">entra</a>.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('algoritmo.ca', 'Com funciona el δ', '<h1>Com funciona el δ</h1>

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

<h2>El que el δ no fa</h2>
<p>No aprèn de tu. No sap què t''agrada. No et mostra una portada diferent de la del teu veí. No premia qui té més seguidors, perquè aquí ningú no té seguidors. No mira a quina hora acostumes a entrar ni quant de temps passes llegint. La mateixa fórmula, els mateixos números, la mateixa portada per a tothom.</p>

<h2>El que sí que tries tu</h2>
<p>L''única cosa que canvia d''una persona a una altra és <b>com es veu</b>, mai <b>què es veu</b>. Al teu perfil tries la <b>plantilla visual</b> (diverses, de revista a fanzín, cadascuna amb versió clara i fosca), el <b>tema clar o fosc</b>, l''<b>idioma</b> (castellà, català, basc, gallec, anglès, francès, alemany o italià) i la pàgina d''entrada. Una comunitat pot tenir la seva pròpia plantilla, que veuràs a la seva pàgina i als seus fils. {site} s''instal·la com a aplicació al mòbil i té missatges privats entre registrats. Res d''això no altera la portada ni l''ordre de res: la mateixa fórmula, amb una altra pell.</p>

<h2>L''aura</h2>
<p>Les persones no tenen δ; tenen <b>aura</b>, que és una altra cosa i en mesura una de sola: el compromís amb la comunitat. Es guanya estant-hi i aportant (+{auraport} per cada enviament teu que arriba a portada, +{auraenv} per cada enviament o article, +{auracom} per cada comentari, amb un límit de +{auracap} al dia) i es perd no estant-hi ({auradec} cada dia, mai per sota de {aurastart}). El màxim és {auramax}.</p>
<p>L''aura <b>mai no dona altaveu</b>: no puja notícies, no ordena comentaris, no pesa en el δ. Es veu com una llum al voltant del teu àlies, i obre algunes portes de confiança: amb més de {commaura} d''aura pots crear comunitats (fins a {commmax}), i amb més de {editaura} pots corregir els teus propis comentaris durant {editmin} minuts després de publicar-los (el comentari queda marcat com a editat).</p>

<h2>Els números d''avui</h2>
<p>Tots els paràmetres d''aquesta pàgina són els que estan en vigor ara mateix a {site}; si l''equip els canvia, aquesta pàgina canvia amb ells. Cicle cada {cycle} minuts · {minage} minuts mínims a Noves · δ mínim per a portada {minpub} · decaïment {decay} per mitja hora · vel de {gm} minuts · δ mínim per ser candidata {mincand} · vida a portada de {ttl} hores · caducitat sense portada al cap de {newttl} hores · comunitats amb aura superior a {commaura}, fins a {commmax} per persona.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('normas.ca', 'Normes d''ús', '<h1>Normes d''ús</h1>

<h2>La regla</h2>
<p>A <b>{site}</b> hi cap tot el que permet la llei. No hi ha línia editorial ni llista de temes prohibits: es pot discrepar, criticar amb duresa, ironitzar i equivocar-se. L''únic límit és el que marca la legislació espanyola i europea, i per això aquestes normes són curtes.</p>

<h2>El que no hi cap</h2>
<p><b>Insultar i vexar.</b> Es discuteix amb les idees, no contra les persones. Els insults, les humiliacions i les desqualificacions que no aporten cap argument es retiren.</p>
<p><b>Odiar.</b> Res que inciti a l''odi, la discriminació o la violència contra algú pel seu origen, sexe, orientació, religió, discapacitat o qualsevol altra condició.</p>
<p><b>Amenaçar i assetjar.</b> Ni amenaces, ni perseguir un usuari de fil en fil, ni campanyes per assenyalar ningú.</p>
<p><b>Publicar dades d''altres persones.</b> Adreces, telèfons, documents, comptes privats, fotos íntimes, llocs de treball o qualsevol dada que serveixi per localitzar o identificar una persona que no l''ha feta pública. Això es diu <i>doxxing</i> i és el més greu que es pot fer a {site}.</p>
<p><b>Difondre contingut il·legal.</b> Material que exploti menors, apologia del terrorisme, incitació a delictes, contingut que vulneri drets d''autor de manera evident.</p>
<p><b>Suplantar.</b> Fer-se passar per una altra persona, mitjà o institució.</p>
<p><b>Fer trampes.</b> Comptes múltiples per votar, publicitat encoberta, enllaços enganyosos i qualsevol manera de manipular la portada.</p>

<h2>Com es modera</h2>
<p>Qualsevol pot <b>denunciar</b> un comentari des del mateix comentari, sense necessitat de tenir compte. Les denúncies les revisen persones: moderadors voluntaris de la comunitat i l''equip de {site}. A més, una IA que viu als nostres servidors (cap text no en surt) fa un primer filtre quan està activada.</p>
<p>Quan un comentari incompleix aquestes normes, no desapareix sense més ni més: el seu text se substitueix per <i>«Comentari eliminat per incomplir les normes d''ús»</i>, de manera que la conversa s''entén i queda constància que allà hi havia alguna cosa que no hi havia de ser. Si un fil sencer és il·legal, es retira.</p>
<p>La moderació s''equivoca de vegades. Si creus que s''ha retirat alguna cosa teva sense motiu, escriu a <a href="mailto:info@deltha.eu">info@deltha.eu</a> i ho revisarem.</p>

<h2>Conseqüències</h2>
<p>La primera vegada, es retira el comentari. Si es repeteix, el compte pot quedar suspès. Els casos que puguin ser delicte es conserven i es posen a disposició de les autoritats si ho requereixen, tal com obliga la llei.</p>

<h2>I la resta</h2>
<p>Tot el que es publica aquí és efímer: els fils s''arxiven al cap de {ttl} hores. Tingues cura del que escrius durant aquestes hores com si hagués de durar sempre, perquè, per a qui ho llegeix, dura sempre.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('privacidad.ca', 'Privacitat i contacte', '<h1>Privacitat i contacte</h1>

<h2>Qui hi ha al darrere</h2>
<p><b>{site}</b> és un servei de <b>Jot Down Media Company S.L.</b>, NIF B88863592, amb domicili a Calle Palillos, núm. 6, porta 36, 41500 Alcalá de Guadaíra (Sevilla), Espanya. És la responsable del tractament de les dades que es descriuen aquí.</p>
<p>Per a qualsevol assumpte relacionat amb les teves dades, i també per a queixes, felicitacions i propostes, escriu a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>El que no fem</h2>
<p>No fem servir galetes amb qui no inicia sessió. No hi ha rastrejadors, ni analítica de tercers, ni publicitat programàtica, ni recursos carregats des d''altres dominis: tot el que mostra aquest web surt dels nostres propis servidors. Ho pots comprovar amb les eines de desenvolupament del teu navegador. No venem ni cedim dades a ningú.</p>

<h2>Quines dades tractem i per a què</h2>
<p><b>Si només llegeixes</b>, no tractem cap dada teva més enllà dels registres tècnics del servidor (adreça IP, data, pàgina sol·licitada i navegador), que existeixen en qualsevol servidor web, serveixen per mantenir el servei i detectar abusos, i s''esborren en un termini breu. Per saber quantes persones hi ha al lloc en cada moment, desem durant uns minuts una empremta no reversible de la teva visita (calculada a partir de la IP, el navegador i el dia, i que no permet recuperar-los); s''esborra sola al cap de deu minuts i no es fa servir per a res més.</p>
<p><b>Si comentes sense compte</b>, el teu comentari es publica sota un àlies generat a partir d''una empremta no reversible de la teva adreça IP, el fil i un secret del servidor. Desem aquesta empremta truncada (perquè el teu àlies sigui el mateix dins d''un fil), mai l''adreça IP.</p>
<p><b>Si crees un compte</b>, desem el teu nom d''usuari, el teu correu electrònic i la teva contrasenya xifrada de manera irreversible. El correu serveix únicament per gestionar el teu compte; no enviem butlletins ni publicitat. Pots afegir una frase i una foto de perfil; a les fotos els eliminem les metadades (EXIF) quan les puges. Amb compte es crea una única galeta tècnica de sessió, imprescindible per saber que ets tu, que desapareix quan surts. Desem també l''hora de la teva última visita, que només serveix per comptar quantes persones hi ha connectades en cada moment.</p>
<p><b>Si envies una notícia</b>, es publiquen l''URL, el titular, l''entradeta i la foto que aportis. Les fotos es retallen i se n''eliminen les metadades al servidor.</p>
<p><b>Mentre l''accés sigui privat</b>, qui entri amb la clau d''accés rep una galeta tècnica que només conté una empremta d''aquesta clau. Deixarà d''existir quan obrim el lloc.</p>

<h2>Quant de temps</h2>
<p>{site} és efímera per disseny. Els enviaments i els seus comentaris s''arxiven al cap de {ttl} hores d''arribar a portada. El teu nom d''usuari apareix al costat de cada aportació al cap de {gm} minuts de publicar-la; fins llavors només es veu l''àlies. Les dades del teu compte es conserven mentre el mantinguis obert.</p>

<h2>Moderació</h2>
<p>La moderació la fa una intel·ligència artificial que s''executa als nostres propis servidors: cap enviament ni comentari no en surt per ser revisat per tercers. Només es filtra el que la llei no permet (insults, delictes d''odi, amenaces i, en general, qualsevol conducta delictiva). Quan el sistema dubta, decideix una persona. Si creus que una decisió ha estat injusta, escriu-nos.</p>

<h2>Els teus drets</h2>
<p>Pots accedir a les teves dades, rectificar-les, demanar que les esborrem, oposar-te al seu tractament, limitar-lo o sol·licitar-ne la portabilitat, d''acord amb el Reglament (UE) 2016/679 i la Llei orgànica 3/2018. N''hi ha prou d''escriure a <a href="mailto:info@deltha.eu">info@deltha.eu</a> des del correu amb què et vas registrar. Si no hi estàs conforme, pots presentar una reclamació davant l''Agència Espanyola de Protecció de Dades (aepd.es).</p>
<p>La base jurídica del tractament és l''execució del servei que sol·licites quan et registres o hi participes, i el nostre interès legítim a mantenir el lloc segur i dins de la llei.</p>

<h2>Canvis</h2>
<p>Si canviem aquesta política, ho direm aquí. Darrera actualització: setembre de 2026.</p>
<p><i>Aquesta és una traducció oferta per comoditat. En cas de discrepància, preval la versió en castellà.</i></p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('que-es.eu', 'Zer da Deltha?', '<h1>Zer da Deltha?</h1>

<p>{site} hiru gauza da aldi berean: <b>albiste-agregatzaile bat</b>, non jendeak estekak bidaltzen dituen eta botoekin erabakitzen duen zer iristen den azalera; <b>sare sozial kontralgoritmiko bat</b>, zer ikusi behar duzun zure ordez aukeratzen duen makinarik gabe; eta <b>eztabaidarako gune bat</b>, sortu, eztabaidatu eta desagertu egiten diren hariekin.</p>

<h2>Nondik dator</h2>
<p>Bost gunetatik. <a href="https://contralgoritmia.com/" rel="noopener">Manifestu kontralgoritmikotik</a> dator funtsezko ideia: giza irizpidea berreskuratzea algoritmoaren hegemoniaren aurrean. <b>Menéame</b>tik, agregatzailea: komunitateak erabakitzen du zer iristen den azalera. <b>X</b> saretik, diseinua: denbora-lerro garbi eta azkarra. <b>4chan</b>etik, iragankortasuna: hariak sortu, eztabaidatu eta desagertu egiten dira. Eta <b>Jot Downen negozio-eredutik</b>, bizirauteko modua: publizitate programatikorik eta jarraipenik gabe, irakurtzen duenari eusten dion komunitate bat.</p>

<h2>Nola funtzionatzen du</h2>
<p><b>Iragankorra da.</b> Bidalketak eta iruzkinak {ttl} orduren buruan artxibatzen dira. Orain gertatzen dena da garrantzitsua, ez metatzen dena.</p>
<p><b>Ez du arrastorik uzten.</b> Jarraipen-cookierik gabe, publizitate programatikorik gabe, hirugarrenik gabe. Zeuk egiazta dezakezu, nabigatzailearen tresnak irekita.</p>
<p><b>Horizontala da.</b> Dena ezizen batekin argitaratzen da, eta egilearen izena {gm} minuturen buruan baino ez da agertzen: lehenik hitzak epaitzen dira, gero esaten dituena.</p>
<p><b>Barrutik ikusten da.</b> Prozesuak atalak zuzenean erakusten ditu ilara, bidalketa bakoitzaren δ-a eta boto, iruzkin eta azal-igoera bakoitza, gertatu ahala.</p>
<p><b>Ezkutuko algoritmorik gabe.</b> Azalera zer iristen den formula publiko, labur eta ikusgai batek erabakitzen du, bidalketa bakoitzaren azpian: <b>δ</b>-k. Osorik azalduta dago hemen: <a href="/algoritmo.php">Nola funtzionatzen duen δ-k</a>.</p>

<h2>Aura</h2>
<p>Ez dago karmarik. <b>Aura</b> dago, eta gauza bakarra neurtzen du: komunitatearekiko zure konpromisoa. Egonez eta ekarriz irabazten da, ez gustatuz: +{auraport} azalera iristen den zure bidalketa bakoitzeko, +{auraenv} bidalketa edo artikulu bakoitzeko, +{auracom} iruzkin bakoitzeko, gehienez +{auracap} egunean. Eta jaitsi egiten da: egunero {auradec} galtzen duzu, {aurastart} gutxienekora iritsi arte. Ez da metatzen: eutsi egiten zaio. Nor dagoen neurtzen du, ez nor egon zen.</p>
<p><b>Ez dizu inoiz bozgorailurik ematen.</b> Aurak ez ditu albisteak igotzen ez iruzkinak ordenatzen. Denek gauza bera ikusten dute, ordena berean. Zure ezizenaren inguruko argi gisa ikusten da, besterik ez. Ateak baino ez ditu irekitzen: {commaura} baino gehiagorekin komunitateak sor ditzakezu eta idatzi berri dituzun iruzkinak edita ditzakezu.</p>

<h2>Zer argitara daiteke?</h2>
<p><b>Legeak baimentzen duen guztia.</b> Muga ez du lerro editorial batek edo gustu batek jartzen: legeak jartzen du. Kanpoan geratzen dira irainak, gorroto-delituak, mehatxuak eta delitu den guztia. <a href="/normas.php">Erabilera-arauetan</a> daude idatzita.</p>
<p><b>Gure zerbitzarietan bizi den AA batek moderatzen du.</b> Bidalketa eta iruzkin bat ere ez da haietatik ateratzen berrikusteko: ez dago hirugarrenik idazten duzuna begira. Zalantza duenean, pertsona batek erabakitzen du. Edonork sala dezake iruzkin bat.</p>
<p><b>Eta gainerako guztirako, helbide elektroniko bat.</b> Kexak, zorionak eta proposamenak: <b>info@deltha.eu</b>.</p>

<h2>Gaiak eta komunitateak</h2>
<p><b>Gaiak {site}-k jartzen ditu.</b> Gutxi eta egonkorrak: gaurkotasuna, zientzia, kultura… Bidalketa bakoitzak bat darama, eta gaiak bultzadatxo bat eman diezaioke δ-ari (koefiziente publiko bat).</p>
<p><b>Komunitateak jendeak sortzen ditu.</b> {commaura} aura baino gehiagorekin {commmax} arte ireki ditzakezu: izen bat, kolore bat eta zertaz doan. Bidalketa bat komunitate batera edo gehiagotara batu daiteke, eta sortu zuenak (edo moderatzaile batek) zaintzen du. Komunitate bakoitzak bere txantiloia aukera dezake, bere orrian eta harietan aplikatzen dena. Eta zuk komunitate bat edo gai bat aukera dezakezu sarrera-orri gisa.</p>

<h2>Zure erara</h2>
<p><b>{skins} txantiloi.</b> Azal bera, zenbaki berak, itxura desberdina: aldizkari, foro, terminal, manga, fanzine… itxurakoak. Txantiloia fitxan aukeratzen da, eta bakoitzak bertsio argia eta iluna ditu.</p>
<p><b>Zure hizkuntzan eta zure poltsikoan.</b> Zortzi hizkuntza: gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana eta italiera. Mugikorrean aplikazio gisa instalatzen da, erregistratuen arteko mezu pribatuekin eta txatarekin, eta bidalketak gogokoetan gorde ditzakezu. Horietako ezerk ez du aldatzen zer ikusten duzun: nola ikusten duzun baino ez.</p>

<h2>Bilatzaileetatik kanpo</h2>
<p><b>{site}-n gertatzen dena {site}-n geratzen da.</b> Bidalketa, iruzkin eta profil bat ere ez da agertzen Googlen ez beste bilatzaileetan: orri guztiek <i>noindex</i> marka daramate. Proiektua azaltzen duten orriak baino ez dira ikusgai, hau bezala.</p>
<p><b>Zuk nahi ezean.</b> Artikulu bat idazten baduzu, laukia marka dezakezu bilatzaileek indexa dezaten. Zure testua da eta zuk erabakitzen duzu; editatzean alda daiteke.</p>
<p><b>Eta AArik ez da entrenatzen idazten duzunarekin.</b> Adimen artifizialeko arakatzaileek (OpenAI, Anthropic, Google, Meta, Common Crawl eta abarrenek) sarrera debekatuta dute, baita bilatzaileentzat irekitako artikuluetan ere.</p>

<h2>{site}-ren jabe izan nahi duzu?</h2>
<p>{site} mundu guztiari irekitako proiektua da. Jabekide izateko ez dizugu dirurik eskatzen: plataforma erabiltzea baino ez. <b>{site}-ren jabetzaren % 50 {auradl} egunean {auramax} aura duten erabiltzaile erregistratu guztien artean banatuko da.</b></p>
<p>Probatu nahi duzu? <a href="/registro.php">Erregistratu</a> edo, kontua baduzu jada, <a href="/login.php">sartu</a>.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('algoritmo.eu', 'Nola funtzionatzen duen δ-k', '<h1>Nola funtzionatzen duen δ-k</h1>

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

<h2>δ-k egiten ez duena</h2>
<p>Ez du zugandik ikasten. Ez daki zer gustatzen zaizun. Ez dizu zure auzokoari baino azal desberdinik erakusten. Ez du saritzen jarraitzaile gehien dituena, hemen inork ez baitu jarraitzailerik. Ez dio begiratzen zein ordutan sartu ohi zaren, ezta zenbat denbora ematen duzun irakurtzen ere. Formula bera, zenbaki berak, azal bera denentzat.</p>

<h2>Zuk aukeratzen duzuna</h2>
<p>Pertsona batetik bestera aldatzen den gauza bakarra <b>nola ikusten den</b> da, inoiz ez <b>zer ikusten den</b>. Zure profilean aukeratzen dituzu <b>txantiloi bisuala</b> (hainbat, aldizkaritik fanzinera, bakoitza bertsio argi eta ilunarekin), <b>gai argia edo iluna</b>, <b>hizkuntza</b> (gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana edo italiera) eta sarrera-orria. Komunitate batek bere txantiloia izan dezake, bere orrian eta harietan ikusiko duzuna. {site} mugikorrean aplikazio gisa instalatzen da, eta erregistratuen arteko mezu pribatuak ditu. Horietako ezerk ez du aldatzen azala, ezta ezeren ordena ere: formula bera, beste itxura batekin.</p>

<h2>Aura</h2>
<p>Pertsonek ez dute δ-rik; <b>aura</b> dute, beste gauza bat dena eta gauza bakarra neurtzen duena: komunitatearekiko konpromisoa. Egonez eta ekarriz irabazten da (+{auraport} azalera iristen den zure bidalketa bakoitzeko, +{auraenv} bidalketa edo artikulu bakoitzeko, +{auracom} iruzkin bakoitzeko, egunean gehienez +{auracap}) eta ez egonez galtzen da (egunero {auradec}, inoiz ez {aurastart}-tik behera). Gehienezkoa {auramax} da.</p>
<p>Aurak <b>ez dizu inoiz bozgorailurik ematen</b>: ez ditu albisteak igotzen, ez ditu iruzkinak ordenatzen, ez du eraginik δ-n. Zure ezizenaren inguruko argi gisa ikusten da, eta konfiantzazko ate batzuk irekitzen ditu: {commaura} aura baino gehiagorekin komunitateak sor ditzakezu ({commmax} arte), eta {editaura} baino gehiagorekin zure iruzkinak zuzen ditzakezu argitaratu eta hurrengo {editmin} minutuetan (iruzkina editatu gisa markatuta geratzen da).</p>

<h2>Gaurko zenbakiak</h2>
<p>Orri honetako parametro guztiak {site}-n une honetan indarrean daudenak dira; taldeak aldatzen baditu, orri hau ere haiekin batera aldatzen da. Zikloa {cycle} minuturo · gutxienez {minage} minutu Berriak atalean · azalerako gutxieneko δ: {minpub} · beherakada: {decay} ordu erdiko · {gm} minutuko beloa · hautagai izateko gutxieneko δ: {mincand} · {ttl} orduko bizitza azalean · azalera iritsi gabe {newttl} ordura iraungitzen da · komunitateak {commaura} baino aura handiagoarekin, gehienez {commmax} pertsonako.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('normas.eu', 'Erabilera-arauak', '<h1>Erabilera-arauak</h1>

<h2>Araua</h2>
<p><b>{site}</b>-n legeak baimentzen duen guztiak du lekua. Ez dago lerro editorialik ez debekatutako gaien zerrendarik: ados ez egon daiteke, gogor kritikatu, ironiaz jardun eta oker ibili. Muga bakarra Espainiako eta Europako legediak jartzen duena da, eta horregatik dira laburrak arau hauek.</p>

<h2>Lekurik ez duena</h2>
<p><b>Iraindu eta umiliatu.</b> Ideiekin eztabaidatzen da, ez pertsonen aurka. Irainak, umiliazioak eta argudiorik ekartzen ez duten gutxiespenak kendu egiten dira.</p>
<p><b>Gorrotoa zabaldu.</b> Ezer ez, norbaiten aurkako gorrotoa, diskriminazioa edo indarkeria bultzatzen duenik, bere jatorri, sexu, orientazio, erlijio, desgaitasun edo beste edozein ezaugarrirengatik.</p>
<p><b>Mehatxatu eta jazarri.</b> Ez mehatxurik, ez erabiltzaile bat haririk hari jazartzerik, ez inor seinalatzeko kanpainarik.</p>
<p><b>Besteen datuak argitaratu.</b> Helbideak, telefonoak, agiriak, kontu pribatuak, argazki intimoak, lantokiak edo, publiko egin ez duen pertsona bat kokatzeko edo identifikatzeko balio duen edozein datu. Hemen <i>doxxing</i> deitzen zaio, eta {site}-n egin daitekeen gauzarik larriena da.</p>
<p><b>Legez kanpoko edukia zabaldu.</b> Adingabeak esplotatzen dituen materiala, terrorismoaren apologia, delituak egitera bultzatzea, egile-eskubideak argi eta garbi urratzen dituen edukia.</p>
<p><b>Beste norbaiten itxura hartu.</b> Beste pertsona, hedabide edo erakunde bat zarela itxuratzea.</p>
<p><b>Tranpak egin.</b> Bozkatzeko kontu anitzak, ezkutuko publizitatea, esteka engainagarriak eta azala manipulatzeko edozein modu.</p>

<h2>Nola moderatzen den</h2>
<p>Edonork <b>sala</b> dezake iruzkin bat iruzkinetik bertatik, konturik izan beharrik gabe. Salaketak pertsonek berrikusten dituzte: komunitateko moderatzaile boluntarioek eta {site}-ren taldeak. Gainera, gure zerbitzarietan bizi den AA batek (testu bat ere ez da haietatik ateratzen) lehen iragazketa egiten du aktibatuta dagoenean.</p>
<p>Iruzkin batek arau hauek betetzen ez dituenean, ez da besterik gabe desagertzen: haren testuaren ordez <i>«Erabilera-arauak hausteagatik ezabatutako iruzkina»</i> jartzen da; horrela, elkarrizketa ulertzen da, eta jasota geratzen da han egon behar ez zuen zerbait egon zela. Hari oso bat legez kanpokoa bada, kendu egiten da.</p>
<p>Moderazioak batzuetan huts egiten du. Zure zerbait arrazoirik gabe kendu dela uste baduzu, idatzi <a href="mailto:info@deltha.eu">info@deltha.eu</a> helbidera eta berrikusiko dugu.</p>

<h2>Ondorioak</h2>
<p>Lehen aldian, iruzkina kentzen da. Errepikatzen bada, kontua eten daiteke. Delitu izan daitezkeen kasuak gorde egiten dira eta agintarien esku jartzen dira hala eskatzen badute, legeak agintzen duen bezala.</p>

<h2>Eta gainerakoa</h2>
<p>Hemen argitaratzen den guztia iragankorra da: hariak {ttl} orduren buruan artxibatzen dira. Zaindu ordu horietan idazten duzuna betiko iraungo balu bezala, irakurtzen duenarentzat hala baita.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('privacidad.eu', 'Pribatutasuna eta harremana', '<h1>Pribatutasuna eta harremana</h1>

<h2>Nor dago atzean</h2>
<p><b>{site}</b> <b>Jot Down Media Company S.L.</b> enpresaren zerbitzu bat da (NIF B88863592), helbidea: Calle Palillos, 6. zk., 36. atea, 41500 Alcalá de Guadaíra (Sevilla), Espainia. Hemen deskribatzen diren datuen tratamenduaren arduraduna da.</p>
<p>Zure datuekin lotutako edozein gairako, baita kexa, zorion eta proposamenetarako ere, idatzi <a href="mailto:info@deltha.eu">info@deltha.eu</a> helbidera.</p>

<h2>Egiten ez duguna</h2>
<p>Ez dugu cookierik erabiltzen saioa hasten ez duenarekin. Ez dago jarraipen-tresnarik, ez hirugarrenen analitikarik, ez publizitate programatikorik, ez beste domeinu batzuetatik kargatutako baliabiderik: webgune honek erakusten duen guztia gure zerbitzarietatik dator. Zure nabigatzailearen garatzaile-tresnekin egiazta dezakezu. Ez diogu inori daturik saltzen ez lagatzen.</p>

<h2>Zer datu tratatzen ditugun eta zertarako</h2>
<p><b>Irakurri besterik egiten ez baduzu</b>, ez dugu zure daturik tratatzen zerbitzariaren erregistro teknikoez harago (IP helbidea, data, eskatutako orria eta nabigatzailea); edozein web-zerbitzaritan daude, zerbitzuari eusteko eta abusuak detektatzeko balio dute, eta epe laburrean ezabatzen dira. Une bakoitzean gunean zenbat pertsona dauden jakiteko, zure bisitaren aztarna itzulezin bat gordetzen dugu minutu batzuez (IPtik, nabigatzailetik eta egunetik kalkulatua, eta horiek berreskuratzeko aukerarik ematen ez duena); hamar minuturen buruan bere kasa ezabatzen da, eta ez da beste ezertarako erabiltzen.</p>
<p><b>Konturik gabe iruzkintzen baduzu</b>, zure iruzkina ezizen batekin argitaratzen da, zure IP helbidearen, hariaren eta zerbitzariaren sekretu baten aztarna itzulezin batetik sortua. Aztarna hori moztuta gordetzen dugu (zure ezizena hari batean berbera izan dadin), inoiz ez IP helbidea.</p>
<p><b>Kontu bat sortzen baduzu</b>, zure erabiltzaile-izena, helbide elektronikoa eta modu itzulezinean zifratutako pasahitza gordetzen ditugu. Helbide elektronikoa zure kontua kudeatzeko baino ez da erabiltzen; ez dugu buletinik ez publizitaterik bidaltzen. Esaldi bat eta profileko argazki bat gehi ditzakezu; argazkiak igotzean, metadatuak (EXIF) kentzen dizkiegu. Kontuarekin saio-cookie tekniko bakar bat sortzen da, zu zarela jakiteko ezinbestekoa, eta saiotik irtetean desagertzen da. Zure azken bisitaren ordua ere gordetzen dugu, une bakoitzean zenbat pertsona dauden konektatuta zenbatzeko baino balio ez duena.</p>
<p><b>Albiste bat bidaltzen baduzu</b>, ematen dituzun URLa, titularra, sarrera-testua eta argazkia argitaratu egiten dira. Argazkiak zerbitzarian mozten dira eta metadatuak kentzen zaizkie.</p>
<p><b>Sarbidea pribatua den bitartean</b>, sarbide-gakoarekin sartzen denak cookie tekniko bat jasotzen du, gako horren aztarna bat besterik ez duena. Gunea irekitzen dugunean desagertuko da.</p>

<h2>Zenbat denboraz</h2>
<p>{site} iragankorra da diseinuz. Bidalketak eta haien iruzkinak azalera iritsi eta {ttl} orduren buruan artxibatzen dira. Zure erabiltzaile-izena ekarpen bakoitzaren ondoan agertzen da hura argitaratu eta {gm} minutura; ordura arte ezizena baino ez da ikusten. Zure kontuaren datuak kontua irekita duzun bitartean gordetzen dira.</p>

<h2>Moderazioa</h2>
<p>Moderazioa gure zerbitzarietan exekutatzen den adimen artifizial batek egiten du: bidalketa eta iruzkin bat ere ez da haietatik ateratzen hirugarrenek berrikus dezaten. Legeak baimentzen ez duena baino ez da iragazten (irainak, gorroto-delituak, mehatxuak eta, oro har, delitu den edozein jokabide). Sistemak zalantza duenean, pertsona batek erabakitzen du. Erabaki bat bidegabea izan dela uste baduzu, idatzi iezaguzu.</p>

<h2>Zure eskubideak</h2>
<p>Zure datuak eskuratu eta zuzendu ditzakezu, ezabatzeko eskatu, haien tratamenduari aurka egin, hura mugatu edo datuen eramangarritasuna eskatu, (EB) 2016/679 Erregelamenduaren eta 3/2018 Lege Organikoaren arabera. Nahikoa da <a href="mailto:info@deltha.eu">info@deltha.eu</a> helbidera idaztea, erregistratzeko erabili zenuen helbide elektronikotik. Ados ez bazaude, erreklamazioa aurkez dezakezu Datuak Babesteko Espainiako Agentzian (aepd.es).</p>
<p>Tratamenduaren oinarri juridikoa zera da: erregistratzean edo parte hartzean eskatzen duzun zerbitzua ematea, eta gunea seguru eta legearen barruan mantentzeko dugun interes legitimoa.</p>

<h2>Aldaketak</h2>
<p>Politika hau aldatzen badugu, hemen esango dugu. Azken eguneratzea: 2026ko iraila.</p>
<p><i>Itzulpen hau erosotasunerako eskaintzen da. Desadostasunik izanez gero, gaztelaniazko bertsioak du lehentasuna.</i></p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('que-es.gl', 'Que é Deltha?', '<h1>Que é Deltha?</h1>

<p>{site} é tres cousas á vez: <b>un agregador de novas</b>, onde a xente envía ligazóns e decide cos seus votos que chega á portada; <b>unha rede social contralgorítmica</b>, sen máquina que escolla por ti que ver; e <b>un espazo para o debate</b>, con fíos que nacen, se discuten e desaparecen.</p>

<h2>De onde vén</h2>
<p>De cinco sitios. Do <a href="https://contralgoritmia.com/" rel="noopener">manifesto contralgorítmico</a> vén a idea de fondo: recuperar o criterio humano fronte á hexemonía do algoritmo. De <b>Menéame</b>, o agregador: a comunidade decide que chega á portada. De <b>X</b>, o deseño: unha liña de tempo limpa e rápida. De <b>4chan</b>, a efemeridade: os fíos nacen, discútense e desaparecen. E do <b>modelo de negocio de Jot Down</b>, a maneira de soster o proxecto: sen publicidade programática nin rastrexo, unha comunidade que sostén o que le.</p>

<h2>Como funciona</h2>
<p><b>É efémera.</b> Os envíos e os comentarios arquívanse ás {ttl} horas. O que importa é o que pasa agora, non o que se acumula.</p>
<p><b>Non deixa pegada.</b> Sen cookies de rastrexo, sen publicidade programática, sen terceiros. Podes comprobalo abrindo as ferramentas do teu navegador.</p>
<p><b>É horizontal.</b> Todo se publica baixo un alias e o nome de quen escribe só aparece aos {gm} minutos, para que se xulguen as palabras antes ca quen as di.</p>
<p><b>Vese por dentro.</b> A sección Procesos amosa en directo a cola, o δ de cada envío e cada voto, comentario e portada segundo ocorren.</p>
<p><b>Sen algoritmo oculto.</b> O que chega á portada decídeo unha fórmula pública, curta e visible baixo cada envío: o <b>δ</b>. Está explicada enteira en <a href="/algoritmo.php">Como funciona o δ</a>.</p>

<h2>A aura</h2>
<p>Non hai karma. Hai <b>aura</b>, e mide unha soa cousa: o teu compromiso coa comunidade. Gáñase estando e achegando, non gustando: +{auraport} por cada envío teu que chega á portada, +{auraenv} por cada envío ou artigo, +{auracom} por cada comentario, como moito +{auracap} ao día. E baixa: cada día perdes {auradec}, ata o mínimo de {aurastart}. Non se acumula: sostense. Mide quen está, non quen estivo.</p>
<p><b>Nunca dá altofalante.</b> A aura non sobe novas nin ordena comentarios. Todo o mundo ve o mesmo, na mesma orde. Vese como luz arredor do teu alias, nada máis. O único que abre son portas: con máis de {commaura} podes crear comunidades e editar os comentarios que acabas de escribir.</p>

<h2>Que se pode publicar?</h2>
<p><b>Todo o que permite a lei.</b> O límite non o pon unha liña editorial nin un gusto: pono a lei. Fóra quedan os insultos, os delitos de odio, as ameazas e todo o que sexa delito. Están escritas nas <a href="/normas.php">normas de uso</a>.</p>
<p><b>Modera unha IA que vive nos nosos servidores.</b> Ningún envío nin comentario sae deles para ser revisado: non hai terceiros mirando o que escribes. Cando dubida, decide unha persoa. Calquera pode denunciar un comentario.</p>
<p><b>E hai un correo para todo o demais.</b> Queixas, parabéns e propostas: <b>info@deltha.eu</b>.</p>

<h2>Temas e comunidades</h2>
<p><b>Os temas ponos {site}.</b> Poucos e estables: actualidade, ciencia, cultura… Cada envío leva un, e o tema pode darlle un pequeno empurrón ao δ (un coeficiente público).</p>
<p><b>As comunidades créaas a xente.</b> Con máis de {commaura} de aura podes abrir ata {commmax}: un nome, unha cor e de que vai. Un envío pode sumarse a unha ou a varias, e quen a creou (ou un moderador) cóidaa. Cada comunidade pode escoller un modelo propio, que se aplica á súa páxina e aos seus fíos. E ti podes escoller unha comunidade ou un tema como páxina de entrada.</p>

<h2>Ao teu xeito</h2>
<p><b>{skins} modelos.</b> A mesma portada, os mesmos números, distinta pel: de revista, de foro, de terminal, de manga, de fanzine… Escóllese na lapela Modelo, e cada un ten versión clara e escura.</p>
<p><b>No teu idioma e no teu peto.</b> Oito idiomas: español, catalán, éuscaro, galego, inglés, francés, alemán e italiano. Instálase como app no móbil, con mensaxes privadas e chat entre rexistrados, e podes gardar envíos en favoritos. Nada disto cambia o que ves: só como o ves.</p>

<h2>Fóra dos buscadores</h2>
<p><b>O que pasa en {site} queda en {site}.</b> Ningún envío, comentario nin perfil aparece en Google nin noutros buscadores: todas as páxinas levan a marca <i>noindex</i>. Só se deixan ver as páxinas que explican o proxecto, coma esta.</p>
<p><b>Agás que ti o queiras.</b> Se escribes un artigo, podes marcar a caixa para que os buscadores o indexen. É o teu texto e decides ti; pódese cambiar ao editalo.</p>
<p><b>E ningunha IA se adestra co que escribes.</b> Os rastrexadores de intelixencia artificial (os de OpenAI, Anthropic, Google, Meta, Common Crawl e compañía) teñen a entrada vetada, mesmo nos artigos abertos aos buscadores.</p>

<h2>Queres ser propietario de {site}?</h2>
<p>{site} é un proxecto aberto a todo o mundo. Para ser copropietario non che pedimos cartos: só que uses a plataforma. <b>O 50 % da propiedade de {site} repartirase entre todos os usuarios rexistrados que teñan aura {auramax} o {auradl}.</b></p>
<p>Queres probala? <a href="/registro.php">Rexístrate</a> ou, se xa tes conta, <a href="/login.php">entra</a>.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('algoritmo.gl', 'Como funciona o δ', '<h1>Como funciona o δ</h1>

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

<h2>O que o δ non fai</h2>
<p>Non aprende de ti. Non sabe que che gusta. Non che amosa unha portada distinta da do teu veciño. Non premia a quen ten máis seguidores, porque aquí ninguén ten seguidores. Non mira a hora á que adoitas entrar nin canto tempo pasas lendo. A mesma fórmula, os mesmos números, a mesma portada para todos.</p>

<h2>O que si escolles ti</h2>
<p>O único que cambia dunha persoa a outra é <b>como se ve</b>, nunca <b>que se ve</b>. No teu perfil escolles o <b>modelo visual</b> (varios, de revista a fanzine, cada un con versión clara e escura), o <b>tema claro ou escuro</b>, o <b>idioma</b> (español, catalán, éuscaro, galego, inglés, francés, alemán ou italiano) e a páxina de entrada. Unha comunidade pode ter o seu propio modelo, que verás na súa páxina e nos seus fíos. {site} instálase como aplicación no móbil e ten mensaxes privadas entre rexistrados. Nada disto altera a portada nin a orde de nada: a mesma fórmula, con outra pel.</p>

<h2>A aura</h2>
<p>As persoas non teñen δ; teñen <b>aura</b>, que é outra cousa e mide unha soa: o compromiso coa comunidade. Gáñase estando e achegando (+{auraport} por cada envío teu que chega á portada, +{auraenv} por cada envío ou artigo, +{auracom} por cada comentario, cun teito de +{auracap} ao día) e pérdese non estando ({auradec} cada día, nunca por debaixo de {aurastart}). O máximo é {auramax}.</p>
<p>A aura <b>nunca dá altofalante</b>: non sobe novas, non ordena comentarios, non pesa no δ. Vese como luz arredor do teu alias, e abre algunhas portas de confianza: con máis de {commaura} de aura podes crear comunidades (ata {commmax}), e con máis de {editaura} podes corrixir os teus propios comentarios durante {editmin} minutos despois de publicalos (o comentario queda marcado como editado).</p>

<h2>Os números de hoxe</h2>
<p>Todos os parámetros desta páxina son os que están en vigor agora mesmo en {site}; se o equipo os cambia, esta páxina cambia con eles. Ciclo cada {cycle} minutos · {minage} minutos mínimos en Novas · δ mínimo para portada {minpub} · decaemento {decay} por media hora · veo de {gm} minutos · δ mínimo para ser candidata {mincand} · vida na portada de {ttl} horas · caducidade sen portada ás {newttl} horas · comunidades con aura maior que {commaura}, ata {commmax} por persoa.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('normas.gl', 'Normas de uso', '<h1>Normas de uso</h1>

<h2>A regra</h2>
<p>En <b>{site}</b> cabe todo o que permite a lei. Non hai liña editorial nin lista de temas prohibidos: pódese discrepar, criticar con dureza, ironizar e equivocarse. O único límite é o que marca a lexislación española e europea, e por iso estas normas son curtas.</p>

<h2>O que non cabe</h2>
<p><b>Insultar e aldraxar.</b> Discútese coas ideas, non contra as persoas. Os insultos, as humillacións e as descualificacións que non achegan argumento retíranse.</p>
<p><b>Odiar.</b> Nada que incite ao odio, á discriminación ou á violencia contra alguén pola súa orixe, sexo, orientación, relixión, discapacidade ou calquera outra condición.</p>
<p><b>Ameazar e acosar.</b> Nin ameazas, nin persecución a un usuario de fío en fío, nin campañas para sinalar a ninguén.</p>
<p><b>Publicar datos doutras persoas.</b> Enderezos, teléfonos, documentos, contas privadas, fotos íntimas, lugares de traballo ou calquera dato que sirva para localizar ou identificar unha persoa que non o fixo público. Aquí chámase <i>doxxing</i> e é o máis grave que se pode facer en {site}.</p>
<p><b>Difundir contido ilegal.</b> Material que explote menores, apoloxía do terrorismo, incitación a delitos, contido que vulnere dereitos de autor de forma evidente.</p>
<p><b>Suplantar.</b> Facerse pasar por outra persoa, medio ou institución.</p>
<p><b>Facer trampas.</b> Contas múltiples para votar, publicidade encuberta, ligazóns enganosas e calquera forma de manipular a portada.</p>

<h2>Como se modera</h2>
<p>Calquera pode <b>denunciar</b> un comentario desde o propio comentario, sen necesidade de ter conta. As denuncias revísanas persoas: moderadores voluntarios da comunidade e o equipo de {site}. Ademais, unha IA que vive nos nosos servidores (ningún texto sae deles) fai un primeiro filtro cando está activada.</p>
<p>Cando un comentario incumpre estas normas, non desaparece sen máis: o seu texto substitúese por <i>«Comentario eliminado por incumprir as normas de uso»</i>, de xeito que a conversa se entende e queda constancia de que aí houbo algo que non debía estar. Se un fío enteiro é ilegal, retírase.</p>
<p>A moderación trabúcase ás veces. Se cres que se retirou algo teu sen motivo, escribe a <a href="mailto:info@deltha.eu">info@deltha.eu</a> e revisámolo.</p>

<h2>Consecuencias</h2>
<p>A primeira vez, retírase o comentario. Se se repite, a conta pode quedar suspendida. Os casos que poidan ser delito consérvanse e ponse á disposición das autoridades se o requiren, como obriga a lei.</p>

<h2>E o demais</h2>
<p>Todo o que se publica aquí é efémero: os fíos arquívanse ás {ttl} horas. Coida o que escribes durante esas horas coma se fose durar sempre, porque para quen o le, dura.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('privacidad.gl', 'Privacidade e contacto', '<h1>Privacidade e contacto</h1>

<h2>Quen está detrás</h2>
<p><b>{site}</b> é un servizo de <b>Jot Down Media Company S.L.</b>, NIF B88863592, con domicilio na Calle Palillos, núm. 6, porta 36, 41500 Alcalá de Guadaíra (Sevilla), España. É a responsable do tratamento dos datos que se describen aquí.</p>
<p>Para calquera asunto relacionado cos teus datos, e tamén para queixas, parabéns e propostas, escribe a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>O que non facemos</h2>
<p>Non usamos cookies con quen non inicia sesión. Non hai rastrexadores, nin analítica de terceiros, nin publicidade programática, nin recursos cargados desde outros dominios: todo o que amosa esta web sae dos nosos propios servidores. Podes comprobalo coas ferramentas de desenvolvemento do teu navegador. Non vendemos nin cedemos datos a ninguén.</p>

<h2>Que datos tratamos e para que</h2>
<p><b>Se só les</b>, non tratamos ningún dato teu máis alá dos rexistros técnicos do servidor (enderezo IP, data, páxina solicitada e navegador), que existen en calquera servidor web, serven para manter o servizo e detectar abusos, e bórranse nun prazo breve. Para saber cantas persoas hai no sitio en cada momento gardamos durante uns minutos unha pegada non reversible da túa visita (calculada a partir da IP, o navegador e o día, e que non permite recuperalos); bórrase soa aos dez minutos e non se usa para nada máis.</p>
<p><b>Se comentas sen conta</b>, o teu comentario publícase baixo un alias xerado a partir dunha pegada non reversible do teu enderezo IP, o fío e un segredo do servidor. Gardamos esa pegada truncada (para que o teu alias sexa o mesmo dentro dun fío), nunca o enderezo IP.</p>
<p><b>Se creas unha conta</b>, gardamos o teu nome de usuario, o teu correo electrónico e o teu contrasinal cifrado de forma irreversible. O correo serve unicamente para xestionar a túa conta; non enviamos boletíns nin publicidade. Podes engadir unha frase e unha foto de perfil; ás fotos eliminámoslles os metadatos (EXIF) ao subilas. Con conta créase unha única cookie técnica de sesión, imprescindible para saber que es ti, que desaparece ao saír. Gardamos tamén a hora da túa última visita, que só serve para contar cantas persoas están conectadas en cada momento.</p>
<p><b>Se envías unha nova</b>, o URL, o titular, a entradiña e a foto que achegues publícanse. As fotos recórtanse e elimínanselles os metadatos no servidor.</p>
<p><b>Mentres o acceso sexa privado</b>, quen entre coa clave de acceso recibe unha cookie técnica que só contén unha pegada desa clave. Deixará de existir cando abramos o sitio.</p>

<h2>Canto tempo</h2>
<p>{site} é efémera por deseño. Os envíos e os seus comentarios arquívanse ás {ttl} horas de chegar á portada. O teu nome de usuario aparece xunto a cada achega aos {gm} minutos de publicala; ata entón só se ve o alias. Os datos da túa conta consérvanse mentres a manteñas aberta.</p>

<h2>Moderación</h2>
<p>A moderación faina unha intelixencia artificial que se executa nos nosos propios servidores: ningún envío nin comentario sae deles para ser revisado por terceiros. Só se filtra o que a lei non permite (insultos, delitos de odio, ameazas e, en xeral, calquera conduta delituosa). Cando o sistema dubida, decide unha persoa. Se cres que unha decisión foi inxusta, escríbenos.</p>

<h2>Os teus dereitos</h2>
<p>Podes acceder aos teus datos, rectificalos, pedir que os borremos, opoñerte ao seu tratamento, limitalo ou solicitar a súa portabilidade, conforme ao Regulamento (UE) 2016/679 e á Lei orgánica 3/2018. Abonda con escribir a <a href="mailto:info@deltha.eu">info@deltha.eu</a> desde o correo co que te rexistraches. Se non quedas conforme, podes reclamar ante a Axencia Española de Protección de Datos (aepd.es).</p>
<p>A base xurídica do tratamento é a execución do servizo que solicitas ao rexistrarte ou participar, e o noso interese lexítimo en manter o sitio seguro e dentro da lei.</p>

<h2>Cambios</h2>
<p>Se cambiamos esta política, dirémolo aquí. Última actualización: setembro de 2026.</p>
<p><i>Esta é unha tradución ofrecida por comodidade. En caso de discrepancia, prevalece a versión en español.</i></p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('que-es.en', 'What is Deltha?', '<h1>What is Deltha?</h1>

<p>{site} is three things at once: <b>a news aggregator</b>, where people submit links and their votes decide what makes the front page; <b>a counter-algorithmic social network</b>, with no machine choosing what you see; and <b>a place for debate</b>, with threads that are born, argued over and then disappear.</p>

<h2>Where it comes from</h2>
<p>From five places. From the <a href="https://contralgoritmia.com/" rel="noopener">counter-algorithmic manifesto</a> comes the core idea: reclaiming human judgement from the hegemony of the algorithm. From <b>Menéame</b>, the aggregator: the community decides what makes the front page. From <b>X</b>, the design: a clean, fast timeline. From <b>4chan</b>, the ephemerality: threads are born, argued over and disappear. And from <b>Jot Down''s business model</b>, the way to stay afloat: no programmatic advertising, no tracking, just a community that supports what it reads.</p>

<h2>How it works</h2>
<p><b>It''s ephemeral.</b> Posts and comments are archived after {ttl} hours. What matters is what''s happening now, not what piles up.</p>
<p><b>It leaves no trace.</b> No tracking cookies, no programmatic advertising, no third parties. You can check for yourself with your browser''s developer tools.</p>
<p><b>It''s horizontal.</b> Everything is published under an alias, and the author''s name only appears after {gm} minutes, so the words are judged before the person saying them.</p>
<p><b>You can see inside it.</b> The Processes section shows the queue live, the δ of every post, and every vote, comment and front-page story as it happens.</p>
<p><b>No hidden algorithm.</b> What makes the front page is decided by a public formula, short and visible under every post: the <b>δ</b>. It''s explained in full in <a href="/algoritmo.php">How δ works</a>.</p>

<h2>Aura</h2>
<p>There''s no karma. There''s <b>aura</b>, and it measures just one thing: your commitment to the community. You earn it by being here and contributing, not by being liked: +{auraport} for each of your posts that makes the front page, +{auraenv} for each post or article, +{auracom} for each comment, up to +{auracap} a day. And it goes down: every day you lose {auradec}, down to a minimum of {aurastart}. It doesn''t pile up: it has to be kept up. It measures who''s here, not who used to be.</p>
<p><b>It never gives you a megaphone.</b> Aura doesn''t push stories up or rank comments. Everyone sees the same thing, in the same order. It shows as a glow around your alias, nothing more. All it does is open doors: with more than {commaura} you can create communities and edit comments you''ve just written.</p>

<h2>What can you post?</h2>
<p><b>Anything the law allows.</b> The limit isn''t set by an editorial line or anyone''s taste: it''s set by the law. Insults, hate crimes, threats and anything else that''s a crime are out. It''s all written down in the <a href="/normas.php">rules of use</a>.</p>
<p><b>Moderation is done by an AI that lives on our servers.</b> No post or comment leaves them to be reviewed: there are no third parties looking at what you write. When it''s unsure, a person decides. Anyone can report a comment.</p>
<p><b>And there''s an email address for everything else.</b> Complaints, compliments and suggestions: <b>info@deltha.eu</b>.</p>

<h2>Topics and communities</h2>
<p><b>Topics are set by {site}.</b> Few and stable: current affairs, science, culture… Every post has one, and the topic can give the δ a small nudge (a public coefficient).</p>
<p><b>Communities are created by people.</b> With more than {commaura} aura you can start up to {commmax}: a name, a colour and what it''s about. A post can join one or several, and whoever created the community (or a moderator) looks after it. Each community can pick its own skin, applied to its page and its threads. And you can choose a community or a topic as your landing page.</p>

<h2>Your way</h2>
<p><b>{skins} skins.</b> Same front page, same numbers, different skin: magazine, forum, terminal, manga, fanzine… You pick one in the Skins tab, and each comes in a light and a dark version.</p>
<p><b>In your language and in your pocket.</b> Eight languages: Spanish, Catalan, Basque, Galician, English, French, German and Italian. It installs as an app on your phone, with private messages and chat between registered users, and you can save posts to your favourites. None of this changes what you see: only how you see it.</p>

<h2>Off the search engines</h2>
<p><b>What happens on {site} stays on {site}.</b> No post, comment or profile shows up on Google or any other search engine: every page carries the <i>noindex</i> tag. Only the pages that explain the project, like this one, are visible.</p>
<p><b>Unless you want it to.</b> If you write an article, you can tick the box to let search engines index it. It''s your text and your call; you can change it when you edit it.</p>
<p><b>And no AI trains on what you write.</b> Artificial intelligence crawlers (from OpenAI, Anthropic, Google, Meta, Common Crawl and the like) are barred at the door, even from articles open to search engines.</p>

<h2>Want to own {site}?</h2>
<p>{site} is a project open to everyone. To become a co-owner we don''t ask you for money: just that you use the platform. <b>50% of the ownership of {site} will be shared among all registered users who have {auramax} aura on {auradl}.</b></p>
<p>Want to try it? <a href="/registro.php">Sign up</a> or, if you already have an account, <a href="/login.php">log in</a>.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('algoritmo.en', 'How δ works', '<h1>How δ works</h1>

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

<h2>What δ doesn''t do</h2>
<p>It doesn''t learn from you. It doesn''t know what you like. It doesn''t show you a different front page from your neighbour''s. It doesn''t reward whoever has the most followers, because nobody here has followers. It doesn''t track what time you usually drop by or how long you spend reading. The same formula, the same numbers, the same front page for everyone.</p>

<h2>What you do choose</h2>
<p>The only thing that changes from one person to another is <b>how things look</b>, never <b>what you see</b>. In your profile you choose the <b>visual template</b> (several of them, from magazine to fanzine, each with a light and a dark version), the <b>light or dark theme</b>, the <b>language</b> (Spanish, Catalan, Basque, Galician, English, French, German or Italian) and your landing page. A community can have its own template, which you''ll see on its page and in its threads. {site} installs as an app on your phone and has private messages between registered users. None of this alters the front page or the order of anything: the same formula, in a different skin.</p>

<h2>Aura</h2>
<p>People don''t have a δ; they have <b>aura</b>, which is something else and measures just one thing: commitment to the community. You earn it by being here and contributing (+{auraport} for each of your posts that makes the front page, +{auraenv} for each post or article, +{auracom} for each comment, capped at +{auracap} a day) and you lose it by not being here ({auradec} every day, never below {aurastart}). The maximum is {auramax}.</p>
<p>Aura <b>never gives you a megaphone</b>: it doesn''t push stories up, doesn''t rank comments, doesn''t weigh on the δ. It shows as a glow around your alias, and it opens a few doors of trust: with more than {commaura} aura you can create communities (up to {commmax}), and with more than {editaura} you can correct your own comments for {editmin} minutes after posting them (the comment is marked as edited).</p>

<h2>Today''s numbers</h2>
<p>All the parameters on this page are the ones in force right now on {site}; if the team changes them, this page changes with them. Cycle every {cycle} minutes · minimum {minage} minutes in New · minimum δ for the front page {minpub} · decay {decay} per half hour · {gm}-minute veil · minimum δ to become a candidate {mincand} · {ttl} hours of life on the front page · expiry without reaching the front page after {newttl} hours · communities for aura above {commaura}, up to {commmax} per person.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('normas.en', 'Rules of use', '<h1>Rules of use</h1>

<h2>The rule</h2>
<p>On <b>{site}</b> there''s room for anything the law allows. There''s no editorial line and no list of banned topics: you can disagree, criticise harshly, be ironic and be wrong. The only limit is the one set by Spanish and European law, and that''s why these rules are short.</p>

<h2>What''s not allowed</h2>
<p><b>Insulting and demeaning.</b> Argue with ideas, not against people. Insults, humiliation and put-downs that add no argument are removed.</p>
<p><b>Hate.</b> Nothing that incites hatred, discrimination or violence against anyone because of their origin, sex, orientation, religion, disability or any other condition.</p>
<p><b>Threats and harassment.</b> No threats, no hounding a user from thread to thread, no campaigns to single anyone out.</p>
<p><b>Posting other people''s data.</b> Addresses, phone numbers, documents, private accounts, intimate photos, workplaces or any information that could be used to locate or identify a person who hasn''t made it public. It''s called <i>doxxing</i>, and it''s the most serious thing you can do on {site}.</p>
<p><b>Spreading illegal content.</b> Material that exploits minors, glorification of terrorism, incitement to crime, content that clearly infringes copyright.</p>
<p><b>Impersonation.</b> Passing yourself off as another person, outlet or institution.</p>
<p><b>Cheating.</b> Multiple accounts to vote, covert advertising, misleading links and any other way of manipulating the front page.</p>

<h2>How moderation works</h2>
<p>Anyone can <b>report</b> a comment from the comment itself, no account needed. Reports are reviewed by people: volunteer moderators from the community and the {site} team. On top of that, an AI that lives on our servers (no text ever leaves them) does a first pass when it''s switched on.</p>
<p>When a comment breaks these rules, it doesn''t just vanish: its text is replaced with <i>“Comment removed for breaking the rules of use”</i>, so the conversation still makes sense and there''s a record that something was there that shouldn''t have been. If an entire thread is illegal, it''s taken down.</p>
<p>Moderation sometimes gets it wrong. If you think something of yours was removed for no reason, write to <a href="mailto:info@deltha.eu">info@deltha.eu</a> and we''ll look into it.</p>

<h2>Consequences</h2>
<p>The first time, the comment is removed. If it happens again, the account may be suspended. Cases that may be crimes are preserved and made available to the authorities if they request them, as the law requires.</p>

<h2>And everything else</h2>
<p>Everything posted here is ephemeral: threads are archived after {ttl} hours. Take care of what you write during those hours as if it were going to last forever, because for whoever reads it, it does.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('privacidad.en', 'Privacy and contact', '<h1>Privacy and contact</h1>

<h2>Who''s behind it</h2>
<p><b>{site}</b> is a service of <b>Jot Down Media Company S.L.</b>, NIF (tax ID) B88863592, with registered address at Calle Palillos, núm. 6, puerta 36, 41500 Alcalá de Guadaíra (Sevilla), Spain. It is the controller of the data processing described here.</p>
<p>For anything related to your data, and also for complaints, compliments and suggestions, write to <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>What we don''t do</h2>
<p>We don''t use cookies with anyone who isn''t signed in. There are no trackers, no third-party analytics, no programmatic advertising and no resources loaded from other domains: everything this website shows comes from our own servers. You can check this with your browser''s developer tools. We don''t sell or hand over data to anyone.</p>

<h2>What data we process and why</h2>
<p><b>If you only read</b>, we don''t process any of your data beyond the server''s technical logs (IP address, date, page requested and browser), which exist on any web server, are used to keep the service running and detect abuse, and are deleted after a short period. To know how many people are on the site at any moment, we keep a non-reversible fingerprint of your visit for a few minutes (calculated from your IP, browser and the date, and from which none of them can be recovered); it deletes itself after ten minutes and is used for nothing else.</p>
<p><b>If you comment without an account</b>, your comment is published under an alias generated from a non-reversible fingerprint of your IP address, the thread and a server secret. We store that truncated fingerprint (so your alias stays the same within a thread), never the IP address.</p>
<p><b>If you create an account</b>, we store your username, your email address and your password, irreversibly encrypted. Your email is used only to manage your account; we don''t send newsletters or advertising. You can add a tagline and a profile photo; we strip the metadata (EXIF) from photos when they''re uploaded. With an account, a single technical session cookie is created, essential for knowing it''s you, and it disappears when you log out. We also store the time of your last visit, used only to count how many people are online at any moment.</p>
<p><b>If you submit a story</b>, the URL, headline, summary and photo you provide are published. Photos are cropped and their metadata is stripped on the server.</p>
<p><b>While access is private</b>, anyone who enters with the access key receives a technical cookie that contains only a fingerprint of that key. It will cease to exist once we open the site.</p>

<h2>How long</h2>
<p>{site} is ephemeral by design. Posts and their comments are archived {ttl} hours after reaching the front page. Your username appears next to each contribution {gm} minutes after it''s published; until then only the alias is visible. Your account data is kept for as long as you keep your account open.</p>

<h2>Moderation</h2>
<p>Moderation is done by an artificial intelligence that runs on our own servers: no post or comment leaves them to be reviewed by third parties. Only what the law doesn''t allow is filtered out (insults, hate crimes, threats and, in general, any criminal conduct). When the system is unsure, a person decides. If you think a decision was unfair, write to us.</p>

<h2>Your rights</h2>
<p>You can access your data, rectify it, ask us to erase it, object to or restrict its processing, or request its portability, under Regulation (EU) 2016/679 (General Data Protection Regulation, GDPR) and the Ley Orgánica 3/2018 (Spanish Organic Law 3/2018 on Data Protection). Just write to <a href="mailto:info@deltha.eu">info@deltha.eu</a> from the email address you registered with. If you''re not satisfied, you can lodge a complaint with the Agencia Española de Protección de Datos (Spanish Data Protection Agency, aepd.es).</p>
<p>The legal basis for processing is the performance of the service you request when you register or take part, and our legitimate interest in keeping the site safe and within the law.</p>

<h2>Changes</h2>
<p>If we change this policy, we''ll say so here. Last updated: September 2026.</p>
<p><i>This is a translation provided for convenience. In case of discrepancy, the Spanish version prevails.</i></p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('que-es.fr', 'Qu''est-ce que Deltha ?', '<h1>Qu''est-ce que Deltha ?</h1>

<p>{site}, c''est trois choses à la fois : <b>un agrégateur d''actualités</b>, où les gens envoient des liens et décident par leurs votes de ce qui arrive en une ; <b>un réseau social contre-algorithmique</b>, sans machine qui choisit pour toi ce que tu vois ; et <b>un espace de débat</b>, avec des fils qui naissent, se discutent et disparaissent.</p>

<h2>D''où ça vient</h2>
<p>De cinq sites. Du <a href="https://contralgoritmia.com/" rel="noopener">manifeste contre-algorithmique</a> vient l''idée de fond : retrouver le jugement humain face à l''hégémonie de l''algorithme. De <b>Menéame</b>, l''agrégateur : la communauté décide de ce qui arrive en une. De <b>X</b>, le design : un fil chronologique propre et rapide. De <b>4chan</b>, l''éphémère : les fils naissent, se discutent et disparaissent. Et du <b>modèle économique de Jot Down</b>, la façon de tenir debout : ni publicité programmatique ni pistage, une communauté qui fait vivre ce qu''elle lit.</p>

<h2>Comment ça marche</h2>
<p><b>C''est éphémère.</b> Les envois et les commentaires sont archivés au bout de {ttl} heures. Ce qui compte, c''est ce qui se passe maintenant, pas ce qui s''accumule.</p>
<p><b>Ça ne laisse pas de traces.</b> Pas de cookies de pistage, pas de publicité programmatique, pas de tiers. Tu peux le vérifier en ouvrant les outils de ton navigateur.</p>
<p><b>C''est horizontal.</b> Tout est publié sous un alias et le nom de l''auteur n''apparaît qu''au bout de {gm} minutes, pour qu''on juge les mots avant la personne qui les dit.</p>
<p><b>On voit l''intérieur.</b> La section Processus montre en direct la file d''attente, le δ de chaque envoi et chaque vote, commentaire et passage en une au moment où ils se produisent.</p>
<p><b>Pas d''algorithme caché.</b> Ce qui arrive en une, c''est une formule publique, courte et visible sous chaque envoi qui le décide : le <b>δ</b>. Elle est expliquée en entier dans <a href="/algoritmo.php">Comment fonctionne le δ</a>.</p>

<h2>L''aura</h2>
<p>Il n''y a pas de karma. Il y a l''<b>aura</b>, et elle ne mesure qu''une chose : ton engagement envers la communauté. Elle se gagne en étant là et en contribuant, pas en plaisant : +{auraport} pour chaque envoi à toi qui arrive en une, +{auraenv} pour chaque envoi ou article, +{auracom} pour chaque commentaire, au maximum +{auracap} par jour. Et elle baisse : chaque jour tu perds {auradec}, jusqu''au minimum de {aurastart}. Elle ne s''accumule pas : elle s''entretient. Elle mesure qui est là, pas qui a été là.</p>
<p><b>Elle ne donne jamais de porte-voix.</b> L''aura ne fait pas monter d''actualités et ne classe pas les commentaires. Tout le monde voit la même chose, dans le même ordre. Elle se voit comme une lumière autour de ton alias, rien de plus. Tout ce qu''elle ouvre, ce sont des portes : au-delà de {commaura}, tu peux créer des communautés et modifier tes commentaires tout juste écrits.</p>

<h2>Que peut-on publier ?</h2>
<p><b>Tout ce que la loi permet.</b> La limite n''est fixée ni par une ligne éditoriale ni par un goût : c''est la loi qui la fixe. Restent dehors les insultes, les délits de haine, les menaces et tout ce qui constitue un délit. C''est écrit dans les <a href="/normas.php">règles d''usage</a>.</p>
<p><b>La modération est faite par une IA qui vit sur nos serveurs.</b> Aucun envoi ni commentaire n''en sort pour être examiné : il n''y a pas de tiers qui regardent ce que tu écris. En cas de doute, c''est une personne qui décide. Tout le monde peut signaler un commentaire.</p>
<p><b>Et il y a une adresse pour tout le reste.</b> Plaintes, félicitations et propositions : <b>info@deltha.eu</b>.</p>

<h2>Thèmes et communautés</h2>
<p><b>Les thèmes, c''est {site} qui les fixe.</b> Peu nombreux et stables : actualité, science, culture… Chaque envoi en a un, et le thème peut donner un petit coup de pouce au δ (un coefficient public).</p>
<p><b>Les communautés, ce sont les gens qui les créent.</b> Avec plus de {commaura} d''aura, tu peux en ouvrir jusqu''à {commmax} : un nom, une couleur et un sujet. Un envoi peut rejoindre une ou plusieurs communautés, et celui qui l''a créée (ou un modérateur) en prend soin. Chaque communauté peut choisir sa propre skin, qui s''applique à sa page et à ses fils. Et tu peux choisir une communauté ou un thème comme page d''arrivée.</p>

<h2>À ta façon</h2>
<p><b>{skins} skins.</b> Même une, mêmes chiffres, autre peau : magazine, forum, terminal, manga, fanzine… Tu la choisis dans l''onglet Skins, et chacune a une version claire et une version sombre.</p>
<p><b>Dans ta langue et dans ta poche.</b> Huit langues : espagnol, catalan, basque, galicien, anglais, français, allemand et italien. Ça s''installe comme une appli sur le mobile, avec messages privés et chat entre inscrits, et tu peux enregistrer des envois dans tes favoris. Rien de tout cela ne change ce que tu vois : seulement la manière dont tu le vois.</p>

<h2>Hors des moteurs de recherche</h2>
<p><b>Ce qui se passe sur {site} reste sur {site}.</b> Aucun envoi, commentaire ni profil n''apparaît dans Google ni dans les autres moteurs de recherche : toutes les pages portent la balise <i>noindex</i>. Seules les pages qui expliquent le projet, comme celle-ci, se laissent voir.</p>
<p><b>Sauf si tu le veux.</b> Si tu écris un article, tu peux cocher la case pour que les moteurs de recherche l''indexent. C''est ton texte, c''est toi qui décides ; tu peux changer d''avis en le modifiant.</p>
<p><b>Et aucune IA ne s''entraîne sur ce que tu écris.</b> Les robots d''intelligence artificielle (ceux d''OpenAI, Anthropic, Google, Meta, Common Crawl et compagnie) ont l''entrée interdite, même sur les articles ouverts aux moteurs de recherche.</p>

<h2>Tu veux devenir propriétaire de {site} ?</h2>
<p>{site} est un projet ouvert à tout le monde. Pour en être copropriétaire, on ne te demande pas d''argent : seulement d''utiliser la plateforme. <b>50 % de la propriété de {site} sera réparti entre tous les utilisateurs inscrits qui auront une aura de {auramax} le {auradl}.</b></p>
<p>Tu veux l''essayer ? <a href="/registro.php">Inscris-toi</a> ou, si tu as déjà un compte, <a href="/login.php">connecte-toi</a>.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('algoritmo.fr', 'Comment fonctionne le δ', '<h1>Comment fonctionne le δ</h1>

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

<h2>Ce que le δ ne fait pas</h2>
<p>Il n''apprend rien de toi. Il ne sait pas ce que tu aimes. Il ne te montre pas une une différente de celle de ton voisin. Il ne récompense pas ceux qui ont le plus d''abonnés, parce qu''ici personne n''a d''abonnés. Il ne regarde pas l''heure à laquelle tu te connectes d''habitude ni le temps que tu passes à lire. La même formule, les mêmes chiffres, la même une pour tout le monde.</p>

<h2>Ce que tu choisis, toi</h2>
<p>La seule chose qui change d''une personne à l''autre, c''est <b>comment on voit</b>, jamais <b>ce qu''on voit</b>. Dans ton profil, tu choisis l''<b>habillage</b> (plusieurs, du magazine au fanzine, chacun en version claire et sombre), le <b>thème clair ou sombre</b>, la <b>langue</b> (espagnol, catalan, basque, galicien, anglais, français, allemand ou italien) et la page d''arrivée. Une communauté peut avoir son propre habillage, que tu verras sur sa page et dans ses fils. {site} s''installe comme une application sur le mobile et propose des messages privés entre inscrits. Rien de tout cela ne modifie la une ni l''ordre de quoi que ce soit : la même formule, avec une autre peau.</p>

<h2>L''aura</h2>
<p>Les personnes n''ont pas de δ ; elles ont une <b>aura</b>, qui est autre chose et ne mesure qu''une chose : l''engagement envers la communauté. Elle se gagne en étant là et en contribuant (+{auraport} pour chaque envoi à toi qui arrive en une, +{auraenv} pour chaque envoi ou article, +{auracom} pour chaque commentaire, avec un plafond de +{auracap} par jour) et se perd en n''étant pas là ({auradec} par jour, jamais en dessous de {aurastart}). Le maximum est de {auramax}.</p>
<p>L''aura <b>ne donne jamais de porte-voix</b> : elle ne fait pas monter d''actualités, ne classe pas les commentaires, ne pèse pas dans le δ. Elle se voit comme une lumière autour de ton alias, et ouvre quelques portes de confiance : avec plus de {commaura} d''aura tu peux créer des communautés (jusqu''à {commmax}), et avec plus de {editaura} tu peux corriger tes propres commentaires pendant {editmin} minutes après les avoir publiés (le commentaire est alors marqué comme modifié).</p>

<h2>Les chiffres du jour</h2>
<p>Tous les paramètres de cette page sont ceux en vigueur en ce moment même sur {site} ; si l''équipe les change, cette page change avec eux. Cycle toutes les {cycle} minutes · {minage} minutes minimum dans Nouveautés · δ minimum pour la une {minpub} · décroissance de {decay} par demi-heure · voile de {gm} minutes · δ minimum pour devenir candidate {mincand} · durée de vie en une de {ttl} heures · expiration sans une au bout de {newttl} heures · communautés avec une aura supérieure à {commaura}, jusqu''à {commmax} par personne.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('normas.fr', 'Règles d''usage', '<h1>Règles d''usage</h1>

<h2>La règle</h2>
<p>Sur <b>{site}</b>, tout ce que la loi permet a sa place. Pas de ligne éditoriale ni de liste de sujets interdits : on peut être en désaccord, critiquer durement, ironiser et se tromper. La seule limite est celle que fixe la législation espagnole et européenne, et c''est pourquoi ces règles sont courtes.</p>

<h2>Ce qui n''a pas sa place</h2>
<p><b>Insulter et humilier.</b> On débat avec des idées, pas contre des personnes. Les insultes, les humiliations et les dénigrements qui n''apportent aucun argument sont retirés.</p>
<p><b>Haïr.</b> Rien qui incite à la haine, à la discrimination ou à la violence contre quelqu''un en raison de son origine, de son sexe, de son orientation, de sa religion, de son handicap ou de toute autre condition.</p>
<p><b>Menacer et harceler.</b> Ni menaces, ni traque d''un utilisateur de fil en fil, ni campagnes pour désigner qui que ce soit à la vindicte.</p>
<p><b>Publier les données d''autrui.</b> Adresses, téléphones, documents, comptes privés, photos intimes, lieux de travail ou toute donnée permettant de localiser ou d''identifier une personne qui ne l''a pas rendue publique. Ça s''appelle le <i>doxxing</i>, et c''est la chose la plus grave qu''on puisse faire sur {site}.</p>
<p><b>Diffuser des contenus illégaux.</b> Contenus exploitant des mineurs, apologie du terrorisme, incitation à commettre des délits, contenus violant manifestement le droit d''auteur.</p>
<p><b>Usurper une identité.</b> Se faire passer pour une autre personne, un média ou une institution.</p>
<p><b>Tricher.</b> Comptes multiples pour voter, publicité déguisée, liens trompeurs et toute forme de manipulation de la une.</p>

<h2>Comment on modère</h2>
<p>Tout le monde peut <b>signaler</b> un commentaire depuis le commentaire lui-même, sans avoir besoin de compte. Les signalements sont examinés par des personnes : des modérateurs bénévoles de la communauté et l''équipe de {site}. En plus, une IA qui vit sur nos serveurs (aucun texte n''en sort) fait un premier tri quand elle est activée.</p>
<p>Quand un commentaire enfreint ces règles, il ne disparaît pas purement et simplement : son texte est remplacé par <i>« Commentaire supprimé pour non-respect des règles d''usage »</i>, de sorte que la conversation reste compréhensible et qu''il reste une trace qu''il y avait là quelque chose qui n''aurait pas dû y être. Si un fil entier est illégal, il est retiré.</p>
<p>La modération se trompe parfois. Si tu penses qu''on a retiré quelque chose de toi sans raison, écris à <a href="mailto:info@deltha.eu">info@deltha.eu</a> et nous le réexaminerons.</p>

<h2>Conséquences</h2>
<p>La première fois, le commentaire est retiré. En cas de récidive, le compte peut être suspendu. Les cas susceptibles de constituer un délit sont conservés et mis à la disposition des autorités si elles le demandent, comme la loi l''impose.</p>

<h2>Et le reste</h2>
<p>Tout ce qui se publie ici est éphémère : les fils sont archivés au bout de {ttl} heures. Soigne ce que tu écris pendant ces heures comme si c''était pour toujours, parce que pour qui le lit, ça l''est.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('privacidad.fr', 'Confidentialité et contact', '<h1>Confidentialité et contact</h1>

<h2>Qui est derrière</h2>
<p><b>{site}</b> est un service de <b>Jot Down Media Company S.L.</b>, NIF B88863592, dont le siège est situé Calle Palillos, núm. 6, puerta 36, 41500 Alcalá de Guadaíra (Sevilla), Espagne. Elle est responsable du traitement des données décrites ici.</p>
<p>Pour toute question concernant tes données, et aussi pour les plaintes, félicitations et propositions, écris à <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Ce que nous ne faisons pas</h2>
<p>Nous n''utilisons pas de cookies pour qui ne se connecte pas. Pas de traceurs, pas d''analyse d''audience par des tiers, pas de publicité programmatique, pas de ressources chargées depuis d''autres domaines : tout ce qu''affiche ce site vient de nos propres serveurs. Tu peux le vérifier avec les outils de développement de ton navigateur. Nous ne vendons ni ne cédons de données à personne.</p>

<h2>Quelles données nous traitons et pourquoi</h2>
<p><b>Si tu ne fais que lire</b>, nous ne traitons aucune donnée te concernant au-delà des journaux techniques du serveur (adresse IP, date, page demandée et navigateur), qui existent sur n''importe quel serveur web, servent à maintenir le service et à détecter les abus, et sont effacés à bref délai. Pour savoir combien de personnes sont sur le site à chaque instant, nous gardons pendant quelques minutes une empreinte irréversible de ta visite (calculée à partir de l''IP, du navigateur et du jour, et qui ne permet pas de les retrouver) ; elle s''efface d''elle-même au bout de dix minutes et ne sert à rien d''autre.</p>
<p><b>Si tu commentes sans compte</b>, ton commentaire est publié sous un alias généré à partir d''une empreinte irréversible de ton adresse IP, du fil et d''un secret du serveur. Nous gardons cette empreinte tronquée (pour que ton alias reste le même dans un fil), jamais l''adresse IP.</p>
<p><b>Si tu crées un compte</b>, nous gardons ton nom d''utilisateur, ton adresse e-mail et ton mot de passe chiffré de manière irréversible. L''adresse e-mail sert uniquement à gérer ton compte ; nous n''envoyons ni lettres d''information ni publicité. Tu peux ajouter une phrase et une photo de profil ; nous supprimons les métadonnées (EXIF) des photos au moment de l''envoi. Avec un compte, un unique cookie technique de session est créé, indispensable pour savoir que c''est bien toi, et il disparaît quand tu te déconnectes. Nous gardons aussi l''heure de ta dernière visite, qui sert seulement à compter combien de personnes sont connectées à chaque instant.</p>
<p><b>Si tu envoies une actualité</b>, l''URL, le titre, le chapeau et la photo que tu fournis sont publiés. Les photos sont recadrées et leurs métadonnées supprimées sur le serveur.</p>
<p><b>Tant que l''accès est privé</b>, qui entre avec la clé d''accès reçoit un cookie technique qui ne contient qu''une empreinte de cette clé. Il cessera d''exister quand nous ouvrirons le site.</p>

<h2>Combien de temps</h2>
<p>{site} est éphémère par conception. Les envois et leurs commentaires sont archivés {ttl} heures après leur arrivée en une. Ton nom d''utilisateur apparaît à côté de chaque contribution {gm} minutes après sa publication ; jusque-là, seul l''alias est visible. Les données de ton compte sont conservées tant que tu le gardes ouvert.</p>

<h2>Modération</h2>
<p>La modération est assurée par une intelligence artificielle qui tourne sur nos propres serveurs : aucun envoi ni commentaire n''en sort pour être examiné par des tiers. Seul est filtré ce que la loi n''autorise pas (insultes, délits de haine, menaces et, de manière générale, toute conduite délictueuse). Quand le système hésite, c''est une personne qui décide. Si tu estimes qu''une décision a été injuste, écris-nous.</p>

<h2>Tes droits</h2>
<p>Tu peux accéder à tes données, les rectifier, demander leur effacement, t''opposer à leur traitement, le limiter ou demander leur portabilité, conformément au Règlement (UE) 2016/679 (RGPD) et à la Ley Orgánica 3/2018 (loi organique espagnole sur la protection des données). Il suffit d''écrire à <a href="mailto:info@deltha.eu">info@deltha.eu</a> depuis l''adresse e-mail avec laquelle tu t''es inscrit. Si la réponse ne te satisfait pas, tu peux déposer une réclamation auprès de l''Agencia Española de Protección de Datos (aepd.es), l''autorité espagnole de protection des données.</p>
<p>La base juridique du traitement est l''exécution du service que tu demandes en t''inscrivant ou en participant, ainsi que notre intérêt légitime à maintenir le site sûr et dans le respect de la loi.</p>

<h2>Modifications</h2>
<p>Si nous modifions cette politique, nous le dirons ici. Dernière mise à jour : septembre 2026.</p>
<p><i>Cette traduction est fournie à titre indicatif. En cas de divergence, la version espagnole fait foi.</i></p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('que-es.de', 'Was ist Deltha?', '<h1>Was ist Deltha?</h1>

<p>{site} ist drei Dinge zugleich: <b>ein Nachrichten-Aggregator</b>, in dem Leute Links einreichen und mit ihren Stimmen entscheiden, was auf die Titelseite kommt; <b>ein gegenalgorithmisches soziales Netzwerk</b>, ohne Maschine, die für dich auswählt, was du siehst; und <b>ein Ort für Debatten</b>, mit Threads, die entstehen, diskutiert werden und wieder verschwinden.</p>

<h2>Woher es kommt</h2>
<p>Von fünf Orten. Aus dem <a href="https://contralgoritmia.com/" rel="noopener">gegenalgorithmischen Manifest</a> stammt die Grundidee: das menschliche Urteil gegen die Vorherrschaft des Algorithmus zurückzugewinnen. Von <b>Menéame</b> der Aggregator: Die Community entscheidet, was auf die Titelseite kommt. Von <b>X</b> das Design: eine klare, schnelle Timeline. Von <b>4chan</b> die Vergänglichkeit: Threads entstehen, werden diskutiert und verschwinden. Und vom <b>Geschäftsmodell von Jot Down</b> die Art, sich zu tragen: ohne programmatische Werbung und ohne Tracking – eine Community, die trägt, was sie liest.</p>

<h2>So funktioniert es</h2>
<p><b>Es ist vergänglich.</b> Beiträge und Kommentare werden nach {ttl} Stunden archiviert. Wichtig ist, was jetzt passiert, nicht was sich ansammelt.</p>
<p><b>Es hinterlässt keine Spuren.</b> Keine Tracking-Cookies, keine programmatische Werbung, keine Dritten. Du kannst es selbst prüfen, mit den Werkzeugen deines Browsers.</p>
<p><b>Es ist horizontal.</b> Alles erscheint zuerst unter einem Alias, der Name des Autors erst nach {gm} Minuten. So werden die Worte beurteilt, bevor es um die Person geht.</p>
<p><b>Man sieht hinein.</b> Der Bereich Abläufe zeigt live die Warteschlange, das δ jedes Beitrags und jede Stimme, jeden Kommentar und jede Titelseite, sobald sie passieren.</p>
<p><b>Kein verborgener Algorithmus.</b> Was auf die Titelseite kommt, entscheidet eine öffentliche, kurze Formel, sichtbar unter jedem Beitrag: das <b>δ</b>. Sie wird vollständig erklärt unter <a href="/algoritmo.php">So funktioniert das δ</a>.</p>

<h2>Die Aura</h2>
<p>Es gibt kein Karma. Es gibt <b>Aura</b>, und sie misst nur eines: dein Engagement für die Community. Man gewinnt sie, indem man da ist und etwas beiträgt, nicht indem man gefällt: +{auraport} für jeden deiner Beiträge, der auf die Titelseite kommt, +{auraenv} für jeden Beitrag oder Artikel, +{auracom} für jeden Kommentar, höchstens +{auracap} pro Tag. Und sie sinkt: Jeden Tag verlierst du {auradec}, bis zum Minimum von {aurastart}. Sie sammelt sich nicht an, sie will gehalten werden. Sie misst, wer da ist, nicht wer da war.</p>
<p><b>Sie macht niemanden lauter.</b> Die Aura hebt keine Nachrichten hoch und sortiert keine Kommentare. Alle sehen dasselbe, in derselben Reihenfolge. Sie erscheint als Licht um deinen Alias, sonst nichts. Sie öffnet nur Türen: Mit mehr als {commaura} kannst du Communities gründen und deine frisch geschriebenen Kommentare bearbeiten.</p>

<h2>Was darf man veröffentlichen?</h2>
<p><b>Alles, was das Gesetz erlaubt.</b> Die Grenze setzt keine redaktionelle Linie und kein Geschmack, sondern das Gesetz. Draußen bleiben Beleidigungen, Hassdelikte, Drohungen und alles, was eine Straftat ist. Das steht in den <a href="/normas.php">Nutzungsregeln</a>.</p>
<p><b>Moderiert wird von einer KI, die auf unseren Servern lebt.</b> Kein Beitrag und kein Kommentar verlässt sie zur Prüfung: Keine Dritten schauen mit, was du schreibst. Im Zweifel entscheidet ein Mensch. Jeder kann einen Kommentar melden.</p>
<p><b>Und für alles andere gibt es eine E-Mail-Adresse.</b> Beschwerden, Lob und Vorschläge: <b>info@deltha.eu</b>.</p>

<h2>Themen und Communities</h2>
<p><b>Die Themen legt {site} fest.</b> Wenige und beständige: Aktuelles, Wissenschaft, Kultur … Jeder Beitrag hat eines, und das Thema kann dem δ einen kleinen Schub geben (ein öffentlicher Koeffizient).</p>
<p><b>Die Communities gründen die Leute.</b> Mit mehr als {commaura} Aura kannst du bis zu {commmax} eröffnen: ein Name, eine Farbe und worum es geht. Ein Beitrag kann einer oder mehreren beitreten, und wer sie gegründet hat (oder ein Moderator) kümmert sich um sie. Jede Community kann ein eigenes Design wählen, das für ihre Seite und ihre Threads gilt. Und du kannst eine Community oder ein Thema als Startseite wählen.</p>

<h2>Wie du willst</h2>
<p><b>{skins} Designs.</b> Dieselbe Titelseite, dieselben Zahlen, eine andere Haut: Magazin, Forum, Terminal, Manga, Fanzine … Du wählst sie im Reiter Design, und jedes gibt es in Hell und Dunkel.</p>
<p><b>In deiner Sprache und in deiner Tasche.</b> Acht Sprachen: Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch und Italienisch. Es lässt sich als App aufs Handy installieren, mit privaten Nachrichten und Chat zwischen registrierten Nutzern, und du kannst Beiträge als Favoriten speichern. Nichts davon ändert, was du siehst – nur wie du es siehst.</p>

<h2>Raus aus den Suchmaschinen</h2>
<p><b>Was auf {site} passiert, bleibt auf {site}.</b> Kein Beitrag, kein Kommentar und kein Profil taucht bei Google oder anderen Suchmaschinen auf: Alle Seiten tragen die Markierung <i>noindex</i>. Sichtbar sind nur die Seiten, die das Projekt erklären, wie diese hier.</p>
<p><b>Es sei denn, du willst es.</b> Wenn du einen Artikel schreibst, kannst du ein Häkchen setzen, damit Suchmaschinen ihn indexieren. Es ist dein Text, und du entscheidest; beim Bearbeiten lässt es sich ändern.</p>
<p><b>Und keine KI wird mit dem trainiert, was du schreibst.</b> Die Crawler der künstlichen Intelligenz (von OpenAI, Anthropic, Google, Meta, Common Crawl und Co.) haben keinen Zutritt, auch nicht bei Artikeln, die für Suchmaschinen freigegeben sind.</p>

<h2>Willst du Miteigentümer von {site} werden?</h2>
<p>{site} ist ein Projekt, das allen offensteht. Um Miteigentümer zu werden, verlangen wir kein Geld: nur, dass du die Plattform nutzt. <b>50 % des Eigentums an {site} werden unter allen registrierten Nutzern aufgeteilt, die am {auradl} eine Aura von {auramax} haben.</b></p>
<p>Willst du es ausprobieren? <a href="/registro.php">Registriere dich</a> oder, wenn du schon ein Konto hast, <a href="/login.php">melde dich an</a>.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('algoritmo.de', 'So funktioniert das δ', '<h1>So funktioniert das δ</h1>

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

<h2>Was das δ nicht tut</h2>
<p>Es lernt nichts über dich. Es weiß nicht, was dir gefällt. Es zeigt dir keine andere Titelseite als deinem Nachbarn. Es belohnt niemanden, der mehr Follower hat, denn hier hat niemand Follower. Es schaut nicht, wann du normalerweise vorbeikommst oder wie lange du liest. Dieselbe Formel, dieselben Zahlen, dieselbe Titelseite für alle.</p>

<h2>Was du selbst wählst</h2>
<p>Das Einzige, was sich von Person zu Person ändert, ist, <b>wie man es sieht</b>, nie <b>was man sieht</b>. In deinem Profil wählst du das <b>visuelle Design</b> (mehrere, vom Magazin bis zum Fanzine, jedes in Hell und Dunkel), das <b>helle oder dunkle Farbschema</b>, die <b>Sprache</b> (Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch oder Italienisch) und die Startseite. Eine Community kann ihr eigenes Design haben, das du auf ihrer Seite und in ihren Threads siehst. {site} lässt sich als App aufs Handy installieren und hat private Nachrichten zwischen registrierten Nutzern. Nichts davon verändert die Titelseite oder die Reihenfolge von irgendetwas: dieselbe Formel, in anderer Haut.</p>

<h2>Die Aura</h2>
<p>Menschen haben kein δ; sie haben <b>Aura</b>, und die ist etwas anderes und misst nur eines: das Engagement für die Community. Man gewinnt sie, indem man da ist und beiträgt (+{auraport} für jeden deiner Beiträge, der auf die Titelseite kommt, +{auraenv} für jeden Beitrag oder Artikel, +{auracom} für jeden Kommentar, mit einer Obergrenze von +{auracap} pro Tag), und man verliert sie, wenn man nicht da ist ({auradec} pro Tag, nie unter {aurastart}). Das Maximum ist {auramax}.</p>
<p>Die Aura <b>macht niemanden lauter</b>: Sie hebt keine Nachrichten hoch, sortiert keine Kommentare, zählt nicht im δ. Sie erscheint als Licht um deinen Alias und öffnet ein paar Türen des Vertrauens: Mit mehr als {commaura} Aura kannst du Communities gründen (bis zu {commmax}), und mit mehr als {editaura} kannst du deine eigenen Kommentare {editmin} Minuten lang nach dem Veröffentlichen korrigieren (der Kommentar wird als bearbeitet markiert).</p>

<h2>Die Zahlen von heute</h2>
<p>Alle Parameter auf dieser Seite sind die, die gerade auf {site} gelten; ändert das Team sie, ändert sich diese Seite mit. Zyklus alle {cycle} Minuten · mindestens {minage} Minuten in Neu · Mindest-δ für die Titelseite {minpub} · Abzug {decay} pro halbe Stunde · Schleier von {gm} Minuten · Mindest-δ für Kandidaten {mincand} · {ttl} Stunden auf der Titelseite · Ablauf ohne Titelseite nach {newttl} Stunden · Communities ab einer Aura über {commaura}, bis zu {commmax} pro Person.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('normas.de', 'Nutzungsregeln', '<h1>Nutzungsregeln</h1>

<h2>Die Regel</h2>
<p>Auf <b>{site}</b> hat alles Platz, was das Gesetz erlaubt. Es gibt keine redaktionelle Linie und keine Liste verbotener Themen: Man darf widersprechen, hart kritisieren, ironisch sein und sich irren. Die einzige Grenze ist die, die das spanische und europäische Recht zieht, und deshalb sind diese Regeln kurz.</p>

<h2>Was keinen Platz hat</h2>
<p><b>Beleidigen und herabwürdigen.</b> Gestritten wird mit Ideen, nicht gegen Menschen. Beleidigungen, Demütigungen und Herabsetzungen ohne Argument werden entfernt.</p>
<p><b>Hass.</b> Nichts, was zu Hass, Diskriminierung oder Gewalt gegen jemanden aufstachelt, wegen Herkunft, Geschlecht, sexueller Orientierung, Religion, Behinderung oder irgendeines anderen Merkmals.</p>
<p><b>Drohen und belästigen.</b> Keine Drohungen, kein Verfolgen eines Nutzers von Thread zu Thread, keine Kampagnen, um jemanden an den Pranger zu stellen.</p>
<p><b>Daten anderer veröffentlichen.</b> Adressen, Telefonnummern, Dokumente, private Konten, intime Fotos, Arbeitsplätze oder jede Angabe, mit der sich eine Person finden oder identifizieren lässt, die sie nicht selbst öffentlich gemacht hat. Das nennt man <i>Doxxing</i>, und es ist das Schlimmste, was man auf {site} tun kann.</p>
<p><b>Illegale Inhalte verbreiten.</b> Material, das Minderjährige ausbeutet, Verherrlichung von Terrorismus, Aufrufe zu Straftaten, Inhalte, die offensichtlich Urheberrechte verletzen.</p>
<p><b>Sich als jemand anderes ausgeben.</b> Sich als andere Person, anderes Medium oder andere Institution ausgeben.</p>
<p><b>Schummeln.</b> Mehrere Konten zum Abstimmen, Schleichwerbung, irreführende Links und jede Form der Manipulation der Titelseite.</p>

<h2>Wie moderiert wird</h2>
<p>Jeder kann einen Kommentar direkt am Kommentar <b>melden</b>, auch ohne Konto. Die Meldungen prüfen Menschen: freiwillige Moderatoren aus der Community und das Team von {site}. Außerdem übernimmt eine KI, die auf unseren Servern lebt (kein Text verlässt sie), einen ersten Filter, wenn sie aktiviert ist.</p>
<p>Verstößt ein Kommentar gegen diese Regeln, verschwindet er nicht einfach: Sein Text wird ersetzt durch <i>„Kommentar wegen Verstoßes gegen die Nutzungsregeln entfernt“</i>. So bleibt das Gespräch verständlich, und es ist festgehalten, dass dort etwas stand, das nicht hingehörte. Ist ein ganzer Thread illegal, wird er entfernt.</p>
<p>Moderation irrt sich manchmal. Wenn du meinst, dass etwas von dir ohne Grund entfernt wurde, schreib an <a href="mailto:info@deltha.eu">info@deltha.eu</a>, und wir sehen es uns an.</p>

<h2>Folgen</h2>
<p>Beim ersten Mal wird der Kommentar entfernt. Wiederholt es sich, kann das Konto gesperrt werden. Fälle, die eine Straftat sein könnten, werden aufbewahrt und den Behörden auf Anfrage zur Verfügung gestellt, wie es das Gesetz verlangt.</p>

<h2>Und der Rest</h2>
<p>Alles, was hier veröffentlicht wird, ist vergänglich: Threads werden nach {ttl} Stunden archiviert. Geh mit dem, was du in diesen Stunden schreibst, so sorgfältig um, als würde es für immer bleiben. Denn für die, die es lesen, tut es das.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('privacidad.de', 'Datenschutz und Kontakt', '<h1>Datenschutz und Kontakt</h1>

<h2>Wer dahintersteht</h2>
<p><b>{site}</b> ist ein Dienst der <b>Jot Down Media Company S.L.</b>, NIF B88863592, mit Sitz in Calle Palillos, núm. 6, puerta 36, 41500 Alcalá de Guadaíra (Sevilla), Spanien. Sie ist die Verantwortliche für die Verarbeitung der hier beschriebenen Daten.</p>
<p>Für alle Fragen zu deinen Daten, und auch für Beschwerden, Lob und Vorschläge, schreib an <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Was wir nicht tun</h2>
<p>Wer nicht angemeldet ist, bekommt von uns keine Cookies. Es gibt keine Tracker, keine Analyse durch Dritte, keine programmatische Werbung und keine Ressourcen, die von anderen Domains geladen werden: Alles, was diese Website zeigt, kommt von unseren eigenen Servern. Du kannst es mit den Entwicklerwerkzeugen deines Browsers prüfen. Wir verkaufen keine Daten und geben sie an niemanden weiter.</p>

<h2>Welche Daten wir verarbeiten und wozu</h2>
<p><b>Wenn du nur liest</b>, verarbeiten wir keine Daten von dir außer den technischen Serverprotokollen (IP-Adresse, Datum, aufgerufene Seite und Browser). Die gibt es auf jedem Webserver; sie dienen dazu, den Dienst am Laufen zu halten und Missbrauch zu erkennen, und werden nach kurzer Zeit gelöscht. Um zu wissen, wie viele Menschen gerade auf der Website sind, speichern wir für ein paar Minuten einen nicht umkehrbaren Fingerabdruck deines Besuchs (berechnet aus IP, Browser und Tag, ohne dass sich diese daraus zurückgewinnen lassen); er löscht sich nach zehn Minuten von selbst und wird für nichts anderes verwendet.</p>
<p><b>Wenn du ohne Konto kommentierst</b>, erscheint dein Kommentar unter einem Alias, der aus einem nicht umkehrbaren Fingerabdruck deiner IP-Adresse, des Threads und eines Servergeheimnisses erzeugt wird. Wir speichern diesen gekürzten Fingerabdruck (damit dein Alias innerhalb eines Threads gleich bleibt), niemals die IP-Adresse.</p>
<p><b>Wenn du ein Konto erstellst</b>, speichern wir deinen Benutzernamen, deine E-Mail-Adresse und dein unumkehrbar verschlüsseltes Passwort. Die E-Mail-Adresse dient nur der Verwaltung deines Kontos; wir verschicken keine Newsletter und keine Werbung. Du kannst ein Motto und ein Profilfoto hinzufügen; bei Fotos entfernen wir beim Hochladen die Metadaten (EXIF). Mit Konto wird ein einziges technisches Sitzungscookie gesetzt, das nötig ist, um zu wissen, dass du es bist, und das beim Abmelden verschwindet. Außerdem speichern wir den Zeitpunkt deines letzten Besuchs, der nur dazu dient zu zählen, wie viele Menschen gerade online sind.</p>
<p><b>Wenn du eine Nachricht einreichst</b>, werden die URL, die Überschrift, der Anreißer und das Foto, die du beisteuerst, veröffentlicht. Fotos werden auf dem Server zugeschnitten und von Metadaten befreit.</p>
<p><b>Solange der Zugang privat ist</b>, erhält, wer sich mit dem Zugangsschlüssel anmeldet, ein technisches Cookie, das nur einen Fingerabdruck dieses Schlüssels enthält. Es fällt weg, sobald wir die Website öffnen.</p>

<h2>Wie lange</h2>
<p>{site} ist von Grund auf vergänglich. Beiträge und ihre Kommentare werden {ttl} Stunden nach Erreichen der Titelseite archiviert. Dein Benutzername erscheint {gm} Minuten nach der Veröffentlichung neben jedem Beitrag; bis dahin ist nur der Alias zu sehen. Die Daten deines Kontos werden aufbewahrt, solange du es bestehen lässt.</p>

<h2>Moderation</h2>
<p>Moderiert wird von einer künstlichen Intelligenz, die auf unseren eigenen Servern läuft: Kein Beitrag und kein Kommentar verlässt sie, um von Dritten geprüft zu werden. Gefiltert wird nur, was das Gesetz nicht erlaubt (Beleidigungen, Hassdelikte, Drohungen und allgemein jedes strafbare Verhalten). Wenn das System unsicher ist, entscheidet ein Mensch. Wenn du eine Entscheidung für ungerecht hältst, schreib uns.</p>

<h2>Deine Rechte</h2>
<p>Du kannst Auskunft über deine Daten verlangen, sie berichtigen, ihre Löschung verlangen, der Verarbeitung widersprechen, sie einschränken lassen oder ihre Übertragbarkeit beantragen, gemäß der Verordnung (EU) 2016/679 (DSGVO) und dem spanischen Datenschutzgesetz Ley Orgánica 3/2018. Es genügt eine E-Mail an <a href="mailto:info@deltha.eu">info@deltha.eu</a> von der Adresse, mit der du dich registriert hast. Wenn du nicht zufrieden bist, kannst du Beschwerde bei der spanischen Datenschutzbehörde Agencia Española de Protección de Datos (aepd.es) einlegen.</p>
<p>Rechtsgrundlage der Verarbeitung ist die Erbringung des Dienstes, den du mit deiner Registrierung oder Teilnahme anforderst, sowie unser berechtigtes Interesse daran, die Website sicher und im Rahmen des Gesetzes zu halten.</p>

<h2>Änderungen</h2>
<p>Wenn wir diese Richtlinie ändern, sagen wir es hier. Letzte Aktualisierung: September 2026.</p>
<p><i>Dies ist eine Übersetzung, die der Einfachheit halber bereitgestellt wird. Bei Abweichungen ist die spanische Fassung maßgeblich.</i></p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('que-es.it', 'Cos''è Deltha?', '<h1>Cos''è Deltha?</h1>

<p>{site} è tre cose insieme: <b>un aggregatore di notizie</b>, dove la gente invia link e decide con i propri voti cosa arriva in prima pagina; <b>un social network controalgoritmico</b>, senza una macchina che scelga per te cosa vedere; e <b>uno spazio di dibattito</b>, con discussioni che nascono, si accendono e scompaiono.</p>

<h2>Da dove viene</h2>
<p>Da cinque posti. Dal <a href="https://contralgoritmia.com/" rel="noopener">manifesto controalgoritmico</a> viene l''idea di fondo: recuperare il giudizio umano contro l''egemonia dell''algoritmo. Da <b>Menéame</b>, l''aggregatore: è la comunità a decidere cosa arriva in prima pagina. Da <b>X</b>, il design: una timeline pulita e veloce. Da <b>4chan</b>, l''effimero: le discussioni nascono, si accendono e scompaiono. E dal <b>modello di business di Jot Down</b>, il modo di reggersi: niente pubblicità programmatica né tracciamento, una comunità che sostiene ciò che legge.</p>

<h2>Come funziona</h2>
<p><b>È effimera.</b> Invii e commenti vengono archiviati dopo {ttl} ore. Conta quello che succede adesso, non quello che si accumula.</p>
<p><b>Non lascia tracce.</b> Niente cookie di tracciamento, niente pubblicità programmatica, niente terze parti. Puoi verificarlo aprendo gli strumenti del tuo browser.</p>
<p><b>È orizzontale.</b> Tutto si pubblica sotto un alias e il nome dell''autore compare solo dopo {gm} minuti, così si giudicano le parole prima di chi le dice.</p>
<p><b>Si vede dentro.</b> La sezione Processi mostra in diretta la coda, il δ di ogni invio e ogni voto, commento e prima pagina man mano che accadono.</p>
<p><b>Niente algoritmo nascosto.</b> Cosa arriva in prima pagina lo decide una formula pubblica, breve e visibile sotto ogni invio: il <b>δ</b>. È spiegata per intero in <a href="/algoritmo.php">Come funziona il δ</a>.</p>

<h2>L''aura</h2>
<p>Non c''è karma. C''è l''<b>aura</b>, e misura una cosa sola: il tuo impegno verso la comunità. Si guadagna partecipando e contribuendo, non piacendo: +{auraport} per ogni tuo invio che arriva in prima pagina, +{auraenv} per ogni invio o articolo, +{auracom} per ogni commento, al massimo +{auracap} al giorno. E scende: ogni giorno perdi {auradec}, fino al minimo di {aurastart}. Non si accumula: si mantiene. Misura chi c''è, non chi c''era.</p>
<p><b>Non dà mai un megafono.</b> L''aura non fa salire notizie né ordina commenti. Tutti vedono le stesse cose, nello stesso ordine. Si vede come una luce intorno al tuo alias, nient''altro. Apre soltanto delle porte: con più di {commaura} puoi creare comunità e modificare i commenti appena scritti.</p>

<h2>Cosa si può pubblicare?</h2>
<p><b>Tutto ciò che la legge consente.</b> Il limite non lo fissa una linea editoriale né un gusto: lo fissa la legge. Restano fuori gli insulti, i crimini d''odio, le minacce e tutto ciò che è reato. È tutto scritto nelle <a href="/normas.php">regole d''uso</a>.</p>
<p><b>Modera un''IA che vive sui nostri server.</b> Nessun invio né commento ne esce per essere revisionato: non ci sono terze parti a guardare cosa scrivi. Quando ha dubbi, decide una persona. Chiunque può segnalare un commento.</p>
<p><b>E c''è un indirizzo email per tutto il resto.</b> Lamentele, complimenti e proposte: <b>info@deltha.eu</b>.</p>

<h2>Temi e comunità</h2>
<p><b>I temi li stabilisce {site}.</b> Pochi e stabili: attualità, scienza, cultura… Ogni invio ne ha uno, e il tema può dare una piccola spinta al δ (un coefficiente pubblico).</p>
<p><b>Le comunità le crea la gente.</b> Con più di {commaura} di aura puoi aprirne fino a {commmax}: un nome, un colore e di cosa parla. Un invio può aggiungersi a una o più comunità, e chi l''ha creata (o un moderatore) se ne prende cura. Ogni comunità può scegliere uno stile proprio, che si applica alla sua pagina e alle sue discussioni. E tu puoi scegliere una comunità o un tema come pagina di ingresso.</p>

<h2>A modo tuo</h2>
<p><b>{skins} stili.</b> Stessa prima pagina, stessi numeri, pelle diversa: da rivista, da forum, da terminale, da manga, da fanzine… Si sceglie nella scheda Stile, e ognuno ha la versione chiara e scura.</p>
<p><b>Nella tua lingua e in tasca.</b> Otto lingue: spagnolo, catalano, basco, galiziano, inglese, francese, tedesco e italiano. Si installa come app sul telefono, con messaggi privati e chat tra utenti registrati, e puoi salvare gli invii nei preferiti. Niente di tutto questo cambia cosa vedi: solo come lo vedi.</p>

<h2>Fuori dai motori di ricerca</h2>
<p><b>Quello che succede su {site} resta su {site}.</b> Nessun invio, commento o profilo compare su Google o su altri motori di ricerca: tutte le pagine portano il marcatore <i>noindex</i>. Si lasciano vedere solo le pagine che spiegano il progetto, come questa.</p>
<p><b>A meno che non lo voglia tu.</b> Se scrivi un articolo, puoi spuntare la casella perché i motori di ricerca lo indicizzino. È il tuo testo e decidi tu; puoi cambiarlo quando lo modifichi.</p>
<p><b>E nessuna IA si addestra con quello che scrivi.</b> I crawler di intelligenza artificiale (quelli di OpenAI, Anthropic, Google, Meta, Common Crawl e compagnia) hanno l''ingresso vietato, anche negli articoli aperti ai motori di ricerca.</p>

<h2>Vuoi essere proprietario di {site}?</h2>
<p>{site} è un progetto aperto a tutti. Per diventarne comproprietario non ti chiediamo soldi: solo di usare la piattaforma. <b>Il 50 % della proprietà di {site} sarà ripartito tra tutti gli utenti registrati che avranno aura {auramax} il {auradl}.</b></p>
<p>Vuoi provarla? <a href="/registro.php">Registrati</a> o, se hai già un account, <a href="/login.php">accedi</a>.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('algoritmo.it', 'Come funziona il δ', '<h1>Come funziona il δ</h1>

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

<h2>Cosa il δ non fa</h2>
<p>Non impara da te. Non sa cosa ti piace. Non ti mostra una prima pagina diversa da quella del tuo vicino. Non premia chi ha più follower, perché qui nessuno ha follower. Non guarda a che ora entri di solito né quanto tempo passi a leggere. La stessa formula, gli stessi numeri, la stessa prima pagina per tutti.</p>

<h2>Cosa scegli tu</h2>
<p>L''unica cosa che cambia da una persona all''altra è <b>come si vede</b>, mai <b>cosa si vede</b>. Nel tuo profilo scegli lo <b>stile visivo</b> (diversi, dalla rivista alla fanzine, ognuno con versione chiara e scura), il <b>tema chiaro o scuro</b>, la <b>lingua</b> (spagnolo, catalano, basco, galiziano, inglese, francese, tedesco o italiano) e la pagina di ingresso. Una comunità può avere il suo stile, che vedrai nella sua pagina e nelle sue discussioni. {site} si installa come app sul telefono e ha messaggi privati tra utenti registrati. Niente di tutto questo altera la prima pagina né l''ordine di alcunché: la stessa formula, con un''altra pelle.</p>

<h2>L''aura</h2>
<p>Le persone non hanno un δ; hanno l''<b>aura</b>, che è un''altra cosa e ne misura una sola: l''impegno verso la comunità. Si guadagna partecipando e contribuendo (+{auraport} per ogni tuo invio che arriva in prima pagina, +{auraenv} per ogni invio o articolo, +{auracom} per ogni commento, con un tetto di +{auracap} al giorno) e si perde non partecipando ({auradec} ogni giorno, mai sotto {aurastart}). Il massimo è {auramax}.</p>
<p>L''aura <b>non dà mai un megafono</b>: non fa salire notizie, non ordina commenti, non pesa sul δ. Si vede come una luce intorno al tuo alias, e apre alcune porte di fiducia: con più di {commaura} di aura puoi creare comunità (fino a {commmax}), e con più di {editaura} puoi correggere i tuoi commenti per {editmin} minuti dopo averli pubblicati (il commento resta contrassegnato come modificato).</p>

<h2>I numeri di oggi</h2>
<p>Tutti i parametri di questa pagina sono quelli in vigore in questo momento su {site}; se il team li cambia, questa pagina cambia con loro. Ciclo ogni {cycle} minuti · {minage} minuti minimi in Nuove · δ minimo per la prima pagina {minpub} · decadimento {decay} ogni mezz''ora · velo di {gm} minuti · δ minimo per diventare candidata {mincand} · vita in prima pagina di {ttl} ore · scadenza senza prima pagina dopo {newttl} ore · comunità con aura maggiore di {commaura}, fino a {commmax} a persona.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('normas.it', 'Regole d''uso', '<h1>Regole d''uso</h1>

<h2>La regola</h2>
<p>Su <b>{site}</b> c''è posto per tutto ciò che la legge consente. Non c''è una linea editoriale né un elenco di temi vietati: si può dissentire, criticare duramente, fare ironia e sbagliare. L''unico limite è quello fissato dalla legislazione spagnola ed europea, ed è per questo che queste regole sono brevi.</p>

<h2>Cosa non ci sta</h2>
<p><b>Insultare e umiliare.</b> Si discute con le idee, non contro le persone. Insulti, umiliazioni e attacchi personali che non portano argomenti vengono rimossi.</p>
<p><b>Odiare.</b> Niente che inciti all''odio, alla discriminazione o alla violenza contro qualcuno per origine, sesso, orientamento, religione, disabilità o qualsiasi altra condizione.</p>
<p><b>Minacciare e molestare.</b> Niente minacce, niente inseguire un utente di discussione in discussione, niente campagne per mettere qualcuno alla gogna.</p>
<p><b>Pubblicare dati altrui.</b> Indirizzi, numeri di telefono, documenti, account privati, foto intime, luoghi di lavoro o qualsiasi dato che serva a localizzare o identificare una persona che non l''ha reso pubblico. Si chiama <i>doxxing</i> ed è la cosa più grave che si possa fare su {site}.</p>
<p><b>Diffondere contenuti illegali.</b> Materiale che sfrutti minori, apologia del terrorismo, istigazione a delinquere, contenuti che violino in modo evidente il diritto d''autore.</p>
<p><b>Spacciarsi per altri.</b> Fingersi un''altra persona, testata o istituzione.</p>
<p><b>Barare.</b> Account multipli per votare, pubblicità occulta, link ingannevoli e qualsiasi forma di manipolazione della prima pagina.</p>

<h2>Come si modera</h2>
<p>Chiunque può <b>segnalare</b> un commento dal commento stesso, senza bisogno di avere un account. Le segnalazioni le esaminano persone: moderatori volontari della comunità e il team di {site}. Inoltre, un''IA che vive sui nostri server (nessun testo ne esce) fa un primo filtro quando è attiva.</p>
<p>Quando un commento viola queste regole, non sparisce e basta: il suo testo viene sostituito da <i>«Commento eliminato per violazione delle regole d''uso»</i>, così la conversazione resta comprensibile e rimane traccia che lì c''era qualcosa che non doveva esserci. Se un''intera discussione è illegale, viene rimossa.</p>
<p>La moderazione a volte sbaglia. Se pensi che qualcosa di tuo sia stato rimosso senza motivo, scrivi a <a href="mailto:info@deltha.eu">info@deltha.eu</a> e lo ricontrolliamo.</p>

<h2>Conseguenze</h2>
<p>La prima volta, il commento viene rimosso. Se succede di nuovo, l''account può essere sospeso. I casi che possono costituire reato vengono conservati e messi a disposizione delle autorità se lo richiedono, come impone la legge.</p>

<h2>E il resto</h2>
<p>Tutto ciò che si pubblica qui è effimero: le discussioni vengono archiviate dopo {ttl} ore. Cura ciò che scrivi in quelle ore come se dovesse durare per sempre, perché per chi lo legge è così.</p>');
INSERT IGNORE INTO pages (slug, title, body) VALUES ('privacidad.it', 'Privacy e contatti', '<h1>Privacy e contatti</h1>

<h2>Chi c''è dietro</h2>
<p><b>{site}</b> è un servizio di <b>Jot Down Media Company S.L.</b>, NIF B88863592, con sede in Calle Palillos, núm. 6, puerta 36, 41500 Alcalá de Guadaíra (Siviglia), Spagna. È la titolare del trattamento dei dati descritti qui.</p>
<p>Per qualsiasi questione relativa ai tuoi dati, e anche per lamentele, complimenti e proposte, scrivi a <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>Cosa non facciamo</h2>
<p>Non usiamo cookie con chi non accede. Non ci sono tracker, né analytics di terze parti, né pubblicità programmatica, né risorse caricate da altri domini: tutto ciò che mostra questo sito esce dai nostri server. Puoi verificarlo con gli strumenti per sviluppatori del tuo browser. Non vendiamo né cediamo dati a nessuno.</p>

<h2>Quali dati trattiamo e perché</h2>
<p><b>Se ti limiti a leggere</b>, non trattiamo alcun tuo dato oltre ai log tecnici del server (indirizzo IP, data, pagina richiesta e browser), che esistono su qualsiasi server web, servono a mantenere il servizio e a individuare abusi, e vengono cancellati a breve. Per sapere quante persone ci sono sul sito in ogni momento conserviamo per qualche minuto un''impronta non reversibile della tua visita (calcolata a partire dall''IP, dal browser e dal giorno, e che non permette di risalire a essi); si cancella da sola dopo dieci minuti e non viene usata per nient''altro.</p>
<p><b>Se commenti senza account</b>, il tuo commento viene pubblicato sotto un alias generato da un''impronta non reversibile del tuo indirizzo IP, della discussione e di un segreto del server. Conserviamo quell''impronta troncata (perché il tuo alias sia lo stesso all''interno di una discussione), mai l''indirizzo IP.</p>
<p><b>Se crei un account</b>, conserviamo il tuo nome utente, il tuo indirizzo email e la tua password cifrata in modo irreversibile. L''email serve unicamente a gestire il tuo account; non inviamo newsletter né pubblicità. Puoi aggiungere una frase e una foto profilo; alle foto rimuoviamo i metadati (EXIF) al caricamento. Con un account viene creato un unico cookie tecnico di sessione, indispensabile per sapere che sei tu, che scompare quando esci. Conserviamo anche l''ora della tua ultima visita, che serve solo a contare quante persone sono connesse in ogni momento.</p>
<p><b>Se invii una notizia</b>, l''URL, il titolo, il sommario e la foto che fornisci vengono pubblicati. Le foto vengono ritagliate e private dei metadati sul server.</p>
<p><b>Finché l''accesso resta privato</b>, chi entra con la chiave di accesso riceve un cookie tecnico che contiene solo un''impronta di quella chiave. Smetterà di esistere quando apriremo il sito.</p>

<h2>Per quanto tempo</h2>
<p>{site} è effimera per scelta. Gli invii e i loro commenti vengono archiviati {ttl} ore dopo essere arrivati in prima pagina. Il tuo nome utente compare accanto a ogni contributo {gm} minuti dopo la pubblicazione; fino ad allora si vede solo l''alias. I dati del tuo account vengono conservati finché lo mantieni aperto.</p>

<h2>Moderazione</h2>
<p>La moderazione la fa un''intelligenza artificiale che gira sui nostri server: nessun invio né commento ne esce per essere esaminato da terze parti. Si filtra solo ciò che la legge non consente (insulti, crimini d''odio, minacce e, in generale, qualsiasi condotta criminale). Quando il sistema ha dubbi, decide una persona. Se pensi che una decisione sia stata ingiusta, scrivici.</p>

<h2>I tuoi diritti</h2>
<p>Puoi accedere ai tuoi dati, rettificarli, chiederne la cancellazione, opporti al loro trattamento, limitarlo o richiederne la portabilità, ai sensi del Regolamento (UE) 2016/679 (GDPR) e della Ley Orgánica 3/2018 (legge spagnola sulla protezione dei dati). Basta scrivere a <a href="mailto:info@deltha.eu">info@deltha.eu</a> dall''indirizzo email con cui ti sei registrato. Se non sei soddisfatto, puoi presentare reclamo all''Agencia Española de Protección de Datos (aepd.es), l''autorità spagnola per la protezione dei dati.</p>
<p>La base giuridica del trattamento è l''esecuzione del servizio che richiedi registrandoti o partecipando, e il nostro legittimo interesse a mantenere il sito sicuro e nel rispetto della legge.</p>

<h2>Modifiche</h2>
<p>Se modifichiamo questa informativa, lo diremo qui. Ultimo aggiornamento: settembre 2026.</p>
<p><i>Questa traduzione è fornita per comodità. In caso di discrepanza, prevale la versione spagnola.</i></p>');
