-- migra-120 (06/10/2026): contadores de lecturas y clics por envio, junto a los votos (tarjeta y hilo).
--   stories.views  = entradas al hilo (/hilo.php)
--   stories.clicks = salidas al medio: los enlaces externos del hilo pasan por /ir.php?id=…, que suma y redirige (302)
-- Una vez por persona, envio y dia (huella de la analitica, apuntada en Redis 24 h; lib/contadores.php). Pedido por Angel.
SET NAMES utf8mb4;
ALTER TABLE stories
    ADD COLUMN views  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'entradas al hilo' AFTER comments,
    ADD COLUMN clicks INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'salidas al medio (/ir.php)' AFTER views;
