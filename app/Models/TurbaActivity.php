<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurbaActivity extends Model
{
    protected $guarded = ['id'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function deliveries()
    {
        return $this->hasMany(TurbaDelivery::class);
    }
}
