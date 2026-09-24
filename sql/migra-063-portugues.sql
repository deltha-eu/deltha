-- Portugues (pt) como noveno idioma de la interfaz (24/09/2026).
--  * Paginas explicativas en portugues neutro (PT/BR): que-es.pt, algoritmo.pt, normas.pt, privacidad.pt.
--    INSERT ... ON DUPLICATE KEY UPDATE: si se vuelve a aplicar, pisa las pt (no toca las demas).
--  * «Ocho idiomas» -> «Nueve idiomas» y la lista de idiomas del algoritmo, en las 16 filas es/ca/eu/gl/en/fr/de/it.
--    REPLACE: si el texto se retoco a mano en admin y ya no coincide, no cambia nada (revisar en admin > Paginas).
-- users.lang y sources.lang ya son VARCHAR(5): no hace falta tocar el esquema.
SET NAMES utf8mb4;

UPDATE pages SET body = REPLACE(body, 'Ocho idiomas: español, catalán, euskera, gallego, inglés, francés, alemán e italiano.', 'Nueve idiomas: español, catalán, euskera, gallego, inglés, francés, alemán, italiano y portugués.') WHERE slug IN ('que-es','algoritmo');
UPDATE pages SET body = REPLACE(body, '(español, catalán, euskera, gallego, inglés, francés, alemán o italiano)', '(español, catalán, euskera, gallego, inglés, francés, alemán, italiano o portugués)') WHERE slug IN ('que-es','algoritmo');
UPDATE pages SET body = REPLACE(body, 'Eight languages: Spanish, Catalan, Basque, Galician, English, French, German and Italian.', 'Nine languages: Spanish, Catalan, Basque, Galician, English, French, German, Italian and Portuguese.') WHERE slug IN ('que-es.en','algoritmo.en');
UPDATE pages SET body = REPLACE(body, '(Spanish, Catalan, Basque, Galician, English, French, German or Italian)', '(Spanish, Catalan, Basque, Galician, English, French, German, Italian or Portuguese)') WHERE slug IN ('que-es.en','algoritmo.en');
UPDATE pages SET body = REPLACE(body, 'Vuit idiomes: castellà, català, basc, gallec, anglès, francès, alemany i italià.', 'Nou idiomes: castellà, català, basc, gallec, anglès, francès, alemany, italià i portuguès.') WHERE slug IN ('que-es.ca','algoritmo.ca');
UPDATE pages SET body = REPLACE(body, '(castellà, català, basc, gallec, anglès, francès, alemany o italià)', '(castellà, català, basc, gallec, anglès, francès, alemany, italià o portuguès)') WHERE slug IN ('que-es.ca','algoritmo.ca');
UPDATE pages SET body = REPLACE(body, 'Zortzi hizkuntza: gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana eta italiera.', 'Bederatzi hizkuntza: gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana, italiera eta portugesa.') WHERE slug IN ('que-es.eu','algoritmo.eu');
UPDATE pages SET body = REPLACE(body, '(gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana edo italiera)', '(gaztelania, katalana, euskara, galegoa, ingelesa, frantsesa, alemana, italiera edo portugesa)') WHERE slug IN ('que-es.eu','algoritmo.eu');
UPDATE pages SET body = REPLACE(body, 'Oito idiomas: español, catalán, éuscaro, galego, inglés, francés, alemán e italiano.', 'Nove idiomas: español, catalán, éuscaro, galego, inglés, francés, alemán, italiano e portugués.') WHERE slug IN ('que-es.gl','algoritmo.gl');
UPDATE pages SET body = REPLACE(body, '(español, catalán, éuscaro, galego, inglés, francés, alemán ou italiano)', '(español, catalán, éuscaro, galego, inglés, francés, alemán, italiano ou portugués)') WHERE slug IN ('que-es.gl','algoritmo.gl');
UPDATE pages SET body = REPLACE(body, 'Huit langues : espagnol, catalan, basque, galicien, anglais, français, allemand et italien.', 'Neuf langues : espagnol, catalan, basque, galicien, anglais, français, allemand, italien et portugais.') WHERE slug IN ('que-es.fr','algoritmo.fr');
UPDATE pages SET body = REPLACE(body, '(espagnol, catalan, basque, galicien, anglais, français, allemand ou italien)', '(espagnol, catalan, basque, galicien, anglais, français, allemand, italien ou portugais)') WHERE slug IN ('que-es.fr','algoritmo.fr');
UPDATE pages SET body = REPLACE(body, 'Acht Sprachen: Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch und Italienisch.', 'Neun Sprachen: Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch, Italienisch und Portugiesisch.') WHERE slug IN ('que-es.de','algoritmo.de');
UPDATE pages SET body = REPLACE(body, '(Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch oder Italienisch)', '(Spanisch, Katalanisch, Baskisch, Galicisch, Englisch, Französisch, Deutsch, Italienisch oder Portugiesisch)') WHERE slug IN ('que-es.de','algoritmo.de');
UPDATE pages SET body = REPLACE(body, 'Otto lingue: spagnolo, catalano, basco, galiziano, inglese, francese, tedesco e italiano.', 'Nove lingue: spagnolo, catalano, basco, galiziano, inglese, francese, tedesco, italiano e portoghese.') WHERE slug IN ('que-es.it','algoritmo.it');
UPDATE pages SET body = REPLACE(body, '(spagnolo, catalano, basco, galiziano, inglese, francese, tedesco o italiano)', '(spagnolo, catalano, basco, galiziano, inglese, francese, tedesco, italiano o portoghese)') WHERE slug IN ('que-es.it','algoritmo.it');

