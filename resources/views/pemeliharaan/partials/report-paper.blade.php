{{--
    Format laporan harian pemeliharaan (sesuai template fisik).
    Dipakai bersama oleh: pdf.blade.php (dompdf) & viewpdf.blade.php (tampilan HTML "Lihat").
    Variabel: $report, $isPdf (true untuk render PDF, false untuk HTML).
--}}
@php
    use App\Enums\MaintenanceStatus;

    $isPdf = $isPdf ?? false;

    try { $year = ($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y'); } catch (\Throwable) { $year = now()->format('Y'); }
    $docId = '#MNT-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '';
    $fmtTime = fn ($t) => $t ? substr($t, 0, 5) : '';

    $mainItems = $report->workItems->where('work_type', 'utama')->sortBy('sort_order')->values();
    $priorityItems = $report->workItems->where('work_type', 'prioritas')->sortBy('sort_order')->values();

    $groups = ['I', 'II', 'III', 'IV'];
    $mainByGroup = [];
    foreach ($groups as $idx => $g) {
        $mainByGroup[$g] = $mainItems->first(fn ($it) => $it->work_group === $g) ?? $mainItems->get($idx);
    }

    $unitNama = function ($item) {
        if (! $item) return '';
        if ($item->unit) return trim($item->unit->unit_code.' '.($item->unit->brand ?? ''));
        return $item->unit_label ?: '';
    };
    $unitNomor = fn ($item) => $item && $item->unit ? $item->unit->unit_number : '';
    $check = fn ($cond) => $cond ? '&#10003;' : '';

    $byCat = fn ($cat) => $report->unitConditions->filter(fn ($c) => optional($c->unit)->macro_category === $cat);
    $conditionUnitLabel = function ($condition) {
        if ($condition->unit) {
            return trim(implode(' ', array_filter([
                $condition->unit->unit_code,
                $condition->unit->unit_number,
            ])));
        }

        return trim((string) preg_replace('/\b(?:UD|YALE|HINO|TOYOTA)\b\s*/i', '', (string) $condition->unit_label));
    };
    $labels = fn ($coll) => $coll->map($conditionUnitLabel)->filter()->values();
    $truck = $byCat('truck'); $heavy = $byCat('heavy');
    $truckReady = $labels($truck->where('condition', 'ready')); $truckRusak = $labels($truck->where('condition', 'rusak'));
    $heavyReady = $labels($heavy->where('condition', 'ready')); $heavyRusak = $labels($heavy->where('condition', 'rusak'));

    $personil = $report->attendances->values();

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
    // PDF memakai logo versi ringan (di-embed base64); HTML tetap logo penuh.
    $logo = $imgSrc($isPdf ? 'assets/KSS-pdf.png' : 'assets/KSS-full.png');
    $isDraft = $report->status === MaintenanceStatus::Draft;
    $creatorSig = $isDraft ? null : $imgSrc($report->creator?->signature_path);
    $approverSig = $report->approver ? $imgSrc($report->approver?->signature_path) : null;
@endphp

<style>
    .report-paper { color: #000; font-size: 8px; font-family: Arial, Helvetica, sans-serif; }
    .report-paper * { font-family: Arial, Helvetica, sans-serif; }
    .report-paper .head-wrap { width: 100%; margin-bottom: 6px; border-collapse: collapse; }
    .report-paper .head-wrap td { vertical-align: middle; }
    .report-paper .logo { height: 36px; }
    .report-paper .title { text-align: center; }
    .report-paper .title .l1 { font-size: 13px; font-weight: bold; letter-spacing: .5px; }
    .report-paper .title .l2 { font-size: 11px; font-weight: bold; letter-spacing: .3px; }
    .report-paper .addr { width: 100%; margin-bottom: 6px; border-collapse: collapse; }
    .report-paper .addr td { vertical-align: top; font-size: 8px; }
    .report-paper .addr .lab { font-weight: bold; }
    .report-paper .addr .meta td { padding: 1px 0; }
    .report-paper .addr .meta .ml { width: 50px; font-weight: bold; }
    .report-paper .addr .meta .line { border-bottom: 1px solid #000; }
    .report-paper table.grid { width: 100%; border-collapse: collapse; }
    .report-paper table.grid th, .report-paper table.grid td { border: 1px solid #000; padding: 2px 3px; }
    .report-paper table.grid th { font-weight: bold; text-align: center; font-size: 7.5px; }
    .report-paper .c { text-align: center; }
    .report-paper .utama-row td { height: 34px; vertical-align: top; }
    .report-paper .grp { font-weight: bold; text-align: center; vertical-align: middle; }
    .report-paper .sec { background: #fff; font-weight: bold; text-align: center; font-size: 8px; padding: 3px; border: 1px solid #000; border-bottom: none; }
    .report-paper .layout { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .report-paper .layout > tbody > tr > td { vertical-align: top; padding: 0; }
    .report-paper .layout .gap { width: 8px; border: none; }
    .report-paper .unitcell { font-size: 7px; line-height: 1.4; text-align: center; vertical-align: top; height: 150px; }
    .report-paper .unitcell.ready { color: #14532d; }
    .report-paper .unitcell.rusak { color: #7f1d1d; }
    .report-paper .totrow td { font-weight: bold; text-align: center; background: #f2f2f2; font-size: 7.5px; }
    .report-paper .sign { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .report-paper .sign td { width: 50%; text-align: center; vertical-align: top; font-size: 8.5px; padding: 2px 20px; }
    .report-paper .sign .sigwrap { height: 52px; }
    .report-paper .sign .sigwrap img { max-height: 52px; max-width: 150px; }
    .report-paper .sign .nm { font-weight: bold; text-decoration: underline; }
    .report-paper .sign .ttl { font-style: italic; font-size: 8px; }
    .report-paper .company { text-align: center; font-weight: bold; font-size: 9px; padding: 8px 0 2px; }
</style>

<div class="report-paper">
    {{-- HEADER --}}
    <table class="head-wrap">
        <tr>
            <td style="width:120px">@if ($logo)<img class="logo" src="{{ $logo }}" alt="KSS">@else<b style="font-size:16px;color:#1f5fd1">KSS</b>@endif</td>
            <td class="title">
                <div class="l1">LAPORAN HARIAN</div>
                <div class="l2">UNIT KERJA PEMELIHARAAN DAN PERALATAN</div>
            </td>
            <td style="width:120px"></td>
        </tr>
    </table>

    {{-- Kepada Yth + Hari/Tanggal --}}
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
                    <tr><td class="ml">HARI</td><td>: <span class="line">&nbsp;{{ $report->day_name }}&nbsp;</span></td></tr>
                    <tr><td class="ml">TANGGAL</td><td>: <span class="line">&nbsp;{{ $fmtDate($report->report_date) }}&nbsp;</span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- PEKERJAAN UTAMA --}}
    <table class="grid">
        <thead>
            <tr>
                <th rowspan="2" style="width:4%">NO</th>
                <th colspan="2">JENIS UNIT</th>
                <th rowspan="2" style="width:30%">PEKERJAAN UTAMA</th>
                <th rowspan="2" style="width:15%">PETUGAS</th>
                <th colspan="2">STATUS</th>
                <th rowspan="2" style="width:13%">KETERANGAN</th>
            </tr>
            <tr>
                <th style="width:11%">NAMA</th>
                <th style="width:9%">NOMOR</th>
                <th style="width:8%">SELESAI</th>
                <th style="width:10%">TDK SELESAI</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $idx => $g)
                @php($item = $mainByGroup[$g])
                <tr class="utama-row">
                    <td class="c grp">{{ $idx + 1 }}</td>
                    <td>{{ $unitNama($item) }}</td>
                    <td class="c">{{ $unitNomor($item) }}</td>
                    <td>{{ $item->description ?? '' }}</td>
                    <td>{{ $item->assignee ?? '' }}</td>
                    <td class="c" style="vertical-align:middle">{!! $item ? $check($item->is_completed) : '' !!}</td>
                    <td class="c" style="vertical-align:middle">{!! $item ? $check(! $item->is_completed && ($item->description || $item->assignee)) : '' !!}</td>
                    <td class="grp">Group. {{ $g }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- PEKERJAAN PRIORITAS --}}
    <table class="grid" style="margin-top:8px">
        <thead>
            <tr>
                <th rowspan="2" style="width:4%">NO</th>
                <th colspan="2">JENIS UNIT</th>
                <th rowspan="2" style="width:30%">PEKERJAAN PRIORITAS</th>
                <th rowspan="2" style="width:15%">PETUGAS</th>
                <th colspan="2">STATUS</th>
                <th rowspan="2" style="width:13%">KETERANGAN</th>
            </tr>
            <tr>
                <th style="width:11%">NAMA</th>
                <th style="width:9%">NOMOR</th>
                <th style="width:8%">SELESAI</th>
                <th style="width:10%">TDK SELESAI</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($priorityItems as $i => $item)
                <tr style="height:18px">
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $unitNama($item) }}</td>
                    <td class="c">{{ $unitNomor($item) }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->assignee }}</td>
                    <td class="c">{!! $check($item->is_completed) !!}</td>
                    <td class="c">{!! $check(! $item->is_completed && ($item->description || $item->assignee)) !!}</td>
                    <td>{{ $item->notes }}</td>
                </tr>
            @empty
                @for ($k = 0; $k < 3; $k++)
                    <tr style="height:18px"><td class="c">{{ $k + 1 }}</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                @endfor
            @endforelse
        </tbody>
    </table>

    {{-- KONDISI UNIT + PERSONIL --}}
    <table class="layout">
        <tr>
            <td style="width:50%">
                <div class="sec">KONDISI UNIT SAAT INI</div>
                <table class="grid">
                    <tr>
                        <th colspan="2">TRAILER / TRONTON / DUMP TRUCK</th>
                        <th colspan="2">FORKLIFT / EXCAVATOR / WHEEL LOADER</th>
                    </tr>
                    <tr>
                        <th style="width:25%">READY / OPERASI</th>
                        <th style="width:25%">RUSAK / TDK OPERASI</th>
                        <th style="width:25%">READY / OPERASI</th>
                        <th style="width:25%">RUSAK / TDK OPERASI</th>
                    </tr>
                    <tr>
                        <td class="unitcell ready">{!! $truckReady->implode('<br>') !!}</td>
                        <td class="unitcell rusak">{!! $truckRusak->implode('<br>') !!}</td>
                        <td class="unitcell ready">{!! $heavyReady->implode('<br>') !!}</td>
                        <td class="unitcell rusak">{!! $heavyRusak->implode('<br>') !!}</td>
                    </tr>
                    <tr class="totrow">
                        <td>{{ $truckReady->count() }}</td>
                        <td>{{ $truckRusak->count() }}</td>
                        <td>{{ $heavyReady->count() }}</td>
                        <td>{{ $heavyRusak->count() }}</td>
                    </tr>
                </table>
            </td>
            <td class="gap"></td>
            <td style="width:49%">
                <div class="sec">PERSONIL</div>
                <table class="grid">
                    <tr>
                        <th style="width:8%">NO</th>
                        <th>NAMA KARYAWAN</th>
                        <th style="width:24%">JABATAN</th>
                        <th style="width:14%">MASUK</th>
                        <th style="width:14%">PULANG</th>
                    </tr>
                    @forelse ($personil as $i => $p)
                        <tr>
                            <td class="c">{{ $i + 1 }}</td>
                            <td>{{ $p->employee_name }}</td>
                            <td>{{ $p->position }}</td>
                            <td class="c">{{ $fmtTime($p->time_in) }}</td>
                            <td class="c">{{ $fmtTime($p->time_out) }}</td>
                        </tr>
                    @empty
                        <tr><td class="c">1</td><td></td><td></td><td></td><td></td></tr>
                    @endforelse
                </table>
            </td>
        </tr>
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
                <div class="ttl">Kasi Pemeliharaan &amp; Peralatan</div>
            </td>
        </tr>
    </table>
</div>
