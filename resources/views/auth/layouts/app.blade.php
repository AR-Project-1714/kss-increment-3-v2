<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Laporan KSS</title>

    <!-- Google Font -->
    <link href="{{ asset('vendor/poppins.css') }}" rel="stylesheet">

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">
    @include('partials.offline-support')

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
    <script>if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark-mode');</script>

    <!-- LOADING SPINNER -->
    <div class="sk-overlay" id="sk-overlay">
        <div class="sk-spinner"></div>
    </div>

    <!-- Animated Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    @include('auth.layouts.theme')

    {{-- Toast notifikasi (komponen bersama) --}}
    @include('partials.toast')

    <!-- Wrapper Content -->
    <div class="login-wrapper">
        @yield('content')
    </div>

    <script src="{{ asset('js/layouts/auth.js') }}?v={{ @filemtime(public_path('js/layouts/auth.js')) }}"></script>
</body>
</html>
