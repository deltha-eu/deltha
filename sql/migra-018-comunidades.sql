-- Comunidades: segunda clasificacion de los envios, creada por los usuarios (aura > comm_min_aura, hasta comm_max_per_user cada uno).
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS communities (
    community_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(40) NOT NULL,
    name         VARCHAR(48) NOT NULL,
    descr        VARCHAR(200) DEFAULT NULL,
    color        CHAR(7) NOT NULL DEFAULT '#534AB7',
    owner_id     INT UNSIGNED NOT NULL,
    status       ENUM('ok','hidden') NOT NULL DEFAULT 'ok',
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_slug (slug),
    UNIQUE KEY uq_name (name),
    KEY idx_owner (owner_id),
    CONSTRAINT fk_co_owner FOREIGN KEY (owner_id) REFERENCES users(user_id)
) ENGINE=InnoDB;
ALTER TABLE stories ADD COLUMN community_id INT UNSIGNED DEFAULT NULL AFTER topic_id, ADD KEY idx_comm (community_id, sent_date);
-- donde aterriza el usuario al entrar: '' = portada | t:ID (tema) | c:ID (comunidad)
ALTER TABLE users ADD COLUMN landing VARCHAR(24) NOT NULL DEFAULT '' AFTER skin;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('comm_min_aura',     '10', 'Aura minima (estricta: mayor que) para poder crear comunidades'),
('comm_max_per_user', '3',  'Comunidades que puede crear cada usuario');
