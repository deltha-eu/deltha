-- migra-139 (12/10/2026): turco (tr) como decimo idioma de la interfaz, pedido por Angel. Turco estandar (Türkiye Türkçesi),
-- que es tambien la lengua escrita de la comunidad turca de Alemania.
--  * Paginas en turco: que-es.tr, algoritmo.tr, normas.tr, privacidad.tr, empezar.tr y cookies.tr.
--    INSERT ... ON DUPLICATE KEY UPDATE: si se vuelve a aplicar, pisa las tr (no toca las demas).
--  * «Nueve idiomas» -> «Diez idiomas» y la lista de idiomas del algoritmo, en es/ca/eu/gl/en/fr/de/it/pt.
--    REPLACE: si el texto se retoco a mano en admin y ya no coincide, no cambia nada (revisar en admin > Paginas).
-- El diccionario de la interfaz va en lib/lang/tr.php y el idioma se da de alta en DDD_LANGS (lib/i18n.php).
-- users.lang y sources.lang son VARCHAR(5): no hace falta tocar el esquema.
SET NAMES utf8mb4;

INSERT INTO pages (slug, title, body) VALUES ('que-es.tr', 'Deltha nedir?', '<h1>Deltha nedir?</h1>

<p>{site}, <b>bağımsız yayınlar ve sesler için bir sosyal ağdır</b>. Arama sonuçlarından kayboldukça yitirdikleri görünürlüğü onlara geri vermek için doğdu. Her yayın ve her yazar kendi içeriğini yayımlar; burada da RSS ile geldiği gibi, en yeniden en eskiye doğru, kimsenin yerine karar veren bir algoritma olmadan okunur. Aynı zamanda çıkar gözeten önerilerden vazgeçen <b>algoritma karşıtı bir ağdır</b>; açılan, tartışılan ve kapanan konularıyla <b>bir tartışma alanıdır</b>; ve insanların bulduklarını paylaştığı, neyin ön sayfaya çıkacağına oylarıyla karar verdiği <b>bir bağlantı toplayıcısıdır</b>.</p>

<h2>Nereden geliyor</h2>
<p>Temeldeki fikir, yani algoritmanın hegemonyası karşısında insan ölçütünü geri kazanma fikri, <a href="https://contralgoritmia.com/" rel="noopener">algoritma karşıtı manifestodan</a> geliyor. Okuma biçimi <b>RSS</b> dünyasından: her site kendi içeriğini açık biçimde yayımlar, her okur da onu aracısız, sırasıyla ve filtresiz takip eder. Toplayıcı <b>Digg</b> geleneğinden geliyor: neyin ön sayfaya çıkacağına topluluğun karar vermesi alışkanlığı. <b>Reddit</b> tarafından, herkesin önem verdiği şeyin etrafında açıp bakımını üstlenebileceği topluluklar. <b>X</b> tarafından, temiz ve hızlı bir zaman akışı. <b>4chan</b> tarafından, doğan, tartışılan ve kapanan konuların geçiciliği. Ve <b>Jot Down iş modelinden</b>, okuduğunu ayakta tutan bir topluluk sayesinde programatik reklam ve izleme olmadan ayakta kalma biçimi.</p>

<h2>Nasıl çalışır</h2>
<p><b>İki ön sayfa ve bir Radar.</b> Gönderilen her şey <b>Radar</b> bölümüne girer. Oradan, oy alarak, iki ön sayfadan birine çıkar: gazetelerin, dergilerin ve diğer sitelerin haberleri için <b>Medya</b>, bültenler için <b>Substacks</b>. <b>Özgün yazılar</b>, yani topluluğun kendi yazdığı metinler, ayrı bir sekmede durur ve ön sayfa için yarışmaz.</p>
<p><b>Geçicidir ama silmez.</b> Bir gönderi ön sayfada {ttltxt} kaldığında, sohbetiyle birlikte arşivlenir: okunmaya devam edilebilir ama artık oy verilemez ve yorum yapılamaz. Radar bölümünden çıkamayanlar, gönderildikten {newttltxt} sonra arşivlenir. Önemli olan şimdiki sohbettir, birikip duran değil.</p>
<p><b>İz bırakmaz.</b> İzleme çerezi yok, programatik reklam yok, üçüncü taraf yok. Tarayıcının araçlarını açarak bunu kendin kontrol edebilirsin.</p>
<p><b>Yataydır.</b> Her şey bir takma adla yayımlanır ve yazarın adı ancak {gm} dakika sonra görünür; böylece sözler, onları söyleyenden önce değerlendirilir.</p>
<p><b>İçi görünür.</b> İşlemler bölümü Radar bölümünü, her gönderinin δ değerini ve her oyu, yorumu ve ön sayfaya çıkışı gerçekleştikleri anda canlı olarak gösterir.</p>
<p><b>Gizli algoritma yok.</b> Her ön sayfaya neyin çıkacağına herkese açık, kısa ve her gönderinin altında görünen bir formül karar verir: <b>δ</b>. Tamamı <a href="/algoritmo.php">δ nasıl çalışır</a> sayfasında açıklanıyor.</p>

<h2>RSS ile gelenler</h2>
<p><b>{site} da okur.</b> İnsanların gönderdiklerinin yanı sıra yayınların, bültenlerin (Substack ve başka sitelerdeki) ve diğer toplayıcıların yayımladıklarını RSS ile toplar ve kendi görünümüyle, en yenisi en üstte olacak şekilde gösterir. Algoritma yok, izleme yok: RSS, her sitenin sunabileceği açık bir biçimdir.</p>
<p><b>Kaynakların elinin altında.</b> Profilinde en fazla {mediamax} yayın ve {newsmax} bülten seçersin; seçmezsen öne çıkanları görürsün. Menéame gibi toplayıcılar, kendi oyları ve yorumlarıyla olduğu gibi gösterilir. Eksik bir şey mi var? {propaura} veya daha fazla aura ile bir yayın ya da bülten önerebilirsin; moderasyon da onu inceler.</p>
<p><b>Okumak oy vermek değildir.</b> RSS ile gelen hiçbir şey {site} üzerindeki Radar bölümüne kendiliğinden girmez. Her haberin onu Radar bölümüne göndermek için bir düğmesi vardır; oradan da her gönderinin izlediği yolu izler: oylar, δ ve döngü.</p>

<h2>Aura</h2>
<p>Karma yok. <b>Aura</b> var ve tek bir şeyi ölçer: topluluğa bağlılığını. Beğenilerek değil, burada olarak ve katkıda bulunarak kazanılır: ön sayfaya çıkan her gönderin için +{auraport}, her gönderi veya özgün yazı için +{auraenv}, her yorum için +{auracom}, günde en fazla +{auracap}. Ve düşer: her gün {auradec} kaybedersin, en az {aurastart} değerine kadar. Ama ulaştığın her tam sayı (6, 7, 8…) senin alt sınırın olarak kalır: girmediğin süre seni onun altına düşürmez. Birikmez: sürdürülür. Kimin burada olduğunu ölçer, kimin bir zamanlar burada olduğunu değil.</p>
<p><b>Asla megafon vermez.</b> Aura haberleri yükseltmez, yorumları da sıralamaz. Herkes aynı ön sayfaları aynı sırayla görür. Takma adının etrafında ışık olarak görünür, o kadar. Açtığı tek şey kapılardır: {commaura} üzerinde aura ile topluluk oluşturabilirsin; {editaura} üzerinde yeni yazdığın yorumlarını, {seditaura} üzerinde ise gönderilerini düzeltebilirsin.</p>

<h2>Neler yayımlanabilir?</h2>
<p><b>Yasanın izin verdiği her şey.</b> Sınırı bir yayın çizgisi ya da bir zevk koymaz: yasa koyar. Hakaretler, nefret suçları, tehditler ve suç olan her şey dışarıda kalır. Bunlar <a href="/normas.php">kullanım kurallarında</a> yazılıdır.</p>
<p><b>Moderasyonu, kendi yapay zekâmızın yardımıyla insanlar yapar.</b> Her şey anında yayımlanır. Sonra, etkinleştirildiğinde, kendi makinelerimizde çalışan bir yapay zekâ onu inceler: yazdıklarına bakan üçüncü taraf yoktur. Yapay zekâ asla bir şey silmez: şüpheli olanı işaretler ve en fazla geçici olarak gizler. Karar her zaman bir insana aittir. Herkes bir yorumu bildirebilir.</p>
<p><b>Geri kalan her şey için de bir e-posta adresi var.</b> Şikâyetler, tebrikler ve öneriler: <b>info@deltha.eu</b>.</p>

<h2>Konu başlıkları ve topluluklar</h2>
<p><b>Konu başlıklarını {site} belirler.</b> Az sayıda ve sabit: gündem, bilim, kültür… Her gönderi bir tanesini taşır ve konu başlığı δ değerine küçük bir itki verebilir (eklenen, herkese açık bir bonus).</p>
<p><b>Toplulukları insanlar oluşturur.</b> {commaura} üzerinde aura ile en fazla {commmax} topluluk açabilirsin: bir ad, bir renk ve neyle ilgili olduğu. Bir gönderi bir veya birkaç topluluğa katılabilir; topluluğa onu oluşturan kişi (ya da bir moderatör) göz kulak olur. Her topluluk, sayfasına ve konularına uygulanan kendi skin seçimini yapabilir. Sen de bir topluluğu ya da bir konu başlığını giriş sayfan olarak seçebilirsin.</p>

<h2>Kendi tarzınla</h2>
<p><b>{skins} skin.</b> Aynı ön sayfalar, aynı sayılar, farklı görünüm: dergi, forum, terminal, manga, fanzin… Skins sekmesinden seçilir ve her birinin açık ve koyu sürümü vardır.</p>
<p><b>Kendi dilinde ve cebinde.</b> On dil: İspanyolca, Katalanca, Baskça, Galiçyaca, İngilizce, Fransızca, Almanca, İtalyanca, Portekizce ve Türkçe. Telefona uygulama olarak kurulur, kayıtlı kullanıcılar arasında özel mesajlar ve Sohbet vardır; gönderileri de favorilerine kaydedebilirsin.</p>
<p><b>Herkesin olan ve senin olan.</b> İki ön sayfa ve Radar herkes için aynıdır, aynı sırayla. Kişiselleştirdiğin şey yoldur: hangi yayınların ve bültenlerin elinin altında olduğu ve {site} açıldığında bir ön sayfaya mı (en son açtığına), bir konu başlığına mı yoksa bir topluluğa mı girdiğin. Değişen, nereden geldiğindir; topluluğun neye karar verdiği değil.</p>

<h2>Arama motorlarının dışında</h2>
<p><b>{site} içinde olan, {site} içinde kalır.</b> Hiçbir gönderi, yorum ya da profil Google veya başka arama motorlarında görünmez: bütün sayfalar <i>noindex</i> işaretini taşır. Yalnızca bu sayfa gibi projeyi anlatan sayfalar görünür.</p>
<p><b>Sen istemediğin sürece.</b> Bir özgün yazı yazarsan, arama motorlarının onu dizine eklemesi için kutucuğu işaretleyebilirsin. Metin senin, karar da senin; düzenlerken değiştirilebilir.</p>
<p><b>Ve hiçbir yapay zekâ yazdıklarınla eğitilmez.</b> Yapay zekâ tarayıcılarının (OpenAI, Anthropic, Google, Meta, Common Crawl ve benzerlerininkilerin) girişi yasaktır; arama motorlarına açık özgün yazılarda bile.</p>

<h2>Bir haberi öne çıkar</h2>
<p>{site} ritmini bulana kadar, yönetici her gün üzerine konuşmaya değer bulduğu haberleri öne çıkarır: öne çıkarılan haber, bir haleyle, ön sayfasının en başında yer alır ve başka biri öne çıkarılana ya da kaldırılana kadar orada kalır; sonra tarihine göre ait olduğu yere döner.</p>
<p>Sen de auranı kullanarak yapabilirsin: öne çıkarmak ön sayfalarda {pincost} aura kullanır (haber {pinmin} dakika boyunca en başta yer alır), Özgün Yazılar bölümünde ise {pincostorig} ({pinhorig} saat); ve asla {aurafloor} değerinin altına inemezsin. Düğme, ne kadar kullandığı yanında yazılı olarak, ön sayfada olanların kartlarında ve özgün yazılarda görünür. Aynı anda bir tane öne çıkarılmış haberin olabilir; başka birini öne çıkarmak öncekinin yerini alır ve kullanılan aura geri alınmaz. Bekleyen birkaç tane varsa sırayla gider.</p>

<h2>Bir kullanıcıyı ghostlamak</h2>
<p>Biri seni rahatsız ediyorsa, onu profilinden ya da herhangi bir yorumundan ghostlayabilirsin. O andan itibaren onun için hep bir Ghost olursun: gönderilerinde, yorumlarında ve bahsetmelerde adını görmez, profilini göremez ve sana Sohbet üzerinden yazamaz. Sen de onu aynı şekilde görürsün: yorumları katlanmış, sıradan bir Ghost olarak; bir gün okumak istersen diye.</p>
<p>Kimseye bildirim gitmez. O, siteyi normal şekilde görmeye devam eder; yalnızca eskiden bir adın olduğu yerde, diğer pek çoğu gibi bir Ghost olur. Ghostlananların herkese açık listesi yoktur.</p>
<p>Ghostlamak oyları etkilemez: gönderiler herkesindir ve herkes onlara oy verebilir. Diğer kullanıcılar için de hiçbir şeyi silmez ya da gizlemez.</p>
<p>Kimi ghostladığını profilinden görebilir ve istediğin zaman geri alabilirsin.</p>

<h2>Oylar hakkında</h2>
<p>{site} üzerinde bir gönderiye verilen oy kesindir: verildikten sonra değiştirilemez ve yalnızca ilk dakikalarda aynı oka tekrar basılarak iptal edilebilir. Bir gönderinin ya da yorumun Ghost olarak göründüğü {gm} dakika boyunca onu kimin yazdığını kimse bilmez; dolayısıyla o süredeki oylar söyleyene değil, söylenene verilir. Sonradan değiştirilememeleri de bunun gerçekten böyle olmasını sağlar.</p>

<h2>{site} projesinin sahibi olmak ister misin?</h2>
<p>{site} herkese açık bir projedir. Ortak sahip olman için senden para istemiyoruz: yalnızca platformu kullanmanı. <b>{site} mülkiyetinin %50''si, {auradl} tarihinde aurası {auramax} olan tüm kayıtlı kullanıcılar arasında paylaştırılacak.</b></p>
<p>Denemek ister misin? <a href="/registro.php">Kaydol</a> ya da zaten bir hesabın varsa <a href="/login.php">giriş yap</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);

