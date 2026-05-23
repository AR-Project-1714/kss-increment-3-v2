<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Model;

class MasterTruck extends Model
{
    use InvalidatesMasterDataCache;

    public const MASTER_DATA_CACHE_KEY = 'master_data.trucks';

    protected $fillable = [
        'name',
        'plate_number',
        'description',
    ];
}
