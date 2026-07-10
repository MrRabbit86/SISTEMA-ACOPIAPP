/* Service Worker — Sistema de Reciclaje Zona Sur (PWA ligera). */
const CACHE = 'reciclaje-v3';
const CAPARAZON = [
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(CAPARAZON)).then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  // Las llamadas a la API siempre van por red (evita datos de sesión obsoletos).
  if (url.pathname.startsWith('/api/')) {
    return;
  }

  // Assets con hash de Vite: cache-first; el resto: network-first.
  const esAssetVite = url.pathname.startsWith('/build/');

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((resp) => {
          const copia = resp.clone();
          caches.open(CACHE).then((cache) => cache.put(request, copia));
          return resp;
        })
        .catch(() => caches.match(request).then((r) => r || caches.match('/dashboard'))),
    );
    return;
  }

  if (esAssetVite) {
    event.respondWith(
      caches.match(request).then((r) => r || fetch(request).then((resp) => {
        const copia = resp.clone();
        caches.open(CACHE).then((cache) => cache.put(request, copia));
        return resp;
      })),
    );
  }
});