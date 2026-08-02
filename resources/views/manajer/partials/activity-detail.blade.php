{{-- Isi satu panel kegiatan.

     Dikirim sebagai potongan HTML lewat route manajer.kegiatan.panel dan
     disisipkan ke halaman saat tabnya dibuka — bukan dirender bersama halaman
     utama, supaya beban query-nya hanya dibayar oleh kegiatan yang dilihat.

     Blok yang tidak punya data sama sekali tidak dirender: panel pada periode
     sepi jadi ringkas, bukan berisi deretan kotak "belum ada data".

     Tabel baris-per-baris ("Daftar Kegiatan") sengaja tidak ditampilkan atas
     permintaan pemangku kepentingan — panel ini untuk membaca ringkasan, bukan
     menelusuri satu per satu. Datanya tetap disusun service dan tetap ikut pada
     berkas ekspor, jadi rinciannya tidak hilang, hanya pindah tempat.

     Parameter:
       $detail  hasil OperationalPerformanceService::activityDetail()
--}}
@php
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    $trend = $detail['trend'] ?? [];
    $trendTotal = array_sum(array_column($trend, 'value'));
    $groups = $detail['groups'] ?? [];

    // Nada sparkline kepala panel. 'flat' juga dipakai saat pembandingnya belum
    // ada — abu-abu jujur menyatakan "tidak bisa dibandingkan", sedangkan hijau
    // atau merah di situ akan mengarang arah yang tidak pernah dihitung.
    $trendDelta = $detail['delta'] ?? ['available' => false, 'tone' => 'flat', 'text' => 'Belum ada pembanding'];
    $trendTone = $trendDelta['tone'] ?? 'flat';
    $trendToneColor = match ($trendTone) {
        'up' => 'var(--success)',
        'down' => 'var(--red-main)',
        default => 'var(--muted)',
    };
    $trendDeltaWord = match ($trendTone) {
        'up' => 'Naik',
        'down' => 'Turun',
        default => 'Perubahan',
    };
    $trendSparkLabel = trim(
        'Tren enam bulan terakhir. '
        .($trendDelta['available'] ?? false
            ? strtolower($trendDeltaWord).' '.($trendDelta['text'] ?? '').' '.($detail['comparisonLabel'] ?? '')
            : ($trendDelta['text'] ?? 'Belum ada pembanding'))
    );

    // Sumbu Y grafik tren. Batas atas tidak memakai nilai tertinggi apa adanya
    // (mis. 1.120) melainkan kelipatan bulat di atasnya (1.500), supaya garis
    // bantunya jatuh di angka yang enak dibaca — 500, 1.000, 1.500 — bukan di
    // pecahan nilai puncak. Langkahnya dipilih dari 1/2/2,5/5/10 kali pangkat
    // sepuluh, pola yang sama dipakai grafik sumbu mana pun.
    $trendPeak = $trend === [] ? 0.0 : max(array_map(fn ($row) => (float) $row['value'], $trend));
    $trendSteps = 4;

    $niceStep = static function (float $rough): float {
        if ($rough <= 0) {
            return 1.0;
        }

        $magnitude = 10 ** floor(log10($rough));
        $ratio = $rough / $magnitude;
        $step = match (true) {
            $ratio <= 1.0 => 1.0,
            $ratio <= 2.0 => 2.0,
            $ratio <= 2.5 => 2.5,
            $ratio <= 5.0 => 5.0,
            default => 10.0,
        };

        return $step * $magnitude;
    };

    $trendStep = $niceStep($trendPeak / $trendSteps);
    $trendAxisMax = max($trendStep * ceil($trendPeak / $trendStep), $trendStep);

    // Langkah yang lebih kecil dari satu tetap harus terbaca (0,2 — bukan "0").
    $trendDecimals = $trendStep < 1 ? (int) ceil(-log10($trendStep)) : 0;

    $trendTicks = [];
    for ($i = 0; $i * $trendStep <= $trendAxisMax + 1e-9; $i++) {
        $value = $i * $trendStep;
        $trendTicks[] = [
            'text' => $fmt($value, $trendDecimals),
            'pct' => round($value / $trendAxisMax * 100, 3),
            'base' => $i === 0,
        ];
    }

    $trendGutter = (int) round(max(array_map(fn ($tick) => mb_strlen($tick['text']), $trendTicks)) * 6.2) + 10;

    $hasTrend = $trendTotal > 0;
    $hasGroups = $groups !== [];

    // Metrik tanpa nilai dilewati, bukan ditampilkan sebagai "Belum ada data".
    $metrics = array_values(array_filter(
        $detail['metrics'] ?? [],
        fn (array $metric): bool => ($metric['value'] ?? null) !== null
    ));

    $shiftSpread = $detail['shiftSpread'] ?? [];
    $workload = $detail['workload'] ?? [];
    $overtime = $detail['overtime'] ?? ['hours' => [], 'count' => []];

    $hasShiftSpread = $shiftSpread !== [];
    $hasWorkload = ($workload['reports'] ?? 0) > 0;
