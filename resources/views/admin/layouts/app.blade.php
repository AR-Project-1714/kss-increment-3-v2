<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KSS Admin')</title>

    <!-- Google Fonts: Poppins -->
    <link href="{{ asset('vendor/poppins.css') }}" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet" crossorigin="anonymous">

    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">

    <!-- Flaticon UICONS -->
    <link rel="stylesheet" href="{{ asset('vendor/uicons/uicons-regular-rounded/css/uicons-regular-rounded.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/uicons/uicons-bold-rounded/css/uicons-bold-rounded.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/uicons/uicons-solid-rounded/css/uicons-solid-rounded.css') }}">

    {{-- =====================================================
         SHARED SHELL CSS (dipakai semua halaman admin)
         ===================================================== --}}
    @vite('resources/css/layouts/admin.css')

    @include('components.kss-datetime-picker')
    @include('components.kss-mobile-sheet')

    {{-- CSS khusus per halaman (di-push dari masing-masing view) --}}
    @stack('styles')
</head>

<body>
    {{-- Terapkan dark mode sebelum render agar tidak flicker --}}
    <script>if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');</script>

    <div class="sk-overlay" id="sk-overlay">
        <div class="sk-spinner"></div>
    </div>

    {{-- Toast notifikasi (komponen bersama) --}}
    @include('partials.toast')

    {{-- SIDEBAR (partial) --}}
    @include('admin.layouts.sidebar', ['active' => trim($__env->yieldContent('active'))])

    {{-- Backdrop untuk sidebar mobile (off-canvas) --}}
    <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Tutup sidebar"></button>

    <div class="main-wrapper">

        {{-- TOP NAVBAR (partial) --}}
        @include('admin.layouts.navbar')

        {{-- PAGE CONTENT --}}
        <main class="page-content">
            @yield('content')
        </main>

        {{-- FOOTER (partial) --}}
        @include('admin.layouts.footer')

    </div>

    <div class="modal-overlay" id="adminConfirmModal" aria-hidden="true">
        <div class="modal-box modal-box--sm" role="dialog" aria-modal="true" aria-labelledby="adminConfirmTitle">
            <div class="kss-modal__header">
                <div class="kss-modal__icon kss-modal__icon--warning" id="adminConfirmIconWrap">
                    <i class="fi fi-rr-triangle-warning" id="adminConfirmIcon"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="adminConfirmTitle">Konfirmasi tindakan</div>
                    <div class="kss-modal__subtitle" id="adminConfirmSubtitle">Pastikan data yang dipilih sudah benar.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="kss-modal__message" id="adminConfirmMessage">
                    Tindakan ini memerlukan konfirmasi sebelum dilanjutkan.
                </div>
                <div class="kss-modal__summary" id="adminConfirmSummary" hidden>
                    <i class="fi fi-rr-info"></i>
                    <span id="adminConfirmSummaryText"></span>
                </div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="button" class="kss-modal__button kss-modal__button--primary" id="adminConfirmAction">
                    Lanjutkan
                </button>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" crossorigin="anonymous"></script>

    {{-- =====================================================
         SHARED JS (sidebar toggle, submenu, dark mode)
         ===================================================== --}}
    <script src="{{ asset('js/layouts/admin.js') }}?v={{ @filemtime(public_path('js/layouts/admin.js')) }}"></script>

    {{-- JS khusus per halaman --}}
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
