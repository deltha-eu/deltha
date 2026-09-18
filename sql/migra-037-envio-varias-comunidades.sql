-- Un envio puede estar en varias comunidades. stories.community_id sigue siendo la principal
-- (la que eligio quien envio; manda sobre la plantilla del hilo). Las demas se «adoptan» desde
-- el hilo: el dueño de una comunidad puede añadir a ella cualquier envio publico (nuevas,
-- candidatas, portada o archivado) y quitarlo; los moderadores arbitran. Tope comm_max_per_story.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS story_communities (
    story_id     INT UNSIGNED NOT NULL,
    community_id INT UNSIGNED NOT NULL,
    added_by     INT UNSIGNED DEFAULT NULL,          -- quien la añadio (NULL: anonimo o cuenta borrada)
    created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (story_id, community_id),
    KEY idx_comm (community_id, created),
    CONSTRAINT fk_sc_story FOREIGN KEY (story_id)     REFERENCES stories(story_id)         ON DELETE CASCADE,
    CONSTRAINT fk_sc_comm  FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE,
    CONSTRAINT fk_sc_user  FOREIGN KEY (added_by)     REFERENCES users(user_id)            ON DELETE SET NULL
) ENGINE=InnoDB;
-- volcado de las comunidades principales que ya existen
INSERT IGNORE INTO story_communities (story_id, community_id, added_by, created)
    SELECT story_id, community_id, user_id, sent_date FROM stories WHERE community_id IS NOT NULL;
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('comm_max_per_story', '3', 'Comunidades en las que puede estar un mismo envio (contando la principal)');
