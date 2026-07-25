{{-- Grafik tren tonase enam bulan, tersedia dalam bentuk garis dan batang.

     Kedua bentuk digambar sekaligus lalu disembunyikan lewat CSS, sehingga
     pergantian tampilan tidak perlu menggambar ulang dan tetap berfungsi
     bila JavaScript gagal dimuat (bentuk garis yang tampil).

     Parameter:
       $trend  deret bulanan dari OperationalPerformanceService
--}}
@php
    $trend = array_values($trend ?? []);
    $count = count($trend);

    $values = array_map(fn ($row) => (float) $row['tonnage'], $trend);
    $peak = $values === [] ? 0.0 : max($values);
    $sum = array_sum($values);
    $mean = $count > 0 ? $sum / $count : 0.0;

    // Batas atas sumbu dibulatkan ke angka "bulat" terdekat supaya garis
    // bantu jatuh di nilai yang enak dibaca (mis. 70.000, bukan 65.911).
    $niceMax = static function (float $value): float {
        if ($value <= 0) {
            return 1.0;
        }

        $magnitude = 10 ** floor(log10($value));
        $ratio = $value / $magnitude;
        $step = match (true) {
            $ratio <= 1.0 => 1.0,
            $ratio <= 1.5 => 1.5,
            $ratio <= 2.0 => 2.0,
            $ratio <= 3.0 => 3.0,
            $ratio <= 5.0 => 5.0,
            default => 10.0,
        };

        return $step * $magnitude;
    };

    $max = $niceMax($peak);

    // Bidang gambar
    $vbW = 720; $vbH = 250;
    $left = 52; $right = 708; $top = 18; $bottom = 190;
    $plotW = $right - $left;
    $plotH = $bottom - $top;
    $slot = $count > 0 ? $plotW / $count : $plotW;

    $yFor = fn (float $value) => round($bottom - ($max > 0 ? ($value / $max) * $plotH : 0), 2);
    $xFor = fn (int $index) => round($left + ($index + 0.5) * $slot, 2);

    $points = [];
    foreach ($values as $index => $value) {
        $points[] = [$xFor($index), $yFor($value)];
    }

    // Kurva halus dengan titik kendali di tengah dua titik: bentuknya lembut
    // tetapi tidak pernah melampaui nilai aslinya seperti spline biasa.
    $smoothPath = static function (array $points): string {
        if ($points === []) {
            return '';
        }

        $d = 'M '.$points[0][0].','.$points[0][1];

        for ($i = 0; $i < count($points) - 1; $i++) {
            [$x0, $y0] = $points[$i];
            [$x1, $y1] = $points[$i + 1];
            $mid = round(($x0 + $x1) / 2, 2);
            $d .= ' C '.$mid.','.$y0.' '.$mid.','.$y1.' '.$x1.','.$y1;
        }

        return $d;
    };

    $linePath = $smoothPath($points);
    $areaPath = $points === []
        ? ''
        : $linePath.' L '.end($points)[0].','.$bottom.' L '.$points[0][0].','.$bottom.' Z';

    $gridSteps = 4;
    $barWidth = min(46, $slot * 0.45);
    $uid = 'trend-'.substr(md5((string) ($trend[0]['key'] ?? 'x').$count), 0, 6);

    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
@endphp

