-- migra-105 (02/10/2026): titulares de los canales de YouTube en mayusculas y minusculas normales y sin emojis, al leer el feed
-- (titular_normal() y sin_emojis() en lib/fuentes.php). 1 = se normalizan; 0 = se guardan tal como vienen.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('youtube_titulares', '1', 'Canales de YouTube: 1 = titulares sin palabras en MAYUSCULAS ni emojis al leer el feed; 0 = tal como vienen');
