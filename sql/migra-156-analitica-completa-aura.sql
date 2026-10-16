-- migra-156 (16/10/2026): la analitica completa (admin -> Analitica: ahora, hoy, por fechas, paginas, procedencia, paises, dispositivos,
-- idiomas y horas) se abre en /analitica a cualquier registrado desde esta aura (ana_full() en lib/analitica.php). 0 = solo el admin.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('analytics_full_aura', '10', 'Aura desde la que un registrado ve en /analitica la analitica completa (ahora, hoy, fechas, paginas, procedencia, paises); 0 = solo el admin');
