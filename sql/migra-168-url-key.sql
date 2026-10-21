-- migra-168 (21/10/2026): duplicados por variantes de la misma URL (?m=1, /amp, www., acortadores, http/https, barra final…).
--  * stories.url_key: forma canonica del enlace para comparar (url_key() en lib/app.php: sin esquema, sin www./m./amp., sin barra
--    final, sin ?m=1 / amp / output, sin seguimiento, parametros ordenados, sin fragmento). El duplicado se busca por esta clave.
--    Se rellena al enviar y, para lo anterior, el cron (url_key_tick) unos cientos por pasada.
--  Idempotente.
SET NAMES utf8mb4;
SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stories' AND COLUMN_NAME = 'url_key');
SET @sql = IF(@ya = 0, "ALTER TABLE stories ADD COLUMN url_key VARCHAR(255) NULL COMMENT 'clave canonica del enlace para detectar duplicados (migra-168)' AFTER url, ADD KEY idx_url_key (url_key)", "SELECT 'stories.url_key ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('url_resolve', '1', '1 = al enviar un enlace se siguen sus redirecciones (acortadores) y se toma la URL canonica que declara la pagina; 0 = se guarda tal cual');