INSERT INTO pages (slug, title, body) VALUES ('que-es.pt', 'O que é Deltha?', '<h1>O que é Deltha?</h1>

<p>{site} é quatro coisas ao mesmo tempo: <b>um agregador de notícias</b>, onde as pessoas enviam links e decidem com os seus votos o que chega à capa; <b>um leitor de fontes</b>, que recolhe por RSS o que publicam meios, newsletters e outros agregadores e o mostra, sem algoritmo, do mais recente ao mais antigo; <b>uma rede social contralgorítmica</b>, sem máquina que escolha em seu lugar o que ver; e <b>um espaço para o debate</b>, com conversas que se abrem, se discutem e se fecham.</p>

<h2>De onde vem</h2>
<p>De cinco lugares. Do <a href="https://contralgoritmia.com/" rel="noopener">manifesto contralgorítmico</a> vem a ideia de fundo: recuperar o critério humano frente à hegemonia do algoritmo. Do <b>Menéame</b>, o agregador: a comunidade decide o que chega à capa. Do <b>X</b>, o design: uma linha do tempo limpa e rápida. Do <b>4chan</b>, a efemeridade: as conversas nascem, são discutidas e fecham. E do <b>modelo de negócio da Jot Down</b>, a forma de se sustentar: sem publicidade programática nem rastreamento, uma comunidade que sustenta o que lê.</p>

<h2>Como funciona</h2>
<p><b>É passageira, mas não apaga.</b> Quando um envio completa {ttl} horas na capa, é arquivado com a sua conversa: ainda pode ser lido, mas já não recebe votos nem comentários. O que não chega à capa é arquivado {newttl} horas depois do envio. O que importa é a conversa de agora, não a que se acumula.</p>
<p><b>Não deixa marcas.</b> Sem cookies de rastreamento, sem publicidade programática, sem terceiros. Para comprovar, basta abrir as ferramentas do navegador.</p>
<p><b>É horizontal.</b> Tudo é publicado sob um alias e o nome do autor só aparece passados {gm} minutos, para que as palavras sejam julgadas antes de quem as diz.</p>
<p><b>Tudo à vista.</b> Em Processos, é possível ver em tempo real a fila, o δ de cada envio e cada voto, comentário e publicação na capa à medida que acontecem.</p>
<p><b>Sem algoritmo oculto.</b> O que chega à capa é decidido por uma fórmula pública, curta e visível sob cada envio: o <b>δ</b>. Está explicada por inteiro em <a href="/algoritmo.php">Como funciona o δ</a>.</p>

<h2>O que chega por RSS</h2>
<p><b>{site} também lê.</b> Além do que as pessoas enviam, recolhe por RSS o que publicam meios, newsletters (do Substack e de outros sites) e outros agregadores, e mostra tudo com a sua própria aparência, o mais recente primeiro. Sem algoritmo e sem rastreamento: o RSS é um formato aberto que qualquer site pode oferecer.</p>
<p><b>As suas fontes, à mão.</b> No perfil, escolha até {mediamax} meios e {newsmax} newsletters; sem escolha, aparecem os destaques. Os agregadores, como o Menéame, são mostrados tal como são, com os seus próprios votos e comentários. Falta alguma coisa? Com {propaura} de aura ou mais, é possível propor um meio ou uma newsletter, e a moderação analisa a proposta.</p>
<p><b>Ler não é votar.</b> Nada do que chega por RSS entra sozinho na fila de {site}. Cada notícia tem um botão que a envia para Novas, e a partir daí segue o caminho de qualquer envio: votos, δ e ciclo.</p>

<h2>A aura</h2>
<p>Não há karma. Há <b>aura</b>, e mede uma única coisa: o compromisso com a comunidade. Ganha-se com presença e contribuição, não com popularidade: +{auraport} por cada envio próprio que chega à capa, +{auraenv} por cada envio ou artigo, +{auracom} por cada comentário, no máximo +{auracap} por dia. E diminui: menos {auradec} por dia, nunca abaixo de {aurastart}. Não se acumula: mantém-se. Mede quem está, não quem esteve.</p>
<p><b>Nunca dá megafone.</b> A aura não sobe notícias nem ordena comentários. Todas as pessoas veem a mesma capa, na mesma ordem. Aparece como uma luz em volta do alias, e nada mais. A única coisa que abre são portas: com mais de {commaura}, é possível criar comunidades; com mais de {editaura}, corrigir os próprios comentários recém-escritos, e com mais de {seditaura}, os próprios envios.</p>

<h2>O que se pode publicar?</h2>
<p><b>Tudo o que a lei permite.</b> O limite não é posto por uma linha editorial nem por um gosto: é posto pela lei. Ficam de fora os insultos, os crimes de ódio, as ameaças e tudo o que seja crime. Está tudo nas <a href="/normas.php">normas de uso</a>.</p>
<p><b>Quem modera é uma IA que vive nos nossos servidores.</b> Nenhum envio ou comentário sai deles para ser analisado: ninguém de fora lê o que é escrito aqui. Na dúvida, decide uma pessoa. Qualquer pessoa pode denunciar um comentário.</p>
<p><b>E há um e-mail para todo o resto.</b> Queixas, elogios e propostas: <b>info@deltha.eu</b>.</p>

<h2>Temas e comunidades</h2>
<p><b>Os temas são definidos por {site}.</b> Poucos e estáveis: atualidade, ciência, cultura… Cada envio leva um, e o tema pode dar um pequeno empurrão ao δ (um coeficiente público).</p>
<p><b>As comunidades são criadas pelas pessoas.</b> Com mais de {commaura} de aura, é possível abrir até {commmax}: um nome, uma cor e o assunto. Um envio pode pertencer a uma ou a várias, e quem a criou (ou um moderador) cuida dela. Cada comunidade pode escolher um modelo próprio, que se aplica à sua página e às suas conversas. E é possível escolher uma comunidade ou um tema como página de entrada.</p>

<h2>À sua maneira</h2>
<p><b>{skins} modelos.</b> A mesma capa, os mesmos números, outra pele: de revista, de fórum, de terminal, de manga, de fanzine… Escolha na página Modelos; cada um tem versão clara e escura.</p>
<p><b>No seu idioma e no bolso.</b> Nove idiomas: espanhol, catalão, basco, galego, inglês, francês, alemão, italiano e português. Pode ser instalado como app no telefone, com mensagens privadas e chat entre membros, e os envios podem ser guardados nos favoritos.</p>
<p><b>O que é de todos e o que é seu.</b> A capa, as candidatas e as novas são as mesmas para todas as pessoas, na mesma ordem. O que é personalizável é o caminho: que meios e newsletters ficam à mão e se, ao abrir {site}, a entrada é pela capa, por um tema ou por uma comunidade. Muda o caminho de chegada, não o que a comunidade decide.</p>

<h2>Fora dos motores de busca</h2>
<p><b>O que acontece em {site} fica em {site}.</b> Nenhum envio, comentário ou perfil aparece no Google nem em outros motores de busca: todas as páginas levam a marca <i>noindex</i>. Só ficam visíveis as páginas que explicam o projeto, como esta.</p>
<p><b>A não ser por vontade própria.</b> Quem escreve um artigo pode marcar a caixa para que os motores de busca o indexem. O texto é seu e a decisão também; pode ser alterada ao editar o artigo.</p>
<p><b>E nenhuma IA é treinada com o que se escreve aqui.</b> Os rastreadores de inteligência artificial (os da OpenAI, Anthropic, Google, Meta, Common Crawl e companhia) têm a entrada vedada, mesmo nos artigos abertos aos motores de busca.</p>

<h2>Quer ser proprietário de {site}?</h2>
<p>{site} é um projeto aberto a todos. Para ser coproprietário, não pedimos dinheiro: apenas que use a plataforma. <b>50 % da propriedade de {site} será repartida entre todos os membros que tenham aura {auramax} em {auradl}.</b></p>
<p>Quer experimentar? <a href="/registro.php">Crie uma conta</a> ou, se já tiver conta, <a href="/login.php">entre</a>.</p>') ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('algoritmo.pt', 'Como funciona o δ', '<h1>Como funciona o δ</h1>

<h2>Sem algoritmo oculto</h2>
<p>Em {site} não há uma máquina que decida o que cada pessoa vê. O que chega à capa é decidido por uma fórmula pública, curta e visível: aparece sob cada envio em Candidatas e Novas, com os seus números e o seu valor nesse momento. Esta página explica a fórmula por inteiro. Quem a entende, entende {site}.</p>

<h2>O δ (delta)</h2>
<p>Cada envio tem um <b>δ</b>: uma pontuação que resume quanto apoio recebe da comunidade e há quanto tempo espera. O cálculo é este:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votos positivos − votos negativos) × coeficiente do tema − {decay} × meias horas desde o envio</p>
<p><b>Os votos</b> são a voz das pessoas: cada membro pode votar uma vez em cada envio, a favor ou contra, e mudar de opinião. Não é possível votar nos próprios envios.</p>
<p><b>O coeficiente do tema</b> é um pequeno empurrão editorial e é o único parâmetro definido por {site}: a maioria dos temas vale 1,00; cultura, ciência ou relatos valem 1,10, ou seja, 10 % a mais por voto. Os coeficientes são públicos e aparecem em cada fórmula.</p>
<p><b>A antiguidade</b> desconta: {decay} pontos por cada meia hora desde o envio. É isso que faz com que {site} seja de hoje e não de sempre: uma notícia com dois votos que está há um dia na fila vale menos do que uma recém-chegada com os mesmos votos. Nada dura para sempre na fila.</p>

