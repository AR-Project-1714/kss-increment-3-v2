<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Master "Data Lingkungan Operasi" — item pemeriksaan Lingkungan Shelter.
 * Dipakai sebagai acuan di form Cek Unit (tab Lingkungan Shelter) dan PDF.
 */
class MasterEnvironmentItem extends Model
{
    use HasFactory;
    use InvalidatesMasterDataCache;

    public const MASTER_DATA_CACHE_KEY = 'master_data.environments';

    protected $table = 'master_environment_items';

    protected $fillable = [
        'name',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function checkLogs()
    {
        return $this->hasMany(UnitCheckLog::class, 'master_id', 'id')->where('category', 'shelter');
    }
}
