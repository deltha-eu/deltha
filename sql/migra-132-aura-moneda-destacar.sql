-- migra-132 (09/10/2026): el aura como moneda. Destacar una noticia ya no gasta «creditos» (uno por cada unidad entera nueva de aura,
-- migra-111) sino aura directamente: pin_cost (0,5) en las portadas y pin_cost_originales (1) en Originales, sin poder bajar de
-- aura_gasto_minimo (por defecto el aura inicial, 5). aura_gastar() / aura_devolver() en lib/aura.php valen para lo que se quiera
-- cobrar en aura mas adelante. Los creditos que la gente tenia se convierten en aura (0,5 cada uno) y se apuntan en aura_log.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('pin_cost', '0.5', 'Aura que cuesta destacar un envio en una portada (Medios, Substacks, Canales). Antes se pagaba con creditos'),
('pin_cost_originales', '1', 'Aura que cuesta destacar un original (dura pin_user_minutes_originales)'),
('aura_gasto_minimo', '5', 'Suelo del aura como moneda: nadie puede gastar aura si se quedaria por debajo de esto (vacio = aura_start)');
INSERT INTO aura_log (user_id, amount, reason) SELECT user_id, pin_credits * 0.5, 'creditos_convertidos' FROM users WHERE pin_credits > 0;
UPDATE users SET aura = aura + pin_credits * 0.5, pin_credits = 0 WHERE pin_credits > 0;
