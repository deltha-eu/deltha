#!/usr/bin/env python3
"""
Lector local de feeds de Deltha.

Algunas webs (Politico.es, Demócrata…) rechazan las peticiones del servidor de Deltha porque vienen de un centro de datos
(403/503 con cualquier navegador simulado). Este programa corre en el PC de la redacción, con una conexión normal: pregunta a
Deltha qué fuentes están marcadas como «lector local» (admin → Fuentes), descarga sus feeds y las fotos que falten, y se lo
entrega todo a /api/lector.php. El servidor nunca llama a este PC. No usa el modelo de IA: solo descarga y entrega.

Uso:
    python lector.py                 bucle normal (Ctrl+C para parar)
    python lector.py --una-vez       una sola pasada y sale
    python lector.py --todas         lee todas las fuentes de lector local aunque no les toque todavía
    python lector.py --prueba URL    descarga esa dirección desde este PC y dice qué responde; no entrega nada

Solo biblioteca estándar: vale el python del .venv de Bonsai-demo o cualquier Python 3.9+.
Configuración en lector.ini (copiar de lector.ini.example); si no existe, usa la dirección y el token de
..\\comentarista\\comentarista.ini o de ..\\moderador\\moderador.ini.
"""
import argparse
import configparser
import gzip
import html
import json
import logging
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import zlib
from pathlib import Path

AQUI = Path(__file__).resolve().parent
NAVEGADOR = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36",
    "Accept": "application/rss+xml, application/atom+xml, application/xml;q=0.9, text/html;q=0.8, */*;q=0.7",
    "Accept-Language": "es-ES,es;q=0.9,en;q=0.6",
    "Accept-Encoding": "gzip, deflate",
}
MAX_FEED = 4_000_000
MAX_IMG = 6_000_000
MAX_HTML = 1_500_000

log = logging.getLogger("lector")


# ---------------------------------------------------------------- configuración
def cargar_config() -> configparser.ConfigParser:
    cfg = configparser.ConfigParser()
    cfg.read_dict({"deltha": {"url": "https://deltha.eu", "token": ""},
                   "worker": {"espera": "300", "espera_error": "120", "timeout": "20", "fotos": "10"}})
    propio = AQUI / "lector.ini"
    if propio.exists():
        cfg.read(propio, encoding="utf-8")
    if not cfg["deltha"]["token"].strip():   # sin lector.ini (o sin token): el de los otros dos workers
        for otro in (AQUI.parent / "comentarista" / "comentarista.ini", AQUI.parent / "moderador" / "moderador.ini"):
            if not otro.exists():
                continue
            aux = configparser.ConfigParser()
            try:
                aux.read(otro, encoding="utf-8-sig")
            except configparser.Error:
                continue
            if aux.has_section("deltha") and aux["deltha"].get("token", "").strip():
                cfg["deltha"]["token"] = aux["deltha"]["token"].strip()
                if aux["deltha"].get("url", "").strip():
                    cfg["deltha"]["url"] = aux["deltha"]["url"].strip()
                log.info("token tomado de %s", otro.name)
                break
    return cfg


# ---------------------------------------------------------------- HTTP
def descargar(url: str, timeout: int, tope: int):
    """Devuelve (bytes | None, texto del estado). Como un navegador; sigue redirecciones."""
    try:
        req = urllib.request.Request(url, headers=NAVEGADOR)
        with urllib.request.urlopen(req, timeout=timeout) as r:
            datos = r.read(tope + 1)
            if len(datos) > tope:
                return None, f"demasiado grande (> {tope} bytes)"
            enc = (r.headers.get("Content-Encoding") or "").lower()
            if enc == "gzip":
                datos = gzip.decompress(datos)
            elif enc == "deflate":
                datos = zlib.decompress(datos, -zlib.MAX_WBITS) if not datos[:1] == b"\x78" else zlib.decompress(datos)
            return datos, f"HTTP {r.status}, {len(datos)} bytes"
    except urllib.error.HTTPError as e:
        return None, f"HTTP {e.code}"
    except Exception as e:   # red caída, DNS, tiempo agotado, certificado…
        return None, f"{type(e).__name__}: {e}"


def api(cfg, act: str, cuerpo=None, extra: str = "", binario: bool = False):
    """Llama a /api/lector.php. cuerpo: dict (JSON) o bytes (imagen). Devuelve el JSON de respuesta."""
    url = cfg["deltha"]["url"].rstrip("/") + "/api/lector.php?act=" + act + extra
    cab = {"X-Deltha-Token": cfg["deltha"]["token"].strip(), "User-Agent": "deltha-lector/1"}
    datos = None
    if cuerpo is not None:
        if binario:
            datos = cuerpo
            cab["Content-Type"] = "application/octet-stream"
        else:
            datos = json.dumps(cuerpo, ensure_ascii=False).encode("utf-8")
            cab["Content-Type"] = "application/json; charset=utf-8"
    req = urllib.request.Request(url, data=datos, headers=cab, method="POST" if datos is not None else "GET")
    try:
        with urllib.request.urlopen(req, timeout=120) as r:
            return json.loads(r.read().decode("utf-8", "replace"))
    except urllib.error.HTTPError as e:
        try:
            return json.loads(e.read().decode("utf-8", "replace"))
        except Exception:
            return {"error": f"HTTP {e.code}"}


