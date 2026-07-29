<?php

namespace Tests\Feature;

use App\Models\BulkLoadingActivity;
use App\Models\ContainerActivity;
use App\Models\DailyReport;
use App\Models\EmployeeLog;
use App\Models\LoadingActivity;
use App\Models\MasterEmployee;
use App\Models\MaterialActivity;
use App\Models\TurbaActivity;
use App\Services\OperationalPerformanceService;
use Carbon\Carbon;
use Database\Seeders\MasterEmployeeSeeder;
use Database\Seeders\OperationalReportSeeder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalReportSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_mengisi_mei_sampai_bulan_berjalan_secara_idempotent_dan_statistiknya_riil(): void
    {
        Carbon::setTestNow('2026-07-29 12:00:00');

        try {
            $this->seed(MasterEmployeeSeeder::class);

            $employeesBefore = $this->employeeSnapshot();

            $this->seed(OperationalReportSeeder::class);

            $this->assertSame(270, DailyReport::count());
            $this->assertSame(270, LoadingActivity::count());
            $this->assertSame(180, BulkLoadingActivity::count());
            $this->assertSame(97, MaterialActivity::count());
            $this->assertSame(68, ContainerActivity::count());
            $this->assertSame(135, TurbaActivity::count());

            $this->assertSame('2026-05-01', Carbon::parse(DailyReport::min('report_date'))->toDateString());
            $this->assertSame('2026-07-29', Carbon::parse(DailyReport::max('report_date'))->toDateString());
            $this->assertSame(
                0,
                DailyReport::whereNotIn('status', ['submitted', 'acknowledged', 'approved'])->count()
            );

            $juneProductivity = $this->averageBaggedValue('2026-06-01', '2026-06-30', 'qty_loading_current');
            $julyProductivity = $this->averageBaggedValue('2026-07-01', '2026-07-29', 'qty_loading_current');
            $juneDamage = $this->averageBaggedValue('2026-06-01', '2026-06-30', 'qty_damage_current');
            $julyDamage = $this->averageBaggedValue('2026-07-01', '2026-07-29', 'qty_damage_current');

            $this->assertGreaterThan($juneProductivity, $julyProductivity);
            $this->assertLessThan($juneDamage, $julyDamage);
            $this->assertSame($employeesBefore, $this->employeeSnapshot());

            $masterNames = MasterEmployee::pluck('name');
            $workloadNames = EmployeeLog::where('category', 'operasi')->pluck('name');
            $this->assertTrue($workloadNames->diff($masterNames)->isEmpty());

            $filters = [
                'start' => Carbon::parse('2026-05-01'),
                'end' => Carbon::parse('2026-07-29'),
                'group' => null,
                'shift' => null,
            ];
            $service = app(OperationalPerformanceService::class);
            $performance = $service->performanceReport($filters);
            $activityPanels = collect($performance['activityPanels'])->keyBy('key');

            $this->assertSame(
                [
                    'bongkar_bahan_baku',
                    'bongkar_container',
                    'muat_amoniak',
                    'muat_container',
                    'muat_curah',
                    'muat_kantong',
                    'trucking_turba',
                ],
                $activityPanels->keys()->sort()->values()->all()
            );
            $this->assertTrue($activityPanels->every(
                fn (array $panel): bool => (float) $panel['value'] > 0
            ));
            $this->assertGreaterThan(0, (float) $performance['summary']['tonnage']['value']);

            $this->assertPerformanceMatchesRawReports($service, $performance, $filters);
            $this->assertMonthlyTrendMatchesRawReports($performance, $filters);

            // Filter pada halaman Kinerja Operasi dan Rincian Kegiatan harus
            // memotong laporan sumber yang sama, bukan sekadar grafiknya.
            $scopedFilters = array_merge($filters, ['group' => 'B', 'shift' => 'Sore']);
            $scopedPerformance = $service->performanceReport($scopedFilters);
            $this->assertPerformanceMatchesRawReports($service, $scopedPerformance, $scopedFilters);
            $this->assertMonthlyTrendMatchesRawReports($scopedPerformance, $scopedFilters);

            $this->seed(OperationalReportSeeder::class);

            $this->assertSame(270, DailyReport::count());
            $this->assertSame(270, LoadingActivity::count());
            $this->assertSame($employeesBefore, $this->employeeSnapshot());
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function employeeSnapshot(): array
    {
        return MasterEmployee::query()
            ->orderBy('id')
            ->get()
            ->map(fn (MasterEmployee $employee): array => $employee->getAttributes())
            ->all();
    }

    private function averageBaggedValue(string $start, string $end, string $column): float
    {
        return (float) DB::table('loading_activities')
            ->join('daily_reports', 'daily_reports.id', '=', 'loading_activities.daily_report_id')
            ->where('daily_reports.report_date', '>=', $start)
            ->where('daily_reports.report_date', '<', Carbon::parse($end)->addDay()->toDateString())
            ->avg('loading_activities.'.$column);
    }

    /**
     * Bandingkan data yang menjadi sumber performa.blade.php dan
     * kegiatan.blade.php dengan agregasi independen langsung dari tabel
     * laporan. Definisi sumber sengaja tidak mengambil activityCatalog()
     * supaya kesalahan pemetaan di service tetap dapat terdeteksi.
     *
     * @param  array<string, mixed>  $performance
     * @param  array<string, mixed>  $filters
     */
    private function assertPerformanceMatchesRawReports(
        OperationalPerformanceService $service,
        array $performance,
        array $filters
    ): void {
        $rawTotals = $this->rawActivityTotals($filters);
        $tonnageKeys = [
            'muat_kantong',
            'muat_curah',
            'muat_amoniak',
            'bongkar_bahan_baku',
            'trucking_turba',
        ];
        $expectedTonnage = array_sum(array_intersect_key($rawTotals, array_flip($tonnageKeys)));

        $this->assertEqualsWithDelta(
            $expectedTonnage,
            (float) $performance['summary']['tonnage']['value'],
            0.001,
            'Total tonase Kinerja Operasi berbeda dari laporan sumber.'
        );
        $this->assertSame(
            $this->rawReportCount($filters),
            (int) $performance['reportCount'],
            'Jumlah laporan pada Kinerja Operasi berbeda dari tabel daily_reports.'
        );

        $cards = collect($performance['activityCards'])->keyBy('key');
        $panels = collect($performance['activityPanels'])->keyBy('key');
        $composition = collect($performance['activities'])->keyBy('key');
        $recap = collect($performance['activityRecap']['rows'])->keyBy('key');
        $monthFilters = array_merge($filters, [
            'start' => $filters['end']->copy()->startOfMonth(),
        ]);
        $previousFilters = array_merge($filters, [
            'end' => $filters['end']->copy()->startOfMonth()->subDay(),
        ]);
        $rawMonth = $this->rawActivityTotals($monthFilters);
        $rawPrevious = $this->rawActivityTotals($previousFilters);

        foreach ($rawTotals as $key => $expected) {
            $this->assertEqualsWithDelta(
                $expected,
                (float) $cards->get($key)['value'],
                0.001,
                "Kartu kegiatan {$key} berbeda dari laporan sumber."
            );
            $this->assertEqualsWithDelta(
                $expected,
                (float) $panels->get($key)['value'],
                0.001,
                "Panel kegiatan {$key} berbeda dari laporan sumber."
            );
            $this->assertEqualsWithDelta(
                $rawMonth[$key],
                (float) $recap->get($key)['month']['value'],
                0.001,
                "Rekap bulan berjalan {$key} berbeda dari laporan sumber."
            );
            $this->assertEqualsWithDelta(
                $rawPrevious[$key],
                (float) $recap->get($key)['previous']['value'],
                0.001,
                "Rekap periode sebelumnya {$key} berbeda dari laporan sumber."
            );
            $this->assertEqualsWithDelta(
                $expected,
                (float) $recap->get($key)['total']['value'],
                0.001,
                "Akumulasi rekap {$key} berbeda dari laporan sumber."
            );

            $detail = $service->activityDetail($key, $filters);

            $this->assertEqualsWithDelta(
                $expected,
                (float) $detail['value'],
                0.001,
                "Angka utama Rincian Kegiatan {$key} berbeda dari laporan sumber."
            );
            $this->assertSame(
                $this->rawActivityReportCount($key, $filters),
                (int) $detail['workload']['reports'],
                "Jumlah laporan pada indikator {$key} berbeda dari laporan sumber."
            );
            $this->assertEqualsWithDelta(
                $expected,
                (float) $detail['recap']['row']['total']['value'],
                0.001,
                "Akumulasi panel Rincian Kegiatan {$key} berbeda dari laporan sumber."
            );

            if (in_array($key, $tonnageKeys, true)) {
                $this->assertEqualsWithDelta(
                    $expected,
                    (float) $composition->get($key)['tonnage'],
                    0.001,
                    "Komposisi kegiatan {$key} berbeda dari laporan sumber."
                );
            } else {
                $this->assertFalse(
                    $composition->has($key),
                    "Kegiatan {$key} bersatuan Teus tidak boleh masuk komposisi tonase."
                );
            }
        }

        $this->assertAmmoniaIndicatorsMatchRawReports(
            $service->activityDetail('muat_amoniak', $filters),
            $filters
        );

        // Verifikasi terakhir di lapisan presentasi: angka yang sudah terbukti
        // benar memang diteruskan ke partial yang dirender oleh kedua halaman.
        $compositionHtml = view('manajer.charts.donut-activity', [
            'activities' => $performance['activities'],
        ])->render();
        $ammoniaDetail = $service->activityDetail('muat_amoniak', $filters);
        $ammoniaPanelHtml = view('manajer.partials.activity-detail', [
            'detail' => $ammoniaDetail,
        ])->render();

        $this->assertStringContainsString('Muat Amoniak', $compositionHtml);
        $this->assertStringContainsString(
            number_format($rawTotals['muat_amoniak'], 2, ',', '.').'<span>Ton</span>',
            $ammoniaPanelHtml
        );
    }

    /**
     * Grafik enam bulan tidak boleh memakai sumber atau batas tanggal yang
     * berbeda dari KPI dan rekap pada bulan yang sama.
     *
     * @param  array<string, mixed>  $performance
     * @param  array<string, mixed>  $filters
     */
    private function assertMonthlyTrendMatchesRawReports(array $performance, array $filters): void
    {
        $trend = collect($performance['trend'])->keyBy('key');

        foreach (['2026-05', '2026-06', '2026-07'] as $month) {
            $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
            $end = $start->copy()->endOfMonth()->min($filters['end']);
            $monthFilters = array_merge($filters, ['start' => $start, 'end' => $end]);
            $raw = $this->rawActivityTotals($monthFilters);
            $row = $trend->get($month);

            $this->assertNotNull($row, "Bucket tren {$month} tidak ditemukan.");
            $this->assertEqualsWithDelta(
                $raw['muat_kantong']
                    + $raw['muat_curah']
                    + $raw['muat_amoniak']
                    + $raw['bongkar_bahan_baku']
                    + $raw['trucking_turba'],
                (float) $row['tonnage'],
                0.001,
                "Tonase tren {$month} berbeda dari laporan sumber."
            );
            $this->assertEqualsWithDelta(
                $raw['bongkar_container'] + $raw['muat_container'],
                (float) $row['teus'],
                0.001,
                "Teus tren {$month} berbeda dari laporan sumber."
            );
        }
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $filters
     */
    private function assertAmmoniaIndicatorsMatchRawReports(array $detail, array $filters): void
    {
        [$query] = $this->rawActivityQuery('muat_amoniak');
        $query = $this->applyRawReportFilters($query, $filters);
        $logCount = (clone $query)->count('bulk_loading_logs.id');
        $shipCount = (clone $query)
            ->select('bulk_loading_activities.ship_name', 'bulk_loading_activities.berthing_time')
            ->distinct()
            ->get()
            ->count();
        $metrics = collect($detail['metrics'])->keyBy('label');

        $this->assertSame(
            $shipCount,
            (int) $metrics->get('Kapal dilayani')['value'],
            'Indikator kapal Amoniak berbeda dari laporan sumber.'
        );
        $this->assertSame(
            $logCount,
            (int) $metrics->get('Entri log jam')['value'],
            'Indikator jumlah log Amoniak berbeda dari laporan sumber.'
        );
        $this->assertEqualsWithDelta(
            $logCount > 0 ? (float) $detail['value'] / $logCount : 0.0,
            (float) $metrics->get('Rata-rata COB per entri')['value'],
            0.001,
            'Indikator rata-rata COB Amoniak berbeda dari laporan sumber.'
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    private function rawActivityTotals(array $filters): array
    {
        $totals = [];

        foreach ([
            'muat_kantong',
            'muat_curah',
            'muat_amoniak',
            'bongkar_bahan_baku',
            'bongkar_container',
            'muat_container',
            'trucking_turba',
        ] as $key) {
            [$query, $column] = $this->rawActivityQuery($key);
            $totals[$key] = (float) $this->applyRawReportFilters($query, $filters)->sum($column);
        }

        return $totals;
    }

    /**
     * Definisi tabel mentah untuk satu kegiatan.
     *
     * @return array{0: Builder, 1: string, 2: string}
     */
    private function rawActivityQuery(string $key): array
    {
        return match ($key) {
            'muat_kantong' => [
                DB::table('loading_activities')
                    ->join('daily_reports', 'daily_reports.id', '=', 'loading_activities.daily_report_id'),
                'loading_activities.qty_loading_current',
                'loading_activities.daily_report_id',
            ],
            'muat_curah' => [
                DB::table('bulk_loading_logs')
                    ->join('bulk_loading_activities', 'bulk_loading_activities.id', '=', 'bulk_loading_logs.bulk_loading_activity_id')
                    ->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id')
                    ->where('bulk_loading_activities.activity_type', BulkLoadingActivity::TYPE_BULK_LOADING),
                'bulk_loading_logs.cob',
                'bulk_loading_activities.daily_report_id',
            ],
            'muat_amoniak' => [
                DB::table('bulk_loading_logs')
                    ->join('bulk_loading_activities', 'bulk_loading_activities.id', '=', 'bulk_loading_logs.bulk_loading_activity_id')
                    ->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id')
                    ->where('bulk_loading_activities.activity_type', BulkLoadingActivity::TYPE_AMMONIA_LOADING),
                'bulk_loading_logs.cob',
                'bulk_loading_activities.daily_report_id',
            ],
            'bongkar_bahan_baku' => [
                DB::table('material_items')
                    ->join('material_activities', 'material_activities.id', '=', 'material_items.material_activity_id')
                    ->join('daily_reports', 'daily_reports.id', '=', 'material_activities.daily_report_id'),
                'material_items.qty_current',
                'material_activities.daily_report_id',
            ],
            'bongkar_container' => [
                DB::table('container_items')
                    ->join('container_activities', 'container_activities.id', '=', 'container_items.container_activity_id')
                    ->join('daily_reports', 'daily_reports.id', '=', 'container_activities.daily_report_id')
                    ->where('container_items.status', 'Empty'),
                'container_items.qty_current',
                'container_activities.daily_report_id',
            ],
            'muat_container' => [
                DB::table('container_items')
                    ->join('container_activities', 'container_activities.id', '=', 'container_items.container_activity_id')
                    ->join('daily_reports', 'daily_reports.id', '=', 'container_activities.daily_report_id')
                    ->where('container_items.status', 'Full'),
                'container_items.qty_current',
                'container_activities.daily_report_id',
            ],
            'trucking_turba' => [
                DB::table('turba_deliveries')
                    ->join('turba_activities', 'turba_activities.id', '=', 'turba_deliveries.turba_activity_id')
                    ->join('daily_reports', 'daily_reports.id', '=', 'turba_activities.daily_report_id'),
                'turba_deliveries.qty_current',
                'turba_activities.daily_report_id',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyRawReportFilters(Builder $query, array $filters): Builder
    {
        $query->whereIn('daily_reports.status', ['submitted', 'acknowledged', 'approved'])
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
     * @param  array<string, mixed>  $filters
     */
    private function rawReportCount(array $filters): int
    {
        return (int) $this->applyRawReportFilters(DB::table('daily_reports'), $filters)
            ->count('daily_reports.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function rawActivityReportCount(string $key, array $filters): int
    {
        [$query, , $reportKey] = $this->rawActivityQuery($key);

        return $this->applyRawReportFilters($query, $filters)
            ->distinct()
            ->count($reportKey);
    }
}
