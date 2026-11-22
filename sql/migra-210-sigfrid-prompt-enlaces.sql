-- migra-210 (22/11/2026): dos retoques al prompt de la tarea «sigfrid» tras la batería de pruebas con Gemma.
--  1) «eso no lo sé mirar» iba en minúscula en el prompt y el modelo empezaba así la frase: ahora con mayúscula.
--  2) Los enlaces, con el mismo texto que llevan en el paquete (el modelo rotuló «Chat» un enlace que era la portada).
--  Solo toca el prompt si conserva esas frases tal cual (si se ha editado en admin → IA, no hace nada). Idempotente.
SET NAMES utf8mb4;

UPDATE ia_tasks SET system_prompt = REPLACE(system_prompt, '(«eso no lo sé mirar»)', '(«Eso no lo sé mirar»)')
 WHERE task_key = 'sigfrid' AND system_prompt LIKE '%(«eso no lo sé mirar»)%';

UPDATE ia_tasks SET system_prompt = REPLACE(system_prompt, 'Los enlaces van como en el paquete: [texto](https://…).', 'Los enlaces van tal cual están en el paquete, con su mismo texto y su misma dirección: [texto](https://…).')
 WHERE task_key = 'sigfrid' AND system_prompt LIKE '%Los enlaces van como en el paquete: [texto](https://…).%';