INSERT INTO pages (slug, title, body) VALUES ('algoritmo.tr', 'δ nasıl çalışır', '<h1>δ nasıl çalışır</h1>

<h2>Gizli algoritma yok</h2>
<p>{site} üzerinde ne göreceğine senin yerine karar veren bir makine yok. Ön sayfaya neyin çıkacağını herkese açık, kısa ve görünür bir formül belirler: Radar''daki her gönderinin altında, sayılarıyla ve o anki değeriyle gösterilir. Bu sayfa onu baştan sona açıklıyor. Onu anlarsan {site} platformunu da anlarsın.</p>

<h2>δ (delta)</h2>
<p>Her gönderinin bir <b>δ</b> değeri vardır: topluluğun onu ne kadar desteklediğini ve ne kadar süredir beklediğini özetleyen bir puan. Şöyle hesaplanır:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = √(sayılan oylar) + konu başlığı bonusu − azalma × beklenen yarım saatler</p>
<p><b>Oylar</b> insanların sesidir: kayıtlı her üye her gönderiye bir kez, olumlu ya da olumsuz oy verebilir. Oy kesindir: değiştirilemez; yalnızca ilk dakikalarda aynı oka yeniden basarak geri alınabilir. Kendi gönderine oy veremezsin. δ değerine olumlu ve olumsuz oyların farkı girer ve iki kural geçerlidir: birkaç hesap aynı gönderiye aynı bağlantıdan oy verirse tek oy sayılır (oyun bir izi saklanır, adres asla saklanmaz); bir hesabın oyları da hesap oluşturulduktan {quartxt} sonra sayılmaya başlar. Oylar ilk andan itibaren görünür; δ bekler.</p>
<p><b>Karekök</b> coşkuyu törpüler: dört oy 2 eder, dokuz oy 3, yüz oy 10. Bir avuç oyla ön sayfaya çıkılır; çok fazla oyla da diğerleri ezilmez. Negatif bir net sonuç aynı karekökle düşülür.</p>
<p><b>Konu başlığı bonusu</b> küçük bir editoryal itmedir ve {site} tarafından belirlenen tek parametredir: konu başlıklarının çoğu hiçbir şey eklemez; şu konu başlıkları ise destek almış her gönderisine bu bonusu ekler: {bonustemas}. Bonus toplanır, çarpılmaz: olumsuz oyları büyütmez, azalmayı da hızlandırmaz. Bonuslar herkese açıktır ve onları her formülde görürsün.</p>
<p><b>Eskilik</b> puan düşürür: beklenen her yarım saat için Medya''da {decay} puan, Substacks''te {decays} puan; süre gönderinin gönderildiği andan değil, ön sayfaya çıkabileceği andan (yani {minage} dakika sonra) itibaren sayılır. Medya''nın bugüne, Substacks''in ise her zamana ait olabilmesini sağlayan budur: iyi bir bülten gündemle birlikte eskimez ve saati daha yavaş işler.</p>
<h2>İki ön sayfa ve bir Radar</h2>
<p>Gönderilen her şey <b>Radar</b>''a girer. Oradan iki ön sayfadan birine ulaşabilir: gazetelerin, dergilerin ve diğer web sitelerinin haberleri için <b>Medya</b>, bültenler için <b>Substacks</b>. Bir gönderi substack.com adresine ya da {site} kataloğundaki bir bültene bağlantı veriyorsa Substacks''e gider; geri kalan her şey Medya''ya gider. Her gönderi yalnızca kendi ön sayfasındakilerle yarışır.</p>
<p><b>Özgün yazılar</b>, yani topluluğun kendi yazdığı metinler, Radar''dan geçmez ve ön sayfaya çıkmaz: tarihe göre sıralanan kendi sekmeleri vardır; orada okunur, oylanır ve yorumlanırlar.</p>

<h2>Döngü</h2>
<p>Her <b>{cycle} dakikada</b> bir döngü gerçekleşir; hep aynı şekilde, elle müdahale olmadan:</p>
<p><b>1. Radar''dan ön sayfaya.</b> Döngüler sırayla çalışır: biri Medya''da yayımlar, sonraki Substacks''te, ve böyle sürer. Her döngüde, sırası gelen ön sayfanın gönderileri arasından en az <b>{minage} dakikadır</b> Radar''da olan ve δ değeri en az <b>{minpub}</b> (Medya) veya <b>{minpubs}</b> (Substacks) olanlardan <b>yalnızca biri</b> yayımlanır ve bu gönderi <b>kurayla</b> seçilir: her adayın δ değeri kadar kura bileti vardır; dolayısıyla δ değeri en yüksek olan en olası adaydır ama garantisi yoktur, arkasından gelenler de bazen çıkar. En az <b>{minvotos} sayılan oy</b> gerekir; konu başlığı bonusu bunların hiçbirinin yerini tutmaz. Sırası gelen ön sayfaya uygun bir şey yoksa sıra diğerine geçer; onda da yoksa o döngüde hiçbir şey yayımlanmaz: ön sayfa sırf dolsun diye doldurulmaz. Asgari süre, bir gönderi yalnızca sakin bir anda geldiği için yükselmesin diye vardır; önce görülmesi ve oylanması gerekir.</p>
<p><b>Tam olarak ne zaman.</b> {cycle} dakikalık her dilimin içinde döngünün çalıştığı dakika değişir ve duyurulmaz; {site} yalnızca “şu saatten önce” der. Böylece kimse oylarını son saniyede salmak üzere saklayamaz: kazanmak isteyen süre tutmak yerine ikna etmek zorundadır. Üstelik bu bir kura olduğu için, yanındakinin onda bir puan önüne geçmek için son oyu ince ince ayarlamak da işe yaramaz.</p>
<p><b>2. Süre dolumu.</b> Ön sayfaya çıkamadan Radar''da <b>{newttltxt}</b> geçiren her şey arşivlenir: Radar da sonsuza kadar değildir.</p>
<p><b>3. Perde kalkar.</b> Herhangi bir gönderi ya da yorum yayımlandıktan {gm} dakika sonra yazarının takma adı yerini gerçek adına bırakır. Ondan önce herkes haleli bir takma addır: imza değil, sözler değerlendirilir.</p>
<p><b>4. Arşiv.</b> Ön sayfada {ttltxt} kalmış olan her şey arşivlenir. Okunmaya devam edebilir ama artık oylanmaz ve yorumlanmaz: konuşma neyse oydu.</p>
<p><b>İşlemler</b> bölümü bütün bunları canlı gösterir: δ değerine göre sıralanmış Radar, her gönderinin gideceği ön sayfa ve oraya ne zaman ulaşacağına dair bir tahminle birlikte; ayrıca her gönderiyi, oyu, yorumu ve ön sayfaya çıkışı gerçekleştiği anda gösteren bir akış.</p>

<h2>Konu başlıkları ve topluluklar</h2>
<p>Her gönderi iki kez sınıflandırılır. <b>Konu başlığını</b> {site} belirler: sayıları azdır, sabittirler (gündem, bilim, kültür…) ve her birinin herkese açık bir bonusu vardır (neredeyse her zaman sıfır). Her gönderinin bir konu başlığı olur.</p>
<p><b>Topluluğu</b> ise insanlar belirler. <b>{commaura} puandan fazla aura</b> sahibi herkes en çok <b>{commmax}</b> topluluk oluşturabilir: bir ad, bir renk ve ne hakkında olduğunu söyleyen bir cümle. Bir gönderi konu başlığının yanı sıra bir topluluğa da katılabilir; böylece bir grubun ilgisini çeken şeyler, {site} karar vermek zorunda kalmadan bir araya gelir. Bir topluluğu oluşturan kişi ona bakar: adını, açıklamasını, rengini ve görsel şablonunu düzenler; moderatörler de aynısını yapabilir ve gerekirse topluluğu gizleyebilir.</p>
<p>Topluluklar <b>δ değerine dokunmaz</b>: bonus vermez, hiçbir şeyi yükseltmez ya da düşürmez. Onlar bir mekândır, hoparlör değil. Yapabileceğin şey ise profilinde bir topluluğu (ya da bir konu başlığını) <b>giriş sayfası</b> olarak seçmektir: {site} açıldığında oraya varırsın; Medya ve Substacks sekmeleri de herkesin ön sayfaları olmayı sürdürür.</p>

<h2>RSS ile gelenler</h2>
<p>{site} ayrıca yayınların, bültenlerin ve diğer toplayıcıların yayımladıklarını RSS ile toplar. Orada <b>ne δ vardır ne döngü</b>: her kaynak yayımlanma sırasına göre, en yenisi en üstte olacak şekilde gösterilir; beğeniye ya da başarıya göre sıralanmaz. Toplayıcılarda kendi oyları ve yorumları görünür; bunlar onların topluluğuna aittir, {site} topluluğuna değil.</p>
<p>RSS ile gelenler <b>Radar''a kendiliğinden girmez</b>. Biri onu her haberdeki düğmeyle Radar''a gönderirse, o andan itibaren sıradan bir gönderidir: herkesinki gibi oylar, δ ve döngü.</p>

<h2>δ neleri yapmaz</h2>
<p>Senden öğrenmez. Neyi sevdiğini bilmez. Sana komşunkinden farklı bir ön sayfa göstermez. Daha çok takipçisi olanı ödüllendirmez, çünkü burada kimsenin takipçisi yoktur. Genellikle hangi saatte girdiğine ya da okumaya ne kadar zaman ayırdığına bakmaz. Herkes için aynı formül, aynı sayılar, aynı ön sayfalar.</p>

<h2>Senin seçtiklerin</h2>
<p>İki ön sayfa ve Radar <b>herkes için aynıdır</b>, aynı sırayla. Kişiden kişiye değişen şey <b>nasıl göründükleri</b> ve <b>nereden girdiğindir</b>. Görünüm için profilinde <b>görsel şablonu</b> ({skins} seçenek, dergiden fanzine, her birinin açık ve koyu sürümü var), <b>açık ya da koyu temayı</b> ve <b>dili</b> (İspanyolca, Katalanca, Baskça, Galiçyaca, İngilizce, Fransızca, Almanca, İtalyanca, Portekizce veya Türkçe) seçersin; bir topluluğun kendi şablonu olabilir, onu topluluğun sayfasında ve konularında görürsün.</p>
<p>Nereden gireceğin için ise <b>giriş sayfanı</b> (bir ön sayfa, bir konu başlığı ya da bir topluluk; ön sayfa seçersen en son baktığın açılır) ve <b>kaynaklarını</b> seçersin: her zaman elinin altında en çok {mediamax} yayın ve {newsmax} bülten. Bu, erişimi kişiselleştirir, değerlendirmeyi değil: seçtiklerinin hiçbiri ön sayfayı, herhangi bir şeyin sırasını ya da herhangi bir gönderinin δ değerini değiştirmez. {site} ayrıca telefona uygulama olarak kurulur ve kayıtlı üyeler arasında özel mesajlaşma sunar.</p>

<h2>Aura</h2>
<p>İnsanların δ değeri yoktur; <b>aura</b>ları vardır. Bu başka bir şeydir ve tek bir şeyi ölçer: topluluğa bağlılığı. Burada olarak ve katkı vererek kazanılır (ön sayfaya çıkan her gönderin için +{auraport}, her gönderi ya da özgün yazı için +{auraenv}, her yorum için +{auracom}; günlük üst sınır +{auracap}) ve burada olmayarak kaybedilir (her gün {auradec}, ama asla {aurastart} değerinin altına inmez). En yüksek değer {auramax} olur. Yine de ulaştığın her tam sayı (6, 7, 8…) senin alt sınırın olarak kalır: girmeden geçen zaman seni onun altına düşürmez.</p>
<p>Aura <b>asla hoparlör vermez</b>: haberleri yükseltmez, yorumları sıralamaz, δ değerinde ağırlığı yoktur. Takma adının çevresinde bir ışık olarak görünür ve bazı güven kapılarını açar: {commaura} puandan fazla aurayla topluluk oluşturabilirsin (en çok {commmax}); {editaura} puandan fazlasıyla kendi yorumlarını yayımladıktan sonraki {editmin} dakika boyunca düzeltebilirsin; {seditaura} puandan fazlasıyla da gönderilerini, ön sayfaya çıkmadıkları sürece {seditmin} dakika boyunca düzeltebilirsin (düzeltilen şey düzenlendi olarak işaretlenir). {propaura} veya daha fazla aurayla yayın ve bülten önerebilirsin.</p>

<h2>Bugünün sayıları</h2>
<p>Bu sayfadaki bütün parametreler şu anda {site} üzerinde yürürlükte olanlardır; ekip onları değiştirirse bu sayfa da onlarla birlikte değişir. Döngü her {cycle} dakikada bir, Medya ve Substacks arasında dönüşümlü, δ değerine göre kurayla · Radar''da en az {minage} dakika · ön sayfa için asgari δ {minpub} (Medya) ve {minpubs} (Substacks) · en az {minvotos} sayılan oy · beklenen her yarım saat için azalma {decay} (Medya) ve {decays} (Substacks) · oylar hesap oluşturulduktan {quartxt} sonra sayılır · bonus: {bonustemas} · {gm} dakikalık perde · ön sayfada ömür: {ttltxt} · Radar''da süre dolumu: {newttltxt} sonra · aurası {commaura} puandan fazla olanlar için topluluklar, kişi başına en çok {commmax}.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);

