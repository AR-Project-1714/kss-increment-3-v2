{{--
    Komponen toast notifikasi bersama (success / error).
    Dipakai oleh SEMUA layout lewat: @include('partials.toast')

    - Menampilkan pesan dari session('success'), session('error'), dan $errors.
    - Menyediakan helper global untuk toast dinamis dari JS:
        window.kssToast(type, title, message, duration)
      beserta alias kompatibel: window.showAdminToast / showManagerToast / showReportToast.
    - Mandiri: membawa CSS & JS sendiri agar tampil konsisten di mana pun
      disertakan. Latarnya memakai token frosted bersama dari
      resources/css/components/frosted-surface.css.
--}}
@php
    $toastMessages = collect();

    if (session('success')) {
        $toastMessages->push([
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => session('success'),
            'icon' => 'fi fi-rr-check-circle',
        ]);
    }

    if (session('error')) {
        $toastMessages->push([
            'type' => 'error',
            'title' => 'Gagal',
            'message' => session('error'),
            'icon' => 'fi fi-rr-triangle-warning',
        ]);
    }

    if ($errors->any()) {
        $toastMessages->push([
            'type' => 'error',
            'title' => 'Periksa Form',
            'message' => $errors->first(),
            'icon' => 'fi fi-rr-info',
        ]);
    }
@endphp

