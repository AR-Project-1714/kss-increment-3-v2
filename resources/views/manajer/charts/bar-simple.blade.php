{{-- Batang horizontal sederhana untuk peringkat satu dimensi: regu pada panel
     kegiatan, jenis bahan baku, dan tujuan pengiriman trucking.

     Berbeda dari manajer.charts.rank-groups yang khusus membandingkan regu
     dengan tonase, delta, dan rasio kerusakan sekaligus, partial ini hanya
     butuh nama + nilai sehingga bisa dipakai ulang lintas panel.

     Parameter:
       $rows       [['name' => string, 'value' => float, 'share' => float, ...]]
       $unit       satuan nilai (Ton / Teus)
       $prefix     awalan nama, mis. "Regu" (opsional)
       $emptyText  teks saat tidak ada data
--}}
@php
    $rows = array_values($rows ?? []);
    $unit = $unit ?? '';
    $prefix = $prefix ?? '';
    $emptyText = $emptyText ?? 'Belum ada data pada periode ini.';

    $fmt = fn ($value, int $decimals = 1) => number_format((float) $value, $decimals, ',', '.');

    // Peringkat teratas hijau, terbawah oranye — sama seperti perbandingan regu
    // di halaman utama, supaya cara bacanya tidak berubah antar panel.
    $rankColor = static function (int $index, int $total): string {
        if ($total <= 1) {
            return 'var(--chart-2)';
        }

        return match (true) {
            $index === 0 => 'var(--chart-2)',
            $index === $total - 1 => 'var(--chart-3)',
            default => 'var(--chart-1)',
        };
    };
@endphp

@if ($rows === [])
    <div class="perf-empty">{{ $emptyText }}</div>
@else
    <div class="rank-bar">
        @foreach ($rows as $index => $row)
            @php
                $color = $rankColor($index, count($rows));
                $name = trim($prefix.' '.$row['name']);
                $tipRows = array_values(array_filter([
                    ['label' => 'Nilai', 'value' => $fmt($row['value']).' '.$unit, 'color' => $color],
                    isset($row['contribution']) ? ['label' => 'Porsi', 'value' => $fmt($row['contribution']).'%'] : null,
                    isset($row['trips']) ? ['label' => 'Rit', 'value' => $row['trips']] : null,
                ]));
            @endphp

            <div class="rank-bar__row">
                <div class="rank-bar__head">
                    <span class="rank-bar__name">{{ $name }}</span>
                    @if (isset($row['trips']))
                        <span class="rank-bar__meta">{{ $row['trips'] }} rit</span>
                    @elseif (isset($row['contribution']))
                        <span class="rank-bar__meta">{{ $fmt($row['contribution']) }}% dari total</span>
                    @endif
                    <span class="rank-bar__value">{{ $fmt($row['value']) }} {{ $unit }}</span>
                </div>

                <div class="rank-bar__track"
                     data-chart-tip
                     data-tip-title="{{ $name }}"
                     data-tip-rows="{{ json_encode($tipRows) }}">
                    <span class="rank-bar__fill" style="width: {{ round($row['share'] ?? 0, 2) }}%; background-color: {{ $color }};"></span>
                </div>
            </div>
        @endforeach
    </div>
@endif
