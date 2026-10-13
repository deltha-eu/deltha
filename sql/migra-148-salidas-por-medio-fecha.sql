-- migra-148 (13/10/2026): la 147 apunto el acumulado por dominio (stories.clicks) a HOY, pero el acumulado total (clicks_daily, migra-146)
-- esta en el 12/10/2026; asi «hoy» del total (88) no cuadraba con el top de hoy (miles). Se pasa la semilla al 12/10 y se deja en hoy
-- solo lo que ha entrado desde el despliegue (la diferencia). Las pocas salidas de /ir entre la 147 y esta ya contaban en stories.clicks:
-- se pierden del dia de hoy (unas unidades), no del total.
INSERT INTO clicks_source (day, source, n)
    SELECT '2026-10-12', source, SUM(clicks) FROM stories WHERE source IS NOT NULL AND source <> '' AND clicks > 0 GROUP BY source
    ON DUPLICATE KEY UPDATE n = n + VALUES(n);
UPDATE clicks_source c JOIN (SELECT source, SUM(clicks) n FROM stories WHERE source IS NOT NULL AND source <> '' GROUP BY source) s ON s.source = c.source
    SET c.n = IF(c.n > s.n, c.n - s.n, 0) WHERE c.day = CURDATE();
DELETE FROM clicks_source WHERE n = 0;