<h2>O ciclo</h2>
<p>A cada <b>{cycle} minutos</b> acontece um ciclo, sempre igual, sem exceções nem intervenção manual:</p>
<p><b>1. De Novas para Candidatas.</b> Entre os envios com pelo menos <b>{minage} minutos</b> em Novas, sobe <b>apenas um</b>: o de maior δ, desde que o seu δ seja de pelo menos <b>{mincand}</b>. Sem votos não há subida, e se nenhum envio chega lá, nesse ciclo nada sobe. O tempo mínimo existe para que um envio não suba só por ter chegado quando havia pouco movimento; tem de ser visto e votado antes. O que passa <b>{newttl} horas</b> sem chegar à capa é arquivado: a fila também não é para sempre.</p>
<p><b>2. De Candidatas para a Capa.</b> Entre todas as candidatas, é publicada na capa <b>a de maior δ</b>, desde que o seu δ seja de pelo menos <b>{minpub}</b>. Se nenhuma chega lá, nesse ciclo nada é publicado. A capa não é preenchida só por preencher.</p>
<p><b>3. Cai o véu.</b> Passados {gm} minutos da publicação de qualquer envio ou comentário, o alias do autor dá lugar ao seu nome. Antes disso, todos são alias com um halo: são julgadas as palavras, não a assinatura.</p>
<p><b>4. Arquivo.</b> O que está há {ttl} horas na capa é arquivado. Ainda pode ser lido, mas já não recebe votos nem comentários: a conversa foi o que foi.</p>
<p>A área <b>Processos</b> mostra tudo isto em tempo real: a fila ordenada por δ com uma estimativa de quando sobe cada envio, e um fluxo com cada envio, voto, comentário e publicação na capa à medida que acontecem.</p>

