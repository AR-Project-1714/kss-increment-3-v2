<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class AccountController extends Controller
{
    /**
     * Menyimpan atau mengganti foto profil milik pengguna yang sedang masuk.
     */
    public function updateProfilePhoto(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'profile_photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=96,min_height=96,max_width=4096,max_height=4096',
            ],
        ], [
            'profile_photo.required' => 'Pilih foto profil terlebih dahulu.',
            'profile_photo.image' => 'File yang dipilih harus berupa gambar.',
            'profile_photo.mimes' => 'Foto profil harus berformat JPG, PNG, atau WebP.',
            'profile_photo.max' => 'Ukuran foto profil maksimal 2 MB.',
            'profile_photo.dimensions' => 'Resolusi foto harus antara 96×96 dan 4096×4096 piksel.',
        ]);

        $user = $request->user();
        $photo = $validated['profile_photo'];
        $directory = public_path('profile-photos');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = sprintf(
            'profile-%s-%s-%s.%s',
            Str::slug($user->username ?: $user->name) ?: 'user',
            now()->format('YmdHis'),
            Str::lower(Str::random(6)),
            Str::lower($photo->extension() ?: 'jpg')
        );

        $photo->move($directory, $filename);
        $newPath = 'profile-photos/'.$filename;
        $oldPath = $user->profile_photo_path;

        try {
            $user->forceFill(['profile_photo_path' => $newPath])->save();
        } catch (Throwable $exception) {
            File::delete(public_path($newPath));

            throw $exception;
        }

        if ($oldPath && Str::startsWith($oldPath, 'profile-photos/')) {
            File::delete(public_path($oldPath));
        }

        AdminActivityLog::create([
            'user_id' => $user->id,
            'type' => 'update',
            'description' => 'Foto profil akun diperbarui oleh pemilik akun.',
            'ip_address' => $request->ip(),
            'properties' => ['event' => 'self_profile_photo_changed'],
        ]);

        $message = 'Foto profil berhasil diperbarui.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'photo_url' => asset($newPath),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Memperbarui password milik pengguna yang sedang masuk tanpa mengakhiri sesi aktif.
     */
    public function updatePassword(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'different:current_password', 'confirmed'],
        ], [
            'current_password.required' => 'Masukkan password saat ini.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Masukkan password baru.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.max' => 'Password baru maksimal 255 karakter.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
            'password.confirmed' => 'Konfirmasi password baru belum sama.',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        // Ganti ID sesi untuk keamanan, tetapi pertahankan pengguna tetap masuk.
        $request->session()->regenerate();

        AdminActivityLog::create([
            'user_id' => $user->id,
            'type' => 'security',
            'description' => 'Password akun berhasil diperbarui oleh pemilik akun.',
            'ip_address' => $request->ip(),
            'properties' => [
                'event' => 'self_password_changed',
                'user_agent' => (string) $request->userAgent(),
            ],
        ]);

        $message = 'Password berhasil diperbarui.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
