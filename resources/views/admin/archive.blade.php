@extends('admin.layouts.app')

@section('title', 'KSS Admin — Arsip Laporan')
@section('active', 'archive')

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

    .section-card__title {
        font-size: 16px;
        font-weight: 600;
        color: var(--black);
    }

    /* Shift Badges */
    .shift {
        display: flex;
        padding: 3px 6px;
        justify-content: center;
        align-items: center;
        gap: 4px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 400;
    }

    .icon-shift { display: inline-flex; align-items: center; justify-content: center; }
    .icon-shift i { font-size: 8px; line-height: 1; }

    .shift.pagi { background-color: var(--cyan-main-10); color: var(--cyan-main); }
    .shift.sore { background-color: var(--orange-main-10); color: var(--orange-main); }
    .shift.malam { background-color: var(--blue-main-10); color: var(--blue-main); }
    .shift.nonshift { background-color: var(--blue-main-5); color: var(--black-secondary); }

    .division-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
    }
    .division-badge i { position: relative; top: 1px; font-size: 11px; }
    .division-badge.operasional { color: var(--blue-main); background-color: var(--blue-main-10); }
    .division-badge.pemeliharaan { color: var(--orange-main); background-color: var(--orange-main-10); }
    .division-badge.safety { color: var(--success); background-color: var(--success-10); }

    /* =============================================
       TOOLBAR & FILTERS
       ============================================= */
    .archive-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

    .archive-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        border: 1px solid var(--smooth-border);
        border-radius: 50px;
        background-color: var(--main-bg);
        flex: 1 1 380px;
        max-width: 460px;
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

    .archive-toolbar__actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    .tool-select,
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
        transition: border-color 0.2s ease;
    }

    .tool-select:focus,
    .filter-input:focus { border-color: var(--blue-main); }

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
    .btn-tool:hover { background-color: var(--blue-main-5); border-color: var(--blue-main-25); color: var(--blue-main); }

    .btn-tool--primary { background-color: var(--blue-main); border-color: var(--blue-main); color: #fff; }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }

    .btn-tool--active { background-color: var(--blue-main-10); border-color: var(--blue-main); color: var(--blue-main); }

    .archive-filters {
        display: flex;
        align-items: flex-end;
        justify-content: flex-start;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 16px;
        animation: filterSlideDown 0.3s ease;
    }

    .archive-filters.collapsed { display: none; }

    @keyframes filterSlideDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .filter-field { display: flex; flex: 1 1 160px; max-width: 200px; flex-direction: column; gap: 4px; }
    .filter-field label { font-size: 10px; font-weight: 500; color: var(--black-secondary); }
    .filter-field .filter-input {
        width: 100%;
        min-width: 0;
        height: 36px;
        display: flex;
        align-items: center;
    }

    .archive-filters .kss-date-trigger.filter-input {
        min-height: 36px;
        padding: 0 12px;
        justify-content: flex-start;
        border-radius: 8px;
        font-size: 12px;
    }

    .archive-filters .kss-date-trigger.filter-input .kss-date-trigger__main {
        width: 100%;
    }

    .archive-filters .kss-date-trigger.filter-input .kss-date-trigger__main i {
        top: 0;
        color: var(--blue-main);
        font-size: 13px;
    }

    .btn-reset {
        padding: 8px 16px;
        border: 1px solid var(--red-main);
        border-radius: 8px;
        background-color: transparent;
        color: var(--red-main);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s ease;
    }
    .btn-reset:hover { background-color: var(--red-main-10); }

    /* Toolbar right cluster + result count badge (match manajer) */
    .archive-toolbar__right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .archive-count {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--blue-main);
        background-color: var(--blue-main-10);
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
    }

    /* Custom dropdown */
    .filter-select-wrapper { position: relative; min-width: 150px; }
    /* Panel selects sit in a column field: size to content so the arrow stays inside the box */
    .archive-filters .filter-select-wrapper { width: 100%; min-width: 0; flex: 0 0 auto; }
    .toolbar-sort-wrapper { min-width: 120px; }

    .filter-select-trigger { display: flex; align-items: center; padding-right: 34px; cursor: pointer; }
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
        max-height: 200px;
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

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* =============================================
       RESPONSIVE (toolbar + filters)
       ============================================= */
    @media (max-width: 920px) {
        .archive-toolbar { align-items: stretch; }

        .search-box {
            flex-basis: 100%;
            max-width: none;
            width: 100%;
        }

        .archive-toolbar__actions,
        .archive-toolbar__right {
            width: 100%;
            justify-content: flex-start;
        }

        /* Only the toolbar (row) wrappers may flex-grow; panel wrappers stay content-sized */
        .archive-toolbar__actions .filter-select-wrapper,
        .archive-toolbar__actions .toolbar-sort-wrapper {
            min-width: 140px;
            flex: 1 1 140px;
        }
    }

    @media (max-width: 560px) {
        .archive-toolbar__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: stretch;
        }

        .archive-toolbar__actions .btn-reset {
            grid-column: 1 / -1;
            text-align: center;
        }

        .btn-tool,
        .btn-reset,
        .filter-input,
        .filter-select-trigger {
            width: 100%;
            justify-content: center;
        }

        .archive-filters {
            width: 100%;
            justify-content: stretch;
        }

        .filter-field {
            width: 100%;
        }
    }

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

    .table-responsive-wrapper table { min-width: 1100px; width: 100%; }

    .thead {
        background-color: var(--blue-main-5);
        border-radius: 6px;
        justify-content: flex-start !important;
    }

    .thead th {
        display: flex;
        padding: 10px 6px;
        align-items: center;
        flex: 0 0 auto;
        font-size: 12px;
        font-weight: 500;
        color: var(--black-secondary);
    }

    .thead th.nomor { width: 50px; flex: none; justify-content: center; padding: 10px 0; }
    .thead th.column-1 { min-width: 135px; }
    .thead th:nth-child(2) { width: 230px; min-width: 230px; }
    .thead th:nth-child(3) { width: 135px; min-width: 135px; }
    .thead th:nth-child(4) { width: 135px; min-width: 135px; }
    .thead th:nth-child(5) { width: 105px; min-width: 105px; }
    .thead th:nth-child(6) { width: 120px; min-width: 120px; }
    .thead th:nth-child(7) { width: 125px; min-width: 125px; }
    .thead th.aksi { width: 225px; min-width: 225px; }

    .tbody {
        border-bottom: 1px solid var(--smooth-border);
        transition: background-color 0.15s ease-in-out;
        justify-content: flex-start !important;
    }
    .tbody:hover { background-color: var(--blue-main-3); }

    .tbody td {
        display: flex;
        align-items: center;
        padding: 10px 6px;
        flex: 0 0 auto;
        font-size: 12px;
        font-weight: 500;
        color: var(--black);
    }

    .tbody td.nomor { width: 50px; flex: none; justify-content: center; padding: 10px 0; color: var(--black-secondary); }

    .tbody td.column-2 {
        width: 230px;
        min-width: 230px;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        gap: 4px;
    }

    .archive-doc-title {
        line-height: 1.25;
        font-weight: 600;
        color: var(--black);
        white-space: nowrap;
    }

    .archive-doc-id {
        line-height: 1.25;
    }

    .tbody td.column-1 { min-width: 135px; }

    .tbody td:nth-child(3) { width: 135px; min-width: 135px; }
    .tbody td:nth-child(4) { width: 135px; min-width: 135px; }
    .tbody td:nth-child(5) { width: 105px; min-width: 105px; }
    .tbody td:nth-child(6) { width: 120px; min-width: 120px; }
    .tbody td:nth-child(7) { width: 125px; min-width: 125px; }

    .tbody td.column-3 { flex-direction: column; align-items: flex-start; gap: 6px; }

    .tbody td.aksi { gap: 6px; flex-wrap: nowrap; width: 225px; min-width: 225px; }

    .report-group {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 20px;
        background-color: var(--white);
        box-shadow: 0 0 1px 0 var(--muted);
        white-space: nowrap;
    }

    .letter-group {
        display: flex;
        width: 16px;
        height: 16px;
        padding: 5px;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-radius: 50px;
        font-size: 8px;
        font-weight: 600;
        color: var(--success);
        background-color: var(--success-10);
        flex-shrink: 0;
    }

    td.aksi form { margin: 0; }

    td.aksi a.btn-act { text-decoration: none; }

    /* Status badges */
    .status {
        display: flex;
        padding: 3px 8px;
        align-items: center;
        gap: 5px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 500;
    }

    .status-dot { width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; flex-shrink: 0; }
    .status.approve { border: 1px solid var(--success);     color: var(--success);     background-color: var(--success-10); }
    .status.confirm { border: 1px solid var(--cyan-main);   color: var(--cyan-main);   background-color: var(--cyan-main-10); }
    .status.submit  { border: 1px solid var(--orange-main); color: var(--orange-main); background-color: var(--orange-main-10); }
    .status.archive { border: 1px solid var(--blue-main);   color: var(--blue-main);   background-color: var(--blue-main-10); }

    /* Action buttons */
    td.aksi .btn-act {
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

    td.aksi .btn-act i { position: relative; top: 1px; }
    td.aksi .btn-act.download { background-color: var(--blue-main); }
    td.aksi .btn-act.download:hover { background-color: var(--blue-hover); transform: translateY(-1px); }
    td.aksi .btn-act.view { background-color: var(--orange-main); width: 30px; padding: 7px; }
    td.aksi .btn-act.view:hover { background-color: var(--orange-hover); transform: translateY(-1px); }
    td.aksi .btn-act.delete { background-color: var(--red-main); width: 30px; padding: 7px; }
    td.aksi .btn-act.delete:hover { background-color: var(--red-hover); transform: translateY(-1px); }

    /* =============================================
       LIVE SEARCH + DROPDOWN SARAN (selaras Manajer)
       ============================================= */
    .archive-search-box { position: relative; padding-right: 44px; }

    .archive-search-box input[type="search"]::-webkit-search-cancel-button,
    .archive-search-box input[type="search"]::-webkit-search-decoration {
        display: none;
        -webkit-appearance: none;
    }

    .archive-search-clear {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border: none;
        border-radius: 50%;
        color: var(--blue-main);
        background-color: var(--blue-main-10);
        cursor: pointer;
        transition: .2s ease-out;
    }
    .archive-search-clear:hover { background-color: var(--blue-main-25); }

    .archive-suggest-dropdown {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        z-index: 40;
        display: none;
        max-height: 360px;
        overflow-y: auto;
        padding: 8px;
        border: 1px solid var(--smooth-border);
        border-radius: 14px;
        background-color: var(--white);
        box-shadow: 0 18px 38px rgba(15, 23, 42, .12);
    }
    .archive-suggest-dropdown.show { display: block; }

    .archive-suggest-header,
    .archive-suggest-empty,
    .archive-suggest-loading {
        padding: 10px 12px;
        font-size: 11px;
        color: var(--muted);
    }
    .archive-suggest-header { font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }

    .archive-suggest-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
        width: 100%;
        padding: 10px 12px;
        border: none;
        border-radius: 10px;
        background: transparent;
        color: inherit;
        font-family: inherit;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
    }
    .archive-suggest-item:hover,
    .archive-suggest-item.is-active { background-color: var(--blue-main-10); }

    .archive-suggest-title {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
    }

    .archive-suggest-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        color: var(--muted);
        font-size: 10px;
    }

    .archive-suggest-chip {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 999px;
        color: var(--blue-main);
        background-color: var(--blue-main-10);
        font-weight: 600;
    }
