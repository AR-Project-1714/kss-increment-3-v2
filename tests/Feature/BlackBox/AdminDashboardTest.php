<?php

namespace Tests\Feature\BlackBox;

use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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
            ->assertSee('Billing Cloud', false)
            ->assertSee('Remaining Credit', false)
            ->assertSee('Estimasi Masa Aktif', false)
            ->assertSee('Status Backup Terakhir', false)
            ->assertSee('Kejadian Keamanan', false);
    }

    public function test_tc_adash_02_kartu_billing_menampilkan_saldo_dan_masa_aktif(): void
    {
        $admin = $this->admin();
        Cache::flush();
        config([
            'services.idcloudhost.api_key' => 'test-token',
            'services.idcloudhost.billing_account_id' => '12345',
            'services.idcloudhost.base_url' => 'https://api.idcloudhost.test/v1',
            'services.idcloudhost.estimated_monthly_cost' => 300000,
        ]);
        Http::fake(['*' => Http::response([
            'running_totals' => ['ongoing' => 500000],
            'is_active' => true,
        ])]);

        $response = $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Remaining Credit', false)
            ->assertSee('Rp 500.000', false)
            ->assertSee('Estimasi Masa Aktif', false);

        $cards = collect($response->original->getData()['stats']);
        $this->assertSame('Rp 500.000', $cards->firstWhere('key', 'cloud-credit')['value']);
        $this->assertNotSame('—', $cards->firstWhere('key', 'cloud-runway')['value']);
    }

    public function test_tc_adash_04_kartu_billing_menampilkan_fallback_yang_jujur(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Tambahkan API key dan Billing Account ID', false)
            ->assertSee('Tidak tersedia', false);
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
