#!/bin/bash
# Instala en el droplet el modelo pequeño de Sigfrid (lib/sigfrid.php, migra-182): llama-server con LFM3-1.7B (Liquid AI, Q4_K_M,
# ~1,1 GB) escuchando en 127.0.0.1:8091 como servicio systemd deltha-sigfrid. Una vez; repetirlo actualiza. Como root:
#   bash /var/www/deltha/scripts/sigfrid/instalar.sh
# Despues, en admin → Config: sigfrid_ia_url = http://127.0.0.1:8091 (sin eso Sigfrid sigue funcionando solo con reglas).
# Deja: /opt/deltha-sigfrid/llama (binarios de llama.cpp), /var/lib/deltha-sigfrid/modelo.gguf. Registro: journalctl -u deltha-sigfrid -f
# Memoria: ~1,3-1,5 GB residentes con contexto 2048 (el modelo va con mmap: el sistema puede soltar paginas si aprieta). CPU: 2 hilos.
# LFM3 necesita llama.cpp b11960 (v0.8.0) o posterior; se fija una version concreta para que una actualizacion no lo rompa.
# Para forzar que vuelva a compilar (p. ej. tras un fallo de librerias): rm -rf /opt/deltha-sigfrid/llama && bash instalar.sh
set -e
LLAMA_TAG=${LLAMA_TAG:-b11960}
GGUF_URL=${GGUF_URL:-https://huggingface.co/LiquidAI/LFM3-1.7B-GGUF/resolve/main/LFM3-1.7B-Q4_K_M.gguf}
PORT=${PORT:-8091}
CTX=${CTX:-2048}
THREADS=${THREADS:-2}
BASE=/opt/deltha-sigfrid
DATA=/var/lib/deltha-sigfrid
mkdir -p "$BASE" "$DATA"
apt-get install -y -q curl unzip > /dev/null

# 1. llama.cpp: binarios ya compilados de la release (Ubuntu x64). Si el nombre del zip cambia en una release futura, compilar
#    (git clone https://github.com/ggml-org/llama.cpp && cmake -B build && cmake --build build -t llama-server) y copiar a $BASE/llama/.
if [ ! -x "$BASE/llama/llama-server" ] || [ "$(cat "$BASE/llama/VERSION" 2>/dev/null)" != "$LLAMA_TAG" ]; then
    echo "Descargando llama.cpp $LLAMA_TAG..."
    rm -rf "$BASE/llama" "$BASE/llama.zip" "$BASE/llama.tmp"
    mkdir -p "$BASE/llama"
    OK=0
    for ZIP in "llama-$LLAMA_TAG-bin-ubuntu-x64.zip" "llama-$LLAMA_TAG-bin-linux-x64.zip" "llama-$LLAMA_TAG-bin-ubuntu-cpu-x64.zip"; do
        if curl -fsSL -o "$BASE/llama.zip" "https://github.com/ggml-org/llama.cpp/releases/download/$LLAMA_TAG/$ZIP"; then OK=1; break; fi
    done
    if [ "$OK" = 1 ]; then
        unzip -q -o "$BASE/llama.zip" -d "$BASE/llama.tmp"
        # el zip trae build/bin/ o bin/ segun la release: se aplana
        find "$BASE/llama.tmp" -type f \( -name 'llama-server' -o -name '*.so*' \) -exec mv -t "$BASE/llama/" {} +
        rm -rf "$BASE/llama.tmp" "$BASE/llama.zip"
    else
        # sin binario para esta release: se compila (unos minutos en 2 vCPU)
        echo "No hay binario precompilado; compilando llama-server $LLAMA_TAG..."
        apt-get install -y -q build-essential cmake git libcurl4-openssl-dev > /dev/null
        rm -rf "$BASE/src" && git clone -q --branch "$LLAMA_TAG" --depth 1 https://github.com/ggml-org/llama.cpp.git "$BASE/src"
        # BUILD_SHARED_LIBS=OFF (09/11/2026): un solo binario sin .so. Con librerias compartidas, libllama-common.so quedaba en
        # build/common/ (no en build/bin/), no se copiaba y el servicio moria con «cannot open shared object file».
        cmake -S "$BASE/src" -B "$BASE/src/build" -DCMAKE_BUILD_TYPE=Release -DLLAMA_CURL=OFF -DBUILD_SHARED_LIBS=OFF > /dev/null
        cmake --build "$BASE/src/build" --config Release -t llama-server -j"$(nproc)" > /dev/null
        find "$BASE/src/build" \( -type f -o -type l \) \( -name 'llama-server' -o -name '*.so*' \) -exec cp -a -t "$BASE/llama/" {} +   # por si alguna version vuelve a sacar .so
        rm -rf "$BASE/src"
    fi
    chmod +x "$BASE/llama/llama-server"
    echo "$LLAMA_TAG" > "$BASE/llama/VERSION"
fi

# 2. El modelo (una vez; ~1,1 GB)
if [ ! -s "$DATA/modelo.gguf" ]; then
    echo "Descargando el modelo (~1,1 GB)..."
    curl -fL --retry 3 -o "$DATA/modelo.gguf.part" "$GGUF_URL"
    mv "$DATA/modelo.gguf.part" "$DATA/modelo.gguf"
fi
chown -R www-data:www-data "$DATA"

# 3. Servicio. --jinja: plantilla de chat del propio GGUF y response_format json_schema; --parallel 1: una peticion cada vez (2 vCPU);
#    --no-webui y solo en localhost. Nice 10 para que Apache y MySQL vayan antes si hay apreton.
cat > /etc/systemd/system/deltha-sigfrid.service <<UNIT
[Unit]
Description=Deltha: modelo de Sigfrid, el ayudante del Chat (lib/sigfrid.php)
After=network.target

[Service]
User=www-data
Environment=LD_LIBRARY_PATH=$BASE/llama
ExecStart=$BASE/llama/llama-server --model $DATA/modelo.gguf --host 127.0.0.1 --port $PORT --ctx-size $CTX --threads $THREADS --parallel 1 --jinja --no-webui --temp 0 --alias sigfrid
Nice=10
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload
systemctl enable --now deltha-sigfrid
systemctl restart deltha-sigfrid
echo "Esperando al modelo..."
for i in $(seq 1 60); do
    if curl -s "http://127.0.0.1:$PORT/health" | grep -q '"ok"'; then
        echo "listo. Prueba:"
        curl -s "http://127.0.0.1:$PORT/v1/chat/completions" -H 'Content-Type: application/json' -d '{"messages":[{"role":"system","content":"Responde solo con JSON: {\"accion\":\"radar|ayuda\"}"},{"role":"user","content":"muestrame el radar"}],"max_tokens":40,"temperature":0,"chat_template_kwargs":{"enable_thinking":false},"reasoning_effort":"none"}' | head -c 600; echo
        echo "Ahora en admin → Config: sigfrid_ia_url = http://127.0.0.1:$PORT"
        exit 0
    fi
    sleep 2
done
echo "[AVISO] el modelo no ha respondido en 2 minutos: journalctl -u deltha-sigfrid -n 50"
exit 1
