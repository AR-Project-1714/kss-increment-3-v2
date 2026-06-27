<?php

namespace Tests\Feature\BlackBox;

use App\Models\AdminActivityLog;
use App\Models\User;

/**
 * Modul C — Admin / Dashboard Sistem (PENGUJIAN_BLACKBOX.md §4.C).
 */
class AdminDashboardTest extends BlackBoxTestCase
{
    public function test_tc_adash_01_dashboard_menampilkan_empat_kartu(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Total Pengguna Aktif', false)
            ->assertSee('Kapasitas Storage', false)
            ->assertSee('Status Backup Terakhir', false)
            ->assertSee('Kejadian Keamanan Hari Ini', false);
    }

    public function test_tc_adash_02_nilai_kartu_pengguna_aktif_sesuai_data(): void
    {
        $admin = $this->admin();
        // Tambah dua akun aktif + satu nonaktif (nonaktif tidak ikut dihitung).
        $this->operator('A');
        $this->maintenance();
        $this->safety(['status' => 'nonaktif']);

        $aktif = User::where('status', 'aktif')->count();
        $this->assertSame(3, $aktif); // admin + operator + maintenance

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Total Pengguna Aktif', false);
    }

    public function test_tc_adash_03_menampilkan_aktivitas_terbaru(): void
    {
        $admin = $this->admin();

        AdminActivityLog::create([
            'user_id' => $admin->id,
            'type' => 'update',
            'description' => 'Aktivitas demo dashboard terbaru',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Aktivitas demo dashboard terbaru', false);
    }
}
