<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan KSS - Pemeliharaan</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @include('partials.offline-support')
    <link href="{{ asset('vendor/poppins.css') }}" rel="stylesheet">

    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet" crossorigin="anonymous">
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-regular-rounded/css/uicons-regular-rounded.css') }}'>
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-bold-rounded/css/uicons-bold-rounded.css') }}'>
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-solid-rounded/css/uicons-solid-rounded.css') }}'>

    @vite('resources/css/layouts/pemeliharaan.css')
    @include('components.kss-mobile-sheet')
    @stack('styles')
    @include('partials.officer-icon-alignment')
</head>
<body class="officer-report-shell">
    <script>if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark-mode');</script>

    <div class="sk-overlay" id="sk-overlay"><div class="sk-spinner"></div></div>

    {{-- Toast notifikasi (komponen bersama) --}}
    @include('partials.toast')

    @include('pemeliharaan.layouts.header')

    @yield('content')

    @include('pemeliharaan.layouts.footer')

    @stack('modals')

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" crossorigin="anonymous"></script>

    <script src="{{ asset('js/layouts/pemeliharaan.js') }}?v={{ @filemtime(public_path('js/layouts/pemeliharaan.js')) }}"></script>

    @stack('scripts')

    <script>
        window.addEventListener('load', function () {
            var sk = document.getElementById('sk-overlay');
            if (sk) { sk.classList.add('sk-done'); setTimeout(() => sk.remove(), 600); }
        });
    </script>
</body>
</html>