INSERT INTO pages (slug, title, body) VALUES ('normas.tr', 'Kullanım kuralları', '<h1>Kullanım kuralları</h1>

<h2>Kural</h2>
<p><b>{site}</b> içinde yasanın izin verdiği her şeye yer var. Ne bir yayın çizgisi ne de yasak konular listesi var: farklı düşünebilir, sert biçimde eleştirebilir, ironi yapabilir ve yanılabilirsin. Tek sınır İspanyol ve Avrupa mevzuatının çizdiği sınırdır; bu kurallar da bu yüzden kısa.</p>

<h2>Yeri olmayanlar</h2>
<p><b>Hakaret etmek ve aşağılamak.</b> Fikirlerle tartışılır, kişilere karşı değil. Hakaretler, küçük düşürmeler ve hiçbir gerekçe sunmayan karalamalar kaldırılır.</p>
<p><b>Nefret etmek.</b> Kökeni, cinsiyeti, yönelimi, dini, engelliliği ya da başka herhangi bir durumu nedeniyle birine karşı nefrete, ayrımcılığa ya da şiddete kışkırtan hiçbir şey.</p>
<p><b>Tehdit ve taciz etmek.</b> Ne tehdit, ne bir kullanıcıyı konudan konuya kovalamak, ne de birini hedef göstermeye yönelik kampanyalar.</p>
<p><b>Başkalarının verilerini yayımlamak.</b> Adresler, telefonlar, belgeler, özel hesaplar, mahrem fotoğraflar, iş yerleri ya da bunları kendisi açıklamamış bir kişinin yerini bulmaya veya kimliğini belirlemeye yarayan her türlü veri. Buna burada <i>doxxing</i> denir ve {site} üzerinde yapılabilecek en ağır şeydir.</p>
<p><b>Yasa dışı içerik yaymak.</b> Reşit olmayanları istismar eden materyal, terörü övme, suça teşvik, telif haklarını açıkça ihlal eden içerik.</p>
<p><b>Kimliğe bürünmek.</b> Kendini başka bir kişi, yayın ya da kurum gibi göstermek.</p>
<p><b>Hile yapmak.</b> Oy vermek için birden çok hesap, gizli reklam, yanıltıcı bağlantılar ve ön sayfayı manipüle etmenin her türlüsü.</p>

<h2>Moderasyon nasıl yapılır</h2>
<p>Herkes bir yorumu, hesabı olmasına gerek kalmadan, doğrudan o yorumun üzerinden <b>bildirebilir</b>. Bildirimleri insanlar inceler: topluluğun gönüllü moderatörleri ve {site} ekibi. Ayrıca kendi makinelerimizde çalışan bir yapay zekâ (hiçbir metin üçüncü taraflardan geçmez), etkinleştirildiğinde ilk elemeyi yapar.</p>
<p>Bir yorum bu kurallara uymadığında öylece ortadan kaybolmaz: metninin yerine <i>«Yorum, kullanım kurallarına uymadığı için silindi»</i> yazılır; böylece konuşma anlaşılır kalır ve orada olmaması gereken bir şeyin bulunduğu kayda geçer. Bir konunun tamamı yasa dışıysa kaldırılır.</p>
<p>Moderasyon bazen yanılır. Sana ait bir şeyin sebepsiz yere kaldırıldığını düşünüyorsan <a href="mailto:info@deltha.eu">info@deltha.eu</a> adresine yaz, yeniden inceleriz.</p>

<h2>Sonuçlar</h2>
<p>İlk seferde yorum kaldırılır. Tekrarlanırsa hesap askıya alınabilir. Suç oluşturabilecek durumlar saklanır ve yasanın zorunlu kıldığı üzere, talep etmeleri hâlinde yetkililerin kullanımına sunulur.</p>

<h2>Ve gerisi</h2>
<p>Burada yayımlanan her şey geçicidir: konular {ttl} saat sonra arşivlenir. O saatler boyunca yazdıklarına sonsuza dek kalacakmış gibi özen göster, çünkü okuyan için öyledir.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);

