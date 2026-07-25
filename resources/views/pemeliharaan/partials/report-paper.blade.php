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

    // Pekerjaan Utama minimal empat baris (Group I-IV) namun mengikuti jumlah
    // group yang benar-benar diisi petugas bila lebih dari empat.
    $romanize = function (int $number): string {
        $map = ['L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $roman = '';
        foreach ($map as $symbol => $value) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return $roman !== '' ? $roman : 'I';
    };
    $romanOrder = [];
    for ($n = 1; $n <= 60; $n++) {
        $romanOrder[$romanize($n)] = $n;
    }

    $mainRows = [];
    foreach ($mainItems as $item) {
        $mainRows[] = ['group' => trim((string) $item->work_group), 'item' => $item];
    }
    $usedGroups = array_values(array_filter(array_column($mainRows, 'group')));
    foreach (['I', 'II', 'III', 'IV'] as $grp) {
        if (! in_array($grp, $usedGroups, true)) {
            $mainRows[] = ['group' => $grp, 'item' => null];
            $usedGroups[] = $grp;
        }
    }
    $nextGroup = 1;
    foreach ($mainRows as $idx => $row) {
        if ($row['group'] !== '') {
            continue;
        }

        while (in_array($romanize($nextGroup), $usedGroups, true)) {
            $nextGroup++;
        }

        $mainRows[$idx]['group'] = $romanize($nextGroup);
        $usedGroups[] = $romanize($nextGroup);
        $nextGroup++;
    }
    usort($mainRows, fn ($a, $b) => ($romanOrder[$a['group']] ?? PHP_INT_MAX) <=> ($romanOrder[$b['group']] ?? PHP_INT_MAX));

    $unitNama = function ($item) {
        if (! $item) return '';
        if ($item->unit) return $item->unit->maintenance_name;
        return $item->unit_label ?: '';
    };
    $unitNomor = fn ($item) => $item && $item->unit ? $item->unit->maintenance_code : '';
    $check = fn ($cond) => $cond ? '&#10003;' : '';

    $byCat = fn ($cat) => $report->unitConditions->filter(fn ($c) => optional($c->unit)->macro_category === $cat);
    // Hasil closure ini dirender lewat {!! implode('<br>') !!} di bawah, jadi
    // setiap cabang WAJIB meng-escape sendiri — unit_label adalah teks bebas
    // ketikan pengguna dan tampil juga di layar admin/manajer.
    $conditionUnitLabel = function ($condition) {
        if ($condition->unit) {
            return e($condition->unit->maintenance_code);
        }

        $label = trim((string) $condition->unit_label);
        if (preg_match('/\b[A-Z]{2,5}[-.\s]?\d+\b/i', $label, $matches)) {
            return strtoupper(str_replace(['.', ' '], '-', $matches[0]));
        }

        return e($label);
    };
    $labels = fn ($coll) => $coll->map($conditionUnitLabel)->filter()->values();
    // Kolom kondisi unit kini selebar setengah kertas (bukan seperempat seperti
    // saat masih bersebelahan dengan personil), jadi label disusun 3 per baris
    // memakai tabel dalam — pola yang aman untuk dompdf — agar tidak memanjang
    // ke bawah dan menyisakan ruang kosong.
    $unitGrid = function ($coll) {
        if ($coll->isEmpty()) {
            return '';
        }

        $html = '<table class="unitgrid">';
        foreach ($coll->chunk(3) as $chunk) {
            $cells = $chunk->values();
            $html .= '<tr>';
            for ($i = 0; $i < 3; $i++) {
                $html .= '<td>'.($cells[$i] ?? '').'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</table>';
    };
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
    /* Kolom kanan .addr (45%) sudah menyentuh tepi kertas, tapi .meta yang
       dulu width:100% membuat isinya rata kiri di dalam kolom itu. Dibiarkan
       shrink-to-fit lalu didorong sendiri ke ujung kanan lewat margin-left:auto. */
    .report-paper .addr .meta { margin-left: auto; margin-right: 0; }
    .report-paper .addr .meta td { padding: 1px 0; }
    .report-paper .addr .meta .ml { width: 50px; font-weight: bold; white-space: nowrap; }
    .report-paper .addr .meta .line { border-bottom: 1px solid #000; }
    .report-paper table.grid { width: 100%; border-collapse: collapse; }
    .report-paper table.grid th, .report-paper table.grid td { border: 1px solid #000; padding: 2px 3px; }
    .report-paper table.grid th { font-weight: bold; text-align: center; font-size: 7.5px; }
    .report-paper .c { text-align: center; }
    .report-paper .utama-row td { height: 34px; vertical-align: top; }
    .report-paper .grp { font-weight: bold; text-align: center; vertical-align: middle; }
    .report-paper .sec { background: #fff; font-weight: bold; text-align: center; font-size: 8px; padding: 3px; border: 1px solid #000; border-bottom: none; }
    .report-paper .unitcell { font-size: 7px; line-height: 1.4; text-align: center; vertical-align: top; height: 60px; }
    .report-paper table.grid .unitgrid { width: 100%; border-collapse: collapse; }
    .report-paper table.grid .unitgrid td { width: 33.33%; border: none; padding: 0 1px; text-align: center; }
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
                <table class="meta">
                    <tr><td class="ml">HARI</td><td>: <span class="line">&nbsp;{{ $report->day_name }}&nbsp;</span></td></tr>
                    <tr><td class="ml">TANGGAL</td><td>: <span class="line">&nbsp;{{ $fmtDate($report->report_date) }}&nbsp;</span></td></tr>
                    @if ($report->work_time_start || $report->work_time_end)
                        <tr><td class="ml">JAM KERJA</td><td>: <span class="line">&nbsp;{{ trim($fmtTime($report->work_time_start).($report->work_time_end ? ' - '.$fmtTime($report->work_time_end) : '')) }}&nbsp;</span></td></tr>
                    @endif
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
            @foreach ($mainRows as $idx => $row)
                @php($item = $row['item'])
                @php($g = $row['group'])
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

    {{-- PERSONIL (lebar penuh) --}}
    <div class="sec" style="margin-top:8px">PERSONIL</div>
    <table class="grid">
        <tr>
            <th style="width:5%">NO</th>
            <th style="width:32%">NAMA KARYAWAN</th>
            <th style="width:22%">JABATAN</th>
            <th style="width:10%">MASUK</th>
            <th style="width:10%">PULANG</th>
            <th style="width:21%">KETERANGAN</th>
        </tr>
        @forelse ($personil as $i => $p)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $p->employee_name }}</td>
                <td>{{ $p->position }}</td>
                <td class="c">{{ $fmtTime($p->time_in) }}</td>
                <td class="c">{{ $fmtTime($p->time_out) }}</td>
                <td class="c">{{ $p->notes }}</td>
            </tr>
        @empty
            <tr><td class="c">1</td><td></td><td></td><td></td><td></td><td></td></tr>
        @endforelse
    </table>

    {{-- KONDISI UNIT SAAT INI (lebar penuh, di bawah personil) --}}
    <div class="sec" style="margin-top:8px">KONDISI UNIT SAAT INI</div>
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
            <td class="unitcell ready">{!! $unitGrid($truckReady) !!}</td>
            <td class="unitcell rusak">{!! $unitGrid($truckRusak) !!}</td>
            <td class="unitcell ready">{!! $unitGrid($heavyReady) !!}</td>
            <td class="unitcell rusak">{!! $unitGrid($heavyRusak) !!}</td>
        </tr>
        <tr class="totrow">
            <td>{{ $truckReady->count() }}</td>
            <td>{{ $truckRusak->count() }}</td>
            <td>{{ $heavyReady->count() }}</td>
            <td>{{ $heavyRusak->count() }}</td>
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
