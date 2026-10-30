#!/usr/bin/env python3
"""
Worker generico de IA de Deltha (migra-173).

Corre en el PC de la redaccion, junto al modelo local (Strata con Qwen3.8-Flash-Next, o llama-server con Bonsai:
lo arranca local/moderador/arrancar-modelo.ps1). Pide trabajo a Deltha (/api/ia.php), pasa cada uno por el modelo
con el prompt y el esquema de SU tarea (que se editan en admin → IA y se bajan en cada pasada) y devuelve el resultado.
El servidor nunca llama a este PC: si el PC esta apagado, la cola espera y la web sigue igual.

Sirve todas las tareas activas, la moderacion incluida (los trabajos "m123" vienen de mod_queue): con este worker en
marcha, moderador.py sobra (si corren los dos, se reparten la cola sin pisarse).

Uso:
    python ia.py                         bucle normal (Ctrl+C para parar)
    python ia.py --una-vez               una sola pasada y sale
    python ia.py --tareas                lista las tareas activas que sirve Deltha y sale
    python ia.py --prueba TAREA "texto"  pasa un texto por el modelo con el prompt de esa tarea, sin tocar la cola

Solo biblioteca estandar (Python 3.9+). Configuracion en ia.ini (copiar de ia.ini.example).
"""
import argparse
import configparser
import json
import logging
import re
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

AQUI = Path(__file__).resolve().parent
log = logging.getLogger("ia")


# ---------------------------------------------------------------- configuracion
def cargar_config(ruta: Path) -> configparser.ConfigParser:
    cfg = configparser.ConfigParser()
    cfg.read_dict({
        "deltha": {"url": "https://deltha.eu", "token": ""},
        "modelo": {"url": "http://127.0.0.1:8080", "api_key": "", "timeout": "180", "nombre": "Qwen3.8-Flash-Next-IQ2_XS"},
        "worker": {"lote": "4", "espera": "20", "espera_error": "60"},
    })
    if ruta.exists():
        cfg.read(ruta, encoding="utf-8")
    return cfg


# ---------------------------------------------------------------- HTTP
def http_json(url, datos=None, cabeceras=None, timeout=30):
    cuerpo = None if datos is None else json.dumps(datos, ensure_ascii=False).encode("utf-8")
    req = urllib.request.Request(url, data=cuerpo, method="POST" if cuerpo is not None else "GET")
    req.add_header("User-Agent", "DelthaIA/1.0")
    req.add_header("Accept", "application/json")
    if cuerpo is not None:
        req.add_header("Content-Type", "application/json; charset=utf-8")
    for k, v in (cabeceras or {}).items():
        req.add_header(k, v)
    try:
        with urllib.request.urlopen(req, timeout=timeout) as r:
            cuerpo = r.read().decode("utf-8") or "{}"
            try:
                return json.loads(cuerpo)
            except ValueError:
                return {"_texto": cuerpo[:500]}
    except urllib.error.HTTPError as e:
        detalle = e.read().decode("utf-8", "replace")[:500]
        raise RuntimeError(f"HTTP {e.code} en {url}: {detalle}") from None


class Deltha:
    def __init__(self, cfg):
        self.base = cfg["deltha"]["url"].rstrip("/") + "/api/ia.php"
        self.cab = {"X-Deltha-Token": cfg["deltha"]["token"]}
        self.tareas = {}
        self.hash = None

    def ping(self):
        return http_json(f"{self.base}?act=ping", cabeceras=self.cab)

    def bajar_tareas(self):
        """Las tareas activas con su prompt y esquema; avisa en el registro cuando algo cambia en admin → IA."""
        r = http_json(f"{self.base}?act=tareas", cabeceras=self.cab)
        if r.get("hash") != self.hash:
            self.tareas = r.get("tareas", {})
            if self.hash is not None:
                log.info("tareas actualizadas desde Deltha: %s", ", ".join(sorted(self.tareas)) or "ninguna")
            self.hash = r.get("hash")
        return self.tareas

    def lease(self, n):
        return http_json(f"{self.base}?act=lease&n={n}", cabeceras=self.cab)

    def resultado(self, job_id, res, modelo, ms=None, tokens=None):
        return http_json(f"{self.base}?act=result", {"id": job_id, "result": res, "model": modelo, "ms": ms, "tokens": tokens}, self.cab)

    def fallo(self, job_id, error):
        return http_json(f"{self.base}?act=fail", {"id": job_id, "error": str(error)[:300]}, self.cab)


