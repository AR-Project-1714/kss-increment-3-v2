{{-- Lima personil dengan lembur terbanyak.

     Ditampilkan dalam dua ukuran karena keduanya menjawab hal berbeda: jam
     lembur menunjukkan beban waktu, frekuensi menunjukkan seberapa sering
     seseorang diminta. Sebagian entri lembur diisi tanpa jam, sehingga orang
     yang muncul di daftar frekuensi belum tentu ada di daftar jam.

     Parameter:
       $leaders  ['hours' => [...], 'count' => [...]] dari OperationalPerformanceService
--}}
@php
    $leaders = $leaders ?? ['hours' => [], 'count' => []];
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    $panels = [
        [
            'key' => 'hours',
            'title' => 'Jam Lembur Terbanyak',
            'color' => 'var(--chart-3)',
            'unit' => 'jam',
            'decimals' => 1,
        ],
        [
            'key' => 'count',
            'title' => 'Paling Sering Lembur',
            'color' => 'var(--chart-5)',
            'unit' => 'kali',
            'decimals' => 0,
        ],
    ];

    $hasAny = ($leaders['hours'] ?? []) !== [] || ($leaders['count'] ?? []) !== [];
@endphp

@if (! $hasAny)
    <div class="perf-empty">Belum ada lembur tercatat pada periode ini.</div>
@else
    <div class="leader-board">
        @foreach ($panels as $panel)
            @php $rows = $leaders[$panel['key']] ?? []; @endphp

            <div class="leader-board__panel">
                <span class="leader-board__title">{{ $panel['title'] }}</span>

                @if ($rows === [])
                    <span class="leader-board__empty">Belum ada data dengan jam tercatat.</span>
                @else
                    <ol class="leader-board__list">
                        @foreach ($rows as $index => $person)
                            <li class="leader-board__item">
                                <span class="leader-board__rank">{{ $index + 1 }}</span>

                                <div class="leader-board__body">
                                    <div class="leader-board__head">
                                        <span class="leader-board__name">{{ $person['name'] }}</span>
                                        <span class="leader-board__value">
                                            {{ $fmt($person[$panel['key']], $panel['decimals']) }} {{ $panel['unit'] }}
                                        </span>
                                    </div>

                                    <div class="leader-board__track"
                                         data-chart-tip
                                         data-tip-title="{{ $person['name'] }}"
                                         data-tip-rows="{{ json_encode([
                                            ['label' => 'Jam lembur', 'value' => $fmt($person['hours'], 1).' jam', 'color' => 'var(--chart-3)'],
                                            ['label' => 'Frekuensi', 'value' => $fmt($person['count']).' kali', 'color' => 'var(--chart-5)'],
                                         ]) }}">
                                        <span class="leader-board__fill"
                                              style="width: {{ round($person['share'], 2) }}%; background-color: {{ $panel['color'] }};"></span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endforeach
    </div>
@endif
