-- migra-204 (16/11/2026): encuestas en los foros y en el Chat. Hasta poll_max_options opciones, de poll_min_hours a poll_max_days,
-- un voto o varios, cambiar el voto en los primeros poll_change_min minutos, resultados a la vista siempre o solo al cerrar. Idempotente.
CREATE TABLE IF NOT EXISTS polls (
  poll_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  story_id   INT UNSIGNED DEFAULT NULL COMMENT 'hilo de foro al que pertenece',
  msg_id     INT UNSIGNED DEFAULT NULL COMMENT 'mensaje del Chat al que pertenece',
  user_id    INT UNSIGNED DEFAULT NULL,
  question   VARCHAR(200) NOT NULL,
  multi      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = se pueden marcar varias opciones',
  results    ENUM('siempre','final') NOT NULL DEFAULT 'siempre' COMMENT 'cuando se ven los resultados',
  change_min SMALLINT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'minutos en los que se puede cambiar el voto',
  ends_at    DATETIME NOT NULL,
  created    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (poll_id), KEY idx_story (story_id), KEY idx_msg (msg_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS poll_options (
  option_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  poll_id   INT UNSIGNED NOT NULL,
  pos       TINYINT UNSIGNED NOT NULL,
  text      VARCHAR(120) NOT NULL,
  votes     INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (option_id), KEY idx_poll (poll_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS poll_votes (
  poll_id   INT UNSIGNED NOT NULL,
  user_id   INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  created   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (poll_id, user_id, option_id), KEY idx_opt (option_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO config (ckey, cvalue, descr) VALUES
 ('poll_max_options', '10', 'encuestas: opciones como mucho (migra-204)'),
 ('poll_min_hours',   '24', 'encuestas: duracion minima en horas (migra-204)'),
 ('poll_max_days',    '30', 'encuestas: duracion maxima en dias (migra-204)'),
 ('poll_change_min',  '10', 'encuestas: minutos en los que se puede cambiar el voto (migra-204)')
 ON DUPLICATE KEY UPDATE descr = VALUES(descr);
