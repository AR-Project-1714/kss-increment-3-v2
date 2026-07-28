{{-- Toolbar periode & filter untuk halaman Kinerja Operasi dan Rincian Kegiatan.

     Keduanya membaca filter yang sama lewat ManajerController::
     performanceFiltersFromRequest(), jadi toolbarnya satu berkas supaya
     pilihan periode tidak pernah berbeda antar menu.

     Parameter:
       $formRoute        nama route tujuan form dan tautan preset
       $presets          daftar preset periode
       $selectedPreset   preset yang sedang aktif
       $selectedStart    tanggal mulai (Y-m-d)
       $selectedEnd      tanggal akhir (Y-m-d)
       $selectedGroup    regu terpilih atau 'all'
       $selectedShift    shift terpilih atau 'all'
       $filterOptions    pilihan regu & shift yang tersedia
       $hasActiveFilter  penanda munculnya tombol Reset
--}}
@php
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

<div class="section-card">
    <div class="section-card__body">
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
                    <input type="hidden" name="dari" value="{{ $selectedStart }}" data-kss-picker="date" data-trigger-class="filter-input" data-placeholder="Tanggal mulai" data-autosubmit-filter>
                </div>

                <div class="filter-field">
                    <label>Sampai Tanggal</label>
                    <input type="hidden" name="sampai" value="{{ $selectedEnd }}" data-kss-picker="date" data-trigger-class="filter-input" data-placeholder="Tanggal akhir" data-autosubmit-filter>
                </div>

                <div class="filter-field">
                    <label>Regu</label>
                    <div class="filter-select-wrapper">
                        <select class="native-select" name="regu" data-autosubmit-filter>
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
                        <select class="native-select" name="shift" data-autosubmit-filter>
                            <option value="all" @selected($selectedShift === 'all')>Semua Shift</option>
                            @foreach ($filterOptions['shifts'] as $shift)
                                <option value="{{ $shift }}" @selected($selectedShift === $shift)>{{ $shiftLabel($shift) }}</option>
                            @endforeach
                        </select>
                        <i class="fi fi-rr-angle-small-down select-arrow"></i>
                    </div>
                </div>

                <div class="perf-toolbar__spacer"></div>

                <div class="filter-field">
                    <label>&nbsp;</label>
                    <div class="archive-toolbar__actions">
                        @if ($hasActiveFilter)
                            <a href="{{ route($formRoute) }}" class="btn-reset">Reset</a>
                        @endif
                        {{-- Filter aktif ikut terbawa lewat query string, jadi berkas
                             yang diunduh selalu sama dengan yang tampil di layar. --}}
                        <a href="{{ route('manajer.performa.export', request()->query()) }}"
                           class="btn-tool btn-tool--primary"
                           title="Unduh rekap performa sesuai filter aktif (Excel)">
                            <i class="fi fi-rr-cloud-upload-alt"></i> Ekspor
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('perf-filter-form');

            document.querySelectorAll('[data-autosubmit-filter]').forEach(control => {
                control.addEventListener('change', () => form?.submit());
            });
        });
    </script>
@endpush
