/* FullVendor PWA service worker — installability + light offline shell */
const FV_SW_VERSION = 'fv-pwa-v1';
const FV_SHELL_CACHE = FV_SW_VERSION + '-shell';

const FV_SHELL_URLS = [
  './',
  './company/',
  './manifest.json',
  './companyasset/images/pwa/icon-192.png',
  './companyasset/images/pwa/icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(FV_SHELL_CACHE)
      .then((cache) => cache.addAll(FV_SHELL_URLS).catch(() => undefined))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys
          .filter((key) => key.startsWith('fv-pwa-') && key !== FV_SHELL_CACHE)
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  // API / dynamic endpoints: network only
  if (
    url.pathname.indexOf('/api/') !== -1
    || url.pathname.indexOf('/index.php') !== -1
  ) {
    return;
  }

  // Navigations: network first, cache fallback
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(FV_SHELL_CACHE).then((cache) => cache.put(req, copy)).catch(() => undefined);
          return res;
        })
        .catch(() => caches.match(req).then((cached) => cached || caches.match('./company/')))
    );
    return;
  }

  // Static assets: cache first, then network
  if (
    url.pathname.indexOf('/companyasset/') !== -1
    || url.pathname.indexOf('/frontend/') !== -1
    || /\.(?:css|js|png|jpg|jpeg|gif|svg|webp|woff2?)$/i.test(url.pathname)
  ) {
    event.respondWith(
      caches.match(req).then((cached) => {
        if (cached) {
          return cached;
        }
        return fetch(req).then((res) => {
          if (res && res.ok) {
            const copy = res.clone();
            caches.open(FV_SHELL_CACHE).then((cache) => cache.put(req, copy)).catch(() => undefined);
          }
          return res;
        });
      })
    );
  }
});
