-- Medios: fuentes RSS de tipo «medio» (Jot Down, Revista Mercurio...) elegibles por cada usuario («Mis medios», hasta media_max_per_user),
-- con boton «enviar a Nuevas» en cada noticia. Menéame pasa a ser de tipo «agregador» (fases y votos).
SET NAMES utf8mb4;

ALTER TABLE sources
    ADD COLUMN kind     ENUM('agregador','medio') NOT NULL DEFAULT 'medio' AFTER slug,
    ADD COLUMN descr    VARCHAR(200) DEFAULT NULL AFTER home,
    ADD COLUMN topic_id SMALLINT UNSIGNED DEFAULT NULL AFTER skin;   -- tema propuesto al enviar una noticia de este medio
UPDATE sources SET kind = 'agregador' WHERE slug = 'meneame';

-- la imagen pasa a ser una ruta relativa a /media: 'fuentes/x.webp' (miniatura de un agregador) o 'x.jpg' (og:image procesada, en /media)
ALTER TABLE source_items MODIFY image VARCHAR(100) DEFAULT NULL;
UPDATE source_items SET image = CONCAT('fuentes/', image) WHERE image IS NOT NULL AND image <> '' AND image NOT LIKE 'fuentes/%';

CREATE TABLE IF NOT EXISTS user_media (
    user_id   INT UNSIGNED NOT NULL,
    source_id SMALLINT UNSIGNED NOT NULL,
    pos       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id, source_id),
    CONSTRAINT fk_um_user   FOREIGN KEY (user_id)   REFERENCES users(user_id)     ON DELETE CASCADE,
    CONSTRAINT fk_um_source FOREIGN KEY (source_id) REFERENCES sources(source_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min) VALUES
('jotdown',  'medio', 'Jot Down',         'https://www.jotdown.es/',        'Contemporary Culture Mag', '', 3, 'https://www.jotdown.es/feed/',        '', 15),
('mercurio', 'medio', 'Revista Mercurio', 'https://www.revistamercurio.es/', 'Revista de libros y cultura', '', 3, 'https://revistamercurio.es/feed/', '', 15);

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('media_max_per_user',   '3',  'Medios que puede elegir cada usuario en su perfil («Mis medios»)'),
('sources_og_image',     '1',  '1 = para los medios sin imagen en el RSS se busca la og:image de la noticia (como en un envio)'),
('sources_og_per_run',   '10', 'Fuentes: og:image maximas que se descargan en cada pasada del cron');
