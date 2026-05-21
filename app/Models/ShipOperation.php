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
