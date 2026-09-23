-- Interruptor para dejar pasar a los asistentes de IA que actuan a peticion de una persona (ChatGPT-User, Claude-User,
-- Perplexity-User, MistralAI-User) en /robots.txt, servido ahora por public/robots.php. Por defecto, vetados como el resto.
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('robots_ai_users', '0', 'robots.txt: 1 = deja pasar a ChatGPT-User, Claude-User, Perplexity-User y MistralAI-User (solo entran cuando una persona se lo pide; pueden usar el enlace de la clave). Los de entrenamiento siguen vetados. Requiere el vhost con robots.php');
