<?php

namespace App\Console\Commands;

use App\Models\DailyReport;
use App\Models\ShipOperation;
use Illuminate\Console\Command;

class PruneStaleReports extends Command
{
    protected $signature = 'reports:prune-stale';

    protected $description = 'Hapus draft laporan & saran operasi kapal yang sudah kadaluarsa.';

    public function handle(): int
    {
        $drafts = DailyReport::pruneStaleDrafts();
        $ships = ShipOperation::pruneStaleActiveSuggestions();

        $this->info("Draft kadaluarsa dihapus: {$drafts}. Saran operasi kapal kadaluarsa dihapus: {$ships}.");

        return self::SUCCESS;
    }
}
