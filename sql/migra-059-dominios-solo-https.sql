-- Comprobacion de la web del dominio del correo al registrarse: interruptor para exigir HTTPS.
-- 0 (por defecto) = vale https://dominio/, https://www.dominio/ o http://dominio/ (iol.ie solo contesta por http y es valido: webmail de Blacknight).
-- 1 = solo cuenta HTTPS.
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('email_web_https', '0', 'Registro: 1 = la web del dominio del correo solo cuenta si responde por HTTPS; 0 = vale tambien http');
