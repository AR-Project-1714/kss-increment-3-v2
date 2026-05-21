<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterInventoryItem extends Model
{
    use HasFactory;

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
