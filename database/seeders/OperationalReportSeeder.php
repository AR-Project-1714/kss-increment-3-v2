<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\MasterEmployee;
use App\Models\MasterEnvironmentItem;
use App\Models\MasterInventoryItem;
use App\Models\MasterUnit;
use App\Models\User;
use App\Services\BulkTonnageService;
use App\Support\ShipNameNormalizer;
use Carbon\Carbon;
use Database\Seeders\Concerns\GuardsSampleData;
use Illuminate\Database\Seeder;

/**
 * Data contoh laporan operasi untuk periode Mei-Juli 2026.
 *
 * Setiap hari berisi tiga shift dengan rotasi Regu A-D. Seluruh sumber data
 * yang dipakai menu Kinerja Operasi dan Rincian Kegiatan ikut diisi supaya
 * perbandingan bulan, regu, shift, jenis kegiatan, kualitas, dan beban kerja
 * terlihat jelas.
 *
 * Master karyawan tidak pernah dibuat atau diubah di sini. Seeder hanya
 * membaca roster asli dari MasterEmployeeSeeder untuk menyalin susunan petugas
 * ke employee_logs pada laporan masing-masing.
 *
 * Idempotent: laporan milik seeder dipertahankan berdasarkan
 * report_date + shift + group_name, sedangkan data contoh lama dengan kombinasi
 * yang sudah tidak dipakai akan dibersihkan.
 */
class OperationalReportSeeder extends Seeder
{
    use GuardsSampleData;

    private const SOURCE = 'OperationalReportSeeder';

    private const PERIOD_START = '2026-05-01';

    private const PERIOD_END = '2026-07-31';

    private const GROUPS = ['A', 'B', 'C', 'D'];

    private const SHIFTS = [
        'Pagi' => '07:00 - 15:00',
        'Sore' => '15:00 - 23:00',
        'Malam' => '23:00 - 07:00',
    ];

    private const SHIFT_START_HOURS = [
        'Pagi' => 7,
        'Sore' => 15,
        'Malam' => 23,
    ];

    private const GROUP_FACTORS = [
        'A' => 1.04,
        'B' => 1.01,
        'C' => 0.98,
        'D' => 1.02,
    ];

    private const SHIFT_FACTORS = [
        'Pagi' => 1.05,
        'Sore' => 1.00,
        'Malam' => 0.94,
    ];

