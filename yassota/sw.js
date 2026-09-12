/* YASSOTA — service worker (app shell + offline fallback) */
const CACHE = 'yassota-v1';
const SHELL = ['assets/app.css', 'assets/app.js', 'assets/favicon.svg', 'manifest.json'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.pathname.includes('/api') || req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match(req)));
    return;
  }
  if (/\.(css|js|svg|png|jpg|jpeg|webp|woff2?)$/.test(url.pathname)) {
    e.respondWith(caches.match(req).then((r) => r || fetch(req).then((resp) => {
      const copy = resp.clone(); caches.open(CACHE).then((c) => c.put(req, copy)); return resp;
    })));
  }
});
