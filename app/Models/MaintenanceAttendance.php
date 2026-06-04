<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceAttendance extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function report()
    {
        return $this->belongsTo(MaintenanceReport::class, 'maintenance_report_id');
    }

    public function employee()
    {
        return $this->belongsTo(MasterEmployee::class, 'master_employee_id');
    }
}
