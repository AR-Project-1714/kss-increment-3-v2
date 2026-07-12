@php
    $isPdf = false;
    $backUrl = $backUrl ?? route('report-ops.index');
    $pdfUrl = $pdfUrl ?? route('report-ops.pdf', $report);
    try { $year = ($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y'); } catch (\Throwable) { $year = now()->format('Y'); }
    $docId = '#OPS-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $docId }} - Laporan Operasi Harian</title>
    <link rel="icon" href="{{ asset('assets/Logo-compressed 1.png') }}">
    <link href="{{ asset('vendor/poppins.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/uicons/uicons-regular-rounded/css/uicons-regular-rounded.css') }}">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; font-family: 'Poppins', Arial, sans-serif; }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 12px 22px;
            flex-wrap: wrap;
            background: #fff;
            border-bottom: 1px solid #d1d5db;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }
        .toolbar .grp { display: flex; gap: 10px; }
        .toolbar .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: .2s;
            line-height: 1.2;
        }
        .toolbar .btn i { position: relative; top: 1px; }
        .btn.back { background: #fff; color: #0f172a; border: 1px solid #d1d5db; }
        .btn.back:hover { background: #f1f5f9; }
        .btn.pdf { background: #D20000; color: #fff; }
        .btn.pdf:hover { filter: brightness(.93); }
        .btn.print { background: #2563EB; color: #fff; }
        .btn.print:hover { filter: brightness(.95); }

        .sheet-frame { width: 100%; }
        .sheet {
            width: 760px;
            max-width: 100%;
            margin: 24px auto;
            padding: 30px 34px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(0,0,0,.14);
        }

        .sheet .report-paper { font-size: 10.5px; line-height: 1.28; }
        .sheet .report-paper .logo { height: 46px; }
        .sheet .report-paper .title .l1 { font-size: 18px; }
        .sheet .report-paper .title .l2 { font-size: 13px; }
        .sheet .report-paper .doc-id { font-size: 12px; }
        .sheet .report-paper .addr td { font-size: 11px; }
        .sheet .report-paper .meta .line { min-width: 142px; }
        .sheet .report-paper .sec { font-size: 11px; padding: 5px 7px; }
        .sheet .report-paper .subsec,
        .sheet .report-paper .panel-title { font-size: 10.5px; }
        .sheet .report-paper .grid th { font-size: 9px; }
        .sheet .report-paper .small { font-size: 9px; }
        .sheet .report-paper .sign td { font-size: 12px; }
        .sheet .report-paper .ttl { font-size: 11px; }
        .sheet .report-paper .company { font-size: 14px; }

        @media (max-width: 800px) {
            .toolbar { padding: 10px 12px; }
            .toolbar .grp { flex-wrap: wrap; justify-content: flex-end; }
            .btn.pdf .btn-text, .btn.print .btn-text { display: none; }
            .btn.pdf, .btn.print { padding: 9px 11px; }
            .sheet-frame { overflow: hidden; }
            .sheet {
                width: 760px;
                max-width: none;
                margin: 12px 0 0 0;
                transform-origin: top left;
            }
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ $backUrl }}" class="btn back" id="btnBack"><i class="fi fi-rr-arrow-small-left"></i> Kembali</a>
        <div class="grp">
            @if ($pdfUrl)
                <a href="{{ $pdfUrl }}" class="btn pdf" id="btnPdf" target="_blank" rel="noopener" aria-label="Unduh PDF"><i class="fi fi-rr-file-pdf"></i> <span class="btn-text">Unduh PDF</span></a>
            @endif
            <button type="button" class="btn print" onclick="window.print()" aria-label="Cetak"><i class="fi fi-rr-print"></i> <span class="btn-text">Cetak</span></button>
        </div>
    </div>

    <script>
        (function () {
            var backBtn = document.getElementById('btnBack');
            if (!backBtn) return;
            backBtn.addEventListener('click', function (event) {
                event.preventDefault();
                var fallbackUrl = backBtn.getAttribute('href');
                window.close();
                window.setTimeout(function () {
                    if (!window.closed) window.location.href = fallbackUrl;
                }, 150);
            });
        })();

        (function () {
            var pdfBtn = document.getElementById('btnPdf');
            if (!pdfBtn) return;
            var backBtn = document.getElementById('btnBack');
            var fallbackUrl = backBtn ? backBtn.getAttribute('href') : '/';
            pdfBtn.addEventListener('click', function () {
                window.setTimeout(function () {
                    window.close();
                    window.setTimeout(function () {
                        if (!window.closed) window.location.href = fallbackUrl;
                    }, 150);
                }, 500);
            });
        })();
    </script>

    <div class="sheet-frame">
        <div class="sheet">
            @include('report-ops.partials.report-paper', ['report' => $report, 'isPdf' => false])
        </div>
    </div>

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
