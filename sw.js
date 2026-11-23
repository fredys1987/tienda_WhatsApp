// Service worker mínimo: permite instalar la tienda como app en el celular.
// No guarda nada en caché para que los precios y productos siempre estén al día.
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });
self.addEventListener('fetch', function () { /* la red responde normalmente */ });