<h2>Temas e comunidades</h2>
<p>Cada envio é classificado duas vezes. <b>O tema</b> é definido por {site}: são poucos, estáveis (atualidade, ciência, cultura…) e cada um tem o seu coeficiente público. Cada envio leva sempre um tema.</p>
<p><b>A comunidade</b> é definida pelas pessoas. Quem tiver mais de <b>{commaura} de aura</b> pode criar até <b>{commmax}</b>: um nome, uma cor e uma frase que diga do que se trata. Um envio pode pertencer a uma comunidade além do seu tema, e assim fica reunido o que interessa a um grupo sem que {site} tenha de decidir. Quem cria uma comunidade cuida dela: edita o nome, a descrição, a cor e o modelo visual; os moderadores podem fazer o mesmo e, se necessário, ocultá-la.</p>
<p>As comunidades <b>não mexem no δ</b>: não dão coeficiente, não sobem nem descem nada. São um lugar, não um megafone. O que é possível é escolher uma comunidade (ou um tema) como <b>página de entrada</b> no perfil: ao abrir {site}, é essa a página que aparece, e Início continua a mostrar a capa de todos.</p>

<h2>O que chega por RSS</h2>
<p>{site} também recolhe por RSS o que publicam meios, newsletters e outros agregadores. Aí <b>não há δ nem ciclo</b>: cada fonte é mostrada por ordem de publicação, o mais recente primeiro, sem ordenar por gostos nem por sucesso. Nos agregadores aparecem os seus próprios votos e comentários, que são os da sua comunidade, não os de {site}.</p>
<p>O que chega por RSS <b>não entra sozinho na fila</b>. Se alguém o envia para Novas com o botão de cada notícia, a partir daí é mais um envio: votos, δ e ciclo, como todos.</p>

