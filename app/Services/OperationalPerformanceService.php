<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\DailyReport;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
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

    /**
     * Ringkasan untuk empat kartu KPI dashboard: bulan berjalan dibanding
     * periode setara bulan lalu, lengkap dengan seri enam bulan untuk sparkline.
     *
     * @return array<string, mixed>
     */
    public function dashboardKpi(): array
    {
        $start = Carbon::today()->startOfMonth();
        $end = Carbon::today();

        $summary = $this->summaryFor(['start' => $start, 'end' => $end]);
        [$prevStart, $prevEnd] = $this->equivalentPreviousPeriod($start, $end);
        $previous = $this->summaryFor(['start' => $prevStart, 'end' => $prevEnd]);

        $trend = $this->monthlyMetrics(6);
        $comparisonLabel = 'vs '.$this->periodLabel($prevStart, $prevEnd);

        return [
            'periodLabel' => $this->periodLabel($start, $end),
            'comparisonLabel' => $comparisonLabel,
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
                // Rasio 0% tanpa muatan kantong bukan capaian — kartu menampilkan
                // tanda strip alih-alih angka ketika dasar hitungnya kosong.
                'hasBase' => $summary['hasDamageBase'],
                'delta' => $this->deltaPoint(
                    $summary['damageRatio'],
                    $previous['damageRatio'],
                    $summary['hasDamageBase'] && $previous['hasDamageBase']
                ),
            ],
            'trend' => $trend,
            'sparklines' => [
                'tonnage' => $this->sparklinePoints(array_column($trend, 'tonnage')),
                'ships' => $this->sparklinePoints(array_column($trend, 'ships')),
                'tonnagePerShift' => $this->sparklinePoints(array_column($trend, 'tonnagePerShift')),
                'damageRatio' => $this->sparklinePoints(array_column($trend, 'damageRatio')),
            ],
        ];
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

        $summary = $this->summaryFor($filters);
        [$prevStart, $prevEnd] = $this->equivalentPreviousPeriod($start, $end);
        $previous = $this->summaryFor(array_merge($filters, ['start' => $prevStart, 'end' => $prevEnd]));

        $activityCurrent = $this->tonnageByActivity($filters);
        $activityPrevious = $this->tonnageByActivity(array_merge($filters, ['start' => $prevStart, 'end' => $prevEnd]));

        $trend = $this->monthlyMetrics(6, $filters);

        return [
            'periodLabel' => $this->periodLabel($start, $end),
            'comparisonLabel' => 'vs '.$this->periodLabel($prevStart, $prevEnd),
            'summary' => [
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
            ],
            'reportCount' => $summary['reports'],
            'trend' => $trend,
            'sparklines' => [
                'tonnage' => $this->sparklinePoints(array_column($trend, 'tonnage')),
                'ships' => $this->sparklinePoints(array_column($trend, 'ships')),
                'tonnagePerShift' => $this->sparklinePoints(array_column($trend, 'tonnagePerShift')),
                'damageRatio' => $this->sparklinePoints(array_column($trend, 'damageRatio')),
            ],
            'trendMax' => max(1.0, max(array_column($trend, 'tonnage') ?: [0.0])),
            'shiftTrend' => $this->shiftTrend(6, $filters),
            'groups' => $this->groupPerformance($filters, $prevStart, $prevEnd),
            'activities' => $this->activityBreakdown($activityCurrent, $activityPrevious),
            'shifts' => $this->shiftBreakdown($filters),
            'workload' => $this->workload($filters, $prevStart, $prevEnd),
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
    // Agregasi inti
    // ============================================================

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function summaryFor(array $filters): array
    {
        $tonnage = array_sum($this->tonnageByActivity($filters));
        $reports = $this->reportQuery($filters)->count();
        $damage = $this->damageTotals($filters);

        return [
            'tonnage' => $tonnage,
            'reports' => $reports,
            'ships' => $this->shipCount($filters),
            'tonnagePerShift' => $reports > 0 ? $tonnage / $reports : 0.0,
            'damageRatio' => $damage['loading'] > 0 ? ($damage['damage'] / $damage['loading']) * 100 : 0.0,
            // Penanda apakah rasio kerusakan punya dasar hitung. Periode tanpa
            // muatan menghasilkan rasio 0% yang bukan capaian, jadi tidak boleh
            // dipakai sebagai pembanding.
            'hasDamageBase' => $damage['loading'] > 0,
        ];
    }

    /**
     * Lima sumber tonase yang tersebar di jenis kegiatan berbeda. Tiap sumber
     * dijelaskan lewat tabel asal, kolom nilai, rantai join, dan kolom yang
     * menghubungkannya kembali ke daily_reports.
     *
     * @return array<string, array<string, mixed>>
     */
    private function tonnageSources(): array
    {
        return [
            'muat_kantong' => [
                'label' => 'Muat Kantong',
                'from' => 'loading_activities',
                'column' => 'loading_activities.qty_loading_current',
                'joins' => [],
                'reportKey' => 'loading_activities.daily_report_id',
            ],
            'muat_curah' => [
                'label' => 'Muat Curah',
                'from' => 'bulk_loading_logs',
                'column' => 'bulk_loading_logs.cob',
                'joins' => [
                    ['bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', 'bulk_loading_activities.id'],
                ],
                'reportKey' => 'bulk_loading_activities.daily_report_id',
            ],
            'bongkar_bahan_baku' => [
                'label' => 'Bongkar Bahan Baku',
                'from' => 'material_items',
                'column' => 'material_items.qty_current',
                'joins' => [
                    ['material_activities', 'material_items.material_activity_id', 'material_activities.id'],
                ],
                'reportKey' => 'material_activities.daily_report_id',
            ],
            'container' => [
                'label' => 'Bongkar/Muat Container',
                'from' => 'container_items',
                'column' => 'container_items.qty_current',
                'joins' => [
                    ['container_activities', 'container_items.container_activity_id', 'container_activities.id'],
                ],
                'reportKey' => 'container_activities.daily_report_id',
            ],
            'turba' => [
                'label' => 'Turba (Pupuk Kantong)',
                'from' => 'turba_deliveries',
                'column' => 'turba_deliveries.qty_current',
                'joins' => [
                    ['turba_activities', 'turba_deliveries.turba_activity_id', 'turba_activities.id'],
                ],
                'reportKey' => 'turba_activities.daily_report_id',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    private function tonnageByActivity(array $filters): array
    {
        $totals = [];

        foreach ($this->tonnageSources() as $key => $source) {
            $totals[$key] = (float) $this->sourceQuery($source, $filters)->sum($source['column']);
        }

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $filters
     */
    private function sourceQuery(array $source, array $filters): QueryBuilder
    {
        $query = DB::table($source['from']);

        foreach ($source['joins'] as [$table, $left, $right]) {
            $query->join($table, $left, '=', $right);
        }

        $query->join('daily_reports', 'daily_reports.id', '=', $source['reportKey']);

        return $this->applyReportFilters($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function reportQuery(array $filters): QueryBuilder
    {
        return $this->applyReportFilters(DB::table('daily_reports'), $filters);
    }

    /**
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
     * @param  array<string, mixed>  $filters
     * @return array{loading: float, damage: float}
     */
    private function damageTotals(array $filters): array
    {
        $row = $this->sourceQuery($this->tonnageSources()['muat_kantong'], $filters)
            ->selectRaw('COALESCE(SUM(loading_activities.qty_loading_current), 0) as loading')
            ->selectRaw('COALESCE(SUM(loading_activities.qty_damage_current), 0) as damage')
            ->first();

        return [
            'loading' => (float) ($row->loading ?? 0),
            'damage' => (float) ($row->damage ?? 0),
        ];
    }

    /**
     * Jumlah kunjungan kapal. Satu kapal bisa muncul di banyak laporan lintas
     * shift, jadi pembeda kunjungan adalah pasangan nama kapal dengan waktu
     * kedatangan (kantong) atau waktu sandar (curah). ship_operations tidak
     * dipakai karena relasinya belum terisi pada data yang ada.
     *
     * @param  array<string, mixed>  $filters
     */
    private function shipCount(array $filters): int
    {
        $total = 0;

        foreach ($this->shipVisitSources() as $visit) {
            $total += (int) $this->applyReportFilters(
                DB::table($visit['table'])->join('daily_reports', 'daily_reports.id', '=', $visit['table'].'.daily_report_id'),
                $filters
            )
                ->whereNotNull($visit['table'].'.ship_name')
                ->where($visit['table'].'.ship_name', '!=', '')
                ->distinct()
                ->count(DB::raw($visit['identity']));
        }

        return $total;
    }

    // ============================================================
    // Rincian untuk halaman performa
    // ============================================================

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function groupPerformance(array $filters, CarbonInterface $prevStart, CarbonInterface $prevEnd): array
    {
        $groups = DailyReport::query()
            ->whereIn('status', self::COUNTED_STATUSES)
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->distinct()
            ->orderBy('group_name')
            ->pluck('group_name')
            ->all();

        $rows = [];

        foreach ($groups as $group) {
            $scoped = array_merge($filters, ['group' => $group]);
            $current = $this->summaryFor($scoped);

            if ($current['reports'] === 0 && $current['tonnage'] <= 0.0) {
                continue;
            }

            $previous = $this->summaryFor(array_merge($scoped, ['start' => $prevStart, 'end' => $prevEnd]));

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
     * @param  array<string, float>  $current
     * @param  array<string, float>  $previous
     * @return array<int, array<string, mixed>>
     */
    private function activityBreakdown(array $current, array $previous): array
    {
        $sources = $this->tonnageSources();
        $total = array_sum($current);
        $max = $current === [] ? 0.0 : max($current);
        $rows = [];

        foreach ($current as $key => $tonnage) {
            $rows[] = [
                'label' => $sources[$key]['label'],
                'tonnage' => $tonnage,
                'contribution' => $total > 0 ? ($tonnage / $total) * 100 : 0.0,
                'share' => $max > 0 ? ($tonnage / $max) * 100 : 0.0,
                'delta' => $this->delta($tonnage, $previous[$key] ?? 0.0),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['tonnage'] <=> $a['tonnage']);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function shiftBreakdown(array $filters): array
    {
        $shifts = DailyReport::query()
            ->whereIn('status', self::COUNTED_STATUSES)
            ->whereNotNull('shift')
            ->where('shift', '!=', '')
            ->distinct()
            ->pluck('shift')
            ->all();

        $rows = [];

        foreach ($shifts as $shift) {
            $scoped = array_merge($filters, ['shift' => $shift]);
            $summary = $this->summaryFor($scoped);

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
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function workload(array $filters, CarbonInterface $prevStart, CarbonInterface $prevEnd): array
    {
        $current = $this->workloadTotals($filters);
        $previous = $this->workloadTotals(array_merge($filters, ['start' => $prevStart, 'end' => $prevEnd]));

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
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function workloadTotals(array $filters): array
    {
        $reports = $this->reportQuery($filters)->count();

        $employeeQuery = fn () => $this->applyReportFilters(
            DB::table('employee_logs')->join('daily_reports', 'daily_reports.id', '=', 'employee_logs.daily_report_id'),
            $filters
        );

        $personnel = (int) $employeeQuery()->where('employee_logs.category', 'shift')->count();

        // Sebagian entri lembur diisi lewat input cepat yang hanya menyimpan nama
        // tanpa jam, sehingga jumlah entri tetap dihitung terpisah agar beban
        // lembur tidak terlihat nol saat jamnya memang tidak pernah diisi.
        $overtimeCount = (int) $employeeQuery()
            ->where('employee_logs.category', 'operasi')
            ->where('employee_logs.description', 'Lembur')
            ->count();

        // Durasi disimpan sebagai kolom TIME, jadi shift yang melewati tengah
        // malam dikoreksi dengan menambah 24 jam saat jam pulang lebih kecil.
        $overtimeSeconds = (float) $employeeQuery()
            ->where('employee_logs.category', 'operasi')
            ->where('employee_logs.description', 'Lembur')
            ->whereNotNull('employee_logs.time_in')
            ->whereNotNull('employee_logs.time_out')
            ->sum(DB::raw($this->overtimeSecondsExpression()));

        $relief = (int) $employeeQuery()
            ->where(function (QueryBuilder $query): void {
                $query->where(function (QueryBuilder $inner): void {
                    $inner->where('employee_logs.category', 'operasi')
                        ->where('employee_logs.description', 'Relief');
                })->orWhere('employee_logs.category', 'replacement');
            })
            ->count();

        $onTime = (int) $this->reportQuery($filters)
            ->whereNotNull('daily_reports.report_date')
            ->whereRaw('DATE(daily_reports.created_at) = daily_reports.report_date')
            ->count();

        return [
            'reports' => $reports,
            'personnelPerShift' => $reports > 0 ? $personnel / $reports : 0.0,
            'overtimeHours' => $overtimeSeconds / 3600,
            'overtimeCount' => $overtimeCount,
            'reliefCount' => $relief,
            'punctuality' => $reports > 0 ? ($onTime / $reports) * 100 : 0.0,
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

        $rows = $bagged->concat($bulk)
            ->sortByDesc('loaded')
            ->values()
            ->all();

        return $rows;
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

    /**
     * Deret bulanan untuk grafik tren dan sparkline kartu KPI.
     *
     * Semua metrik dikumpulkan dengan query yang dikelompokkan per bulan, bukan
     * satu query per bulan, sehingga jumlah query tetap sama berapa pun panjang
     * rentang yang diminta.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function monthlyMetrics(int $months, array $filters = []): array
    {
        $end = Carbon::today()->endOfMonth();
        $start = Carbon::today()->startOfMonth()->subMonthsNoOverflow($months - 1);
        $bucketExpression = $this->monthBucket('daily_reports.report_date');

        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets[$cursor->format('Y-m')] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->locale('id')->translatedFormat('M'),
                'tonnage' => 0.0,
                'reports' => 0,
                'ships' => 0,
                'loading' => 0.0,
                'damage' => 0.0,
            ];
            $cursor->addMonthNoOverflow();
        }

        $scope = array_merge($filters, ['start' => $start, 'end' => $end]);

        $absorb = function ($rows, string $field) use (&$buckets): void {
            foreach ($rows as $row) {
                if (isset($buckets[$row->bucket])) {
                    $buckets[$row->bucket][$field] += $row->total;
                }
            }
        };

        foreach ($this->tonnageSources() as $source) {
            $absorb($this->sourceQuery($source, $scope)
                ->groupBy(DB::raw($bucketExpression))
                ->selectRaw($bucketExpression.' as bucket')
                ->selectRaw('COALESCE(SUM('.$source['column'].'), 0) as total')
                ->get(), 'tonnage');
        }

        $absorb($this->reportQuery($scope)
            ->groupBy(DB::raw($bucketExpression))
            ->selectRaw($bucketExpression.' as bucket')
            ->selectRaw('COUNT(*) as total')
            ->get(), 'reports');

        $damageRows = $this->sourceQuery($this->tonnageSources()['muat_kantong'], $scope)
            ->groupBy(DB::raw($bucketExpression))
            ->selectRaw($bucketExpression.' as bucket')
            ->selectRaw('COALESCE(SUM(loading_activities.qty_loading_current), 0) as loading')
            ->selectRaw('COALESCE(SUM(loading_activities.qty_damage_current), 0) as damage')
            ->get();

        foreach ($damageRows as $row) {
            if (isset($buckets[$row->bucket])) {
                $buckets[$row->bucket]['loading'] += (float) $row->loading;
                $buckets[$row->bucket]['damage'] += (float) $row->damage;
            }
        }

        foreach ($this->shipVisitSources() as $visit) {
            $absorb($this->applyReportFilters(
                DB::table($visit['table'])->join('daily_reports', 'daily_reports.id', '=', $visit['table'].'.daily_report_id'),
                $scope
            )
                ->whereNotNull($visit['table'].'.ship_name')
                ->where($visit['table'].'.ship_name', '!=', '')
                ->groupBy(DB::raw($bucketExpression))
                ->selectRaw($bucketExpression.' as bucket')
                ->selectRaw('COUNT(DISTINCT '.$visit['identity'].') as total')
                ->get(), 'ships');
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
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function shiftTrend(int $months, array $filters = []): array
    {
        $end = Carbon::today()->endOfMonth();
        $start = Carbon::today()->startOfMonth()->subMonthsNoOverflow($months - 1);
        $bucketExpression = $this->monthBucket('daily_reports.report_date');

        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets[$cursor->format('Y-m')] = [
                'label' => $cursor->locale('id')->translatedFormat('M'),
                'Pagi' => 0.0,
                'Sore' => 0.0,
                'Malam' => 0.0,
            ];
            $cursor->addMonthNoOverflow();
        }

        $scope = array_merge($filters, ['start' => $start, 'end' => $end]);

        foreach ($this->tonnageSources() as $source) {
            $rows = $this->sourceQuery($source, $scope)
                ->groupBy(DB::raw($bucketExpression), 'daily_reports.shift')
                ->selectRaw($bucketExpression.' as bucket')
                ->selectRaw('daily_reports.shift as shift')
                ->selectRaw('COALESCE(SUM('.$source['column'].'), 0) as total')
                ->get();

            foreach ($rows as $row) {
                if (! isset($buckets[$row->bucket])) {
                    continue;
                }

                $buckets[$row->bucket][$this->normalizeShift($row->shift)] += (float) $row->total;
            }
        }

        return array_values(array_map(static function (array $bucket): array {
            $bucket['total'] = $bucket['Pagi'] + $bucket['Sore'] + $bucket['Malam'];

            return $bucket;
        }, $buckets));
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
