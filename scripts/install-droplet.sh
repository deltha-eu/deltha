#!/bin/bash
# Instalacion completa de Deltha en un droplet Ubuntu 24.04 recien creado. Ejecutar como root.
# Necesita en /root:  deltha.zip  (codigo)  y, si es una migracion,  deltha-migracion.tar.gz
# Uso:  bash install-droplet.sh deltha.eu
set -e
DOMAIN=${1:-deltha.eu}
DIR=/var/www/deltha
export DEBIAN_FRONTEND=noninteractive

echo "== 1. Sistema"
hostnamectl set-hostname "$DOMAIN"
grep -q "$DOMAIN" /etc/hosts || echo "127.0.1.1 $DOMAIN" >> /etc/hosts
timedatectl set-timezone Europe/Madrid
apt-get update -q
echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections
echo "postfix postfix/mailname string $DOMAIN" | debconf-set-selections
apt-get install -y -q apache2 mysql-server php8.3-fpm php8.3-mysql php8.3-gd php8.3-curl php8.3-mbstring php8.3-xml \
    unzip curl postfix opendkim opendkim-tools fail2ban ufw

echo "== 2. Codigo"
mkdir -p "$DIR"
unzip -o -q /root/deltha.zip -d "$DIR"
chown -R root:root "$DIR"

echo "== 3. Datos migrados (si hay paquete)"
MIG=""
if [ -f /root/deltha-migracion.tar.gz ]; then
  tar -C /root -xzf /root/deltha-migracion.tar.gz
  MIG=/root/deltha-migracion
  mkdir -p /etc/deltha
  cp "$MIG/config.ini" /etc/deltha/config.ini
  tar -C "$DIR/public" -xzf "$MIG/media.tgz"
  [ -f "$MIG/curl-impersonate.tgz" ] && tar -C /opt -xzf "$MIG/curl-impersonate.tgz"
fi
if [ ! -f /etc/deltha/config.ini ]; then
  mkdir -p /etc/deltha
  SECRET=$(head -c 32 /dev/urandom | base64 | tr -d '/+=')
  DBPASS=$(head -c 24 /dev/urandom | base64 | tr -d '/+=')
  sed -e "s/CAMBIAR-por-algo-largo-y-aleatorio/$SECRET/" -e "s/pass = \"CAMBIAR\"/pass = \"$DBPASS\"/" "$DIR/config.ini.example" > /etc/deltha/config.ini
fi
# zona horaria comun web/cron
grep -q '^timezone' /etc/deltha/config.ini || sed -i '/^\[app\]/a timezone = "Europe\/Madrid"' /etc/deltha/config.ini
chmod 640 /etc/deltha/config.ini; chgrp www-data /etc/deltha/config.ini
mkdir -p "$DIR/public/media/avatars"; chown -R www-data:www-data "$DIR/public/media"

echo "== 4. MySQL"
DBPASS=$(grep -E '^pass' /etc/deltha/config.ini | head -1 | sed -E 's/.*"(.*)".*/\1/')
mysql -e "CREATE DATABASE IF NOT EXISTS deltha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
          CREATE USER IF NOT EXISTS 'deltha'@'localhost' IDENTIFIED BY '$DBPASS';
          ALTER USER 'deltha'@'localhost' IDENTIFIED BY '$DBPASS';
          GRANT SELECT,INSERT,UPDATE,DELETE ON deltha.* TO 'deltha'@'localhost'; FLUSH PRIVILEGES;"
if [ -n "$MIG" ]; then
  mysql deltha < "$MIG/deltha.sql"
else
  mysql deltha < "$DIR/sql/schema.sql"
  for f in "$DIR"/sql/migra-*.sql; do mysql deltha < "$f" || true; done
fi

