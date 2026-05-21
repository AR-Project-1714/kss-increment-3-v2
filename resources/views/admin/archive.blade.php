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

    .table-responsive-wrapper table { min-width: 1000px; width: 100%; }

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

    .thead th.nomor { width: 50px; flex: none; justify-content: center; padding: 10px 0; }
    .thead th.column-1 { min-width: 160px; }
    .thead th.aksi { min-width: 230px; }

    .tbody { border-bottom: 1px solid var(--smooth-border); transition: background-color 0.15s ease-in-out; }
    .tbody:hover { background-color: var(--blue-main-3); }

    .tbody td {
        display: flex;
        align-items: center;
        padding: 0 10px;
        flex: 1 0 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--black);
    }

    .tbody td.nomor { width: 50px; flex: none; justify-content: center; padding: 12px 0; color: var(--black-secondary); }

    .tbody td.column-2 {
        min-width: 160px;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        gap: 2px;
    }

    .tbody td.column-1 { min-width: 160px; }

    .tbody td.column-3 { flex-direction: column; align-items: flex-start; gap: 10px; }

    .tbody td.aksi { gap: 8px; flex-wrap: nowrap; min-width: 230px; }

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
        ['no' => 1, 'title' => 'Laporan Shift Harian', 'id' => '#1', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'approve', 'status_label' => 'Ditanda Tangani'],
        ['no' => 2, 'title' => 'Laporan Shift Harian', 'id' => '#2', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'approve', 'status_label' => 'Ditanda Tangani'],
        ['no' => 3, 'title' => 'Laporan Shift Harian', 'id' => '#3', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'approve', 'status_label' => 'Ditanda Tangani'],
        ['no' => 4, 'title' => 'Laporan Shift Harian', 'id' => '#4', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'approve', 'status_label' => 'Ditanda Tangani'],
        ['no' => 5, 'title' => 'Laporan Shift Harian', 'id' => '#5', 'date' => '17-Januari-2026', 'regu' => 'Regu B', 'shift' => 'pagi', 'shift_label' => 'Shift Pagi', 'status' => 'approve', 'status_label' => 'Ditanda Tangani'],
    ]);
@endphp

<div class="page-header">
    <span class="page-title">Arsip Laporan</span>
    <span class="page-subtitle">Daftar seluruh laporan yang telah disetujui secara digital.</span>
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
        <div class="search-box">
            <span><i class="fi fi-rr-search"></i></span>
            <input type="text" name="q" value="{{ $archiveSearch ?? '' }}" placeholder="Pencarian Laporan">
        </div>
        <div class="archive-toolbar__actions">
            <div class="filter-select-wrapper toolbar-sort-wrapper">
                <select class="native-select" name="sort">
                    <option value="newest" @selected(($sort ?? 'newest') === 'newest')>Terbaru</option>
                    <option value="oldest" @selected(($sort ?? 'newest') === 'oldest')>Terlama</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
            <button type="button" class="btn-tool" id="btnFilter"><i class="fi fi-rr-filter"></i> Filter</button>
            <button type="submit" class="btn-tool"><i class="fi fi-rr-search"></i> Terapkan</button>
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

    <!-- Filters -->
    <div class="archive-filters collapsed" id="archiveFilters">
        <div class="filter-field">
            <label>Tanggal</label>
            <input type="date" class="filter-input" name="tanggal" value="{{ $selectedDate ?? '' }}">
        </div>
        <div class="filter-field">
            <label>Regu</label>
            <div class="filter-select-wrapper">
                <select class="native-select" name="regu">
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
                <select class="native-select" name="shift">
                    <option value="all" @selected(($selectedShift ?? 'all') === 'all')>Semua Shift</option>
                    <option value="pagi" @selected(($selectedShift ?? 'all') === 'pagi')>Shift Pagi</option>
                    <option value="sore" @selected(($selectedShift ?? 'all') === 'sore')>Shift Sore</option>
                    <option value="malam" @selected(($selectedShift ?? 'all') === 'malam')>Shift Malam</option>
                </select>
                <i class="fi fi-rr-angle-small-down select-arrow"></i>
            </div>
        </div>
        <a href="{{ route('admin.archive') }}"
                class="btn-reset"
                data-confirm
                data-confirm-redirect="{{ route('admin.archive') }}"
                data-confirm-tone="warning"
                data-confirm-title="Reset filter arsip?"
                data-confirm-subtitle="Pilihan filter akan dikembalikan ke kondisi awal."
                data-confirm-message="Pencarian dan filter tanggal, regu, serta shift akan dikosongkan."
                data-confirm-label="Reset Filter"
                data-confirm-icon="fi fi-rr-refresh">
            Reset
        </a>
    </div>
    </form>

    <!-- Table -->
    <div class="table-responsive-wrapper">
        <table>
            <tr class="thead d-flex justify-content-between align-items-center">
                <th class="nomor">No</th>
                <th class="column-1">Info Dokumen</th>
                <th class="column-1">Tanggal Laporan</th>
                <th>Regu</th>
                <th>Shift</th>
                <th>Status</th>
                <th class="aksi">Aksi</th>
            </tr>

            @forelse ($reports as $r)
                <tr class="tbody d-flex justify-content-between align-items-center">
                    <td class="nomor">{{ $r['no'] }}</td>
                    <td class="column-2">
                        <span>{{ $r['title'] }}</span>
                        <span class="fsize-10 fw-400 text-muted-custom">ID: {{ $r['id'] }}</span>
                    </td>
                    <td class="column-1">{{ $r['date'] }}</td>
                    <td>{{ $r['regu'] }}</td>
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
                                data-confirm
                                data-confirm-redirect="{{ $r['download_url'] ?? '#' }}"
                                data-confirm-tone="success"
                                data-confirm-title="Download laporan?"
                                data-confirm-subtitle="File laporan akan disiapkan untuk diunduh."
                                data-confirm-message="Pastikan laporan yang dipilih sudah sesuai sebelum melanjutkan."
                                data-confirm-summary="{{ $r['title'] }} {{ $r['id'] }} - {{ $r['date'] }}"
                                data-confirm-label="Download"
                                data-confirm-icon="fi fi-rr-download">
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
    });
</script>
@endpush
