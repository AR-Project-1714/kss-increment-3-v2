{{--
    Partial form laporan harian K3 (dipakai create & edit).
    Variabel dari wrapper: $isEdit, $formAction, $headerTitle, $headerDocumentLabel
    Master/template dari controller: $locationGroups, $operationRows, $incidentRows, $catalogItems, $conditions
--}}
@php
    use App\Enums\SafetyStatus;

    $reportModel = isset($report) && is_object($report) ? $report : null;
    $statusValue = $reportModel ? $reportModel->status->value : SafetyStatus::Draft->value;

    $reportDateValue = old('report_date', $reportModel && $reportModel->report_date ? $reportModel->report_date->format('Y-m-d') : now()->toDateString());

    // Jam kerja disimpan sebagai satu kolom "time_range" (mis. "07:00 - 16:00").
    // Untuk form, pecah kembali jadi dua input manual: masuk & pulang.
    $savedRange = $reportModel->time_range ?? '';
    $rangeParts = preg_split('/\s*[-–—]\s*/u', $savedRange, 2);
    $workStartValue = old('work_time_start', trim($rangeParts[0] ?? ''));
    $workEndValue   = old('work_time_end', trim($rangeParts[1] ?? ''));

    $conditionLabels = [
        'bagus'        => 'Bagus',
        'rusak'        => 'Rusak',
        'normal'       => 'Normal',
        'tidak_normal' => 'Tidak Normal',
    ];
    $conditionClass = fn ($value) => str_replace('_', '', $value);

    $locationGroups = $locationGroups ?? [];
    $operationRows = $operationRows ?? [];
    $incidentRows = $incidentRows ?? [];
    $catalogItems = $catalogItems ?? [];

    // Pastikan minimal satu baris kegiatan & satu baris kejadian (placeholder kosong).
    if (empty($operationRows)) {
        $operationRows[] = ['activity_name' => '', 'condition' => '', 'action' => '', 'notes' => ''];
    }
    if (empty($incidentRows)) {
        $incidentRows[] = ['description' => '', 'condition' => '', 'action' => '', 'notes' => ''];
    }
@endphp

