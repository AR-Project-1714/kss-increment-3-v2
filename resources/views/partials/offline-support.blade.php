{{--
    Dukungan mode lapangan — dipakai semua layout lewat:
        @include('partials.offline-support')
    (diletakkan di dalam <head>).

    - sw.js: cache aset statis + fallback offline agar aplikasi tetap terbuka
      saat sinyal buruk di lapangan.

    Aplikasi sengaja TIDAK installable sebagai PWA standalone (tidak ada
    manifest). Alur tinjau, cetak, dan unduh PDF memakai target="_blank" di
    banyak tempat; pada mode standalone tab baru terpaksa dibuka sebagai window
    terpisah, yang justru mengganggu petugas. Service worker tidak memerlukan
    mode standalone, jadi cache aset dan fallback offline tetap didapat penuh
    lewat tab browser biasa.

    theme-color mewarnai toolbar Chrome Android, dan apple-touch-icon dipakai
    bila petugas menaruh shortcut di home screen — keduanya berlaku untuk tab
    browser biasa, bukan bagian dari install PWA.
--}}
<meta name="theme-color" content="#2563EB">
<link rel="apple-touch-icon" href="{{ asset('assets/apple-touch-icon.png') }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@json(asset('sw.js'))).catch(function () {
                // Best-effort: tanpa service worker aplikasi tetap berjalan normal.
            });
        });
    }
</script>
