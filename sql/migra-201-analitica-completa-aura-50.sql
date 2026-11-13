-- migra-201 (13/11/2026): la analítica completa de /analitica pasa a ser solo para el admin y para quien tenga más de 50 de aura
-- (antes, desde 10). El resto de registrados ve una versión limitada: cifras cerradas, actividad, «Ahora» y «Hoy»; sin «Por fechas»
-- hacia abajo, sin salidas por visitante y sin uso de las relacionadas. Decisión de Ángel. ana_full() compara ahora con «mayor que».
SET NAMES utf8mb4;

INSERT INTO config (ckey, cvalue, descr) VALUES
('analytics_full_aura', '50', 'Aura por encima de la cual un registrado ve en /analitica la analitica completa (por fechas, paginas, procedencia, paises, salidas, relacionadas); el resto ve la limitada; 0 = solo el admin')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), descr = VALUES(descr);