# ---------------------------------------------------------------- fotos
META = re.compile(r'<meta[^>]+(?:property|name)\s*=\s*["\'](?:og:image(?::secure_url)?|twitter:image)["\'][^>]*>', re.I)
CONT = re.compile(r'content\s*=\s*["\']([^"\']+)["\']', re.I)


def foto_de_pagina(url: str, timeout: int):
    """La og:image (o twitter:image) de una noticia, o None."""
    datos, _ = descargar(url, timeout, MAX_HTML)
    if not datos:
        return None
    texto = datos.decode("utf-8", "replace")
    for m in META.finditer(texto):
        c = CONT.search(m.group(0))
        if c:
            return urllib.parse.urljoin(url, html.unescape(c.group(1).strip()))
    return None


def traer_fotos(cfg, pendientes, timeout: int, tope: int) -> int:
    hechas = 0
    for p in pendientes[:tope]:
        img = (p.get("thumb") or "").strip() or foto_de_pagina(p.get("url", ""), timeout)
        if not img:
            continue
        datos, estado = descargar(img, timeout, MAX_IMG)
        if not datos or len(datos) < 100:
            log.info("   foto de #%s: %s", p.get("item_id"), estado)
            continue
        r = api(cfg, "image", datos, extra=f"&item={int(p['item_id'])}", binario=True)
        if r.get("ok"):
            hechas += 1
        else:
            log.info("   foto de #%s: el servidor no la acepta (%s)", p.get("item_id"), r.get("error", "?"))
    return hechas


# ---------------------------------------------------------------- una pasada
def pasada(cfg, todas: bool) -> int:
    timeout = cfg["worker"].getint("timeout")
    r = api(cfg, "lease")
    if "error" in r:
        raise RuntimeError("Deltha responde: " + str(r["error"]))
    fuentes = r.get("sources", [])
    if not fuentes:
        log.info("no hay fuentes marcadas como «lector local» (admin → Fuentes)")
        return 0
    n = 0
    for f in fuentes:
        if not (todas or f.get("due")):
            continue
        n += 1
        cuerpo = {"slug": f["slug"]}
        for fase in ("portada", "nuevas"):
            url = (f.get(fase) or "").strip()
            if not url:
                continue
            datos, estado = descargar(url, timeout, MAX_FEED)
            if datos:
                cuerpo[fase] = datos.decode("utf-8", "replace")
            else:
                cuerpo["error_" + fase] = estado
            log.info("%s · %s: %s", f["name"], fase, estado)
        resp = api(cfg, "feed", cuerpo)
        if "error" in resp:
            log.warning("%s: Deltha no lo acepta: %s", f["name"], resp["error"])
            continue
        for linea in resp.get("log", []):
            log.info("   %s", linea)
        pend = resp.get("images", [])
        if pend:
            k = traer_fotos(cfg, pend, timeout, cfg["worker"].getint("fotos"))
            log.info("   fotos: %d de %d", k, len(pend))
    return n


def main() -> int:
    ap = argparse.ArgumentParser(description="Lector local de feeds de Deltha")
    ap.add_argument("--una-vez", action="store_true")
    ap.add_argument("--todas", action="store_true")
    ap.add_argument("--prueba", metavar="URL")
    a = ap.parse_args()

    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(message)s", datefmt="%Y-%m-%d %H:%M:%S",
                        handlers=[logging.StreamHandler(sys.stdout), logging.FileHandler(AQUI / "lector.log", encoding="utf-8")])
    cfg = cargar_config()

    if a.prueba:
        datos, estado = descargar(a.prueba, cfg["worker"].getint("timeout"), MAX_FEED)
        print(estado)
        if datos:
            print(datos[:300].decode("utf-8", "replace"))
        return 0 if datos else 1

    if len(cfg["deltha"]["token"].strip()) < 24:
        log.error("Falta el token: copia lector.ini.example a lector.ini y pon el token (o deja el del moderador en su .ini).")
        return 1

    log.info("lector local en marcha contra %s", cfg["deltha"]["url"])
    while True:
        try:
            n = pasada(cfg, a.todas)
            espera = cfg["worker"].getint("espera")
            if n:
                log.info("pasada hecha: %d fuente(s)", n)
        except KeyboardInterrupt:
            return 0
        except Exception as e:
            log.warning("pasada fallida: %s", e)
            espera = cfg["worker"].getint("espera_error")
        if a.una_vez:
            return 0
        try:
            time.sleep(espera)
        except KeyboardInterrupt:
            return 0


if __name__ == "__main__":
    sys.exit(main())
