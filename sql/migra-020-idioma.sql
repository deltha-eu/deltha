-- Idioma de la interfaz por usuario: '' = el del navegador (Accept-Language) | es | en | fr.
ALTER TABLE users ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT '' AFTER landing;
