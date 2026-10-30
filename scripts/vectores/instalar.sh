#!/bin/bash
# Instala el servicio de vectores de titulares en el droplet (una vez; repetirlo actualiza). Como root:
#   bash /var/www/deltha/scripts/vectores/instalar.sh
# Deja: /opt/deltha-vectores (venv con fastembed), /var/lib/deltha-vectores (cache del modelo, 220 MB la primera vez),
# y el servicio systemd deltha-vectores (127.0.0.1:8090). Registro: journalctl -u deltha-vectores -f
set -e
DIR=${DIR:-/var/www/deltha}
VENV=/opt/deltha-vectores
mkdir -p "$VENV" /var/lib/deltha-vectores
if [ ! -x "$VENV/bin/python" ]; then
    apt-get install -y -q python3-venv > /dev/null
    python3 -m venv "$VENV"
fi
"$VENV/bin/pip" install -q --upgrade pip
"$VENV/bin/pip" install -q fastembed numpy
chown -R www-data:www-data /var/lib/deltha-vectores
cat > /etc/systemd/system/deltha-vectores.service <<UNIT
[Unit]
Description=Deltha: vectores de titulares (lib/vectores.php)
After=network.target

[Service]
User=www-data
Environment=VECTORES_CACHE=/var/lib/deltha-vectores
ExecStart=$VENV/bin/python $DIR/scripts/vectores/servidor.py
Restart=always
RestartSec=5
# el modelo tarda unos segundos en cargar; mientras, /embed responde 503 y la web cae al filtro de palabras

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload
systemctl enable --now deltha-vectores
systemctl restart deltha-vectores
echo "Esperando al modelo (la primera vez lo descarga, ~220 MB)..."
for i in $(seq 1 120); do
    if curl -s http://127.0.0.1:8090/health | grep -q '"ok": true'; then echo "listo: $(curl -s http://127.0.0.1:8090/health)"; exit 0; fi
    sleep 2
done
echo "[AVISO] el servicio no ha respondido en 4 minutos: journalctl -u deltha-vectores -n 50"
exit 1
