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

    protected $table = 'master_employees';

    protected $fillable = [
        'npk',
        'name',
        'group_name',
        'position',
        'status',
    ];
}
