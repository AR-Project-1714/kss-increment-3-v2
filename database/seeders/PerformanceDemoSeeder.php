<?php

namespace Database\Seeders;

use App\Models\MaintenanceReport;
use App\Models\SafetyReport;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh enam bulan untuk menguji dashboard manajer dan halaman
 * Performa Operasional.
 *
 * Yang dihasilkan:
 *   - ~320 laporan operasi (18 hari/bulan x 3 shift x 6 bulan), rotasi 4 regu
 *   - Kunjungan kapal kantong & curah yang tonasenya dibagi antar shift, jadi
 *     kolom "Realisasi" pada tabel kapal berada di kisaran wajar (75-95%)
 *   - Bongkar bahan baku, container, dan turba sebagai pelengkap komposisi
 *   - Karyawan shift, lembur berjam, dan relief untuk kartu Beban Kerja
 *   - Laporan pemeliharaan & K3 supaya tab dashboard tidak kosong
 *
 * Angka acaknya dikunci lewat mt_srand sehingga hasil seeding selalu sama —
 * penting agar tangkapan layar dan pengujian tampilan bisa dibandingkan.
 *
 * Seeder ini hanya menghapus datanya sendiri (ditandai payload.source), jadi
 * aman dijalankan berulang tanpa menyentuh laporan asli.
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=PerformanceDemoSeeder
 */
class PerformanceDemoSeeder extends Seeder
{
    private const SOURCE = 'PerformanceDemoSeeder';

    /** Jumlah bulan ke belakang, termasuk bulan berjalan. */
    private const MONTHS = 6;

    /** Hari aktif per bulan. 18 hari x 3 shift = 54 laporan per bulan. */
    private const DAYS_PER_MONTH = 18;

    private const GROUPS = ['A', 'B', 'C', 'D'];

    private const SHIFTS = [
        'Pagi' => '07:00 - 15:00',
        'Sore' => '15:00 - 23:00',
        'Malam' => '23:00 - 07:00',
    ];

    /**
     * Pengali musiman per bulan (indeks 0 = bulan paling lama). Dibuat naik
     * turun supaya grafik tren punya bentuk, bukan garis datar.
     */
    private const SEASONAL = [0.78, 0.94, 1.12, 0.86, 1.04, 1.21];

    /** Produktivitas relatif tiap regu, agar perbandingan antar regu terlihat. */
    private const GROUP_FACTOR = ['A' => 1.16, 'B' => 0.99, 'C' => 0.87, 'D' => 1.05];

    /** Porsi tonase tiap jenis kegiatan terhadap total bulanan. */
    private const MIX = [
        'kantong' => 0.30,
        'curah' => 0.35,
        'material' => 0.20,
        'container' => 0.08,
        'turba' => 0.07,
    ];

    /** Tonase dasar sebulan sebelum dikali faktor musiman. */
    private const MONTHLY_BASE = 46000.0;

    /** @var array<int, int> */
    private array $userByGroup = [];

    private ?int $managerId = null;

    public function run(): void
    {
        // Kunci deret acak supaya hasil seeding selalu identik.
        mt_srand(20260725);

        $this->resolveUsers();
        $this->purgePreviousRun();

        $monthStart = Carbon::today()->startOfMonth()->subMonthsNoOverflow(self::MONTHS - 1);
        $total = 0;

        for ($index = 0; $index < self::MONTHS; $index++) {
            $month = $monthStart->copy()->addMonthsNoOverflow($index);
            $isCurrentMonth = $month->isSameMonth(Carbon::today());

            $total += $this->seedMonth($month, self::SEASONAL[$index], $isCurrentMonth);
        }

        $this->seedMaintenanceReports();
        $this->seedSafetyReports();
        $this->seedActivityLogs();

        $this->command?->info("Selesai. {$total} laporan operasi dibuat untuk ".self::MONTHS.' bulan terakhir.');
        $this->command?->info('Bersihkan cache dashboard dengan: php artisan cache:clear');
    }

