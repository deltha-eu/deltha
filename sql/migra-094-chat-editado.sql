-- migra-094: los administradores pueden editar mensajes del Chat (/editar-chat.php). Los moderadores siguen pudiendo solo
-- eliminarlos y el autor, borrar el suyo en los primeros minutos. El mensaje editado queda marcado como «editado»
-- (chat.edited_at) y el texto original de la primera edicion se guarda en chat.content_orig.
SET NAMES utf8mb4;

ALTER TABLE chat ADD COLUMN edited_at DATETIME NULL DEFAULT NULL AFTER created;