@endphp

<div class="act-panel__inner">
    {{-- Kepala panel: kegiatan yang sedang dibaca dan angka utamanya, supaya
         isi panel tidak kehilangan konteks setelah tab berpindah. --}}
    <div class="act-panel__head">
        <span class="act-panel__title">{{ $detail['label'] }}</span>
        @if (isset($detail['value']))
            <div class="act-panel__metric">
                <span class="act-panel__figure">
                    {{ $fmt($detail['value'], 2) }}<span>{{ $detail['unit'] }}</span>
                </span>

                {{-- Sparkline enam bulan tepat di bawah satuannya. Warnanya
                     menjawab "lebih baik atau lebih buruk dari periode
                     sebelumnya", bentuknya menjawab "bagaimana jalannya".
                     Keterangan lengkapnya muncul saat disentuh, jadi kepala
                     panel tetap ringkas tapi warnanya tidak jadi teka-teki. --}}
                <div class="act-panel__spark"
                     data-chart-tip
                     data-tip-title="Tren 6 bulan · {{ $detail['label'] }}"
                     data-tip-rows="{{ json_encode(array_values(array_filter([
                        ['label' => 'Periode ini', 'value' => $fmt($detail['value'], 1).' '.$detail['unit'], 'color' => 'var(--chart-1)'],
                        ['label' => $trendDelta['available'] ? $trendDeltaWord : 'Pembanding', 'value' => $trendDelta['text'] ?? '-', 'color' => $trendToneColor],
                     ]))) }}">
                    @include('charts.sparkline', [
                        'points' => $detail['sparkline'] ?? '',
                        'tone' => $trendTone,
                        'id' => 'spark-act-'.($detail['key'] ?? 'panel'),
                        'class' => 'act-panel__spark-svg',
                        'label' => $trendSparkLabel,
                    ])
                </div>
            </div>
        @endif

        <span class="act-panel__caption">
            Periode {{ $detail['periodLabel'] }}.
            @if (! empty($detail['sparkline']))
                Grafik kecil di kanan: tren enam bulan — hijau bila naik, merah bila turun
                dibanding periode sebelumnya.
            @endif
        </span>
    </div>

    {{-- Rekap kegiatan ini: bulan berjalan, sebelumnya, dan akumulasinya. --}}
    @include('manajer.partials.activity-recap-cards', ['recap' => $detail['recap'] ?? []])

    {{-- Metrik sekunder dan catatan khas kegiatan berada dalam satu card,
         sejajar dengan rekap, tren, beban kerja, dan bagian lain di bawahnya. --}}
    @if ($metrics !== [] || ! empty($detail['note']))
        <div class="act-block">
            <span class="act-block__title">Indikator Kegiatan</span>
            <span class="act-block__subtitle">Metrik operasional khusus untuk kegiatan yang sedang dipilih.</span>

            @if ($metrics !== [])
                <div @class(['act-metrics', 'act-metrics--'.count($metrics) => count($metrics) < 4])>
                    @foreach ($metrics as $metric)
                        <div class="act-metric">
                            <span class="act-metric__label">{{ $metric['label'] }}</span>
                            <span class="act-metric__value">
                                {{ $fmt($metric['value'], $metric['decimals'] ?? 0) }}
                                <span class="act-metric__unit">{{ $metric['unit'] ?? '' }}</span>
                            </span>
                            @if (! empty($metric['caption']))
                                <span class="act-metric__caption">{{ $metric['caption'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if (! empty($detail['note']))
                <p class="act-panel__note">{{ $detail['note'] }}</p>
            @endif
        </div>
    @endif

    {{-- Dua kolom hanya kalau keduanya ada isinya; kalau tunggal, biarkan
         selebar panel supaya tidak menyisakan kolom kosong. --}}
    @if ($hasTrend || $hasGroups)
        <div @class(['perf-layout' => $hasTrend && $hasGroups])>
            {{-- Tren enam bulan untuk kegiatan ini saja --}}
            @if ($hasTrend)
                <div class="act-block">
                    <span class="act-block__title">Tren 6 Bulan</span>
                    <span class="act-block__subtitle">Nilai bulanan dalam {{ $detail['unit'] }}.</span>

                    {{-- Angka sumbu dan garis bantu berupa elemen di luar deret
                         batang, diposisikan dalam persen tinggi bidang plot —
                         jadi keduanya tetap sejajar berapa pun tinggi kartunya. --}}
                    <div class="act-trend" style="--act-trend-gutter: {{ $trendGutter }}px;">
                        <div class="act-trend__plot">
                            @foreach ($trendTicks as $tick)
                                <span @class(['act-trend__rule', 'act-trend__rule--base' => $tick['base']])
                                      style="bottom: {{ $tick['pct'] }}%" aria-hidden="true"></span>
                                <span class="act-trend__axis" style="bottom: {{ $tick['pct'] }}%">{{ $tick['text'] }}</span>
                            @endforeach

                            <div class="act-trend__cols">
                                @foreach ($trend as $bucket)
                                    @php
                                        $height = $trendAxisMax > 0 ? ((float) $bucket['value'] / $trendAxisMax) * 100 : 0;
                                    @endphp

                                    <div class="act-trend__col"
                                         data-chart-tip
                                         data-tip-title="{{ $bucket['label'] }}"
                                         data-tip-rows="{{ json_encode([
                                            ['label' => $detail['label'], 'value' => $fmt($bucket['value'], 1).' '.$detail['unit'], 'color' => 'var(--chart-1)'],
                                         ]) }}">
                                        <span class="act-trend__bar" style="height: {{ max(round($height, 2), $bucket['value'] > 0 ? 2 : 0) }}%;"></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="act-trend__labels">
                            @foreach ($trend as $bucket)
                                <span class="act-trend__label">{{ $bucket['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Peringkat regu untuk kegiatan ini --}}
            @if ($hasGroups)
                <div class="act-block">
                    <span class="act-block__title">Peringkat Regu</span>
                    <span class="act-block__subtitle">Seluruh regu tetap ditampilkan meski filter regu sedang aktif.</span>

                    @include('manajer.charts.bar-simple', [
                        'rows' => $groups,
                        'unit' => $detail['unit'],
                        'prefix' => 'Regu',
                        'emptyText' => 'Belum ada catatan kegiatan ini pada periode terpilih.',
                    ])
                </div>
            @endif
        </div>
    @endif

    {{-- Beban kerja & sebaran shift pada laporan yang memuat kegiatan ini --}}
    @if ($hasWorkload || $hasShiftSpread)
        <div @class(['perf-layout' => $hasWorkload && $hasShiftSpread])>
            @if ($hasWorkload)
                <div class="act-block">
                    <span class="act-block__title">Beban Kerja</span>
                    <span class="act-block__subtitle">
                        Dihitung dari {{ $fmt($workload['reports']) }} laporan yang memuat kegiatan ini.
                    </span>

                    <div class="perf-row">
                        <span class="perf-row__label">Personil rata-rata per shift</span>
                        <span class="perf-row__value">{{ $fmt($workload['personnelPerShift'] ?? 0, 1) }} orang</span>
                    </div>
                    <div class="perf-row">
                        <span class="perf-row__label">Jam lembur tercatat</span>
                        <span class="perf-row__value">{{ $fmt($workload['overtimeHours'] ?? 0, 1) }} jam</span>
                    </div>
                    <div class="perf-row">
                        <span class="perf-row__label">Entri lembur</span>
                        <span class="perf-row__value">{{ $fmt($workload['overtimeCount'] ?? 0) }} entri</span>
                    </div>
                    <div class="perf-row">
                        <span class="perf-row__label">Relief &amp; pengganti</span>
                        <span class="perf-row__value">{{ $fmt($workload['reliefCount'] ?? 0) }} kali</span>
                    </div>
                    <div class="perf-row">
                        <span class="perf-row__label">Ketepatan waktu lapor</span>
                        <span class="perf-row__value">{{ $fmt($workload['punctuality'] ?? 0, 1) }}%</span>
                    </div>
                </div>
            @endif

            @if ($hasShiftSpread)
                <div class="act-block">
                    <span class="act-block__title">Sebaran Kegiatan per Shift</span>
                    <span class="act-block__subtitle">Jumlah laporan yang memuat kegiatan ini.</span>

                    @include('manajer.charts.bar-simple', [
                        'rows' => $shiftSpread,
                        'unit' => 'laporan',
                        'prefix' => 'Shift',
                        'emptyText' => 'Belum ada laporan yang memuat kegiatan ini.',
                    ])
                </div>
            @endif
        </div>
    @endif

    {{-- Peringkat lembur pada kegiatan ini --}}
    <div class="act-block">
        <span class="act-block__title">Peringkat Lembur</span>
        <span class="act-block__subtitle">Seluruh personil pada laporan yang memuat kegiatan ini, diurutkan menurut total jam dan dibandingkan dengan periode sebelumnya.</span>

        @include('manajer.charts.overtime-leaders', ['leaders' => $overtime, 'visible' => 10])
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
</div>
