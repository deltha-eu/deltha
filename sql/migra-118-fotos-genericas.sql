-- migra-118 (05/10/2026): fotos genericas de los medios. Cuando una noticia no tiene foto propia, muchos medios (Infobae…)
-- ponen de og:image su logo o una imagen de relleno, y salia a todo el ancho. Dos señales (lib/imagen.php, imagen_de_pagina()):
--   1) la misma imagen (sha1 de los bytes originales) en config.img_generic_min paginas distintas del mismo dominio
--      -> queda apuntada en image_generic, se quita de los envios y noticias que ya la llevaban y no se vuelve a guardar;
--   2) el nombre del fichero (logo, default, placeholder, fallback, og-image, share-image…).
-- La noticia queda sin foto (tarjeta de solo texto). Solo afecta a las fotos que Deltha saca de la pagina o del feed:
-- las que sube una persona no se tocan. img_generic_min = 0 lo apaga. Para indultar una imagen: borrar su fila de image_generic.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS image_seen (
    seen_id  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    domain   VARCHAR(120) NOT NULL,                       -- dominio de la pagina (source_of)
    hash     CHAR(40) NOT NULL,                           -- sha1 de los bytes originales de la imagen
    page     CHAR(32) NOT NULL,                           -- md5 de la URL de la pagina: se cuentan paginas distintas
    file     VARCHAR(40) DEFAULT NULL,                    -- fichero en /media que salio de ella (para retirarlo si resulta generica)
    seen     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_imgseen_hash (domain, hash),
    KEY idx_imgseen_file (file),
    KEY idx_imgseen_seen (seen)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS image_generic (
    domain   VARCHAR(120) NOT NULL,
    hash     CHAR(40) NOT NULL,
    created  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (domain, hash)
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('img_generic_min', '3', 'Foto de la pagina o del feed repetida en este numero de noticias distintas del mismo medio = generica (logo, relleno): no se usa. 0 = no mirar');
