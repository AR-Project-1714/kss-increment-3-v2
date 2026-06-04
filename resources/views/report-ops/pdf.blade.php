<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Operasi Harian</title>
    <link rel="icon" href="{{ public_path('assets/Logo-compressed 1.png') }}">
    <style>
        @page {
            /* F4: 21.59cm x 33.02cm */
            size: 21.59cm 33.02cm;
            margin: 0.5cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            /* UPDATED: Naik 1pt jadi 7pt */
            font-size: 7pt;
            line-height: 1.1;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 5px;
        }
        th, td {
            padding: 2px 3px;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-bold { font-weight: bold; }
        .w-100 { width: 100%; }
        .w-50 { width: 50%; }
        .w-33 { width: 33.33%; }

        /* Borders */
        .border-all { border: 0.5px solid black; }
        .table-bordered th, .table-bordered td { border: 0.5px solid black; }
        .border-top { border-top: 0.5px solid black; }
        .border-bottom { border-bottom: 0.5px solid black; }
        .border-left { border-left: 0.5px solid black; }
        .border-right { border-right: 0.5px solid black; }
        .no-border { border: none !important; }

        /* Styles */
        .header-title {
            /* UPDATED: Naik jadi 11pt */
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 5px 0 8px 0;
            letter-spacing: 0.5px;
        }
        .section-header {
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 3px;
            /* UPDATED: Naik jadi 8pt */
            font-size: 8pt;
            text-transform: uppercase;
            background-color: #e0e0e0;
            padding: 2px 4px;
            border: 0.5px solid black;
        }
        .bg-gray { background-color: #f0f0f0; }

        /* Layout Helpers */
        .label-col { width: 70px; }
        .sep-col { width: 5px; text-align: center; }
        .val-col { border-bottom: 0.5px dotted #000; }
        .activity-box { border: 0.5px solid black; margin-bottom: 6px; page-break-inside: avoid; }
        .section-container { page-break-inside: avoid; }
        .page-break { page-break-after: always; }
        /* UPDATED: Naik jadi 6pt */
        .tiny-text { font-size: 6pt; }
        .row-empty { height: 10px; }
        .text-green { color: #008000; font-weight: bold; }
        .text-red { color: #FF0000; font-weight: bold; }

        /* Logo Style */
        .logo-img {
            height: 50px; /* Sesuaikan tinggi agar pas sejajar */
            width: auto;
            display: block;
            margin: 0 auto;
        }

        /* --- NEW: Signature Image Style --- */
        .signature-box {
            height: 70px; /* Tinggi tetap agar layout tidak bergeser */
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 5px 0;
        }
        .signature-img {
            height: 100%; /* Mengikuti tinggi signature-box */
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>
    {{-- HELPER FUNCTION FOR DYNAMIC DECIMAL --}}
    @php
        if (!function_exists('formatQty')) {
            function formatQty($val) {
                if ($val === null || $val === '') return '';
                $val = (float)$val;
                // Jika ada sisa pembagian 1 (berarti pecahan), tampilkan 2 desimal
                // Jika tidak (bulat), tampilkan 0 desimal
                // number_format($val, decimals, decimal_separator, thousands_separator)
                // Default: comma for thousands, dot for decimal
                return fmod($val, 1) !== 0.00 ? number_format($val, 2) : number_format($val, 0);
            }
        }
    @endphp

    <!-- HEADER INFO: 3 KOLOM SEJAJAR -->
    <table class="w-100 no-border" style="margin-bottom: 5px;">
        <tr>
            <!-- KIRI: KEPADA YTH (40%) -->
            <td style="width: 40%; vertical-align: top;">
                <div style="margin-bottom: 2px;">Kepada Yth,</div>
                <!-- UPDATED: Font size inline jadi 9pt -->
                <div class="text-bold" style="font-size: 9pt;">Bapak Direktur</div>
                <div class="text-bold" style="font-size: 9pt;">PT. Kaltim Satria Samudera</div>
                <div>di- Bontang</div>
            </td>

            <!-- TENGAH: LOGO (20%) -->
            <td style="width: 20%; vertical-align: top; text-align: center;">
                <img src="{{ public_path('assets/KSS-pdf.png') }}" alt="Logo KSS" class="logo-img">
            </td>

            <!-- KANAN: INFO UMUM (40%) -->
            <td style="width: 40%; vertical-align: top;">
                <table class="no-border" align="right">
                    <tr><td class="text-right" style="width: 60px;">Hari</td><td class="sep-col">:</td><td class="border-bottom" style="width: 90px;">{{ \Carbon\Carbon::parse($report->report_date)->locale('id')->translatedFormat('l') }}</td></tr>
                    <tr><td class="text-right">Tanggal</td><td class="sep-col">:</td><td class="border-bottom" style="width: 90px;">{{ \Carbon\Carbon::parse($report->report_date)->locale('id')->translatedFormat('d F Y') }}</td></tr>
                    <tr><td class="text-right">Jam Kerja</td><td class="sep-col">:</td><td class="border-bottom" style="width: 90px;">{{ $report->time_range }}</td></tr>
                    <tr><td class="text-right">Shift</td><td class="sep-col">:</td><td class="border-bottom" style="width: 90px;">{{ $report->shift }}</td></tr>
                    <tr><td class="text-right">Group</td><td class="sep-col">:</td><td class="border-bottom" style="width: 90px;">{{ $report->group_name }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- JUDUL LAPORAN -->
    <div class="header-title">LAPORAN SHIFT HARIAN</div>

    <!-- I. PEMUATAN PUPUK KANTONG -->
    <div class="section-header">I. PEMUATAN PUPUK KANTONG</div>

    @php
        $loadingActivities = $report->loadingActivities->sortBy('sequence');
        // Jika kosong, buat array dengan 1 elemen null agar loop berjalan sekali (untuk menampilkan form kosong)
        if ($loadingActivities->isEmpty()) {
            $loadingActivities = [null];
        }
    @endphp

    @foreach($loadingActivities as $index => $activity)
        <div class="activity-box">
            <!-- INFO ATAS -->
            <table class="w-100" style="margin-bottom: 0;">
                <tr>
                    <!-- KIRI -->
                    <td style="width: 34%; border-right: 0.5px solid black; padding: 2px; vertical-align: top;">
                        <table class="w-100 no-border">
                            {{-- Gunakan optional() agar tidak error jika $activity null --}}
                            <tr><td style="width: 15px;"><b>{{ optional($activity)->sequence ?? ($loop->iteration) }}.</b></td><td class="label-col">Nama Kapal</td><td class="sep-col">:</td><td class="border-bottom">{{ optional($activity)->ship_name ?? '' }}</td></tr>
                            <tr><td></td><td>Agent</td><td class="sep-col">:</td><td class="border-bottom">{{ optional($activity)->agent ?? '' }}</td></tr>
                            <tr><td></td><td>Dermaga</td><td class="sep-col">:</td><td class="border-bottom">{{ optional($activity)->jetty ?? '' }}</td></tr>
                            <tr><td></td><td>Tujuan</td><td class="sep-col">:</td><td class="border-bottom">{{ optional($activity)->destination ?? '' }}</td></tr>
                        </table>
                        <div style="font-weight: bold; margin-top: 2px; margin-left: 5px;">a. Pengiriman</div>
                        <table class="w-100 no-border" style="margin-left: 5px; width: 95%;">
                            <tr><td style="width: 50px;">- Sekarang</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_delivery_current) : '' }}</td><td style="width: 15px;">Ton</td></tr>
                            <tr><td>- Lalu</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_delivery_prev) : '' }}</td><td>Ton</td></tr>
                            <tr><td>- Akumulasi</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_delivery_current + $activity->qty_delivery_prev) : '' }}</td><td>Ton</td></tr>
                        </table>
                    </td>
                    <!-- TENGAH -->
                    <td style="width: 33%; border-right: 0.5px solid black; padding: 2px; vertical-align: top;">
                        <table class="w-100 no-border">
                            <tr><td class="label-col">Kapasitas</td><td class="sep-col">:</td><td class="border-bottom text-right">{{ optional($activity)->capacity ? formatQty($activity->capacity) : '' }}</td><td style="width: 15px;">Ton</td></tr>
                            <tr><td class="label-col">No. WO/SO</td><td class="sep-col">:</td><td class="border-bottom" colspan="2">{{ optional($activity)->wo_number ?? '' }}</td></tr>
                            <tr><td class="label-col">Jenis</td><td class="sep-col">:</td><td class="border-bottom" colspan="2">{{ optional($activity)->cargo_type ?? '' }}</td></tr>
                            <tr><td class="label-col">Marking</td><td class="sep-col">:</td><td class="border-bottom" colspan="2">{{ optional($activity)->marking ?? '' }}</td></tr>
                        </table>
                        <div style="font-weight: bold; margin-top: 2px; margin-left: 5px;">b. Pemuatan</div>
                        <table class="w-100 no-border" style="margin-left: 5px; width: 95%;">
                            <tr><td style="width: 50px;">- Sekarang</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_loading_current) : '' }}</td><td style="width: 15px;">Ton</td></tr>
                            <tr><td>- Lalu</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_loading_prev) : '' }}</td><td>Ton</td></tr>
                            <tr><td>- Akumulasi</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_loading_current + $activity->qty_loading_prev) : '' }}</td><td>Ton</td></tr>
                        </table>
                    </td>
                    <!-- KANAN -->
                    <td style="width: 33%; padding: 2px; vertical-align: top;">
                        <table class="w-100 no-border">
                            <tr><td class="label-col">Tiba/Sandar</td><td class="sep-col">:</td><td class="border-bottom">{{ isset($activity->arrival_time) ? \Carbon\Carbon::parse($activity->arrival_time)->format('d/m H:i') : '' }}</td></tr>
                            <tr><td class="label-col">Gang Ops</td><td class="sep-col">:</td><td class="border-bottom">{{ optional($activity)->operating_gang ?? '' }}</td></tr>
                            <tr><td class="label-col">Jml TKBM</td><td class="sep-col">:</td><td class="border-bottom">{{ optional($activity)->tkbm_count ? $activity->tkbm_count . ' Orang' : '' }}</td></tr>
                            <tr><td class="label-col">Mandor</td><td class="sep-col">:</td><td class="border-bottom">{{ optional($activity)->foreman ?? '' }}</td></tr>
                        </table>
                        <div style="font-weight: bold; margin-top: 2px; margin-left: 5px;">c. Kerusakan</div>
                        <table class="w-100 no-border" style="margin-left: 5px; width: 95%;">
                            <tr><td style="width: 50px;">- Sekarang</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_damage_current) : '' }}</td><td style="width: 15px;">Ton</td></tr>
                            <tr><td>- Lalu</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_damage_prev) : '' }}</td><td>Ton</td></tr>
                            <tr><td>- Akumulasi</td><td class="sep-col">:</td><td class="text-right border-bottom">{{ $activity ? formatQty($activity->qty_damage_current + $activity->qty_damage_prev) : '' }}</td><td>Ton</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!-- TIME SHEET & PETUGAS -->
            <!-- UPDATED: Font size jadi 7pt -->
            <div class="text-center border-top border-bottom bg-gray text-bold" style="padding: 1px; font-size: 7pt;">TIME SHEET</div>
            <table class="w-100" style="margin-bottom: 0;">
                <tr>
                    <!-- Timesheet Pengiriman -->
                    <td class="w-50 border-right" style="padding:0; vertical-align: top;">
                        <table class="w-100 table-bordered no-border">
                            <tr><th style="width: 15%;">JAM</th><th>PENGIRIMAN</th></tr>
                            @php
                                $dLogs = $activity ? $activity->timesheets->where('category', 'delivery')->values() : collect([]);
                                $lLogs = $activity ? $activity->timesheets->where('category', 'loading')->values() : collect([]);
                                $maxRows = max(3, $dLogs->count(), $lLogs->count());
                            @endphp
                            @for($r=0; $r < $maxRows; $r++)
                            <tr>
                                <td class="text-center" style="height: 10px;">{{ isset($dLogs[$r]) ? \Carbon\Carbon::parse($dLogs[$r]->time)->format('H:i') : '' }}</td>
                                <td>{{ isset($dLogs[$r]) ? $dLogs[$r]->activity : '' }}</td>
                            </tr>
                            @endfor
                        </table>
                        <!-- Petugas Kiri -->
                        <!-- UPDATED: Font size jadi 7pt -->
                        <table class="w-100 no-border" style="font-size: 7pt; border-top: 0.5px solid black;">
                            <tr><td style="width: 60px;">Tally Gudang</td><td class="sep-col">:</td><td>{{ optional($activity)->tally_warehouse ?? '' }}</td></tr>
                            <tr><td>Driver</td><td class="sep-col">:</td><td>{{ optional($activity)->driver_name ?? '' }}</td></tr>
                            <tr><td>Truck No.</td><td class="sep-col">:</td><td>{{ optional($activity)->truck_number ?? '' }}</td></tr>
                        </table>
                    </td>
                    <!-- Timesheet Pemuatan -->
                    <td class="w-50" style="padding:0; vertical-align: top;">
                        <table class="w-100 table-bordered no-border">
                            <tr><th style="width: 15%;">JAM</th><th>PEMUATAN</th></tr>
                            @for($r=0; $r < $maxRows; $r++)
                            <tr>
                                <td class="text-center" style="height: 10px;">{{ isset($lLogs[$r]) ? \Carbon\Carbon::parse($lLogs[$r]->time)->format('H:i') : '' }}</td>
                                <td>{{ isset($lLogs[$r]) ? $lLogs[$r]->activity : '' }}</td>
                            </tr>
                            @endfor
                        </table>
                        <!-- Petugas Kanan -->
                        <!-- UPDATED: Font size jadi 7pt -->
                        <table class="w-100 no-border" style="font-size: 7pt; border-top: 0.5px solid black;">
                            <tr>
                                <td style="width: 75px;">Tally Kapal</td><td class="sep-col">:</td><td style="width: 60px;">{{ optional($activity)->tally_ship ?? '' }}</td>
                                <td style="width: 40px;">Operator</td><td class="sep-col">:</td><td>{{ optional($activity)->operator_ship ?? '' }}</td>
                                <td>Forklift</td><td class="sep-col">:</td><td>{{ optional($activity)->forklift_ship ?? '' }}</td>
                            </tr>
                            <tr>
                                <td style="white-space: nowrap;">Operator Gudang</td><td class="sep-col">:</td><td>{{ optional($activity)->operator_warehouse ?? '' }}</td>
                                <td>Forklift</td><td class="sep-col">:</td><td>{{ optional($activity)->forklift_warehouse ?? '' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach

    <!-- II. PEMUATAN UREA CURAH -->
    <div class="section-header">II. PEMUATAN UREA CURAH</div>

    @php
        $bulkActivities = $report->bulkLoadingActivities->sortBy('sequence');
        // Jika kosong, buat array dengan 1 elemen null agar loop berjalan sekali (untuk menampilkan form kosong)
        if ($bulkActivities->isEmpty()) {
            $bulkActivities = [null];
        }
    @endphp

    @foreach($bulkActivities as $index => $bulk)
        <div class="activity-box">
            <table class="w-100 no-border">
                <tr>
                    <td style="width: 50%; vertical-align: top; border-right: 0.5px solid black; padding: 2px;">
                        <table class="w-100">
                            <!-- UPDATED: Width Nama Kapal diperlebar jadi 70px -->
                            <tr><td style="width: 15px;"><b>{{ optional($bulk)->sequence ?? ($loop->iteration) }}.</b></td><td style="width: 70px;">Nama Kapal</td><td class="sep-col">:</td><td>{{ optional($bulk)->ship_name ?? '' }}</td></tr>
                            <tr><td></td><td>Agent</td><td class="sep-col">:</td><td>{{ optional($bulk)->agent ?? '' }}</td></tr>
                            <tr><td></td><td>Jenis Urea</td><td class="sep-col">:</td><td>{{ optional($bulk)->commodity ?? '' }}</td></tr>
                            <tr><td></td><td>Kapasitas</td><td class="sep-col">:</td><td>{{ optional($bulk)->capacity ? formatQty($bulk->capacity) . ' MT' : '' }}</td></tr>
                        </table>
                    </td>
                    <td style="width: 50%; vertical-align: top; padding: 2px;">
                        <table class="w-100">
                            <tr><td style="width: 60px;">Sandar</td><td class="sep-col">:</td><td>{{ optional($bulk)->berthing_time ?? '' }}</td></tr>
                            <tr><td>Mulai Muat</td><td class="sep-col">:</td><td>{{ optional($bulk)->start_loading_time ?? '' }}</td></tr>
                            <tr><td>Tujuan</td><td class="sep-col">:</td><td>{{ optional($bulk)->destination ?? '' }}</td></tr>
                            <tr><td>Petugas PBM</td><td class="sep-col">:</td><td>{{ optional($bulk)->stevedoring ?? '' }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table class="table-bordered w-100" style="margin-top: 1px; border:none;">
                <tr class="bg-gray">
                    <th style="width: 15%;">TANGGAL</th><th style="width: 10%;">JAM</th><th>URAIAN KEGIATAN</th><th style="width: 10%;">COB</th>
                </tr>
                @php
                    // UPDATED: Sort data berdasarkan datetime ascending (lebih lama/tua dulu)
                    $bLogs = $bulk ? $bulk->logs->sortBy('datetime') : collect([]);
                @endphp
                @foreach($bLogs as $log)
                <tr>
                    <td class="text-center">{{ \Carbon\Carbon::parse($log->datetime)->translatedFormat('d M Y') }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($log->datetime)->format('H:i') }}</td>
                    <td>{{ $log->activity }}</td>
                    <td class="text-center">{{ $log->cob }}</td>
                </tr>
                @endforeach
                @for($k=0; $k < (2 - count($bLogs)); $k++)
                <tr><td class="row-empty"></td><td></td><td></td><td></td></tr>
                @endfor
            </table>
        </div>
    @endforeach

    <!-- III. BONGKAR BAHAN BAKU / CONTAINER -->
    <table class="w-100 section-container" style="margin-bottom: 2px; margin-top: 5px;">
        <tr>
            <td class="w-50" style="padding:0;"><div class="section-header" style="margin: 0;">III. BONGKAR BAHAN BAKU</div></td>
            <td class="w-50" style="padding:0; vertical-align: bottom;"><div class="text-center text-bold section-header" style="margin: 0;">BONGKAR / MUAT CONTAINER</div></td>
        </tr>
    </table>

    <div class="section-container">
        <table class="w-100">
            <tr>
                <!-- KIRI: MATERIAL ACTIVITY -->
                <td class="w-50" style="padding-right: 2px; vertical-align: top;">
                    @if($report->materialActivity)
                    <div class="border-all">
                        <table class="w-100 no-border" style="padding: 2px;">
                            <tr><td style="width: 15px;"><b>1.</b></td><td style="width: 50px;">Nama Kapal</td><td class="sep-col">:</td><td class="border-bottom">{{ $report->materialActivity->ship_name }}</td></tr>
                            <tr><td></td><td>Agent</td><td class="sep-col">:</td><td class="border-bottom">{{ $report->materialActivity->agent }}</td></tr>
                            <tr><td></td><td>Kapasitas</td><td class="sep-col">:</td><td class="border-bottom">{{ formatQty($report->materialActivity->capacity) }} MT</td></tr>
                        </table>
                        <table class="table-bordered w-100 no-border-left no-border-right" style="margin-top: 1px;">
                            <tr class="bg-gray"><th>Jenis</th><th>Sekarang</th><th>Lalu</th><th>Total</th></tr>
                            @php $materials = $report->materialActivity->items; $matMin = 3; @endphp
                            @foreach($materials as $mat)
                            <tr>
                                <td>{{ $mat->raw_material_type }}</td>
                                <td class="text-center">{{ formatQty($mat->qty_current) }}</td>
                                <td class="text-center">{{ formatQty($mat->qty_prev) }}</td>
                                <td class="text-center">{{ formatQty($mat->qty_total) }}</td>
                            </tr>
                            @endforeach
                            @for($y = $materials->count(); $y < $matMin; $y++)
                            <tr><td class="row-empty"></td><td></td><td></td><td></td></tr>
                            @endfor
                        </table>
                        <div style="border-top: 0.5px solid black; padding: 2px;">
                            <table class="w-100 no-border">
                                <tr>
                                    <td style="width: 50px;">Tally Kapal</td><td style="width: 3px;">:</td>
                                    <td style="border-right: 0.5px solid black; padding-right:3px;">{{ $report->materialActivity->ship_tally_names }}</td>
                                    <td style="width: 50px; padding-left: 3px;">Operator FL</td><td style="width: 3px;">:</td>
                                    <td>{{ $report->materialActivity->forklift_operator_names }}</td>
                                </tr>
                                <tr style="border-top: 0.5px solid black;">
                                    <td>Tally Kirim</td><td>:</td>
                                    <td style="border-right: 0.5px solid black; padding-right:3px;">{{ $report->materialActivity->delivery_tally_names }}</td>
                                    <td style="padding-left: 3px;">Jam Kerja</td><td>:</td>
                                    <td>{{ $report->materialActivity->working_hours }}</td>
                                </tr>
                                <tr style="border-top: 0.5px solid black;"><td>Driver</td><td>:</td><td colspan="4">{{ $report->materialActivity->driver_names }}</td></tr>
                            </table>
                        </div>
                    </div>
                    @endif
                </td>
                <!-- KANAN: CONTAINER ACTIVITY -->
                <td class="w-50" style="padding-left: 2px; vertical-align: top;">
                    @if($report->containerActivity)
                    <div class="border-all">
                        <table class="w-100 no-border" style="padding: 2px;">
                            <tr><td style="width: 15px;"><b>2.</b></td><td style="width: 50px;">Nama Kapal</td><td class="sep-col">:</td><td class="border-bottom">{{ $report->containerActivity->ship_name }}</td></tr>
                            <tr><td></td><td>Agent</td><td class="sep-col">:</td><td class="border-bottom">{{ $report->containerActivity->agent }}</td></tr>
                            <tr><td></td><td>Kapasitas</td><td class="sep-col">:</td><td class="border-bottom">{{ $report->containerActivity->capacity }}</td></tr>
                        </table>
                        <table class="table-bordered w-100 no-border-left no-border-right" style="margin-top: 1px;">
                            <tr class="bg-gray"><th>Jam</th><th>Sekarang</th><th>Lalu</th><th>Total</th><th>Ket</th></tr>
                            @php $containers = $report->containerActivity->items; $cntMin = 3; @endphp
                            @foreach($containers as $cont)
                            <tr>
                                <td class="text-center">{{ $cont->time ? \Carbon\Carbon::parse($cont->time)->format('H:i') : '' }}</td>
                                <td class="text-center">{{ formatQty($cont->qty_current) }}</td>
                                <td class="text-center">{{ formatQty($cont->qty_prev) }}</td>
                                <td class="text-center">{{ formatQty($cont->qty_total) }}</td>
                                <td>{{ $cont->status }}</td>
                            </tr>
                            @endforeach
                            @for($x = $containers->count(); $x < $cntMin; $x++)
                            <tr><td class="row-empty"></td><td></td><td></td><td></td><td></td></tr>
                            @endfor
                        </table>
                        <div style="border-top: 0.5px solid black; padding: 2px;">
                            <table class="w-100 no-border">
                                <tr style="border-bottom: 0.5px solid black;">
                                    <td style="width: 60px;">Tally muat</td><td style="width: 3px;">:</td><td>{{ $report->containerActivity->ship_tally_names }}</td>
                                </tr>
                                <tr style="border-bottom: 0.5px solid black;">
                                    <td>Tally gudang</td><td>:</td><td>{{ $report->containerActivity->gudang_tally_names }}</td>
                                </tr>
                                <tr><td>Driver</td><td>:</td><td>{{ $report->containerActivity->driver_names }}</td></tr>
                            </table>
                        </div>
                    </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- IV. TRACKING -->
    <div class="section-container">
        <div class="section-header">IV. TRACKING PENGIRIMAN PUPUK KANTONG</div>
        <table class="table-bordered w-100">
            <tr class="bg-gray">
                <th rowspan="2" style="width: 20px;">NO.</th><th rowspan="2">NAMA TRUCK</th><th colspan="2">NOMOR</th><th rowspan="2">JENIS MARKING</th><th colspan="3">TERKIRIM</th>
            </tr>
            <tr class="bg-gray"><th>DO/SO</th><th>KAPASITAS</th><th>SEKARANG</th><th>LALU</th><th>AKUMULASI</th></tr>
            @php $turbaNo = 1; @endphp
            @if($report->turbaActivity && $report->turbaActivity->deliveries->count() > 0)
                @foreach($report->turbaActivity->deliveries as $deliv)
                <tr>
                    <td class="text-center">{{ $turbaNo++ }}</td>
                    <td>{{ $deliv->truck_name }}</td>
                    <td class="text-center">{{ $deliv->do_so_number }}</td>
                    <td class="text-right">{{ formatQty($deliv->capacity) }}</td>
                    <td class="text-center">{{ $deliv->marking_type }}</td>
                    <td class="text-right">{{ formatQty($deliv->qty_current) }}</td>
                    <td class="text-right">{{ formatQty($deliv->qty_prev) }}</td>
                    <td class="text-right">{{ formatQty($deliv->qty_accumulated) }}</td>
                </tr>
                @endforeach
            @endif
            @for($j=$turbaNo; $j<=5; $j++)
                <tr><td class="text-center">{{ $j }}</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </table>
        @if($report->turbaActivity)
        <table class="w-100 border-left border-right border-bottom" style="margin-bottom: 2px;">
            <tr>
                <td class="w-33" style="padding: 1px;">> Tally Gudang : {{ $report->turbaActivity->tally_gudang_names }}</td>
                <td class="w-33" style="padding: 1px;">> Operator Forklift : {{ $report->turbaActivity->forklift_operator_names }}</td>
                <td class="w-33" style="padding: 1px;">> Jam Kerja : {{ $report->turbaActivity->working_hours }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 1px;">> Driver : {{ $report->turbaActivity->driver_names }}</td>
            </tr>
        </table>
        @endif
    </div>

    <!-- V. KEADAAN PERALATAN -->
    <div class="section-container">
        <div class="section-header">V. KEADAAN PERALATAN DAN KENDARAAN OPERASIONAL</div>
        <table class="w-100">
            <tr>
                <!-- KIRI: KENDARAAN & FORKLIFT -->
                <td style="width: 58%; padding-right: 2px; vertical-align: top;">
                    <div class="text-center border-all bg-gray text-bold" style="border-bottom:none; padding: 1px;">TRAILLER / FORKLIFT DAN SARANA JEMPUTAN</div>
                    <table class="w-100">
                        <tr>
                            @php
                                $masterUnits = \App\Models\MasterUnit::orderBy('id')->get();
                                $unitLogs = $report->unitCheckLogs->where('category', 'vehicle')->keyBy('master_id');
                                $chunks = $masterUnits->chunk(ceil($masterUnits->count() / 2));
                            @endphp
                            @foreach($chunks as $chunk)
                            <td class="w-50 p-0" style="vertical-align: top;">
                                <table class="table-bordered w-100 tiny-text">
                                    <tr class="bg-gray">
                                        <th rowspan="2" style="width:15px;">NO</th><th rowspan="2">NAMA ALAT</th><th rowspan="2" style="width:20px;">ISI BBM</th><th colspan="2">KONDISI</th>
                                    </tr>
                                    <!-- EDIT 1: UBAH TRM/SRH MENJADI TERIMA/SERAHKAN -->
                                    <tr class="bg-gray"><th style="width:20px;">TERIMA</th><th style="width:20px;">SERAHKAN</th></tr>
                                    @foreach($chunk as $index => $unit)
                                    @php
                                        $log = $unitLogs->get($unit->id);
                                        $rec = $log->condition_received ?? ''; $han = $log->condition_handed_over ?? '';
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $unit->id }}</td>
                                        <td>{{ $unit->short_display_name }}</td>
                                        <td class="text-center">{{ $log->fuel_level ?? '' }}</td>
                                        <td class="text-center {{ $rec == 'Baik' ? 'text-green' : ($rec == 'Rusak' ? 'text-red' : '') }}">{{ $rec }}</td>
                                        <td class="text-center {{ $han == 'Baik' ? 'text-green' : ($han == 'Rusak' ? 'text-red' : '') }}">{{ $han }}</td>
                                    </tr>
                                    @endforeach
                                </table>
                            </td>
                            @endforeach
                        </tr>
                    </table>
                </td>
                <!-- KANAN: INVENTARIS & SHELTER -->
                <td style="width: 42%; padding-left: 2px; vertical-align: top;">
                    <div class="text-center border-all bg-gray text-bold" style="border-bottom:none; padding: 1px;">DAFTAR INVENTARIS</div>
                    <table class="table-bordered w-100 mb-2 tiny-text">
                        <tr class="bg-gray">
                            <th rowspan="2" style="width: 15px;">NO</th><th rowspan="2">NAMA BARANG</th><th rowspan="2" style="width: 20px;">JML</th><th colspan="2">KONDISI</th>
                        </tr>
                        <!-- EDIT 2: UBAH TRM/SRH MENJADI TERIMA/SERAHKAN -->
                        <tr class="bg-gray"><th>TERIMA</th><th>SERAHKAN</th></tr>
                        @php
                            $masterInventories = \App\Models\MasterInventoryItem::orderBy('id')->get();
                            $invLogs = $report->unitCheckLogs->where('category', 'inventory')->keyBy('master_id');
                        @endphp
                        @foreach($masterInventories as $index => $item)
                        @php $log = $invLogs->get($item->id); $rec = $log->condition_received ?? ''; $han = $log->condition_handed_over ?? ''; @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-center">{{ $log->quantity ?? $item->stock }}</td>
                            <td class="text-center {{ $rec == 'Baik' ? 'text-green' : ($rec == 'Rusak' ? 'text-red' : '') }}">{{ $rec }}</td>
                            <td class="text-center {{ $han == 'Baik' ? 'text-green' : ($han == 'Rusak' ? 'text-red' : '') }}">{{ $han }}</td>
                        </tr>
                        @endforeach
                    </table>
                    <div class="text-center border-all bg-gray text-bold" style="border-bottom:none; padding: 1px;">LINGKUNGAN SHELTER</div>
                    <table class="table-bordered w-100 tiny-text">
                        <tr class="bg-gray">
                            <th rowspan="2" style="width: 15px;">NO</th><th rowspan="2">ITEM</th><th colspan="2">KONDISI</th>
                        </tr>
                        <!-- EDIT 3: UBAH TRM/SRH MENJADI TERIMA/SERAHKAN -->
                        <tr class="bg-gray"><th>TERIMA</th><th>SERAHKAN</th></tr>
                        @php
                            $shelterLogs = $report->unitCheckLogs->where('category', 'shelter')->keyBy('item_name');
                            $groups = ['1' => ['label' => 'KEBERSIHAN', 'items' => ['Ruangan Shelter', 'Halaman Shelter', 'Selokan/Parit']], '2' => ['label' => 'KERAPIAN', 'items' => ['Jala-Jala Angkat', 'Jala-Jala Lambung', 'Terpal', 'Chain Sling']]];
                        @endphp
                        @foreach($groups as $idx => $group)
                            <tr>
                                <td class="text-center text-bold">{{ $idx }}</td>
                                <td colspan="3" class="text-bold text-center" style="background-color: #f9f9f9;">{{ $group['label'] }}</td>
                            </tr>
                            @foreach($group['items'] as $itemName)
                            @php $sLog = $shelterLogs->get($itemName); $rec = $sLog->condition_received ?? ''; $han = $sLog->condition_handed_over ?? ''; @endphp
                            <tr>
                                <td class="no-border-top no-border-bottom"></td>
                                <td style="padding-left: 5px;">{{ $itemName }}</td>
                                <td class="text-center {{ $rec == 'Baik' ? 'text-green' : ($rec == 'Rusak' ? 'text-red' : '') }}">{{ $rec }}</td>
                                <td class="text-center {{ $han == 'Baik' ? 'text-green' : ($han == 'Rusak' ? 'text-red' : '') }}">{{ $han }}</td>
                            </tr>
                            @endforeach
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- VI. KARYAWAN -->
    <div class="section-container">
        <div class="section-header">VI. KARYAWAN</div>
        <table class="w-100">
            <tr>
                <!-- KIRI: KARYAWAN SHIFT (DYNAMIC ROWS) -->
                <td class="w-50" style="padding-right: 2px; vertical-align: top;">
                    <table class="table-bordered w-100">
                        <tr class="bg-gray"><th colspan="5" class="text-left" style="padding-left: 5px;">KARYAWAN SHIFT YANG BERTUGAS</th></tr>
                        <tr class="bg-gray"><th style="width: 15px;">NO.</th><th>NAMA</th><th>MASUK</th><th>PULANG</th><th>KET</th></tr>
                        @php $shiftEmps = $report->employeeLogs->where('category', 'shift')->values(); @endphp
                        @foreach($shiftEmps as $index => $emp)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $emp->name }}</td>
                            <td class="text-center">{{ $emp->time_in ? \Carbon\Carbon::parse($emp->time_in)->format('H:i') : '' }}</td>
                            <td class="text-center">{{ $emp->time_out ? \Carbon\Carbon::parse($emp->time_out)->format('H:i') : '' }}</td>
                            <td>{{ $emp->description }}</td>
                        </tr>
                        @endforeach
                        {{-- Fallback if no employees found --}}
                        @if($shiftEmps->count() == 0)
                             <tr><td class="text-center">1</td><td></td><td></td><td></td><td></td></tr>
                        @endif
                    </table>
                </td>
                <!-- KANAN: OPERASI & LAIN -->
                <td class="w-50" style="padding-left: 2px; vertical-align: top;">
                    <div class="text-center border-all bg-gray text-bold" style="border-bottom:none; padding: 1px;">KARYAWAN OPERASI</div>
                    <table class="table-bordered w-100 mb-2">
                        <tr class="bg-gray"><th style="width: 15px;">NO.</th><th>LEMBUR</th><th style="width: 15px;">NO.</th><th>RELIEF SIANG/MALAM</th></tr>
                        @php
                            $lemburEmps = $report->employeeLogs->where('category', 'operasi')->where('description', 'Lembur')->values();
                            $reliefEmps = $report->employeeLogs->where('category', 'operasi')->where('description', 'Relief Malam')->values();
                        @endphp
                        @for($j=0; $j<15; $j++)
                        <tr>
                            <td class="text-center">{{ $j+1 }}</td>
                            <td>{{ isset($lemburEmps[$j]) ? $lemburEmps[$j]->name : '' }}</td>
                            <td class="text-center">{{ $j+16 }}</td>
                            <td>{{ isset($reliefEmps[$j]) ? $reliefEmps[$j]->name : '' }}</td>
                        </tr>
                        @endfor
                    </table>
                    <table class="table-bordered w-100">
                        <tr class="bg-gray"><th>KEGIATAN LAIN</th><th>PERSONIL</th><th>JAM KERJA</th></tr>
                        @php $otherActs = $report->employeeLogs->where('category', 'lain')->values(); @endphp
                        @for($k=0; $k<5; $k++)
                        <tr>
                            <td style="height: 12px;">{{ isset($otherActs[$k]) ? $otherActs[$k]->description : '' }}</td>
                            <td>{{ isset($otherActs[$k]) ? $otherActs[$k]->name : '' }}</td>
                            <td class="text-center">{{ isset($otherActs[$k]) ? $otherActs[$k]->work_time : '' }}</td>
                        </tr>
                        @endfor
                    </table>
                </td>
            </tr>
        </table>

        <!-- ADDED: TABEL KARYAWAN OP.7 & PENGGANTI (FULL WIDTH DI BAWAH) -->
        <div class="text-center border-all bg-gray text-bold" style="border-bottom:none; margin-top: 10px; padding: 1px;">KARYAWAN OP.7</div>
        <table class="table-bordered w-100 mb-2" style="table-layout: fixed;">
            <tr class="bg-gray">
                <th style="width: 4%;">NO.</th>
                <th style="width: 22%;">NAMA</th>
                <th style="width: 15%;">NO. FORKLIFT</th>
                <th style="width: 23%;">AREA KERJA</th>
                <th style="width: 7%;">MASUK</th>
                <th style="width: 7%;">KELUAR</th>
                <th style="width: 22%;">KETERANGAN</th>
            </tr>
            @php $op7Emps = $report->employeeLogs->where('category', 'op7')->values(); @endphp
            @foreach($op7Emps as $index => $emp)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $emp->name }}</td>
                <td>{{ $emp->no_forklift_ }}</td>
                <td>{{ $emp->work_area }}</td>
                <td class="text-center">{{ $emp->time_in ? \Carbon\Carbon::parse($emp->time_in)->format('H:i') : '' }}</td>
                <td class="text-center">{{ $emp->time_out ? \Carbon\Carbon::parse($emp->time_out)->format('H:i') : '' }}</td>
                <td>{{ $emp->description }}</td>
            </tr>
            @endforeach
            @if($op7Emps->count() == 0)
                <tr><td class="text-center">1</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endif
        </table>

        <div class="text-center border-all bg-gray text-bold" style="border-bottom:none; margin-top: 10px; padding: 1px;">DAFTAR PENGGANTI OPERATOR YANG TIDAK MASUK</div>
        <table class="table-bordered w-100" style="table-layout: fixed;">
            <tr class="bg-gray">
                <th style="width: 4%;">NO.</th>
                <th style="width: 22%;">NAMA PENGGANTI</th>
                <th style="width: 15%;">NO. FORKLIFT</th>
                <th style="width: 23%;">AREA KERJA</th>
                <th style="width: 7%;">MASUK</th>
                <th style="width: 7%;">KELUAR</th>
                <th style="width: 22%;">MENGGANTIKAN / KET</th>
            </tr>
            @php $replacementEmps = $report->employeeLogs->where('category', 'replacement')->values(); @endphp
            @foreach($replacementEmps as $index => $emp)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $emp->name }}</td>
                <td>{{ $emp->no_forklift_ }}</td>
                <td>{{ $emp->work_area }}</td>
                <td class="text-center">{{ $emp->time_in ? \Carbon\Carbon::parse($emp->time_in)->format('H:i') : '' }}</td>
                <td class="text-center">{{ $emp->time_out ? \Carbon\Carbon::parse($emp->time_out)->format('H:i') : '' }}</td>
                <td>{{ $emp->description }}</td>
            </tr>
            @endforeach
            @if($replacementEmps->count() == 0)
                <tr><td class="text-center">1</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endif
        </table>
    </div>

    <!-- SIGNATURES (UPDATED LAYOUT) -->

    <!-- 1. Kota dan Tanggal (Menggunakan Tabel agar sejajar presisi dengan kolom tanda tangan kanan) -->
    <table class="w-100 no-border" style="margin-top: 25px; margin-bottom: 10px; font-size: 9pt;">
        <tr>
            <td class="w-33"></td> <!-- Kosong untuk kiri -->
            <td class="w-33"></td> <!-- Kosong untuk tengah -->
            <td class="w-33 text-center"> <!-- Rata tengah mengikuti kolom tanda tangan kanan -->
                Bontang, {{ \Carbon\Carbon::parse($report->report_date)->locale('id')->translatedFormat('d F Y') }}
            </td>
        </tr>
    </table>

    <!-- Tabel Tanda Tangan -->
    <table class="w-100 no-border" style="font-size: 9pt;">
        <tr>
            <!-- KIRI: MANAGER OPERASI & K3 (ADMIN) -->
            <td class="text-center w-33" style="vertical-align: top;">
                <!-- Spacer agar sejajar dengan baris kedua kolom kanan (karena ada tanggal) -->
                <div style="margin-bottom: 20px;">Mengetahui,</div>

                <div class="signature-box" style="height: 80px; margin-bottom: 5px;">
                    @if($report->status === \App\Enums\ReportStatus::Approved && $report->approver && $report->approver->signature_path)
                        @if(file_exists(public_path($report->approver->signature_path)))
                            <img src="{{ public_path($report->approver->signature_path) }}" class="signature-img">
                        @endif
                    @endif
                </div>

                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $report->approver ? $report->approver->name : 'Mustari, ST' }}
                </div>
                <div>Manager Operasi & K3</div>
            </td>

            <!-- TENGAH: PENERIMA (FOREMAN SHIFT BERIKUTNYA) -->
            <td class="text-center w-33" style="vertical-align: top;">
                <!-- Spacer agar sejajar dengan baris kedua kolom kanan -->
                <div style="margin-bottom: 20px;">Diterima / Melanjutkan,</div>

                <div class="signature-box" style="height: 80px; margin-bottom: 5px;">
                    @if(in_array($report->status, [\App\Enums\ReportStatus::Acknowledged, \App\Enums\ReportStatus::Approved], true) && $report->receiver && $report->receiver->signature_path)
                        @if(file_exists(public_path($report->receiver->signature_path)))
                            <img src="{{ public_path($report->receiver->signature_path) }}" class="signature-img">
                        @endif
                    @endif
                </div>

                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $report->receiver ? $report->receiver->name : '_____________________' }}
                </div>
                <div>Foreman Group {{ $report->received_by_group }}</div>
            </td>

            <!-- KANAN: PEMBUAT (FOREMAN SHIFT SAAT INI) -->
            <td class="text-center w-33" style="vertical-align: top;">
                <!-- Tanggal diletakkan di dalam cell agar center dengan tanda tangan -->
                <div style="margin-bottom: 20px;">Dilaksanakan / Menyerahkan,</div>

                <div class="signature-box" style="height: 80px; margin-bottom: 5px;">
                    @if($report->creator && $report->creator->signature_path)
                        @if(file_exists(public_path($report->creator->signature_path)))
                            <img src="{{ public_path($report->creator->signature_path) }}" class="signature-img">
                        @endif
                    @endif
                </div>

                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $report->creator ? $report->creator->name : '_____________________' }}
                </div>
                <div>Foreman Group {{ $report->group_name }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
