<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\BulkLoadingActivity;
use App\Models\DailyReport;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan performa divisi operasi dari laporan harian.
 *
 * Dipakai oleh kartu KPI di dashboard manajer dan halaman Performa Operasional.
 * Semua angka diturunkan dari data laporan yang sudah ada — tidak ada tabel baru.
 *
 * Catatan penting soal tonase: kolom qty_*_current berisi tonase shift itu saja,
 * sedangkan qty_*_prev adalah akumulasi shift sebelumnya. Karena itu penjumlahan
 * kolom current di rentang tanggal tidak menghitung ganda meskipun satu kapal
 * muncul pada banyak laporan.
 *
 * Catatan penting soal satuan: container dicatat dalam Teus (jumlah box), bukan
 * ton, sehingga tidak boleh ikut dijumlahkan ke Total Tonase. Penandanya ada di
 * activityCatalog() lewat `countsToTonnage`, bukan di masing-masing pemanggil,
 * supaya kekeliruan satuan tidak bisa terulang saat kegiatan baru ditambahkan.
 *
 * Catatan penting soal jumlah query: seluruh rincian per regu dan per shift
 * diambil dari satu query beragregasi per sumber, bukan satu query per regu.
 * Dengan begitu jumlah query tidak ikut tumbuh saat regu atau shift bertambah.
 */
class OperationalPerformanceService
{
    /**
     * Status laporan yang ikut dihitung. Draft selalu dikecualikan karena masih
     * bisa berubah; selebihnya mengikuti konvensi statistik dashboard yang lama.
     */
    public const COUNTED_STATUSES = [
        ReportStatus::Submitted->value,
        ReportStatus::Acknowledged->value,
        ReportStatus::Approved->value,
    ];

    /** Panjang deret tren bulanan untuk grafik dan sparkline. */
    private const TREND_MONTHS = 6;

    /** Batas baris tabel rincian pada panel kegiatan. */
    private const DETAIL_ROW_LIMIT = 50;

    /** Batas aman rincian per kegiatan yang boleh masuk satu sheet ekspor. */
    private const EXPORT_DETAIL_ROW_LIMIT = 5000;

    /**
     * Trucking dicatat satu baris per rit — bukan per kunjungan kapal seperti
     * kegiatan lain — sehingga daftarnya paling cepat memanjang. Panelnya cukup
     * menampilkan rit terbaru saja.
     */
    private const TURBA_ROW_LIMIT = 10;

    // ============================================================
    // Katalog kegiatan — satu-satunya sumber kebenaran
    // ============================================================

    /**
     * Lima jenis kegiatan operasi beserta asal datanya.
     *
     * `countsToTonnage` menandai kegiatan yang boleh dijumlahkan ke Total Tonase.
     * Container bernilai false karena satuannya Teus.
     *
     * `showOnPerformance` dan `showOnActivityDetail` menentukan kegiatan mana
     * yang muncul di tiap menu. Ditulis sekali di sini supaya service,
     * controller, view, dan ekspor tidak perlu menyalin daftarnya masing-masing.
     *
     * `recap` mengatur bentuk rekap bulanan gaya laporan manajemen: kolom apa
     * yang dihitung, satuan pencacahnya (kapal atau rit), dan — khusus
     * container — pemecahan baris menurut kolom penanda Empty/Full.
     *
     * @return array<string, array<string, mixed>>
     */
    public function activityCatalog(): array
    {
        return [
            'muat_kantong' => [
                'label' => 'Pemuatan Pupuk Kantong',
                'short' => 'Muat Kantong',
                'unit' => 'Ton',
                'icon' => 'fi fi-sr-box',
                'tint' => 'blue',
                'countsToTonnage' => true,
                'showOnPerformance' => true,
                'showOnActivityDetail' => true,
                'from' => 'loading_activities',
                'column' => 'loading_activities.qty_loading_current',
                'joins' => [],
                'reportKey' => 'loading_activities.daily_report_id',
                // Kolom tambahan yang ikut dijumlahkan pada query yang sama,
                // sehingga rasio kerusakan tidak butuh query sendiri.
                'extra' => [
                    'damage' => 'loading_activities.qty_damage_current',
                    'delivery' => 'loading_activities.qty_delivery_current',
                ],
                'recap' => [
                    'label' => 'Pemuatan Pupuk Kantong',
                    'countLabel' => 'Kapal',
                    'valueLabel' => 'Pemuatan',
                    'deliveryLabel' => 'Pengiriman',
                    'damageLabel' => 'Kerusakan',
                    'count' => $this->visitIdentity('loading_activities', 'loading_activities.arrival_time'),
                    'delivery' => 'loading_activities.qty_delivery_current',
                    'damage' => 'loading_activities.qty_damage_current',
                ],
            ],
            'muat_curah' => [
                'label' => 'Pemuatan Urea Curah',
                'short' => 'Muat Curah',
                'unit' => 'MT',
                'icon' => 'fi fi-sr-ship',
                'tint' => 'cyan',
                'countsToTonnage' => true,
                'showOnPerformance' => true,
                'showOnActivityDetail' => true,
                'from' => 'bulk_loading_logs',
                // cob berisi Cargo On Board, yaitu pembacaan KUMULATIF muatan di
                // kapal. Yang boleh dijumlahkan adalah pertambahannya, bukan
                // pembacaannya. Lihat BulkTonnageService.
                'column' => 'bulk_loading_logs.cob_delta',
                'joins' => [
                    ['bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', 'bulk_loading_activities.id'],
                ],
                'conditions' => [
                    ['bulk_loading_activities.activity_type', BulkLoadingActivity::TYPE_BULK_LOADING],
                ],
                'reportKey' => 'bulk_loading_activities.daily_report_id',
                'extra' => [],
                'recap' => [
                    'label' => 'Pemuatan Urea Curah',
                    'countLabel' => 'Kapal',
                    'valueLabel' => 'Pemuatan',
                    'count' => $this->bulkVisitIdentity(),
                ],
            ],
            'muat_amoniak' => [
                'label' => 'Pemuatan Amoniak',
                'short' => 'Muat Amoniak',
                'unit' => 'MT',
                'icon' => 'fi fi-rr-flask',
                'tint' => 'cyan',
                'countsToTonnage' => true,
                'showOnPerformance' => true,
                'showOnActivityDetail' => true,
                'from' => 'bulk_loading_logs',
                'column' => 'bulk_loading_logs.cob_delta',
                'joins' => [
                    ['bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', 'bulk_loading_activities.id'],
                ],
                'conditions' => [
                    ['bulk_loading_activities.activity_type', BulkLoadingActivity::TYPE_AMMONIA_LOADING],
                ],
                'reportKey' => 'bulk_loading_activities.daily_report_id',
                'extra' => [],
                'recap' => [
                    'label' => 'Pemuatan Amoniak',
                    'countLabel' => 'Kapal',
                    'valueLabel' => 'Pemuatan',
                    'count' => $this->bulkVisitIdentity(),
                ],
            ],
            'bongkar_bahan_baku' => [
                'label' => 'Bongkar Bahan Baku',
                'short' => 'Bongkar Bahan Baku',
                'unit' => 'Ton',
                'icon' => 'fi fi-sr-inbox-in',
                'tint' => 'green',
                'countsToTonnage' => true,
                'showOnPerformance' => true,
                'showOnActivityDetail' => true,
                'from' => 'material_items',
                'column' => 'material_items.qty_current',
                'joins' => [
                    ['material_activities', 'material_items.material_activity_id', 'material_activities.id'],
                ],
                'reportKey' => 'material_activities.daily_report_id',
                'extra' => [],
                'recap' => [
                    'label' => 'Bongkar Bahan Baku',
                    'countLabel' => 'Kapal',
                    'valueLabel' => 'Pembongkaran',
                    'count' => $this->visitIdentity('material_activities'),
                ],
            ],
            // Bongkar dan muat container tercatat menyatu pada satu bagian form
            // laporan, tetapi tiap barisnya menandai Empty atau Full. Penanda
            // itulah yang memisahkan keduanya menjadi dua kegiatan di sini,
            // tanpa menyentuh form maupun menambah kolom baru.
            'bongkar_container' => [
                'label' => 'Bongkar Container (Empty)',
                'short' => 'Bongkar Container',
                // Satuan Teus, bukan Ton, karena itu tidak ikut Total Tonase.
                'unit' => 'Teus',
                'icon' => 'fi fi-sr-container-storage',
                'tint' => 'orange',
                'countsToTonnage' => false,
                'showOnPerformance' => true,
                'showOnActivityDetail' => true,
                'from' => 'container_items',
                'column' => 'container_items.qty_current',
                'joins' => [
                    ['container_activities', 'container_items.container_activity_id', 'container_activities.id'],
                ],
                'conditions' => [['container_items.status', 'Empty']],
                'reportKey' => 'container_activities.daily_report_id',
                'extra' => [],
                'capacityColumn' => 'COALESCE(container_activities.capacity_empty, container_activities.capacity)',
                'recap' => [
                    'label' => 'Bongkar Container (Empty)',
                    'countLabel' => 'Kapal',
                    'summaryLabel' => 'Bongkar Container',
                    'valueLabel' => 'Bongkar Empty',
                    'count' => $this->visitIdentity('container_activities'),
                ],
            ],
            'muat_container' => [
                'label' => 'Muat Container (Full)',
                'short' => 'Muat Container',
                'unit' => 'Teus',
                'icon' => 'fi fi-sr-boxes',
                'tint' => 'cyan',
                'countsToTonnage' => false,
                'showOnPerformance' => true,
                'showOnActivityDetail' => true,
                'from' => 'container_items',
                'column' => 'container_items.qty_current',
                'joins' => [
                    ['container_activities', 'container_items.container_activity_id', 'container_activities.id'],
                ],
                'conditions' => [['container_items.status', 'Full']],
                'reportKey' => 'container_activities.daily_report_id',
                'extra' => [],
                'capacityColumn' => 'container_activities.capacity_full',
                'recap' => [
                    'label' => 'Muat Container (Full)',
                    'countLabel' => 'Kapal',
                    'summaryLabel' => 'Muat Container',
                    'valueLabel' => 'Muat Full',
                    'count' => $this->visitIdentity('container_activities'),
                ],
            ],
            'trucking_turba' => [
                'label' => 'Trucking Pengiriman Pupuk Kantong',
                'short' => 'Trucking Turba',
                'unit' => 'Ton',
                'icon' => 'fi fi-sr-truck-side',
                'tint' => 'blue',
                'countsToTonnage' => true,
                'showOnPerformance' => true,
                'showOnActivityDetail' => true,
                'from' => 'turba_deliveries',
                'column' => 'turba_deliveries.qty_current',
                'joins' => [
                    ['turba_activities', 'turba_deliveries.turba_activity_id', 'turba_activities.id'],
                ],
                'reportKey' => 'turba_activities.daily_report_id',
                'extra' => [],
                'recap' => [
                    'label' => 'Trucking Pengiriman Pupuk Kantong',
                    // Trucking tidak dihitung per kapal: satu baris sama dengan
                    // satu rit pengiriman.
                    'countLabel' => 'Rit',
                    'summaryLabel' => 'Trucking ke Gudang Turba',
                    'summaryCountLabel' => 'Rit/DO',
                    'valueLabel' => 'Pembongkaran',
                    'count' => 'turba_deliveries.id',
                ],
            ],
        ];
    }

    /**
     * Kegiatan yang boleh tampil pada satu menu.
     *
     * $surface bernilai 'performance' (Kinerja Operasi) atau 'activityDetail'
     * (Rincian Kegiatan). Dipakai service, controller, view, dan ekspor supaya
     * daftar kegiatan tidak pernah disalin manual di banyak berkas.
     *
     * @return array<string, array<string, mixed>>
     */
    public function activitiesFor(string $surface): array
    {
        $flag = match ($surface) {
            'performance' => 'showOnPerformance',
            'activityDetail' => 'showOnActivityDetail',
            default => throw new \InvalidArgumentException('Menu kegiatan tidak dikenal: '.$surface),
        };

        return array_filter(
            $this->activityCatalog(),
            static fn (array $activity): bool => (bool) ($activity[$flag] ?? false)
        );
    }

    /**
     * Apakah satu kegiatan boleh ditampilkan pada menu tertentu.
     */
    public function activityVisibleOn(string $key, string $surface): bool
    {
        return array_key_exists($key, $this->activitiesFor($surface));
    }

    /**
     * Kegiatan yang satuannya Ton — hanya ini yang boleh dijumlahkan.
     *
     * @return array<int, string>
     */
    private function tonnageKeys(): array
    {
        return array_keys(array_filter(
            $this->activityCatalog(),
            static fn (array $activity): bool => $activity['countsToTonnage']
        ));
    }

    /**
     * Kegiatan bermassa menurut satuan sumbernya. Ton dipakai kegiatan umum,
     * sedangkan MT dipakai pembacaan COB curah dan amoniak. Keduanya tetap
     * masuk total tonase, tetapi dipisah pada grafik agar labelnya jujur.
     *
     * @return array<int, string>
     */
    private function massKeysForUnit(string $unit): array
    {
        return array_keys(array_filter(
            $this->activityCatalog(),
            static fn (array $activity): bool => $activity['countsToTonnage'] && $activity['unit'] === $unit
        ));
    }

    /**
     * Kegiatan bersatuan Teus — bongkar muat container. Dijumlahkan terpisah
     * dari tonase karena satuannya berbeda, bukan karena nilainya diabaikan.
     *
     * @return array<int, string>
     */
    private function teusKeys(): array
    {
        return array_keys(array_filter(
            $this->activityCatalog(),
            static fn (array $activity): bool => ! $activity['countsToTonnage']
        ));
    }

    // ============================================================
    // Ringkasan untuk dashboard & halaman performa
    // ============================================================

    /**
     * Ringkasan Dashboard memakai capaian tahun berjalan sebagai nilai utama.
     * Badge tujuh kartu kegiatan membandingkan bulan berjalan dengan bulan
     * kalender sebelumnya agar rentang YTD tidak dibandingkan dengan rentang
     * yang bertumpang tindih.
     *
     * @return array<string, mixed>
     */
    public function dashboardKpi(): array
    {
        $today = Carbon::today();
        $filters = [
            'start' => $today->copy()->startOfYear(),
            'end' => $today,
        ];

        [$prevStart, $prevEnd] = $this->equivalentPreviousPeriod($filters['start'], $filters['end']);
        $previousMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();

        // Kartu "Kapal Dilayani" hanya ada di dashboard, jadi kunjungan kapal
        // memang perlu ditarik di sini — berbeda dengan halaman Kinerja Operasi
        // yang sudah tidak memakainya sama sekali.
        $matrix = $this->periodMatrix($filters, $prevStart, $prevEnd, withVisits: true);
        $monthly = $this->monthlyMatrix($filters, withVisits: true);

        $summary = $this->summaryFrom($matrix, 'ini', $filters);
        $previous = $this->summaryFrom($matrix, 'lalu', $filters);
        $trend = $this->trendSeries($monthly, $filters);

        return array_merge(
            $this->summaryCards($summary, $previous),
            [
                'periodLabel' => $this->periodLabel($filters['start'], $filters['end']),
                'periodStart' => $filters['start']->toDateString(),
                'periodEnd' => $filters['end']->toDateString(),
                'comparisonLabel' => 'vs '.$this->periodLabel($prevStart, $prevEnd),
                'trend' => $trend,
                'sparklines' => $this->sparklinesFor($trend),
                // Grid kegiatan memakai rekap yang sama dengan Kinerja Operasi,
                // lalu diperkaya satu pembanding bulanan khusus Dashboard.
                'activitySummary' => $this->dashboardActivitySummary(
                    $filters,
                    $previousMonthStart,
                    $previousMonthEnd,
                ),
            ]
        );
    }

