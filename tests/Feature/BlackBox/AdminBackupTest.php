<?php

namespace Tests\Feature\BlackBox;

use App\Models\DailyReport;
use Illuminate\Support\Facades\Storage;

/**
 * Modul H — Admin / Manajemen Backup (PENGUJIAN_BLACKBOX.md §4.H).
 */
class AdminBackupTest extends BlackBoxTestCase
{
    public function test_tc_abck_01_backup_manual_membuat_berkas(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.backup.generate'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Backup manual berhasil dibuat.');

        $this->assertNotEmpty(Storage::disk('local')->files('admin-backups'));
    }

    public function test_tc_abck_02_atur_jadwal_backup(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.backup.schedule'), [
                'frequency' => 'Mingguan',
                'time' => '03:30',
                'retention' => '60 Hari',
                'target' => 'Local Storage',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Jadwal backup berhasil diperbarui.');

        $this->assertTrue(Storage::disk('local')->exists('admin-backups/schedule.json'));
    }

    public function test_tc_abck_03_unduh_berkas_backup(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.backup.generate'))->assertRedirect();

        $file = basename(collect(Storage::disk('local')->files('admin-backups'))->first());

        $this->actingAs($admin)
            ->get(route('admin.backup.download', $file))
            ->assertOk();
    }

    public function test_tc_abck_04_hapus_berkas_backup(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.backup.generate'))->assertRedirect();
        $file = basename(collect(Storage::disk('local')->files('admin-backups'))->first());

        $this->actingAs($admin)
            ->delete(route('admin.backup.destroy', $file))
            ->assertRedirect()
            ->assertSessionHas('success', 'File backup berhasil dihapus.');

        $this->assertFalse(Storage::disk('local')->exists('admin-backups/'.$file));
    }

    public function test_tc_abck_05_restore_tidak_dijalankan_otomatis_dan_dicatat(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.backup.generate'))->assertRedirect();
        $file = basename(collect(Storage::disk('local')->files('admin-backups'))->first());

        $this->actingAs($admin)
            ->post(route('admin.backup.restore', $file))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('admin_activity_logs', [
            'type' => 'backup',
        ]);
        $this->assertStringContainsString('Restore tidak dijalankan otomatis', session('error'));
    }

    public function test_tc_abck_06_backup_tahunan_tidak_tersedia_tanpa_laporan_tahun_lalu(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.backup.annual'))
            ->assertSessionHas('error');

        $this->assertStringContainsString('Backup tahunan belum tersedia', session('error'));
    }

    public function test_tc_abck_07_backup_tahunan_mengarsipkan_laporan_tahun_lalu(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('Ekstensi ZIP tidak tersedia di lingkungan ini.');
        }

        Storage::fake('local');
        $admin = $this->admin();
        $operator = $this->operator('A');

        $lastYear = (int) now()->subYear()->year;
        $report = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => $lastYear.'-03-15',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.backup.annual'))
            ->assertSessionHas('success');

        // Laporan tahun lalu diarsipkan ke ZIP lalu dihapus dari sistem.
        $this->assertDatabaseMissing('daily_reports', ['id' => $report->id]);
        $this->assertTrue(
            Storage::disk('local')->exists('admin-backups/Laporan_Harian_KSS_Tahun_'.$lastYear.'.zip')
        );
    }

    public function test_tc_abck_08_kapasitas_storage_ditampilkan(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.backup'))
            ->assertOk()
            ->assertSee('Storage', false);
    }
}
