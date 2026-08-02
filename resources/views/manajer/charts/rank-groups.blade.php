{{-- Perbandingan massa Ton/MT antar regu sebagai batang horizontal.

     Warna mengikuti peringkat, bukan identitas regu: hijau untuk yang teratas
     lalu menurun ke oranye. Angka dan keterangan tetap ditulis di setiap baris
     supaya urutannya terbaca tanpa bergantung pada warna saja.

     Parameter:
       $groups           baris regu dari OperationalPerformanceService
       $comparisonLabel  keterangan periode pembanding
--}}
@php
    $groups = array_values($groups ?? []);
    $peak = $groups === [] ? 0.0 : max(array_map(fn ($row) => (float) $row['tonnage'], $groups));

    // Peringkat 1 hijau, terakhir oranye — warna di antaranya netral supaya
    // yang menonjol hanya ujung teratas dan terbawah.
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

    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
@endphp

@if ($groups === [])
    <div class="perf-empty">Belum ada laporan operasi pada periode ini.</div>
@else
    <div class="rank-bar">
        @foreach ($groups as $index => $group)
            @php
                $color = $rankColor($index, count($groups));
                $width = $peak > 0 ? ((float) $group['tonnage'] / $peak) * 100 : 0;
                $delta = $group['delta'] ?? [];
                $deltaText = ($delta['available'] ?? false)
                    ? (($delta['direction'] ?? '') === 'up' ? '+' : '−').$delta['text']
                    : null;
            @endphp

            <div class="rank-bar__row">
                <div class="rank-bar__head">
                    <span class="rank-bar__name">Regu {{ $group['name'] }}</span>
                    <span class="rank-bar__meta">{{ $group['reports'] }} laporan · {{ $fmt($group['tonnagePerShift'], 1) }} Ton/MT per shift</span>
                    <span class="rank-bar__value">
                        {{ $fmt($group['tonnage']) }} Ton/MT
                        @if ($deltaText)
                            <span class="perf-delta perf-delta--{{ $delta['tone'] ?? 'flat' }}">{{ $deltaText }}</span>
                        @endif
                    </span>
                </div>

                <div class="rank-bar__track"
                     data-chart-tip
                     data-tip-title="Regu {{ $group['name'] }}"
                     data-tip-rows="{{ json_encode(array_values(array_filter([
                        ['label' => 'Massa', 'value' => $fmt($group['tonnage'], 1).' Ton/MT', 'color' => $color],
                        ['label' => 'Massa per shift', 'value' => $fmt($group['tonnagePerShift'], 1).' Ton/MT'],
                        ['label' => 'Rasio kerusakan', 'value' => $fmt($group['damageRatio'], 2).'%'],
                        $deltaText ? ['label' => $comparisonLabel ?? 'Perubahan', 'value' => $deltaText] : null,
                     ]))) }}">
                    <span class="rank-bar__fill" style="width: {{ round($width, 2) }}%; background-color: {{ $color }};"></span>
                </div>
            </div>
        @endforeach
    </div>
@endif
