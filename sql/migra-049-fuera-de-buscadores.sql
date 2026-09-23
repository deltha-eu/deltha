-- Fuera de los buscadores: todo Deltha sale con noindex (meta robots + X-Robots-Tag) salvo las paginas escaparate
-- (que-es, algoritmo, normas, privacidad) y los articulos cuyo autor lo pide (stories.indexable = 1).
-- config.index_articles = 0 cierra tambien esos articulos. Los rastreadores de IA se vetan en /robots.txt.
-- Añade la seccion «Fuera de los buscadores» a ¿Que es Deltha?, antes de la de propiedad (solo si no esta ya).
SET NAMES utf8mb4;
ALTER TABLE stories ADD COLUMN indexable TINYINT(1) NOT NULL DEFAULT 0 AFTER edited_at;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('index_articles', '1', 'Buscadores: 1 = el autor de un articulo puede abrirlo a Google y demas (casilla al escribir/editar); 0 = nada se indexa salvo las paginas fijas');
UPDATE pages SET body = REPLACE(body, '<h2>¿Quieres ser propietario de {site}?</h2>', '<h2>Fuera de los buscadores</h2>
<p><b>Lo que pasa en {site} se queda en {site}.</b> Ningún envío, comentario ni perfil aparece en Google ni en otros buscadores: todas las páginas llevan la marca <i>noindex</i>. Solo se dejan ver las páginas que explican el proyecto, como esta.</p>
<p><b>Salvo que tú lo quieras.</b> Si escribes un artículo, puedes marcar la casilla para que los buscadores lo indexen. Es tu texto y decides tú; se puede cambiar al editarlo.</p>
<p><b>Y ninguna IA se entrena con lo que escribes.</b> Los rastreadores de inteligencia artificial (los de OpenAI, Anthropic, Google, Meta, Common Crawl y compañía) tienen la entrada vetada, incluso en los artículos abiertos a buscadores.</p>

<h2>¿Quieres ser propietario de {site}?</h2>')
WHERE slug = 'que-es' AND body NOT LIKE '%Fuera de los buscadores%';
