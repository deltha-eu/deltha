-- Edicion (y retirada) del envio propio: el autor con aura > story_edit_min_aura puede editar
-- titular, entradilla/texto, tema y foto durante story_edit_minutes tras enviar, mientras el
-- envio no llegue a portada. El enlace no se toca. Toda edicion marca stories.edited_at.
SET NAMES utf8mb4;
ALTER TABLE stories ADD COLUMN edited_at DATETIME DEFAULT NULL AFTER sent_date;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('story_edit_minutes',  '15', 'Minutos tras enviar en los que el autor puede editar o retirar su envio (mientras no este en portada)'),
('story_edit_min_aura', '10', 'Aura minima (estricta: mayor que) para poder editar los envios propios');
