-- migra-145 (12/10/2026): historico de salidas a los medios por dia, reconstruido desde la analitica. /ir era un GET y la analitica propia
-- (hits, desde el 25/09/2026, se conserva analytics_keep_days = 90 dias) lo apuntaba como una pagina vista mas, asi que ahi esta cada salida con
-- su fecha. Se rehace clicks_daily entera con eso (la fila de hoy que dejo la migra-144 con el acumulado sin fecha se sustituye) y, desde
-- ahora, ana_hit() ya no cuenta /ir como pagina vista: las paginas vistas bajaran un poco respecto a dias anteriores (antes incluian las salidas).
DELETE FROM clicks_daily;
INSERT INTO clicks_daily (day, n)
SELECT DATE(ts), COUNT(*) FROM hits WHERE path LIKE '/ir?%' OR path LIKE '/ir.php?%' GROUP BY DATE(ts);
