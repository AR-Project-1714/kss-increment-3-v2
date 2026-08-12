<?php

namespace App\Services;

use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProfilePhotoService
{
    public const MAX_UPLOAD_KILOBYTES = 10 * 1024;

    public const MAX_SOURCE_DIMENSION = 4096;

    public const MAX_OUTPUT_DIMENSION = 512;

    private const WEBP_QUALITY = 82;

    /** @var array<int, list<string>> */
    private const EXTENSIONS_BY_IMAGE_TYPE = [
        IMAGETYPE_JPEG => ['jpg', 'jpeg'],
        IMAGETYPE_PNG => ['png'],
        IMAGETYPE_WEBP => ['webp'],
    ];

    /**
     * Decode, sanitize, resize, and store an uploaded profile photo as WebP.
     * The original upload is never copied into the public directory.
     */
    public function store(UploadedFile $photo, User $user): string
    {
        $this->ensureWebpSupport();

        $sourcePath = $photo->getRealPath();
        if (! $photo->isValid() || ! is_string($sourcePath) || $sourcePath === '') {
            $this->invalid('Upload foto tidak lengkap atau rusak. Silakan pilih ulang foto.');
        }

        $binary = File::get($sourcePath);
        $imageInfo = @getimagesize($sourcePath);

        if (! is_array($imageInfo)) {
            $this->invalid();
        }

        [$width, $height, $imageType] = $imageInfo;
        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($sourcePath);
        $expectedMime = image_type_to_mime_type($imageType);
        $extension = Str::lower($photo->getClientOriginalExtension());

        if (
            ! isset(self::EXTENSIONS_BY_IMAGE_TYPE[$imageType])
            || ! in_array($extension, self::EXTENSIONS_BY_IMAGE_TYPE[$imageType], true)
            || $detectedMime !== $expectedMime
            || $width < 96
            || $height < 96
            || $width > self::MAX_SOURCE_DIMENSION
            || $height > self::MAX_SOURCE_DIMENSION
        ) {
            $this->invalid();
        }

        $this->rejectEmbeddedScript($binary);

        $source = @imagecreatefromstring($binary);
        if (! $source instanceof GdImage) {
            $this->invalid();
        }

        $source = $this->applyExifOrientation($source, $sourcePath, $imageType);
        $output = null;
        $pendingPath = null;

        try {
            $output = $this->resize($source);
            $directory = public_path('profile-photos');

            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $token = Str::lower(Str::random(24));
            $filename = sprintf('profile-user-%d-%s.webp', $user->id, $token);
            $pendingPath = $directory.DIRECTORY_SEPARATOR.'.pending-'.$token;
            $finalPath = $directory.DIRECTORY_SEPARATOR.$filename;

            if (! imagewebp($output, $pendingPath, self::WEBP_QUALITY)) {
                throw new RuntimeException('GD gagal membuat berkas WebP.');
            }

            $writtenInfo = @getimagesize($pendingPath);
            if (($writtenInfo['mime'] ?? null) !== 'image/webp' || ! File::move($pendingPath, $finalPath)) {
                throw new RuntimeException('Berkas WebP hasil pemrosesan tidak valid.');
            }

            return 'profile-photos/'.$filename;
        } catch (Throwable $exception) {
            if ($pendingPath) {
                File::delete($pendingPath);
            }

            throw $exception;
        } finally {
            imagedestroy($source);
            if ($output instanceof GdImage) {
                imagedestroy($output);
            }
        }
    }

    /**
     * Delete only files created for profile photos. Arbitrary database paths
     * must never become filesystem delete targets.
     */
    public function deleteStored(?string $storedPath): bool
    {
        if (! is_string($storedPath) || preg_match(
            '/\Aprofile-photos\/profile-[a-zA-Z0-9._-]+\.(?:jpe?g|png|webp)\z/',
            $storedPath,
        ) !== 1) {
            return false;
        }

        return File::delete(public_path('profile-photos/'.basename($storedPath)));
    }

    private function resize(GdImage $source): GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, self::MAX_OUTPUT_DIMENSION / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $output = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $output instanceof GdImage) {
            throw new RuntimeException('GD gagal menyiapkan gambar profil.');
        }

        imagealphablending($output, false);
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefill($output, 0, 0, $transparent);

        if (! imagecopyresampled(
            $output,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        )) {
            imagedestroy($output);
            throw new RuntimeException('GD gagal memperkecil gambar profil.');
        }

        return $output;
    }

    private function applyExifOrientation(GdImage $image, string $path, int $imageType): GdImage
    {
        if ($imageType !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, in_array($orientation, [2, 5], true) ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
        }

        $angle = match ($orientation) {
            3 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if (! $rotated instanceof GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    private function rejectEmbeddedScript(string $binary): void
    {
        // Normalisasi null byte menangkap payload sederhana yang disisipkan ke
        // metadata atau ditempelkan setelah akhir data gambar (polyglot).
        $normalized = str_replace("\0", '', $binary);

        foreach (['<?php', '<?=', '<script', 'javascript:', 'data:text/html'] as $marker) {
            if (stripos($normalized, $marker) !== false) {
                $this->invalid('Foto ditolak karena mengandung konten aktif yang tidak aman.');
            }
        }

        if (preg_match('/<\?\s*(?:php\b|=|eval\b|system\b|exec\b|shell_exec\b|passthru\b)/i', $normalized) === 1) {
            $this->invalid('Foto ditolak karena mengandung konten aktif yang tidak aman.');
        }
    }

    private function ensureWebpSupport(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new RuntimeException('Ekstensi PHP GD dengan dukungan WebP wajib tersedia.');
        }
    }

    private function invalid(string $message = 'Isi file tidak cocok dengan gambar JPG, PNG, atau WebP yang aman.'): never
    {
        throw ValidationException::withMessages([
            'profile_photo' => [$message],
        ]);
    }
}
