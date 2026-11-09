-- migra-185 (09/11/2026): el modelo de Sigfrid en el droplet lee el prompt a ~90 tokens/s y la primera llamada (prompt sin cachear)
-- se pasaba de los 4 s: PHP colgaba y Sigfrid caia en la ayuda. Tope a 8 s si nadie lo ha cambiado a mano (y prompt mas corto en
-- lib/sigfrid.php). Idempotente.
SET NAMES utf8mb4;
UPDATE config SET cvalue = '8000' WHERE ckey = 'sigfrid_ia_timeout_ms' AND cvalue = '4000';
