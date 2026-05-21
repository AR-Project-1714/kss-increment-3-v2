<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoadingActivity extends Model
{
    protected $guarded = ['id'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function shipOperation()
    {
        return $this->belongsTo(ShipOperation::class);
    }

    public function timesheets()
    {
        return $this->hasMany(LoadingTimesheet::class);
    }
}