    // ============================================================
    // Persiapan
    // ============================================================

    private function resolveUsers(): void
    {
        foreach (self::GROUPS as $group) {
            $user = User::where('username', 'karu.'.strtolower($group))->first() ?? User::first();
            $this->userByGroup[$group] = (int) $user?->id;
        }

        $this->managerId = User::where('username', 'manajer')->value('id');
    }

    /**
     * Hapus hasil seeding sebelumnya. Kegiatan turunan ikut terhapus lewat
     * foreign key cascade, jadi cukup menghapus baris induknya.
     */
    private function purgePreviousRun(): void
    {
        $ids = DB::table('daily_reports')
            ->whereJsonContains('payload->source', self::SOURCE)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('daily_reports')->whereIn('id', $ids)->delete();
            $this->command?->info("Menghapus {$ids->count()} laporan demo dari seeding sebelumnya.");
        }

        DB::table('maintenance_reports')->where('karu_pemeliharaan_name', 'like', '%[demo]%')->delete();
        DB::table('safety_reports')->where('document_number', 'like', 'DEMO-%')->delete();
    }

    // ============================================================
    // Satu bulan penuh
    // ============================================================

    private function seedMonth(CarbonInterface $month, float $factor, bool $isCurrentMonth): int
    {
        $days = $this->activeDays($month, $isCurrentMonth);

        if ($days === []) {
            return 0;
        }

        // Slot = satu kesempatan kerja (tanggal + shift), urut kronologis.
        // Kunjungan kapal menempati rentang slot yang berurutan.
        $slots = [];

        foreach ($days as $day) {
            foreach (array_keys(self::SHIFTS) as $shift) {
                $slots[] = ['day' => $day, 'shift' => $shift];
            }
        }

        $monthlyTonnage = self::MONTHLY_BASE * $factor;
        $baggedVisits = $this->buildVisits($slots, 'kantong', $monthlyTonnage * self::MIX['kantong'], 4, 9);
        $bulkVisits = $this->buildVisits($slots, 'curah', $monthlyTonnage * self::MIX['curah'], 3, 6);

        $created = 0;

        foreach ($slots as $slotIndex => $slot) {
            /** @var Carbon $day */
            $day = $slot['day'];
            $shift = $slot['shift'];

            $group = self::GROUPS[($day->day + array_search($shift, array_keys(self::SHIFTS), true)) % 4];
            $nextGroup = self::GROUPS[(array_search($group, self::GROUPS, true) + 1) % 4];
            $groupFactor = self::GROUP_FACTOR[$group];

            $reportId = $this->createReport($day, $shift, $group, $nextGroup, $isCurrentMonth);
            $created++;

            $this->attachBagged($reportId, $baggedVisits, $slotIndex, $groupFactor, $factor);
            $this->attachBulk($reportId, $bulkVisits, $slotIndex, $groupFactor);
            $this->attachMaterial($reportId, $slotIndex, $monthlyTonnage * self::MIX['material'], count($slots), $groupFactor);
            $this->attachContainer($reportId, $slotIndex, $monthlyTonnage * self::MIX['container'], count($slots), $groupFactor);
            $this->attachTurba($reportId, $slotIndex, $monthlyTonnage * self::MIX['turba'], count($slots), $groupFactor);
            $this->attachEmployees($reportId, $shift, $day);
        }

        return $created;
    }

    /**
     * Tanggal aktif dalam sebulan. Bulan berjalan berhenti di hari ini supaya
     * tidak ada laporan bertanggal masa depan.
     *
     * @return array<int, Carbon>
     */
    private function activeDays(CarbonInterface $month, bool $isCurrentMonth): array
    {
        $lastDay = $isCurrentMonth
            ? (int) Carbon::today()->day
            : (int) $month->copy()->endOfMonth()->day;

        // Sebar hari aktif merata sepanjang bulan, bukan menumpuk di awal.
        $wanted = min(self::DAYS_PER_MONTH, $lastDay);

        if ($wanted <= 0) {
            return [];
        }

        $step = $lastDay / $wanted;
        $days = [];

        for ($i = 0; $i < $wanted; $i++) {
            $dayNumber = (int) floor($i * $step) + 1;
            $days[$dayNumber] = $month->copy()->startOfMonth()->addDays($dayNumber - 1);
        }

        return array_values($days);
    }

    // ============================================================
    // Kunjungan kapal
    // ============================================================

    /**
     * Bagi total tonase satu jenis kapal ke beberapa kunjungan. Tiap kunjungan
     * menempati rentang slot berurutan dan punya kapasitas kapal yang lebih
     * besar dari tonase termuat, sehingga realisasinya realistis.
     *
     * @param  array<int, array{day: Carbon, shift: string}>  $slots
     * @return array<int, array<string, mixed>>
     */
    private function buildVisits(array $slots, string $type, float $totalTonnage, int $visitCount, int $slotsPerVisit): array
    {
        $slotCount = count($slots);

        if ($slotCount === 0 || $visitCount <= 0) {
            return [];
        }

        $ships = $type === 'kantong'
            ? [
                ['Bahtera Sukses', 'PT. NDSB', 'Tursina', 'Pontianak'],
                ['Dhana Bahari 2', 'PT. NDSB', 'Tursina', 'Banjarmasin'],
                ['Sinar Sulawesi', 'PT. Samator', 'Jetty 3', 'Makassar'],
                ['Karya Bahari 7', 'PT. Pelayaran Nusantara', 'Tursina', 'Surabaya'],
                ['Mitra Ocean', 'PT. Samator', 'Jetty 3', 'Balikpapan'],
            ]
            : [
                ['Maximus-I', 'Berkah Samudera Berjaya', 'Jetty 1', 'Luar Negeri'],
                ['Oriental Diamond', 'Samudera Indonesia', 'Jetty 2', 'Vietnam'],
                ['Pacific Talent', 'Meratus Line', 'Jetty 1', 'Filipina'],
                ['Nordic Star', 'Samudera Indonesia', 'Jetty 2', 'Thailand'],
            ];

        $visits = [];
        // Ruang antar kunjungan supaya kapal tidak selalu bersandar bersamaan.
        $stride = max(1, (int) floor($slotCount / $visitCount));

        for ($i = 0; $i < $visitCount; $i++) {
            $start = min($i * $stride + mt_rand(0, 1), max(0, $slotCount - 1));
            $length = min($slotsPerVisit, $slotCount - $start);

            if ($length <= 0) {
                continue;
            }

            [$name, $agent, $jetty, $destination] = $ships[$i % count($ships)];

            $loaded = $totalTonnage / $visitCount;
            // Realisasi 76-95%: kapasitas selalu lebih besar dari tonase termuat.
            $realization = mt_rand(76, 95) / 100;
            $capacity = round($loaded / $realization, -2);

            $visits[] = [
                'ship_name' => $name,
                'agent' => $agent,
                'jetty' => $jetty,
                'destination' => $destination,
                'capacity' => max($capacity, 500),
                'start' => $start,
                'length' => $length,
                'per_slot' => $loaded / $length,
                // Waktu sandar dikunci di slot pertama agar seluruh laporan
                // menyebut kunjungan yang sama — service memakai pasangan
                // nama kapal + waktu ini sebagai identitas kunjungan.
                'moment' => $slots[$start]['day']->copy()->setTime(mt_rand(0, 23), [0, 15, 30, 45][mt_rand(0, 3)]),
                'accumulated' => 0.0,
            ];
        }

        return $visits;
    }

    /**
     * @param  array<int, array<string, mixed>>  $visits
     * @return array<int, int>
     */
    private function activeVisitKeys(array $visits, int $slotIndex): array
    {
        $keys = [];

        foreach ($visits as $key => $visit) {
            if ($slotIndex >= $visit['start'] && $slotIndex < $visit['start'] + $visit['length']) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    // ============================================================
    // Laporan & kegiatan
    // ============================================================

    private function createReport(CarbonInterface $day, string $shift, string $group, string $nextGroup, bool $isCurrentMonth): int
    {
        // Sebagian kecil laporan sengaja dibuat H+1 supaya metrik ketepatan
        // waktu lapor tidak selalu 100%.
        $late = mt_rand(1, 100) <= 12;
        $createdAt = $day->copy()->setTime(mt_rand(8, 20), mt_rand(0, 59));

        if ($late) {
            $createdAt->addDay();
        }

        [$status, $receivedAt, $approvedAt] = $this->resolveStatus($day, $createdAt, $isCurrentMonth);

        return (int) DB::table('daily_reports')->insertGetId([
            'user_id' => $this->userByGroup[$group],
            'created_by' => $this->userByGroup[$group],
            'report_date' => $day->toDateString(),
            'shift' => $shift,
            'group_name' => $group,
            'received_by_group' => $nextGroup,
            'received_by_user_id' => $this->userByGroup[$nextGroup],
            'received_at' => $receivedAt,
            'time_range' => self::SHIFTS[$shift],
            'status' => $status,
            'approved_by' => $approvedAt ? $this->managerId : null,
            'approved_at' => $approvedAt,
            'payload' => json_encode(['source' => self::SOURCE]),
            'created_at' => $createdAt,
            'updated_at' => $approvedAt ?? $receivedAt ?? $createdAt,
        ]);
    }

    /**
     * Bulan-bulan lampau seluruhnya sudah diarsipkan. Bulan berjalan menyisakan
     * beberapa laporan yang masih menunggu tanda tangan agar dashboard punya
     * isi pada bagian "Laporan Masuk".
     *
     * @return array{0: string, 1: ?string, 2: ?string}
     */
    private function resolveStatus(CarbonInterface $day, CarbonInterface $createdAt, bool $isCurrentMonth): array
    {
        $receivedAt = $createdAt->copy()->addHours(mt_rand(1, 6));

        if (! $isCurrentMonth) {
            return ['approved', $receivedAt->toDateTimeString(), $receivedAt->copy()->addHours(mt_rand(2, 30))->toDateTimeString()];
        }

        $daysAgo = $day->diffInDays(Carbon::today());

        // Hanya dua hari terakhir yang belum tuntas, supaya daftar laporan
        // masuk tetap pendek dan terbaca.
        if ($daysAgo <= 1) {
            return mt_rand(0, 1) === 1
                ? ['acknowledged', $receivedAt->toDateTimeString(), null]
                : ['submitted', null, null];
        }

        return ['approved', $receivedAt->toDateTimeString(), $receivedAt->copy()->addHours(mt_rand(2, 20))->toDateTimeString()];
    }

    /**
     * @param  array<int, array<string, mixed>>  $visits
     */
    private function attachBagged(int $reportId, array &$visits, int $slotIndex, float $groupFactor, float $monthFactor): void
    {
        $sequence = 1;

        foreach ($this->activeVisitKeys($visits, $slotIndex) as $key) {
            $visit = &$visits[$key];

            $current = round($visit['per_slot'] * $groupFactor * $this->jitter(), 2);
            $prev = round($visit['accumulated'], 2);
            $visit['accumulated'] += $current;

            // Rasio kerusakan bergerak antar bulan: bulan sibuk sedikit lebih
            // tinggi karena bongkar-muat dikejar waktu.
            $damageRate = (0.0015 + (mt_rand(0, 90) / 100000)) * $monthFactor;

            $activityId = (int) DB::table('loading_activities')->insertGetId([
                'daily_report_id' => $reportId,
                'sequence' => $sequence++,
                'ship_name' => $visit['ship_name'],
                'agent' => $visit['agent'],
                'jetty' => $visit['jetty'],
                'destination' => $visit['destination'],
                'capacity' => $visit['capacity'],
                'wo_number' => 'WO-'.str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'cargo_type' => 'UK. Granul',
                'marking' => 'Nitrea',
                'arrival_time' => $visit['moment'],
                'operating_gang' => (string) mt_rand(1, 3),
                'tkbm_count' => mt_rand(18, 32),
                'foreman' => ['Nasir', 'Linta', 'Sahrul', 'Bahar'][mt_rand(0, 3)],
                'qty_delivery_current' => round($current * 1.04, 2),
                'qty_delivery_prev' => round($prev * 1.04, 2),
                'qty_loading_current' => $current,
                'qty_loading_prev' => $prev,
                'qty_damage_current' => round($current * $damageRate, 2),
                'qty_damage_prev' => round($prev * $damageRate, 2),
                'tally_warehouse' => ['Syamsuddin', 'Asmuni', 'Zein'][mt_rand(0, 2)],
                'driver_name' => 'Arlis, Udin, Nurdian',
                'truck_number' => 'TRL-02, TRL-05',
                'tally_ship' => 'Jefry, Zein',
                'operator_ship' => 'Wirawan',
                'forklift_ship' => 'FL-71, FL-16',
                'operator_warehouse' => 'Gudang Op',
                'forklift_warehouse' => 'FL-17',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('loading_timesheets')->insert([
                ['loading_activity_id' => $activityId, 'category' => 'delivery', 'time' => '01:00', 'activity' => 'Lanjut kirim', 'created_at' => now(), 'updated_at' => now()],
                ['loading_activity_id' => $activityId, 'category' => 'loading', 'time' => '02:30', 'activity' => 'Muat palka 2', 'created_at' => now(), 'updated_at' => now()],
            ]);

            unset($visit);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $visits
     */
    private function attachBulk(int $reportId, array &$visits, int $slotIndex, float $groupFactor): void
    {
        $sequence = 1;

        foreach ($this->activeVisitKeys($visits, $slotIndex) as $key) {
            $visit = &$visits[$key];

            $current = $visit['per_slot'] * $groupFactor * $this->jitter();
            $visit['accumulated'] += $current;

            $activityId = (int) DB::table('bulk_loading_activities')->insertGetId([
                'daily_report_id' => $reportId,
                'sequence' => $sequence++,
                'ship_name' => $visit['ship_name'],
                'agent' => $visit['agent'],
                'jetty' => $visit['jetty'],
                'destination' => $visit['destination'],
                'stevedoring' => 'PBM KSS',
                'commodity' => 'UC. Granul',
                'capacity' => $visit['capacity'],
                'berthing_time' => $visit['moment'],
                'start_loading_time' => $visit['moment']->copy()->addHours(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Tonase curah dicatat lewat kolom cob pada log kegiatan, jadi satu
            // shift dipecah menjadi beberapa entri seperti pencatatan asli.
            $logCount = mt_rand(2, 4);
            $perLog = $current / $logCount;
            $rows = [];

            for ($i = 0; $i < $logCount; $i++) {
                $rows[] = [
                    'bulk_loading_activity_id' => $activityId,
                    'datetime' => $visit['moment']->copy()->addHours(3 + $i * 2),
                    'activity' => $i === 0 ? 'Mulai muat' : 'Lanjut muat #'.($i + 1),
                    'cob' => (int) round($perLog),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('bulk_loading_logs')->insert($rows);

            unset($visit);
        }
    }

    private function attachMaterial(int $reportId, int $slotIndex, float $monthlyShare, int $slotCount, float $groupFactor): void
    {
        // Bongkar bahan baku tidak terjadi setiap shift.
        if ($slotIndex % 3 !== 0) {
            return;
        }

        $activeSlots = max(1, (int) ceil($slotCount / 3));
        $perSlot = $monthlyShare / $activeSlots;

        $activityId = (int) DB::table('material_activities')->insertGetId([
            'daily_report_id' => $reportId,
            'ship_name' => ['MV. Bongkar Jaya', 'MV. Sinar Timur', 'MV. Anugerah'][mt_rand(0, 2)],
            'agent' => 'Agen KSS',
            'capacity' => 5000,
            'ship_tally_names' => 'Budi',
            'forklift_operator_names' => 'Santoso',
            'delivery_tally_names' => 'Rudi',
            'driver_names' => 'Eko, Dwi',
            'working_hours' => '07:00 - 15:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $materials = ['Clay JB', 'Dolomite JB', 'MGO 18% 50kg', 'Limestone'];
        $weights = [0.34, 0.28, 0.20, 0.18];
        $rows = [];

        foreach ($materials as $index => $material) {
            $qty = round($perSlot * $weights[$index] * $groupFactor * $this->jitter(), 2);

            $rows[] = [
                'material_activity_id' => $activityId,
                'raw_material_type' => $material,
                'qty_current' => $qty,
                'qty_prev' => round($qty * mt_rand(0, 300) / 100, 2),
                'qty_total' => $qty,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('material_items')->insert($rows);
    }

    private function attachContainer(int $reportId, int $slotIndex, float $monthlyShare, int $slotCount, float $groupFactor): void
    {
        if ($slotIndex % 4 !== 1) {
            return;
        }

        $activeSlots = max(1, (int) ceil($slotCount / 4));
        $perSlot = $monthlyShare / $activeSlots;

        $activityId = (int) DB::table('container_activities')->insertGetId([
            'daily_report_id' => $reportId,
            'ship_name' => ['MV. Container Line', 'MV. Meratus Kalbut'][mt_rand(0, 1)],
            'agent' => 'Meratus Line',
            'capacity' => 4000,
            'ship_tally_names' => 'Tally Container',
            'gudang_tally_names' => 'Tally Gudang',
            'driver_names' => 'Eko, Dwi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemCount = mt_rand(2, 4);
        $perItem = ($perSlot * $groupFactor * $this->jitter()) / $itemCount;
        $rows = [];

        for ($i = 0; $i < $itemCount; $i++) {
            $rows[] = [
                'container_activity_id' => $activityId,
                'time' => sprintf('%02d:00', 8 + $i * 2),
                'status' => $i % 2 === 0 ? 'Full' : 'Empty',
                'qty_current' => round($perItem, 2),
                'qty_prev' => 0,
                'qty_total' => round($perItem, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('container_items')->insert($rows);
    }

    private function attachTurba(int $reportId, int $slotIndex, float $monthlyShare, int $slotCount, float $groupFactor): void
    {
        if ($slotIndex % 3 !== 2) {
            return;
        }

        $activeSlots = max(1, (int) ceil($slotCount / 3));
        $perSlot = $monthlyShare / $activeSlots;

        $activityId = (int) DB::table('turba_activities')->insertGetId([
            'daily_report_id' => $reportId,
            'tally_gudang_names' => 'Asmuni',
            'forklift_operator_names' => 'Syamsuddin',
            'driver_names' => 'Doni, Rahim',
            'working_hours' => '08:00 - 12:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deliveryCount = mt_rand(1, 3);
        $perDelivery = ($perSlot * $groupFactor * $this->jitter()) / $deliveryCount;
        $rows = [];

        for ($i = 0; $i < $deliveryCount; $i++) {
            $qty = round($perDelivery, 2);

            $rows[] = [
                'turba_activity_id' => $activityId,
                'truck_name' => ['Buffer Stok', 'Gudang Lini II', 'Distributor Samarinda'][mt_rand(0, 2)],
                'do_so_number' => (string) mt_rand(5000, 6999),
                'capacity' => round($qty * mt_rand(110, 160) / 100, 2),
                'marking_type' => 'Granul Khusus',
                'qty_current' => $qty,
                'qty_prev' => 0,
                'qty_accumulated' => $qty,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('turba_deliveries')->insert($rows);
    }

    private function attachEmployees(int $reportId, string $shift, CarbonInterface $day): void
    {
        [$timeIn, $timeOut] = match ($shift) {
            'Pagi' => ['07:00:00', '15:00:00'],
            'Sore' => ['15:00:00', '23:00:00'],
            default => ['23:00:00', '07:00:00'],
        };

        $roster = [
            'Sugianto', 'Jhon Mailoor', 'Yacob', 'Sadam Hasanuddin', 'Wirawan',
            'Jefry Kurnia', 'Syamrisal', 'Irfan Maulana', 'Supriadi', 'Fadli Rahman',
            'Reza Pratama', 'Abd. Azis', 'Rahim Saputra', 'Doni Amping', 'Zainuddin',
        ];

        shuffle($roster);
        $crewCount = mt_rand(10, 14);
        $rows = [];

        foreach (array_slice($roster, 0, $crewCount) as $name) {
            $rows[] = [
                'daily_report_id' => $reportId,
                'category' => 'shift',
                'name' => $name,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'description' => '-',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Lembur: jamnya diisi supaya kartu Beban Kerja punya total jam, bukan
        // sekadar jumlah entri.
        $overtimeCount = mt_rand(0, 3);

        for ($i = 0; $i < $overtimeCount; $i++) {
            $startHour = mt_rand(15, 22);
            $duration = mt_rand(2, 5);

            $rows[] = [
                'daily_report_id' => $reportId,
                'category' => 'operasi',
                'name' => $roster[($i + 3) % count($roster)],
                'time_in' => sprintf('%02d:00:00', $startHour),
                'time_out' => sprintf('%02d:00:00', ($startHour + $duration) % 24),
                'description' => 'Lembur',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $reliefCount = mt_rand(0, 2);

        for ($i = 0; $i < $reliefCount; $i++) {
            $rows[] = [
                'daily_report_id' => $reportId,
                'category' => 'operasi',
                'name' => $roster[($i + 7) % count($roster)],
                'time_in' => null,
                'time_out' => null,
                'description' => 'Relief',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $rows[] = [
            'daily_report_id' => $reportId,
            'category' => 'lain',
            'name' => 'All Team',
            'time_in' => $timeIn,
            'time_out' => null,
            'description' => 'Safety briefing '.$day->locale('id')->translatedFormat('d M'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('employee_logs')->insert($rows);
    }

    // ============================================================
    // Divisi lain (agar tab dashboard tidak kosong)
    // ============================================================

    private function seedMaintenanceReports(): void
    {
        $creator = User::where('username', 'kasi.pemeliharaan')->first()
            ?? User::whereHas('role', fn ($q) => $q->where('name', 'pemeliharaan'))->first();

        for ($i = 0; $i < 10; $i++) {
            $date = Carbon::today()->subDays($i * 3);
            $submitted = $i < 2;

            MaintenanceReport::create([
                'report_date' => $date->toDateString(),
                'day_name' => $date->locale('id')->translatedFormat('l'),
                'status' => $submitted ? 'submitted' : 'approved',
                'created_by' => $creator?->id,
                'submitted_at' => $date->copy()->setTime(16, 0),
                'approved_by' => $submitted ? null : $this->managerId,
                'approved_at' => $submitted ? null : $date->copy()->setTime(19, 0),
                'karu_pemeliharaan_name' => 'Sungkono [demo]',
                'karu_peralatan_name' => 'Hendra',
            ]);
        }
    }

    private function seedSafetyReports(): void
    {
        $creator = User::where('username', 'karu.safety')->first()
            ?? User::whereHas('role', fn ($q) => $q->where('name', 'safety'))->first();

        for ($i = 0; $i < 8; $i++) {
            $date = Carbon::today()->subDays($i * 4);
            $submitted = $i < 1;

            SafetyReport::create([
                'document_number' => 'DEMO-'.$date->format('Ymd').'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'report_date' => $date->toDateString(),
                'time_range' => '19:00 - 03:00',
                'shift' => 'Malam',
                'status' => $submitted ? 'submitted' : 'approved',
                'created_by' => $creator?->id,
                'submitted_at' => $date->copy()->setTime(4, 0),
                'approved_by' => $submitted ? null : $this->managerId,
                'approved_at' => $submitted ? null : $date->copy()->setTime(9, 0),
            ]);
        }
    }

    /**
     * Log aktivitas 30 hari untuk grafik dashboard admin.
     *
     * Pola sengaja dibuat menyerupai kenyataan: aktivitas ramai pada hari
     * kerja dan lengang di akhir pekan, dengan beberapa hari yang punya lonjakan
     * percobaan login gagal supaya pita "Keamanan" pada grafik tidak selalu
     * rata dan zona merahnya ikut teruji.
     */
    private function seedActivityLogs(): void
    {
        DB::table('admin_activity_logs')->where('description', 'like', '%[demo]%')->delete();

        $users = User::query()->inRandomOrder()->limit(8)->pluck('name', 'id')->all();

        if ($users === []) {
            return;
        }

        $userIds = array_keys($users);

        $templates = [
            'login' => ['Berhasil masuk ke sistem', 'Keluar dari sistem', 'Sesi diperpanjang otomatis'],
            'update' => ['Memperbarui data master unit', 'Menyunting profil pengguna', 'Mengubah pengaturan jadwal backup'],
            'create' => ['Menambahkan pengguna baru', 'Membuat data master karyawan', 'Menambahkan lokasi inspeksi K3'],
            'export' => ['Mengekspor arsip laporan ke Excel', 'Mengunduh rekap aktivitas'],
            'backup' => ['Menjalankan backup manual', 'Backup otomatis selesai'],
            'security' => ['Percobaan masuk gagal: kata sandi salah', 'Akses ditolak untuk halaman terbatas'],
            'delete' => ['Menghapus arsip laporan lama', 'Menonaktifkan akun petugas'],
        ];

        // Hari dengan lonjakan insiden keamanan, dihitung mundur dari hari ini.
        $incidentDays = [3, 11, 12, 19, 26];
        $rows = [];

        for ($dayOffset = 29; $dayOffset >= 0; $dayOffset--) {
            $date = Carbon::today()->subDays($dayOffset);
            $isWeekend = $date->isWeekend();
            $isIncident = in_array($dayOffset, $incidentDays, true);

            $volume = $isWeekend ? mt_rand(3, 9) : mt_rand(12, 26);

            for ($i = 0; $i < $volume; $i++) {
                $type = $this->pickActivityType($isIncident);
                $messages = $templates[$type];

                $rows[] = [
                    'user_id' => $userIds[array_rand($userIds)],
                    'type' => $type,
                    'description' => $messages[array_rand($messages)].' [demo]',
                    'ip_address' => '192.168.'.mt_rand(1, 4).'.'.mt_rand(2, 250),
                    'properties' => null,
                    'created_at' => $date->copy()->setTime(mt_rand(6, 22), mt_rand(0, 59), mt_rand(0, 59)),
                    'updated_at' => $date->copy()->setTime(mt_rand(6, 22), mt_rand(0, 59)),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('admin_activity_logs')->insert($chunk);
        }

        $this->command?->info(count($rows).' log aktivitas admin dibuat untuk 30 hari terakhir.');
    }

    /**
     * Bobot jenis log. Pada hari insiden, porsi kejadian keamanan dinaikkan
     * tajam sehingga terlihat sebagai lonjakan di grafik.
     */
    private function pickActivityType(bool $isIncident): string
    {
        $weights = $isIncident
            ? ['security' => 45, 'login' => 25, 'update' => 15, 'create' => 5, 'export' => 5, 'backup' => 3, 'delete' => 2]
            : ['login' => 42, 'update' => 22, 'create' => 12, 'export' => 10, 'backup' => 7, 'security' => 5, 'delete' => 2];

        $roll = mt_rand(1, array_sum($weights));
        $cursor = 0;

        foreach ($weights as $type => $weight) {
            $cursor += $weight;

            if ($roll <= $cursor) {
                return $type;
            }
        }

        return 'update';
    }

    /** Variasi kecil ±12% agar angka tidak terlihat dibuat mesin. */
    private function jitter(): float
    {
        return mt_rand(88, 112) / 100;
    }
}
