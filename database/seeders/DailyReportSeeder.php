<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\MasterInventoryItem;
use App\Models\MasterUnit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DailyReportSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::where('username', 'karu.d')->first() ?? User::first();
        $receiver = User::where('username', 'karu.a')->first();

        $report = DailyReport::updateOrCreate(
            [
                'report_date' => Carbon::today()->toDateString(),
                'shift' => 'Malam',
                'group_name' => 'D',
            ],
            [
                'user_id' => $creator?->id,
                'created_by' => $creator?->id,
                'received_by_group' => 'A',
                'received_by_user_id' => $receiver?->id,
                'time_range' => '23:00 - 07:00',
                'status' => 'submitted',
                'payload' => [
                    'source' => 'DailyReportSeeder',
                    'note' => 'Sample laporan operasional dari referensi project lama.',
                ],
            ]
        );

        $this->resetReportDetails($report);

        $loading1 = $report->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'Bahtera Sukses',
            'agent' => 'PT.NDSB',
            'jetty' => 'Kumai',
            'destination' => 'Tursina',
            'capacity' => 3700,
            'wo_number' => '-',
            'cargo_type' => 'UK.Granul',
            'marking' => 'Nitrea',
            'arrival_time' => Carbon::now()->subHours(5),
            'operating_gang' => '2',
            'tkbm_count' => 26,
            'foreman' => 'Nasir',
            'qty_delivery_current' => 309.00,
            'qty_delivery_prev' => 3020.00,
            'qty_loading_current' => 377.50,
            'qty_loading_prev' => 2643.85,
            'qty_damage_current' => 0,
            'qty_damage_prev' => 0,
            'tally_warehouse' => 'Syamsuddin',
            'driver_name' => 'Arlis, udin, nurdian',
            'truck_number' => '02, 05, 06',
            'tally_ship' => 'Jefry, Zein',
            'operator_ship' => 'Wirawan',
            'forklift_ship' => '71, 16',
            'operator_warehouse' => 'Gudang Op',
            'forklift_warehouse' => '17',
        ]);

        $loading1->timesheets()->createMany([
            ['category' => 'delivery', 'time' => '23:00', 'activity' => 'Lanjut kirim'],
            ['category' => 'delivery', 'time' => '04:00', 'activity' => 'Stop kirim'],
            ['category' => 'loading', 'time' => '00:00', 'activity' => 'Stop muat sampai jam 00:00'],
            ['category' => 'loading', 'time' => '01:00', 'activity' => 'Kapal tidak muat'],
        ]);

        $loading2 = $report->loadingActivities()->create([
            'sequence' => 2,
            'ship_name' => 'DHANA BAHARI 2',
            'agent' => 'NDSB',
            'jetty' => 'TURSINA',
            'destination' => 'PONTIANAK',
            'capacity' => 2500,
            'wo_number' => '-',
            'cargo_type' => 'UK.GRANUL',
            'marking' => 'NITREA',
            'arrival_time' => Carbon::now()->subHours(8),
            'operating_gang' => '2',
            'tkbm_count' => 23,
            'foreman' => 'Linta',
            'qty_delivery_current' => 224.00,
            'qty_delivery_prev' => 1832.00,
            'qty_loading_current' => 403.65,
            'qty_loading_prev' => 1425.55,
            'qty_damage_current' => 0,
            'qty_damage_prev' => 0,
            'tally_warehouse' => 'Asmuni',
            'driver_name' => 'Doni, Rahim, Azis',
            'truck_number' => '07, 08, 09',
            'tally_ship' => 'Ardy',
            'operator_ship' => 'Musliadi, rifky',
            'forklift_ship' => '71, 16',
            'operator_warehouse' => 'Zein, syamrisal',
            'forklift_warehouse' => '03, 13',
        ]);

        $loading2->timesheets()->createMany([
            ['category' => 'delivery', 'time' => '23:00', 'activity' => 'Lanjut kirim'],
            ['category' => 'delivery', 'time' => '04:00', 'activity' => 'Stop kirim'],
            ['category' => 'loading', 'time' => '00:00', 'activity' => 'Stop muat sampai jam 00:00'],
            ['category' => 'loading', 'time' => '01:00', 'activity' => 'Kapal tidak muat'],
        ]);

        $bulk = $report->bulkLoadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'MAXIMUS-I (sadam)',
            'agent' => 'BERKAH SAMUDERA BERJAYA',
            'jetty' => 'Jetty 1',
            'destination' => 'Luar Negeri',
            'stevedoring' => 'PBM KSS',
            'commodity' => 'UC.GRANUL',
            'capacity' => 15000,
            'berthing_time' => Carbon::now()->subDay(),
            'start_loading_time' => Carbon::now()->subHours(10),
        ]);

        $bulk->logs()->createMany([
            ['datetime' => Carbon::now()->setTime(0, 5), 'activity' => 'Stop muat #1', 'cob' => 123],
            ['datetime' => Carbon::now()->setTime(0, 45), 'activity' => 'Lanjut muat #3', 'cob' => 125],
        ]);

        $material = $report->materialActivity()->create([
            'ship_name' => 'MV. BONGKAR JAYA',
            'agent' => 'AGEN KSS',
            'capacity' => 5000,
            'ship_tally_names' => 'Budi',
            'forklift_operator_names' => 'Santoso',
            'delivery_tally_names' => 'Rudi',
            'driver_names' => 'Eko, Dwi',
            'working_hours' => '23:00 - 07:00',
        ]);

        $material->items()->createMany([
            ['raw_material_type' => 'Clay JB', 'qty_current' => 0, 'qty_prev' => 0, 'qty_total' => 0],
            ['raw_material_type' => 'Dolomite JB', 'qty_current' => 0, 'qty_prev' => 0, 'qty_total' => 0],
            ['raw_material_type' => 'MGO 18% 50kg', 'qty_current' => 0, 'qty_prev' => 0, 'qty_total' => 0],
            ['raw_material_type' => 'Limestone', 'qty_current' => 0, 'qty_prev' => 0, 'qty_total' => 0],
        ]);

        $container = $report->containerActivity()->create([
            'ship_name' => 'MV. BONGKAR JAYA',
            'agent' => 'AGEN KSS',
            'capacity' => 5000,
            'ship_tally_names' => 'Tally Muat Container',
            'gudang_tally_names' => 'Tally Gudang Container',
            'driver_names' => 'Eko, Dwi',
        ]);

        $container->items()->create([
            'time' => '01:00',
            'status' => 'Full',
            'qty_current' => 1,
            'qty_prev' => 0,
            'qty_total' => 1,
        ]);

        $turba = $report->turbaActivity()->create([
            'tally_gudang_names' => 'Asmuni',
            'forklift_operator_names' => 'Syamsudin',
            'driver_names' => 'Dony, rahim',
            'working_hours' => '23:00 - 03:00',
        ]);

        $turba->deliveries()->createMany([
            [
                'truck_name' => 'BUFFER Stok',
                'do_so_number' => '5940',
                'capacity' => 943.80,
                'marking_type' => 'Granul Khusus',
                'qty_current' => 63.80,
                'qty_prev' => 880.00,
                'qty_accumulated' => 943.80,
            ],
            [
                'truck_name' => 'BUFFER Stok',
                'do_so_number' => '-',
                'capacity' => 0,
                'marking_type' => '-',
                'qty_current' => 0,
                'qty_prev' => 0,
                'qty_accumulated' => 0,
            ],
        ]);

        foreach (MasterUnit::orderBy('id')->get() as $unit) {
            $report->unitCheckLogs()->create([
                'category' => 'vehicle',
                'item_name' => $unit->name,
                'master_id' => $unit->id,
                'fuel_level' => ((int) ($unit->id % 4) + 1).'/4',
                'condition_received' => $unit->id % 7 === 0 ? 'Rusak' : 'Baik',
                'condition_handed_over' => $unit->id % 9 === 0 ? 'Rusak' : 'Baik',
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

        foreach (['Ruangan Shelter', 'Halaman Shelter', 'Selokan/Parit', 'Jala-Jala Angkat', 'Jala-Jala Lambung', 'Terpal', 'Chain Sling'] as $item) {
            $report->unitCheckLogs()->create([
                'category' => 'shelter',
                'item_name' => $item,
                'condition_received' => 'Baik',
                'condition_handed_over' => 'Baik',
            ]);
        }

        foreach (['Sugianto', 'Jhon Mailoor', 'Yacob', 'Sadam hasanuddin', 'Wirawan', 'Jefry', 'Syamrisal', 'Irfan', 'Supriadi', 'Fadli', 'Reza', 'Abd.Azis', 'Rahim', 'Doni amping'] as $name) {
            $report->employeeLogs()->create([
                'category' => 'shift',
                'name' => $name,
                'time_in' => '23:00',
                'time_out' => '07:00',
                'description' => '-',
            ]);
        }

        $report->employeeLogs()->createMany([
            ['category' => 'operasi', 'name' => 'syamsudin udin', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'zein', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'rifky', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'musliady', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'asmuni', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'ronal', 'description' => 'Relief Malam'],
            ['category' => 'operasi', 'name' => 'ardy', 'description' => 'Relief Malam'],
            ['category' => 'operasi', 'name' => 'ardian', 'description' => 'Relief Malam'],
            ['category' => 'operasi', 'name' => 'edo', 'description' => 'Relief Malam'],
            ['category' => 'lain', 'name' => 'All Team', 'time_in' => '22:45', 'description' => 'Pemberian Safety Briefing'],
        ]);

        $draftOwner = User::where('username', 'petugas')->first()
            ?? User::where('username', 'karu.a')->first()
            ?? $creator;

        $draft = DailyReport::updateOrCreate(
            [
                'report_date' => Carbon::today()->toDateString(),
                'shift' => 'Pagi',
                'group_name' => 'A',
                'created_by' => $draftOwner?->id,
                'status' => 'draft',
            ],
            [
                'user_id' => $draftOwner?->id,
                'received_by_group' => 'B',
                'time_range' => '07.00 - 14.00',
                'payload' => [
                    'source' => 'DailyReportSeeder',
                    'note' => 'Contoh draft agar fitur simpan sebagai draft dan reminder bisa diuji.',
                    'fields' => [
                        ['key' => 'report_date', 'name' => 'report_date', 'value' => Carbon::today()->toDateString()],
                        ['key' => 'shift', 'name' => 'shift', 'value' => 'Pagi'],
                        ['key' => 'group_name', 'name' => 'group_name', 'value' => 'A'],
                        ['key' => 'time_range', 'name' => 'time_range', 'value' => '07.00 - 14.00'],
                        ['key' => 'received_by_group', 'name' => 'received_by_group', 'value' => 'B'],
                        ['key' => 'ship_name_1', 'name' => 'ship_name_1', 'value' => 'KM Draft Ops'],
                    ],
                ],
            ]
        );

        $this->resetReportDetails($draft);

        $draft->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'KM Draft Ops',
            'agent' => 'AGEN DRAFT',
            'jetty' => 'Jetty 2',
            'destination' => 'Gudang Utama',
            'capacity' => 1200,
            'cargo_type' => 'UK.Granul',
            'marking' => 'Nitrea',
            'arrival_time' => Carbon::today()->setTime(8, 30),
            'operating_gang' => '1',
            'tkbm_count' => 12,
            'foreman' => 'Petugas Draft',
            'qty_delivery_current' => 50,
            'qty_delivery_prev' => 0,
            'qty_loading_current' => 45,
            'qty_loading_prev' => 0,
        ]);
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

        if ($report->materialActivity) {
            $report->materialActivity->items()->delete();
            $report->materialActivity->delete();
        }

        if ($report->containerActivity) {
            $report->containerActivity->items()->delete();
            $report->containerActivity->delete();
        }

        if ($report->turbaActivity) {
            $report->turbaActivity->deliveries()->delete();
            $report->turbaActivity->delete();
        }

        $report->unitCheckLogs()->delete();
        $report->employeeLogs()->delete();
    }
}