<h2>O que o δ não faz</h2>
<p>Não aprende com o seu comportamento. Não conhece os seus gostos. Não mostra uma capa diferente da do vizinho. Não premia quem tem mais seguidores, porque aqui ninguém tem seguidores. Não olha para o horário em que costuma entrar nem para o tempo que dedica à leitura. A mesma fórmula, os mesmos números, a mesma capa para todos.</p>

<h2>O que cada pessoa escolhe</h2>
<p>A capa, as candidatas e as novas são <b>as mesmas para todos</b>, na mesma ordem. O que muda de uma pessoa para outra é <b>como aparecem</b> e <b>por onde se entra</b>. Para a aparência, escolha no perfil o <b>modelo visual</b> ({skins}, da revista ao fanzine, cada um com versão clara e escura), o <b>tema claro ou escuro</b> e o <b>idioma</b> (espanhol, catalão, basco, galego, inglês, francês, alemão, italiano ou português); uma comunidade pode ter o seu próprio modelo, visível na sua página e nas suas conversas.</p>
<p>Para a entrada, escolha a <b>página de entrada</b> (a capa, um tema ou uma comunidade) e as <b>suas fontes</b>: até {mediamax} meios e {newsmax} newsletters sempre à mão. Isso personaliza o acesso, não o juízo: nada do que se escolhe altera a capa, a ordem de nada nem o δ de nenhum envio. {site} pode ainda ser instalado como aplicação no telefone e tem mensagens privadas entre membros.</p>

<h2>A aura</h2>
<p>As pessoas não têm δ; têm <b>aura</b>, que é outra coisa e mede uma só: o compromisso com a comunidade. Ganha-se com presença e contribuição (+{auraport} por cada envio próprio que chega à capa, +{auraenv} por cada envio ou artigo, +{auracom} por cada comentário, com um teto de +{auracap} por dia) e perde-se com a ausência ({auradec} por dia, nunca abaixo de {aurastart}). O máximo é {auramax}.</p>
<p>A aura <b>nunca dá megafone</b>: não sobe notícias, não ordena comentários, não pesa no δ. Aparece como uma luz em volta do alias e abre algumas portas de confiança: com mais de {commaura} de aura, é possível criar comunidades (até {commmax}); com mais de {editaura}, corrigir os próprios comentários durante {editmin} minutos após a publicação, e com mais de {seditaura}, os próprios envios durante {seditmin} minutos enquanto não chegarem à capa (o que for corrigido fica marcado como editado). Com {propaura} ou mais, é possível propor meios e newsletters.</p>

