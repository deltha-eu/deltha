-- migra-193 (11/11/2026): registro de IP con caducidad, pedido por Ángel para dos cosas y ninguna más: detectar multicuentas y
-- poder responder a un requerimiento formal de un juzgado o de la policía.
--  1) Tabla ip_log: la IP al registrarse, al abrir sesión y al publicar (envío, artículo, comentario, mensaje del Chat), con cuenta
--     o sin ella. Los votos no se apuntan. lib/iplog.php.
--  2) config.ip_log_days (365): días que se conserva cada fila; lo purga el ciclo. 0 = no se guarda nada.
--  3) config.ip_proxies: rangos extra (además de los de Cloudflare, que van en el código) a los que se les cree la cabecera
--     CF-Connecting-IP. client_ip() ya no se fía de esa cabecera si la petición no llega de Cloudflare.
-- Solo lo consulta el administrador (/ips) y cada consulta queda en mod_log. Las huellas de votos, anónimos y analítica no cambian.
-- Idempotente.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `ip_log` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL COMMENT 'NULL = anónimo',
  `kind` enum('registro','login','story','comment','chat') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'id del envío, comentario o mensaje; 0 en registro y login',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_ipl_user` (`user_id`,`created`),
  KEY `idx_ipl_ip` (`ip`,`created`),
  KEY `idx_ipl_item` (`kind`,`ref_id`),
  KEY `idx_ipl_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('ip_log_days', '365', 'Registro de IP (multicuentas y requerimientos judiciales o policiales): dias que se conserva cada fila; 0 = no se guarda nada. Solo lo ve el admin en /ips'),
('ip_proxies',  '',    'Rangos CIDR extra, separados por comas, a los que se les cree la cabecera CF-Connecting-IP (los de Cloudflare ya van en el codigo)');
