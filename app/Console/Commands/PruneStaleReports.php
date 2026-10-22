<?php

namespace App\Console\Commands;

use App\Models\DailyReport;
use App\Models\ShipOperation;
use App\Services\ShipHandoverService;
use Illuminate\Console\Command;

class PruneStaleReports extends Command
{
    protected $signature = 'reports:prune-stale';

    protected $description = 'Hapus draft laporan kadaluarsa & arsipkan operasi kapal yang lama tidak dipakai atau tidak dilanjutkan regu penerima.';

    public function handle(ShipHandoverService $handovers): int
    {
        $drafts = DailyReport::pruneStaleDrafts();
        $ships = ShipOperation::pruneStaleActiveSuggestions();
        $released = $handovers->releaseAbandoned();

        $this->info("Draft kadaluarsa dihapus: {$drafts}. Saran operasi kapal diarsipkan: {$ships}. Operasi kapal yang tidak dilanjutkan regu penerima diarsipkan: {$released}.");

        return self::SUCCESS;
    }
}
