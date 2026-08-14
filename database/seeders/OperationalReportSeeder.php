<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\MasterEmployee;
use App\Models\MasterEnvironmentItem;
use App\Models\MasterInventoryItem;
use App\Models\MasterUnit;
use App\Models\ShipOperation;
use App\Models\User;
use App\Services\BulkTonnageService;
use App\Support\MaterialPackaging;
use App\Support\ShipNameNormalizer;
use Carbon\Carbon;
use Database\Seeders\Concerns\GuardsSampleData;
use Illuminate\Database\Seeder;

/**
 * Data contoh laporan operasi dari Mei 2026 sampai hari ini.
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

    private const AMMONIA_SHIPS = [
        ['MT. Gas Indonesia', 'PT. Pertamina Trans Kontinental', 'Jetty 1', 'Makassar', 5200],
        ['MT. Sinar Amoniak', 'PT. Samudera Energi', 'Jetty 2', 'Balikpapan', 4800],
        ['MT. Celebes Gas', 'PT. Pelayaran Bahari', 'Jetty 1', 'Bontang', 5500],
        ['MT. Energi Timur', 'PT. Lintas Samudera', 'Jetty 2', 'Surabaya', 5000],
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
        $end = Carbon::today()->startOfDay();

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
                $isLatest = $day->isSameDay($end) && $shiftName === 'Malam';

                // Skenario uji handover: laporan terbaru selalu dikirim oleh
                // Grup B ke Grup A, agar akun Grup A dapat meneruskan data
                // kapal yang sedang berjalan pada shift berikutnya.
                if ($isLatest) {
                    $group = 'B';
                    $nextGroup = 'A';
                }

                $slotData = [$day->copy(), $shiftName, $group, $nextGroup, $slot, $isLatest];

                $slots[] = $slotData;
                $expectedReports[$this->reportKey($day, $shiftName, $group)] = true;
                $slot++;
            }
        }

        $this->pruneObsoleteSampleReports($expectedReports);

        foreach ($slots as [$day, $shiftName, $group, $nextGroup, $slotNumber, $isLatest]) {
            $this->seedSlot($day, $shiftName, $group, $nextGroup, $slotNumber, $isLatest);
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

    private function seedSlot(
        Carbon $day,
        string $shiftName,
        string $group,
        string $nextGroup,
        int $slot,
        bool $isLatest
    ): void {
        $creator = $this->usersByUsername['karu.'.strtolower($group)] ?? null;
        $receiver = $this->usersByUsername['karu.'.strtolower($nextGroup)] ?? null;
        $approver = $this->usersByUsername['manajer'] ?? null;
        $submittedAt = $this->submissionMoment($day, $shiftName, $slot);
        // Laporan sebelum laporan terakhir sudah berada di arsip (approved).
        // Hanya handover terbaru yang masih terkirim agar terlihat di inbox
        // Grup A dan bisa dipakai menguji sinkronisasi antar-shift.
        $status = $isLatest ? 'submitted' : 'approved';
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
                    'period' => 'Mei 2026 - '.$day->copy()->endOfDay()->locale('id')->translatedFormat('d F Y'),
                    'scenario' => $isLatest
                        ? 'Handover kapal antar-shift: Grup B ke Grup A'
                        : 'Perbandingan kegiatan dan kinerja operasional (arsip)',
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

        if ($isLatest || ($dayIndex + $shiftIndex) % 3 !== 2) {
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

        // Jalankan setelah seluruh kegiatan kapal dibuat. Pada laporan terbaru,
        // kegiatan pertama tiap jenis diganti menjadi kapal handover tanpa
        // menambah tab ketiga atau menciptakan tonase ganda.
        if ($isLatest) {
            $this->seedLatestInterShiftShips($report, $day, $creator);
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
        $activityCount = 2;
        $voyageLength = 6;
        $voyageIndex = intdiv($slot, $voyageLength);
        $voyageStartSlot = $voyageIndex * $voyageLength;
        $shares = [1 => 0.57, 2 => 0.43];

        for ($sequence = 1; $sequence <= $activityCount; $sequence++) {
            $shipIndex = $voyageIndex * $activityCount + $sequence - 1;
            [$name, $agent, $jetty, $destination, $capacity] = self::BAGGED_SHIPS[$shipIndex % count(self::BAGGED_SHIPS)];
            $share = $shares[$sequence];
            $current = (int) round($this->baggedQuantity($slot, $factor) * $share);
            $previous = 0;

            for ($previousSlot = $voyageStartSlot; $previousSlot < $slot; $previousSlot++) {
                $previousDay = Carbon::parse(self::PERIOD_START)->addDays(intdiv($previousSlot, count(self::SHIFTS)));
                $previousShift = array_keys(self::SHIFTS)[$previousSlot % count(self::SHIFTS)];
                $previousGroup = self::GROUPS[$previousSlot % count(self::GROUPS)];
                $previous += (int) round($this->baggedQuantity(
                    $previousSlot,
                    $this->productivityFactor($previousDay, $previousGroup, $previousShift, $previousSlot)
                ) * $share);
            }

            $damage = ($day->month === 6
                ? 0.75 + ($slot % 4) * 0.18
                : 0.22 + ($slot % 3) * 0.11) * $share;

            $activity = $report->loadingActivities()->create([
                'sequence' => $sequence,
                'ship_name' => $name,
                'ship_name_key' => ShipNameNormalizer::key($name) ?: null,
                'agent' => $agent,
                'jetty' => $jetty,
                'destination' => $destination,
                'capacity' => $capacity,
                'wo_number' => 'WO-KTG-2026-'.str_pad((string) ($shipIndex + 1), 4, '0', STR_PAD_LEFT),
                'cargo_type' => 'Urea Granul',
                'marking' => $shipIndex % 2 === 0 ? 'Nitrea' : 'Pupuk Indonesia',
                'arrival_time' => $this->slotMoment($voyageStartSlot, -90 + $sequence * 15),
                'operating_gang' => (string) (1 + $shipIndex % 3),
                'tkbm_count' => 20 + (($slot + $sequence) % 8),
                'foreman' => ['Nasir', 'Linta', 'Sahrul', 'Bahar'][($slot + $sequence - 1) % 4],
                'qty_delivery_current' => $current + 9,
                'qty_delivery_prev' => $previous + 20,
                'qty_loading_current' => $current,
                'qty_loading_prev' => $previous,
                'qty_damage_current' => round($damage, 2),
                'qty_damage_prev' => round(($slot - $voyageStartSlot) * $damage, 2),
                'tally_warehouse' => ['Syamsuddin', 'Asmuni', 'Zein'][($slot + $sequence) % 3],
                'driver_name' => $sequence === 1 ? 'Arlis, Udin' : 'Nurdian, Samsul',
                'truck_number' => $sequence === 1 ? 'TRL-02, TRL-05' : 'TRL-01, TRL-04',
                'tally_ship' => $sequence === 1 ? 'Jefry, Zein' : 'Mustafa, Rudi',
                'operator_ship' => $sequence === 1 ? 'Wirawan' : 'Santoso',
                'forklift_ship' => $sequence === 1 ? 'FL-71, FL-16' : 'FL-72, FL-18',
                'operator_warehouse' => 'Tim Gudang Produk '.($sequence === 1 ? 'A' : 'B'),
                'forklift_warehouse' => $sequence === 1 ? 'FL-17' : 'FL-19',
            ]);

            $activity->timesheets()->createMany([
                [
                    'category' => 'delivery',
                    'time' => $this->shiftMoment($day, $shiftName, 20 + $sequence * 20)->format('H:i'),
                    'activity' => 'Distribusi kantong dari gudang ke dermaga sesuai WO kegiatan '.$sequence,
                ],
                [
                    'category' => 'loading',
                    'time' => $this->shiftMoment($day, $shiftName, 95 + $sequence * 25)->format('H:i'),
                    'activity' => 'Pemuatan palka dan verifikasi tally kapal kegiatan '.$sequence,
                ],
            ]);
        }
    }

    /**
     * Kapal uji yang sengaja dibiarkan aktif pada laporan terakhir. Ketika
     * Grup A membuka form shift berikutnya, ketiganya muncul sebagai saran
     * operasi berjalan di Muat Kantong, Muat Curah, dan Muat Amoniak.
     */
    private function seedLatestInterShiftShips(DailyReport $report, Carbon $day, ?User $creator): void
    {
        foreach ([
            [
                'sequence' => 1,
                'name' => 'KM. Sinkronisasi Antar Shift',
                'wo' => 'WO-SINKRON-'.$day->format('Ymd'),
                'jetty' => 'Jetty 3',
                'destination' => 'Makassar',
                'capacity' => 3200,
                'marking' => 'Nitrea',
                'arrival' => $day->copy()->setTime(20, 30),
            ],
            [
                'sequence' => 2,
                'name' => 'KM. Kesinambungan Kegiatan 2',
                'wo' => 'WO-SINKRON-2-'.$day->format('Ymd'),
                'jetty' => 'Tursina',
                'destination' => 'Banjarmasin',
                'capacity' => 2800,
                'marking' => 'Pupuk Indonesia',
                'arrival' => $day->copy()->setTime(21, 15),
            ],
        ] as $ship) {
            $activity = $report->loadingActivities()
                ->where('sequence', $ship['sequence'])
                ->first();

            if (! $activity) {
                continue;
            }

            $operation = $this->upsertHandoverOperation(
                ShipOperation::TYPE_BAG_LOADING,
                $ship['name'],
                $ship['wo'],
                [
                    'agent' => 'PT. Pelayaran Uji KSS',
                    'jetty' => $ship['jetty'],
                    'destination' => $ship['destination'],
                    'capacity' => $ship['capacity'],
                    'cargo_type' => 'Urea Granul',
                    'marking' => $ship['marking'],
                    'arrival_time' => $ship['arrival'],
                ],
                $report,
                $creator
            );

            $activity->update([
                'ship_operation_id' => $operation->id,
                'ship_name' => $operation->ship_name,
                'ship_name_key' => ShipNameNormalizer::key($operation->ship_name),
                'agent' => $operation->agent,
                'jetty' => $operation->jetty,
                'destination' => $operation->destination,
                'capacity' => $operation->capacity,
                'wo_number' => $operation->wo_number,
                'cargo_type' => $operation->cargo_type,
                'marking' => $operation->marking,
                'arrival_time' => $operation->arrival_time,
            ]);
        }

        foreach ([
            [
                'type' => ShipOperation::TYPE_BULK_LOADING,
                'sequence' => 1,
                'name' => 'MV. Curah Kesinambungan',
                'capacity' => 15000,
                'commodity' => 'Urea Curah Granul',
                'berthing' => $day->copy()->setTime(19, 45),
                'cob' => [8450, 8725],
            ],
            [
                'type' => ShipOperation::TYPE_BULK_LOADING,
                'sequence' => 2,
                'name' => 'MV. Curah Kesinambungan II',
                'capacity' => 9000,
                'commodity' => 'Urea Curah Granul',
                'berthing' => $day->copy()->setTime(20, 15),
                'cob' => [4125, 4380],
            ],
            [
                'type' => ShipOperation::TYPE_AMMONIA_LOADING,
                'sequence' => 1,
                'name' => 'MT. Amoniak Kesinambungan',
                'capacity' => 5000,
                'commodity' => 'Amoniak Cair',
                'berthing' => $day->copy()->setTime(21, 0),
                'cob' => [2180.5, 2315.75],
            ],
            [
                'type' => ShipOperation::TYPE_AMMONIA_LOADING,
                'sequence' => 2,
                'name' => 'MT. Amoniak Kesinambungan II',
                'capacity' => 4800,
                'commodity' => 'Amoniak Cair',
                'berthing' => $day->copy()->setTime(21, 30),
                'cob' => [1650.25, 1788.5],
            ],
        ] as $ship) {
            $bulkOperation = $this->upsertHandoverOperation(
                $ship['type'],
                $ship['name'],
                null,
                [
                    'agent' => 'PT. Pelayaran Uji KSS',
                    'jetty' => $ship['type'] === ShipOperation::TYPE_BULK_LOADING ? 'Jetty 1' : 'Jetty 2',
                    'destination' => 'Makassar',
                    'capacity' => $ship['capacity'],
                    'stevedoring' => 'PBM KSS',
                    'commodity' => $ship['commodity'],
                    'berthing_time' => $ship['berthing'],
                    'start_loading_time' => $ship['berthing']->copy()->addHours(1),
                ],
                $report,
                $creator
            );

            $activity = $report->bulkLoadingActivities()
                ->where('activity_type', $ship['type'])
                ->where('sequence', $ship['sequence'])
                ->first();

            if (! $activity) {
                continue;
            }

            $activity->update([
                'ship_operation_id' => $bulkOperation->id,
                'ship_name' => $bulkOperation->ship_name,
                'ship_name_key' => ShipNameNormalizer::key($bulkOperation->ship_name),
                'agent' => $bulkOperation->agent,
                'jetty' => $bulkOperation->jetty,
                'destination' => $bulkOperation->destination,
                'stevedoring' => $bulkOperation->stevedoring,
                'commodity' => $bulkOperation->commodity,
                'capacity' => $bulkOperation->capacity,
                'berthing_time' => $bulkOperation->berthing_time,
                'start_loading_time' => $bulkOperation->start_loading_time,
            ]);

            $activity->logs()->delete();
            $activity->logs()->createMany([
                [
                    'datetime' => $ship['berthing']->copy()->addHours(1),
                    'activity' => 'Pemuatan awal untuk diteruskan regu berikutnya',
                    'cob' => $ship['cob'][0],
                ],
                [
                    'datetime' => $ship['berthing']->copy()->addHours(2),
                    'activity' => 'Pemuatan berjalan dan pencatatan COB handover',
                    'cob' => $ship['cob'][1],
                ],
            ]);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function upsertHandoverOperation(
        string $type,
        string $shipName,
        ?string $woNumber,
        array $attributes,
        DailyReport $report,
        ?User $creator
    ): ShipOperation {
        $operation = ShipOperation::updateOrCreate(
            [
                'type' => $type,
                'ship_name_key' => ShipNameNormalizer::key($shipName),
                'wo_number' => $woNumber,
            ],
            array_merge($attributes, [
                'status' => ShipOperation::STATUS_ACTIVE,
                'ship_name' => $shipName,
                'ship_name_key' => ShipNameNormalizer::key($shipName),
                'wo_number' => $woNumber,
                'started_at' => $attributes['arrival_time'] ?? $attributes['berthing_time'] ?? now(),
                'created_by' => $creator?->id,
                'last_report_id' => $report->id,
                'last_report_date' => $report->report_date,
            ])
        );

        $report->operationDecisions()->updateOrCreate(
            ['ship_operation_id' => $operation->id],
            [
                'status' => ShipOperation::STATUS_ACTIVE,
                'decided_by' => $creator?->id,
                'decided_at' => $report->updated_at ?? now(),
            ],
        );

        return $operation;
    }

    private function seedBulk(
        DailyReport $report,
        int $slot,
        Carbon $day,
        string $shiftName,
        float $factor
    ): void {
        $activityCount = 2;
        $voyageLength = 12;
        $voyageIndex = intdiv($slot, $voyageLength);
        $voyageStartSlot = $voyageIndex * $voyageLength;

        foreach ([
            [ShipOperation::TYPE_BULK_LOADING, self::BULK_SHIPS, 610, 24, 'Urea Curah Granul'],
            [ShipOperation::TYPE_AMMONIA_LOADING, self::AMMONIA_SHIPS, 260, 12, 'Amoniak Cair'],
        ] as $typeIndex => [$activityType, $ships, $baseQuantity, $variation, $commodity]) {
            foreach ([1 => 0.56, 2 => 0.44] as $sequence => $share) {
                $shipIndex = $voyageIndex * $activityCount + $sequence - 1;
                [$name, $agent, $jetty, $destination, $capacity] = $ships[$shipIndex % count($ships)];
                $loaded = round(($baseQuantity + ($slot % 5) * $variation) * $factor * $share, 2);
                $voyageKey = $activityType.'|'.$voyageIndex.'|'.$sequence;

                // COB dicatat kumulatif per kapal/pelayaran. Masing-masing tab
                // mempunyai kunci sendiri agar Kegiatan 2 tidak melanjutkan COB
                // milik Kegiatan 1.
                $carried = $this->bulkVoyageCob[$voyageKey] ?? 0;
                $firstLog = round($carried + $loaded * 0.46, 2);
                $secondLog = round($carried + $loaded, 2);
                $this->bulkVoyageCob[$voyageKey] = $secondLog;

                $berthing = $this->slotMoment(
                    $voyageStartSlot,
                    -210 + $typeIndex * 45 + $sequence * 20
                );
                $startLoading = $berthing->copy()->addHours(3 + (($voyageIndex + $sequence) % 3));

                $activity = $report->bulkLoadingActivities()->create([
                    'activity_type' => $activityType,
                    'sequence' => $sequence,
                    'ship_name' => $name,
                    'ship_name_key' => ShipNameNormalizer::key($name) ?: null,
                    'agent' => $agent,
                    'jetty' => $jetty,
                    'destination' => $destination,
                    'stevedoring' => 'PBM KSS Tim '.$sequence,
                    'commodity' => $commodity,
                    'capacity' => $capacity,
                    'berthing_time' => $berthing,
                    'start_loading_time' => $startLoading,
                ]);

                $activity->logs()->createMany([
                    [
                        'datetime' => $this->shiftMoment($day, $shiftName, 35 + $sequence * 20),
                        'activity' => 'Pemuatan awal dan pemeriksaan kapal kegiatan '.$sequence,
                        'cob' => $firstLog,
                    ],
                    [
                        'datetime' => $this->shiftMoment($day, $shiftName, 180 + $sequence * 25),
                        'activity' => 'Pemuatan lanjutan dan pencatatan COB kegiatan '.$sequence,
                        'cob' => $secondLog,
                    ],
                ]);
            }
        }
    }

    private function seedMaterial(DailyReport $report, int $slot, float $factor): void
    {
        $activityCount = 2;

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
            $packages = MaterialPackaging::active();

            foreach (self::RAW_MATERIALS as $index => $type) {
                // Contoh data memakai seluruh kemasan katalog secara bergiliran.
                // Tonasenya dipertahankan setara data lama, lalu dibalik menjadi
                // jumlah Bag sesuai kemasannya supaya angka pada dasbor tetap
                // masuk akal untuk semua ukuran kemasan.
                $package = $packages[$index % count($packages)];
                $tonnage = (95 + $index * 27 + ($slot % 4) * 8) * $factor / $activityCount;
                $current = (int) round($tonnage / $package['tonPerBag']);
                $previous = (int) round($current * (1.5 + (($slot + $index) % 3)));

                $items[] = [
                    'raw_material_type' => $type,
                    'packaging_type' => $package['label'],
                    'packaging_code' => $package['code'],
                    'packaging_factor' => $package['tonPerBag'],
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
        $activityCount = 2;
        $voyageIndex = intdiv($slot, 8);

        foreach ([1 => 0.55, 2 => 0.45] as $sequence => $share) {
            $shipIndex = $voyageIndex * $activityCount + $sequence - 1;
            [$name, $agent, $jetty, $emptyCapacity, $fullCapacity] = self::CONTAINER_SHIPS[
                $shipIndex % count(self::CONTAINER_SHIPS)
            ];
            $emptyCurrent = min($emptyCapacity, (int) round((27 + $slot % 8) * $factor * $share));
            $fullCurrent = min($fullCapacity, (int) round((16 + $slot % 6) * $factor * $share));
            $emptyPrevious = (int) round(($slot % 3) * 9 * $share);
            $fullPrevious = (int) round(($slot % 2) * 7 * $share);

            $container = $report->containerActivity()->create([
                'sequence' => $sequence,
                'ship_name' => $name,
                'ship_name_key' => ShipNameNormalizer::key($name) ?: null,
                'agent' => $agent,
                'jetty' => $jetty,
                'capacity' => $emptyCapacity,
                'capacity_empty' => $emptyCapacity,
                'capacity_full' => $fullCapacity,
                'ship_tally_names' => ['Asri Sahibu', 'Mustafa', 'Zein'][($slot + $sequence) % 3],
                'gudang_tally_names' => ['Mustafa', 'Jefry', 'Asmuni'][($slot + $sequence - 1) % 3],
                'driver_names' => $sequence === 1 ? 'Samsul Zainuddin, Arlis' : 'Udin, Nurdian',
                'truck_number' => $sequence === 1 ? 'TRL-01, TRL-03' : 'TRL-02, TRL-05',
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
