-- migra-146 (12/10/2026): la 145 vacio clicks_daily creyendo que la analitica (hits) tenia las salidas con fecha; no las tenia, porque /ir
-- redirige sin pintar pagina y ana_hit() solo se apunta al pintar. Se repone lo unico que existe, el acumulado sin fecha (stories.clicks),
-- en el dia de hoy. No hay historico por dias anterior a este despliegue; desde ahora /ir apunta cada salida en su dia.
INSERT INTO clicks_daily (day, n) SELECT CURDATE(), COALESCE(SUM(clicks), 0) FROM stories
    ON DUPLICATE KEY UPDATE n = GREATEST(n, VALUES(n));
