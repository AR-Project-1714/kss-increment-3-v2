{{-- Rekap kegiatan gaya laporan manajemen.

     Bentuknya mengikuti rekap yang biasa dipaparkan: tiap kegiatan dibaca dalam
     tiga kelompok kolom, masing-masing berisi pencacah (kapal atau rit) dan
     volumenya. Garis tipis di batas kelompok menahan mata agar tidak
     menyeberang saat membaca satu baris.

     Container dipecah menjadi Empty dan Full memakai penanda baris yang sudah
     ada di laporan, dan satuannya tetap Teus sehingga tidak pernah dijumlahkan
     bersama kegiatan bersatuan Ton.

     Parameter:
       $recap  hasil OperationalPerformanceService::activityRecap()
--}}
@php
    $labels = $recap['labels'] ?? [];
    $hasPrevious = (bool) ($recap['hasPrevious'] ?? false);

    // Baris yang seluruh angkanya nol tidak ikut dicetak.
    $rows = array_values(array_filter(
        $recap['rows'] ?? [],
        fn (array $row): bool => $row['total']['count'] > 0 || $row['total']['value'] > 0
    ));

    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
    $volumeDecimals = fn (string $unit): int => in_array($unit, ['Teus', 'Bag'], true) ? 0 : 2;

    // Periode yang seluruhnya berada di satu bulan tidak punya pembanding, dan
    // akumulasinya sama persis dengan bulan berjalan. Satu kelompok kolom saja,
    // supaya tidak ada dua kelompok berisi angka yang sama.
    $groups = $hasPrevious
        ? [
            ['key' => 'month', 'title' => 'Bulan Berjalan', 'range' => $labels['month'] ?? null],
            ['key' => 'previous', 'title' => 'Sebelumnya', 'range' => $labels['previous'] ?? null],
            ['key' => 'total', 'title' => 'Akumulasi', 'range' => $labels['total'] ?? null],
        ]
        : [['key' => 'total', 'title' => 'Periode Ini', 'range' => $labels['total'] ?? null]];
@endphp

@if ($rows === [])
    <div class="perf-empty">Belum ada kegiatan tercatat pada periode ini.</div>
@else
    <div class="table-responsive-wrapper">
        <table class="perf-table perf-table--grouped" style="min-width: {{ 240 + count($groups) * 200 }}px;">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 240px;">Kegiatan</th>
                    @foreach ($groups as $group)
                        <th colspan="2" class="is-group-head is-group-start">
                            {{ $group['title'] }}
                            @if (! empty($group['range']))
                                <span class="th-range">{{ $group['range'] }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($groups as $group)
                        <th class="is-group-start" style="width: 84px;">Jumlah</th>
                        <th style="width: 116px;">Volume</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="perf-table__name">
                            {{ $row['label'] }}
                            <span class="perf-table__muted" style="display: block; font-weight: 400;">
                                Satuan {{ $row['unit'] }}, dihitung per {{ strtolower($row['countLabel']) }}
                            </span>
                        </td>

                        @foreach ($groups as $group)
                            @php $cell = $row[$group['key']]; @endphp

                            <td class="perf-table__num is-group-start">
                                {{ $fmt($cell['count']) }}<span class="perf-table__unit">{{ $row['countLabel'] }}</span>
                            </td>
                            <td class="perf-table__num">
                                {{ $fmt($cell['value'], $volumeDecimals($row['unit'])) }}<span class="perf-table__unit">{{ $row['unit'] }}</span>
                            </td>
                        @endforeach
                    </tr>

                    {{-- Muat kantong punya dua angka pendamping yang memang
                         dicatat: pengiriman gudang ke kapal, dan kerusakan.
                         Keduanya menempel pada baris kegiatannya. --}}
                    @foreach ([
                        ['show' => $row['hasDelivery'], 'field' => 'delivery', 'label' => 'Kirim gudang ke kapal'],
                        ['show' => $row['hasDamage'], 'field' => 'damage', 'label' => 'Kerusakan tercatat'],
                    ] as $extra)
                        @continue(! $extra['show'])

                        <tr class="perf-table__row--sub">
                            <td>{{ $extra['label'] }}</td>

                            @foreach ($groups as $group)
                                <td class="is-group-start"></td>
                                <td class="perf-table__num">
                                    {{ $fmt($row[$group['key']][$extra['field']], 2) }}<span class="perf-table__unit">{{ $row['unit'] }}</span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="act-panel__note">
        Akumulasi adalah jumlah kedua kelompok sebelumnya. Kapal yang sandar melewati
        pergantian bulan ikut terhitung pada keduanya, sama seperti cara rekap manual disusun.
    </p>
@endif
