-- migra-081 — todo dura una semana mientras haya pocos usuarios (26/09/2026, decision de Angel): vida en portada 288 h -> 168 h.
-- (La caducidad del radar ya quedo en 168 en migra-080.) Se revisara cuando crezca la comunidad.
UPDATE config SET cvalue = '168', descr = 'Vida del hilo en portada, en horas (destacadas -> archivo); 168 = una semana' WHERE ckey = 'thread_ttl_hours';
UPDATE config SET cvalue = '168' WHERE ckey = 'nuevas_ttl_hours';
