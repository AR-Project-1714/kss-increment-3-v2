<?php

namespace App\Console\Commands;

use App\Services\SystemMetricsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Merekam keadaan sistem hari ini ke system_metric_snapshots.
 *
 * Dijadwalkan sekali sehari (lihat routes/console.php). Storage terpakai dan
 * jumlah pengguna tidak punya jejak historis di tabel mana pun, jadi tanpa
 * rekaman ini dashboard admin tidak bisa menampilkan perubahan antar periode.
 *
 * Opsi --backfill mengisi hari-hari ke belakang untuk keperluan demo. Nilai
 * storage dan pengguna pada hari lampau tidak bisa direkonstruksi, jadi yang
 * ditulis adalah perkiraan menurun dari keadaan sekarang — cukup untuk menguji
 * tampilan, tetapi bukan data historis sungguhan.
 */
class CaptureSystemMetrics extends Command
{
    protected $signature = 'system:snapshot
                            {--backfill= : Isi mundur sejumlah hari dengan angka perkiraan (khusus demo)}';

    protected $description = 'Rekam metrik sistem hari ini untuk pembanding dashboard admin';

    public function handle(SystemMetricsService $metrics): int
    {
        $snapshot = $metrics->capture();

        $this->info(sprintf(
            'Snapshot %s tersimpan: %s pengguna aktif, storage %s, %d aktivitas.',
            $snapshot->captured_on->toDateString(),
            $snapshot->active_users,
            $metrics->formatBytes($snapshot->storage_used_bytes),
            $snapshot->activity_events
        ));

        $backfill = (int) $this->option('backfill');

        if ($backfill > 0) {
            $this->backfill($metrics, $snapshot, $backfill);
        }

        return self::SUCCESS;
    }

    private function backfill(SystemMetricsService $metrics, $today, int $days): void
    {
        $model = $today::class;

        for ($i = 1; $i <= $days; $i++) {
            $date = Carbon::today()->subDays($i);

            // Semakin ke belakang, storage dan jumlah pengguna diperkirakan
            // sedikit lebih kecil. Deretnya dibuat mulus, bukan acak, supaya
            // grafik pembanding tidak terlihat bergerigi tanpa sebab.
            $decay = 1 - ($i * 0.004);

            $model::updateOrCreate(
                ['captured_on' => $date->toDateString()],
                [
                    'storage_used_bytes' => (int) round($today->storage_used_bytes * $decay),
                    'active_users' => max(1, (int) round($today->active_users * (1 - ($i * 0.002)))),
                    'total_users' => max(1, (int) round($today->total_users * (1 - ($i * 0.002)))),
                    'security_events' => 0,
                    'activity_events' => 0,
                    'reports_created' => 0,
                ]
            );
        }

        $this->info("Riwayat perkiraan {$days} hari ke belakang diisi (khusus demo).");
    }
}
