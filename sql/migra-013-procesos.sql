-- /procesos.php (fisgona): intervalo del cron para la estimacion de ascenso e indices
-- por fecha para el flujo cronologico de votos y comentarios.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('cycle_minutes', '5', 'Minutos entre ciclos del cron (para estimar en /procesos.php cuando asciende cada nueva)');
ALTER TABLE votes    ADD KEY idx_created (created);
ALTER TABLE comments ADD KEY idx_created (created);
