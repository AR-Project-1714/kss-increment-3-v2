<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSafetyItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_countable' => 'boolean',
            'is_active'    => 'boolean',
        ];
    }

    public function locations()
    {
        return $this->belongsToMany(MasterSafetyLocation::class, 'master_safety_location_items', 'item_id', 'location_id')
            ->withPivot('default_qty', 'sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
