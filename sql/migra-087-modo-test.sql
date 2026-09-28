-- migra-087 — modo test (28/09/2026). Con la segunda formula y el minimo de votos, en la beta casi nada llega a portada
-- (no hay gente para votar). Con test_mode = 1, si el ciclo no publica nada y la ultima promocion tiene mas de 60 min
-- (TEST_MODE_MINUTES, lib/ciclo.php), publica el enlace del radar con mas delta (primero la portada del turno, luego la otra)
-- sin exigir edad, votos ni delta minimos. Se apaga en admin -> Ciclo -> Modo test cuando haya usuarios de verdad.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('test_mode', '1', '1 = modo test (beta): si el ciclo no publica nada y la ultima promocion tiene mas de 60 min, publica el enlace del radar con mas delta sin exigir edad, votos ni delta minimos');
UPDATE config SET cvalue = '1' WHERE ckey = 'test_mode';
