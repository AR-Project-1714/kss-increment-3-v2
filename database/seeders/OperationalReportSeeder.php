<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\MasterEmployee;
use App\Models\MasterEnvironmentItem;
use App\Models\MasterInventoryItem;
use App\Models\MasterUnit;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Concerns\GuardsSampleData;
use Illuminate\Database\Seeder;

/**
 * Contoh laporan operasional untuk 4 hari terakhir, rotasi 3 shift/hari
 * (Pagi/Sore/Malam) secara berkesinambungan A -> B -> C -> D -> A -> ...
 * sehingga 4 hari x 3 shift = 12 laporan dan tiap regu mendapat tepat 3 shift.
 *
 * Karyawan (shift & OP.7) diambil dari roster ASLI MasterEmployeeSeeder, bukan
 * nama karangan — seeder lama (DailyReportSeeder/PerformanceDemoSeeder) memakai
 * nama dummy yang mencemari fitur memori susunan karyawan OP.7, lihat
 * ReportOpsController::lastOp7Rosters().
 *
 * Idempotent: updateOrCreate berdasarkan report_date + shift + group_name.
 */
class OperationalReportSeeder extends Seeder
{
    use GuardsSampleData;

    private const SOURCE = 'OperationalReportSeeder';

    private const GROUPS = ['A', 'B', 'C', 'D'];

    private const SHIFTS = [
        'Pagi' => '07:00 - 15:00',
        'Sore' => '15:00 - 23:00',
        'Malam' => '23:00 - 07:00',
    ];

    /** [no_forklift, area] per posisi baris OP.7 — baris 1 stasiun tetap Operator P.6. */
    private const OP7_POSITIONS = [
        ['FL.KSS-100', 'P.6'],
        ['FL.KSS-101', 'Popka'],
        ['FL.KSS-102', 'Bagging-1'],
        ['FL.KSS-104', 'Bagging-1'],
        ['FL.KSS-105', 'Bagging-2'],
        ['FL.KSS-106', 'Bagging-2'],
        ['FL.KSS-108', 'Gudang Produk Tursina'],
        ['FL.KSS-109', 'Blending'],
        ['FL.KSS-103', 'Blending'],
        ['FL.KSS-107', 'Blending'],
        ['FL.KSS-110', 'Blending'],
    ];

    private const BAGGED_SHIPS = [
        ['Bahtera Sukses', 'PT. NDSB', 'Tursina', 'Pontianak', 3700],
        ['Dhana Bahari 2', 'PT. NDSB', 'Tursina', 'Banjarmasin', 2500],
        ['Sinar Sulawesi', 'PT. Samator', 'Jetty 3', 'Makassar', 4200],
        ['Karya Bahari 7', 'PT. Pelayaran Nusantara', 'Tursina', 'Surabaya', 3100],
    ];

    private const BULK_SHIPS = [
        ['Maximus-I', 'Berkah Samudera Berjaya', 'Jetty 1', 'Luar Negeri', 15000],
        ['Oriental Diamond', 'Samudera Indonesia', 'Jetty 2', 'Vietnam', 8000],
        ['Pacific Talent', 'Meratus Line', 'Jetty 1', 'Filipina', 6500],
    ];

    public function run(): void
    {
        if ($this->shouldSkipSampleData()) {
            return;
        }

        $days = collect(range(3, 0))->map(fn ($offset) => Carbon::today()->subDays($offset));
        $shiftNames = array_keys(self::SHIFTS);

        $slot = 0;
        $created = 0;

        foreach ($days as $day) {
            foreach ($shiftNames as $shiftName) {
                $group = self::GROUPS[$slot % 4];
                $nextGroup = self::GROUPS[($slot + 1) % 4];

                $this->seedSlot($day, $shiftName, $group, $nextGroup, $slot);
                $created++;
                $slot++;
            }
        }

        $this->command?->info("{$created} laporan operasional dibuat untuk 4 hari terakhir (rotasi Regu A-D, 3 shift/hari).");
    }

    private function seedSlot(Carbon $day, string $shiftName, string $group, string $nextGroup, int $slot): void
    {
        $creator = User::where('username', 'karu.'.strtolower($group))->first();
        $receiver = User::where('username', 'karu.'.strtolower($nextGroup))->first();

        $report = DailyReport::updateOrCreate(
            [
                'report_date' => $day->toDateString(),
                'shift' => $shiftName,
                'group_name' => $group,
            ],
            [
                'user_id' => $creator?->id,
                'created_by' => $creator?->id,
                'received_by_group' => $nextGroup,
                'received_by_user_id' => $receiver?->id,
                'time_range' => self::SHIFTS[$shiftName],
                'status' => 'submitted',
                'payload' => ['source' => self::SOURCE],
            ]
        );

        $this->resetDetails($report);

        $this->seedBagged($report, $slot, $day);
        if ($slot % 2 === 0) {
            $this->seedBulk($report, $slot, $day);
        }
        if ($slot % 3 === 0) {
            $this->seedMaterial($report);
        }
        if ($slot % 4 === 1) {
            $this->seedContainer($report);
        }

        $this->seedUnitChecks($report);
        $this->seedEmployees($report, $group, self::SHIFTS[$shiftName]);
    }