@push('styles')
<style>
    #mainReportForm { width: 100%; align-self: stretch; display: block; }
    .content { max-width: 1800px; width: 100%; margin: 0 auto; }
    .content-header { width: 100%; max-width: 1800px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 20px; }
    .title-header { display: flex; flex-direction: column; gap: 2px; min-width: auto; }

    /* Animasi tombol navigasi (sama seperti operasional/pemeliharaan) */
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
    .btn-form .icon i { position: relative; top: 2px; line-height: 1; }

    .form-meta-note { font-size:11px; color:var(--muted); display:flex; align-items:center; gap:6px; }
    .form-meta-note i { position:relative; top:1px; }

    /* ===== Accordion Lokasi Inspeksi ===== */
    .loc-list { display:flex; flex-direction:column; gap:14px; width:100%; }
    .loc-accordion { border:1px solid var(--blue-main-40); border-radius:12px; background:var(--white); width:100%; box-shadow:0 1px 3px var(--blue-main-10); overflow:hidden; }
    .loc-head { display:flex; align-items:center; gap:12px; padding:12px 16px; background:var(--blue-main-5); border-bottom:1px solid transparent; cursor:pointer; transition:.2s ease-out; flex-wrap:wrap; }
    .loc-accordion.open .loc-head { border-bottom-color:var(--divider); }
    .loc-head:hover { background:var(--blue-main-10); }
    .loc-toggle { width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; background:var(--blue-main-10); color:var(--blue-main); flex:0 0 auto; transition:transform .25s ease; }
    .loc-accordion.open .loc-toggle { transform:rotate(90deg); }
    .loc-toggle i { position:relative; top:2px; }
    /* Wrap melebar agar badge tetap di kanan & area kosongnya bisa diklik untuk buka/tutup. */
    .loc-name-wrap { display:flex; align-items:center; gap:8px; flex:1 1 240px; min-width:200px; }
    /* Input hanya selebar teksnya (pakai atribut size), sisanya jadi area klik buka box. */
    .loc-name-input { width:auto; max-width:100%; min-width:0; border:1px solid transparent; background:transparent; font-size:13px; font-weight:600; color:var(--dark-main); font-family:'Poppins',sans-serif; padding:6px 10px; border-radius:8px; outline:none; cursor:text; transition:.2s; }
    .loc-name-input:hover { border-color:var(--blue-main-25); background:var(--white); }
    .loc-name-input:focus { border-color:var(--blue-main); background:var(--white); box-shadow:0 0 0 3px var(--blue-main-10); }
    .loc-count { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; background:var(--white); border:1px solid var(--blue-main-10); font-size:10px; color:var(--muted); flex:0 0 auto; }
    .loc-count i { position:relative; top:1px; }
    /* Badge status pemeriksaan lokasi (Belum diperiksa / Selesai) */
    .loc-status { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:10px; font-weight:600; flex:0 0 auto; border:1px solid transparent; white-space:nowrap; }
    .loc-status i { position:relative; top:1px; }
    .loc-status.is-incomplete { background:var(--orange-main-10); color:var(--orange-main); border-color:var(--orange-main-40); }
    .loc-status.is-complete { background:var(--success-10); color:var(--success); border-color:var(--success); }
    /* Daftar lokasi belum dinilai di modal peringatan */
    .incomplete-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; max-height:220px; overflow-y:auto; }
    .incomplete-list li { display:flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid var(--orange-main-40); background:var(--orange-main-10); border-radius:8px; }
    .incomplete-list li i { color:var(--orange-main); position:relative; top:1px; }
    .incomplete-list li .nm { font-weight:600; font-size:12px; color:var(--dark-main); flex:1 1 auto; }
    .incomplete-list li .ct { font-size:10px; font-weight:600; color:var(--orange-main); white-space:nowrap; }
    .loc-remove { width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; border:none; border-radius:8px; background:var(--red-main-10); color:var(--red-main); cursor:pointer; transition:.2s ease-out; flex:0 0 auto; }
    .loc-remove:hover { background:var(--red-main-25); transform:translateY(-1px); }
    .loc-remove i { position: relative; top:2px; }
    .loc-body { padding:14px 16px; display:none; }
    .loc-accordion.open .loc-body { display:block; }

    /* Kolom tabel inspeksi */
    .table-column.insp-no { width:50px; text-align:center; justify-content:center; font-weight:600; font-size:13px; flex-shrink:0; }
    .table-column.insp-item { flex:1.4; min-width:190px; }
    .table-column.insp-qty { width:96px; flex:0 0 auto; justify-content:center; }
    .table-column.insp-cond { flex:1.8; min-width:340px; }
    .table-column.insp-reco { flex:1.2; min-width:170px; }
    .table-column.insp-del { width:56px; justify-content:center; flex-shrink:0; }
    .insp-table .table-input { min-width:860px; }
    /* Pembungkus baris harus melebar penuh agar sejajar dengan header (head=align-self:stretch). */
    .insp-table .insp-rows { align-self:stretch; width:100%; }
    .insp-qty-dash { width:100%; text-align:center; color:var(--muted); font-weight:600; }

    /* ===== Segmented control kondisi (4 nilai, pilih satu) ===== */
    .cond-group { display:flex; gap:6px; width:100%; }
    .cond-opt { position:relative; flex:1; display:flex; }
    .cond-opt input[type="radio"] { position:absolute; opacity:0; width:0; height:0; }
    .cond-opt label { display:flex; padding:8px 6px; justify-content:center; align-items:center; gap:4px; flex:1 0 0; border:1px solid var(--divider); border-radius:8px; cursor:pointer; font-size:10.5px; font-weight:600; color:var(--muted); background:var(--white); transition:all .2s ease; margin:0; white-space:nowrap; text-align:center; }
    .cond-opt label i { font-size:10px; display:flex; align-items:center; }
    .cond-opt.bagus input:checked + label { border-color:var(--success); background:var(--success-10); color:var(--success); }
    .cond-opt.rusak input:checked + label { border-color:var(--red-main); background:var(--red-main-10); color:var(--red-main); }
    .cond-opt.normal input:checked + label { border-color:var(--blue-main); background:var(--blue-main-10); color:var(--blue-main); }
    .cond-opt.tidaknormal input:checked + label { border-color:var(--orange-main); background:var(--orange-main-10); color:var(--orange-main); }

    .btn-tambah-card { display:flex; padding:13px; justify-content:center; align-items:center; gap:8px; align-self:stretch; width:100%; border-radius:10px; background:transparent; color:var(--blue-main); font-size:13px; font-weight:600; cursor:pointer; transition:.2s; border:1.5px dashed var(--blue-main-40); margin-top:6px; }
    .btn-tambah-card:hover { background:var(--blue-main-5); }
    .btn-tambah-card i { font-size:14px; position:relative; top:1px; }

    .info-work-time { min-width:200px; }
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
</style>
@endpush