INSERT INTO pages (slug, title, body) VALUES ('privacidad.tr', 'Gizlilik ve iletişim', '<h1>Gizlilik ve iletişim</h1>

<h2>Arkasında kim var</h2>
<p><b>{site}</b>, vergi kimlik numarası (NIF) B88863592 olan ve Calle Palillos, núm. 6, puerta 36, 41500 Alcalá de Guadaíra (Sevilla), España adresinde yerleşik <b>Jot Down Media Company S.L.</b> şirketinin bir hizmetidir. Burada açıklanan verilerin işlenmesinde veri sorumlusu bu şirkettir.</p>
<p>Verilerinle ilgili her konuda, ayrıca şikâyet, tebrik ve önerilerin için <a href="mailto:info@deltha.eu">info@deltha.eu</a> adresine yaz.</p>

<h2>Yapmadıklarımız</h2>
<p>Oturum açmayan kişilerde çerez kullanmıyoruz. İzleyici yok, üçüncü taraf analitiği yok, programatik reklam yok, başka alan adlarından yüklenen kaynak yok: bu web sitesinin gösterdiği her şey kendi sunucularımızdan gelir. Bunu tarayıcının geliştirici araçlarıyla doğrulayabilirsin. Verileri kimseye satmıyor ve kimseye devretmiyoruz.</p>

<h2>Hangi verileri, ne amaçla işliyoruz</h2>
<p><b>Yalnızca okuyorsan</b>, sunucunun teknik kayıtları (IP adresi, tarih, istenen sayfa ve tarayıcı) dışında sana ait hiçbir veriyi işlemiyoruz; bu kayıtlar her web sunucusunda bulunur, hizmeti sürdürmeye ve kötüye kullanımları tespit etmeye yarar ve kısa bir süre içinde silinir. Sitede her an kaç kişinin bulunduğunu bilmek için ziyaretinin geri döndürülemez bir izini birkaç dakika boyunca saklıyoruz (IP, tarayıcı ve günden hesaplanır ve bunların geri elde edilmesine imkân vermez); on dakika sonra kendiliğinden silinir. Aynı günlük izle, kendi analitiğimiz için görüntülenen sayfaları sayıyoruz (günlük ziyaretçi, oturum ve sayfa sayıları; çerez ve üçüncü taraf hizmeti olmadan); iz her gün değiştiği için kimseyi bir günden ötekine takip etmeye imkân vermez.</p>
<p><b>Hesabın olmadan yorum yaparsan</b>, yorumun; IP adresinin, başlığın ve sunucuya ait bir sırrın geri döndürülemez bir izinden üretilen bir takma adla yayımlanır. Bu izin kısaltılmış hâlini saklıyoruz (takma adın bir başlık içinde aynı kalsın diye), IP adresini ise asla.</p>
<p><b>Bir hesap oluşturursan</b>, kullanıcı adını, e-posta adresini ve geri döndürülemez biçimde şifrelenmiş parolanı saklıyoruz. E-posta yalnızca hesabını yönetmek için kullanılır; bülten veya reklam göndermiyoruz. Bir cümle ve bir profil fotoğrafı ekleyebilirsin; fotoğrafların meta verilerini (EXIF) yüklenirken siliyoruz. Hesapla birlikte, sen olduğunu bilmek için zorunlu olan ve çıkış yaptığında kaybolan tek bir teknik oturum çerezi oluşturulur. Son ziyaretinin saatini de saklıyoruz; bu yalnızca her an kaç kişinin bağlı olduğunu saymaya yarar.</p>
<p><b>Bir haber gönderirsen</b>, sağladığın URL, başlık, spot ve fotoğraf yayımlanır. Fotoğraflar sunucuda kırpılır ve meta verileri silinir.</p>
<p><b>Erişim özel olduğu sürece</b>, erişim anahtarıyla giren kişi, yalnızca o anahtarın bir izini içeren teknik bir çerez alır. Siteyi açtığımızda bu çerez ortadan kalkacaktır.</p>

<h2>Ne kadar süreyle</h2>
<p>{site} tasarımı gereği geçicidir. Gönderiler ve yorumları, ön sayfaya ulaşmalarından {ttl} saat sonra arşivlenir. Kullanıcı adın, her katkının yanında yayımlanmasından {gm} dakika sonra görünür; o zamana kadar yalnızca takma ad görülür. Hesabına ait veriler, hesabı açık tuttuğun sürece saklanır.</p>

<h2>Moderasyon</h2>
<p>Moderasyonu kendi sunucularımızda çalışan bir yapay zekâ yapar: hiçbir gönderi veya yorum, üçüncü taraflarca incelenmek üzere bu sunucuların dışına çıkmaz. Yalnızca yasanın izin vermediği şeyler süzülür (hakaretler, nefret suçları, tehditler ve genel olarak suç teşkil eden her türlü davranış). Sistem tereddüt ettiğinde kararı bir insan verir. Bir kararın haksız olduğunu düşünüyorsan bize yaz.</p>

<h2>Hakların</h2>
<p>(AB) 2016/679 sayılı Tüzük (GDPR, Genel Veri Koruma Tüzüğü) ve 3/2018 sayılı Organik Kanun (Ley Orgánica 3/2018) uyarınca verilerine erişebilir, onları düzeltebilir, silmemizi isteyebilir, işlenmelerine itiraz edebilir, işlenmelerini kısıtlayabilir veya taşınabilirliklerini talep edebilirsin. Kaydolduğun e-posta adresinden <a href="mailto:info@deltha.eu">info@deltha.eu</a> adresine yazman yeterlidir. Sonuçtan memnun kalmazsan İspanya Veri Koruma Kurumu''na (Agencia Española de Protección de Datos, aepd.es) şikâyette bulunabilirsin.</p>
<p>İşlemenin hukuki dayanağı, kaydolurken veya katılırken talep ettiğin hizmetin ifası ile siteyi güvenli ve yasaya uygun tutmaktaki meşru menfaatimizdir.</p>

<h2>Değişiklikler</h2>
<p>Bu politikayı değiştirirsek burada bildireceğiz. Son güncelleme: Eylül 2026.</p>

<h2>YouTube videoları</h2>
<p>YouTube videoları, {site} üzerinde saklanan bir küçük resimle gösterilir: oynat düğmesine basmadığın sürece tarayıcın YouTube''a bağlanmaz. Bastığında video <code>youtube-nocookie.com</code> adresinden yüklenir ve o andan itibaren Google IP adresini alır ve kendi gizlilik politikasına göre tarayıcına veri kaydedebilir. Bu, sitedeki tek üçüncü taraf içeriğidir ve yalnızca sen istersen yüklenir. JavaScript olmadan bağlantı videoyu YouTube''da açar.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);

