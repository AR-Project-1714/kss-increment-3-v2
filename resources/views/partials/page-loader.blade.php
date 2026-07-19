{{--
    partials/page-loader.blade.php

    Dua indikator loading yang saling melengkapi (di-include di <head> tiap layout):

    - SPINNER full-screen (#sk-overlay, markup tetap di tiap layout) hanya untuk
      FIRST LOAD / hard refresh. Saat berpindah antar halaman internal, spinner
      disembunyikan agar tidak berkedip.
    - TRICKLE BAR tipis di atas layar (gaya NProgress) muncul saat klik link
      internal atau submit form yang benar-benar berpindah halaman. Memberi
      umpan balik selama menunggu halaman berikutnya, tanpa menggelapkan layar.

    Deteksi navigasi memakai flag sessionStorage: di-set saat klik/submit yang
    berpindah halaman, lalu dibaca sedini mungkin di halaman tujuan untuk
    menyembunyikan spinner. Warna bar memakai --blue-main dari CSS layout.
--}}
<style>
    .kss-progress {
        position: fixed;
        inset: 0 0 auto 0;
        height: 3px;
        z-index: 10000;
        pointer-events: none;
        opacity: 0;
        transition: opacity .25s ease;
    }
    .kss-progress.is-active { opacity: 1; }
    .kss-progress__bar {
        height: 100%;
        width: 0;
        background: var(--blue-main, #2563EB);
        box-shadow: 0 0 8px var(--blue-main, #2563EB), 0 0 4px var(--blue-main, #2563EB);
        border-radius: 0 3px 3px 0;
        transition: width .2s ease;
    }

    /* Saat berpindah halaman internal, spinner first-load ditekan agar tak berkedip. */
    html.kss-is-nav .sk-overlay { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .kss-progress,
        .kss-progress__bar { transition: none; }
    }
</style>
<script>
    (function () {
        // 1) Deteksi sedini mungkin (sebelum body dicat): jika halaman ini dibuka
        //    lewat navigasi internal, tandai <html> supaya CSS langsung
        //    menyembunyikan spinner first-load.
        try {
            if (sessionStorage.getItem('kssNav') === '1') {
                document.documentElement.classList.add('kss-is-nav');
                sessionStorage.removeItem('kssNav');
            }
        } catch (e) {}

        var track = null, bar = null, value = 0, running = false;
        var trickleTimer = null, safetyTimer = null;

        function ensureBar() {
            if (track) return true;
            if (!document.body) return false;
            track = document.createElement('div');
            track.className = 'kss-progress';
            track.setAttribute('aria-hidden', 'true');
            bar = document.createElement('div');
            bar.className = 'kss-progress__bar';
            track.appendChild(bar);
            document.body.appendChild(track);
            return true;
        }

        function setValue(v) {
            value = v;
            if (bar) bar.style.width = (v * 100) + '%';
        }

        function start() {
            if (running || !ensureBar()) return;
            running = true;
            track.classList.add('is-active');
            // Reset tanpa animasi lalu mulai naik.
            bar.style.transition = 'none';
            setValue(0);
            void bar.offsetWidth; // paksa reflow agar reset kebaca
            bar.style.transition = '';
            setValue(0.08);
            trickleTimer = setInterval(function () {
                var remaining = 0.9 - value;
                if (remaining > 0.001) setValue(value + remaining * 0.06);
            }, 380);
            // Jaring pengaman: bila navigasi batal (mis. dibatalkan), bar direset.
            clearTimeout(safetyTimer);
            safetyTimer = setTimeout(reset, 12000);
        }

        function reset() {
            clearInterval(trickleTimer);
            clearTimeout(safetyTimer);
            running = false;
            if (track) track.classList.remove('is-active');
            setValue(0);
        }

        function markNavigating() {
            try { sessionStorage.setItem('kssNav', '1'); } catch (e) {}
        }

        function beginNavIndicator() {
            markNavigating();
            start();
        }

        function isModifiedClick(e) {
            return e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey;
        }

        document.addEventListener('click', function (e) {
            if (isModifiedClick(e)) return;
            var a = e.target && e.target.closest ? e.target.closest('a') : null;
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#') return;
            if (href.toLowerCase().indexOf('javascript:') === 0) return;
            if (a.hasAttribute('download')) return;
            if (a.target && a.target !== '_self') return;
            var url;
            try { url = new URL(a.href, location.href); } catch (err) { return; }
            if (url.origin !== location.origin) return;
            if (url.href.split('#')[0] === location.href.split('#')[0]) return; // halaman sama
            // Tunda cek defaultPrevented: handler lain (tab, modal) bisa membatalkan
            // navigasi setelah listener ini. Bila akhirnya batal, jangan tampilkan bar.
            setTimeout(function () {
                if (e.defaultPrevented) return;
                beginNavIndicator();
            }, 0);
        }, false);

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.hasAttribute('data-no-progress')) return;
            if (form.target && form.target !== '_self') return;
            setTimeout(function () {
                if (e.defaultPrevented) return; // form AJAX (preventDefault) diabaikan
                beginNavIndicator();
            }, 0);
        }, false);

        // Kembali via tombol back (bfcache) atau navigasi yang batal: pastikan bersih.
        window.addEventListener('pageshow', reset);
    })();
</script>
