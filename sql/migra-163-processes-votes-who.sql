-- migra-163 (17/10/2026): quien ve la pestaña Votos de /procesos (y los votos del flujo «Todo»).
-- Valores: todos (tambien anonimos) | usuarios (registrados) | moderadores (y admin) | admin.
-- Se cambia en admin > Config. Por defecto todos, como hasta ahora.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('processes_votes_who', 'todos', 'Quien ve la pestaña Votos de /procesos y los votos del flujo «Todo»: todos | usuarios | moderadores | admin');