<div class="chart-stack" data-chart-stack data-chart-view="line">
    <svg class="chart" viewBox="0 0 {{ $vbW }} {{ $vbH }}" preserveAspectRatio="xMidYMid meet"
         role="img" aria-label="Grafik tonase bulanan enam bulan terakhir">
        <defs>
            <linearGradient id="{{ $uid }}-fill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="var(--chart-1)" stop-opacity="0.28"></stop>
                <stop offset="100%" stop-color="var(--chart-1)" stop-opacity="0.02"></stop>
            </linearGradient>
        </defs>

        {{-- Garis bantu horizontal beserta nilainya --}}
        <g class="chart__grid">
            @for ($i = 0; $i <= $gridSteps; $i++)
                @php
                    $value = $max - ($max / $gridSteps) * $i;
                    $y = round($top + ($plotH / $gridSteps) * $i, 2);
                @endphp
                <line x1="{{ $left }}" y1="{{ $y }}" x2="{{ $right }}" y2="{{ $y }}"></line>
                <text class="chart__tick" x="{{ $left - 10 }}" y="{{ $y + 3.5 }}" text-anchor="end">{{ $fmt($value) }}</text>
            @endfor
        </g>

        <line class="chart__baseline" x1="{{ $left }}" y1="{{ $bottom }}" x2="{{ $right }}" y2="{{ $bottom }}"></line>

        {{-- Garis rata-rata tidak diberi label di dalam kanvas: pada lebar
             sempit teksnya bertabrakan dengan grafik, sedangkan angkanya
             sudah disebut di keterangan bawah. --}}
        @if ($mean > 0)
            <line class="chart__mean" x1="{{ $left }}" y1="{{ $yFor($mean) }}" x2="{{ $right }}" y2="{{ $yFor($mean) }}"></line>
        @endif

        <line class="chart__cursor" id="{{ $uid }}-cursor" y1="{{ $top }}" y2="{{ $bottom }}" x1="0" x2="0"></line>

        {{-- Bentuk garis --}}
        <g class="chart-stack__line">
            @if ($areaPath !== '')
                <path d="{{ $areaPath }}" fill="url(#{{ $uid }}-fill)"></path>
            @endif
            @if ($linePath !== '')
                <path class="chart__line" d="{{ $linePath }}" stroke="var(--chart-1)"></path>
            @endif
        </g>

        {{-- Bentuk batang --}}
        <g class="chart-stack__bar">
            @foreach ($values as $index => $value)
                @php
                    $height = max($bottom - $yFor($value), 0);
                    $isNow = $index === $count - 1;
                @endphp
                <rect class="chart__bar"
                      x="{{ round($xFor($index) - $barWidth / 2, 2) }}"
                      y="{{ $yFor($value) }}"
                      width="{{ round($barWidth, 2) }}"
                      height="{{ round($height, 2) }}"
                      rx="5"
                      fill="var(--chart-1)"
                      opacity="{{ $isNow ? '1' : '0.45' }}"></rect>
            @endforeach
        </g>

        {{-- Label bulan --}}
        @foreach ($trend as $index => $row)
            <text class="chart__tick {{ $index === $count - 1 ? 'chart__tick--now' : '' }}"
                  x="{{ $xFor($index) }}" y="{{ $bottom + 20 }}" text-anchor="middle">{{ $row['label'] }}</text>
        @endforeach

        {{-- Titik data & area sasaran kursor. Ditaruh paling akhir agar berada
             di lapisan teratas dan bisa menerima kejadian tetikus. --}}
        @foreach ($trend as $index => $row)
            @php
                $rows = [
                    ['label' => 'Tonase', 'value' => $fmt($row['tonnage'], 1).' Ton', 'color' => 'var(--chart-1)'],
                    ['label' => 'Laporan', 'value' => $fmt($row['reports']), 'color' => 'var(--chart-4)'],
                    ['label' => 'Kapal', 'value' => $fmt($row['ships']), 'color' => 'var(--chart-2)'],
                ];
            @endphp

            <g>
                <rect class="chart__hotspot"
                      data-chart-tip
                      data-tip-title="{{ $row['label'] }} · {{ $fmt($row['reports']) }} laporan"
                      data-tip-rows="{{ json_encode($rows) }}"
                      data-tip-cursor="{{ $uid }}-cursor"
                      data-tip-x="{{ $xFor($index) }}"
                      x="{{ round($left + $index * $slot, 2) }}"
                      y="{{ $top }}"
                      width="{{ round($slot, 2) }}"
                      height="{{ $plotH }}"></rect>

                <g class="chart__marker chart-stack__line">
                    <circle class="chart__dot" cx="{{ $xFor($index) }}" cy="{{ $yFor((float) $row['tonnage']) }}" r="4"
                            fill="var(--chart-1)"></circle>
                </g>
            </g>
        @endforeach
    </svg>

    <div class="perf-chart__footer">
        <span>Total 6 bulan: <strong>{{ $fmt($sum) }} Ton</strong></span>
        <span>Rata-rata bulanan: <strong>{{ $fmt($mean) }} Ton</strong></span>
        <span>Tertinggi: <strong>{{ $fmt($peak) }} Ton</strong></span>
    </div>
</div>
