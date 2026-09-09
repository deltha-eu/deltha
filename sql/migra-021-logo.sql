-- Logo personalizable desde admin (pestaña Logo): nombre del fichero en /media/logo, '' = el de serie (/img/logo.png y logo-dark.png).
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('logo_light', '', 'Logo para fondo claro: fichero en /media/logo (se gestiona en Admin > Logo; vacio = el de serie)'),
('logo_dark',  '', 'Logo para fondo oscuro: fichero en /media/logo (vacio = el claro personalizado si lo hay, o el de serie)');