INSERT INTO pages (slug, title, body) VALUES ('empezar.tr', 'Nasıl başlanır', '<h1>Nasıl başlanır</h1>

<p>{site}, insanların okuduklarını —yayınlardaki makaleleri, Substack bültenlerini, videoları— paylaştığı ve yorumladığı bir sitedir. Burada senin yerine karar veren bir algoritma yok: ön sayfaya neyin çıkacağını oylar belirler; tek, herkese açık ve herkes için aynı olan bir kuralla. Substack''ten ya da sosyal ağlardan geliyorsan ve hiç forum kullanmadıysan, bilmen gerekenler bunlar. Beş dakikada okunur.</p>

<h2>Telefonda: uygulamayı yükle</h2>
<p>{site}, hiçbir mağazaya uğramadan bir uygulama gibi çalışır (o da gelecek). Tarayıcıdan on saniyede yüklenir ve kendi simgesiyle ana ekranda durur; tam ekran ve adres çubuğu olmadan. <b>Android</b>''de (Chrome, Edge, Brave, Samsung Internet): deltha.eu adresini aç, üç noktalı menüye ⋮ dokun ve <b>«Uygulamayı yükle»</b> ya da <b>«Ana ekrana ekle»</b> seçeneğini seç; bazen tarayıcı bunu altta bir bildirimle kendisi önerir. <b>iPhone ve iPad</b>''de Safari olmak zorunda: <b>paylaş</b> düğmesine (yukarı oklu kare) dokun, <b>«Ana ekrana ekle»</b> seçeneğine kadar in ve onayla. Bilgisayarda Chrome ve Edge, adres çubuğunun sağında bir yükleme simgesi gösterir. Bir gün sorun çıkarırsa ana ekrandan sil ve baştan yükle.</p>

<h2>Geri kalan her şey nerede</h2>
<p>Telefona yalnızca iki menü sığıyor: yukarıda <b>Medya · Radar · Substacks</b> ve <b>Özgün Yazılar</b>; aşağıda <b>Sohbet</b>, <b>Konu başlıkları</b>, <b>Topluluklar</b>, <b>Kullanıcılar</b>, <b>İşlemler</b>, <b>İstatistikler</b> ve <b>Mesajlar</b>. Gerisi <b>her sayfanın sonunda</b>: en alta kadar in; orada <b>{site} nedir?</b> sayfasının, bu rehberin, <b>algoritma karşıtı manifestonun</b>, <b>kuralların</b>, <b>gizlilik ve iletişim</b> sayfasının, <b>çerezlerle</b> ilgili bilgilerin, <b>δ nasıl çalışır</b> sayfasının, toplulukların ve takip edebileceğin <b>yayın</b> ve <b>substack</b> kataloğunun bağlantılarını bulursun. Bilgisayarda aynı menü sol sütunun altında sabit durur. Bir şey arıyor ve göremiyorsan, neredeyse kesin oradadır.</p>

<h2>Hesap gerekli mi?</h2>
<p>Okumak için hayır. Oy vermek, bağlantı göndermek, favorileri kaydetmek ve yayınları takip etmek için evet. <a href="/registro">Hesap oluştur</a> sayfasında bir dakikada açılır: bir kullanıcı adı, bir e-posta adresi ve bir parola. Sana etkinleştirme bağlantısı içeren bir e-posta gelir; göremiyorsan spam klasörüne bak, hiç gelmezse <a href="/reenviar">yeniden gönder</a> sayfasından bir tane daha iste. Telefon numarası da başka bir şey de istemiyoruz.</p>

<h2>Ön sayfalar</h2>
<p>Yukarıda üç tane var: <b>Medya</b> (basın ve web siteleri), ortada <b>Radar</b> ve <b>Substacks</b> (bültenler). Medya ve Substacks, oyların süzgecinden çoktan geçmiş olanların ön sayfalarıdır; her birinin kendi kuyruğu ve kendi temposu vardır, birindeki hiçbir şey ötekine karışmaz. Girdiğinde gördüğün, en son açtığın sayfadır.</p>

<h2>Radar</h2>
<p>Her şeyin içinden geçtiği ön sayfadır. Birinin gönderdiği her şey önce orada görünür ve oylama orada yapılır: neyin yükseleceğini etkilemek istiyorsan yerin Radar''dır. Her {cycle} dakikada bir, sırası gelen ön sayfanın (bir kez Medya, bir kez Substacks) en çok destek alan gönderisi oraya geçer; yeter ki asgari oy sayısına ulaşmış olsun. Her gönderinin altında tam hesabı (<b>δ</b>) görürsün: kaç oy aldığı, konu başlığının ne eklediği ve zamanın ne kadar düşürdüğü. Başka hiçbir şey yok; hepsi <a href="/algoritmo">δ nasıl çalışır</a> sayfasında anlatılıyor.</p>

<h2>Oy vermek</h2>
<p>Her gönderide bir yukarı ok, bir de aşağı ok vardır. Yukarı «bu ön sayfayı hak ediyor» demektir; aşağı ise «bu hak etmiyor». Kişi ve gönderi başına bir oy. Yanılırsan ilk dakikalarda aynı oka bir daha bas, oy iptal olur. Kendi gönderilerine oy verme: mümkün değil.</p>

<h2>Bağlantı göndermek</h2>
<p><b>+</b> düğmesi (sağ altta ya da menüde <b>Gönder</b>). Makalenin, bültenin ya da videonun adresini yapıştır; {site} başlığı, girişi ve fotoğrafı doldurur, istersen sen de düzeltirsin. Bir <b>konu başlığı</b> seç ve gönder. Radar''a girer, gerisine insanlar karar verir. Bağlantılar izleme kodları olmadan gider: onları biz çıkarıyoruz. Biri daha önce göndermişse seni onun konusuna götürür. Bağlantısız, kendine ait söyleyecek bir şeyin varsa o bir <a href="/articulo">Özgün Yazı</a>''dır: kendi sekmesinde okunan, oylanan ve yorumlanan, en fazla bin kelimelik bir metin.</p>

<h2>Yorum yapmak</h2>
<p>Her gönderi bir <b>konu</b> açar. Başlığa (ya da yorum sayısına) dokun; en altta yazma kutusu var. Belirli birine cevap vermek için onun yorumunun altındaki <b>Yanıtla</b>''yı kullan: cevabın onunkine bağlı kalır. Yorumlarda bir <b>güneş</b> (beğendim) ve bir <b>gözyaşı</b> (beğenmedim) bulunur; ön sayfayı değiştirmezler, yalnızca neyin katkı sağladığını ve neyin sağlamadığını söylerler. Konular {ttl} saat sonra arşivlenir: okunabilirler ama artık onlara yazılamaz. Burada sohbet bilerek geçicidir.</p>

<h2>Ghost modu</h2>
<p>Gönderi yaptığında, oy verdiğinde ya da yorum yazdığında ilk {gm} dakika boyunca adın görünmez: bir takma ad görünür. O süre geçince adın ortaya çıkar. Böylece ilk yargılanan şey kimin söylediği değil, ne söylediğin olur. Biri sana çekilmez geliyorsa profilinde <b>Ghostla</b> var: kimse fark etmeden onu görmeyi bırakırsın.</p>

<h2>Sohbet</h2>
<p>Ne olursa olsun, {site} dahil, her şey hakkında konuşmak için tek bir kısa mesaj akışıdır. 123 numaralı mesajı alıntılamak için <b>#123</b> yaz (bağlantıya dönüşür), birinden bahsetmek için de <b>@ad</b> yaz. <b>Konulara göre</b> her mesajı cevaplarıyla birlikte gruplar; <b>Zamana göre</b> onları geliş sırasıyla gösterir. Bir şey çalışmıyorsa ya da nasıl yapıldığını bilmiyorsan orada sor: orası aynı zamanda yardım kanalıdır ve megafonla işaretlenmiş mesajlar ekibin tüm topluluğa yaptığı duyurulardır.</p>

<h2>Yayınlarım ve Substack''lerim</h2>
<p><a href="/medios">Medya</a> sayfasında RSS''li kaynakların kataloğu var. Okuduklarında <b>Takip et</b>''e bas; son haberleri seni sol sütunda ve <b>Son</b> bölümünde bekler; oradan <b>Radar''a gönder</b> onları tek tıkla oyuna sokar. Bültenin ya da yayının eksikse aynı sayfadan öner.</p>

<h2>Aura</h2>
<p>Yavaş büyüyen bir sayı biçimindeki itibarındır: gönderi yaptığında, yorum yazdığında ve özellikle gönderdiğin şey ön sayfaya çıktığında artar; hiçbir şey yapmadığında ise her gün biraz azalır. Ama ulaştığın her tam sayı (6, 7, 8…) asgari değerin olarak kalır: siteye girmeden geçen zaman seni onun altına düşürmez. Aura ile bazı şeylerin kilidi açılır (örneğin bir haberi birkaç dakikalığına ön sayfada <b>Öne çıkar</b>, bu {pincost} aura kullanır; asla {aurafloor} değerinin altına düşmezsin). Peşinden koşmana gerek yok: kendiliğinden gelir.</p>

<h2>Zevkine göre</h2>
<p><a href="/perfil">Profilinde</a> fotoğrafı, dili (dokuz tane), açık ya da koyu temayı, <b>skin</b>''i (birkaç tane var: klasik forum, basın, koyu…) ve sütunları kaldırıp yalnızca okumayı bırakan <b>dikkat dağıtmayan modu</b> seçersin. Hesabın olmadan da skin''i, temayı ve dili menüden değiştirebilirsin: tarayıcında saklanırlar, o kadar.</p>

<h2>Yapmadıklarımız</h2>
<p>Programatik reklam yok, izleyici yok, üçüncü taraf çerezi yok, gördüklerini sıralayan hiçbir gizli ölçüt de yok. Birlikte yaşama kuralları <a href="/normas">Kullanım kuralları</a> sayfasında; neleri sakladığımız ve bize nasıl yazacağın ise <a href="/privacidad">Gizlilik ve iletişim</a> sayfasında. Orada olmaması gereken bir şey görürsen <b>bildir</b> her gönderinin, yorumun ve mesajın altındadır.</p>

<p>Hepsi bu. Gerisi kullandıkça öğrenilir. <a href="/registro">Hesabını oluştur</a> ya da zaten varsa <a href="/login">giriş yap</a>.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);