@section('content')
<form action="{{ $formAction }}" method="POST" id="mainReportForm">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <input type="hidden" name="status" id="reportStatus" value="{{ $statusValue }}">

    <div class="content d-flex flex-column align-items-start align-self-stretch gap-30 p-content">

        {{-- HEADER --}}
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
            <div class="list-form-tab"><span class="icon-tab"><i class="fi fi-rr-shield-check"></i></span><span>Inspeksi K3</span></div>
            <div class="list-form-tab"><span class="icon-tab"><i class="fi fi-rr-settings"></i></span><span>Kegiatan</span></div>
            <div class="list-form-tab"><span class="icon-tab"><i class="fi fi-rr-triangle-warning"></i></span><span>Kejadian</span></div>
        </div>

        {{-- ===================== SEKSI 1: INFO UMUM ===================== --}}
        <div class="box-form form-step d-flex flex-column align-items-start align-self-stretch w-100" id="step-info-umum">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-document"></i></span>
                    <span class="fw-600">Info Umum</span>
                </div>
                <div class="counter-form">Form 1 dari 4</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="d-flex align-items-start align-self-stretch flex-wrap gap-20 w-100">
                    <div class="box-input-1">
                        <div class="box-label-1"><label for="report_date">Tanggal</label><span class="text-red">*</span></div>
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
                        <div class="box-label-1"><label for="work_time_start">Jam Kerja</label><span class="text-red">*</span></div>
                        <div class="info-work-time__range d-flex align-items-center gap-10 w-100">
                            <div class="input-wrapper">
                                <i class="fi fi-rr-clock input-icon" style="left:15px;right:auto;color:var(--blue-main)"></i>
                                <input type="text" id="work_time_start" name="work_time_start" value="{{ $workStartValue }}" class="custom-input time-picker-input" placeholder="00:00" maxlength="5" style="text-align:center;padding-left:38px" required>
                            </div>
                            <span class="info-work-time__arrow text-secondary fw-600 fsize-18" style="line-height:1">&rarr;</span>
                            <div class="input-wrapper">
                                <i class="fi fi-rr-clock input-icon" style="left:15px;right:auto;color:var(--red-main)"></i>
                                <input type="text" id="work_time_end" name="work_time_end" value="{{ $workEndValue }}" class="custom-input time-picker-input" placeholder="00:00" maxlength="5" style="text-align:center;padding-left:38px">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-meta-note"><i class="fi fi-rr-info"></i><span>Isi jam masuk dan jam pulang kerja secara manual. Tidak ada pengisian otomatis. Jam masuk wajib diisi saat mengirim laporan, dan menjadi pembeda jika ada lebih dari satu laporan pada tanggal yang sama.</span></div>
            </div>
            <div class="content-form box-button" style="padding-top:0">
                <a href="{{ route('safety.index') }}" class="btn-form cancel"><span class="icon"><i class="fi fi-br-cross-small"></i></span><span>Batalkan</span></a>
                <button type="button" class="btn-form next btn-next-step"><span>Lanjut</span><span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span></button>
            </div>
        </div>

        {{-- ===================== SEKSI 2: INSPEKSI K3 ===================== --}}
        <div class="box-form form-step d-none flex-column align-items-start align-self-stretch w-100" id="step-inspeksi">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-shield-check"></i></span>
                    <span class="fw-600">Inspeksi K3</span>
                </div>
                <div class="counter-form">Form 2 dari 4</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="inspection-header">
                    <div class="form-meta-note" style="flex:1 0 0;"><i class="fi fi-rr-info"></i><span>Tiap lokasi berisi daftar item untuk dinilai. Klik judul lokasi untuk buka/tutup. QTY hanya untuk item terhitung.</span></div>
                    <button type="button" class="set-all-good" id="set-all-bagus"><i class="fi fi-rr-check-double"></i> Set Semua Bagus</button>
                </div>

                <div class="loc-list" id="location-list">
                    @foreach ($locationGroups as $L => $group)
                        <div class="loc-accordion {{ $L === 0 ? 'open' : '' }}" data-loc-index="{{ $L }}" data-next-item="{{ count($group['items']) }}">
                            <div class="loc-head">
                                <span class="loc-toggle"><i class="fi fi-rr-angle-small-right"></i></span>
                                <input type="hidden" name="locations[{{ $L }}][location_id]" value="{{ $group['location_id'] }}">
                                <div class="loc-name-wrap">
                                    <i class="fi fi-sr-marker" style="color:var(--blue-main);"></i>
                                    <input type="text" class="loc-name-input" name="locations[{{ $L }}][location_name]" value="{{ $group['location_name'] }}" placeholder="Nama lokasi" data-noprop size="{{ min(max(mb_strlen($group['location_name'] ?: 'Nama lokasi') + 1, 8), 60) }}">
                                </div>
                                <span class="loc-status is-incomplete" data-noprop><i class="fi fi-rr-exclamation"></i> Belum diperiksa</span>
                                <span class="loc-count"><i class="fi fi-rr-list-check"></i> <span class="loc-count-num">{{ count($group['items']) }}</span> item</span>
                                <button type="button" class="loc-remove" data-remove-location data-noprop><i class="fi fi-rr-trash"></i></button>
                            </div>
                            <div class="loc-body">
                                <div class="table-wrapper insp-table">
                                    <div class="table-input">
                                        <div class="head">
                                            <div class="table-column insp-no"><span>No</span></div>
                                            <div class="table-column insp-item"><span>Item Diperiksa</span></div>
                                            <div class="table-column insp-qty"><span>QTY</span></div>
                                            <div class="table-column insp-cond"><span>Kondisi</span></div>
                                            <div class="table-column insp-reco"><span>Rekomendasi</span></div>
                                            <div class="table-column insp-del"><span>Hapus</span></div>
                                        </div>
                                        <div class="insp-rows">
                                            @foreach ($group['items'] as $I => $item)
                                                <div class="body insp-row">
                                                    <div class="table-column insp-no"><span class="row-no">{{ $I + 1 }}</span></div>
                                                    <div class="table-column insp-item">
                                                        <div class="table-input-wrapper">
                                                            <i class="fi fi-sr-checkbox"></i>
                                                            <input type="hidden" name="locations[{{ $L }}][items][{{ $I }}][item_id]" value="{{ $item['item_id'] }}">
                                                            <input type="text" name="locations[{{ $L }}][items][{{ $I }}][item_name]" value="{{ $item['item_name'] }}" list="safety-item-datalist" placeholder="Nama item">
                                                        </div>
                                                    </div>
                                                    <div class="table-column insp-qty">
                                                        @if ($item['is_countable'])
                                                            <div class="table-input-wrapper"><input type="number" min="0" name="locations[{{ $L }}][items][{{ $I }}][qty]" value="{{ $item['qty'] }}" placeholder="0" style="text-align:center"></div>
                                                        @else
                                                            <span class="insp-qty-dash">&minus;</span>
                                                        @endif
                                                    </div>
                                                    <div class="table-column insp-cond">
                                                        <div class="cond-group">
                                                            @foreach ($conditionLabels as $val => $lbl)
                                                                <div class="cond-opt {{ $conditionClass($val) }}">
                                                                    <input type="radio" name="locations[{{ $L }}][items][{{ $I }}][condition]" id="cond_{{ $L }}_{{ $I }}_{{ $val }}" value="{{ $val }}" @checked(($item['condition'] ?? '') === $val)>
                                                                    <label for="cond_{{ $L }}_{{ $I }}_{{ $val }}">{{ $lbl }}</label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="table-column insp-reco">
                                                        <div class="table-input-wrapper"><i class="fi fi-sr-comment-alt"></i><input type="text" name="locations[{{ $L }}][items][{{ $I }}][recommendation]" value="{{ $item['recommendation'] }}" placeholder="Rekomendasi (opsional)"></div>
                                                    </div>
                                                    <div class="table-column insp-del"><button type="button" class="btn-trash-row" data-remove-row><i class="fi fi-rr-trash"></i></button></div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn-tambah-baris" data-add-item><i class="fi fi-rr-plus-small"></i> Tambah Item</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="btn-tambah-card" id="add-location"><i class="fi fi-rr-plus-small"></i> Tambah Lokasi</button>
            </div>
            <div class="content-form box-button" style="padding-top:0">
                <button type="button" class="btn-form back btn-back-step"><span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span><span>Kembali</span></button>
                <button type="button" class="btn-form next btn-next-step"><span>Lanjut</span><span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span></button>
            </div>
        </div>

        {{-- ===================== SEKSI 3: KEGIATAN OPERASI & PEMELIHARAAN ===================== --}}
        <div class="box-form form-step d-none flex-column align-items-start align-self-stretch w-100" id="step-kegiatan">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-settings"></i></span>
                    <span class="fw-600">Kegiatan Operasi &amp; Pemeliharaan</span>
                </div>
                <div class="counter-form">Form 3 dari 4</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="form-meta-note"><i class="fi fi-rr-info"></i><span>Catat kegiatan operasi & pemeliharaan beserta kondisinya (mis. "Aman"). Tambah baris bila perlu.</span></div>
                <div class="table-wrapper">
                    <div class="table-input" id="operation-table">
                        <div class="head">
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Kegiatan</span></div>
                            <div class="table-column medium"><span>Kondisi</span></div>
                            <div class="table-column medium"><span>Tindakan</span></div>
                            <div class="table-column medium"><span>Keterangan</span></div>
                            <div class="table-column delete"><span>Hapus</span></div>
                        </div>
                        @foreach ($operationRows as $i => $row)
                            <div class="body operation-row">
                                <div class="table-column no"><span class="row-no">{{ $i + 1 }}</span></div>
                                <div class="table-column main"><div class="table-input-wrapper"><i class="fi fi-sr-briefcase"></i><input type="text" name="operations[{{ $i }}][activity_name]" value="{{ $row['activity_name'] }}" placeholder="Nama kegiatan"></div></div>
                                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-shield-check"></i><input type="text" name="operations[{{ $i }}][condition]" value="{{ $row['condition'] }}" placeholder="mis. Aman"></div></div>
                                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-wrench-simple"></i><input type="text" name="operations[{{ $i }}][action]" value="{{ $row['action'] }}" placeholder="Tindakan"></div></div>
                                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-comment-alt"></i><input type="text" name="operations[{{ $i }}][notes]" value="{{ $row['notes'] }}" placeholder="Keterangan"></div></div>
                                <div class="table-column delete"><button type="button" class="btn-trash-row" data-remove-row><i class="fi fi-rr-trash"></i></button></div>
                            </div>
                        @endforeach
                        <button type="button" class="btn-tambah-baris" id="add-operation-row"><i class="fi fi-rr-plus-small"></i> Tambah Baris</button>
                    </div>
                </div>
            </div>
            <div class="content-form box-button" style="padding-top:0">
                <button type="button" class="btn-form back btn-back-step"><span class="icon"><i class="fi fi-rr-arrow-small-left"></i></span><span>Kembali</span></button>
                <button type="button" class="btn-form next btn-next-step"><span>Lanjut</span><span class="icon"><i class="fi fi-rr-arrow-small-right"></i></span></button>
            </div>
        </div>

        {{-- ===================== SEKSI 4: KEJADIAN & LAIN-LAIN ===================== --}}
        <div class="box-form form-step d-none flex-column align-items-start align-self-stretch w-100" id="step-kejadian">
            <div class="header-form d-flex justify-content-between align-items-center align-self-stretch">
                <div class="title-form d-flex align-items-center gap-10">
                    <span class="icon-title-form"><i class="fi fi-sr-triangle-warning"></i></span>
                    <span class="fw-600">Laporan Kejadian &amp; Lain-lain</span>
                </div>
                <div class="counter-form">Form 4 dari 4</div>
            </div>
            <div class="content-form d-flex flex-column align-items-start align-self-stretch w-100">
                <div class="form-meta-note"><i class="fi fi-rr-info"></i><span>Bagian ini boleh dikosongkan bila tidak ada kejadian. Tambah baris untuk mencatat kejadian.</span></div>
                <div class="table-wrapper">
                    <div class="table-input" id="incident-table">
                        <div class="head">
                            <div class="table-column no"><span>No</span></div>
                            <div class="table-column main"><span>Uraian Kejadian</span></div>
                            <div class="table-column medium"><span>Kondisi</span></div>
                            <div class="table-column medium"><span>Tindakan</span></div>
                            <div class="table-column medium"><span>Keterangan</span></div>
                            <div class="table-column delete"><span>Hapus</span></div>
                        </div>
                        @foreach ($incidentRows as $i => $row)
                            <div class="body incident-row">
                                <div class="table-column no"><span class="row-no">{{ $i + 1 }}</span></div>
                                <div class="table-column main"><div class="table-input-wrapper"><i class="fi fi-sr-document"></i><input type="text" name="incidents[{{ $i }}][description]" value="{{ $row['description'] }}" placeholder="Uraian kejadian"></div></div>
                                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-shield-check"></i><input type="text" name="incidents[{{ $i }}][condition]" value="{{ $row['condition'] }}" placeholder="Kondisi"></div></div>
                                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-wrench-simple"></i><input type="text" name="incidents[{{ $i }}][action]" value="{{ $row['action'] }}" placeholder="Tindakan"></div></div>
                                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-comment-alt"></i><input type="text" name="incidents[{{ $i }}][notes]" value="{{ $row['notes'] }}" placeholder="Keterangan"></div></div>
                                <div class="table-column delete"><button type="button" class="btn-trash-row" data-remove-row><i class="fi fi-rr-trash"></i></button></div>
                            </div>
                        @endforeach
                        <button type="button" class="btn-tambah-baris" id="add-incident-row"><i class="fi fi-rr-plus-small"></i> Tambah Baris</button>
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

