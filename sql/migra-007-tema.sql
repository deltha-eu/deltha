-- Tema de interfaz por usuario (perfil). auto = sigue al sistema.
ALTER TABLE users ADD COLUMN theme ENUM('auto','light','dark') NOT NULL DEFAULT 'auto' AFTER bio;
