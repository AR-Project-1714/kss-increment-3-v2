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

            {{-- Badge delta di atas sudah menyebut arah dan besarnya, jadi
                 sparkline-nya tidak perlu diumumkan lagi ke pembaca layar. --}}
            @include('charts.sparkline', [
                'points' => $card['sparkline'] ?? '',
                'tone' => $card['delta']['tone'] ?? 'flat',
                'id' => 'spark-'.($card['key'] ?? $loop->index),
                'class' => 'kpi-card__spark',
                'label' => null,
            ])
        </div>
    @endforeach
</div>
