{{--
    Format laporan operasi harian.
    Dipakai bersama oleh: pdf.blade.php (DomPDF) dan viewpdf.blade.php (preview HTML).
    Variabel: $report, $isPdf (true untuk render PDF, false untuk HTML).
--}}
@php
    use App\Enums\ReportStatus;

    $isPdf = $isPdf ?? false;

    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '';
    $fmtShortDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '';
    $fmtDay = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('l') : '';
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('H:i') : '';
    $fmtDateTime = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y H:i') : '';
    $fmtQty = function ($value, string $suffix = '') {
        if ($value === null || $value === '') return '';
        $number = (float) $value;
        $text = fmod($number, 1.0) !== 0.0 ? number_format($number, 2) : number_format($number, 0);
        return trim($text.' '.$suffix);
    };
    $sumQty = fn ($a, $b) => (float) ($a ?? 0) + (float) ($b ?? 0);
    $statusValue = $report->status instanceof ReportStatus ? $report->status->value : (string) $report->status;

    $imgSrc = function ($path) use ($isPdf) {
        $path = ltrim((string) $path, '/');
        if ($path === '') return null;
        $files = [public_path($path), public_path('storage/'.$path), storage_path('app/public/'.$path)];
        if ($isPdf) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'png';
                    $mime = $ext === 'jpg' ? 'jpeg' : $ext;
                    return 'data:image/'.$mime.';base64,'.base64_encode(file_get_contents($file));
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
    $creatorSig = $report->creator ? $imgSrc($report->creator?->signature_path) : null;
    $receiverSig = in_array($statusValue, [ReportStatus::Acknowledged->value, ReportStatus::Approved->value], true) && $report->receiver
        ? $imgSrc($report->receiver?->signature_path)
        : null;
    $approverSig = $statusValue === ReportStatus::Approved->value && $report->approver
        ? $imgSrc($report->approver?->signature_path)
        : null;

    $loadingActivities = $report->loadingActivities->sortBy('sequence')->values();
    $bulkActivities = $report->bulkLoadingActivities->sortBy('sequence')->values();
    $materialActivities = $report->materialActivity->sortBy('sequence')->values();
    $containerActivities = $report->containerActivity->sortBy('sequence')->values();
    $turba = $report->turbaActivity;
    $turbaDeliveries = $turba ? $turba->deliveries->values() : collect();

    $masterUnits = \App\Models\MasterUnit::orderedForReport()->get();
    $vehicleLogs = $report->unitCheckLogs->where('category', 'vehicle')->values();
    $vehicleByMaster = $vehicleLogs->whereNotNull('master_id')->keyBy(fn ($log) => (string) $log->master_id);
    $unitIds = $masterUnits->pluck('id')->map(fn ($id) => (string) $id)->all();
    $extraVehicleLogs = $vehicleLogs->filter(fn ($log) => ! $log->master_id || ! in_array((string) $log->master_id, $unitIds, true))->values();

    $masterInventories = \App\Models\MasterInventoryItem::orderBy('id')->get();
    $inventoryLogs = $report->unitCheckLogs->where('category', 'inventory')->values();
    $inventoryByMaster = $inventoryLogs->whereNotNull('master_id')->keyBy(fn ($log) => (string) $log->master_id);
    $inventoryIds = $masterInventories->pluck('id')->map(fn ($id) => (string) $id)->all();
    $extraInventoryLogs = $inventoryLogs->filter(fn ($log) => ! $log->master_id || ! in_array((string) $log->master_id, $inventoryIds, true))->values();

    $shelterLogs = $report->unitCheckLogs->where('category', 'shelter')->values();
    $shelterByName = $shelterLogs->keyBy(fn ($log) => mb_strtolower((string) $log->item_name));
    $shelterMaster = \App\Models\MasterEnvironmentItem::where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();
    $shelterNames = $shelterMaster->pluck('name')->map(fn ($name) => mb_strtolower((string) $name))->all();
    $extraShelterLogs = $shelterLogs->filter(fn ($log) => ! in_array(mb_strtolower((string) $log->item_name), $shelterNames, true))->values();

    $shiftEmps = $report->employeeLogs->where('category', 'shift')->values();
    $operationEmps = $report->employeeLogs->where('category', 'operasi')->values();
    $overtimeEmps = $operationEmps
        ->filter(fn ($employee): bool => str_contains(strtolower((string) $employee->description), 'lembur'))
        ->values();
    $reliefEmps = $operationEmps
        ->filter(fn ($employee): bool => str_contains(strtolower((string) $employee->description), 'relief'))
        ->values();
    $op7Emps = $report->employeeLogs->where('category', 'op7')->values();
    $replacementEmps = $report->employeeLogs->where('category', 'replacement')->values();
    $otherActs = $report->employeeLogs->where('category', 'lain')->values();

    $vehicleRows = collect();
    $vehicleNo = 1;
    foreach ($masterUnits as $unit) {
        $log = $vehicleByMaster->get((string) $unit->id);
        $vehicleRows->push([
            'no' => $vehicleNo++,
            'name' => $unit->operational_name ?: $unit->short_display_name,
            'fuel' => $log->fuel_level ?? '',
            'received' => $log->condition_received ?? '',
            'handed' => $log->condition_handed_over ?? '',
        ]);
    }
    foreach ($extraVehicleLogs as $log) {
        $vehicleRows->push([
            'no' => $vehicleNo++,
            'name' => $log->item_name,
            'fuel' => $log->fuel_level,
            'received' => $log->condition_received,
            'handed' => $log->condition_handed_over,
        ]);
    }
    $vehicleSplit = (int) ceil(max(1, $vehicleRows->count()) / 2);
    $vehicleLeftRows = $vehicleRows->take($vehicleSplit)->values();
    $vehicleRightRows = $vehicleRows->slice($vehicleSplit)->values();
    $vehicleMaxRows = max($vehicleLeftRows->count(), $vehicleRightRows->count());

    $inventoryRows = collect();
    $inventoryNo = 1;
    foreach ($masterInventories as $item) {
        $log = $inventoryByMaster->get((string) $item->id);
        $inventoryRows->push([
            'no' => $inventoryNo++,
            'name' => $item->name,
            'qty' => $log->quantity ?? $item->stock,
            'received' => $log->condition_received ?? '',
            'handed' => $log->condition_handed_over ?? '',
        ]);
    }
    foreach ($extraInventoryLogs as $log) {
        $inventoryRows->push([
            'no' => $inventoryNo++,
            'name' => $log->item_name,
            'qty' => $log->quantity,
            'received' => $log->condition_received,
            'handed' => $log->condition_handed_over,
        ]);
    }

    $conditionClass = function ($value) {
        $value = strtolower(trim((string) $value));
        if ($value === '') return '';
        return match (true) {
            in_array($value, ['baik', 'ready', 'normal', 'bersih', 'rapi', 'ok', 'aman'], true) => 'good',
            in_array($value, ['rusak', 'tidak normal', 'tdk normal', 'kotor', 'berantakan', 'tidak baik', 'tdk baik', 'rusak berat', 'rusak ringan', 'bermasalah'], true) => 'bad',
            default => '',
        };
    };
@endphp

<style>
    .report-paper { color: #000; font-size: 8px; line-height: 1.25; font-family: Arial, Helvetica, sans-serif; }
    .report-paper * { font-family: Arial, Helvetica, sans-serif; box-sizing: border-box; }
    .report-paper table { width: 100%; border-collapse: collapse; border-spacing: 0; margin: 0; }
    .report-paper th, .report-paper td { padding: 2px 3px; vertical-align: top; }
    .report-paper .head-wrap { margin-bottom: 6px; }
    .report-paper .head-wrap td { vertical-align: middle; }
    .report-paper .logo { height: 36px; }
    .report-paper .title { text-align: center; }
    .report-paper .title .l1 { font-size: 13px; font-weight: bold; letter-spacing: .5px; }
    .report-paper .title .l2 { font-size: 10px; font-weight: bold; letter-spacing: .3px; }
    .report-paper .doc-id { text-align: right; font-weight: bold; font-size: 8px; }
    .report-paper .addr { margin-bottom: 7px; }
    .report-paper .addr td { font-size: 8px; }
    .report-paper .addr .lab { font-weight: bold; }
    .report-paper .meta td { padding: 1px 0; }
    .report-paper .meta .ml { width: 62px; font-weight: bold; }
    .report-paper .meta .line { border-bottom: 1px solid #000; display: inline-block; min-width: 112px; padding-left: 4px; }
    .report-paper .sec { margin-top: 8px; padding: 4px 6px; border: 1px solid #000; border-bottom: none; background: #e5e7eb; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .2px; page-break-after: avoid; }
    .report-paper .subsec { padding: 3px 5px; border: 1px solid #000; border-bottom: none; background: #eef0f2; font-size: 8px; font-weight: bold; text-transform: uppercase; }
    .report-paper .panel { border: none; margin-bottom: 6px; page-break-inside: avoid; }
    .report-paper .panel-title { padding: 4px 6px; border: 1px solid #000; border-bottom: none; background: #eef0f2; font-weight: bold; }
    .report-paper .grid th, .report-paper .grid td { border: 1px solid #000; }
    .report-paper .grid th { background: #eceff1; text-align: center; font-size: 7.4px; font-weight: bold; vertical-align: middle; }
    .report-paper .info td { padding: 1px 3px; }
    .report-paper .label { width: 62px; font-weight: bold; white-space: nowrap; }
    .report-paper .colon { width: 5px; text-align: center; }
    .report-paper .line-cell { border-bottom: 1px solid #000; min-height: 10px; word-break: break-word; }
    .report-paper .metric-title { font-weight: bold; padding: 3px 3px 1px; }
    .report-paper .metric td { padding: 1px 3px; }
    .report-paper .c { text-align: center; }
    .report-paper .r { text-align: right; }
    .report-paper .b { font-weight: bold; }
    .report-paper .muted { color: #444; }
    .report-paper .empty-note { text-align: center; font-style: italic; color: #555; padding: 6px; }
    .report-paper .small { font-size: 7px; }
    .report-paper .good { background: #fff; color: #166534; font-weight: bold; }
    .report-paper .bad { background: #fff; color: #991b1b; font-weight: bold; }
    .report-paper .avoid-break { page-break-inside: avoid; }
    .report-paper .capacity-line { padding: 2px 3px; }
    .report-paper .capacity-line .cap-value { border-bottom: 1px dotted #000; display: inline-block; min-width: 72px; text-align: center; }
    .report-paper .pair { table-layout: fixed; }
    .report-paper .pair td { vertical-align: top; }
    .report-paper .pair .pair-left { width: 50%; padding-right: 3px; }
    .report-paper .pair .pair-right { width: 50%; padding-left: 3px; }
    .report-paper .pair .sec { margin-top: 8px; }
    .report-paper .pair .panel { margin-bottom: 5px; }
    .report-paper .compact th, .report-paper .compact td { padding: 1px 2px; }
    .report-paper .compact th { font-size: 6.8px; }
    .report-paper .mini-title { text-align: center; font-weight: bold; background: #eceff1; text-transform: uppercase; }
    .report-paper .category-row td { text-align: center; font-weight: bold; background: #eef0f2; }
    .report-paper .sign { margin-top: 8px; page-break-inside: avoid; }
    .report-paper .company { text-align: center; font-weight: bold; font-size: 9px; padding: 8px 0 2px; }
    .report-paper .sign td { width: 33.33%; text-align: center; vertical-align: top; font-size: 8.5px; padding: 2px 12px; }
    .report-paper .sigwrap { height: 52px; margin: 3px 0; }
    .report-paper .sigwrap img { max-height: 52px; max-width: 145px; }
    .report-paper .nm { font-weight: bold; text-decoration: underline; }
    .report-paper .ttl { font-style: italic; font-size: 8px; }
    /* Gabungkan garis 1px pada pertemuan dua tabel/panel bersebelahan agar tidak dobel. */
    .report-paper .grid + .grid,
    .report-paper .panel table + table { margin-top: -1px; }
    /* Bingkai blok info (Pemuatan Pupuk Kantong) tanpa mengandalkan border panel. */
    .report-paper .licol { border-top: 1px solid #000; }
    .report-paper .licol-first { border-left: 1px solid #000; }
    .report-paper .licol-last { border-right: 1px solid #000; }
</style>

<div class="report-paper">
    <table class="head-wrap">
        <tr>
            <td style="width:120px">@if ($logo)<img class="logo" src="{{ $logo }}" alt="KSS">@else<b style="font-size:16px;color:#1f5fd1">KSS</b>@endif</td>
            <td class="title">
                <div class="l1">LAPORAN SHIFT HARIAN</div>
                <div class="l2">OPERASIONAL PELABUHAN DAN BONGKAR MUAT</div>
            </td>
            <td class="doc-id" style="width:120px"></td>
        </tr>
    </table>

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
                    <tr><td class="ml">HARI</td><td>: <span class="line">{{ $fmtDay($report->report_date) }}</span></td></tr>
                    <tr><td class="ml">TANGGAL</td><td>: <span class="line">{{ $fmtDate($report->report_date) }}</span></td></tr>
                    <tr><td class="ml">JAM KERJA</td><td>: <span class="line">{{ $report->time_range }}</span></td></tr>
                    <tr><td class="ml">SHIFT</td><td>: <span class="line">{{ $report->shift }}</span></td></tr>
                    <tr><td class="ml">GROUP</td><td>: <span class="line">{{ $report->group_name }}</span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="sec">I. Pemuatan Pupuk Kantong</div>
    @forelse ($loadingActivities as $activity)
        @php
            $dLogs = $activity->timesheets->where('category', 'delivery')->sortBy('time')->values();
            $lLogs = $activity->timesheets->where('category', 'loading')->sortBy('time')->values();
            $maxRows = max(1, $dLogs->count(), $lLogs->count());
        @endphp
        <div class="panel">
            <div class="panel-title">{{ $activity->sequence ?? $loop->iteration }}. {{ $activity->ship_name ?: 'Nama kapal belum diisi' }}</div>
            <table class="loading-info">
                <tr>
                    <td class="licol licol-first" style="width:33.34%; border-right:1px solid #000;">
                        <table class="info">
                            <tr><td class="label">Nama Kapal</td><td class="colon">:</td><td class="line-cell">{{ $activity->ship_name }}</td></tr>
                            <tr><td class="label">Agent</td><td class="colon">:</td><td class="line-cell">{{ $activity->agent }}</td></tr>
                            <tr><td class="label">Dermaga</td><td class="colon">:</td><td class="line-cell">{{ $activity->jetty }}</td></tr>
                            <tr><td class="label">Tujuan</td><td class="colon">:</td><td class="line-cell">{{ $activity->destination }}</td></tr>
                        </table>
                        <div class="metric-title">a. Pengiriman</div>
                        <table class="metric">
                            <tr><td>Sekarang</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($activity->qty_delivery_current) }}</td><td style="width:18px">Ton</td></tr>
                            <tr><td>Lalu</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($activity->qty_delivery_prev) }}</td><td>Ton</td></tr>
                            <tr><td>Akumulasi</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($sumQty($activity->qty_delivery_current, $activity->qty_delivery_prev)) }}</td><td>Ton</td></tr>
                        </table>
                    </td>
                    <td class="licol" style="width:33.33%; border-right:1px solid #000;">
                        <table class="info">
                            <tr><td class="label">Kapasitas</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($activity->capacity) }}</td><td style="width:18px">Ton</td></tr>
                            <tr><td class="label">No. WO/SO</td><td class="colon">:</td><td class="line-cell" colspan="2">{{ $activity->wo_number }}</td></tr>
                            <tr><td class="label">Jenis</td><td class="colon">:</td><td class="line-cell" colspan="2">{{ $activity->cargo_type }}</td></tr>
                            <tr><td class="label">Marking</td><td class="colon">:</td><td class="line-cell" colspan="2">{{ $activity->marking }}</td></tr>
                        </table>
                        <div class="metric-title">b. Pemuatan</div>
                        <table class="metric">
                            <tr><td>Sekarang</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($activity->qty_loading_current) }}</td><td style="width:18px">Ton</td></tr>
                            <tr><td>Lalu</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($activity->qty_loading_prev) }}</td><td>Ton</td></tr>
                            <tr><td>Akumulasi</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($sumQty($activity->qty_loading_current, $activity->qty_loading_prev)) }}</td><td>Ton</td></tr>
                        </table>
                    </td>
                    <td class="licol licol-last" style="width:33.33%;">
                        <table class="info">
                            <tr><td class="label">Tiba/Sandar</td><td class="colon">:</td><td class="line-cell">{{ $fmtDateTime($activity->arrival_time) }}</td></tr>
                            <tr><td class="label">Gang Ops</td><td class="colon">:</td><td class="line-cell">{{ $activity->operating_gang }}</td></tr>
                            <tr><td class="label">Jml TKBM</td><td class="colon">:</td><td class="line-cell">{{ $activity->tkbm_count ? $activity->tkbm_count.' Orang' : '' }}</td></tr>
                            <tr><td class="label">Mandor</td><td class="colon">:</td><td class="line-cell">{{ $activity->foreman }}</td></tr>
                        </table>
                        <div class="metric-title">c. Kerusakan</div>
                        <table class="metric">
                            <tr><td>Sekarang</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($activity->qty_damage_current) }}</td><td style="width:18px">Ton</td></tr>
                            <tr><td>Lalu</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($activity->qty_damage_prev) }}</td><td>Ton</td></tr>
                            <tr><td>Akumulasi</td><td class="colon">:</td><td class="line-cell r">{{ $fmtQty($sumQty($activity->qty_damage_current, $activity->qty_damage_prev)) }}</td><td>Ton</td></tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="grid">
                <thead>
                    <tr><th colspan="4">TIME SHEET</th></tr>
                    <tr>
                        <th style="width:9%">JAM</th>
                        <th style="width:41%">PENGIRIMAN</th>
                        <th style="width:9%">JAM</th>
                        <th style="width:41%">PEMUATAN</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($r = 0; $r < $maxRows; $r++)
                        <tr>
                            <td class="c">{{ isset($dLogs[$r]) ? $fmtTime($dLogs[$r]->time) : '' }}</td>
                            <td>{{ isset($dLogs[$r]) ? $dLogs[$r]->activity : '' }}</td>
                            <td class="c">{{ isset($lLogs[$r]) ? $fmtTime($lLogs[$r]->time) : '' }}</td>
                            <td>{{ isset($lLogs[$r]) ? $lLogs[$r]->activity : '' }}</td>
                        </tr>
                    @endfor
                </tbody>
            </table>
            <table class="grid small">
                <tr>
                    <td style="width:16%" class="b">Tally Gudang</td><td style="width:17%">{{ $activity->tally_warehouse }}</td>
                    <td style="width:16%" class="b">Driver</td><td style="width:17%">{{ $activity->driver_name }}</td>
                    <td style="width:16%" class="b">Truck No.</td><td style="width:18%">{{ $activity->truck_number }}</td>
                </tr>
                <tr>
                    <td class="b">Tally Kapal</td><td>{{ $activity->tally_ship }}</td>
                    <td class="b">Operator Kapal</td><td>{{ $activity->operator_ship }}</td>
                    <td class="b">Forklift Kapal</td><td>{{ $activity->forklift_ship }}</td>
                </tr>
                <tr>
                    <td class="b">Operator Gudang</td><td>{{ $activity->operator_warehouse }}</td>
                    <td class="b">Forklift Gudang</td><td colspan="3">{{ $activity->forklift_warehouse }}</td>
                </tr>
            </table>
        </div>
    @empty
        <table class="grid"><tr><td class="empty-note">Tidak ada data pemuatan pupuk kantong.</td></tr></table>
    @endforelse

    <div class="sec">II. Pemuatan Urea Curah</div>
    @forelse ($bulkActivities as $bulk)
        @php
            $bLogs = $bulk->logs->sortBy('datetime')->values();
        @endphp
        <div class="panel">
            <div class="panel-title">{{ $bulk->sequence ?? $loop->iteration }}. {{ $bulk->ship_name ?: 'Nama kapal belum diisi' }}</div>
            <table class="grid">
                <tr>
                    <td style="width:50%">
                        <table class="info">
                            <tr><td class="label">Nama Kapal</td><td class="colon">:</td><td class="line-cell">{{ $bulk->ship_name }}</td></tr>
                            <tr><td class="label">Agent</td><td class="colon">:</td><td class="line-cell">{{ $bulk->agent }}</td></tr>
                            <tr><td class="label">Dermaga</td><td class="colon">:</td><td class="line-cell">{{ $bulk->jetty }}</td></tr>
                            <tr><td class="label">Jenis Urea</td><td class="colon">:</td><td class="line-cell">{{ $bulk->commodity }}</td></tr>
                            <tr><td class="label">Kapasitas</td><td class="colon">:</td><td class="line-cell">{{ $fmtQty($bulk->capacity, 'MT') }}</td></tr>
                        </table>
                    </td>
                    <td style="width:50%">
                        <table class="info">
                            <tr><td class="label">Sandar</td><td class="colon">:</td><td class="line-cell">{{ $fmtDateTime($bulk->berthing_time) }}</td></tr>
                            <tr><td class="label">Mulai Muat</td><td class="colon">:</td><td class="line-cell">{{ $fmtDateTime($bulk->start_loading_time) }}</td></tr>
                            <tr><td class="label">Tujuan</td><td class="colon">:</td><td class="line-cell">{{ $bulk->destination }}</td></tr>
                            <tr><td class="label">Petugas PBM</td><td class="colon">:</td><td class="line-cell">{{ $bulk->stevedoring }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table class="grid">
                <thead><tr><th style="width:15%">TANGGAL</th><th style="width:10%">JAM</th><th>URAIAN KEGIATAN</th><th style="width:10%">COB</th></tr></thead>
                <tbody>
                    @forelse ($bLogs as $log)
                        <tr>
                            <td class="c">{{ $fmtShortDate($log->datetime) }}</td>
                            <td class="c">{{ $fmtTime($log->datetime) }}</td>
                            <td>{{ $log->activity }}</td>
                            <td class="c">{{ $log->cob }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-note">Tidak ada log kegiatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <table class="grid"><tr><td class="empty-note">Tidak ada data pemuatan urea curah.</td></tr></table>
    @endforelse

    <table class="pair">
        <tr>
            <td class="pair-left">
                <div class="sec">III. Bongkar Bahan Baku</div>
                @forelse ($materialActivities as $material)
                    <div class="panel">
                        <div class="panel-title">{{ $material->sequence ?? $loop->iteration }}. {{ $material->ship_name ?: 'Nama kapal belum diisi' }}</div>
                        <table class="grid compact">
                            <tr>
                                <td style="width:50%"><span class="b">Agent:</span> {{ $material->agent }}</td>
                                <td style="width:50%"><span class="b">Dermaga:</span> {{ $material->jetty }}</td>
                            </tr>
                            <tr>
                                <td><span class="b">Kapasitas:</span> {{ $fmtQty($material->capacity, 'MT') }}</td>
                                <td><span class="b">Jam Kerja:</span> {{ $material->working_hours }}</td>
                            </tr>
                        </table>
                        <table class="grid compact">
                            <thead><tr><th>JENIS</th><th style="width:18%">SEKARANG</th><th style="width:18%">LALU</th><th style="width:18%">TOTAL</th></tr></thead>
                            <tbody>
                                @forelse ($material->items as $item)
                                    <tr>
                                        <td>{{ $item->raw_material_type }}</td>
                                        <td class="r">{{ $fmtQty($item->qty_current) }}</td>
                                        <td class="r">{{ $fmtQty($item->qty_prev) }}</td>
                                        <td class="r">{{ $fmtQty($item->qty_total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="empty-note">Tidak ada rincian bahan baku.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <table class="grid compact small">
                            <tr>
                                <td class="b" style="width:20%">Tally Kapal</td><td>{{ $material->ship_tally_names }}</td>
                                <td class="b" style="width:20%">Tally Kirim</td><td>{{ $material->delivery_tally_names }}</td>
                            </tr>
                            <tr>
                                <td class="b">Operator FL</td><td>{{ $material->forklift_operator_names }}</td>
                                <td class="b">No Forklift</td><td>{{ $material->forklift_number }}</td>
                            </tr>
                            <tr>
                                <td class="b">Driver</td><td>{{ $material->driver_names }}</td>
                                <td class="b">No Truck</td><td>{{ $material->truck_number }}</td>
                            </tr>
                        </table>
                    </div>
                @empty
                    <table class="grid"><tr><td class="empty-note">Tidak ada data bongkar bahan baku.</td></tr></table>
                @endforelse
            </td>
            <td class="pair-right">
                <div class="sec">Bongkar / Muat Container</div>
                @forelse ($containerActivities as $container)
                    @php
                        $containerCapacityEmpty = $container->capacity_empty ?? $container->capacity;
                        $containerCapacityFull = $container->capacity_full ?? null;
                    @endphp
                    <div class="panel">
                        <div class="panel-title">{{ $container->sequence ?? $loop->iteration }}. {{ $container->ship_name ?: 'Nama kapal belum diisi' }}</div>
                        <table class="grid compact">
                            <tr>
                                <td style="width:50%"><span class="b">Agent:</span> {{ $container->agent }}</td>
                                <td style="width:50%"><span class="b">Dermaga:</span> {{ $container->jetty }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="capacity-line">
                                    <span class="b">Kapasitas:</span>
                                    Empty = <span class="cap-value">{{ $fmtQty($containerCapacityEmpty) }}</span> Teus
                                    / Full = <span class="cap-value">{{ $fmtQty($containerCapacityFull) }}</span> Teus
                                </td>
                            </tr>
                        </table>
                        <table class="grid compact">
                            <thead><tr><th style="width:18%">JAM</th><th style="width:16%">SEKARANG</th><th style="width:16%">LALU</th><th style="width:16%">TOTAL</th><th>KET</th></tr></thead>
                            <tbody>
                                @forelse ($container->items as $item)
                                    <tr>
                                        <td class="c">{{ $item->time_text ?: $fmtTime($item->time) }}</td>
                                        <td class="r">{{ $fmtQty($item->qty_current) }}</td>
                                        <td class="r">{{ $fmtQty($item->qty_prev) }}</td>
                                        <td class="r">{{ $fmtQty($item->qty_total) }}</td>
                                        <td>{{ $item->status }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="empty-note">Tidak ada rincian container.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <table class="grid compact small">
                            <tr>
                                <td class="b" style="width:18%">Tally Muat</td><td>{{ $container->ship_tally_names }}</td>
                                <td class="b" style="width:20%">Tally Gudang</td><td>{{ $container->gudang_tally_names }}</td>
                            </tr>
                            <tr>
                                <td class="b">Driver</td><td>{{ $container->driver_names }}</td>
                                <td class="b">No Truck</td><td>{{ $container->truck_number }}</td>
                            </tr>
                        </table>
                    </div>
                @empty
                    <table class="grid"><tr><td class="empty-note">Tidak ada data bongkar / muat container.</td></tr></table>
                @endforelse
            </td>
        </tr>
    </table>

    <div class="sec">IV. Tracking Pengiriman Pupuk Kantong</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width:4%">NO</th>
                <th>NAMA TRUCK</th>
                <th style="width:14%">DO/SO</th>
                <th style="width:12%">KAPASITAS</th>
                <th style="width:16%">JENIS MARKING</th>
                <th style="width:11%">SEKARANG</th>
                <th style="width:11%">LALU</th>
                <th style="width:11%">AKUMULASI</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($turbaDeliveries as $delivery)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $delivery->truck_name }}</td>
                    <td class="c">{{ $delivery->do_so_number }}</td>
                    <td class="r">{{ $fmtQty($delivery->capacity) }}</td>
                    <td>{{ $delivery->marking_type }}</td>
                    <td class="r">{{ $fmtQty($delivery->qty_current) }}</td>
                    <td class="r">{{ $fmtQty($delivery->qty_prev) }}</td>
                    <td class="r">{{ $fmtQty($delivery->qty_accumulated) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-note">Tidak ada tracking pengiriman.</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="grid small">
        <tr>
            <td class="b" style="width:18%">Tally Gudang Kirim</td><td>{{ optional($turba)->tally_gudang_names }}</td>
            <td class="b" style="width:18%">Tally Gudang Terima</td><td>{{ optional($turba)->tally_gudang_terima }}</td>
        </tr>
        <tr>
            <td class="b">Operator Forklift</td><td>{{ optional($turba)->forklift_operator_names }}</td>
            <td class="b">No Forklift</td><td>{{ optional($turba)->fl_no }}</td>
        </tr>
        <tr>
            <td class="b">Driver</td><td>{{ optional($turba)->driver_names }}</td>
            <td class="b">No Truck</td><td>{{ optional($turba)->trl_no }}</td>
        </tr>
        <tr>
            <td class="b">Jam Kerja</td><td colspan="3">{{ optional($turba)->working_hours }}</td>
        </tr>
    </table>

    <div class="sec">V. Keadaan Peralatan dan Kendaraan Operasional</div>
    <table class="grid compact">
        <thead>
            <tr><th colspan="10">TRAILLER / FORKLIFT DAN SARANA JEMPUTAN</th></tr>
            <tr>
                <th rowspan="2" style="width:4%">NO</th>
                <th rowspan="2">NAMA ALAT</th>
                <th rowspan="2" style="width:7%">ISI BBM</th>
                <th colspan="2" style="width:16%">KONDISI</th>
                <th rowspan="2" style="width:4%">NO</th>
                <th rowspan="2">NAMA ALAT</th>
                <th rowspan="2" style="width:7%">ISI BBM</th>
                <th colspan="2" style="width:16%">KONDISI</th>
            </tr>
            <tr>
                <th>TERIMA</th><th>SERAHKAN</th>
                <th>TERIMA</th><th>SERAHKAN</th>
            </tr>
        </thead>
        <tbody>
            @if ($vehicleMaxRows === 0)
                <tr><td colspan="10" class="empty-note">Tidak ada data kendaraan atau alat operasional.</td></tr>
            @else
                @for ($i = 0; $i < $vehicleMaxRows; $i++)
                    @php
                        $leftVehicle = $vehicleLeftRows->get($i);
                        $rightVehicle = $vehicleRightRows->get($i);
                    @endphp
                    <tr>
                        @if ($leftVehicle)
                            <td class="c">{{ $leftVehicle['no'] }}</td>
                            <td>{{ $leftVehicle['name'] }}</td>
                            <td class="c">{{ $leftVehicle['fuel'] }}</td>
                            <td class="c {{ $conditionClass($leftVehicle['received']) }}">{{ $leftVehicle['received'] }}</td>
                            <td class="c {{ $conditionClass($leftVehicle['handed']) }}">{{ $leftVehicle['handed'] }}</td>
                        @else
                            <td>&nbsp;</td><td></td><td></td><td></td><td></td>
                        @endif

                        @if ($rightVehicle)
                            <td class="c">{{ $rightVehicle['no'] }}</td>
                            <td>{{ $rightVehicle['name'] }}</td>
                            <td class="c">{{ $rightVehicle['fuel'] }}</td>
                            <td class="c {{ $conditionClass($rightVehicle['received']) }}">{{ $rightVehicle['received'] }}</td>
                            <td class="c {{ $conditionClass($rightVehicle['handed']) }}">{{ $rightVehicle['handed'] }}</td>
                        @else
                            <td>&nbsp;</td><td></td><td></td><td></td><td></td>
                        @endif
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>

    <table class="pair">
        <tr>
            <td class="pair-left">
                <div class="subsec" style="margin-top:6px">Daftar Inventaris</div>
                <table class="grid compact">
                    <thead>
                        <tr><th style="width:5%">NO</th><th>NAMA BARANG</th><th style="width:10%">JML</th><th style="width:15%">TERIMA</th><th style="width:15%">SERAHKAN</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($inventoryRows as $row)
                            <tr>
                                <td class="c">{{ $row['no'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="c">{{ $row['qty'] }}</td>
                                <td class="c {{ $conditionClass($row['received']) }}">{{ $row['received'] }}</td>
                                <td class="c {{ $conditionClass($row['handed']) }}">{{ $row['handed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-note">Tidak ada data inventaris.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td class="pair-right">
                <div class="subsec" style="margin-top:6px">Lingkungan Shelter</div>
                <table class="grid compact">
                    <thead>
                        <tr><th rowspan="2" style="width:6%">NO</th><th rowspan="2">ITEM</th><th colspan="2" style="width:34%">KONDISI</th></tr>
                        <tr><th>TERIMA</th><th>SERAHKAN</th></tr>
                    </thead>
                    <tbody>
                        @php
                            $shelterNo = 1;
                        @endphp
                        @foreach ($shelterMaster->groupBy('category') as $category => $items)
                            <tr class="category-row">
                                <td class="c">{{ $shelterNo++ }}</td>
                                <td colspan="3">{{ strtoupper((string) $category) }}</td>
                            </tr>
                            @foreach ($items as $item)
                                @php
                                    $log = $shelterByName->get(mb_strtolower((string) $item->name));
                                @endphp
                                <tr>
                                    <td></td>
                                    <td>{{ $item->name }}</td>
                                    <td class="c {{ $conditionClass($log->condition_received ?? '') }}">{{ $log->condition_received ?? '' }}</td>
                                    <td class="c {{ $conditionClass($log->condition_handed_over ?? '') }}">{{ $log->condition_handed_over ?? '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if ($extraShelterLogs->isNotEmpty())
                            <tr class="category-row">
                                <td class="c">{{ $shelterNo++ }}</td>
                                <td colspan="3">LAINNYA</td>
                            </tr>
                            @foreach ($extraShelterLogs as $log)
                                <tr>
                                    <td></td>
                                    <td>{{ $log->item_name }}</td>
                                    <td class="c {{ $conditionClass($log->condition_received) }}">{{ $log->condition_received }}</td>
                                    <td class="c {{ $conditionClass($log->condition_handed_over) }}">{{ $log->condition_handed_over }}</td>
                                </tr>
                            @endforeach
                        @endif
                        @if ($shelterNo === 1)
                            <tr><td colspan="4" class="empty-note">Tidak ada data lingkungan shelter.</td></tr>
                        @endif
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="sec">VI. Karyawan</div>
    @php
        $overtimeLineRows = max(5, $overtimeEmps->count());
        $reliefLineRows = max(5, $reliefEmps->count());
        $operationLineRows = max($overtimeLineRows, $reliefLineRows);
    @endphp
    <table class="pair">
        <tr>
            <td class="pair-left">
                <table class="grid compact">
                    <thead>
                        <tr><th colspan="5">KARYAWAN SHIFT YANG BERTUGAS</th></tr>
                        <tr><th style="width:5%">NO</th><th>NAMA</th><th style="width:14%">MASUK</th><th style="width:14%">PULANG</th><th style="width:20%">KET</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($shiftEmps as $employee)
                            <tr>
                                <td class="c">{{ $loop->iteration }}</td>
                                <td>{{ $employee->name }}</td>
                                <td class="c">{{ $fmtTime($employee->time_in) }}</td>
                                <td class="c">{{ $fmtTime($employee->time_out) }}</td>
                                <td class="c">{{ $employee->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-note">Tidak ada data karyawan shift.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td class="pair-right">
                <table class="grid compact">
                    <thead>
                        <tr><th colspan="6">KARYAWAN OPERASI</th></tr>
                        <tr><th style="width:7%">NO</th><th>LEMBUR</th><th style="width:16%">JAM KERJA</th><th style="width:7%">NO</th><th>RELIEF SIANG/MALAM</th><th style="width:16%">JAM KERJA</th></tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $operationLineRows; $i++)
                            @php
                                $overtimeEmployee = $overtimeEmps->get($i);
                                $reliefEmployee = $reliefEmps->get($i);
                            @endphp
                            <tr>
                                <td class="c">{{ $i < $overtimeLineRows ? $i + 1 : '' }}</td>
                                <td>{{ $overtimeEmployee?->name }}</td>
                                <td class="c">{{ $overtimeEmployee?->work_time }}</td>
                                <td class="c">{{ $i < $reliefLineRows ? $i + 1 : '' }}</td>
                                <td>{{ $reliefEmployee?->name }}</td>
                                <td class="c">{{ $reliefEmployee?->work_time }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <table class="grid" style="margin-top:6px">
        <thead><tr><th colspan="7">KARYAWAN OP.7</th></tr><tr><th style="width:4%">NO</th><th>NAMA</th><th style="width:14%">NO. FORKLIFT</th><th style="width:18%">AREA KERJA</th><th style="width:10%">MASUK</th><th style="width:10%">KELUAR</th><th>KETERANGAN</th></tr></thead>
        <tbody>
            @forelse ($op7Emps as $employee)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->no_forklift_ }}</td>
                    <td>{{ $employee->work_area }}</td>
                    <td class="c">{{ $fmtTime($employee->time_in) }}</td>
                    <td class="c">{{ $fmtTime($employee->time_out) }}</td>
                    <td class="c">{{ $employee->description }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-note">Tidak ada data karyawan OP.7.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="grid" style="margin-top:6px">
        <thead><tr><th colspan="7">DAFTAR PENGGANTI OPERATOR YANG TIDAK MASUK</th></tr><tr><th style="width:4%">NO</th><th>NAMA PENGGANTI</th><th style="width:14%">NO. FORKLIFT</th><th style="width:18%">AREA KERJA</th><th style="width:10%">MASUK</th><th style="width:10%">KELUAR</th><th>MENGGANTIKAN / KET</th></tr></thead>
        <tbody>
            @forelse ($replacementEmps as $employee)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->no_forklift_ }}</td>
                    <td>{{ $employee->work_area }}</td>
                    <td class="c">{{ $fmtTime($employee->time_in) }}</td>
                    <td class="c">{{ $fmtTime($employee->time_out) }}</td>
                    <td class="c">{{ $employee->description }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-note">Tidak ada data pengganti operator.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="grid" style="margin-top:6px">
        <thead><tr><th colspan="5">KEGIATAN LAIN</th></tr><tr><th style="width:4%">NO</th><th>KEGIATAN</th><th>PERSONIL</th><th style="width:12%">MASUK</th><th style="width:12%">PULANG</th></tr></thead>
        <tbody>
            @forelse ($otherActs as $activity)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $activity->description }}</td>
                    <td>{{ $activity->name ?: $activity->personil_count }}</td>
                    <td class="c">{{ $fmtTime($activity->time_in) }}</td>
                    <td class="c">{{ $fmtTime($activity->time_out) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-note">Tidak ada kegiatan lain.</td></tr>
            @endforelse
        </tbody>
    </table>

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
                Diterima / Melanjutkan,
                <div class="sigwrap">@if ($receiverSig)<img src="{{ $receiverSig }}" alt="TTD">@endif</div>
                <div class="nm">{{ $report->receiver?->name ?: '(.....................)' }}</div>
                <div class="ttl">Foreman Group {{ $report->received_by_group ?: '-' }}</div>
            </td>
            <td>
                <span class="small">Bontang, {{ $fmtDate($report->report_date) }}</span><br>
                Dilaksanakan / Menyerahkan,
                <div class="sigwrap">@if ($creatorSig)<img src="{{ $creatorSig }}" alt="TTD">@endif</div>
                <div class="nm">{{ $report->creator?->name ?: '(.....................)' }}</div>
                <div class="ttl">Foreman Group {{ $report->group_name ?: '-' }}</div>
            </td>
        </tr>
    </table>
</div>
