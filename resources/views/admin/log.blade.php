@extends('admin.layouts.app')

@section('title', 'KSS Admin - Log Aktivitas')
@section('active', 'log')

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
       TOOLBAR & FILTERS
       ============================================= */
    .archive-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

    .performance-page-header {
        position: relative;
        z-index: 20;
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .performance-page-header__heading {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .performance-filter {
        position: relative;
        flex: 0 0 auto;
    }

    .performance-filter--with-export {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .performance-export-button {
        min-height: 38px;
        color: var(--success);
        border-color: var(--success);
        background-color: var(--success-10);
        text-decoration: none;
    }

    .performance-export-button:hover {
        color: var(--success);
        border-color: var(--success);
        background-color: var(--success-10);
    }

    .performance-filter__trigger {
        min-height: 38px;
        padding-inline: 14px;
        color: #fff;
        background-color: var(--blue-main);
        border-color: var(--blue-main);
        box-shadow: 0 5px 14px rgba(37, 99, 235, .18);
    }

    .performance-filter__trigger:hover {
        color: #fff;
        background-color: var(--blue-hover);
        border-color: var(--blue-hover);
    }

    .performance-filter__trigger[aria-expanded="true"] {
        box-shadow: 0 0 0 3px var(--blue-main-10), 0 5px 14px rgba(37, 99, 235, .18);
    }

    .performance-filter__status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 18px;
        padding: 1px 6px;
        border-radius: 999px;
        color: var(--blue-main);
        background-color: #fff;
        font-size: 9px;
        font-weight: 700;
    }

    .performance-filter__popover {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        z-index: 40;
        width: min(620px, calc(100vw - 112px));
        padding: 16px;
        /* Frosted bersama — lihat resources/css/components/frosted-surface.css. */
        border: 1px solid var(--kss-frost-border);
        border-radius: 14px;
        background-color: var(--kss-frost-surface);
        -webkit-backdrop-filter: var(--kss-frost-filter);
        backdrop-filter: var(--kss-frost-filter);
        box-shadow: inset 0 1px 0 var(--kss-frost-edge), var(--kss-frost-shadow);
    }

    .performance-filter__popover[hidden] { display: none; }

    .performance-filter__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--smooth-border);
    }

    .performance-filter__title {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--black);
    }

    .performance-filter__subtitle {
        display: block;
        margin-top: 2px;
        font-size: 10px;
        color: var(--muted);
    }

    .performance-filter__close {
        width: 30px;
        height: 30px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        color: var(--black-secondary);
        background-color: transparent;
        cursor: pointer;
    }

    .performance-filter__actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 14px;
    }

    .log-filter-fields {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: end;
        gap: 12px;
    }

    .log-filter-fields .filter-field {
        min-width: 0;
        max-width: none;
    }

    .log-filter-fields .filter-select-wrapper {
        width: 100%;
        min-width: 0;
    }

    .log-filter-fields .filter-input,
    .log-filter-fields .filter-select-trigger {
        width: 100%;
        min-width: 0;
        height: 36px;
    }

    .log-filter-fields .kss-date-trigger.filter-input {
        min-height: 36px;
        padding: 0 12px;
        justify-content: flex-start;
        border-radius: 8px;
        font-size: 12px;
    }

    .log-filter-fields .kss-date-trigger__main { width: 100%; }

    .log-filter-fields .kss-date-trigger__main i {
        top: 0;
        color: var(--blue-main);
        font-size: 13px;
    }

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

    .filter-field { display: flex; flex-direction: column; gap: 4px; }
    .filter-field label { font-size: 10px; font-weight: 500; color: var(--black-secondary); }
    .filter-field .filter-input { min-width: 150px; }

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

    /* Custom dropdown */
    .filter-select-wrapper { position: relative; min-width: 150px; }
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

    @media (max-width: 920px) {
        .performance-page-header { align-items: stretch; }
        .archive-toolbar { align-items: stretch; }

        .search-box {
            flex-basis: 100%;
            max-width: none;
            width: 100%;
        }

        .archive-toolbar__actions {
            width: 100%;
            justify-content: flex-start;
        }

        .archive-toolbar__actions .filter-select-wrapper,
        .archive-toolbar__actions .toolbar-sort-wrapper {
            min-width: 140px;
            flex: 1 1 140px;
        }
    }

    @media (max-width: 560px) {
        .performance-page-header { flex-direction: column; }

        .performance-filter--with-export,
        .performance-filter__actions {
            width: 100%;
        }

        .performance-filter--with-export > *,
        .performance-filter__actions > * {
            flex: 1 1 0;
            justify-content: center;
        }

        .performance-filter__popover {
            right: auto;
            left: 0;
            width: calc(100vw - 48px);
        }

        .log-filter-fields { grid-template-columns: 1fr; }

        .btn-tool,
        .btn-reset,
        .filter-input,
        .filter-select-trigger {
            width: 100%;
            justify-content: center;
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

    /* Log columns */
    .thead th.col-user, .tbody td.col-user { min-width: 180px; }
    .thead th.col-time, .tbody td.col-time { min-width: 140px; }
    .thead th.col-type, .tbody td.col-type { min-width: 90px; }
    .thead th.col-desc, .tbody td.col-desc { min-width: 240px; flex: 2 0 0; }
    .thead th.col-ip,   .tbody td.col-ip   { min-width: 110px; }

    .tbody td.col-user {
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 2px;
    }

    .log-user__name { font-size: 12px; font-weight: 600; color: var(--black); }
    .log-user__name.unknown { color: var(--red-main); }
    .log-user__sub  { font-size: 10px; font-weight: 400; color: var(--muted); }

    .tbody td.col-time { font-weight: 600; }
    .tbody td.col-desc { font-weight: 400; color: var(--black-secondary); }
    .tbody td.col-ip   { font-weight: 400; color: var(--muted); }

    /* Activity type badges */
    .log-type {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 500;
    }

    .log-type.update { background-color: var(--orange-main-10); color: var(--orange-main); }
    .log-type.login  { background-color: var(--blue-main-10);   color: var(--blue-main); }
    .log-type.error  { background-color: var(--red-main-10);    color: var(--red-main); }
    .log-type.blue   { background-color: var(--blue-main-10);   color: var(--blue-main); }
    .log-type.green  { background-color: var(--success-10);     color: var(--success); }
    .log-type.red    { background-color: var(--red-main-10);    color: var(--red-main); }
</style>
@endpush

@section('content')
@php
    $stats = $stats ?? [
        ['label' => 'Total Pengguna Aktif',      'value' => '12',       'icon' => 'fi fi-sr-user',         'color' => 'blue'],
        ['label' => 'Kapasitas Server Terpakai', 'value' => '65%',      'icon' => 'fi fi-sr-database',     'color' => 'cyan'],
        ['label' => 'Status Backup Terakhir',    'value' => 'Berhasil', 'icon' => 'fi fi-sr-cloud-upload', 'color' => 'green'],
    ];

    $logs = $logs ?? [
        ['user' => 'Administrator Sistem', 'sub' => 'Role: Admin',             'unknown' => false, 'time' => '11 Mei 2026, 11:23', 'type' => 'update', 'type_label' => 'Update', 'desc' => 'Admin menonaktifkan akun <strong>"karu_a"</strong>', 'ip' => '192.168.1.104'],
        ['user' => 'Mustari, S.H',         'sub' => 'Role: Manajer',           'unknown' => false, 'time' => '10 Mei 2026, 23:11', 'type' => 'login',  'type_label' => 'Login',  'desc' => '<strong>"Pak Mustari"</strong> login ke dalam sistem', 'ip' => '192.168.1.104'],
        ['user' => 'Unknown',              'sub' => 'Username: mgr_mustari',    'unknown' => true,  'time' => '10 Mei 2026, 23:08', 'type' => 'error',  'type_label' => 'Error',  'desc' => 'Gagal Login: Percobaan password salah 3x oleh <strong>"Pak Mustari"</strong>', 'ip' => '192.168.1.104'],
    ];

    $hasPanelFilter = filled($selectedDate ?? '')
        || ! in_array($selectedRole ?? 'all', ['all', ''], true)
        || ! in_array($selectedType ?? 'all', ['all', ''], true);
    $hasActiveFilter = filled($activitySearch ?? '')
        || $hasPanelFilter
        || ($sort ?? 'newest') !== 'newest';
@endphp

<div class="page-header performance-page-header">
    <div class="performance-page-header__heading">
        <span class="page-title">Log Aktivitas Sistem</span>
        <span class="page-subtitle">Pantau rekam jejak aktivitas seluruh pengguna untuk keperluan audit dan keamanan.</span>
    </div>

    <div class="performance-filter performance-filter--with-export" data-log-filter>
        <a href="{{ route('admin.log.export', request()->except('page')) }}"
                class="btn-tool performance-export-button"
                data-confirm
                data-confirm-redirect="{{ route('admin.log.export', request()->except('page')) }}"
                data-confirm-tone="success"
                data-confirm-title="Ekspor log aktivitas?"
                data-confirm-subtitle="Berkas Excel akan diunduh sesuai filter yang sedang aktif."
                data-confirm-message="Ekspor mengambil {{ $activityTotal }} aktivitas sesuai pencarian, tanggal, role, dan tipe yang sedang diterapkan pada tabel."
                data-confirm-summary="Format: Excel (.xlsx), {{ $activityTotal }} aktivitas"
                data-confirm-label="Ekspor Log"
                data-confirm-icon="fi fi-rr-cloud-upload-alt">
            <i class="fi fi-rr-cloud-upload-alt" aria-hidden="true"></i>
            <span>Ekspor Excel</span>
        </a>

        <button type="button"
                class="btn-tool btn-tool--primary performance-filter__trigger {{ $hasActiveFilter ? 'performance-filter__trigger--active' : '' }}"
                data-log-filter-trigger
                aria-expanded="false"
                aria-controls="log-filter-popover">
            <i class="fi fi-rr-settings-sliders" aria-hidden="true"></i>
            <span>Filter</span>
            @if ($hasActiveFilter)
                <span class="performance-filter__status" aria-label="Filter aktif">Aktif</span>
            @endif
        </button>

        <div class="performance-filter__popover"
             id="log-filter-popover"
             data-log-filter-popover
             hidden>
            <div class="performance-filter__head">
                <div>
                    <span class="performance-filter__title">Filter Log Aktivitas</span>
                    <span class="performance-filter__subtitle">Atur tanggal, role, dan tipe aktivitas yang ingin ditampilkan.</span>
                </div>
                <button type="button"
                        class="performance-filter__close"
                        data-log-filter-close
                        aria-label="Tutup filter">
                    <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('admin.log') }}" id="log-filter-form" autocomplete="off">
                <input type="hidden" name="q" value="{{ $activitySearch ?? '' }}">
                <input type="hidden" name="sort" value="{{ $sort ?? 'newest' }}">

                <div class="log-filter-fields">
                    <div class="filter-field">
                        <label>Tanggal</label>
                        <input type="hidden" name="tanggal" value="{{ $selectedDate ?? '' }}" data-kss-picker="date" data-trigger-class="filter-input" data-placeholder="Pilih tanggal">
                    </div>
                    <div class="filter-field">
                        <label>Role</label>
                        <div class="filter-select-wrapper">
                            <select class="native-select" name="role">
                                <option value="all" @selected(($selectedRole ?? 'all') === 'all')>Semua Role</option>
                                <option value="admin" @selected(($selectedRole ?? 'all') === 'admin')>Admin</option>
                                <option value="manajer" @selected(($selectedRole ?? 'all') === 'manajer')>Manajer</option>
                                <option value="operasional" @selected(($selectedRole ?? 'all') === 'operasional')>Operasional</option>
                                <option value="pemeliharaan" @selected(($selectedRole ?? 'all') === 'pemeliharaan')>Pemeliharaan</option>
                                <option value="safety" @selected(($selectedRole ?? 'all') === 'safety')>Safety</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down select-arrow"></i>
                        </div>
                    </div>
                    <div class="filter-field">
                        <label>Tipe Aktivitas</label>
                        <div class="filter-select-wrapper">
                            <select class="native-select" name="type">
                                <option value="all" @selected(($selectedType ?? 'all') === 'all')>Semua Tipe</option>
                                <option value="update" @selected(($selectedType ?? 'all') === 'update')>Update</option>
                                <option value="delete" @selected(($selectedType ?? 'all') === 'delete')>Hapus</option>
                                <option value="backup" @selected(($selectedType ?? 'all') === 'backup')>Backup</option>
                                <option value="export" @selected(($selectedType ?? 'all') === 'export')>Ekspor</option>
                                <option value="support" @selected(($selectedType ?? 'all') === 'support')>Bantuan</option>
                                <option value="login" @selected(($selectedType ?? 'all') === 'login')>Login</option>
                                <option value="security" @selected(($selectedType ?? 'all') === 'security')>Keamanan</option>
                                <option value="error" @selected(($selectedType ?? 'all') === 'error')>Error</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down select-arrow"></i>
                        </div>
                    </div>
                </div>

                <div class="performance-filter__actions">
                    @if ($hasActiveFilter)
                        <a href="{{ route('admin.log') }}"
                                class="btn-reset"
                                data-confirm
                                data-confirm-redirect="{{ route('admin.log') }}"
                                data-confirm-tone="warning"
                                data-confirm-title="Reset filter log?"
                                data-confirm-subtitle="Pilihan filter log akan dikembalikan ke kondisi awal."
                                data-confirm-message="Pencarian dan filter tanggal, role, serta tipe aktivitas akan dikosongkan."
                                data-confirm-label="Reset Filter"
                                data-confirm-icon="fi fi-rr-refresh">
                            Reset
                        </a>
                    @endif
                    <button type="submit" class="btn-tool btn-tool--primary">
                        <i class="fi fi-rr-check" aria-hidden="true"></i> Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Kartu yang sama dengan Dashboard Sistem, lengkap dengan angka pembanding. --}}
