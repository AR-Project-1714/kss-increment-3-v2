{{--
    PWA / mode lapangan — dipakai semua layout lewat: @include('partials.pwa')
    (diletakkan di dalam <head>).

    - manifest.webmanifest: aplikasi bisa di-install ke home screen (standalone).
    - sw.js: cache aset statis + fallback offline agar aplikasi tetap terbuka
      saat sinyal buruk di lapangan.
--}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#2563EB">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Laporan KSS">
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
