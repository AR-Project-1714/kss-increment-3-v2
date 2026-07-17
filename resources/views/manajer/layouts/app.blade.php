<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSS Admin — Dashboard</title>

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">
    @include('partials.pwa')

    <!-- Google Fonts: Poppins -->
    <link href="{{ asset('vendor/poppins.css') }}" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet" crossorigin="anonymous">

    <!-- Flaticon UICONS -->
    <link rel="stylesheet" href="{{ asset('vendor/uicons/uicons-regular-rounded/css/uicons-regular-rounded.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/uicons/uicons-bold-rounded/css/uicons-bold-rounded.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/uicons/uicons-solid-rounded/css/uicons-solid-rounded.css') }}">

    @vite('resources/css/layouts/manajer.css')
    @include('components.kss-datetime-picker')
    @include('components.kss-mobile-sheet')
    @stack('styles')
</head>

<body>

    <div class="sk-overlay" id="sk-overlay">
        <div class="sk-spinner"></div>
    </div>

    {{-- Toast notifikasi (komponen bersama) --}}
    @include('partials.toast')

    <!-- ==========================================
                    SIDEBAR
    ========================================== -->
    @include('manajer.layouts.sidebar')
    <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Tutup sidebar"></button>

    <!-- ==========================================
         MAIN WRAPPER
    ========================================== -->
    <div class="main-wrapper">

        <!-- TOP NAVBAR -->
        @include('manajer.layouts.navbar')

        <!-- PAGE CONTENT -->
        @yield('content')

        <!-- FOOTER -->
        @include('manajer.layouts.footer')

    </div>

    @stack('modals')

    <!-- Bootstrap JS -->
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" crossorigin="anonymous"></script>

    <script src="{{ asset('js/layouts/manajer.js') }}?v={{ @filemtime(public_path('js/layouts/manajer.js')) }}"></script>
    @stack('scripts')

    <script>
        window.addEventListener('load', function () {
            var sk = document.getElementById('sk-overlay');
            if (sk) {
                sk.classList.add('sk-done');
                setTimeout(function () { sk.remove(); }, 600);
            }
        });
    </script>

</body>
</html>
