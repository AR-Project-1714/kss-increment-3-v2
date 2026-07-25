{{-- Grafik area bertumpuk yang dipakai bersama dashboard manajer dan admin.

     Tinggi tiap pita adalah nilai seri tersebut, dan garis paling atas menjadi
     total periode itu.

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

    $vbW = 720; $vbH = 210;
    $left = 52; $right = 708; $top = 14; $bottom = 156;
    $plotW = $right - $left;
    $plotH = $bottom - $top;
    $slot = $count > 0 ? $plotW / $count : $plotW;

    // Deret harian terlalu rapat untuk diberi label di setiap titik, jadi
    // hanya sebagian yang ditulis. Nilai bawaan dihitung dari jumlah titik.
    $labelStep = $labelStep ?? max(1, (int) ceil($count / 8));

    $yFor = fn (float $value) => round($bottom - ($max > 0 ? ($value / $max) * $plotH : 0), 2);
    $xFor = fn (int $index) => round($left + ($index + 0.5) * $slot, 2);

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

    $gridSteps = 3;
    $fmt = fn ($value, int $dec = 0) => number_format((float) $value, $dec, ',', '.');
@endphp

@if ($peak <= 0)
    <div class="perf-empty">{{ $emptyText }}</div>
@else
    <svg class="chart" viewBox="0 0 {{ $vbW }} {{ $vbH }}" preserveAspectRatio="xMidYMid meet"
         role="img" aria-label="{{ $ariaLabel ?? 'Grafik area bertumpuk' }}">
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

        {{-- Pita digambar dari yang paling bawah agar tumpukannya benar. --}}
        @foreach ($bands as $band)
            <path d="{{ $band['path'] }}" fill="{{ $band['color'] }}" fill-opacity="0.55"></path>
            <path class="chart__line" d="{{ $band['line'] }}" stroke="{{ $band['color'] }}" stroke-width="1.75"></path>
        @endforeach

        <line class="chart__baseline" x1="{{ $left }}" y1="{{ $bottom }}" x2="{{ $right }}" y2="{{ $bottom }}"></line>

        {{-- Titik terakhir selalu diberi label karena itu periode berjalan.
             Label sebelumnya dilewati bila jaraknya terlalu rapat, agar
             keduanya tidak saling menimpa. --}}
        @php $lastLabelled = $count - 1; @endphp
        @foreach ($rows as $index => $row)
            @php $isLast = $index === $lastLabelled; @endphp
            @if ($isLast || ($index % $labelStep === 0 && $lastLabelled - $index >= $labelStep * 0.6))
                <text class="chart__tick {{ $isLast ? 'chart__tick--now' : '' }}"
                      x="{{ $xFor($index) }}" y="{{ $bottom + 19 }}" text-anchor="middle">{{ $row['label'] }}</text>
            @endif
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
                  x="{{ round($left + $index * $slot, 2) }}"
                  y="{{ $top }}"
                  width="{{ round($slot, 2) }}"
                  height="{{ $plotH }}"></rect>
        @endforeach
    </svg>

    <ul class="chart-legend" style="margin-top: 12px;">
        @foreach ($series as $serie)
            <li class="chart-legend__item">
                <span class="chart-legend__swatch" style="background-color: {{ $serie['color'] }};"></span>
                {{ $serie['label'] }}
            </li>
        @endforeach
    </ul>
@endif
