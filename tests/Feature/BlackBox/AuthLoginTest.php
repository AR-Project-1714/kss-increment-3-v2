<?php

namespace Tests\Feature\BlackBox;

use App\Models\AdminActivityLog;
use App\Models\Role;

/**
 * Modul A — Autentikasi & Login (PENGUJIAN_BLACKBOX.md §4.A).
 */
class AuthLoginTest extends BlackBoxTestCase
{
    public function test_tc_login_01_login_benar_diarahkan_sesuai_peran(): void
    {
        $cases = [
            [Role::ADMIN, [], 'admin.index'],
            [Role::MANAGER, [], 'manajer.index'],
            [Role::OPERATIONAL, ['group' => 'A'], 'report-ops.index'],
            [Role::MAINTENANCE, [], 'pemeliharaan.index'],
            [Role::SAFETY, [], 'safety.index'],
        ];

        foreach ($cases as [$roleName, $overrides, $home]) {
            $user = $this->makeUser($roleName, array_merge(['password' => 'password'], $overrides));

            $this->post(route('login.authenticate'), [
                'username' => $user->username,
                'password' => 'password',
            ])->assertRedirect(route($home));

            $this->assertAuthenticatedAs($user);
            $this->post(route('logout'));
        }
    }

    public function test_tc_login_02_password_salah_ditolak(): void
    {
        $user = $this->operator('A', false, ['password' => 'password-benar']);

        $response = $this->from(route('login.index'))->post(route('login.authenticate'), [
            'username' => $user->username,
            'password' => 'password-salah',
        ]);

        $response->assertRedirect(route('login.index'));
        $response->assertSessionHasErrors(['username' => 'Username/email atau password salah.']);
        $this->assertGuest();
    }

    public function test_tc_login_03_username_tidak_terdaftar_ditolak(): void
    {
        $response = $this->from(route('login.index'))->post(route('login.authenticate'), [
            'username' => 'akun-tidak-ada',
            'password' => 'apa-saja',
        ]);

        $response->assertRedirect(route('login.index'));
        $response->assertSessionHasErrors(['username' => 'Username/email atau password salah.']);
        $this->assertGuest();
    }

    public function test_tc_login_04_field_kosong_memunculkan_validasi_wajib(): void
    {
        $response = $this->from(route('login.index'))->post(route('login.authenticate'), [
            'username' => '',
            'password' => '',
        ]);

        $response->assertRedirect(route('login.index'));
        $response->assertSessionHasErrors(['username', 'password']);
        $this->assertGuest();
    }

    public function test_tc_login_05_akun_nonaktif_ditolak(): void
    {
        $user = $this->operator('A', false, [
            'password' => 'password',
            'status' => 'nonaktif',
        ]);

        $response = $this->from(route('login.index'))->post(route('login.authenticate'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['username' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.']);
        $this->assertGuest();
    }

    public function test_tc_login_06_percobaan_berlebih_diblokir_sementara(): void
    {
        $user = $this->operator('A', false, ['password' => 'password-benar']);
        $ip = '10.20.30.40';

        // 5 percobaan gagal pertama: pesan kredensial salah.
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->from(route('login.index'))
                ->post(route('login.authenticate'), [
                    'username' => $user->username,
                    'password' => 'password-salah',
                ])
                ->assertSessionHasErrors(['username' => 'Username/email atau password salah.']);
        }

        // Percobaan ke-6: diblokir sementara.
        $blocked = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from(route('login.index'))
            ->post(route('login.authenticate'), [
                'username' => $user->username,
                'password' => 'password-salah',
            ]);

        $blocked->assertSessionHasErrors('username');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('username')
        );
    }

    public function test_tc_login_07_ingat_saya_menyimpan_remember_token(): void
    {
        $user = $this->operator('A', false, ['password' => 'password']);

        $this->post(route('login.authenticate'), [
            'username' => $user->username,
            'password' => 'password',
            'remember' => 1,
        ])->assertRedirect(route('report-ops.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_tc_login_08_halaman_login_menampilkan_indikator_caps_lock(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="capsHint"', false)
            ->assertSee('Caps Lock aktif', false)
            ->assertSee('name="remember"', false)
            ->assertSee('Ingat Saya', false);
    }

    public function test_tc_login_09_logout_mengakhiri_sesi_dan_kembali_ke_login(): void
    {
        $user = $this->operator('A');

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_tc_login_10_akses_url_terproteksi_saat_belum_login_diarahkan_ke_login(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
        $this->get(route('manajer.index'))->assertRedirect(route('login'));
        $this->get(route('report-ops.index'))->assertRedirect(route('login'));
        $this->get(route('pemeliharaan.index'))->assertRedirect(route('login'));
        $this->get(route('safety.index'))->assertRedirect(route('login'));
    }

    public function test_login_gagal_tercatat_sebagai_kejadian_keamanan(): void
    {
        // Pendukung TC-ALOG-05: login gagal -> log security.
        $this->from(route('login.index'))->post(route('login.authenticate'), [
            'username' => 'siapa-saja',
            'password' => 'salah',
        ]);

        $this->assertTrue(
            AdminActivityLog::where('type', 'security')->exists()
        );
    }
}
