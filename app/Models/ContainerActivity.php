<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContainerActivity extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function items()
    {
        return $this->hasMany(ContainerItem::class);
    }
}
