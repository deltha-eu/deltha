/* Deltha — service worker mínimo.
   Cachea solo estáticos propios (CSS, logos, iconos) y sirve /app/offline.html cuando no hay red.
   Todo lo dinámico (PHP, media subida) va siempre al servidor: nada de HTML cacheado, nada de votos
   ni comentarios en cola. La version es DDD_CSS_VER (lib/app.php): subirla al cambiar este fichero o el CSS. */
const VER = self.location.search.replace(/^\?v=/, '') || '0';
const CACHE = 'ddd-' + VER;
const OFFLINE = '/app/offline.html';
const PRECACHE = [
  OFFLINE, '/css/ddd.css',
  '/img/logo.png', '/img/logo-dark.png', '/img/favicon.png', '/img/icon-180.png', '/img/contralgoritmia.png',
  '/app/icon-192.png', '/app/icon-512.png'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
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

  // Estáticos propios (/css, /img, /app): caché primero, y se rellena al vuelo.
  if (/^\/(css|img|app)\//.test(url.pathname) && !url.pathname.endsWith('sw.js') && !url.pathname.endsWith('.php')) {   // manifest.php es dinamico: nunca en cache
    e.respondWith(
      caches.match(req).then(hit => hit || fetch(req).then(res => {
        if (res.ok) { const copy = res.clone(); caches.open(CACHE).then(c => c.put(req, copy)); }
        return res;
      }))
    );
  }
  // El resto (PHP, /media) no se toca.
});
