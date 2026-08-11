{{-- Ringkasan kegiatan khusus halaman Kinerja Operasi.

     Angka utama adalah akumulasi periode terpilih. Batang horizontal memecah
     tiap metrik menjadi bulan berjalan dan periode sebelumnya, sehingga
     perbandingan tetap terbaca tanpa mencampur Ton, MT, dan Teus. --}}
@php
    $summary = $summary ?? [];
    $linkQuery = $linkQuery ?? [];
    $rows = $summary['rows'] ?? [];
    $labels = $summary['labels'] ?? [];
    $comparisonCards = collect($comparisonCards ?? [])->keyBy('key');
    $hasPrevious = (bool) ($summary['hasPrevious'] ?? false);
    $formatValue = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
    $decimalsFor = fn ($value, string $unit): int => $unit === 'Teus' || abs((float) $value - round((float) $value)) < 0.005 ? 0 : 2;
@endphp

<section class="performance-activity-overview" aria-labelledby="performance-activity-title">
    <div class="performance-activity-grid">
        @foreach ($rows as $row)
            @php
                $metrics = [[
                    'field' => 'value',
                    'label' => $row['valueLabel'],
                ]];

                if ($row['hasDelivery']) {
                    $metrics[] = ['field' => 'delivery', 'label' => $row['deliveryLabel']];
                }

                if ($row['hasDamage']) {
                    $metrics[] = ['field' => 'damage', 'label' => $row['damageLabel']];
                }

                $isFeatured = count($metrics) > 1;
                $total = $row['total'] ?? [];
                $totalValue = (float) ($total['value'] ?? 0);
                $countValue = (int) ($total['count'] ?? 0);
                $hasData = collect(['count', 'value', 'delivery', 'damage'])
                    ->contains(fn (string $field): bool => (float) ($total[$field] ?? 0) !== 0.0);
                $detailUrl = route('manajer.kegiatan', array_merge($linkQuery, ['kegiatan' => $row['key']]));
                $trend = $comparisonCards->get($row['key'], []);
                $comparison = $trend['comparison'] ?? [];
                $comparisonCurrent = (float) ($comparison['current'] ?? 0);
                $comparisonPrevious = (float) ($comparison['previous'] ?? 0);
                $delta = $trend['delta'] ?? ['available' => false, 'text' => 'Belum ada data', 'direction' => 'flat'];
                $chartTone = $comparisonCurrent > $comparisonPrevious
                    ? 'up'
                    : ($comparisonCurrent < $comparisonPrevious ? 'down' : 'flat');
                $deltaIcon = $chartTone === 'up'
                    ? 'fi fi-rr-arrow-trend-up'
                    : ($chartTone === 'down' ? 'fi fi-rr-arrow-trend-down' : null);
                $deltaText = ($delta['available'] ?? false)
                    ? (($delta['direction'] ?? 'flat') === 'up' ? '+' : (($delta['direction'] ?? 'flat') === 'down' ? '−' : '')).$delta['text']
                    : ($comparisonCurrent > 0 && $comparisonPrevious <= 0 ? 'Baru' : '-');
            @endphp

            <article class="performance-activity-card performance-activity-card--{{ $row['tint'] }} {{ $isFeatured ? 'performance-activity-card--featured' : '' }}">
                <div class="performance-activity-card__header">
                    <div class="performance-activity-card__identity">
                        <span class="performance-activity-card__icon" aria-hidden="true">
                            <i class="{{ $row['icon'] }}"></i>
                        </span>
                        <div>
                            <h3 class="performance-activity-card__title">{{ $row['dashboardLabel'] }}</h3>
                            <span class="performance-activity-card__context">Akumulasi periode terpilih</span>
                        </div>
                    </div>
                    <a href="{{ $detailUrl }}" class="performance-activity-card__link" aria-label="Lihat rincian {{ $row['dashboardLabel'] }}">
                        <span>Lihat rincian</span>
                        <i class="fi fi-rr-arrow-small-right" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="performance-activity-card__stats">
                    <div class="performance-activity-card__stat">
                        <span class="performance-activity-card__stat-label">{{ $row['valueLabel'] }}</span>
                        @if ($hasData)
                            <span class="performance-activity-card__stat-value">
                                {{ $formatValue($totalValue, $decimalsFor($totalValue, $row['unit'])) }}
                                <small>{{ $row['unit'] }}</small>
                            </span>
                        @else
                            <span class="performance-activity-card__stat-value performance-activity-card__stat-value--empty">&mdash;</span>
                        @endif
                    </div>
                    <div class="performance-activity-card__stat">
                        <span class="performance-activity-card__stat-label">{{ $row['summaryCountLabel'] }}</span>
                        @if ($hasData)
                            <span class="performance-activity-card__stat-value">
                                {{ $formatValue($countValue) }}
                                <small>{{ $row['countLabel'] }}</small>
                            </span>
                        @else
                            <span class="performance-activity-card__stat-value performance-activity-card__stat-value--empty">&mdash;</span>
                        @endif
                    </div>
                    <div class="performance-activity-card__trend performance-activity-card__trend--{{ $chartTone }}">
                        <div class="performance-activity-card__trend-head">
                            <span>Tren 6 bulan</span>
                            <span class="performance-activity-card__trend-delta">
                                @if ($deltaIcon)
                                    <i class="{{ $deltaIcon }}" aria-hidden="true"></i>
                                @endif
                                {{ $deltaText }}
                            </span>
                        </div>
                        @include('charts.sparkline', [
                            'points' => $trend['sparkline'] ?? '',
                            'tone' => $chartTone,
                            'id' => 'performance-activity-trend-'.$row['key'],
                            'class' => 'performance-activity-card__spark',
                            'label' => 'Tren enam bulan '.$row['dashboardLabel'].'. '.$deltaText.' '.($comparison['label'] ?? 'dibandingkan periode sebelumnya'),
                        ])
                        <span class="performance-activity-card__trend-note">{{ $comparison['label'] ?? 'vs periode sebelumnya' }}</span>
                    </div>
                </div>

                @if (! $hasData)
                    <div class="performance-activity-card__empty">Belum ada kegiatan tercatat pada periode ini.</div>
                @else
                    <div class="performance-activity-card__comparisons {{ $isFeatured ? 'performance-activity-card__comparisons--featured' : '' }}">
                        @foreach ($metrics as $metric)
                            @php
                                $field = $metric['field'];
                                $currentValue = (float) ($row['month'][$field] ?? 0);
                                $previousValue = (float) ($row['previous'][$field] ?? 0);
                                $maximum = max($currentValue, $previousValue, 1);
                                $currentWidth = $currentValue > 0 ? max(($currentValue / $maximum) * 100, 3) : 0;
                                $previousWidth = $previousValue > 0 ? max(($previousValue / $maximum) * 100, 3) : 0;
                                $currentDecimals = $decimalsFor($currentValue, $row['unit']);
                                $previousDecimals = $decimalsFor($previousValue, $row['unit']);
                            @endphp

                            <div class="performance-activity-comparison">
                                <div class="performance-activity-comparison__heading">
                                    <span>{{ $metric['label'] }}</span>
                                    <span>{{ $row['unit'] }}</span>
                                </div>

                                <div class="performance-activity-comparison__row">
                                    <span class="performance-activity-comparison__label">Bulan berjalan</span>
                                    <span class="performance-activity-comparison__track" aria-hidden="true">
                                        <span class="performance-activity-comparison__fill performance-activity-comparison__fill--current" style="width: {{ number_format($currentWidth, 2, '.', '') }}%"></span>
                                    </span>
                                    <span class="performance-activity-comparison__value">{{ $formatValue($currentValue, $currentDecimals) }}</span>
                                </div>

                                @if ($hasPrevious)
                                    <div class="performance-activity-comparison__row">
                                        <span class="performance-activity-comparison__label">Sebelumnya</span>
                                        <span class="performance-activity-comparison__track" aria-hidden="true">
                                            <span class="performance-activity-comparison__fill performance-activity-comparison__fill--previous" style="width: {{ number_format($previousWidth, 2, '.', '') }}%"></span>
                                        </span>
                                        <span class="performance-activity-comparison__value">{{ $formatValue($previousValue, $previousDecimals) }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</section>
