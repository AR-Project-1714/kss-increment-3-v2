@extends('admin.layouts.app')

@section('title', 'KSS Admin — Log Aktivitas')
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
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        animation: filterSlideDown 0.3s ease;
    }

    .archive-filters.collapsed { display: none; }

    @keyframes filterSlideDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

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
</style>
@endpush

@section('content')
@php
    $stats = [
        ['label' => 'Total Pengguna Aktif',      'value' => '12',       'icon' => 'fi fi-sr-user',         'color' => 'blue'],
        ['label' => 'Kapasitas Server Terpakai', 'value' => '65%',      'icon' => 'fi fi-sr-database',     'color' => 'cyan'],
        ['label' => 'Status Backup Terakhir',    'value' => 'Berhasil', 'icon' => 'fi fi-sr-cloud-upload', 'color' => 'green'],
    ];

    $logs = [
        ['user' => 'Administrator Sistem', 'sub' => 'Role: Admin',             'unknown' => false, 'time' => '11 Mei 2026, 11:23', 'type' => 'update', 'type_label' => 'Update', 'desc' => 'Admin menonaktifkan akun <strong>"karu_a"</strong>', 'ip' => '192.168.1.104'],
        ['user' => 'Mustari, S.H',         'sub' => 'Role: Manajer',           'unknown' => false, 'time' => '10 Mei 2026, 23:11', 'type' => 'login',  'type_label' => 'Login',  'desc' => '<strong>"Pak Mustari"</strong> login ke dalam sistem', 'ip' => '192.168.1.104'],
        ['user' => 'Unknown',              'sub' => 'Username: mgr_mustari',    'unknown' => true,  'time' => '10 Mei 2026, 23:08', 'type' => 'error',  'type_label' => 'Error',  'desc' => 'Gagal Login: Percobaan password salah 3x oleh <strong>"Pak Mustari"</strong>', 'ip' => '192.168.1.104'],
    ];
@endphp

<div class="page-header">
    <span class="page-title">Log Aktivitas Sistem</span>
    <span class="page-subtitle">Pantau rekam jejak aktivitas seluruh pengguna untuk keperluan audit dan keamanan.</span>
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

<!-- Riwayat Aktivitas Sistem -->
@component('admin.layouts.card', ['title' => 'Riwayat Aktivitas Sistem'])
    <!-- Toolbar -->
    <div class="archive-toolbar">
        <div class="search-box">
            <span><i class="fi fi-rr-search"></i></span>
            <input type="text" placeholder="Pencarian Laporan">
        </div>
        <div class="archive-toolbar__actions">
            <div class="filter-select-wrapper toolbar-sort-wrapper">
                <select class="native-select">
                    <option value="newest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
            <button type="button" class="btn-tool" id="btnFilter"><i class="fi fi-rr-filter"></i> Filter</button>
            <button type="button"
                    class="btn-tool btn-tool--primary"
                    data-confirm
                    data-confirm-tone="success"
                    data-confirm-title="Ekspor log aktivitas?"
                    data-confirm-subtitle="Data audit akan disiapkan sebagai file unduhan."
                    data-confirm-message="Gunakan ekspor untuk pemeriksaan audit, keamanan, atau dokumentasi aktivitas sistem."
                    data-confirm-summary="Format preview: Excel"
                    data-confirm-label="Ekspor Log"
                    data-confirm-icon="fi fi-rr-cloud-upload-alt">
                <i class="fi fi-rr-cloud-upload-alt"></i> Ekspor
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="archive-filters collapsed" id="archiveFilters">
        <div class="filter-field">
            <label>Tanggal</label>
            <input type="date" class="filter-input">
        </div>
        <div class="filter-field">
            <label>Role</label>
            <div class="filter-select-wrapper">
                <select class="native-select">
                    <option value="all">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="manajer">Manajer</option>
                    <option value="karyawan">Karyawan</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
        </div>
        <div class="filter-field">
            <label>Tipe Aktivitas</label>
            <div class="filter-select-wrapper">
                <select class="native-select">
                    <option value="all">Semua Tipe</option>
                    <option value="login">Login</option>
                    <option value="update">Update</option>
                    <option value="error">Error</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
        </div>
        <button type="button"
                class="btn-reset"
                data-confirm
                data-confirm-tone="warning"
                data-confirm-title="Reset filter log?"
                data-confirm-subtitle="Pilihan filter log akan dikembalikan ke kondisi awal."
                data-confirm-message="Pencarian dan filter tanggal, role, serta tipe aktivitas akan dikosongkan."
                data-confirm-label="Reset Filter"
                data-confirm-icon="fi fi-rr-refresh">
            Reset
        </button>
    </div>

    <!-- Table -->
    <div class="table-responsive-wrapper">
        <table>
            <tr class="thead d-flex justify-content-between align-items-center">
                <th class="col-user">Pengguna</th>
                <th class="col-time">Waktu</th>
                <th class="col-type">Shift</th>
                <th class="col-desc">Deskripsi Aktivitas</th>
                <th class="col-ip">IP Address</th>
            </tr>

            @foreach ($logs as $l)
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
            @endforeach
        </table>
    </div>
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
    });
</script>
@endpush
