-- migra-121 (07/10/2026): dos cosas pedidas por Angel.
-- 1) Chat: administradores y moderadores pueden marcar un mensaje como «mensaje a la comunidad» (chat.anuncio): sale resaltado
--    en su sitio y, los ultimos tres, en una franja arriba del Chat (pagina 1). Se marca con un icono (megafono) al escribir
--    o en las acciones de cualquier mensaje ya publicado; el admin tambien desde /editar-chat.
-- 2) Votos a envios: quien ha votado puede anular su voto pulsando otra vez la misma flecha durante vote_undo_minutes (5; 0 = nunca).
SET NAMES utf8mb4;

ALTER TABLE chat
    ADD COLUMN anuncio TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'mensaje a la comunidad (admin/mod)' AFTER edited_at,
    ADD KEY idx_anuncio (anuncio, msg_id);

INSERT INTO config (ckey, cvalue, descr) VALUES
('vote_undo_minutes', '5', 'Minutos durante los que un voto a un envío se puede anular pulsando otra vez la misma flecha (0 = nunca)')
ON DUPLICATE KEY UPDATE descr = VALUES(descr);
