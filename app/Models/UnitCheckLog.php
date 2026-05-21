<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitCheckLog extends Model
{
    protected $guarded = ['id'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }
}