echo "== 5. Apache"
a2enmod -q rewrite headers expires proxy_fcgi setenvif ssl
a2enconf -q php8.3-fpm
sed -e "s/deltha\.eu/$DOMAIN/g" "$DIR/apache.conf.example" > /etc/apache2/sites-available/deltha.conf
# HTTPS con certificado de origen de Cloudflare si esta en /etc/ssl/deltha/
if [ -f /etc/ssl/deltha/origin.pem ] && [ -f /etc/ssl/deltha/origin.key ]; then
  sed -n '/<VirtualHost \*:80>/,/<\/VirtualHost>/p' /etc/apache2/sites-available/deltha.conf \
    | sed -e 's/<VirtualHost \*:80>/<VirtualHost *:443>\n    SSLEngine on\n    SSLCertificateFile \/etc\/ssl\/deltha\/origin.pem\n    SSLCertificateKeyFile \/etc\/ssl\/deltha\/origin.key/' \
    >> /etc/apache2/sites-available/deltha.conf
fi
a2dissite -q 000-default || true
a2ensite -q deltha
apachectl configtest
systemctl reload apache2

echo "== 6. Cron"
( crontab -l 2>/dev/null | grep -v promocionar.php; echo "*/5 * * * * /usr/bin/php $DIR/scripts/promocionar.php >> /var/log/deltha-promo.log 2>&1" ) | crontab -

echo "== 7. Correo saliente: Postfix + DKIM"
postconf -e "myhostname = $DOMAIN" "myorigin = $DOMAIN" "inet_interfaces = loopback-only" "mydestination = localhost" \
  "smtp_tls_security_level = may" "smtpd_milters = inet:127.0.0.1:8891" "non_smtpd_milters = inet:127.0.0.1:8891" \
  "milter_default_action = accept" "milter_protocol = 6"
mkdir -p /etc/opendkim/keys/$DOMAIN
[ -f /etc/opendkim/keys/$DOMAIN/deltha.private ] || opendkim-genkey -b 2048 -d "$DOMAIN" -D /etc/opendkim/keys/$DOMAIN -s deltha
cat > /etc/opendkim.conf <<EOC
Syslog                  yes
UMask                   007
Mode                    s
Canonicalization        relaxed/simple
Socket                  inet:8891@127.0.0.1
KeyTable                /etc/opendkim/key.table
SigningTable            refile:/etc/opendkim/signing.table
InternalHosts           127.0.0.1, ::1
UserID                  opendkim
PidFile                 /run/opendkim/opendkim.pid
EOC
echo "deltha._domainkey.$DOMAIN $DOMAIN:deltha:/etc/opendkim/keys/$DOMAIN/deltha.private" > /etc/opendkim/key.table
echo "*@$DOMAIN deltha._domainkey.$DOMAIN" > /etc/opendkim/signing.table
chown -R opendkim:opendkim /etc/opendkim
systemctl enable -q --now opendkim postfix
systemctl restart opendkim postfix

echo "== 8. Cortafuegos y fail2ban"
ufw allow OpenSSH >/dev/null; ufw allow 80/tcp >/dev/null; ufw allow 443/tcp >/dev/null; ufw --force enable >/dev/null
cat > /etc/fail2ban/jail.local <<'EOJ'
[DEFAULT]
backend = systemd
bantime = 1h
findtime = 10m
maxretry = 5
[sshd]
enabled = true
EOJ
systemctl enable -q --now fail2ban; systemctl restart fail2ban

echo
echo "================ LISTO ================"
echo "Registro DKIM para Cloudflare (TXT, nombre deltha._domainkey, nube gris). Contenido, unido en una linea:"
sed -e 's/.*(//' -e 's/).*//' /etc/opendkim/keys/$DOMAIN/deltha.txt | tr -d '"\n\t ' ; echo
echo
echo "Prueba de correo:  printf 'Subject: prueba\n\nhola\n' | sendmail -f noreply@$DOMAIN TU_CORREO"
echo "Cron activo:       crontab -l"
echo "Web (antes de cambiar DNS): curl -sk -H 'Host: $DOMAIN' https://127.0.0.1/ | head -5"
