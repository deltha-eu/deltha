-- Chat: el aviso «Mensaje eliminado por incumplir las normas de uso» se ve media hora y luego desaparece del flujo
-- (el original sigue en content_orig). chat_deleted_minutes = 0 lo deja para siempre. lib/chat.php: chat_visible_sql().
SET NAMES utf8mb4;
ALTER TABLE chat ADD COLUMN deleted_at DATETIME DEFAULT NULL AFTER deleted;
-- los ya borrados: el plazo cuenta desde que se escribieron (los antiguos desaparecen ya)
UPDATE chat SET deleted_at = created WHERE deleted = 1;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('chat_deleted_minutes', '30', 'Chat: minutos que se ve el aviso de mensaje eliminado por moderacion antes de desaparecer del flujo (0 = siempre)');