    /**
     * Tambahkan delta bulan berjalan terhadap bulan kalender sebelumnya pada
     * rekap YTD tanpa mengubah struktur rekap yang dipakai Kinerja Operasi.
     *
     * @param  array{start: CarbonInterface, end: CarbonInterface}  $filters
     * @return array<string, mixed>
     */
    private function dashboardActivitySummary(
        array $filters,
        CarbonInterface $previousMonthStart,
        CarbonInterface $previousMonthEnd,
    ): array {
        $summary = $this->activityRecap($filters);
        $previousRows = collect($this->activityRecap([
            'start' => $previousMonthStart,
            'end' => $previousMonthEnd,
        ])['rows'] ?? [])->keyBy('key');
        $comparisonLabel = 'vs '.$previousMonthStart->copy()->locale('id')->translatedFormat('M Y');

        foreach ($summary['rows'] as $index => $row) {
            $currentValue = (float) ($row['month']['value'] ?? 0);
            $previousValue = (float) ($previousRows->get($row['key'])['total']['value'] ?? 0);

            $summary['rows'][$index]['comparison'] = [
                'current' => $currentValue,
                'previous' => $previousValue,
                'label' => $comparisonLabel,
                'delta' => $this->delta($currentValue, $previousValue),
            ];
        }

        $summary['comparisonLabel'] = $comparisonLabel;

        return $summary;
    }

