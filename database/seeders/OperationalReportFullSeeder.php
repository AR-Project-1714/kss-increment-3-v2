<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\MasterEnvironmentItem;
use App\Models\MasterInventoryItem;
use App\Models\MasterUnit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Contoh laporan operasi harian yang LENGKAP untuk keperluan uji tampilan / PDF.
 *
 * Isi utama sesuai permintaan:
 *   - 2 Pemuatan Pupuk Kantong (loading) dengan data lengkap + time sheet
 *   - 3 Pemuatan Urea Curah (bulk) dengan log kegiatan
 *   - 3 Bongkar Bahan Baku (material) dengan rincian + petugas lengkap
 *   - 2 Bongkar / Muat Container dengan kapasitas Empty/Full + petugas
 *
 * Ditambah bagian pendukung (tracking turba, cek unit/inventaris/shelter, dan
 * karyawan) supaya laporan bisa dilihat utuh dari awal sampai tanda tangan.
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=OperationalReportFullSeeder
 */
class OperationalReportFullSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::where('username', 'karu.a')->first() ?? User::first();
        $receiver = User::where('username', 'karu.b')->first();

        $report = DailyReport::updateOrCreate(
            [
                'report_date' => Carbon::today()->toDateString(),
                'shift' => 'Malam',
                'group_name' => 'A',
            ],
            [
                'user_id' => $creator?->id,
                'created_by' => $creator?->id,
                'received_by_group' => 'B',
                'received_by_user_id' => $receiver?->id,
                'time_range' => '23:00 - 07:00',
                'status' => 'submitted',
                'payload' => [
                    'source' => 'OperationalReportFullSeeder',
                    'note' => 'Contoh laporan operasi lengkap (2 loading, 3 curah, 3 bongkar bahan baku, 2 container).',
                ],
            ]
        );

        $this->resetReportDetails($report);

        $this->seedLoading($report);
        $this->seedBulk($report);
        $this->seedMaterial($report);
        $this->seedContainer($report);
        $this->seedTurba($report);
        $this->seedUnitChecks($report);
        $this->seedEmployees($report);

        $this->command?->info("Laporan operasi lengkap dibuat: #{$report->id} ({$report->report_date->toDateString()} / {$report->shift} / Group {$report->group_name}).");
    }

    /** ================= I. PEMUATAN PUPUK KANTONG (2 kegiatan) ================= */
    private function seedLoading(DailyReport $report): void
    {
        $loading1 = $report->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'Bahtera Sukses',
            'agent' => 'PT. NDSB',
            'jetty' => 'Tursina',
            'destination' => 'Pontianak',
            'capacity' => 3700,
            'wo_number' => 'WO-0123',
            'cargo_type' => 'UK. Granul',
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
            'driver_name' => 'Arlis, Udin, Nurdian',
            'truck_number' => 'TRL-02, TRL-05, TRL-06',
            'tally_ship' => 'Jefry, Zein',
            'operator_ship' => 'Wirawan',
            'forklift_ship' => 'FL-71, FL-16',
            'operator_warehouse' => 'Gudang Op',
            'forklift_warehouse' => 'FL-17',
        ]);

        $loading1->timesheets()->createMany([
            ['category' => 'delivery', 'time' => '23:00', 'activity' => 'Lanjut kirim'],
            ['category' => 'delivery', 'time' => '02:30', 'activity' => 'Kirim normal'],
            ['category' => 'delivery', 'time' => '04:00', 'activity' => 'Stop kirim'],
            ['category' => 'loading', 'time' => '23:15', 'activity' => 'Mulai muat palka 1'],
            ['category' => 'loading', 'time' => '00:00', 'activity' => 'Stop muat sampai jam 00:00'],
            ['category' => 'loading', 'time' => '01:00', 'activity' => 'Lanjut muat palka 2'],
        ]);

        $loading2 = $report->loadingActivities()->create([
            'sequence' => 2,
            'ship_name' => 'Dhana Bahari 2',
            'agent' => 'NDSB',
            'jetty' => 'Tursina',
            'destination' => 'Pontianak',
            'capacity' => 2500,
            'wo_number' => 'WO-0456',
            'cargo_type' => 'UK. Granul',
            'marking' => 'Nitrea',
            'arrival_time' => Carbon::now()->subHours(8),
            'operating_gang' => '2',
            'tkbm_count' => 23,
            'foreman' => 'Linta',
            'qty_delivery_current' => 224.00,
            'qty_delivery_prev' => 1832.00,
            'qty_loading_current' => 403.65,
            'qty_loading_prev' => 1425.55,
            'qty_damage_current' => 1.5,
            'qty_damage_prev' => 0,
            'tally_warehouse' => 'Asmuni',
            'driver_name' => 'Doni, Rahim, Azis',
            'truck_number' => 'TRL-07, TRL-08, TRL-09',
            'tally_ship' => 'Ardy',
            'operator_ship' => 'Musliadi, Rifky',
            'forklift_ship' => 'FL-71, FL-16',
            'operator_warehouse' => 'Zein, Syamrisal',
            'forklift_warehouse' => 'FL-03, FL-13',
        ]);

        $loading2->timesheets()->createMany([
            ['category' => 'delivery', 'time' => '23:00', 'activity' => 'Lanjut kirim'],
            ['category' => 'delivery', 'time' => '04:00', 'activity' => 'Stop kirim'],
            ['category' => 'loading', 'time' => '00:00', 'activity' => 'Stop muat sampai jam 00:00'],
            ['category' => 'loading', 'time' => '01:00', 'activity' => 'Kapal tidak muat'],
        ]);
    }

    /** ================= II. PEMUATAN UREA CURAH (3 kegiatan) ================= */
    private function seedBulk(DailyReport $report): void
    {
        $bulks = [
            [
                'ship_name' => 'Maximus-I',
                'agent' => 'Berkah Samudera Berjaya',
                'jetty' => 'Jetty 1',
                'destination' => 'Luar Negeri',
                'stevedoring' => 'PBM KSS',
                'commodity' => 'UC. Granul',
                'capacity' => 15000,
                'berthing' => Carbon::now()->subDay(),
                'start' => Carbon::now()->subHours(10),
                'logs' => [
                    ['t' => [0, 5], 'activity' => 'Stop muat #1', 'cob' => 123],
                    ['t' => [0, 45], 'activity' => 'Lanjut muat #3', 'cob' => 125],
                    ['t' => [3, 20], 'activity' => 'Ganti palka', 'cob' => 118],
                ],
            ],
            [
                'ship_name' => 'Oriental Diamond',
                'agent' => 'Samudera Indonesia',
                'jetty' => 'Jetty 2',
                'destination' => 'Surabaya',
                'stevedoring' => 'PBM KSS',
                'commodity' => 'UC. Prill',
                'capacity' => 8000,
                'berthing' => Carbon::now()->subHours(20),
                'start' => Carbon::now()->subHours(6),
                'logs' => [
                    ['t' => [1, 0], 'activity' => 'Mulai muat palka 2', 'cob' => 140],
                    ['t' => [2, 30], 'activity' => 'Hujan, stop muat', 'cob' => 0],
                    ['t' => [3, 15], 'activity' => 'Cuaca cerah, lanjut muat', 'cob' => 132],
                ],
            ],
            [
                'ship_name' => 'Meratus Bontang',
                'agent' => 'Meratus Line',
                'jetty' => 'Jetty 1',
                'destination' => 'Makassar',
                'stevedoring' => 'PBM KSS',
                'commodity' => 'UC. Granul',
                'capacity' => 6500,
                'berthing' => Carbon::now()->subHours(14),
                'start' => Carbon::now()->subHours(4),
                'logs' => [
                    ['t' => [4, 0], 'activity' => 'Mulai muat', 'cob' => 110],
                    ['t' => [5, 10], 'activity' => 'Muat normal', 'cob' => 128],
                ],
            ],
        ];

        foreach ($bulks as $index => $data) {
            $bulk = $report->bulkLoadingActivities()->create([
                'sequence' => $index + 1,
                'ship_name' => $data['ship_name'],
                'agent' => $data['agent'],
                'jetty' => $data['jetty'],
                'destination' => $data['destination'],
                'stevedoring' => $data['stevedoring'],
                'commodity' => $data['commodity'],
                'capacity' => $data['capacity'],
                'berthing_time' => $data['berthing'],
                'start_loading_time' => $data['start'],
            ]);

            $bulk->logs()->createMany(array_map(fn ($log) => [
                'datetime' => Carbon::now()->setTime($log['t'][0], $log['t'][1]),
                'activity' => $log['activity'],
                'cob' => $log['cob'] ?: null,
            ], $data['logs']));
        }
    }

    /** ================= III. BONGKAR BAHAN BAKU (3 kegiatan) ================= */
    private function seedMaterial(DailyReport $report): void
    {
        $materials = [
            [
                'ship_name' => 'MV. Sinar Baru',
                'agent' => 'PT. Deraaga',
                'jetty' => 'Gokil',
                'capacity' => 5000,
                'ship_tally' => 'Mustafa',
                'fl_operator' => 'Muhammad Zein Al-Fiqri',
                'fl_number' => 'FL.KSS-201',
                'delivery_tally' => 'Asri Sahibu',
                'driver' => 'Samsul Zainuddin, Arlis',
                'truck' => 'TRL-01, TRL-03',
                'hours' => '23:00 - 07:00',
                'items' => [
                    ['Clay JB', 320, 1280, 1600],
                    ['Dolomite JB', 210, 640, 850],
                    ['MGO 18% 50kg', 150, 300, 450],
                    ['Limestone', 400, 1100, 1500],
                ],
            ],
            [
                'ship_name' => 'MV. Karya Abadi',
                'agent' => 'PT. Samator',
                'jetty' => 'Tursina',
                'capacity' => 3500,
                'ship_tally' => 'Jenri Tangruru',
                'fl_operator' => 'Boyska Albian',
                'fl_number' => 'FL.KSS-105',
                'delivery_tally' => 'Zulkifli A',
                'driver' => 'Rahim, Doni',
                'truck' => 'TRL-04, TRL-05',
                'hours' => '23:30 - 06:30',
                'items' => [
                    ['Clay JB', 180, 720, 900],
                    ['Phosphate Rock', 260, 540, 800],
                    ['Sulfur', 90, 210, 300],
                ],
            ],
            [
                'ship_name' => 'MV. Bahari Jaya',
                'agent' => 'AGEN KSS',
                'jetty' => 'Jetty 1',
                'capacity' => 4200,
                'ship_tally' => 'Musliady',
                'fl_operator' => 'Rifky Rana Juliansyah',
                'fl_number' => 'FL.KSS-108',
                'delivery_tally' => 'Ardy',
                'driver' => 'Eko, Dwi',
                'truck' => 'TRL-06, TRL-10',
                'hours' => '00:00 - 07:00',
                'items' => [
                    ['Dolomite JB', 300, 900, 1200],
                    ['Limestone', 250, 750, 1000],
                    ['MGO 18% 50kg', 120, 380, 500],
                ],
            ],
        ];

        foreach ($materials as $index => $data) {
            $material = $report->materialActivity()->create([
                'sequence' => $index + 1,
                'ship_name' => $data['ship_name'],
                'agent' => $data['agent'],
                'jetty' => $data['jetty'],
                'capacity' => $data['capacity'],
                'ship_tally_names' => $data['ship_tally'],
                'forklift_operator_names' => $data['fl_operator'],
                'forklift_number' => $data['fl_number'],
                'delivery_tally_names' => $data['delivery_tally'],
                'driver_names' => $data['driver'],
                'truck_number' => $data['truck'],
                'working_hours' => $data['hours'],
            ]);

            $material->items()->createMany(array_map(fn ($item) => [
                'raw_material_type' => $item[0],
                'qty_current' => $item[1],
                'qty_prev' => $item[2],
                'qty_total' => $item[3],
            ], $data['items']));
        }
    }

    /** ================= BONGKAR / MUAT CONTAINER (2 kegiatan) ================= */
    private function seedContainer(DailyReport $report): void
    {
        $containers = [
            [
                'ship_name' => 'KM Tanto Sejahtera',
                'agent' => 'KDMP',
                'jetty' => 'Tursina',
                'empty' => 100,
                'full' => 40,
                'ship_tally' => 'Asri Sahibu',
                'gudang_tally' => 'Mustafa',
                'driver' => 'Samsul Zainuddin, Arlis, Jenri Tangruru',
                'truck' => 'TRL-01, TRL-03, TRL-05',
                'items' => [
                    ['07:00 - 23:00', 100, 50, 150, 'Empty'],
                    ['23:00 - 04:00', 40, 20, 60, 'Full'],
                ],
            ],
            [
                'ship_name' => 'KM Meratus Kelana',
                'agent' => 'Meratus Line',
                'jetty' => 'Jetty 2',
                'empty' => 60,
                'full' => 25,
                'ship_tally' => 'Zulkifli A',
                'gudang_tally' => 'Boyska Albian',
                'driver' => 'Rahim, Doni',
                'truck' => 'TRL-04, TRL-06',
                'items' => [
                    ['23:00 - 03:00', 60, 30, 90, 'Empty'],
                    ['03:00 - 06:00', 25, 15, 40, 'Full'],
                ],
            ],
        ];

        foreach ($containers as $index => $data) {
            $container = $report->containerActivity()->create([
                'sequence' => $index + 1,
                'ship_name' => $data['ship_name'],
                'agent' => $data['agent'],
                'jetty' => $data['jetty'],
                'capacity' => $data['empty'],
                'capacity_empty' => $data['empty'],
                'capacity_full' => $data['full'],
                'ship_tally_names' => $data['ship_tally'],
                'gudang_tally_names' => $data['gudang_tally'],
                'driver_names' => $data['driver'],
                'truck_number' => $data['truck'],
            ]);

            $container->items()->createMany(array_map(fn ($item) => [
                'time_text' => $item[0],
                'qty_current' => $item[1],
                'qty_prev' => $item[2],
                'qty_total' => $item[3],
                'status' => $item[4],
            ], $data['items']));
        }
    }

    /** ================= IV. TRACKING PENGIRIMAN PUPUK KANTONG ================= */
    private function seedTurba(DailyReport $report): void
    {
        $turba = $report->turbaActivity()->create([
            'tally_gudang_names' => 'Asmuni, Syamsuddin',
            'tally_gudang_terima' => 'Mustafa',
            'forklift_operator_names' => 'Boyska Albian',
            'fl_no' => 'FL-01, FL-05',
            'driver_names' => 'Samsul Zainuddin',
            'trl_no' => 'TRL-03',
            'working_hours' => '23:00 - 03:00',
        ]);

        $turba->deliveries()->createMany([
            [
                'truck_name' => 'Buffer Stok',
                'do_so_number' => '5940',
                'capacity' => 943.80,
                'marking_type' => 'Granul Khusus',
                'qty_current' => 63.80,
                'qty_prev' => 880.00,
                'qty_accumulated' => 943.80,
            ],
            [
                'truck_name' => 'Buffer Stuffing',
                'do_so_number' => '5941',
                'capacity' => 500.00,
                'marking_type' => 'Nitrea',
                'qty_current' => 120.00,
                'qty_prev' => 300.00,
                'qty_accumulated' => 420.00,
            ],
            [
                'truck_name' => 'Buffer Stok',
                'do_so_number' => '5942',
                'capacity' => 250.00,
                'marking_type' => 'Granul Khusus',
                'qty_current' => 50.00,
                'qty_prev' => 150.00,
                'qty_accumulated' => 200.00,
            ],
        ]);
    }

    /** ================= V. CEK UNIT / INVENTARIS / SHELTER ================= */
    private function seedUnitChecks(DailyReport $report): void
    {
        foreach (MasterUnit::orderBy('id')->get() as $unit) {
            $report->unitCheckLogs()->create([
                'category' => 'vehicle',
                'item_name' => $unit->name,
                'master_id' => $unit->id,
                'fuel_level' => ((int) ($unit->id % 4) + 1).'/4',
                'condition_received' => $unit->id % 8 === 0 ? 'Rusak' : 'Baik',
                'condition_handed_over' => $unit->id % 11 === 0 ? 'Rusak' : 'Baik',
            ]);
        }

        foreach (MasterInventoryItem::orderBy('id')->get() as $inventory) {
            $report->unitCheckLogs()->create([
                'category' => 'inventory',
                'item_name' => $inventory->name,
                'master_id' => $inventory->id,
                'quantity' => $inventory->stock,
                'condition_received' => 'Baik',
                'condition_handed_over' => $inventory->id % 6 === 0 ? 'Rusak' : 'Baik',
            ]);
        }

        $shelterItems = MasterEnvironmentItem::where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->pluck('name');

        if ($shelterItems->isEmpty()) {
            $shelterItems = collect(['Ruangan Shelter', 'Halaman Shelter', 'Selokan/Parit', 'Jala-Jala Angkat', 'Jala-Jala Lambung', 'Terpal', 'Chain Sling']);
        }

        foreach ($shelterItems as $i => $item) {
            $report->unitCheckLogs()->create([
                'category' => 'shelter',
                'item_name' => $item,
                'condition_received' => 'Baik',
                'condition_handed_over' => $i === 2 ? 'Rusak' : 'Baik',
            ]);
        }
    }

    /** ================= VI. KARYAWAN ================= */
    private function seedEmployees(DailyReport $report): void
    {
        $shift = ['Jhon Maradona Mailoor', 'Zainuddin', 'Mustafa', 'Asri Sahibu', 'Boyska Albian', 'Muhammad Zein Al-Fiqri', 'Zulkifli A', 'Jenri Tangruru', 'Samsul Zainuddin', 'Arlis', 'Musliady', 'Rifky Rana Juliansyah'];
        foreach ($shift as $name) {
            $report->employeeLogs()->create([
                'category' => 'shift',
                'name' => $name,
                'time_in' => '23:00',
                'time_out' => '07:00',
                'description' => '-',
            ]);
        }

        $report->employeeLogs()->createMany([
            ['category' => 'operasi', 'name' => 'Syamsuddin', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'Zein', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'Rifky', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'Asmuni', 'description' => 'Lembur'],
            ['category' => 'operasi', 'name' => 'Ronal', 'description' => 'Relief Malam'],
            ['category' => 'operasi', 'name' => 'Ardy', 'description' => 'Relief Malam'],
            ['category' => 'operasi', 'name' => 'Ardian', 'description' => 'Relief Malam'],
        ]);

        $op7 = [
            ['Aziz Bukhari Surya', 'FL.KSS-100', 'P.6'],
            ['Juprianto', 'FL.KSS-101', 'Popka'],
            ['Ahmad Faitzal', 'FL.KSS-102', 'Bagging-1'],
            ['Ashar', 'FL.KSS-104', 'Bagging-1'],
            ['Ediansyah', 'FL.KSS-105', 'Bagging-2'],
            ['Artanto Adhiguna', 'FL.KSS-106', 'Bagging-2'],
            ['Kiki Arifin Saputra', 'FL.KSS-108', 'Gudang Produk Tursina'],
            ['Firman', 'FL.KSS-109', 'Blending'],
            ['Aji Faitsal', 'FL.KSS-103', 'Blending'],
            ['Edi Sutomo', 'FL.KSS-107', 'Blending'],
        ];
        foreach ($op7 as $row) {
            $report->employeeLogs()->create([
                'category' => 'op7',
                'name' => $row[0],
                'no_forklift_' => $row[1],
                'work_area' => $row[2],
                'time_in' => '15:00',
                'time_out' => '23:00',
                'description' => '-',
            ]);
        }

        // Satu operator OP.7 tidak masuk -> ada penggantinya.
        $report->employeeLogs()->create([
            'category' => 'replacement',
            'name' => 'Sabarudin',
            'no_forklift_' => 'FL.KSS-101',
            'work_area' => 'Popka',
            'time_in' => '15:00',
            'time_out' => '23:00',
            'description' => 'Menggantikan Juprianto (sakit)',
        ]);

        $report->employeeLogs()->createMany([
            ['category' => 'lain', 'name' => 'All Team', 'time_in' => '22:45', 'time_out' => '23:00', 'description' => 'Pemberian Safety Briefing'],
            ['category' => 'lain', 'name' => 'Tim Kebersihan', 'time_in' => '05:30', 'time_out' => '06:30', 'description' => 'Pembersihan area shelter'],
        ]);
    }

    /** Bersihkan seluruh detail laporan agar seeder idempotent (bisa diulang). */
    private function resetReportDetails(DailyReport $report): void
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
