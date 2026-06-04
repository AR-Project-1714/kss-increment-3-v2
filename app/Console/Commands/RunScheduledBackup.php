<?php

namespace App\Console\Commands;

use App\Models\AdminActivityLog;
use App\Services\SystemBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RunScheduledBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Membuat snapshot backup data sistem (dipakai penjadwal backup otomatis).';

    public function handle(SystemBackupService $backup): int
    {
        try {
            $filename = $backup->createSnapshot('otomatis');
        } catch (Throwable $exception) {
            $this->error('Backup otomatis gagal: '.$exception->getMessage());

            AdminActivityLog::create([
                'type' => 'error',
                'description' => 'Backup otomatis terjadwal gagal dibuat.',
                'properties' => ['message' => $exception->getMessage()],
            ]);

            return self::FAILURE;
        }

        $pruned = $backup->pruneAutomaticByRetention($this->retentionDays());

        AdminActivityLog::create([
            'type' => 'backup',
            'description' => 'Backup otomatis terjadwal berhasil dibuat: '.$filename,
            'properties' => ['file' => $filename, 'trigger' => 'schedule', 'pruned_old' => $pruned],
        ]);

        $this->info('Backup otomatis dibuat: '.$filename.($pruned > 0 ? " (menghapus {$pruned} backup lama)" : ''));

        return self::SUCCESS;
    }

    /**
     * Masa retensi (hari) dari pengaturan admin; default 30 hari.
     */
    private function retentionDays(): int
    {
        $retention = '30 Hari';

        if (Storage::disk('local')->exists('admin-backups/schedule.json')) {
            $decoded = json_decode((string) Storage::disk('local')->get('admin-backups/schedule.json'), true);

            if (is_array($decoded) && ! empty($decoded['retention'])) {
                $retention = (string) $decoded['retention'];
            }
        }

        return (int) (preg_replace('/\D+/', '', $retention) ?: 30);
    }
}
