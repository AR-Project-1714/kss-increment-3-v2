<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterEmployee extends Model
{
    use HasFactory;
    use InvalidatesMasterDataCache;

    public const MASTER_DATA_CACHE_KEY = 'master_data.employees_grouped';
    public const MAINTENANCE_DATA_CACHE_KEY = 'maintenance.master.employees';
    public const MASTER_DATA_CACHE_KEYS = [
        self::MASTER_DATA_CACHE_KEY,
        self::MAINTENANCE_DATA_CACHE_KEY,
    ];

    public const DIVISION_OPERATIONAL = 'operasional';
    public const DIVISION_MAINTENANCE = 'pemeliharaan';
    public const DIVISION_SAFETY = 'safety';
    public const DIVISION_OFFICE = 'office';
    public const DIVISION_BOTH = 'both';

    protected $table = 'master_employees';

    protected $fillable = [
        'npk',
        'name',
        'group_name',
        'position',
        'division',
        'work_time',
        'status',
    ];

    public function employeeLogs()
    {
        return $this->hasMany(EmployeeLog::class, 'master_employee_id');
    }

    public function maintenanceAttendances()
    {
        return $this->hasMany(MaintenanceAttendance::class, 'master_employee_id');
    }

    public function scopeForMaintenance($query)
    {
        return $query->whereIn('division', [self::DIVISION_MAINTENANCE, self::DIVISION_BOTH]);
    }

    public function scopeForOperational($query)
    {
        return $query->whereIn('division', [self::DIVISION_OPERATIONAL, self::DIVISION_BOTH]);
    }
}
