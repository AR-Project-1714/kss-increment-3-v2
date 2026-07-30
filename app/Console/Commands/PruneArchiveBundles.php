<?php

namespace App\Console\Commands;

use App\Models\ArchiveBundle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PruneArchiveBundles extends Command
{
    protected $signature = 'archive:prune-bundles';

    protected $description = 'Hapus bundel ZIP arsip yang sudah kedaluwarsa beserta berkasnya.';

    /**
     * Bundel yang macet (queued/processing) lebih lama dari batas ini dianggap
     * gagal — biasanya karena queue worker mati di tengah pengerjaan.
     */
    private const STUCK_HOURS = 2;

    public function handle(): int
    {
        $expired = 0;

        ArchiveBundle::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(function (ArchiveBundle $bundle) use (&$expired): void {
                $bundle->purge();
                $expired++;
            });

        $stuck = ArchiveBundle::query()
            ->whereIn('status', [ArchiveBundle::STATUS_QUEUED, ArchiveBundle::STATUS_PROCESSING])
            ->where('created_at', '<', now()->subHours(self::STUCK_HOURS))
            ->update([
                'status' => ArchiveBundle::STATUS_FAILED,
                'error' => 'Penyiapan bundel terhenti di server. Silakan siapkan ulang.',
                'finished_at' => now(),
            ]);

        $orphans = $this->pruneOrphanFiles();

        $this->info("Bundel kedaluwarsa dihapus: {$expired}. Bundel macet ditandai gagal: {$stuck}. Berkas tanpa induk dihapus: {$orphans}.");

        return self::SUCCESS;
    }

    /**
     * Berkas ZIP yang barisnya sudah hilang (mis. dihapus massal lewat query,
     * yang tidak melewati ArchiveBundle::purge) tetap memakan disk — bersihkan.
     */
    private function pruneOrphanFiles(): int
    {
        $disk = Storage::disk(ArchiveBundle::DISK);

        if (! $disk->exists(ArchiveBundle::DIRECTORY)) {
            return 0;
        }

        $known = ArchiveBundle::whereNotNull('file_path')->pluck('file_path')->all();
        $removed = 0;

        foreach ($disk->files(ArchiveBundle::DIRECTORY) as $path) {
            if (! Str::endsWith($path, '.zip') || in_array($path, $known, true)) {
                continue;
            }

            $disk->delete($path);
            $removed++;
        }

        return $removed;
    }
}
