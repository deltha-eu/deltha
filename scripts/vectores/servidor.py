#!/usr/bin/env python3
"""
Servicio de vectores de titulares de Deltha (lib/vectores.php, migra-175). Corre en el droplet, solo en 127.0.0.1:8090.

    POST /embed  {"texts": ["titular 1", "titular 2"]}  ->  {"model": "...", "dim": 384, "vectors": [[...], [...]]}
    GET  /health                                         ->  {"ok": true, "model": "...", "dim": 384}

Modelo: sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2 via fastembed (ONNX en CPU, sin torch, sin GPU;
unos 150 MB de RAM, decenas de milisegundos por titular). Los vectores salen normalizados: el coseno es el producto escalar.
La primera vez descarga el modelo (220 MB) a la cache (VECTORES_CACHE, por defecto /var/lib/deltha-vectores).
Lo instala scripts/vectores/instalar.sh como servicio systemd (deltha-vectores).
"""
import json
import os
import sys
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

MODELO = os.environ.get("VECTORES_MODELO", "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2")
CACHE = os.environ.get("VECTORES_CACHE", "/var/lib/deltha-vectores")
PUERTO = int(os.environ.get("VECTORES_PUERTO", "8090"))
MAX_TEXTOS = 64
MAX_CHARS = 600

modelo = None
dim = 0
cerrojo = threading.Lock()   # ONNX en CPU: una inferencia cada vez evita que dos peticiones se peleen por los hilos


def cargar():
    global modelo, dim
    from fastembed import TextEmbedding
    t0 = time.time()
    modelo = TextEmbedding(MODELO, cache_dir=CACHE)
    dim = len(next(iter(modelo.embed(["prueba"]))))
    print(f"modelo {MODELO} cargado en {time.time() - t0:.1f} s · {dim} dimensiones", flush=True)


def vectores(textos):
    import numpy as np
    with cerrojo:
        vs = list(modelo.embed(textos))
    out = []
    for v in vs:
        v = np.asarray(v, dtype="float32")
        n = float(np.linalg.norm(v)) or 1.0
        out.append([round(float(x), 6) for x in (v / n)])
    return out


class H(BaseHTTPRequestHandler):
    def log_message(self, *a):
        pass

    def _json(self, obj, code=200):
        b = json.dumps(obj, ensure_ascii=False).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(b)))
        self.end_headers()
        self.wfile.write(b)

    def do_GET(self):
        if self.path.startswith("/health"):
            self._json({"ok": modelo is not None, "model": MODELO, "dim": dim})
        else:
            self._json({"error": "no existe"}, 404)

    def do_POST(self):
        if not self.path.startswith("/embed"):
            return self._json({"error": "no existe"}, 404)
        if modelo is None:
            return self._json({"error": "modelo cargando"}, 503)
        try:
            n = int(self.headers.get("Content-Length") or 0)
            body = json.loads(self.rfile.read(n) or b"{}")
            textos = body.get("texts")
            if not isinstance(textos, list) or not textos or len(textos) > MAX_TEXTOS:
                return self._json({"error": f"texts: lista de 1 a {MAX_TEXTOS} textos"}, 400)
            textos = [str(t)[:MAX_CHARS] or " " for t in textos]
            return self._json({"model": MODELO, "dim": dim, "vectors": vectores(textos)})
        except Exception as e:
            return self._json({"error": str(e)[:300]}, 500)


def main():
    if "--prueba" in sys.argv:   # servidor.py --prueba "titular A" "titular B" ...: parecido entre cada par, para calibrar los umbrales
        cargar()
        textos = [a for a in sys.argv[1:] if a != "--prueba"]
        vs = vectores(textos)
        for i in range(len(textos)):
            for j in range(i + 1, len(textos)):
                sim = sum(a * b for a, b in zip(vs[i], vs[j]))
                print(f"{sim:.3f}  {textos[i][:60]!r}  ~  {textos[j][:60]!r}")
        return
    threading.Thread(target=cargar, daemon=True).start()   # el servidor escucha ya; /embed responde 503 hasta que el modelo este
    srv = ThreadingHTTPServer(("127.0.0.1", PUERTO), H)
    print(f"vectores de Deltha en http://127.0.0.1:{PUERTO}", flush=True)
    srv.serve_forever()


if __name__ == "__main__":
    main()
