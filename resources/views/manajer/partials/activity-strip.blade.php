{{-- Strip lima kartu kegiatan operasi.

     Angka pada kartu ini tidak butuh query tambahan: semuanya diambil dari
     matriks agregat yang sudah dihitung untuk kartu KPI di atasnya.

     Satuan ditulis eksplisit di setiap kartu karena tidak seragam — container
     memakai Teus, sisanya Ton. Karena itu pula angka container tidak ikut
     dijumlahkan ke Total Tonase.

     Parameter:
       $cards  baris dari OperationalPerformanceService::activityCards()
--}}
@php
    $cards = array_values($cards ?? []);

    // Angka besar dibulatkan penuh; angka kecil tetap memakai satu desimal
    // supaya muatan sebagian rit tidak terbaca sebagai nol.
    $formatValue = function ($value): string {
        $value = (float) $value;

        return number_format($value, $value > 0 && $value < 100 ? 1 : 0, ',', '.');
    };
@endphp

<div class="act-grid">
    @foreach ($cards as $card)
        @php
            $delta = $card['delta'] ?? [];
            $tone = $delta['tone'] ?? 'flat';
        @endphp

        <div class="act-card">
            <div class="act-card__head">
                <span class="kpi-card__icon kpi-card__icon--{{ $card['tint'] }}"><i class="{{ $card['icon'] }}"></i></span>
                <span class="act-card__label" title="{{ $card['label'] }}">{{ $card['label'] }}</span>
            </div>

            <div class="act-card__row">
                <span class="act-card__value">
                    {{ $formatValue($card['value']) }}<span class="act-card__unit">{{ $card['unit'] }}</span>
                </span>
                @include('charts.delta', ['delta' => $delta])
            </div>

            <span class="act-card__meta">
                @if ($delta['available'] ?? false)
                    {{ $comparisonLabel ?? 'vs periode sebelumnya' }}
                @else
                    {{ $delta['text'] ?? '–' }}
                @endif
            </span>

            @if (($card['sparkline'] ?? '') !== '')
                <svg class="act-card__spark act-card__spark--{{ $tone }}"
                     viewBox="0 0 100 24"
                     preserveAspectRatio="none"
                     aria-hidden="true">
                    <polyline points="{{ $card['sparkline'] }}"></polyline>
                </svg>
            @endif
        </div>
    @endforeach
</div>