# ---------------------------------------------------------------- modelo
class Modelo:
    def __init__(self, cfg):
        self.base = cfg["modelo"]["url"].rstrip("/")
        self.timeout = int(cfg["modelo"]["timeout"])
        self.nombre = cfg["modelo"]["nombre"]
        self.cab = {"Authorization": f"Bearer {cfg['modelo']['api_key']}"} if cfg["modelo"]["api_key"] else {}
        self.formato = "json_schema"   # si el servidor no lo admite, se baja a json_object y luego a texto libre

    def listo(self):
        # llama-server devuelve {"status":"ok"}; Strata responde 200 con otro cuerpo: vale cualquier 200 que no diga lo contrario
        try:
            r = http_json(self.base + "/health", cabeceras=self.cab, timeout=5)
            return str(r.get("status", "ok")).lower() in ("ok", "ready", "healthy") if isinstance(r, dict) else True
        except Exception:
            return False

    def _peticion(self, tarea, mensajes):
        p = {
            "messages": mensajes,
            "temperature": float(tarea.get("temperature") or 0),
            "max_tokens": int(tarea.get("max_tokens") or 400),
            "chat_template_kwargs": {"enable_thinking": False},   # Qwen hibrido (llama-server): sin razonamiento, respuesta directa
            "reasoning_effort": "none",                            # lo mismo para Strata (sin esto razona en modo high)
        }
        if tarea.get("model"):
            p["model"] = tarea["model"]
        esquema = tarea.get("schema")
        if esquema and self.formato == "json_schema":
            p["response_format"] = {"type": "json_schema", "json_schema": {"name": "respuesta", "strict": True, "schema": esquema}}
        elif esquema and self.formato == "json_object":
            p["response_format"] = {"type": "json_object"}
        return p

    def completar(self, tarea, usuario):
        """Devuelve (resultado, ms, tokens). Con esquema: el JSON validado a grandes rasgos; sin esquema: {"text": ...}."""
        mensajes = [{"role": "system", "content": tarea.get("system") or ""}, {"role": "user", "content": usuario}]
        t0 = time.time()
        while True:
            try:
                r = http_json(self.base + "/v1/chat/completions", self._peticion(tarea, mensajes), self.cab, self.timeout)
                break
            except RuntimeError as e:
                if "HTTP 400" in str(e) and tarea.get("schema") and self.formato != "libre":
                    self.formato = {"json_schema": "json_object", "json_object": "libre"}[self.formato]
                    log.warning("el servidor no acepta el formato pedido; paso a %s", self.formato)
                    continue
                raise
        salida = r["choices"][0]["message"].get("content") or ""
        ms = int((time.time() - t0) * 1000)
        tokens = (r.get("usage") or {}).get("completion_tokens")
        if tarea.get("schema"):
            return interpretar(salida, tarea["schema"]), ms, tokens
        return {"text": re.sub(r"<think>.*?</think>", "", salida, flags=re.S).strip()}, ms, tokens

    def embeddings(self, tarea, texto):
        p = {"input": texto}
        if tarea.get("model"):
            p["model"] = tarea["model"]
        t0 = time.time()
        r = http_json(self.base + "/v1/embeddings", p, self.cab, self.timeout)
        return {"embedding": r["data"][0]["embedding"]}, int((time.time() - t0) * 1000), (r.get("usage") or {}).get("total_tokens")


def interpretar(salida: str, esquema: dict) -> dict:
    """Extrae el JSON de la respuesta y comprueba que tenga las claves obligatorias del esquema. ValueError si no sirve."""
    s = re.sub(r"<think>.*?</think>", "", salida, flags=re.S).strip()
    m = re.search(r"\{.*\}", s, flags=re.S)
    if not m:
        raise ValueError(f"respuesta sin JSON: {s[:200]!r}")
    v = json.loads(m.group(0))
    if not isinstance(v, dict):
        raise ValueError("la respuesta no es un objeto JSON")
    faltan = [k for k in esquema.get("required", []) if k not in v]
    if faltan:
        raise ValueError(f"faltan claves en la respuesta: {', '.join(faltan)}")
    for k, prop in (esquema.get("properties") or {}).items():
        if k in v and prop.get("enum") and isinstance(v[k], str):
            v[k] = v[k].strip().lower()
            if v[k] not in prop["enum"]:
                raise ValueError(f"valor fuera del enum en {k}: {v[k]!r}")
    return v


