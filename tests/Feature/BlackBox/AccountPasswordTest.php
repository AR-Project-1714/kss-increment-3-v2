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
                ->assertSee('Pengaturan Akun', false)
                ->assertSee('accountPhotoModal', false)
                ->assertSee('data-account-photo-dropzone', false)
                ->assertSee('data-account-photo-progress', false)
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
            $this->assertFileExists(public_path($firstPath));
            $this->assertStringContainsString('profile-photos/', $firstResponse->json('photo_url'));

            $this->actingAs($user)
                ->patchJson(route('account.profile-photo.update'), [
                    'profile_photo' => UploadedFile::fake()->image('profil-pengganti.jpg', 480, 360)->size(500),
                ])
                ->assertOk();

            $secondPath = $user->fresh()->profile_photo_path;
            $createdPaths[] = $secondPath;
            $this->assertNotSame($firstPath, $secondPath);
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
