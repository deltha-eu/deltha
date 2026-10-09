-- migra-134 (09/10/2026): registro de acciones de moderadores y admin (mod_log). Se escribe desde mod_log_post() (lib/app.php),
-- llamada al entrar en el POST de /moderacion, /admin, /editar-usuario, /crear-usuario, /cambiar-tema, /editar-chat, el megafono del chat,
-- los destacados del admin (/destacar) y las ediciones de envios, comentarios y comunidades ajenos por un moderador. Se ve en admin -> Registro.
CREATE TABLE IF NOT EXISTS mod_log (
  log_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id  INT UNSIGNED NOT NULL,
  action   VARCHAR(60) NOT NULL,            -- pagina:act (moderacion:rep_borrar, admin:config, editar-usuario, destacar:quitar…)
  detail   VARCHAR(800) NULL,               -- campos del formulario, sin contraseñas, recortados
  result   VARCHAR(600) NULL,               -- lo que vio en pantalla (mensajes de $out / $err) o el objeto tocado
  ip       VARCHAR(45) NULL,
  created  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (user_id, log_id), KEY (created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
