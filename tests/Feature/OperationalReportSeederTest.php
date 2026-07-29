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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalReportSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_mengisi_juni_juli_secara_idempotent_tanpa_mengubah_master_karyawan(): void
    {
        Carbon::setTestNow('2026-07-28 12:00:00');

        try {
            $this->seed(MasterEmployeeSeeder::class);

            $employeesBefore = $this->employeeSnapshot();

            $this->seed(OperationalReportSeeder::class);

            $this->assertSame(174, DailyReport::count());
            $this->assertSame(174, LoadingActivity::count());
            $this->assertGreaterThan(0, BulkLoadingActivity::count());
            $this->assertGreaterThan(0, MaterialActivity::count());
            $this->assertGreaterThan(0, ContainerActivity::count());
            $this->assertGreaterThan(0, TurbaActivity::count());

            $this->assertSame('2026-06-01', Carbon::parse(DailyReport::min('report_date'))->toDateString());
            $this->assertSame('2026-07-28', Carbon::parse(DailyReport::max('report_date'))->toDateString());
            $this->assertSame(
                0,
                DailyReport::whereNotIn('status', ['submitted', 'acknowledged', 'approved'])->count()
            );

            $juneProductivity = $this->averageBaggedValue('2026-06-01', '2026-06-28', 'qty_loading_current');
            $julyProductivity = $this->averageBaggedValue('2026-07-01', '2026-07-28', 'qty_loading_current');
            $juneDamage = $this->averageBaggedValue('2026-06-01', '2026-06-28', 'qty_damage_current');
            $julyDamage = $this->averageBaggedValue('2026-07-01', '2026-07-28', 'qty_damage_current');

            $this->assertGreaterThan($juneProductivity, $julyProductivity);
            $this->assertLessThan($juneDamage, $julyDamage);
            $this->assertSame($employeesBefore, $this->employeeSnapshot());

            $masterNames = MasterEmployee::pluck('name');
            $workloadNames = EmployeeLog::where('category', 'operasi')->pluck('name');
            $this->assertTrue($workloadNames->diff($masterNames)->isEmpty());

            $performance = app(OperationalPerformanceService::class)->performanceReport([
                'start' => Carbon::parse('2026-06-01'),
                'end' => Carbon::parse('2026-07-28'),
                'group' => null,
                'shift' => null,
            ]);
            $activityPanels = collect($performance['activityPanels'])->keyBy('key');

            $this->assertSame(
                [
                    'bongkar_bahan_baku',
                    'bongkar_container',
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

            $this->seed(OperationalReportSeeder::class);

            $this->assertSame(174, DailyReport::count());
            $this->assertSame(174, LoadingActivity::count());
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
            ->whereBetween('daily_reports.report_date', [$start, $end])
            ->avg('loading_activities.'.$column);
    }
}
