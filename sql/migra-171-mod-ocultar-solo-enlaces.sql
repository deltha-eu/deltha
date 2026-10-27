-- migra-171 (27/10/2026): la IA moderadora solo oculta provisionalmente lo que lleva enlace, pedido por Angel.
--  Con mod_auto_hide = 1 y confianza >= mod_hide_score, antes se ocultaba cualquier categoria salvo «otro»; ahora ademas el texto
--  tiene que llevar un enlace (http/https, www. o dominio.tld: mod_has_link, lib/moderacion.php). Lo demas se señala y lo decide
--  una persona. Idempotente.
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('mod_hide_links_only', '1', '1 = la IA solo oculta provisionalmente (mod_auto_hide) lo que lleva algun enlace; sin enlace se limita a señalar. 0 = oculta por confianza, lleve enlace o no');
