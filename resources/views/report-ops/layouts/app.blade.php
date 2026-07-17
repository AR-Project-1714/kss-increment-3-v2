<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan KSS - Dashboard</title>

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">
    @include('partials.offline-support')

    <!-- Google Font -->
    <link href="{{ asset('vendor/poppins.css') }}" rel="stylesheet">

    <!-- LINK BOOTSTRAP 5 CSS (Terbaru) -->
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet" crossorigin="anonymous">

    <!-- LINK FLATICON UICONS (Versi 2.6.0) -->
    <!-- Regular Rounded (fi-rr-*) -->
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-regular-rounded/css/uicons-regular-rounded.css') }}'>

    <!-- Bold Rounded (fi-br-*) -->
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-bold-rounded/css/uicons-bold-rounded.css') }}'>

    <!-- Solid Rounded (fi-sr-*) -->
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-solid-rounded/css/uicons-solid-rounded.css') }}'>

    @stack('styles')
    <!-- Style Internal CSS -->
     @vite('resources/css/layouts/report-ops.css')

    @include('components.kss-datetime-picker')
    @include('components.kss-mobile-sheet')
    @include('partials.officer-icon-alignment')

</head>
<body class="officer-report-shell">
    <!-- Dark mode init lebih awal agar overlay langsung pakai warna yang benar -->
    <script>if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark-mode');</script>

    <div class="sk-overlay" id="sk-overlay">
        <div class="sk-spinner"></div>
    </div>

    {{-- Toast notifikasi (komponen bersama) --}}
    @include('partials.toast')

    @include('report-ops.layouts.header')

    @yield('content')

    @include('report-ops.layouts.footer')

    @stack('modals')

    <!-- LINK BOOTSTRAP 5 JS BUNDLE -->
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" crossorigin="anonymous"></script>

    {{-- Javascript Interaktif --}}
    <script src="{{ asset('js/layouts/report-ops.js') }}?v={{ @filemtime(public_path('js/layouts/report-ops.js')) }}"></script>

    @stack('scripts')

    {{-- Loading Spinner js --}}
    <script>
        window.addEventListener('load', function() {
            var sk = document.getElementById('sk-overlay');
            if (sk) {
                sk.classList.add('sk-done');
                setTimeout(function() { sk.remove(); }, 600);
            }
        });
    </script>
</body>
</html>
