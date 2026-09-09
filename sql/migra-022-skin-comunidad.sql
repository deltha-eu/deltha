-- Plantilla visual propia de cada comunidad ('' = ninguna): la elige quien la crea y la cambian luego el creador, los moderadores y el admin.
-- Se aplica en /comunidad.php y en los hilos enviados a esa comunidad, por encima de la plantilla del usuario (no de ?skin=).
ALTER TABLE communities ADD COLUMN skin VARCHAR(16) NOT NULL DEFAULT '' AFTER color;
UPDATE config SET descr = 'Plantilla visual por defecto (anonimos y quien no elija): editorial | comunidad | delta | flujo | retro | manga | punk | popart | zen | orange' WHERE ckey = 'skin_default';