    private function seedBagged(DailyReport $report, int $slot, Carbon $day): void
    {
        [$name, $agent, $jetty, $destination, $capacity] = self::BAGGED_SHIPS[$slot % count(self::BAGGED_SHIPS)];

        $prev = 800 + ($slot % 5) * 150;
        $current = 250 + ($slot % 4) * 40;

        $activity = $report->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => $name,
            'agent' => $agent,
            'jetty' => $jetty,
            'destination' => $destination,
            'capacity' => $capacity,
            'wo_number' => 'WO-'.str_pad((string) (100 + $slot), 4, '0', STR_PAD_LEFT),
            'cargo_type' => 'UK. Granul',
            'marking' => 'Nitrea',
            'arrival_time' => $day->copy()->setTime(6, 30),
            'operating_gang' => (string) (1 + $slot % 3),
            'tkbm_count' => 20 + $slot % 10,
            'foreman' => ['Nasir', 'Linta', 'Sahrul', 'Bahar'][$slot % 4],
            'qty_delivery_current' => $current + 30,
            'qty_delivery_prev' => $prev + 300,
            'qty_loading_current' => $current,
            'qty_loading_prev' => $prev,
            'qty_damage_current' => 0,
            'qty_damage_prev' => 0,
            'tally_warehouse' => ['Syamsuddin', 'Asmuni', 'Zein'][$slot % 3],
            'driver_name' => 'Arlis, Udin, Nurdian',
            'truck_number' => 'TRL-02, TRL-05',
            'tally_ship' => 'Jefry, Zein',
            'operator_ship' => 'Wirawan',
            'forklift_ship' => 'FL-71, FL-16',
            'operator_warehouse' => 'Gudang Op',
            'forklift_warehouse' => 'FL-17',
        ]);

        $activity->timesheets()->createMany([
            ['category' => 'delivery', 'time' => '07:30', 'activity' => 'Lanjut kirim'],
            ['category' => 'loading', 'time' => '09:00', 'activity' => 'Muat palka lanjut'],
        ]);
    }

    private function seedBulk(DailyReport $report, int $slot, Carbon $day): void
    {
        [$name, $agent, $jetty, $destination, $capacity] = self::BULK_SHIPS[$slot % count(self::BULK_SHIPS)];

        $activity = $report->bulkLoadingActivities()->create([
            'sequence' => 1,
            'ship_name' => $name,
            'agent' => $agent,
            'jetty' => $jetty,
            'destination' => $destination,
            'stevedoring' => 'PBM KSS',
            'commodity' => 'UC. Granul',
            'capacity' => $capacity,
            'berthing_time' => $day->copy()->subHours(6),
            'start_loading_time' => $day->copy()->setTime(8, 0),
        ]);

        $activity->logs()->createMany([
            ['datetime' => $day->copy()->setTime(9, 0), 'activity' => 'Mulai muat', 'cob' => 110 + $slot % 20],
            ['datetime' => $day->copy()->setTime(11, 30), 'activity' => 'Lanjut muat', 'cob' => 128 + $slot % 15],
        ]);
    }

    private function seedMaterial(DailyReport $report): void
    {
        $material = $report->materialActivity()->create([
            'ship_name' => 'MV. Bongkar Jaya',
            'agent' => 'Agen KSS',
            'capacity' => 5000,
            'ship_tally_names' => 'Budi',
            'forklift_operator_names' => 'Santoso',
            'delivery_tally_names' => 'Rudi',
            'driver_names' => 'Eko, Dwi',
            'working_hours' => '07:00 - 15:00',
        ]);

        $material->items()->createMany([
            ['raw_material_type' => 'Clay JB', 'qty_current' => 320, 'qty_prev' => 1280, 'qty_total' => 1600],
            ['raw_material_type' => 'Dolomite JB', 'qty_current' => 210, 'qty_prev' => 640, 'qty_total' => 850],
            ['raw_material_type' => 'Limestone', 'qty_current' => 400, 'qty_prev' => 1100, 'qty_total' => 1500],
        ]);
    }

    private function seedContainer(DailyReport $report): void
    {
        $container = $report->containerActivity()->create([
            'ship_name' => 'KM Tanto Sejahtera',
            'agent' => 'KDMP',
            'jetty' => 'Tursina',
            'capacity' => 100,
            'capacity_empty' => 100,
            'capacity_full' => 40,
            'ship_tally_names' => 'Asri Sahibu',
            'gudang_tally_names' => 'Mustafa',
            'driver_names' => 'Samsul Zainuddin, Arlis',
            'truck_number' => 'TRL-01, TRL-03',
        ]);

        $container->items()->createMany([
            ['time_text' => '07:00 - 12:00', 'qty_current' => 30, 'qty_prev' => 20, 'qty_total' => 50, 'status' => 'Empty'],
            ['time_text' => '12:00 - 15:00', 'qty_current' => 15, 'qty_prev' => 10, 'qty_total' => 25, 'status' => 'Full'],
        ]);
    }

    private function seedUnitChecks(DailyReport $report): void
    {
        foreach (MasterUnit::orderBy('id')->get() as $unit) {
            $report->unitCheckLogs()->create([
                'category' => 'vehicle',
                'item_name' => $unit->name,
                'master_id' => $unit->id,
                'fuel_level' => ((int) ($unit->id % 4) + 1).'/4',
                'condition_received' => 'Baik',
                'condition_handed_over' => 'Baik',
            ]);
        }

        foreach (MasterInventoryItem::orderBy('id')->get() as $inventory) {
            $report->unitCheckLogs()->create([
                'category' => 'inventory',
                'item_name' => $inventory->name,
                'master_id' => $inventory->id,
                'quantity' => $inventory->stock,
                'condition_received' => 'Baik',
                'condition_handed_over' => 'Baik',
            ]);
        }

        $shelterItems = MasterEnvironmentItem::where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->pluck('name');

        if ($shelterItems->isEmpty()) {
            $shelterItems = collect(['Ruangan Shelter', 'Halaman Shelter', 'Selokan/Parit', 'Jala-Jala Angkat', 'Jala-Jala Lambung', 'Terpal', 'Chain Sling']);
        }

        foreach ($shelterItems as $item) {
            $report->unitCheckLogs()->create([
                'category' => 'shelter',
                'item_name' => $item,
                'condition_received' => 'Baik',
                'condition_handed_over' => 'Baik',
            ]);
        }
    }

    /**
     * Karyawan shift & OP.7 dari roster ASLI regu yang bertugas (mengikuti
     * penugasan `shift_group_name` untuk personil Relief/Bengkel). Jam kerja
     * OP.7 disamakan dengan jam shift laporan ini, sesuai perilaku default
     * form (lihat currentWorkTimes() pada report-form.blade.php).
     */
    private function seedEmployees(DailyReport $report, string $group, string $timeRange): void
    {
        [$timeIn, $timeOut] = array_map('trim', explode('-', $timeRange));

        $shiftEmployees = MasterEmployee::forOperational()
            ->where('status', 'active')
            ->where(function ($query) use ($group): void {
                $query->where('group_name', 'Group '.$group)
                    ->orWhere('shift_group_name', 'Group '.$group);
            })
            ->orderBy('id')
            ->get();

        // Baris 1 & 2 dikunci KARU lalu Wakil KARU, meniru urutan yang
        // dihasilkan renderEmployeeShiftRows() di form (report-form.blade.php)
        // — supaya susunan yang "diingat" nanti konsisten dengan form asli.
        $isWakaru = fn (MasterEmployee $e) => str_contains(mb_strtolower((string) $e->position), 'wakil');
        $isKaru = fn (MasterEmployee $e) => ! $isWakaru($e) && preg_match('/karu|kepala regu/i', (string) $e->position);

        $karu = $shiftEmployees->first($isKaru);
        $wakaru = $shiftEmployees->first($isWakaru);
        $leaders = collect([$karu, $wakaru])->filter();
        $rest = $shiftEmployees->reject(fn (MasterEmployee $e) => $leaders->contains(fn ($l) => $l->id === $e->id));

        foreach ($leaders->concat($rest) as $employee) {
            $report->employeeLogs()->create([
                'category' => 'shift',
                'name' => $employee->name,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'description' => '-',
            ]);
        }

        $op7Employees = MasterEmployee::forOperational()
            ->where('status', 'active')
            ->where('group_name', 'OP.7 Group '.$group)
            ->orderBy('id')
            ->get();

        $op7Rows = [['name' => 'Operator P.6'], ...$op7Employees->map(fn (MasterEmployee $e) => ['name' => $e->name])->all()];

        foreach ($op7Rows as $index => $row) {
            [$forklift, $area] = self::OP7_POSITIONS[$index] ?? [null, null];

            $report->employeeLogs()->create([
                'category' => 'op7',
                'name' => $row['name'],
                'no_forklift_' => $forklift,
                'work_area' => $area,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'description' => '-',
            ]);
        }

        $report->employeeLogs()->create([
            'category' => 'lain',
            'name' => 'All Team',
            'time_in' => $timeIn,
            'description' => 'Pemberian Safety Briefing',
        ]);
    }

    private function resetDetails(DailyReport $report): void
    {
        $report->loadingActivities->each(function ($activity): void {
            $activity->timesheets()->delete();
            $activity->delete();
        });

        $report->bulkLoadingActivities->each(function ($activity): void {
            $activity->logs()->delete();
            $activity->delete();
        });

        $report->materialActivity->each(function ($activity): void {
            $activity->items()->delete();
            $activity->delete();
        });

        $report->containerActivity->each(function ($activity): void {
            $activity->items()->delete();
            $activity->delete();
        });

        if ($report->turbaActivity) {
            $report->turbaActivity->deliveries()->delete();
            $report->turbaActivity->delete();
        }

        $report->unitCheckLogs()->delete();
        $report->employeeLogs()->delete();

        $report->refresh();
    }
}
