<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;

    public const DRAFT_TTL_DAYS = 3;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'received_at' => 'datetime',
            'approved_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function loadingActivities()
    {
        return $this->hasMany(LoadingActivity::class);
    }

    public function bulkLoadingActivities()
    {
        return $this->hasMany(BulkLoadingActivity::class);
    }

    public function materialActivity()
    {
        return $this->hasOne(MaterialActivity::class);
    }

    public function containerActivity()
    {
        return $this->hasOne(ContainerActivity::class);
    }

    public function turbaActivity()
    {
        return $this->hasOne(TurbaActivity::class);
    }

    public function unitCheckLogs()
    {
        return $this->hasMany(UnitCheckLog::class);
    }

    public function employeeLogs()
    {
        return $this->hasMany(EmployeeLog::class);
    }
}
