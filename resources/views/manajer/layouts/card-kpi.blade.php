{{-- Kartu KPI performa operasi untuk dashboard manajer & halaman Performa.

     Halaman Arsip Laporan tetap memakai manajer.layouts.card (class .stat-card)
     yang berisi statistik jumlah laporan — kartu KPI ini memakai class .kpi-card
     tersendiri agar perubahan di dashboard tidak ikut mengubah tampilan arsip.

     Anatomi kartu: baris ikon + judul, angka besar dengan satuan, badge pill
     perubahan di kanan angka, keterangan pembanding, lalu sparkline 6 bulan. --}}
@php
    $kpi = $kpi ?? [];
    $comparisonLabel = $kpi['comparisonLabel'] ?? null;
    $formatValue = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    $kpiCards = [
        [
            'key' => 'tonnage',
            'label' => 'Tonase Ditangani',
            'icon' => 'fi fi-sr-box',
            'tint' => 'blue',
            'unit' => 'Ton',
            'decimals' => 0,
        ],
        [
            'key' => 'ships',
            'label' => 'Kapal Dilayani',
            'icon' => 'fi fi-sr-ship',
            'tint' => 'cyan',
            'unit' => 'Kapal',
            'decimals' => 0,
        ],
        [
            'key' => 'tonnagePerShift',
            'label' => 'Tonase per Shift',
            'icon' => 'fi fi-sr-bolt',
            'tint' => 'green',
            'unit' => 'Ton',
            'decimals' => 1,
        ],
        [
            'key' => 'damageRatio',
            'label' => 'Rasio Kerusakan',
            'icon' => 'fi fi-sr-exclamation',
            'tint' => 'orange',
            'unit' => '%',
            'decimals' => 2,
        ],
    ];
@endphp

<div class="kpi-row">
    @foreach ($kpiCards as $card)
        @php
            $metric = $kpi[$card['key']] ?? ['value' => 0, 'delta' => []];
            $delta = $metric['delta'] ?? ['text' => 'Belum ada data', 'direction' => 'flat', 'tone' => 'flat'];
            $sparkline = $kpi['sparklines'][$card['key']] ?? '';
            // hasBase hanya dikirim untuk rasio kerusakan: nilai 0% tanpa
            // muatan kantong bukan capaian, jadi ditampilkan sebagai strip.
            // Fallback true menjaga data lama di cache tetap tampil.
            $hasBase = $metric['hasBase'] ?? true;
        @endphp

        <div class="kpi-card">
            <div class="kpi-card__head">
                <span class="kpi-card__icon kpi-card__icon--{{ $card['tint'] }}"><i class="{{ $card['icon'] }}"></i></span>
                <span class="kpi-card__label">{{ $card['label'] }}</span>
            </div>

            <div class="kpi-card__row">
                @if ($hasBase)
                    <span class="kpi-card__value">
                        {{ $formatValue($metric['value'] ?? 0, $card['decimals']) }}<span class="kpi-card__unit">{{ $card['unit'] }}</span>
                    </span>
                @else
                    <span class="kpi-card__value kpi-card__value--empty" title="Belum ada muatan kantong pada periode ini">&ndash;</span>
                @endif

                @include('charts.delta', ['delta' => $delta])
            </div>

            <span class="kpi-card__note">
                @if ($delta['available'] ?? false)
                    {{ $comparisonLabel ?? 'vs periode sebelumnya' }}
                @elseif (! $hasBase)
                    Belum ada muatan kantong
                @else
                    {{ $delta['text'] ?? '–' }}
                @endif
            </span>

            @if ($sparkline !== '')
                @php
                    $tone = $delta['tone'] ?? 'flat';
                    $gradientId = 'spark-'.$card['key'];
                    // Titik pertama & terakhir dipakai untuk menutup bentuk isian
                    // sampai ke dasar kanvas, sehingga garisnya punya area.
                    $sparkPoints = explode(' ', $sparkline);
                    $firstX = explode(',', $sparkPoints[0])[0] ?? '0';
                    $lastX = explode(',', end($sparkPoints))[0] ?? '100';
                    $sparkArea = $sparkline.' '.$lastX.',24 '.$firstX.',24';
                @endphp

                <svg class="kpi-card__spark kpi-card__spark--{{ $tone }}"
                     viewBox="0 0 100 24"
                     preserveAspectRatio="none"
                     aria-hidden="true">
                    <defs>
                        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="currentColor" stop-opacity="0.26"></stop>
                            <stop offset="100%" stop-color="currentColor" stop-opacity="0"></stop>
                        </linearGradient>
                    </defs>
                    <polygon class="kpi-card__spark-area" points="{{ $sparkArea }}" fill="url(#{{ $gradientId }})"></polygon>
                    <polyline points="{{ $sparkline }}"></polyline>
                </svg>
            @endif
        </div>
    @endforeach
</div>