<style>
    /* Viewport memegang batas lebar; toast-nya sendiri menyusut ke lebar teks
       lewat align-items: center, jadi 460px kini berlaku sebagai max-width. */
    .toast-viewport {
        position: fixed;
        top: 18px;
        left: 50%;
        z-index: 10050;
        width: min(460px, calc(100vw - 32px));
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        transform: translateX(-50%);
        pointer-events: none;
    }

    /* Frosted, satu resep dengan popover & dropdown lain
       (lihat resources/css/components/frosted-surface.css).
       Bukan liquid glass: tidak ada gradien diagonal, border terang/gelap
       asimetris, atau inner sheen — hanya satu veil rata + hairline atas. */
    .toast-message {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 100%;
        padding: 12px 14px;
        /* Satu langkah di atas surface 14px: toast kini selebar isinya, dan
           bentuk lozenge butuh sudut lebih lunak agar tidak terbaca kotak. */
        border-radius: 18px;
        border: 1px solid var(--kss-frost-border);
        background: var(--kss-frost-surface);
        color: var(--black, var(--dark-main, #0F172A));
        box-shadow:
            inset 0 1px 0 var(--kss-frost-edge),
            var(--kss-frost-shadow);
        -webkit-backdrop-filter: var(--kss-frost-filter);
        backdrop-filter: var(--kss-frost-filter);
        opacity: 0;
        transform: translateY(-140%) scale(0.98);
        /* Ease-out eksponensial, bukan overshoot pegas. Pantulan itu bagian dari
           karakter liquid glass yang mengilap; permukaan frosted yang matte
           turun dan berhenti, tidak melenting. */
        transition: transform 0.42s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.28s ease-out;
        pointer-events: auto;
    }

    .toast-message.show { opacity: 1; transform: translateY(0) scale(1); }

    .toast-message.is-hiding {
        opacity: 0;
        transform: translateY(-140%) scale(0.98);
        transition: transform 0.36s ease-in, opacity 0.28s ease-in;
    }

    .toast-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .toast-icon i,
    .toast-close i { position: relative; top: 2px; }

    .toast-message.success .toast-icon {
        color: var(--success, #10B981);
        background: rgba(16, 185, 129, 0.14);
        border: 1px solid rgba(16, 185, 129, 0.30);
    }

    .toast-message.error .toast-icon {
        color: var(--red-main, #D20000);
        background: rgba(210, 0, 0, 0.12);
        border: 1px solid rgba(210, 0, 0, 0.30);
    }

    body.dark-mode .toast-message.error .toast-icon {
        color: var(--red-main, #EF4444);
        background: rgba(239, 68, 68, 0.16);
        border-color: rgba(239, 68, 68, 0.34);
    }

    .toast-copy { min-width: 0; flex: 1 1 auto; }

    /* Teks panjang dipotong dengan elipsis, bukan membungkus, supaya toast tetap
       satu lozenge selebar isinya. Teks utuh tetap terbaca lewat atribut title. */
    .toast-title,
    .toast-text {
        display: block;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .toast-title {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.25;
        color: var(--black, var(--dark-main, #0F172A));
    }

    .toast-text {
        margin-top: 2px;
        font-size: 11px;
        font-weight: 400;
        line-height: 1.35;
        color: var(--black-secondary, var(--dark-secondary, #334155));
    }

    .toast-close {
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--muted, #94A3B8);
        background: var(--kss-frost-inset);
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .toast-close:hover { color: var(--black); background-color: rgba(51, 65, 85, 0.10); }

    body.dark-mode .toast-close:hover { background-color: rgba(226, 232, 240, 0.14); }

    .toast-close:focus-visible {
        outline: 2px solid var(--blue-main, #2563EB);
        outline-offset: 2px;
    }

    @media (max-width: 480px) {
        .toast-viewport { top: 12px; width: calc(100vw - 24px); }
        .toast-message { padding: 10px 12px; gap: 9px; }
        .toast-icon { width: 34px; height: 34px; }
    }
</style>

<div class="toast-viewport" id="kssToastViewport" aria-live="polite" aria-atomic="true">
    @foreach ($toastMessages as $toast)
        <div class="toast-message {{ $toast['type'] }}" data-duration="4200" role="status">
            <div class="toast-icon"><i class="{{ $toast['icon'] }}"></i></div>
            <div class="toast-copy">
                <span class="toast-title">{{ $toast['title'] }}</span>
                <span class="toast-text" title="{{ $toast['message'] }}">{{ $toast['message'] }}</span>
            </div>
            <button type="button" class="toast-close" aria-label="Tutup notifikasi">
                <i class="fi fi-rr-cross-small"></i>
            </button>
        </div>
    @endforeach
</div>

<script>
    (function () {
        var ICONS = { success: 'fi fi-rr-check-circle', error: 'fi fi-rr-triangle-warning' };

        function viewport() {
            var v = document.getElementById('kssToastViewport');
            if (!v) {
                v = document.createElement('div');
                v.className = 'toast-viewport';
                v.id = 'kssToastViewport';
                v.setAttribute('aria-live', 'polite');
                v.setAttribute('aria-atomic', 'true');
                document.body.appendChild(v);
            }
            return v;
        }

        function hide(toast) {
            toast.classList.add('is-hiding');
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 480);
        }

        function activate(toast) {
            if (toast.dataset.bound === 'true') return;
            toast.dataset.bound = 'true';

            var duration = parseInt(toast.getAttribute('data-duration'), 10) || 4200;
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { toast.classList.add('show'); });
            });

            var timer = setTimeout(function () { hide(toast); }, duration);
            var closeBtn = toast.querySelector('.toast-close');
            if (closeBtn) closeBtn.addEventListener('click', function () { clearTimeout(timer); hide(toast); });
            toast.addEventListener('mouseenter', function () { clearTimeout(timer); });
            toast.addEventListener('mouseleave', function () { timer = setTimeout(function () { hide(toast); }, 1800); });
        }

        // Helper global untuk toast dinamis dari JS.
        window.kssToast = function (type, title, message, duration) {
            var safe = type === 'success' ? 'success' : 'error';
            var el = document.createElement('div');
            el.className = 'toast-message ' + safe;
            el.setAttribute('data-duration', duration || 4200);
            el.setAttribute('role', 'status');
            el.innerHTML =
                '<div class="toast-icon"><i class="' + ICONS[safe] + '"></i></div>' +
                '<div class="toast-copy"><span class="toast-title"></span><span class="toast-text"></span></div>' +
                '<button type="button" class="toast-close" aria-label="Tutup notifikasi"><i class="fi fi-rr-cross-small"></i></button>';
            el.querySelector('.toast-title').textContent = title || (safe === 'success' ? 'Berhasil' : 'Gagal');
            var textEl = el.querySelector('.toast-text');
            textEl.textContent = message || '';
            // Teks dipotong elipsis kalau panjang; simpan versi utuh di title.
            textEl.title = message || '';
            viewport().appendChild(el);
            activate(el);
            return el;
        };

        // Alias kompatibel agar pemanggil lama tetap berfungsi tanpa diubah.
        window.showAdminToast = window.showManagerToast = window.showReportToast = window.kssToast;

        // Aktifkan toast yang dirender dari server.
        function initServerToasts() {
            var v = document.getElementById('kssToastViewport');
            if (v) v.querySelectorAll('.toast-message').forEach(activate);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initServerToasts);
        } else {
            initServerToasts();
        }
    })();
</script>
