-- Medios: lectura cada hora por defecto (Meneame sigue a 5 min).
UPDATE sources SET interval_min = 60 WHERE kind = 'medio';