INSERT INTO pages (slug, title, body) VALUES ('cookies.tr', 'Çerezler ve JavaScript', '<h1>Çerezler ve JavaScript</h1>

<p>Bu sayfa, {site} sitesinin tarayıcında sakladığı her şeyin ve orada çalıştırdığı kodun eksiksiz dökümüdür. Üçüncü taraflara ait hiçbir şey yoktur; yalnızca sen istersen devreye giren tek bir istisna dışında: YouTube oynatıcısı (bu sayfanın sonunda). Bunun dışında, dışarıdan gelen ne bir çerez, ne bir betik, ne de bir yazı tipi vardır. Bu döküm elle ve herkesin gözü önünde güncel tutulur; tarayıcının araçlarını açarak her şeyi doğrulayabilirsin.</p>

<h2>Çerezler</h2>
<p>Yedi tane; hepsi bize ait ve hiçbiri seni izlemek için değil. <b>Oturum açmayan ve hiçbir şey seçmeyen kişi hiçbirini almaz.</b></p>
<div class="tabla"><table>
<tr><th>Çerez</th><th>Ne zaman oluşturulur</th><th>Ne kadar sürer</th><th>Ne için</th></tr>
<tr><td><code>ddd_s</code></td><td>oturum açıldığında</td><td>30 gün, her ziyarette yenilenir</td><td>oturumu açık tutmak</td></tr>
<tr><td><code>ddd_k</code></td><td>site özel moddayken anahtar doğru girildiğinde</td><td>30 gün</td><td>senden anahtarı yeniden istememek</td></tr>
<tr><td><code>ddd_skin</code></td><td>hesabın olmadan bir kaplama (skin) seçtiğinde</td><td>1 yıl</td><td>kaplamayı hatırlamak</td></tr>
<tr><td><code>ddd_theme</code></td><td>hesabın olmadan açık veya koyu seçtiğinde</td><td>1 yıl</td><td>görünümü hatırlamak</td></tr>
<tr><td><code>ddd_lang</code></td><td>hesabın olmadan dil seçtiğinde</td><td>1 yıl</td><td>dili hatırlamak</td></tr>
<tr><td><code>ddd_srclang</code></td><td>hesabın olmadan «Yalnızca kendi dilimdeki kaynaklar» kutusunu işaretlediğinde</td><td>1 yıl</td><td>o kutuyu hatırlamak</td></tr>
<tr><td><code>ddd_portada</code></td><td>Medya veya Substack''ler bölümünü açtığında</td><td>1 yıl</td><td>seni en son gördüğün ön sayfaya geri götürmek</td></tr>
</table></div>
<p>Hepsi <code>HttpOnly</code> (JavaScript bunları okuyamaz), <code>SameSite=Lax</code> (başka sitelere gitmezler) ve bağlantı HTTPS olduğunda <code>Secure</code> olarak işaretlenir. Hesabın varsa tercihler profilinde saklanır ve bu çerezler silinir. Çıkış yaptığında oturum çerezi silinir.</p>

<h2>Tarayıcında kalan diğer şeyler</h2>
<ul>
<li><b>Bir konum notu</b> (<code>sessionStorage</code>): oy verirken, kaydederken veya yorum yaparken, seni oraya geri götürmek için nerede kaldığın not edilir. Kullanıldığında silinir ve 15 saniye sonra geçerliliğini yitirir.</li>
<li><b>Kendi dosyalarımızın bir önbelleği</b> (service worker): stil sayfası, logolar, simgeler ve bağlantı yokken görünen sayfa. İçerik kesinlikle yok: gönderiler, yorumlar ve fotoğraflar her zaman sunucudan istenir.</li>
</ul>
<p><code>localStorage</code> veya tarayıcının herhangi bir veritabanı kullanılmaz.</p>

<h2>JavaScript</h2>
<p>Bize ait dokuz dosya, toplam yaklaşık 10 KB, bu aynı alan adından sunulur. Üçüncü taraflara ait hiçbir kitaplık yok. <b>JavaScript olmadan site eksiksiz çalışır</b>: aynı şekilde okunur, oy verilir, yorum yapılır, gönderi yapılır ve dil ya da kaplama değiştirilir.</p>
<div class="tabla"><table>
<tr><th>Dosya</th><th>Nerede</th><th>Ne yapar</th></tr>
<tr><td><code>register.js</code></td><td>tüm sayfalar</td><td>kendi dosyalarımızın önbelleğini çalıştırır</td></tr>
<tr><td><code>tabs.js</code></td><td>tümü</td><td>ekran dar olduğunda üstteki sekmeleri sığdırır</td></tr>
<tr><td><code>scroll.js</code></td><td>tümü</td><td>oy verdikten, kaydettikten veya yorum yaptıktan sonra seni bulunduğun noktaya geri götürür</td></tr>
<tr><td><code>escribir.js</code></td><td>tümü</td><td>yanıtlarken imleci alıntının arkasına koyar ve sayfanın yeniden yüklenmesinin yazmakta olduğunu silip götürmesini önler</td></tr>
<tr><td><code>avisos.js</code></td><td>yalnızca oturum açıkken</td><td>zil simgesindeki sayıyı her dakika günceller</td></tr>
<tr><td><code>share.js</code></td><td>tümü</td><td>«paylaş»: mobil cihazın menüsünü açar veya bağlantıyı kopyalar</td></tr>
<tr><td><code>sw.js</code></td><td>arka planda</td><td>kendi dosyalarımızı saklar ve bağlantın kesildiğinde bir sayfa gösterir</td></tr>
<tr><td><code>video.js</code></td><td>tümü</td><td>bir YouTube videosunun küçük resmine basıldığında oynatıcıyı onun yerine koyar</td></tr>
<tr><td><code>pw.js</code></td><td>parola içeren formlar</td><td>yazdığın parolayı görme düğmesi</td></tr>
</table></div>
<p>JavaScript''in kendi başına yaptığı tek istek bildirim sayısı isteğidir ve bu aynı siteye gider. Buradan dışarı hiçbir şey çıkmaz.</p>

<h2>Olmayanlar</h2>
<ul>
<li>Ne Google Analytics, ne pikseller, ne sosyal ağ düğmeleri, ne reklamlar, ne de başkalarına ait gömülü haritalar.</li>
<li>Hiçbir reklam profili, satılan veya devredilen hiçbir veri.</li>
<li>Ziyaretleri saymak için hiçbir çerez: ziyaretler günlük bir izle sayılır; bu, bağlantının ve tarayıcının her gün değişen ve geriye dönülemeyen, geri döndürülemez bir özetidir. IP adresin asla saklanmaz. Bu, <a href="/privacidad.php">Gizlilik ve iletişim</a> sayfasında anlatılmıştır.</li>
</ul>

<h2>YouTube videoları</h2>
<p>YouTube videoları, {site} üzerinde saklanan bir küçük resimle gösterilir: oynat düğmesine basmadığın sürece tarayıcın YouTube''a bağlanmaz. Bastığında video <code>youtube-nocookie.com</code> adresinden yüklenir ve o andan itibaren Google IP adresini alır ve kendi gizlilik politikasına göre tarayıcına veri kaydedebilir. Bu, sitedeki tek üçüncü taraf içeriğidir ve yalnızca sen istersen yüklenir. JavaScript olmadan bağlantı videoyu YouTube''da açar.</p>')
ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);

