{{-- Grafik tren tonase & teus enam bulan, tersedia dalam bentuk garis dan batang.

     Dua deret digambar berdampingan karena satuannya berbeda: kegiatan pupuk
     dicatat dalam Ton, sedangkan bongkar muat container dalam Teus (jumlah
     box). Keduanya tidak boleh dijumlahkan, jadi masing-masing memakai sumbu
     sendiri — Ton di kiri, Teus di kanan — dan tiap periode punya dua batang.
     Deret Teus baru muncul bila memang ada datanya.

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

    $values = array_map(fn ($row) => (float) $row['tonnage'], $trend);
    $teusValues = array_map(fn ($row) => (float) ($row['teus'] ?? 0), $trend);

    $peak = $values === [] ? 0.0 : max($values);
    $sum = array_sum($values);
    $mean = $count > 0 ? $sum / $count : 0.0;

    $teusPeak = $teusValues === [] ? 0.0 : max($teusValues);
    $teusSum = array_sum($teusValues);

    // Sumbu kanan hanya dipasang bila container memang tercatat; kartu yang
    // tidak punya kegiatan container tetap tampil sesederhana sebelumnya.
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

    $max = $niceMax($peak);
    $teusMax = $niceMax($teusPeak);

    // Bidang gambar — viewBox kini sebatas bidang plot saja.
    $vbW = 720; $vbH = 210;
    $top = 8; $bottom = 202;
    $plotH = $bottom - $top;
    $slot = $count > 0 ? $vbW / $count : $vbW;

    $yFor = fn (float $value) => round($bottom - ($max > 0 ? ($value / $max) * $plotH : 0), 2);
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

    $points = [];
    foreach ($values as $index => $value) {
        $points[] = [$xFor($index), $yFor($value)];
    }

    $teusPoints = [];
    foreach ($teusValues as $index => $value) {
        $teusPoints[] = [$xFor($index), $yForTeus($value)];
    }

    $linePath = $smoothPath($points);
    $areaPath = $points === []
        ? ''
        : $linePath.' L '.end($points)[0].','.$bottom.' L '.$points[0][0].','.$bottom.' Z';
    $teusLinePath = $hasTeus ? $smoothPath($teusPoints) : '';

    $gridSteps = 4;

    // Satu batang memakai lebar penuh; dua batang berbagi slot dengan sela
    // sempit di tengahnya supaya pasangannya terbaca sebagai satu periode.
    $barWidth = $hasTeus ? min(30, $slot * 0.26) : min(46, $slot * 0.45);
    $barGap = $hasTeus ? min(8, $slot * 0.06) : 0.0;
    $barOffset = $hasTeus
        ? [-($barGap / 2) - $barWidth, $barGap / 2]
        : [-$barWidth / 2];

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

        {{-- Garis rata-rata tidak diberi label di sebelahnya: pada lebar sempit
             teksnya bertabrakan dengan grafik, sedangkan angkanya sudah
             disebut di keterangan bawah. --}}
        @if ($mean > 0)
            <span class="chart-rule chart-rule--mean" style="top: {{ $pctY($yFor($mean)) }}%" aria-hidden="true"></span>
        @endif

        <svg class="chart" viewBox="0 0 {{ $vbW }} {{ $vbH }}" preserveAspectRatio="none"
             role="img" aria-label="Grafik tonase{{ $hasTeus ? ' dan teus' : '' }} bulanan enam bulan terakhir">
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
                @if ($linePath !== '')
                    <path class="chart__line" d="{{ $linePath }}" stroke="{{ $tonColor }}"></path>
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
                            ['y' => $yFor($values[$index]), 'color' => $tonColor],
                            $hasTeus
                                ? ['y' => $yForTeus($teusValues[$index]), 'color' => $teusColor]
                                : null,
                        ]));
                    @endphp

                    @foreach ($bars as $serie => $bar)
                        <rect class="chart__bar"
                              x="{{ round($xFor($index) + $barOffset[$serie], 2) }}"
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
                        ['label' => 'Tonase', 'value' => $fmt($row['tonnage'], 1).' Ton', 'color' => $tonColor],
                        $hasTeus
                            ? ['label' => 'Teus', 'value' => $fmt($teusValues[$index]).' Teus', 'color' => $teusColor]
                            : null,
                        ['label' => 'Laporan', 'value' => $fmt($row['reports']), 'color' => 'var(--chart-4)'],
                        ($row['ships'] ?? 0) > 0
                            ? ['label' => 'Kapal', 'value' => $fmt($row['ships']), 'color' => 'var(--chart-2)']
                            : null,
                    ]));
                @endphp

                <g>
                    <rect class="chart__hotspot"
                          data-chart-tip
                          data-tip-title="{{ $row['label'] }} · {{ $fmt($row['reports']) }} laporan"
                          data-tip-rows="{{ json_encode($rows) }}"
                          data-tip-cursor="{{ $uid }}-cursor"
                          data-tip-x="{{ $xFor($index) }}"
                          x="{{ round($index * $slot, 2) }}"
                          y="{{ $top }}"
                          width="{{ round($slot, 2) }}"
                          height="{{ $plotH }}"></rect>

                    <g class="chart__marker chart-stack__line">
                        <circle class="chart__dot" cx="{{ $xFor($index) }}" cy="{{ $yFor((float) $row['tonnage']) }}" r="4"
                                fill="{{ $tonColor }}"></circle>
                        @if ($hasTeus)
                            <circle class="chart__dot" cx="{{ $xFor($index) }}" cy="{{ $yForTeus($teusValues[$index]) }}" r="4"
                                    fill="{{ $teusColor }}"></circle>
                        @endif
                    </g>
                </g>
            @endforeach
        </svg>

        {{-- Label bulan --}}
        <div class="chart-axis-row">
            @foreach ($trend as $index => $row)
                <span class="chart-axis chart-axis--x {{ $index === $count - 1 ? 'chart-axis--now' : '' }}"
                      style="left: {{ $pctX($xFor($index)) }}%">{{ $row['label'] }}</span>
            @endforeach
        </div>
    </div>

    @if ($hasTeus)
        <ul class="chart-legend">
            <li class="chart-legend__item">
                <span class="chart-legend__swatch" style="background-color: {{ $tonColor }};"></span>
                Tonase — Ton, sumbu kiri
            </li>
            <li class="chart-legend__item">
                <span class="chart-legend__swatch" style="background-color: {{ $teusColor }};"></span>
                Container — Teus, sumbu kanan
            </li>
        </ul>
    @endif

    <div class="perf-chart__footer">
        <span>Total 6 bulan: <strong>{{ $fmt($sum) }} Ton</strong></span>
        <span>Rata-rata bulanan: <strong>{{ $fmt($mean) }} Ton</strong></span>
        <span>Tertinggi: <strong>{{ $fmt($peak) }} Ton</strong></span>
        @if ($hasTeus)
            <span>Total container: <strong>{{ $fmt($teusSum) }} Teus</strong></span>
        @endif
    </div>
</div>
