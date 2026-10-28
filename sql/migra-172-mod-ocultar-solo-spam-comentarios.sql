-- migra-172 (28/10/2026): la IA solo oculta provisionalmente comentarios de spam (con enlace, migra-171); envios, chat y el resto
--  de categorias se señalan y se posmoderan. Pedido por Angel tras ver envios ocultos. Idempotente.
SET NAMES utf8mb4;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('mod_hide_types', 'comment', 'Tipos que la IA puede ocultar provisionalmente (mod_auto_hide), separados por coma: comment, chat, story. Vacio = todos'),
('mod_hide_cats',  'spam',    'Categorias que la IA puede ocultar provisionalmente, separadas por coma: insulto, odio, acoso, datos, ilegal, spam. Vacio = todas menos otro');
