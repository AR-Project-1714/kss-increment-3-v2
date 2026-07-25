{{-- Grafik area bertumpuk yang dipakai bersama dashboard manajer dan admin.

     Tinggi tiap pita adalah nilai seri tersebut, dan garis paling atas menjadi
     total periode itu.

     Kanvas SVG diregangkan mengikuti lebar kartu (preserveAspectRatio="none"),
     jadi hanya bentuk data yang digambar di dalamnya. Sumbu, garis bantu, dan
     angkanya berupa elemen HTML di atas kanvas — kalau ikut di dalam viewBox,
     hurufnya turut melar dan terlihat lonjong pada kartu yang lebar.

     Parameter:
       $rows        array baris: setiap baris punya 'label' dan satu kunci per seri
       $series      array: [['key' => 'Pagi', 'label' => 'Shift Pagi', 'color' => 'var(--chart-3)'], ...]
       $suffix      satuan yang ditempel di tooltip (mis. ' Ton'); boleh kosong
       $decimals    jumlah desimal pada tooltip
       $emptyText   pesan bila seluruh nilainya nol
       $labelStep   tampilkan label sumbu X tiap n titik (untuk deret harian yang padat)
--}}
@php
    $rows = array_values($rows ?? []);
    $series = $series ?? [];
    $suffix = $suffix ?? '';
    $decimals = $decimals ?? 0;
    $emptyText = $emptyText ?? 'Belum ada data pada periode ini.';
    $count = count($rows);

    // Total per baris dihitung di sini, bukan diandalkan dari pemanggil, supaya
    // partial ini tetap benar walau datanya datang dari sumber berbeda.
    $totals = [];
    foreach ($rows as $row) {
        $sum = 0.0;
        foreach ($series as $serie) {
            $sum += (float) ($row[$serie['key']] ?? 0);
        }
        $totals[] = $sum;
    }

    $peak = $totals === [] ? 0.0 : max($totals);

    // Batas atas dibulatkan ke angka "bulat" terdekat supaya garis bantu jatuh
    // di nilai yang enak dibaca (mis. 30, bukan 26).
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
            $ratio <= 6.0 => 6.0,
            default => 10.0,
        };

        return $step * $magnitude;
    };

    $max = $niceMax($peak);

    // viewBox kini hanya sebesar bidang plot: tidak ada lagi ruang sumbu di
    // dalamnya karena angkanya ditempel dari luar.
    $vbW = 720; $vbH = 200;
    $top = 6; $bottom = 194;
    $plotH = $bottom - $top;
    $slot = $count > 0 ? $vbW / $count : $vbW;

    // Deret harian terlalu rapat untuk diberi label di setiap titik, jadi
    // hanya sebagian yang ditulis. Nilai bawaan dihitung dari jumlah titik.
    $labelStep = $labelStep ?? max(1, (int) ceil($count / 8));

    $yFor = fn (float $value) => round($bottom - ($max > 0 ? ($value / $max) * $plotH : 0), 2);
    $xFor = fn (int $index) => round(($index + 0.5) * $slot, 2);

    // Posisi elemen HTML dinyatakan dalam persen bidang plot, sehingga tetap
    // sejajar dengan kanvas berapa pun lebar kartunya.
    $pctX = fn (float $x) => round($x / $vbW * 100, 3);
    $pctY = fn (float $y) => round($y / $vbH * 100, 3);

    $smooth = static function (array $points): string {
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

    // Tiap pita dibangun dari garis batas atasnya sendiri dan garis batas pita
    // di bawahnya (dibalik), sehingga bentuknya menutup rapat.
    $running = array_fill(0, max($count, 1), 0.0);
    $bands = [];

    foreach ($series as $serie) {
        $lower = [];
        $upper = [];

        foreach ($rows as $index => $row) {
            $lower[] = [$xFor($index), $yFor($running[$index])];
            $running[$index] += (float) ($row[$serie['key']] ?? 0);
            $upper[] = [$xFor($index), $yFor($running[$index])];
        }

        $path = $smooth($upper);

        foreach (array_reverse($lower) as $point) {
            $path .= ' L '.$point[0].','.$point[1];
        }

        $bands[] = [
            'color' => $serie['color'],
            'path' => $path.' Z',
            'line' => $smooth($upper),
        ];
    }

    // Jumlah garis bantu dipilih agar jaraknya jatuh di angka bulat: dengan
    // batas 100.000, empat langkah memberi 25.000 sedangkan tiga langkah
    // memberi 33.333 yang tidak enak dibaca — begitu pula sebaliknya untuk
    // batas 30 (10 vs 7,5).
    $gridSteps = fmod($max, 4.0) === 0.0 ? 4 : 3;
    $fmt = fn ($value, int $dec = 0) => number_format((float) $value, $dec, ',', '.');

    // Nilai sumbu Y dihitung lebih dulu agar lebar selokan kirinya bisa
    // disesuaikan dengan angka terpanjang — "100.000" butuh ruang jauh lebih
    // besar daripada "30".
    $yTicks = [];
    for ($i = 0; $i <= $gridSteps; $i++) {
        $yTicks[] = [
            'text' => $fmt($max - ($max / $gridSteps) * $i),
            'y' => round($top + ($plotH / $gridSteps) * $i, 2),
        ];
    }

    $gutter = (int) round(max(array_map(fn ($tick) => mb_strlen($tick['text']), $yTicks)) * 6.6) + 12;

    // Label sumbu X disaring lebih dulu supaya jumlahnya diketahui — deret
    // padat perlu penjarangan tambahan pada layar sempit.
    $lastLabelled = $count - 1;
    $xLabels = [];
    foreach ($rows as $index => $row) {
        $isLast = $index === $lastLabelled;

        if ($isLast || ($index % $labelStep === 0 && $lastLabelled - $index >= $labelStep * 0.6)) {
            $xLabels[] = ['text' => $row['label'], 'x' => $xFor($index), 'now' => $isLast];
        }
    }
@endphp

@if ($peak <= 0)
    <div class="perf-empty">{{ $emptyText }}</div>
@else
    <div class="chart-frame" style="--chart-gutter: {{ $gutter }}px;">
        {{-- Garis bantu horizontal. Digambar di belakang kanvas; pita area yang
             setengah tembus pandang membuatnya tetap terlihat. --}}
        @foreach ($yTicks as $tick)
            @if ($tick['y'] < $bottom)
                <span class="chart-rule" style="top: {{ $pctY($tick['y']) }}%" aria-hidden="true"></span>
            @endif
            <span class="chart-axis chart-axis--y" style="top: {{ $pctY($tick['y']) }}%">{{ $tick['text'] }}</span>
        @endforeach

        <span class="chart-rule chart-rule--base" style="top: {{ $pctY($bottom) }}%" aria-hidden="true"></span>

        <svg class="chart" viewBox="0 0 {{ $vbW }} {{ $vbH }}" preserveAspectRatio="none"
             role="img" aria-label="{{ $ariaLabel ?? 'Grafik area bertumpuk' }}">
            {{-- Pita digambar dari yang paling bawah agar tumpukannya benar. --}}
            @foreach ($bands as $band)
                <path d="{{ $band['path'] }}" fill="{{ $band['color'] }}" fill-opacity="0.55"></path>
                <path class="chart__line" d="{{ $band['line'] }}" stroke="{{ $band['color'] }}" stroke-width="1.75"></path>
            @endforeach

            @foreach ($rows as $index => $row)
                @php
                    $tipRows = [];
                    foreach ($series as $serie) {
                        $tipRows[] = [
                            'label' => $serie['label'],
                            'value' => $fmt($row[$serie['key']] ?? 0, $decimals).$suffix,
                            'color' => $serie['color'],
                        ];
                    }
                    $tipRows[] = ['label' => 'Total', 'value' => $fmt($totals[$index], $decimals).$suffix];
                @endphp

                <rect class="chart__hotspot"
                      data-chart-tip
                      data-tip-title="{{ $row['label'] }}"
                      data-tip-rows="{{ json_encode($tipRows) }}"
                      x="{{ round($index * $slot, 2) }}"
                      y="{{ $top }}"
                      width="{{ round($slot, 2) }}"
                      height="{{ $plotH }}"></rect>
            @endforeach
        </svg>

        {{-- Titik terakhir selalu diberi label karena itu periode berjalan.
             Label sebelumnya dilewati bila jaraknya terlalu rapat, agar
             keduanya tidak saling menimpa. --}}
        <div class="chart-axis-row {{ count($xLabels) > 6 ? 'chart-axis-row--dense' : '' }}">
            @foreach ($xLabels as $label)
                <span class="chart-axis chart-axis--x {{ $label['now'] ? 'chart-axis--now' : '' }}"
                      style="left: {{ $pctX($label['x']) }}%">{{ $label['text'] }}</span>
            @endforeach
        </div>
    </div>

    <ul class="chart-legend">
        @foreach ($series as $serie)
            <li class="chart-legend__item">
                <span class="chart-legend__swatch" style="background-color: {{ $serie['color'] }};"></span>
                {{ $serie['label'] }}
            </li>
        @endforeach
    </ul>
@endif