UPDATE pages SET body = REPLACE(body, 'Nueve idiomas: español, catalán, euskera, gallego, inglés, francés, alemán, italiano y portugués.', 'Diez idiomas: español, catalán, euskera, gallego, inglés, francés, alemán, italiano, portugués y turco.') WHERE slug IN ('que-es', 'algoritmo');
UPDATE pages SET body = REPLACE(body, 'italiano o portugués)', 'italiano, portugués o turco)') WHERE slug IN ('que-es', 'algoritmo');
UPDATE pages SET body = REPLACE(body, 'Nou idiomes: castellà, català, basc, gallec, anglès, francès, alemany, italià i portuguès.', 'Deu idiomes: castellà, català, basc, gallec, anglès, francès, alemany, italià, portuguès i turc.') WHERE slug IN ('que-es.ca', 'algoritmo.ca');
UPDATE pages SET body = REPLACE(body, 'italià o portuguès)', 'italià, portuguès o turc)') WHERE slug IN ('que-es.ca', 'algoritmo.ca');
UPDATE pages SET body = REPLACE(body, 'Neun Sprachen: Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch, Italienisch und Portugiesisch.', 'Zehn Sprachen: Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch, Italienisch, Portugiesisch und Türkisch.') WHERE slug IN ('que-es.de', 'algoritmo.de');
UPDATE pages SET body = REPLACE(body, 'Italienisch oder Portugiesisch)', 'Italienisch, Portugiesisch oder Türkisch)') WHERE slug IN ('que-es.de', 'algoritmo.de');
UPDATE pages SET body = REPLACE(body, 'Nine languages: Spanish, Catalan, Basque, Galician, English, French, German, Italian and Portuguese.', 'Ten languages: Spanish, Catalan, Basque, Galician, English, French, German, Italian, Portuguese and Turkish.') WHERE slug IN ('que-es.en', 'algoritmo.en');
UPDATE pages SET body = REPLACE(body, 'Italian or Portuguese)', 'Italian, Portuguese or Turkish)') WHERE slug IN ('que-es.en', 'algoritmo.en');
UPDATE pages SET body = REPLACE(body, 'Bederatzi hizkuntza: gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana, italiera eta portugesa.', 'Hamar hizkuntza: gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana, italiera, portugesa eta turkiera.') WHERE slug IN ('que-es.eu', 'algoritmo.eu');
UPDATE pages SET body = REPLACE(body, 'italiera edo portugesa)', 'italiera, portugesa edo turkiera)') WHERE slug IN ('que-es.eu', 'algoritmo.eu');
UPDATE pages SET body = REPLACE(body, 'Neuf langues : espagnol, catalan, basque, galicien, anglais, français, allemand, italien et portugais.', 'Dix langues : espagnol, catalan, basque, galicien, anglais, français, allemand, italien, portugais et turc.') WHERE slug IN ('que-es.fr', 'algoritmo.fr');
UPDATE pages SET body = REPLACE(body, 'italien ou portugais)', 'italien, portugais ou turc)') WHERE slug IN ('que-es.fr', 'algoritmo.fr');
UPDATE pages SET body = REPLACE(body, 'Nove idiomas: español, catalán, éuscaro, galego, inglés, francés, alemán, italiano e portugués.', 'Dez idiomas: español, catalán, éuscaro, galego, inglés, francés, alemán, italiano, portugués e turco.') WHERE slug IN ('que-es.gl', 'algoritmo.gl');
UPDATE pages SET body = REPLACE(body, 'italiano ou portugués)', 'italiano, portugués ou turco)') WHERE slug IN ('que-es.gl', 'algoritmo.gl');
UPDATE pages SET body = REPLACE(body, 'Nove lingue: spagnolo, catalano, basco, galiziano, inglese, francese, tedesco, italiano e portoghese.', 'Dieci lingue: spagnolo, catalano, basco, galiziano, inglese, francese, tedesco, italiano, portoghese e turco.') WHERE slug IN ('que-es.it', 'algoritmo.it');
UPDATE pages SET body = REPLACE(body, 'italiano o portoghese)', 'italiano, portoghese o turco)') WHERE slug IN ('que-es.it', 'algoritmo.it');
UPDATE pages SET body = REPLACE(body, 'Nove idiomas: espanhol, catalão, basco, galego, inglês, francês, alemão, italiano e português.', 'Dez idiomas: espanhol, catalão, basco, galego, inglês, francês, alemão, italiano, português e turco.') WHERE slug IN ('que-es.pt', 'algoritmo.pt');
UPDATE pages SET body = REPLACE(body, 'italiano ou português)', 'italiano, português ou turco)') WHERE slug IN ('que-es.pt', 'algoritmo.pt');
