<?php

namespace App\Services;

use App\Enums\ReportStatus;
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
            ],
            'muat_curah' => [
                'label' => 'Pemuatan Urea Curah',
                'short' => 'Muat Curah',
                'unit' => 'Ton',
                'icon' => 'fi fi-sr-ship',
                'tint' => 'cyan',
                'countsToTonnage' => true,
                'from' => 'bulk_loading_logs',
                'column' => 'bulk_loading_logs.cob',
                'joins' => [
                    ['bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', 'bulk_loading_activities.id'],
                ],
                'reportKey' => 'bulk_loading_activities.daily_report_id',
                'extra' => [],
            ],
            'bongkar_bahan_baku' => [
                'label' => 'Bongkar Bahan Baku',
                'short' => 'Bongkar Bahan Baku',
                'unit' => 'Ton',
                'icon' => 'fi fi-sr-inbox-in',
                'tint' => 'green',
                'countsToTonnage' => true,
                'from' => 'material_items',
                'column' => 'material_items.qty_current',
                'joins' => [
                    ['material_activities', 'material_items.material_activity_id', 'material_activities.id'],
                ],
                'reportKey' => 'material_activities.daily_report_id',
                'extra' => [],
            ],
            'container' => [
                'label' => 'Bongkar/Muat Container',
                'short' => 'Container',
                // Satuan Teus, bukan Ton — karena itu tidak ikut Total Tonase.
                'unit' => 'Teus',
                'icon' => 'fi fi-sr-box-open',
                'tint' => 'orange',
                'countsToTonnage' => false,
                'from' => 'container_items',
                'column' => 'container_items.qty_current',
                'joins' => [
                    ['container_activities', 'container_items.container_activity_id', 'container_activities.id'],
                ],
                'reportKey' => 'container_activities.daily_report_id',
                'extra' => [],
            ],
            'trucking_turba' => [
                'label' => 'Trucking Pengiriman Pupuk Kantong',
                'short' => 'Trucking Turba',
                'unit' => 'Ton',
                'icon' => 'fi fi-sr-truck-side',
                'tint' => 'blue',
                'countsToTonnage' => true,
                'from' => 'turba_deliveries',
                'column' => 'turba_deliveries.qty_current',
                'joins' => [
                    ['turba_activities', 'turba_deliveries.turba_activity_id', 'turba_activities.id'],
                ],
                'reportKey' => 'turba_activities.daily_report_id',
                'extra' => [],
            ],
        ];
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

    // ============================================================
    // Ringkasan untuk dashboard & halaman performa
    // ============================================================

    /**
     * Ringkasan untuk empat kartu KPI dashboard: bulan berjalan dibanding
     * periode setara bulan lalu, lengkap dengan seri enam bulan untuk sparkline.
     *
     * @return array<string, mixed>
     */
    public function dashboardKpi(): array
    {
        $filters = [
            'start' => Carbon::today()->startOfMonth(),
            'end' => Carbon::today(),
        ];

        [$prevStart, $prevEnd] = $this->equivalentPreviousPeriod($filters['start'], $filters['end']);

        $matrix = $this->periodMatrix($filters, $prevStart, $prevEnd);
        $monthly = $this->monthlyMatrix($filters);

        $summary = $this->summaryFrom($matrix, 'ini', $filters);
        $previous = $this->summaryFrom($matrix, 'lalu', $filters);
        $trend = $this->trendSeries($monthly, $filters);

        return array_merge(
            $this->summaryCards($summary, $previous),
            [
                'periodLabel' => $this->periodLabel($filters['start'], $filters['end']),
                'comparisonLabel' => 'vs '.$this->periodLabel($prevStart, $prevEnd),
                'trend' => $trend,
                'sparklines' => $this->sparklinesFor($trend),
            ]
        );
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

        $matrix = $this->periodMatrix($filters, $prevStart, $prevEnd);
        $monthly = $this->monthlyMatrix($filters);

        $summary = $this->summaryFrom($matrix, 'ini', $filters);
        $previous = $this->summaryFrom($matrix, 'lalu', $filters);
        $trend = $this->trendSeries($monthly, $filters);

        return [
            'periodLabel' => $this->periodLabel($start, $end),
            'comparisonLabel' => 'vs '.$this->periodLabel($prevStart, $prevEnd),
            'summary' => $this->summaryCards($summary, $previous),
            'reportCount' => $summary['reports'],
            'trend' => $trend,
            'sparklines' => $this->sparklinesFor($trend),
            'trendMax' => max(1.0, max(array_column($trend, 'tonnage') ?: [0.0])),
            'shiftTrend' => $this->shiftTrend($monthly, $filters),
            'groups' => $this->groupPerformance($matrix, $filters),
            'activities' => $this->activityBreakdown($matrix, $filters),
            'activityCards' => $this->activityCards($matrix, $monthly, $filters),
            'shifts' => $this->shiftBreakdown($matrix, $filters),
            'workload' => $this->workload($matrix, $filters),
            'overtimeLeaders' => $this->overtimeLeaders($filters),
            'ships' => $this->shipList($filters),
        ];
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
    private function periodMatrix(array $filters, CarbonInterface $prevStart, CarbonInterface $prevEnd): array
    {
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

        $reports = $this->reportQuery($range)
            ->groupBy(DB::raw('periode'), 'daily_reports.group_name', 'daily_reports.shift')
            ->selectRaw($this->periodCase().' as periode', [$filters['start']->toDateString()])
            ->selectRaw('daily_reports.group_name as regu')
            ->selectRaw('daily_reports.shift as shift')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN '.$this->sameDayExpression().' THEN 1 ELSE 0 END) as ontime')
            ->get();

        return [
            'activities' => $activities,
            'reports' => $reports,
            'visits' => $this->visitRows($range, $filters['start']),
            'workload' => $this->workloadRows($range, $filters['start']),
        ];
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
            ->groupBy(DB::raw('periode'), 'daily_reports.group_name', 'daily_reports.shift')
            ->selectRaw($this->periodCase().' as periode', [$start->toDateString()])
            ->selectRaw('daily_reports.group_name as regu')
            ->selectRaw('daily_reports.shift as shift')
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
    private function monthlyMatrix(array $filters): array
    {
        $end = Carbon::today()->endOfMonth();
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

        foreach ($this->shipVisitSources() as $visit) {
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
            'ships' => $this->countVisits($matrix['visits'], $periode, $filters, $ignore),
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
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function summaryCards(array $summary, array $previous): array
    {
        return [
            'tonnage' => [
                'value' => $summary['tonnage'],
                'delta' => $this->delta($summary['tonnage'], $previous['tonnage']),
            ],
            'ships' => [
                'value' => $summary['ships'],
                'delta' => $this->delta($summary['ships'], $previous['ships']),
            ],
            'tonnagePerShift' => [
                'value' => $summary['tonnagePerShift'],
                'delta' => $this->delta($summary['tonnagePerShift'], $previous['tonnagePerShift']),
            ],
            'damageRatio' => [
                'value' => $summary['damageRatio'],
                'hasBase' => $summary['hasDamageBase'],
                'delta' => $this->deltaPoint(
                    $summary['damageRatio'],
                    $previous['damageRatio'],
                    $summary['hasDamageBase'] && $previous['hasDamageBase']
                ),
            ],
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
        $cards = [];

        foreach ($this->activityCatalog() as $key => $activity) {
            $series = $this->activitySeries($monthly, $key, $filters);

            $cards[] = [
                'key' => $key,
                'label' => $activity['label'],
                'short' => $activity['short'],
                'unit' => $activity['unit'],
                'icon' => $activity['icon'],
                'tint' => $activity['tint'],
                'value' => $current['perActivity'][$key] ?? 0.0,
                'delta' => $this->delta($current['perActivity'][$key] ?? 0.0, $previous['perActivity'][$key] ?? 0.0),
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
    public function activityDetail(string $key, array $filters): array
    {
        $catalog = $this->activityCatalog();
        $activity = $catalog[$key] ?? null;

        if ($activity === null) {
            throw new \InvalidArgumentException('Kegiatan tidak dikenal: '.$key);
        }

        $detail = match ($key) {
            'muat_kantong' => $this->baggedDetail($activity, $filters),
            'muat_curah' => $this->bulkDetail($activity, $filters),
            'bongkar_bahan_baku' => $this->materialDetail($activity, $filters),
            'container' => $this->containerDetail($activity, $filters),
            'trucking_turba' => $this->turbaDetail($activity, $filters),
        };

        $trend = $this->activityTrend($activity, $filters);

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
            'groups' => $this->activityGroupRanking($activity, $filters),
        ]);
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
        $end = Carbon::today()->endOfMonth();
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
    private function baggedDetail(array $activity, array $filters): array
    {
        $totals = $this->parentQuery('loading_activities', $filters)
            ->selectRaw('COALESCE(SUM(loading_activities.qty_loading_current), 0) as loaded')
            ->selectRaw('COALESCE(SUM(loading_activities.qty_delivery_current), 0) as delivery')
            ->selectRaw('COALESCE(SUM(loading_activities.qty_damage_current), 0) as damage')
            ->selectRaw('COALESCE(AVG(NULLIF(loading_activities.tkbm_count, 0)), 0) as tkbm')
            ->selectRaw('COUNT(DISTINCT '.$this->visitIdentity('loading_activities.ship_name', 'loading_activities.arrival_time').') as visits')
            ->first();

        $rows = $this->parentQuery('loading_activities', $filters)
            ->whereNotNull('loading_activities.ship_name')
            ->where('loading_activities.ship_name', '!=', '')
            ->groupBy('loading_activities.ship_name', 'loading_activities.arrival_time')
            ->selectRaw('loading_activities.ship_name as ship_name')
            ->selectRaw('loading_activities.arrival_time as moment')
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
            ], $rows),
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
    private function bulkDetail(array $activity, array $filters): array
    {
        $totals = $this->scopedSourceQuery($activity, $filters)
            ->selectRaw('COALESCE(SUM(bulk_loading_logs.cob), 0) as loaded')
            ->selectRaw('COUNT(bulk_loading_logs.id) as log_count')
            ->first();

        $rows = $this->parentQuery('bulk_loading_activities', $filters)
            ->leftJoin('bulk_loading_logs', 'bulk_loading_logs.bulk_loading_activity_id', '=', 'bulk_loading_activities.id')
            ->whereNotNull('bulk_loading_activities.ship_name')
            ->where('bulk_loading_activities.ship_name', '!=', '')
            ->groupBy('bulk_loading_activities.ship_name', 'bulk_loading_activities.berthing_time')
            ->selectRaw('bulk_loading_activities.ship_name as ship_name')
            ->selectRaw('bulk_loading_activities.berthing_time as berthing')
            ->selectRaw('MAX(bulk_loading_activities.start_loading_time) as start_loading')
            ->selectRaw('MAX(bulk_loading_activities.agent) as agent')
            ->selectRaw('MAX(bulk_loading_activities.stevedoring) as stevedoring')
            ->selectRaw('MAX(bulk_loading_activities.commodity) as commodity')
            ->selectRaw('MAX(bulk_loading_activities.jetty) as jetty')
            ->selectRaw('MAX(bulk_loading_activities.capacity) as capacity')
            ->selectRaw('COALESCE(SUM(bulk_loading_logs.cob), 0) as loaded')
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
                ['label' => 'Rata-rata COB per entri', 'value' => ($totals->log_count ?? 0) > 0 ? (float) $totals->loaded / (int) $totals->log_count : 0.0, 'unit' => 'Ton', 'decimals' => 1],
                ['label' => 'Rata-rata jeda sandar → mulai muat', 'value' => $waits === [] ? null : array_sum($waits) / count($waits), 'unit' => 'jam', 'decimals' => 1],
            ],
            'table' => $this->detailTable([
                ['label' => 'Kapal', 'type' => 'name'],
                ['label' => 'Agen', 'type' => 'muted'],
                ['label' => 'Stevedoring', 'type' => 'muted'],
                ['label' => 'Komoditi', 'type' => 'muted'],
                ['label' => 'Dermaga', 'type' => 'muted'],
                ['label' => 'Kapasitas', 'type' => 'number', 'decimals' => 0, 'unit' => 'Ton'],
                ['label' => 'COB', 'type' => 'number', 'decimals' => 1, 'unit' => 'Ton'],
                ['label' => 'Realisasi', 'type' => 'ratio'],
                ['label' => 'Sandar', 'type' => 'muted'],
                ['label' => 'Mulai Muat', 'type' => 'muted'],
                ['label' => 'Jeda', 'type' => 'number', 'decimals' => 1, 'unit' => 'jam'],
            ], $tableRows),
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
    private function materialDetail(array $activity, array $filters): array
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
            ->groupBy('material_activities.ship_name')
            ->selectRaw('material_activities.ship_name as ship_name')
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
            ], $rows),
            'breakdown' => $breakdown,
            'breakdownTitle' => 'Komposisi menurut Jenis Bahan Baku',
        ];
    }

    /**
     * Bongkar/muat container. Seluruh angkanya bersatuan Teus — bongkar dan
     * muat masih tercatat dalam satu bagian pada form laporan, jadi keduanya
     * tampil menyatu di sini.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function containerDetail(array $activity, array $filters): array
    {
        $totals = $this->scopedSourceQuery($activity, $filters)
            ->selectRaw('COALESCE(SUM(container_items.qty_current), 0) as loaded')
            ->selectRaw('COUNT(container_items.id) as item_count')
            ->first();

        $capacities = $this->parentQuery('container_activities', $filters)
            ->selectRaw('COALESCE(SUM(COALESCE(container_activities.capacity_empty, container_activities.capacity)), 0) as capacity_empty')
            ->selectRaw('COALESCE(SUM(container_activities.capacity_full), 0) as capacity_full')
            ->selectRaw('COUNT(DISTINCT container_activities.ship_name) as ships')
            ->first();

        $rows = $this->parentQuery('container_activities', $filters)
            ->join('container_items', 'container_items.container_activity_id', '=', 'container_activities.id')
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
            ->selectRaw('container_items.status as status')
            ->get()
            ->map(fn (object $row): array => [
                $row->ship_name ?: 'Nama kapal belum diisi',
                $row->agent ?: '-',
                $row->jetty ?: '-',
                $row->time_text ?: ($row->time ? substr((string) $row->time, 0, 5) : '-'),
                (float) $row->qty_current,
                (float) $row->qty_prev,
                (float) $row->qty_total,
                $row->status ?: '-',
            ])
            ->all();

        return [
            'value' => (float) ($totals->loaded ?? 0),
            'metrics' => [
                ['label' => 'Kapal dilayani', 'value' => (int) ($capacities->ships ?? 0), 'unit' => 'kapal', 'decimals' => 0],
                ['label' => 'Kapasitas Empty', 'value' => (float) ($capacities->capacity_empty ?? 0), 'unit' => 'Teus', 'decimals' => 0],
                ['label' => 'Kapasitas Full', 'value' => (float) ($capacities->capacity_full ?? 0), 'unit' => 'Teus', 'decimals' => 0],
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
                ['label' => 'Ket', 'type' => 'muted'],
            ], $rows),
            'note' => 'Seluruh angka pada panel ini bersatuan Teus, sehingga tidak ikut '
                .'dijumlahkan ke Total Tonase. Bongkar dan muat masih tercatat menyatu '
                .'karena form laporan memakai satu bagian untuk keduanya.',
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
    private function turbaDetail(array $activity, array $filters): array
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
            ], $rows, self::TURBA_ROW_LIMIT),
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
     * Lima personil dengan lembur terbanyak, diurutkan menurut dua ukuran
     * berbeda karena keduanya menjawab pertanyaan yang berbeda: jam lembur
     * menunjukkan beban waktu, sedangkan frekuensi menunjukkan seberapa sering
     * seseorang diminta. Keduanya perlu ditampilkan karena sebagian entri
     * lembur diisi tanpa jam masuk/pulang, sehingga hanya terhitung di frekuensi.
     *
     * Nama dirapikan menjadi huruf kecil saat pengelompokan supaya "Zein" dan
     * "zein" tidak menjadi dua orang, lalu ditampilkan dengan kapitalisasi
     * dari entri yang paling sering muncul.
     *
     * @param  array<string, mixed>  $filters
     * @return array{hours: array<int, array<string, mixed>>, count: array<int, array<string, mixed>>}
     */
    private function overtimeLeaders(array $filters, int $limit = 5): array
    {
        $rows = $this->applyReportFilters(
            DB::table('employee_logs')->join('daily_reports', 'daily_reports.id', '=', 'employee_logs.daily_report_id'),
            $filters
        )
            ->where('employee_logs.category', 'operasi')
            ->where('employee_logs.description', 'Lembur')
            ->whereNotNull('employee_logs.name')
            ->where('employee_logs.name', '!=', '')
            ->selectRaw('employee_logs.name as name')
            ->selectRaw('employee_logs.time_in as time_in')
            ->selectRaw('employee_logs.time_out as time_out')
            ->get();

        $people = [];

        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) $row->name));

            if ($key === '') {
                continue;
            }

            $people[$key] ??= ['name' => trim((string) $row->name), 'hours' => 0.0, 'count' => 0];
            $people[$key]['count']++;

            if ($row->time_in && $row->time_out) {
                $people[$key]['hours'] += $this->durationInHours((string) $row->time_in, (string) $row->time_out);
            }
        }

        $take = static function (array $people, string $field, int $limit): array {
            $people = array_values(array_filter($people, fn (array $person) => $person[$field] > 0));

            usort($people, fn (array $a, array $b) => $b[$field] <=> $a[$field]);

            $people = array_slice($people, 0, $limit);
            $peak = $people === [] ? 0.0 : (float) $people[0][$field];

            foreach ($people as $index => $person) {
                $people[$index]['share'] = $peak > 0 ? ($person[$field] / $peak) * 100 : 0.0;
            }

            return $people;
        };

        return [
            'hours' => $take($people, 'hours', $limit),
            'count' => $take($people, 'count', $limit),
        ];
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

    /**
     * Daftar kunjungan kapal beserta realisasi muat terhadap kapasitasnya.
     *
     * Untuk muat kantong, tonase termuat diambil dari akumulasi tertinggi
     * (current + prev) karena kolom prev sudah memuat total shift sebelumnya.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function shipList(array $filters): array
    {
        $bagged = $this->applyReportFilters(
            DB::table('loading_activities')->join('daily_reports', 'daily_reports.id', '=', 'loading_activities.daily_report_id'),
            $filters
        )
            ->whereNotNull('loading_activities.ship_name')
            ->where('loading_activities.ship_name', '!=', '')
            ->groupBy('loading_activities.ship_name', 'loading_activities.arrival_time')
            ->selectRaw('loading_activities.ship_name as ship_name')
            ->selectRaw('loading_activities.arrival_time as moment')
            ->selectRaw('MAX(loading_activities.agent) as agent')
            ->selectRaw('MAX(loading_activities.jetty) as jetty')
            ->selectRaw('MAX(loading_activities.capacity) as capacity')
            ->selectRaw('MAX(loading_activities.qty_loading_current + loading_activities.qty_loading_prev) as loaded')
            ->selectRaw('COUNT(DISTINCT loading_activities.daily_report_id) as report_count')
            ->get()
            ->map(fn ($row) => $this->shipRow($row, 'Muat Kantong'));

        $bulk = $this->applyReportFilters(
            DB::table('bulk_loading_activities')->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id'),
            $filters
        )
            ->leftJoin('bulk_loading_logs', 'bulk_loading_logs.bulk_loading_activity_id', '=', 'bulk_loading_activities.id')
            ->whereNotNull('bulk_loading_activities.ship_name')
            ->where('bulk_loading_activities.ship_name', '!=', '')
            ->groupBy('bulk_loading_activities.ship_name', 'bulk_loading_activities.berthing_time')
            ->selectRaw('bulk_loading_activities.ship_name as ship_name')
            ->selectRaw('bulk_loading_activities.berthing_time as moment')
            ->selectRaw('MAX(bulk_loading_activities.agent) as agent')
            ->selectRaw('MAX(bulk_loading_activities.jetty) as jetty')
            ->selectRaw('MAX(bulk_loading_activities.capacity) as capacity')
            ->selectRaw('COALESCE(SUM(bulk_loading_logs.cob), 0) as loaded')
            ->selectRaw('COUNT(DISTINCT bulk_loading_activities.daily_report_id) as report_count')
            ->get()
            ->map(fn ($row) => $this->shipRow($row, 'Muat Curah'));

        return $bagged->concat($bulk)
            ->sortByDesc('loaded')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function shipRow(object $row, string $type): array
    {
        $capacity = (float) ($row->capacity ?? 0);
        $loaded = (float) ($row->loaded ?? 0);

        return [
            'ship_name' => (string) $row->ship_name,
            'type' => $type,
            'agent' => $row->agent ?: '-',
            'jetty' => $row->jetty ?: '-',
            'capacity' => $capacity,
            'loaded' => $loaded,
            'realization' => $capacity > 0 ? min(($loaded / $capacity) * 100, 999.9) : null,
            // Sengaja disimpan sebagai teks siap tampil: hasil rangkuman ini
            // masuk cache, dan objek tanggal tidak aman melewati serialisasi.
            'moment' => $row->moment
                ? Carbon::parse($row->moment)->locale('id')->translatedFormat('d M · H:i')
                : null,
            'report_count' => (int) $row->report_count,
        ];
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
                'reports' => 0,
                'ships' => 0,
                'loading' => 0.0,
                'damage' => 0.0,
            ];
        }

        foreach ($this->tonnageKeys() as $key) {
            foreach ($monthly['activities'][$key] as $row) {
                if (isset($buckets[$row->bucket]) && $this->rowMatches($row, $filters)) {
                    $buckets[$row->bucket]['tonnage'] += (float) $row->total;
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
     * Tonase bulanan yang dipecah menjadi tiga shift, untuk grafik area
     * bertumpuk. Nama shift di data lapangan tidak seragam ("1", "Pagi",
     * "Shift 1"), jadi dirapikan dulu ke tiga kelompok tetap.
     *
     * @param  array<string, mixed>  $monthly
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function shiftTrend(array $monthly, array $filters): array
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

        foreach ($this->tonnageKeys() as $key) {
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
    private function sparklinesFor(array $trend): array
    {
        return [
            'tonnage' => $this->sparklinePoints(array_column($trend, 'tonnage')),
            'ships' => $this->sparklinePoints(array_column($trend, 'ships')),
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
                'identity' => $this->visitIdentity('loading_activities.ship_name', 'loading_activities.arrival_time'),
            ],
            [
                'table' => 'bulk_loading_activities',
                'identity' => $this->visitIdentity('bulk_loading_activities.ship_name', 'bulk_loading_activities.berthing_time'),
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

        return $this->applyRangeFilters($query, $range);
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

        $current = [$range['start']->toDateString(), $range['end']->toDateString()];

        if (empty($range['prevStart'])) {
            return $query->whereBetween('daily_reports.report_date', $current);
        }

        $previous = [$range['prevStart']->toDateString(), $range['prevEnd']->toDateString()];

        return $query->where(function (QueryBuilder $inner) use ($current, $previous): void {
            $inner->whereBetween('daily_reports.report_date', $current)
                ->orWhereBetween('daily_reports.report_date', $previous);
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
            ->whereBetween('daily_reports.report_date', [
                $filters['start']->toDateString(),
                $filters['end']->toDateString(),
            ]);

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
            ? "date(daily_reports.created_at) = date(daily_reports.report_date)"
            : 'DATE(daily_reports.created_at) = daily_reports.report_date';
    }

    /**
     * Penanda satu kunjungan kapal: nama kapal digabung dengan waktu tibanya.
     */
    private function visitIdentity(string $shipColumn, string $momentColumn): string
    {
        $ship = 'COALESCE('.$shipColumn.", '')";
        $moment = 'COALESCE('.$momentColumn.", '')";

        return $this->isSqlite()
            ? $ship." || '|' || ".$moment
            : 'CONCAT('.$ship.", '|', ".$moment.')';
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
     * Periode pembanding yang setara. Untuk rentang yang dimulai pada tanggal 1,
     * pembandingnya adalah rentang yang sama di bulan sebelumnya (1–25 Juli
     * dibanding 1–25 Juni) supaya bulan berjalan tidak terlihat anjlok. Untuk
     * rentang bebas, dipakai periode sepanjang durasi yang sama tepat sebelumnya.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function equivalentPreviousPeriod(CarbonInterface $start, CarbonInterface $end): array
    {
        if ((int) $start->day === 1) {
            return [
                $start->copy()->subMonthNoOverflow()->startOfMonth(),
                $end->copy()->subMonthNoOverflow(),
            ];
        }

        $length = $start->diffInDays($end);
        $prevEnd = $start->copy()->subDay();

        return [$prevEnd->copy()->subDays($length), $prevEnd];
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

        if ($start->isSameMonth($end)) {
            return $start->translatedFormat('j').'–'.$end->translatedFormat('j M Y');
        }

        return $start->translatedFormat('j M').' – '.$end->translatedFormat('j M Y');
    }
}
