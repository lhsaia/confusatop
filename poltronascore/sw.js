const CACHE_NAME = 'poltrona-score-v3';
const ASSETS = [
  '/poltronascore/',
  '/poltronascore/index.php',
  '/poltronascore/css/poltrona.css',
  '/poltronascore/js/poltrona.js',
  '/poltronascore/manifest.json',
  '/poltronascore/icons/icon-192.png',
  '/poltronascore/icons/icon-512.png',
  '/poltronascore/icons/favicon.png',
  'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Montserrat:wght@400;500;600;700&display=swap',
  'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200'
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);
  
  // If it is an API request, use Network-First strategy
  if (url.pathname.includes('/api/poltronascore/')) {
    e.respondWith(
      fetch(e.request)
        .then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(e.request, clone);
          });
          return response;
        })
        .catch(() => {
          return caches.match(e.request);
        })
    );
  } else {
    // For static assets, use Stale-While-Revalidate
    e.respondWith(
      caches.match(e.request).then((cachedResponse) => {
        if (cachedResponse) {
          fetch(e.request).then((networkResponse) => {
            if (networkResponse.status === 200) {
              caches.open(CACHE_NAME).then((cache) => cache.put(e.request, networkResponse));
            }
          }).catch(() => {});
          return cachedResponse;
        }
        return fetch(e.request);
      })
    );
  }
});
