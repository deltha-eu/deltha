-- 25/09/2026. El Chat se recargaba solo cada 60 s con un <meta refresh> y se llevaba por delante el mensaje a medio escribir
-- (visto en Chrome de Android: abrir el chat, escribir sin enviar y esperar un minuto). Ahora el meta va dentro de <noscript>
-- y, con JS, /app/escribir.js programa la recarga (a la misma URL con auto=1, como el meta) y la cancela en cuanto hay algo
-- escrito, y mientras la pestaña esta oculta. El plazo es configurable: chat_refresh_sec (60; 0 = el Chat no se recarga solo).
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('chat_refresh_sec', '60', 'Segundos entre recargas automaticas del Chat (0 = no se recarga sola; nunca mientras se esta escribiendo)');
