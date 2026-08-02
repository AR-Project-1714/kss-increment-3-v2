{{-- Grafik tren kuantum enam bulan, tersedia dalam bentuk garis dan batang.

     Ton untuk kegiatan umum dan MT untuk COB curah/amoniak berbagi sumbu massa
     di kiri. Container memakai sumbu Teus di kanan. Ketiga seri tidak dilebur
     menjadi satu garis sehingga satuan sumber selalu terlihat.

     Kedua bentuk digambar sekaligus lalu disembunyikan lewat CSS, sehingga
     pergantian tampilan tidak perlu menggambar ulang dan tetap berfungsi
     bila JavaScript gagal dimuat (bentuk garis yang tampil).

     Kanvas SVG diregangkan mengikuti lebar kartu (preserveAspectRatio="none"),
     jadi isinya hanya bentuk data. Sumbu, garis bantu, dan angkanya berupa
     elemen HTML di atas kanvas supaya ukurannya tetap dalam piksel — di dalam
     viewBox, hurufnya ikut melar dan terlihat lonjong pada kartu yang lebar.

     Parameter:
       $trend  deret bulanan dari OperationalPerformanceService
--}}
@php
    $trend = array_values($trend ?? []);
    $count = count($trend);

    $tonValues = array_map(fn ($row) => (float) ($row['ton'] ?? 0), $trend);
    $mtValues = array_map(fn ($row) => (float) ($row['metricTons'] ?? 0), $trend);
    $teusValues = array_map(fn ($row) => (float) ($row['teus'] ?? 0), $trend);

    $tonPeak = $tonValues === [] ? 0.0 : max($tonValues);
    $mtPeak = $mtValues === [] ? 0.0 : max($mtValues);
    $teusPeak = $teusValues === [] ? 0.0 : max($teusValues);

    $tonSum = array_sum($tonValues);
    $mtSum = array_sum($mtValues);
    $teusSum = array_sum($teusValues);

    $hasMt = $mtPeak > 0;
    $hasTeus = $teusPeak > 0;

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

    $max = $niceMax(max($tonPeak, $mtPeak));
    $teusMax = $niceMax($teusPeak);

    // Bidang gambar — viewBox kini sebatas bidang plot saja.
    $vbW = 720; $vbH = 210;
    $top = 8; $bottom = 202;
    $plotH = $bottom - $top;
    $slot = $count > 0 ? $vbW / $count : $vbW;

    $yForMass = fn (float $value) => round($bottom - ($max > 0 ? ($value / $max) * $plotH : 0), 2);
    $yForTeus = fn (float $value) => round($bottom - ($teusMax > 0 ? ($value / $teusMax) * $plotH : 0), 2);
    $xFor = fn (int $index) => round(($index + 0.5) * $slot, 2);

    // Posisi elemen HTML dinyatakan dalam persen bidang plot, sehingga tetap
    // sejajar dengan kanvas berapa pun lebar kartunya.
    $pctX = fn (float $x) => round($x / $vbW * 100, 3);
    $pctY = fn (float $y) => round($y / $vbH * 100, 3);

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

    $tonPoints = [];
    foreach ($tonValues as $index => $value) {
        $tonPoints[] = [$xFor($index), $yForMass($value)];
    }

    $mtPoints = [];
    foreach ($mtValues as $index => $value) {
        $mtPoints[] = [$xFor($index), $yForMass($value)];
    }

    $teusPoints = [];
    foreach ($teusValues as $index => $value) {
        $teusPoints[] = [$xFor($index), $yForTeus($value)];
    }

    $tonLinePath = $smoothPath($tonPoints);
    $areaPath = $tonPoints === []
        ? ''
        : $tonLinePath.' L '.end($tonPoints)[0].','.$bottom.' L '.$tonPoints[0][0].','.$bottom.' Z';
    $mtLinePath = $hasMt ? $smoothPath($mtPoints) : '';
    $teusLinePath = $hasTeus ? $smoothPath($teusPoints) : '';

    $gridSteps = 4;

    // Setiap seri mendapat batang sendiri dan keseluruhan kelompok tetap
    // dipusatkan pada bulan yang sama.
    $seriesCount = 1 + ($hasMt ? 1 : 0) + ($hasTeus ? 1 : 0);
    $barWidth = min(30, $slot * (0.72 / $seriesCount));
    $barGap = $seriesCount > 1 ? min(6, $slot * 0.04) : 0.0;
    $barGroupWidth = ($seriesCount * $barWidth) + (($seriesCount - 1) * $barGap);
    $barOffsets = [];

    for ($index = 0; $index < $seriesCount; $index++) {
        $barOffsets[] = -($barGroupWidth / 2) + ($barWidth / 2) + ($index * ($barWidth + $barGap));
    }

    $uid = 'trend-'.substr(md5((string) ($trend[0]['key'] ?? 'x').$count), 0, 6);

    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    // Nilai sumbu dihitung lebih dulu agar lebar selokan kiri dan kanan bisa
    // disesuaikan dengan angka terpanjang masing-masing.
    $ticksFor = static function (float $axisMax) use ($gridSteps, $top, $plotH, $fmt): array {
        $ticks = [];

        for ($i = 0; $i <= $gridSteps; $i++) {
            $ticks[] = [
                'text' => $fmt($axisMax - ($axisMax / $gridSteps) * $i),
                'y' => round($top + ($plotH / $gridSteps) * $i, 2),
            ];
        }

        return $ticks;
    };

    $gutterFor = static fn (array $ticks): int
        => (int) round(max(array_map(fn ($tick) => mb_strlen($tick['text']), $ticks)) * 6.6) + 12;

    $yTicks = $ticksFor($max);
    $teusTicks = $hasTeus ? $ticksFor($teusMax) : [];

    $gutter = $gutterFor($yTicks);
    $gutterRight = $hasTeus ? $gutterFor($teusTicks) : 0;

    $tonColor = 'var(--chart-1)';
    $mtColor = 'var(--chart-2)';
    $teusColor = 'var(--chart-5)';
