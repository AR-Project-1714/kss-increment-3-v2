<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipOperation extends Model
{
    public const TYPE_BAG_LOADING = 'muat_kantong';

    public const TYPE_BULK_LOADING = 'muat_curah';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_COMPLETED = 'completed';

    public const ACTIVE_SUGGESTION_TTL_DAYS = 3;

    protected $guarded = ['id'];

    /**
     * Arsipkan (bukan hapus) saran operasi kapal yang tidak diperbarui melewati
     * masa simpan. Operasi kapal bisa jeda beberapa hari (cuaca, antrean jetty),
     * jadi datanya dipertahankan agar akumulasi muat tidak putus — kapal terarsip
     * tetap bisa ditemukan lewat pencarian saran dan aktif kembali saat dipakai.
     * Dipakai on-request maupun lewat penjadwal.
     */
    public static function pruneStaleActiveSuggestions(): int
    {
        $cutoff = now()->subDays(self::ACTIVE_SUGGESTION_TTL_DAYS);

        return static::query()
            ->whereIn('type', [self::TYPE_BAG_LOADING, self::TYPE_BULK_LOADING])
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($query) use ($cutoff): void {
                $query->where('updated_at', '<', $cutoff)
                    ->orWhere(function ($fallback) use ($cutoff): void {
                        $fallback->whereNull('updated_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->update(['status' => self::STATUS_INACTIVE]);
    }

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
            'arrival_time' => 'datetime',
            'berthing_time' => 'datetime',
            'start_loading_time' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_report_date' => 'date',
        ];
    }

    public function loadingActivities()
    {
        return $this->hasMany(LoadingActivity::class);
    }

    public function bulkLoadingActivities()
    {
        return $this->hasMany(BulkLoadingActivity::class);
    }
}
