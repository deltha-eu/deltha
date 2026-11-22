-- migra-209 (22/11/2026): los nombres cortos de los asuntos de Sigfrid («Abascal en La Revuelta») se guardan. Cada uno le cuesta 1-4 s al
--  modelo del droplet y el paquete de la capa 3 lleva hasta seis (temas de la semana y de hoy): el POST del Chat tardaba unos 20 s en
--  devolver el «un momento». Ahora se piden una vez por grupo de titulares (clave = sha1 del idioma y los titulares), el cron los deja
--  listos en cada ciclo (sigfrid_calentar) y el Chat ya no espera al modelo: lo que no esté guardado sale con las palabras repetidas.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS sigfrid_etiquetas (
  clave   CHAR(40)    NOT NULL COMMENT 'sha1 del idioma y los titulares del grupo, ordenados',
  lang    VARCHAR(5)  NOT NULL DEFAULT 'es',
  tema    VARCHAR(80) NOT NULL,
  created DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (clave),
  KEY idx_created (created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
