-- migra-208 (22/11/2026): Sigfrid contesta antes, pedido por Ángel. La espera no estaba en el modelo sino alrededor:
--  1) el «un momento» sale al instante: si el worker del PC está a la vista, la pregunta que no encaja en las reglas va directa a él
--     (sigfrid_pc_primero) y el clasificador del droplet, que tardaba 2-4 s dentro del POST del Chat, queda de respaldo;
--  2) la tarea «sigfrid» pasa por delante de la moderación en la cola (prioridad 10; la moderación es 9);
--  3) el Chat se refresca cada sigfrid_refresco_seg (2 s, antes 6 fijos) mientras hay una respuesta pendiente.
--  El sondeo largo de /api/ia.php?act=lease&wait=N y el nuevo orden del mensaje no llevan config. Idempotente.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('sigfrid_pc_primero',   '1', '1 = lo que las reglas de Sigfrid no entienden va directo al modelo del PC si el worker esta a la vista (el «un momento» sale al instante); 0 = primero el clasificador del droplet, como antes'),
('sigfrid_refresco_seg', '2', 'Segundos entre refrescos del Chat mientras Sigfrid tiene una respuesta pendiente del PC');

UPDATE ia_tasks SET priority = 10 WHERE task_key = 'sigfrid' AND priority = 7;