@include('charts.kpi-row', ['cards' => $stats])

<!-- Riwayat Aktivitas Sistem -->
@component('admin.layouts.card', ['title' => 'Riwayat Aktivitas Sistem'])
    <form method="GET" action="{{ route('admin.log') }}" id="log-search-form" autocomplete="off">
    <!-- Toolbar -->
    <div class="archive-toolbar">
        <div class="search-box">
            <span><i class="fi fi-rr-search"></i></span>
            <input type="search" name="q" value="{{ $activitySearch ?? '' }}" placeholder="Pencarian aktivitas">
        </div>
        <div class="archive-toolbar__actions">
            <div class="filter-select-wrapper toolbar-sort-wrapper">
                <select class="native-select" name="sort" data-autosubmit-filter>
                    <option value="newest" @selected(($sort ?? 'newest') === 'newest')>Terbaru</option>
                    <option value="oldest" @selected(($sort ?? 'newest') === 'oldest')>Terlama</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
        </div>
    </div>

    <input type="hidden" name="tanggal" value="{{ $selectedDate ?? '' }}">
    <input type="hidden" name="role" value="{{ $selectedRole ?? 'all' }}">
    <input type="hidden" name="type" value="{{ $selectedType ?? 'all' }}">
    </form>

    <!-- Table -->
    <div class="table-responsive-wrapper">
        <table>
            <tr class="thead d-flex justify-content-between align-items-center">
                <th class="col-user">Pengguna</th>
                <th class="col-time">Waktu</th>
                <th class="col-type">Tipe</th>
                <th class="col-desc">Deskripsi Aktivitas</th>
                <th class="col-ip">IP Address</th>
            </tr>

            @forelse ($logs as $l)
                <tr class="tbody d-flex justify-content-between align-items-center">
                    <td class="col-user">
                        <span class="log-user__name {{ $l['unknown'] ? 'unknown' : '' }}">{{ $l['user'] }}</span>
                        <span class="log-user__sub">{{ $l['sub'] }}</span>
                    </td>
                    <td class="col-time">{{ $l['time'] }}</td>
                    <td class="col-type"><span class="log-type {{ $l['type'] }}">{{ $l['type_label'] }}</span></td>
                    <td class="col-desc">{!! $l['desc'] !!}</td>
                    <td class="col-ip">{{ $l['ip'] }}</td>
                </tr>
            @empty
                <tr class="tbody d-flex justify-content-center align-items-center">
                    <td class="col-desc text-muted-custom" style="min-width: 100%; justify-content: center;">Belum ada aktivitas admin yang tercatat.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endcomponent
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const logFilter = document.querySelector('[data-log-filter]');
        const filterTrigger = logFilter?.querySelector('[data-log-filter-trigger]');
        const filterPopover = logFilter?.querySelector('[data-log-filter-popover]');
        const filterClose = logFilter?.querySelector('[data-log-filter-close]');

        const setFilterOpen = (open) => {
            if (!logFilter || !filterTrigger || !filterPopover) return;
            filterPopover.hidden = !open;
            filterTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            logFilter.classList.toggle('is-open', open);
        };

        filterTrigger?.addEventListener('click', () => setFilterOpen(filterPopover?.hidden ?? true));
        filterClose?.addEventListener('click', () => {
            setFilterOpen(false);
            filterTrigger?.focus();
        });

        document.addEventListener('click', event => {
            const isDatePickerClick = event.target.closest?.('.kss-date-popover');

            if (filterPopover && !filterPopover.hidden && !logFilter.contains(event.target) && !isDatePickerClick) {
                setFilterOpen(false);
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || !filterPopover || filterPopover.hidden) return;
            setFilterOpen(false);
            filterTrigger?.focus();
        });

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

        document.querySelectorAll('[data-autosubmit-filter]').forEach(function (control) {
            control.addEventListener('change', function () {
                control.closest('form')?.submit();
            });
        });
    });
</script>
@endpush
