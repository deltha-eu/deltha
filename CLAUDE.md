# Deltha — reglas para quien toque este repositorio con un asistente

Lo general está en el README, sección «Colaborar (ramas, PR y migraciones)». Aquí, lo que ya ha fallado una vez y no debe repetirse.

## Migraciones (`sql/migra-NNN-*.sql` y `sql/schema.sql`)
- **Producción es MySQL 8.** MariaDB tolera cosas que MySQL 8 rechaza: una migración probada solo en MariaDB no está probada.
- **El nombre de una clave foránea es único en toda la base de datos, no por tabla.** Repetirlo da `ERROR 1826` y la migración se queda a medias. Nombra siempre `fk_<tabla entera>_<columna>` (p. ej. `fk_usenet_comments_user`), sin abreviar la tabla, y antes de escribirlo busca el nombre en `sql/`.
- Antes de abrir una PR que toque `sql/`: `php scripts/comprobar-fk.php` (lo pasa también la acción «SQL» de GitHub).
- Toda tabla o columna nueva va en la migración **y** en `sql/schema.sql`, con el mismo nombre de índices y claves.
- Las migraciones tienen que poder ejecutarse dos veces sin efectos.
