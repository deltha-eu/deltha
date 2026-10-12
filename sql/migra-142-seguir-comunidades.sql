-- migra-142 (12/10/2026): seguir comunidades. user_communities (quien sigue cual); boton «Seguir» en /comunidades y en la cabecera de cada
-- comunidad (comunidad_seguir_form, lib/app.php; endpoint /seguir-comunidad); /comunidades con dos vistas, «Mis comunidades» y «Todas»
-- (cookie ddd_cv con la ultima). Quien crea una comunidad la sigue; aqui se apunta a los dueños actuales y a quien la tiene como pagina de entrada.
CREATE TABLE IF NOT EXISTS user_communities (
  user_id      INT UNSIGNED NOT NULL,
  community_id INT UNSIGNED NOT NULL,
  created      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, community_id), KEY (community_id),
  CONSTRAINT fk_uc_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_uc_comm FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT IGNORE INTO user_communities (user_id, community_id) SELECT owner_id, community_id FROM communities;
INSERT IGNORE INTO user_communities (user_id, community_id) SELECT u.user_id, c.community_id FROM users u JOIN communities c ON u.landing = CONCAT('c:', c.community_id);
