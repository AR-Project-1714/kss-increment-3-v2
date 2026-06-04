<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceUnitCondition extends Model
{
    public const CONDITION_READY = 'ready';
    public const CONDITION_RUSAK = 'rusak';

    protected $guarded = ['id'];

    public function report()
    {
        return $this->belongsTo(MaintenanceReport::class, 'maintenance_report_id');
    }

    public function unit()
    {
        return $this->belongsTo(MasterUnit::class, 'master_unit_id');
    }
}
