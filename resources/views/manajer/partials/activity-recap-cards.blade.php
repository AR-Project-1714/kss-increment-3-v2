{{-- Rekap satu jenis kegiatan dalam bentuk kartu.

     Isinya sama dengan rekap yang dipaparkan ke manajemen: bulan berjalan,
     bulan-bulan sebelumnya di dalam periode, dan akumulasi keduanya. Di panel
     rincian bentuknya kartu, bukan tabel, karena hanya satu kegiatan yang
     dibaca sekaligus.

     Parameter:
       $recap  bagian 'recap' dari OperationalPerformanceService::activityDetail()
--}}
@php
    $row = $recap['row'] ?? null;
    $labels = $recap['labels'] ?? [];

    // Periode yang seluruhnya berada di satu bulan tidak punya pembanding, dan
    // akumulasinya sama persis dengan bulan berjalan. Satu kartu saja, supaya
    // tidak ada dua kotak berisi angka yang sama.
    $groups = ($recap['hasPrevious'] ?? false)
        ? [
            ['key' => 'month', 'title' => 'Bulan Berjalan', 'range' => $labels['month'] ?? null],
            ['key' => 'previous', 'title' => 'Sebelumnya', 'range' => $labels['previous'] ?? null],
            ['key' => 'total', 'title' => 'Akumulasi', 'range' => $labels['total'] ?? null],
        ]
        : [['key' => 'total', 'title' => 'Periode Ini', 'range' => $labels['total'] ?? null]];

    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
@endphp

@if ($row !== null)
    <div class="act-block">
        <span class="act-block__title">Rekap Kegiatan</span>
        <span class="act-block__subtitle">
            Volume dan jumlah {{ strtolower($row['countLabel']) }} menurut segmen bulan.
        </span>

        <div @class(['act-metrics', 'act-metrics--'.count($groups) => count($groups) < 4])>
            @foreach ($groups as $group)
                @php $cell = $row[$group['key']]; @endphp

                <div @class(['act-metric', 'act-metric--accent' => $group['key'] === 'total'])>
                    <span class="act-metric__label">{{ $group['title'] }}</span>
                    <span class="act-metric__value">
                        {{ $fmt($cell['value'], 2) }}<span class="act-metric__unit">{{ $row['unit'] }}</span>
                    </span>
                    <span class="act-metric__caption">
                        {{ $fmt($cell['count']) }} {{ $row['countLabel'] }}@if (! empty($group['range'])), {{ $group['range'] }}@endif
                    </span>

                    @if ($row['hasDelivery'] || $row['hasDamage'])
                        <span class="act-metric__caption">
                            @if ($row['hasDelivery'])
                                Kirim {{ $fmt($cell['delivery'], 2) }} {{ $row['unit'] }}
                                <br>
                                {{ $row['valueLabel'] }} {{ $fmt($cell['value'], 2) }} {{ $row['unit'] }}
                            @endif
                            @if ($row['hasDamage'])
                                <br>
                                Kerusakan {{ $fmt($cell['damage'], 2) }} {{ $row['unit'] }}
                            @endif
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
