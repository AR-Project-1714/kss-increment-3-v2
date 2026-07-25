{{-- Baris kartu KPI, dipakai bersama dashboard admin, arsip, dan log.

     Setiap kartu berisi ikon + judul, nilai besar dengan satuan, badge pill
     perubahan, dan keterangan pembanding. Sparkline hanya digambar bila
     kartunya menyertakan deret angkanya.

     Parameter:
       $cards  array kartu, tiap kartu berisi:
               label, value, unit, icon, tint, delta, note
               (opsional) sparkline berupa atribut points polyline
--}}
@php
    $cards = $cards ?? [];
@endphp

<div class="kpi-row">
    @foreach ($cards as $card)
        <div class="kpi-card">
            <div class="kpi-card__head">
                <span class="kpi-card__icon kpi-card__icon--{{ $card['tint'] }}"><i class="{{ $card['icon'] }}"></i></span>
                <span class="kpi-card__label">{{ $card['label'] }}</span>
            </div>

            <div class="kpi-card__row">
                <span class="kpi-card__value">
                    {{ $card['value'] }}@if (! empty($card['unit']))<span class="kpi-card__unit">{{ $card['unit'] }}</span>@endif
                </span>

                @include('charts.delta', ['delta' => $card['delta'] ?? []])
            </div>

            {{-- Saat pembanding tidak tersedia, badge tidak dirender dan
                 alasannya yang ditampilkan sebagai catatan. --}}
            <span class="kpi-card__note">
                @if ($card['delta']['available'] ?? false)
                    {{ $card['note'] ?? '' }}
                @else
                    {{ $card['delta']['text'] ?? $card['note'] ?? '' }}
                @endif
            </span>

            @if (! empty($card['sparkline']))
                @php
                    $tone = $card['delta']['tone'] ?? 'flat';
                    $gradientId = 'spark-'.($card['key'] ?? $loop->index);
                    $points = explode(' ', $card['sparkline']);
                    $firstX = explode(',', $points[0])[0] ?? '0';
                    $lastX = explode(',', end($points))[0] ?? '100';
                @endphp

                <svg class="kpi-card__spark kpi-card__spark--{{ $tone }}"
                     viewBox="0 0 100 24" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="currentColor" stop-opacity="0.26"></stop>
                            <stop offset="100%" stop-color="currentColor" stop-opacity="0"></stop>
                        </linearGradient>
                    </defs>
                    <polygon class="kpi-card__spark-area"
                             points="{{ $card['sparkline'].' '.$lastX.',24 '.$firstX.',24' }}"
                             fill="url(#{{ $gradientId }})"></polygon>
                    <polyline points="{{ $card['sparkline'] }}"></polyline>
                </svg>
            @endif
        </div>
    @endforeach
</div>
