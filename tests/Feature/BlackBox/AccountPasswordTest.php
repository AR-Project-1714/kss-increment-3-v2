<?php

namespace Tests\Feature\BlackBox;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class AccountPasswordTest extends BlackBoxTestCase
{
    public function test_pengaturan_akun_tersedia_di_semua_layout(): void
    {
        $pages = [
            [$this->admin(), 'admin.index'],
            [$this->manager(), 'manajer.index'],
            [$this->operator('A'), 'report-ops.index'],
            [$this->maintenance(), 'pemeliharaan.index'],
            [$this->safety(), 'safety.index'],
        ];

        foreach ($pages as [$user, $routeName]) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('Buka pengaturan akun', false)
                ->assertSee('Profil Pengguna', false)
                ->assertSee('Ubah Password', false)
                ->assertSee('accountPhotoModal', false)
                ->assertSee('data-account-photo-dropzone', false)
                ->assertSee('data-account-photo-progress', false)
                ->assertSee('data-account-photo-delete-open', false)
                ->assertSee(route('account.profile-photo.delete'), false)
                ->assertSee('accountPasswordModal', false);
        }
    }

    public function test_pengguna_tamu_tidak_dapat_mengubah_password(): void
    {
        $this->patchJson(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertUnauthorized();
    }

    public function test_pengguna_tamu_tidak_dapat_menghapus_foto_profil(): void
    {
        $this->deleteJson(route('account.profile-photo.delete'))->assertUnauthorized();
    }

    public function test_pengguna_dapat_mengunggah_dan_mengganti_foto_profil(): void
    {
        $user = $this->operator('A');
        $createdPaths = [];

        try {
            $firstResponse = $this->actingAs($user)
                ->patchJson(route('account.profile-photo.update'), [
                    'profile_photo' => UploadedFile::fake()->image('profil-pertama.png', 320, 320)->size(400),
                ])
                ->assertOk()
                ->assertJsonStructure(['message', 'photo_url']);

            $firstPath = $user->fresh()->profile_photo_path;
            $createdPaths[] = $firstPath;
            $this->assertNotNull($firstPath);
            $this->assertStringEndsWith('.webp', $firstPath);
            $this->assertFileExists(public_path($firstPath));
            $this->assertStringContainsString('profile-photos/', $firstResponse->json('photo_url'));
            $firstImage = getimagesize(public_path($firstPath));
            $this->assertSame('image/webp', $firstImage['mime']);
            $this->assertLessThanOrEqual(512, max($firstImage[0], $firstImage[1]));
            $this->assertLessThan(400 * 1024, File::size(public_path($firstPath)));

            $this->actingAs($user)
                ->patchJson(route('account.profile-photo.update'), [
                    'profile_photo' => UploadedFile::fake()->image('profil-pengganti.jpg', 480, 360)->size(500),
                ])
                ->assertOk();

            $secondPath = $user->fresh()->profile_photo_path;
            $createdPaths[] = $secondPath;
            $this->assertNotSame($firstPath, $secondPath);
            $this->assertStringEndsWith('.webp', $secondPath);
            $this->assertFileDoesNotExist(public_path($firstPath));
            $this->assertFileExists(public_path($secondPath));
        } finally {
            foreach (array_filter($createdPaths) as $path) {
                File::delete(public_path($path));
            }
        }
    }

    public function test_upload_foto_profil_menolak_file_yang_tidak_valid(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->patchJson(route('account.profile-photo.update'), [
                'profile_photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_photo');

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_upload_foto_profil_menerima_gambar_hingga_10_mb_lalu_mengecilkannya(): void
    {
        $user = $this->operator('A');
        $path = null;

        try {
            $this->actingAs($user)
                ->patchJson(route('account.profile-photo.update'), [
                    'profile_photo' => UploadedFile::fake()->image('foto-besar.jpg', 1200, 900)->size(10 * 1024),
                ])
                ->assertOk();

            $path = $user->fresh()->profile_photo_path;
            $this->assertNotNull($path);
            $this->assertStringEndsWith('.webp', $path);
            $this->assertLessThan(10 * 1024 * 1024, File::size(public_path($path)));
            $dimensions = getimagesize(public_path($path));
            $this->assertSame([512, 384], [$dimensions[0], $dimensions[1]]);
        } finally {
            if ($path) {
                File::delete(public_path($path));
            }
        }
    }

    public function test_upload_foto_biasa_dengan_data_vendor_tambahan_tetap_diterima(): void
    {
        $user = $this->operator('A');
        $cleanImage = UploadedFile::fake()->image('foto-ponsel.jpg', 1200, 900);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'kss-profile-vendor-');
        $storedPath = null;

        try {
            // Sejumlah kamera/ponsel menaruh padding atau metadata vendor
            // setelah marker akhir JPEG. Data ini akan hilang saat re-encode.
            File::put(
                $temporaryPath,
                File::get($cleanImage->getPathname())."VENDOR_CAMERA_METADATA\x01\x02"
            );

            $mobilePhoto = new UploadedFile($temporaryPath, 'foto-ponsel.jpg', 'image/jpeg', null, true);

            $this->actingAs($user)
                ->patchJson(route('account.profile-photo.update'), ['profile_photo' => $mobilePhoto])
                ->assertOk();

            $storedPath = $user->fresh()->profile_photo_path;
            $this->assertNotNull($storedPath);
            $this->assertStringEndsWith('.webp', $storedPath);
            $this->assertSame('image/webp', getimagesize(public_path($storedPath))['mime']);
            $this->assertStringNotContainsString(
                'VENDOR_CAMERA_METADATA',
                File::get(public_path($storedPath)),
            );
        } finally {
            File::delete($temporaryPath);
            if ($storedPath) {
                File::delete(public_path($storedPath));
            }
        }
    }

    public function test_upload_foto_profil_menolak_gambar_di_atas_10_mb(): void
    {
        $user = $this->operator('A');

        $this->actingAs($user)
            ->patchJson(route('account.profile-photo.update'), [
                'profile_photo' => UploadedFile::fake()->image('terlalu-besar.jpg', 1200, 900)->size((10 * 1024) + 1),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_photo');

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_upload_foto_profil_menolak_gambar_polyglot_yang_disusupi_script(): void
    {
        $user = $this->manager();
        $cleanImage = UploadedFile::fake()->image('avatar.png', 320, 320);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'kss-profile-');

        try {
            File::put(
                $temporaryPath,
                File::get($cleanImage->getPathname())."<?php system(\$_GET['cmd'] ?? ''); ?>"
            );

            $polyglot = new UploadedFile($temporaryPath, 'avatar.png', 'image/png', null, true);

            $this->actingAs($user)
                ->patchJson(route('account.profile-photo.update'), ['profile_photo' => $polyglot])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('profile_photo');

            $this->assertNull($user->fresh()->profile_photo_path);
        } finally {
            File::delete($temporaryPath);
        }
    }

    public function test_upload_foto_profil_menolak_ekstensi_script_meski_isinya_gambar(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->patchJson(route('account.profile-photo.update'), [
                'profile_photo' => UploadedFile::fake()->image('avatar.php', 320, 320),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_photo');

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_pengguna_dapat_menghapus_foto_profilnya(): void
    {
        $user = $this->operator('A');

        $this->actingAs($user)
            ->patchJson(route('account.profile-photo.update'), [
                'profile_photo' => UploadedFile::fake()->image('avatar.png', 320, 320),
            ])
            ->assertOk();

        $path = $user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        $this->assertFileExists(public_path($path));

        $this->actingAs($user)
            ->deleteJson(route('account.profile-photo.delete'))
            ->assertOk()
            ->assertJson([
                'message' => 'Foto profil berhasil dihapus.',
                'photo_url' => null,
            ]);

        $this->assertNull($user->fresh()->profile_photo_path);
        $this->assertFileDoesNotExist(public_path($path));
        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $user->id,
            'type' => 'update',
            'description' => 'Foto profil akun dihapus oleh pemilik akun.',
        ]);
    }

    public function test_hapus_foto_profil_aman_jika_foto_belum_ada(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->deleteJson(route('account.profile-photo.delete'))
            ->assertOk()
            ->assertJson(['message' => 'Akun belum memiliki foto profil.']);

        $this->assertDatabaseMissing('admin_activity_logs', [
            'user_id' => $user->id,
            'description' => 'Foto profil akun dihapus oleh pemilik akun.',
        ]);
    }

    public function test_hapus_foto_profil_tidak_menghapus_path_di_luar_folder_yang_dikelola(): void
    {
        $user = $this->manager();
        $guardPath = public_path('profile-delete-guard.txt');

        try {
            File::put($guardPath, 'jangan dihapus');
            $user->forceFill(['profile_photo_path' => 'profile-delete-guard.txt'])->save();

            $this->actingAs($user)
                ->deleteJson(route('account.profile-photo.delete'))
                ->assertOk();

            $this->assertNull($user->fresh()->profile_photo_path);
            $this->assertFileExists($guardPath);
        } finally {
            File::delete($guardPath);
        }
    }

    public function test_password_saat_ini_harus_sesuai(): void
    {
        $user = $this->operator('A', false, ['password' => 'password-lama']);

        $this->actingAs($user)
            ->patchJson(route('account.password.update'), [
                'current_password' => 'password-salah',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }

    public function test_konfirmasi_password_baru_harus_sama(): void
    {
        $user = $this->manager(['password' => 'password-lama']);

        $this->actingAs($user)
            ->patchJson(route('account.password.update'), [
                'current_password' => 'password-lama',
                'password' => 'password-baru',
                'password_confirmation' => 'tidak-sama',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_semua_role_dapat_mengubah_password_dan_tetap_masuk(): void
    {
        $users = [
            $this->admin(['password' => 'password-lama']),
            $this->manager(['password' => 'password-lama']),
            $this->operator('A', false, ['password' => 'password-lama']),
            $this->maintenance(['password' => 'password-lama']),
            $this->safety(['password' => 'password-lama']),
        ];

        foreach ($users as $user) {
            $this->actingAs($user)
                ->patchJson(route('account.password.update'), [
                    'current_password' => 'password-lama',
                    'password' => 'password-baru-'.$user->id,
                    'password_confirmation' => 'password-baru-'.$user->id,
                ])
                ->assertOk()
                ->assertJson(['message' => 'Password berhasil diperbarui.']);

            $this->assertAuthenticatedAs($user);
            $this->assertTrue(Hash::check('password-baru-'.$user->id, $user->fresh()->password));
        }
    }
}
