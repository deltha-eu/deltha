-- Favoritos: cada usuario registrado puede guardar envios y articulos desde el hilo
-- (boton «guardar» junto a los votos) y verlos en la pestaña Favoritos de su perfil (/favoritos.php).
-- Privado: solo los ve su dueño. Borrar la cuenta o el envio borra la marca (ON DELETE CASCADE).
CREATE TABLE IF NOT EXISTS favorites (
    user_id     INT UNSIGNED NOT NULL,
    story_id    INT UNSIGNED NOT NULL,
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, story_id),
    KEY idx_fav_created (user_id, created),
    CONSTRAINT fk_fav_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)   ON DELETE CASCADE,
    CONSTRAINT fk_fav_story FOREIGN KEY (story_id) REFERENCES stories(story_id) ON DELETE CASCADE
) ENGINE=InnoDB;
