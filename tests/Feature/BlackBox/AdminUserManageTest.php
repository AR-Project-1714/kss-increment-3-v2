<?php

namespace Tests\Feature\BlackBox;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

/**
 * Modul F — Admin / Kelola Pengguna (PENGUJIAN_BLACKBOX.md §4.F).
 */
class AdminUserManageTest extends BlackBoxTestCase
{
    public function test_tc_ausr_01_tambah_pengguna_lengkap_valid(): void
    {
        $admin = $this->admin();
        $roleId = $this->role('operasional')->id;

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Operator Lengkap',
                'username' => 'operator-lengkap',
                'email' => 'operator-lengkap@example.com',
                'password' => 'password',
                'role_id' => $roleId,
                'group' => 'A',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Pengguna berhasil ditambahkan.');

        $this->assertDatabaseHas('users', [
            'username' => 'operator-lengkap',
            'status' => 'aktif',
        ]);
    }

    public function test_tc_ausr_02_username_duplikat_ditolak(): void
    {
        $admin = $this->admin();
        $existing = $this->operator('A', false, ['username' => 'sudah-ada']);

        $this->actingAs($admin)
            ->from(route('admin.user-manage'))
            ->post(route('admin.users.store'), [
                'name' => 'Duplikat',
                'username' => 'sudah-ada',
                'password' => 'password',
                'role_id' => $this->role('operasional')->id,
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_tc_ausr_03_password_kurang_dari_enam_karakter_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.user-manage'))
            ->post(route('admin.users.store'), [
                'name' => 'Password Pendek',
                'username' => 'password-pendek',
                'password' => '123',
                'role_id' => $this->role('operasional')->id,
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_tc_ausr_04_tanpa_email_dibuat_otomatis(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Tanpa Email',
                'username' => 'tanpa-email',
                'password' => 'password',
                'role_id' => $this->role('operasional')->id,
            ])
            ->assertRedirect();

        $user = User::where('username', 'tanpa-email')->firstOrFail();
        $this->assertNotNull($user->email);
        $this->assertNotEmpty($user->email);
    }

    public function test_tc_ausr_05_tanda_tangan_png_valid_tersimpan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Operator TTD',
                'username' => 'operator-ttd',
                'password' => 'password',
                'role_id' => $this->role('operasional')->id,
                'group' => 'A',
                'signature' => UploadedFile::fake()->create('signature.png', 8, 'image/png'),
            ])
            ->assertRedirect();

        $user = User::where('username', 'operator-ttd')->firstOrFail();
        $this->assertNotNull($user->signature_path);
        $this->assertFileExists(public_path($user->signature_path));
        @unlink(public_path($user->signature_path));
    }

    public function test_tc_ausr_06_tanda_tangan_bukan_png_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.user-manage'))
            ->post(route('admin.users.store'), [
                'name' => 'Operator Salah TTD',
                'username' => 'operator-salah-ttd',
                'password' => 'password',
                'role_id' => $this->role('operasional')->id,
                'signature' => UploadedFile::fake()->create('signature.jpg', 8, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('signature');

        $this->assertDatabaseMissing('users', ['username' => 'operator-salah-ttd']);
    }

    public function test_tc_ausr_07_edit_tanpa_password_tidak_mengubah_password_lama(): void
    {
        $admin = $this->admin();
        $user = $this->operator('A', false, ['password' => 'rahasia-lama']);
        $originalHash = $user->fresh()->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Nama Diperbarui',
                'username' => $user->username,
                'role_id' => $user->role_id,
                'group' => $user->group,
            ])
            ->assertRedirect()
            ->assertSessionMissing('user_credentials');

        $fresh = $user->fresh();
        $this->assertSame('Nama Diperbarui', $fresh->name);
        $this->assertSame($originalHash, $fresh->password, 'Password lama tidak boleh berubah.');
    }

