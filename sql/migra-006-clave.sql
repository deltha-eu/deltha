-- Acceso privado temporal: clave de entrada. Vacia = web abierta.
INSERT INTO config (ckey, cvalue, descr) VALUES
('access_key', 'CAMBIAR', 'Clave temporal de acceso: mientras no este vacia, quien no la tenga ve /delta/ en vez de la portada. Cambiarla expulsa a todos.');
