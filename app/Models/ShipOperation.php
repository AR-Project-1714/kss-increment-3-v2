<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipOperation extends Model
{
    public const TYPE_BAG_LOADING = 'muat_kantong';

    public const TYPE_BULK_LOADING = 'muat_curah';

    public const TYPE_AMMONIA_LOADING = 'muat_amoniak';

    public const TYPE_MATERIAL_UNLOADING = 'bongkar_bahan_baku';

    /**
     * Bongkar dan muat container dicatat pada satu bagian form yang sama —
     * pembedanya penanda Empty/Full per baris, bukan kapalnya. Karena itu
     * keduanya berbagi satu jenis operasi kapal.
     */
    public const TYPE_CONTAINER = 'container';

    /**
     * Seluruh jenis operasi kapal. Dipakai sebagai satu-satunya daftar acuan
     * agar penambahan jenis baru tidak perlu disalin ke banyak tempat.
     *
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_BAG_LOADING,
            self::TYPE_BULK_LOADING,
            self::TYPE_AMMONIA_LOADING,
            self::TYPE_MATERIAL_UNLOADING,
            self::TYPE_CONTAINER,
        ];
    }

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_COMPLETED = 'completed';

    public const ACTIVE_SUGGESTION_TTL_DAYS = 3;

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_BAG_LOADING => 'Muat Kantong',
            self::TYPE_BULK_LOADING => 'Muat Curah',
            self::TYPE_AMMONIA_LOADING => 'Muat Amoniak',
            self::TYPE_MATERIAL_UNLOADING => 'Bongkar Bahan Baku',
            self::TYPE_CONTAINER => 'Bongkar/Muat Container',
            default => 'Operasi Kapal',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_INACTIVE => 'Diarsipkan',
            default => 'Masih Berjalan',
        };
    }

    protected $guarded = ['id'];

    /**
     * Arsipkan (bukan hapus) saran operasi lama yang belum memiliki histori
     * keputusan. Operasi yang sudah dikonfirmasi tetap aktif sampai petugas
     * menandainya selesai, termasuk saat ada jeda cuaca atau antrean.
     */
    public static function pruneStaleActiveSuggestions(): int
    {
        $cutoff = now()->subDays(self::ACTIVE_SUGGESTION_TTL_DAYS);

        return static::query()
            ->whereIn('type', self::types())
            ->where('status', self::STATUS_ACTIVE)
            ->whereDoesntHave('decisions')
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

    public function materialActivities()
    {
        return $this->hasMany(MaterialActivity::class);
    }

    public function containerActivities()
    {
        return $this->hasMany(ContainerActivity::class);
    }

    public function decisions()
    {
        return $this->hasMany(ShipOperationDecision::class);
    }

    public function lastReport()
    {
        return $this->belongsTo(DailyReport::class, 'last_report_id');
    }
}
