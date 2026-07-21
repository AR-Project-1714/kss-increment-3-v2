{{--
    Partial form laporan harian pemeliharaan (dipakai create & edit).
    Variabel dari wrapper: $isEdit, $formAction, $headerTitle, $headerDocumentLabel
    Master data dari controller: $units, $unitsTruck, $unitsHeavy, $employees, $workGroups
--}}
@php
    use App\Enums\MaintenanceStatus;

    $reportModel       = isset($report) && is_object($report) ? $report : null;
    $existingMain      = $reportModel ? $reportModel->workItems->where('work_type', 'utama')->values() : collect();
    $existingPriority  = $reportModel ? $reportModel->workItems->where('work_type', 'prioritas')->values() : collect();
    $latestCond        = collect($latestUnitConditions ?? [])
        ->filter(fn ($condition) => $condition && isset($condition->master_unit_id))
        ->mapWithKeys(fn ($condition) => [(string) $condition->master_unit_id => $condition]);
    $reportCond        = $reportModel
        ? $reportModel->unitConditions
            ->filter()
            ->mapWithKeys(fn ($condition) => [(string) $condition->master_unit_id => $condition])
        : collect();
    $existingCond      = $latestCond->replace($reportCond);
    $existingAtt       = $reportModel ? $reportModel->attendances : collect();

    $reportDateValue = old('report_date', $reportModel && $reportModel->report_date ? $reportModel->report_date->format('Y-m-d') : now()->toDateString());

    $unitIdByLabel = collect($units ?? [])->mapWithKeys(fn ($u) => [(string) $u['label'] => (string) $u['id']]);

    // Empat baris tetap Pekerjaan Utama (Group I-IV).
    $mainRows = [];
    foreach ($workGroups as $i => $grp) {
        $item = $existingMain[$i] ?? null;
        $mainUnitId = $item ? ($item->master_unit_id ?? '') : '';

        $mainRows[] = [
            'work_group'   => $item->work_group ?? $grp,
            'unit_id'      => $mainUnitId,
            'description'  => $item->description ?? '',
            'assignee'     => $item->assignee ?? '',
            'is_completed' => $item ? (int) $item->is_completed : 0,
            'notes'        => $item->notes ?? '',
        ];
    }

    // Pekerjaan Prioritas (dinamis); minimal satu baris kosong.
    $priorityRows = [];
    foreach ($existingPriority as $item) {
        $priorityUnitId = $item->master_unit_id ?: $unitIdByLabel->get((string) ($item->unit_label ?? ''), '');

        $priorityRows[] = [
            'unit_id'      => $priorityUnitId,
            'description'  => $item->description ?? '',
            'assignee'     => $item->assignee ?? '',
            'is_completed' => (int) $item->is_completed,
            'notes'        => $item->notes ?? '',
        ];
    }
    // Carry-over: pekerjaan prioritas yang belum selesai dari laporan terakhir
    // dimuat otomatis sebagai baris awal laporan baru (kesinambungan antar hari).
    $carryRows = collect($carryOverPriority ?? []);
    $carryOverInfo = null;
    if (! $reportModel && empty($priorityRows) && $carryRows->isNotEmpty()) {
        foreach ($carryRows as $item) {
            $carryNote = trim((string) ($item['notes'] ?? ''));
            $carryMark = $item['source_date'] ? 'Lanjutan dari '.$item['source_date'] : 'Lanjutan laporan sebelumnya';

            $priorityRows[] = [
                'unit_id'      => $item['unit_id'] ?: $unitIdByLabel->get((string) ($item['unit_label'] ?? ''), ''),
                'description'  => $item['description'] ?? '',
                'assignee'     => $item['assignee'] ?? '',
                'is_completed' => 0,
                'notes'        => $carryNote === '' ? $carryMark : $carryNote.' - '.$carryMark,
            ];
        }

        $carryOverInfo = [
            'count' => $carryRows->count(),
            'date'  => $carryRows->first()['source_date'] ?? null,
        ];
    }
    if (empty($priorityRows)) {
        $priorityRows[] = ['unit_id' => '', 'description' => '', 'assignee' => '', 'is_completed' => 0, 'notes' => ''];
    }

    // Daftar Hadir: pakai data laporan bila edit, jika tidak preload roster.
    $attendanceRows = [];
    if ($existingAtt->count()) {
        foreach ($existingAtt as $a) {
            $attendanceRows[] = [
                'id'        => $a->master_employee_id,
                'name'      => $a->employee_name,
                'position'  => $a->position,
                'time_in'   => $a->time_in ? substr($a->time_in, 0, 5) : '',
                'time_out'  => $a->time_out ? substr($a->time_out, 0, 5) : '',
                'notes'     => $a->notes,
            ];
        }
    } else {
        foreach ($employees as $e) {
            $attendanceRows[] = ['id' => $e['id'], 'name' => $e['name'], 'position' => $e['position'], 'time_in' => '', 'time_out' => '', 'notes' => ''];
        }
    }

    // Jam kerja laporan disimpan di kolom work_time_start/work_time_end. Untuk
    // laporan lama sebelum kolom ini ada, jatuhkan ke jam masuk karyawan pertama
    // pada Daftar Hadir sebagai perkiraan awal.
    $firstAttendanceTime = collect($attendanceRows)->first(function ($row) {
        return trim((string) ($row['time_in'] ?? '')) !== '' || trim((string) ($row['time_out'] ?? '')) !== '';
    });
    $initialWorkStart = old('work_time_start', $reportModel->work_time_start ?? ($firstAttendanceTime['time_in'] ?? ''));
    $initialWorkEnd = old('work_time_end', $reportModel->work_time_end ?? ($firstAttendanceTime['time_out'] ?? ''));

    $statusValue = $reportModel ? $reportModel->status->value : MaintenanceStatus::Draft->value;
@endphp

