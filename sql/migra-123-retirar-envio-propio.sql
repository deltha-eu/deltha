-- migra-123 (08/10/2026): corregir y retirar lo propio al alcance de los recien llegados. Pedido por Angel.
-- 1) story_edit_min_aura pasa de 6 a 0: cualquier cuenta puede corregir o retirar su envio durante story_edit_minutes (15)
--    mientras no llegue a portada (antes hacia falta aura > 6 y los nuevos, que empiezan en 5, no podian ni retirar un error).
-- 2) Ademas (codigo, sin config): el autor puede RETIRAR su envio sin limite de tiempo mientras siga en el radar y nadie lo haya
--    comentado (can_retire_own_story, lib/app.php); en el hilo le sale «retirar» cuando ya no puede «editar».
SET NAMES utf8mb4;
UPDATE config SET cvalue = '0', descr = 'Aura minima (estricta) para que el autor edite o retire su envio en plazo; 0 = todos' WHERE ckey = 'story_edit_min_aura';
