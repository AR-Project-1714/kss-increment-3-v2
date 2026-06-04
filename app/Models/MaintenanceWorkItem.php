<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceWorkItem extends Model
{
    public const TYPE_UTAMA     = 'utama';
    public const TYPE_PRIORITAS = 'prioritas';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'sort_order'   => 'integer',
        ];
    }

    public function report()
    {
        return $this->belongsTo(MaintenanceReport::class, 'maintenance_report_id');
    }

    public function unit()
    {
        return $this->belongsTo(MasterUnit::class, 'master_unit_id');
    }

    /**
     * Label unit terbaik yang tersedia: relasi master -> snapshot teks.
     */
    public function getUnitDisplayAttribute(): string
    {
        return $this->unit?->display_name ?: (string) ($this->unit_label ?? '');
    }
}