<h2>Os números de hoje</h2>
<p>Todos os parâmetros desta página são os que estão em vigor neste momento em {site}; se mudarem, esta página muda com eles. Ciclo a cada {cycle} minutos · {minage} minutos mínimos em Novas · δ mínimo para a capa {minpub} · decaimento de {decay} por meia hora · véu de {gm} minutos · δ mínimo para ser candidata {mincand} · vida na capa de {ttl} horas · expiração sem capa às {newttl} horas · comunidades com aura superior a {commaura}, até {commmax} por pessoa.</p>') ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);

INSERT INTO pages (slug, title, body) VALUES ('normas.pt', 'Normas de uso', '<h1>Normas de uso</h1>

<h2>A regra</h2>
<p>Em <b>{site}</b> cabe tudo o que a lei permite. Não há linha editorial nem lista de temas proibidos: é possível discordar, criticar com dureza, ironizar e errar. O único limite é o que marca a legislação espanhola e europeia, e por isso estas normas são curtas.</p>

<h2>O que não cabe</h2>
<p><b>Insultar e ofender.</b> A discussão é com as ideias, não contra as pessoas. Os insultos, as humilhações e as desqualificações que não trazem nenhum argumento são retirados.</p>
<p><b>Odiar.</b> Nada que incite ao ódio, à discriminação ou à violência contra alguém pela sua origem, sexo, orientação, religião, deficiência ou qualquer outra condição.</p>
<p><b>Ameaçar e assediar.</b> Nem ameaças, nem perseguir um membro de conversa em conversa, nem campanhas para expor seja quem for.</p>
<p><b>Publicar dados de outras pessoas.</b> Endereços, telefones, documentos, contas privadas, fotos íntimas, locais de trabalho ou qualquer dado que sirva para localizar ou identificar uma pessoa que não o tornou público. É o chamado <i>doxxing</i> e é a falta mais grave que se pode cometer em {site}.</p>
<p><b>Difundir conteúdo ilegal.</b> Material que explore menores, apologia do terrorismo, incitação ao crime, conteúdo que viole de forma evidente os direitos de autor.</p>
<p><b>Usurpar identidades.</b> Fazer-se passar por outra pessoa, meio de comunicação ou instituição.</p>
<p><b>Fazer jogo sujo.</b> Contas múltiplas para votar, publicidade disfarçada, links enganosos e qualquer forma de manipular a Capa.</p>

<h2>Como funciona a moderação</h2>
<p>Qualquer pessoa pode <b>denunciar</b> um comentário a partir do próprio comentário, sem necessidade de ter conta. As denúncias são analisadas por pessoas: moderadores voluntários da comunidade e os responsáveis de {site}. Além disso, uma IA que funciona nos nossos servidores (nenhum texto sai deles) faz uma primeira filtragem quando está ativada.</p>
<p>Quando um comentário não cumpre estas normas, não desaparece simplesmente: o seu texto é substituído por <i>«Comentário eliminado por não cumprir as normas de uso»</i>, de modo que a conversa continua compreensível e fica claro que ali havia algo que não devia estar. Se uma conversa inteira for ilegal, é retirada.</p>
<p>A moderação às vezes erra. Se achar que algo seu foi retirado sem motivo, escreva para <a href="mailto:info@deltha.eu">info@deltha.eu</a> e analisamos o caso.</p>

<h2>Consequências</h2>
<p>Na primeira vez, o comentário é retirado. Se voltar a acontecer, a conta pode ser suspensa. Os casos que possam constituir crime são conservados e postos à disposição das autoridades, se estas o solicitarem, como a lei obriga.</p>

<h2>E o resto</h2>
<p>Tudo o que é publicado aqui dura pouco: as conversas são arquivadas após {ttl} horas. Escreva nessas horas como se o texto fosse durar para sempre, porque, para quem o lê, dura.</p>') ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
INSERT INTO pages (slug, title, body) VALUES ('privacidad.pt', 'Privacidade e quem somos', '<h1>Privacidade e quem somos</h1>

<h2>Quem está por trás</h2>
<p><b>{site}</b> é um serviço da <b>Jot Down Group Media Company</b>, NIF B88863592, com sede na Calle Palillos, n.º 6, porta 36, 41500 Alcalá de Guadaíra (Sevilha), Espanha. É a responsável pelo tratamento dos dados aqui descritos.</p>
<p>Para qualquer assunto relativo aos seus dados, e também para queixas, elogios e propostas, escreva para <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</p>

<h2>O que não fazemos</h2>
<p>Não usamos cookies com quem não inicia sessão. Não há rastreadores, nem análise de terceiros, nem publicidade programática, nem recursos carregados de outros domínios: tudo o que este site mostra sai dos nossos próprios servidores. Basta abrir as ferramentas de desenvolvimento do navegador para comprovar. Não vendemos nem cedemos dados a ninguém.</p>

<h2>Que dados tratamos e para quê</h2>
<p><b>Se só lê</b>, não tratamos nenhum dado seu além dos logs técnicos do servidor (endereço IP, data, página pedida e navegador), que existem em qualquer servidor web, servem para manter o serviço e identificar abusos, e são apagados pouco tempo depois. Para saber quantas pessoas há no site a cada momento, guardamos durante alguns minutos uma impressão digital irreversível da sua visita (calculada a partir do IP, do navegador e do dia, e que não permite recuperar esses dados); é apagada automaticamente passados dez minutos e não serve para mais nada.</p>
<p><b>Se comentar sem conta</b>, o seu comentário é publicado sob um alias gerado a partir de uma impressão digital irreversível do seu endereço IP, da conversa e de um segredo do servidor. Guardamos essa impressão truncada (para que o seu alias seja o mesmo dentro de uma conversa), nunca o endereço IP.</p>
<p><b>Se criar uma conta</b>, guardamos o nome da conta, o seu e-mail e a sua senha, cifrada de forma irreversível. O e-mail serve unicamente para gerir a sua conta; não enviamos newsletters nem publicidade. É possível acrescentar uma frase e uma foto de perfil; as fotos perdem os metadados (EXIF) ao serem enviadas. Com conta, é criado um único cookie técnico de sessão, imprescindível para manter a sessão iniciada, que desaparece ao sair. Guardamos também a hora da sua última visita, que só serve para contar quantas pessoas estão online a cada momento.</p>
<p><b>Se enviar uma notícia</b>, o link, o título, o resumo e a foto que fornecer são publicados. As fotos são recortadas e os seus metadados são eliminados no servidor.</p>
<p><b>Enquanto o acesso for privado</b>, quem entrar com a chave de acesso recebe um cookie técnico que contém apenas uma impressão digital dessa chave. Esse cookie deixará de existir quando abrirmos o site ao público.</p>

<h2>Durante quanto tempo</h2>
<p>{site} foi feita para ser passageira. Os envios e os comentários correspondentes são arquivados {ttl} horas depois de chegarem à Capa. O nome da sua conta aparece junto a cada contribuição {gm} minutos depois de publicada; até lá, só se vê o alias. Os dados da sua conta são conservados enquanto a mantiver ativa.</p>

<h2>Moderação</h2>
<p>A moderação é feita por uma inteligência artificial que funciona nos nossos próprios servidores: nenhum envio ou comentário sai deles para ser analisado por terceiros. Só se filtra o que a lei não permite (insultos, crimes de ódio, ameaças e, em geral, qualquer conduta criminosa). Quando o sistema tem dúvidas, decide uma pessoa. Se achar que uma decisão foi injusta, escreva-nos.</p>

<h2>Os seus direitos</h2>
<p>Pode consultar os seus dados, retificá-los, pedir que os apaguemos, opor-se ao seu tratamento, limitá-lo ou solicitar a sua portabilidade, nos termos do Regulamento (UE) 2016/679 (RGPD) e da Lei Orgânica espanhola 3/2018. Basta escrever para <a href="mailto:info@deltha.eu">info@deltha.eu</a> a partir do e-mail com que criou a conta. Se a resposta não for satisfatória, pode apresentar reclamação à Agencia Española de Protección de Datos (aepd.es).</p>
<p>A base jurídica do tratamento é a execução do serviço pedido ao criar conta ou participar, e o nosso interesse legítimo em manter o site seguro e dentro da lei.</p>

<h2>Alterações</h2>
<p>Se alterarmos esta política, avisaremos aqui. Última atualização: setembro de 2026.</p>
<p><i>Esta é uma tradução oferecida por comodidade. Em caso de discrepância, prevalece a versão em espanhol.</i></p>') ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body);
