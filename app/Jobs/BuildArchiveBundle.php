<?php

namespace App\Jobs;

use App\Models\ArchiveBundle;
use App\Services\ArchiveBundleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Merakit satu bundel ZIP arsip di latar.
 *
 * Dipakai untuk permintaan yang terlalu besar untuk satu request HTTP: laporan
 * yang belum punya cache PDF harus dirender dompdf satu per satu, dan di sini
 * proses itu bebas berjalan lama tanpa membuat browser pengguna menunggu.
 * Progresnya ditulis ke baris ArchiveBundle supaya halaman arsip bisa
 * menampilkan kemajuan lewat polling.
 */
class BuildArchiveBundle implements ShouldQueue
{
    use Queueable;

    /**
     * Bundel besar memang lama; batas satu jam mencegah job menggantung
     * selamanya kalau ada laporan yang bikin dompdf macet.
     */
    public int $timeout = 3600;

    /**
     * Sekali jalan saja: percobaan ulang otomatis hanya akan merender ulang
     * ratusan PDF dari nol. Kegagalan dilaporkan ke pengguna agar bisa
     * mencoba lagi dengan pilihan yang lebih kecil.
     */
    public int $tries = 1;

    public function __construct(public int $bundleId) {}

    public function handle(ArchiveBundleService $service): void
    {
        $bundle = ArchiveBundle::find($this->bundleId);

        if ($bundle === null || $bundle->status === ArchiveBundle::STATUS_READY) {
            return;
        }

        $refs = collect($bundle->refs ?? []);

        if ($refs->isEmpty()) {
            $bundle->update([
                'status' => ArchiveBundle::STATUS_FAILED,
                'error' => 'Tidak ada laporan yang bisa dibundel.',
                'finished_at' => now(),
            ]);

            return;
        }

        Storage::disk(ArchiveBundle::DISK)->makeDirectory(ArchiveBundle::DIRECTORY);

        $relativePath = ArchiveBundle::DIRECTORY.'/'.$bundle->token.'.zip';
        $absolutePath = Storage::disk(ArchiveBundle::DISK)->path($relativePath);

        $bundle->update([
            'status' => ArchiveBundle::STATUS_PROCESSING,
            'processed_reports' => 0,
            'started_at' => now(),
            'error' => null,
        ]);

        try {
            // Progres disimpan hemat: cukup setiap berkas, tapi lewat update
            // kolom tunggal supaya tidak menimpa perubahan lain pada baris.
            $result = $service->writeZip($refs, $absolutePath, function (int $processed) use ($bundle): void {
                ArchiveBundle::whereKey($bundle->id)->update(['processed_reports' => $processed]);
            });
        } catch (Throwable $exception) {
            @unlink($absolutePath);

            Log::error('Bundel arsip gagal dirakit.', [
                'bundle_id' => $bundle->id,
                'message' => $exception->getMessage(),
            ]);

            $bundle->update([
                'status' => ArchiveBundle::STATUS_FAILED,
                'error' => 'Bundel gagal dirakit di server. Silakan coba lagi dengan pilihan lebih kecil.',
                'finished_at' => now(),
            ]);

            return;
        }

        if ($result['added'] === 0) {
            @unlink($absolutePath);

            $bundle->update([
                'status' => ArchiveBundle::STATUS_FAILED,
                // Daftar laporan dibekukan saat permintaan dibuat, jadi laporan
                // bisa saja sudah dihapus sebelum job ini berjalan.
                'error' => $result['matched'] === 0
                    ? 'Laporan yang dipilih sudah tidak ada di arsip saat bundel dirakit.'
                    : 'Tidak ada PDF yang berhasil disiapkan dari laporan terpilih.',
                'skipped_reports' => $result['skipped'],
                'finished_at' => now(),
            ]);

            return;
        }

        $bundle->update([
            'status' => ArchiveBundle::STATUS_READY,
            'processed_reports' => $result['added'] + $result['skipped'],
            'skipped_reports' => $result['skipped'],
            'file_name' => $service->downloadFileName($result['added']),
            'file_path' => $relativePath,
            'file_size' => is_file($absolutePath) ? filesize($absolutePath) : null,
            'finished_at' => now(),
        ]);
    }

    /**
     * Dipanggil saat job gagal total (mis. timeout terlampaui) supaya halaman
     * arsip tidak menampilkan progres yang menggantung selamanya.
     */
    public function failed(?Throwable $exception): void
    {
        ArchiveBundle::whereKey($this->bundleId)
            ->whereIn('status', [ArchiveBundle::STATUS_QUEUED, ArchiveBundle::STATUS_PROCESSING])
            ->update([
                'status' => ArchiveBundle::STATUS_FAILED,
                'error' => 'Bundel gagal dirakit di server. Silakan coba lagi dengan pilihan lebih kecil.',
                'finished_at' => now(),
            ]);
    }
}