@endphp

<div class="chart-stack" data-chart-stack data-chart-view="line">
    <div class="chart-frame" style="--chart-gutter: {{ $gutter }}px; --chart-gutter-right: {{ $gutterRight }}px;">
        {{-- Garis bantu horizontal beserta nilainya. Kedua sumbu memakai
             jumlah langkah yang sama, jadi satu berkas garis bantu melayani
             deret Ton maupun Teus. --}}
        @foreach ($yTicks as $step => $tick)
            @if ($tick['y'] < $bottom)
                <span class="chart-rule" style="top: {{ $pctY($tick['y']) }}%" aria-hidden="true"></span>
            @endif
            <span class="chart-axis chart-axis--y" style="top: {{ $pctY($tick['y']) }}%">{{ $tick['text'] }}</span>
            @if ($hasTeus)
                <span class="chart-axis chart-axis--y2" style="top: {{ $pctY($tick['y']) }}%">{{ $teusTicks[$step]['text'] }}</span>
            @endif
        @endforeach

        <span class="chart-rule chart-rule--base" style="top: {{ $pctY($bottom) }}%" aria-hidden="true"></span>

        <svg class="chart" viewBox="0 0 {{ $vbW }} {{ $vbH }}" preserveAspectRatio="none"
             role="img" aria-label="Grafik kuantum Ton, MT, dan Teus bulanan enam bulan terakhir">
            <defs>
                <linearGradient id="{{ $uid }}-fill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="{{ $tonColor }}" stop-opacity="0.28"></stop>
                    <stop offset="100%" stop-color="{{ $tonColor }}" stop-opacity="0.02"></stop>
                </linearGradient>
            </defs>

            <line class="chart__cursor" id="{{ $uid }}-cursor" y1="{{ $top }}" y2="{{ $bottom }}" x1="0" x2="0"></line>

            {{-- Bentuk garis --}}
            <g class="chart-stack__line">
                @if ($areaPath !== '')
                    <path d="{{ $areaPath }}" fill="url(#{{ $uid }}-fill)"></path>
                @endif
                @if ($tonLinePath !== '')
                    <path class="chart__line" d="{{ $tonLinePath }}" stroke="{{ $tonColor }}"></path>
                @endif
                @if ($mtLinePath !== '')
                    <path class="chart__line chart__line--mt" d="{{ $mtLinePath }}" stroke="{{ $mtColor }}"></path>
                @endif
                @if ($teusLinePath !== '')
                    {{-- Tanpa area: bidang di bawahnya sudah dipakai deret Ton,
                         dan skalanya berbeda sehingga tidak boleh terbaca
                         sebagai tumpukan. --}}
                    <path class="chart__line chart__line--alt" d="{{ $teusLinePath }}" stroke="{{ $teusColor }}"></path>
                @endif
            </g>

            {{-- Bentuk batang: satu pasang per periode bila Teus tersedia. --}}
            <g class="chart-stack__bar">
                @foreach ($trend as $index => $row)
                    @php
                        $isNow = $index === $count - 1;
                        $bars = array_values(array_filter([
                            ['y' => $yForMass($tonValues[$index]), 'color' => $tonColor],
                            $hasMt
                                ? ['y' => $yForMass($mtValues[$index]), 'color' => $mtColor]
                                : null,
                            $hasTeus
                                ? ['y' => $yForTeus($teusValues[$index]), 'color' => $teusColor]
                                : null,
                        ]));
                    @endphp

                    @foreach ($bars as $serie => $bar)
                        <rect class="chart__bar"
                              x="{{ round($xFor($index) + $barOffsets[$serie] - ($barWidth / 2), 2) }}"
                              y="{{ $bar['y'] }}"
                              width="{{ round($barWidth, 2) }}"
                              height="{{ round(max($bottom - $bar['y'], 0), 2) }}"
                              rx="5"
                              fill="{{ $bar['color'] }}"
                              opacity="{{ $isNow ? '1' : '0.45' }}"></rect>
                    @endforeach
                @endforeach
            </g>

            {{-- Titik data & area sasaran kursor. Ditaruh paling akhir agar berada
                 di lapisan teratas dan bisa menerima kejadian tetikus. --}}
            @foreach ($trend as $index => $row)
                @php
                    // Kunjungan kapal hanya dihitung untuk dashboard; pada
                    // Kinerja Operasi barisnya memang tidak ada, jadi tooltip
                    // tidak boleh menampilkan "0 kapal" yang menyesatkan.
                    $rows = array_values(array_filter([
                        ['label' => 'Kegiatan umum', 'value' => $fmt($tonValues[$index], 1).' Ton', 'color' => $tonColor],
                        $hasMt
                            ? ['label' => 'COB Curah/Amoniak', 'value' => $fmt($mtValues[$index], 1).' MT', 'color' => $mtColor]
                            : null,
                        $hasTeus
                            ? ['label' => 'Container', 'value' => $fmt($teusValues[$index]).' Teus', 'color' => $teusColor]
                            : null,
                        ['label' => 'Laporan', 'value' => $fmt($row['reports']), 'color' => 'var(--chart-4)'],
                        ($row['ships'] ?? 0) > 0
                            ? ['label' => 'Kapal', 'value' => $fmt($row['ships']), 'color' => 'var(--chart-2)']
                            : null,
                    ]));
                    $accessibleSummary = $row['label'].'. '.implode('. ', array_map(
                        fn (array $item): string => $item['label'].': '.$item['value'],
                        $rows
                    ));
                @endphp

                <g>
                    <rect class="chart__hotspot"
                          data-chart-tip
                          data-tip-title="{{ $row['label'] }} · {{ $fmt($row['reports']) }} laporan"
                          data-tip-rows="{{ json_encode($rows) }}"
                          data-tip-cursor="{{ $uid }}-cursor"
                          data-tip-x="{{ $xFor($index) }}"
                          data-tip-marker="{{ $uid }}-marker-{{ $index }}"
                          tabindex="0"
                          role="img"
                          aria-label="{{ $accessibleSummary }}"
                          x="{{ round($index * $slot, 2) }}"
                          y="{{ $top }}"
                          width="{{ round($slot, 2) }}"
                          height="{{ $plotH }}"></rect>
                </g>
            @endforeach
        </svg>

        {{-- Marker berada di lapisan HTML agar diameternya tetap dalam piksel.
             Circle di dalam SVG akan ikut melebar karena kanvas memakai
             preserveAspectRatio="none" untuk mengisi kartu. --}}
        <div class="chart-marker-layer chart-stack__line" aria-hidden="true">
            @foreach ($trend as $index => $row)
                <span class="chart__marker-group" id="{{ $uid }}-marker-{{ $index }}">
                    <span class="chart__point"
                          style="left: {{ $pctX($xFor($index)) }}%; top: {{ $pctY($yForMass($tonValues[$index])) }}%; background-color: {{ $tonColor }};"></span>
                    @if ($hasMt)
                        <span class="chart__point"
                              style="left: {{ $pctX($xFor($index)) }}%; top: {{ $pctY($yForMass($mtValues[$index])) }}%; background-color: {{ $mtColor }};"></span>
                    @endif
                    @if ($hasTeus)
                        <span class="chart__point"
                              style="left: {{ $pctX($xFor($index)) }}%; top: {{ $pctY($yForTeus($teusValues[$index])) }}%; background-color: {{ $teusColor }};"></span>
                    @endif
                </span>
            @endforeach
        </div>

        {{-- Label bulan --}}
        <div class="chart-axis-row">
            @foreach ($trend as $index => $row)
                <span class="chart-axis chart-axis--x {{ $index === $count - 1 ? 'chart-axis--now' : '' }}"
                      style="left: {{ $pctX($xFor($index)) }}%">{{ $row['label'] }}</span>
            @endforeach
        </div>
    </div>

    @if ($hasMt || $hasTeus)
        <ul class="chart-legend">
            <li class="chart-legend__item">
                <span class="chart-legend__swatch" style="background-color: {{ $tonColor }};"></span>
                Kegiatan umum — Ton, sumbu kiri
            </li>
            @if ($hasMt)
                <li class="chart-legend__item">
                    <span class="chart-legend__swatch" style="background-color: {{ $mtColor }};"></span>
                    COB Curah &amp; Amoniak (MT), sumbu kiri
                </li>
            @endif
            @if ($hasTeus)
                <li class="chart-legend__item">
                    <span class="chart-legend__swatch" style="background-color: {{ $teusColor }};"></span>
                    Container — Teus, sumbu kanan
                </li>
            @endif
        </ul>
    @endif

    <div class="perf-chart__footer">
        <span>Total kegiatan umum: <strong>{{ $fmt($tonSum) }} Ton</strong></span>
        @if ($hasMt)
            <span>Total COB Curah/Amoniak: <strong>{{ $fmt($mtSum) }} MT</strong></span>
        @endif
        @if ($hasTeus)
            <span>Total container: <strong>{{ $fmt($teusSum) }} Teus</strong></span>
        @endif
    </div>
</div>
