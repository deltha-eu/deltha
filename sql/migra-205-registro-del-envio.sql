-- migra-205 (18/11/2026): pestaña «Registro» de cada envío (/hilo/N?v=registro), pedida por Ángel: todo lo que ha pasado con el
--  envío (quién lo envía, votos, comentarios, llegadas a portada), como el flujo de /procesos pero de un solo hilo.
--  La ven moderadores y admin, y los registrados con aura por encima de story_log_aura. registro_envio_ve() en lib/app.php.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('story_log_aura', '50', 'Aura por encima de la cual un registrado ve la pestaña Registro de cada envio (votos, comentarios, quien lo envia, llegadas a portada); moderadores y admin, siempre; 0 = solo moderadores y admin');