# ---------------------------------------------------------------- bucle
def pasada(deltha: Deltha, modelo: Modelo, lote: int) -> int:
    tareas = deltha.bajar_tareas()
    r = deltha.lease(lote)
    if not r.get("enabled", False):
        log.info("la IA esta apagada en Deltha (ia_enabled = 0 y moderacion apagada)")
        return 0
    jobs = r.get("jobs", [])
    for j in jobs:
        etiqueta = f"{j['task']} #{j['id']}"
        tarea = tareas.get(j["task"])
        if not tarea:
            log.error("%s: Deltha me da un trabajo de una tarea que no tengo; lo devuelvo", etiqueta)
            try:
                deltha.fallo(j["id"], "tarea desconocida para el worker")
            except Exception as e2:
                log.error("%s: no pude avisar del fallo: %s", etiqueta, e2)
            continue
        try:
            if tarea.get("kind") == "embed":
                res, ms, tokens = modelo.embeddings(tarea, j["user"])
            else:
                res, ms, tokens = modelo.completar(tarea, j["user"])
        except Exception as e:
            log.error("%s: fallo del modelo: %s", etiqueta, e)
            try:
                deltha.fallo(j["id"], e)
            except Exception as e2:
                log.error("%s: no pude avisar del fallo: %s", etiqueta, e2)
            continue
        try:
            out = deltha.resultado(j["id"], res, modelo.nombre, ms, tokens)
        except Exception as e:   # p. ej. la reserva caduco: volvera a la cola sola
            log.error("%s: Deltha no acepto el resultado: %s", etiqueta, e)
            continue
        resumen = json.dumps(res, ensure_ascii=False)
        log.info("%s → %s (%s ms) %s", etiqueta, out.get("status"), ms, resumen[:160] + ("…" if len(resumen) > 160 else ""))
    return len(jobs)


def bucle(cfg, una_vez=False):
    deltha, modelo = Deltha(cfg), Modelo(cfg)
    if not cfg["deltha"]["token"]:
        sys.exit("Falta [deltha] token en ia.ini (el mismo que [ia] token, o [mod] token, en /etc/deltha/config.ini).")
    lote = int(cfg["worker"]["lote"])
    espera, espera_error = int(cfg["worker"]["espera"]), int(cfg["worker"]["espera_error"])
    avisado = False
    while True:
        try:
            if not modelo.listo():
                if not avisado:
                    log.warning("el modelo no responde en %s; espero (¿esta arrancado arrancar-modelo.ps1?)", modelo.base)
                    avisado = True
                if una_vez:
                    return
                time.sleep(espera)
                continue
            if avisado:
                log.info("modelo listo")
                avisado = False
            n = pasada(deltha, modelo, lote)
            if una_vez:
                return
            if n == 0:
                time.sleep(espera)
        except KeyboardInterrupt:
            log.info("parado")
            return
        except Exception as e:
            log.error("%s", e)
            if una_vez:
                return
            time.sleep(espera_error)


def main():
    ap = argparse.ArgumentParser(description="Worker generico de IA de Deltha")
    ap.add_argument("--config", default=str(AQUI / "ia.ini"))
    ap.add_argument("--una-vez", action="store_true")
    ap.add_argument("--tareas", action="store_true")
    ap.add_argument("--prueba", nargs=2, metavar=("TAREA", "TEXTO"))
    a = ap.parse_args()

    logging.basicConfig(level=logging.INFO, format="%(asctime)s  %(message)s", datefmt="%H:%M:%S",
                        handlers=[logging.StreamHandler(), logging.FileHandler(AQUI / "ia.log", encoding="utf-8")])
    try:
        sys.stdout.reconfigure(encoding="utf-8")
    except Exception:
        pass
    cfg = cargar_config(Path(a.config))
    if not cfg["deltha"]["token"]:
        sys.exit("Falta [deltha] token en ia.ini (el mismo que [ia] token, o [mod] token, en /etc/deltha/config.ini).")

    if a.tareas:
        for k, t in sorted(Deltha(cfg).bajar_tareas().items()):
            print(f"{k:14} {t.get('kind'):5} temp {t.get('temperature')} · {t.get('max_tokens')} tokens · "
                  f"{'con esquema' if t.get('schema') else 'texto libre'} · prompt {len(t.get('system') or '')} car.")
    elif a.prueba:
        tareas = Deltha(cfg).bajar_tareas()
        tarea = tareas.get(a.prueba[0])
        if not tarea:
            sys.exit(f"Deltha no tiene activa la tarea {a.prueba[0]!r}; activas: {', '.join(sorted(tareas)) or 'ninguna'}")
        modelo = Modelo(cfg)
        if not modelo.listo():
            sys.exit(f"el modelo no responde en {modelo.base}")
        res, ms, tokens = modelo.completar(tarea, a.prueba[1])
        print(json.dumps(res, ensure_ascii=False, indent=2))
        print(f"({ms} ms, {tokens} tokens)")
    else:
        log.info("worker de IA de Deltha: %s ← %s", cfg["deltha"]["url"], cfg["modelo"]["url"])
        bucle(cfg, a.una_vez)


if __name__ == "__main__":
    main()
