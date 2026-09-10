#!/bin/bash
# Regenera /etc/apache2/sites-available/deltha.conf a partir de apache.conf.example
# (el del repo desplegado, o el que se pase como primer argumento), anadiendo el
# <VirtualHost *:443> con el certificado de origen de Cloudflare igual que install-droplet.sh.
# Uso: bash scripts/vhost-actualizar.sh [ruta/apache.conf.example]
set -e
SRC=${1:-/var/www/deltha/apache.conf.example}
DST=/etc/apache2/sites-available/deltha.conf
DOMAIN=$(grep -m1 '^ *ServerName' "$DST" 2>/dev/null | awk '{print $2}')
DOMAIN=${DOMAIN:-deltha.eu}
[ -f "$SRC" ] || { echo "no existe $SRC"; exit 1; }
cp "$DST" "$DST.bak.$(date +%Y%m%d%H%M%S)" 2>/dev/null || true
sed -e "s/deltha\.eu/$DOMAIN/g" "$SRC" > "$DST"
if [ -f /etc/ssl/deltha/origin.pem ] && [ -f /etc/ssl/deltha/origin.key ]; then
  sed -n '/<VirtualHost \*:80>/,/<\/VirtualHost>/p' "$DST" \
    | sed -e 's/<VirtualHost \*:80>/<VirtualHost *:443>\n    SSLEngine on\n    SSLCertificateFile \/etc\/ssl\/deltha\/origin.pem\n    SSLCertificateKeyFile \/etc\/ssl\/deltha\/origin.key/' \
    >> "$DST"
else
  echo "AVISO: no hay /etc/ssl/deltha/origin.pem: deltha.conf queda solo con :80"
fi
apachectl configtest && systemctl reload apache2
echo "vhost actualizado: $(grep -c '<VirtualHost' "$DST") VirtualHost"
