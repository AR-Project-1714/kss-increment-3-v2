<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLog extends Model
{
    protected $guarded = ['id'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function employee()
    {
        return $this->belongsTo(MasterEmployee::class, 'master_employee_id');
    }
}