</style>
@endpush

@section('content')
@php
    $stats = $stats ?? [
        ['label' => 'Laporan Hari ini',  'value' => '12',    'icon' => 'fi fi-sr-calendar', 'color' => 'green'],
        ['label' => 'Laporan Pending',   'value' => '8',     'icon' => 'fi fi-sr-document', 'color' => 'orange'],
        ['label' => 'Laporan Bulan ini', 'value' => '34',    'icon' => 'fi fi-sr-folder',   'color' => 'cyan'],
        ['label' => 'Total Laporan',     'value' => '1.252', 'icon' => 'fi fi-sr-book-alt', 'color' => 'blue'],
    ];

    $reports = $reports ?? collect([
        ['no' => 1, 'title' => 'Laporan Operasi Harian', 'id' => '#1', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'archive', 'status_label' => 'Diarsipkan'],
        ['no' => 2, 'title' => 'Laporan Operasi Harian', 'id' => '#2', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'archive', 'status_label' => 'Diarsipkan'],
        ['no' => 3, 'title' => 'Laporan Operasi Harian', 'id' => '#3', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'archive', 'status_label' => 'Diarsipkan'],
        ['no' => 4, 'title' => 'Laporan Operasi Harian', 'id' => '#4', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'archive', 'status_label' => 'Diarsipkan'],
        ['no' => 5, 'title' => 'Laporan Operasi Harian', 'id' => '#5', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'archive', 'status_label' => 'Diarsipkan'],
    ]);

    $selectedDivision = $selectedDivision ?? 'all';
    $selectedStatus = $selectedStatus ?? 'all';
    $hasPanelFilter = filled($selectedDate ?? '')
        || ! in_array($selectedGroup ?? 'ALL', ['ALL', ''], true)
        || ! in_array($selectedShift ?? 'all', ['all', ''], true)
        || ! in_array($selectedDivision, ['all', ''], true)
        || ! in_array($selectedStatus, ['all', ''], true);
    $hasActiveFilter = filled($archiveSearch ?? '')
        || $hasPanelFilter
        || ($sort ?? 'newest') !== 'newest';
    $archiveTotal = method_exists($reports, 'total') ? $reports->total() : $reports->count();
    $archiveFirstItem = method_exists($reports, 'firstItem') ? ($reports->firstItem() ?? 1) : 1;
    $archiveCountLabel = filled($archiveSearch ?? '') || $hasActiveFilter ? 'hasil' : 'laporan';
