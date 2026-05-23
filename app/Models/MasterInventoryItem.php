<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterInventoryItem extends Model
{
    use HasFactory;
    use InvalidatesMasterDataCache;

    public const MASTER_DATA_CACHE_KEY = 'master_data.inventories';

    protected $table = 'master_inventory_items';

    protected $fillable = [
        'name',
        'category',
        'stock',
        'status',
    ];

    public function checkLogs()
    {
        return $this->hasMany(UnitCheckLog::class, 'master_id', 'id')->where('category', 'inventory');
    }
}
