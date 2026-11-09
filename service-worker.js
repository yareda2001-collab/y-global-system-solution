const CACHE_NAME = "y-global-static-v4";
const APP_ROOT = self.registration.scope;
const OFFLINE_URL = new URL("offline.html", APP_ROOT).href;
const PRECACHE_URLS = [
  OFFLINE_URL,
  new URL("assets/img/pwa-192.png", APP_ROOT).href,
  new URL("assets/img/pwa-512.png", APP_ROOT).href
];
const STATIC_FILE_PATTERN = /\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?)$/i;

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => Promise.all(
        cacheNames
          .filter((cacheName) => cacheName.startsWith("y-global-") && cacheName !== CACHE_NAME)
          .map((cacheName) => caches.delete(cacheName))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  const request = event.request;
  const requestUrl = new URL(request.url);

  if (request.method !== "GET" || requestUrl.origin !== self.location.origin) {
    return;
  }

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request).catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  if (!STATIC_FILE_PATTERN.test(requestUrl.pathname)) {
    return;
  }

  event.respondWith(
    fetch(request).then((response) => {
      if (response.ok && response.type === "basic") {
        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, responseToCache));
      }
      return response;
    }).catch(() => caches.match(request))
  );
});
