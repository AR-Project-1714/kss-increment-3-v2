{{-- Kartu KPI performa operasi untuk dashboard manajer & halaman Performa.

     Halaman Arsip Laporan tetap memakai manajer.layouts.card (class .stat-card)
     yang berisi statistik jumlah laporan — kartu KPI ini memakai class .kpi-card
     tersendiri agar perubahan di dashboard tidak ikut mengubah tampilan arsip.

     Anatomi kartu: baris ikon + judul, angka besar dengan satuan, badge pill
     perubahan di kanan angka, keterangan pembanding, lalu sparkline 6 bulan.

     Parameter:
       $kpi       nilai tiap indikator + sparkline + label pembanding
       $cardKeys  indikator yang ditampilkan (opsional). Dashboard memakai
                  susunan bawaan; halaman Kinerja Operasi mengganti kartu
                  Kapal Dilayani dengan jumlah laporan masuk karena blok kapal
                  sudah tidak ditampilkan di sana. --}}
@php
    $kpi = $kpi ?? [];
    $comparisonLabel = $kpi['comparisonLabel'] ?? null;
    $formatValue = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    $cardCatalog = [
        'tonnage' => [
            'label' => 'Tonase Ditangani',
            'icon' => 'fi fi-sr-box',
            'tint' => 'blue',
            'unit' => 'Ton/MT',
            'decimals' => 0,
        ],
        'ships' => [
            'label' => 'Kapal Dilayani',
            'icon' => 'fi fi-sr-ship',
            'tint' => 'cyan',
            'unit' => 'Kapal',
            'decimals' => 0,
        ],
        'reports' => [
            'label' => 'Laporan Masuk',
            'icon' => 'fi fi-sr-document',
            'tint' => 'cyan',
            'unit' => 'Laporan',
            'decimals' => 0,
        ],
        'tonnagePerShift' => [
            'label' => 'Tonase per Shift',
            'icon' => 'fi fi-sr-bolt',
            'tint' => 'green',
            'unit' => 'Ton/MT',
            'decimals' => 1,
        ],
        'damageRatio' => [
            'label' => 'Rasio Kerusakan',
            'icon' => 'fi fi-sr-exclamation',
            'tint' => 'orange',
            'unit' => '%',
            'decimals' => 2,
        ],
    ];

    $kpiCards = [];

    foreach ($cardKeys ?? ['tonnage', 'ships', 'tonnagePerShift', 'damageRatio'] as $key) {
        if (isset($cardCatalog[$key])) {
            $kpiCards[] = $cardCatalog[$key] + ['key' => $key];
        }
    }
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

            {{-- Badge delta di atas sudah menyebut arah dan besarnya, jadi
                 sparkline-nya tidak perlu diumumkan lagi ke pembaca layar. --}}
            @include('charts.sparkline', [
                'points' => $sparkline,
                'tone' => $delta['tone'] ?? 'flat',
                'id' => 'spark-'.$card['key'],
                'class' => 'kpi-card__spark',
                'label' => null,
            ])
        </div>
    @endforeach
</div>
