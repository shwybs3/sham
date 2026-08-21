/* Service Worker — دفتر الدكان v3 */
const CACHE_VER = 'dukkan-v3';
const STATIC_ASSETS = [
  './',
  './assets/css/style.css',
  './manifest.json',
  './offline.html',
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_VER)
      .then(c => c.addAll(STATIC_ASSETS.map(u => new Request(u, {cache: 'reload'}))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k !== CACHE_VER).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
      .then(() => {
        self.clients.matchAll({type: 'window'}).then(clients =>
          clients.forEach(c => c.postMessage({type: 'SW_UPDATED', version: CACHE_VER}))
        );
      })
  );
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  if (e.request.method !== 'GET') return;
  if (url.pathname.includes('/api/') || url.pathname.includes('/admin/')) return;
  if (url.pathname.endsWith('sw.js') || url.pathname.endsWith('sw.php')) return;

  e.respondWith(
    fetch(e.request)
      .then(res => {
        if (res && res.status === 200 && res.type !== 'opaque') {
          caches.open(CACHE_VER).then(c => c.put(e.request, res.clone()));
        }
        return res;
      })
      .catch(() =>
        caches.match(e.request).then(cached => cached || caches.match('./offline.html'))
      )
  );
});

self.addEventListener('message', e => {
  if (e.data === 'SKIP_WAITING') self.skipWaiting();
});
