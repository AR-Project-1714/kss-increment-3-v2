<?php

namespace Tests\Feature\BlackBox;

use App\Models\AdminActivityLog;
use App\Models\SystemMetricSnapshot;
use App\Models\User;
use Carbon\Carbon;

/**
 * Modul C — Admin / Dashboard Sistem (PENGUJIAN_BLACKBOX.md §4.C).
 */
class AdminDashboardTest extends BlackBoxTestCase
{
    public function test_tc_adash_01_dashboard_menampilkan_empat_kartu(): void
    {
        $admin = $this->admin();

        // Status backup bukan angka dan tidak punya pembanding, jadi ia tidak
        // lagi menempati kartu KPI — informasinya pindah ke keterangan di
        // bawah grafik aktivitas, tetapi tetap harus terbaca di halaman.
        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Pengguna Aktif', false)
            ->assertSee('Storage Terpakai', false)
            ->assertSee('Status Backup Terakhir', false)
            ->assertSee('Kejadian Keamanan', false);
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

        $response = $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Pengguna Aktif', false);

        // Angkanya diperiksa dari data yang dikirim ke view, bukan dari
        // potongan HTML — supaya penataan ulang markup kartu tidak membuat
        // pengujian ini gagal tanpa ada perilaku yang benar-benar berubah.
        $card = collect($response->original->getData()['stats'])->firstWhere('key', 'users');

        $this->assertSame((string) $aktif, $card['value']);
    }

    public function test_tc_adash_04_kartu_menampilkan_pembanding_periode(): void
    {
        $admin = $this->admin();

        // Tanpa rekaman harian, kartu kumulatif harus mengakui bahwa
        // pembandingnya belum ada — bukan menampilkan angka palsu.
        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Belum ada riwayat', false);

        SystemMetricSnapshot::create([
            'captured_on' => Carbon::today()->subDays(10)->toDateString(),
            'storage_used_bytes' => 1024 * 1024,
            'active_users' => 1,
            'total_users' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee('Belum ada riwayat', false);
    }

    public function test_tc_adash_05_grafik_aktivitas_mengelompokkan_jenis_kejadian(): void
    {
        $admin = $this->admin();

        AdminActivityLog::create(['user_id' => $admin->id, 'type' => 'login', 'description' => 'Masuk sistem']);
        AdminActivityLog::create(['user_id' => $admin->id, 'type' => 'security', 'description' => 'Percobaan gagal']);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Aktivitas Sistem', false)
            ->assertSee('Perubahan Data', false)
            ->assertSee('Keamanan', false);
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
