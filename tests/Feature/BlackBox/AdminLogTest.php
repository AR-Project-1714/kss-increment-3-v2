<?php

namespace Tests\Feature\BlackBox;

use App\Models\AdminActivityLog;

/**
 * Modul E — Admin / Log Aktivitas (PENGUJIAN_BLACKBOX.md §4.E).
 */
class AdminLogTest extends BlackBoxTestCase
{
    public function test_tc_alog_01_membuka_log_aktivitas(): void
    {
        $admin = $this->admin();

        AdminActivityLog::create([
            'user_id' => $admin->id,
            'type' => 'update',
            'description' => 'Entri log pertama',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.log'))
            ->assertOk()
            ->assertSee('Entri log pertama', false);
    }

    public function test_tc_alog_02_pencarian_log_menyaring_sesuai_kata_kunci(): void
    {
        $admin = $this->admin();

        AdminActivityLog::create(['user_id' => $admin->id, 'type' => 'update', 'description' => 'Aktivitas KATAKUNCIUNIK']);
        AdminActivityLog::create(['user_id' => $admin->id, 'type' => 'update', 'description' => 'Aktivitas lain biasa']);

        $this->actingAs($admin)
            ->get(route('admin.log', ['q' => 'KATAKUNCIUNIK']))
            ->assertOk()
            ->assertSee('KATAKUNCIUNIK', false)
            ->assertDontSee('Aktivitas lain biasa', false);
    }

    public function test_tc_alog_03_filter_jenis_log(): void
    {
        $admin = $this->admin();

        AdminActivityLog::create(['user_id' => $admin->id, 'type' => 'security', 'description' => 'Kejadian keamanan tersaring']);
        AdminActivityLog::create(['user_id' => $admin->id, 'type' => 'delete', 'description' => 'Penghapusan biasa tersembunyi']);

        $this->actingAs($admin)
            ->get(route('admin.log', ['type' => 'security']))
            ->assertOk()
            ->assertSee('Kejadian keamanan tersaring', false)
            ->assertDontSee('Penghapusan biasa tersembunyi', false);
    }

    public function test_tc_alog_04_tindakan_admin_tercatat_otomatis(): void
    {
        $admin = $this->admin();

        // Tindakan: membuat tiket bantuan -> harus tercatat di log.
        $this->actingAs($admin)->post(route('admin.help.ticket'), [
            'category' => 'Akun & Role',
            'priority' => 'Normal',
            'title' => 'Tiket pemicu log',
            'description' => 'Memastikan aktivitas tercatat otomatis.',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_activity_logs', [
            'type' => 'support',
            'description' => 'Membuat tiket bantuan: Tiket pemicu log',
        ]);
    }

    public function test_tc_alog_05_login_gagal_tercatat_sebagai_security(): void
    {
        $admin = $this->admin();

        $this->from(route('login.index'))->post(route('login.authenticate'), [
            'username' => 'akun-hantu',
            'password' => 'salah',
        ]);

        $this->assertDatabaseHas('admin_activity_logs', ['type' => 'security']);

        $this->actingAs($admin)
            ->get(route('admin.log', ['type' => 'security']))
            ->assertOk();
    }
}
