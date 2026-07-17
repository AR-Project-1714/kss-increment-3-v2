<?php

namespace App\Console\Commands;

use App\Models\DailyReport;
use App\Models\ShipOperation;
use Illuminate\Console\Command;

class PruneStaleReports extends Command
{
    protected $signature = 'reports:prune-stale';

    protected $description = 'Hapus draft laporan kadaluarsa & arsipkan saran operasi kapal yang lama tidak dipakai.';

    public function handle(): int
    {
        $drafts = DailyReport::pruneStaleDrafts();
        $ships = ShipOperation::pruneStaleActiveSuggestions();

        $this->info("Draft kadaluarsa dihapus: {$drafts}. Saran operasi kapal diarsipkan: {$ships}.");

        return self::SUCCESS;
    }
}
