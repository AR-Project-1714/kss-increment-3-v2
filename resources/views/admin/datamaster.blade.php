@extends('admin.layouts.app')

@section('title', 'KSS Admin — Master Data')
@section('active', 'master')

@push('styles')
<style>
    /* =============================================
       SECTION CARD
       ============================================= */
    .section-card {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        transition: background-color 0.3s ease;
    }

    .section-card__title { font-size: 16px; font-weight: 600; color: var(--black); }

    /* =============================================
       BREADCRUMB
       ============================================= */
    .page-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 400;
    }
    .page-breadcrumb__root { color: var(--muted); }
    .page-breadcrumb__sep { color: var(--muted); font-size: 11px; display: flex; position: relative; top: 1px; }
    .page-breadcrumb__current { color: var(--black-secondary); font-weight: 500; }

    /* =============================================
       TOOLBAR
       ============================================= */
    .archive-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

    .archive-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .search-action-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1 1 540px;
        max-width: 620px;
        min-width: 0;
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        border: 1px solid var(--smooth-border);
        border-radius: 50px;
        background-color: var(--main-bg);
        flex: 1 1 auto;
        max-width: none;
        min-width: 260px;
    }

    .search-box i { color: var(--muted); font-size: 13px; position: relative; top: 1px; }

    .search-box input {
        border: none;
        background: transparent;
        outline: none;
        font-family: inherit;
        font-size: 12px;
        color: var(--black);
        width: 100%;
    }

    .search-box input::placeholder { color: var(--muted); }

    .btn-tool {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        color: var(--black-secondary);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .btn-tool i { position: relative; top: 1px; }
    .btn-tool--primary { background-color: var(--blue-main); border-color: var(--blue-main); color: #fff; }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }

    .search-action-group .btn-tool {
        height: 44px;
        flex-shrink: 0;
        padding: 0 16px;
    }

    @media (max-width: 640px) {
        .search-action-group {
            flex: 1 1 100%;
            max-width: none;
        }

        .search-box {
            min-width: 0;
        }
    }

    /* =============================================
       FILTER (tombol toggle + panel, pola Arsip Laporan)
       ============================================= */
    .master-toolbar-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    .btn-tool:hover { background-color: var(--blue-main-5); border-color: var(--blue-main-25); color: var(--blue-main); }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }
    .btn-tool--active { background-color: var(--blue-main-10); border-color: var(--blue-main); color: var(--blue-main); }
    .btn-tool.is-hidden { display: none; }

    .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border: 1px solid var(--red-main);
        border-radius: 8px;
        background-color: transparent;
        color: var(--red-main);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: 0.2s ease;
    }
    .btn-reset i { position: relative; top: 1px; }
    .btn-reset:hover { background-color: var(--red-main-10, rgba(239,68,68,0.08)); }
    .btn-reset.is-hidden { display: none; }

    .master-filter-panel {
        flex: 1 1 100%;
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 4px;
        animation: filterSlideDown 0.3s ease;
    }

    .master-filter-panel.collapsed { display: none; }

    @keyframes filterSlideDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .master-filter-group { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; width: 100%; }

    .filter-field { display: flex; flex: 1 1 160px; min-width: 0; flex-direction: column; gap: 4px; }
    .filter-field label { font-size: 10px; font-weight: 500; color: var(--black-secondary); }

    /* Base input look (dipakai trigger custom dropdown) */
    .filter-input {
        font-family: inherit;
        font-size: 12px;
        color: var(--black);
        background-color: var(--white);
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .filter-field .filter-input { width: 100%; min-width: 0; height: 38px; display: flex; align-items: center; }

    /* Custom dropdown (pola Arsip Laporan) */
    .native-select { display: none; }

    .filter-select-wrapper { position: relative; width: 100%; min-width: 0; }

    .filter-select-trigger { display: flex; align-items: center; padding-right: 34px; cursor: pointer; }
    .filter-select-trigger span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .filter-select-trigger.focus-active { border-color: var(--blue-main); box-shadow: 0 0 0 3px var(--blue-main-10); }

    .select-arrow {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--blue-main);
        font-size: 14px;
        pointer-events: none;
        display: flex;
        transition: transform 0.2s ease;
    }
    .filter-select-trigger.focus-active ~ .select-arrow { transform: translateY(-50%) rotate(180deg); }

    .filter-select-options {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background-color: var(--white);
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        z-index: 999;
        display: none;
        max-height: 220px;
        overflow-y: auto;
        padding: 6px 0;
    }
    .filter-select-options.open { display: block; animation: fadeIn 0.2s ease-out; }

    .filter-select-option {
        padding: 9px 14px;
        font-size: 12px;
        color: var(--black-secondary);
        cursor: pointer;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .filter-select-option:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
    .filter-select-option.selected {
        background-color: var(--blue-main-5);
        color: var(--blue-main);
        border-left: 3px solid var(--blue-main);
        font-weight: 500;
    }

    @media (max-width: 640px) {
        .master-toolbar-actions { width: 100%; }
        .filter-field { max-width: none; }
        .master-filter-group { width: 100%; }
    }

    /* =============================================
       MASTER DATA PANES (Tab switching)
       ============================================= */
    .master-pane { display: none; }
    .master-pane.active { display: block; }

    /* =============================================
       TABLE
       ============================================= */
    .table-responsive-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: var(--blue-main-25) transparent;
    }

    .table-responsive-wrapper::-webkit-scrollbar { height: 6px; }
    .table-responsive-wrapper::-webkit-scrollbar-track { background: transparent; border-radius: 10px; }
    .table-responsive-wrapper::-webkit-scrollbar-thumb { background-color: var(--blue-main-25); border-radius: 10px; }
    .table-responsive-wrapper::-webkit-scrollbar-thumb:hover { background-color: var(--blue-main-40); }

    .table-responsive-wrapper table { min-width: 900px; width: 100%; }

    .thead { background-color: var(--blue-main-5); border-radius: 6px; }

    .thead th {
        display: flex;
        padding: 10px;
        align-items: center;
        flex: 1 0 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--black-secondary);
    }

    .tbody { border-bottom: 1px solid var(--smooth-border); transition: background-color 0.15s ease-in-out; }
    .tbody:hover { background-color: var(--blue-main-3); }

    .tbody td {
        display: flex;
        align-items: center;
        padding: 12px 10px;
        flex: 1 0 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--black);
    }

    /* Columns */
    .thead th.col-no,       .tbody td.col-no       { width: 50px; flex: none; justify-content: center; padding: 12px 0; color: var(--black-secondary); }
    .thead th.col-name,     .tbody td.col-name     { min-width: 150px; }
    .thead th.col-code,     .tbody td.col-code     { min-width: 85px; }
    .thead th.col-brand,    .tbody td.col-brand    { min-width: 105px; }
    .thead th.col-number,   .tbody td.col-number   { min-width: 110px; }
    .thead th.col-npk,      .tbody td.col-npk      { min-width: 110px; }
    .thead th.col-group,    .tbody td.col-group    { min-width: 100px; }
    .thead th.col-position, .tbody td.col-position { min-width: 100px; }
    .thead th.col-division, .tbody td.col-division { min-width: 120px; }
    .thead th.col-worktime, .tbody td.col-worktime { min-width: 110px; }
    .thead th.col-type,     .tbody td.col-type     { min-width: 120px; }
    .thead th.col-year,     .tbody td.col-year     { min-width: 80px; }
    .thead th.col-plate,    .tbody td.col-plate    { min-width: 120px; }
    .thead th.col-desc,     .tbody td.col-desc     { min-width: 200px; flex: 2 0 0; }
    .thead th.col-category, .tbody td.col-category { min-width: 130px; }
    .thead th.col-opscheck, .tbody td.col-opscheck { min-width: 110px; }
    .thead th.col-stock,    .tbody td.col-stock    { min-width: 90px; }
    .thead th.col-order,    .tbody td.col-order    { min-width: 90px; }
    .thead th.col-count,    .tbody td.col-count    { min-width: 110px; }
    .thead th.col-status,   .tbody td.col-status   { min-width: 110px; }
    .thead th.col-qtyflag,  .tbody td.col-qtyflag  { min-width: 120px; }
    .thead th.col-aksi,     .tbody td.col-aksi     { min-width: 180px; gap: 8px; flex-wrap: nowrap; }

    /* Action buttons */
    td.col-aksi .btn-act {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 7px 10px;
        border: none;
        border-radius: 6px;
        color: #fff;
        font-family: inherit;
        font-size: 10px;
        font-weight: 500;
        white-space: nowrap;
        cursor: pointer;
        transition: 0.2s ease-out;
    }

    td.col-aksi .btn-act i { position: relative; top: 1px; }
    td.col-aksi .btn-act.edit { background-color: var(--blue-main); }
    td.col-aksi .btn-act.edit:hover { background-color: var(--blue-hover); transform: translateY(-1px); }
    td.col-aksi .btn-act.delete { background-color: var(--red-main); }
    td.col-aksi .btn-act.delete:hover { background-color: var(--red-hover); transform: translateY(-1px); }
