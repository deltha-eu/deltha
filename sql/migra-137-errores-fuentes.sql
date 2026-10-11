-- migra-137 (11/10/2026): historial de errores de lectura de las fuentes (source_errors), escrito por fuente_error_log() (lib/fuentes.php)
-- cada vez que una lectura falla; una fila por fuente y texto de error, con veces y primera/ultima vez. Se ve en /moderacion -> Errores,
-- junto a las fuentes cuya ultima lectura fallo (sources.last_error), desde donde se pueden apagar, encender o releer.
CREATE TABLE IF NOT EXISTS source_errors (
  err_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  source_id  INT UNSIGNED NOT NULL,
  errhash    CHAR(32) NOT NULL,
  error      VARCHAR(255) NOT NULL,
  first_seen DATETIME NOT NULL,
  last_seen  DATETIME NOT NULL,
  times      INT UNSIGNED NOT NULL DEFAULT 1,
  UNIQUE KEY uq_src_err (source_id, errhash), KEY (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
