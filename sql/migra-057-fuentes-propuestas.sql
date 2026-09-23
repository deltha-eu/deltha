-- Fuentes propuestas por los usuarios (medios y substacks/newsletters): /proponer-fuente.php, revision en /moderacion.php#fuentes.
-- Pueden proponer las cuentas con aura >= source_propose_aura (moderadores y admin, siempre). No entran en el catalogo hasta que un
-- moderador las aprueba; al proponerlas se prueba el RSS y el resultado queda en test_items / test_info para quien revisa.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS source_proposals (
    prop_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,                       -- quien la propone
    kind        ENUM('medio','newsletter') NOT NULL DEFAULT 'medio',
    name        VARCHAR(48) NOT NULL,
    home        VARCHAR(200) NOT NULL,                       -- web
    feed        VARCHAR(300) NOT NULL,                       -- RSS (el dado o el encontrado en la web)
    descr       VARCHAR(200) DEFAULT NULL,
    note        VARCHAR(500) DEFAULT NULL,                   -- nota de quien propone para moderacion
    lang        VARCHAR(5) NOT NULL DEFAULT '',              -- idioma que declara el feed ('' = no lo dice)
    test_items  SMALLINT UNSIGNED NOT NULL DEFAULT 0,        -- noticias leidas en la ultima prueba (0 = no se lee)
    test_info   VARCHAR(600) DEFAULT NULL,                   -- ultimos titulos o el error de la prueba
    status      ENUM('pending','ok','rejected') NOT NULL DEFAULT 'pending',
    reply       VARCHAR(300) DEFAULT NULL,                   -- motivo del rechazo (se le envia por mensaje)
    source_id   SMALLINT UNSIGNED DEFAULT NULL,              -- la fuente creada al aprobarla
    reviewed_by INT UNSIGNED DEFAULT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed    DATETIME DEFAULT NULL,
    KEY idx_fp_status (status, created),
    KEY idx_fp_user (user_id, created),
    CONSTRAINT fk_fp_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('source_propose_aura',        '6', 'Aura minima (mayor o igual) para proponer medios y substacks en /proponer-fuente.php; moderadores y admin, siempre'),
('source_propose_max_pending', '3', 'Propuestas de fuentes pendientes a la vez por usuario'),
('source_propose_per_day',     '5', 'Propuestas de fuentes por usuario en 24 horas');
