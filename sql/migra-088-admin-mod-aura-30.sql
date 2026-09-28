-- migra-088 — admins asignables, moderacion por aura y aura maxima 30 (28/09/2026).
-- 1) El admin puede dar (y quitar) el nivel admin a otros usuarios: en la ficha (/editar-usuario.php, /u.php#modedit), en admin ->
--    Usuarios (columna «nivel», antes casilla «mod») y al crear un usuario. El propio nivel no se lo cambia uno mismo. No hay cambio
--    de esquema: users.level ya es ENUM('normal','mod','admin') desde migra-008.
-- 2) Con aura >= mod_aura (20) cualquier registrado modera aunque su nivel sea normal (user_is_mod(), lib/app.php): ve /moderacion.php,
--    edita envios y comentarios ajenos, fichas de quien no modera, etc. Como el aura baja cada dia, se pierde solo al dejar de participar.
--    0 = solo moderan los de nivel mod/admin, como hasta ahora.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('mod_aura', '20', 'Aura desde la que cualquier registrado modera aunque su nivel sea normal (0 = solo por nivel mod/admin)');
UPDATE config SET cvalue = '20' WHERE ckey = 'mod_aura';

-- 3) Tope de aura: de 50 a 30. Quien lo pasara se queda en 30 (queda en el historial como ajuste del sistema).
INSERT INTO aura_log (user_id, amount, reason) SELECT user_id, ROUND(30 - aura, 3), 'admin: tope aura_max 30' FROM users WHERE aura > 30;
UPDATE users SET aura = 30 WHERE aura > 30;
UPDATE config SET cvalue = '30' WHERE ckey = 'aura_max';
