<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterUnit extends Model
{
    use HasFactory;
    use InvalidatesMasterDataCache;

    public const MASTER_DATA_CACHE_KEY = 'master_data.vehicles';

    protected $table = 'master_units';

    protected $fillable = [
        'name',
        'type',
        'status',
    ];

    public function checkLogs()
    {
        return $this->hasMany(UnitCheckLog::class, 'master_id', 'id')->where('category', 'vehicle');
    }
}
