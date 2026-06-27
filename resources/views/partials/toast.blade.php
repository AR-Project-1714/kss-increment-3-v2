{{--
    Komponen toast notifikasi bersama (success / error).
    Dipakai oleh SEMUA layout lewat: @include('partials.toast')

    - Menampilkan pesan dari session('success'), session('error'), dan $errors.
    - Menyediakan helper global untuk toast dinamis dari JS:
        window.kssToast(type, title, message, duration)
      beserta alias kompatibel: window.showAdminToast / showManagerToast / showReportToast.
    - Mandiri: membawa CSS (glassmorphism) & JS sendiri agar tampil konsisten
      di mana pun disertakan.
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
    .toast-viewport {
        position: fixed;
        top: 18px;
        left: 50%;
        z-index: 10050;
        width: min(460px, calc(100vw - 32px));
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        transform: translateX(-50%);
        pointer-events: none;
    }

    .toast-message {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 24px;
        border-top: 1px solid rgba(255, 255, 255, 0.70);
        border-left: 1px solid rgba(255, 255, 255, 0.70);
        border-right: 1px solid rgba(255, 255, 255, 0.20);
        border-bottom: 1px solid rgba(255, 255, 255, 0.20);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.42) 0%, rgba(255, 255, 255, 0.14) 100%);
        color: var(--black, var(--dark-main, #0F172A));
        box-shadow:
            0 25px 45px rgba(15, 23, 42, 0.12),
            inset 0 0 0 1px rgba(255, 255, 255, 0.30),
            inset 0 2px 10px rgba(255, 255, 255, 0.36);
        backdrop-filter: blur(28px) saturate(150%);
        -webkit-backdrop-filter: blur(28px) saturate(150%);
        opacity: 0;
        transform: translateY(-140%) scale(0.98);
        transition: transform 0.48s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.28s ease-out;
        pointer-events: auto;
    }

    .toast-message::before {
        content: "";
        position: absolute;
        inset: 2px;
        border-radius: 22px;
        background: transparent;
        pointer-events: none;
        z-index: -1;
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
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.70),
            inset 0 -10px 20px rgba(255, 255, 255, 0.10),
            0 8px 18px rgba(15, 23, 42, 0.08);
    }

    .toast-icon i,
    .toast-close i { position: relative; top: 2px; }

    .toast-message.success .toast-icon {
        color: var(--success, #10B981);
        background:
            linear-gradient(145deg, rgba(255, 255, 255, 0.30), rgba(255, 255, 255, 0.08)),
            rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.34);
    }

    .toast-message.error .toast-icon {
        color: var(--red-main, #D20000);
        background:
            linear-gradient(145deg, rgba(255, 255, 255, 0.30), rgba(255, 255, 255, 0.08)),
            rgba(210, 0, 0, 0.12);
        border: 1px solid rgba(210, 0, 0, 0.34);
    }

    .toast-copy { min-width: 0; flex: 1 1 auto; }

    .toast-title {
        display: block;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.25;
        color: var(--black, var(--dark-main, #0F172A));
    }

    .toast-text {
        display: block;
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
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--muted, #94A3B8);
        background: rgba(255, 255, 255, 0.24);
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .toast-close:hover { color: var(--black); background-color: rgba(51, 65, 85, 0.10); }

    body.dark-mode .toast-message {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(30, 41, 59, 0.45);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.40);
    }

    @media (max-width: 480px) {
        .toast-viewport { top: 12px; width: calc(100vw - 24px); }
        .toast-message { padding: 10px 12px; gap: 9px; border-radius: 22px; }
        .toast-icon { width: 34px; height: 34px; border-radius: 12px; }
    }
</style>

<div class="toast-viewport" id="kssToastViewport" aria-live="polite" aria-atomic="true">
    @foreach ($toastMessages as $toast)
        <div class="toast-message {{ $toast['type'] }}" data-duration="4200" role="status">
            <div class="toast-icon"><i class="{{ $toast['icon'] }}"></i></div>
            <div class="toast-copy">
                <span class="toast-title">{{ $toast['title'] }}</span>
                <span class="toast-text">{{ $toast['message'] }}</span>
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
            el.querySelector('.toast-text').textContent = message || '';
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
