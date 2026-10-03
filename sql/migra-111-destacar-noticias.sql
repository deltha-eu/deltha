-- migra-111 — destacar noticias de medios y substacks (03/10/2026).
-- Para centrar el interes en comentar mientras no hay masa critica: en cada tarjeta de noticia de un medio o un substack hay un
-- boton «Destacar». La destacada sale la primera en «Mis medios» / «Mis substacks» (/ultimo.php?k=medio|newsletter) con un halo
-- ligero y no baja hasta que se destaque otra (se desmarca sola) o se quite en el mismo sitio: entonces cae a la cola, en la
-- posicion que le toque por fecha.
--  - Admin: una destacada por grupo (medios / substacks), sin caducidad.
--  - Usuarios: cada vez que su aura sube una unidad entera (6,9 -> 7,0) ganan un «destacado» (users.pin_credits, se cuenta en
--    aura_add); gastarlo destaca una noticia durante pin_user_minutes (30). Una activa por usuario: destacar otra sustituye la
--    anterior (y gasta otro). Quitarla a mano no devuelve el destacado.
SET NAMES utf8mb4;

ALTER TABLE users ADD COLUMN pin_credits SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER aura;   -- destacados disponibles

CREATE TABLE IF NOT EXISTS source_pins (
    pin_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    item_id     INT UNSIGNED NOT NULL,                 -- la noticia (source_items)
    kind        VARCHAR(16) NOT NULL,                  -- grupo en el que va la primera: medio | newsletter
    user_id     INT UNSIGNED NOT NULL,                 -- quien la destaco
    by_admin    TINYINT(1) NOT NULL DEFAULT 0,         -- 1 = destacada por el admin (sin caducidad, una por grupo)
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires     DATETIME DEFAULT NULL,                 -- NULL = no caduca (admin); usuarios: created + pin_user_minutes
    UNIQUE KEY uq_item (item_id),
    KEY idx_kind (kind, by_admin, expires),
    KEY idx_user (user_id),
    CONSTRAINT fk_sp_item FOREIGN KEY (item_id) REFERENCES source_items(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_sp_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('pins_enabled', '1', 'Destacar noticias de medios y substacks (boton en la tarjeta; 0 = sin boton ni destacadas)'),
('pin_user_minutes', '30', 'Minutos que dura una noticia destacada por un usuario (las del admin no caducan)');
