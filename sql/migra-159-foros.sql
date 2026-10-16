-- migra-159 (16/10/2026): Originales pasa a ser la seccion FOROS (pedido por Angel: «la zona originales la quiero convertir en un foro»).
--  * Tabla forums: cada foro con su slug (es la URL, /foros?f=slug, y el grupo de destacados en pins.grupo), nombre, descripcion,
--    interruptor (status on/off: los apagados solo los ve el admin), orden de las pestañas y criterio de orden de sus hilos
--    ('fecha' = el hilo mas reciente arriba, como Originales hasta ahora; 'actividad' = el ultimo mensaje arriba, como un foro).
--  * stories.forum_id: el foro de cada texto propio (content_type = article). Todo lo que habia en Originales pasa, hilo a hilo,
--    al foro «originales» (cada original ya era un hilo: su autor, su fecha, sus votos y sus comentarios se quedan como estan).
--  * Tres foros de salida: Originales (activo), Deltha (activo: dudas, propuestas y conversacion sobre el sitio; el nombre es
--    provisional, se cambia desde admin > Foros) y Areopago (apagado hasta que este importado el foro SMF).
--  Idempotente (MySQL, sin «IF NOT EXISTS» en ALTER): la columna se añade solo si no esta.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS forums (
    forum_id   SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug       VARCHAR(16)  NOT NULL,                         -- URL y grupo de pins (pins.grupo es VARCHAR(16))
    name       VARCHAR(48)  NOT NULL,
    descr      VARCHAR(300) NOT NULL DEFAULT '',             -- aviso gris de cabecera del foro (texto plano)
    status     ENUM('on','off') NOT NULL DEFAULT 'on',        -- off = solo lo ve el admin (y sus hilos siguen abriendose por URL)
    position   TINYINT UNSIGNED NOT NULL DEFAULT 1,           -- orden de las pestañas
    orden      ENUM('fecha','actividad') NOT NULL DEFAULT 'fecha',   -- fecha = hilo mas reciente arriba; actividad = ultimo mensaje arriba
    created    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (forum_id),
    UNIQUE KEY uq_forum_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO forums (slug, name, descr, status, position, orden) VALUES
('originales', 'Originales', 'Textos propios de la comunidad. No se permiten textos ya publicados en otros sitios ni extractos de ellos.', 'on', 1, 'fecha'),
('deltha',     'Deltha',     'Dudas, propuestas de mejora y conversación sobre el propio sitio.', 'on', 2, 'actividad'),
('areopago',   'Areópago',   'El foro Areópago, en Deltha.', 'off', 3, 'actividad');

SET @ya = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stories' AND COLUMN_NAME = 'forum_id');
SET @sql = IF(@ya = 0, "ALTER TABLE stories ADD COLUMN forum_id SMALLINT UNSIGNED NULL COMMENT 'foro del texto propio (content_type = article); NULL en los enlaces' AFTER portada, ADD KEY idx_forum (forum_id, status, sent_date)", "SELECT 'stories.forum_id ya presente'");
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- lo que habia en Originales: cada original es ya un hilo del foro «originales»
UPDATE stories s JOIN forums f ON f.slug = 'originales' SET s.forum_id = f.forum_id WHERE s.content_type = 'article' AND s.forum_id IS NULL;
