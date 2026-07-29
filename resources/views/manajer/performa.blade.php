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
        // Teus ikut dihitung: bulan yang hanya berisi kegiatan container tetap
        // punya isi untuk digambar, meski tonasenya nol.
        $trendTotal = array_sum(array_column($trend, 'tonnage'))
            + array_sum(array_column($trend, 'teus'));
    @endphp

    <main class="page-content">
        <div class="page-header performance-page-header">
            <div class="performance-page-header__heading">
                <span class="page-title">Ringkasan Kinerja Operasi</span>
                <span class="page-subtitle">
                    Capaian seluruh divisi pada {{ $report['periodLabel'] }}: tonase, komposisi kegiatan,
                    sebaran shift, perbandingan regu, dan beban kerja.
                </span>
            </div>

            @include('manajer.partials.performance-toolbar', ['formRoute' => 'manajer.performa'])
        </div>

                {{-- Empat angka utama menjadi pembuka ringkasan. Kapal Dilayani
                     tetap berada di dashboard; di sini tempatnya dipakai jumlah
                     laporan yang menjadi dasar seluruh analitik. --}}
                @include('manajer.layouts.card-kpi', [
                    'kpi' => $kpi,
                    'cardKeys' => ['tonnage', 'reports', 'tonnagePerShift', 'damageRatio'],
                ])

                {{-- Baris analitik: grafik tren sebagai kartu utama, komposisi
                     kegiatan sebagai panel ringkasan di sampingnya. --}}
                <div class="perf-layout">
                    {{-- Tren tonase: garis atau batang --}}
                    <div class="section-card">
                        <div class="section-card__header">
                            <div class="section-card__heading">
                                <span class="section-card__title">Tren Tonase &amp; Teus</span>
                                <span class="section-card__subtitle">6 bulan terakhir. Ton dan Teus punya sumbu sendiri karena satuannya berbeda. Garis putus-putus mendatar adalah rata-rata tonase bulanan.</span>
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
                                <div class="perf-empty">Belum ada tonase maupun teus yang tercatat pada enam bulan terakhir.</div>
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
                                <span class="section-card__subtitle">Porsi tonase menurut jenis kegiatan bersatuan Ton, {{ $report['periodLabel'] }}.</span>
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
                                <span class="section-card__subtitle">Diurutkan menurut tonase. Perubahan dihitung {{ $report['comparisonLabel'] }}.</span>
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
                                    <span class="perf-subhead">Tonase per Shift pada Periode Ini</span>
                                @endif

                                @foreach ($report['shifts'] as $shift)
                                    <div class="perf-row">
                                        <span class="perf-row__label">
                                            {{ $shiftLabel($shift['name']) }}
                                            <span class="perf-table__muted">{{ $shift['reports'] }} laporan</span>
                                        </span>
                                        <span class="perf-row__value">{{ $formatValue($shift['tonnage'], 1) }} Ton</span>
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
                            <span class="section-card__subtitle">Sepuluh personil teratas, {{ $report['periodLabel'] }}. Daftar penuh dibuka lewat tautan di bawah tiap kolom.</span>
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