    public function test_tc_ausr_08_toggle_status_dan_akun_nonaktif_tidak_bisa_login(): void
    {
        // Bagian 1 — akun nonaktif tidak bisa login (dijalankan sebagai tamu,
        // sebelum ada sesi admin yang aktif di test client).
        $blocked = $this->operator('A', false, ['password' => 'password', 'status' => 'nonaktif']);
        $this->post(route('login.authenticate'), [
            'username' => $blocked->username,
            'password' => 'password',
        ])->assertSessionHasErrors(['username' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.']);

        // Bagian 2 — toggle status oleh admin berfungsi dua arah.
        $admin = $this->admin();
        $user = $this->operator('B', false, ['password' => 'password']);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $user))
            ->assertRedirect()
            ->assertSessionHas('success', 'Status pengguna berhasil diperbarui.');
        $this->assertSame('nonaktif', $user->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.users.status', $user))->assertRedirect();
        $this->assertSame('aktif', $user->fresh()->status);
    }

    public function test_tc_ausr_09_hapus_pengguna_lain(): void
    {
        $admin = $this->admin();
        $user = $this->operator('A');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect()
            ->assertSessionHas('success', 'Pengguna berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_tc_ausr_10_admin_tidak_bisa_menonaktifkan_atau_menghapus_diri_sendiri(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $admin))
            ->assertSessionHas('error', 'Akun admin yang sedang dipakai tidak bisa dinonaktifkan.');
        $this->assertSame('aktif', $admin->fresh()->status);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('error', 'Akun admin yang sedang dipakai tidak bisa dihapus.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_tc_ausr_11_pencarian_pengguna(): void
    {
        $admin = $this->admin();
        $this->operator('A', false, ['name' => 'Cari Saya Unik', 'username' => 'cari-saya-unik']);
        $this->operator('B', false, ['name' => 'Jangan Muncul', 'username' => 'jangan-muncul']);

        $this->actingAs($admin)
            ->get(route('admin.user-manage', ['q' => 'Cari Saya Unik']))
            ->assertOk()
            ->assertSee('cari-saya-unik', false)
            ->assertDontSee('jangan-muncul', false);
    }

    public function test_tc_ausr_12_role_non_operasional_otomatis_menggunakan_kantor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Petugas Safety Kantor',
                'username' => 'safety-kantor',
                'password' => 'password-baru',
                'role_id' => $this->role('safety')->id,
                'group' => 'D',
            ])
            ->assertRedirect()
            ->assertSessionHas('user_credentials', fn (array $credentials): bool => $credentials['username'] === 'safety-kantor'
                && ! array_key_exists('password', $credentials)
                && Crypt::decryptString($credentials['password_ciphertext']) === 'password-baru');

        $sessionPayload = session('user_credentials');
        $this->assertIsArray($sessionPayload);
        $this->assertStringNotContainsString('password-baru', serialize($sessionPayload));

        $this->assertDatabaseHas('users', [
            'username' => 'safety-kantor',
            'group' => null,
        ]);

        $this->get(route('admin.user-manage'))
            ->assertOk()
            ->assertSee('password-baru', false)
            ->assertSessionMissing('user_credentials');
    }

    public function test_tc_ausr_13_role_operasional_tetap_menyimpan_regu_pilihan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Operator Regu D',
                'username' => 'operator-regu-d',
                'password' => 'password',
                'role_id' => $this->role('operasional')->id,
                'group' => 'D',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'operator-regu-d',
            'group' => 'D',
        ]);
    }

    public function test_tc_ausr_14_reset_password_menampilkan_kredensial_baru_satu_kali(): void
    {
        $admin = $this->admin();
        $user = $this->operator('B', false, ['password' => 'password-lama']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'username' => $user->username,
                'role_id' => $user->role_id,
                'group' => 'B',
                'password' => 'password-baru',
            ])
            ->assertRedirect()
            ->assertSessionHas('user_credentials', fn (array $credentials): bool => $credentials['username'] === $user->username
                && ! array_key_exists('password', $credentials)
                && Crypt::decryptString($credentials['password_ciphertext']) === 'password-baru');

        $sessionPayload = session('user_credentials');
        $this->assertIsArray($sessionPayload);
        $this->assertStringNotContainsString('password-baru', serialize($sessionPayload));

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }
}