    /**
     * Rangkuman lengkap untuk halaman Performa Operasional.
     *
     * @param  array{start: CarbonInterface, end: CarbonInterface, group?: ?string, shift?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function performanceReport(array $filters): array
    {
        $start = $filters['start'];
        $end = $filters['end'];
        [$prevStart, $prevEnd] = $this->equivalentPreviousPeriod($start, $end);

        // Kunjungan kapal tidak lagi dipakai halaman mana pun yang memakai
        // laporan ini — kartu, tabel, dan sheet Kapal Dilayani sudah dihapus —
        // jadi keempat query-nya sekalian tidak dijalankan.
        $matrix = $this->periodMatrix($filters, $prevStart, $prevEnd, withVisits: false, withActivityReports: true);
        $monthly = $this->monthlyMatrix($filters, withVisits: false);

        $summary = $this->summaryFrom($matrix, 'ini', $filters);
        $previous = $this->summaryFrom($matrix, 'lalu', $filters);
        $trend = $this->trendSeries($monthly, $filters);
        $kpiComparisonLabel = 'vs '.$this->periodLabel($prevStart, $prevEnd);
        $kpiCurrent = $summary;
        $kpiPrevious = $previous;

        // Nilai utama periode bawaan tetap merupakan capaian tahun berjalan.
        // Untuk badge warna pada kartu, perbandingan YTD tidak dipakai karena
        // rentangnya bertumpang tindih dengan pergeseran satu bulan. Gunakan
        // bulan berjalan melawan bulan sebelumnya dari seri bulanan yang sama.
        if ($this->isCurrentYearToDatePeriod($start, $end) && count($trend) >= 2) {
            $kpiCurrent = $this->summaryFromTrendBucket($trend[array_key_last($trend)]);
            $kpiPrevious = $this->summaryFromTrendBucket($trend[array_key_last($trend) - 1]);

            $previousMonth = Carbon::createFromFormat('Y-m-d', $trend[array_key_last($trend) - 1]['key'].'-01')
                ->locale('id');
            $kpiComparisonLabel = 'vs '.$previousMonth->translatedFormat('M Y');
        }

        $overtimeRows = $this->overtimeRows($filters);
        $previousOvertimeRows = $this->overtimeRows(array_merge($filters, [
            'start' => $prevStart,
            'end' => $prevEnd,
        ]));

        return [
            'periodLabel' => $this->periodLabel($start, $end),
            'comparisonLabel' => 'vs '.$this->periodLabel($prevStart, $prevEnd),
            'kpiComparisonLabel' => $kpiComparisonLabel,
            'summary' => $this->summaryCards(
                $summary,
                $previous,
                withShips: false,
                deltaCurrent: $kpiCurrent,
                deltaPrevious: $kpiPrevious,
            ),
            'reportCount' => $summary['reports'],
            'trend' => $trend,
            'sparklines' => $this->sparklinesFor($trend, withShips: false),
            'trendMax' => max(1.0, max(array_column($trend, 'tonnage') ?: [0.0])),
            'shiftTrend' => $this->shiftTrend($monthly, $filters, $this->massKeysForUnit('Ton')),
            'shiftTrendMt' => $this->shiftTrend($monthly, $filters, $this->massKeysForUnit('MT')),
            'shiftTrendTeus' => $this->shiftTrend($monthly, $filters, $this->teusKeys()),
            'groups' => $this->groupPerformance($matrix, $filters),
            'activities' => $this->activityBreakdown($matrix, $filters),
            'activityCards' => $this->activityCards($matrix, $monthly, $filters),
            'activityRecap' => $this->activityRecap($filters),
            'activityPanels' => $this->activityPerformancePanels(
                $matrix,
                $monthly,
                $filters,
                $overtimeRows,
                $previousOvertimeRows
            ),
            'shifts' => $this->shiftBreakdown($matrix, $filters),
            'workload' => $this->workload($matrix, $filters),
            // Tanpa batas: halaman menampilkan sepuluh teratas dan menyimpan
            // sisanya di balik tombol "lihat semua".
            'overtimeLeaders' => $this->overtimeLeadersFrom(
                $overtimeRows,
                limit: null,
                previousRows: $previousOvertimeRows
            ),
        ];
    }

    // ============================================================
    // Rentang periode bawaan
    // ============================================================

    /**
     * 1 Januari sampai hari berjalan — periode bawaan Kinerja Operasi.
     *
     * Untuk tahun yang sudah lewat, ujungnya 31 Desember karena "hari ini"
     * tidak berada di dalam tahun itu.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function yearToDateRange(?int $year = null): array
    {
        $today = Carbon::today();
        $year ??= (int) $today->year;

        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = $year === (int) $today->year ? $today->copy() : $start->copy()->endOfYear()->startOfDay();

        return [$start, $end];
    }

    /**
     * Tanggal 1 sampai hari berjalan — periode bawaan Rincian Kegiatan.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function currentMonthRange(): array
    {
        return [Carbon::today()->startOfMonth(), Carbon::today()];
    }

    /**
     * Daftar grup dan shift yang benar-benar ada di laporan, untuk mengisi filter.
     *
     * @return array{groups: array<int, string>, shifts: array<int, string>}
     */
    public function filterOptions(): array
    {
        $base = fn (string $column) => DailyReport::query()
            ->whereIn('status', self::COUNTED_STATUSES)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'groups' => $base('group_name'),
            'shifts' => $base('shift'),
        ];
    }

    // ============================================================
    // Matriks agregat — inti perhitungan
    //
    // Satu query per sumber, dikelompokkan menurut periode × regu × shift.
    // Filter regu/shift sengaja TIDAK diterapkan di SQL: hasilnya adalah
    // superset yang dipotong di PHP, sehingga tabel "Perbandingan Regu" tetap
    // memuat seluruh regu meski filter regu sedang aktif — persis seperti
    // perilaku halaman sebelumnya.
    // ============================================================

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function periodMatrix(
        array $filters,
        CarbonInterface $prevStart,
        CarbonInterface $prevEnd,
        bool $withVisits = true,
        bool $withActivityReports = false
    ): array {
        $range = [
            'start' => $filters['start'],
            'end' => $filters['end'],
            'prevStart' => $prevStart,
            'prevEnd' => $prevEnd,
        ];

        $activities = [];

        foreach ($this->activityCatalog() as $key => $source) {
            $activities[$key] = $this->sourceQuery($source, $range)
                ->groupBy(DB::raw('periode'), 'daily_reports.group_name', 'daily_reports.shift')
                ->selectRaw($this->periodCase().' as periode', [$filters['start']->toDateString()])
                ->selectRaw('daily_reports.group_name as regu')
                ->selectRaw('daily_reports.shift as shift')
                ->selectRaw('COALESCE(SUM('.$source['column'].'), 0) as total')
                ->tap(function (QueryBuilder $query) use ($source): void {
                    foreach ($source['extra'] as $alias => $column) {
                        $query->selectRaw('COALESCE(SUM('.$column.'), 0) as '.$alias);
                    }
                })
                ->get();
        }

        // Laporan ditarik satu baris per laporan, bukan sebagai hitungan yang
        // sudah teragregasi: analisis per kegiatan perlu tahu laporan mana saja
        // yang memuat kegiatan tertentu. Jumlah barisnya tetap kecil — sebanyak
        // laporan pada dua periode — dan hitungannya dilakukan di PHP.
        $reports = $this->reportQuery($range)
            ->selectRaw($this->periodCase().' as periode', [$filters['start']->toDateString()])
            ->selectRaw('daily_reports.id as report_id')
            ->selectRaw('daily_reports.group_name as regu')
            ->selectRaw('daily_reports.shift as shift')
            ->selectRaw('1 as total')
            ->selectRaw('CASE WHEN '.$this->sameDayExpression().' THEN 1 ELSE 0 END as ontime')
            ->get();

        return [
            'activities' => $activities,
            // Hanya halaman Kinerja Operasi yang memecah beban kerja dan lembur
            // per kegiatan; dashboard tidak perlu ikut membayar query-nya.
            'activityReports' => $withActivityReports ? $this->activityReportSets($range) : [],
            'reports' => $reports,
            'visits' => $withVisits ? $this->visitRows($range, $filters['start']) : null,
            'workload' => $this->workloadRows($range, $filters['start']),
        ];
    }

    /**
     * Laporan mana saja yang memuat tiap jenis kegiatan.
     *
     * Ditarik sebagai satu query gabungan (UNION) untuk kelima kegiatan, bukan
     * satu query per kegiatan, supaya beban kerja dan peringkat lembur bisa
     * dipotong per kegiatan tanpa menambah query sebanyak jumlah kegiatan.
     *
     * @param  array<string, mixed>  $range
     * @return array<string, array<int, bool>>
     */
    private function activityReportSets(array $range): array
    {
        $union = null;

        foreach ($this->activityCatalog() as $key => $source) {
            // Kunci kegiatan adalah konstanta katalog (huruf kecil + garis
            // bawah), bukan masukan pengguna, jadi aman ditulis langsung —
            // placeholder pada daftar select membuat UNION sulit dibaca driver.
            $query = $this->sourceQuery($source, $range)
                ->distinct()
                ->selectRaw("'".$key."' as activity")
                ->selectRaw('daily_reports.id as report_id');

            $union = $union === null ? $query : $union->union($query);
        }

        $sets = array_fill_keys(array_keys($this->activityCatalog()), []);

        if ($union === null) {
            return $sets;
        }

        foreach ($union->get() as $row) {
            $sets[$row->activity][(int) $row->report_id] = true;
        }

        return $sets;
    }

    /**
     * Baris kunjungan kapal tanpa agregasi. Satu kunjungan bisa tersebar di
     * beberapa laporan lintas shift, jadi penghitungan distinct-nya dilakukan
     * di PHP untuk tiap potongan yang dibutuhkan — menjumlahkan hasil COUNT
     * DISTINCT per potongan akan menghitung ganda.
     *
     * @param  array<string, mixed>  $range
     */
    private function visitRows(array $range, CarbonInterface $start): Collection
    {
        $rows = collect();

        foreach ($this->shipVisitSources() as $visit) {
            $rows = $rows->concat(
                $this->applyRangeFilters(
                    DB::table($visit['table'])->join('daily_reports', 'daily_reports.id', '=', $visit['table'].'.daily_report_id'),
                    $range
                )
                    ->whereNotNull($visit['table'].'.ship_name')
                    ->where($visit['table'].'.ship_name', '!=', '')
                    ->distinct()
                    ->selectRaw($this->periodCase().' as periode', [$start->toDateString()])
                    ->selectRaw('daily_reports.group_name as regu')
                    ->selectRaw('daily_reports.shift as shift')
                    ->selectRaw($visit['identity'].' as identity')
                    ->get()
            );
        }

        return $rows;
    }

    /**
     * Beban kerja personil dalam satu query: jumlah personil shift, entri dan
     * durasi lembur, serta relief/pengganti.
     *
     * @param  array<string, mixed>  $range
     */
    private function workloadRows(array $range, CarbonInterface $start): Collection
    {
        $overtime = "employee_logs.category = 'operasi' AND employee_logs.description = 'Lembur'";

        return $this->applyRangeFilters(
            DB::table('employee_logs')->join('daily_reports', 'daily_reports.id', '=', 'employee_logs.daily_report_id'),
            $range
        )
            // Ikut dikelompokkan per laporan supaya beban kerja bisa dipotong
            // menurut kegiatan yang ada di laporan itu, tanpa query tambahan.
            ->groupBy(DB::raw('periode'), 'daily_reports.group_name', 'daily_reports.shift', 'employee_logs.daily_report_id')
            ->selectRaw($this->periodCase().' as periode', [$start->toDateString()])
            ->selectRaw('daily_reports.group_name as regu')
            ->selectRaw('daily_reports.shift as shift')
            ->selectRaw('employee_logs.daily_report_id as report_id')
            ->selectRaw("SUM(CASE WHEN employee_logs.category = 'shift' THEN 1 ELSE 0 END) as personnel")
            ->selectRaw('SUM(CASE WHEN '.$overtime.' THEN 1 ELSE 0 END) as overtime_count')
            ->selectRaw(
                'SUM(CASE WHEN '.$overtime.' AND employee_logs.time_in IS NOT NULL AND employee_logs.time_out IS NOT NULL'
                .' THEN '.$this->overtimeSecondsExpression().' ELSE 0 END) as overtime_seconds'
            )
            ->selectRaw(
                "SUM(CASE WHEN (employee_logs.category = 'operasi' AND employee_logs.description = 'Relief')"
                ." OR employee_logs.category = 'replacement' THEN 1 ELSE 0 END) as relief"
            )
            ->get();
    }

    /**
     * Deret bulanan enam bulan terakhir, dikelompokkan menurut bulan × regu ×
     * shift. Satu query per sumber melayani grafik tren, grafik shift, dan
     * sparkline sekaligus.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function monthlyMatrix(array $filters, bool $withVisits = true): array
    {
        // Bulan berjalan hanya boleh dihitung sampai hari ini. Sebelumnya
        // endOfMonth() ikut menarik laporan bertanggal masa depan pada bulan
        // yang sama, sehingga grafik tren bisa berisi angka yang belum masuk
        // rentang KPI/rekap.
        $end = Carbon::today();
        $start = Carbon::today()->startOfMonth()->subMonthsNoOverflow(self::TREND_MONTHS - 1);
        $range = ['start' => $start, 'end' => $end];
        $bucket = $this->monthBucket('daily_reports.report_date');

        $activities = [];

        foreach ($this->activityCatalog() as $key => $source) {
            $activities[$key] = $this->sourceQuery($source, $range)
                ->groupBy(DB::raw($bucket), 'daily_reports.group_name', 'daily_reports.shift')
                ->selectRaw($bucket.' as bucket')
                ->selectRaw('daily_reports.group_name as regu')
                ->selectRaw('daily_reports.shift as shift')
                ->selectRaw('COALESCE(SUM('.$source['column'].'), 0) as total')
                ->tap(function (QueryBuilder $query) use ($source): void {
                    foreach ($source['extra'] as $alias => $column) {
                        $query->selectRaw('COALESCE(SUM('.$column.'), 0) as '.$alias);
                    }
                })
                ->get();
        }

        $reports = $this->reportQuery($range)
            ->groupBy(DB::raw($bucket), 'daily_reports.group_name', 'daily_reports.shift')
            ->selectRaw($bucket.' as bucket')
            ->selectRaw('daily_reports.group_name as regu')
            ->selectRaw('daily_reports.shift as shift')
            ->selectRaw('COUNT(*) as total')
            ->get();

        $visits = collect();

        foreach ($withVisits ? $this->shipVisitSources() : [] as $visit) {
            $visits = $visits->concat(
                $this->applyRangeFilters(
                    DB::table($visit['table'])->join('daily_reports', 'daily_reports.id', '=', $visit['table'].'.daily_report_id'),
                    $range
                )
                    ->whereNotNull($visit['table'].'.ship_name')
                    ->where($visit['table'].'.ship_name', '!=', '')
                    ->distinct()
                    ->selectRaw($bucket.' as bucket')
                    ->selectRaw('daily_reports.group_name as regu')
                    ->selectRaw('daily_reports.shift as shift')
                    ->selectRaw($visit['identity'].' as identity')
                    ->get()
            );
        }

        return [
            'buckets' => $this->monthBuckets($start, $end),
            'activities' => $activities,
            'reports' => $reports,
            'visits' => $visits,
        ];
    }

    // ============================================================
    // Pemotongan matriks di PHP
    // ============================================================

    /**
     * Apakah satu baris agregat masuk potongan yang diminta.
     *
     * $ignore menyebut filter yang sengaja diabaikan: tabel perbandingan regu
     * mengabaikan filter regu, dan sebaran shift mengabaikan filter shift.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $ignore
     */
    private function rowMatches(object $row, array $filters, array $ignore = []): bool
    {
        if (! in_array('group', $ignore, true) && ! empty($filters['group']) && $row->regu !== $filters['group']) {
            return false;
        }

        if (! in_array('shift', $ignore, true) && ! empty($filters['shift']) && $row->shift !== $filters['shift']) {
            return false;
        }

        // Potongan per kegiatan: hanya baris dari laporan yang benar-benar
        // memuat kegiatan itu. Baris yang tidak membawa penanda laporan —
        // agregat per regu/shift — tidak terpengaruh.
        if (isset($filters['reports']) && isset($row->report_id) && ! isset($filters['reports'][(int) $row->report_id])) {
            return false;
        }

        return true;
    }

    /**
     * Jumlahkan satu kolom dari baris agregat sesuai potongan.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $ignore
     */
    private function sumRows(Collection $rows, ?string $periode, array $filters, array $ignore = [], string $field = 'total'): float
    {
        return (float) $rows
            ->filter(fn (object $row): bool => ($periode === null || $row->periode === $periode) && $this->rowMatches($row, $filters, $ignore))
            ->sum(fn (object $row) => (float) ($row->{$field} ?? 0));
    }

    /**
     * Hitung kunjungan kapal unik pada satu potongan.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $ignore
     */
    private function countVisits(Collection $rows, ?string $periode, array $filters, array $ignore = []): int
    {
        return $rows
            ->filter(fn (object $row): bool => ($periode === null || $row->periode === $periode) && $this->rowMatches($row, $filters, $ignore))
            ->pluck('identity')
            ->unique()
            ->count();
    }

    /**
     * Angka pokok satu periode: tonase, laporan, kapal, dan kerusakan.
     *
     * @param  array<string, mixed>  $matrix
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $ignore
     * @return array<string, mixed>
     */
    private function summaryFrom(array $matrix, string $periode, array $filters, array $ignore = []): array
    {
        $perActivity = [];

        foreach ($matrix['activities'] as $key => $rows) {
            $perActivity[$key] = $this->sumRows($rows, $periode, $filters, $ignore);
        }

        $tonnage = 0.0;

        foreach ($this->tonnageKeys() as $key) {
            $tonnage += $perActivity[$key] ?? 0.0;
        }

        $reports = (int) $this->sumRows($matrix['reports'], $periode, $filters, $ignore);
        $loading = $perActivity['muat_kantong'] ?? 0.0;
        $damage = $this->sumRows($matrix['activities']['muat_kantong'], $periode, $filters, $ignore, 'damage');

        return [
            'tonnage' => $tonnage,
            'perActivity' => $perActivity,
            'reports' => $reports,
            // Kunjungan kapal hanya ditarik untuk dashboard; halaman Kinerja
            // Operasi tidak lagi memakainya sehingga matriksnya kosong.
            'ships' => $matrix['visits'] instanceof Collection
                ? $this->countVisits($matrix['visits'], $periode, $filters, $ignore)
                : 0,
            'tonnagePerShift' => $reports > 0 ? $tonnage / $reports : 0.0,
            'damageRatio' => $loading > 0 ? ($damage / $loading) * 100 : 0.0,
            // Penanda apakah rasio kerusakan punya dasar hitung. Periode tanpa
            // muatan menghasilkan rasio 0% yang bukan capaian, jadi tidak boleh
            // dipakai sebagai pembanding.
            'hasDamageBase' => $loading > 0,
            'onTime' => (int) $this->sumRows($matrix['reports'], $periode, $filters, $ignore, 'ontime'),
        ];
    }

    /**
     * Empat indikator utama beserta deltanya.
     *
     * Kartu "Kapal Dilayani" hanya dipakai dashboard manajer. Halaman Kinerja
     * Operasi menggantinya dengan jumlah laporan masuk, sesuai arahan bahwa
     * blok kapal tidak lagi ditampilkan di sana.
     *
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function summaryCards(
        array $summary,
        array $previous,
        bool $withShips = true,
        ?array $deltaCurrent = null,
        ?array $deltaPrevious = null
    ): array {
        $deltaCurrent ??= $summary;
        $deltaPrevious ??= $previous;

        return [
            'tonnage' => [
                'value' => $summary['tonnage'],
                'delta' => $this->delta($deltaCurrent['tonnage'], $deltaPrevious['tonnage']),
            ],
            ...($withShips ? [
                'ships' => [
                    'value' => $summary['ships'],
                    'delta' => $this->delta($deltaCurrent['ships'], $deltaPrevious['ships']),
                ],
            ] : [
                'reports' => [
                    'value' => $summary['reports'],
                    'delta' => $this->delta($deltaCurrent['reports'], $deltaPrevious['reports']),
                ],
            ]),
            'tonnagePerShift' => [
                'value' => $summary['tonnagePerShift'],
                'delta' => $this->delta($deltaCurrent['tonnagePerShift'], $deltaPrevious['tonnagePerShift']),
            ],
            'damageRatio' => [
                'value' => $summary['damageRatio'],
                'hasBase' => $summary['hasDamageBase'],
                'delta' => $this->deltaPoint(
                    $deltaCurrent['damageRatio'],
                    $deltaPrevious['damageRatio'],
                    $deltaCurrent['hasDamageBase'] && $deltaPrevious['hasDamageBase']
                ),
            ],
        ];
    }

    /**
     * Bentuk ringkasan minimal dari satu titik tren bulanan agar rumus delta
     * kartu sama persis dengan ringkasan periode utama.
     *
     * @param  array<string, mixed>  $bucket
     * @return array<string, float|int|bool>
     */
    private function summaryFromTrendBucket(array $bucket): array
    {
        return [
            'tonnage' => (float) ($bucket['tonnage'] ?? 0),
            'ships' => (int) ($bucket['ships'] ?? 0),
            'reports' => (int) ($bucket['reports'] ?? 0),
            'tonnagePerShift' => (float) ($bucket['tonnagePerShift'] ?? 0),
            'damageRatio' => (float) ($bucket['damageRatio'] ?? 0),
            'hasDamageBase' => (float) ($bucket['loading'] ?? 0) > 0,
        ];
    }

    // ============================================================
    // Rincian untuk halaman performa
    // ============================================================

    /**
     * @param  array<string, mixed>  $matrix
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function groupPerformance(array $matrix, array $filters): array
    {
        $groups = $matrix['reports']
            ->concat($matrix['activities']['muat_kantong'])
            ->pluck('regu')
            ->filter(fn ($regu) => $regu !== null && $regu !== '')
            ->unique()
            ->sort()
            ->values();

        $rows = [];

        foreach ($groups as $group) {
            $scoped = array_merge($filters, ['group' => $group]);
            $current = $this->summaryFrom($matrix, 'ini', $scoped);

            if ($current['reports'] === 0 && $current['tonnage'] <= 0.0) {
                continue;
            }

            $previous = $this->summaryFrom($matrix, 'lalu', $scoped);

            $rows[] = [
                'name' => $group,
                'reports' => $current['reports'],
                'tonnage' => $current['tonnage'],
                'tonnagePerShift' => $current['tonnagePerShift'],
                'damageRatio' => $current['damageRatio'],
                'delta' => $this->delta($current['tonnage'], $previous['tonnage']),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['tonnage'] <=> $a['tonnage']);

        $max = $rows === [] ? 0.0 : (float) $rows[0]['tonnage'];

        foreach ($rows as $index => $row) {
            $rows[$index]['share'] = $max > 0 ? ($row['tonnage'] / $max) * 100 : 0.0;
        }

        return $rows;
    }

    /**
     * Komposisi tonase menurut kegiatan. Hanya kegiatan bersatuan Ton yang
     * masuk, karena porsinya dihitung terhadap total tonase.
     *
     * @param  array<string, mixed>  $matrix
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityBreakdown(array $matrix, array $filters): array
    {
        $catalog = $this->activityCatalog();
        $current = $this->summaryFrom($matrix, 'ini', $filters);
        $previous = $this->summaryFrom($matrix, 'lalu', $filters);

        $rows = [];
        $total = 0.0;

        foreach ($this->tonnageKeys() as $key) {
            $total += $current['perActivity'][$key] ?? 0.0;
        }

        $max = 0.0;

        foreach ($this->tonnageKeys() as $key) {
            $max = max($max, $current['perActivity'][$key] ?? 0.0);
        }

        foreach ($this->tonnageKeys() as $key) {
            $tonnage = $current['perActivity'][$key] ?? 0.0;

            $rows[] = [
                'key' => $key,
                'label' => $catalog[$key]['short'],
                'unit' => $catalog[$key]['unit'],
                'tonnage' => $tonnage,
                'contribution' => $total > 0 ? ($tonnage / $total) * 100 : 0.0,
                'share' => $max > 0 ? ($tonnage / $max) * 100 : 0.0,
                'delta' => $this->delta($tonnage, $previous['perActivity'][$key] ?? 0.0),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['tonnage'] <=> $a['tonnage']);

        return $rows;
    }

    /**
     * Kartu ringkas untuk kelima kegiatan. Seluruh angkanya diambil dari
     * matriks yang sudah dihitung, jadi tidak ada query tambahan.
     *
     * @param  array<string, mixed>  $matrix
     * @param  array<string, mixed>  $monthly
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityCards(array $matrix, array $monthly, array $filters): array
    {
        $current = $this->summaryFrom($matrix, 'ini', $filters);
        $previous = $this->summaryFrom($matrix, 'lalu', $filters);
        [$previousStart, $previousEnd] = $this->equivalentPreviousPeriod($filters['start'], $filters['end']);
        $defaultComparisonLabel = 'vs '.$this->periodLabel($previousStart, $previousEnd);
        $bucketKeys = array_keys($monthly['buckets']);
        $compareMonths = $this->isCurrentYearToDatePeriod($filters['start'], $filters['end'])
            && count($bucketKeys) >= 2;
        $cards = [];

        foreach ($this->activityCatalog() as $key => $activity) {
            $series = $this->activitySeries($monthly, $key, $filters);
            $comparisonCurrent = (float) ($current['perActivity'][$key] ?? 0.0);
            $comparisonPrevious = (float) ($previous['perActivity'][$key] ?? 0.0);
            $comparisonLabel = $defaultComparisonLabel;

            if ($compareMonths) {
                $comparisonCurrent = (float) ($series[array_key_last($series)] ?? 0.0);
                $comparisonPrevious = (float) ($series[array_key_last($series) - 1] ?? 0.0);
                $previousMonth = Carbon::createFromFormat('Y-m-d', $bucketKeys[array_key_last($bucketKeys) - 1].'-01')
                    ->locale('id');
                $comparisonLabel = 'vs '.$previousMonth->translatedFormat('M Y');
            }

            $cards[] = [
                'key' => $key,
                'label' => $activity['label'],
                'short' => $activity['short'],
                'unit' => $activity['unit'],
                'icon' => $activity['icon'],
                'tint' => $activity['tint'],
                'value' => $current['perActivity'][$key] ?? 0.0,
                'delta' => $this->delta($comparisonCurrent, $comparisonPrevious),
                'comparison' => [
                    'current' => $comparisonCurrent,
                    'previous' => $comparisonPrevious,
                    'label' => $comparisonLabel,
                ],
                'sparkline' => $this->sparklinePoints($series),
                'reports' => (int) $this->sumRows($matrix['reports'], 'ini', $filters),
            ];
        }

        return $cards;
    }

    /**
     * Deret bulanan satu kegiatan untuk sparkline kartunya.
     *
     * @param  array<string, mixed>  $monthly
     * @param  array<string, mixed>  $filters
     * @return array<int, float>
     */
    private function activitySeries(array $monthly, string $key, array $filters): array
    {
        $buckets = $monthly['buckets'];
        $totals = array_fill_keys(array_keys($buckets), 0.0);

        foreach ($monthly['activities'][$key] as $row) {
            if (isset($totals[$row->bucket]) && $this->rowMatches($row, $filters)) {
                $totals[$row->bucket] += (float) $row->total;
            }
        }

        return array_values($totals);
    }

    // ============================================================
    // Rekap kegiatan gaya laporan manajemen
    //
    // Bentuknya mengikuti rekap yang biasa dipaparkan: tiap kegiatan dibaca
    // dalam tiga kolom — bulan berjalan, bulan-bulan sebelumnya di dalam
    // periode, dan akumulasi keduanya — dengan pencacah kapal atau rit di
    // samping tonase/volumenya.
    // ============================================================

    /**
     * Rekap seluruh kegiatan menurut segmen bulan, dalam satu query gabungan.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function activityRecap(array $filters, ?array $keys = null): array
    {
        [$monthStart, $hasPrevious] = $this->recapMonthStart($filters);

        $catalog = $keys === null
            ? $this->activityCatalog()
            : array_intersect_key($this->activityCatalog(), array_flip($keys));

        $union = null;

        foreach ($catalog as $key => $source) {
            $recap = $source['recap'];

            $query = $this->scopedSourceQuery($source, $filters)
                ->groupBy(DB::raw('segmen'))
                ->selectRaw("'".$key."' as kegiatan")
                ->selectRaw($this->segmentCase().' as segmen', [$monthStart->toDateString()])
                ->selectRaw('COUNT(DISTINCT '.$recap['count'].') as jumlah')
                ->selectRaw('COALESCE(SUM('.$source['column'].'), 0) as muat')
                ->selectRaw('COALESCE(SUM('.($recap['delivery'] ?? '0').'), 0) as kirim')
                ->selectRaw('COALESCE(SUM('.($recap['damage'] ?? '0').'), 0) as kerusakan');

            $union = $union === null ? $query : $union->union($query);
        }

        $rows = $union === null ? collect() : $union->get();
        $totals = [];

        foreach ($rows as $row) {
            $totals[$row->kegiatan][$row->segmen === 'bulan' ? 'bulan' : 'sebelum'] = [
                'count' => (int) $row->jumlah,
                'value' => (float) $row->muat,
                'delivery' => (float) $row->kirim,
                'damage' => (float) $row->kerusakan,
            ];
        }

        return [
            'labels' => [
                'month' => $this->periodLabel($monthStart->greaterThan($filters['start']) ? $monthStart : $filters['start'], $filters['end']),
                'previous' => $hasPrevious
                    ? $this->periodLabel($filters['start'], $monthStart->copy()->subDay())
                    : null,
                'total' => $this->periodLabel($filters['start'], $filters['end']),
            ],
            'hasPrevious' => $hasPrevious,
            'rows' => $this->recapRows($totals, $catalog),
        ];
    }

    /**
     * Awal "bulan sekarang" pada rekap: bulan tempat periode berakhir, dibatasi
     * agar tidak keluar dari periode yang dipilih. Periode yang seluruhnya
     * berada di satu bulan tidak punya kolom pembanding.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonInterface, 1: bool}
     */
    private function recapMonthStart(array $filters): array
    {
        $monthStart = $filters['end']->copy()->startOfMonth();

        if ($monthStart->lessThanOrEqualTo($filters['start'])) {
            return [$filters['start']->copy(), false];
        }

        return [$monthStart, true];
    }

    /**
     * Susun baris rekap mengikuti urutan katalog.
     *
     * @param  array<string, array<string, array<string, float|int>>>  $totals
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array<int, array<string, mixed>>
     */
    private function recapRows(array $totals, array $catalog): array
    {
        $empty = ['count' => 0, 'value' => 0.0, 'delivery' => 0.0, 'damage' => 0.0];
        $rows = [];

        foreach ($catalog as $key => $source) {
            $recap = $source['recap'];
            $segments = $totals[$key] ?? [];
            $month = $segments['bulan'] ?? $empty;
            $previous = $segments['sebelum'] ?? $empty;

            $rows[] = [
                'key' => $key,
                'label' => $recap['label'],
                'summaryLabel' => $recap['summaryLabel'] ?? $recap['label'],
                // Dashboard memakai nama singkat agar tujuh kartu tetap
                // ringkas; Kinerja Operasi mempertahankan label rekap penuh.
                'dashboardLabel' => $recap['dashboardLabel'] ?? $source['short'],
                'unit' => $source['unit'],
                'countLabel' => $recap['countLabel'],
                'summaryCountLabel' => $recap['summaryCountLabel'] ?? $recap['countLabel'],
                'valueLabel' => $recap['valueLabel'],
                'deliveryLabel' => $recap['deliveryLabel'] ?? null,
                'damageLabel' => $recap['damageLabel'] ?? null,
                'icon' => $source['icon'],
                'tint' => $source['tint'],
                'hasDelivery' => isset($recap['delivery']),
                'hasDamage' => isset($recap['damage']),
                'month' => $month,
                'previous' => $previous,
                'total' => [
                    'count' => $month['count'] + $previous['count'],
                    'value' => $month['value'] + $previous['value'],
                    'delivery' => $month['delivery'] + $previous['delivery'],
                    'damage' => $month['damage'] + $previous['damage'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Penanda segmen rekap: bulan berjalan atau bulan-bulan sebelumnya.
     *
     * Kapal yang disandari melintasi dua segmen akan terhitung pada keduanya,
     * sehingga kolom akumulasi dijumlahkan dari dua segmen itu — sama seperti
     * cara rekap manual dibuat.
     */
    private function segmentCase(): string
    {
        return "CASE WHEN daily_reports.report_date >= ? THEN 'bulan' ELSE 'sebelum' END";
    }

    // ============================================================
    // Analisis per kegiatan pada halaman Kinerja Operasi
    //
    // Seluruh angkanya diambil dari matriks yang sudah dihitung untuk halaman,
    // jadi memecah analisis menjadi lima kegiatan tidak menambah satu query pun.
    // ============================================================

    /**
     * Satu panel analisis untuk tiap jenis kegiatan: tren, komposisi, sebaran
     * shift, rasio kerusakan, perbandingan regu, beban kerja, dan lembur.
     *
     * @param  array<string, mixed>  $matrix
     * @param  array<string, mixed>  $monthly
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityPerformancePanels(
        array $matrix,
        array $monthly,
        array $filters,
        Collection $overtimeRows,
        Collection $previousOvertimeRows
    ): array {
        $current = $this->summaryFrom($matrix, 'ini', $filters);
        $previous = $this->summaryFrom($matrix, 'lalu', $filters);
        $catalog = $this->activityCatalog();

        // Komposisi hanya membandingkan nilai bersatuan sama: Ton dijumlahkan
        // antar empat kegiatan, Teus berdiri sendiri.
        $unitTotals = [];

        foreach ($catalog as $key => $activity) {
            $unitTotals[$activity['unit']] = ($unitTotals[$activity['unit']] ?? 0.0) + ($current['perActivity'][$key] ?? 0.0);
        }

        $panels = [];

        foreach ($this->activitiesFor('performance') as $key => $activity) {
            $rows = $matrix['activities'][$key];
            $value = $current['perActivity'][$key] ?? 0.0;
            $reportSet = $matrix['activityReports'][$key] ?? [];
            $scoped = array_merge($filters, ['reports' => $reportSet]);
            $unitTotal = $unitTotals[$activity['unit']] ?? 0.0;

            $trend = $this->activityTrendSeries($monthly, $key, $filters);
            $peers = count(array_filter(
                $catalog,
                static fn (array $peer): bool => $peer['unit'] === $activity['unit']
            ));

            $panels[] = [
                'key' => $key,
                'label' => $activity['label'],
                'short' => $activity['short'],
                'unit' => $activity['unit'],
                'icon' => $activity['icon'],
                'tint' => $activity['tint'],
                'countsToTonnage' => $activity['countsToTonnage'],
                'value' => $value,
                'delta' => $this->delta($value, $previous['perActivity'][$key] ?? 0.0),
                'reports' => (int) $this->sumRows($matrix['reports'], 'ini', $scoped),
                'trend' => $trend,
                'trendMax' => max(1.0, max(array_column($trend, 'value') ?: [0.0])),
                'composition' => [
                    'contribution' => $unitTotal > 0 ? ($value / $unitTotal) * 100 : 0.0,
                    'unitTotal' => $unitTotal,
                    'peers' => $peers,
                ],
                'shifts' => $this->activityShiftValues($rows, $filters),
                'shiftSpread' => $this->activityShiftSpread($matrix['reports'], $scoped),
                'damage' => $this->activityDamage($activity, $rows, $filters),
                'groups' => $this->activityGroupValues($rows, $filters),
                'workload' => $this->activityWorkloadFrom($matrix, $scoped),
                'overtime' => $this->overtimeLeadersFrom(
                    $overtimeRows,
                    $reportSet,
                    previousRows: $previousOvertimeRows,
                    previousOnlyReports: $reportSet
                ),
            ];
        }

        return $panels;
    }

    /**
     * Tren enam bulan satu kegiatan, lengkap dengan label bulannya.
     *
     * @param  array<string, mixed>  $monthly
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityTrendSeries(array $monthly, string $key, array $filters): array
    {
        $values = $this->activitySeries($monthly, $key, $filters);
        $labels = array_values(array_column($monthly['buckets'], 'label'));

        $series = [];

        foreach ($values as $index => $value) {
            $series[] = ['label' => $labels[$index] ?? '-', 'value' => $value];
        }

        return $series;
    }

    /**
     * Tonase atau volume satu kegiatan menurut shift kerja.
     *
     * Filter shift tetap diterapkan: kalau manajer sedang menyaring satu shift,
     * yang tampil memang hanya shift itu.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityShiftValues(Collection $rows, array $filters): array
    {
        $totals = ['Pagi' => 0.0, 'Sore' => 0.0, 'Malam' => 0.0];

        foreach ($rows as $row) {
            if ($row->periode === 'ini' && $this->rowMatches($row, $filters)) {
                $totals[$this->normalizeShift($row->shift)] += (float) $row->total;
            }
        }

        return $this->sharedRows($totals);
    }

    /**
     * Sebaran jumlah laporan yang memuat satu kegiatan menurut shift.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityShiftSpread(Collection $reports, array $filters): array
    {
        $totals = ['Pagi' => 0.0, 'Sore' => 0.0, 'Malam' => 0.0];

        foreach ($reports as $row) {
            if ($row->periode === 'ini' && $this->rowMatches($row, $filters)) {
                $totals[$this->normalizeShift($row->shift)] += 1.0;
            }
        }

        return $this->sharedRows($totals);
    }

    /**
     * Kontribusi tiap regu untuk satu kegiatan. Filter regu sengaja diabaikan
     * supaya perbandingan antar regu tetap utuh; filter shift tetap berlaku.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityGroupValues(Collection $rows, array $filters): array
    {
        $totals = [];

        foreach ($rows as $row) {
            if ($row->periode !== 'ini' || ! $this->rowMatches($row, $filters, ['group'])) {
                continue;
            }

            $name = (string) ($row->regu ?? '');

            if ($name === '') {
                continue;
            }

            $totals[$name] = ($totals[$name] ?? 0.0) + (float) $row->total;
        }

        arsort($totals);

        return $this->sharedRows($totals);
    }

    /**
     * Rasio kerusakan satu kegiatan — hanya ada pada kegiatan yang sumbernya
     * memang menyimpan kolom kerusakan. Tanpa dasar hitung, nilainya null
     * supaya tampilan menandainya dengan strip, bukan 0%.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|null
     */
    private function activityDamage(array $activity, Collection $rows, array $filters): ?array
    {
        if (! isset($activity['extra']['damage'])) {
            return null;
        }

        $loaded = $this->sumRows($rows, 'ini', $filters);
        $damage = $this->sumRows($rows, 'ini', $filters, [], 'damage');

        return [
            'damage' => $damage,
            'loaded' => $loaded,
            'ratio' => $loaded > 0 ? ($damage / $loaded) * 100 : 0.0,
            'hasBase' => $loaded > 0,
        ];
    }

    /**
     * Beban kerja pada laporan yang memuat satu kegiatan.
     *
     * @param  array<string, mixed>  $matrix
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function activityWorkloadFrom(array $matrix, array $filters): array
    {
        $reports = (int) $this->sumRows($matrix['reports'], 'ini', $filters);
        $onTime = (int) $this->sumRows($matrix['reports'], 'ini', $filters, [], 'ontime');

        return [
            'reports' => $reports,
            'personnelPerShift' => $reports > 0
                ? $this->sumRows($matrix['workload'], 'ini', $filters, [], 'personnel') / $reports
                : 0.0,
            'overtimeHours' => $this->sumRows($matrix['workload'], 'ini', $filters, [], 'overtime_seconds') / 3600,
            'overtimeCount' => (int) $this->sumRows($matrix['workload'], 'ini', $filters, [], 'overtime_count'),
            'reliefCount' => (int) $this->sumRows($matrix['workload'], 'ini', $filters, [], 'relief'),
            'punctuality' => $reports > 0 ? ($onTime / $reports) * 100 : 0.0,
        ];
    }

    /**
     * Ubah pasangan nama → nilai menjadi baris siap tampil bagi partial batang,
     * lengkap dengan porsi terhadap nilai tertinggi. Nilai nol dibuang supaya
     * blok yang seluruhnya kosong tidak ikut dirender.
     *
     * @param  array<string, float>  $totals
     * @return array<int, array<string, mixed>>
     */
    private function sharedRows(array $totals): array
    {
        $totals = array_filter($totals, static fn (float $value): bool => $value > 0);

        if ($totals === []) {
            return [];
        }

        $max = max($totals);
        $sum = array_sum($totals);
        $rows = [];

        foreach ($totals as $name => $value) {
            $rows[] = [
                'name' => (string) $name,
                'value' => $value,
                'share' => $max > 0 ? ($value / $max) * 100 : 0.0,
                'contribution' => $sum > 0 ? ($value / $sum) * 100 : 0.0,
            ];
        }

        return $rows;
    }

    // ============================================================
    // Panel detail per kegiatan
    //
    // Dipanggil lewat endpoint tersendiri ketika tab kegiatan dibuka, bukan
    // saat halaman utama dirender, supaya beban query-nya hanya dibayar oleh
    // kegiatan yang benar-benar dilihat.
    // ============================================================

    /**
     * Isi satu panel kegiatan: metrik sekunder, tren bulanan, peringkat regu,
     * dan tabel rincian.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function activityDetail(string $key, array $filters, ?int $rowLimit = null): array
    {
        $catalog = $this->activityCatalog();
        $activity = $catalog[$key] ?? null;

        if ($activity === null) {
            throw new \InvalidArgumentException('Kegiatan tidak dikenal: '.$key);
        }

        $detailLimit = $rowLimit ?? ($key === 'trucking_turba' ? self::TURBA_ROW_LIMIT : self::DETAIL_ROW_LIMIT);

        $detail = match ($key) {
            'muat_kantong' => $this->baggedDetail($activity, $filters, $detailLimit),
            'muat_curah', 'muat_amoniak' => $this->bulkDetail($activity, $filters, $detailLimit),
            'bongkar_bahan_baku' => $this->materialDetail($activity, $filters, $detailLimit),
            'bongkar_container', 'muat_container' => $this->containerDetail($activity, $filters, $detailLimit),
            'trucking_turba' => $this->turbaDetail($activity, $filters, $detailLimit),
        };

        $trend = $this->activityTrend($activity, $filters);
        $reportStats = $this->activityReportStats($activity, $filters);
        // Rekap kegiatan ini saja: bentuk yang sama dengan rekap gabungan di
        // Kinerja Operasi, dihitung untuk satu kunci sehingga tetap satu query.
        $recap = $this->activityRecap($filters, [$key]);

        // Pembanding untuk warna sparkline di kepala panel. Diukur dengan kolom
        // yang sama seperti angka utamanya, jadi naik/turunnya benar-benar
        // membandingkan hal yang sama — bukan dua ukuran berbeda.
        [$prevStart, $prevEnd] = $this->equivalentPreviousPeriod($filters['start'], $filters['end']);
        $previousValue = (float) $this->scopedSourceQuery(
            $activity,
            array_merge($filters, ['start' => $prevStart, 'end' => $prevEnd])
        )->selectRaw('COALESCE(SUM('.$activity['column'].'), 0) as total')->value('total');

        return array_merge([
            'key' => $key,
            'label' => $activity['label'],
            'unit' => $activity['unit'],
            'icon' => $activity['icon'],
            'tint' => $activity['tint'],
            'periodLabel' => $this->periodLabel($filters['start'], $filters['end']),
            'breakdown' => null,
            'breakdownTitle' => null,
            'tableCaption' => null,
            'note' => null,
        ], $detail, [
            'trend' => $trend,
            'trendMax' => max(1.0, max(array_column($trend, 'value') ?: [0.0])),
            'sparkline' => $this->sparklinePoints(array_column($trend, 'value')),
            'delta' => $this->delta($detail['value'] ?? 0.0, $previousValue),
            'comparisonLabel' => 'vs '.$this->periodLabel($prevStart, $prevEnd),
            'groups' => $this->activityGroupRanking($activity, $filters),
            'shiftSpread' => $reportStats['shifts'],
            'workload' => $this->activityPanelWorkload($activity, $filters, $reportStats),
            'overtime' => $this->activityPanelOvertime($activity, $filters),
            'recap' => [
                'labels' => $recap['labels'],
                'hasPrevious' => $recap['hasPrevious'],
                'row' => $recap['rows'][0] ?? null,
            ],
        ]);
    }

    /**
     * Rincian untuk workbook: memuat hingga 5.000 baris per kegiatan, lebih
     * longgar dari panel layar tanpa membiarkan satu unduhan menghabiskan
     * memori server tanpa batas.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function activityDetailForExport(string $key, array $filters): array
    {
        return $this->activityDetail($key, $filters, self::EXPORT_DETAIL_ROW_LIMIT);
    }

    /**
     * Subquery berisi id laporan yang memuat satu kegiatan pada periode dan
     * filter yang sedang aktif. Dipakai sebagai batas untuk beban kerja dan
     * peringkat lembur panel, sehingga keduanya tetap satu query masing-masing.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     */
    private function activityReportScope(array $activity, array $filters): QueryBuilder
    {
        return $this->scopedSourceQuery($activity, $filters)
            ->select($activity['reportKey']);
    }

    /**
     * Jumlah laporan yang memuat satu kegiatan, ketepatan lapornya, dan
     * sebarannya menurut shift — semuanya dari satu query.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array{reports: int, onTime: int, shifts: array<int, array<string, mixed>>}
     */
    private function activityReportStats(array $activity, array $filters): array
    {
        $rows = $this->applyReportFilters(DB::table('daily_reports'), $filters)
            ->whereIn('daily_reports.id', $this->activityReportScope($activity, $filters))
            ->groupBy('daily_reports.shift')
            ->selectRaw('daily_reports.shift as shift')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN '.$this->sameDayExpression().' THEN 1 ELSE 0 END) as ontime')
            ->get();

        $totals = ['Pagi' => 0.0, 'Sore' => 0.0, 'Malam' => 0.0];
        $reports = 0;
        $onTime = 0;

        foreach ($rows as $row) {
            $totals[$this->normalizeShift($row->shift)] += (float) $row->total;
            $reports += (int) $row->total;
            $onTime += (int) $row->ontime;
        }

        return [
            'reports' => $reports,
            'onTime' => $onTime,
            'shifts' => $this->sharedRows($totals),
        ];
    }

    /**
     * Beban kerja personil pada laporan yang memuat satu kegiatan.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @param  array{reports: int, onTime: int, shifts: array<int, mixed>}  $reportStats
     * @return array<string, mixed>
     */
    private function activityPanelWorkload(array $activity, array $filters, array $reportStats): array
    {
        $overtime = "employee_logs.category = 'operasi' AND employee_logs.description = 'Lembur'";

        $row = $this->applyReportFilters(
            DB::table('employee_logs')->join('daily_reports', 'daily_reports.id', '=', 'employee_logs.daily_report_id'),
            $filters
        )
            ->whereIn('employee_logs.daily_report_id', $this->activityReportScope($activity, $filters))
            ->selectRaw("SUM(CASE WHEN employee_logs.category = 'shift' THEN 1 ELSE 0 END) as personnel")
            ->selectRaw('SUM(CASE WHEN '.$overtime.' THEN 1 ELSE 0 END) as overtime_count')
            ->selectRaw(
                'SUM(CASE WHEN '.$overtime.' AND employee_logs.time_in IS NOT NULL AND employee_logs.time_out IS NOT NULL'
                .' THEN '.$this->overtimeSecondsExpression().' ELSE 0 END) as overtime_seconds'
            )
            ->selectRaw(
                "SUM(CASE WHEN (employee_logs.category = 'operasi' AND employee_logs.description = 'Relief')"
                ." OR employee_logs.category = 'replacement' THEN 1 ELSE 0 END) as relief"
            )
            ->first();

        $reports = $reportStats['reports'];

        return [
            'reports' => $reports,
            'personnelPerShift' => $reports > 0 ? (float) ($row->personnel ?? 0) / $reports : 0.0,
            'overtimeHours' => (float) ($row->overtime_seconds ?? 0) / 3600,
            'overtimeCount' => (int) ($row->overtime_count ?? 0),
            'reliefCount' => (int) ($row->relief ?? 0),
            'punctuality' => $reports > 0 ? ($reportStats['onTime'] / $reports) * 100 : 0.0,
        ];
    }

    /**
     * Peringkat lembur pada laporan yang memuat satu kegiatan.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array{ranking: array<int, array<string, mixed>>, hours: array<int, array<string, mixed>>, count: array<int, array<string, mixed>>}
     */
    private function activityPanelOvertime(array $activity, array $filters): array
    {
        $rowsFor = function (array $rangeFilters) use ($activity): Collection {
            return $this->applyReportFilters(
                DB::table('employee_logs')->join('daily_reports', 'daily_reports.id', '=', 'employee_logs.daily_report_id'),
                $rangeFilters
            )
                ->whereIn('employee_logs.daily_report_id', $this->activityReportScope($activity, $rangeFilters))
                ->where('employee_logs.category', 'operasi')
                ->where('employee_logs.description', 'Lembur')
                ->whereNotNull('employee_logs.name')
                ->where('employee_logs.name', '!=', '')
                ->selectRaw('employee_logs.name as name')
                ->selectRaw('employee_logs.daily_report_id as report_id')
                ->selectRaw('daily_reports.group_name as group_name')
                ->selectRaw('employee_logs.time_in as time_in')
                ->selectRaw('employee_logs.time_out as time_out')
                ->get();
        };

        [$prevStart, $prevEnd] = $this->equivalentPreviousPeriod($filters['start'], $filters['end']);

        return $this->overtimeLeadersFrom(
            $rowsFor($filters),
            limit: null,
            previousRows: $rowsFor(array_merge($filters, [
                'start' => $prevStart,
                'end' => $prevEnd,
            ]))
        );
    }

    /**
     * Tren enam bulan terakhir untuk satu kegiatan saja.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityTrend(array $activity, array $filters): array
    {
        // Jangan membaca sisa tanggal pada bulan berjalan. Nilai grafik harus
        // konsisten dengan angka periode aktif yang paling jauh berakhir hari
        // ini, terutama bila ada data lama yang telanjur bertanggal masa depan.
        $end = Carbon::today();
        $start = Carbon::today()->startOfMonth()->subMonthsNoOverflow(self::TREND_MONTHS - 1);
        $bucket = $this->monthBucket('daily_reports.report_date');

        $totals = [];

        foreach ($this->monthBuckets($start, $end) as $monthKey => $month) {
            $totals[$monthKey] = ['label' => $month['label'], 'value' => 0.0];
        }

        $rows = $this->scopedSourceQuery($activity, array_merge($filters, ['start' => $start, 'end' => $end]))
            ->groupBy(DB::raw($bucket))
            ->selectRaw($bucket.' as bucket')
            ->selectRaw('COALESCE(SUM('.$activity['column'].'), 0) as total')
            ->get();

        foreach ($rows as $row) {
            if (isset($totals[$row->bucket])) {
                $totals[$row->bucket]['value'] = (float) $row->total;
            }
        }

        return array_values($totals);
    }

    /**
     * Peringkat regu untuk satu kegiatan. Filter regu sengaja diabaikan —
     * gunanya memang membandingkan antar regu, sama seperti tabel
     * "Perbandingan Regu" di halaman utama.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function activityGroupRanking(array $activity, array $filters): array
    {
        $rows = $this->scopedSourceQuery($activity, array_merge($filters, ['group' => null]))
            ->groupBy('daily_reports.group_name')
            ->selectRaw('daily_reports.group_name as name')
            ->selectRaw('COALESCE(SUM('.$activity['column'].'), 0) as total')
            ->get()
            ->filter(fn (object $row): bool => $row->name !== null && $row->name !== '' && (float) $row->total > 0)
            ->map(fn (object $row): array => ['name' => (string) $row->name, 'value' => (float) $row->total])
            ->sortByDesc('value')
            ->values()
            ->all();

        $max = $rows === [] ? 0.0 : (float) $rows[0]['value'];

        foreach ($rows as $index => $row) {
            $rows[$index]['share'] = $max > 0 ? ($row['value'] / $max) * 100 : 0.0;
        }

        return $rows;
    }

    /**
     * Query satu sumber kegiatan dengan filter periode + regu + shift.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     */
    private function scopedSourceQuery(array $activity, array $filters): QueryBuilder
    {
        $query = DB::table($activity['from']);

        foreach ($activity['joins'] as [$table, $left, $right]) {
            $query->join($table, $left, '=', $right);
        }

        $query->join('daily_reports', 'daily_reports.id', '=', $activity['reportKey']);

        $this->applyActivityConditions($query, $activity);

        return $this->applyReportFilters($query, $filters);
    }

    /**
     * Query tabel induk kegiatan (bukan tabel rinciannya), untuk metrik yang
     * bersifat per kapal/per kegiatan seperti kapasitas.
     *
     * @param  array<string, mixed>  $filters
     */
    private function parentQuery(string $table, array $filters): QueryBuilder
    {
        return $this->applyReportFilters(
            DB::table($table)->join('daily_reports', 'daily_reports.id', '=', $table.'.daily_report_id'),
            $filters
        );
    }

    /**
     * Rakit tabel rincian: buang kolom yang tidak terisi sama sekali, potong
     * daftarnya ke batas tampilan, lalu tandai bila masih ada sisanya.
     *
     * Kolom yang tidak dipakai di lapangan — mis. No DO/SO dan Kapasitas pada
     * trucking — hanya menghasilkan deretan tanda hubung yang membuat tabel
     * melebar tanpa menambah informasi. Kolom identitas selalu dipertahankan
     * supaya tabel tetap punya penanda baris meski sisanya kosong.
     *
     * Bila yang tersisa hanya kolom identitas, barisnya tidak menyimpan angka
     * apa pun untuk dibaca — tabelnya ditandai `blank` supaya tampilan cukup
     * menyebut jumlah barisnya, bukan mencetak deretan nama tanpa nilai.
     *
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{columns: array<int, array<string, mixed>>, rows: array<int, array<int, mixed>>, limited: bool, total: int, blank: bool}
     */
    private function detailTable(array $columns, array $rows, int $limit = self::DETAIL_ROW_LIMIT): array
    {
        $keep = [];

        foreach ($columns as $index => $column) {
            $keep[$index] = $this->isIdentityColumn($column) || $this->columnHasValue($rows, $index);
        }

        $columns = array_values(array_filter(
            $columns,
            fn (array $column, int $index): bool => $keep[$index],
            ARRAY_FILTER_USE_BOTH
        ));

        $rows = array_map(
            fn (array $row): array => array_values(array_filter(
                $row,
                fn (mixed $value, int $index): bool => $keep[$index] ?? true,
                ARRAY_FILTER_USE_BOTH
            )),
            $rows
        );

        $total = count($rows);
        $blank = $total > 0 && array_filter(
            $columns,
            fn (array $column): bool => ! $this->isIdentityColumn($column)
        ) === [];

        return [
            'columns' => $columns,
            'rows' => $blank ? [] : array_slice($rows, 0, $limit),
            'limited' => ! $blank && $total > $limit,
            'total' => $total,
            'blank' => $blank,
        ];
    }

    /**
     * Kolom penanda baris — nama dan tanggal. Kolom ini tidak pernah dipangkas
     * karena tanpanya baris kehilangan konteks, tapi juga tidak dihitung
     * sebagai isi: tabel yang hanya menyisakannya berarti tabel tanpa angka.
     *
     * @param  array<string, mixed>  $column
     */
    private function isIdentityColumn(array $column): bool
    {
        return ($column['identity'] ?? false) || ($column['type'] ?? '') === 'name';
    }

    /**
     * Satu kolom dianggap terisi bila ada minimal satu baris yang nilainya
     * bukan kosong: null, teks kosong, tanda hubung pengganti, atau angka nol.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function columnHasValue(array $rows, int $index): bool
    {
        foreach ($rows as $row) {
            $value = $row[$index] ?? null;

            if ($value === null || $value === '' || $value === '-') {
                continue;
            }

            if (is_numeric($value) && (float) $value === 0.0) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Pemuatan pupuk kantong: satu baris tabel mewakili satu kunjungan kapal.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function baggedDetail(array $activity, array $filters, int $limit): array
    {
        $totals = $this->parentQuery('loading_activities', $filters)
            ->selectRaw('COALESCE(SUM(loading_activities.qty_loading_current), 0) as loaded')
            ->selectRaw('COALESCE(SUM(loading_activities.qty_delivery_current), 0) as delivery')
            ->selectRaw('COALESCE(SUM(loading_activities.qty_damage_current), 0) as damage')
            ->selectRaw('COALESCE(AVG(NULLIF(loading_activities.tkbm_count, 0)), 0) as tkbm')
            ->selectRaw('COUNT(DISTINCT '.$this->visitIdentity('loading_activities', 'loading_activities.arrival_time').') as visits')
            ->first();

        // Dikelompokkan menurut identitas pelayaran, bukan nama mentah, supaya
        // satu kapal yang ejaannya berbeda antar shift tidak terpecah menjadi
        // beberapa baris dengan akumulasi masing-masing.
        $baggedVisit = $this->visitIdentity('loading_activities', 'loading_activities.arrival_time');

        $rows = $this->parentQuery('loading_activities', $filters)
            ->whereNotNull('loading_activities.ship_name')
            ->where('loading_activities.ship_name', '!=', '')
            ->groupBy(DB::raw($baggedVisit))
            ->selectRaw('MAX(loading_activities.ship_name) as ship_name')
            ->selectRaw('MAX(loading_activities.arrival_time) as moment')
            ->selectRaw('MAX(loading_activities.agent) as agent')
            ->selectRaw('MAX(loading_activities.jetty) as jetty')
            ->selectRaw('MAX(loading_activities.destination) as destination')
            ->selectRaw('MAX(loading_activities.capacity) as capacity')
            ->selectRaw('MAX(loading_activities.qty_loading_current + loading_activities.qty_loading_prev) as loaded')
            ->selectRaw('COALESCE(SUM(loading_activities.qty_damage_current), 0) as damage')
            ->selectRaw('MAX(loading_activities.tkbm_count) as tkbm')
            ->get()
            ->sortByDesc('loaded')
            ->map(fn (object $row): array => [
                (string) $row->ship_name,
                $row->agent ?: '-',
                $row->jetty ?: '-',
                $row->destination ?: '-',
                (float) $row->capacity,
                (float) $row->loaded,
                $this->realization((float) $row->loaded, (float) $row->capacity),
                (float) $row->damage,
                (int) $row->tkbm,
                $this->momentText($row->moment),
            ])
            ->values()
            ->all();

        $loaded = (float) ($totals->loaded ?? 0);
        $damage = (float) ($totals->damage ?? 0);

        return [
            'value' => $loaded,
            'metrics' => [
                ['label' => 'Kapal dilayani', 'value' => (int) ($totals->visits ?? 0), 'unit' => 'kapal', 'decimals' => 0],
                ['label' => 'Tonase delivery gudang → kapal', 'value' => (float) ($totals->delivery ?? 0), 'unit' => 'Ton', 'decimals' => 1],
                ['label' => 'Rasio kerusakan', 'value' => $loaded > 0 ? ($damage / $loaded) * 100 : 0.0, 'unit' => '%', 'decimals' => 2],
                ['label' => 'Rata-rata TKBM per kegiatan', 'value' => (float) ($totals->tkbm ?? 0), 'unit' => 'orang', 'decimals' => 1],
            ],
            'table' => $this->detailTable([
                ['label' => 'Kapal', 'type' => 'name'],
                ['label' => 'Agen', 'type' => 'muted'],
                ['label' => 'Dermaga', 'type' => 'muted'],
                ['label' => 'Tujuan', 'type' => 'muted'],
                ['label' => 'Kapasitas', 'type' => 'number', 'decimals' => 0, 'unit' => 'Ton'],
                ['label' => 'Termuat', 'type' => 'number', 'decimals' => 1, 'unit' => 'Ton'],
                ['label' => 'Realisasi', 'type' => 'ratio'],
                ['label' => 'Kerusakan', 'type' => 'number', 'decimals' => 2, 'unit' => 'Ton'],
                ['label' => 'TKBM', 'type' => 'number', 'decimals' => 0],
                ['label' => 'Waktu Tiba', 'type' => 'muted'],
            ], $rows, $limit),
            'note' => 'Termuat memakai akumulasi tertinggi (shift ini + shift sebelumnya), '
                .'sedangkan angka utama panel hanya menghitung tonase pada periode terpilih.',
        ];
    }

    /**
     * Pemuatan urea curah: jeda sandar → mulai muat jadi metrik khas panel ini
     * karena hanya di sini kedua waktunya tersimpan sebagai kolom tanggal-waktu.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function bulkDetail(array $activity, array $filters, int $limit): array
    {
        $totals = $this->scopedSourceQuery($activity, $filters)
            ->selectRaw('COALESCE(SUM(bulk_loading_logs.cob_delta), 0) as loaded')
            ->selectRaw('COUNT(bulk_loading_logs.id) as log_count')
            ->first();

        $rowsQuery = $this->parentQuery('bulk_loading_activities', $filters)
            ->leftJoin('bulk_loading_logs', 'bulk_loading_logs.bulk_loading_activity_id', '=', 'bulk_loading_activities.id')
            ->whereNotNull('bulk_loading_activities.ship_name')
            ->where('bulk_loading_activities.ship_name', '!=', '');

        $this->applyActivityConditions($rowsQuery, $activity);

        $rows = $rowsQuery
            ->groupBy(DB::raw($this->bulkVisitIdentity()))
            ->selectRaw('MAX(bulk_loading_activities.ship_name) as ship_name')
            ->selectRaw('MIN(bulk_loading_activities.berthing_time) as berthing')
            ->selectRaw('MAX(bulk_loading_activities.start_loading_time) as start_loading')
            ->selectRaw('MAX(bulk_loading_activities.agent) as agent')
            ->selectRaw('MAX(bulk_loading_activities.stevedoring) as stevedoring')
            ->selectRaw('MAX(bulk_loading_activities.commodity) as commodity')
            ->selectRaw('MAX(bulk_loading_activities.jetty) as jetty')
            ->selectRaw('MAX(bulk_loading_activities.capacity) as capacity')
            ->selectRaw('COALESCE(SUM(bulk_loading_logs.cob_delta), 0) as loaded')
            ->get()
            ->sortByDesc('loaded')
            ->values();

        $waits = [];

        $tableRows = $rows->map(function (object $row) use (&$waits): array {
            $wait = $this->waitHours($row->berthing, $row->start_loading);

            if ($wait !== null) {
                $waits[] = $wait;
            }

            return [
                (string) $row->ship_name,
                $row->agent ?: '-',
                $row->stevedoring ?: '-',
                $row->commodity ?: '-',
                $row->jetty ?: '-',
                (float) $row->capacity,
                (float) $row->loaded,
                $this->realization((float) $row->loaded, (float) $row->capacity),
                $this->momentText($row->berthing),
                $this->momentText($row->start_loading),
                $wait,
            ];
        })->all();

        return [
            'value' => (float) ($totals->loaded ?? 0),
            'metrics' => [
                ['label' => 'Kapal dilayani', 'value' => $rows->count(), 'unit' => 'kapal', 'decimals' => 0],
                ['label' => 'Entri log jam', 'value' => (int) ($totals->log_count ?? 0), 'unit' => 'entri', 'decimals' => 0],
                ['label' => 'Rata-rata COB per entri', 'value' => ($totals->log_count ?? 0) > 0 ? (float) $totals->loaded / (int) $totals->log_count : 0.0, 'unit' => 'MT', 'decimals' => 1],
                ['label' => 'Rata-rata jeda sandar → mulai muat', 'value' => $waits === [] ? null : array_sum($waits) / count($waits), 'unit' => 'jam', 'decimals' => 1],
            ],
            'table' => $this->detailTable([
                ['label' => 'Kapal', 'type' => 'name'],
                ['label' => 'Agen', 'type' => 'muted'],
                ['label' => 'Stevedoring', 'type' => 'muted'],
                ['label' => 'Komoditi', 'type' => 'muted'],
                ['label' => 'Dermaga', 'type' => 'muted'],
                ['label' => 'Kapasitas', 'type' => 'number', 'decimals' => 0, 'unit' => 'Ton'],
                ['label' => 'COB', 'type' => 'number', 'decimals' => 1, 'unit' => 'MT'],
                ['label' => 'Realisasi', 'type' => 'ratio'],
                ['label' => 'Sandar', 'type' => 'muted'],
                ['label' => 'Mulai Muat', 'type' => 'muted'],
                ['label' => 'Jeda', 'type' => 'number', 'decimals' => 1, 'unit' => 'jam'],
            ], $tableRows, $limit),
        ];
    }

    /**
     * Bongkar bahan baku: nilai tambah panelnya adalah komposisi menurut jenis
     * bahan baku, yang tidak terlihat di halaman utama.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function materialDetail(array $activity, array $filters, int $limit): array
    {
        $breakdown = $this->scopedSourceQuery($activity, $filters)
            ->groupBy('material_items.raw_material_type')
            ->selectRaw('material_items.raw_material_type as name')
            ->selectRaw('COALESCE(SUM(material_items.qty_current), 0) as total')
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->name ?: 'Tidak diisi',
                'value' => (float) $row->total,
            ])
            ->filter(fn (array $row): bool => $row['value'] > 0)
            ->sortByDesc('value')
            ->values()
            ->all();

        $rows = $this->parentQuery('material_activities', $filters)
            ->leftJoin('material_items', 'material_items.material_activity_id', '=', 'material_activities.id')
            // Dikelompokkan menurut nama kanonik, bukan nama mentah: satu kapal
            // yang ejaannya berbeda antar shift harus tetap satu baris dengan
            // tonase yang utuh.
            ->groupBy(DB::raw($this->visitIdentity('material_activities')))
            ->selectRaw('MAX(material_activities.ship_name) as ship_name')
            ->selectRaw('MAX(material_activities.agent) as agent')
            ->selectRaw('MAX(material_activities.jetty) as jetty')
            ->selectRaw('MAX(material_activities.capacity) as capacity')
            ->selectRaw('MAX(material_activities.working_hours) as working_hours')
            ->selectRaw('COALESCE(SUM(material_items.qty_current), 0) as loaded')
            ->selectRaw('COUNT(DISTINCT material_activities.id) as activities')
            ->get()
            ->sortByDesc('loaded')
            ->map(fn (object $row): array => [
                $row->ship_name ?: 'Nama kapal belum diisi',
                $row->agent ?: '-',
                $row->jetty ?: '-',
                (float) $row->capacity,
                (float) $row->loaded,
                $this->realization((float) $row->loaded, (float) $row->capacity),
                (int) $row->activities,
                $row->working_hours ?: '-',
            ])
            ->values()
            ->all();

        $total = array_sum(array_column($breakdown, 'value'));
        $max = $breakdown === [] ? 0.0 : (float) $breakdown[0]['value'];

        foreach ($breakdown as $index => $row) {
            $breakdown[$index]['share'] = $max > 0 ? ($row['value'] / $max) * 100 : 0.0;
            $breakdown[$index]['contribution'] = $total > 0 ? ($row['value'] / $total) * 100 : 0.0;
        }

        return [
            'value' => $total,
            'metrics' => [
                ['label' => 'Kapal dilayani', 'value' => count($rows), 'unit' => 'kapal', 'decimals' => 0],
                ['label' => 'Jenis bahan baku', 'value' => count($breakdown), 'unit' => 'jenis', 'decimals' => 0],
                ['label' => 'Kegiatan tercatat', 'value' => array_sum(array_column($rows, 6)), 'unit' => 'kegiatan', 'decimals' => 0],
                ['label' => 'Rata-rata per kapal', 'value' => $rows === [] ? 0.0 : $total / count($rows), 'unit' => 'Ton', 'decimals' => 1],
            ],
            'table' => $this->detailTable([
                ['label' => 'Kapal', 'type' => 'name'],
                ['label' => 'Agen', 'type' => 'muted'],
                ['label' => 'Dermaga', 'type' => 'muted'],
                ['label' => 'Kapasitas', 'type' => 'number', 'decimals' => 0, 'unit' => 'Ton'],
                ['label' => 'Bongkar', 'type' => 'number', 'decimals' => 1, 'unit' => 'Ton'],
                ['label' => 'Realisasi', 'type' => 'ratio'],
                ['label' => 'Kegiatan', 'type' => 'number', 'decimals' => 0],
                ['label' => 'Jam Kerja', 'type' => 'muted'],
            ], $rows, $limit),
            'breakdown' => $breakdown,
            'breakdownTitle' => 'Komposisi menurut Jenis Bahan Baku',
        ];
    }

    /**
     * Bongkar atau muat container, tergantung penanda Empty/Full pada katalog.
     * Seluruh angkanya bersatuan Teus.
     *
     * Kapasitas diambil dari tabel induk, dibatasi pada kegiatan yang benar-benar
     * punya baris dengan penanda yang sama, supaya kapal yang hanya melayani
     * bongkar tidak ikut membawa kapasitas muatnya.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function containerDetail(array $activity, array $filters, int $limit): array
    {
        $totals = $this->scopedSourceQuery($activity, $filters)
            ->selectRaw('COALESCE(SUM(container_items.qty_current), 0) as loaded')
            ->selectRaw('COUNT(container_items.id) as item_count')
            ->selectRaw('COUNT(DISTINCT '.$this->visitIdentity('container_activities').') as ships')
            ->first();

        $capacity = $this->parentQuery('container_activities', $filters)
            ->whereIn('container_activities.id', $this->scopedSourceQuery($activity, $filters)
                ->select('container_items.container_activity_id'))
            ->selectRaw('COALESCE(SUM('.$activity['capacityColumn'].'), 0) as capacity')
            ->first();

        $rows = $this->scopedSourceQuery($activity, $filters)
            ->orderByDesc('daily_reports.report_date')
            ->orderBy('container_items.id')
            ->selectRaw('container_activities.ship_name as ship_name')
            ->selectRaw('container_activities.agent as agent')
            ->selectRaw('container_activities.jetty as jetty')
            ->selectRaw('container_items.time_text as time_text')
            ->selectRaw('container_items.time as time')
            ->selectRaw('container_items.qty_current as qty_current')
            ->selectRaw('container_items.qty_prev as qty_prev')
            ->selectRaw('container_items.qty_total as qty_total')
            ->get()
            ->map(fn (object $row): array => [
                $row->ship_name ?: 'Nama kapal belum diisi',
                $row->agent ?: '-',
                $row->jetty ?: '-',
                $row->time_text ?: ($row->time ? substr((string) $row->time, 0, 5) : '-'),
                (float) $row->qty_current,
                (float) $row->qty_prev,
                (float) $row->qty_total,
            ])
            ->all();

        $loaded = (float) ($totals->loaded ?? 0);
        $capacityValue = (float) ($capacity->capacity ?? 0);

        return [
            'value' => $loaded,
            'metrics' => [
                ['label' => 'Kapal dilayani', 'value' => (int) ($totals->ships ?? 0), 'unit' => 'kapal', 'decimals' => 0],
                ['label' => 'Kapasitas tercatat', 'value' => $capacityValue, 'unit' => 'Teus', 'decimals' => 0],
                ['label' => 'Realisasi terhadap kapasitas', 'value' => $this->realization($loaded, $capacityValue), 'unit' => '%', 'decimals' => 1],
                ['label' => 'Baris kegiatan tercatat', 'value' => (int) ($totals->item_count ?? 0), 'unit' => 'baris', 'decimals' => 0],
            ],
            'table' => $this->detailTable([
                ['label' => 'Kapal', 'type' => 'name'],
                ['label' => 'Agen', 'type' => 'muted'],
                ['label' => 'Dermaga', 'type' => 'muted'],
                ['label' => 'Jam Kerja', 'type' => 'muted'],
                ['label' => 'Sekarang', 'type' => 'number', 'decimals' => 0, 'unit' => 'Teus'],
                ['label' => 'Lalu', 'type' => 'number', 'decimals' => 0, 'unit' => 'Teus'],
                ['label' => 'Total', 'type' => 'number', 'decimals' => 0, 'unit' => 'Teus'],
            ], $rows, $limit),
            'note' => 'Seluruh angka pada panel ini bersatuan Teus, sehingga tidak ikut '
                .'dijumlahkan ke Total Tonase. Pemisahan bongkar dan muat mengikuti '
                .'penanda Empty atau Full pada tiap baris laporan.',
        ];
    }

    /**
     * Trucking pengiriman pupuk kantong. Kolom truck_name pada data lapangan
     * berisi tujuan pengiriman (mis. Buffer Stock), bukan nama truk — jadi
     * yang diperingkat adalah tujuannya.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function turbaDetail(array $activity, array $filters, int $limit): array
    {
        $breakdown = $this->scopedSourceQuery($activity, $filters)
            ->groupBy('turba_deliveries.truck_name')
            ->selectRaw('turba_deliveries.truck_name as name')
            ->selectRaw('COALESCE(SUM(turba_deliveries.qty_current), 0) as total')
            ->selectRaw('COUNT(turba_deliveries.id) as trips')
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->name ?: 'Tujuan belum diisi',
                'value' => (float) $row->total,
                'trips' => (int) $row->trips,
            ])
            ->sortByDesc('value')
            ->values()
            ->all();

        $rows = $this->scopedSourceQuery($activity, $filters)
            ->orderByDesc('daily_reports.report_date')
            ->orderByDesc('turba_deliveries.id')
            ->selectRaw('daily_reports.report_date as report_date')
            ->selectRaw('turba_deliveries.truck_name as destination')
            ->selectRaw('turba_deliveries.do_so_number as do_so_number')
            ->selectRaw('turba_deliveries.marking_type as marking_type')
            ->selectRaw('turba_deliveries.capacity as capacity')
            ->selectRaw('turba_deliveries.qty_current as qty_current')
            ->selectRaw('turba_deliveries.qty_accumulated as qty_accumulated')
            ->get()
            ->map(fn (object $row): array => [
                $row->destination ?: 'Tujuan belum diisi',
                $this->dateText($row->report_date),
                $row->do_so_number ?: '-',
                $row->marking_type ?: '-',
                (float) $row->capacity,
                (float) $row->qty_current,
                $this->realization((float) $row->qty_current, (float) $row->capacity),
                (float) $row->qty_accumulated,
            ])
            ->all();

        $total = array_sum(array_column($breakdown, 'value'));
        $trips = array_sum(array_column($breakdown, 'trips'));
        $max = $breakdown === [] ? 0.0 : (float) $breakdown[0]['value'];

        foreach ($breakdown as $index => $row) {
            $breakdown[$index]['share'] = $max > 0 ? ($row['value'] / $max) * 100 : 0.0;
            $breakdown[$index]['contribution'] = $total > 0 ? ($row['value'] / $total) * 100 : 0.0;
        }

        return [
            'value' => $total,
            'metrics' => [
                ['label' => 'Rit / DO tercatat', 'value' => $trips, 'unit' => 'rit', 'decimals' => 0],
                ['label' => 'Tujuan pengiriman', 'value' => count($breakdown), 'unit' => 'tujuan', 'decimals' => 0],
                ['label' => 'Rata-rata muatan per rit', 'value' => $trips > 0 ? $total / $trips : 0.0, 'unit' => 'Ton', 'decimals' => 1],
                ['label' => 'Tujuan terbesar', 'value' => $breakdown === [] ? null : $breakdown[0]['value'], 'unit' => 'Ton', 'decimals' => 1, 'caption' => $breakdown[0]['name'] ?? null],
            ],
            'table' => $this->detailTable([
                ['label' => 'Tujuan', 'type' => 'name'],
                ['label' => 'Tanggal', 'type' => 'muted', 'identity' => true],
                ['label' => 'No DO/SO', 'type' => 'muted'],
                ['label' => 'Marking', 'type' => 'muted'],
                ['label' => 'Kapasitas', 'type' => 'number', 'decimals' => 0, 'unit' => 'Ton'],
                ['label' => 'Terkirim', 'type' => 'number', 'decimals' => 1, 'unit' => 'Ton'],
                ['label' => 'Realisasi', 'type' => 'ratio'],
                ['label' => 'Akumulasi', 'type' => 'number', 'decimals' => 1, 'unit' => 'Ton'],
            ], $rows, $limit),
            'tableCaption' => 'Diurutkan dari rit terbaru.',
            'breakdown' => $breakdown,
            'breakdownTitle' => 'Peringkat Tujuan Pengiriman',
        ];
    }

    /**
     * Realisasi terhadap kapasitas. Kapasitas kosong berarti tidak bisa
     * dihitung — dikembalikan null supaya tampilan menandainya, bukan 0%.
     */
    private function realization(float $value, float $capacity): ?float
    {
        return $capacity > 0 ? min(($value / $capacity) * 100, 999.9) : null;
    }

    /**
     * Selisih dua waktu dalam jam, untuk jeda sandar → mulai muat.
     */
    private function waitHours(mixed $from, mixed $to): ?float
    {
        if (! $from || ! $to) {
            return null;
        }

        $hours = Carbon::parse($from)->floatDiffInHours(Carbon::parse($to), false);

        return $hours >= 0 ? round($hours, 2) : null;
    }

    /**
     * Waktu siap tampil. Hasil panel masuk cache, jadi tanggal disimpan
     * sebagai teks — objek tanggal tidak aman melewati serialisasi.
     */
    private function momentText(mixed $moment): string
    {
        return $moment
            ? Carbon::parse($moment)->locale('id')->translatedFormat('d M · H:i')
            : '-';
    }

    /** Tanggal laporan siap tampil, dengan alasan yang sama seperti momentText(). */
    private function dateText(mixed $date): string
    {
        return $date
            ? Carbon::parse($date)->locale('id')->translatedFormat('d M Y')
            : '-';
    }

    /**
     * @param  array<string, mixed>  $matrix
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function shiftBreakdown(array $matrix, array $filters): array
    {
        $shifts = $matrix['reports']
            ->pluck('shift')
            ->filter(fn ($shift) => $shift !== null && $shift !== '')
            ->unique()
            ->values();

        $rows = [];

        foreach ($shifts as $shift) {
            $scoped = array_merge($filters, ['shift' => $shift]);
            $summary = $this->summaryFrom($matrix, 'ini', $scoped);

            if ($summary['reports'] === 0) {
                continue;
            }

            $rows[] = [
                'name' => $shift,
                'reports' => $summary['reports'],
                'tonnage' => $summary['tonnage'],
                'tonnagePerShift' => $summary['tonnagePerShift'],
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['tonnage'] <=> $a['tonnage']);

        return $rows;
    }

    /**
     * Beban kerja personil: rata-rata jumlah orang per shift, jam lembur,
     * relief/pengganti, dan kedisiplinan waktu pelaporan.
     *
     * @param  array<string, mixed>  $matrix
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function workload(array $matrix, array $filters): array
    {
        $totals = function (string $periode) use ($matrix, $filters): array {
            $reports = (int) $this->sumRows($matrix['reports'], $periode, $filters);
            $onTime = (int) $this->sumRows($matrix['reports'], $periode, $filters, [], 'ontime');

            return [
                'reports' => $reports,
                'personnelPerShift' => $reports > 0
                    ? $this->sumRows($matrix['workload'], $periode, $filters, [], 'personnel') / $reports
                    : 0.0,
                'overtimeHours' => $this->sumRows($matrix['workload'], $periode, $filters, [], 'overtime_seconds') / 3600,
                'overtimeCount' => (int) $this->sumRows($matrix['workload'], $periode, $filters, [], 'overtime_count'),
                'reliefCount' => (int) $this->sumRows($matrix['workload'], $periode, $filters, [], 'relief'),
                'punctuality' => $reports > 0 ? ($onTime / $reports) * 100 : 0.0,
            ];
        };

        $current = $totals('ini');
        $previous = $totals('lalu');

        return [
            'personnelPerShift' => $current['personnelPerShift'],
            'overtimeHours' => [
                'value' => $current['overtimeHours'],
                'delta' => $this->delta($current['overtimeHours'], $previous['overtimeHours']),
            ],
            'overtimeCount' => [
                'value' => $current['overtimeCount'],
                'delta' => $this->delta($current['overtimeCount'], $previous['overtimeCount']),
            ],
            'reliefCount' => [
                'value' => $current['reliefCount'],
                'delta' => $this->delta($current['reliefCount'], $previous['reliefCount']),
            ],
            'punctuality' => [
                'value' => $current['punctuality'],
                // Periode pembanding tanpa laporan menghasilkan 0% yang semu,
                // jadi delta hanya dihitung saat kedua periode punya laporan.
                'delta' => $this->deltaPoint(
                    $current['punctuality'],
                    $previous['punctuality'],
                    $current['reports'] > 0 && $previous['reports'] > 0,
                    downIsGood: false
                ),
            ],
        ];
    }

    /**
     * Baris lembur personil beserta regu asal laporan. Agregasi dan pengurutan
     * dilakukan setelah query supaya satu kumpulan data dapat dipakai ulang
     * untuk peringkat keseluruhan maupun tiap kegiatan.
     *
     * @param  array<string, mixed>  $filters
     */
    private function overtimeRows(array $filters): Collection
    {
        return $this->applyReportFilters(
            DB::table('employee_logs')->join('daily_reports', 'daily_reports.id', '=', 'employee_logs.daily_report_id'),
            $filters
        )
            ->where('employee_logs.category', 'operasi')
            ->where('employee_logs.description', 'Lembur')
            ->whereNotNull('employee_logs.name')
            ->where('employee_logs.name', '!=', '')
            ->selectRaw('employee_logs.name as name')
            ->selectRaw('employee_logs.daily_report_id as report_id')
            ->selectRaw('daily_reports.group_name as group_name')
            ->selectRaw('employee_logs.time_in as time_in')
            ->selectRaw('employee_logs.time_out as time_out')
            ->get();
    }

    /**
     * Peringkat lembur dari baris yang sudah ditarik. Dipisah dari query-nya
     * supaya halaman Kinerja Operasi bisa menyusun peringkat lintas divisi dan
     * peringkat per kegiatan dari satu kali pengambilan data.
     *
     * $onlyReports membatasi perhitungan pada laporan yang memuat satu kegiatan
     * tertentu; null berarti seluruh laporan pada periode terpilih.
     *
     * $limit bernilai null berarti seluruh personil ikut diurutkan — dipakai
     * halaman Kinerja Operasi dan panel kegiatan. Keduanya menampilkan sepuluh
     * teratas lebih dulu lalu membuka sisanya tanpa request baru.
     *
     * Posisi utama diurutkan dari total jam, lalu frekuensi dan nama. Perubahan
     * posisi dibandingkan dengan rentang pembanding yang setara. Regu dipilih
     * dari regu yang paling sering menaungi personil pada periode berjalan.
     *
     * @param  array<int, bool>|null  $onlyReports
     * @param  array<int, bool>|null  $previousOnlyReports
     * @return array{ranking: array<int, array<string, mixed>>, hours: array<int, array<string, mixed>>, count: array<int, array<string, mixed>>}
     */
    private function overtimeLeadersFrom(
        Collection $rows,
        ?array $onlyReports = null,
        ?int $limit = 5,
        ?Collection $previousRows = null,
        ?array $previousOnlyReports = null
    ): array {
        $people = $this->aggregateOvertimePeople($rows, $onlyReports);
        $previousPeople = $this->sortOvertimePeople(
            $this->aggregateOvertimePeople($previousRows ?? collect(), $previousOnlyReports)
        );

        $previousPositions = [];

        foreach ($previousPeople as $index => $person) {
            $previousPositions[$person['_key']] = $index + 1;
        }

        $ranking = $this->sortOvertimePeople($people);

        foreach ($ranking as $index => $person) {
            $position = $index + 1;
            $previousPosition = $previousPositions[$person['_key']] ?? null;
            $difference = $previousPosition === null ? null : $previousPosition - $position;

            $ranking[$index]['position'] = $position;
            $ranking[$index]['previousPosition'] = $previousPosition;
            $ranking[$index]['movement'] = match (true) {
                $difference === null => 'new',
                $difference > 0 => 'up',
                $difference < 0 => 'down',
                default => 'same',
            };
            $ranking[$index]['movementValue'] = abs((int) ($difference ?? 0));
            unset($ranking[$index]['_key']);
        }

        if ($limit !== null) {
            $ranking = array_slice($ranking, 0, $limit);
        }

        $take = static function (array $people, string $field, ?int $limit): array {
            $people = array_values(array_filter($people, fn (array $person) => $person[$field] > 0));

            usort($people, static function (array $a, array $b) use ($field): int {
                return ($b[$field] <=> $a[$field])
                    ?: ($b['hours'] <=> $a['hours'])
                    ?: ($b['count'] <=> $a['count'])
                    ?: strnatcasecmp($a['name'], $b['name']);
            });

            if ($limit !== null) {
                $people = array_slice($people, 0, $limit);
            }

            $peak = $people === [] ? 0.0 : (float) $people[0][$field];

            foreach ($people as $index => $person) {
                $people[$index]['share'] = $peak > 0 ? ($person[$field] / $peak) * 100 : 0.0;
                unset($people[$index]['_key']);
            }

            return $people;
        };

        return [
            'ranking' => $ranking,
            // Dipertahankan untuk kompatibilitas ekspor rincian kegiatan yang
            // masih memakai daftar jam dan frekuensi secara terpisah.
            'hours' => $take($people, 'hours', $limit),
            'count' => $take($people, 'count', $limit),
        ];
    }

    /**
     * @param  array<int, bool>|null  $onlyReports
     * @return array<int, array<string, mixed>>
     */
    private function aggregateOvertimePeople(Collection $rows, ?array $onlyReports = null): array
    {
        $people = [];

        foreach ($rows as $row) {
            if ($onlyReports !== null && ! isset($onlyReports[(int) $row->report_id])) {
                continue;
            }

            $key = mb_strtolower(trim((string) $row->name));

            if ($key === '') {
                continue;
            }

            $people[$key] ??= [
                '_key' => $key,
                'name' => trim((string) $row->name),
                'hours' => 0.0,
                'count' => 0,
                'groups' => [],
            ];
            $people[$key]['count']++;

            $group = strtoupper(trim((string) ($row->group_name ?? '')));

            if ($group !== '') {
                $people[$key]['groups'][$group] = ($people[$key]['groups'][$group] ?? 0) + 1;
            }

            if ($row->time_in && $row->time_out) {
                $people[$key]['hours'] += $this->durationInHours((string) $row->time_in, (string) $row->time_out);
            }
        }

        foreach ($people as $key => $person) {
            $groups = [];

            foreach ($person['groups'] as $group => $count) {
                $groups[] = ['name' => $group, 'count' => $count];
            }

            usort($groups, static fn (array $a, array $b): int => ($b['count'] <=> $a['count'])
                ?: strnatcasecmp($a['name'], $b['name']));

            $people[$key]['group'] = $groups[0]['name'] ?? '-';
            $people[$key]['averageHours'] = $person['count'] > 0
                ? $person['hours'] / $person['count']
                : 0.0;
            unset($people[$key]['groups']);
        }

        return array_values($people);
    }

    /**
     * @param  array<int, array<string, mixed>>  $people
     * @return array<int, array<string, mixed>>
     */
    private function sortOvertimePeople(array $people): array
    {
        usort($people, static fn (array $a, array $b): int => ($b['hours'] <=> $a['hours'])
            ?: ($b['count'] <=> $a['count'])
            ?: strnatcasecmp($a['name'], $b['name']));

        return $people;
    }

    /**
     * Selisih dua kolom TIME dalam jam. Jam pulang yang lebih kecil berarti
     * lembur melewati tengah malam, jadi ditambah satu hari.
     */
    private function durationInHours(string $timeIn, string $timeOut): float
    {
        $toSeconds = static function (string $value): int {
            $parts = array_map('intval', explode(':', $value) + [0, 0, 0]);

            return $parts[0] * 3600 + ($parts[1] ?? 0) * 60 + ($parts[2] ?? 0);
        };

        $start = $toSeconds($timeIn);
        $end = $toSeconds($timeOut);

        if ($end < $start) {
            $end += 86400;
        }

        return ($end - $start) / 3600;
    }

    // ============================================================
    // Deret bulanan
    // ============================================================

    /**
     * Kerangka bulan kosong sebagai wadah hasil agregasi.
     *
     * @return array<string, array<string, mixed>>
     */
    private function monthBuckets(CarbonInterface $start, CarbonInterface $end): array
    {
        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets[$cursor->format('Y-m')] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->locale('id')->translatedFormat('M'),
            ];
            $cursor->addMonthNoOverflow();
        }

        return $buckets;
    }

    /**
     * Deret bulanan untuk grafik tren dan sparkline kartu KPI.
     *
     * @param  array<string, mixed>  $monthly
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function trendSeries(array $monthly, array $filters): array
    {
        $buckets = [];

        foreach ($monthly['buckets'] as $key => $bucket) {
            $buckets[$key] = $bucket + [
                'tonnage' => 0.0,
                'ton' => 0.0,
                'metricTons' => 0.0,
                'teus' => 0.0,
                'reports' => 0,
                'ships' => 0,
                'loading' => 0.0,
                'damage' => 0.0,
            ];
        }

        foreach ($this->tonnageKeys() as $key) {
            foreach ($monthly['activities'][$key] as $row) {
                if (isset($buckets[$row->bucket]) && $this->rowMatches($row, $filters)) {
                    $value = (float) $row->total;
                    $buckets[$row->bucket]['tonnage'] += $value;

                    $field = $this->activityCatalog()[$key]['unit'] === 'MT'
                        ? 'metricTons'
                        : 'ton';
                    $buckets[$row->bucket][$field] += $value;
                }
            }
        }

        // Container punya deretnya sendiri: satuannya Teus, jadi tidak boleh
        // menumpang pada batang tonase meskipun periodenya sama.
        foreach ($this->teusKeys() as $key) {
            foreach ($monthly['activities'][$key] as $row) {
                if (isset($buckets[$row->bucket]) && $this->rowMatches($row, $filters)) {
                    $buckets[$row->bucket]['teus'] += (float) $row->total;
                }
            }
        }

        foreach ($monthly['activities']['muat_kantong'] as $row) {
            if (isset($buckets[$row->bucket]) && $this->rowMatches($row, $filters)) {
                $buckets[$row->bucket]['loading'] += (float) $row->total;
                $buckets[$row->bucket]['damage'] += (float) $row->damage;
            }
        }

        foreach ($monthly['reports'] as $row) {
            if (isset($buckets[$row->bucket]) && $this->rowMatches($row, $filters)) {
                $buckets[$row->bucket]['reports'] += (int) $row->total;
            }
        }

        $visitsByBucket = [];

        foreach ($monthly['visits'] as $row) {
            if (isset($buckets[$row->bucket]) && $this->rowMatches($row, $filters)) {
                $visitsByBucket[$row->bucket][$row->identity] = true;
            }
        }

        foreach ($visitsByBucket as $bucket => $identities) {
            $buckets[$bucket]['ships'] = count($identities);
        }

        return array_values(array_map(static function (array $bucket): array {
            $bucket['tonnagePerShift'] = $bucket['reports'] > 0 ? $bucket['tonnage'] / $bucket['reports'] : 0.0;
            $bucket['damageRatio'] = $bucket['loading'] > 0 ? ($bucket['damage'] / $bucket['loading']) * 100 : 0.0;

            return $bucket;
        }, $buckets));
    }

    /**
     * Kuantum bulanan yang dipecah menjadi tiga shift, untuk grafik area
     * bertumpuk. Secara bawaan hanya kegiatan bersatuan Ton; daftar kunci dapat
     * diganti dengan kegiatan Teus agar kedua satuan tetap berdiri sendiri.
     * Nama shift di data lapangan tidak seragam ("1", "Pagi", "Shift 1"),
     * jadi dirapikan dulu ke tiga kelompok tetap.
     *
     * @param  array<string, mixed>  $monthly
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function shiftTrend(array $monthly, array $filters, ?array $activityKeys = null): array
    {
        $buckets = [];

        foreach ($monthly['buckets'] as $key => $bucket) {
            $buckets[$key] = [
                'label' => $bucket['label'],
                'Pagi' => 0.0,
                'Sore' => 0.0,
                'Malam' => 0.0,
            ];
        }

        foreach (($activityKeys ?? $this->tonnageKeys()) as $key) {
            foreach ($monthly['activities'][$key] as $row) {
                if (isset($buckets[$row->bucket]) && $this->rowMatches($row, $filters)) {
                    $buckets[$row->bucket][$this->normalizeShift($row->shift)] += (float) $row->total;
                }
            }
        }

        return array_values(array_map(static function (array $bucket): array {
            $bucket['total'] = $bucket['Pagi'] + $bucket['Sore'] + $bucket['Malam'];

            return $bucket;
        }, $buckets));
    }

    /**
     * @param  array<int, array<string, mixed>>  $trend
     * @return array<string, string>
     */
    private function sparklinesFor(array $trend, bool $withShips = true): array
    {
        return [
            'tonnage' => $this->sparklinePoints(array_column($trend, 'tonnage')),
            ...($withShips
                ? ['ships' => $this->sparklinePoints(array_column($trend, 'ships'))]
                : ['reports' => $this->sparklinePoints(array_column($trend, 'reports'))]),
            'tonnagePerShift' => $this->sparklinePoints(array_column($trend, 'tonnagePerShift')),
            'damageRatio' => $this->sparklinePoints(array_column($trend, 'damageRatio')),
        ];
    }

    /**
     * Samakan penulisan shift ke tiga kelompok. Nilai yang tidak dikenali
     * dimasukkan ke shift pagi agar tonasenya tidak hilang dari grafik.
     */
    private function normalizeShift(?string $shift): string
    {
        $normalized = strtolower(trim((string) $shift));

        return match (true) {
            in_array($normalized, ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'], true) => 'Sore',
            in_array($normalized, ['3', 'malam', 'shift 3', 'shift malam'], true) => 'Malam',
            default => 'Pagi',
        };
    }

    /**
     * Dua sumber kunjungan kapal beserta ekspresi identitas kunjungannya.
     *
     * Satu kapal bisa muncul di banyak laporan lintas shift, jadi pembeda
     * kunjungan adalah pasangan nama kapal dengan waktu kedatangan (kantong)
     * atau waktu sandar (curah). ship_operations tidak dipakai karena
     * relasinya belum terisi pada data yang ada.
     *
     * @return array<int, array{table: string, identity: string}>
     */
    private function shipVisitSources(): array
    {
        return [
            [
                'table' => 'loading_activities',
                'identity' => $this->visitIdentity('loading_activities', 'loading_activities.arrival_time'),
            ],
            [
                'table' => 'bulk_loading_activities',
                'identity' => $this->bulkVisitIdentity(),
            ],
        ];
    }

    // ============================================================
    // Penyusun query dasar
    // ============================================================

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $range
     */
    private function sourceQuery(array $source, array $range): QueryBuilder
    {
        $query = DB::table($source['from']);

        foreach ($source['joins'] as [$table, $left, $right]) {
            $query->join($table, $left, '=', $right);
        }

        $query->join('daily_reports', 'daily_reports.id', '=', $source['reportKey']);

        $this->applyActivityConditions($query, $source);

        return $this->applyRangeFilters($query, $range);
    }

    /**
     * Syarat tambahan yang membatasi satu kegiatan pada sebagian barisnya.
     * Dipakai bongkar dan muat container, yang berbagi tabel tetapi dibedakan
     * oleh penanda Empty atau Full pada tiap barisnya.
     *
     * @param  array<string, mixed>  $source
     */
    private function applyActivityConditions(QueryBuilder $query, array $source): void
    {
        foreach ($source['conditions'] ?? [] as [$column, $value]) {
            $query->where($column, $value);
        }
    }

    /**
     * @param  array<string, mixed>  $range
     */
    private function reportQuery(array $range): QueryBuilder
    {
        return $this->applyRangeFilters(DB::table('daily_reports'), $range);
    }

    /**
     * Batasi query pada status yang dihitung dan rentang tanggal yang diminta.
     * Bila `prevStart` diisi, periode pembanding ikut ditarik dalam satu query
     * supaya keduanya tidak perlu dijalankan dua kali.
     *
     * @param  array<string, mixed>  $range
     */
    private function applyRangeFilters(QueryBuilder $query, array $range): QueryBuilder
    {
        $query->whereIn('daily_reports.status', self::COUNTED_STATUSES);

        $currentStart = $range['start']->toDateString();
        $currentEndExclusive = $range['end']->copy()->addDay()->toDateString();

        if (empty($range['prevStart'])) {
            return $query
                ->where('daily_reports.report_date', '>=', $currentStart)
                ->where('daily_reports.report_date', '<', $currentEndExclusive);
        }

        $previousStart = $range['prevStart']->toDateString();
        $previousEndExclusive = $range['prevEnd']->copy()->addDay()->toDateString();

        return $query->where(function (QueryBuilder $inner) use (
            $currentStart,
            $currentEndExclusive,
            $previousStart,
            $previousEndExclusive
        ): void {
            $inner->where(function (QueryBuilder $period) use ($currentStart, $currentEndExclusive): void {
                $period->where('daily_reports.report_date', '>=', $currentStart)
                    ->where('daily_reports.report_date', '<', $currentEndExclusive);
            })->orWhere(function (QueryBuilder $period) use ($previousStart, $previousEndExclusive): void {
                $period->where('daily_reports.report_date', '>=', $previousStart)
                    ->where('daily_reports.report_date', '<', $previousEndExclusive);
            });
        });
    }

    /**
     * Filter laporan versi sederhana (satu periode + regu/shift), dipakai oleh
     * bagian yang memang tidak butuh pembanding: daftar kapal, peringkat
     * lembur, dan tabel rincian panel kegiatan.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyReportFilters(QueryBuilder $query, array $filters): QueryBuilder
    {
        $query->whereIn('daily_reports.status', self::COUNTED_STATUSES)
            ->where('daily_reports.report_date', '>=', $filters['start']->toDateString())
            ->where('daily_reports.report_date', '<', $filters['end']->copy()->addDay()->toDateString());

        if (! empty($filters['group'])) {
            $query->where('daily_reports.group_name', $filters['group']);
        }

        if (! empty($filters['shift'])) {
            $query->where('daily_reports.shift', $filters['shift']);
        }

        return $query;
    }

    /**
     * Penanda periode pada baris agregat. Rentang periode ini dan periode
     * pembanding tidak pernah bertumpang tindih, jadi cukup dibedakan dari
     * tanggal mulai periode berjalan — nilainya diikat lewat binding selectRaw.
     */
    private function periodCase(): string
    {
        return "CASE WHEN daily_reports.report_date >= ? THEN 'ini' ELSE 'lalu' END";
    }

    // ============================================================
    // Ekspresi SQL yang berbeda antar driver
    //
    // Aplikasi berjalan di MySQL, sedangkan rangkaian pengujian memakai SQLite.
    // Fungsi tanggal dan teks di kedua driver tidak sama, jadi ekspresinya
    // dibangun sesuai koneksi yang sedang aktif.
    // ============================================================

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Pengelompokan per bulan dalam format YYYY-MM.
     */
    private function monthBucket(string $column): string
    {
        return $this->isSqlite()
            ? "strftime('%Y-%m', ".$column.')'
            : 'DATE_FORMAT('.$column.", '%Y-%m')";
    }

    /**
     * Laporan yang dibuat pada hari yang sama dengan tanggal laporannya.
     */
    private function sameDayExpression(): string
    {
        return $this->isSqlite()
            ? 'date(daily_reports.created_at) = date(daily_reports.report_date)'
            : 'DATE(daily_reports.created_at) = daily_reports.report_date';
    }

    /**
     * Penanda satu kunjungan kapal.
     *
     * Yang dipakai lebih dulu adalah nomor operasi kapal, karena itulah
     * identitas pelayaran yang sudah disepakati saat laporan disimpan. Nama
     * mentah tidak bisa menjadi penanda: satu kapal ditulis berbeda-beda tiap
     * shift ("KM. Golden Rejeki" / "KM. GOLDEN REJEKI"), sehingga satu
     * pelayaran akan terhitung sebagai beberapa kunjungan.
     *
     * Untuk baris lama yang belum punya nomor operasi, penggantinya adalah nama
     * KANONIK digabung waktu sandar/tiba — bukan nama mentah.
     */
    private function visitIdentity(string $table, ?string $momentColumn = null): string
    {
        $operation = $table.'.ship_operation_id';

        // Bongkar bahan baku dan container tidak mencatat waktu sandar, jadi
        // cadangannya cukup nama kanonik saja.
        $fallback = $momentColumn === null
            ? $this->shipIdentity($table)
            : ($this->isSqlite()
                ? $this->shipIdentity($table)." || '|' || COALESCE(".$momentColumn.", '')"
                : 'CONCAT('.$this->shipIdentity($table).", '|', COALESCE(".$momentColumn.", ''))");

        $fallback = $this->isSqlite()
            ? "'nama:' || ".$fallback
            : "CONCAT('nama:', ".$fallback.')';

        $matched = $this->isSqlite()
            ? "'operasi:' || ".$operation
            : "CONCAT('operasi:', ".$operation.')';

        return 'CASE WHEN '.$operation.' IS NOT NULL THEN '.$matched.' ELSE '.$fallback.' END';
    }

    /**
     * Nama kanonik kapal sebagai penanda cadangan — dipakai saat baris belum
     * terhubung ke operasi kapal, mis. laporan lama yang ditulis sebelum
     * kegiatan bongkar punya operasi kapal.
     *
     * Tanpa ini, "MV. Sumber Rezeki" dan "MV.SUMBER REZEKI" terhitung dua kapal
     * pada rekap bulanan dan muncul sebagai dua baris terpisah — masing-masing
     * hanya membawa sebagian tonasenya.
     */
    private function shipIdentity(string $table): string
    {
        return 'COALESCE(NULLIF('.$table.".ship_name_key, ''), ".$table.".ship_name, '')";
    }

    /**
     * Penanda kunjungan untuk muat curah dan muat amoniak — keduanya berbagi
     * satu tabel aktivitas.
     */
    private function bulkVisitIdentity(): string
    {
        return $this->visitIdentity('bulk_loading_activities', 'bulk_loading_activities.berthing_time');
    }

    /**
     * Durasi lembur dalam detik. Shift yang melewati tengah malam menghasilkan
     * jam pulang lebih kecil daripada jam masuk, jadi ditambah 24 jam.
     */
    private function overtimeSecondsExpression(): string
    {
        $in = $this->timeToSeconds('employee_logs.time_in');
        $out = $this->timeToSeconds('employee_logs.time_out');

        return $out.' - '.$in.' + CASE WHEN '.$out.' < '.$in.' THEN 86400 ELSE 0 END';
    }

    /**
     * Konversi kolom TIME menjadi detik sejak tengah malam.
     */
    private function timeToSeconds(string $column): string
    {
        if (! $this->isSqlite()) {
            return 'TIME_TO_SEC('.$column.')';
        }

        return '(CAST(substr('.$column.', 1, 2) AS INTEGER) * 3600'
            .' + CAST(substr('.$column.', 4, 2) AS INTEGER) * 60'
            .' + CAST(substr('.$column.', 7, 2) AS INTEGER))';
    }

    // ============================================================
    // Delta & pembanding periode
    // ============================================================

    /**
     * Periode pembanding yang setara dan tidak bertumpang tindih. Rentang YTD
     * memakai tanggal yang sama pada tahun sebelumnya. Rentang yang dimulai pada
     * tanggal 1 digeser sebanyak jumlah bulan yang dicakup; rentang bebas memakai
     * durasi yang sama tepat sebelum periode berjalan.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function equivalentPreviousPeriod(CarbonInterface $start, CarbonInterface $end): array
    {
        if ($this->isCurrentYearToDatePeriod($start, $end)) {
            return [
                $start->copy()->subYear(),
                $end->copy()->subYear(),
            ];
        }

        if ((int) $start->day === 1) {
            $monthSpan = (int) $start->copy()->startOfMonth()
                ->diffInMonths($end->copy()->startOfMonth(), true) + 1;

            return [
                $start->copy()->subMonthsNoOverflow($monthSpan)->startOfMonth(),
                $end->copy()->subMonthsNoOverflow($monthSpan),
            ];
        }

        $length = $start->diffInDays($end);
        $prevEnd = $start->copy()->subDay();

        return [$prevEnd->copy()->subDays($length), $prevEnd];
    }

    /**
     * Apakah rentang merupakan filter bawaan 1 Januari sampai hari ini.
     */
    private function isCurrentYearToDatePeriod(CarbonInterface $start, CarbonInterface $end): bool
    {
        $today = Carbon::today();

        return $start->toDateString() === $today->copy()->startOfYear()->toDateString()
            && $end->toDateString() === $today->toDateString();
    }

    /**
     * Delta relatif dalam persen. Baseline nol tidak pernah diubah menjadi
     * "+100%" karena menyesatkan — ditandai sebagai data baru.
     *
     * @return array<string, mixed>
     */
    private function delta(float|int $current, float|int $previous): array
    {
        if ((float) $previous <= 0.0) {
            return [
                'available' => false,
                'text' => (float) $current > 0.0 ? 'Baru pada periode ini' : 'Belum ada data',
                'direction' => 'flat',
                'tone' => 'flat',
            ];
        }

        $change = (((float) $current - (float) $previous) / (float) $previous) * 100;
        $direction = abs($change) < 0.05 ? 'flat' : ($change > 0 ? 'up' : 'down');

        return [
            'available' => true,
            'value' => $change,
            'text' => number_format(abs($change), 1, ',', '.').'%',
            'direction' => $direction,
            'tone' => $direction === 'flat' ? 'flat' : ($change > 0 ? 'up' : 'down'),
        ];
    }

    /**
     * Delta untuk metrik yang sudah berbentuk persen. Selisihnya adalah poin
     * persentase, bukan persen relatif — 0,24% ke 0,32% adalah +0,08 pp.
     *
     * $downIsGood mengatur nada warna: rasio kerusakan membaik ketika turun
     * (bawaan), sedangkan ketepatan waktu justru memburuk ketika turun.
     *
     * @return array<string, mixed>
     */
    private function deltaPoint(float $current, float $previous, bool $hasBaseline = true, bool $downIsGood = true): array
    {
        if (! $hasBaseline) {
            return [
                'available' => false,
                'text' => 'Belum ada pembanding',
                'direction' => 'flat',
                'tone' => 'flat',
            ];
        }

        $change = $current - $previous;

        if (abs($change) < 0.005) {
            return [
                'available' => true,
                'value' => 0.0,
                'text' => 'Tetap',
                'direction' => 'flat',
                'tone' => 'flat',
            ];
        }

        $direction = $change > 0 ? 'up' : 'down';
        $isGood = $downIsGood ? $change < 0 : $change > 0;

        return [
            'available' => true,
            'value' => $change,
            'text' => number_format(abs($change), 2, ',', '.').' pp',
            'direction' => $direction,
            'tone' => $isGood ? 'up' : 'down',
        ];
    }

    /**
     * Ubah deret angka menjadi atribut points untuk polyline sparkline
     * pada kanvas 100x24, tanpa perlu pustaka grafik.
     *
     * @param  array<int, float>  $values
     */
    private function sparklinePoints(array $values): string
    {
        $values = array_values(array_map('floatval', $values));
        $count = count($values);

        if ($count < 2) {
            return '';
        }

        $min = min($values);
        $max = max($values);
        $range = $max - $min;
        $step = 100 / ($count - 1);

        $points = [];

        foreach ($values as $index => $value) {
            $ratio = $range > 0 ? ($value - $min) / $range : 0.5;
            $points[] = round($index * $step, 2).','.round(22 - ($ratio * 20), 2);
        }

        return implode(' ', $points);
    }

    private function periodLabel(CarbonInterface $start, CarbonInterface $end): string
    {
        $start = $start->copy()->locale('id');
        $end = $end->copy()->locale('id');

        // Tanda hubung biasa, bukan en-dash: rentang tanggal ini ikut tercetak
        // ke Excel dan ke berkas ekspor, dan tanda panjang kerap berubah bentuk
        // di sana. Satu bentuk saja untuk seluruh aplikasi.
        if ($start->isSameMonth($end)) {
            return $start->translatedFormat('j').'-'.$end->translatedFormat('j M Y');
        }

        return $start->translatedFormat('j M').' - '.$end->translatedFormat('j M Y');
    }
}
