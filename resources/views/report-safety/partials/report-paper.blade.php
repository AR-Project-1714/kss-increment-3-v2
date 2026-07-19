{{--
    Format laporan harian K3 (mereplikasi template form fisik).
    Dipakai bersama oleh: pdf.blade.php (dompdf) & viewpdf.blade.php (tampilan HTML "Lihat").
    Variabel: $report, $isPdf (true untuk render PDF, false untuk HTML).
--}}
@php
    use App\Enums\SafetyStatus;

    $isPdf = $isPdf ?? false;

    try { $year = ($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y'); } catch (\Throwable) { $year = now()->format('Y'); }
    $docId = '#K3-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '';
    $dayName = $report->report_date ? \Carbon\Carbon::parse($report->report_date)->locale('id')->translatedFormat('l') : '';

    // Kelompokkan inspeksi per lokasi (urut sesuai sort_order).
    $byLocation = $report->inspections->groupBy('location_name_snapshot');

    $check = fn ($cond, $val) => $cond === $val ? '&#10003;' : '';
    $qtyText = fn ($qty) => $qty === null ? '&minus;' : $qty;
    // Warna latar sel kondisi yang terpilih: bagus=hijau, rusak=merah, normal=biru, tidak_normal=oranye.
    $condClass = fn ($cond, $val) => $cond === $val ? 'cond-'.str_replace('_', '', $val) : '';

    $operations = $report->operationLogs->values();
    $incidents = $report->incidentLogs->values();

    // Sumber gambar: PDF -> base64 (andal untuk dompdf); HTML -> URL asset.
    $imgSrc = function ($path) use ($isPdf) {
        $path = ltrim((string) $path, '/');
        if ($path === '') return null;
        $files = [public_path($path), public_path('storage/'.$path), storage_path('app/public/'.$path)];
        if ($isPdf) {
            foreach ($files as $f) {
                if (is_file($f)) {
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION)) ?: 'png';
                    return 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($f));
                }
            }
            return null;
        }
        if (is_file(public_path($path))) return asset($path);
        if (is_file(public_path('storage/'.$path))) return asset('storage/'.$path);
        if (is_file(storage_path('app/public/'.$path))) return asset('storage/'.$path);
        return null;
    };
    $logo = $imgSrc($isPdf ? 'assets/KSS-pdf.png' : 'assets/KSS-full.png');
    $k3Logo = $imgSrc('assets/k3-logo.png');
    $isDraft = $report->status === SafetyStatus::Draft;
    $creatorSig = $isDraft ? null : $imgSrc($report->creator?->signature_path);
    $approverSig = $report->approver ? $imgSrc($report->approver?->signature_path) : null;
@endphp

