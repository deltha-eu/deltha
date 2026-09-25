-- 25/09/2026. «Las sesiones se cierran de vez en cuando»: PHP trae de serie una cookie de sesion que muere al cerrar el
-- navegador y borra la sesion a los 24 min de inactividad (session.gc_maxlifetime = 1440). Ahora dura session_days (30)
-- y el plazo cuenta desde la ultima visita (lib/app.php: session_arranca() y session_refresca()).
-- Ojo: en el droplet hace falta ademas, una sola vez, en /etc/php/8.3/fpm/pool.d/www.conf:
--   php_admin_value[session.gc_maxlifetime] = 2592000
-- y reiniciar php8.3-fpm; el limpiador de sesiones de Debian/Ubuntu lee ese valor del pool, no el de la web.
-- (scripts/install-droplet.sh ya lo deja puesto en las instalaciones nuevas.)
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('session_days', '30', 'Dias que dura la sesion iniciada (cuenta desde la ultima visita; el pool de php-fpm debe permitir al menos otro tanto)');
