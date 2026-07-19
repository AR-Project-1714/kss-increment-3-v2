@php
    $isPdf = false;
    $backUrl = $backUrl ?? route('pemeliharaan.index');
    $pdfUrl = $pdfUrl ?? null;
    try { $year = ($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y'); } catch (\Throwable) { $year = now()->format('Y'); }
    $docId = '#MNT-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $docId }} - Laporan Pemeliharaan</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="{{ asset('vendor/poppins.css') }}" rel="stylesheet">
    <link rel='stylesheet' href='{{ asset('vendor/uicons/uicons-regular-rounded/css/uicons-regular-rounded.css') }}'>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; font-family: 'Poppins', Arial, sans-serif; }
        .toolbar {
            position: sticky; top: 0; z-index: 20; background: #fff; border-bottom: 1px solid #d1d5db;
            display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 12px 22px; flex-wrap: wrap;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }
        .toolbar .grp { display: flex; gap: 10px; }
        .toolbar .btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: .2s; }
        .toolbar .btn i { position: relative; top: 1px; }
        .btn.back { background: #fff; color: #0f172a; border: 1px solid #d1d5db; }
        .btn.back:hover { background: #f1f5f9; }
        .btn.pdf { background: #D20000; color: #fff; }
        .btn.pdf:hover { filter: brightness(.93); }
        .btn.print { background: #2563EB; color: #fff; }
        .btn.print:hover { filter: brightness(.95); }

        /* Kertas F4 putih (preview tampilan PDF) */
        .sheet-frame { width: 100%; }
        .sheet { width: 760px; max-width: 100%; margin: 24px auto; background: #fff; padding: 30px 34px; box-shadow: 0 8px 28px rgba(0,0,0,.14); }

        /* Perbesar ukuran font untuk keterbacaan layar — format tetap identik dengan PDF */
        .sheet .report-paper { font-size: 11px; }
        .sheet .report-paper .title .l1 { font-size: 18px; }
        .sheet .report-paper .title .l2 { font-size: 15px; }
        .sheet .report-paper .addr td { font-size: 11px; }
        .sheet .report-paper table.grid th { font-size: 10px; }
        .sheet .report-paper .unitcell { font-size: 10px; }
        .sheet .report-paper .totrow td { font-size: 11px; }
        .sheet .report-paper .sign td { font-size: 12px; }
        .sheet .report-paper .sign .ttl { font-size: 11px; }
        .sheet .report-paper .company { font-size: 14px; }
        .sheet .report-paper .logo { height: 46px; }

        /* Di layar kecil dokumen tidak dipampatkan paksa (membuat tabel berantakan).
           Sheet tetap selebar dokumen asli (760px) lalu diperkecil utuh agar pas
           selebar layar — seperti pratinjau halaman PDF. Skala dihitung script di bawah. */
        @media (max-width: 800px) {
            .btn.pdf .btn-text, .btn.print .btn-text { display: none; }
            .btn.pdf, .btn.print { padding: 9px 11px; }
            .sheet-frame { overflow: hidden; }
            .sheet { width: 760px; max-width: none; margin: 12px 0 0 0; transform-origin: top left; }
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
    @include('partials.pdf-preview-floating-toolbar')
</head>
<body>
    <div class="toolbar pdf-preview-toolbar{{ request()->boolean('peek') ? ' is-peek' : '' }}">
        @unless (request()->boolean('peek'))
            <a href="{{ $backUrl }}" class="btn back"><i class="fi fi-rr-arrow-small-left"></i> Kembali</a>
        @endunless
        <div class="grp">
            @if ($pdfUrl)
                <a href="{{ $pdfUrl }}" class="btn pdf" id="btnPdf" target="_blank" rel="noopener" aria-label="Unduh PDF"><i class="fi fi-rr-file-pdf"></i> <span class="btn-text">Unduh PDF</span></a>
            @endif
            <button type="button" class="btn print" onclick="window.print()" aria-label="Cetak"><i class="fi fi-rr-print"></i> <span class="btn-text">Cetak</span></button>
        </div>
    </div>

    {{-- Tombol "Unduh PDF": PDF terbuka di tab baru, lalu tab pratinjau ini
         ditutup dan fokus kembali ke website laporan KSS. Jika browser memblokir
         penutupan tab, navigasi balik ke daftar laporan. --}}
    @unless (request()->boolean('peek'))
    <script>
        (function () {
            var pdfBtn = document.getElementById('btnPdf');
            if (!pdfBtn) return;
            var backBtn = document.querySelector('.toolbar .btn.back');
            var fallbackUrl = backBtn ? backBtn.getAttribute('href') : '/';

            pdfBtn.addEventListener('click', function () {
                window.setTimeout(function () {
                    window.close();
                    window.setTimeout(function () {
                        if (!window.closed) {
                            window.location.href = fallbackUrl;
                        }
                    }, 150);
                }, 500);
            });
        })();
    </script>
    @endunless

    <div class="sheet-frame">
        <div class="sheet">
            @include('pemeliharaan.partials.report-paper', ['report' => $report, 'isPdf' => false])
        </div>
    </div>

    {{-- Skala dokumen agar pas selebar layar di perangkat mobile (pratinjau PDF). --}}
    <script>
        (function () {
            var frame = document.querySelector('.sheet-frame');
            var sheet = frame ? frame.querySelector('.sheet') : null;
            function fitSheet() {
                if (!frame || !sheet) return;
                if (!window.matchMedia('(max-width: 800px)').matches) {
                    sheet.style.transform = '';
                    sheet.style.marginLeft = '';
                    frame.style.height = '';
                    return;
                }
                sheet.style.transform = 'none';
                sheet.style.marginLeft = '0';
                var docW = sheet.offsetWidth;
                var docH = sheet.offsetHeight;
                var gap = 16;
                var avail = frame.clientWidth - gap;
                var scale = Math.min(1, avail / docW);
                var left = Math.max(0, (frame.clientWidth - docW * scale) / 2);
                sheet.style.transform = 'scale(' + scale + ')';
                sheet.style.marginLeft = left + 'px';
                frame.style.height = (docH * scale + 12) + 'px';
            }
            window.addEventListener('load', fitSheet);
            window.addEventListener('resize', fitSheet);
            window.addEventListener('orientationchange', fitSheet);
            if (document.readyState === 'complete') fitSheet();
        })();
    </script>

    @if (request('print'))
        <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
    @endif

    @if ($signAction ?? null)
        @include('components.kss-sign-fab', ['signAction' => $signAction, 'signMessage' => $signMessage ?? null])
    @endif
</body>
</html>
