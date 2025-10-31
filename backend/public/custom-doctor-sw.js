const CACHE_NAME = 'custom-doctor-login-v1';
const OFFLINE_URL = './custom-doctor-offline.html';
const PRECACHE_URLS = [
  './custom-doctor-login',
  OFFLINE_URL,
  './custom-doctor-manifest.webmanifest',
  './custom-doctor-icon-192.png',
  './custom-doctor-icon-512.png'
];

const NETWORK_ONLY_PATTERNS = [
  /\/socket\.io\//,
  /\/api\/(?:v[0-9]+\/)?socket/,
  /\/socket\//
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  if (request.method !== 'GET') {
    return;
  }

  if (NETWORK_ONLY_PATTERNS.some((regex) => regex.test(url.pathname))) {
    event.respondWith(fetch(request));
    return;
  }

  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          return response;
        })
        .catch(async () => {
          const cache = await caches.open(CACHE_NAME);
          const cachedResponse = await cache.match(request);
          return cachedResponse || cache.match(OFFLINE_URL);
        })
    );
    return;
  }

  if (url.origin === self.location.origin) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) {
          return cached;
        }
        return fetch(request)
          .then((response) => {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
            return response;
          })
          .catch((err) => {
            if (request.destination === 'document') {
              return caches.match(OFFLINE_URL);
            }
            throw err;
          });
      })
    );
  }
});
