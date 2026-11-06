-- migra-180 (06/11/2026): tope de cajas que se pintan en la columna derecha (side_max = 10). La columna es pegajosa y con scroll
-- oculto: con las 13 cajas posibles (migra-179) lo de abajo no se veia. Se aplica al orden de admin y al de cada usuario:
-- se pintan las N primeras del orden en vigor. Idempotente.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('side_max', '10', 'Cuantas cajas se pintan como mucho en la columna derecha (las N primeras del orden en vigor, de admin o del usuario); 0 = sin tope');
