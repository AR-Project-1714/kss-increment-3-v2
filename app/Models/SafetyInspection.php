<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafetyInspection extends Model
{
    public const CONDITION_BAGUS        = 'bagus';
    public const CONDITION_RUSAK        = 'rusak';
    public const CONDITION_NORMAL       = 'normal';
    public const CONDITION_TIDAK_NORMAL = 'tidak_normal';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'qty'        => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function report()
    {
        return $this->belongsTo(SafetyReport::class, 'safety_report_id');
    }

    public function location()
    {
        return $this->belongsTo(MasterSafetyLocation::class, 'location_id');
    }

    public function item()
    {
        return $this->belongsTo(MasterSafetyItem::class, 'item_id');
    }

    /**
     * Label item terbaik yang tersedia: relasi master -> snapshot teks.
     */
    public function getItemDisplayAttribute(): string
    {
        return $this->item?->name ?: (string) ($this->item_name_snapshot ?? '');
    }

    /**
     * Label lokasi terbaik yang tersedia: relasi master -> snapshot teks.
     */
    public function getLocationDisplayAttribute(): string
    {
        return $this->location?->name ?: (string) ($this->location_name_snapshot ?? '');
    }
}