@push('styles')
<style>
    /* Lebar form konsisten & lega (mengikuti modul operasional) */
    /* <form> adalah anak langsung body (flex column, align-items:center); tanpa ini
       form menyusut mengikuti isi tiap tab sehingga lebar berubah-ubah. */
    #mainReportForm { width: 100%; align-self: stretch; display: block; }
    .content { max-width: 1800px; width: 100%; margin: 0 auto; }
    .content-header { width: 100%; max-width: 1800px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 20px; }
    .title-header { display: flex; flex-direction: column; gap: 2px; min-width: auto; }

    /* Animasi tombol navigasi (ikon meluncur / berputar saat hover) — sama seperti operasional */
    .btn-form.back { gap: 0; }
    .btn-form.back .icon { opacity: 0; max-width: 0; margin-right: 0; overflow: hidden; transition: max-width .3s ease, opacity .3s ease, margin-right .3s ease, transform .3s ease; transform: translateX(10px); display: inline-flex; align-items: center; justify-content: center; top: 0; }
    .btn-form.back:hover .icon { opacity: 1; max-width: 20px; margin-right: 10px; transform: translateX(0); }
    .btn-form.cancel .icon { position: relative; top: 0; display: inline-flex; justify-content: center; align-items: center; width: 18px; height: 18px; line-height: 0; transition: transform .3s ease; transform-origin: center; }
    .btn-form.cancel:hover .icon { transform: rotate(90deg); }
    .btn-form.next { gap: 0; }
    .btn-form.next .icon { opacity: 0; max-width: 0; margin-left: 0; overflow: hidden; transition: max-width .3s ease, opacity .3s ease, margin-left .3s ease, transform .3s ease; transform: translateX(-10px); display: inline-flex; align-items: center; justify-content: center; top: 0; }
    .btn-form.next:hover .icon { opacity: 1; max-width: 20px; margin-left: 10px; transform: translateX(0); }
    .btn-form.finish { gap: 0; }
    .btn-form.finish .icon { opacity: 0; max-width: 0; margin-left: 0; overflow: hidden; transition: max-width .3s ease, opacity .3s ease, margin-left .3s ease, transform .3s ease; transform: translateX(-10px); display: inline-flex; align-items: center; justify-content: center; top: 0; }
    .btn-form.finish:hover .icon { opacity: 1; max-width: 20px; margin-left: 10px; transform: translateX(0); }
    /* Ikon dalam tombol nav: glyph flaticon agak tinggi -> turunkan sedikit agar center vertikal. */
    .btn-form .icon i { position: relative; top: 2px; line-height: 1; }

    .radio-custom.neutral input[type="radio"]:checked + label { border-color: var(--dark-secondary); background-color: var(--dark-secondary-10); color: var(--dark-secondary); }
    /* Tag "Group I-IV" pada kolom Keterangan Pekerjaan Utama */
    .group-tag { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:9px 12px; border-radius:8px; background:var(--blue-main-5); border:1px solid var(--blue-main-10); color:var(--blue-main); font-size:12px; font-weight:600; }
    .group-tag i { position:relative; top:1px; font-size:11px; }
    /* ===== Kartu Pekerjaan (Utama & Prioritas) ===== */
    .work-card-list { display:flex; flex-direction:column; gap:16px; width:100%; }
    .work-card { border:1px solid var(--divider); border-radius:12px; background:var(--white); width:100%; box-shadow:0 1px 3px var(--blue-main-10); }
    .work-card-head { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 18px; background:var(--blue-main-5); border-bottom:1px solid var(--divider); border-radius:12px 12px 0 0; flex-wrap:wrap; }
    .priority-card .work-card-head { background:var(--orange-main-5); }
    .wc-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; background:var(--blue-main-10); color:var(--blue-main); font-size:13px; font-weight:700; }
    .wc-badge.orange { background:var(--orange-main-10); color:var(--orange-main); }
    .wc-badge i { position:relative; top:1px; font-size:12px; }
    .wc-status { width:auto !important; flex:0 0 auto; }
    .wc-status .radio-custom label { min-height:40px; padding:8px 14px; font-size:11px; white-space:nowrap; }
    .wc-status .radio-custom.good input[type="radio"]:checked + label {
        border-color: var(--success);
        background-color: var(--success);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 4px 12px var(--success-40);
    }
    .wc-status .radio-custom.good input[type="radio"]:checked + label i { color: #fff; }
    .work-card-body { padding:18px; display:flex; flex-direction:column; gap:16px; }
    .work-card-grid { display:flex; gap:16px; flex-wrap:wrap; align-items:flex-start; }
    .work-card-grid .box-input-1 { min-width:220px; }
    .wc-remove { width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; border:none; border-radius:8px; background:var(--red-main-10); color:var(--red-main); cursor:pointer; transition:.2s ease-out; flex:0 0 auto; }
    .wc-remove:hover { background:var(--red-main-25); transform:translateY(-1px); }
    .wc-remove i { position:static; line-height:1; }
    .work-card .tbl-custom-select-trigger { min-height:42px; }
    .work-card textarea.custom-input { resize:vertical; min-height:64px; padding:10px 15px; line-height:1.45; cursor:text; }
    .btn-tambah-card { display:flex; padding:14px; justify-content:center; align-items:center; gap:8px; align-self:stretch; width:100%; border-radius:10px; background:transparent; color:var(--blue-main); font-size:13px; font-weight:600; cursor:pointer; transition:.2s; border:1.5px dashed var(--blue-main-40); }
    .btn-tambah-card:hover { background:var(--blue-main-5); }
    .btn-tambah-card i { font-size:14px; position:relative; top:1px; }
    /* Header kolom Status & Kondisi rata tengah */
    .table-input .head .table-column.radio-col { justify-content: center; }
    .table-input .head .table-column.radio-col span { width: 100%; text-align: center; }
    /* Beri ruang antar kolom agar Status & Keterangan tidak berdempet */
    .table-input .table-column { padding-left: 12px; padding-right: 12px; }
    .table-column.radio-col { min-width: 195px; }
    .table-column.radio-col .radio-group-custom { gap: 10px; }
    /* Beri jarak ekstra antara kolom Status dan Keterangan di Pekerjaan Prioritas */
    #priority-table .table-column.radio-col { padding-right: 18px; }
    #priority-table .table-column.medium { min-width: 165px; }
    .form-meta-note { font-size:11px; color:var(--muted); display:flex; align-items:center; gap:6px; }
    .form-meta-note { font-size:11px; color:var(--muted); display:flex; align-items:center; gap:6px; }
    .form-meta-note i { position:relative; top:1px; }
    .info-work-time { min-width:160px; }
    .info-work-time__range { flex-wrap:nowrap; }
    .info-work-time__range .input-wrapper { min-width:0; flex:1 1 0; }
    .info-work-time__arrow { flex:0 0 auto; }
    @media (max-width:920px) {
        .info-work-time { flex-basis:100%; }
        .info-work-time__range .input-wrapper { min-width:145px; }
    }
    @media (max-width:480px) {
        .info-work-time__range { gap:8px!important; }
        .info-work-time__range .input-wrapper { min-width:118px; }
    }
    .pengesahan-card { border:1px solid var(--divider); border-radius:12px; padding:16px; background:var(--blue-main-2); display:flex; flex-direction:column; gap:6px; min-width:240px; flex:1 0 0; }
    .pengesahan-card .role-label { font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
    .pengesahan-card .person { font-size:14px; font-weight:600; color:var(--dark-main); }
    .pengesahan-badge { display:inline-flex; align-items:center; gap:6px; align-self:flex-start; padding:3px 8px; border-radius:8px; font-size:10px; font-weight:600; background:var(--blue-main-10); color:var(--blue-main); }
    .pengesahan-badge i { position:relative; top:1px; }
</style>
@endpush

@section('content')
<form action="{{ $formAction }}" method="POST" id="mainReportForm">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <input type="hidden" name="status" id="reportStatus" value="{{ $statusValue }}">

    <div class="content d-flex flex-column align-items-start align-self-stretch gap-30 p-content">

        {{-- HEADER: judul + ID + Simpan Draft --}}
        <div class="content-header" style="border-radius:16px">
            <div class="title-header">
                <span class="text-header fw-600 fsize-20">{{ $headerTitle }}</span>
                <span class="note fw-300 fsize-12 text-secondary">ID Dokumen: {{ $headerDocumentLabel }}</span>
            </div>
            <button type="button" class="btn-draft-save" id="btnSaveDraft">
                <span class="icon-new"><i class="fi fi-rr-disk"></i></span>
                <span class="btn-text fsize-14 fw-500">Simpan Sebagai Draft</span>
            </button>
        </div>

        {{-- TAB SEKSI --}}
        <div class="tab-form">
            <div class="list-form-tab active"><span class="icon-tab"><i class="fi fi-rr-document"></i></span><span>Info Umum</span></div>
            <div class="list-form-tab"><span class="icon-tab"><i class="fi fi-rr-settings"></i></span><span>Pekerjaan Utama</span></div>
            <div class="list-form-tab"><span class="icon-tab"><i class="fi fi-rr-flame"></i></span><span>Pekerjaan Prioritas</span></div>
            <div class="list-form-tab"><span class="icon-tab"><i class="fi fi-rr-truck-side"></i></span><span>Kondisi Unit</span></div>
            <div class="list-form-tab"><span class="icon-tab"><i class="fi fi-rr-employee-man"></i></span><span>Daftar Hadir</span></div>
        </div>

        {{-- ===================== SEKSI 1: INFO UMUM ===================== --}}
        <div class="box-form form-step d-flex flex-column align-items-start align-self-stretch w-100" id="step-info-umum">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-document"></i></span>
                    <span class="fw-600">Info Umum</span>
                </div>
                <div class="counter-form">Form 1 dari 5</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="d-flex align-items-start align-self-stretch flex-wrap gap-20 w-100">
                    <div class="box-input-1">
                        <div class="box-label-1"><label for="report_date">Hari / Tanggal</label><span class="text-red">*</span></div>
                        <div class="input-wrapper">
                            <input type="date" id="report_date" name="report_date" value="{{ $reportDateValue }}" class="custom-input" onclick="if(this.showPicker)this.showPicker()" required>
                            <i class="fi fi-rr-calendar input-icon"></i>
                        </div>
                    </div>
                    <div class="box-input-1">
                        <div class="box-label-1"><label>Hari</label></div>
                        <div class="input-wrapper">
                            <input type="text" id="day_name_display" class="custom-input" placeholder="Otomatis dari tanggal" readonly style="cursor:default;background:var(--blue-main-2)">
                            <i class="fi fi-rr-time-past input-icon"></i>
                        </div>
                    </div>
                    <div class="box-input-1 info-work-time">
                        <div class="box-label-1"><label for="jam_masuk">Rentang Jam Kerja</label><span class="text-red">*</span></div>
                        <div class="info-work-time__range d-flex align-items-center gap-10 w-100">
                            <div class="input-wrapper">
                                <i class="fi fi-rr-clock input-icon" style="left:15px;right:auto;color:var(--blue-main)"></i>
                                <input type="text" id="jam_masuk" name="work_time_start" value="{{ $initialWorkStart }}" class="custom-input time-picker-input" placeholder="00:00" maxlength="5" style="text-align:center;padding-left:38px" required>
                            </div>
                            <span class="info-work-time__arrow text-secondary fw-600 fsize-18" style="line-height:1">&rarr;</span>
                            <div class="input-wrapper">
                                <i class="fi fi-rr-clock input-icon" style="left:15px;right:auto;color:var(--red-main)"></i>
                                <input type="text" id="jam_pulang" name="work_time_end" value="{{ $initialWorkEnd }}" class="custom-input time-picker-input" placeholder="00:00" maxlength="5" style="text-align:center;padding-left:38px">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-meta-note"><i class="fi fi-rr-info"></i><span>Jam kerja otomatis: Senin-Kamis 07.00-16.00, Jumat 07.00-17.00. Sabtu/Minggu dikosongkan dan dapat diisi manual oleh petugas. Jam masuk wajib diisi saat mengirim laporan, dan menjadi pembeda jika ada lebih dari satu laporan pada tanggal yang sama.</span></div>
            </div>
            <div class="content-form box-button" style="padding-top:0">
                <a href="{{ route('pemeliharaan.index') }}" class="btn-form cancel"><span class="icon"><i class="fi fi-br-cross-small"></i></span><span>Batalkan</span></a>
                <button type="button" class="btn-form next btn-next-step"><span>Lanjut</span><span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span></button>
            </div>
        </div>

        {{-- ===================== SEKSI 2: PEKERJAAN UTAMA ===================== --}}
        <div class="box-form form-step d-none flex-column align-items-start align-self-stretch w-100" id="step-utama">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-settings"></i></span>
                    <span class="fw-600">Pekerjaan Utama</span>
                </div>
                <div class="counter-form">Form 2 dari 5</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="form-meta-note"><i class="fi fi-rr-info"></i><span>Empat kelompok kerja (Group I–IV). Kartu boleh dibiarkan kosong bila grup tidak ada pekerjaan.</span></div>
                <div class="work-card-list">
                    @foreach ($mainRows as $i => $row)
                        <div class="work-card">
                            <div class="work-card-head">
                                <span class="wc-badge"><i class="fi fi-sr-bookmark"></i> Group {{ $row['work_group'] }}</span>
                                <div class="radio-group-custom wc-status">
                                    <div class="radio-custom neutral">
                                        <input type="radio" name="main_items[{{ $i }}][is_completed]" id="mu_undone_{{ $i }}" value="0" @checked($row['is_completed'] !== 1)>
                                        <label for="mu_undone_{{ $i }}"><i class="fi fi-rr-clock"></i> Belum</label>
                                    </div>
                                    <div class="radio-custom good">
                                        <input type="radio" name="main_items[{{ $i }}][is_completed]" id="mu_done_{{ $i }}" value="1" @checked($row['is_completed'] === 1)>
                                        <label for="mu_done_{{ $i }}"><i class="fi fi-rr-check"></i> Selesai</label>
                                    </div>
                                </div>
                            </div>
                            <div class="work-card-body">
                                <input type="hidden" name="main_items[{{ $i }}][work_group]" value="{{ $row['work_group'] }}">
                                <div class="work-card-grid">
                                    <div class="box-input-1">
                                        <div class="box-label-1"><label>Jenis Unit</label></div>
                                        <div class="tbl-select-wrapper" data-search="true">
                                            <select name="main_items[{{ $i }}][unit_id]" class="tbl-native-select">
                                                <option value="">Pilih Unit</option>
                                                @foreach ($unitsTruck as $u)<option value="{{ $u['id'] }}" @selected((string)$row['unit_id'] === (string)$u['id'])>{{ $u['label'] }}</option>@endforeach
                                                @foreach ($unitsHeavy as $u)<option value="{{ $u['id'] }}" @selected((string)$row['unit_id'] === (string)$u['id'])>{{ $u['label'] }}</option>@endforeach
                                            </select>
                                            <span class="sel-caret"><i class="fi fi-rr-angle-small-down"></i></span>
                                        </div>
                                    </div>
                                    <div class="box-input-1">
                                        <div class="box-label-1"><label>Petugas</label></div>
                                        <div class="input-wrapper">
                                            <input type="text" name="main_items[{{ $i }}][assignee]" value="{{ $row['assignee'] }}" list="maintenance-employee-datalist" class="custom-input" placeholder="Nama petugas">
                                            <i class="fi fi-rr-user input-icon"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="box-input-1">
                                    <div class="box-label-1"><label>Pekerjaan Utama</label></div>
                                    <div class="input-wrapper">
                                        <textarea name="main_items[{{ $i }}][description]" class="custom-input" rows="2" placeholder="Uraian pekerjaan utama...">{{ $row['description'] }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="content-form box-button" style="padding-top:0">
                <button type="button" class="btn-form back btn-back-step"><span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span><span>Kembali</span></button>
                <button type="button" class="btn-form next btn-next-step"><span>Lanjut</span><span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span></button>
            </div>
        </div>

        {{-- ===================== SEKSI 3: PEKERJAAN PRIORITAS ===================== --}}
        <div class="box-form form-step d-none flex-column align-items-start align-self-stretch w-100" id="step-prioritas">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-flame"></i></span>
                    <span class="fw-600">Pekerjaan Prioritas</span>
                </div>
                <div class="counter-form">Form 3 dari 5</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="form-meta-note"><i class="fi fi-rr-info"></i><span>Tambah kartu untuk tiap pekerjaan prioritas. Nama unit dipilih dari master data unit.</span></div>
                @if (! empty($carryOverInfo))
                    <div class="form-meta-note carry-over-note" style="border-color: rgba(245, 158, 11, 0.45); background: rgba(245, 158, 11, 0.10); color: #92400E;">
                        <i class="fi fi-rr-time-forward"></i>
                        <span>
                            {{ $carryOverInfo['count'] }} pekerjaan yang belum selesai
                            {{ $carryOverInfo['date'] ? 'dari laporan '.$carryOverInfo['date'] : 'dari laporan sebelumnya' }}
                            dimuat otomatis sebagai lanjutan. Hapus kartunya bila pekerjaan tersebut sudah tidak relevan.
                        </span>
                    </div>
                @endif
                <div class="work-card-list" id="priority-list">
                    @foreach ($priorityRows as $i => $row)
                        <div class="work-card priority-card">
                            <div class="work-card-head">
                                <span class="wc-badge orange"><i class="fi fi-sr-flame"></i> Prioritas <span class="pr-no">{{ $i + 1 }}</span></span>
                                    <div class="d-flex align-items-center gap-10">
                                    <div class="radio-group-custom wc-status">
                                        <div class="radio-custom neutral">
                                            <input type="radio" name="priority_items[{{ $i }}][is_completed]" id="pr_undone_{{ $i }}" value="0" @checked($row['is_completed'] !== 1)>
                                            <label for="pr_undone_{{ $i }}"><i class="fi fi-rr-clock"></i> Belum</label>
                                        </div>
                                        <div class="radio-custom good">
                                            <input type="radio" name="priority_items[{{ $i }}][is_completed]" id="pr_done_{{ $i }}" value="1" @checked($row['is_completed'] === 1)>
                                            <label for="pr_done_{{ $i }}"><i class="fi fi-rr-check"></i> Selesai</label>
                                        </div>
                                    </div>
                                    <button type="button" class="wc-remove" data-remove-card><i class="fi fi-rr-trash"></i></button>
                                </div>
                            </div>
                            <div class="work-card-body">
                                <div class="work-card-grid">
                                    <div class="box-input-1">
                                        <div class="box-label-1"><label>Unit</label></div>
                                        <div class="tbl-select-wrapper" data-search="true">
                                            <select name="priority_items[{{ $i }}][unit_id]" class="tbl-native-select">
                                                <option value="">Pilih Unit</option>
                                                @foreach ($unitsTruck as $u)<option value="{{ $u['id'] }}" @selected((string)$row['unit_id'] === (string)$u['id'])>{{ $u['label'] }}</option>@endforeach
                                                @foreach ($unitsHeavy as $u)<option value="{{ $u['id'] }}" @selected((string)$row['unit_id'] === (string)$u['id'])>{{ $u['label'] }}</option>@endforeach
                                            </select>
                                            <span class="sel-caret"><i class="fi fi-rr-angle-small-down"></i></span>
                                        </div>
                                    </div>
                                    <div class="box-input-1">
                                        <div class="box-label-1"><label>Petugas</label></div>
                                        <div class="input-wrapper">
                                            <input type="text" name="priority_items[{{ $i }}][assignee]" value="{{ $row['assignee'] }}" list="maintenance-employee-datalist" class="custom-input" placeholder="Nama petugas">
                                            <i class="fi fi-rr-user input-icon"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="box-input-1">
                                    <div class="box-label-1"><label>Pekerjaan Prioritas</label></div>
                                    <div class="input-wrapper">
                                        <textarea name="priority_items[{{ $i }}][description]" class="custom-input" rows="2" placeholder="Uraian pekerjaan prioritas...">{{ $row['description'] }}</textarea>
                                    </div>
                                </div>
                                <div class="box-input-1">
                                    <div class="box-label-1"><label>Keterangan</label></div>
                                    <div class="input-wrapper">
                                        <input type="text" name="priority_items[{{ $i }}][notes]" value="{{ $row['notes'] }}" class="custom-input" placeholder="Keterangan (opsional)">
                                        <i class="fi fi-rr-comment-alt input-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn-tambah-card" id="add-priority-card"><i class="fi fi-rr-plus-small"></i> Tambah Pekerjaan Prioritas</button>
            </div>
            <div class="content-form box-button" style="padding-top:0">
                <button type="button" class="btn-form back btn-back-step"><span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span><span>Kembali</span></button>
                <button type="button" class="btn-form next btn-next-step"><span>Lanjut</span><span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span></button>
            </div>
        </div>

        {{-- ===================== SEKSI 4: KONDISI UNIT ===================== --}}
        <div class="box-form form-step d-none flex-column align-items-start align-self-stretch w-100" id="step-kondisi">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-truck-side"></i></span>
                    <span class="fw-600">Kondisi Unit Saat Ini</span>
                </div>
                <div class="counter-form">Form 4 dari 5</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="inspection-header">
                    <div class="tab-group">
                        <div class="tab-sections active" data-cond-target="cond-truck">
                            <span class="icon"><i class="fi fi-rr-truck-side"></i></span>
                            <span>Trailer / Tronton / DT</span>
                        </div>
                        <div class="tab-sections" data-cond-target="cond-heavy">
                            <span class="icon"><i class="fi fi-rr-forklift"></i></span>
                            <span>Forklift / Excavator / WL</span>
                        </div>
                    </div>
                    <button type="button" class="set-all-good" id="set-all-ready"><i class="fi fi-rr-check-double"></i> Set Semua Ready</button>
                </div>

                {{-- Kelompok A: truck --}}
                <div id="cond-truck" class="cond-pane w-100 d-flex flex-column gap-10">
                    <div class="condition-counter">
                        <span class="count-chip ready"><i class="fi fi-rr-check-circle"></i> Ready: <span data-count="ready" data-group="truck">0</span></span>
                        <span class="count-chip rusak"><i class="fi fi-rr-cross-circle"></i> Rusak: <span data-count="rusak" data-group="truck">0</span></span>
                    </div>
                    <div class="table-wrapper">
                        <div class="table-input">
                            <div class="head">
                                <div class="table-column no"><span>No</span></div>
                                <div class="table-column main"><span>Unit</span></div>
                                <div class="table-column radio-col"><span>Kondisi</span></div>
                            </div>
                            @foreach ($unitsTruck as $i => $u)
                                @php($cond = $existingCond[$u['id']]->condition ?? 'ready')
                                @php($cnote = $existingCond[$u['id']]->notes ?? '')
                                <div class="body">
                                    <div class="table-column no"><span>{{ $i + 1 }}</span></div>
                                    <div class="table-column main"><div class="table-input-wrapper"><i class="fi fi-sr-truck-container"></i><input type="text" name="conditions[{{ $u['id'] }}][unit_label]" value="{{ $u['label'] }}"></div></div>
                                    <div class="table-column radio-col">
                                        <div class="radio-group-custom">
                                            <div class="radio-custom good">
                                                <input type="radio" name="conditions[{{ $u['id'] }}][condition]" id="ct_ready_{{ $u['id'] }}" value="ready" data-cond-group="truck" @checked($cond === 'ready')>
                                                <label for="ct_ready_{{ $u['id'] }}"><i class="fi fi-rr-check"></i> Ready</label>
                                            </div>
                                            <div class="radio-custom bad">
                                                <input type="radio" name="conditions[{{ $u['id'] }}][condition]" id="ct_rusak_{{ $u['id'] }}" value="rusak" data-cond-group="truck" @checked($cond === 'rusak')>
                                                <label for="ct_rusak_{{ $u['id'] }}"><i class="fi fi-rr-cross-small"></i> Rusak</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Kelompok B: heavy --}}
                <div id="cond-heavy" class="cond-pane w-100 d-none flex-column gap-10">
                    <div class="condition-counter">
                        <span class="count-chip ready"><i class="fi fi-rr-check-circle"></i> Ready: <span data-count="ready" data-group="heavy">0</span></span>
                        <span class="count-chip rusak"><i class="fi fi-rr-cross-circle"></i> Rusak: <span data-count="rusak" data-group="heavy">0</span></span>
                    </div>
                    <div class="table-wrapper heavy">
                        <div class="table-input">
                            <div class="head">
                                <div class="table-column no"><span>No</span></div>
                                <div class="table-column main"><span>Unit</span></div>
                                <div class="table-column radio-col"><span>Kondisi</span></div>
                            </div>
                            @foreach ($unitsHeavy as $i => $u)
                                @php($cond = $existingCond[$u['id']]->condition ?? 'ready')
                                @php($cnote = $existingCond[$u['id']]->notes ?? '')
                                <div class="body">
                                    <div class="table-column no"><span>{{ $i + 1 }}</span></div>
                                    <div class="table-column main"><div class="table-input-wrapper"><i class="fi fi-sr-forklift"></i><input type="text" name="conditions[{{ $u['id'] }}][unit_label]" value="{{ $u['label'] }}"></div></div>
                                    <div class="table-column radio-col">
                                        <div class="radio-group-custom">
                                            <div class="radio-custom good">
                                                <input type="radio" name="conditions[{{ $u['id'] }}][condition]" id="ch_ready_{{ $u['id'] }}" value="ready" data-cond-group="heavy" @checked($cond === 'ready')>
                                                <label for="ch_ready_{{ $u['id'] }}"><i class="fi fi-rr-check"></i> Ready</label>
                                            </div>
                                            <div class="radio-custom bad">
                                                <input type="radio" name="conditions[{{ $u['id'] }}][condition]" id="ch_rusak_{{ $u['id'] }}" value="rusak" data-cond-group="heavy" @checked($cond === 'rusak')>
                                                <label for="ch_rusak_{{ $u['id'] }}"><i class="fi fi-rr-cross-small"></i> Rusak</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-form box-button" style="padding-top:0">
                <button type="button" class="btn-form back btn-back-step"><span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span><span>Kembali</span></button>
                <button type="button" class="btn-form next btn-next-step"><span>Lanjut</span><span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span></button>
            </div>
        </div>

        {{-- ===================== SEKSI 5: DAFTAR HADIR ===================== --}}
        <div class="box-form form-step d-none flex-column align-items-start align-self-stretch w-100" id="step-hadir">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-employee-man"></i></span>
                    <span class="fw-600">Daftar Hadir Karyawan</span>
                </div>
                <div class="counter-form">Form 5 dari 5</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="form-meta-note"><i class="fi fi-rr-info"></i><span>Roster di-preload otomatis (Waktu Kerja Non Shift). Tambah baris untuk personel tambahan.</span></div>
                <div class="table-wrapper">
                    <div class="table-input" id="attendance-table">
                        <div class="head">
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Nama Karyawan</span></div>
                            <div class="table-column medium"><span>Jabatan</span></div>
                            <div class="table-column absent"><span>Masuk</span></div>
                            <div class="table-column absent"><span>Pulang</span></div>
                            <div class="table-column medium"><span>Keterangan</span></div>
                            <div class="table-column delete"><span>Hapus</span></div>
                        </div>
                        @foreach ($attendanceRows as $i => $row)
                            <div class="body attendance-row">
                                <div class="table-column no"><span class="row-no">{{ $i + 1 }}</span></div>
                                <div class="table-column main">
                                    <input type="hidden" name="attendances[{{ $i }}][master_employee_id]" value="{{ $row['id'] }}">
                                    <div class="table-input-wrapper"><i class="fi fi-sr-user"></i><input type="text" name="attendances[{{ $i }}][employee_name]" value="{{ $row['name'] }}" list="maintenance-employee-datalist" placeholder="Nama karyawan"></div>
                                </div>
                                <div class="table-column medium">
                                    <div class="table-input-wrapper"><i class="fi fi-sr-id-badge"></i><input type="text" name="attendances[{{ $i }}][position]" value="{{ $row['position'] }}" placeholder="Jabatan"></div>
                                </div>
                                <div class="table-column absent">
                                    <div class="table-input-wrapper"><i class="fi fi-rr-time-quarter-past"></i><input type="text" name="attendances[{{ $i }}][time_in]" value="{{ $row['time_in'] }}" class="time-picker-input" placeholder="00:00" style="text-align:center"></div>
                                </div>
                                <div class="table-column absent">
                                    <div class="table-input-wrapper"><i class="fi fi-rr-time-check text-red"></i><input type="text" name="attendances[{{ $i }}][time_out]" value="{{ $row['time_out'] }}" class="time-picker-input" placeholder="00:00" style="text-align:center"></div>
                                </div>
                                <div class="table-column medium">
                                    <div class="table-input-wrapper"><input type="text" name="attendances[{{ $i }}][notes]" value="{{ $row['notes'] }}" list="pemeliharaan-keterangan-options" class="att-notes" placeholder="Keterangan" autocomplete="off"></div>
                                </div>
                                <div class="table-column delete"><button type="button" class="btn-trash-row" data-remove-row><i class="fi fi-rr-trash"></i></button></div>
                            </div>
                        @endforeach
                        <button type="button" class="btn-tambah-baris" id="add-attendance-row"><i class="fi fi-rr-plus-small"></i> Tambah Baris</button>
                    </div>
                </div>
            </div>
            <div class="content-form box-button" style="padding-top:0">
                <button type="button" class="btn-form back btn-back-step"><span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span><span>Kembali</span></button>
                <button type="button" class="btn-form finish" id="btnOpenConfirm"><span>Selesai</span><span class="icon"><i class="fi fi-rr-check"></i></span></button>
            </div>
        </div>
    </div>
</form>

<datalist id="maintenance-employee-datalist">
    @foreach ($employees as $e)<option value="{{ $e['name'] }}"></option>@endforeach
</datalist>

{{-- Saran cepat keterangan absensi; tetap bisa diketik manual apa saja. --}}
<datalist id="pemeliharaan-keterangan-options">
    <option value="Sakit"></option>
    <option value="Cuti"></option>
    <option value="Tidak Masuk"></option>
</datalist>
@endsection

@push('modals')
<div class="modal-overlay" id="finishModal">
    <div class="pop-up signed d-flex flex-column gap-20">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-600 fsize-16 text-main">Konfirmasi Penyelesaian</span>
            <button type="button" class="button-close" data-close-modal><i class="fi fi-br-cross"></i></button>
        </div>
        <div class="pop-up-content d-flex flex-column gap-15">
            <div class="pop-up detail d-flex align-items-center">
                <span class="icon-document"><i class="fi fi-sr-assept-document"></i></span>
                <div class="d-flex flex-column">
                    <span class="fw-600 fsize-14 text-main">Kirim Laporan Pemeliharaan Sekarang?</span>
                    <span class="fsize-10 text-secondary">ID: {{ $headerDocumentLabel }}</span>
                </div>
            </div>
            <p class="fsize-12 text-muted m-0">
                Laporan ini akan dikirim langsung ke <span class="fw-600">Manajer</span> untuk ditinjau dan ditandatangani. Setelah dikirim, status laporan menjadi <span class="fw-600">Diserahkan</span>.
            </p>
        </div>
        <div class="pop-up footer d-flex justify-content-end gap-10">
            <button type="button" class="btn cancel" data-close-modal>Periksa Lagi</button>
            <button type="button" class="btn confirm" id="btnFinalSubmit"><i class="fi fi-rr-paper-plane"></i> Ya, Kirim Laporan</button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('mainReportForm');
    const statusInput = document.getElementById('reportStatus');

    // ---- Hari otomatis dari tanggal ----
    const dateInput = document.getElementById('report_date');
    const dayDisplay = document.getElementById('day_name_display');
    const jamMasuk = document.getElementById('jam_masuk');
    const jamPulang = document.getElementById('jam_pulang');
    const DAYS = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const hasInitialWorkTime = Boolean((jamMasuk?.value || '').trim() || (jamPulang?.value || '').trim());

    function selectedDate() {
        if (!dateInput?.value) return null;
        const d = new Date(dateInput.value + 'T00:00:00');
        return isNaN(d) ? null : d;
    }

    function updateDay() {
        if (!dateInput || !dayDisplay) return;
        const d = selectedDate();
        dayDisplay.value = d ? DAYS[d.getDay()] : '';
    }

    // ---- Kondisi Unit: sub-tab + auto count ----
    document.querySelectorAll('[data-cond-target]').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('[data-cond-target]').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.cond-pane').forEach(p => { p.classList.add('d-none'); p.classList.remove('d-flex'); });
            tab.classList.add('active');
            const pane = document.getElementById(tab.dataset.condTarget);
            pane.classList.remove('d-none'); pane.classList.add('d-flex');
        });
    });
    function recountConditions() {
        ['truck', 'heavy'].forEach(group => {
            let ready = 0, rusak = 0;
            document.querySelectorAll(`input[type="radio"][data-cond-group="${group}"]:checked`).forEach(r => {
                if (r.value === 'rusak') rusak++; else ready++;
            });
            const r = document.querySelector(`[data-count="ready"][data-group="${group}"]`);
            const k = document.querySelector(`[data-count="rusak"][data-group="${group}"]`);
            if (r) r.textContent = ready;
            if (k) k.textContent = rusak;
        });
    }
    document.querySelectorAll('input[data-cond-group]').forEach(r => r.addEventListener('change', recountConditions));
    recountConditions();

    document.getElementById('set-all-ready')?.addEventListener('click', () => {
        // Set Semua Ready hanya untuk pane yang sedang aktif.
        const activePane = document.querySelector('.cond-pane.d-flex') || document.getElementById('cond-truck');
        activePane?.querySelectorAll('input[type="radio"][value="ready"]').forEach(r => { r.checked = true; });
        recountConditions();
    });

    // ---- Tambah / hapus baris (Prioritas & Daftar Hadir) ----
    let priorityIndex = {{ count($priorityRows) }};
    let attendanceIndex = {{ count($attendanceRows) }};

    function renumber(tableId, rowClass) {
        document.querySelectorAll(`#${tableId} .${rowClass}`).forEach((row, i) => {
            const no = row.querySelector('.row-no');
            if (no) no.textContent = i + 1;
        });
    }

    function renumberPriorityCards() {
        document.querySelectorAll('#priority-list .priority-card .pr-no').forEach((el, i) => { el.textContent = i + 1; });
    }

    document.getElementById('add-priority-card')?.addEventListener('click', function () {
        const i = priorityIndex++;
        const html = `
            <div class="work-card priority-card">
                <div class="work-card-head">
                    <span class="wc-badge orange"><i class="fi fi-sr-flame"></i> Prioritas <span class="pr-no"></span></span>
                    <div class="d-flex align-items-center gap-10">
                        <div class="radio-group-custom wc-status">
                            <div class="radio-custom neutral"><input type="radio" name="priority_items[${i}][is_completed]" id="pr_undone_${i}" value="0" checked><label for="pr_undone_${i}"><i class="fi fi-rr-clock"></i> Belum</label></div>
                            <div class="radio-custom good"><input type="radio" name="priority_items[${i}][is_completed]" id="pr_done_${i}" value="1"><label for="pr_done_${i}"><i class="fi fi-rr-check"></i> Selesai</label></div>
                        </div>
                        <button type="button" class="wc-remove" data-remove-card><i class="fi fi-rr-trash"></i></button>
                    </div>
                </div>
                <div class="work-card-body">
                    <div class="work-card-grid">
                        <div class="box-input-1"><div class="box-label-1"><label>Unit</label></div><div class="tbl-select-wrapper" data-search="true"><select name="priority_items[${i}][unit_id]" class="tbl-native-select"><option value="">Pilih Unit</option>@foreach ($unitsTruck as $u)<option value="{{ $u['id'] }}">{{ $u['label'] }}</option>@endforeach @foreach ($unitsHeavy as $u)<option value="{{ $u['id'] }}">{{ $u['label'] }}</option>@endforeach</select><span class="sel-caret"><i class="fi fi-rr-angle-small-down"></i></span></div></div>
                        <div class="box-input-1"><div class="box-label-1"><label>Petugas</label></div><div class="input-wrapper"><input type="text" name="priority_items[${i}][assignee]" list="maintenance-employee-datalist" class="custom-input" placeholder="Nama petugas"><i class="fi fi-rr-user input-icon"></i></div></div>
                    </div>
                    <div class="box-input-1"><div class="box-label-1"><label>Pekerjaan Prioritas</label></div><div class="input-wrapper"><textarea name="priority_items[${i}][description]" class="custom-input" rows="2" placeholder="Uraian pekerjaan prioritas..."></textarea></div></div>
                    <div class="box-input-1"><div class="box-label-1"><label>Keterangan</label></div><div class="input-wrapper"><input type="text" name="priority_items[${i}][notes]" class="custom-input" placeholder="Keterangan (opsional)"><i class="fi fi-rr-comment-alt input-icon"></i></div></div>
                </div>
            </div>`;
        document.getElementById('priority-list').insertAdjacentHTML('beforeend', html);
        window.__pmlHydrateSelects?.(document.getElementById('priority-list').lastElementChild);
        renumberPriorityCards();
    });

    document.getElementById('add-attendance-row')?.addEventListener('click', function () {
        const i = attendanceIndex++;
        const html = `
            <div class="body attendance-row">
                <div class="table-column no"><span class="row-no"></span></div>
                <div class="table-column main"><input type="hidden" name="attendances[${i}][master_employee_id]" value=""><div class="table-input-wrapper"><i class="fi fi-sr-user"></i><input type="text" name="attendances[${i}][employee_name]" list="maintenance-employee-datalist" placeholder="Nama karyawan"></div></div>
                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-id-badge"></i><input type="text" name="attendances[${i}][position]" placeholder="Jabatan"></div></div>
                <div class="table-column absent"><div class="table-input-wrapper"><i class="fi fi-rr-time-quarter-past"></i><input type="text" name="attendances[${i}][time_in]" class="time-picker-input" placeholder="00:00" style="text-align:center"></div></div>
                <div class="table-column absent"><div class="table-input-wrapper"><i class="fi fi-rr-time-check text-red"></i><input type="text" name="attendances[${i}][time_out]" class="time-picker-input" placeholder="00:00" style="text-align:center"></div></div>
                <div class="table-column medium"><div class="table-input-wrapper"><input type="text" name="attendances[${i}][notes]" list="pemeliharaan-keterangan-options" class="att-notes" placeholder="Keterangan" autocomplete="off"></div></div>
                <div class="table-column delete"><button type="button" class="btn-trash-row" data-remove-row><i class="fi fi-rr-trash"></i></button></div>
            </div>`;
        this.insertAdjacentHTML('beforebegin', html);
        renumber('attendance-table', 'attendance-row');
        const newRow = this.previousElementSibling;
        window.__pmlHydrateSelects?.(newRow);
        applyWorkHoursToRow(newRow);
    });

    // ---- Jam Kerja (range) -> auto-isi Masuk/Pulang di Daftar Hadir ----
    // Keterangan diketik bebas (bukan dropdown terkunci); Sakit/Cuti/Tidak Masuk
    // hanya saran cepat lewat datalist, dicocokkan tanpa peduli huruf besar/kecil.
    const ABSEN = ['sakit', 'cuti', 'tidak masuk'];

    function isAbsentNote(value) {
        return ABSEN.includes(String(value || '').trim().toLowerCase());
    }

    function rowIsAbsen(row) {
        const input = row.querySelector('input[name$="[notes]"]');
        return input ? isAbsentNote(input.value) : false;
    }
    function applyWorkHoursToRow(row) {
        if (!row) return;
        const ti = row.querySelector('input[name$="[time_in]"]');
        const to = row.querySelector('input[name$="[time_out]"]');
        if (rowIsAbsen(row)) { if (ti) ti.value = ''; if (to) to.value = ''; return; }
        const ms = jamMasuk ? jamMasuk.value.trim() : '';
        const pl = jamPulang ? jamPulang.value.trim() : '';
        if (ti) ti.value = ms;
        if (to) to.value = pl;
    }
    function applyWorkHoursAll() {
        document.querySelectorAll('#attendance-table .attendance-row').forEach(applyWorkHoursToRow);
    }

    function setWorkTime(start, end) {
        if (jamMasuk) jamMasuk.value = start;
        if (jamPulang) jamPulang.value = end;
    }

    function scheduleForDate() {
        const d = selectedDate();

        if (!d) {
            return { type: 'empty', start: '', end: '' };
        }

        const day = d.getDay();

        if (day >= 1 && day <= 4) {
            return { type: 'regular', start: '07:00', end: '16:00' };
        }

        if (day === 5) {
            return { type: 'friday', start: '07:00', end: '17:00' };
        }

        return { type: 'weekend', start: '', end: '' };
    }

    function updateMaintenanceSchedule(options = {}) {
        updateDay();

        const schedule = scheduleForDate();
        const shouldPreserveExisting = options.preserveExisting === true && hasInitialWorkTime;

        if (schedule.type === 'regular' || schedule.type === 'friday') {
            if (!shouldPreserveExisting) setWorkTime(schedule.start, schedule.end);
        } else {
            if (!shouldPreserveExisting) setWorkTime('', '');
        }

        applyWorkHoursAll();
    }

    jamMasuk?.addEventListener('input', applyWorkHoursAll);
    jamPulang?.addEventListener('input', applyWorkHoursAll);
    dateInput?.addEventListener('change', () => updateMaintenanceSchedule({ preserveExisting: false }));
    updateMaintenanceSchedule({ preserveExisting: true });

    // Keterangan absen -> kosongkan Masuk/Pulang; kembali Hadir -> isi ulang jam kerja.
    // Bereaksi langsung saat mengetik ('input') maupun saat memilih dari datalist
    // atau keluar dari field ('change'), agar konsisten dengan pola Karyawan Shift/OP.7.
    document.addEventListener('input', handleAttendanceNoteChange);
    document.addEventListener('change', handleAttendanceNoteChange);

    function handleAttendanceNoteChange(e) {
        const field = e.target;
        if (!field.matches?.('#attendance-table input[name$="[notes]"]')) return;
        const row = field.closest('.attendance-row');
        const ti = row.querySelector('input[name$="[time_in]"]');
        const to = row.querySelector('input[name$="[time_out]"]');
        if (isAbsentNote(field.value)) {
            if (ti) ti.value = '';
            if (to) to.value = '';
        } else {
            const ms = jamMasuk ? jamMasuk.value.trim() : '';
            const pl = jamPulang ? jamPulang.value.trim() : '';
            if (ti && ms) ti.value = ms;
            if (to && pl) to.value = pl;
        }
    }

    document.addEventListener('click', function (e) {
        const cardBtn = e.target.closest('[data-remove-card]');
        if (cardBtn) {
            cardBtn.closest('.work-card')?.remove();
            renumberPriorityCards();
            return;
        }
        const btn = e.target.closest('[data-remove-row]');
        if (!btn) return;
        const row = btn.closest('.body');
        const table = row.closest('.table-input');
        row.remove();
        if (table?.id === 'attendance-table') renumber('attendance-table', 'attendance-row');
    });

    // ---- Simpan Draft / Kirim ----
    function submitAs(status) {
        if (!form || !statusInput) return;
        window.__reportAutosaveSuppress = true; // pengiriman manual: matikan autosave
        statusInput.value = status;
        if (status === 'draft') {
            form.querySelectorAll('[required]').forEach(el => { el.dataset.wasReq = '1'; el.required = false; });
        }
        if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
    }

    document.getElementById('btnSaveDraft')?.addEventListener('click', () => submitAs('draft'));

    document.getElementById('btnOpenConfirm')?.addEventListener('click', () => {
        // Validasi HTML5 (report_date) sebelum membuka modal konfirmasi.
        if (!form.checkValidity()) {
            window.__pmlShowStep?.(0);
            form.reportValidity();
            return;
        }
        window.__pmlOpenModal?.('finishModal');
    });

    document.getElementById('btnFinalSubmit')?.addEventListener('click', function () {
        this.disabled = true;
        this.innerHTML = 'Mengirim...';
        submitAs('submitted');
    });
});
</script>
@endpush

@include('partials.report-autosave')
@include('partials.report-peek')
