-- migra-201 (13/11/2026): niveles de /analitica (decisión de Ángel). Para todo el mundo sigue estando /estadisticas.
--  · analytics_full_aura pasa de 10 a 50: la analítica completa (de «Por fechas» hacia abajo, salidas por visitante y uso de las
--    relacionadas) es solo para el admin y para quien tenga MÁS de 50 de aura (ana_full() compara ahora con «mayor que»).
--  · analytics_live_aura (10, el límite que la analítica tenía de siempre): desde esa aura se ve la versión limitada, que añade
--    «Ahora» y «Hoy» a las cifras cerradas y la actividad. Por debajo, solo cifras cerradas y actividad, como antes.
SET NAMES utf8mb4;

INSERT INTO config (ckey, cvalue, descr) VALUES
('analytics_full_aura', '50', 'Aura por encima de la cual un registrado ve en /analitica la analitica completa (por fechas, paginas, procedencia, paises, salidas, relacionadas); el resto ve la limitada; 0 = solo el admin')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), descr = VALUES(descr);

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('analytics_live_aura', '10', 'Aura desde la que un registrado ve en /analitica la version limitada (ahora y hoy, ademas de las cifras cerradas); 0 = solo quien ve la completa');
