-- migra-211 (22/11/2026): la descripción de config.ghost_users_enabled seguía diciendo «Ghostear», y el botón se llama «Silenciar» desde
--  el 20/10/2026. No era solo cosmético: Sigfrid lee esas descripciones (bloque [reglas] del paquete) y recomendaba «el botón Ghostear».
--  Admin → Config solo deja cambiar el valor, no la descripción, así que va por migración. Idempotente.
SET NAMES utf8mb4;

UPDATE config SET descr = '1 = boton «Silenciar» (ignorar usuarios): cada uno ve al otro siempre como Ghost, comentarios plegados, sin perfil ni privados entre ambos'
 WHERE ckey = 'ghost_users_enabled' AND descr LIKE '%«Ghostear»%';
