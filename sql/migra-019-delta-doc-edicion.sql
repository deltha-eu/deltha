-- Pagina «Como funciona el delta», edicion de comentarios propios (aura > comment_edit_min_aura, hasta comment_edit_minutes) y marca de editado.
SET NAMES utf8mb4;
ALTER TABLE comments ADD COLUMN edited_at DATETIME DEFAULT NULL AFTER created;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('comment_edit_minutes',  '10', 'Minutos tras publicar en los que el autor puede editar su comentario'),
('comment_edit_min_aura', '10', 'Aura minima (estricta: mayor que) para poder editar los comentarios propios');

INSERT IGNORE INTO pages (slug, title, body) VALUES ('algoritmo', 'Cómo funciona el δ', '<h1>Cómo funciona el δ</h1>

<h2>Sin algoritmo oculto</h2>
<p>En {site} no hay una máquina que decida por ti qué ver. Lo que llega a portada lo decide una fórmula pública, corta y visible: se muestra bajo cada envío en Candidatas y Nuevas, con sus números. Esta página la explica entera. Si la entiendes, entiendes {site}.</p>

<h2>El δ (delta)</h2>
<p>Cada envío tiene un <b>δ</b>: una puntuación que resume cuánto lo apoya la comunidad y cuánto tiempo lleva esperando. Se calcula así:</p>
<p style="font-family:ui-monospace,Menlo,monospace;padding:10px 14px;border:1px solid currentColor;border-radius:8px;display:inline-block">δ = (votos positivos − votos negativos) × coeficiente del tema − {decay} × medias horas desde el envío</p>
<p><b>Los votos</b> son la voz de la gente: cada registrado puede votar una vez cada envío, a favor o en contra, y cambiar de opinión. No se puede votar lo propio.</p>
<p><b>El coeficiente del tema</b> es un pequeño empujón editorial y es el único parámetro que pone {site}: la mayoría de los temas valen 1,00; cultura, ciencia o relatos valen 1,10, es decir, un 10 % más por voto. Los coeficientes son públicos y los verás en cada fórmula.</p>
<p><b>La antigüedad</b> resta: {decay} puntos por cada media hora que pasa desde que se envió. Es lo que hace que {site} sea de hoy y no de siempre: una noticia con dos votos que lleva un día en cola vale menos que una recién llegada con los mismos votos. Nada dura para siempre en la cola.</p>

<h2>El ciclo</h2>
<p>Cada <b>{cycle} minutos</b> ocurre un ciclo, siempre igual, sin excepciones ni intervención manual:</p>
<p><b>1. De Nuevas a Candidatas.</b> Entre los envíos que llevan al menos <b>{minage} minutos</b> en Nuevas, asciende <b>uno solo</b>: el de mayor δ. Ese tiempo mínimo existe para que un envío no suba solo porque llegó en un momento tranquilo; tiene que ser visto y votado antes.</p>
<p><b>2. De Candidatas a Portada.</b> Entre todas las candidatas, se publica en portada <b>la de mayor δ</b>, siempre que su δ sea al menos <b>{minpub}</b>. Si ninguna llega, ese ciclo no se publica nada. La portada no se rellena por rellenar.</p>
<p><b>3. El velo se levanta.</b> A los {gm} minutos de publicarse cualquier envío o comentario, el alias de su autor deja paso a su nombre. Antes, todos son alias con un halo: se juzgan las palabras, no la firma.</p>
<p><b>4. Archivo.</b> Lo que lleva {ttl} horas en portada se archiva. Se puede seguir leyendo, pero ya no se vota ni se comenta: la conversación fue lo que fue.</p>
<p>La sección <b>Procesos</b> muestra todo esto en directo: la cola ordenada por δ con una estimación de cuándo asciende cada envío, y un flujo con cada envío, voto, comentario y portada.</p>

<h2>Lo que el δ no hace</h2>
<p>No aprende de ti. No sabe qué te gusta. No te enseña una portada distinta que a tu vecino. No premia a quien tiene más seguidores, porque aquí nadie tiene seguidores. No mira la hora a la que sueles entrar ni cuánto tiempo pasas leyendo. La misma fórmula, los mismos números, la misma portada para todos.</p>

<h2>El aura</h2>
<p>Las personas no tienen δ; tienen <b>aura</b>, que es otra cosa y mide una sola: el compromiso con la comunidad. Se gana estando y aportando (+{auraport} por cada envío tuyo que llega a portada, +{auraenv} por cada envío o artículo, +{auracom} por cada comentario, con un tope de +{auracap} al día) y se pierde no estando ({auradec} cada día, nunca por debajo de {aurastart}). El máximo es {auramax}.</p>
<p>El aura <b>nunca da altavoz</b>: no sube noticias, no ordena comentarios, no pesa en el δ. Se ve como luz alrededor de tu alias, y abre algunas puertas de confianza: con más de {commaura} de aura puedes crear comunidades, y con más de {editaura} puedes corregir tus propios comentarios durante {editmin} minutos tras publicarlos.</p>

<h2>Los números de hoy</h2>
<p>Todos los parámetros de esta página son los que están en vigor ahora mismo en {site}; si el equipo los cambia, esta página cambia con ellos. Ciclo cada {cycle} minutos · {minage} minutos mínimos en Nuevas · δ mínimo para portada {minpub} · decaimiento {decay} por media hora · velo de {gm} minutos · vida en portada de {ttl} horas.</p>');
