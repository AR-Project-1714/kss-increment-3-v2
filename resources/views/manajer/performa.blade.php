@extends('manajer.layouts.app')

@section('content')
    @php
        $formatValue = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

        $deltaBadge = function (array $delta): string {
            $tone = $delta['tone'] ?? 'flat';
            $text = $delta['available'] ?? false
                ? (($delta['direction'] ?? 'flat') === 'up' ? '+' : (($delta['direction'] ?? 'flat') === 'down' ? '−' : '')).$delta['text']
                : '–';

            return '<span class="perf-delta perf-delta--'.$tone.'">'.e($text).'</span>';
        };

        $shiftLabel = function (?string $shift): string {
            $normalized = strtolower(trim((string) $shift));

            return match (true) {
                in_array($normalized, ['1', 'pagi', 'shift 1'], true) => 'Shift Pagi',
                in_array($normalized, ['2', 'sore', 'siang', 'shift 2'], true) => 'Shift Sore',
                in_array($normalized, ['3', 'malam', 'shift 3'], true) => 'Shift Malam',
                default => $shift ? 'Shift '.$shift : 'Shift -',
            };
        };

        $trend = $report['trend'] ?? [];
        $trendTotal = array_sum(array_column($trend, 'tonnage'));
    @endphp

    <main class="page-content">
        <div class="page-header">
            <span class="page-title">Performa Operasional</span>
            <span class="page-subtitle">Analisis produktivitas divisi operasi berdasarkan laporan harian yang masuk.</span>
        </div>

        {{-- Toolbar periode & filter --}}
        <div class="section-card">
            <div class="section-card__body">
                <form method="GET" action="{{ route('manajer.performa') }}" id="perf-filter-form" autocomplete="off">
                    <div class="perf-toolbar">
                        <div class="filter-field">
                            <label>Periode</label>
                            <div class="perf-toolbar__presets">
                                @foreach ($presets as $value => $label)
                                    <a href="{{ route('manajer.performa', ['periode' => $value, 'regu' => $selectedGroup, 'shift' => $selectedShift]) }}"
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
                                    <a href="{{ route('manajer.performa') }}" class="btn-reset">Reset</a>
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

        @include('manajer.layouts.card-kpi', ['kpi' => $kpi])

        {{-- Baris analitik: grafik tren sebagai kartu utama, komposisi
             kegiatan sebagai panel ringkasan di sampingnya. --}}
        <div class="perf-layout">
            {{-- Tren tonase: garis atau batang --}}
            <div class="section-card">
                <div class="section-card__header">
                    <div class="section-card__heading">
                        <span class="section-card__title">Tren Tonase</span>
                        <span class="section-card__subtitle">6 bulan terakhir. Garis putus-putus adalah rata-rata bulanan.</span>
                    </div>
                    @if ($trendTotal > 0)
                        <div class="section-card__tools">
                            <div class="chart-toggle" role="group" aria-label="Jenis grafik tren">
                                <button type="button" class="chart-toggle__btn is-active" data-chart-switch="line" aria-pressed="true">
                                    <i class="fi fi-rr-chart-line-up" aria-hidden="true"></i> Garis
                                </button>
                                <button type="button" class="chart-toggle__btn" data-chart-switch="bar" aria-pressed="false">
                                    <i class="fi fi-rr-chart-histogram" aria-hidden="true"></i> Batang
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="section-card__body">
                    @if ($trendTotal <= 0)
                        <div class="perf-empty">Belum ada tonase yang tercatat pada enam bulan terakhir.</div>
                    @else
                        @include('manajer.charts.trend-tonnage', ['trend' => $trend])
                    @endif
                </div>
            </div>

            {{-- Komposisi kegiatan --}}
            <div class="section-card">
                <div class="section-card__header">
                    <div class="section-card__heading">
                        <span class="section-card__title">Komposisi Kegiatan</span>
                        <span class="section-card__subtitle">Porsi tonase menurut jenis kegiatan, {{ $report['periodLabel'] }}.</span>
                    </div>
                </div>
                <div class="section-card__body">
                    @include('manajer.charts.donut-activity', ['activities' => $report['activities']])
                </div>
            </div>
        </div>

        {{-- Baris kedua: sebaran shift lintas bulan + rasio kerusakan --}}
        <div class="perf-layout">
            <div class="section-card">
                <div class="section-card__header">
                    <div class="section-card__heading">
                        <span class="section-card__title">Tonase per Shift</span>
                        <span class="section-card__subtitle">Tumpukan tonase bulanan menurut shift kerja, 6 bulan terakhir.</span>
                    </div>
                </div>
                <div class="section-card__body">
                    @include('manajer.charts.area-shift', ['shiftTrend' => $report['shiftTrend'] ?? []])
                </div>
            </div>

            <div class="section-card">
                <div class="section-card__header">
                    <div class="section-card__heading">
                        <span class="section-card__title">Rasio Kerusakan</span>
                        <span class="section-card__subtitle">Kerusakan terhadap tonase muat kantong, {{ $report['periodLabel'] }}.</span>
                    </div>
                </div>
                <div class="section-card__body">
                    @include('manajer.charts.gauge-damage', [
                        'value' => $report['summary']['damageRatio']['value'] ?? 0,
                        'hasBase' => $report['summary']['damageRatio']['hasBase'] ?? true,
                        'delta' => $report['summary']['damageRatio']['delta'] ?? [],
                        'comparisonLabel' => $report['comparisonLabel'],
                    ])
                </div>
            </div>
        </div>

        <div class="perf-layout">
            {{-- Perbandingan regu --}}
            <div class="section-card">
                <div class="section-card__header">
                    <div class="section-card__heading">
                        <span class="section-card__title">Perbandingan Regu</span>
                        <span class="section-card__subtitle">{{ $report['periodLabel'] }} · diurutkan menurut tonase · Δ {{ $report['comparisonLabel'] }}</span>
                    </div>
                </div>
                <div class="section-card__body">
                    @include('manajer.charts.rank-groups', [
                        'groups' => $report['groups'],
                        'comparisonLabel' => $report['comparisonLabel'],
                    ])
                </div>
            </div>

            {{-- Beban kerja & sebaran shift --}}
            <div class="section-card">
                <div class="section-card__header">
                    <div class="section-card__heading">
                        <span class="section-card__title">Beban Kerja</span>
                        <span class="section-card__subtitle">Personil, lembur, dan sebaran tonase antar shift.</span>
                    </div>
                </div>
                <div class="section-card__body">
                    <div>
                        <div class="perf-row">
                            <span class="perf-row__label">Personil rata-rata per shift</span>
                            <span class="perf-row__value">{{ $formatValue($report['workload']['personnelPerShift'], 1) }} orang</span>
                        </div>
                        <div class="perf-row">
                            <span class="perf-row__label">Jam lembur tercatat</span>
                            <span class="perf-row__value">
                                {{ $formatValue($report['workload']['overtimeHours']['value'], 1) }} jam
                                {!! $deltaBadge($report['workload']['overtimeHours']['delta']) !!}
                            </span>
                        </div>
                        <div class="perf-row">
                            <span class="perf-row__label">Entri lembur</span>
                            <span class="perf-row__value">
                                {{ $formatValue($report['workload']['overtimeCount']['value']) }} entri
                                {!! $deltaBadge($report['workload']['overtimeCount']['delta']) !!}
                            </span>
                        </div>
                        <div class="perf-row">
                            <span class="perf-row__label">Relief &amp; pengganti</span>
                            <span class="perf-row__value">
                                {{ $formatValue($report['workload']['reliefCount']['value']) }} kali
                                {!! $deltaBadge($report['workload']['reliefCount']['delta']) !!}
                            </span>
                        </div>
                        <div class="perf-row">
                            <span class="perf-row__label">Ketepatan waktu lapor</span>
                            <span class="perf-row__value">
                                {{ $formatValue($report['workload']['punctuality']['value'], 1) }}%
                                {!! $deltaBadge($report['workload']['punctuality']['delta']) !!}
                            </span>
                        </div>

                        @if (! empty($report['shifts']))
                            <span class="perf-subhead">Sebaran Tonase per Shift</span>
                        @endif

                        @foreach ($report['shifts'] as $shift)
                            <div class="perf-row">
                                <span class="perf-row__label">{{ $shiftLabel($shift['name']) }}</span>
                                <span class="perf-row__value">
                                    {{ $formatValue($shift['tonnage'], 1) }} Ton
                                    <span class="perf-table__muted" style="font-weight: 400;">· {{ $shift['reports'] }} laporan</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Peringkat lembur: selebar halaman supaya kedua daftarnya
             (jam dan frekuensi) muat bersebelahan. --}}
        <div class="section-card">
            <div class="section-card__header">
                <div class="section-card__heading">
                    <span class="section-card__title">Peringkat Lembur</span>
                    <span class="section-card__subtitle">Lima personil dengan lembur terbanyak, {{ $report['periodLabel'] }}.</span>
                </div>
            </div>
            <div class="section-card__body">
                @include('manajer.charts.overtime-leaders', ['leaders' => $report['overtimeLeaders'] ?? []])
            </div>
        </div>

        {{-- Kapal dilayani --}}
        <div class="section-card">
            <div class="section-card__header">
                <div class="section-card__heading">
                    <span class="section-card__title">Kapal Dilayani</span>
                    <span class="section-card__subtitle">Satu baris mewakili satu kunjungan kapal pada periode ini.</span>
                </div>
            </div>
            <div class="section-card__body">
                @if (empty($report['ships']))
                    <div class="perf-empty">Belum ada kegiatan kapal pada periode ini.</div>
                @else
                    <div class="table-responsive-wrapper">
                        <table class="perf-table" style="min-width: 860px;">
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Kapal</th>
                                    <th style="width: 110px;">Jenis</th>
                                    <th style="width: 120px;">Agen</th>
                                    <th style="width: 90px;">Dermaga</th>
                                    <th style="width: 100px;">Kapasitas</th>
                                    <th style="width: 100px;">Termuat</th>
                                    <th>Realisasi</th>
                                    <th style="width: 110px;">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['ships'] as $ship)
                                    <tr>
                                        <td class="perf-table__name">{{ $ship['ship_name'] }}</td>
                                        <td><span class="perf-tag {{ $ship['type'] === 'Muat Curah' ? 'perf-tag--curah' : '' }}">{{ $ship['type'] }}</span></td>
                                        <td class="perf-table__muted">{{ $ship['agent'] }}</td>
                                        <td class="perf-table__muted">{{ $ship['jetty'] }}</td>
                                        <td>{{ $formatValue($ship['capacity'], 0) }} Ton</td>
                                        <td>{{ $formatValue($ship['loaded'], 1) }} Ton</td>
                                        <td>
                                            @if ($ship['realization'] === null)
                                                <span class="perf-table__muted">Kapasitas belum diisi</span>
                                            @else
                                                <span>{{ $formatValue($ship['realization'], 1) }}%</span>
                                                <div class="perf-bar__track" style="margin-top: 6px;">
                                                    <span class="perf-bar__fill" style="width: {{ min(round($ship['realization'], 2), 100) }}%;"></span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="perf-table__muted">
                                            {{ $ship['moment'] ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/components/charts.js') }}?v={{ @filemtime(public_path('js/components/charts.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('perf-filter-form');

            document.querySelectorAll('[data-autosubmit-filter]').forEach(control => {
                control.addEventListener('change', () => form?.submit());
            });
        });
    </script>
@endpush
