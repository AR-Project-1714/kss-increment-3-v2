{{-- Isi satu panel kegiatan.

     Dikirim sebagai potongan HTML lewat route manajer.performa.kegiatan dan
     disisipkan ke halaman saat tabnya dibuka — bukan dirender bersama halaman
     utama, supaya beban query-nya hanya dibayar oleh kegiatan yang dilihat.

     Susunan kolom tabel datang dari service (label + tipe), sehingga satu
     template ini melayani kelima kegiatan tanpa percabangan per kegiatan.

     Parameter:
       $detail  hasil OperationalPerformanceService::activityDetail()
--}}
@php
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    $trend = $detail['trend'] ?? [];
    $trendMax = (float) ($detail['trendMax'] ?? 1);
    $trendTotal = array_sum(array_column($trend, 'value'));
    $table = $detail['table'] ?? ['rows' => [], 'limited' => false, 'total' => 0];
    $columns = $detail['columns'] ?? [];
@endphp

<div class="act-panel__inner">
    {{-- Metrik sekunder khas kegiatan --}}
    <div class="act-metrics">
        @foreach ($detail['metrics'] ?? [] as $metric)
            <div class="act-metric">
                <span class="act-metric__label">{{ $metric['label'] }}</span>
                <span class="act-metric__value">
                    @if ($metric['value'] === null)
                        <span class="perf-table__muted">Belum ada data</span>
                    @else
                        {{ $fmt($metric['value'], $metric['decimals'] ?? 0) }}
                        <span class="act-metric__unit">{{ $metric['unit'] ?? '' }}</span>
                    @endif
                </span>
                @if (! empty($metric['caption']))
                    <span class="act-metric__caption">{{ $metric['caption'] }}</span>
                @endif
            </div>
        @endforeach
    </div>

    @if (! empty($detail['note']))
        <p class="act-panel__note">{{ $detail['note'] }}</p>
    @endif

    <div class="perf-layout">
        {{-- Tren enam bulan untuk kegiatan ini saja --}}
        <div class="act-block">
            <span class="act-block__title">Tren 6 Bulan — {{ $detail['label'] }}</span>

            @if ($trendTotal <= 0)
                <div class="perf-empty">Belum ada catatan pada enam bulan terakhir.</div>
            @else
                <div class="act-trend">
                    @foreach ($trend as $bucket)
                        @php
                            $height = $trendMax > 0 ? ((float) $bucket['value'] / $trendMax) * 100 : 0;
                        @endphp

                        <div class="act-trend__col"
                             data-chart-tip
                             data-tip-title="{{ $bucket['label'] }}"
                             data-tip-rows="{{ json_encode([
                                ['label' => $detail['label'], 'value' => $fmt($bucket['value'], 1).' '.$detail['unit'], 'color' => 'var(--chart-1)'],
                             ]) }}">
                            <div class="act-trend__slot">
                                <span class="act-trend__bar" style="height: {{ max(round($height, 2), $bucket['value'] > 0 ? 2 : 0) }}%;"></span>
                            </div>
                            <span class="act-trend__label">{{ $bucket['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Peringkat regu untuk kegiatan ini --}}
        <div class="act-block">
            <span class="act-block__title">Peringkat Regu</span>
            <span class="act-block__subtitle">Seluruh regu ikut ditampilkan meski filter regu sedang aktif.</span>

            @include('manajer.charts.bar-simple', [
                'rows' => $detail['groups'] ?? [],
                'unit' => $detail['unit'],
                'prefix' => 'Regu',
                'emptyText' => 'Belum ada catatan kegiatan ini pada periode terpilih.',
            ])
        </div>
    </div>

    {{-- Rincian tambahan khas kegiatan: jenis bahan baku / tujuan trucking --}}
    @if (! empty($detail['breakdown']))
        <div class="act-block">
            <span class="act-block__title">{{ $detail['breakdownTitle'] }}</span>

            @include('manajer.charts.bar-simple', [
                'rows' => $detail['breakdown'],
                'unit' => $detail['unit'],
                'prefix' => '',
                'emptyText' => 'Belum ada rincian pada periode ini.',
            ])
        </div>
    @endif

    {{-- Tabel rincian --}}
    <div class="act-block">
        <span class="act-block__title">Rincian {{ $detail['label'] }}</span>

        @if (empty($table['rows']))
            <div class="perf-empty">Belum ada kegiatan ini pada periode terpilih.</div>
        @else
            <div class="table-responsive-wrapper">
                <table class="perf-table" style="min-width: {{ max(720, count($columns) * 105) }}px;">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th>{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($table['rows'] as $row)
                            <tr>
                                @foreach ($columns as $index => $column)
                                    @php $value = $row[$index] ?? null; @endphp

                                    @switch($column['type'])
                                        @case('name')
                                            <td class="perf-table__name">{{ $value }}</td>
                                            @break

                                        @case('number')
                                            <td>
                                                {{ $fmt($value, $column['decimals'] ?? 0) }}
                                                @if (! empty($column['unit']))
                                                    {{ $column['unit'] }}
                                                @endif
                                            </td>
                                            @break

                                        @case('ratio')
                                            <td>
                                                @if ($value === null)
                                                    <span class="perf-table__muted">Kapasitas belum diisi</span>
                                                @else
                                                    <span>{{ $fmt($value, 1) }}%</span>
                                                    <div class="perf-bar__track" style="margin-top: 6px;">
                                                        <span class="perf-bar__fill" style="width: {{ min(round($value, 2), 100) }}%;"></span>
                                                    </div>
                                                @endif
                                            </td>
                                            @break

                                        @default
                                            <td class="perf-table__muted">{{ $value === null || $value === '' ? '-' : $value }}</td>
                                    @endswitch
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($table['limited'])
                <p class="act-panel__note">
                    Menampilkan {{ count($table['rows']) }} dari {{ $fmt($table['total']) }} baris.
                    Gunakan tombol Ekspor di atas untuk rincian lengkapnya.
                </p>
            @endif
        @endif
    </div>
</div>
