#!/bin/bash
# Despliegue en el droplet.
#   sin argumentos : descomprime /root/deltha.zip sobre /var/www/deltha (modo antiguo, sin tocar media ni config)
#   --git          : el codigo ya lo ha volcado el hook post-receive; solo migraciones y comprobacion
# En ambos casos aplica las migraciones SQL no aplicadas aun (registro en /etc/deltha/migradas.txt).
set -e
DIR=/var/www/deltha
REG=/etc/deltha/migradas.txt

if [ "$1" != "--git" ]; then
  [ -f /root/deltha.zip ] || { echo "Falta /root/deltha.zip"; exit 1; }
  echo "== codigo (zip)"
  unzip -o -q /root/deltha.zip -d "$DIR" -x 'public/media/*' 'deltha.zip'
  chown -R root:root "$DIR"; chown -R www-data:www-data "$DIR/public/media"
fi

echo "== migraciones"
if [ ! -f "$REG" ]; then   # primera vez: todo lo que hay ya esta aplicado
  ls "$DIR"/sql/migra-*.sql | xargs -n1 basename > "$REG"
  echo "registro creado con $(wc -l < "$REG") migraciones existentes"
fi
for f in "$DIR"/sql/migra-*.sql; do
  b=$(basename "$f")
  grep -qx "$b" "$REG" && continue
  echo "aplicando $b"
  mysql deltha < "$f" && echo "$b" >> "$REG"
done

echo "== comprobacion"
for f in $(find "$DIR/lib" "$DIR/public" "$DIR/scripts" -name '*.php'); do php -l "$f" >/dev/null || echo "ERROR de sintaxis en $f"; done
systemctl reload php8.3-fpm 2>/dev/null || true
echo "desplegado $(date '+%F %T'): css v$(grep -o "DDD_CSS_VER', [0-9]*" "$DIR/lib/app.php" | grep -o '[0-9]*$')"
