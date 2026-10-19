-- migra-165 (19/10/2026): el foro Areópago no se va a usar (el foro SMF se queda en su web). Su fila pasa a ser «Hardusers», un foro
--  solo para usuarios con aura > 20. Para eso, forums.min_aura: 0 = abierto a todo el mundo; N = solo lo ven (lista, hilos, búsqueda)
--  y escriben quienes tienen aura mayor que N; admin y moderadores siempre. Idempotente.
SET NAMES utf8mb4;
SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'forums' AND COLUMN_NAME = 'min_aura');
SET @sql = IF(@ya = 0, "ALTER TABLE forums ADD COLUMN min_aura DECIMAL(6,1) NOT NULL DEFAULT 0 COMMENT 'aura minima (estricta) para ver y escribir; 0 = abierto' AFTER orden", "SELECT 'forums.min_aura ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE forums SET slug = 'hardusers', name = 'Hardusers', descr = 'Foro reservado a quienes tienen más de 20 de aura. Lo que se habla aquí no sale en búsquedas ni en portadas para el resto.',
    status = 'on', orden = 'actividad', min_aura = 20
  WHERE slug = 'areopago' AND NOT EXISTS (SELECT 1 FROM forums f2 WHERE f2.slug = 'hardusers');
INSERT IGNORE INTO forums (slug, name, descr, status, position, orden, min_aura) VALUES
('hardusers', 'Hardusers', 'Foro reservado a quienes tienen más de 20 de aura. Lo que se habla aquí no sale en búsquedas ni en portadas para el resto.', 'on', 3, 'actividad', 20);
