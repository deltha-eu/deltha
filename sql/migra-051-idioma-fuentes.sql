-- Idioma de las fuentes (medios, substacks, agregadores) y casilla del perfil «Mostrar fuentes solo en mi idioma».
-- sources.lang: dos letras (es, en…); '' = sin saber (se detecta del RSS al darla de alta o en la siguiente lectura).
-- Las que ya hay se marcan en español: casi todas lo son; las pocas en otro idioma se corrigen en admin > Fuentes.
-- users.src_mylang: 0 por defecto (casilla desmarcada) mientras haya pocas fuentes en otros idiomas.
SET NAMES utf8mb4;
ALTER TABLE sources ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT '' AFTER descr;
UPDATE sources SET lang = 'es';
ALTER TABLE users ADD COLUMN src_mylang TINYINT(1) NOT NULL DEFAULT 0 AFTER lang;
