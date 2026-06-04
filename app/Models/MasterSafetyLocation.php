<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSafetyLocation extends Model
{
    public const DATA_CACHE_KEY = 'master_safety_locations.active';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Item template untuk lokasi ini (lokasi -> item + default_qty + urutan).
     */
    public function items()
    {
        return $this->belongsToMany(MasterSafetyItem::class, 'master_safety_location_items', 'location_id', 'item_id')
            ->withPivot('default_qty', 'sort_order')
            ->orderBy('pivot_sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
