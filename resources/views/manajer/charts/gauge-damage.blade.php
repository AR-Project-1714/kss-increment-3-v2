{{-- Gauge setengah lingkaran untuk rasio kerusakan muat kantong.

     Skala berhenti di 2% karena di atas angka itu kondisinya sudah sama-sama
     buruk dan detailnya tidak lagi berguna; nilai yang melampaui batas
     ditahan di ujung merah dan angkanya tetap ditulis apa adanya.

     Parameter:
       $value    rasio kerusakan dalam persen
       $hasBase  false bila periode ini tidak punya muatan kantong
       $delta    perubahan terhadap periode pembanding
--}}
@php
    $value = (float) ($value ?? 0);
    $hasBase = $hasBase ?? true;
    $delta = $delta ?? [];

    $scaleMax = 2.0;
    $ratio = $hasBase ? min($value / $scaleMax, 1.0) : 0.0;

    // Ambang: di bawah 0,5% dianggap aman, 0,5–1% perlu dipantau, di atas itu
    // harus ditindaklanjuti. Warna busur mengikuti zona tempat nilainya jatuh.
    $zone = match (true) {
        ! $hasBase => ['color' => 'var(--muted)', 'text' => 'Belum ada muatan kantong'],
        $value < 0.5 => ['color' => 'var(--chart-2)', 'text' => 'Terkendali'],
        $value < 1.0 => ['color' => 'var(--chart-3)', 'text' => 'Perlu dipantau'],
        default => ['color' => 'var(--red-main)', 'text' => 'Perlu tindak lanjut'],
    };

    // Busur setengah lingkaran dari kiri (180°) ke kanan (0°). Titik pusat
    // sengaja tidak di tepi atas kanvas supaya label skala punya ruang.
    $cx = 110; $cy = 108; $r = 76;
    $arcLength = M_PI * $r;
    $dashOffset = round($arcLength * (1 - $ratio), 2);
    $trackPath = 'M '.($cx - $r).','.$cy.' A '.$r.','.$r.' 0 0 1 '.($cx + $r).','.$cy;

    // Jarum ditempatkan pada sudut yang sama dengan ujung busur.
    $angle = M_PI * (1 - $ratio);
    $needleX = round($cx + cos($angle) * ($r - 16), 2);
    $needleY = round($cy - sin($angle) * ($r - 16), 2);

    $fmt = fn ($value, int $decimals = 2) => number_format((float) $value, $decimals, ',', '.');
@endphp

<div class="gauge-wrap">
    <svg class="gauge" viewBox="0 0 220 134" preserveAspectRatio="xMidYMid meet" role="img"
         aria-label="Rasio kerusakan {{ $hasBase ? $fmt($value).' persen' : 'belum tersedia' }}">
        <path class="gauge__track" d="{{ $trackPath }}"></path>

        <path class="gauge__value"
              d="{{ $trackPath }}"
              stroke="{{ $zone['color'] }}"
              stroke-dasharray="{{ round($arcLength, 2) }}"
              stroke-dashoffset="{{ $dashOffset }}"></path>

        {{-- Penanda skala. Posisinya dihitung dari sudut yang sama dengan
             busur, jadi label selalu jatuh tepat di nilainya. --}}
        @foreach ([0.0, 0.5, 1.0, 2.0] as $threshold)
            @php
                $tickAngle = M_PI * (1 - $threshold / $scaleMax);
                $cosine = cos($tickAngle);
                $sine = sin($tickAngle);
                $isEdge = $threshold === 0.0 || $threshold === $scaleMax;
                $anchor = match (true) {
                    $threshold === 0.0 => 'start',
                    $threshold === $scaleMax => 'end',
                    default => 'middle',
                };
            @endphp

            @unless ($isEdge)
                <line class="gauge__tick"
                      x1="{{ round($cx + $cosine * ($r - 9), 2) }}"
                      y1="{{ round($cy - $sine * ($r - 9), 2) }}"
                      x2="{{ round($cx + $cosine * ($r + 9), 2) }}"
                      y2="{{ round($cy - $sine * ($r + 9), 2) }}"></line>
            @endunless

            <text class="chart__tick"
                  x="{{ round($cx + $cosine * ($r + 21), 2) }}"
                  y="{{ round($cy - $sine * ($r + 21) + ($isEdge ? 4 : 0), 2) }}"
                  text-anchor="{{ $anchor }}">{{ $threshold === $scaleMax ? '2%+' : $fmt($threshold, $threshold === 0.5 ? 1 : 0).'%' }}</text>
        @endforeach

        @if ($hasBase)
            <line class="gauge__needle" x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $needleX }}" y2="{{ $needleY }}"></line>
            <circle class="gauge__pivot" cx="{{ $cx }}" cy="{{ $cy }}" r="4"></circle>
        @endif
    </svg>

    <span class="gauge__reading">{{ $hasBase ? $fmt($value).'%' : '–' }}</span>

    <span class="gauge__caption">
        {{ $zone['text'] }}
        @if ($delta['available'] ?? false)
            · <span class="perf-delta perf-delta--{{ $delta['tone'] ?? 'flat' }}">{{ ($delta['direction'] ?? '') === 'up' ? '+' : '−' }}{{ $delta['text'] }}</span>
            {{ $comparisonLabel ?? '' }}
        @endif
    </span>
</div>
