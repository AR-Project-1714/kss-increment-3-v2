{{--
    Panel "Intip Laporan Sebelumnya" — dipakai form ketiga modul lewat:
        @include('partials.report-peek')

    Menampilkan tombol melayang yang membuka drawer berisi laporan periode
    sebelumnya (halaman tinjau laporan) tanpa keluar dari form, supaya petugas
    bisa mencocokkan data lanjutan (kondisi unit, akumulasi, pekerjaan, dsb.).

    Controller mengirim $previousReportPeek = ['url' => ..., 'title' => ..., 'meta' => ...]
    atau null bila belum ada laporan sebelumnya (panel tidak dirender).
--}}
@if (! empty($previousReportPeek))
    <style>
        .report-peek-fab {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 9000;
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            width: 40px;
            height: 40px;
            padding: 0 11px;
            box-sizing: border-box;
            overflow: hidden;
            white-space: nowrap;
            border: 1px solid var(--blue-main-40, rgba(37, 99, 235, 0.4));
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            color: var(--blue-main, #2563EB);
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 22px rgba(37, 99, 235, 0.14);
            will-change: width, transform;
            transition:
                width 0.42s cubic-bezier(.22, 1, .36, 1),
                transform 0.24s ease,
                color 0.2s ease,
                border-color 0.2s ease,
                background-color 0.2s ease,
                box-shadow 0.24s ease;
        }

        .report-peek-fab:hover,
        .report-peek-fab:focus-visible {
            width: 208px;
            transform: translateY(-2px);
            border-color: var(--blue-main, #2563EB);
            background: rgba(255, 255, 255, 0.96);
            color: var(--blue-hover, #1D4ED8);
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.22);
        }

        body.dark-mode .report-peek-fab {
            border-color: rgba(147, 197, 253, 0.55);
            background: rgba(30, 41, 59, 0.88);
            color: #93C5FD;
            box-shadow: 0 8px 24px rgba(2, 6, 23, 0.3);
        }

        body.dark-mode .report-peek-fab:hover,
        body.dark-mode .report-peek-fab:focus-visible {
            border-color: #93C5FD;
            background: rgba(30, 41, 59, 0.96);
            color: #DBEAFE;
            box-shadow: 0 12px 30px rgba(2, 6, 23, 0.42), 0 0 18px rgba(59, 130, 246, 0.12);
        }

        .report-peek-fab:active { transform: translateY(-1px) scale(0.98); transition-duration: 0.12s; }

        .report-peek-fab:focus-visible { outline: 3px solid rgba(37, 99, 235, 0.28); outline-offset: 3px; }

        .report-peek-fab i {
            flex: 0 0 16px;
            display: block;
            width: 16px;
            font-size: 15px;
            line-height: 1;
        }

        .report-peek-fab .text {
            opacity: 0;
            transform: translateX(-6px);
            transition: opacity 0.2s ease 0.05s, transform 0.3s ease 0.05s;
        }

        .report-peek-fab:hover .text,
        .report-peek-fab:focus-visible .text {
            opacity: 1;
            transform: translateX(0);
        }

        .report-peek-overlay {
            position: fixed;
            inset: 0;
            z-index: 9500;
            display: flex;
            justify-content: flex-end;
            background: rgba(15, 23, 42, 0.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .report-peek-overlay.is-open { opacity: 1; pointer-events: auto; }

        .report-peek-panel {
            display: flex;
            flex-direction: column;
            width: min(720px, 100%);
            height: 100%;
            background: var(--white, #fff);
            box-shadow: -18px 0 40px rgba(15, 23, 42, 0.25);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .report-peek-overlay.is-open .report-peek-panel { transform: translateX(0); }

        .report-peek-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
            flex-wrap: wrap;
        }

        .report-peek-title { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .report-peek-title .title { font-size: 14px; font-weight: 700; color: var(--black, #0F172A); }
        .report-peek-title .meta { font-size: 11px; color: var(--black-secondary, #64748B); }

        .report-peek-actions { display: inline-flex; align-items: center; gap: 8px; }

        .report-peek-actions a,
        .report-peek-actions button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 18px;
            border: 1px solid rgba(37, 99, 235, 0.35);
            background: rgba(37, 99, 235, 0.08);
            color: #1D4ED8;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .report-peek-actions .report-peek-close {
            border-color: rgba(100, 116, 139, 0.35);
            background: rgba(100, 116, 139, 0.08);
            color: #475569;
        }

        .report-peek-body { flex: 1 1 auto; min-height: 0; }

        .report-peek-body iframe { display: block; width: 100%; height: 100%; border: none; background: #F1F5F9; }

        @media (max-width: 640px) {
            .report-peek-fab { right: 12px; bottom: 12px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .report-peek-fab,
            .report-peek-fab .text { transition: none; }
        }
    </style>

    <button type="button" class="report-peek-fab" id="reportPeekFab" title="Intip laporan sebelumnya" aria-label="Intip laporan sebelumnya" aria-haspopup="dialog" aria-controls="reportPeekOverlay" aria-expanded="false">
        <i class="fi fi-rr-eye"></i>
        <span class="text">Intip Laporan Sebelumnya</span>
    </button>

    <div class="report-peek-overlay" id="reportPeekOverlay" aria-hidden="true">
        <div class="report-peek-panel" role="dialog" aria-modal="true" aria-label="Laporan sebelumnya">
            <div class="report-peek-head">
                <div class="report-peek-title">
                    <span class="title">{{ $previousReportPeek['title'] ?? 'Laporan Sebelumnya' }}</span>
                    @if (! empty($previousReportPeek['meta']))
                        <span class="meta">{{ $previousReportPeek['meta'] }}</span>
                    @endif
                </div>
                <div class="report-peek-actions">
                    <a href="{{ $previousReportPeek['url'] }}" target="_blank" rel="noopener">
                        <i class="fi fi-rr-arrow-up-right-from-square"></i> Buka Penuh
                    </a>
                    <button type="button" class="report-peek-close" id="reportPeekClose">
                        <i class="fi fi-rr-cross-small"></i> Tutup
                    </button>
                </div>
            </div>
            <div class="report-peek-body">
                {{-- src diisi saat pertama dibuka agar form tidak ikut memuat halaman laporan. --}}
                <iframe id="reportPeekFrame" data-src="{{ $previousReportPeek['url'] }}?peek=1" title="Pratinjau laporan sebelumnya" loading="lazy"></iframe>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var fab = document.getElementById('reportPeekFab');
            var overlay = document.getElementById('reportPeekOverlay');
            var frame = document.getElementById('reportPeekFrame');
            var closeBtn = document.getElementById('reportPeekClose');

            if (!fab || !overlay || !frame) return;

            function open() {
                if (!frame.getAttribute('src')) {
                    frame.setAttribute('src', frame.getAttribute('data-src'));
                }
                overlay.classList.add('is-open');
                overlay.setAttribute('aria-hidden', 'false');
                fab.setAttribute('aria-expanded', 'true');
            }

            function close() {
                overlay.classList.remove('is-open');
                overlay.setAttribute('aria-hidden', 'true');
                fab.setAttribute('aria-expanded', 'false');
                fab.focus();
            }

            fab.addEventListener('click', open);
            if (closeBtn) closeBtn.addEventListener('click', close);
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) close();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && overlay.classList.contains('is-open')) close();
            });
        })();
    </script>
@endif
