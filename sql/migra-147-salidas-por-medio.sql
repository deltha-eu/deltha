-- migra-147 (13/10/2026): salidas a los medios por dia y por medio (dominio), para el «top» de /estadisticas?v=medios (hoy, mes, año).
-- La alimentan /ir (envios: stories.source) e /ir-fuente (fichas de medios y substacks: dominio de la noticia). Como en la 146,
-- lo acumulado sin fecha (stories.clicks por dominio) se apunta en el dia de hoy para arrancar con algo.
CREATE TABLE IF NOT EXISTS clicks_source (
  day    DATE NOT NULL,
  source VARCHAR(100) NOT NULL,
  n      INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (day, source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO clicks_source (day, source, n)
    SELECT CURDATE(), source, SUM(clicks) FROM stories WHERE source IS NOT NULL AND source <> '' AND clicks > 0 GROUP BY source
    ON DUPLICATE KEY UPDATE n = GREATEST(n, VALUES(n));
