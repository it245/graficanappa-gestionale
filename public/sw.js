// MES Grafica Nappa — Service Worker v2.0
const CACHE_NAME = 'mes-cache-v2';
const OFFLINE_URL = '/offline.html';

// Risorse da precachare (shell dell'app)
const PRECACHE_URLS = [
    '/offline.html',
    '/images/logo_gn.png',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'
];

// Install: precache risorse statiche
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate: pulisci cache vecchie
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch: network-first per pagine, cache-first per asset statici
self.addEventListener('fetch', event => {
    const { request } = event;

    // Skip non-GET
    if (request.method !== 'GET') return;

    // Skip API calls e fetch AJAX
    if (request.headers.get('X-CSRF-TOKEN') || request.headers.get('X-Op-Token')) return;
    if (request.url.includes('/api/') || request.url.includes('/tracking')) return;

    // Asset statici (font, CSS, JS, immagini): cache-first
    if (request.url.match(/\.(css|js|woff2?|ttf|png|jpg|jpeg|svg|ico)(\?|$)/)) {
        event.respondWith(
            caches.match(request).then(cached => {
                if (cached) return cached;
                return fetch(request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Pagine HTML: network-first con fallback offline
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache la risposta per uso offline
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                    return response;
                })
                .catch(() => {
                    // Offline: prova cache, poi pagina offline
                    return caches.match(request).then(cached => cached || caches.match(OFFLINE_URL));
                })
        );
        return;
    }
});

// Push notification handler
self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'MES Grafica Nappa';
    const options = {
        body: data.body || '',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/icon-72x72.png',
        tag: data.tag || 'mes-notification',
        data: { url: data.url || '/' },
        vibrate: [200, 100, 200],
        requireInteraction: data.requireInteraction || false
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

// Click su notifica: apri URL
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            // Se c'è già una finestra aperta, focusala
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            // Altrimenti apri nuova finestra
            return clients.openWindow(url);
        })
    );
});
