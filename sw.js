const CACHE_NAME = 'confusa-pwa-cache-v5';
const urlsToCache = [
  '/',
  '/site.webmanifest',
  '/css/newindex.css',
  '/css/login.css',
  '/js/prefixfree.js'
];

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      // Use Map to handle failures individually without blocking install
      return Promise.allSettled(
        urlsToCache.map(url => 
          cache.add(url).catch(err => console.warn('Failed to cache during install:', url, err))
        )
      );
    })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.filter(name => name !== CACHE_NAME).map(name => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  // Only handle GET requests and HTTP/HTTPS schemes
  if (event.request.method !== 'GET' || !event.request.url.startsWith('http')) {
    return;
  }

  // Network-First strategy: always try the network first.
  // This guarantees online users get the latest assets (including icons and styles) immediately.
  event.respondWith(
    fetch(event.request)
      .then(response => {
        return response;
      })
      .catch(() => {
        // Fallback to cache if offline
        return caches.match(event.request, { ignoreSearch: true });
      })
  );
});