</style>
@endpush

@section('content')
@php
    $employees = $employees ?? collect([
        ['no' => 1, 'npk' => '2000.1.010', 'name' => 'Mustari S,T',         'group' => 'Kantor', 'position' => 'Admin'],
        ['no' => 2, 'npk' => '2000.1.011', 'name' => 'Budi Santoso',        'group' => 'Regu A', 'position' => 'Operator'],
        ['no' => 3, 'npk' => '2000.1.012', 'name' => 'Andi Wijaya',         'group' => 'Regu B', 'position' => 'Mekanik'],
        ['no' => 4, 'npk' => '2000.1.013', 'name' => 'Siti Aminah',         'group' => 'Kantor', 'position' => 'Staff'],
    ]);
    $units = $units ?? collect([
        ['no' => 1, 'name' => 'Excavator PC200',    'type' => 'Alat Berat'],
        ['no' => 2, 'name' => 'Dump Truck HD465',   'type' => 'Kendaraan'],
        ['no' => 3, 'name' => 'Bulldozer D85ESS',   'type' => 'Alat Berat'],
        ['no' => 4, 'name' => 'Wheel Loader WA380', 'type' => 'Alat Berat'],
    ]);
    $trucks = $trucks ?? collect([
        ['no' => 1, 'name' => 'Hino 500',        'plate' => 'B 9012 KSS', 'desc' => 'Truk Angkut Material'],
        ['no' => 2, 'name' => 'Mitsubishi Fuso', 'plate' => 'B 9013 KSS', 'desc' => 'Truk Tangki Air'],
        ['no' => 3, 'name' => 'Isuzu Giga',      'plate' => 'B 9014 KSS', 'desc' => 'Truk Angkut Batu'],
        ['no' => 4, 'name' => 'Scania P360',     'plate' => 'B 9015 KSS', 'desc' => 'Truk Trailer'],
    ]);
    $inventories = $inventories ?? collect([
        ['no' => 1, 'name' => 'Helm Safety',        'category' => 'APD'],
        ['no' => 2, 'name' => 'Sepatu Boots',       'category' => 'APD'],
        ['no' => 3, 'name' => 'Oli Mesin SAE 40',   'category' => 'Sparepart'],
        ['no' => 4, 'name' => 'Ban Truck 1000-20',  'category' => 'Sparepart'],
    ]);
    $safetyLocations = $safetyLocations ?? collect([]);
    $safetyItems = $safetyItems ?? collect([]);
    $masterActions = $masterActions ?? [
        'karyawan' => ['store' => '#'],
        'unit' => ['store' => '#'],
        'truck' => ['store' => '#'],
        'inventaris' => ['store' => '#'],
        'safety_lokasi' => ['store' => '#'],
        'safety_item' => ['store' => '#'],
    ];
    $masterUi = [
        'karyawan'      => ['title' => 'Data Karyawan',   'search' => 'Cari Karyawan',   'add' => 'Tambah Karyawan',   'icon' => 'fi fi-rr-user-add'],
        'unit'          => ['title' => 'Data Unit',       'search' => 'Cari Unit',       'add' => 'Tambah Unit',       'icon' => 'fi fi-rr-add'],
        'truck'         => ['title' => 'Data Truck',      'search' => 'Cari Truck',      'add' => 'Tambah Truck',      'icon' => 'fi fi-rr-add'],
        'inventaris'    => ['title' => 'Data Inventaris', 'search' => 'Cari Inventaris', 'add' => 'Tambah Inventaris', 'icon' => 'fi fi-rr-add'],
        'safety_lokasi' => ['title' => 'Data Lokasi K3',  'search' => 'Cari Lokasi K3',  'add' => 'Tambah Lokasi',     'icon' => 'fi fi-rr-marker'],
        'safety_item'   => ['title' => 'Data Item K3',    'search' => 'Cari Item K3',    'add' => 'Tambah Item',       'icon' => 'fi fi-rr-checkbox'],
    ];

    $activePane = $activePane ?? 'karyawan';
    $activeMasterUi = $masterUi[$activePane] ?? $masterUi['karyawan'];
    $masterSearch = $masterSearch ?? '';
    $masterFilters = $masterFilters ?? ['group' => '', 'division' => '', 'position' => '', 'type' => '', 'category' => ''];

    // Opsi filter dropdown (diselaraskan dengan modal tambah/edit).
    $filterGroupOptions = [
        '' => 'Semua Group', 'kantor' => 'Kantor', 'bengkel' => 'Bengkel',
        'Relief 1' => 'Relief 1', 'Relief 2' => 'Relief 2',
        'A' => 'Regu A', 'B' => 'Regu B', 'C' => 'Regu C', 'D' => 'Regu D',
        'OP7 A' => 'OP7 A', 'OP7 B' => 'OP7 B', 'OP7 C' => 'OP7 C', 'OP7 D' => 'OP7 D',
    ];
    $filterDivisionOptions = [
        '' => 'Semua Divisi', 'Operasional' => 'Operasional', 'Pemeliharaan' => 'Pemeliharaan', 'Safety (Coming Soon)' => 'Safety', 'Office' => 'Office',
    ];
    $positionOptionList = ['Checker', 'Operator FL', 'Driver', 'Operator Exca/ WL', 'Operator WL/ Exca', 'Kasi Pemeliharaan & Peralatan', 'Karu Peralatan', 'Karu Pemeliharaan', 'Mekanik', 'Helper', 'Rigger', 'Operator OP.7', 'Manager', 'Kabag', 'Kasi', 'Staf Ahli', 'Staf', 'Kepala Seksi'];
    $filterPositionOptions = array_merge(['' => 'Semua Jabatan'], array_combine($positionOptionList, $positionOptionList));
    $unitTypeOptionList = ['Trailer', 'Tronton', 'Dump Truck', 'Minibus', 'Bus', 'Pickup', 'Forklift', 'Wheel Loader', 'Excavator'];
    $filterTypeOptions = array_merge(['' => 'Semua Tipe'], array_combine($unitTypeOptionList, $unitTypeOptionList));
    $filterCategoryOptions = [
        '' => 'Semua Kategori', 'truck' => 'Truck', 'bus' => 'Bus', 'heavy' => 'Heavy', '-' => 'Tanpa Kategori',
    ];

    $masterFilterActive = [
        'karyawan' => $masterFilters['group'] !== '' || $masterFilters['division'] !== '' || $masterFilters['position'] !== '',
        'unit' => $masterFilters['type'] !== '' || $masterFilters['category'] !== '',
    ];

    // Pencarian/filter hanya berlaku pada pane yang sedang aktif, jadi pesan
    // "tidak ada hasil" hanya muncul pada pane tersebut.
    $masterEmptyState = function (string $pane, string $singular, string $icon) use ($activePane, $masterSearch, $masterFilterActive): array {
        $isFiltering = $activePane === $pane && ($masterSearch !== '' || ($masterFilterActive[$pane] ?? false));

        return [
            'icon' => $isFiltering ? 'fi fi-rr-search' : $icon,
            'title' => $isFiltering ? 'Tidak ada '.$singular.' yang cocok' : 'Belum ada data '.$singular,
            'message' => $isFiltering
                ? 'Tidak ada '.$singular.' yang sesuai dengan pencarian atau filter aktif. Coba ubah kata kunci atau atur ulang filter.'
                : 'Tambahkan '.$singular.' baru lewat tombol di atas untuk melengkapi master data.',
        ];
    };
