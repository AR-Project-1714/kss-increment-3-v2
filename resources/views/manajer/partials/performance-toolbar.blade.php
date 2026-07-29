{{-- Filter bersama untuk halaman Kinerja Operasi dan Rincian Kegiatan.

     Komponen dirender di dalam page header. Kontrolnya disembunyikan dalam
     popover agar isi analitik menjadi fokus utama sampai pengguna menekan
     tombol Filter.
--}}
@php
    $exportRoute = $exportRoute ?? 'manajer.performa.export';
    $exportTitle = $exportTitle ?? 'Unduh rekap performa sesuai filter aktif (Excel)';
    $exportQuery = array_merge(
        $selectedPreset === 'custom'
            ? ['dari' => $selectedStart, 'sampai' => $selectedEnd]
            : ['periode' => $selectedPreset],
        request()->only(['regu', 'shift'])
    );

    $shiftLabel = function (?string $shift): string {
        $normalized = strtolower(trim((string) $shift));

        return match (true) {
            in_array($normalized, ['1', 'pagi', 'shift 1'], true) => 'Shift Pagi',
            in_array($normalized, ['2', 'sore', 'siang', 'shift 2'], true) => 'Shift Sore',
            in_array($normalized, ['3', 'malam', 'shift 3'], true) => 'Shift Malam',
            default => $shift ? 'Shift '.$shift : 'Shift -',
        };
    };
@endphp

<div class="performance-filter {{ ($primaryExport ?? false) ? 'performance-filter--with-export' : '' }}"
     data-performance-filter>
    @if ($primaryExport ?? false)
        <a href="{{ route($exportRoute, $exportQuery) }}"
           class="btn-tool performance-export-button"
           title="{{ $exportTitle }}"
           data-activity-export>
            <i class="fi fi-rr-cloud-upload-alt" aria-hidden="true"></i>
            <span>Ekspor Excel</span>
        </a>
    @endif

    <button type="button"
            class="btn-tool btn-tool--primary performance-filter__trigger {{ $hasActiveFilter ? 'performance-filter__trigger--active' : '' }}"
            data-performance-filter-trigger
            aria-expanded="false"
            aria-controls="performance-filter-popover">
        <i class="fi fi-rr-settings-sliders" aria-hidden="true"></i>
        <span>Filter</span>
        @if ($hasActiveFilter)
            <span class="performance-filter__status" aria-label="Filter aktif">Aktif</span>
        @endif
    </button>

    <div class="performance-filter__popover"
         id="performance-filter-popover"
         data-performance-filter-popover
         hidden>
        <div class="performance-filter__head">
            <div>
                <span class="performance-filter__title">Filter Data</span>
                <span class="performance-filter__subtitle">Atur periode, regu, dan shift yang ingin ditampilkan.</span>
            </div>
            <button type="button"
                    class="performance-filter__close"
                    data-performance-filter-close
                    aria-label="Tutup filter">
                <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
            </button>
        </div>

        <form method="GET" action="{{ route($formRoute) }}" id="perf-filter-form" autocomplete="off">
            <div class="perf-toolbar">
                <div class="filter-field">
                    <label>Periode</label>
                    <div class="perf-toolbar__presets">
                        @foreach ($presets as $value => $label)
                            <a href="{{ route($formRoute, ['periode' => $value, 'regu' => $selectedGroup, 'shift' => $selectedShift]) }}"
                               class="btn-tool {{ $selectedPreset === $value ? 'btn-tool--active' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="filter-field">
                    <label>Dari Tanggal</label>
                    <input type="hidden"
                           name="dari"
                           value="{{ $selectedStart }}"
                           data-kss-picker="date"
                           data-trigger-class="filter-input"
                           data-placeholder="Tanggal mulai">
                </div>

                <div class="filter-field">
                    <label>Sampai Tanggal</label>
                    <input type="hidden"
                           name="sampai"
                           value="{{ $selectedEnd }}"
                           data-kss-picker="date"
                           data-trigger-class="filter-input"
                           data-placeholder="Tanggal akhir">
                </div>

                <div class="filter-field">
                    <label>Regu</label>
                    <div class="filter-select-wrapper">
                        <select class="native-select" name="regu">
                            <option value="all" @selected($selectedGroup === 'all')>Semua Regu</option>
                            @foreach ($filterOptions['groups'] as $group)
                                <option value="{{ $group }}" @selected($selectedGroup === $group)>Regu {{ $group }}</option>
                            @endforeach
                        </select>
                        <i class="fi fi-rr-angle-small-down select-arrow"></i>
                    </div>
                </div>

                <div class="filter-field">
                    <label>Shift</label>
                    <div class="filter-select-wrapper">
                        <select class="native-select" name="shift">
                            <option value="all" @selected($selectedShift === 'all')>Semua Shift</option>
                            @foreach ($filterOptions['shifts'] as $shift)
                                <option value="{{ $shift }}" @selected($selectedShift === $shift)>{{ $shiftLabel($shift) }}</option>
                            @endforeach
                        </select>
                        <i class="fi fi-rr-angle-small-down select-arrow"></i>
                    </div>
                </div>

                <div class="perf-toolbar__spacer"></div>

                <div class="performance-filter__actions">
                    @if ($hasActiveFilter)
                        <a href="{{ route($formRoute) }}" class="btn-reset">Reset</a>
                    @endif
                    @unless ($hidePopoverExport ?? false)
                        <a href="{{ route($exportRoute, $exportQuery) }}"
                           class="btn-tool"
                           title="{{ $exportTitle }}">
                            <i class="fi fi-rr-cloud-upload-alt" aria-hidden="true"></i> Ekspor
                        </a>
                    @endunless
                    <button type="submit" class="btn-tool btn-tool--primary">
                        <i class="fi fi-rr-check" aria-hidden="true"></i> Terapkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filter = document.querySelector('[data-performance-filter]');
            const trigger = filter?.querySelector('[data-performance-filter-trigger]');
            const popover = filter?.querySelector('[data-performance-filter-popover]');
            const closeButton = filter?.querySelector('[data-performance-filter-close]');

            if (!filter || !trigger || !popover) return;

            const setOpen = (open) => {
                popover.hidden = !open;
                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                filter.classList.toggle('is-open', open);
            };

            trigger.addEventListener('click', () => setOpen(popover.hidden));
            closeButton?.addEventListener('click', () => {
                setOpen(false);
                trigger.focus();
            });

            document.addEventListener('click', (event) => {
                const isDatePickerClick = event.target.closest?.('.kss-date-popover');

                if (!popover.hidden && !filter.contains(event.target) && !isDatePickerClick) {
                    setOpen(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape' || popover.hidden) return;
                setOpen(false);
                trigger.focus();
            });
        });
    </script>
@endpush
