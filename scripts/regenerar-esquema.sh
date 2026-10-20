#!/bin/bash
# Regenera sql/schema.sql a partir de la base real (20/10/2026). Se ejecuta en el droplet y escribe por la salida estandar:
#   ssh root@DROPLET 'bash /var/www/deltha/scripts/regenerar-esquema.sh' > sql/schema.sql     (desde el PC, en la raiz del repo)
# Conserva la cabecera y los datos de partida (los INSERT del final) del schema.sql actual y sustituye solo la estructura
# (mysqldump --no-data, sin AUTO_INCREMENT ni DROP). Asi el fichero refleja todas las migraciones aplicadas en produccion.
set -e
DIR=${DIR:-/var/www/deltha}
SRC="$DIR/sql/schema.sql"
DB=${DB:-deltha}
HOY=$(date '+%d/%m/%Y')
ULT=$(ls "$DIR"/sql/migra-*.sql | sed 's/.*migra-\([0-9]*\).*/\1/' | sort -n | tail -n1)
# cabecera: hasta la linea anterior al primer CREATE TABLE, con la fecha y la ultima migracion al dia
sed -n '1,/^CREATE TABLE/p' "$SRC" | sed '$d' \
  | sed -E "s|^-- Regenerado el .* a partir de la base real.*|-- Regenerado el $HOY a partir de la base real (scripts/regenerar-esquema.sh, mysqldump --no-data $DB): las tablas tal como estan|" \
  | sed -E "s|^-- en produccion con las migraciones .* aplicadas.*|-- en produccion con las migraciones 001-$ULT aplicadas.|"
# estructura
mysqldump --no-data --skip-add-drop-table --skip-comments --skip-set-charset --compact "$DB" \
  | sed -E 's/ AUTO_INCREMENT=[0-9]+//' \
  | sed -E 's|^/\*!40101 SET character_set_client = utf8mb4 \*/;$||; s|^/\*!40101 SET @saved_cs_client     = @@character_set_client \*/;$||; s|^/\*!40101 SET character_set_client = @saved_cs_client \*/;$||' \
  | sed '/^$/N;/^\n$/D'
echo "SET FOREIGN_KEY_CHECKS = 1;"
# datos de partida: desde el separador hasta el final
sed -n '/^-- Datos de partida/,$p' "$SRC" | sed '1i\
-- ---------------------------------------------------------------------------------------------'