    /** [no_forklift, area] per posisi baris OP.7 - baris 1 stasiun tetap Operator P.6. */
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
        ['Nusantara Sejati', 'PT. Tanto Intim Line', 'Jetty 3', 'Balikpapan', 3600],
    ];

    private const BULK_SHIPS = [
        ['Maximus-I', 'Berkah Samudera Berjaya', 'Jetty 1', 'Luar Negeri', 15000],
        ['Oriental Diamond', 'Samudera Indonesia', 'Jetty 2', 'Vietnam', 8000],
        ['Pacific Talent', 'Meratus Line', 'Jetty 1', 'Filipina', 6500],
        ['Ocean Makmur', 'PT. Pelayaran Bahari', 'Jetty 2', 'Thailand', 9000],
    ];

    private const MATERIAL_SHIPS = [
        ['MV. Bongkar Jaya', 'PT. Karya Samudera', 'Jetty 3', 6500],
        ['MV. Timur Laut', 'PT. Lintas Bahari', 'Jetty 2', 7200],
        ['MV. Anugerah 88', 'PT. Duta Maritim', 'Jetty 3', 5800],
        ['MV. Sumber Rezeki', 'PT. Karya Samudera', 'Jetty 2', 8000],
    ];

    private const RAW_MATERIALS = [
        'Clay JB',
        'Dolomite JB',
        'Limestone',
        'Phosphate Rock',
    ];

    private const CONTAINER_SHIPS = [
        ['KM Tanto Sejahtera', 'KDMP', 'Tursina', 110, 70],
        ['KM Meratus Makassar', 'Meratus Line', 'Jetty 3', 125, 80],
        ['KM Temas Ende', 'Temas Shipping', 'Tursina', 100, 65],
    ];

    private const TURBA_DESTINATIONS = [
        ['Buffer Stock Makassar', 'Nitrea'],
        ['Gudang Lini II Parepare', 'Urea Granul'],
        ['Distributor Gowa', 'Nitrea'],
        ['Gudang Lini III Maros', 'Urea Granul'],
    ];

    /** @var array<string, User> */
    private array $usersByUsername = [];

    /** COB kumulatif yang sudah tercatat per pelayaran, dikunci nomor pelayaran. */
    private array $bulkVoyageCob = [];

    public function run(): void
    {
        if ($this->shouldSkipSampleData()) {
            return;
        }

        $start = Carbon::parse(self::PERIOD_START)->startOfDay();
        $configuredEnd = Carbon::parse(self::PERIOD_END)->startOfDay();
        $end = Carbon::today()->lessThan($configuredEnd) ? Carbon::today() : $configuredEnd;

        if ($end->lessThan($start)) {
            $this->command?->warn('OperationalReportSeeder belum dijalankan karena periode Mei 2026 belum dimulai.');

            return;
        }

        $this->loadUsers();

        $slots = [];
        $expectedReports = [];
        $slot = 0;

        for ($day = $start->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            foreach (array_keys(self::SHIFTS) as $shiftName) {
                $group = self::GROUPS[$slot % count(self::GROUPS)];
                $nextGroup = self::GROUPS[($slot + 1) % count(self::GROUPS)];
                $slotData = [$day->copy(), $shiftName, $group, $nextGroup, $slot];

                $slots[] = $slotData;
                $expectedReports[$this->reportKey($day, $shiftName, $group)] = true;
                $slot++;
            }
        }

        $this->pruneObsoleteSampleReports($expectedReports);

        foreach ($slots as [$day, $shiftName, $group, $nextGroup, $slotNumber]) {
            $this->seedSlot($day, $shiftName, $group, $nextGroup, $slotNumber);
        }

        // Tonase muat curah/amoniak adalah selisih antar pembacaan COB, jadi
        // baru bisa dihitung setelah seluruh pelayaran tertulis lengkap.
        app(BulkTonnageService::class)->recalculate();

        $this->command?->info(
            count($slots).' laporan operasional dibuat untuk '
            .$start->locale('id')->translatedFormat('d F Y').' - '
            .$end->locale('id')->translatedFormat('d F Y')
            .' (3 shift/hari, rotasi Regu A-D, seluruh jenis kegiatan).'
        );
    }

    private function loadUsers(): void
    {
        $usernames = ['manajer'];

        foreach (self::GROUPS as $group) {
            $usernames[] = 'karu.'.strtolower($group);
        }

        $this->usersByUsername = User::query()
            ->whereIn('username', $usernames)
            ->get()
            ->keyBy('username')
            ->all();
    }

    private function seedSlot(Carbon $day, string $shiftName, string $group, string $nextGroup, int $slot): void
    {
        $creator = $this->usersByUsername['karu.'.strtolower($group)] ?? null;
        $receiver = $this->usersByUsername['karu.'.strtolower($nextGroup)] ?? null;
        $approver = $this->usersByUsername['manajer'] ?? null;
        $submittedAt = $this->submissionMoment($day, $shiftName, $slot);
        $status = $slot % 13 === 0 ? 'submitted' : ($slot % 5 === 0 ? 'acknowledged' : 'approved');
        $receivedAt = $status === 'submitted' ? null : $submittedAt->copy()->addMinutes(12 + ($slot % 9));
        $approvedAt = $status === 'approved' ? $receivedAt?->copy()->addMinutes(35 + ($slot % 45)) : null;

        $report = DailyReport::updateOrCreate(
            [
                // Gunakan objek tanggal agar binding updateOrCreate sama dengan
                // serialisasi date cast pada SQLite maupun kolom DATE MySQL.
                'report_date' => $day->copy()->startOfDay(),
                'shift' => $shiftName,
                'group_name' => $group,
            ],
            [
                'user_id' => $creator?->id,
                'created_by' => $creator?->id,
                'received_by_group' => $nextGroup,
                'received_by_user_id' => $receivedAt ? $receiver?->id : null,
                'received_at' => $receivedAt,
                'time_range' => self::SHIFTS[$shiftName],
                'status' => $status,
                'approved_by' => $approvedAt ? $approver?->id : null,
                'approved_at' => $approvedAt,
                'payload' => [
                    'source' => self::SOURCE,
                    'period' => 'Mei-Juli 2026',
                    'scenario' => 'Perbandingan kegiatan dan kinerja operasional',
                ],
                'created_at' => $submittedAt,
                'updated_at' => $approvedAt ?? $receivedAt ?? $submittedAt,
            ]
        );

        $this->resetDetails($report);

        $factor = $this->productivityFactor($day, $group, $shiftName, $slot);
        // Carbon 3 mengembalikan selisih bertanda secara bawaan. Menghitung
        // dari tanggal laporan ke awal periode membuat hari setelah 1 Mei
        // bernilai negatif, sehingga pola modulo kegiatan menjadi timpang
        // (hampir semua shift mendapat bulk, container nyaris tidak pernah).
        $dayIndex = (int) Carbon::parse(self::PERIOD_START)->diffInDays($day, true);
        $shiftIndex = array_search($shiftName, array_keys(self::SHIFTS), true);

        $this->seedBagged($report, $slot, $day, $shiftName, $factor);

        if (($dayIndex + $shiftIndex) % 3 !== 2) {
            $this->seedBulk($report, $slot, $day, $shiftName, $factor);
        }

        if (($dayIndex * 2 + $shiftIndex) % 4 === 0) {
            $this->seedMaterial($report, $slot, $factor);
        }

        if (($dayIndex + $shiftIndex * 2) % 4 === 1) {
            $this->seedContainer($report, $slot, $shiftName, $factor);
        }

        if (($dayIndex + $shiftIndex) % 2 === 0) {
            $this->seedTurba($report, $slot, $shiftName, $factor);
        }

        $this->seedUnitChecks($report);
        $this->seedEmployees($report, $group, self::SHIFTS[$shiftName], $slot);
    }

    private function seedBagged(
        DailyReport $report,
        int $slot,
        Carbon $day,
        string $shiftName,
        float $factor
    ): void {
        $voyageLength = 6;
        $voyageIndex = intdiv($slot, $voyageLength);
        $voyageStartSlot = $voyageIndex * $voyageLength;
        [$name, $agent, $jetty, $destination, $capacity] = self::BAGGED_SHIPS[$voyageIndex % count(self::BAGGED_SHIPS)];

        $current = $this->baggedQuantity($slot, $factor);
        $previous = 0;

        for ($previousSlot = $voyageStartSlot; $previousSlot < $slot; $previousSlot++) {
            $previousDay = Carbon::parse(self::PERIOD_START)->addDays(intdiv($previousSlot, count(self::SHIFTS)));
            $previousShift = array_keys(self::SHIFTS)[$previousSlot % count(self::SHIFTS)];
            $previousGroup = self::GROUPS[$previousSlot % count(self::GROUPS)];
            $previous += $this->baggedQuantity(
                $previousSlot,
                $this->productivityFactor($previousDay, $previousGroup, $previousShift, $previousSlot)
            );
        }

        $damage = $day->month === 6
            ? 0.75 + ($slot % 4) * 0.18
            : 0.22 + ($slot % 3) * 0.11;

        $activity = $report->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => $name,
            'ship_name_key' => ShipNameNormalizer::key($name) ?: null,
            'agent' => $agent,
            'jetty' => $jetty,
            'destination' => $destination,
            'capacity' => $capacity,
            'wo_number' => 'WO-KTG-2026-'.str_pad((string) ($voyageIndex + 1), 4, '0', STR_PAD_LEFT),
            'cargo_type' => 'Urea Granul',
            'marking' => $voyageIndex % 2 === 0 ? 'Nitrea' : 'Pupuk Indonesia',
            'arrival_time' => $this->slotMoment($voyageStartSlot, -75),
            'operating_gang' => (string) (1 + $voyageIndex % 3),
            'tkbm_count' => 22 + ($slot % 7),
            'foreman' => ['Nasir', 'Linta', 'Sahrul', 'Bahar'][$slot % 4],
            'qty_delivery_current' => $current + 18,
            'qty_delivery_prev' => $previous + 45,
            'qty_loading_current' => $current,
            'qty_loading_prev' => $previous,
            'qty_damage_current' => round($damage, 2),
            'qty_damage_prev' => round(($slot - $voyageStartSlot) * $damage, 2),
            'tally_warehouse' => ['Syamsuddin', 'Asmuni', 'Zein'][$slot % 3],
            'driver_name' => 'Arlis, Udin, Nurdian',
            'truck_number' => 'TRL-02, TRL-05',
            'tally_ship' => 'Jefry, Zein',
            'operator_ship' => 'Wirawan',
            'forklift_ship' => 'FL-71, FL-16',
            'operator_warehouse' => 'Tim Gudang Produk',
            'forklift_warehouse' => 'FL-17',
        ]);

        [$deliveryTime, $loadingTime] = match ($shiftName) {
            'Pagi' => ['07:30', '09:00'],
            'Sore' => ['15:30', '17:00'],
            default => ['23:30', '01:00'],
        };

        $activity->timesheets()->createMany([
            [
                'category' => 'delivery',
                'time' => $deliveryTime,
                'activity' => 'Distribusi kantong dari gudang ke dermaga sesuai WO',
            ],
            [
                'category' => 'loading',
                'time' => $loadingTime,
                'activity' => 'Pemuatan palka dan verifikasi tally kapal',
            ],
        ]);
    }

    private function seedBulk(
        DailyReport $report,
        int $slot,
        Carbon $day,
        string $shiftName,
        float $factor
    ): void {
        $voyageLength = 12;
        $voyageIndex = intdiv($slot, $voyageLength);
        $voyageStartSlot = $voyageIndex * $voyageLength;
        [$name, $agent, $jetty, $destination, $capacity] = self::BULK_SHIPS[$voyageIndex % count(self::BULK_SHIPS)];
        $loaded = (int) round((610 + ($slot % 5) * 24) * $factor);

        // COB dicatat KUMULATIF, sama seperti pada form asli: angkanya adalah
        // total muatan yang sudah ada di kapal, bukan tambahan shift ini. Data
        // contoh harus mengikuti kebiasaan itu, karena di situlah letak
        // kekeliruan penjumlahan yang pernah membuat total tonase membengkak.
        $carried = $this->bulkVoyageCob[$voyageIndex] ?? 0;
        $firstLog = $carried + (int) round($loaded * 0.46);
        $secondLog = $carried + $loaded;
        $this->bulkVoyageCob[$voyageIndex] = $secondLog;

        $berthing = $this->slotMoment($voyageStartSlot, -180);
        $startLoading = $berthing->copy()->addHours(4 + ($voyageIndex % 3));
        $activityType = $voyageIndex % 2 === 0 ? 'muat_curah' : 'muat_amoniak';

        $activity = $report->bulkLoadingActivities()->create([
            'activity_type' => $activityType,
            'sequence' => 1,
            'ship_name' => $name,
            'ship_name_key' => ShipNameNormalizer::key($name) ?: null,
            'agent' => $agent,
            'jetty' => $jetty,
            'destination' => $destination,
            'stevedoring' => 'PBM KSS',
            'commodity' => $activityType === 'muat_amoniak' ? 'Amoniak Cair' : 'Urea Curah Granul',
            'capacity' => $capacity,
            'berthing_time' => $berthing,
            'start_loading_time' => $startLoading,
        ]);

        $activity->logs()->createMany([
            [
                'datetime' => $this->shiftMoment($day, $shiftName, 50),
                'activity' => 'Pemuatan palka aktif dan pemeriksaan draft awal',
                'cob' => $firstLog,
            ],
            [
                'datetime' => $this->shiftMoment($day, $shiftName, 210),
                'activity' => 'Pemuatan lanjutan dan pemerataan muatan palka',
                'cob' => $secondLog,
            ],
        ]);
    }

    private function seedMaterial(DailyReport $report, int $slot, float $factor): void
    {
        $activityCount = $slot % 13 === 0 ? 2 : 1;

        for ($sequence = 1; $sequence <= $activityCount; $sequence++) {
            $shipIndex = intdiv($slot, 4) + $sequence - 1;
            [$name, $agent, $jetty, $capacity] = self::MATERIAL_SHIPS[$shipIndex % count(self::MATERIAL_SHIPS)];

            $material = $report->materialActivity()->create([
                'sequence' => $sequence,
                'ship_name' => $name,
                'ship_name_key' => ShipNameNormalizer::key($name) ?: null,
                'agent' => $agent,
                'jetty' => $jetty,
                'capacity' => $capacity,
                'ship_tally_names' => ['Budi', 'Rahmat', 'Sulaiman'][$slot % 3],
                'forklift_operator_names' => ['Santoso', 'Herman', 'Akbar'][$slot % 3],
                'forklift_number' => 'FL.KSS-'.(101 + ($slot + $sequence) % 10),
                'delivery_tally_names' => ['Rudi', 'Zein', 'Jefry'][$slot % 3],
                'driver_names' => 'Eko, Dwi, Arlis',
                'truck_number' => 'TRL-0'.(1 + ($slot % 6)).', TRL-0'.(1 + (($slot + 2) % 6)),
                'working_hours' => self::SHIFTS[array_keys(self::SHIFTS)[$slot % count(self::SHIFTS)]],
            ]);

            $items = [];

            foreach (self::RAW_MATERIALS as $index => $type) {
                $current = (int) round((95 + $index * 27 + ($slot % 4) * 8) * $factor / $activityCount);
                $previous = (int) round($current * (1.5 + (($slot + $index) % 3)));

                $items[] = [
                    'raw_material_type' => $type,
                    'qty_current' => $current,
                    'qty_prev' => $previous,
                    'qty_total' => $current + $previous,
                ];
            }

            $material->items()->createMany($items);
        }
    }

    private function seedContainer(DailyReport $report, int $slot, string $shiftName, float $factor): void
    {
        $voyageIndex = intdiv($slot, 8);
        [$name, $agent, $jetty, $emptyCapacity, $fullCapacity] = self::CONTAINER_SHIPS[
            $voyageIndex % count(self::CONTAINER_SHIPS)
        ];
        $emptyCurrent = min($emptyCapacity, (int) round((27 + $slot % 8) * $factor));
        $fullCurrent = min($fullCapacity, (int) round((16 + $slot % 6) * $factor));
        $emptyPrevious = ($slot % 3) * 9;
        $fullPrevious = ($slot % 2) * 7;

        $container = $report->containerActivity()->create([
            'sequence' => 1,
            'ship_name' => $name,
            'ship_name_key' => ShipNameNormalizer::key($name) ?: null,
            'agent' => $agent,
            'jetty' => $jetty,
            'capacity' => $emptyCapacity,
            'capacity_empty' => $emptyCapacity,
            'capacity_full' => $fullCapacity,
            'ship_tally_names' => ['Asri Sahibu', 'Mustafa', 'Zein'][$slot % 3],
            'gudang_tally_names' => ['Mustafa', 'Jefry', 'Asmuni'][$slot % 3],
            'driver_names' => 'Samsul Zainuddin, Arlis',
            'truck_number' => 'TRL-01, TRL-03',
        ]);

        $container->items()->createMany([
            [
                'time_text' => self::SHIFTS[$shiftName],
                'qty_current' => $emptyCurrent,
                'qty_prev' => $emptyPrevious,
                'qty_total' => $emptyCurrent + $emptyPrevious,
                'status' => 'Empty',
            ],
            [
                'time_text' => self::SHIFTS[$shiftName],
                'qty_current' => $fullCurrent,
                'qty_prev' => $fullPrevious,
                'qty_total' => $fullCurrent + $fullPrevious,
                'status' => 'Full',
            ],
        ]);
    }

    private function seedTurba(DailyReport $report, int $slot, string $shiftName, float $factor): void
    {
        $activity = $report->turbaActivity()->create([
            'tally_gudang_names' => ['Syamsuddin', 'Asmuni', 'Zein'][$slot % 3],
            'tally_gudang_terima' => ['Jefry', 'Mustafa', 'Rudi'][$slot % 3],
            'forklift_operator_names' => ['Santoso', 'Herman', 'Akbar'][$slot % 3],
            'fl_no' => 'FL.KSS-'.(101 + $slot % 10),
            'driver_names' => 'Arlis, Udin',
            'trl_no' => 'TRL-0'.(1 + $slot % 6).', TRL-0'.(1 + ($slot + 1) % 6),
            'working_hours' => self::SHIFTS[$shiftName],
        ]);

        $deliveries = [];

        foreach ([0, 1] as $index) {
            [$destination, $marking] = self::TURBA_DESTINATIONS[($slot + $index) % count(self::TURBA_DESTINATIONS)];
            $capacity = 32;
            $current = min($capacity, (int) round((23 + (($slot + $index) % 6)) * $factor));
            $previous = (int) round(($slot % 5) * $current);

            $deliveries[] = [
                'truck_name' => $destination,
                'do_so_number' => 'DO-2026-'.str_pad((string) ($slot * 2 + $index + 1), 5, '0', STR_PAD_LEFT),
                'capacity' => $capacity,
                'marking_type' => $marking,
                'qty_current' => $current,
                'qty_prev' => $previous,
                'qty_accumulated' => $previous + $current,
            ];
        }

        $activity->deliveries()->createMany($deliveries);
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
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name');

        if ($shelterItems->isEmpty()) {
            $shelterItems = collect([
                'Ruangan Shelter',
                'Halaman Shelter',
                'Selokan/Parit',
                'Jala-Jala Angkat',
                'Jala-Jala Lambung',
                'Terpal',
                'Chain Sling',
            ]);
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
     * Seluruh nama diambil dari master yang sudah ada. Query di bawah hanya
     * membaca roster dan tidak pernah menjalankan create/update/delete pada
     * tabel master_employees.
     */
    private function seedEmployees(DailyReport $report, string $group, string $timeRange, int $slot): void
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

        $isWakaru = fn (MasterEmployee $employee): bool => str_contains(
            mb_strtolower((string) $employee->position),
            'wakil'
        );
        $isKaru = fn (MasterEmployee $employee): bool => ! $isWakaru($employee)
            && (bool) preg_match('/karu|kepala regu/i', (string) $employee->position);

        $karu = $shiftEmployees->first($isKaru);
        $wakaru = $shiftEmployees->first($isWakaru);
        $leaders = collect([$karu, $wakaru])->filter();
        $rest = $shiftEmployees->reject(
            fn (MasterEmployee $employee): bool => $leaders->contains(
                fn (MasterEmployee $leader): bool => $leader->id === $employee->id
            )
        );

        foreach ($leaders->concat($rest) as $employee) {
            $report->employeeLogs()->create([
                'category' => 'shift',
                'name' => $employee->name,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'description' => 'Hadir',
            ]);
        }

        $op7Employees = MasterEmployee::forOperational()
            ->where('status', 'active')
            ->where('group_name', 'OP.7 Group '.$group)
            ->orderBy('id')
            ->get();

        $op7Rows = [
            ['name' => 'Operator P.6'],
            ...$op7Employees->map(fn (MasterEmployee $employee): array => ['name' => $employee->name])->all(),
        ];

        foreach ($op7Rows as $index => $row) {
            [$forklift, $area] = self::OP7_POSITIONS[$index] ?? [null, null];

            $report->employeeLogs()->create([
                'category' => 'op7',
                'name' => $row['name'],
                'no_forklift_' => $forklift,
                'work_area' => $area,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'description' => 'Hadir',
            ]);
        }

        $workers = $rest->values();

        if ($slot % 7 === 0 && $workers->isNotEmpty()) {
            $employee = $workers->get($slot % $workers->count());
            $overtimeStart = Carbon::createFromFormat('H:i', $timeOut);
            $overtimeEnd = $overtimeStart->copy()->addHours(2 + ($slot % 2));

            $report->employeeLogs()->create([
                'category' => 'operasi',
                'name' => $employee->name,
                'time_in' => $overtimeStart->format('H:i'),
                'time_out' => $overtimeEnd->format('H:i'),
                'work_time' => $overtimeStart->format('H:i').' - '.$overtimeEnd->format('H:i'),
                'description' => 'Lembur',
            ]);
        }

        if ($slot % 11 === 0 && $workers->count() > 1) {
            $employee = $workers->get(($slot + 1) % $workers->count());

            $report->employeeLogs()->create([
                'category' => 'operasi',
                'name' => $employee->name,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'work_time' => $timeRange,
                'description' => 'Relief',
            ]);
        }

        $report->employeeLogs()->create([
            'category' => 'lain',
            'name' => (string) max(1, $shiftEmployees->count()),
            'personil_count' => (string) max(1, $shiftEmployees->count()),
            'time_in' => $timeIn,
            'time_out' => Carbon::createFromFormat('H:i', $timeIn)->addMinutes(20)->format('H:i'),
            'work_time' => $timeIn.' - '.Carbon::createFromFormat('H:i', $timeIn)->addMinutes(20)->format('H:i'),
            'description' => 'Safety briefing dan pembagian area kerja',
        ]);
    }

    /**
     * Hanya laporan contoh milik seeder ini yang boleh dibersihkan. Laporan
     * pengguna dan data master tidak masuk query ini.
     *
     * @param  array<string, bool>  $expectedReports
     */
    private function pruneObsoleteSampleReports(array $expectedReports): void
    {
        DailyReport::query()
            ->whereNotNull('payload')
            ->chunkById(100, function ($reports) use ($expectedReports): void {
                foreach ($reports as $report) {
                    if (($report->payload['source'] ?? null) !== self::SOURCE) {
                        continue;
                    }

                    $key = $this->reportKey(
                        Carbon::parse($report->report_date),
                        (string) $report->shift,
                        (string) $report->group_name
                    );

                    if (! isset($expectedReports[$key])) {
                        $report->delete();
                    }
                }
            });
    }

    private function reportKey(Carbon $day, string $shiftName, string $group): string
    {
        return $day->toDateString().'|'.$shiftName.'|'.$group;
    }

    private function productivityFactor(Carbon $day, string $group, string $shiftName, int $slot): float
    {
        $monthFactor = $day->month === 7 ? 1.12 : 1.00;
        $dailyVariation = 0.96 + (($slot * 7) % 9) * 0.0125;

        return $monthFactor
            * (self::GROUP_FACTORS[$group] ?? 1.0)
            * (self::SHIFT_FACTORS[$shiftName] ?? 1.0)
            * $dailyVariation;
    }

    private function baggedQuantity(int $slot, float $factor): int
    {
        return (int) round((345 + ($slot % 6) * 13) * $factor);
    }

    private function submissionMoment(Carbon $day, string $shiftName, int $slot): Carbon
    {
        $moment = match ($shiftName) {
            'Pagi' => $day->copy()->setTime(14, 50),
            'Sore' => $day->copy()->setTime(22, 50),
            default => $day->copy()->setTime(23, 55),
        };

        // Sekitar 6% laporan sengaja terlambat agar indikator ketepatan waktu
        // menampilkan nilai realistis dan bukan 100% datar.
        return $slot % 17 === 0 ? $moment->addDay() : $moment;
    }

    private function slotMoment(int $slot, int $minuteOffset = 0): Carbon
    {
        $shiftName = array_keys(self::SHIFTS)[$slot % count(self::SHIFTS)];

        return Carbon::parse(self::PERIOD_START)
            ->addDays(intdiv($slot, count(self::SHIFTS)))
            ->setTime(self::SHIFT_START_HOURS[$shiftName], 0)
            ->addMinutes($minuteOffset);
    }

    private function shiftMoment(Carbon $day, string $shiftName, int $minuteOffset): Carbon
    {
        return $day->copy()
            ->setTime(self::SHIFT_START_HOURS[$shiftName], 0)
            ->addMinutes($minuteOffset);
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
