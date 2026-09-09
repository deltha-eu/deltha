-- Plantillas visuales elegibles por el usuario (perfil): editorial | comunidad | delta | flujo.
-- '' = la plantilla por defecto del sitio (config.skin_default).
ALTER TABLE users ADD COLUMN skin VARCHAR(16) NOT NULL DEFAULT '' AFTER theme;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('skin_default', 'editorial', 'Plantilla visual por defecto (anonimos y quien no elija): editorial | comunidad | delta | flujo');
