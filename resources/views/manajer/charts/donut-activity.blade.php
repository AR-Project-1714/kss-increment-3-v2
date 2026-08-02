{{-- Donut komposisi massa per jenis kegiatan.

     Segmen digambar dengan stroke-dasharray pada satu lingkaran: panjang garis
     mewakili porsi, offset menggeser posisi awalnya. Cara ini lebih ringkas
     daripada menghitung path busur, dan tebal segmen tetap rapi saat disorot.

     Parameter:
       $activities  baris dari OperationalPerformanceService (label, tonnage, contribution)
--}}
@php
    $activities = array_values($activities ?? []);
    $total = array_sum(array_column($activities, 'tonnage'));

    // Radius dan keliling dipakai sebagai satuan dasar seluruh perhitungan segmen.
    $radius = 78;
    $circumference = 2 * M_PI * $radius;
    $canvas = 200;
    $center = $canvas / 2;

    $palette = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-5)', 'var(--chart-4)'];

    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    // Jarak antar segmen supaya batasnya terbaca. Hanya dipakai bila kategori
    // yang punya nilai lebih dari satu — kalau tunggal, lingkaran dibiarkan utuh.
    $filled = array_values(array_filter($activities, fn ($row) => (float) $row['tonnage'] > 0));
    $gap = count($filled) > 1 ? 2.0 : 0.0;

    $offset = 0.0;
    $segments = [];

    foreach ($filled as $index => $row) {
        $share = $total > 0 ? (float) $row['tonnage'] / $total : 0.0;
        $length = max($share * $circumference - $gap, 0.5);

        $segments[] = [
            'color' => $palette[$index % count($palette)],
            'dash' => round($length, 2).' '.round($circumference - $length, 2),
            'offset' => round(-$offset, 2),
            'label' => $row['label'],
            'unit' => $row['unit'],
            'tonnage' => (float) $row['tonnage'],
            'share' => $share * 100,
        ];

        $offset += $share * $circumference;
    }
@endphp

@if ($segments === [])
    <div class="perf-empty">Belum ada kegiatan bersatuan Ton maupun MT pada periode ini.</div>
@else
    <div class="donut-wrap">
        <div class="donut__stage">
            <svg class="donut" viewBox="0 0 {{ $canvas }} {{ $canvas }}" role="img"
                 aria-label="Komposisi massa menurut jenis kegiatan">
                <circle class="donut__track" cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}"></circle>

                @foreach ($segments as $segment)
                    <circle class="donut__segment"
                            cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}"
                            stroke="{{ $segment['color'] }}"
                            stroke-dasharray="{{ $segment['dash'] }}"
                            stroke-dashoffset="{{ $segment['offset'] }}"
                            stroke-linecap="butt"
                            data-chart-tip
                            data-tip-title="{{ $segment['label'] }}"
                            data-tip-rows="{{ json_encode([
                                ['label' => 'Kuantum', 'value' => $fmt($segment['tonnage'], 1).' '.$segment['unit'], 'color' => $segment['color']],
                                ['label' => 'Porsi', 'value' => $fmt($segment['share'], 1).'%'],
                            ]) }}"></circle>
                @endforeach
            </svg>

            <div class="donut-center">
                <span class="donut-center__value">{{ $fmt($total) }}</span>
                <span class="donut-center__label">Ton/MT total</span>
            </div>
        </div>

        <div class="donut-details">
            <div class="donut-legend">
                @foreach ($segments as $segment)
                    <div class="donut-legend__row">
                        <span class="chart-legend__swatch" style="background-color: {{ $segment['color'] }};"></span>
                        <span class="donut-legend__name">{{ $segment['label'] }}</span>
                        <span class="donut-legend__value">
                            {{ $fmt($segment['tonnage']) }}
                            <span class="donut-legend__unit">{{ $segment['unit'] }}</span>
                            <span class="donut-legend__share">{{ $fmt($segment['share'], 1) }}%</span>
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Ton dan MT setara secara massa, tetapi label sumber tetap ditulis.
                 Container tidak masuk karena satuannya Teus. --}}
            <p class="donut-note">COB Curah/Amoniak memakai MT; container dihitung terpisah karena bersatuan Teus.</p>
        </div>
    </div>
@endif
