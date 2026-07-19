{{--
    partials/first-load-loader.blade.php

    Overlay first-load (#sk-overlay) — dipakai di baris pertama <body> tiap layout.
    Menggantikan spinner lingkaran polos dengan animasi "spinner -> logo KSS" yang
    berulang (siklus 2.4s), berdasarkan referensi desain kss-pure-loader.html.

    JS penghapusannya TETAP di masing-masing layout (window.addEventListener('load', ...)
    menambah class .sk-done lalu me-remove elemen ini) — markup & CSS di sini sengaja
    hanya menyediakan tampilan, bukan logikanya, supaya tiap layout tetap kontrol penuh
    kapan overlay dianggap "selesai".

    Overlay ini DITEKAN otomatis saat navigasi internal (lihat partials/page-loader.blade.php,
    kelas `html.kss-is-nav .sk-overlay`) sehingga hanya benar-benar tampil saat first load /
    hard refresh.
--}}
<style>
    .sk-overlay {
        position: fixed;
        inset: 0;
        z-index: 9998;
        background-color: var(--main-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }

    .sk-overlay.sk-done {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .kss-loader {
        --kss-loader-blue: #078fe5;
        --kss-loader-orange: #ff9d00;
        position: relative;
        width: 112px;
        height: 112px;
    }

    .kss-loader__spinner,
    .kss-loader__logo {
        position: absolute;
        inset: 0;
        margin: auto;
    }

    .kss-loader__spinner {
        width: 82px;
        height: 82px;
        border: 6px solid rgba(7, 143, 229, 0.14);
        border-top-color: var(--kss-loader-blue);
        border-right-color: var(--kss-loader-orange);
        border-radius: 50%;
        filter: drop-shadow(0 8px 14px rgba(0, 92, 184, 0.18));
        animation: kss-spinner-cycle 2.4s ease-in-out infinite;
    }

    .kss-loader__logo {
        width: 96px;
        height: 96px;
        object-fit: contain;
        opacity: 0;
        transform: scale(0.55) rotate(-8deg);
        filter: drop-shadow(0 10px 16px rgba(0, 92, 184, 0.18));
        animation: kss-logo-cycle 2.4s ease-in-out infinite;
    }

    @keyframes kss-spinner-cycle {
        0%   { opacity: 1; transform: scale(1) rotate(0deg); }
        28%  { opacity: 1; transform: scale(1) rotate(300deg); }
        43%  { opacity: 0; transform: scale(0.45) rotate(430deg); }
        73%  { opacity: 0; transform: scale(0.45) rotate(430deg); }
        88%  { opacity: 1; transform: scale(0.92) rotate(650deg); }
        100% { opacity: 1; transform: scale(1) rotate(720deg); }
    }

    @keyframes kss-logo-cycle {
        0%, 31%   { opacity: 0; transform: scale(0.55) rotate(-8deg); }
        47%       { opacity: 1; transform: scale(1.1) rotate(0deg); }
        58%, 70%  { opacity: 1; transform: scale(1) rotate(0deg); }
        86%, 100% { opacity: 0; transform: scale(0.55) rotate(8deg); }
    }

    @media (prefers-reduced-motion: reduce) {
        .kss-loader__spinner { animation: none; opacity: 0; }
        .kss-loader__logo { animation: none; opacity: 1; transform: scale(1) rotate(0deg); }
    }
</style>
<div class="sk-overlay" id="sk-overlay">
    <div class="kss-loader" role="status" aria-label="Memuat aplikasi">
        <span class="kss-loader__spinner" aria-hidden="true"></span>
        <img
            class="kss-loader__logo"
            src="{{ asset('assets/loading-state.webp') }}"
            alt=""
            width="96"
            height="96"
            fetchpriority="high"
        >
    </div>
</div>
