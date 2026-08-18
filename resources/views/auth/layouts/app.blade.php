<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Laporan KSS</title>

    <!-- Google Font -->
    <link href="{{ asset('vendor/poppins.css') }}" rel="stylesheet">

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @include('partials.offline-support')
    @include('partials.page-loader')

    <!-- LINK BOOTSTRAP 5 CSS -->
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet" crossorigin="anonymous">

    <!-- LINK FLATICON UICONS -->
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-regular-rounded/css/uicons-regular-rounded.css') }}'>
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-bold-rounded/css/uicons-bold-rounded.css') }}'>
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-solid-rounded/css/uicons-solid-rounded.css') }}'>

    @vite('resources/css/layouts/auth.css')
</head>
<body>
    <!-- Dark mode init lebih awal agar overlay langsung pakai warna yang benar -->
    @include('partials.theme-init')

    @include('partials.first-load-loader')

    <!-- Animated Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    {{-- Toast notifikasi (komponen bersama) --}}
    @include('partials.toast')

    {{-- Seluruh elemen khas mobile di bawah ini disembunyikan secara default dan
         baru dimunculkan di media query mobile, sehingga tampilan desktop
         (brand mark kiri atas + kartu kaca di tengah + hak cipta) tidak berubah. --}}
    <div class="auth-shell">
        {{-- Hero. Desktop: hanya baris atas berisi logo KSS lengkap di kiri.
             Mobile: logo dan sapaan di bawahnya, menyatu dengan form dalam
             satu permukaan penuh layar (tanpa lembar/sheet terpisah). --}}
        <header class="auth-hero">
            <div class="auth-hero__topbar">
                {{-- Satu-satunya isi topbar: logo KSS lengkap, rata kiri di semua
                     lebar layar. Halaman login sengaja tidak punya kontrol tema —
                     temanya ditentukan window.kssTheme (partials/theme-init):
                     preferensi tersimpan bila pengguna pernah memilih, dan
                     mengikuti tema perangkat bila belum. --}}
                <img src="{{ asset('assets/KSS-full.png') }}" alt="Logo KSS"
                     class="auth-brand-full" width="324" height="118">
            </div>

            <div class="auth-hero__intro">
                <img src="{{ asset('assets/login-mobile-kss.webp') }}" alt=""
                     class="auth-hero__logo" width="60" height="60">
                <h1 class="auth-hero__title">Selamat Datang!</h1>
                <p class="auth-hero__subtitle">Sistem Manajemen Dokumen Operasional</p>
            </div>
        </header>

        <!-- Wrapper Content -->
        <div class="login-wrapper">
            @yield('content')
        </div>

        <!-- Hak Cipta -->
        <footer class="auth-footer">
            <span class="auth-footer__line">© {{ date('Y') }} Sistem Laporan KSS.</span>
            <span class="auth-footer__line">Dibuat oleh Muhammad Arobi.</span>
        </footer>
    </div>

    <script src="{{ asset('js/layouts/auth.js') }}?v={{ @filemtime(public_path('js/layouts/auth.js')) }}"></script>
</body>
</html>
