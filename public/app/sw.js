/* Deltha — service worker mínimo.
   Cachea solo estáticos propios (CSS, logos, iconos) y sirve /app/offline.php cuando no hay red.
   Lo que se guarda al instalar lo dice /app/precache.php (el logo y el icono en vigor están en /media/logo y cambian de nombre
   al subir otros desde Admin > Logo; antes iban fijos aquí los de serie de /img y la página sin conexión salía con el logo viejo).
   Todo lo dinámico (PHP, media subida) va siempre al servidor: nada de HTML cacheado, nada de votos
   ni comentarios en cola. La version es DDD_CSS_VER (lib/app.php): subirla al cambiar este fichero o el CSS. */
const VER = self.location.search.replace(/^\?v=/, '') || '0';
const CACHE = 'ddd-' + VER;
const OFFLINE = '/app/offline.php';
const FALLBACK = [OFFLINE, '/css/ddd.css', '/img/favicon.png'];   // si /app/precache.php no responde

self.addEventListener('install', e => {
  e.waitUntil(
    fetch('/app/precache.php', { cache: 'no-store' }).then(r => r.ok ? r.json() : FALLBACK).catch(() => FALLBACK)
      .then(list => caches.open(CACHE).then(c => Promise.allSettled(list.map(u => c.add(u)))))   // uno a uno: un icono que falte no impide instalar el resto
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(ks => Promise.all(ks.filter(k => k !== CACHE).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Navegaciones: red primero; si falla, página sin conexión.
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
    return;
  }

  // Estáticos propios (/css, /img, /app) y el logo e iconos subidos (/media/logo): caché primero, y se rellena al vuelo.
  if (/^\/(css|img|app|media\/logo)\//.test(url.pathname) && !url.pathname.endsWith('sw.js') && !url.pathname.endsWith('.php')) {   // manifest.php, precache.php y offline.php son dinamicos: solo en la precarga
    e.respondWith(
      caches.match(req).then(hit => hit || fetch(req).then(res => {
        if (res.ok) { const copy = res.clone(); caches.open(CACHE).then(c => c.put(req, copy)); }
        return res;
      }))
    );
  }
  // El resto (PHP, /media) no se toca.
});
