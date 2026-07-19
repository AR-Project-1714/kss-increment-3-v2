/*
 * Service worker Sistem Laporan KSS — mode lapangan.
 *
 * Aplikasi sengaja TIDAK installable sebagai PWA standalone (tidak ada
 * manifest): alur tinjau/cetak/unduh PDF memakai target="_blank" di banyak
 * tempat, dan di mode standalone tab baru terpaksa jadi window terpisah.
 * Service worker tidak memerlukan mode standalone, jadi cache aset dan
 * fallback offline tetap berjalan penuh lewat tab browser biasa.
 *
 * Strategi:
 *  - Precache halaman fallback offline.
 *  - Navigasi halaman: network-first; saat jaringan putus tampilkan offline.html
 *    (halaman laporan berisi data dinamis milik user, tidak di-cache).
 *  - Aset statis same-origin (build Vite, gambar assets, favicon): cache-first —
 *    nama file build sudah berhash / dipanggil dengan ?v=filemtime sehingga URL
 *    ikut berubah setiap file berubah, aman di-cache lama.
 *  - Aset vendor (Bootstrap, Poppins, uicons) dan aset lintas origin:
 *    stale-while-revalidate — URL-nya tidak berversi, jadi cache-first akan
 *    membuat versi lama nyangkut selamanya saat file vendor di-update.
 */

const CACHE_VERSION = 'kss-offline-v7';
const STATIC_CACHE = CACHE_VERSION + '-static';
const RUNTIME_CACHE = CACHE_VERSION + '-runtime';

const OFFLINE_URL = new URL('offline.html', self.registration.scope).href;
const OFFLINE_ILLUSTRATION_URL = new URL('assets/kss-offline_state.webp', self.registration.scope).href;

// offline.html + ilustrasinya — CSS tetap inline, tapi gambar maskot harus
// ikut di-precache di sini juga, kalau tidak halaman offline sendiri akan
// tampil dengan gambar rusak saat benar-benar tanpa koneksi. Satu URL 404 di
// sini membuat cache.addAll menolak dan install service worker gagal total,
// jadi daftar ini sengaja dijaga seminimal mungkin.
const PRECACHE_URLS = [
    OFFLINE_URL,
    OFFLINE_ILLUSTRATION_URL,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.indexOf(CACHE_VERSION) !== 0)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

function isVendorAsset(url) {
    return url.origin === self.location.origin
        && url.pathname.indexOf('/vendor/') !== -1;
}

function isStaticAsset(url) {
    if (url.origin !== self.location.origin) {
        return false;
    }

    // Tanda tangan bersifat privat — jangan pernah di-cache.
    if (url.pathname.indexOf('/signatures/') !== -1) {
        return false;
    }

    // Vendor tidak berversi — ditangani staleWhileRevalidate, bukan cache-first.
    if (isVendorAsset(url)) {
        return false;
    }

    return url.pathname.indexOf('/build/') !== -1
        || url.pathname.indexOf('/assets/') !== -1
        || url.pathname.indexOf('/js/') !== -1
        || /\.(png|jpg|jpeg|svg|gif|ico|woff2?|ttf|css|js)$/.test(url.pathname);
}

function isCdnAsset(url) {
    return url.origin !== self.location.origin
        && /^(https?:)$/.test(url.protocol);
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }

    const response = await fetch(request);
    if (response && response.ok) {
        const cache = await caches.open(RUNTIME_CACHE);
        cache.put(request, response.clone());
    }
    return response;
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await cache.match(request);

    const network = fetch(request)
        .then((response) => {
            // Response CDN bisa opaque (type 'opaque'); tetap boleh disimpan.
            if (response && (response.ok || response.type === 'opaque')) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);

    return cached || network.then((response) => response || Response.error());
}

async function pageNetworkFirst(request) {
    try {
        return await fetch(request);
    } catch (_) {
        const offline = await caches.match(OFFLINE_URL);
        return offline || Response.error();
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return; // POST/PUT (simpan laporan) tidak pernah dilayani cache.
    }

    const url = new URL(request.url);

    if (request.mode === 'navigate') {
        event.respondWith(pageNetworkFirst(request));
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (isVendorAsset(url) || isCdnAsset(url)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});
