-- migra-216 (27/11/2026): que los robots no falseen «Uso de las relacionadas» de /analitica. El 26/11 uno recorrió los hilos siguiendo
--  los enlaces de la caja «Relacionadas»: 8.032 veces mostrada y 10.253 clics en un día (127,7 %), y otros seis días entre el 14 y el 25
--  salieron entre el 22 y el 73 %, cuando una persona la pulsa entre un 5 y un 10 % de las veces. rel_cuenta() (lib/contadores.php):
--   · el clic solo cuenta si se pulsa desde una página de Deltha (cabecera Sec-Fetch-Site: same-origin o, si el navegador no la manda,
--     Referer del propio sitio);
--   · máximo por visitante y día (misma huella que la deduplicación; necesita Redis): rel_max_clicks_dia cajas pulsadas y
--     rel_max_shown_dia cajas vistas. Lo que pase de ahí no se apunta. 0 = sin máximo.
--  Solo config: no cambia ninguna tabla. Idempotente.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('rel_max_clicks_dia', '20',  'Relacionadas (migra-216): cajas «Relacionadas» pulsadas que se le cuentan como mucho a un mismo visitante en un dia, para /analitica; 0 = sin maximo'),
('rel_max_shown_dia',  '200', 'Relacionadas (migra-216): cajas «Relacionadas» vistas que se le cuentan como mucho a un mismo visitante en un dia, para /analitica; 0 = sin maximo');
