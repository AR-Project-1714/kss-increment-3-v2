<?php

namespace Tests\Feature;

use App\Models\BulkLoadingActivity;
use App\Models\BulkLoadingLog;
use App\Models\DailyReport;
use App\Models\ShipOperation;
use Carbon\Carbon;
use Database\Seeders\BackupOperationalReportSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackupOperationalReportSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_memutar_ulang_laporan_juli_beserta_cacat_datanya(): void
    {
        $this->seedBackup();

        $this->assertSame(66, DailyReport::count());

        // Status dikembalikan seperti pada backup: 65 sudah disetujui dan satu
        // masih draft. Bukan disamaratakan, karena statistik manajer memang
        // tidak menghitung draft.
        $this->assertSame(1, DailyReport::where('status', 'draft')->count());
        $this->assertSame(65, DailyReport::where('status', 'approved')->count());

        $this->assertSame('2026-07-08', Carbon::parse(DailyReport::min('report_date'))->toDateString());
        $this->assertSame('2026-07-29', Carbon::parse(DailyReport::max('report_date'))->toDateString());
    }

    public function test_penamaan_kapal_di_lapangan_memang_tidak_konsisten(): void
    {
        $this->seedBackup();

        $names = BulkLoadingActivity::query()
            ->whereNotNull('ship_name')
            ->distinct()
            ->orderBy('ship_name')
            ->pluck('ship_name')
            ->all();

        // Enam kapal fisik, sembilan ejaan. Inilah cacat yang dilaporkan
        // manajer operasi dan yang diperbaiki oleh normalisasi identitas kapal.
        $this->assertContains('KM. Golden Rejeki', $names);
        $this->assertContains('KM. GOLDEN REJEKI', $names);
        $this->assertContains('KM. Malacca Strait', $names);
        $this->assertContains('Km.Malacca Strait', $names);
        $this->assertContains('KM. Noah Asyera', $names);
        $this->assertContains('KM.NOAH ASYERA', $names);
    }

    /**
     * Angka yang dikeluhkan manajer operasi, direproduksi dan diperbaiki dari
     * data nyata Juli 2026.
     */
    public function test_tonase_muat_curah_kembali_masuk_akal(): void
    {
        $this->seedBackup();

        $counted = ['submitted', 'acknowledged', 'approved'];

        $lama = (float) DB::table('bulk_loading_logs')
            ->join('bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', '=', 'bulk_loading_activities.id')
            ->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id')
            ->whereIn('daily_reports.status', $counted)
            ->sum('bulk_loading_logs.cob');

        $baru = (float) DB::table('bulk_loading_logs')
            ->join('bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', '=', 'bulk_loading_activities.id')
            ->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id')
            ->whereIn('daily_reports.status', $counted)
            ->sum('bulk_loading_logs.cob_delta');

        // Menjumlahkan pembacaan COB memberi angka belasan kali lipat…
        $this->assertGreaterThan(800_000, $lama);

        // …sedangkan selisih per pelayaran memberi angka yang wajar.
        $this->assertEqualsWithDelta(66_330.25, $baru, 0.01);

        // Setiap pelayaran harus berhenti di sekitar kapasitas kapalnya. Inilah
        // uji kewarasan yang dulu tidak pernah terpenuhi.
        $perVoyage = DB::table('bulk_loading_logs')
            ->join('bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', '=', 'bulk_loading_activities.id')
            ->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id')
            ->whereIn('daily_reports.status', $counted)
            ->groupBy('bulk_loading_activities.ship_operation_id')
            ->select([
                DB::raw('MAX(bulk_loading_activities.ship_name) as ship_name'),
                DB::raw('MAX(bulk_loading_activities.capacity) as capacity'),
                DB::raw('SUM(bulk_loading_logs.cob_delta) as tonnage'),
            ])
            ->get();

        foreach ($perVoyage as $voyage) {
            $this->assertLessThanOrEqual(
                (float) $voyage->capacity * 1.15,
                (float) $voyage->tonnage,
                'Tonase '.$voyage->ship_name.' melampaui kapasitas kapalnya.',
            );
        }
    }

    /**
     * Enam kapal fisik, sembilan ejaan — tetapi hanya enam pelayaran.
     */
    public function test_pelayaran_yang_sama_tidak_terpecah_oleh_ejaan(): void
    {
        $this->seedBackup();

        $operations = ShipOperation::query()
            ->whereIn('type', [ShipOperation::TYPE_BULK_LOADING, ShipOperation::TYPE_AMMONIA_LOADING])
            ->get();

        $this->assertCount(6, $operations);
        $this->assertSame(
            0,
            BulkLoadingActivity::whereNull('ship_operation_id')->count(),
            'Setiap aktivitas muat curah harus terikat pada satu pelayaran.',
        );

        $golden = $operations->firstWhere('ship_name_key', 'GOLDEN REJEKI');
        $this->assertNotNull($golden);
        $this->assertSame(
            23,
            BulkLoadingActivity::where('ship_operation_id', $golden->id)->count(),
            'Dua puluh tiga shift KM. Golden Rejeki — dengan dua ejaan berbeda — adalah satu pelayaran.',
        );
    }

    /**
     * Perintah perbaikan boleh dijalankan berkali-kali tanpa mengubah angka.
     */
    public function test_perintah_perbaikan_aman_dijalankan_ulang(): void
    {
        $this->seedBackup();

        $before = (float) DB::table('bulk_loading_logs')->sum('cob_delta');

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();
        $afterFirst = (float) DB::table('bulk_loading_logs')->sum('cob_delta');

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();
        $afterSecond = (float) DB::table('bulk_loading_logs')->sum('cob_delta');

        $this->assertEqualsWithDelta($before, $afterFirst, 0.01);
        $this->assertEqualsWithDelta($afterFirst, $afterSecond, 0.01);
    }

    public function test_seeder_idempoten(): void
    {
        $this->seedBackup();

        $reports = DailyReport::count();
        $activities = BulkLoadingActivity::count();
        $logs = BulkLoadingLog::count();

        $this->seed(BackupOperationalReportSeeder::class);

        $this->assertSame($reports, DailyReport::count());
        $this->assertSame($activities, BulkLoadingActivity::count());
        $this->assertSame($logs, BulkLoadingLog::count());
    }

    private function seedBackup(): void
    {
        Carbon::setTestNow('2026-07-30 02:00:00');

        $this->seed(RoleSeeder::class);

        $roles = DB::table('roles')->pluck('id', 'name');

        $usernames = [
            'admin' => 'admin', 'manajer' => 'manajer',
            'karu.a' => 'operasional', 'wakaru.a' => 'operasional',
            'karu.b' => 'operasional', 'wakaru.b' => 'operasional',
            'karu.c' => 'operasional', 'wakaru.c' => 'operasional',
            'karu.d' => 'operasional', 'wakaru.d' => 'operasional',
        ];

        foreach ($usernames as $username => $role) {
            DB::table('users')->insert([
                'name' => $username,
                'username' => $username,
                'email' => $username.'@example.test',
                'password' => bcrypt('password'),
                'role_id' => $roles[$role] ?? null,
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->seed(BackupOperationalReportSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