@endphp

<div class="page-header">
    <span class="page-title">Arsip Laporan</span>
    <span class="page-subtitle">Daftar seluruh laporan yang telah diserahkan, diterima, dan diarsipkan.</span>
</div>

<!-- Stats Row -->
<div class="stats-row">
    @foreach ($stats as $s)
        <div class="stat-card">
            <span class="stat-card__label">{{ $s['label'] }}</span>
            <div class="stat-card__row">
                <span class="stat-card__value">{{ $s['value'] }}</span>
                <span class="stat-card__icon stat-card__icon--{{ $s['color'] }}"><i class="{{ $s['icon'] }}"></i></span>
            </div>
        </div>
    @endforeach
</div>

<!-- Riwayat Laporan -->
@component('admin.layouts.card', ['title' => 'Riwayat Laporan'])
    <form method="GET" action="{{ route('admin.archive') }}" id="archiveFilterForm">
    <!-- Toolbar -->
    <div class="archive-toolbar">
        <div class="search-box archive-search-box">
            <span><i class="fi fi-rr-search"></i></span>
            <input
                type="search"
                id="archive-search-input"
                name="q"
                placeholder="Cari ID, divisi, tanggal, shift, regu, kapal, karyawan, atau isi laporan"
                value="{{ $archiveSearch ?? '' }}"
                data-initial-value="{{ $archiveSearch ?? '' }}"
                data-page-start="{{ $archiveFirstItem }}"
                data-suggest-url="{{ route('admin.archive.suggestions') }}"
                autocomplete="off"
                role="combobox"
                aria-expanded="false"
                aria-controls="archive-suggest-dropdown"
            >
            @if (filled($archiveSearch ?? ''))
                <a href="{{ route('admin.archive', request()->except(['q', 'page'])) }}" class="archive-search-clear" aria-label="Bersihkan pencarian">
                    <i class="fi fi-br-cross-small"></i>
                </a>
            @else
                <button type="button" id="archive-search-clear" class="archive-search-clear d-none" aria-label="Bersihkan pencarian">
                    <i class="fi fi-br-cross-small"></i>
                </button>
            @endif
            <div id="archive-suggest-dropdown" class="archive-suggest-dropdown" role="listbox" aria-label="Saran pencarian arsip laporan"></div>
        </div>
        <div class="archive-toolbar__right">
            <span id="archive-count" class="archive-count" data-total="{{ $archiveTotal }}" data-label="{{ $archiveCountLabel }}">
                <i class="fi fi-rr-folder-open"></i>
                <span>{{ $archiveTotal }} {{ $archiveCountLabel }}</span>
            </span>
            <div class="archive-toolbar__actions">
                <div class="filter-select-wrapper toolbar-sort-wrapper">
                    <select class="native-select" name="sort" data-autosubmit-filter>
                        <option value="newest" @selected(($sort ?? 'newest') === 'newest')>Terbaru</option>
                        <option value="oldest" @selected(($sort ?? 'newest') === 'oldest')>Terlama</option>
                    </select>
                    <i class="fi fi-rr-angle-small-down select-arrow"></i>
                </div>
                <button type="button" class="btn-tool {{ $hasPanelFilter ? 'btn-tool--active' : '' }}" id="btnFilter"><i class="fi fi-rr-filter"></i> Filter</button>
                @if ($hasActiveFilter)
                    <a href="{{ route('admin.archive') }}"
                            class="btn-reset"
                            data-confirm
                            data-confirm-redirect="{{ route('admin.archive') }}"
                            data-confirm-tone="warning"
                            data-confirm-title="Reset filter arsip?"
                            data-confirm-subtitle="Pilihan filter akan dikembalikan ke kondisi awal."
                            data-confirm-message="Pencarian dan filter tanggal, divisi, regu, shift, serta status akan dikosongkan."
                            data-confirm-label="Reset Filter"
                            data-confirm-icon="fi fi-rr-refresh">
                        Reset
                    </a>
                @endif
                <button type="button"
                        class="btn-tool btn-tool--primary"
                        data-confirm
                        data-confirm-tone="success"
                        data-confirm-title="Ekspor arsip laporan?"
                        data-confirm-subtitle="Data arsip akan disiapkan sebagai file unduhan."
                        data-confirm-message="Gunakan ekspor untuk mengambil daftar laporan sesuai filter yang sedang aktif."
                        data-confirm-summary="Format preview: Excel"
                        data-confirm-label="Ekspor Data"
                        data-confirm-icon="fi fi-rr-cloud-upload-alt">
                    <i class="fi fi-rr-cloud-upload-alt"></i> Ekspor
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="archive-filters {{ $hasPanelFilter ? '' : 'collapsed' }}" id="archiveFilters">
        <div class="filter-field">
            <label>Tanggal</label>
            <input type="hidden" name="tanggal" value="{{ $selectedDate ?? '' }}" data-kss-picker="date" data-trigger-class="filter-input" data-placeholder="Pilih tanggal" data-autosubmit-filter>
        </div>
        <div class="filter-field">
            <label>Divisi</label>
            <div class="filter-select-wrapper">
                <select class="native-select" name="divisi" data-autosubmit-filter>
                    <option value="all" @selected($selectedDivision === 'all')>Semua Divisi</option>
                    <option value="operasional" @selected($selectedDivision === 'operasional')>Operasional</option>
                    <option value="pemeliharaan" @selected($selectedDivision === 'pemeliharaan')>Pemeliharaan</option>
                    <option value="safety" @selected($selectedDivision === 'safety')>Safety (Coming Soon)</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
        </div>
        <div class="filter-field">
            <label>Regu</label>
            <div class="filter-select-wrapper">
                <select class="native-select" name="regu" data-autosubmit-filter>
                    <option value="all" @selected(($selectedGroup ?? 'ALL') === 'ALL')>Semua Regu</option>
                    <option value="A" @selected(($selectedGroup ?? 'ALL') === 'A')>Regu A</option>
                    <option value="B" @selected(($selectedGroup ?? 'ALL') === 'B')>Regu B</option>
                    <option value="C" @selected(($selectedGroup ?? 'ALL') === 'C')>Regu C</option>
                    <option value="D" @selected(($selectedGroup ?? 'ALL') === 'D')>Regu D</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
        </div>
        <div class="filter-field">
            <label>Shift</label>
            <div class="filter-select-wrapper">
                <select class="native-select" name="shift" data-autosubmit-filter>
                    <option value="all" @selected(($selectedShift ?? 'all') === 'all')>Semua Shift</option>
                    <option value="pagi" @selected(($selectedShift ?? 'all') === 'pagi')>Shift Pagi</option>
                    <option value="sore" @selected(($selectedShift ?? 'all') === 'sore')>Shift Sore</option>
                    <option value="malam" @selected(($selectedShift ?? 'all') === 'malam')>Shift Malam</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
        </div>
        <div class="filter-field">
            <label>Status</label>
            <div class="filter-select-wrapper">
                <select class="native-select" name="status" data-autosubmit-filter>
                    <option value="all" @selected($selectedStatus === 'all')>Semua Status</option>
                    <option value="submitted" @selected($selectedStatus === \App\Enums\ReportStatus::Submitted->value)>Diserahkan</option>
                    <option value="acknowledged" @selected($selectedStatus === \App\Enums\ReportStatus::Acknowledged->value)>Diterima</option>
                    <option value="approved" @selected($selectedStatus === \App\Enums\ReportStatus::Approved->value)>Diarsipkan</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
        </div>
    </div>
    </form>

    <!-- Table -->
    <div class="table-responsive-wrapper">
        <table>
            <tr class="thead d-flex justify-content-between align-items-center">
                <th class="nomor">No</th>
                <th class="column-1">Info Dokumen</th>
                <th class="column-1">Tanggal Laporan</th>
                <th>Divisi</th>
                <th>Regu</th>
                <th>Shift</th>
                <th>Status</th>
                <th class="aksi">Aksi</th>
            </tr>

            @forelse ($reports as $r)
                @php
                    $reguName = trim((string) ($r['regu'] ?? '-'));
                    $reguCodeSource = trim(preg_replace('/^(regu|group)\s*/i', '', $reguName));
                    $reguCode = $reguCodeSource !== '' ? strtoupper(substr($reguCodeSource, 0, 1)) : '-';
                @endphp
                <tr class="tbody d-flex justify-content-between align-items-center" data-history-row data-history-search="{{ $r['search'] ?? '' }}">
                    <td class="nomor">{{ $r['no'] }}</td>
                    <td class="column-2">
                        <span class="archive-doc-title">{{ $r['title'] }}</span>
                        <span class="archive-doc-id fsize-10 fw-400 text-muted-custom">ID: {{ $r['id'] }}</span>
                    </td>
                    <td class="column-1">{{ $r['date'] }}</td>
                    <td>
                        <span class="division-badge {{ $r['division_class'] ?? 'operasional' }}">
                            <i class="{{ $r['division_icon'] ?? 'fi fi-rr-ship' }}"></i>
                            {{ $r['division_label'] ?? 'Operasional' }}
                        </span>
                    </td>
                    <td>
                        <div class="report-group">
                            <div class="letter-group">{{ $reguCode }}</div>
                            <span class="text fsize-10 fw-600">{{ $reguName }}</span>
                        </div>
                    </td>
                    <td class="column-3">
                        <div class="shift {{ $r['shift'] }}">
                            <span class="icon-shift"><i class="{{ $r['shift_icon'] ?? 'fi fi-rr-sunrise' }}"></i></span>
                            <span class="text">{{ $r['shift_label'] }}</span>
                        </div>
                    </td>
                    <td class="column-3">
                        <div class="status {{ $r['status'] }}">
                            <span class="status-dot"></span>
                            <span class="text">{{ $r['status_label'] }}</span>
                        </div>
                    </td>
                    <td class="aksi">
                        <button type="button"
                                class="btn-act download"
                                title="Download"
                                data-download-url="{{ $r['download_url'] ?? '#' }}">
                            <i class="fi fi-rr-download"></i> Download
                        </button>
                        <a href="{{ $r['view_url'] ?? '#' }}" class="btn-act view" title="Lihat"><i class="fi fi-rr-eye"></i></a>
                        <form method="POST" action="{{ $r['destroy_url'] ?? '#' }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn-act delete"
                                    title="Hapus"
                                    data-confirm
                                    data-confirm-submit="true"
                                    data-confirm-tone="danger"
                                    data-confirm-title="Hapus arsip laporan?"
                                    data-confirm-subtitle="Laporan akan dihapus dari daftar arsip."
                                    data-confirm-message="Tindakan hapus arsip sebaiknya hanya dilakukan jika dokumen tidak lagi valid."
                                    data-confirm-summary="{{ $r['summary'] ?? ($r['title'].' '.$r['id'].' - '.$r['date']) }}"
                                    data-confirm-label="Hapus Arsip"
                                    data-confirm-icon="fi fi-rr-trash">
                                <i class="fi fi-rr-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="tbody d-flex justify-content-center align-items-center">
                    <td class="column-1 text-muted-custom" style="min-width: 100%; justify-content: center;">Belum ada laporan arsip.</td>
                </tr>
            @endforelse

            @if ((method_exists($reports, 'count') ? $reports->count() : count($reports)) > 0)
                <tr id="archive-search-empty" class="tbody d-flex justify-content-center align-items-center d-none">
                    <td class="column-1 text-muted-custom" style="min-width: 100%; justify-content: center;">
                        Laporan tidak ditemukan di halaman ini. Tekan Enter untuk mencari ke seluruh arsip.
                    </td>
                </tr>
            @endif
        </table>
    </div>
    @include('admin.layouts.pagination', ['paginator' => $reports, 'label' => 'laporan'])
