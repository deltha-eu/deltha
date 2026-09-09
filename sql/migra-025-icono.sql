-- Icono del sitio (favicon, iOS, PWA) subido desde Admin > Logo: base del nombre en /media/logo (icon-<hex>), '' = los de serie.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('logo_icon', '', 'Icono del sitio: base del nombre en /media/logo con variantes -64/-180/-192/-512/-512m (Admin > Logo; vacio = los de serie)');
