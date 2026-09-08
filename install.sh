#!/bin/bash
# Instalacion en el servidor. Ejecutar como root desde la raiz del proyecto.
set -e
DIR=$(cd "$(dirname "$0")" && pwd)
read -p "Password MySQL para el usuario deltha: " DBPASS
SECRET=$(head -c 32 /dev/urandom | base64 | tr -d '/+=' )

mysql < "$DIR/sql/schema.sql"
mysql -e "CREATE USER IF NOT EXISTS 'deltha'@'localhost' IDENTIFIED BY '$DBPASS';
          GRANT SELECT,INSERT,UPDATE,DELETE ON deltha.* TO 'deltha'@'localhost'; FLUSH PRIVILEGES;"

mkdir -p /etc/deltha
sed -e "s/CAMBIAR-por-algo-largo-y-aleatorio/$SECRET/" -e "s/pass = \"CAMBIAR\"/pass = \"$DBPASS\"/" \
    "$DIR/config.ini.example" > /etc/deltha/config.ini
chmod 640 /etc/deltha/config.ini
chgrp www-data /etc/deltha/config.ini

mkdir -p "$DIR/public/media"
chown -R www-data:www-data "$DIR/public/media"

# usuario admin
read -p "Login admin: " ALOGIN
read -s -p "Password admin: " APASS; echo
HASH=$(php -r 'echo password_hash($argv[1], PASSWORD_DEFAULT);' "$APASS")
mysql deltha -e "INSERT INTO users (login,email,pass,level,aura) VALUES ('$ALOGIN','$ALOGIN@localhost','$HASH','admin',5);"

echo "Cron (añadir a crontab -e):"
echo "*/5 * * * * /usr/bin/php $DIR/scripts/promocionar.php >> /var/log/deltha-promo.log 2>&1"
echo "Listo. Apunta el vhost a $DIR/public (ver apache.conf.example)."