@endcomponent
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // FILTER TOGGLE
        const btnFilter = document.getElementById('btnFilter');
        const archiveFilters = document.getElementById('archiveFilters');
        if (btnFilter && archiveFilters) {
            btnFilter.addEventListener('click', function () {
                const isOpen = !archiveFilters.classList.toggle('collapsed');
                btnFilter.classList.toggle('btn-tool--active', isOpen);
            });
        }

        // CUSTOM DROPDOWN
        document.querySelectorAll('.filter-select-wrapper').forEach(function (wrapper) {
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
                    select.dispatchEvent(new Event('change'));
                    label.textContent = opt.text;
                    list.querySelectorAll('.filter-select-option').forEach(o => o.classList.remove('selected'));
                    item.classList.add('selected');
                    list.classList.remove('open');
                    trigger.classList.remove('focus-active');
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

        // LIVE SEARCH + DROPDOWN SARAN (selaras dengan halaman Manajer)
        (function () {
            const searchForm = document.getElementById('archiveFilterForm');
            const input = document.getElementById('archive-search-input');
            const clearButton = document.getElementById('archive-search-clear');
            const dropdown = document.getElementById('archive-suggest-dropdown');
            const countBadge = document.getElementById('archive-count');
            const countText = countBadge?.querySelector('span');
            const emptyRow = document.getElementById('archive-search-empty');
            const rows = Array.from(document.querySelectorAll('[data-history-row]'));
            const pageStart = Number(input?.dataset.pageStart || 1);
            const serverTotal = Number(countBadge?.dataset.total || rows.length);
            const serverLabel = countBadge?.dataset.label || 'laporan';
            const suggestUrl = input?.dataset.suggestUrl || '';
            const initialKeyword = input?.dataset.initialValue || '';
            const minSuggestLength = 2;
            let timer = null;
            let controller = null;
            let items = [];
            let activeIndex = -1;

            if (!input) return;

            function normalize(value) {
                return String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[̀-ͯ]/g, '')
                    .trim();
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function updateRows() {
                const keyword = normalize(input.value);
                const initial = normalize(initialKeyword);
                let visible = 0;

                rows.forEach(row => {
                    const target = normalize(row.dataset.historySearch || row.textContent);
                    const match = keyword === '' || target.includes(keyword);
                    row.classList.toggle('d-none', !match);

                    if (match) {
                        visible += 1;
                        const numberCell = row.querySelector('.nomor');
                        if (numberCell) numberCell.textContent = pageStart + visible - 1;
                    }
                });

                if (emptyRow) {
                    emptyRow.classList.toggle('d-none', keyword === '' || visible > 0);
                }

                if (clearButton) {
                    clearButton.classList.toggle('d-none', keyword === '');
                }

                if (countText) {
                    countText.textContent = keyword === '' || keyword === initial
                        ? `${serverTotal} ${serverLabel}`
                        : `${visible} dari ${rows.length} di halaman ini`;
                }
            }

            function closeDropdown() {
                if (timer) window.clearTimeout(timer);
                if (controller) controller.abort();
                timer = null;
                controller = null;
                items = [];
                activeIndex = -1;
                dropdown?.classList.remove('show');
                if (dropdown) dropdown.innerHTML = '';
                input.setAttribute('aria-expanded', 'false');
            }

            function showDropdown(html) {
                if (!dropdown) return;
                dropdown.innerHTML = html;
                dropdown.classList.add('show');
                input.setAttribute('aria-expanded', 'true');
            }

            function renderItems(payload) {
                items = Array.isArray(payload?.items) ? payload.items : [];
                activeIndex = items.length > 0 ? 0 : -1;

                if (!items.length) {
                    showDropdown('<div class="archive-suggest-empty">Tidak ada arsip yang cocok.</div>');
                    return;
                }

                const header = `<div class="archive-suggest-header">${items.length} saran teratas</div>`;
                const list = items.map((item, index) => `
                    <button type="button" class="archive-suggest-item${index === 0 ? ' is-active' : ''}" data-index="${index}">
                        <div class="archive-suggest-title">
                            <span>${escapeHtml(item.title)} &middot; ${escapeHtml(item.report_date)}</span>
                            <span>${escapeHtml(item.document_id)}</span>
                        </div>
                        <div class="archive-suggest-meta">
                            <span class="archive-suggest-chip">${escapeHtml(item.division_label || 'Operasional')}</span>
                            <span class="archive-suggest-chip">${escapeHtml(item.shift_label)}</span>
                            ${item.group_from && item.group_from !== '-' ? `<span class="archive-suggest-chip">Regu ${escapeHtml(item.group_from)}</span>` : ''}
                            <span>Disetujui ${escapeHtml(item.approver)}</span>
                        </div>
                    </button>
                `).join('');

                showDropdown(header + list);
            }

            function itemSearchTerm(item) {
                return String(item?.document_id || input.value || '').trim();
            }

            function setActive(index) {
                const nodes = dropdown?.querySelectorAll('.archive-suggest-item') || [];
                if (!nodes.length) return;

                activeIndex = ((index % nodes.length) + nodes.length) % nodes.length;
                nodes.forEach((node, i) => node.classList.toggle('is-active', i === activeIndex));
                nodes[activeIndex]?.scrollIntoView({ block: 'nearest' });
            }

            async function fetchSuggestions(keyword) {
                if (!suggestUrl || !dropdown) return;
                if (controller) controller.abort();
                controller = new AbortController();
                showDropdown('<div class="archive-suggest-loading">Memuat saran...</div>');

                try {
                    const url = new URL(suggestUrl, window.location.origin);
                    url.searchParams.set('q', keyword);

                    const response = await fetch(url.toString(), {
                        signal: controller.signal,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) throw new Error('request failed');
                    renderItems(await response.json());
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    showDropdown('<div class="archive-suggest-empty">Saran belum bisa dimuat. Coba lagi.</div>');
                }
            }

            function openDropdownFromSearch() {
                const keyword = input.value.trim();
                if (normalize(keyword).length >= minSuggestLength) fetchSuggestions(keyword);
            }

            function isPointerInsideSuggestArea(event) {
                if (!dropdown?.classList.contains('show')) return false;

                const searchRect = input.closest('.archive-search-box')?.getBoundingClientRect();
                const dropdownRect = dropdown.getBoundingClientRect();
                if (!searchRect) return false;

                const safeGap = 10;
                const left = Math.min(searchRect.left, dropdownRect.left) - safeGap;
                const right = Math.max(searchRect.right, dropdownRect.right) + safeGap;
                const top = Math.min(searchRect.top, dropdownRect.top) - safeGap;
                const bottom = Math.max(searchRect.bottom, dropdownRect.bottom) + safeGap;

                return event.clientX >= left
                    && event.clientX <= right
                    && event.clientY >= top
                    && event.clientY <= bottom;
            }

            function scheduleSearch() {
                updateRows();
                if (timer) window.clearTimeout(timer);

                const keyword = input.value.trim();
                if (normalize(keyword).length < minSuggestLength) {
                    closeDropdown();
                    return;
                }

                timer = window.setTimeout(() => fetchSuggestions(keyword), 220);
            }

            function submitSearch(keyword) {
                if (!searchForm) return;
                input.value = String(keyword || '').trim();
                closeDropdown();
                searchForm.requestSubmit ? searchForm.requestSubmit() : searchForm.submit();
            }

            input.addEventListener('input', scheduleSearch);
            input.addEventListener('focus', openDropdownFromSearch);
            input.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    input.value = '';
                    updateRows();
                    closeDropdown();
                    return;
                }

                if (event.key === 'ArrowDown' && items.length) {
                    event.preventDefault();
                    setActive(activeIndex + 1);
                }

                if (event.key === 'ArrowUp' && items.length) {
                    event.preventDefault();
                    setActive(activeIndex - 1);
                }
            });

            input.closest('.archive-search-box')?.addEventListener('click', event => {
                const item = event.target.closest('.archive-suggest-item');
                if (item) {
                    event.preventDefault();
                    const index = Number(item.dataset.index || -1);
                    submitSearch(itemSearchTerm(items[index]));
                    return;
                }

                if (!event.target.closest('.archive-suggest-dropdown')) {
                    openDropdownFromSearch();
                }
            });

            searchForm?.addEventListener('submit', () => {
                if (timer) window.clearTimeout(timer);
                closeDropdown();
            });

            clearButton?.addEventListener('click', () => {
                input.value = '';
                updateRows();
                closeDropdown();
                input.focus();
            });

            document.addEventListener('mousemove', event => {
                if (!dropdown?.classList.contains('show')) return;
                if (!isPointerInsideSuggestArea(event)) closeDropdown();
            });

            document.addEventListener('click', event => {
                if (!event.target.closest('.archive-search-box')) closeDropdown();
            });

            updateRows();
        })();

        // AUTO-SUBMIT FILTERS (instant execution, sama seperti halaman Manajer)
        const archiveForm = document.getElementById('archiveFilterForm');
        document.querySelectorAll('[data-autosubmit-filter]').forEach(function (control) {
            control.addEventListener('change', function () {
                if (archiveForm) archiveForm.submit();
            });
        });
    });
</script>
@endpush
