-- migra-155 (15/10/2026): topes de ritmo al enviar (lib/topes.php), contra la autopromocion en serie (un medio envio 10 seguidas
-- con un agente y usuario anonimo). No se veta a nadie: se limita cuanto puede enviar cada cual en poco tiempo.
--   stories.anon_key: huella HMAC(ip|navegador) del anonimo que envio (la IP no se guarda); NULL en envios con cuenta.
--   stories.pub_key:  publicador para el tope diario: dominio sin www, o yt:UC… (canal) en YouTube; NULL si no aplica
--                     (plataformas compartidas). Los envios anteriores se rellenan con su dominio, salvo YouTube.
ALTER TABLE stories
  ADD COLUMN anon_key CHAR(16) NULL DEFAULT NULL AFTER user_id,
  ADD COLUMN pub_key VARCHAR(120) NULL DEFAULT NULL AFTER source,
  ADD KEY idx_anon_sent (anon_key, sent_date),
  ADD KEY idx_pub_sent (pub_key, sent_date);
UPDATE stories SET pub_key = source
 WHERE pub_key IS NULL AND source IS NOT NULL AND content_type = 'link'
   AND source NOT REGEXP '(^|\\.)(youtube\\.com|youtu\\.be)$';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('send_anon_per_hour', '3', 'Envios por hora de un anonimo (huella de ip+navegador). 0 = sin tope. Al pasarse se le invita a registrarse'),
('send_user_per_hour', '10', 'Envios por hora de una cuenta. 0 = sin tope'),
('send_domain_per_day', '5', 'Envios en 24 h de un mismo publicador (dominio; en YouTube, el canal), los envie quien los envie. Las fuentes del catalogo estan exentas. 0 = sin tope'),
('send_domain_platforms', 'youtube.com,youtu.be,x.com,twitter.com,medium.com,reddit.com,archive.org,wikipedia.org,github.com,bsky.app,mastodon.social,threads.net,tiktok.com,instagram.com,facebook.com,vimeo.com,twitch.tv,linkedin.com,wordpress.com,blogspot.com,telegram.org,t.me', 'Plataformas compartidas (dominio exacto, separadas por comas): sin tope por dominio, solo por persona. YouTube va por canal');