@endphp

<div class="page-header">
    <span class="page-title">Master Data</span>
    <div class="page-breadcrumb">
        <span class="page-breadcrumb__root">Data Master</span>
        <span class="page-breadcrumb__sep"><i class="fi fi-rr-angle-small-right"></i></span>
        <span class="page-breadcrumb__current" id="masterCrumb">{{ $activeMasterUi['title'] }}</span>
    </div>
</div>

@component('admin.layouts.card', ['title' => $activeMasterUi['title'], 'titleId' => 'masterTitle'])
    <!-- Toolbar -->
    <form class="archive-toolbar" method="GET" action="{{ route('admin.datamaster') }}" id="masterSearchForm" autocomplete="off">
        <input type="hidden" name="pane" id="masterPaneInput" value="{{ $activePane ?? 'karyawan' }}">
        <div class="search-action-group">
            <div class="search-box">
                <span><i class="fi fi-rr-search"></i></span>
                <input type="text" name="q" value="{{ $masterSearch ?? '' }}" placeholder="{{ $activeMasterUi['search'] }}" id="masterSearch" data-search-debounce="650" aria-label="Cari data master">
            </div>
        </div>
        @php
            $filterApplied = $masterFilterActive[$activePane] ?? false;
            $hasFilterPane = in_array($activePane, ['karyawan', 'unit'], true);
        @endphp
        <div class="master-toolbar-actions">
            <button type="button"
                    @class(['btn-tool', 'btn-tool--active' => $filterApplied, 'is-hidden' => ! $hasFilterPane])
                    id="masterFilterBtn" aria-expanded="{{ $filterApplied ? 'true' : 'false' }}">
                <i class="fi fi-rr-filter"></i> Filter
            </button>
            <a href="{{ route('admin.datamaster', ['pane' => $activePane]) }}"
               @class(['btn-reset', 'is-hidden' => ! $filterApplied && $masterSearch === ''])
               id="masterFilterReset">
                <i class="fi fi-rr-refresh"></i> Reset
            </a>
            <button type="button" class="btn-tool btn-tool--primary" id="masterAddBtn">
                <i class="{{ $activeMasterUi['icon'] }}" id="masterAddIcon"></i> <span id="masterAddText">{{ $activeMasterUi['add'] }}</span>
            </button>
        </div>

        {{-- Filter panel (muncul saat tombol Filter ditekan, pola Arsip Laporan) --}}
        <div @class(['master-filter-panel', 'collapsed' => ! $filterApplied || ! $hasFilterPane]) id="masterFilterPanel">
            {{-- Filter Karyawan --}}
            <div class="master-filter-group" data-filter-pane="karyawan" @style(['display:none' => $activePane !== 'karyawan'])>
                <div class="filter-field">
                    <label>Divisi</label>
                    <div class="filter-select-wrapper">
                        <select name="f_division" class="native-select js-master-filter" aria-label="Filter Divisi">
                            @foreach ($filterDivisionOptions as $val => $label)
                                <option value="{{ $val }}" @selected($masterFilters['division'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <i class="fi fi-rr-angle-small-down select-arrow"></i>
                    </div>
                </div>
                <div class="filter-field">
                    <label>Group</label>
                    <div class="filter-select-wrapper">
                        <select name="f_group" class="native-select js-master-filter" aria-label="Filter Group">
                            @foreach ($filterGroupOptions as $val => $label)
                                <option value="{{ $val }}" @selected($masterFilters['group'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <i class="fi fi-rr-angle-small-down select-arrow"></i>
                    </div>
                </div>
                <div class="filter-field">
                    <label>Jabatan</label>
                    <div class="filter-select-wrapper">
                        <select name="f_position" class="native-select js-master-filter" aria-label="Filter Jabatan">
                            @foreach ($filterPositionOptions as $val => $label)
                                <option value="{{ $val }}" @selected($masterFilters['position'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <i class="fi fi-rr-angle-small-down select-arrow"></i>
                    </div>
                </div>
            </div>

            {{-- Filter Unit --}}
            <div class="master-filter-group" data-filter-pane="unit" @style(['display:none' => $activePane !== 'unit'])>
                <div class="filter-field">
                    <label>Tipe Unit</label>
                    <div class="filter-select-wrapper">
                        <select name="f_type" class="native-select js-master-filter" aria-label="Filter Tipe Unit">
                            @foreach ($filterTypeOptions as $val => $label)
                                <option value="{{ $val }}" @selected($masterFilters['type'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <i class="fi fi-rr-angle-small-down select-arrow"></i>
                    </div>
                </div>
                <div class="filter-field">
                    <label>Kategori</label>
                    <div class="filter-select-wrapper">
                        <select name="f_category" class="native-select js-master-filter" aria-label="Filter Kategori">
                            @foreach ($filterCategoryOptions as $val => $label)
                                <option value="{{ $val }}" @selected($masterFilters['category'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <i class="fi fi-rr-angle-small-down select-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- PANE: Master Employees -->
    <div class="master-pane {{ $activePane === 'karyawan' ? 'active' : '' }}" data-pane="karyawan">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-npk">NPK</th>
                    <th class="col-name">Name</th>
                    <th class="col-group">Group</th>
                    <th class="col-position">Position</th>
                    <th class="col-division">Divisi</th>
                    <th class="col-worktime">Jam Kerja</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($employees as $e)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $e['update_url'] ?? '' }}">
                        <td class="col-no">{{ $e['no'] }}</td>
                        <td class="col-npk">{{ $e['npk'] }}</td>
                        <td class="col-name">{{ $e['name'] }}</td>
                        <td class="col-group">{{ $e['group'] }}</td>
                        <td class="col-position">{{ $e['position'] }}</td>
                        <td class="col-division">{{ $e['division'] ?? 'Operasional' }}</td>
                        <td class="col-worktime">{{ $e['work_time'] ?? '-' }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $e['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('karyawan', 'karyawan', 'fi fi-rr-users-alt'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Units -->
    <div class="master-pane {{ $activePane === 'unit' ? 'active' : '' }}" data-pane="unit">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Name</th>
                    <th class="col-code">Kode</th>
                    <th class="col-brand">Merk</th>
                    <th class="col-number">Plat</th>
                    <th class="col-type">Type</th>
                    <th class="col-category">Kategori</th>
                    <th class="col-opscheck">Cek Unit</th>
                    <th class="col-year">Tahun</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($units as $u)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $u['update_url'] ?? '' }}">
                        <td class="col-no">{{ $u['no'] }}</td>
                        <td class="col-name">{{ $u['name'] }}</td>
                        <td class="col-code">{{ $u['unit_number'] }}</td>
                        <td class="col-brand">{{ $u['brand'] }}</td>
                        <td class="col-number">{{ $u['plate'] }}</td>
                        <td class="col-type">{{ $u['type'] }}</td>
                        <td class="col-category">{{ $u['macro_category'] }}</td>
                        <td class="col-opscheck">{{ $u['in_operational_check'] }}</td>
                        <td class="col-year">{{ $u['year'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $u['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('unit', 'unit', 'fi fi-rr-truck-side'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Trucks -->
    <div class="master-pane {{ $activePane === 'truck' ? 'active' : '' }}" data-pane="truck">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Name</th>
                    <th class="col-plate">Plate Number</th>
                    <th class="col-desc">Description</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($trucks as $t)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $t['update_url'] ?? '' }}">
                        <td class="col-no">{{ $t['no'] }}</td>
                        <td class="col-name">{{ $t['name'] }}</td>
                        <td class="col-plate">{{ $t['plate'] }}</td>
                        <td class="col-desc">{{ $t['desc'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $t['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('truck', 'truck', 'fi fi-rr-truck-moving'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Inventory Items -->
    <div class="master-pane {{ $activePane === 'inventaris' ? 'active' : '' }}" data-pane="inventaris">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Name</th>
                    <th class="col-category">Category</th>
                    <th class="col-stock">Jumlah</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($inventories as $i)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $i['update_url'] ?? '' }}">
                        <td class="col-no">{{ $i['no'] }}</td>
                        <td class="col-name">{{ $i['name'] }}</td>
                        <td class="col-category">{{ $i['category'] }}</td>
                        <td class="col-stock">{{ $i['stock'] ?? 0 }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $i['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('inventaris', 'inventaris', 'fi fi-rr-box-open'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Safety Locations -->
    <div class="master-pane {{ $activePane === 'safety_lokasi' ? 'active' : '' }}" data-pane="safety_lokasi">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Nama Lokasi</th>
                    <th class="col-order">Urutan</th>
                    <th class="col-count">Jumlah Item</th>
                    <th class="col-status">Status</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($safetyLocations as $loc)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $loc['update_url'] ?? '' }}">
                        <td class="col-no">{{ $loc['no'] }}</td>
                        <td class="col-name">{{ $loc['name'] }}</td>
                        <td class="col-order">{{ $loc['sort_order'] }}</td>
                        <td class="col-count">{{ $loc['item_count'] }} item</td>
                        <td class="col-status">{{ $loc['is_active'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $loc['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('safety_lokasi', 'lokasi K3', 'fi fi-rr-marker'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Safety Items -->
    <div class="master-pane {{ $activePane === 'safety_item' ? 'active' : '' }}" data-pane="safety_item">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Nama Item</th>
                    <th class="col-qtyflag">Pakai QTY</th>
                    <th class="col-status">Status</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($safetyItems as $it)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $it['update_url'] ?? '' }}">
                        <td class="col-no">{{ $it['no'] }}</td>
                        <td class="col-name">{{ $it['name'] }}</td>
                        <td class="col-qtyflag">{{ $it['is_countable'] }}</td>
                        <td class="col-status">{{ $it['is_active'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $it['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('safety_item', 'item K3', 'fi fi-rr-checkbox'))
                @endforelse
            </table>
        </div>
    </div>

    @if (($activePane ?? 'karyawan') === 'karyawan' && method_exists($employees, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $employees, 'label' => 'karyawan'])
    @elseif (($activePane ?? 'karyawan') === 'unit' && method_exists($units, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $units, 'label' => 'unit'])
    @elseif (($activePane ?? 'karyawan') === 'truck' && method_exists($trucks, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $trucks, 'label' => 'truck'])
    @elseif (($activePane ?? 'karyawan') === 'inventaris' && method_exists($inventories, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $inventories, 'label' => 'inventaris'])
    @elseif (($activePane ?? 'karyawan') === 'safety_lokasi' && method_exists($safetyLocations, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $safetyLocations, 'label' => 'lokasi K3'])
    @elseif (($activePane ?? 'karyawan') === 'safety_item' && method_exists($safetyItems, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $safetyItems, 'label' => 'item K3'])
    @endif
@endcomponent

<div class="modal-overlay" id="masterFormModal" aria-hidden="true">
    <div class="modal-box modal-box--wide" role="dialog" aria-modal="true" aria-labelledby="masterFormTitle">
        <form method="POST" action="#" id="masterForm">
            @csrf
            <input type="hidden" name="_method" id="masterFormMethod" value="POST">
            <div class="kss-modal__header">
                <div class="kss-modal__icon">
                    <i class="fi fi-rr-database" id="masterFormIcon"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="masterFormTitle">Tambah Data Master</div>
                    <div class="kss-modal__subtitle" id="masterFormSubtitle">Lengkapi data sesuai kategori master yang aktif.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="kss-modal__grid" id="masterFormFields"></div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="submit" class="kss-modal__button kss-modal__button--primary" id="masterFormSubmit">
                    <i class="fi fi-rr-disk"></i> Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // MASTER DATA TAB SWITCHING (via submenu sidebar)
        const masterTabs = {
            karyawan:      { title: 'Data Karyawan',   search: 'Cari Karyawan',   add: 'Tambah Karyawan',   icon: 'fi fi-rr-user-add' },
            unit:          { title: 'Data Unit',       search: 'Cari Unit',       add: 'Tambah Unit',       icon: 'fi fi-rr-add' },
            truck:         { title: 'Data Truck',      search: 'Cari Truck',      add: 'Tambah Truck',      icon: 'fi fi-rr-add' },
            inventaris:    { title: 'Data Inventaris', search: 'Cari Inventaris', add: 'Tambah Inventaris', icon: 'fi fi-rr-add' },
            safety_lokasi: { title: 'Data Lokasi K3',  search: 'Cari Lokasi K3',  add: 'Tambah Lokasi',     icon: 'fi fi-rr-marker' },
            safety_item:   { title: 'Data Item K3',    search: 'Cari Item K3',    add: 'Tambah Item',       icon: 'fi fi-rr-checkbox' }
        };

        const employeeGroupOptions = ['-', 'Bengkel', 'Relief 1', 'Relief 2', 'A', 'B', 'C', 'D', 'OP7 A', 'OP7 B', 'OP7 C', 'OP7 D'];
        const employeePositionOptions = [
            '-',
            'Kepala Regu ( KARU )',
            'Wakil Karu',
            'Wakil Kepala Regu',
            'Checker',
            'Operator FL',
            'Driver',
            'Operator Exca/ WL',
            'Operator WL/ Exca',
            'Kasi Pemeliharaan & Peralatan',
            'Karu Peralatan',
            'Karu Pemeliharaan',
            'Mekanik',
            'Helper',
            'Rigger',
            'Operator OP.7',
            'Manager',
            'Kabag',
            'Kasi',
            'Karu',
            'Staf Ahli',
            'Staf',
            'Kepala Seksi',
            'Kepala Regu',
        ];
        const employeeDivisionOptions = ['Operasional', 'Pemeliharaan', 'Safety (Coming Soon)', 'Office'];
        const employeeWorkTimeOptions = ['Non Shift', 'Shift', 'Relief'];

        const masterSchemas = {
            karyawan: {
                label: 'Karyawan',
                icon: 'fi fi-rr-user',
                fields: [
                    { key: 'npk', label: 'NPK', placeholder: 'cth, 2000.1.010' },
                    { key: 'name', label: 'Nama Karyawan', placeholder: 'cth, Budi Santoso' },
                    { key: 'group', label: 'Group', type: 'select', options: employeeGroupOptions },
                    { key: 'position', label: 'Jabatan', type: 'select', options: employeePositionOptions },
                    { key: 'division', label: 'Divisi', type: 'select', options: employeeDivisionOptions },
                    { key: 'work_time', label: 'Jam Kerja', type: 'select', options: employeeWorkTimeOptions },
                ],
            },
            unit: {
                label: 'Unit',
                icon: 'fi fi-rr-truck-side',
                fields: [
                    // Nama unit otomatis = Tipe + Kode unit, jadi tidak ada input nama manual.
                    { key: 'unit_number', label: 'Kode Unit', placeholder: 'cth, TRL-01 / FL-01' },
                    { key: 'brand', label: 'Merk', placeholder: 'cth, NISSAN CWM 330' },
                    { key: 'plate_number', label: 'Nomor Plat', placeholder: 'cth, KTDE 8512' },
                    { key: 'type', label: 'Tipe Unit', type: 'select', options: ['Trailer', 'Tronton', 'Dump Truck', 'Minibus', 'Bus', 'Pickup', 'Forklift', 'Wheel Loader', 'Excavator'] },
                    { key: 'macro_category', label: 'Kategori', type: 'select', options: ['-', 'truck', 'bus', 'heavy'] },
                    { key: 'in_operational_check', label: 'Masuk Cek Unit Operasional', type: 'select', options: ['Ya', 'Tidak'] },
                    { key: 'year', label: 'Tahun Pembuatan', type: 'number', placeholder: 'cth, 2024' },
                ],
            },
            truck: {
                label: 'Truck',
                icon: 'fi fi-rr-truck-moving',
                fields: [
                    { key: 'name', label: 'Nama Truck', placeholder: 'cth, Hino 500' },
                    { key: 'plate', label: 'Nomor Polisi', placeholder: 'cth, B 9012 KSS' },
                    { key: 'desc', label: 'Deskripsi', type: 'textarea', placeholder: 'cth, Truk angkut material' },
                ],
            },
            inventaris: {
                label: 'Inventaris',
                icon: 'fi fi-rr-box-open',
                fields: [
                    { key: 'name', label: 'Nama Inventaris', placeholder: 'cth, Helm Safety' },
                    { key: 'category', label: 'Kategori', type: 'select', options: ['APD', 'Sparepart', 'Tools', 'Consumable'] },
                    { key: 'stock', label: 'Jumlah', type: 'number', placeholder: 'cth, 50' },
                ],
            },
            safety_lokasi: {
                label: 'Lokasi K3',
                icon: 'fi fi-rr-marker',
                fields: [
                    { key: 'name', label: 'Nama Lokasi', placeholder: 'cth, Shelter Shift Operasi' },
                    { key: 'sort_order', label: 'Urutan', type: 'number', placeholder: 'cth, 1' },
                    { key: 'is_active', label: 'Status', type: 'select', options: ['Aktif', 'Nonaktif'] },
                ],
            },
            safety_item: {
                label: 'Item K3',
                icon: 'fi fi-rr-checkbox',
                fields: [
                    { key: 'name', label: 'Nama Item', placeholder: 'cth, APAR' },
                    { key: 'is_countable', label: 'Pakai QTY?', type: 'select', options: ['Tidak', 'Ya'] },
                    { key: 'is_active', label: 'Status', type: 'select', options: ['Aktif', 'Nonaktif'] },
                ],
            },
        };

        const masterTitle    = document.getElementById('masterTitle');
        const masterCrumb     = document.getElementById('masterCrumb');
        const masterSearch    = document.getElementById('masterSearch');
        const masterSearchForm = document.getElementById('masterSearchForm');
        const masterPaneInput = document.getElementById('masterPaneInput');
        const masterAddText   = document.getElementById('masterAddText');
        const masterAddIcon   = document.getElementById('masterAddIcon');
        const masterAddBtn    = document.getElementById('masterAddBtn');
        const masterPanes     = document.querySelectorAll('.master-pane');
        const masterMenuItems = document.querySelectorAll('.sidebar__submenu-item[data-pane]');
        const masterFormModal = document.getElementById('masterFormModal');
        const masterFormTitle = document.getElementById('masterFormTitle');
        const masterFormSubtitle = document.getElementById('masterFormSubtitle');
        const masterFormIcon = document.getElementById('masterFormIcon');
        const masterFormFields = document.getElementById('masterFormFields');
        const masterFormSubmit = document.getElementById('masterFormSubmit');
        const masterForm = document.getElementById('masterForm');
        const masterFormMethod = document.getElementById('masterFormMethod');
        const masterFilterBtn = document.getElementById('masterFilterBtn');
        const masterFilterPanel = document.getElementById('masterFilterPanel');
        const masterFilterGroups = document.querySelectorAll('.master-filter-group[data-filter-pane]');
        const masterFilterPanes = ['karyawan', 'unit'];
        const masterActions = @json($masterActions);
        let activeMasterPane = @json($activePane ?? 'karyawan');
        let masterSearchTimer = null;
        let lastSubmittedSearch = masterSearch ? masterSearch.value : '';
        let lastSubmittedPane = masterPaneInput ? masterPaneInput.value : activeMasterPane;

        function switchMasterPane(pane) {
            const cfg = masterTabs[pane];
            if (!cfg) return;
            activeMasterPane = pane;
            masterPanes.forEach(p => p.classList.toggle('active', p.getAttribute('data-pane') === pane));
            masterMenuItems.forEach(m => m.classList.toggle('active', m.getAttribute('data-pane') === pane));
            if (masterTitle)   masterTitle.textContent = cfg.title;
            if (masterCrumb)   masterCrumb.textContent = cfg.title;
            if (masterSearch)  masterSearch.placeholder = cfg.search;
            if (masterPaneInput) masterPaneInput.value = pane;
            if (masterAddText) masterAddText.textContent = cfg.add;
            if (masterAddIcon) masterAddIcon.className = cfg.icon;
            syncMasterFilters(pane);
        }

        // Tampilkan tombol Filter hanya untuk pane yang punya filter; tampilkan
        // grup filter milik pane aktif & nonaktifkan select pane lain agar tidak
        // ikut terkirim ke URL.
        function syncMasterFilters(pane) {
            const hasFilters = masterFilterPanes.includes(pane);
            if (masterFilterBtn) masterFilterBtn.classList.toggle('is-hidden', !hasFilters);
            if (!hasFilters && masterFilterPanel) {
                masterFilterPanel.classList.add('collapsed');
                masterFilterBtn?.classList.remove('btn-tool--active');
            }
            masterFilterGroups.forEach(function (group) {
                const isActive = group.getAttribute('data-filter-pane') === pane;
                group.style.display = isActive ? 'flex' : 'none';
                group.querySelectorAll('select').forEach(function (select) {
                    select.disabled = !isActive;
                });
            });
        }

        function submitMasterFilter() {
            if (!masterSearchForm) return;
            window.clearTimeout(masterSearchTimer);
            if (typeof masterSearchForm.requestSubmit === 'function') {
                masterSearchForm.requestSubmit();
            } else {
                masterSearchForm.submit();
            }
        }

        function scheduleMasterSearchSubmit(delay = null) {
            if (!masterSearchForm || !masterSearch) return;

            window.clearTimeout(masterSearchTimer);
            const debounceMs = delay ?? Number(masterSearch.dataset.searchDebounce || 650);

            masterSearchTimer = window.setTimeout(function () {
                const currentSearch = masterSearch.value;
                const currentPane = masterPaneInput ? masterPaneInput.value : activeMasterPane;
                if (currentSearch === lastSubmittedSearch && currentPane === lastSubmittedPane) return;

                lastSubmittedSearch = currentSearch;
                lastSubmittedPane = currentPane;
                if (typeof masterSearchForm.requestSubmit === 'function') {
                    masterSearchForm.requestSubmit();
                } else {
                    masterSearchForm.submit();
                }
            }, debounceMs);
        }

        function readMasterRow(row, pane) {
            const text = selector => row.querySelector(selector)?.textContent.trim() || '';
            if (pane === 'karyawan') {
                const group = text('.col-group');

                return {
                    npk: text('.col-npk') === '-' ? '' : text('.col-npk'),
                    name: text('.col-name'),
                    group: group === 'Kantor' || group === '-' ? '-' : group.replace(/^Regu\s+/i, ''),
                    position: text('.col-position') === '-' ? '' : text('.col-position'),
                    division: text('.col-division') || 'Operasional',
                    work_time: text('.col-worktime') === '-' ? 'Non Shift' : text('.col-worktime'),
                };
            }
            if (pane === 'unit') {
                const category = text('.col-category');
                return {
                    name: text('.col-name'),
                    unit_number: text('.col-code') === '-' ? '' : text('.col-code'),
                    brand: text('.col-brand') === '-' ? '' : text('.col-brand'),
                    plate_number: text('.col-number') === '-' ? '' : text('.col-number'),
                    type: text('.col-type'),
                    macro_category: category === 'Truck' ? 'truck' : (category === 'Bus' ? 'bus' : (category === 'Heavy' ? 'heavy' : '-')),
                    in_operational_check: text('.col-opscheck') === 'Ya' ? 'Ya' : 'Tidak',
                    year: text('.col-year') === '-' ? '' : text('.col-year'),
                };
            }
            if (pane === 'truck') {
                return { name: text('.col-name'), plate: text('.col-plate'), desc: text('.col-desc') };
            }
            if (pane === 'safety_lokasi') {
                return {
                    name: text('.col-name'),
                    sort_order: text('.col-order'),
                    is_active: text('.col-status') || 'Aktif',
                };
            }
            if (pane === 'safety_item') {
                return {
                    name: text('.col-name'),
                    is_countable: text('.col-qtyflag') || 'Tidak',
                    is_active: text('.col-status') || 'Aktif',
                };
            }
            return { name: text('.col-name'), category: text('.col-category'), stock: text('.col-stock') };
        }

        function addField(field, value, index) {
            const wrapper = document.createElement('div');
            wrapper.className = 'kss-modal__field';
            if (field.type === 'textarea') wrapper.classList.add('kss-modal__field--full');

            const label = document.createElement('label');
            label.setAttribute('for', `masterField_${field.key}`);
            label.textContent = field.label;
            wrapper.appendChild(label);

            let control;
            if (field.type === 'select') {
                const selectWrapper = document.createElement('div');
                selectWrapper.className = 'kss-modal__select-wrapper';

                control = document.createElement('select');
                control.className = 'kss-modal__native-select';
                field.options.forEach(optionText => {
                    const option = document.createElement('option');
                    option.textContent = optionText;
                    option.value = optionText;
                    control.appendChild(option);
                });
                selectWrapper.appendChild(control);

                const icon = document.createElement('i');
                icon.className = 'fi fi-rr-angle-small-down kss-modal__select-icon';
                selectWrapper.appendChild(icon);

                control.id = `masterField_${field.key}`;
                control.name = field.key;
                if (value && Array.from(control.options).some(option => option.value === value)) {
                    control.value = value;
                } else {
                    control.selectedIndex = 0;
                }
                if (index === 0) control.dataset.modalFocus = 'true';
                wrapper.appendChild(selectWrapper);
                masterFormFields.appendChild(wrapper);
                return;
            } else if (field.type === 'textarea') {
                control = document.createElement('textarea');
                control.className = 'kss-modal__textarea';
            } else {
                control = document.createElement('input');
                control.type = field.type === 'number' ? 'number' : 'text';
                control.className = 'kss-modal__input';
                if (field.type === 'number') {
                    control.min = '0';
                    control.step = '1';
                    control.inputMode = 'numeric';
                }
            }

            control.id = `masterField_${field.key}`;
            control.name = field.key;
            control.value = value || '';
            control.placeholder = field.placeholder || '';
            if (index === 0) control.dataset.modalFocus = 'true';
            wrapper.appendChild(control);
            masterFormFields.appendChild(wrapper);
        }

        function openMasterForm(mode, pane, values = {}) {
            const schema = masterSchemas[pane];
            if (!schema) return;

            masterFormTitle.textContent = `${mode === 'edit' ? 'Edit' : 'Tambah'} ${schema.label}`;
            masterFormSubtitle.textContent = mode === 'edit'
                ? `Perbarui informasi ${schema.label.toLowerCase()} yang dipilih.`
                : `Masukkan detail ${schema.label.toLowerCase()} baru ke master data.`;
            masterFormIcon.className = schema.icon;
            masterFormSubmit.innerHTML = `<i class="fi fi-rr-disk"></i> ${mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Data'}`;
            if (masterForm) masterForm.action = mode === 'edit' && values.updateUrl ? values.updateUrl : (masterActions[pane]?.store || '#');
            if (masterFormMethod) masterFormMethod.value = mode === 'edit' ? 'PUT' : 'POST';

            masterFormFields.replaceChildren();
            schema.fields.forEach((field, index) => addField(field, values[field.key], index));
            window.KssAdminModal.initSelects(masterFormModal);
            window.KssAdminModal.syncSelects(masterFormModal);
            window.KssAdminModal.open(masterFormModal);
        }

        masterMenuItems.forEach(function (item) {
            item.addEventListener('click', function (e) {
                const pane = item.getAttribute('data-pane');
                if (!pane || !masterTabs[pane]) return;
                window.clearTimeout(masterSearchTimer);
            });
        });

        const initialPane = new URLSearchParams(window.location.search).get('pane') || @json($activePane ?? 'karyawan');
        switchMasterPane(initialPane);

        masterSearch?.addEventListener('input', function () {
            scheduleMasterSearchSubmit();
        });

        masterSearch?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            scheduleMasterSearchSubmit(0);
        });

        masterSearchForm?.addEventListener('submit', function () {
            window.clearTimeout(masterSearchTimer);
            if (masterSearch) {
                masterSearch.value = masterSearch.value.trim();
            }
        });

        masterAddBtn?.addEventListener('click', function () {
            openMasterForm('add', activeMasterPane);
        });

        masterFilterBtn?.addEventListener('click', function () {
            if (!masterFilterPanel) return;
            const isOpen = !masterFilterPanel.classList.toggle('collapsed');
            masterFilterBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            masterFilterBtn.classList.toggle('btn-tool--active', isOpen);
        });

        document.querySelectorAll('.js-master-filter').forEach(function (select) {
            select.addEventListener('change', submitMasterFilter);
        });

        // Custom dropdown bergaya Arsip Laporan untuk select filter.
        document.querySelectorAll('.master-filter-panel .filter-select-wrapper').forEach(function (wrapper) {
            const select = wrapper.querySelector('select');
            if (!select) return;
            select.style.display = 'none';

            const trigger = document.createElement('div');
            trigger.className = 'filter-input filter-select-trigger';
            const label = document.createElement('span');
            label.textContent = select.options[select.selectedIndex].text;
            trigger.appendChild(label);
            wrapper.insertBefore(trigger, select.nextSibling);

            const list = document.createElement('div');
            list.className = 'filter-select-options';
            Array.from(select.options).forEach(function (opt, i) {
                const item = document.createElement('div');
                item.className = 'filter-select-option';
                item.textContent = opt.text;
                if (i === select.selectedIndex) item.classList.add('selected');
                item.addEventListener('click', function (e) {
                    e.stopPropagation();
                    select.value = opt.value;
                    label.textContent = opt.text;
                    list.querySelectorAll('.filter-select-option').forEach(o => o.classList.remove('selected'));
                    item.classList.add('selected');
                    list.classList.remove('open');
                    trigger.classList.remove('focus-active');
                    select.dispatchEvent(new Event('change'));
                });
                list.appendChild(item);
            });
            wrapper.appendChild(list);

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.filter-select-options.open').forEach(function (c) {
                    if (c !== list) {
                        c.classList.remove('open');
                        const t = c.parentElement.querySelector('.filter-select-trigger');
                        if (t) t.classList.remove('focus-active');
                    }
                });
                list.classList.toggle('open');
                trigger.classList.toggle('focus-active');
            });
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.filter-select-options.open').forEach(c => c.classList.remove('open'));
            document.querySelectorAll('.filter-select-trigger.focus-active').forEach(t => t.classList.remove('focus-active'));
        });

        document.querySelectorAll('.js-master-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                const pane = button.closest('.master-pane')?.getAttribute('data-pane') || activeMasterPane;
                const row = button.closest('tr');
                openMasterForm('edit', pane, { ...readMasterRow(row, pane), updateUrl: row?.dataset.updateUrl || '' });
            });
        });

        document.querySelectorAll('.js-master-delete').forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const pane = button.closest('.master-pane')?.getAttribute('data-pane') || activeMasterPane;
                const cfg = masterTabs[pane];
                const rowData = readMasterRow(button.closest('tr'), pane);
                button.dataset.confirmTone = 'danger';
                button.dataset.confirmTitle = `Hapus ${cfg.title}?`;
                button.dataset.confirmSubtitle = 'Data master akan dihapus dari daftar.';
                button.dataset.confirmMessage = 'Pastikan data ini sudah tidak dipakai pada laporan atau referensi operasional.';
                button.dataset.confirmSummary = rowData.name || rowData.npk || cfg.title;
                button.dataset.confirmLabel = 'Hapus Data';
                button.dataset.confirmIcon = 'fi fi-rr-trash';
                button.dataset.confirmSubmit = 'true';
                window.KssAdminModal.confirm(button);
            });
        });
    });
</script>
@endpush
