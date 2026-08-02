@extends('manajer.layouts.app')

@section('title', 'Kinerja Operasi - Manajer')

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
        // Ketiga satuan ikut dihitung untuk menentukan empty state. Ton dan MT
        // berbagi skala massa, tetapi tetap menjadi seri terpisah karena MT
        // berasal dari pembacaan COB curah/amoniak.
        $trendTotal = array_sum(array_column($trend, 'ton'))
            + array_sum(array_column($trend, 'metricTons'))
            + array_sum(array_column($trend, 'teus'));
    @endphp

    <main class="page-content">
        <div class="page-header performance-page-header">
            <div class="performance-page-header__heading">
                <span class="page-title">Kinerja Operasi</span>
                <span class="page-subtitle">
                    Ringkasan kegiatan dan analitik operasi pada {{ $report['periodLabel'] }}.
                    Ton, MT, dan Teus selalu diberi label sesuai sumbernya.
                </span>
            </div>

            @include('manajer.partials.performance-toolbar', ['formRoute' => 'manajer.performa'])
        </div>

                @include('manajer.partials.performance-activity-cards', [
                    'summary' => $report['activityRecap'] ?? [],
                    'comparisonCards' => $report['activityCards'] ?? [],
                    'periodLabel' => $report['periodLabel'],
                    'linkQuery' => request()->only(['periode', 'dari', 'sampai', 'regu', 'shift']),
                ])

                {{-- Grafik utama memakai seluruh bidang agar enam bulan dan tiga
                     satuan tetap terbaca tanpa menyisakan kolom kosong. --}}
                <div class="section-card">
                    <div class="section-card__header">
                        <div class="section-card__heading">
                            <span class="section-card__title">Tren Kuantum</span>
                            <span class="section-card__subtitle">6 bulan terakhir. Ton mencakup kegiatan umum, MT berasal dari COB Muat Curah dan Amoniak, sedangkan Teus khusus container.</span>
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
                            <div class="perf-empty">Belum ada kuantum Ton, MT, maupun Teus yang tercatat pada enam bulan terakhir.</div>
                        @else
                            @include('manajer.charts.trend-tonnage', ['trend' => $trend])
                        @endif
                    </div>
                </div>

                {{-- Ringkasan komposisi memimpin baris; rasio kerusakan menjadi
                     indikator risiko pendamping pada kolom yang lebih ringkas. --}}
                <div class="perf-layout">
                    <div class="section-card performance-composition-card">
                        <div class="section-card__header">
                            <div class="section-card__heading">
                                <span class="section-card__title">Komposisi Kegiatan</span>
                                <span class="section-card__subtitle">Porsi massa menurut kegiatan bersatuan Ton dan MT, {{ $report['periodLabel'] }}.</span>
                            </div>
                        </div>
                        <div class="section-card__body">
                            @include('manajer.charts.donut-activity', ['activities' => $report['activities']])
                        </div>
                    </div>

                    <div class="section-card performance-damage-card">
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

                {{-- Kuantum per shift mengikuti lebar Tren Kuantum. Tiga grafik
                     tetap berada dalam satu alur vertikal agar skala tidak saling
                     tercampur dan legend mempunyai ruang napas yang konsisten. --}}
                <div class="section-card">
                    <div class="section-card__header">
                        <div class="section-card__heading">
                            <span class="section-card__title">Kuantum per Shift</span>
                            <span class="section-card__subtitle">Ton, MT, dan Teus ditampilkan pada grafik terpisah agar sumber satuannya tetap jelas.</span>
                        </div>
                    </div>
                    <div class="section-card__body">
                        <div class="quantum-shift-grid">
                            <div class="quantum-shift-grid__panel">
                                <span class="quantum-shift-grid__title">Tonase</span>
                                <span class="quantum-shift-grid__subtitle">Pupuk kantong, bahan baku, dan trucking</span>
                                <div class="quantum-shift-grid__chart">
                                    @include('manajer.charts.area-shift', [
                                        'shiftTrend' => $report['shiftTrend'] ?? [],
                                        'unit' => 'Ton',
                                    ])
                                </div>
                            </div>
                            <div class="quantum-shift-grid__panel">
                                <span class="quantum-shift-grid__title">COB</span>
                                <span class="quantum-shift-grid__subtitle">Muat Curah dan Amoniak</span>
                                <div class="quantum-shift-grid__chart">
                                    @include('manajer.charts.area-shift', [
                                        'shiftTrend' => $report['shiftTrendMt'] ?? [],
                                        'unit' => 'MT',
                                    ])
                                </div>
                            </div>
                            <div class="quantum-shift-grid__panel">
                                <span class="quantum-shift-grid__title">Container</span>
                                <span class="quantum-shift-grid__subtitle">Bongkar Empty dan Muat Full</span>
                                <div class="quantum-shift-grid__chart">
                                    @include('manajer.charts.area-shift', [
                                        'shiftTrend' => $report['shiftTrendTeus'] ?? [],
                                        'unit' => 'Teus',
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="perf-layout">
                    {{-- Perbandingan regu --}}
                    <div class="section-card">
                        <div class="section-card__header">
                            <div class="section-card__heading">
                                <span class="section-card__title">Perbandingan Regu</span>
                                <span class="section-card__subtitle">Diurutkan menurut massa Ton/MT. Perubahan dihitung {{ $report['comparisonLabel'] }}.</span>
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
                                <span class="section-card__subtitle">Personil, lembur, dan sebaran massa Ton/MT antar shift.</span>
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
                                    <span class="perf-subhead">Massa per Shift pada Periode Ini</span>
                                @endif

                                @foreach ($report['shifts'] as $shift)
                                    <div class="perf-row">
                                        <span class="perf-row__label">
                                            {{ $shiftLabel($shift['name']) }}
                                            <span class="perf-table__muted">{{ $shift['reports'] }} laporan</span>
                                        </span>
                                        <span class="perf-row__value">{{ $formatValue($shift['tonnage'], 1) }} Ton/MT</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Peringkat lembur lintas kegiatan, diurutkan menurut total jam. --}}
                <div class="section-card">
                    <div class="section-card__header">
                        <div class="section-card__heading">
                            <span class="section-card__title">Peringkat Lembur</span>
                            <span class="section-card__subtitle">Diurutkan menurut total jam, {{ $report['periodLabel'] }}. Perubahan posisi {{ $report['comparisonLabel'] }}.</span>
                        </div>
                    </div>
                    <div class="section-card__body">
                        @include('manajer.charts.overtime-leaders', ['leaders' => $report['overtimeLeaders'] ?? []])
                    </div>
                </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/components/charts.js') }}?v={{ @filemtime(public_path('js/components/charts.js')) }}"></script>
@endpush
