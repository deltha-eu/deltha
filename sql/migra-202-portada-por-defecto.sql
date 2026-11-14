-- migra-202 (14/11/2026): la portada de la primera visita pasa a ser un ajuste (decisión de Ángel) y vale Medios.
--  Hasta ahora estaba fija en el código (Substacks desde el 24/09/2026). Quien ya eligió una portada sigue viendo la suya
--  (cookie ddd_portada). portada_defecto() en lib/app.php; desplegable en admin > Config.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('portada_default', 'medios', 'Portada que ve quien entra por primera vez, sin haber elegido ninguna: medios | substacks | canales (despues manda la ultima que abrio)');
