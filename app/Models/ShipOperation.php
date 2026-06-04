<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipOperation extends Model
{
    public const TYPE_BAG_LOADING = 'muat_kantong';

    public const TYPE_BULK_LOADING = 'muat_curah';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const ACTIVE_SUGGESTION_TTL_DAYS = 3;

    protected $guarded = ['id'];

    /**
     * Hapus saran operasi kapal (muat kantong/curah) yang sudah tidak aktif
     * melewati masa simpan. Dipakai on-request maupun lewat penjadwal.
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
            ->delete();
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