<style>
    .report-paper { color: #000; font-size: 8px; font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; }
    .report-paper * { font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; }
    .report-paper .head-wrap { width: 100%; margin-bottom: 6px; border-collapse: collapse; }
    .report-paper .head-wrap td { vertical-align: middle; }
    .report-paper .logo { height: 36px; }
    .report-paper .logo-k3 { height: 42px; }
    .report-paper .title { text-align: center; }
    .report-paper .title .l1 { font-size: 13px; font-weight: bold; letter-spacing: .5px; }
    .report-paper .title .l2 { font-size: 10px; font-weight: bold; letter-spacing: .3px; }
    .report-paper .addr { width: 100%; margin-bottom: 6px; border-collapse: collapse; }
    .report-paper .addr td { vertical-align: top; font-size: 8px; }
    .report-paper .addr .lab { font-weight: bold; }
    .report-paper .addr .meta td { padding: 1px 0; }
    .report-paper .addr .meta .ml { width: 60px; font-weight: bold; }
    .report-paper .addr .meta .line { border-bottom: 1px solid #000; }
    .report-paper table.grid { width: 100%; border-collapse: collapse; }
    .report-paper table.grid th, .report-paper table.grid td { border: 1px solid #000; padding: 2px 3px; }
    .report-paper table.grid th { font-weight: bold; text-align: center; font-size: 7px; background: #ffe600; }
    .report-paper .c { text-align: center; }
    /* Sel lokasi (merge vertikal antar item dalam satu lokasi) */
    .report-paper td.loc-no { font-weight: bold; vertical-align: middle; font-size: 9px; }
    .report-paper td.loc-name { font-weight: bold; vertical-align: middle; font-size: 9px; text-align: center; }
    /* Latar sel kondisi sesuai pilihan */
    .report-paper td.cond-bagus { background: #a5d6a7; }
    .report-paper td.cond-rusak { background: #ef9a9a; }
    .report-paper td.cond-normal { background: #90caf9; }
    .report-paper td.cond-tidaknormal { background: #ffcc80; }
    .report-paper .sec { background: #f2f2f2; font-weight: bold; text-align: left; font-size: 8.5px; padding: 4px 6px; border: 1px solid #000; border-bottom: none; margin-top: 8px; }
    .report-paper .sign { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .report-paper .sign td { width: 50%; text-align: center; vertical-align: top; font-size: 8.5px; padding: 2px 20px; }
    .report-paper .sign .sigwrap { height: 52px; }
    .report-paper .sign .sigwrap img { max-height: 52px; max-width: 150px; }
    .report-paper .sign .nm { font-weight: bold; text-decoration: underline; }
    .report-paper .sign .ttl { font-style: italic; font-size: 8px; }
    .report-paper .company { text-align: center; font-weight: bold; font-size: 9px; padding: 8px 0 2px; }
    .report-paper .empty-note { text-align: center; font-style: italic; color: #555; padding: 6px; }
</style>

<div class="report-paper">
    {{-- HEADER --}}
    <table class="head-wrap">
        <tr>
            <td style="width:120px">@if ($logo)<img class="logo" src="{{ $logo }}" alt="KSS">@else<b style="font-size:16px;color:#1f5fd1">KSS</b>@endif</td>
            <td class="title">
                <div class="l1">LAPORAN HARIAN</div>
                <div class="l2">KESELAMATAN DAN KESEHATAN KERJA (K3)</div>
            </td>
            <td style="width:120px; text-align:right">@if ($k3Logo)<img class="logo-k3" src="{{ $k3Logo }}" alt="K3">@endif</td>
        </tr>
    </table>

    {{-- Kepada Yth + Hari/Tanggal/Jam Kerja --}}
    <table class="addr">
        <tr>
            <td style="width:55%; line-height:1.5">
                KEPADA YTH,<br>
                <span class="lab">BAPAK DIREKTUR</span><br>
                <span class="lab">PT KALTIM SATRIA SAMUDERA</span><br>
                DI<br>
                &nbsp;&nbsp;&nbsp;&nbsp;<span class="lab">BONTANG</span>
            </td>
            <td style="width:45%">
                <table class="meta" style="width:100%">
                    <tr><td class="ml">HARI</td><td>: <span class="line">&nbsp;{{ $dayName }}&nbsp;</span></td></tr>
                    <tr><td class="ml">TANGGAL</td><td>: <span class="line">&nbsp;{{ $fmtDate($report->report_date) }}&nbsp;</span></td></tr>
                    <tr><td class="ml">JAM KERJA</td><td>: <span class="line">&nbsp;{{ $report->time_range }}&nbsp;</span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- INSPEKSI K3 --}}
    <div class="sec">INSPEKSI K3 / LINGKUNGAN KERJA</div>
    <table class="grid">
        <thead>
            <tr>
                <th rowspan="2" style="width:4%">NO.</th>
                <th rowspan="2" style="width:16%">LOKASI<br>TEMPAT KERJA</th>
                <th rowspan="2" style="width:18%">ITEM<br>YANG DILAPORKAN</th>
                <th rowspan="2" style="width:6%">QTY</th>
                <th colspan="4">KONDISI</th>
                <th rowspan="2" style="width:28%">REKOMENDASI</th>
            </tr>
            <tr>
                <th style="width:7%">BAGUS</th>
                <th style="width:7%">RUSAK</th>
                <th style="width:7%">NORMAL</th>
                <th style="width:7%">TIDAK<br>NORMAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byLocation as $locationName => $rows)
                @foreach ($rows as $insp)
                    <tr>
                        @if ($loop->first)
                            <td rowspan="{{ $rows->count() }}" class="c loc-no">{{ $loop->parent->iteration }}</td>
                            <td rowspan="{{ $rows->count() }}" class="loc-name">{{ $locationName }}</td>
                        @endif
                        <td>{{ $insp->item_name_snapshot }}</td>
                        <td class="c">{!! $qtyText($insp->qty) !!}</td>
                        <td class="c {{ $condClass($insp->condition, 'bagus') }}">{!! $check($insp->condition, 'bagus') !!}</td>
                        <td class="c {{ $condClass($insp->condition, 'rusak') }}">{!! $check($insp->condition, 'rusak') !!}</td>
                        <td class="c {{ $condClass($insp->condition, 'normal') }}">{!! $check($insp->condition, 'normal') !!}</td>
                        <td class="c {{ $condClass($insp->condition, 'tidak_normal') }}">{!! $check($insp->condition, 'tidak_normal') !!}</td>
                        <td>{{ $insp->recommendation }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="9" class="empty-note">Tidak ada data inspeksi.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- SECTION 8: KEGIATAN OPERASI & PEMELIHARAAN --}}
    <div class="sec">KEGIATAN OPERASI &amp; PEMELIHARAAN</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width:4%">NO</th>
                <th style="width:34%">KEGIATAN</th>
                <th style="width:14%">KONDISI</th>
                <th style="width:24%">TINDAKAN</th>
                <th style="width:24%">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($operations as $i => $op)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $op->activity_name }}</td>
                    <td class="c">{{ $op->condition }}</td>
                    <td>{{ $op->action }}</td>
                    <td>{{ $op->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-note">Tidak ada kegiatan tercatat.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- SECTION 9: LAPORAN KEJADIAN & LAIN-LAIN --}}
    <div class="sec">LAPORAN KEJADIAN &amp; LAIN-LAIN</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width:4%">NO</th>
                <th style="width:34%">URAIAN KEJADIAN</th>
                <th style="width:14%">KONDISI</th>
                <th style="width:24%">TINDAKAN</th>
                <th style="width:24%">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($incidents as $i => $inc)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $inc->description }}</td>
                    <td class="c">{{ $inc->condition }}</td>
                    <td>{{ $inc->action }}</td>
                    <td>{{ $inc->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-note">Nihil - tidak ada kejadian.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- PENGESAHAN --}}
    <div class="company">PT KALTIM SATRIA SAMUDERA</div>
    <table class="sign">
        <tr>
            <td>
                Mengetahui,
                <div class="sigwrap">@if ($approverSig)<img src="{{ $approverSig }}" alt="TTD">@endif</div>
                <div class="nm">{{ $report->approver?->name ?: '(.....................)' }}</div>
                <div class="ttl">Manager Operasi &amp; K3</div>
            </td>
            <td>
                Dilaporkan,
                <div class="sigwrap">@if ($creatorSig)<img src="{{ $creatorSig }}" alt="TTD">@endif</div>
                <div class="nm">{{ $report->creator?->name ?: '(.....................)' }}</div>
                <div class="ttl">Karu Safety / K3</div>
            </td>
        </tr>
    </table>
</div>
