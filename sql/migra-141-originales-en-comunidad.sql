-- migra-141 (12/10/2026): los originales (/articulo) guardaban su comunidad solo en stories.community_id, pero la pagina de la comunidad
-- lee story_communities (como hace /enviar), asi que no aparecian en ella. public/articulo.php ya inserta la fila; esto repone las que faltan.
INSERT IGNORE INTO story_communities (story_id, community_id, added_by, created)
SELECT s.story_id, s.community_id, s.user_id, s.sent_date FROM stories s
 WHERE s.community_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM story_communities sc WHERE sc.story_id = s.story_id AND sc.community_id = s.community_id);