<datalist id="safety-item-datalist">
    @foreach ($catalogItems as $c)<option value="{{ $c['name'] }}"></option>@endforeach
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
                    <span class="fw-600 fsize-14 text-main">Kirim Laporan K3 Sekarang?</span>
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

<div class="modal-overlay" id="incompleteModal">
    <div class="pop-up signed d-flex flex-column gap-20">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-600 fsize-16 text-main">Inspeksi Belum Lengkap</span>
            <button type="button" class="button-close" data-close-modal><i class="fi fi-br-cross"></i></button>
        </div>
        <div class="pop-up-content d-flex flex-column gap-15">
            <div class="pop-up detail d-flex align-items-center">
                <span class="icon-document" style="background-color:var(--orange-main-10);color:var(--orange-main)"><i class="fi fi-sr-triangle-warning"></i></span>
                <div class="d-flex flex-column">
                    <span class="fw-600 fsize-14 text-main">Masih ada lokasi yang belum dinilai</span>
                    <span class="fsize-10 text-secondary">Lengkapi kondisi tiap item sebelum menyelesaikan laporan.</span>
                </div>
            </div>
            <ul class="incomplete-list" id="incompleteList"></ul>
            <p class="fsize-12 text-muted m-0">Anda tetap bisa menekan <span class="fw-600">Simpan Sebagai Draft</span> bila ingin melanjutkannya nanti.</p>
        </div>
        <div class="pop-up footer d-flex justify-content-end gap-10">
            <button type="button" class="btn cancel" id="btnSubmitAnyway"><i class="fi fi-rr-paper-plane"></i> Tetap Kirim</button>
            <button type="button" class="btn confirm" id="btnGotoInspeksi" style="background-color:var(--orange-main)"><i class="fi fi-rr-shield-check"></i> Periksa Lokasi</button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('mainReportForm');
    const statusInput = document.getElementById('reportStatus');

    const conditionLabels = @json($conditionLabels);
    const conditionClass = v => v.replace(/_/g, '');

    // ---- Hari otomatis dari tanggal ----
    const dateInput = document.getElementById('report_date');
    const dayDisplay = document.getElementById('day_name_display');
    const DAYS = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    function updateDay() {
        if (!dateInput || !dayDisplay) return;
        if (!dateInput.value) { dayDisplay.value = ''; return; }
        const d = new Date(dateInput.value + 'T00:00:00');
        dayDisplay.value = isNaN(d) ? '' : DAYS[d.getDay()];
    }
    dateInput?.addEventListener('change', updateDay);
    updateDay();

    // ---- Accordion lokasi ----
    document.getElementById('location-list')?.addEventListener('click', function (e) {
        if (e.target.closest('[data-noprop]')) return;
        const head = e.target.closest('.loc-head');
        if (!head) return;
        head.closest('.loc-accordion')?.classList.toggle('open');
    });

    // ---- Build markup baris inspeksi ----
    function condGroupHtml(locIndex, itemIndex, selected) {
        let html = '<div class="cond-group">';
        Object.keys(conditionLabels).forEach(val => {
            const id = `cond_${locIndex}_${itemIndex}_${val}`;
            const checked = selected === val ? 'checked' : '';
            html += `<div class="cond-opt ${conditionClass(val)}"><input type="radio" name="locations[${locIndex}][items][${itemIndex}][condition]" id="${id}" value="${val}" ${checked}><label for="${id}">${conditionLabels[val]}</label></div>`;
        });
        return html + '</div>';
    }
    function inspectionRowHtml(locIndex, itemIndex, itemName = '', countable = true) {
        const qtyCell = countable
            ? `<div class="table-input-wrapper"><input type="number" min="0" name="locations[${locIndex}][items][${itemIndex}][qty]" placeholder="0" style="text-align:center"></div>`
            : '<span class="insp-qty-dash">&minus;</span>';
        return `
            <div class="body insp-row">
                <div class="table-column insp-no"><span class="row-no">${itemIndex + 1}</span></div>
                <div class="table-column insp-item"><div class="table-input-wrapper"><i class="fi fi-sr-checkbox"></i><input type="hidden" name="locations[${locIndex}][items][${itemIndex}][item_id]" value=""><input type="text" name="locations[${locIndex}][items][${itemIndex}][item_name]" value="${itemName.replace(/"/g, '&quot;')}" list="safety-item-datalist" placeholder="Nama item"></div></div>
                <div class="table-column insp-qty">${qtyCell}</div>
                <div class="table-column insp-cond">${condGroupHtml(locIndex, itemIndex, '')}</div>
                <div class="table-column insp-reco"><div class="table-input-wrapper"><i class="fi fi-sr-comment-alt"></i><input type="text" name="locations[${locIndex}][items][${itemIndex}][recommendation]" placeholder="Rekomendasi (opsional)"></div></div>
                <div class="table-column insp-del"><button type="button" class="btn-trash-row" data-remove-row><i class="fi fi-rr-trash"></i></button></div>
            </div>`;
    }

    function renumberInsp(accordion) {
        const rows = accordion.querySelectorAll('.insp-rows .insp-row');
        rows.forEach((r, i) => { const n = r.querySelector('.row-no'); if (n) n.textContent = i + 1; });
        const counter = accordion.querySelector('.loc-count-num');
        if (counter) counter.textContent = rows.length;
        updateLocStatus(accordion);
    }

    // Lebar input nama lokasi mengikuti panjang teksnya, jadi sisa header (area kosong)
    // tetap bisa diklik untuk membuka/menutup box inspeksi lokasi.
    function autosizeLocInput(input) {
        if (!input) return;
        const text = input.value || input.placeholder || '';
        input.size = Math.min(Math.max(text.length + 1, 8), 60);
    }

    // Status pemeriksaan lokasi: lokasi dianggap "Belum diperiksa" selama masih ada
    // item yang belum dipilih kondisinya. Badge di kepala accordion ikut terlihat
    // walau accordion ditutup, jadi petugas tahu lokasi mana yang belum lengkap.
    function updateLocStatus(accordion) {
        if (!accordion) return;
        const badge = accordion.querySelector('.loc-status');
        if (!badge) return;
        const rows = accordion.querySelectorAll('.insp-rows .insp-row');
        const total = rows.length;
        let rated = 0;
        rows.forEach(r => { if (r.querySelector('.cond-group input[type="radio"]:checked')) rated++; });

        if (total === 0 || rated === 0) {
            badge.className = 'loc-status is-incomplete';
            badge.innerHTML = '<i class="fi fi-rr-exclamation"></i> Belum diperiksa';
        } else if (rated < total) {
            badge.className = 'loc-status is-incomplete';
            badge.innerHTML = `<i class="fi fi-rr-exclamation"></i> ${total - rated} item belum dinilai`;
        } else {
            badge.className = 'loc-status is-complete';
            badge.innerHTML = '<i class="fi fi-rr-check"></i> Selesai';
        }
    }

    function refreshAllLocStatus() {
        document.querySelectorAll('#location-list .loc-accordion').forEach(updateLocStatus);
    }

    // Tambah item per lokasi
    document.getElementById('location-list')?.addEventListener('click', function (e) {
        const addBtn = e.target.closest('[data-add-item]');
        if (!addBtn) return;
        const accordion = addBtn.closest('.loc-accordion');
        const locIndex = accordion.dataset.locIndex;
        let nextItem = parseInt(accordion.dataset.nextItem || '0', 10);
        const rowsBox = accordion.querySelector('.insp-rows');
        rowsBox.insertAdjacentHTML('beforeend', inspectionRowHtml(locIndex, nextItem, '', true));
        accordion.dataset.nextItem = String(nextItem + 1);
        renumberInsp(accordion);
    });

    // Hapus baris inspeksi
    document.getElementById('location-list')?.addEventListener('click', function (e) {
        const delBtn = e.target.closest('[data-remove-row]');
        if (!delBtn) return;
        const accordion = delBtn.closest('.loc-accordion');
        delBtn.closest('.insp-row')?.remove();
        renumberInsp(accordion);
    });

    // Hapus lokasi
    document.getElementById('location-list')?.addEventListener('click', function (e) {
        const rmLoc = e.target.closest('[data-remove-location]');
        if (!rmLoc) return;
        rmLoc.closest('.loc-accordion')?.remove();
    });

    // Pilih kondisi -> perbarui status "Belum diperiksa" lokasi terkait.
    document.getElementById('location-list')?.addEventListener('change', function (e) {
        if (e.target.matches('.cond-group input[type="radio"]')) {
            updateLocStatus(e.target.closest('.loc-accordion'));
        }
    });

    // Lebar input nama lokasi menyesuaikan teks saat diketik.
    document.getElementById('location-list')?.addEventListener('input', function (e) {
        if (e.target.classList.contains('loc-name-input')) autosizeLocInput(e.target);
    });

    // Tambah lokasi
    let locIndexCounter = {{ count($locationGroups) }};
    document.getElementById('add-location')?.addEventListener('click', function () {
        const L = locIndexCounter++;
        const html = `
            <div class="loc-accordion open" data-loc-index="${L}" data-next-item="1">
                <div class="loc-head">
                    <span class="loc-toggle"><i class="fi fi-rr-angle-small-right"></i></span>
                    <input type="hidden" name="locations[${L}][location_id]" value="">
                    <div class="loc-name-wrap">
                        <i class="fi fi-sr-marker" style="color:var(--blue-main);"></i>
                        <input type="text" class="loc-name-input" name="locations[${L}][location_name]" value="" placeholder="Nama lokasi" data-noprop size="12">
                    </div>
                    <span class="loc-status is-incomplete" data-noprop><i class="fi fi-rr-exclamation"></i> Belum diperiksa</span>
                    <span class="loc-count"><i class="fi fi-rr-list-check"></i> <span class="loc-count-num">1</span> item</span>
                    <button type="button" class="loc-remove" data-remove-location data-noprop><i class="fi fi-rr-trash"></i></button>
                </div>
                <div class="loc-body">
                    <div class="table-wrapper insp-table">
                        <div class="table-input">
                            <div class="head">
                                <div class="table-column insp-no"><span>No</span></div>
                                <div class="table-column insp-item"><span>Item Diperiksa</span></div>
                                <div class="table-column insp-qty"><span>QTY</span></div>
                                <div class="table-column insp-cond"><span>Kondisi</span></div>
                                <div class="table-column insp-reco"><span>Rekomendasi</span></div>
                                <div class="table-column insp-del"><span>Hapus</span></div>
                            </div>
                            <div class="insp-rows">${inspectionRowHtml(L, 0, '', true)}</div>
                            <button type="button" class="btn-tambah-baris" data-add-item><i class="fi fi-rr-plus-small"></i> Tambah Item</button>
                        </div>
                    </div>
                </div>
            </div>`;
        // Sisipkan ke dalam #location-list agar tercakup event delegation
        // (toggle, hapus lokasi, tambah/hapus item). Tombol #add-location berada
        // di luar list, jadi 'beforebegin' akan menaruh kartu di luar jangkauan.
        document.getElementById('location-list').insertAdjacentHTML('beforeend', html);
        const newAcc = document.getElementById('location-list').lastElementChild;
        updateLocStatus(newAcc);
        autosizeLocInput(newAcc?.querySelector('.loc-name-input'));
    });

    // ---- Set Semua Bagus ----
    document.getElementById('set-all-bagus')?.addEventListener('click', function () {
        document.querySelectorAll('#location-list input[type="radio"][value="bagus"]').forEach(r => { r.checked = true; });
        refreshAllLocStatus();
        window.showReportToast?.('success', 'Berhasil', 'Semua item disetel ke kondisi Bagus.', 2600);
    });

    // Status awal & lebar input nama tiap lokasi saat halaman dibuka.
    refreshAllLocStatus();
    document.querySelectorAll('#location-list .loc-name-input').forEach(autosizeLocInput);

    // ---- Tabel dinamis kegiatan & kejadian ----
    function renumber(tableId, rowClass) {
        document.querySelectorAll(`#${tableId} .${rowClass}`).forEach((row, i) => {
            const no = row.querySelector('.row-no');
            if (no) no.textContent = i + 1;
        });
    }
    let operationIndex = {{ count($operationRows) }};
    let incidentIndex = {{ count($incidentRows) }};

    document.getElementById('add-operation-row')?.addEventListener('click', function () {
        const i = operationIndex++;
        const html = `
            <div class="body operation-row">
                <div class="table-column no"><span class="row-no"></span></div>
                <div class="table-column main"><div class="table-input-wrapper"><i class="fi fi-sr-briefcase"></i><input type="text" name="operations[${i}][activity_name]" placeholder="Nama kegiatan"></div></div>
                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-shield-check"></i><input type="text" name="operations[${i}][condition]" placeholder="mis. Aman"></div></div>
                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-wrench-simple"></i><input type="text" name="operations[${i}][action]" placeholder="Tindakan"></div></div>
                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-comment-alt"></i><input type="text" name="operations[${i}][notes]" placeholder="Keterangan"></div></div>
                <div class="table-column delete"><button type="button" class="btn-trash-row" data-remove-row><i class="fi fi-rr-trash"></i></button></div>
            </div>`;
        this.insertAdjacentHTML('beforebegin', html);
        renumber('operation-table', 'operation-row');
    });

    document.getElementById('add-incident-row')?.addEventListener('click', function () {
        const i = incidentIndex++;
        const html = `
            <div class="body incident-row">
                <div class="table-column no"><span class="row-no"></span></div>
                <div class="table-column main"><div class="table-input-wrapper"><i class="fi fi-sr-document"></i><input type="text" name="incidents[${i}][description]" placeholder="Uraian kejadian"></div></div>
                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-shield-check"></i><input type="text" name="incidents[${i}][condition]" placeholder="Kondisi"></div></div>
                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-wrench-simple"></i><input type="text" name="incidents[${i}][action]" placeholder="Tindakan"></div></div>
                <div class="table-column medium"><div class="table-input-wrapper"><i class="fi fi-sr-comment-alt"></i><input type="text" name="incidents[${i}][notes]" placeholder="Keterangan"></div></div>
                <div class="table-column delete"><button type="button" class="btn-trash-row" data-remove-row><i class="fi fi-rr-trash"></i></button></div>
            </div>`;
        this.insertAdjacentHTML('beforebegin', html);
        renumber('incident-table', 'incident-row');
    });

    // Hapus baris kegiatan/kejadian
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-remove-row]');
        if (!btn) return;
        const opRow = btn.closest('.operation-row');
        const inRow = btn.closest('.incident-row');
        if (opRow) { opRow.remove(); renumber('operation-table', 'operation-row'); }
        else if (inRow) { inRow.remove(); renumber('incident-table', 'incident-row'); }
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

    // Daftar lokasi yang masih punya item tanpa kondisi (belum diperiksa).
    const escapeHtml = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    function incompleteLocations() {
        const result = [];
        document.querySelectorAll('#location-list .loc-accordion').forEach(acc => {
            const rows = acc.querySelectorAll('.insp-rows .insp-row');
            const total = rows.length;
            let rated = 0;
            rows.forEach(r => { if (r.querySelector('.cond-group input[type="radio"]:checked')) rated++; });
            if (total === 0 || rated < total) {
                const name = (acc.querySelector('.loc-name-input')?.value || '').trim() || 'Lokasi tanpa nama';
                result.push({ name, remaining: total === 0 ? 0 : total - rated, total });
            }
        });
        return result;
    }

    document.getElementById('btnOpenConfirm')?.addEventListener('click', () => {
        if (!form.checkValidity()) {
            window.__pmlShowStep?.(0);
            form.reportValidity();
            return;
        }

        // Cegah penyelesaian bila masih ada lokasi yang belum dinilai.
        const incomplete = incompleteLocations();
        if (incomplete.length > 0) {
            const list = document.getElementById('incompleteList');
            if (list) {
                list.innerHTML = incomplete.map(loc => {
                    const detail = loc.total === 0 ? 'belum ada item' : `${loc.remaining} item belum dinilai`;
                    return `<li><i class="fi fi-sr-marker"></i><span class="nm">${escapeHtml(loc.name)}</span><span class="ct">${detail}</span></li>`;
                }).join('');
            }
            window.__pmlOpenModal?.('incompleteModal');
            return;
        }

        window.__pmlOpenModal?.('finishModal');
    });

    document.getElementById('btnGotoInspeksi')?.addEventListener('click', () => {
        window.__pmlCloseModals?.();
        window.__pmlShowStep?.(1); // tab Inspeksi K3
    });

    // Tetap kirim walau inspeksi belum lengkap: lanjut ke konfirmasi penyelesaian.
    document.getElementById('btnSubmitAnyway')?.addEventListener('click', () => {
        window.__pmlCloseModals?.();
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
