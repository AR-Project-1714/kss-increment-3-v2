{{--
    Perilaku "bottom sheet" untuk seluruh modal/pop-up di sistem (kelas
    `.modal-overlay` + `.pop-up.signed` / `.modal-box`). Di layar mobile
    (≤768px) modal menempel & meluncur dari bawah dengan handle geser untuk
    menutup; di desktop tetap di tengah seperti semula (tidak diubah).

    Cakupan selector bersifat generik agar otomatis berlaku untuk ketiga
    varian modal yang ada di sistem (report-ops/report-safety/pemeliharaan
    yang memakai `.pop-up.signed`, serta admin/manajer yang memakai
    `.modal-box`) tanpa perlu menyalin CSS/JS ini ke tiap layout.
--}}
@once
<style>
    .kss-sheet-handle {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 30px;
        align-items: center;
        justify-content: center;
        touch-action: none;
        cursor: grab;
        z-index: 2;
    }

    .kss-sheet-handle::after {
        content: '';
        width: 42px;
        height: 4px;
        border-radius: 999px;
        background: var(--divider, #cbd5e1);
    }

    @media (max-width: 768px) {
        .modal-overlay {
            align-items: flex-end !important;
            padding: 0 !important;
        }

        .modal-overlay .pop-up.signed,
        .modal-overlay .modal-box {
            position: relative !important;
            width: 100% !important;
            max-width: 100% !important;
            max-height: min(86vh, 680px) !important;
            margin: 0 !important;
            border-radius: 20px 20px 0 0 !important;
            padding-top: 30px !important;
            padding-bottom: max(18px, env(safe-area-inset-bottom, 0px)) !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            /* transform/transition sengaja TANPA !important agar bisa
               ditimpa langsung oleh inline style saat drag (lihat JS). */
            transform: translateY(100%);
            transition: transform 0.38s cubic-bezier(0.32, 0.72, 0, 1);
        }

        .modal-overlay.show .pop-up.signed,
        .modal-overlay.show .modal-box {
            transform: translateY(0);
        }

        .kss-sheet-handle {
            display: flex !important;
        }

        /* Tombol aksi modal ditumpuk penuh (full-width), aksi utama di
           atas — urutan DOM di semua modal sudah: batal dulu, aksi utama
           terakhir, sehingga column-reverse otomatis menaruhnya di atas. */
        .pop-up.footer,
        .kss-modal__footer,
        .modal-box__footer {
            flex-direction: column-reverse !important;
            flex-wrap: nowrap !important;
            align-items: stretch !important;
            justify-content: flex-start !important;
            gap: 10px !important;
        }

        .pop-up.footer > *,
        .kss-modal__footer > *,
        .modal-box__footer > * {
            width: 100% !important;
            margin: 0 !important;
        }

        .pop-up.footer .btn,
        .kss-modal__button,
        .btn-modal {
            width: 100% !important;
            justify-content: center !important;
        }
    }
</style>

<script>
    (function () {
        if (window.KssMobileSheet) return;

        const MOBILE_QUERY = '(max-width: 768px)';
        const CLOSE_THRESHOLD_PX = 110;
        const CLOSE_VELOCITY = 0.55; // px/ms

        function isMobile() {
            return window.matchMedia(MOBILE_QUERY).matches;
        }

        function closeOverlay(overlay) {
            overlay.classList.remove('show');
        }

        function bindHandle(handle, panel) {
            let startY = 0;
            let startTime = 0;
            let dragging = false;

            handle.addEventListener('touchstart', event => {
                if (!isMobile() || !event.touches.length) return;
                startY = event.touches[0].clientY;
                startTime = Date.now();
                dragging = true;
                panel.style.transition = 'none';
            }, { passive: true });

            handle.addEventListener('touchmove', event => {
                if (!dragging || !event.touches.length) return;
                const deltaY = Math.max(0, event.touches[0].clientY - startY);
                panel.style.transform = `translateY(${deltaY}px)`;
                event.preventDefault();
            }, { passive: false });

            function endDrag(event) {
                if (!dragging) return;
                dragging = false;
                panel.style.transition = '';

                const touch = event.changedTouches[0];
                const deltaY = touch ? Math.max(0, touch.clientY - startY) : 0;
                const elapsed = Math.max(1, Date.now() - startTime);
                const velocity = deltaY / elapsed;

                panel.style.transform = '';

                const overlay = panel.closest('.modal-overlay');
                if (overlay && (deltaY > CLOSE_THRESHOLD_PX || velocity > CLOSE_VELOCITY)) {
                    closeOverlay(overlay);
                }
            }

            handle.addEventListener('touchend', endDrag);
            handle.addEventListener('touchcancel', endDrag);
        }

        function ensureHandle(panel) {
            if (!panel || panel.querySelector(':scope > .kss-sheet-handle')) return;

            const handle = document.createElement('div');
            handle.className = 'kss-sheet-handle';
            handle.setAttribute('aria-hidden', 'true');
            panel.prepend(handle);
            bindHandle(handle, panel);
        }

        function initAll(root = document) {
            root.querySelectorAll('.modal-overlay .pop-up.signed, .modal-overlay .modal-box').forEach(ensureHandle);
        }

        document.addEventListener('DOMContentLoaded', () => {
            initAll(document);

            const observer = new MutationObserver(records => {
                records.forEach(record => {
                    record.addedNodes.forEach(node => {
                        if (node.nodeType !== Node.ELEMENT_NODE) return;
                        if (node.matches?.('.pop-up.signed, .modal-box')) ensureHandle(node);
                        initAll(node);
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        });

        window.KssMobileSheet = { init: initAll };
    })();
</script>
@endonce
