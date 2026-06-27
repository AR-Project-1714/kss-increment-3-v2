@extends('manajer.layouts.app')

@push('styles')
    <style>
        .archive-toolbar form {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .archive-search-box {
            position: relative;
            padding-right: 44px;
            max-width: 600px;
        }

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
            transition: .2s ease-out;
        }

        .archive-search-clear:hover {
            background-color: var(--blue-main-25);
        }

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

        .archive-suggest-dropdown.show {
            display: block;
        }

        .archive-suggest-header,
        .archive-suggest-empty,
        .archive-suggest-loading {
            padding: 10px 12px;
            font-size: 11px;
            color: var(--muted);
        }

        .archive-suggest-header {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

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
        .archive-suggest-item.is-active {
            background-color: var(--blue-main-10);
        }

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

        .shift.nonshift {
            background-color: var(--blue-main-5);
            color: var(--black-secondary);
        }

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

        .division-badge i {
            position: relative;
            top: 1px;
            font-size: 11px;
        }

        .division-badge.operasional {
            color: var(--blue-main);
            background-color: var(--blue-main-10);
        }

        .division-badge.pemeliharaan {
            color: var(--orange-main);
            background-color: var(--orange-main-10);
        }

        .division-badge.safety {
            color: var(--success);
            background-color: var(--success-10);
        }

        .archive-body .table-responsive-wrapper table {
            min-width: 1100px;
        }

        .archive-body .thead,
        .archive-body .tbody {
            justify-content: space-between !important;
        }

        .archive-body .thead th,
        .archive-body .tbody td {
            padding-left: 6px;
            padding-right: 6px;
            flex: 0 0 auto;
        }

        .archive-body .thead th:nth-child(2),
        .archive-body .tbody td.column-2 {
            width: 230px;
            min-width: 230px;
        }

        .archive-body .thead th:nth-child(3),
        .archive-body .tbody td:nth-child(3) {
            width: 135px;
            min-width: 135px;
        }

        .archive-body .thead th:nth-child(4),
        .archive-body .tbody td:nth-child(4) {
            width: 135px;
            min-width: 135px;
        }

        .archive-body .thead th:nth-child(5),
        .archive-body .tbody td:nth-child(5) {
            width: 105px;
            min-width: 105px;
        }

        .archive-body .thead th:nth-child(6),
        .archive-body .tbody td:nth-child(6) {
            width: 120px;
            min-width: 120px;
        }

        .archive-body .thead th:nth-child(7),
        .archive-body .tbody td:nth-child(7) {
            width: 125px;
            min-width: 125px;
        }

        .archive-body .thead th.aksi,
        .archive-body .tbody td.aksi {
            width: 225px;
            min-width: 225px;
        }

        .archive-body .tbody td.column-2 > span:first-child {
            white-space: nowrap;
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

        .archive-toolbar__right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .archive-filters {
            width: 100%;
            flex: 0 0 100%;
            justify-content: flex-start;
            margin-top: 4px;
        }

        .archive-filters .filter-field {
            flex: 1 1 160px;
            max-width: 200px;
        }

        .archive-filters .filter-field .filter-input,
        .archive-filters .filter-select-trigger {
            width: 100%;
            min-width: 0;
            height: 36px;
            display: flex;
            align-items: center;
        }

        /* Panel selects sit in a column field: keep them content-sized so the
           absolutely-positioned arrow stays inside the box on mobile. */
        .archive-filters .filter-select-wrapper {
            width: 100%;
            min-width: 0;
            flex: 0 0 auto;
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

        .archive-empty {
            width: 100%;
            padding: 34px 18px;
            border: 1px dashed var(--divider);
            border-radius: 10px;
            text-align: center;
            color: var(--muted);
            background-color: var(--blue-main-3);
        }

        .archive-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 4px;
            flex-wrap: wrap;
        }

        .archive-page-list {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .archive-page-link,
        .archive-page-disabled {
            display: inline-flex;
            min-width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            color: var(--blue-main);
            background-color: var(--white);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .archive-page-link.active {
            color: #fff;
            border-color: var(--blue-main);
            background-color: var(--blue-main);
        }

        .archive-page-disabled {
            color: var(--muted);
            opacity: .55;
        }

        td.aksi form {
            margin: 0;
        }

        td.aksi a.btn-act {
            text-decoration: none;
        }

        .archive-body .tbody td {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .archive-body .tbody td.nomor {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .archive-body .tbody td.column-3 {
            gap: 6px;
        }

        .archive-body .tbody td.aksi {
            gap: 6px;
        }

        .archive-body .report-group {
            gap: 5px;
            padding: 5px 8px;
        }

        @media (max-width: 920px) {
            .archive-toolbar__right {
                width: 100%;
                margin-left: 0;
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $archiveTotal = method_exists($reports, 'total') ? $reports->total() : $reports->count();
        $archiveFirstItem = method_exists($reports, 'firstItem') ? $reports->firstItem() : ($archiveTotal > 0 ? 1 : null);
        $archiveLastItem = method_exists($reports, 'lastItem') ? $reports->lastItem() : $reports->count();
        $selectedDivision = $selectedDivision ?? 'all';
        $selectedStatus = $selectedStatus ?? 'all';
        $hasPanelFilter = filled($selectedDate)
            || !in_array($selectedGroup, ['ALL', ''], true)
            || !in_array($selectedShift, ['all', ''], true)
            || !in_array($selectedDivision, ['all', ''], true)
            || !in_array($selectedStatus, ['all', ''], true);
        $hasActiveFilter = $archiveSearch !== ''
            || $hasPanelFilter
            || $sort !== 'newest';
        $documentId = function ($report): string {
            $date = $report->report_date ?: $report->created_at;

            try {
                $year = $date ? \Carbon\Carbon::parse($date)->format('Y') : now()->format('Y');
            } catch (\Throwable) {
                $year = now()->format('Y');
            }

            return '#OPS-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        };
        $formatDate = fn ($date) => $date ? $date->locale('id')->translatedFormat('d F Y') : '-';
        $formatDiff = fn ($date) => $date ? $date->locale('id')->diffForHumans() : '-';
        $shiftMeta = function ($shift): array {
            $normalized = strtolower(trim((string) $shift));

            return match (true) {
                in_array($normalized, ['1', 'pagi', 'shift 1', 'shift pagi'], true) => ['label' => 'Shift Pagi', 'class' => 'pagi', 'icon' => 'fi fi-rr-sunrise'],
                in_array($normalized, ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'], true) => ['label' => 'Shift Sore', 'class' => 'sore', 'icon' => 'fi fi-rr-sun'],
                in_array($normalized, ['3', 'malam', 'shift 3', 'shift malam'], true) => ['label' => 'Shift Malam', 'class' => 'malam', 'icon' => 'fi fi-rr-moon-stars'],
                default => ['label' => $shift ? 'Shift '.$shift : 'Shift -', 'class' => 'pagi', 'icon' => 'fi fi-rr-clock'],
            };
        };
        $statusMeta = function ($status): array {
            $value = $status instanceof \App\Enums\ReportStatus ? $status->value : (string) $status;

            return match ($value) {
                \App\Enums\ReportStatus::Submitted->value => ['label' => 'Diserahkan', 'class' => 'submit'],
                \App\Enums\ReportStatus::Acknowledged->value => ['label' => 'Diterima', 'class' => 'confirm'],
                \App\Enums\ReportStatus::Approved->value => ['label' => 'Diarsipkan', 'class' => 'archive'],
                default => ['label' => ucfirst($value), 'class' => 'submit'],
            };
        };
        $flattenSearchValues = function ($value) use (&$flattenSearchValues): array {
            if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                $attributes = $value->attributesToArray();

                foreach ($value->getRelations() as $relationName => $relationValue) {
                    $attributes[$relationName] = $relationValue;
                }

                $value = $attributes;
            } elseif ($value instanceof \Illuminate\Support\Collection) {
                $value = $value->all();
            } elseif ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
                $value = $value->toArray();
            } elseif ($value instanceof \DateTimeInterface) {
                return [$value->format('Y-m-d H:i:s')];
            }

            if (is_array($value)) {
                $result = [];

                foreach ($value as $key => $item) {
                    if (is_string($key)) {
                        $result[] = str_replace(['_', '-'], ' ', $key);
                    }

                    array_push($result, ...$flattenSearchValues($item));
                }

                return $result;
            }

            return is_scalar($value) && filled($value) ? [(string) $value] : [];
        };
    @endphp

    <main class="page-content">
        <div class="page-header">
            <span class="page-title">Arsip Laporan</span>
            <span class="page-subtitle">Daftar laporan yang berstatus diserahkan, ditanda tangani, dan diarsipkan.</span>
        </div>

        @include('manajer.layouts.card')

        <div class="section-card">
            <div class="archive-body">
                <span class="section-card__title">Riwayat Laporan</span>

                <div class="archive-toolbar">
                    <form method="GET" action="{{ route('manajer.archive') }}" id="archive-search-form" autocomplete="off">
                        <div class="search-box archive-search-box">
                            <span><i class="fi fi-rr-search"></i></span>
                            <input
                                type="search"
                                id="archive-search-input"
                                name="q"
                                placeholder="Cari ID, divisi, tanggal, shift, regu, kapal, karyawan, atau isi laporan"
                                value="{{ $archiveSearch }}"
                                data-initial-value="{{ $archiveSearch }}"
                                data-page-start="{{ $archiveFirstItem ?? 1 }}"
                                data-suggest-url="{{ route('manajer.archive.suggestions') }}"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-controls="archive-suggest-dropdown"
                            >
                            @if ($archiveSearch !== '')
                                <a href="{{ route('manajer.archive', request()->except(['q', 'page'])) }}" class="archive-search-clear" aria-label="Bersihkan pencarian">
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
                            <span id="archive-count" class="archive-count" data-total="{{ $archiveTotal }}" data-label="{{ $archiveSearch !== '' || $hasActiveFilter ? 'hasil' : 'laporan' }}">
                                <i class="fi fi-rr-folder-open"></i>
                                <span>{{ $archiveTotal }} {{ $archiveSearch !== '' || $hasActiveFilter ? 'hasil' : 'laporan' }}</span>
                            </span>

                            <div class="archive-toolbar__actions">
                                <div class="filter-select-wrapper toolbar-sort-wrapper">
                                    <select class="native-select" name="sort" data-autosubmit-filter>
                                        <option value="newest" @selected($sort === 'newest')>Terbaru</option>
                                        <option value="oldest" @selected($sort === 'oldest')>Terlama</option>
                                    </select>
                                    <i class="fi fi-rr-angle-small-down select-arrow"></i>
                                </div>
                                <button type="button" class="btn-tool {{ $hasPanelFilter ? 'btn-tool--active' : '' }}" id="btnFilter"><i class="fi fi-rr-filter"></i> Filter</button>
                                @if ($hasActiveFilter)
                                    <a href="{{ route('manajer.archive') }}" class="btn-reset">Reset</a>
                                @endif
                            </div>
                        </div>

                        <div class="archive-filters {{ $hasPanelFilter ? '' : 'collapsed' }}" id="archiveFilters">
                            <div class="filter-field">
                                <label>Tanggal</label>
                                <input type="hidden" name="tanggal" value="{{ $selectedDate }}" data-kss-picker="date" data-trigger-class="filter-input" data-placeholder="Pilih tanggal" data-autosubmit-filter>
                            </div>
                            <div class="filter-field">
                                <label>Divisi</label>
                                <div class="filter-select-wrapper">
                                    <select class="native-select" name="divisi" data-autosubmit-filter>
                                        <option value="all" @selected($selectedDivision === 'all')>Semua Divisi</option>
                                        <option value="operasional" @selected($selectedDivision === 'operasional')>Operasional</option>
                                        <option value="pemeliharaan" @selected($selectedDivision === 'pemeliharaan')>Pemeliharaan</option>
                                        <option value="safety" @selected($selectedDivision === 'safety')>Safety</option>
                                    </select>
                                    <i class="fi fi-rr-angle-small-down select-arrow"></i>
                                </div>
                            </div>
                            <div class="filter-field">
                                <label>Regu</label>
                                <div class="filter-select-wrapper">
                                    <select class="native-select" name="regu" data-autosubmit-filter>
                                        <option value="all" @selected($selectedGroup === 'ALL')>Semua Regu</option>
                                        @foreach (['A', 'B', 'C', 'D'] as $group)
                                            <option value="{{ $group }}" @selected($selectedGroup === $group)>Regu {{ $group }}</option>
                                        @endforeach
                                    </select>
                                    <i class="fi fi-rr-angle-small-down select-arrow"></i>
                                </div>
                            </div>
                            <div class="filter-field">
                                <label>Shift</label>
                                <div class="filter-select-wrapper">
                                    <select class="native-select" name="shift" data-autosubmit-filter>
                                        <option value="all" @selected($selectedShift === 'all')>Semua Shift</option>
                                        <option value="pagi" @selected($selectedShift === 'pagi')>Shift Pagi</option>
                                        <option value="sore" @selected($selectedShift === 'sore')>Shift Sore</option>
                                        <option value="malam" @selected($selectedShift === 'malam')>Shift Malam</option>
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
                </div>

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
                                $reguCode = $reguCodeSource !== '' && $reguCodeSource !== '-' ? strtoupper(substr($reguCodeSource, 0, 1)) : '-';
                            @endphp
                            <tr
                                class="tbody d-flex justify-content-between align-items-center"
                                data-history-row
                                data-history-search="{{ $r['search'] ?? '' }}"
                            >
                                <td class="nomor">{{ $r['no'] }}</td>
                                <td class="column-2">
                                    <span>{{ $r['title'] }}</span>
                                    <span class="fsize-10 fw-400 text-muted-custom">ID: {{ $r['id'] }}</span>
                                </td>
                                <td class="column-1">
                                    <span>{{ $r['date'] }}</span>
                                </td>
                                <td>
                                    <span class="division-badge {{ $r['division_class'] ?? 'operasional' }}">
                                        <i class="{{ $r['division_icon'] ?? 'fi fi-rr-ship' }}"></i>
                                        {{ $r['division_label'] ?? 'Operasional' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="report-group d-flex align-items-center gap-6">
                                        <div class="letter-group out">{{ $reguCode }}</div>
                                        <span class="text fsize-10 fw-600">{{ $reguName === '-' ? '-' : $reguName }}</span>
                                    </div>
                                </td>
                                <td class="column-3">
                                    <div class="shift {{ $r['shift'] }}">
                                        <span class="icon-shift"><i class="{{ $r['shift_icon'] }}"></i></span>
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
                                    <a href="{{ $r['download_url'] ?? '#' }}" class="btn-act download">
                                        <i class="fi fi-rr-download"></i> Download
                                    </a>
                                    <a href="{{ $r['view_url'] ?? '#' }}" class="btn-act view" title="Lihat" target="_blank" rel="noopener">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                    <button type="button" class="btn-act delete js-open-modal" data-modal="delete-report-modal-{{ $r['key'] }}" title="Hapus">
                                        <i class="fi fi-rr-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="border-0 p-0">
                                    <div class="archive-empty">
                                        <div class="fw-600 mb-1" style="color: var(--black);">{{ $archiveSearch !== '' || $hasActiveFilter ? 'Laporan tidak ditemukan' : 'Arsip masih kosong' }}</div>
                                        <div class="fsize-12">{{ $archiveSearch !== '' || $hasActiveFilter ? 'Coba gunakan ID, tanggal, shift, regu, divisi, status, kapal, karyawan, atau isi laporan lain.' : 'Laporan berstatus diserahkan, ditanda tangani, dan diarsipkan akan tampil di sini.' }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        @if ($reports->count() > 0)
                            <tr id="archive-search-empty" class="d-none">
                                <td colspan="8" class="border-0 p-0">
                                    <div class="archive-empty">
                                        <div class="fw-600 mb-1" style="color: var(--black);">Laporan tidak ditemukan di halaman ini</div>
                                        <div class="fsize-12">Tekan Enter untuk mencari ke seluruh arsip, atau coba kata kunci lain.</div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>

                @if ($reports->hasPages())
                    <div class="archive-pagination">
                        <div class="fsize-11 fw-500 text-muted-custom">
                            Menampilkan {{ $archiveFirstItem }}-{{ $archiveLastItem }} dari {{ $archiveTotal }} {{ $archiveSearch !== '' || $hasActiveFilter ? 'hasil' : 'laporan' }}
                        </div>
                        <div class="archive-page-list">
                            @if ($reports->onFirstPage())
                                <span class="archive-page-disabled"><i class="fi fi-rr-angle-small-left"></i></span>
                            @else
                                <a href="{{ $reports->previousPageUrl() }}" class="archive-page-link" aria-label="Halaman sebelumnya">
                                    <i class="fi fi-rr-angle-small-left"></i>
                                </a>
                            @endif

                            @foreach ($reports->getUrlRange(1, $reports->lastPage()) as $page => $url)
                                <a href="{{ $url }}" class="archive-page-link {{ $reports->currentPage() === $page ? 'active' : '' }}" aria-label="Halaman {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endforeach

                            @if ($reports->hasMorePages())
                                <a href="{{ $reports->nextPageUrl() }}" class="archive-page-link" aria-label="Halaman berikutnya">
                                    <i class="fi fi-rr-angle-small-right"></i>
                                </a>
                            @else
                                <span class="archive-page-disabled"><i class="fi fi-rr-angle-small-right"></i></span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection

@push('modals')
    @foreach ($reports as $r)
        <div class="modal-overlay" id="delete-report-modal-{{ $r['key'] }}">
            <div class="modal-box">
                <div class="modal-box__header">
                    <span class="modal-box__title">Hapus Arsip Laporan</span>
                    <button type="button" class="btn-modal-close js-close-modal" data-modal="delete-report-modal-{{ $r['key'] }}">
                        <i class="fi fi-br-cross"></i>
                    </button>
                </div>

                <div class="modal-box__doc-detail">
                    <span class="modal-box__doc-icon"><i class="fi fi-rr-trash"></i></span>
                    <div>
                        <div class="modal-box__doc-title">{{ $r['title'] }}</div>
                        <div class="modal-box__doc-sub">{{ $r['id'] }} &bull; {{ $r['date'] }}</div>
                    </div>
                </div>

                <div class="modal-box__note">
                    <i class="fi fi-rr-info"></i>
                    <span>Laporan yang dihapus akan keluar dari arsip dan file PDF cache-nya ikut dibersihkan.</span>
                </div>

                <div class="modal-box__footer">
                    <button type="button" class="btn-modal btn-modal--cancel js-close-modal" data-modal="delete-report-modal-{{ $r['key'] }}">Batal</button>
                    <form action="{{ $r['destroy_url'] ?? '#' }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-modal" style="background-color: var(--red-main); color: #fff;">
                            <i class="fi fi-rr-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('archive-search-form');
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

            function normalize(value) {
                return String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
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
                if (!input) return;

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
                input?.setAttribute('aria-expanded', 'false');
            }

            function showDropdown(html) {
                if (!dropdown) return;
                dropdown.innerHTML = html;
                dropdown.classList.add('show');
                input?.setAttribute('aria-expanded', 'true');
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
                return String(item?.document_id || input?.value || '').trim();
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
                const keyword = input?.value.trim() || '';
                if (normalize(keyword).length >= minSuggestLength) fetchSuggestions(keyword);
            }

            function isPointerInsideSuggestArea(event) {
                if (!input || !dropdown?.classList.contains('show')) {
                    return false;
                }

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

                const keyword = input?.value.trim() || '';
                if (normalize(keyword).length < minSuggestLength) {
                    closeDropdown();
                    return;
                }

                timer = window.setTimeout(() => fetchSuggestions(keyword), 220);
            }

            function submitSearch(keyword) {
                if (!form || !input) return;
                input.value = String(keyword || '').trim();
                closeDropdown();
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }

            input?.addEventListener('input', scheduleSearch);
            input?.addEventListener('focus', () => {
                openDropdownFromSearch();
            });
            input?.addEventListener('keydown', event => {
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

            input?.closest('.archive-search-box')?.addEventListener('click', event => {
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

            form?.addEventListener('submit', event => {
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

            document.querySelectorAll('[data-autosubmit-filter]').forEach(control => {
                control.addEventListener('change', () => form?.submit());
            });

            updateRows();
        });
    </script>
@endpush
