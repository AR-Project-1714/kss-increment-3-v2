<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\MasterEmployee;
use App\Models\MasterInventoryItem;
use App\Models\MasterTruck;
use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Pembuat snapshot data sistem (JSON). Dipakai oleh aksi backup manual di admin
 * dan oleh command terjadwal (backup:run), supaya logika pembuatan backup tidak
 * lagi terduplikasi antara controller dan penjadwal.
 */
class SystemBackupService
{
    /**
     * Buat satu file snapshot di disk lokal dan kembalikan nama filenya.
     *
     * @param  'manual'|'otomatis'  $type  Penanda jenis backup (memengaruhi nama file & pelabelan di UI).
     * @param  array<string, mixed>|null  $generatedBy  Ringkasan pembuat (null untuk backup terjadwal).
     */
    public function createSnapshot(string $type = 'otomatis', ?array $generatedBy = null): string
    {
        $type = $type === 'manual' ? 'manual' : 'otomatis';
        $filename = 'backup-kss-'.$type.'-'.now()->format('Ymd-His').'.json';

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'generated_by' => $generatedBy,
            'type' => $type,
            'summary' => [
                'users' => User::count(),
                'daily_reports' => DailyReport::count(),
                'master_employees' => MasterEmployee::count(),
                'master_units' => MasterUnit::count(),
                'master_trucks' => MasterTruck::count(),
                'master_inventory_items' => MasterInventoryItem::count(),
            ],
            'data' => [
                'users' => User::with('role:id,name')->get(['id', 'name', 'email', 'username', 'role_id', 'status', 'group', 'created_at', 'updated_at']),
                'daily_reports' => DailyReport::latest()->limit(500)->get(),
                'master_employees' => MasterEmployee::all(),
                'master_units' => MasterUnit::all(),
                'master_trucks' => MasterTruck::all(),
                'master_inventory_items' => MasterInventoryItem::all(),
            ],
        ];

        Storage::disk('local')->put(
            'admin-backups/'.$filename,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        return $filename;
    }

    /**
     * Hapus backup OTOMATIS yang lebih tua dari masa retensi. Backup manual dan
     * arsip tahunan (.zip) sengaja tidak ikut dihapus karena dikelola admin.
     */
    public function pruneAutomaticByRetention(int $days): int
    {
        if ($days < 1) {
            return 0;
        }

        $cutoff = now()->subDays($days)->getTimestamp();
        $deleted = 0;

        foreach (Storage::disk('local')->files('admin-backups') as $path) {
            $name = basename($path);

            if (! str_starts_with($name, 'backup-kss-otomatis-') || ! str_ends_with($name, '.json')) {
                continue;
            }

            if (Storage::disk('local')->lastModified($path) < $cutoff) {
                Storage::disk('local')->delete($path);
                $deleted++;
            }
        }

        return $deleted;
    }
}
