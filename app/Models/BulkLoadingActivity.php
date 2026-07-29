<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkLoadingActivity extends Model
{
    public const TYPE_BULK_LOADING = 'muat_curah';

    public const TYPE_AMMONIA_LOADING = 'muat_amoniak';

    protected $guarded = ['id'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function shipOperation()
    {
        return $this->belongsTo(ShipOperation::class);
    }

    public function logs()
    {
        return $this->hasMany(BulkLoadingLog::class);
    }
}
