{{-- Grid ringkasan kegiatan untuk Dashboard dan Kinerja Operasi.

     Angka berasal dari OperationalPerformanceService::activityRecap(). Ton dan
     Teus tidak pernah dijumlahkan. Dashboard menampilkan ketujuh kelompok agar
     katalog tetap terlihat; Kinerja Operasi boleh menyembunyikan kelompok yang
     seluruh metriknya kosong. Dashboard memakai kartu KPI kegiatan tanpa
     pembungkus section-card, sedangkan Kinerja Operasi memakai grup datar di
     satu surface.
--}}
@php
    $showEmptyGroups = (bool) ($showEmptyGroups ?? false);
    $variant = $variant ?? 'groups';
    $asCards = $variant === 'cards';
    $linkQuery = $linkQuery ?? [];
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    $cards = [];

    foreach ($summary['rows'] ?? [] as $row) {
        $total = $row['total'] ?? [];
        $hasData = collect(['count', 'value', 'delivery', 'damage'])
            ->contains(fn (string $field): bool => (float) ($total[$field] ?? 0) !== 0.0);

        if ($hasData || $showEmptyGroups) {
            $metrics = [];

            if (! $asCards) {
                $metrics = [[
                    'field' => 'count',
                    'label' => $row['summaryCountLabel'],
                    'unit' => $row['countLabel'],
                    'decimals' => 0,
                ]];

                if ($row['hasDelivery']) {
                    $metrics[] = ['field' => 'delivery', 'label' => $row['deliveryLabel'], 'unit' => $row['unit'], 'decimals' => 2];
                }

                $metrics[] = [
                    'field' => 'value',
                    'label' => $row['valueLabel'],
                    'unit' => $row['unit'],
                    'decimals' => $row['unit'] === 'Teus' ? 0 : 2,
                ];

                if ($row['hasDamage']) {
                    $metrics[] = ['field' => 'damage', 'label' => $row['damageLabel'], 'unit' => $row['unit'], 'decimals' => 2];
                }
            }

            $cards[] = $row + ['metrics' => $metrics, 'values' => $total, 'hasData' => $hasData];
        }
    }
@endphp

@if ($cards === [])
    <div class="perf-empty">Belum ada kegiatan operasi tercatat pada periode ini.</div>
@else
    <div class="operation-summary-grid {{ $asCards ? 'operation-summary-grid--cards' : '' }}">
        @foreach ($cards as $card)
            @php
                $detailUrl = $asCards
                    ? route('manajer.performa', $linkQuery)
                    : route('manajer.kegiatan', array_merge($linkQuery, ['kegiatan' => $card['key']]));
                $cardTitle = $asCards ? $card['dashboardLabel'] : $card['summaryLabel'];
            @endphp
            <article class="operation-summary-group {{ $asCards ? 'operation-summary-group--card' : '' }}">
                @if ($asCards)
                    @php
                        $primaryValue = (float) ($card['values']['value'] ?? 0);
                        $primaryDecimals = abs($primaryValue - round($primaryValue)) < 0.005 ? 0 : 2;
                        $countValue = (int) ($card['values']['count'] ?? 0);
                        $comparison = $card['comparison'] ?? [];
                        $delta = $comparison['delta'] ?? ['available' => false, 'text' => 'Belum ada pembanding', 'tone' => 'flat', 'direction' => 'flat'];
                        $deltaIcon = match ($delta['direction'] ?? 'flat') {
                            'up' => 'fi fi-rr-arrow-trend-up',
                            'down' => 'fi fi-rr-arrow-trend-down',
                            default => null,
                        };
                        $deltaText = ($delta['available'] ?? false)
                            ? $delta['text']
                            : (((float) ($comparison['current'] ?? 0)) > 0 ? 'Baru bulan ini' : 'Belum ada pembanding');
                    @endphp

                    <div class="activity-dashboard-card__header">
                        <div class="activity-dashboard-card__identity">
                            <span class="operation-summary-group__icon operation-summary-group__icon--{{ $card['tint'] }}" aria-hidden="true">
                                <i class="{{ $card['icon'] }}"></i>
                            </span>
                            <span class="operation-summary-group__title">{{ $cardTitle }}</span>
                        </div>
                        <a href="{{ $detailUrl }}" class="activity-dashboard-card__arrow"
                           aria-label="Buka Kinerja Operasi untuk {{ $cardTitle }}">
                            <i class="fi fi-rr-arrow-small-right" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="activity-dashboard-card__body">
                        <div class="activity-dashboard-card__primary">
                            @if ($card['hasData'])
                                <span class="activity-dashboard-card__value">{{ $fmt($primaryValue, $primaryDecimals) }}</span>
                                <span class="activity-dashboard-card__unit">{{ $card['unit'] }}</span>
                            @else
                                <span class="activity-dashboard-card__value activity-dashboard-card__value--empty"
                                      aria-label="Belum ada {{ strtolower($card['valueLabel']) }} pada tahun berjalan">&mdash;</span>
                            @endif
                        </div>

                        <span class="activity-dashboard-card__delta activity-dashboard-card__delta--{{ $delta['tone'] ?? 'flat' }}">
                            @if ($deltaIcon)
                                <i class="{{ $deltaIcon }}" aria-hidden="true"></i>
                            @endif
                            <span>{{ $deltaText }}</span>
                        </span>
                    </div>

                    @php
                        $countPeriodSuffix = strcasecmp($card['summaryCountLabel'], 'Kapal') === 0
                            ? ''
                            : ' sejak awal tahun';
                    @endphp
                    <span class="activity-dashboard-card__count">
                        @if ($card['hasData'])
                            <strong>{{ $fmt($countValue) }}</strong> {{ $card['summaryCountLabel'] }}{{ $countPeriodSuffix }}
                        @else
                            Belum ada {{ strtolower($card['summaryCountLabel']) }}{{ $countPeriodSuffix }}
                        @endif
                    </span>
                @else
                    <div class="operation-summary-group__head">
                        <span class="operation-summary-group__icon operation-summary-group__icon--{{ $card['tint'] }}" aria-hidden="true">
                            <i class="{{ $card['icon'] }}"></i>
                        </span>
                        <a href="{{ $detailUrl }}" class="operation-summary-group__title">{{ $cardTitle }}</a>
                    </div>

                    <dl class="operation-summary-group__metrics">
                        @foreach ($card['metrics'] as $metric)
                            <div class="operation-summary-group__metric">
                                <dt>{{ $metric['label'] }}</dt>
                                <dd @if (! $card['hasData']) aria-label="{{ $metric['label'] }}: belum ada data pada periode ini" @endif>
                                    @if ($card['hasData'])
                                        <span>{{ $fmt($card['values'][$metric['field']] ?? 0, $metric['decimals']) }}</span>
                                        @if ($metric['unit'])
                                            <small>{{ $metric['unit'] }}</small>
                                        @endif
                                    @else
                                        <span class="operation-summary-group__empty" aria-hidden="true">&mdash;</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    <a href="{{ $detailUrl }}" class="operation-summary-group__link">
                        <span>Lihat rincian</span>
                        <i class="fi fi-rr-arrow-small-right" aria-hidden="true"></i>
                    </a>
                @endif
            </article>
        @endforeach
    </div>
@endif
