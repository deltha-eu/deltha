-- Orden y visibilidad de los modulos de la columna derecha (escritorio), editable en admin -> Lateral.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('side_order', 'ciclo,temas,comunidades,votadas,agregadores,medios,substacks', 'Modulos de la columna derecha (escritorio), en orden; lo que no este no se pinta');
