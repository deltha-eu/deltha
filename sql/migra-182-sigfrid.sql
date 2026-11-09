-- migra-182 (09/11/2026): Sigfrid, el ayudante del Chat (lib/sigfrid.php). Una cuenta normal que responde en el Chat cuando se le
-- menciona (@Sigfrid) o se le habla por el nombre: «@Sigfrid muestrame el radar», «Sigfrid, quien va ganando el concurso»,
-- «cual es el medio mas leido esta semana». Lo previsto lo resuelve con reglas en PHP, al instante; lo que no entiende se lo
-- pregunta, si esta configurado, a un modelo pequeño OpenAI-compatible (sigfrid_ia_url, p. ej. llama-server con LFM3-1.7B en el
-- propio droplet) que solo elige la accion y los parametros: nunca redacta la respuesta ni toca datos. Idempotente.
SET NAMES utf8mb4;

-- La cuenta. Contraseña imposible ('!' nunca pasa password_verify): nadie entra con ella. Avatar y bio se cambian desde admin.
INSERT IGNORE INTO users (login, email, pass, verified, level, bio, lang)
VALUES ('Sigfrid', 'sigfrid@deltha.eu', '!', 1, 'normal', 'Ayudante del Chat. Menciona a @Sigfrid y pregúntame por el radar, el concurso de relatos o el medio más leído.', 'es');

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('sigfrid_enabled',    '1',       '1 = Sigfrid responde en el Chat cuando se le menciona (@Sigfrid o «Sigfrid, …»); 0 = calla'),
('sigfrid_login',      'Sigfrid', 'Nombre de la cuenta con la que escribe Sigfrid (tiene que existir en users)'),
('sigfrid_ia_url',     '',        'URL de un servidor OpenAI-compatible (/v1/chat/completions) para entender lo que las reglas no entienden; vacio = solo reglas. P. ej. http://127.0.0.1:8091 (llama-server con LFM3-1.7B en el droplet)'),
('sigfrid_ia_key',     '',        'Clave Bearer del servidor de sigfrid_ia_url, si la pide'),
('sigfrid_ia_timeout_ms', '4000', 'Tope de espera al modelo de Sigfrid, en ms; si no llega, Sigfrid contesta con la ayuda'),
('sigfrid_concurso_regex', 'concurso|microrrelato|relato', 'Expresion regular (MySQL REGEXP, sin distinguir mayusculas) que identifica en el titulo los hilos de concurso del foro Originales');
