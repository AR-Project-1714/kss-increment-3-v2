<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HistoryPaginationReportSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::where('username', 'karu.a')->first() ?? User::first();
        $receivers = User::whereIn('username', ['karu.a', 'karu.b', 'karu.c', 'karu.d'])
            ->get()
            ->keyBy(fn (User $user): string => strtoupper((string) $user->group));

        if (! $creator) {
            return;
        }

        $shifts = [
            ['name' => 'Pagi', 'range' => '07:00 - 15:00'],
            ['name' => 'Sore', 'range' => '15:00 - 23:00'],
            ['name' => 'Malam', 'range' => '23:00 - 07:00'],
        ];
        $receiverGroups = ['B', 'C', 'D', 'A'];

        for ($i = 1; $i <= 25; $i++) {
            $shift = $shifts[($i - 1) % count($shifts)];
            $receiverGroup = $receiverGroups[($i - 1) % count($receiverGroups)];
            $reportDate = Carbon::create(2026, 5, 19)->subDays($i);
            $sequence = str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            $report = DailyReport::updateOrCreate(
                [
                    'created_by' => $creator->id,
                    'report_date' => $reportDate->toDateString(),
                    'shift' => $shift['name'],
                    'group_name' => 'A',
                    'time_range' => $shift['range'],
                ],
                [
                    'user_id' => $creator->id,
                    'received_by_group' => $receiverGroup,
                    'received_by_user_id' => $receivers->get($receiverGroup)?->id,
                    'received_at' => $reportDate->copy()->setTime(16, 30),
                    'status' => $i % 4 === 0 ? 'approved' : 'acknowledged',
                    'payload' => [
                        'source' => 'HistoryPaginationReportSeeder',
                        'fields' => [
                            ['key' => 'ship_name_1', 'value' => "KM Paginasi {$sequence}"],
                            ['key' => 'agent_1', 'value' => "Agen Uji {$sequence}"],
                            ['key' => 'truck_name', 'value' => "Truck Seed {$sequence}"],
                            ['key' => 'employee_name', 'value' => "Karyawan Seed {$sequence}"],
                            ['key' => 'timesheet_activity', 'value' => "Aktivitas pagination {$sequence}"],
                        ],
                    ],
                    'created_at' => $reportDate->copy()->setTime(7, 0),
                    'updated_at' => $reportDate->copy()->setTime(16, 45),
                ]
            );

            $this->resetReportDetails($report);
            $this->createReportDetails($report, $sequence, $shift['range']);
        }
    }

    private function resetReportDetails(DailyReport $report): void
    {
        $report->loadingActivities()->with('timesheets')->get()->each(function ($activity): void {
            $activity->timesheets()->delete();
            $activity->delete();
        });

        $report->bulkLoadingActivities()->with('logs')->get()->each(function ($activity): void {
            $activity->logs()->delete();
            $activity->delete();
        });

        $materialActivity = $report->materialActivity()->with('items')->first();
        if ($materialActivity) {
            $materialActivity->items()->delete();
            $materialActivity->delete();
        }

        $containerActivity = $report->containerActivity()->with('items')->first();
        if ($containerActivity) {
            $containerActivity->items()->delete();
            $containerActivity->delete();
        }

        $turbaActivity = $report->turbaActivity()->with('deliveries')->first();
        if ($turbaActivity) {
            $turbaActivity->deliveries()->delete();
            $turbaActivity->delete();
        }

        $report->unitCheckLogs()->delete();
        $report->employeeLogs()->delete();
    }

    private function createReportDetails(DailyReport $report, string $sequence, string $timeRange): void
    {
        $loading = $report->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => "KM Paginasi {$sequence}",
            'agent' => "Agen Uji {$sequence}",
            'jetty' => 'Dermaga Seed',
            'destination' => 'Gudang Paginasi',
            'capacity' => 1200 + (int) $sequence,
            'wo_number' => "WO-PG-{$sequence}",
            'cargo_type' => 'Urea Kantong',
            'marking' => "Marking {$sequence}",
            'arrival_time' => Carbon::parse($report->report_date)->setTime(8, 15),
            'operating_gang' => '2',
            'tkbm_count' => 18,
            'foreman' => "Foreman Seed {$sequence}",
            'qty_delivery_current' => 100 + (int) $sequence,
            'qty_delivery_prev' => 50,
            'qty_loading_current' => 120 + (int) $sequence,
            'qty_loading_prev' => 75,
            'qty_damage_current' => 0,
            'qty_damage_prev' => 0,
            'tally_warehouse' => "Tally Gudang {$sequence}",
            'driver_name' => "Driver Seed {$sequence}",
            'truck_number' => "TRK-PG-{$sequence}",
            'tally_ship' => "Tally Kapal {$sequence}",
            'operator_ship' => "Operator Kapal {$sequence}",
            'forklift_ship' => "FL-{$sequence}",
            'operator_warehouse' => "Operator Gudang {$sequence}",
            'forklift_warehouse' => "FG-{$sequence}",
        ]);

        $loading->timesheets()->createMany([
            ['category' => 'delivery', 'time' => '09:00', 'activity' => "Aktivitas pagination {$sequence} pengiriman"],
            ['category' => 'loading', 'time' => '10:30', 'activity' => "Aktivitas pagination {$sequence} pemuatan"],
        ]);

        $bulk = $report->bulkLoadingActivities()->create([
            'sequence' => 1,
            'ship_name' => "MV Curah {$sequence}",
            'jetty' => 'Jetty Seed',
            'destination' => 'Tujuan Curah',
            'agent' => "Agen Curah {$sequence}",
            'stevedoring' => "PBM {$sequence}",
            'commodity' => 'Urea Curah',
            'capacity' => 2200 + (int) $sequence,
            'berthing_time' => Carbon::parse($report->report_date)->setTime(11, 0),
            'start_loading_time' => Carbon::parse($report->report_date)->setTime(13, 0),
        ]);

        $bulk->logs()->create([
            'datetime' => Carbon::parse($report->report_date)->setTime(14, 0),
            'activity' => "COB pagination {$sequence}",
            'cob' => 300 + (int) $sequence,
        ]);

        $turba = $report->turbaActivity()->create([
            'tally_gudang_names' => "Tally Turba {$sequence}",
            'forklift_operator_names' => "Operator Turba {$sequence}",
            'driver_names' => "Driver Turba {$sequence}",
            'working_hours' => $timeRange,
        ]);

        $turba->deliveries()->create([
            'truck_name' => "Truck Seed {$sequence}",
            'do_so_number' => "DO-PG-{$sequence}",
            'capacity' => 30,
            'marking_type' => "Marking Turba {$sequence}",
            'qty_current' => 20 + (int) $sequence,
            'qty_prev' => 10,
            'qty_accumulated' => 30 + (int) $sequence,
        ]);

        $report->unitCheckLogs()->create([
            'category' => 'vehicle',
            'item_name' => "Unit Seed {$sequence}",
            'fuel_level' => 'Full',
            'condition_received' => 'Baik',
            'condition_handed_over' => 'Baik',
        ]);

        $report->employeeLogs()->create([
            'category' => 'shift',
            'name' => "Karyawan Seed {$sequence}",
            'time_in' => substr($timeRange, 0, 5),
            'time_out' => substr($timeRange, -5),
            'work_time' => $timeRange,
            'description' => 'Masuk',
        ]);
    }
}
