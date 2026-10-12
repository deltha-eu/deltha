-- migra-144 (12/10/2026): salidas a los medios por dia (clicks_daily). /ir.php suma una por clic ademas de stories.clicks; /estadisticas
-- enseña hoy, este mes y este año (cuanto trafico deriva Deltha a los medios). Empieza a contar al desplegarse: lo anterior solo esta
-- en el total por envio (stories.clicks), sin fecha. Para arrancar con algo, el total acumulado hasta hoy se apunta en el dia de hoy.
CREATE TABLE IF NOT EXISTS clicks_daily (
  day DATE NOT NULL PRIMARY KEY,
  n   INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO clicks_daily (day, n) SELECT CURDATE(), COALESCE(SUM(clicks), 0) FROM stories ON DUPLICATE KEY UPDATE n = n + VALUES(n);
