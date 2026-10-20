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

echo "== sesiones"
# La sesion iniciada dura 30 dias (config.session_days). El limpiador de sesiones de Ubuntu (phpsessionclean.timer,
# a las :09 y :39, /usr/lib/php/sessionclean) lee gc_maxlifetime de php.ini + conf.d de cada SAPI: NO ve el
# ini_set() de lib/app.php ni los php_admin_value del pool de php-fpm. Sin este fichero borra la sesion a los
# 24 min de inactividad y el usuario aparece desconectado (README, «Sesiones»). Idempotente.
for d in /etc/php/8.3/fpm/conf.d /etc/php/8.3/apache2/conf.d; do
  [ -d "$d" ] || continue
  INI="$d/99-deltha-sesiones.ini"
  if [ ! -f "$INI" ]; then
    printf '; Deltha: la sesion iniciada dura 30 dias (ver README, «Sesiones»)\nsession.gc_maxlifetime = 2592000\n' > "$INI"
    echo "gc_maxlifetime = 30 dias en $INI"
  fi
done

echo "== media y subidas"
# Carpetas de subidas (fotos, avatares, comunidades, imagenes de comentarios por año/mes) propiedad del usuario de PHP-FPM.
# El 20/10/2026 PHP no podia crear public/media/c (mkdir: Permission denied) ni escribir fotos: se asegura en cada despliegue.
PHPU=$(ps -o user= -C php-fpm8.3 2>/dev/null | grep -v '^root$' | sort -u | head -n1); PHPU=${PHPU:-www-data}
install -d -o "$PHPU" -g "$PHPU" "$DIR/public/media" "$DIR/public/media/avatars" "$DIR/public/media/comunidades" "$DIR/public/media/c" "$DIR/public/media/c/$(date +%Y)" "$DIR/public/media/c/$(date +%Y/%m)"
chown -R "$PHPU:$PHPU" "$DIR/public/media"
# Limites de subida de PHP: el valor por defecto (upload_max_filesize = 2M) rechazaba fotos normales de movil aunque img_max_bytes admite 6 MB.
for d in /etc/php/8.3/fpm/conf.d /etc/php/8.3/apache2/conf.d; do
  [ -d "$d" ] || continue
  INI="$d/99-deltha-subidas.ini"
  if [ ! -f "$INI" ]; then
    printf '; Deltha: fotos de hasta 6 MB (config img_max_bytes) con margen para el resto del formulario\nupload_max_filesize = 8M\npost_max_size = 10M\n' > "$INI"
    echo "limites de subida en $INI"
  fi
done

echo "== comprobacion"
for f in $(find "$DIR/lib" "$DIR/public" "$DIR/scripts" -name '*.php'); do php -l "$f" >/dev/null || echo "ERROR de sintaxis en $f"; done
systemctl reload php8.3-fpm 2>/dev/null || true
echo "desplegado $(date '+%F %T'): css v$(grep -o "DDD_CSS_VER', [0-9]*" "$DIR/lib/app.php" | grep -o '[0-9]*$')"
