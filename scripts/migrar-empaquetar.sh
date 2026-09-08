#!/bin/bash
# Ejecutar como root EN EL SERVIDOR VIEJO (JotDownV2). Empaqueta todo lo que hay que llevarse
# a /root/deltha-migracion.tar.gz: volcado de MySQL, config.ini, media, cron y curl-impersonate.
set -e
DIR=${1:-/var/www/deltha}
OUT=/root/deltha-migracion
rm -rf "$OUT"; mkdir -p "$OUT"
echo "== volcado de la base de datos deltha"
mysqldump --single-transaction --routines --default-character-set=utf8mb4 deltha > "$OUT/deltha.sql"
sed -i '/enable the sandbox mode/d' "$OUT/deltha.sql"   # linea de MariaDB 11 que MySQL 8 no entiende
echo "== config.ini"
cp /etc/deltha/config.ini "$OUT/config.ini"
echo "== media (fotos y avatares)"
tar -C "$DIR/public" -czf "$OUT/media.tgz" media
echo "== cron"
crontab -l 2>/dev/null | grep -i deltha > "$OUT/crontab.txt" || true
if [ -d /opt/curl-impersonate ]; then
  echo "== curl-impersonate"
  tar -C /opt -czf "$OUT/curl-impersonate.tgz" curl-impersonate
fi
tar -C /root -czf /root/deltha-migracion.tar.gz deltha-migracion
rm -rf "$OUT"
ls -lh /root/deltha-migracion.tar.gz
echo
echo "Ahora copia el paquete al droplet nuevo:"
echo "  scp /root/deltha-migracion.tar.gz root@IP_NUEVA:/root/"
