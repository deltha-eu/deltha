-- migra-170 (25/10/2026): seguir foros y contador de hilos sin leer junto a «Foros» (menu y pestañas), pedido por Angel.
--  * user_forums: quien sigue que foro (como user_communities, migra-142). Boton «Seguir» en la barra de pestañas de /foros
--    (foro_seguir_form, lib/app.php; endpoint /seguir-foro). El foro «deltha» se sigue por defecto (todos los registrados de hoy
--    y los que se registren: registro.php); Originales y Hardusers, a voluntad.
--  * story_reads: ultima lectura de cada hilo de foro por usuario (se apunta al abrir /hilo/N; solo registrados). Un hilo esta
--    «sin leer» si su ultima actividad es posterior a la lectura, o nunca se abrio. Solo cuenta lo posterior a user_forums.created:
--    quien empieza a seguir un foro no hereda todo su historial como pendiente. Se poda por cron (foros_tick: lecturas de hilos
--    archivados o de mas de forum_reads_days dias).
--  * stories.last_activity: fecha del ultimo comentario visible o, sin comentarios, la del hilo. Columna real (antes una subconsulta
--    por fila en /foros) que se mantiene al comentar y al retirar comentarios (story_touch). Se rellena aqui para lo que ya hay.
--  Idempotente.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS user_forums (
  user_id   INT UNSIGNED NOT NULL,
  forum_id  SMALLINT UNSIGNED NOT NULL,
  created   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- suelo del contador: solo cuenta la actividad posterior
  PRIMARY KEY (user_id, forum_id), KEY (forum_id),
  CONSTRAINT fk_uf_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_uf_forum FOREIGN KEY (forum_id) REFERENCES forums(forum_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_reads (
  user_id   INT UNSIGNED NOT NULL,
  story_id  INT UNSIGNED NOT NULL,
  read_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, story_id), KEY idx_sr_story (story_id), KEY idx_sr_read (read_at),
  CONSTRAINT fk_sr_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_sr_story FOREIGN KEY (story_id) REFERENCES stories(story_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stories' AND COLUMN_NAME = 'last_activity');
SET @sql = IF(@ya = 0, "ALTER TABLE stories ADD COLUMN last_activity DATETIME NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ultimo comentario visible o fecha del hilo (migra-170)' AFTER sent_date, ADD KEY idx_forum_act (forum_id, status, last_activity)", "SELECT 'stories.last_activity ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- relleno: hilos de foro (y, de paso, el resto) sin fecha de actividad
UPDATE stories s LEFT JOIN (SELECT story_id, MAX(created) AS lc FROM comments WHERE status = 'ok' GROUP BY story_id) c ON c.story_id = s.story_id
  SET s.last_activity = GREATEST(s.sent_date, COALESCE(c.lc, s.sent_date)) WHERE s.last_activity IS NULL;

-- el foro Deltha, seguido por defecto; su suelo es ahora: lo anterior no cuenta como pendiente
INSERT IGNORE INTO user_forums (user_id, forum_id) SELECT u.user_id, f.forum_id FROM users u JOIN forums f ON f.slug = 'deltha';

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('forum_follow_default', 'deltha', 'Foros (slugs separados por coma) que sigue de salida cada cuenta nueva; vacio = ninguno'),
('forum_reads_days', '180', 'Dias que se guarda la ultima lectura de un hilo de foro por usuario (story_reads); pasado ese tiempo, o archivado el hilo, se olvida y el hilo deja de contar como pendiente'),
('forum_unread_cache_sec', '120', 'Segundos que se cachea en sesion el contador de hilos sin leer junto a Foros (se recalcula al abrir un hilo o cambiar de foros seguidos)');
