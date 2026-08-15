{{--
    Inisialisasi tema — disertakan tepat setelah <body> di SEMUA layout:
        @include('partials.theme-init')

    Dipasang sedini mungkin supaya halaman tidak berkedip terang lebih dulu
    sebelum JS utama dimuat.

    Preferensi disimpan di localStorage['theme'] dengan tiga nilai:
        'light'  — selalu terang
        'dark'   — selalu gelap
        'system' — mengikuti tema perangkat (default bila belum pernah dipilih)

    Nilai lama 'light'/'dark' tetap sah, jadi pengguna lama tidak kehilangan
    pilihannya. Yang belum pernah memilih kini otomatis ikut tema perangkat.

    Menyediakan window.kssTheme sebagai SATU-SATUNYA sumber kebenaran supaya
    tidak ada lagi salinan `localStorage.getItem('theme') === 'dark'` yang
    tersebar dan gampang ketinggalan saat aturannya berubah:
        kssTheme.get()           -> 'light' | 'dark' | 'system'
        kssTheme.resolve(pref?)  -> true bila hasil akhirnya gelap
        kssTheme.apply(pref)     -> simpan + terapkan + siarkan
--}}
<script>
(function () {
    var KEY = 'theme';
    var media = window.matchMedia('(prefers-color-scheme: dark)');

    function get() {
        var value = localStorage.getItem(KEY);
        return (value === 'light' || value === 'dark' || value === 'system') ? value : 'system';
    }

    function resolve(pref) {
        pref = pref || get();
        return pref === 'dark' || (pref === 'system' && media.matches);
    }

    function paint(isDark) {
        if (document.body) document.body.classList.toggle('dark-mode', isDark);
        document.documentElement.classList.toggle('kss-dark-theme', isDark);
    }

    function apply(pref, persist) {
        if (persist !== false) localStorage.setItem(KEY, pref);
        var isDark = resolve(pref);
        paint(isDark);
        document.dispatchEvent(new CustomEvent('kss:theme-change', {
            detail: { preference: pref, dark: isDark },
        }));
        return isDark;
    }

    window.kssTheme = { get: get, resolve: resolve, apply: apply, paint: paint, media: media };

    paint(resolve());

    // Saat preferensi 'system', perubahan tema perangkat langsung diikuti tanpa
    // perlu muat ulang — dan tanpa menimpa preferensi yang tersimpan.
    function onSystemChange() {
        if (get() === 'system') apply('system', false);
    }
    if (media.addEventListener) media.addEventListener('change', onSystemChange);
    else if (media.addListener) media.addListener(onSystemChange);
})();
</script>
