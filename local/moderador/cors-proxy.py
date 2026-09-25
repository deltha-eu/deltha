#!/usr/bin/env python3
"""
Intermediario CORS para el chat web del modelo (http://127.0.0.1:8080).

El navegador no deja que la web del 8080 hable con el puente MCP de Brave (8001) porque éste no envía
cabeceras CORS. Este proxy escucha en 127.0.0.1:8002, reenvía todo a 127.0.0.1:8001 y añade el permiso.
Solo acepta conexiones locales y solo desde orígenes locales (localhost / 127.0.0.1).

Uso:  python cors-proxy.py [--puerto 8002] [--destino http://127.0.0.1:8001]
En el chat web, el servidor MCP se da de alta como  http://127.0.0.1:8002/mcp
"""
import argparse
import http.client
import http.server
import socketserver
import sys
from urllib.parse import urlsplit

PASA = {"accept", "content-type", "mcp-session-id", "mcp-protocol-version", "last-event-id", "origin", "authorization"}
EXPONE = "Mcp-Session-Id, Mcp-Protocol-Version, Content-Type"


def origen_local(origin: str) -> bool:
    try:
        h = urlsplit(origin).hostname or ""
    except Exception:
        return False
    return h in ("localhost", "127.0.0.1", "::1")


class Proxy(http.server.BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.0"   # la respuesta termina al cerrar: vale para JSON y para SSE sin trocear
    destino = ("127.0.0.1", 8001)

    def log_message(self, fmt, *args):
        sys.stdout.write("%s  %s\n" % (self.log_date_time_string(), fmt % args))

    def _cors(self):
        o = self.headers.get("Origin", "")
        if o and origen_local(o):
            self.send_header("Access-Control-Allow-Origin", o)
            self.send_header("Vary", "Origin")
            self.send_header("Access-Control-Expose-Headers", EXPONE)

    def do_OPTIONS(self):
        o = self.headers.get("Origin", "")
        if o and not origen_local(o):
            self.send_response(403); self.end_headers(); return
        self.send_response(204)
        self._cors()
        self.send_header("Access-Control-Allow-Methods", "GET, POST, DELETE, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", self.headers.get("Access-Control-Request-Headers", "*"))
        self.send_header("Access-Control-Max-Age", "600")
        self.end_headers()

    def _reenviar(self):
        o = self.headers.get("Origin", "")
        if o and not origen_local(o):
            self.send_response(403); self.end_headers(); return
        n = int(self.headers.get("Content-Length") or 0)
        cuerpo = self.rfile.read(n) if n else None
        cab = {k: v for k, v in self.headers.items() if k.lower() in PASA}
        if cuerpo is not None:
            cab["Content-Length"] = str(len(cuerpo))
        c = http.client.HTTPConnection(*self.destino, timeout=300)
        try:
            c.request(self.command, self.path, body=cuerpo, headers=cab)
            r = c.getresponse()
        except Exception as e:
            self.send_response(502); self._cors(); self.end_headers()
            self.wfile.write(f"proxy: no llego al puente MCP ({e})".encode()); return
        self.send_response(r.status, r.reason)
        for k, v in r.getheaders():
            if k.lower() not in ("transfer-encoding", "connection", "access-control-allow-origin", "keep-alive"):
                self.send_header(k, v)
        self._cors()
        self.end_headers()
        try:
            while True:
                trozo = r.read1(65536) if hasattr(r, "read1") else r.read(65536)
                if not trozo:
                    break
                self.wfile.write(trozo)
                self.wfile.flush()
        except (BrokenPipeError, ConnectionResetError):
            pass
        finally:
            c.close()

    do_GET = do_POST = do_DELETE = _reenviar


class Servidor(socketserver.ThreadingMixIn, http.server.HTTPServer):
    daemon_threads = True
    allow_reuse_address = True


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--puerto", type=int, default=8002)
    ap.add_argument("--destino", default="http://127.0.0.1:8001")
    a = ap.parse_args()
    d = urlsplit(a.destino)
    Proxy.destino = (d.hostname, d.port or 80)
    print(f"Proxy CORS: http://127.0.0.1:{a.puerto}/mcp  ->  {a.destino}/mcp  (Ctrl+C para parar)", flush=True)
    Servidor(("127.0.0.1", a.puerto), Proxy).serve_forever()


if __name__ == "__main__":
    main()
